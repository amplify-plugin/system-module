<?php

namespace Amplify\System\Jobs\Sitemap;

use Amplify\System\Cms\Models\Menu;
use Amplify\System\Cms\Models\Page;
use Amplify\System\Cms\Models\Sitemap;
use Amplify\System\Sitemap\Tags\Url;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StaticSitemapGenerateJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sitemapFile = \Amplify\System\Support\Sitemap::create();

        foreach (Sitemap::all() as $sitemap) {

            $url = match ($sitemap->mappable_type) {
                Menu::class => $sitemap->mappable->link(),
                Page::class => $sitemap->mappable->full_url,
                default => url('#')
            };

            $urlTag = Url::create($url)
                ->setLastModificationDate($sitemap->updated_at)
                ->setChangeFrequency($sitemap->changefreq ?? URL::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority($sitemap->priority ?? 0.5);

            if ($sitemap->sitemapTags()->exists()) {
                foreach ($sitemap->sitemapTags as $sitemapTag) {
                    $fields = $sitemapTag->fields;
                    switch ($sitemapTag->type) {
                        case 'image':
                        {
                            $urlTag = $urlTag->addImage(
                                url: $fields['url'] ?? '',
                                caption: $fields['description'] ?? '',
                                title: $fields['title'] ?? '');
                            break;
                        }

                        case 'video':
                        {
                            $urlTag = $urlTag->addVideo(
                                thumbnailLoc: $fields['url'] ?? '',
                                title: $fields['title'] ?? '',
                                description: $fields['description'] ?? '',
                                contentLoc: $fields['url'] ?? '',
                                options: [
                                    'family_friendly' => 'yes',
                                    'live' => 'no',

                                ]
                            );
                            break;
                        }

                        case 'news':
                        {
//                        $urlTag = $urlTag->addNews(url: $fields['url'] ?? '', caption: $fields['description'] ?? '', title: $fields['title'] ?? '');
                            break;
                        }
                    }
                }
            }

            $sitemapFile->add($urlTag);
        }

        $sitemapFile->writeToFile(public_path('sitemaps' . DIRECTORY_SEPARATOR . 'customs.xml'));

    }
}
