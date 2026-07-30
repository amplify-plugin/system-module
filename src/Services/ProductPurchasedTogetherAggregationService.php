<?php

namespace Amplify\System\Services;

use Amplify\System\Backend\Models\CustomerOrder;
use Amplify\System\Backend\Models\ProductPurchasedTogether;
use Amplify\System\Jobs\ProcessProductPurchasedTogetherOrderChunkJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductPurchasedTogetherAggregationService
{
    /**
     * Run a full rebuild: truncate existing rows, aggregate from order history, store results.
     *
     * @return array{orders_processed: int, pairs_stored: int, mode: string}
     */
    public function aggregate(): array
    {
        if (! config('amplify.purchased_together.enabled', true)) {
            Log::info('Product purchased together aggregation skipped (disabled in config).');

            return [
                'orders_processed' => 0,
                'pairs_stored' => 0,
                'mode' => 'disabled',
            ];
        }

        $this->truncate();

        if (config('amplify.purchased_together.use_order_chunks', false)) {
            return $this->aggregateViaOrderChunks();
        }

        $pairCounts = $this->computePairCountsViaSql();
        $pairsStored = $this->storePairCounts($pairCounts);

        return [
            'orders_processed' => $this->countEligibleOrders(),
            'pairs_stored' => $pairsStored,
            'mode' => 'sql',
        ];
    }

    /**
     * Process all eligible orders in chunks synchronously (no queue).
     *
     * @return array{orders_processed: int, pairs_stored: int, mode: string, chunks_processed: int}
     */
    public function aggregateWithOrderChunksSync(): array
    {
        $this->truncate();

        $orderIds = $this->eligibleOrdersQuery()->pluck('id')->all();
        $chunkSize = (int) config('amplify.purchased_together.order_chunk_size', 500);
        $chunks = array_chunk($orderIds, $chunkSize);

        $pairsStored = 0;
        $ordersProcessed = 0;

        foreach ($chunks as $chunk) {
            $result = $this->processOrderChunk($chunk);
            $pairsStored += $result['pairs_upserted'];
            $ordersProcessed += $result['orders_processed'];
        }

        return [
            'orders_processed' => $ordersProcessed,
            'pairs_stored' => $pairsStored,
            'mode' => 'order_chunks_sync',
            'chunks_processed' => count($chunks),
        ];
    }

    /**
     * Truncate aggregation table and dispatch queued jobs per order chunk.
     *
     * @return array{orders_processed: int, pairs_stored: int, mode: string, chunks_dispatched: int}
     */
    public function dispatchOrderChunkJobs(bool $truncate = true): array
    {
        if (! config('amplify.purchased_together.enabled', true)) {
            return [
                'orders_processed' => 0,
                'pairs_stored' => 0,
                'mode' => 'disabled',
                'chunks_dispatched' => 0,
            ];
        }

        if ($truncate) {
            $this->truncate();
        }

        $orderIds = $this->eligibleOrdersQuery()->pluck('id')->all();
        $chunkSize = (int) config('amplify.purchased_together.order_chunk_size', 500);
        $chunks = array_chunk($orderIds, $chunkSize);

        foreach ($chunks as $chunk) {
            ProcessProductPurchasedTogetherOrderChunkJob::dispatch($chunk);
        }

        return [
            'orders_processed' => count($orderIds),
            'pairs_stored' => 0,
            'mode' => 'order_chunks',
            'chunks_dispatched' => count($chunks),
        ];
    }

    /**
     * Process a batch of order IDs and upsert pair counts (used by chunk jobs).
     *
     * @param  array<int>  $orderIds
     * @return array{pairs_upserted: int, orders_processed: int}
     */
    public function processOrderChunk(array $orderIds): array
    {
        if ($orderIds === []) {
            return [
                'pairs_upserted' => 0,
                'orders_processed' => 0,
            ];
        }

        $orders = CustomerOrder::query()
            ->whereIn('id', $orderIds)
            ->with(['orderLines' => fn ($query) => $query->whereNotNull('product_id')])
            ->get();

        $pairCounts = $this->countPairsFromOrders($orders);
        $pairsUpserted = $this->incrementPairCounts($pairCounts);

        return [
            'pairs_upserted' => $pairsUpserted,
            'orders_processed' => $orders->count(),
        ];
    }

    public function truncate(): void
    {
        ProductPurchasedTogether::query()->delete();
    }

    /**
     * @param  Collection<int, CustomerOrder>  $orders
     * @return array<string, int>
     */
    public function countPairsFromOrders(Collection $orders): array
    {
        $pairCounts = [];

        foreach ($orders as $order) {
            $productIds = $order->orderLines
                ->pluck('product_id')
                ->unique()
                ->sort()
                ->values();

            if ($productIds->count() < 2) {
                continue;
            }

            for ($i = 0; $i < $productIds->count(); $i++) {
                for ($j = $i + 1; $j < $productIds->count(); $j++) {
                    $key = $productIds[$i].':'.$productIds[$j];
                    $pairCounts[$key] = ($pairCounts[$key] ?? 0) + 1;
                }
            }
        }

        return $pairCounts;
    }

    /**
     * @param  array<string, int>  $pairCounts
     */
    public function storePairCounts(array $pairCounts): int
    {
        if ($pairCounts === []) {
            return 0;
        }

        $now = now();
        $rows = [];

        foreach ($pairCounts as $key => $count) {
            [$productIdA, $productIdB] = array_map('intval', explode(':', $key));

            $rows[] = [
                'product_id_a' => $productIdA,
                'product_id_b' => $productIdB,
                'occurrence_count' => $count,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $this->insertRows($rows);
    }

    /**
     * @param  array<string, int>  $pairCounts
     */
    public function incrementPairCounts(array $pairCounts): int
    {
        if ($pairCounts === []) {
            return 0;
        }

        $connection = (new ProductPurchasedTogether)->getConnectionName();
        $now = now()->toDateTimeString();
        $upserted = 0;

        foreach ($pairCounts as $key => $count) {
            [$productIdA, $productIdB] = array_map('intval', explode(':', $key));

            DB::connection($connection)->insert(
                'INSERT INTO product_purchased_together (product_id_a, product_id_b, occurrence_count, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    occurrence_count = occurrence_count + VALUES(occurrence_count),
                    updated_at = VALUES(updated_at)',
                [$productIdA, $productIdB, $count, $now, $now]
            );

            $upserted++;
        }

        return $upserted;
    }

    /**
     * @return array<string, int>
     */
    protected function computePairCountsViaSql(): array
    {
        $query = $this->buildAggregationQuery();
        $results = DB::connection($this->orderConnection())->select($query['sql'], $query['bindings']);

        $pairCounts = [];

        foreach ($results as $row) {
            $pairCounts["{$row->product_id_a}:{$row->product_id_b}"] = (int) $row->occurrence_count;
        }

        return $pairCounts;
    }

    /**
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    protected function buildAggregationQuery(): array
    {
        $statuses = (array) config('amplify.purchased_together.eligible_order_statuses', []);
        $statusPlaceholders = implode(', ', array_fill(0, count($statuses), '?'));

        $bindings = [
            config('amplify.purchased_together.order_type', CustomerOrder::IS_ORDER_TYPE),
            ...$statuses,
        ];

        $lookbackSql = '';

        if ($since = $this->lookbackSince()) {
            $lookbackSql = ' AND o.created_at >= ?';
            $bindings[] = $since->toDateTimeString();
        }

        $sql = "
            SELECT
                LEAST(l1.product_id, l2.product_id) AS product_id_a,
                GREATEST(l1.product_id, l2.product_id) AS product_id_b,
                COUNT(DISTINCT l1.customer_order_id) AS occurrence_count
            FROM customer_order_lines l1
            INNER JOIN customer_order_lines l2
                ON l1.customer_order_id = l2.customer_order_id
                AND l1.product_id < l2.product_id
            INNER JOIN customer_orders o
                ON o.id = l1.customer_order_id
            WHERE l1.product_id IS NOT NULL
                AND l2.product_id IS NOT NULL
                AND o.order_type = ?
                AND o.order_status IN ({$statusPlaceholders})
                {$lookbackSql}
            GROUP BY product_id_a, product_id_b
        ";

        return [
            'sql' => $sql,
            'bindings' => $bindings,
        ];
    }

    protected function eligibleOrdersQuery(): Builder
    {
        $query = CustomerOrder::query()
            ->where('order_type', config('amplify.purchased_together.order_type', CustomerOrder::IS_ORDER_TYPE))
            ->whereIn('order_status', config('amplify.purchased_together.eligible_order_statuses', []));

        if ($since = $this->lookbackSince()) {
            $query->where('created_at', '>=', $since);
        }

        return $query;
    }

    protected function countEligibleOrders(): int
    {
        return $this->eligibleOrdersQuery()->count();
    }

    /**
     * @return array{orders_processed: int, pairs_stored: int, mode: string, chunks_dispatched: int}
     */
    protected function aggregateViaOrderChunks(): array
    {
        return $this->dispatchOrderChunkJobs(truncate: false);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function insertRows(array $rows): int
    {
        $insertChunkSize = (int) config('amplify.purchased_together.insert_chunk_size', 1000);
        $stored = 0;

        foreach (array_chunk($rows, $insertChunkSize) as $chunk) {
            ProductPurchasedTogether::query()->insert($chunk);
            $stored += count($chunk);
        }

        return $stored;
    }

    protected function orderConnection(): ?string
    {
        return (new CustomerOrder)->getConnectionName();
    }

    protected function lookbackSince(): ?Carbon
    {
        $months = config('amplify.purchased_together.months_lookback');

        if ($months === null || $months === '') {
            return null;
        }

        return now()->subMonths((int) $months);
    }
}
