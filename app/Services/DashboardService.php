<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Service for dashboard statistics and data.
 */
class DashboardService
{
    /**
     * Get all dashboard statistics.
     */
    public function getStatistics(string $organizationId): array
    {
        return once(function () use ($organizationId) {
            return [
                'projects' => $this->getProjectStats($organizationId),
                'tasks' => $this->getTaskStats($organizationId),
                'recent_tasks' => $this->getRecentTasks($organizationId),
                'upcoming_deadlines' => $this->getUpcomingDeadlines($organizationId),
            ];
        });
    }

    /**
     * Get project statistics.
     */
    public function getProjectStats(string $organizationId): array
    {
        $stats = DB::table('projects')
            ->where('organization_id', $organizationId)
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active'),
            ])
            ->first();

        return [
            'total' => (int) ($stats->total ?? 0),
            'active' => (int) ($stats->active ?? 0),
        ];
    }

    /**
     * Get task statistics.
     */
    public function getTaskStats(string $organizationId): array
    {
        $now = now()->toDateTimeString();

        $stats = DB::table('tasks')
            ->where('organization_id', $organizationId)
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "'.TaskStatus::Todo.'" THEN 1 ELSE 0 END) as todo'),
                DB::raw('SUM(CASE WHEN status = "'.TaskStatus::InProgress.'" THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN status = "'.TaskStatus::Review.'" THEN 1 ELSE 0 END) as review'),
                DB::raw('SUM(CASE WHEN status = "'.TaskStatus::Done.'" THEN 1 ELSE 0 END) as done'),
                DB::raw('SUM(CASE WHEN due_date < "'.$now.'" AND status != "'.TaskStatus::Done.'" THEN 1 ELSE 0 END) as overdue'),
            ])
            ->first();

        return [
            'total' => (int) ($stats->total ?? 0),
            'todo' => (int) ($stats->todo ?? 0),
            'in_progress' => (int) ($stats->in_progress ?? 0),
            'review' => (int) ($stats->review ?? 0),
            'done' => (int) ($stats->done ?? 0),
            'overdue' => (int) ($stats->overdue ?? 0),
        ];
    }

    /**
     * Get recent tasks (last 5).
     */
    public function getRecentTasks(string $organizationId): array
    {
        return Task::query()
            ->where('organization_id', $organizationId)
            ->with(['project:id,name', 'assignee:id,name'])
            ->latest()
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Get upcoming deadlines (next 7 days).
     */
    public function getUpcomingDeadlines(string $organizationId): array
    {
        return Task::query()
            ->where('organization_id', $organizationId)
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->where('status', '!=', TaskStatus::Done)
            ->with(['project:id,name', 'assignee:id,name'])
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get task distribution by project.
     */
    public function getTaskDistributionByProject(string $organizationId): array
    {
        return DB::table('tasks')
            ->join('projects', 'tasks.project_id', '=', 'projects.id')
            ->where('tasks.organization_id', $organizationId)
            ->select([
                'projects.name as project_name',
                'projects.color',
                DB::raw('COUNT(*) as task_count'),
            ])
            ->groupBy('projects.id', 'projects.name', 'projects.color')
            ->orderByDesc('task_count')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
