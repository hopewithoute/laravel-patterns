# Testing Pattern

> **Feature Tests dengan Workspace Context & Factory Helpers**

## Overview

Project ini menerapkan testing strategy yang meng-handle multi-tenancy context. Setiap test harus:

1. Membuat **user + organization** (workspace context)
2. Set **session** dengan `organization_id`
3. Test behavior, bukan implementation

## Struktur Direktori

```
tests/
├── TestCase.php                         → Base test dengan helper methods
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

## Implementasi

### Base TestCase dengan Workspace Helper

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

        // 2. Act — request dengan user + session
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

### Factory dengan Enum Values

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

## Pola-Pola Kunci

### 1. `createWorkspaceUser()` Helper
Setiap test yang butuh authenticated + workspace context pakai helper ini:

```php
[$user, $organization] = $this->createWorkspaceUser();
```

> Membuat user, organization, dan attach membership dalam satu call.

### 2. Session Injection
Multi-tenancy butuh `organization_id` di session:

```php
$this->actingAs($user)
     ->withSession(['organization_id' => $organization->id])
     ->get(route('tasks.index'));
```

### 3. Inertia Assertions
Gunakan `assertInertia()` untuk test Inertia response:

```php
$response->assertInertia(fn (Assert $page) => $page
    ->component('Task/Index')        // Correct Vue component
    ->has('tasks.data', 3)           // Has pagination data
    ->where('task.id', $task->id)    // Exact value match
    ->has('options.projects')        // Data structure exists
);
```

### 4. Enum Values di Factory
Factory menggunakan `getValues()` dari Enum untuk generate realistic data:

```php
'status'   => $this->faker->randomElement(TaskStatus::getValues()),
'priority' => $this->faker->randomElement(Priority::getValues()),
```

### 5. Behavior Testing
Test **behavior**, bukan implementation:

```php
// ✅ Test behavior — "marking complete changes status and sets timestamp"
$this->assertEquals(TaskStatus::Done, $task->fresh()->status);
$this->assertNotNull($task->fresh()->completed_at);

// ❌ Test implementation — "calls markAsCompleted method"
```

---

**Referensi file:**
- `tests/TestCase.php`
- `tests/Feature/*.php`
- `database/factories/*.php`
