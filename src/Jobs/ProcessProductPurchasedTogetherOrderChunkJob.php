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

class ProcessProductPurchasedTogetherOrderChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int>  $orderIds
     */
    public function __construct(
        public array $orderIds,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ProductPurchasedTogetherAggregationService $service): void
    {
        $result = $service->processOrderChunk($this->orderIds);

        Log::info('Product purchased together order chunk processed.', [
            'order_ids_count' => count($this->orderIds),
            ...$result,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Product purchased together order chunk failed.', [
            'order_ids_count' => count($this->orderIds),
            'message' => $exception->getMessage(),
            'exception' => $exception,
        ]);
    }
}
