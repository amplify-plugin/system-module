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
                $newRows = [];

                // Insert clones via the query builder: the Sluggable trait regenerates
                // category_slug from category_name on every Eloquent create/update,
                // which both mangles the cloned slug and can violate the unique slug
                // index. Bypassing model events keeps cloned values deterministic.
                foreach ($subtree as $original) {
                    $attributes = $original->getAttributes();
                    unset($attributes['id'], $attributes['created_at'], $attributes['updated_at']);

                    $attributes['parent_id'] = null;
                    $attributes['catalog_id'] = null;
                    $attributes['lft'] = null;
                    $attributes['rgt'] = null;
                    $attributes['depth'] = null;
                    $attributes['level'] = null;
                    $attributes['is_new'] = true;
                    $attributes['is_updated'] = false;
                    $attributes['deleted_at'] = null;
                    $attributes['category_code'] = $this->uniqueCategoryValue('category_code', $original->category_code);
                    $attributes['category_slug'] = $this->uniqueCategoryValue('category_slug', $original->category_slug);
                    $attributes['created_at'] = now();
                    $attributes['updated_at'] = now();

                    $attributes['id'] = DB::table('categories')->insertGetId($attributes);

                    $mapping[$original->id] = $attributes['id'];
                    $newRows[$original->id] = $attributes;
                }

                // Link cloned children to their cloned parents only; the cloned root
                // stays an independent root (parent_id stays null).
                foreach ($subtree as $original) {
                    if ($original->id === $this->category->id) {
                        continue;
                    }

                    DB::table('categories')
                        ->where('id', $mapping[$original->id])
                        ->update(['parent_id' => $mapping[$original->parent_id]]);
                }

                // Rebuild nested-set columns for the new tree, appended after the last existing node.
                $currentLft = (int) (Category::query()->max('rgt') ?? 0) + 1;

                $this->assignNestedSet($this->category, $subtree, $mapping, $currentLft, depth: 1);

                // Create exactly one new Catalog named after the cloned root, then link
                // the cloned root to it via categories.catalog_id. Inserted via the query
                // builder so Catalog::booted()'s saved hook never fires and no
                // placeholder Category is created.
                $catalogId = DB::table('catalogs')->insertGetId([
                    'name' => $this->category->label,
                    'slug' => $this->uniqueCatalogSlug($this->category->label),
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('categories')
                    ->where('id', $mapping[$this->category->id])
                    ->update(['catalog_id' => $catalogId]);
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
        array $mapping,
        int &$currentLft,
        int $depth
    ): void {
        $cloneId = $mapping[$original->id];

        DB::table('categories')->where('id', $cloneId)->update([
            'lft' => $currentLft++,
            'depth' => $depth,
        ]);

        $children = $subtree->where('parent_id', $original->id)->sortBy('lft');

        foreach ($children as $child) {
            $this->assignNestedSet($child, $subtree, $mapping, $currentLft, $depth + 1);
        }

        DB::table('categories')->where('id', $cloneId)->update([
            'rgt' => $currentLft++,
        ]);
    }

    /**
     * category_code has a unique index and category_slug is expected unique, so
     * cloned values get a numeric suffix: a trailing number is incremented
     * (PARTS-42 -> PARTS-43), otherwise a number is appended (parts -> parts-2).
     * Checks include soft-deleted rows because the unique indexes still apply to them.
     */
    private function uniqueCategoryValue(string $column, ?string $value, ?int $ignoreId = null): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (preg_match('/^(.*?)(\d+)$/u', $value, $m)) {
            $base = $m[1];
            $start = (int) $m[2];
        } else {
            $base = $value.'-';
            $start = 1;
        }

        $i = $start;

        do {
            $candidate = $base.(++$i);

            $query = Category::withTrashed()->where($column, $candidate);

            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }
        } while ($query->exists());

        return $candidate;
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
