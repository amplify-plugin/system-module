<?php

namespace Amplify\System\Contracts;

interface Checkout
{
    public function handle(array $data, \Closure $next);

}