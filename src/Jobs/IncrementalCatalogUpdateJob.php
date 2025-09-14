<?php

namespace Amplify\System\Jobs;

use Amplify\System\Backend\Models\Attribute;
use Amplify\System\Backend\Models\Brand;
use Amplify\System\Backend\Models\Category;
use Amplify\System\Backend\Models\DocumentType;
use Amplify\System\Backend\Models\Manufacturer;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Backend\Models\ProductClassification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IncrementalCatalogUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public $chunk,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->chunk as $item) {
            DB::beginTransaction();

            try {
                $jsonData = json_decode($item, true);

                if ($jsonData !== null) {
                    $productData = $this->generateProductData($jsonData);

                    $product = Product::updateOrCreate([
                        'product_code' => $jsonData['distributor_product_id'],
                    ], $productData);

                    if (isset($jsonData['primary_image'])) {
                        $primaryImage = $jsonData['primary_image'];
                        $mainImagePaths = [];
                        $syncImageSizes = []; // Array to collect image sizes for syncing

                        // Upload the main primary image
                        if (! empty($primaryImage['uri'])) {
                            $mainImagePaths['main'] = $primaryImage['uri'];
                        }

                        // Collect image sizes for syncing
                        foreach ($primaryImage['sizes'] as $key => $size) {
                            if (empty($size['uri'])) {
                                continue;
                            }

                            $syncImageSizes[] = [
                                'type' => 'primary',
                                'name' => $key,
                                'path' => $size['uri'],
                            ];

                            if ($key === 'thumb') {
                                $mainImagePaths['thumbnail'] = $size['uri'];
                            }
                        }

                        // Update or create the main product image
                        if ($primaryImage['uri']) {
                            $productImage = $product->productImage()->updateOrCreate([], $mainImagePaths);

                            // Delete all previous image sizes for the current product image
                            $productImage->imageSizes()->where('type', 'primary')->delete();

                            // Insert only the new image sizes from the current data
                            foreach ($syncImageSizes as &$imageSize) {
                                $imageSize['product_image_id'] = $productImage->id;
                            }
                            $productImage->imageSizes()->insert($syncImageSizes);
                        }
                    } else {
                        // Remove existing primary images if no primary image exists in import
                        if ($product->productImage) {
                            $product->productImage->delete();
                        }
                    }

                    $additional = [];

                    if (isset($jsonData['alternate_images'])) {
                        $syncAlternateSizes = [];

                        foreach ($jsonData['alternate_images'] as $image) {
                            // Directly use the image URL instead of uploading
                            if (! empty($image['uri'])) {
                                $additional[] = $image['uri'];
                            }

                            // Collect alternate image sizes for syncing
                            foreach ($image['sizes'] as $name => $size) {
                                if (! empty($size['uri'])) {
                                    $syncAlternateSizes[] = [
                                        'type' => 'alternate',
                                        'name' => $name,
                                        'path' => $size['uri'],
                                    ];
                                }
                            }
                        }

                        // Ensure unique additional images
                        $additional = array_unique($additional);

                        // Update or create the product image with additional images
                        $productImage = $product->productImage()->updateOrCreate([], ['additional' => $additional]);

                        // Delete old alternate image sizes and only keep the new ones
                        $productImage->imageSizes()->where('type', 'alternate')->delete();
                        // Insert only the new image sizes from the current data
                        foreach ($syncAlternateSizes as &$imageSize) {
                            $imageSize['product_image_id'] = $productImage->id;
                        }
                        $productImage->imageSizes()->insert($syncAlternateSizes);
                    } else {
                        // Remove existing alternate images if no alternate images exist in import
                        if ($product->productImage) {
                            $product->productImage()->update(['additional' => null]);
                            $product->productImage->imageSizes()->where('type', 'alternate')->delete();
                        }
                    }

                    if (isset($jsonData['videos'])) {
                        $videos = array_reduce($jsonData['videos'], function ($carry, $video) {
                            return array_merge($carry, array_column($video['group_items'], 'uri'));
                        }, []);

                        $allAdditionalItems = array_unique(array_merge($videos, $additional));
                        $product->productImage()->updateOrCreate([], ['additional' => $allAdditionalItems]);
                    }

                    if (isset($jsonData['downloads'])) {
                        $this->createDocument($jsonData['downloads'], $product);
                    }

                    if (isset($jsonData['facets'])) {
                        $this->createAttribute($jsonData['facets'], $product);
                    }

                    if (isset($jsonData['categories'])) {
                        $categoryIds = $this->createCategory($jsonData['categories']);
                        $product->categories()->sync($categoryIds);
                    }

                    if (isset($jsonData['images_360'])) {
                        $this->createImage360($jsonData['images_360'], $product);
                    }
                }
                DB::commit();
                Log::channel('dds')->info('product ID: '.$product->id ?? '');
            } catch (Exception $e) {
                Log::channel('dds')->error('Error inserting product', [
                    'distributor_product_id' => $jsonData['distributor_product_id'] ?? null,
                    'message' => $e->getMessage(),
                ]);
                DB::rollBack();
            }
        }
    }

    private function generateProductData(array $jsonData): array
    {
        $manufacturer_id = $this->createManufacturer($jsonData) ?? null;
        $manufacturerNumber = $this->getManufacturerNumber($jsonData) ?? null;
        $productClassificationId = $this->createProductClassification($jsonData['facets']) ?? null;
        $brand = $this->createOrGetBrand($jsonData) ?? null;

        return [
            'product_name' => $jsonData['name'] ?? $this->getFallBackProductNameOrShortDescription($jsonData),
            //'short_description' => $jsonData['short_description'] ?? $this->getFallBackProductNameOrShortDescription($jsonData),
            'description' => $jsonData['long_description'] ?? null,
            'features' => isset($jsonData['features']) ? json_encode($jsonData['features']) : null,
            'specifications' => isset($jsonData['specifications']) ? json_encode($jsonData['specifications']) : null,
            'upc_number' => $jsonData['upc'] ?? null,
            'user_id' => 1,
            'manufacturer_id' => $manufacturer_id,
            'manufacturer' => $manufacturerNumber,
            'selling_price' => $jsonData['list_price'] ?? null,
            'prop65_message' => $jsonData['prop_65_message'] ?? null,
            'product_classification_id' => $productClassificationId,
            'brand_id'  => $brand['id'] ?? null,
            'brand_name' => $brand['name'] ?? null,
        ];
    }

    private function fileUpload(
        string $fileUrl,
        string $folderName = 'images/products',
        ?string $fileName = null,
        ?bool $coreName = false
    ): bool|string {
        if (empty($fileUrl)) {
            return false;
        }

        $filePath = $this->getFilePath($fileUrl, $folderName, $fileName, $coreName);

        if (Storage::disk('uploads')->exists($filePath)) {
            return $this->getUploadUrl($filePath);
        }

        $imageContent = @file_get_contents($fileUrl);
        if ($imageContent === false) {
            return false;
        }

        $storage = Storage::disk('uploads')->put($filePath, $imageContent);

        return $storage ? $this->getUploadUrl($filePath) : false;
    }

    private function getFilePath(
        string $fileUrl,
        string $folderName,
        ?string $fileName = null,
        ?bool $coreName = false
    ): string {
        $basename = basename($fileUrl);
        $pathInfo = pathinfo($basename);
        $name = $pathInfo['filename'];
        $extension = $pathInfo['extension'];

        $fileName = $fileName ?? $name;
        if ($coreName) {
            $fileName .= '_'.$name;
        }

        return $folderName.'/'.Str::slug($fileName, '_').'.'.$extension;
    }

    private function getUploadUrl(string $filePath): string
    {
        return config('filesystems.disks.uploads.url').'/'.$filePath;
    }

    private function createManufacturer(array $manufacturerData): bool|int
    {
        $manufacturerName = null;
        if (! empty($manufacturerData['distributor_attributes'])) {
            foreach ($manufacturerData['distributor_attributes'] as $attribute) {
                if ($attribute['attribute_key'] === 'distributor_mfr_name' && ! empty($attribute['attribute_value'])) {
                    $manufacturerName = $attribute['attribute_value'];
                    break;
                }
            }
        }
        if (empty($manufacturerName)) {
            return false;
        }

        $imgPath = null;
        if (! empty($manufacturerData['manufacturer']['image']['uri'])) {
            $imgPath = $this->fileUpload(
                fileUrl: $manufacturerData['manufacturer']['image']['uri'],
                folderName: 'images/manufacturers',
                fileName: $manufacturerName
            ) ?? null;
        }

        $manufacturer = Manufacturer::updateOrCreate(
            ['code' => $manufacturerName],
            ['name' => $manufacturerName, 'image' => $imgPath]
        );
        // Remove manufacturer images if no image is present in import
        if (empty($manufacturerData['manufacturer']['image']['uri'])) {
            $manufacturer->update(['image' => null]);
        }

        // Handle image sizes
        if (! empty($manufacturerData['manufacturer']['image']['sizes'])) {
            $syncImageSizes = [];

            foreach ($manufacturerData['manufacturer']['image']['sizes'] as $key => $size) {
                if (! empty($size['uri'])) {
                    $imagePath = $this->fileUpload(
                        fileUrl: $size['uri'],
                        folderName: 'images/manufacturers',
                        fileName: $manufacturerName.'_'.$key
                    );

                    if ($imagePath) {
                        $syncImageSizes[] = [
                            'name' => $key,
                            'path' => $imagePath,
                        ];
                    }
                }
            }

            // Remove all existing image sizes and insert new ones
            $manufacturer->imageSizes()->delete();
            if (! empty($syncImageSizes)) {
                $manufacturer->imageSizes()->createMany($syncImageSizes);
            }
        } else {
            // If no sizes are present in import, remove existing ones
            $manufacturer->imageSizes()->delete();
        }

        return $manufacturer->id;
    }

    private function createProductClassification($productClassificationData): int|bool
    {
        if (empty($productClassificationData)) {
            return false;
        }

        $name = 'All Products';
        $productClassification = ProductClassification::where('title', 'like', '%'.$name.'%')->first();

        if ($productClassification) {
            $productClassificationId = $productClassification->id;
        } else {
            $productClassificationId = ProductClassification::create(['title' => $name])->id;
        }

        return $productClassificationId;
    }

    private function createCategory(array $categories): array
    {
        $categoryIds = [];
        foreach ($categories as $category) {
            $parent_id = isset($category['parent_id']) ? Category::whereCategoryCode($category['parent_id'])->first()?->id : null;

            $categoryIds[] = Category::updateOrCreate(
                ['category_code' => $category['code']],
                ['category_name' => $category['name'], 'parent_id' => $parent_id]
            )->id;
        }

        return $categoryIds;
    }

    private function createDocument(array $documents, Product $product): void
    {
        if (empty($documents)) {
            return;
        }

        foreach ($documents as $document) {
            if (empty($document['group_items'])) {
                continue;
            }

            foreach ($document['group_items'] as $item) {
                if (empty($item)) {
                    continue;
                }

                $mediaType = explode('/', $item['mime_type'])[1];

                if ($mediaType === 'mp4') {
                    $mediaType = 'video';
                }

                $documentType = DocumentType::updateOrCreate(
                    ['name' => $item['display_name'] ?? 'Documents'],
                    ['description' => $item['name'], 'media_type' => $mediaType]
                );

                $filePath = $item['uri'];

                if ($filePath) {
                    $product->documents()->sync([
                        $documentType->id => ['file_path' => $filePath],
                    ]);
                }
            }
        }
    }

    private function createAttribute(array $attributes, Product $product): void
    {
        // Prepare an array to hold the current attribute values
        $currentAttributes = [];

        // Iterate through the provided attributes
        foreach ($attributes as $attribute) {
            if (empty($attribute['name'])) {
                continue;
            }

            // Perform a partial, case-insensitive match for the attribute name
            $existingAttribute = Attribute::where('name', 'LIKE', '%'.$attribute['name'].'%')->first();

            // If the attribute does not exist, create it
            if (! $existingAttribute) {
                $existingAttribute = Attribute::create([
                    'name' => $attribute['name'],
                    'type' => 'text',
                    'is_new' => 1,
                ]);
            }

            // Collect the attribute values for syncing
            foreach ($attribute['values'] as $value) {
                if (! empty($value['value'])) {
                    $currentAttributes[$existingAttribute->id] = ['attribute_value' => $value['value']];
                }
            }
        }

        // Replace old attributes with the current ones
        $product->attributes()->sync($currentAttributes);
    }

    private function createImage360(array $images_360, Product $product): void
    {
        if (empty($images_360)) {
            return;
        }

        $documentType = DocumentType::updateOrCreate(
            ['name' => '360 Image'],
            [
                'description' => '360 Image',
                'media_type' => 'embedded',
            ]
        );

        $product->documents()->sync([
            $documentType->id => ['content' => json_encode($images_360)],
        ]);
    }

    private function createOrGetBrand(array $specifications): ?array
    {
        $brandName = null;

        if (! empty($specifications['distributor_attributes'])) {
            foreach ($specifications['distributor_attributes'] as $distributor_attribute) {
                if ($distributor_attribute['attribute_key'] == 'distributor_brand_name') {
                    $brandName = $distributor_attribute['attribute_value'];
                    break;
                }
            }
        }

        if (! $brandName && ! empty($specifications['specifications'])) {
            foreach ($specifications['specifications'] as $specification) {
                foreach ($specification['group_items'] as $group_item) {
                    if ($group_item['name'] === 'Brand') {
                        $brandName = $group_item['value'];
                        break 2;
                    }
                }
            }
        }

        if (! $brandName && isset($specifications['manufacturer']['name']) &&
            ! empty($specifications['manufacturer']['name'])) {
            $brandName = $specifications['manufacturer']['name'];
        }

        if (! $brandName && ! empty($specifications['distributor_attributes'])) {
            foreach ($specifications['distributor_attributes'] as $distributor_attribute) {
                if ($distributor_attribute['attribute_key'] == 'distributor_manufacturer_name') {
                    $brandName = $distributor_attribute['attribute_value'];
                    break;
                }
            }
        }

        if ( empty($brandName)) {
            return null; // no brand found
        }

        // Handle image upload if exists
        $imgPath = null;
        if (! empty($specifications['manufacturer']['image']['uri'])) {
            $imgPath = $this->fileUpload(
                fileUrl: $specifications['manufacturer']['image']['uri'],
                folderName: 'images/brands',
                fileName: $brandName
            ) ?? null;
        }


        $brand = Brand::updateOrCreate(
            ['title' => $brandName],
            ['image' => $imgPath]
        );

        return [
            'id'   => $brand->id,
            'name' => $brandName,
        ];
    }


    public function getManufacturerNumber($manufacturer): ?string
    {
        if (! empty($manufacturer['manufacturer_catalog_number'])) {
            return $manufacturer['manufacturer_catalog_number'];
        }

        if (! empty($manufacturer['distributor_attributes'])) {
            foreach ($manufacturer['distributor_attributes'] as $distributor_attribute) {
                if ($distributor_attribute['attribute_key'] == 'distributor_mcn') {
                    return $distributor_attribute['attribute_value'];
                }
            }
        }

        return false;
    }

    public function getFallbackProductNameOrShortDescription($jsonData): ?string
    {
        if (! empty($jsonData['distributor_attributes']) && is_array($jsonData['distributor_attributes']) && count($jsonData['distributor_attributes']) > 0) {
            foreach ($jsonData['distributor_attributes'] as $distributor_attribute) {
                if ($distributor_attribute['attribute_key'] == 'distributor_short_description') {
                    return $distributor_attribute['attribute_value'];
                }
            }
        }

        return null;
    }
}
