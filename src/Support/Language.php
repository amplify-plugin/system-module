<?php

namespace Amplify\System\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Language extends Collection
{
    public function __construct()
    {
        $locales = config('backpack.crud.locales', []);

        $items = [];

        foreach ($locales as $code => $name) {

            $code = match($code) {
                'en' => 'us',
                'bn' => 'bd',
                default => $code
            };

            $items[] = (object)[
                'name' => $name,
                'code' => $code,
                'flag' => "https://flagsapi.com/".Str::upper($code)."/flat/64.png"
            ];
        }

        parent::__construct($items);
    }
}
