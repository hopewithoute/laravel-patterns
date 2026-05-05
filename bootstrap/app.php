<?php

use App\Http\Middleware\ApiSetOrganization;
use App\Http\Middleware\ContextualRoleMiddleware;
use App\Http\Middleware\EnsureWorkspaceSelected;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register custom middleware aliases
        $middleware->alias([
            'role' => ContextualRoleMiddleware::class,
            'ensure_workspace_selected' => EnsureWorkspaceSelected::class,
            'api.organization' => ApiSetOrganization::class,
        ]);

        // Append middleware to web group
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // Append middleware to API group
        $middleware->api(prepend: [
            ApiSetOrganization::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
