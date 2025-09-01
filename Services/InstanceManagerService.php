<?php

namespace Amplify\System\Backend\Services;

class InstanceManagerService
{
    private static array $RESOLVED = [];

    /**
     * This method will take 2 parameters 1st is the key name and 2nd is optional.
     * 2nd parameter is responsive for if there have no data against the $key, then it will set the 2nd param data againts $key and return it.
     *
     * @return mixed|null
     */
    public function get($key, $setter = '__optional__')
    {
        if ($setter !== '__optional__') {
            $this->set($key, $setter);
        }

        return $this->isExists($key) ? self::$RESOLVED[$key] : null;
    }

    /**
     * This method is responsible for setting data.
     * It has 2 params. 1st is key name and 2nd is data.
     * If data is a closure then it will be lazy load, and 3rd param is for forcefully set/update data.
     *
     * @return void
     */
    public function set($key, $setter, $force = false)
    {
        if (! $this->isExists($key) || $force) {
            self::$RESOLVED[$key] = $setter instanceof \Closure ? $setter() : $setter;
        }
    }

    /**
     * Find key exists or not.
     *
     * @return bool
     */
    public function isExists($key)
    {
        return array_key_exists($key, self::$RESOLVED);
    }
}
