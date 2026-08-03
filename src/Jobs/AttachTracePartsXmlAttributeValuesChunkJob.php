<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Attribute;
use Amplify\System\Backend\Models\AttributeProduct;
use Amplify\System\Backend\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttachTracePartsXmlAttributeValuesChunkJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected array $products) {}

    public function handle(): void
    {
        $productCodes = collect($this->products)
            ->pluck('product_code')
            ->filter()
            ->unique()
            ->values();

        $attributeIds = collect($this->products)
            ->flatMap(fn (array $product) => collect($product['attributes'] ?? [])->pluck('attribute_id'))
            ->filter()
            ->unique()
            ->values();

        $products = Product::whereIn('product_code', $productCodes)->get()->keyBy('product_code');
        $attributes = Attribute::whereIn('traceparts_attribute_id', $attributeIds)->pluck('id', 'traceparts_attribute_id');

        foreach ($this->products as $item) {
            $product = $products->get(strtoupper($item['product_code']) ?? null);

            // Never create products from this XML attachment import.
            if (! $product) {
                continue;
            }

            Log::info($item['product_code']);

            DB::transaction(function () use ($item, $product, $attributes): void {
                foreach ($item['attributes'] ?? [] as $attribute) {
                    $attributeId = $attributes->get($attribute['attribute_id'] ?? null);

                    if (! $attributeId) {
                        continue;
                    }

                    AttributeProduct::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'attribute_id' => $attributeId,
                        ],
                        [
                            'attribute_value' => $attribute['attribute_value'],
                            'group' => $attribute['group'] ?? null,
                        ]
                    );
                }
            });
        }
    }
}
