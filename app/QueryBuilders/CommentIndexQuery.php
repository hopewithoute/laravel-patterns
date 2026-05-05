<?php

namespace App\QueryBuilders;

use App\Models\Comment;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Query builder for Comment index/list.
 * Handles filtering, sorting, and including relationships.
 */
class CommentIndexQuery extends QueryBuilder
{
    public function __construct(Request $request)
    {
        $query = Comment::query()
            ->with(['user:id,name,avatar']);

        parent::__construct($query, $request);

        $this
            ->allowedFilters(
                AllowedFilter::exact('task_id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::scope('search', 'search'),
            )
            ->allowedSorts(
                'created_at',
                'updated_at',
            )
            ->defaultSort('-created_at');
    }
}
