<?php

namespace Amplify\System\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class DatabaseScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (config('amplify.pim.pim_db_enabled')) {
            $builder->getQuery()->connection = DB::connection('pim_db');
        }
    }
}
