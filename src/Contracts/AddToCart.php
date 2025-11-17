<?php

namespace Amplify\System\Contracts;

interface AddToCart
{
    public function handle(array $data, \Closure $next);
}
