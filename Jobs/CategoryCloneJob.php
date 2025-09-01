<?php

namespace Amplify\System\Jobs;

use App\Models\Category;
use App\Models\ProductClassification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CategoryCloneJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $new_parent_id = null;

    public $has_children;

    public $is_parent_job;

    public $children;

    public $category_data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($category_data, $is_parent_job = false, $new_parent_id = null)
    {
        $this->category_data = $category_data;
        $this->is_parent_job = $is_parent_job;
        $this->new_parent_id = $new_parent_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->prepareInitialData();
        $this->cloneCategoryToProductClassification();
    }

    public function failed($exception): void
    {
        echo PHP_EOL, PHP_EOL, 'CategoryCloneJob failed :: Error - '.$exception->getMessage(), PHP_EOL, PHP_EOL;

        Log::info('CategoryCloneJob failed', [
            'exception' => $exception,
        ]);
    }

    private function prepareInitialData(): void
    {
        $this->children = Category::query()->where('parent_id', $this->category_data->id)->get();
        $this->has_children = $this->children->count() > 0;
    }

    private function cloneCategoryToProductClassification(): void
    {
        /* Saving product classification to database */
        $productClassification = new ProductClassification;
        $productClassification->title = $this->category_data->category_name;
        $productClassification->parent_id = ! $this->is_parent_job
            ? $this->new_parent_id
            : null;
        $productClassification->level = $this->category_data->level;
        $productClassification->depth = $this->category_data->depth;
        $productClassification->lft = $this->category_data->lft;
        $productClassification->rgt = $this->category_data->rgt;
        $productClassification->save();

        /* If a category has children, then dispatch the job for clone operation */
        if ($this->has_children) {
            foreach ($this->children as $child) {
                self::dispatch($child, false, $productClassification->id);
            }
        }
    }
}
