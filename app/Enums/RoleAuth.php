<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use Illuminate\Support\Facades\Session;

/**
 * Role authentication enum for Task Management SaaS.
 * Supports multi-tenancy with organization-based roles.
 */
final class RoleAuth extends Enum
{
    public const SuperAdmin = 'Super Admin';

    public const Owner = 'Owner';

    public const Admin = 'Admin';

    public const Member = 'Member';

    // Role checks
    public static function isSuperAdmin(): bool
    {
        return Session::get('roles') === self::SuperAdmin;
    }

    public static function isOwner(): bool
    {
        return Session::get('roles') === self::Owner;
    }

    public static function isAdmin(): bool
    {
        return Session::get('roles') === self::Admin;
    }

    public static function isMember(): bool
    {
        return Session::get('roles') === self::Member;
    }

    /**
     * Global roles - not bound to any organization.
     */
    public static function globalRole(): array
    {
        return [
            self::SuperAdmin,
        ];
    }

    /**
     * Contextual roles - bound to organization context.
     */
    public static function contextualRole(): array
    {
        return [
            self::Owner,
            self::Admin,
            self::Member,
        ];
    }

    public static function globalRoleOptions(): array
    {
        return collect(self::globalRole())->map(function ($value, $key) {
            return [
                'text' => $value,
                'value' => $value,
            ];
        })->toArray();
    }

    public static function contextualRoleOptions(): array
    {
        return collect(self::contextualRole())->map(function ($value, $key) {
            return [
                'text' => $value,
                'value' => $value,
            ];
        })->toArray();
    }

    public static function isGlobalRole(): bool
    {
        return in_array(Session::get('roles'), self::globalRole());
    }

    public static function isContextualRole(): bool
    {
        return in_array(Session::get('roles'), self::contextualRole());
    }

    /**
     * Get all roles as options for dropdowns.
     */
    public static function asOptions(): array
    {
        return [
            ['text' => 'Super Admin', 'value' => self::SuperAdmin],
            ['text' => 'Owner', 'value' => self::Owner],
            ['text' => 'Admin', 'value' => self::Admin],
            ['text' => 'Member', 'value' => self::Member],
        ];
    }

    /**
     * Roles that can manage projects (create, update, delete).
     */
    public static function canManageProjects(): array
    {
        return [self::Owner, self::Admin];
    }

    /**
     * Roles that can manage members.
     */
    public static function canManageMembers(): array
    {
        return [self::Owner, self::Admin];
    }
}
