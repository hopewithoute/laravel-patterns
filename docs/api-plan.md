# REST API Implementation Plan

> API version of existing web endpoints, reusing Actions and QueryBuilders. Patterns adapted from simsarpras.

---

## Decision: Sanctum Authentication Strategy

### How Sanctum Works (v4)

Sanctum offers **two** authentication modes:

| Mode | Use Case | How It Works |
|------|----------|--------------|
| **SPA Auth** | Same-domain Vue/React SPA | Cookie-based session, CSRF protection |
| **API Tokens** | Mobile apps, service-to-service | Bearer token in `Authorization` header |

### What We Need

| Consumer | Auth Method | Token Type |
|----------|-------------|------------|
| **Mobile App** | `Authorization: Bearer {token}` | Personal Access Token (long-lived) |
| **Service-to-Service** | `Authorization: Bearer {token}` | Scoped Token with abilities |
| **Existing SPA (Inertia)** | Cookie session | Already works (no change) |

### Why Sanctum Over JWT (tymon/jwt-auth)

| Aspect | Sanctum Tokens | JWT |
|--------|----------------|-----|
| Built-in | ✅ Ships with Laravel | ❌ External package |
| Revocation | ✅ Database-backed, instant revoke | ❌ Requires blocklist |
| Abilities | ✅ Fine-grained scopes | ❌ Claims-based, more complex |
| Expiry | ✅ Per-token expiry | ✅ Per-token expiry |
| Complexity | Low | Medium-High |

**Decision:** Use Sanctum's built-in token auth. No JWT library needed.

### Token Strategy

```
Mobile Login → POST /api/auth/login → Returns { token, user }
                    ↓
            Create Personal Access Token
            - name: "mobile"
            - abilities: ['*'] (full access)
            - expires_at: 30 days

Service-to-Service → POST /api/auth/token → Returns { token }
                    ↓
            Create Scoped Token
            - name: "service-name"
            - abilities: ['tasks:read', 'projects:read']
            - expires_at: 1 year
```

---

## API Architecture

### Request Flow

```
Request → api middleware group
       → Auth:sanctum (token verification)
       → EnsureWorkspaceSelected (org context)
       → Controller (thin)
       → Reuse existing Action / QueryBuilder
       → API Resource (transform response)
       → JSON Response
```

### Key Principle: Reuse Everything

```
Web Controller                    API Controller
    │                                 │
    ├── TaskData (validate)    ←──────┤ (same DTO)
    ├── TaskCreateAction       ←──────┤ (same Action)
    ├── TaskIndexQuery         ←──────┤ (same QueryBuilder)
    │                                 │
    └── Inertia::render()            └── TaskResource::collection()
```

**Exception:** API may need separate QueryBuilder if eager loading differs (pattern from simsarpras).

---

## Implementation Plan

### Phase 1: Foundation

| # | Task | Files |
|---|------|-------|
| 1 | Enable API routing in `bootstrap/app.php` | `bootstrap/app.php` |
| 2 | Create `routes/api.php` with route groups | `routes/api.php` |
| 3 | Create `Api\AuthController` (login, logout, register, token management) | `app/Http/Controllers/Api/AuthController.php` |
| 4 | Create API middleware group (auth, workspace, role) | `bootstrap/app.php` |

### Phase 2: Token Management (from simsarpras)

| # | Task | Files |
|---|------|-------|
| 1 | Create `Token` model extending `PersonalAccessToken` | `app/Models/Token.php` |
| 2 | Create `TokenData` DTO | `app/Data/TokenData.php` |
| 3 | Create `TokenController` for API token CRUD | `app/Http/Controllers/Api/TokenController.php` |
| 4 | Add web `TokenController` for Inertia responses | `app/Http/Controllers/TokenController.php` |
| 5 | Create Token Management UI | `resources/js/pages/Settings/Tokens.vue` |
| 6 | Add "API Tokens" tab to Settings | `resources/js/pages/Settings/Index.vue` |

### Phase 3: API Resources

| # | Resource | Model |
|---|----------|-------|
| 1 | `TaskResource` | Task |
| 2 | `ProjectResource` | Project |
| 3 | `CommentResource` | Comment |
| 4 | `UserResource` | User |
| 5 | `OrganizationResource` | Organization |

### Phase 4: API Controllers

| # | Controller | Endpoints | Reuses |
|---|------------|-----------|--------|
| 1 | `TaskController` | CRUD + kanban + move + complete + assign | TaskActions, TaskQueries |
| 2 | `ProjectController` | CRUD + tasks list | ProjectActions, ProjectQuery |
| 3 | `CommentController` | Create + Delete | CommentActions |
| 4 | `DashboardController` | Statistics | DashboardService |
| 5 | `WorkspaceController` | List + Set + Create | WorkspaceActions |
| 6 | `TeamController` | List + Invite + Remove | UserActions |

### Phase 5: Token Management UI

Token management integrated into Settings page as a new "API Tokens" tab.

#### Features
- List all tokens with name, abilities, created date, last used, expiry
- Create new token with name and optional abilities
- Copy token to clipboard (shown only once on creation)
- Revoke/delete token
- Visual indicator for expired tokens

#### UI Structure (follows existing Settings tabs)

```
Settings/Index.vue
├── Profile Tab (existing)
├── Password Tab (existing)
├── Workspace Tab (existing)
└── API Tokens Tab (NEW)
    ├── Token list with status badges
    ├── Create token form
    └── Revoke confirmation
```

#### Web TokenController (Inertia)

```php
// app/Http/Controllers/TokenController.php
class TokenController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Settings/Index', [
            'tokens' => $request->user()->tokens()->latest()->get(),
        ]);
    }

    public function store(TokenData $data, Request $request): RedirectResponse
    {
        $token = $request->user()->createToken(
            $data->name,
            $data->abilities ?? ['*'],
            $data->expires_at
        );

        return back()->with('flash', [
            'type' => 'success',
            'text' => 'Token created. Copy it now — it won\'t be shown again.',
            'token' => $token->plainTextToken, // Only shown once
        ]);
    }

    public function destroy(Token $token, Request $request): RedirectResponse
    {
        if ($token->tokenable_id !== $request->user()->id) {
            abort(403);
        }

        $token->delete();

        return back()->with('flash', [
            'type' => 'success',
            'text' => 'Token revoked successfully.',
        ]);
    }
}
```

#### Tokens.vue Component

```vue
<script setup>
// Props: tokens array
// useForm for create form
// copyToClipboard utility
// revoke with confirmation
</script>

<template>
    <!-- Token List -->
    <div v-for="token in tokens">
        <span>{{ token.name }}</span>
        <span>{{ token.last_used_at_formatted }}</span>
        <span>{{ token.expires_at_formatted }}</span>
        <button @click="revoke(token)">Revoke</button>
    </div>

    <!-- Create Token Form -->
    <form @submit.prevent="createToken">
        <input v-model="form.name" placeholder="Token name" />
        <select v-model="form.abilities" multiple>
            <option value="*">Full Access</option>
            <option value="tasks:read">Read Tasks</option>
            <option value="tasks:write">Write Tasks</option>
        </select>
        <input v-model="form.expires_at" type="date" />
        <button>Create Token</button>
    </form>

    <!-- Newly Created Token (shown once) -->
    <div v-if="newToken">
        <code>{{ newToken }}</code>
        <button @click="copy">Copy</button>
        <p>Save this token — it won't be shown again.</p>
    </div>
</template>
```

### Phase 6: Auth Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/auth/register` | POST | Register new user |
| `/api/auth/login` | POST | Login, returns token |
| `/api/auth/logout` | POST | Revoke current token |
| `/api/auth/me` | GET | Current user info |
| `/api/auth/tokens` | GET | List user's tokens |
| `/api/auth/tokens` | POST | Create new token (with abilities) |
| `/api/auth/tokens/{token}` | DELETE | Revoke token |

### Phase 6: API-Specific QueryBuilders (if needed)

```
app/QueryBuilders/
├── TaskIndexQuery.php              ← Web
├── ProjectIndexQuery.php           ← Web
└── Api/
    ├── TaskIndexQuery.php          ← API (different eager loading)
    └── ProjectIndexQuery.php       ← API (different eager loading)
```

Only create separate API QueryBuilders when:
- Eager loading differs (API needs more/fewer relations)
- Pagination differs (`jsonPaginate()` vs `paginate()`)
- Filter/sort differs

---

## API Routes Structure

```php
// routes/api.php

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Token Management
    Route::get('auth/tokens', [TokenController::class, 'index']);
    Route::post('auth/tokens', [TokenController::class, 'store']);
    Route::delete('auth/tokens/{token}', [TokenController::class, 'destroy']);

    // Workspace-scoped routes
    Route::middleware('ensure_workspace_selected')->group(function () {
        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);

        // Tasks
        Route::apiResource('tasks', TaskController::class);
        Route::get('tasks/kanban', [TaskController::class, 'kanban']);
        Route::patch('tasks/{task}/move', [TaskController::class, 'move']);
        Route::put('tasks/{task}/complete', [TaskController::class, 'complete']);
        Route::patch('tasks/{task}/status', [TaskController::class, 'status']);
        Route::put('tasks/{task}/assign', [TaskController::class, 'assign']);

        // Projects
        Route::apiResource('projects', ProjectController::class);
        Route::get('projects/{project}/tasks', [ProjectController::class, 'tasks']);

        // Comments
        Route::post('tasks/{task}/comments', [CommentController::class, 'store']);
        Route::delete('tasks/{task}/comments/{comment}', [CommentController::class, 'destroy']);

        // Team
        Route::get('team', [TeamController::class, 'index']);
        Route::post('team/invite', [TeamController::class, 'invite']);
        Route::delete('team/{user}', [TeamController::class, 'remove']);

        // Workspace
        Route::get('workspaces', [WorkspaceController::class, 'index']);
        Route::post('workspaces', [WorkspaceController::class, 'store']);
        Route::post('workspaces/set', [WorkspaceController::class, 'set']);
    });
});
```

---

## Token Management (from simsarpras)

### Token Model

```php
// app/Models/Token.php
class Token extends PersonalAccessToken
{
    protected $table = 'personal_access_tokens';

    protected $appends = [
        'created_at_formatted',
        'updated_at_formatted',
        'last_used_at_formatted',
        'expires_at_formatted',
    ];

    protected function createdAtFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at?->diffForHumans()
        );
    }

    // ... other formatted accessors
}
```

### Token Controller

```php
// app/Http/Controllers/Api/TokenController.php
class TokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->tokens()->get(),
        ]);
    }

    public function store(TokenData $data, Request $request): JsonResponse
    {
        $token = $request->user()->createToken(
            $data->name,
            $data->abilities ?? ['*'],
            $data->expires_at
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'name' => $data->name,
        ], 201);
    }

    public function destroy(Token $token): JsonResponse
    {
        $token->delete();

        return response()->json(['message' => 'Token revoked.']);
    }
}
```

### Token Data (with Abilities)

```php
// app/Data/TokenData.php
class TokenData extends Data
{
    public function __construct(
        public string $name,
        public ?array $abilities = ['*'],
        public ?string $expires_at = null,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
            'abilities.*' => 'string',
            'expires_at' => 'nullable|date|after:now',
        ];
    }
}
```

### Token Abilities for Service-to-Service

```php
// Create scoped token for external service
$user->createToken('payment-service', [
    'tasks:read',
    'projects:read',
    'dashboard:read',
]);

// Check ability in controller
if ($request->user()->tokenCant('tasks:read')) {
    abort(403, 'Insufficient permissions.');
}

// Or use middleware
Route::middleware('abilities:tasks:read')->group(function () {
    Route::get('tasks', [TaskController::class, 'index']);
});
```

---

## API Response Format

### Success Response

```json
{
    "data": {
        "id": "uuid",
        "title": "Task Title",
        "status": "Todo",
        "priority": "High",
        "project": {
            "id": "uuid",
            "name": "Project Name"
        }
    }
}
```

### Collection Response

```json
{
    "data": [...],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75
    }
}
```

### Error Response

```json
{
    "message": "Validation failed",
    "errors": {
        "title": ["The title field is required."]
    }
}
```

---

## API Resource Example (from simsarpras pattern)

```php
// app/Http/Resources/TaskResource.php

/** @mixin Task */
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'description'=> $this->description,
            'status'     => $this->status,
            'priority'   => $this->priority,
            'due_date'   => $this->due_date?->toDateString(),
            'sort_order' => $this->sort_order,
            'is_overdue' => $this->is_overdue,
            'completed_at' => $this->completed_at?->toISOString(),

            // Formatted attributes (from simsarpras pattern)
            'status_formatted' => $this->status->value,
            'priority_formatted' => $this->priority->value,
            'due_date_formatted' => $this->due_date?->translatedFormat('d F Y'),

            // Relations with whenLoaded
            'project'    => new ProjectResource($this->whenLoaded('project')),
            'assignee'   => new UserResource($this->whenLoaded('assignee')),
            'comments'   => CommentResource::collection($this->whenLoaded('comments')),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

---

## API Controller Example (Reusing Action)

```php
// app/Http/Controllers/Api/TaskController.php

class TaskController extends Controller
{
    public function index(TaskIndexQuery $query): JsonResponse
    {
        $tasks = $query->paginate(15);

        return TaskResource::collection($tasks)->response();
    }

    public function store(TaskData $data, TaskCreateAction $action): JsonResponse
    {
        $task = $action->execute($data);
        $task->load(['project', 'assignee']);

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Task $task): JsonResponse
    {
        $task->load(['project', 'assignee', 'comments.user']);

        return (new TaskResource($task))->response();
    }

    public function update(TaskData $data, Task $task, TaskUpdateAction $action): JsonResponse
    {
        $action->execute($data, $task);
        $task->load(['project', 'assignee']);

        return (new TaskResource($task))->response();
    }

    public function destroy(Task $task, TaskDeleteAction $action): JsonResponse
    {
        $action->execute($task);

        return response()->json(['message' => 'Task deleted.'], 200);
    }

    public function kanban(KanbanData $data, TaskKanbanQuery $query): JsonResponse
    {
        return response()->json(
            $query->getBoard($data->start_date, $data->end_date)
        );
    }

    public function move(Task $task, TaskMoveData $data, TaskMoveAction $action): JsonResponse
    {
        $this->authorize('update', $task);

        $updated = $action->execute($task, $data);

        return response()->json([
            'data' => (new TaskResource($updated))->resolve(),
            'message' => 'Task moved.',
        ]);
    }

    public function complete(Task $task, TaskCompleteAction $action): JsonResponse
    {
        $this->authorize('update', $task);

        $action->execute($task);

        return response()->json(['message' => 'Task completed.']);
    }

    public function status(Task $task, TaskStatusUpdateAction $action): JsonResponse
    {
        $this->authorize('update', $task);

        $status = request()->input('status');
        $action->execute($task, $status);

        return response()->json(['message' => "Task status updated to {$status}."]);
    }

    public function assign(Task $task, TaskAssignData $data, TaskAssignAction $action): JsonResponse
    {
        $action->execute($task, $data);

        return response()->json(['message' => 'Task assigned.']);
    }
}
```

---

## File Structure After Implementation

```
app/Http/
├── Controllers/
│   ├── Api/
│   │   ├── AuthController.php
│   │   ├── TokenController.php
│   │   ├── TaskController.php
│   │   ├── ProjectController.php
│   │   ├── CommentController.php
│   │   ├── DashboardController.php
│   │   ├── WorkspaceController.php
│   │   └── TeamController.php
│   └── (existing web controllers)
├── Resources/
│   ├── TaskResource.php
│   ├── ProjectResource.php
│   ├── CommentResource.php
│   ├── UserResource.php
│   └── OrganizationResource.php
└── (existing middleware)

app/Models/
├── Token.php (NEW - extends PersonalAccessToken)
└── (existing models)

app/Data/
├── TokenData.php (NEW)
└── (existing DTOs)

resources/js/pages/Settings/
├── Index.vue (existing - add Tokens tab)
└── Tokens.vue (NEW - token management UI)

app/QueryBuilders/
├── TaskIndexQuery.php (existing - web)
├── ProjectIndexQuery.php (existing - web)
└── Api/ (NEW - only if needed)
    ├── TaskIndexQuery.php
    └── ProjectIndexQuery.php

routes/
├── api.php (NEW)
└── web.php (existing)
```

---

## Testing Strategy

```bash
# Auth tests
php artisan make:test --phpunit Api/Auth/LoginTest
php artisan make:test --phpunit Api/Auth/RegisterTest
php artisan make:test --phpunit Api/Auth/TokenManagementTest

# Resource tests
php artisan make:test --phpunit Api/TaskApiTest
php artisan make:test --phpunit Api/ProjectApiTest
```

### Test Helper

```php
// In TestCase or trait
protected function createApiUser(array $attributes = []): array
{
    [$user, $organization] = $this->createWorkspaceUser($attributes);
    $token = $user->createToken('test')->plainTextToken;

    return [$user, $organization, $token];
}

protected function apiHeaders(string $token): array
{
    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];
}
```

### Example Test

```php
class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_tasks(): void
    {
        [$user, $org, $token] = $this->createApiUser();
        Task::factory()->count(3)->create(['organization_id' => $org->id]);

        $response = $this->getJson('/api/tasks', $this->apiHeaders($token));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'title', 'status', 'priority']],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    public function test_can_create_task(): void
    {
        [$user, $org, $token] = $this->createApiUser();
        $project = Project::factory()->create(['organization_id' => $org->id]);

        $response = $this->postJson('/api/tasks', [
            'project_id' => $project->id,
            'title' => 'New Task',
            'status' => 'Todo',
            'priority' => 'High',
        ], $this->apiHeaders($token));

        $response->assertCreated()
            ->assertJsonPath('data.title', 'New Task');
    }
}
```

---

## Summary

| Aspect | Decision |
|--------|----------|
| Auth | Sanctum tokens (not JWT) |
| Token Model | Extend PersonalAccessToken (from simsarpras) |
| Token Abilities | Scoped for service-to-service |
| Controllers | New API controllers, reuse Actions |
| Response | API Resources with formatted attributes |
| Routing | `routes/api.php`, `apiResource` |
| Middleware | `auth:sanctum` + `ensure_workspace_selected` |
| QueryBuilders | Reuse existing, create API-specific only if needed |
