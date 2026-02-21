<?php

namespace Amplify\System\Jobs\Sitemap;

use Amplify\System\Sayt\Facade\Sayt;
use Amplify\System\Sitemap\Tags\Url;
use Amplify\System\Support\Sitemap;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CategoryGenerateJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $depth = 1)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $categories = [];

        $entries = Sayt::storeCategories(options: [
            'with_sub_category' => true,
            'sub_category_depth' => $this->depth,
        ])->getCategories();

        foreach ($entries as $node) {
            $categories[] = [
                'name' => $node->getName(),
                'path' => $node->getSEOPath(),
                'image' => $node->getImage()
            ];
            $subs = $node->getSubCategories();
            if (!empty($subs)) {
                $this->collectCategories($subs, $categories);
            }
        }

        $sitemap = Sitemap::create();

        foreach ($categories as $cat) {
            $sitemap->add(
                Url::create(frontendShopURL($cat['path']))
                    ->addImage(url: asset($cat['image']), caption: $cat['name'], title: $cat['name'], license: 'MIT')
                    ->setLastModificationDate(now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.5)
            );
        }

        $sitemap->writeToFile(public_path('sitemaps' . DIRECTORY_SEPARATOR . 'categories.xml'));
    }

    private function collectCategories($nodes, &$cat = []): void
    {
        if (!empty($nodes)) {
            foreach ($nodes as $node) {
                $cat[] = [
                    'name' => $node->getName(),
                    'path' => $node->getSEOPath(),
                    'image' => $node->getImage()
                ];
                $subs = $node->getSubCategories();
                if (!empty($subs)) {
                    $this->collectCategories($node->getSubCategories(), $cat);
                }
            }
        }
    }
}
