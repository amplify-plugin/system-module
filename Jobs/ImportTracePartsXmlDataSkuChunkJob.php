<?php

namespace Amplify\System\Jobs;

use App\Models\Attribute;
use App\Models\AttributeProduct;
use App\Models\DocumentTypeProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ImportTracePartsXmlDataSkuChunkJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected array $chunk;

    public function __construct(array $chunk)
    {
        $this->chunk = $chunk;
    }

    /**
     * Return the currently authenticated Backpack user id,
     * or look up a fallback by e-mail (config/backpack.php → import_user_email).
     */
    protected function getImportUserId(): int
    {
        if ($id = backpack_auth()->id()) {
            return $id;
        }

        $email = 'raitken@easyask.com';

        return User::where('email', $email)->value('id')
            ?? throw new \RuntimeException("No import user found for email “{$email}”");
    }

    public function handle()
    {
        $userId = $this->getImportUserId();

        foreach ($this->chunk as $item) {
            $product = $this->getUpdatedProduct($item, $userId);
            if (empty($product)) {
                continue;
            }

            DB::transaction(function () use ($item, $product) {

                // Create or update attributes
                foreach ($item['attributes'] as $a) {
                    // get actual attribute ID from the database
                    $attributeId = Attribute::where('traceparts_attribute_id', $a['attribute_id'])
                        ->value('id') ?? null;

                    AttributeProduct::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'attribute_id' => $attributeId,
                        ],
                        [
                            'attribute_value' => $a['attribute_value'],
                            'group' => $a['group'] ?? null,
                        ]
                    );
                }

                // Create or update documents
                $order = 1;
                foreach ($item['documents'] as $doc) {
                    DocumentTypeProduct::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'document_type_id' => 47,
                            'file_path' => $doc['url'],
                        ],
                        [
                            'order' => $order++,
                            'content' => $doc['name'],
                        ]
                    );
                }

                /*SkuProduct::updateOrCreate(
                    [
                        'parent_id' => $item['parent_id'],
                        'sku_id' => $product->id,
                    ],
                    []
                );*/
            });
        }
    }

    private function getUpdatedProduct(mixed &$item, int $userId)
    {
        // Get actual parent ID from the database
        $item['parent_id'] = Product::where('product_code', $item['parent_product_code'])
            ->value('id') ?? null;

        $existingProduct = Product::where('product_code', $item['product_code'])
            ->first();

        if (empty($existingProduct)) {
            // do not create product from traceparts
            return null;
        }

        return $existingProduct;
    }
}
