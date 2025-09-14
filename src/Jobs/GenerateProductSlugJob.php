<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;

class GenerateProductSlugJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $productGroups = [];

    /**
     * Create a new job instance.
     */
    public function __construct($productGroups)
    {
        $this->productGroups = $productGroups['products'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (! empty($this->productGroups)) {
            Product::whereIn('id', $this->productGroups)->get()->each(function (Product $product) {
                $product->product_slug = Str::slug($product->product_name);
                $product->save();
            });
        }
    }
}
