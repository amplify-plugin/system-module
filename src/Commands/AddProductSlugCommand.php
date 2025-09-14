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
    protected $signature = 'create:product-slug';

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
        $productCollection = Product::whereProductSlug(null)->get()->pluck('id');

        $productCollection->chunk(20)->each(function (Collection $group) {
            GenerateProductSlugJob::dispatch(['products' => array_values($group->toArray())]);
        });

        return self::SUCCESS;
    }
}
