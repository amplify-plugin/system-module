<?php

namespace Amplify\System\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Amplify\System\Captcha\Captcha
 */
class CaptchaFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'captcha';
    }
}
