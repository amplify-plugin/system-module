<?php

namespace Amplify\System\Facades;

use Amplify\System\Payment\PayApiService;
use Illuminate\Support\Facades\Facade;

/**
 * Payment service Facade
 *
 * @see PayApiService
 */
class PayApi extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'PayApi';
    }
}
