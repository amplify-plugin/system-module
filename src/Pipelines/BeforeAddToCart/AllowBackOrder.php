<?php

namespace Amplify\System\Pipelines\AddToCart;

class AllowBackOrder
{
    public function handle($data, $next)
    {
        return $next($data);
    }
}
