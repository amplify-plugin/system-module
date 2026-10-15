<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Category;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CloneCategoryAndCreateCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Category $category) {}

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                $subtree = $this->fetchSubtree($this->category->id);

                $mapping = [];
                $newCategories = [];

                // Create all cloned records first (new PKs, tree fields reset, parent not yet linked).
                foreach ($subtree as $original) {
                    $new = $original->replicate();
                    $new->parent_id = null;
                    $new->catalog_id = null;
                    $new->lft = null;
                    $new->rgt = null;
                    $new->depth = null;
                    $new->level = null;
                    $new->is_new = true;
                    $new->is_updated = false;
                    $new->deleted_at = null;
                    $new->save();

                    $mapping[$original->id] = $new->id;
                    $newCategories[$original->id] = $new;
                }

                // Link cloned children to their cloned parents only.
                foreach ($subtree as $original) {
                    $new = $newCategories[$original->id];

                    if ($original->id === $this->category->id) {
                        $new->parent_id = null; // cloned root is an independent root
                    } else {
                        $new->parent_id = $mapping[$original->parent_id];
                    }
                    $new->save();
                }

                // Rebuild nested-set columns for the new tree, appended after the last existing node.
                $currentLft = (int) (Category::query()->max('rgt') ?? 0) + 1;

                $this->assignNestedSet(
                    $this->category,
                    $subtree,
                    $newCategories,
                    $mapping,
                    $currentLft,
                    depth: 1
                );

                // Create exactly one new Catalog named after the cloned root.
                // Inserted via the query builder so Catalog::booted()'s saved hook never
                // fires: no placeholder Category is created and no Category is ever
                // associated with the Catalog (cloned categories keep catalog_id = null).
                $clonedRoot = $newCategories[$this->category->id];
                $catalogName = $clonedRoot->label;

                DB::table('catalogs')->insert([
                    'name' => $catalogName,
                    'slug' => $this->uniqueCatalogSlug($catalogName),
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('CloneCategoryAndCreateCatalogJob failed', [
                'category_id' => $this->category->id,
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CloneCategoryAndCreateCatalogJob failed', [
            'category_id' => $this->category->id ?? null,
            'exception' => $exception,
        ]);
    }

    /**
     * Fetch the selected category and all descendants (any depth) as full models,
     * keyed by id, in lft order so sibling order is preserved.
     */
    private function fetchSubtree(int $rootId): \Illuminate\Support\Collection
    {
        $sql = <<<'SQL'
        WITH RECURSIVE category_tree AS (
            SELECT id FROM categories WHERE id = ?
            UNION ALL
            SELECT c.id FROM categories c
            INNER JOIN category_tree ct ON c.parent_id = ct.id
        )
        SELECT id FROM category_tree
        SQL;

        $ids = collect(DB::select($sql, [$rootId]))->pluck('id')->all();

        return Category::query()
            ->whereIn('id', $ids)
            ->orderBy('lft')
            ->get()
            ->keyBy('id');
    }

    /**
     * Pre-order traversal assigning fresh lft/rgt/depth values so the cloned tree
     * is a valid independent nested set that does not collide with existing nodes.
     */
    private function assignNestedSet(
        Category $original,
        \Illuminate\Support\Collection $subtree,
        array $newCategories,
        array $mapping,
        int &$currentLft,
        int $depth
    ): void {
        $new = $newCategories[$original->id];
        $new->lft = $currentLft++;
        $new->depth = $depth;
        $new->save();

        $children = $subtree->where('parent_id', $original->id)->sortBy('lft');

        foreach ($children as $child) {
            $this->assignNestedSet($child, $subtree, $newCategories, $mapping, $currentLft, $depth + 1);
        }

        $new->rgt = $currentLft++;
        $new->save();
    }

    private function uniqueCatalogSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'catalog';
        $slug = $base;
        $i = 1;

        while (DB::table('catalogs')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
