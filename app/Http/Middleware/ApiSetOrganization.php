<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API middleware to set organization from X-Organization header.
 * Sets the organization_id in session so DTOs work correctly.
 */
class ApiSetOrganization
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $request->header('X-Organization');

        if ($organizationId) {
            session(['organization_id' => $organizationId]);
        }

        return $next($request);
    }
}
