<?php

namespace App\QueryBuilders;

use App\Models\Organization;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Query builder for Organization index/list.
 * Handles filtering, sorting, and including relationships.
 */
class OrganizationIndexQuery extends QueryBuilder
{
    public function __construct(Request $request)
    {
        $query = Organization::query();

        parent::__construct($query, $request);

        $this
            ->allowedFilters(
                AllowedFilter::exact('is_active'),
                AllowedFilter::partial('name'),
                AllowedFilter::scope('search', 'search'),
            )
            ->allowedSorts(
                'name',
                'created_at',
                'updated_at',
            )
            ->defaultSort('-created_at');
    }
}
