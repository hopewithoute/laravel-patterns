<?php

namespace App\QueryBuilders;

use App\Models\Task;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Query builder for Task index/list.
 * Handles filtering, sorting, and including relationships.
 */
class TaskIndexQuery extends QueryBuilder
{
    public function __construct(Request $request)
    {
        $query = Task::query()
            ->with(['project:id,name,color', 'assignee:id,name,avatar']);

        parent::__construct($query, $request);

        $this
            ->allowedFilters(
                AllowedFilter::exact('project_id'),
                AllowedFilter::exact('assigned_to'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('priority'),
                AllowedFilter::scope('search', 'search'),
                AllowedFilter::scope('overdue', 'overdue'),
                AllowedFilter::scope('open', 'open'),
            )
            ->allowedSorts(
                'title',
                'status',
                'priority',
                'due_date',
                'created_at',
                'updated_at',
            )
            ->defaultSort('-priority', 'due_date');
    }
}
