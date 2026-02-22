<?php

namespace Amplify\System\Commands;

use Amplify\System\Backend\Models\Product;
use Amplify\System\Jobs\GenerateProductThumbnailJob;
use Illuminate\Console\Command;

class AddProductThumbnailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:create-product-thumbnail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create product thumbnail from product main image if thumbnail field is empty.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Product::select('id')
            ->whereNull('thumbnail')
            ->chunkById(2000, function ($products) {
                $products->chunk(50)->each(function ($group) {
                    GenerateProductThumbnailJob::dispatch(['products' => $group->pluck('id')->all()]);
                });
            });

        return self::SUCCESS;
    }
}
