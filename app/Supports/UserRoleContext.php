<?php

namespace App\Supports;

use App\Enums\RoleAuth;
use App\Models\User;

/**
 * Helper to check user roles in organization context.
 */
class UserRoleContext
{
    /**
     * Check if user has a global role.
     */
    public static function checkGlobalRole(User $user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }

    /**
     * Check if user has a contextual role in current organization.
     * Uses pivot role if available, falls back to global roles.
     */
    public static function checkContextualRole(User $user, array $roles): bool
    {
        $orgId = GetActiveOrganization::getSelected();

        if ($orgId) {
            $orgRole = $user->getRoleInOrganization($orgId);
            if ($orgRole === 'admin') {
                return true; // Admin has all permissions
            }
        }

        return $user->hasAnyRole($roles);
    }

    /**
     * Check if user can manage projects.
     */
    public static function canManageProjects(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return self::checkContextualRole($user, RoleAuth::canManageProjects());
    }

    /**
     * Check if user can manage members.
     */
    public static function canManageMembers(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return self::checkContextualRole($user, RoleAuth::canManageMembers());
    }
}
