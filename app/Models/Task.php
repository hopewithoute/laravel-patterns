<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Traits\HasOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Task model.
 * Represents a task within a project.
 */
class Task extends Model
{
    use HasFactory, HasOrganization, HasUuids;

    protected $fillable = [
        'organization_id',
        'project_id',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'sort_order',
        'completed_at',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'priority' => Priority::class,
        'due_date' => 'date',
        'sort_order' => 'integer',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'status_color',
        'priority_color',
        'is_overdue',
    ];

    /**
     * Project this task belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * User assigned to this task.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Comments on this task.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return TaskStatus::getColor($this->status);
    }

    /**
     * Get priority color for UI.
     */
    public function getPriorityColorAttribute(): string
    {
        return Priority::getColor($this->priority);
    }

    /**
     * Check if task is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (! $this->due_date || $this->status === TaskStatus::Done) {
            return false;
        }

        return $this->due_date->isPast();
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by project.
     */
    public function scopeByProject(Builder $query, string $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to filter by assigned user.
     */
    public function scopeAssignedTo(Builder $query, string $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope to get overdue tasks.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', TaskStatus::Done);
    }

    /**
     * Scope to get open tasks.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', TaskStatus::openStatuses());
    }

    /**
     * Scope to search by title.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"));
    }

    /**
     * Mark task as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => TaskStatus::Done,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark task as open (move from done).
     */
    public function markAsOpen(string $status = TaskStatus::Todo): void
    {
        $this->update([
            'status' => $status,
            'completed_at' => null,
        ]);
    }
}
