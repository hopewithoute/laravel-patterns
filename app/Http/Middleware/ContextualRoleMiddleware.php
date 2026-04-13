<?php

namespace App\Http\Middleware;

use App\Enums\RoleAuth;
use App\Supports\UserRoleContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;

/**
 * Middleware for contextual role-based authorization.
 * Handles both global roles (Super Admin) and contextual roles (Owner, Admin, Member).
 */
class ContextualRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  string|array  ...$roles  Roles as arguments
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        // Exception case: Super Admin always passes
        if (RoleAuth::isSuperAdmin()) {
            return $next($request);
        }

        // Global Role check
        if (RoleAuth::isGlobalRole() && UserRoleContext::checkGlobalRole($user, $roles)) {
            return $next($request);
        }

        // Contextual Role check (bound to organization)
        if (RoleAuth::isContextualRole() && UserRoleContext::checkContextualRole($user, $roles)) {
            return $next($request);
        }

        // If no role matches, throw 403 exception
        throw UnauthorizedException::forRoles($roles);
    }
}
