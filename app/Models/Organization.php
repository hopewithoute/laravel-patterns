<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Organization model for multi-tenancy.
 * Each organization is a workspace/tenant.
 */
class Organization extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'is_active',
        'invite_code',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Generate slug and invite_code from name.
     */
    protected static function booted(): void
    {
        static::creating(function (self $organization) {
            if (empty($organization->slug)) {
                $organization->slug = \Str::slug($organization->name);
            }
            if (empty($organization->invite_code)) {
                $organization->invite_code = strtoupper(\Str::random(8));
            }
        });
    }

    /**
     * Get all users belonging to this organization (many-to-many).
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user', 'organization_id', 'user_id')
            ->withPivot(['role', 'joined_at']);
    }

    /**
     * Get all projects in this organization.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Check if user is a member of this organization.
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('users.id', $user->id)->exists();
    }

    /**
     * Get user's role in this organization.
     */
    public function getMemberRole(User $user): ?string
    {
        $member = $this->members()->where('users.id', $user->id)->first();

        return $member?->pivot->role;
    }

    /**
     * Add user to organization with role.
     */
    public function addMember(User $user, string $role = 'member'): void
    {
        $this->members()->syncWithoutDetaching([
            $user->id => ['role' => $role, 'joined_at' => now()],
        ]);
    }

    /**
     * Remove user from organization.
     */
    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
    }

    /**
     * Scope to get only active organizations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get route key name for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
