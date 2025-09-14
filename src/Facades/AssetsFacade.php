<?php

namespace Amplify\System\Facades;

use Amplify\System\Support\AssetsLoader;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;

/**
 * @method static void add($asset, string $type, string $group = AssetsLoader::DEFAULT_GROUP, array $attributes = [])
 * @method static string image($path = null)
 * @method static HtmlString js($group = AssetsLoader::DEFAULT_GROUP)
 * @method static HtmlString css($group = AssetsLoader::DEFAULT_GROUP)
 * @method static HtmlString html($group = AssetsLoader::DEFAULT_GROUP)
 * @method static array collection()
 */
class AssetsFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return AssetsLoader::class;
    }
}
