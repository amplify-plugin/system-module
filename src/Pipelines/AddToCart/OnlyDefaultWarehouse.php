<?php

namespace Amplify\System\Pipelines\AddToCart;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\System\Contracts\AddToCart;

class OnlyDefaultWarehouse implements AddToCart
{
    public function handle(array $data, \Closure $next)
    {
        $defaultWarehouse = ErpApi::getCustomerDetail()->DefaultWarehouse;

        return $next($data);
    }
}
