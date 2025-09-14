<?php

namespace Amplify\System\Commands;

use Amplify\System\Cms\Models\Banner;
use Amplify\System\Cms\Models\BannerZone;
use Amplify\System\Cms\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MovedBannerItemsToHeroSliderTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:moved-banner-items-to-banner-slider-table-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // create a banner zone for hero slider
        $bannerZone = BannerZone::firstOrCreate([
            'name' => 'Home Page Hero Slider',
            'code' => 'banner-slider',
            'fetch_data_from_easyask' => false,
        ]);

        if (Schema::hasTable('hero_sliders')) {
            // run the migration to add zone field
            $this->call('migrate', ['--path' => 'database/migrations/2023_12_29_123553_update_add_columns_in_hero_sliders_table.php']);

            // rename banner_slider table to banner table
            Schema::dropIfExists('banners');

            Schema::rename('hero_sliders', 'banners');
        }

        Banner::all()->each(function ($item) use ($bannerZone) {

            $item->code = Str::slug($item->name);

            if ($item->banner_zone_id == null) {
                $item->banner_zone_id = $bannerZone->id ?? null;
            }

            $item->save();
        });

        // replace hero slider title from page table
        Page::where('content', 'like', '%x-banner-slider%')->get()->each(function ($item) {
            $item->content = str_replace('x-banner-slider', 'x-banner-slider', $item->content);
            $item->save();
        });
    }
}
