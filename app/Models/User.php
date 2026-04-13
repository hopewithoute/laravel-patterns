<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * User model for Task Management SaaS.
 * Users belong to organizations and can have tasks assigned.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'organization_id',
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Organizations this user belongs to (many-to-many).
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user', 'user_id', 'organization_id')
            ->withPivot(['role', 'joined_at']);
    }

    /**
     * Tasks assigned to this user.
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * Comments created by this user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Scope to get active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if user belongs to an organization.
     */
    public function belongsToOrganization(string $organizationId): bool
    {
        return $this->organizations()->where('organizations.id', $organizationId)->exists();
    }

    /**
     * Get user's role in an organization.
     */
    public function getRoleInOrganization(string $organizationId): ?string
    {
        $pivot = $this->organizations()->where('organizations.id', $organizationId)->first();

        return $pivot?->pivot->role;
    }

    /**
     * Check if user is admin in an organization.
     */
    public function isAdminInOrganization(string $organizationId): bool
    {
        return $this->getRoleInOrganization($organizationId) === 'admin';
    }

    /**
     * Get tasks count for dashboard.
     */
    public function getOpenTasksCount(): int
    {
        return $this->assignedTasks()
            ->whereIn('status', TaskStatus::openStatuses())
            ->count();
    }
}
