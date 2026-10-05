<?php

namespace Amplify\System\Contexts;

class CheckoutContext
{
    public array $resolved = [];
    public array $calculated = [];
    public array $errors = [];
    public array $meta = [];

    public function __construct(
        public array $payload
    )
    {
    }
}