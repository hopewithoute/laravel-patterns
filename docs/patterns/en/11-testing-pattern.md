# Testing Pattern

> **Feature Tests with Workspace Context & Factory Helpers**

## Overview

This project implements a testing strategy that handles multi-tenancy context. Each test must:

1. Create a **user + organization** (workspace context)
2. Set **session** with `organization_id`
3. Test behavior, not implementation

## Directory Structure

```
tests/
├── TestCase.php                         → Base test with helper methods
├── Feature/
│   ├── CommentControllerTest.php
│   ├── ProjectControllerTest.php
│   ├── TaskControllerTest.php
│   ├── TaskKanbanEndpointTest.php
│   ├── TaskMoveEndpointTest.php
│   ├── UserManagementControllerTest.php
│   └── WorkspaceControllerTest.php
└── Unit/
    └── ExampleTest.php
```

## Implementation

### Base TestCase with Workspace Helper

```php
abstract class TestCase extends BaseTestCase
{
    /**
     * Helper to create a user within an organization.
     * Returns [User, Organization] tuple.
     */
    protected function createWorkspaceUser(
        array $userAttributes = [],
        array $orgAttributes = []
    ): array {
        $organization = Organization::factory()->create($orgAttributes);
        $user = User::factory()->create(array_merge([
            'organization_id' => $organization->id,
        ], $userAttributes));

        // Attach user to organization with admin role
        $organization->members()->attach($user->id, [
            'role'      => 'admin',
            'joined_at' => now(),
        ]);

        return [$user, $organization];
    }
}
```

### Feature Test

```php
class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_list_tasks(): void
    {
        // 1. Setup workspace context
        [$user, $organization] = $this->createWorkspaceUser();
        Task::factory()->count(3)->create(['organization_id' => $organization->id]);

        // 2. Act — request with user + session
        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->get(route('tasks.index'));

        // 3. Assert — Inertia response assertions
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Task/Index')
            ->has('tasks.data', 3)
            ->has('filters.statuses')
            ->has('filters.priorities')
        );
    }

    public function test_it_can_store_a_new_task(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $taskData = [
            'project_id'  => $project->id,
            'title'       => 'New Test Task',
            'description' => 'Test Description',
            'status'      => TaskStatus::Todo,
            'priority'    => Priority::Medium,
            'due_date'    => now()->addDays(5)->toDateString(),
        ];

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->post(route('tasks.store'), $taskData);

        // Assert database state
        $this->assertDatabaseHas('tasks', [
            'title'           => 'New Test Task',
            'organization_id' => $organization->id,
            'project_id'      => $project->id,
        ]);

        // Assert redirect
        $task = Task::where('title', 'New Test Task')->first();
        $response->assertRedirect(route('tasks.show', $task));
    }

    public function test_it_can_mark_task_as_completed(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $task = Task::factory()->create([
            'organization_id' => $organization->id,
            'status'          => TaskStatus::Todo,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->put(route('tasks.complete', $task));

        $response->assertRedirect();
        $this->assertEquals(TaskStatus::Done, $task->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);
    }
}
```

### Factory with Enum Values

```php
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'project_id'      => Project::factory(),
            'assigned_to'     => User::factory(),
            'title'           => $this->faker->sentence(),
            'description'     => $this->faker->paragraph(),
            'status'          => $this->faker->randomElement(TaskStatus::getValues()),
            'priority'        => $this->faker->randomElement(Priority::getValues()),
            'due_date'        => $this->faker->dateTimeBetween('now', '+1 month'),
            'sort_order'      => 0,
        ];
    }
}
```

## Key Patterns

### 1. `createWorkspaceUser()` Helper
Every test needing authenticated + workspace context uses this helper:

```php
[$user, $organization] = $this->createWorkspaceUser();
```

> Creates user, organization, and attaches membership in one call.

### 2. Session Injection
Multi-tenancy requires `organization_id` in session:

```php
$this->actingAs($user)
     ->withSession(['organization_id' => $organization->id])
     ->get(route('tasks.index'));
```

### 3. Inertia Assertions
Use `assertInertia()` to test Inertia responses:

```php
$response->assertInertia(fn (Assert $page) => $page
    ->component('Task/Index')        // Correct Vue component
    ->has('tasks.data', 3)           // Has pagination data
    ->where('task.id', $task->id)    // Exact value match
    ->has('options.projects')        // Data structure exists
);
```

### 4. Enum Values in Factories
Factories use `getValues()` from Enums to generate realistic data:

```php
'status'   => $this->faker->randomElement(TaskStatus::getValues()),
'priority' => $this->faker->randomElement(Priority::getValues()),
```

### 5. Behavior Testing
Test **behavior**, not implementation:

```php
// ✅ Test behavior — "marking complete changes status and sets timestamp"
$this->assertEquals(TaskStatus::Done, $task->fresh()->status);
$this->assertNotNull($task->fresh()->completed_at);

// ❌ Test implementation — "calls markAsCompleted method"
```

## API Testing with Sanctum

### Setup

```php
use Laravel\Sanctum\Sanctum;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;
}
```

### Acting as Sanctum User

```php
// Simple authentication
Sanctum::actingAs($user);

// With specific abilities
Sanctum::actingAs($user, ['tasks:read', 'tasks:write']);
```

### API Test Structure

```php
public function test_can_list_tasks(): void
{
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->addMember($user);

    Task::factory(3)->create([
        'organization_id' => $organization->id,
    ]);

    $this->withHeader('X-Organization', $organization->id)
         ->getJson('/api/tasks')
         ->assertOk()
         ->assertJsonStructure([
             'data' => [
                 '*' => ['id', 'title', 'status'],
             ],
             'links',
             'meta',
         ])
         ->assertJsonCount(3, 'data');
}
```

### Token Management Tests

```php
public function test_user_can_create_token(): void
{
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/auth/tokens', [
        'name' => 'My App',
        'abilities' => ['tasks:read'],
    ])
    ->assertOk()
    ->assertJsonStructure(['token', 'token' => ['id', 'name']]);
}
```

### Multi-tenancy in API Tests

```php
public function test_cannot_access_other_organization_tasks(): void
{
    $user = User::factory()->create();
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();
    $org1->addMember($user);

    $task = Task::factory()->create([
        'organization_id' => $org2->id,
    ]);

    $this->withHeader('X-Organization', $org1->id)
         ->getJson("/api/tasks/{$task->id}")
         ->assertNotFound();
}
```

### Authentication Tests

```php
public function test_cannot_access_without_token(): void
{
    $this->getJson('/api/tasks')
         ->assertUnauthorized();
}

public function test_login_returns_token(): void
{
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ])
    ->assertOk()
    ->assertJsonStructure(['token', 'user']);
}
```

### API Test Helpers

```php
// Get headers for API requests
protected function apiHeaders(Organization $organization): array
{
    return [
        'X-Organization' => $organization->id,
        'Accept' => 'application/json',
    ];
}
```

---

**Reference files:**
- `tests/TestCase.php`
- `tests/Feature/Api/**/*.php`
- `tests/Feature/*.php`
- `database/factories/*.php`
