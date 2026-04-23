<?php

namespace App\AI\Runtime\Retrieval;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\KnowledgeSource;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

readonly class WorkspaceDatabaseKnowledgeSource implements KnowledgeSource
{
    public function __construct(
        private Project $project,
        private Task $task,
    ) {}

    public function supports(RetrievalPlan $plan): bool
    {
        return $plan->required && in_array('workspace_db', $plan->sources, true);
    }

    public function retrieve(AiRuntimeContext $context, RetrievalPlan $plan): RetrievalResult
    {
        if (! $this->supports($plan)) {
            return RetrievalResult::empty();
        }

        $terms = $this->extractSearchTerms($plan->query ?? $context->prompt);
        $projects = $this->retrieveProjects($context, $plan, $terms);
        $tasks = $this->retrieveTasks($context, $plan, $terms);

        return new RetrievalResult(
            query: $plan->query ?? $context->prompt,
            documents: [
                ...$this->mapProjectDocuments($projects),
                ...$this->mapTaskDocuments($tasks),
            ],
            metadata: [
                'summary' => $this->buildSummary($projects, $tasks),
                'sources' => ['workspace_db'],
                'documents_count' => $projects->count() + $tasks->count(),
                'driver' => 'database',
                'project_count' => $projects->count(),
                'task_count' => $tasks->count(),
                'search_terms' => $terms,
            ],
        );
    }

    /**
     * @param  array<int, string>  $terms
     * @return Collection<int, Project>
     */
    private function retrieveProjects(AiRuntimeContext $context, RetrievalPlan $plan, array $terms): Collection
    {
        $limit = (int) ($plan->filters['project_limit'] ?? 3);

        return $this->project->newQuery()
            ->where('organization_id', $context->organization->id)
            ->withCount('tasks')
            ->when($terms !== [], fn (Builder $query): Builder => $query->where(function (Builder $innerQuery) use ($terms): void {
                foreach ($terms as $term) {
                    $escapedTerm = $this->escapeLike($term);
                    $innerQuery
                        ->orWhere('name', 'like', "%{$escapedTerm}%")
                        ->orWhere('description', 'like', "%{$escapedTerm}%");
                }
            }))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<int, string>  $terms
     * @return Collection<int, Task>
     */
    private function retrieveTasks(AiRuntimeContext $context, RetrievalPlan $plan, array $terms): Collection
    {
        $limit = (int) ($plan->filters['task_limit'] ?? 5);
        $normalizedQuery = Str::lower($plan->query ?? $context->prompt);

        return $this->task->newQuery()
            ->where('organization_id', $context->organization->id)
            ->with([
                'project:id,name',
                'assignee:id,name',
            ])
            ->when(Str::contains($normalizedQuery, 'overdue'), fn (Builder $query): Builder => $query->overdue())
            ->when(
                ! Str::contains($normalizedQuery, 'overdue') && Str::contains($normalizedQuery, ['open', 'in progress', 'review', 'todo']),
                fn (Builder $query): Builder => $query->open(),
            )
            ->when($terms !== [], function (Builder $query) use ($terms): Builder {
                return $query->where(function (Builder $innerQuery) use ($terms): void {
                    foreach ($terms as $term) {
                        $escapedTerm = $this->escapeLike($term);
                        $innerQuery
                            ->orWhere('title', 'like', "%{$escapedTerm}%")
                            ->orWhere('description', 'like', "%{$escapedTerm}%")
                            ->orWhereHas('project', fn (Builder $projectQuery) => $projectQuery->where('name', 'like', "%{$escapedTerm}%"));
                    }
                });
            })
            ->orderByRaw($this->taskPriorityOrderSql())
            ->orderBy('due_date')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array<int, array<string, mixed>>
     */
    private function mapProjectDocuments(Collection $projects): array
    {
        return $projects
            ->map(fn (Project $project): array => [
                'type' => 'project',
                'id' => $project->id,
                'title' => $project->name,
                'content' => trim(implode(' ', array_filter([
                    "Project {$project->name}.",
                    $project->description,
                    $project->is_active ? 'Project is active.' : 'Project is inactive.',
                    "Task count: {$project->tasks_count}.",
                ]))),
                'metadata' => [
                    'organization_id' => $project->organization_id,
                    'tasks_count' => $project->tasks_count,
                    'is_active' => $project->is_active,
                ],
            ])
            ->all();
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return array<int, array<string, mixed>>
     */
    private function mapTaskDocuments(Collection $tasks): array
    {
        return $tasks
            ->map(fn (Task $task): array => [
                'type' => 'task',
                'id' => $task->id,
                'title' => $task->title,
                'content' => trim(implode(' ', array_filter([
                    "Task {$task->title}.",
                    $task->description,
                    'Status: '.$this->stringValue($task->status),
                    'Priority: '.$this->stringValue($task->priority),
                    $task->project?->name ? "Project: {$task->project->name}." : null,
                    $task->assignee?->name ? "Assignee: {$task->assignee->name}." : null,
                    $task->due_date?->toDateString() ? "Due date: {$task->due_date->toDateString()}." : null,
                ]))),
                'metadata' => [
                    'organization_id' => $task->organization_id,
                    'project_id' => $task->project_id,
                    'status' => $this->stringValue($task->status),
                    'priority' => $this->stringValue($task->priority),
                    'assigned_to' => $task->assigned_to,
                ],
            ])
            ->all();
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @param  Collection<int, Task>  $tasks
     */
    private function buildSummary(Collection $projects, Collection $tasks): string
    {
        $projectLines = $projects
            ->map(fn (Project $project): string => "- Project {$project->name} [{$project->id}] active=".($project->is_active ? 'yes' : 'no')." tasks={$project->tasks_count}")
            ->implode("\n");

        $taskLines = $tasks
            ->map(fn (Task $task): string => "- Task {$task->title} [{$task->id}] status=".$this->stringValue($task->status).' priority='.$this->stringValue($task->priority).($task->project?->name ? " project={$task->project->name}" : ''))
            ->implode("\n");

        return trim(implode("\n", array_filter([
            $projectLines !== '' ? "Relevant projects:\n{$projectLines}" : null,
            $taskLines !== '' ? "Relevant tasks:\n{$taskLines}" : null,
        ])));
    }

    /**
     * @return array<int, string>
     */
    private function extractSearchTerms(string $query): array
    {
        $stopWords = [
            'the', 'and', 'for', 'with', 'from', 'that', 'this', 'into', 'show', 'list', 'find',
            'what', 'which', 'when', 'where', 'create', 'make', 'need', 'please', 'project',
            'task', 'tasks', 'projects', 'status', 'open', 'overdue', 'review', 'todo', 'give',
            'about', 'card', 'stats', 'metrics', 'workspace',
        ];

        return collect(preg_split('/\s+/', Str::lower($query)) ?: [])
            ->map(fn (string $part): string => trim($part, " \t\n\r\0\x0B,.:;!?()[]{}\"'`"))
            ->filter(fn (string $part): bool => strlen($part) >= 3)
            ->reject(fn (string $part): bool => in_array($part, $stopWords, true))
            ->unique()
            ->take(8)
            ->values()
            ->all();
    }

    private function taskPriorityOrderSql(): string
    {
        return "case priority
            when '".Priority::Urgent."' then 1
            when '".Priority::High."' then 2
            when '".Priority::Medium."' then 3
            when '".Priority::Low."' then 4
            else 5
        end";
    }

    private function stringValue(mixed $value): string
    {
        if ($value instanceof TaskStatus || $value instanceof Priority) {
            return $value->value;
        }

        return (string) $value;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }
}
