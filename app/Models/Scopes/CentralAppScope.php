<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class CentralAppScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! tenancy()->initialized) {
            $builder->whereNull($model->qualifyColumn(BelongsToTenant::$tenantIdColumn));
        }
    }

    public function extend(Builder $builder)
    {
        $builder->macro('withoutCentralApp', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
