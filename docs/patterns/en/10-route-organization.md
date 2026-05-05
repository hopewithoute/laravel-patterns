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

---

**Reference files:**
- `routes/web.php`
- `routes/web/*.php`
- `app/Supports/RouteHelper.php`
