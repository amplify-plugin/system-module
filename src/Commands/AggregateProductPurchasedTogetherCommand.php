<?php

namespace Amplify\System\Commands;

use Amplify\System\Jobs\AggregateProductPurchasedTogetherJob;
use Amplify\System\Services\ProductPurchasedTogetherAggregationService;
use Illuminate\Console\Command;

class AggregateProductPurchasedTogetherCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:aggregate-purchased-together {--sync : Run aggregation synchronously in this process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate product co-purchase pairs from order history into product_purchased_together';

    /**
     * Execute the console command.
     */
    public function handle(ProductPurchasedTogetherAggregationService $service): int
    {
        if (! config('amplify.purchased_together.enabled', true)) {
            $this->warn('Product purchased together aggregation is disabled in config.');

            return self::SUCCESS;
        }

        if ($this->option('sync')) {
            $result = config('amplify.purchased_together.use_order_chunks', false)
                ? $service->aggregateWithOrderChunksSync()
                : $service->aggregate();

            $this->table(
                ['Metric', 'Value'],
                collect($result)->map(fn ($value, $key) => [$key, (string) $value])->values()->all()
            );

            return self::SUCCESS;
        }

        AggregateProductPurchasedTogetherJob::dispatch();

        $this->info('Product purchased together aggregation job dispatched to the queue.');

        return self::SUCCESS;
    }
}
