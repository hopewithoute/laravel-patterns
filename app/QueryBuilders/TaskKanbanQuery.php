<?php

namespace App\QueryBuilders;

use App\Enums\Priority;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TaskKanbanQuery extends QueryBuilder
{
    public function __construct(Request $request)
    {
        $query = Task::query()
            ->with(['project:id,name,color', 'assignee:id,name,avatar']);

        parent::__construct($query, $request);

        $this->allowedFilters(
            AllowedFilter::exact('project_id'),
            AllowedFilter::exact('assigned_to'),
            AllowedFilter::exact('status'),
            AllowedFilter::exact('priority'),
            AllowedFilter::scope('search', 'search'),
            AllowedFilter::scope('overdue', 'overdue'),
            AllowedFilter::scope('open', 'open'),
        );
    }

    public function getBoard(string $startDate, string $endDate): array
    {
        $tasks = $this
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('due_date', [$startDate, $endDate])
                    ->orWhereNull('due_date');
            })
            ->orderBy('sort_order')
            ->orderByRaw($this->priorityCaseExpression().' desc')
            ->orderBy('title')
            ->get();

        $columns = $this->buildColumns($startDate, $endDate);
        $tasksByColumn = collect($columns)
            ->pluck('key')
            ->mapWithKeys(fn (string $key) => [$key => []])
            ->all();

        foreach ($tasks as $task) {
            $columnKey = $task->due_date?->toDateString() ?? 'no_due_date';
            $tasksByColumn[$columnKey][] = $this->serializeTask($task);
        }

        return [
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'today' => now()->toDateString(),
            ],
            'columns' => $columns,
            'tasks_by_column' => $tasksByColumn,
        ];
    }

    private function buildColumns(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $columns = [[
            'key' => 'no_due_date',
            'label' => 'No Due Date',
            'date' => null,
        ]];

        while ($start->lte($end)) {
            $columns[] = [
                'key' => $start->toDateString(),
                'label' => $start->format('D j'),
                'date' => $start->toDateString(),
            ];

            $start->addDay();
        }

        return $columns;
    }

    public function serializeTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'due_date' => $task->due_date?->toDateString(),
            'sort_order' => $task->sort_order,
            'is_overdue' => $task->is_overdue,
            'project' => $task->project ? [
                'id' => $task->project->id,
                'name' => $task->project->name,
                'color' => $task->project->color,
            ] : null,
            'assignee' => $task->assignee ? [
                'id' => $task->assignee->id,
                'name' => $task->assignee->name,
                'avatar' => $task->assignee->avatar,
            ] : null,
        ];
    }

    private function priorityCaseExpression(): string
    {
        return sprintf(
            "case priority when '%s' then 4 when '%s' then 3 when '%s' then 2 when '%s' then 1 else 0 end",
            Priority::Urgent,
            Priority::High,
            Priority::Medium,
            Priority::Low,
        );
    }
}
