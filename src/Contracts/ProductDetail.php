<?php

namespace Amplify\System\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface ProductDetail
{
    public function handle(Builder $query, \Closure $next);
}
