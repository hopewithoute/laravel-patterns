# Route Organization Pattern

> **Domain-Split Routes dengan Auto-Loading**

## Overview

Project ini memisahkan route per domain/feature ke file-file terpisah di `routes/web/`. File utama `routes/web.php` hanya berisi route global (landing page) dan **auto-loader** yang me-require semua route files.

## Struktur

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

// Auto-load semua route files
RouteHelper::loadRoutesFromDirectory(__DIR__.'/web');
```

### Domain Route File

```php
// routes/web/tasks.php

Route::middleware([
    'auth',
    'ensure_workspace_selected',
])->group(static function () {

    // Custom endpoints sebelum resource
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

## Pola-Pola Kunci

### 1. Middleware Group per Feature
Setiap domain route menerapkan middleware yang relevan:

```php
Route::middleware([
    'auth',                      // Must be logged in
    'ensure_workspace_selected', // Must have workspace selected
])->group(static function () { ... });
```

### 2. Custom Routes Sebelum Resource
Custom endpoint harus didefinisikan **sebelum** `Route::resource()` untuk menghindari konflik:

```php
// ✅ Custom dulu
Route::get('tasks/kanban', ...)->name('tasks.kanban');

// Baru resource
Route::resource('tasks', TaskController::class);

// ❌ Jika resource dulu, 'kanban' akan di-match sebagai {task} parameter
```

### 3. RESTful Naming Convention
Operasi tambahan menggunakan HTTP verb yang semantik:

```
PATCH  /tasks/{task}/move     → Partial update (reorder)
PUT    /tasks/{task}/complete → Full state change
PATCH  /tasks/{task}/status   → Single field update
PUT    /tasks/{task}/assign   → Full reassignment
POST   /tasks/{task}/comments → Create nested resource
```

## Organisasi Route API

Route API didefinisikan di `routes/api.php`:

```php
// routes/api.php

// Public routes (tidak perlu auth)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes (perlu auth)
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

### Penamaan Route API

```
/api/auth/login          → api.auth.login
/api/tasks               → api.tasks.index
/api/tasks/{task}        → api.tasks.show
/api/projects            → api.projects.index
/api/tasks/{task}/comments → api.tasks.comments.index
```

---

**Referensi file:**
- `routes/web.php`
- `routes/web/*.php`
- `routes/api.php`
- `app/Supports/RouteHelper.php`
