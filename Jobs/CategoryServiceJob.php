<?php

namespace Amplify\System\Jobs;

use App\Models\Category;
use ErrorException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class CategoryServiceJob extends BaseImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $category;

    public $category_id;

    public $parent_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->className = get_class($this);

        parent::__construct($data);
    }

    protected function getMappingProcessed($aCsv): void
    {
        echo PHP_EOL, "## $this->className :: getMappingProcessed() ##", PHP_EOL, PHP_EOL;

        App::setLocale($this->locale);

        $this->prepareInitialProperty($aCsv);

        $category = Category::query()->find($this->category_id);
        empty($category)
            ? $this->handleCreateOperation($aCsv)
            : $this->handleUpdateOperation($aCsv, $category);

        App::setLocale($this->default_locale);
    }

    protected function handleCreateOperation($aCsv): void
    {
        $this->category = new Category;
        $this->saveDataToDatabase($aCsv);
    }

    protected function handleUpdateOperation($aCsv, $entity): void
    {
        $this->category = $entity;
        $this->is_updating = true;
        $this->saveDataToDatabase($aCsv);
        $this->is_updating = false;
    }

    /**
     * @throws ErrorException
     */
    protected function setParentId($parent_data): void
    {
        if (! empty($parent_data) || (int) $parent_data !== 0) {
            $is_parent_exist = Category::query()
                ->where($this->importDefinition->import_type_field, $parent_data)
                ->first();

            if (empty($is_parent_exist)) {
                throw new ErrorException('Parent not found');
            }
            $this->parent_id = $is_parent_exist->id;
        }
    }

    protected function prepareInitialProperty($aCsv): void
    {
        collect($this->column_mapping)
            ->map(function ($item, $index) use ($aCsv) {
                if ($item->field_or_attribute_name === 'id') {
                    $this->category_id = $aCsv[$index];
                }

                if ($this->importDefinition->has_hierarchy
                    && $this->importDefinition->import_file_field === $item->column_name) {
                    $parent_data = $aCsv[$index];
                    $this->setParentId($parent_data);
                }
            });
    }

    protected function saveDataToDatabase($aCsv): void
    {
        $this->handleColumnMapping();

        if (! $this->is_updating) {
            $this->category->is_new = 1;
        }

        $this->category->is_updated = (bool) $this->is_updating;
        $this->category->parent_id = $this->parent_id ?? null;
        $this->category->save();
    }

    /**
     * @throws ErrorException
     */
    protected function mapToField($item, $value): void
    {
        if ($this->is_updating) {
            if (! empty($value)) {
                $this->category->{$item->field_or_attribute_name} = $value;
            }
        } else {
            if ($item->field_or_attribute_name === 'category_code') {
                $is_category_code_exist = (bool) Category::query()->where('category_code', $value)->first();
                if ($is_category_code_exist) {
                    throw new ErrorException('Category code already exist');
                }
            }

            $this->category->{$item->field_or_attribute_name} = $value;
        }

        if (! empty($this->category->category_name)) {
            $is_category_slug_exist = Category::query()->whereId($this->category->id)->first();
            if (! (bool) $is_category_slug_exist) {
                $this->category->category_slug = getCategorySlug(
                    Str::slug($this->category->category_name),
                    $this->category->id
                );
            }
        }
    }

    protected function mapToAttribute($item, $value): void
    {
        //
    }

    protected function mapToTable($item, $value): void
    {
        //
    }
}
