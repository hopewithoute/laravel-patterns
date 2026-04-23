# Service Pattern

> **Complex Query Aggregation & Read-Only Business Logic**

## Overview

Service class digunakan untuk **read-only operations** yang melibatkan multiple queries dan data aggregation. Berbeda dengan Action (write operations), Service fokus pada **menyiapkan data** untuk display.

## Kapan Pakai Service vs Action?

| Aspek              | Action                 | Service                        |
|--------------------|------------------------|--------------------------------|
| Tujuan             | Write (Create/Update)  | Read (Query/Aggregate)         |
| Side effects       | Ya (mutasi database)   | Tidak (read-only)              |
| Transaction        | Ya (`DB::transaction`) | Tidak                          |
| Naming             | `{Verb}Action`         | `{Domain}Service`              |
| Contoh             | `TaskCreateAction`     | `DashboardService`             |

## Implementasi

### DashboardService

```php
<?php

namespace App\Services;

class DashboardService
{
    /**
     * Get all dashboard statistics.
     * Menggunakan once() untuk memoization — prevent duplicate queries.
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
     * Aggregate project stats di satu query.
     * Menggunakan raw SQL untuk efficiency.
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
     * Aggregate task stats dengan multiple CASE expressions.
     * Satu query untuk semua status counts + overdue count.
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
     * Get recent tasks dengan selective eager loading.
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

## Pola-Pola Kunci

### 1. `once()` Memoization
Gunakan `once()` untuk cache result dalam satu request lifecycle:

```php
public function getStatistics(string $organizationId): array
{
    return once(function () use ($organizationId) {
        return [...]; // Hanya execute sekali per request
    });
}
```

### 2. Single Query Aggregation
Gabungkan multiple counts dalam satu query menggunakan `CASE` expression:

```php
// ✅ 1 query untuk semua stats
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
Gunakan enum constant langsung di raw query:

```php
DB::raw('SUM(CASE WHEN status = "'.TaskStatus::Done.'" THEN 1 ELSE 0 END) as done'),
```

---

**Referensi file:** `app/Services/DashboardService.php`
