<?php

namespace App\Supports;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Helper to get the currently active organization.
 */
class GetActiveOrganization
{
    /**
     * Get the selected organization ID from session.
     */
    public static function getSelected(): ?string
    {
        return Session::get('organization_id');
    }

    /**
     * Get the selected organization model (validates membership).
     */
    public static function get(): ?Organization
    {
        $id = self::getSelected();
        if (! $id) {
            return null;
        }

        $user = Auth::user();
        if (! $user) {
            return null;
        }

        // Validate user is member of this organization
        return once(fn () => $user->organizations()->find($id));
    }

    /**
     * Set the active organization (validates membership).
     */
    public static function set(string $organizationId): bool
    {
        $user = Auth::user();
        if (! $user || ! $user->belongsToOrganization($organizationId)) {
            return false;
        }

        Session::put('organization_id', $organizationId);

        return true;
    }

    /**
     * Set organization without validation (for registration/creation).
     */
    public static function setWithoutValidation(string $organizationId): void
    {
        Session::put('organization_id', $organizationId);
    }

    /**
     * Clear the active organization.
     */
    public static function clear(): void
    {
        Session::forget('organization_id');
    }

    /**
     * Check if an organization is selected.
     */
    public static function hasSelected(): bool
    {
        return Session::has('organization_id');
    }
}
