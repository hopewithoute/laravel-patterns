<?php

namespace App\QueryBuilders;

use App\Enums\TaskStatus;
use App\Models\Project;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Query builder for Project index/list.
 * Handles filtering, sorting, and including relationships.
 *
 * @method ProjectIndexQuery withTaskCounts()
 */
class ProjectIndexQuery extends QueryBuilder
{
    public function __construct(Request $request)
    {
        $query = Project::query()
            ->withCount(['tasks as total_tasks'])
            ->withCount(['tasks as completed_tasks' => function ($q) {
                $q->where('status', TaskStatus::Done);
            }]);

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
                AllowedSort::field('tasks_count', 'total_tasks'),
            )
            ->defaultSort('-created_at');
    }
}
