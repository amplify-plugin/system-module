<?php

namespace Amplify\System\Pipelines\AddToCart;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\System\Contracts\AddToCart;

class ErpIsEnabled implements AddToCart
{
    public function handle(array $data, \Closure $next)
    {
        abort_if(!ErpApi::enabled(), 500, __('ERP Service is not enabled.'));

        return $next($data);
    }
}
