# Route Organization Pattern

> **Domain-Split Routes with Auto-Loading**

## Overview

This project separates routes per domain/feature into separate files in `routes/web/`. The main `routes/web.php` file only contains global routes (landing page) and an **auto-loader** that requires all route files.

## Structure

```
routes/
├── web.php              → Global routes + auto-loader
├── console.php          → CLI commands
└── web/
    ├── auth.php          → Login, register, password reset
    ├── dashboard.php     → Dashboard
    ├── projects.php      → Project CRUD
    ├── settings.php      → App settings
    ├── tasks.php         → Task CRUD + kanban + comments
    ├── team.php          → User management
    └── workspace.php     → Workspace switching
```

## Auto-Loading Mechanism

### `routes/web.php`

```php
use App\Supports\RouteHelper;

// Global routes
Route::get('/', function () { return inertia('Landing'); })->name('home');

// Auto-load all route files
RouteHelper::loadRoutesFromDirectory(__DIR__.'/web');
```

### Domain Route File

```php
// routes/web/tasks.php

Route::middleware([
    'auth',
    'ensure_workspace_selected',
])->group(static function () {

    // Custom endpoints before resource
    Route::get('tasks/kanban', [TaskController::class, 'kanban'])
        ->name('tasks.kanban');

    Route::patch('tasks/{task}/move', [TaskController::class, 'move'])
        ->name('tasks.move');

    // Resource routes (index, create, store, show, edit, update, destroy)
    Route::resource('tasks', TaskController::class);

    // Additional operations
    Route::put('tasks/{task}/complete', [TaskController::class, 'complete'])
        ->name('tasks.complete');

    Route::patch('tasks/{task}/status', [TaskController::class, 'status'])
        ->name('tasks.status');

    Route::put('tasks/{task}/assign', [TaskController::class, 'assign'])
        ->name('tasks.assign');

    // Nested comments
    Route::post('tasks/{task}/comments', [CommentController::class, 'store'])
        ->name('tasks.comments.store');
});
```

## Key Patterns

### 1. Middleware Group per Feature
Each domain route applies relevant middleware:

```php
Route::middleware([
    'auth',                      // Must be logged in
    'ensure_workspace_selected', // Must have workspace selected
])->group(static function () { ... });
```

### 2. Custom Routes Before Resource
Custom endpoints must be defined **before** `Route::resource()` to avoid conflicts:

```php
// ✅ Custom first
Route::get('tasks/kanban', ...)->name('tasks.kanban');

// Then resource
Route::resource('tasks', TaskController::class);

// ❌ If resource first, 'kanban' would be matched as {task} parameter
```

### 3. RESTful Naming Convention
Additional operations use semantic HTTP verbs:

```
PATCH  /tasks/{task}/move     → Partial update (reorder)
PUT    /tasks/{task}/complete → Full state change
PATCH  /tasks/{task}/status   → Single field update
PUT    /tasks/{task}/assign   → Full reassignment
POST   /tasks/{task}/comments → Create nested resource
```

## API Route Organization

API routes use **versioning** via route prefix:

### Entry Point: `routes/api.php`

```php
// routes/api.php

// Redirect /api to latest version
Route::redirect('/api', '/api/v1', 302);

// Version-specific routes
Route::prefix('v1')->group(base_path('routes/api/v1.php'));
```

### Version Routes: `routes/api/v1.php`

```php
// routes/api/v1.php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TaskController;
// ...

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // Token management
    Route::get('auth/tokens', [TokenController::class, 'index']);
    Route::post('auth/tokens', [TokenController::class, 'store']);
    Route::delete('auth/tokens/{token}', [TokenController::class, 'destroy']);

    // Resources
    Route::apiResource('tasks', TaskController::class);
    Route::apiResource('projects', ProjectController::class);

    // Nested resources
    Route::get('tasks/{task}/comments', [CommentController::class, 'index']);
    Route::post('tasks/{task}/comments', [CommentController::class, 'store']);

    // Custom actions
    Route::get('organizations/{organization}/members', [OrganizationController::class, 'members']);
});
```

### Controller Namespace

```
app/Http/Controllers/Api/
└── V1/
    ├── AuthController.php
    ├── TokenController.php
    ├── TaskController.php
    ├── ProjectController.php
    ├── CommentController.php
    └── OrganizationController.php
```

### API Route URLs

```
/api/auth/login              → Redirects to /api/v1/auth/login
/api/v1/auth/login           → Token + user
/api/v1/tasks                → Task list
/api/v1/tasks/{task}         → Task detail
/api/v1/tasks/{task}/comments → Task comments
/api/v1/organizations        → Organization list
```

### Adding a New Version

1. Create `routes/api/v2.php`
2. Create `App\Http\Controllers\Api\V2\` namespace
3. Add to `routes/api.php`:
   ```php
   Route::prefix('v2')->group(base_path('routes/api/v2.php'));
   ```
4. Keep v1 running until clients migrate

---

**Reference files:**
- `routes/web.php`
- `routes/web/*.php`
- `routes/api.php`
- `routes/api/v1.php`
- `app/Supports/RouteHelper.php`
