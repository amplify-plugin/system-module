<?php

namespace Amplify\System\Contracts;

use Amplify\System\Contexts\CheckoutContext;

interface Checkout
{
    public function handle(CheckoutContext $context, \Closure $next);

}