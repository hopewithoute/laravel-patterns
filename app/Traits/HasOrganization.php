<?php

namespace App\Traits;

use App\Models\Organization;
use App\Supports\GetActiveOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Scope;

/**
 * Trait for multi-tenancy support.
 * Automatically scopes all queries to the current organization.
 *
 * Usage: Add `use HasOrganization;` to your model.
 *
 * Features:
 * - Global scope filters all queries by organization_id
 * - Auto-fills organization_id on create
 */
trait HasOrganization
{
    /**
     * Boot the trait.
     * Automatically applies Global Scope and event listeners.
     */
    protected static function bootHasOrganization(): void
    {
        // 1. Apply Global Scope to filter all SELECT queries automatically.
        static::addGlobalScope('organization', new class implements Scope
        {
            public function apply(Builder $builder, Model $model)
            {
                $orgId = GetActiveOrganization::getSelected();

                if ($orgId) {
                    $builder->where($model->getTable().'.organization_id', $orgId);
                }
            }
        });

        // 2. Event listener that runs when model is being CREATED (INSERT).
        static::creating(function ($model) {
            if (empty($model->organization_id)) {
                $orgId = GetActiveOrganization::getSelected();
                if ($orgId) {
                    $model->organization_id = $orgId;
                }
            }
        });
    }

    /**
     * Define the relationship that this model "belongs to" one Organization.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Scope to filter by specific organization.
     */
    public function scopeFilterByOrganization(Builder $query, ?string $organizationId = null): void
    {
        $query->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId));
    }

    /**
     * Scope to include data without organization scope (bypass global scope).
     * Use with caution - only for Super Admin operations.
     */
    public function scopeWithoutOrganizationScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('organization');
    }
}
