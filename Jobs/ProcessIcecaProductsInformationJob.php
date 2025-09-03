<?php

namespace Amplify\System\Jobs;

use Amplify\System\Services\JobFailService;
use Amplify\System\Utility\Models\IcecatTransformationError;
use App\Models\DocumentType;
use App\Models\DocumentTypeProduct;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductImage;
use Carbon\Carbon;
use ErrorException;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Image;

class ProcessIcecaProductsInformationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $productData;

    public $details;

    public $icecatTransformation;

    public $productImage = [
        'product_id' => null,
        'main' => null,
        'thumbnail' => null,
        'additional' => null,
    ];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details, $productData, $icecatTransformation)
    {
        $this->details = $details;
        $this->productData = $productData;
        $this->icecatTransformation = $icecatTransformation;
        $this->productImage['product_id'] = $details['product']->id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $jobFailService = JobFailService::factory();
        $jobFailService->job = $this->job;

        $responseData = $this->getIcecatResponseData();

        $product = Product::find($this->details['product']->product_code);

        $this->updateProductInformation($product, $responseData);

        ProductImage::updateOrCreate(
            ['product_id' => $product->id],
            $this->productImage
        );

        $product->save();

        if (! ($this->icecatTransformation->errors->count() > 0)) {
            $this->icecatTransformation->update([
                'status' => 'success',
            ]);
        }

        $this->icecatTransformation->increment('success_count', 1);
    }

    private function updateProductInformation(&$product, $responseData)
    {
        $this->updateProductImages($product, $responseData);

        $this->updateProductDocumentInfo($product, $responseData);

        $this->updateBrandInfo($product, $responseData);

        $this->updateFeaturesInfo($product, $responseData);

        $this->updateGeneralInfo($product, $responseData);
    }

    public function updateProductDocumentInfo(&$product, $responseData)
    {
        $documents = [];
        if ($responseData->has('Multimedia')) {
            if (optional(optional($this->icecatTransformation)->definition)->documentChecked()
                    && count($responseData['Multimedia'])) {
                $documentTypes = DocumentType::query()->get();

                if (empty($documentTypes)) {
                    return;
                }

                foreach ($responseData['Multimedia'] as $key => $file) {
                    $expectedDocument = $documentTypes->filter(function ($item) use ($file) {
                        return strtolower($item['name']) == strtolower($file->Description);
                    });

                    if ($expectedDocument->isEmpty()) {
                        continue;
                    }

                    $fileUrl = $file->URL;
                    $file_headers = get_headers($fileUrl);

                    if (strpos($file_headers[0], '404') !== true) {
                        $contents = file_get_contents($fileUrl);
                        $ext = pathinfo($fileUrl, PATHINFO_EXTENSION);
                        $fileDetails = getFileDetails('product_document', $product->product_code, $key, $ext);
                        $path = $fileDetails['file_path']."{$product->product_code}-{$key}.{$ext}";
                        Storage::disk('uploads')->put($path, $contents);
                        $documents[] = $this->getDocumentValue($fileDetails['file_url'], $product->id, $expectedDocument->toArray()[0]['id']);
                    }
                }

                $this->updateProductDocuments($documents, $product->id);
            }
        }
    }

    public function getDocumentValue($path, $productId, $documentTypeId)
    {
        return [
            'product_id' => $productId,
            'document_type_id' => $documentTypeId,
            'order' => 1,
            'file_path' => $path,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    public function updateProductDocuments($documents, $productId)
    {
        DocumentTypeProduct::query()->where('product_id', $productId)->delete();
        DocumentTypeProduct::insert($documents);
    }

    private function updateProductMainImage(&$product, $responseData)
    {
        if (property_exists($responseData['Image'], 'HighPic')) {
            if (optional(optional($this->icecatTransformation)->definition)->mainImageChecked()) {
                $file = optional($responseData['Image'])->HighPic;
                $file_headers = get_headers($file);

                if (strpos($file_headers[0], '404') !== true) {
                    $contents = Image::make(optional($responseData['Image'])->HighPic)->encode('jpg', 50);
                    $fileDetails = getFileDetails('product_image', $product->product_code);
                    Storage::disk('uploads')->put($fileDetails['file_path']."{$product->product_code}.jpg", $contents->__toString());
                    $this->productImage['main'] = $fileDetails['file_url'];
                }
            }
        }
    }

    private function updateProductThumbnailImage(&$product, $responseData)
    {
        if (property_exists($responseData['Image'], 'ThumbPic')) {
            if (optional(optional($this->icecatTransformation)->definition)->thumbnailChecked()) {
                $file = optional($responseData['Image'])->ThumbPic;
                $file_headers = get_headers($file);

                if (strpos($file_headers[0], '404') !== true) {
                    $contents = file_get_contents(optional($responseData['Image'])->ThumbPic);
                    $fileDetails = getFileDetails('product_image', $product->product_code, 't');
                    Storage::disk('uploads')->put($fileDetails['file_path']."{$product->product_code}-t.jpg", $contents);
                    $product->thumbnail = $fileDetails['file_url'];
                    $this->productImage['thumbnail'] = $fileDetails['file_url'];
                }
            }
        }
    }

    private function updateProductGalleryImages(&$product, $responseData)
    {
        if (optional(optional($this->icecatTransformation)->definition)->galleryChecked()) {
            $galleryImages = $responseData['Gallery'];
            $images = [];

            foreach ($galleryImages as $key => $file) {
                $picFile = $file->Pic;
                $file_headers = get_headers($picFile);
                $key += 1;
                if (strpos($file_headers[0], '404') !== true) {
                    $contents = Image::make($picFile)->encode('jpg', 50);
                    $fileDetails = getFileDetails('product_image', $product->product_code, $key);
                    Storage::disk('uploads')->put($fileDetails['file_path']."{$product->product_code}-{$key}.jpg", $contents->__toString());
                    $images[] = $fileDetails['file_url'];
                }
            }

            $this->productImage['additional'] = $images;
        }
    }

    private function updateProductImages(&$product, $responseData)
    {
        if ($responseData->has('Image')) {
            if (! $product->product_code) {
                throw new ErrorException('The product does not have product_code.');
            }

            $this->updateProductMainImage($product, $responseData);

            $this->updateProductThumbnailImage($product, $responseData);
        }

        if ($responseData->has('Gallery')) {
            $this->updateProductGalleryImages($product, $responseData);
        }
    }

    private function updateFeaturesInfo(&$product, $responseData)
    {
        if ($responseData->has('FeaturesGroups')) {
            if (optional(optional($this->icecatTransformation)
                ->definition)
                ->featuresChecked()) {
                $product->features = json_encode($responseData['FeaturesGroups']);
            }
        }
    }

    private function updateGeneralInfo(&$product, $responseData)
    {
        if ($responseData->has('GeneralInfo')) {
            if (optional(optional($this->icecatTransformation)->definition)->productNameChecked()) {
                if (property_exists($responseData['GeneralInfo'], 'Title')) {
                    $product->product_name = $responseData['GeneralInfo']->Title;
                }
            }

            if (optional(optional($this->icecatTransformation)->definition)->shortDescriptionChecked()) {
                if (property_exists($responseData['GeneralInfo'], 'SummaryDescription')) {
                    if (property_exists($responseData['GeneralInfo']->SummaryDescription, 'ShortSummaryDescription')) {
                        $product->short_description = $responseData['GeneralInfo']->SummaryDescription->ShortSummaryDescription;
                    }
                }
            }

            if (optional(optional($this->icecatTransformation)->definition)->longDescriptionChecked()) {
                if (property_exists($responseData['GeneralInfo'], 'SummaryDescription')) {
                    if (property_exists($responseData['GeneralInfo']->SummaryDescription, 'LongSummaryDescription')) {
                        $product->description = $responseData['GeneralInfo']->SummaryDescription->LongSummaryDescription;
                    }
                }
            }

            if (optional(optional($this->icecatTransformation)->definition)->brandPartCodeChecked()) {
                if (property_exists($responseData['GeneralInfo'], 'BrandPartCode')) {
                    $product->manufacturer = $responseData['GeneralInfo']->BrandPartCode;   // This is manufacter part code
                }
            }

            if (optional(optional($this->icecatTransformation)->definition)->gtinChecked()) {
                if (property_exists($responseData['GeneralInfo'], 'GTIN')
                    && count($responseData['GeneralInfo']->GTIN) > 0) {
                    $product->gtin_number = $responseData['GeneralInfo']->GTIN[0];
                }
            }
        }
    }

    private function updateBrandInfo(&$product, $responseData)
    {
        if (property_exists($responseData['GeneralInfo'], 'Brand')) {
            if ($product->manufacturer && $this->icecatTransformation?->definition?->brandChecked()) {
                // If product has manufacturer then we are just going to update the information of that product
                $manufacturer = $product->manufacturerr;
                $manufacturer->name = $responseData['GeneralInfo']->Brand;
                $manufacturer->save();
            } elseif (optional(optional($this->icecatTransformation)->definition)->brandChecked()) {
                /**
                 * If product does not have any manufacturer then we are going to create new manufacturer and
                 * assign that id to the
                 */
                $existingManufacturer = Manufacturer::where('code', strtolower($responseData['GeneralInfo']->Brand))
                    ->first();
                // check if there is a manufacturer existing by that code
                if (! $existingManufacturer) {
                    $newManufacturer = Manufacturer::create([
                        'name' => $responseData['GeneralInfo']->Brand,
                        'code' => strtolower($responseData['GeneralInfo']->Brand),
                    ]);

                    $product->manufacturer_id = $newManufacturer->id;
                    $product->save();
                } else {
                    $product->manufacturer_id = $existingManufacturer->id;
                    $product->save();
                    $existingManufacturer->name = $responseData['GeneralInfo']->Brand;
                    $existingManufacturer->save();
                }

                $updatedProduct = Product::find($product->id); // because referenced product is not providing the updated column values

                $this->updateBrandImage($updatedProduct, $responseData);

                return true;
            }
        }

        $this->updateBrandImage($product, $responseData);
    }

    private function updateBrandImage($product, $responseData)
    {
        if ($product->manufacturerr && $responseData->has('GeneralInfo') && property_exists($responseData['GeneralInfo'], 'BrandLogo')) {
            if (optional(optional($this->icecatTransformation)->definition)->brandLogoChecked()) {
                $file = optional($responseData['GeneralInfo'])->BrandLogo;
                $file_headers = get_headers($file);

                if (strpos($file_headers[0], '404') !== true) {
                    $contents = file_get_contents(optional($responseData['GeneralInfo'])->BrandLogo);
                    $filename = strtolower(optional($responseData['GeneralInfo'])->Brand);
                    $fileDetails = getFileDetails('brand_image', $filename, 'logo');

                    Storage::disk('uploads')->put($fileDetails['file_path']."{$filename}-logo.jpg", $contents);
                    $manufacturer = $product->manufacturerr;
                    $manufacturer->image = $fileDetails['file_url'];
                    $manufacturer->save();
                }
            }
        }
    }

    private function getIcecatResponseData()
    {
        $this->checkMandatoryFields();

        $client = new Client;
        $urlToHit = "https://live.icecat.biz/api?UserName={$this->details['icecatUsername']}&Language=en&Content=All&GTIN={$this->details['product']->gtin_number}";
        $response = $client->request('GET', $urlToHit, ['http_errors' => false]);

        if ($response->getStatusCode() != 200) {
            if (! $this->details['icecatUsername'] && ! $this->details['manufacturer'] && ! $this->details['product']->manufacturer) {
                $urlToHit = "https://live.icecat.biz/api?UserName={$this->details['icecatUsername']}&Language=en&Content=All&Brand={$this->details['manufacturer']->code}&ProductCode={$this->details['product']->manufacturer}";
                $response = $client->request('GET', $urlToHit, ['http_errors' => false]);

                if ($response->getStatusCode() != 200) {
                    $this->getResponseError($response, 'Message');
                }
            }

            $this->getResponseError($response, 'Message');
        }

        $responseBody = $response->getBody();

        $responseData = collect(json_decode($responseBody)->data);

        return $responseData;
    }

    private function checkMandatoryFields()
    {
        if (! $this->details['product']->gtin_number) {
            if (! $this->details['manufacturer']) {
                throw new ErrorException('The product does not have a manufacturer and the GTIN is not set also.');
            }

            if (! $this->details['product']->manufacturer) {
                throw new ErrorException('The product neither have an MPN set nor a GTIN number.');
            }
        }
    }

    /**
     * @method getResponseError
     *
     * @param  $message  = 'Error' // Accepted values for $message: Message, Error
     *
     * @throws ErrorException
     */
    private function getResponseError($response, $message = 'Error') // Accepted values for $message: Message, Error
    {
        Log::channel('emergency')->info(json_decode($response->getBody())->Message);

        throw new ErrorException(json_decode($response->getBody())->$message);
    }

    public function failed($exception): void
    {
        echo PHP_EOL, PHP_EOL, 'ExecuteScriptJob failed :: '.$exception->getMessage(), PHP_EOL, PHP_EOL;

        $jobFailService = JobFailService::factory();
        $job = $jobFailService->job;

        Log::channel('emergency')->info('Sorry I failed miserably before storing the error', [
            'icecat_transformation_id' => $this->icecatTransformation->id,
            'icecat_transformation' => json_encode($this->productData),
            'job_name' => get_class($this),
            'uuid' => $job->uuid(),
            'error_message' => $exception,
        ]);

        $this->icecatTransformation->increment('failed_count', 1);

        $icecat_transformation_error = IcecatTransformationError::create([
            'icecat_transformation_id' => $this->icecatTransformation->id,
            'icecat_transformation' => json_encode($this->productData, JSON_THROW_ON_ERROR),
            'job_name' => get_class($this),
            'uuid' => $job->uuid(),
            'error_message' => $exception->getMessage(),
        ]);

        $this->icecatTransformation->update([
            'status' => 'failed',
        ]);

        Log::channel('emergency')->info('Sorry I failed miserably after storing the error', [
            'db_data' => $icecat_transformation_error,
        ]);
    }
}
