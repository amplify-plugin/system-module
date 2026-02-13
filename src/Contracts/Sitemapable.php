<?php

namespace Amplify\System\Contracts;

use Amplify\System\Support\Sitemap\Url;

interface Sitemapable
{
    public function toSitemapTag(): Url | string | array;
}
