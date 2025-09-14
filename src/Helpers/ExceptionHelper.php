<?php

namespace Amplify\System\Helpers;

use Illuminate\Support\Str;
use Throwable;

class ExceptionHelper
{
    public static array $response;

    public static Throwable $exception;

    public function __construct($exception)
    {
        self::$exception = $exception;
    }

    public static function init(): ExceptionHelper
    {
        self::$response = [
            'message' => self::$exception->getMessage(),
            'code' => self::$exception->getCode(),
            'file' => self::$exception->getFile(),
            'line' => self::$exception->getLine(),
            'trace' => self::$exception->getTraceAsString(),
        ];

        return new static(self::$exception);
    }

    /**
     * @return void
     */
    public static function handleError(?array $handleErrorFor = null)
    {
        foreach ($handleErrorFor as $key => $errorFor) {
            self::$key($errorFor);
        }
    }

    /**
     * @return void
     */
    public static function db($codes)
    {
        foreach ($codes as $code) {
            if ($code === self::$exception->getCode()
                || Str::contains(self::$exception->getMessage(), "[$code]")
                || Str::contains(self::$exception->getMessage(), " $code Table")
            ) {
                $method = "code_$code";
                self::$method();
            }
        }
    }

    /**
     * @return void
     *
     * @throws Throwable
     */
    public static function code_1049()
    {
        EnvHelper::resetToDefaultDB();
        sleep(1);
    }

    /**
     * @return void
     *
     * @throws Throwable
     */
    public static function code_1146()
    {
        EnvHelper::resetToDefaultDB();
        sleep(1);
    }
}
