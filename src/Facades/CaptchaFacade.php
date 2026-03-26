<?php

namespace Amplify\System\Facades;

use Amplify\System\Captcha\Captcha;
use Illuminate\Support\Facades\Facade;

/**
 * @see Captcha
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
