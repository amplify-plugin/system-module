<?php

namespace Amplify\System\Jobs;

use Amplify\System\Cms\Models\Page;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SiteMapGeneratorJob implements ShouldQueue
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
    public function handle(): void
    {
        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
                xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
                xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
                xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">\n
        XML;

        // Adding products to sitemap.
        Product::orderBy('id', 'ASC')->chunk(200, function ($products) use (&$xml) {
            foreach ($products as $product) {
                $product_url = url('shop/'.$product->product_slug);
                $last_updated = $product->updated_at->tz('UTC')->toAtomString();

                $xml .= <<<XML
                    <url>
                        <loc>$product_url</loc>
                        <lastmod>$last_updated</lastmod>
                        <changefreq>monthly</changefreq>
                        <priority>0.5</priority>
                    </url>\n
                XML;
            }
        });

        // Adding static page to sitemap.
        Page::where('page_type', 'static_page')->orderBy('id', 'ASC')->chunk(200, function ($pages) use (&$xml) {
            foreach ($pages as $page) {
                $page_url = url($page->slug);
                $last_updated = $page->updated_at->tz('UTC')->toAtomString();

                $xml .= <<<XML
                    <url>
                        <loc>$page_url</loc>
                        <lastmod>$last_updated</lastmod>
                        <changefreq>monthly</changefreq>
                        <priority>0.5</priority>
                    </url>\n
                XML;
            }
        });

        $xml .= '</urlset>';

        file_put_contents(public_path('sitemap-test.xml'), $xml);
    }
}
