<?php

namespace Amplify\System\Commands;

use Amplify\System\Backend\Models\Product;
use Amplify\System\Jobs\GenerateProductSlugJob;
use Amplify\System\Jobs\Sitemap\CategoryGenerateJob;
use Amplify\System\Jobs\Sitemap\PageGenerateJob;
use Amplify\System\Jobs\Sitemap\ProductGenerateJob;
use Amplify\System\Sitemap\SitemapIndex;
use Amplify\System\Sitemap\Tags\Url;
use Amplify\System\Sitemap\Tags\Sitemap as SitemapTag;
use Amplify\System\Support\Sitemap;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;

class SitemapGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:sitemap-generate';

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
        try {

            $jobs = [
                new CategoryGenerateJob(2),
                new PageGenerateJob(),
            ];

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
                    }
                })
                ->progress(function (Batch $batch) {
                    logger()->debug('Single Job', $batch->toArray());
                })
                ->then(function (Batch $batch) {
                    //Task Are Done
                })
                ->catch(function (Batch $batch, Throwable $e) {
                    logger()->error($e);
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
                ->dispatch();

            return self::SUCCESS;

        } catch (\Exception $exception) {

            $this->error($exception);

            return self::FAILURE;
        }
    }

    private function createSitemapForStaticContent()
    {
        $sitemap = Sitemap::create();

        $entries = \Amplify\System\Cms\Models\Sitemap::all();

        foreach ($entries as $cat) {
            $sitemap->add(
                Url::create(frontendShopURL($cat['path']))
                    ->addImage(url: asset($cat['image']), caption: $cat['name'], title: $cat['name'], license: 'MIT')
                    ->setLastModificationDate(now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.5)
            );
        }

//        $sitemap->writeToFile($this->sitemapDirectoryPath .
//            DIRECTORY_SEPARATOR .
//            'sitemap-static-content.xml');
    }

    private function createSitemapForProducts(): void
    {
        Product::select('id')
            ->chunkById(5000, function ($products) {
                $products->chunk(1000)->each(function ($group) {
                    GenerateProductSlugJob::dispatch(['products' => $group->pluck('id')->all()]);
                });
            });
    }
}
