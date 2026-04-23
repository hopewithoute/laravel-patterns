<?php

namespace App\Supports;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Helper to get the currently active organization.
 */
class GetActiveOrganization
{
    /**
     * Get the selected organization ID from static state, header, or session.
     */
    public static function getSelected(): ?string
    {
        return (app()->bound('active_organization_id') ? app('active_organization_id') : null)
            ?? request()?->header('X-Organization-Id')
            ?? Session::get('organization_id');
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
     * Get the selected organization model or fail if the context is invalid.
     */
    public static function resolveOrFail(): Organization
    {
        return self::get() ?? throw new AccessDeniedHttpException('Active workspace is invalid.');
    }

    /**
     * Set the active organization (validates membership).
     * Throws an exception if the user is not authorized.
     */
    public static function set(string $organizationId): void
    {
        $user = Auth::user();
        if (! $user || ! $user->belongsToOrganization($organizationId)) {
            throw new AccessDeniedHttpException('Unauthorized access to workspace.');
        }

        app()->instance('active_organization_id', $organizationId);
        Session::put('organization_id', $organizationId);
    }

    /**
     * Set organization without validation (for registration/creation).
     */
    public static function setWithoutValidation(string $organizationId): void
    {
        app()->instance('active_organization_id', $organizationId);
        Session::put('organization_id', $organizationId);
    }

    /**
     * Clear the active organization.
     */
    public static function clear(): void
    {
        if (app()->bound('active_organization_id')) {
            app()->forgetInstance('active_organization_id');
        }
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
