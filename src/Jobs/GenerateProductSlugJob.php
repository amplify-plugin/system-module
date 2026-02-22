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

    /**
     * Create a new job instance.
     */
    public function __construct(public array $ids = [])
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!empty($this->ids)) {
            foreach (Product::select('id', 'product_name', 'product_slug')->whereIn('id', $this->ids)->cursor() as $product) {

                $base = generate_product_slug($product->product_name);

                if (config('amplify.client_code') != "STV") {
                    do {
                        $slug = $base . '-' . Str::lower(Str::random(6));
                        $exists = Product::select('id', 'product_slug')->where('product_slug', $slug)->exists();
                    } while ($exists);
                } else {
                    $slug = $base;
                }

                $product->product_slug = $slug;

                $product->save();
            }
        }
    }
}
