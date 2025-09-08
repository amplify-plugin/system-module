<?php

namespace Amplify\System\Commands;

use Amplify\System\Backend\Models\Product;
use Amplify\System\Backend\Models\SkuProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TransformProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transform-product';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transform Product';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Product::select('id', 'parent_id')
            ->whereNotNull('parent_id')
            ->chunk(500, function ($products) {
                foreach ($products as $product) {
                    try {
                        SkuProduct::insert([
                            'sku_id' => $product->id,
                            'parent_id' => $product->parent_id,
                        ]);
                    } catch (\Throwable $th) {
                        Log::error($th);
                    }
                }
            });
    }
}
