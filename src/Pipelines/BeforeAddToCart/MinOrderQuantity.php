<?php

namespace Amplify\System\Pipelines\AddToCart;

class MinOrderQuantity
{
    public function handle($data, $next)
    {
        return $next($data);
    }
}
