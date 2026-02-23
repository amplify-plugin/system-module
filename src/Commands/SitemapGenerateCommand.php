<?php

namespace Amplify\System\Commands;

use Amplify\System\Backend\Models\Product;
use Amplify\System\Jobs\Sitemap\CategoryGenerateJob;
use Amplify\System\Jobs\Sitemap\ProductGenerateJob;
use Amplify\System\Jobs\Sitemap\StaticSitemapGenerateJob;
use Amplify\System\Sitemap\SitemapIndex;
use Amplify\System\Sitemap\Tags\Sitemap as SitemapTag;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Throwable;

class SitemapGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:sitemap-generate  {--category-depth=1} {--product-chunk-size=1000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initiate jobs to generate a sitemap';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $categoryDepth = $this->option('category-depth');

        $productChunkSize = $this->option('product-chunk-size');

        try {

            $jobs = [
                new CategoryGenerateJob($categoryDepth),
                new StaticSitemapGenerateJob(),
            ];

            $chunkIndex = 0;

            Product::select('id')
                ->whereNotIn('status', ['draft', 'archived'])
                ->chunkById($productChunkSize, function ($products) use (&$jobs, &$chunkIndex) {
                    $chunkIndex++;
                    $jobs[] = new ProductGenerateJob($chunkIndex, $products->pluck('id')->toArray());
                });

            Bus::batch($jobs)
                ->before(function (Batch $batch) {
                    $gitIgnoreFile = public_path('sitemaps' . DIRECTORY_SEPARATOR . '.gitignore');
                    if (!file_exists($gitIgnoreFile)) {
                        mkdir(dirname($gitIgnoreFile), 777, true);
                        file_put_contents($gitIgnoreFile, <<<HTML
*
!.gitignore

HTML
                        );
                    } else {
                        $files = glob(public_path('sitemaps'. DIRECTORY_SEPARATOR . '*.xml'));
                        foreach ($files as $file) {
                            @unlink($file);
                        }
                    }
                })
                ->catch(function (Batch $batch, Throwable $e) {
                    logger()->error($e);
                    throw_if(!app()->isProduction(), $e);
                })
                ->finally(function (Batch $batch) {
                    $sitemapIndex = SitemapIndex::create();
                    $entries = glob(public_path('sitemaps' . DIRECTORY_SEPARATOR . '*.xml'));
                    foreach ($entries as $entry) {
                        $sitemapIndex->add(
                            SitemapTag::create(\url('sitemaps/' . basename($entry)))
                                ->setLastModificationDate(
                                    Carbon::createFromTimestamp(filemtime($entry))
                                )
                        );
                    }
                    $sitemapIndex->writeToFile(public_path('sitemap.xml'));
                })
                ->onQueue('production')
                ->dispatch();

            return self::SUCCESS;

        } catch (\Exception $exception) {
            $this->error($exception);
            return self::FAILURE;
        }
    }
}
