<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Product;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $chunk;

    public int $distributorProductIdIndex;

    /**
     * Create a new job instance.
     */
    public function __construct(array $chunk, int $distributorProductIdIndex)
    {
        $this->chunk = $chunk;
        $this->distributorProductIdIndex = $distributorProductIdIndex;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->chunk as $line) {
            $data = str_getcsv($line);

            // Ensure valid CSV structure
            if (count($data) < 1) {
                Log::channel('dds')->warning('Invalid CSV line skipped: '.$line);

                continue;
            }

            $distributorProductId = $data[$this->distributorProductIdIndex];

            DB::beginTransaction();
            try {
                $product = Product::where('product_code', $distributorProductId)->first();

                if ($product) {
                    $product->status = 'archived';
                    $product->archived_at = now();
                    $product->save();
                    Log::channel('dds')->info("Product deleted: product_id = {$distributorProductId}");
                } else {
                    Log::channel('dds')->warning("Product not found for product_id = {$distributorProductId}");
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::channel('dds')->error("Error deleting product: distributor_product_id = {$distributorProductId}, Error: {$e->getMessage()}");
            }
        }
    }
}
