# Service Pattern

> **Complex Query Aggregation & Read-Only Business Logic**

## Overview

Service classes are used for **read-only operations** involving multiple queries and data aggregation. Unlike Actions (write operations), Services focus on **preparing data** for display.

## When to Use Service vs Action?

| Aspect           | Action                 | Service                        |
|------------------|------------------------|--------------------------------|
| Purpose          | Write (Create/Update)  | Read (Query/Aggregate)         |
| Side effects     | Yes (database mutation)| No (read-only)                 |
| Transaction      | Yes (`DB::transaction`)| No                             |
| Naming           | `{Verb}Action`         | `{Domain}Service`              |
| Example          | `TaskCreateAction`     | `DashboardService`             |

## Implementation

### DashboardService

```php
<?php

namespace App\Services;

class DashboardService
{
    /**
     * Get all dashboard statistics.
     * Uses once() for memoization — prevents duplicate queries.
     */
    public function getStatistics(string $organizationId): array
    {
        return once(function () use ($organizationId) {
            return [
                'projects'           => $this->getProjectStats($organizationId),
                'tasks'              => $this->getTaskStats($organizationId),
                'recent_tasks'       => $this->getRecentTasks($organizationId),
                'upcoming_deadlines' => $this->getUpcomingDeadlines($organizationId),
            ];
        });
    }

    /**
     * Aggregate project stats in a single query.
     * Uses raw SQL for efficiency.
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
            'total'  => (int) ($stats->total ?? 0),
            'active' => (int) ($stats->active ?? 0),
        ];
    }

    /**
     * Aggregate task stats with multiple CASE expressions.
     * One query for all status counts + overdue count.
     */
    public function getTaskStats(string $organizationId): array
    {
        $stats = DB::table('tasks')
            ->where('organization_id', $organizationId)
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "'.TaskStatus::Todo.'" THEN 1 ELSE 0 END) as todo'),
                DB::raw('SUM(CASE WHEN status = "'.TaskStatus::InProgress.'" THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN status = "'.TaskStatus::Review.'" THEN 1 ELSE 0 END) as review'),
                DB::raw('SUM(CASE WHEN status = "'.TaskStatus::Done.'" THEN 1 ELSE 0 END) as done'),
                DB::raw('SUM(CASE WHEN due_date < "..." AND status != "..." THEN 1 ELSE 0 END) as overdue'),
            ])
            ->first();

        return [ /* cast to int */ ];
    }

    /**
     * Get recent tasks with selective eager loading.
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
}
```

## Key Patterns

### 1. `once()` Memoization
Use `once()` to cache results within a single request lifecycle:

```php
public function getStatistics(string $organizationId): array
{
    return once(function () use ($organizationId) {
        return [...]; // Only executes once per request
    });
}
```

### 2. Single Query Aggregation
Combine multiple counts in one query using `CASE` expressions:

```php
// ✅ 1 query for all stats
DB::table('tasks')->select([
    DB::raw('COUNT(*) as total'),
    DB::raw('SUM(CASE WHEN status = "Todo" THEN 1 ELSE 0 END) as todo'),
    DB::raw('SUM(CASE WHEN status = "Done" THEN 1 ELSE 0 END) as done'),
])->first();

// ❌ N+1 queries
$total = Task::count();
$todo  = Task::where('status', 'Todo')->count();
$done  = Task::where('status', 'Done')->count();
```

### 3. Enum Constants in SQL
Use enum constants directly in raw queries:

```php
DB::raw('SUM(CASE WHEN status = "'.TaskStatus::Done.'" THEN 1 ELSE 0 END) as done'),
```

---

**Reference files:** `app/Services/DashboardService.php`
