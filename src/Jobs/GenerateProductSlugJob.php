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
        if (!empty($this->productGroups)) {
            Product::select('id', 'product_name', 'product_slug')->whereIn('id', $this->productGroups)->get()->each(function (Product $product) {
                $base = generate_product_slug($product->product_name);

                if (config('amplify.client_code') != "STV") {
                    do {
                        $slug = $base . '-' . Str::lower(Str::random(6));
                        $exists = Product::select('id', 'product_slug')->where('product_slug', $slug)->exists();
                    } while ($exists);
                } else {
                    $slug = $base . '-' . Str::lower(Str::random(6));
                }

                $product->product_slug = $slug;

                $product->save();
            });
        }
    }
}
