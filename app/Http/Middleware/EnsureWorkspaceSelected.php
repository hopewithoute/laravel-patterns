<?php

namespace App\Http\Middleware;

use App\Enums\RoleAuth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure user has selected a workspace/organization.
 */
class EnsureWorkspaceSelected
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Super Admin without workspace is allowed (global dashboard)
        if (RoleAuth::isSuperAdmin() && ! Session::has('organization_id')) {
            return $next($request);
        }

        // Other users must have an organization selected
        if (! Session::has('organization_id')) {
            return Redirect::route('workspace.select');
        }

        return $next($request);
    }
}
