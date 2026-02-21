<?php

namespace Amplify\System\Jobs\Sitemap;

use Amplify\System\Backend\Models\Product;
use Amplify\System\Sitemap\Tags\Url;
use Amplify\System\Support\Sitemap;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProductGenerateJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $chunk = 1, public array $ids = [])
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sitemapFile = Sitemap::create();

        foreach (Product::with('productImage')->whereIn('id', $this->ids)->cursor() as $product) {

            $urlTag = Url::create(frontendSingleProductURL($product));

            if (!empty($product?->productImage?->main)) {
                $urlTag = $urlTag->addImage(url: $product->productImage->main, caption: "{$product->product_name} View Image", title: $product->product_name);
            }

            if (!empty($product?->productImage?->thumbnail)) {
                $urlTag = $urlTag->addImage(url: $product->productImage->thumbnail, caption: "{$product->product_name} Thumbnail Image", title: $product->product_name);
            }

            if (!empty($product?->productImage?->small)) {
                $urlTag = $urlTag->addImage(url: $product->productImage->small, caption: "{$product->product_name} Small Image", title: $product->product_name);
            }

            if (!empty($product?->productImage?->medium)) {
                $urlTag = $urlTag->addImage(url: $product->productImage->medium, caption: "{$product->product_name} Medium Image", title: $product->product_name);
            }

            if (!empty($product?->productImage?->large)) {
                $urlTag = $urlTag->addImage(url: $product->productImage->large, caption: "{$product->product_name} Large Image", title: $product->product_name);
            }

            $urlTag = $urlTag->setLastModificationDate($product->updated_at)
                ->setPriority(0.8)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY);

            $sitemapFile->add($urlTag);
        }

        $sitemapFile->writeToFile(public_path('sitemaps' . DIRECTORY_SEPARATOR . "products-{$this->chunk}.xml"));
    }
}
