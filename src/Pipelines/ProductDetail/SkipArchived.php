<?php

namespace Amplify\System\Pipelines\ProductDetail;

use Amplify\System\Contracts\ProductDetail;
use Illuminate\Database\Eloquent\Builder;

class SkipArchived implements ProductDetail
{
    public function handle(Builder $query, \Closure $next)
    {
        $query->whereNotIn('status', ['archived']);

        return $next($query);
    }
}
