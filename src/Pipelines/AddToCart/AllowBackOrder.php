<?php

namespace Amplify\System\Pipelines\AddToCart;

use Amplify\System\Contracts\AddToCart;

class AllowBackOrder implements AddToCart
{
    public function handle(array $data, \Closure $next)
    {
        return $next($data);
    }
}
