<?php

namespace Amplify\System\Services;

class JobFailService
{
    /**
     * @var mixed
     */
    public $job;

    /**
     * @var JobFailService
     */
    private static $instance;

    public static function factory(): JobFailService
    {
        if (! self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}
