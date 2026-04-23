<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Traits\HasOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Project model.
 * Represents a project within an organization.
 */
class Project extends Model
{
    use HasFactory, HasOrganization, HasUuids;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'tasks_count',
        'completed_tasks_count',
        'completion_percentage',
    ];

    /**
     * Organization this project belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Tasks in this project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get total tasks count.
     */
    public function getTasksCountAttribute(): int
    {
        return $this->tasks()->count();
    }

    /**
     * Get completed tasks count.
     */
    public function getCompletedTasksCountAttribute(): int
    {
        return $this->tasks()->where('status', TaskStatus::Done)->count();
    }

    /**
     * Scope to get only active projects.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by name.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"));
    }

    /**
     * Get completion percentage.
     */
    public function getCompletionPercentageAttribute(): int
    {
        $total = $this->tasks_count;
        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->completed_tasks_count / $total) * 100);
    }
}
