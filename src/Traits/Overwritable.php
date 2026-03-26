<?php

namespace Amplify\System\Traits;

trait Overwritable
{
    /**
     * The registered string macros.
     *
     * @var array
     */
    protected static $overwrites = [];

    /**
     * Register a custom overwrite.
     *
     * @param  string  $name
     */
    public static function overwrite($name, $overwrite): void
    {
        static::$overwrites[$name] = $overwrite;
    }

    private function processOverwriteCall($method, $parameters, $instance = null)
    {
        $overwrite = static::$overwrites[$method];

        if ($overwrite instanceof \Closure) {
            $overwrite = $overwrite->bindTo($instance ?? $this, static::class);
        }

        return $overwrite(...$parameters);
    }
}
