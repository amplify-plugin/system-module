<?php

namespace Amplify\System\Jobs;

use Amplify\System\Services\ProductPurchasedTogetherAggregationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class AggregateProductPurchasedTogetherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ProductPurchasedTogetherAggregationService $service): void
    {
        if (config('amplify.purchased_together.use_order_chunks', false)) {
            $result = $service->dispatchOrderChunkJobs();
        } else {
            $result = $service->aggregate();
        }

        Log::info('Product purchased together aggregation completed.', $result);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Product purchased together aggregation failed.', [
            'message' => $exception->getMessage(),
            'exception' => $exception,
        ]);
    }
}
