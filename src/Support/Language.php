<?php

namespace Amplify\System\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Language extends Collection
{
    use \Illuminate\Support\Traits\ForwardsCalls;

    public function __construct()
    {
        $locales = config('backpack.crud.locales', []);

        $items = [];

        foreach ($locales as $code => $name) {
            $items[] = (object)[
                'name' => $name,
                'code' => $code,
                'flag' => "https://flagsapi.com/".Str::upper($code)."/flat/64.png"
            ];
        }

        parent::__construct($items);
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic($method, $parameters)
    {
        $collection = new static();
        if (method_exists($collection, $method)) {
            return $collection->{$method}(...$parameters);
        }

        if (! static::hasMacro($method)) {
            throw new \BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        $macro = static::$macros[$method];

        if ($macro instanceof \Closure) {
            $macro = $macro->bindTo(null, static::class);
        }

        return $macro(...$parameters);
    }
}
