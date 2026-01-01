<?php

namespace Amplify\System\Commands;

use Amplify\System\Backend\Models\Product;
use Amplify\System\Jobs\GenerateProductSlugJob;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AddProductSlugCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:create-product-slug';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create product slug from product name if slug field is empty.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Product::select('id')
            ->whereNull('product_slug')
            ->chunkById(1000, function ($products) {
                $products->chunk(20)->each(function ($group) {
                    GenerateProductSlugJob::dispatch(['products' => $group->pluck('id')->all()]);
                });
            });

        return self::SUCCESS;
    }
}
