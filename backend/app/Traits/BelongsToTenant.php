<?php

namespace App\Traits;

use App\Models\Tenant;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model) {
            if (!$model->tenant_id) {
                if (Filament::getTenant()) {
                    $model->tenant_id = Filament::getTenant()->id;
                } elseif (app()->has('active_tenant_id')) {
                    $model->tenant_id = app('active_tenant_id');
                } else {
                    $firstTenant = Tenant::first();
                    if ($firstTenant) {
                        $model->tenant_id = $firstTenant->id;
                    }
                }
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (Filament::getTenant()) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', Filament::getTenant()->id);
            } elseif (app()->has('active_tenant_id')) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', app('active_tenant_id'));
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
