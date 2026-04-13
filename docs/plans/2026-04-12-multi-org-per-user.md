# Multi-Organization Per User Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Migrate from single organization per user to multiple organizations per user using a pivot table.

**Architecture:** Introduce `organization_user` pivot table with `role` column to support many-to-many relationship. Users can belong to multiple organizations with different roles per organization. Active organization stored in session, validated against membership.

**Tech Stack:** Laravel 11, Inertia.js, Vue 3, SQLite

---

## Task 1: Create Pivot Table Migration

**Files:**

- Create: `database/migrations/2026_04_12_000001_create_organization_user_table.php`

**Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_user', function (Blueprint $table) {
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->primary(['organization_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_user');
    }
};
```

**Step 2: Run migration**

Run: `php artisan migrate`
Expected: "Migration completed successfully"

**Step 3: Commit**

```bash
git add database/migrations/2026_04_12_000001_create_organization_user_table.php
git commit -m "feat: add organization_user pivot table for multi-org support"
```

---

## Task 2: Update User Model

**Files:**

- Modify: `app/Models/User.php`

**Step 1: Update the User model**

Replace the `organization()` relationship and add new methods:

```php
<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'organization_id', // Keep for backward compatibility during migration
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Organizations this user belongs to (many-to-many).
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user', 'user_id', 'organization_id')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Tasks assigned to this user.
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * Comments created by this user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Scope to get active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if user belongs to an organization.
     */
    public function belongsToOrganization(int $organizationId): bool
    {
        return $this->organizations()->where('organizations.id', $organizationId)->exists();
    }

    /**
     * Get user's role in an organization.
     */
    public function getRoleInOrganization(int $organizationId): ?string
    {
        $pivot = $this->organizations()->where('organizations.id', $organizationId)->first();
        return $pivot?->pivot->role;
    }

    /**
     * Check if user is admin in an organization.
     */
    public function isAdminInOrganization(int $organizationId): bool
    {
        return $this->getRoleInOrganization($organizationId) === 'admin';
    }

    /**
     * Get tasks count for dashboard.
     */
    public function getOpenTasksCount(): int
    {
        return $this->assignedTasks()
            ->whereIn('status', \App\Enums\TaskStatus::openStatuses())
            ->count();
    }
}
```

**Step 2: Commit**

```bash
git add app/Models/User.php
git commit -m "feat: update User model with many-to-many organization relationship"
```

---

## Task 3: Update Organization Model

**Files:**

- Modify: `app/Models/Organization.php`

**Step 1: Update the Organization model**

Update the `members()` relationship:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'is_active',
        'invite_code',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $organization) {
            if (empty($organization->slug)) {
                $organization->slug = \Str::slug($organization->name);
            }
            if (empty($organization->invite_code)) {
                $organization->invite_code = strtoupper(\Str::random(8));
            }
        });
    }

    /**
     * Get all users belonging to this organization (many-to-many).
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user', 'organization_id', 'user_id')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get all projects in this organization.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Scope to get only active organizations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get route key name for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Check if user is a member of this organization.
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('users.id', $user->id)->exists();
    }

    /**
     * Get user's role in this organization.
     */
    public function getMemberRole(User $user): ?string
    {
        $member = $this->members()->where('users.id', $user->id)->first();
        return $member?->pivot->role;
    }

    /**
     * Add user to organization with role.
     */
    public function addMember(User $user, string $role = 'member'): void
    {
        $this->members()->syncWithoutDetaching([
            $user->id => ['role' => $role, 'joined_at' => now()],
        ]);
    }

    /**
     * Remove user from organization.
     */
    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
    }
}
```

**Step 2: Commit**

```bash
git add app/Models/Organization.php
git commit -m "feat: update Organization model with many-to-many members relationship"
```

---

## Task 4: Update GetActiveOrganization Helper

**Files:**

- Modify: `app/Supports/GetActiveOrganization.php`

**Step 1: Update to validate membership**

```php
<?php

namespace App\Supports;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class GetActiveOrganization
{
    /**
     * Get the selected organization ID from session.
     */
    public static function getSelected(): ?int
    {
        return Session::get('organization_id');
    }

    /**
     * Get the selected organization model (validates membership).
     */
    public static function get(): ?Organization
    {
        $id = self::getSelected();
        if (!$id) {
            return null;
        }

        $user = Auth::user();
        if (!$user) {
            return null;
        }

        // Validate user is member of this organization
        return once(fn () => $user->organizations()->find($id));
    }

    /**
     * Set the active organization (validates membership).
     */
    public static function set(int $organizationId): bool
    {
        $user = Auth::user();
        if (!$user || !$user->belongsToOrganization($organizationId)) {
            return false;
        }

        Session::put('organization_id', $organizationId);
        return true;
    }

    /**
     * Set organization without validation (for registration/creation).
     */
    public static function setWithoutValidation(int $organizationId): void
    {
        Session::put('organization_id', $organizationId);
    }

    /**
     * Clear the active organization.
     */
    public static function clear(): void
    {
        Session::forget('organization_id');
    }

    /**
     * Check if an organization is selected.
     */
    public static function hasSelected(): bool
    {
        return Session::has('organization_id');
    }
}
```

**Step 2: Commit**

```bash
git add app/Supports/GetActiveOrganization.php
git commit -m "feat: validate organization membership in GetActiveOrganization"
```

---

## Task 5: Update RegisterController

**Files:**

- Modify: `app/Http/Controllers/Auth/RegisterController.php`

**Step 1: Update to use pivot table**

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Supports\GetActiveOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Register', [
            'invite_code' => $request->query('invite'),
            'organization' => $request->query('invite')
                ? Organization::where('invite_code', $request->query('invite'))->first(['name', 'invite_code'])
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'invite_code' => ['nullable', 'string', 'exists:organizations,invite_code'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => true,
        ]);

        // If invite code provided, attach to organization
        if (!empty($validated['invite_code'])) {
            $organization = Organization::where('invite_code', $validated['invite_code'])->first();
            if ($organization) {
                $organization->addMember($user, 'member');
                auth()->login($user);
                GetActiveOrganization::setWithoutValidation($organization->id);
                return redirect()->route('dashboard.index')
                    ->with('success', 'Account created and joined organization successfully.');
            }
        }

        auth()->login($user);
        return redirect()->route('workspace.select')
            ->with('success', 'Account created successfully. Please select or create a workspace.');
    }
}
```

**Step 2: Commit**

```bash
git add app/Http/Controllers/Auth/RegisterController.php
git commit -m "feat: use pivot table for organization membership in registration"
```

---

## Task 6: Update LoginController

**Files:**

- Modify: `app/Http/Controllers/Auth/LoginController.php`

**Step 1: Update to use pivot table**

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Supports\GetActiveOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (auth()->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = auth()->user();

            // Get user's first organization
            $firstOrg = $user->organizations()->first();

            if ($firstOrg) {
                GetActiveOrganization::setWithoutValidation($firstOrg->id);
                return redirect()->intended(route('dashboard.index'));
            }

            return redirect()->route('workspace.select');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        GetActiveOrganization::clear();

        return redirect()->route('login');
    }
}
```

**Step 2: Commit**

```bash
git add app/Http/Controllers/Auth/LoginController.php
git commit -m "feat: use pivot table for organization lookup in login"
```

---

## Task 7: Update WorkspaceController

**Files:**

- Modify: `app/Http/Controllers/WorkspaceController.php`

**Step 1: Update to use pivot table**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Supports\GetActiveOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function select(Request $request): Response
    {
        $user = $request->user();

        $organizations = $user->organizations()
            ->get(['organizations.id', 'organizations.name', 'organizations.slug', 'organizations.logo']);

        return Inertia::render('Workspace/Select', [
            'organizations' => $organizations,
        ]);
    }

    public function set(Request $request): RedirectResponse
    {
        $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
        ]);

        $user = $request->user();

        // Validate membership
        if (!$user->belongsToOrganization($request->organization_id)) {
            return back()->withErrors(['organization_id' => 'You do not have access to this organization.']);
        }

        GetActiveOrganization::setWithoutValidation($request->organization_id);

        return redirect()->intended(route('dashboard.index'))
            ->with('success', 'Workspace selected.');
    }

    public function create(): Response
    {
        return Inertia::render('Workspace/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $organization = Organization::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        // Add user as admin
        $organization->addMember($request->user(), 'admin');

        // Set as active workspace
        GetActiveOrganization::setWithoutValidation($organization->id);

        return redirect()->route('dashboard.index')
            ->with('success', 'Workspace created successfully.');
    }
}
```

**Step 2: Commit**

```bash
git add app/Http/Controllers/WorkspaceController.php
git commit -m "feat: use pivot table for organization membership in workspace controller"
```

---

## Task 8: Update UserManagementController

**Files:**

- Modify: `app/Http/Controllers/UserManagementController.php`

**Step 1: Update to use pivot table**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Supports\GetActiveOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(): Response
    {
        $organizationId = GetActiveOrganization::getSelected();

        if (!$organizationId) {
            return redirect()->route('workspace.select');
        }

        $organization = Organization::findOrFail($organizationId);

        $members = $organization->members()
            ->paginate(20);

        return Inertia::render('Team/Index', [
            'organization' => $organization,
            'members' => $members,
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $organizationId = GetActiveOrganization::getSelected();
        $organization = Organization::findOrFail($organizationId);

        $user = User::where('email', $validated['email'])->first();

        // Check if user already in organization
        if ($organization->hasMember($user)) {
            return back()->withErrors(['email' => 'User is already a member of this organization.']);
        }

        // Add user to organization
        $organization->addMember($user, 'member');

        return back()->with('success', "Invited {$user->email} to your team.");
    }

    public function remove(User $user): RedirectResponse
    {
        $organizationId = GetActiveOrganization::getSelected();

        // Can't remove yourself
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot remove yourself from the team.']);
        }

        $organization = Organization::findOrFail($organizationId);

        // Check user belongs to this org
        if (!$organization->hasMember($user)) {
            return back()->withErrors(['error' => 'User does not belong to this organization.']);
        }

        $organization->removeMember($user);

        return back()->with('success', "Removed {$user->name} from the team.");
    }

    public function inviteCode(): Response
    {
        $organizationId = GetActiveOrganization::getSelected();
        $organization = Organization::findOrFail($organizationId);

        return Inertia::render('Team/Invite', [
            'organization' => $organization,
        ]);
    }

    public function regenerateCode(): RedirectResponse
    {
        $organizationId = GetActiveOrganization::getSelected();
        $organization = Organization::findOrFail($organizationId);

        $organization->update([
            'invite_code' => strtoupper(\Str::random(8)),
        ]);

        return back()->with('success', 'Invite code regenerated.');
    }
}
```

**Step 2: Commit**

```bash
git add app/Http/Controllers/UserManagementController.php
git commit -m "feat: use pivot table for team management"
```

---

## Task 9: Update Policies

**Files:**

- Modify: `app/Policies/ProjectPolicy.php`
- Modify: `app/Policies/TaskPolicy.php`

**Step 1: Update ProjectPolicy**

```php
<?php

namespace App\Policies;

use App\Enums\RoleAuth;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $user->belongsToOrganization($project->organization_id);
    }

    public function create(User $user): bool
    {
        return RoleAuth::canManageProjects();
    }

    public function update(User $user, Project $project): bool
    {
        return $user->belongsToOrganization($project->organization_id)
            && RoleAuth::canManageProjects();
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->belongsToOrganization($project->organization_id)
            && RoleAuth::canManageProjects();
    }
}
```

**Step 2: Update TaskPolicy**

```php
<?php

namespace App\Policies;

use App\Enums\RoleAuth;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->belongsToOrganization($task->organization_id);
    }

    public function create(User $user): bool
    {
        return RoleAuth::canManageProjects();
    }

    public function update(User $user, Task $task): bool
    {
        return $user->belongsToOrganization($task->organization_id) && (
            RoleAuth::canManageProjects() || $task->assigned_to === $user->id
        );
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->belongsToOrganization($task->organization_id)
            && RoleAuth::canManageProjects();
    }
}
```

**Step 3: Commit**

```bash
git add app/Policies/ProjectPolicy.php app/Policies/TaskPolicy.php
git commit -m "feat: use membership check instead of organization_id in policies"
```

---

## Task 10: Update UserRoleContext

**Files:**

- Modify: `app/Supports/UserRoleContext.php`

**Step 1: Update to check org-specific role**

```php
<?php

namespace App\Supports;

use App\Models\User;

class UserRoleContext
{
    /**
     * Check if user has a global role.
     */
    public static function checkGlobalRole(User $user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }

    /**
     * Check if user has a contextual role in current organization.
     * Uses pivot role if available, falls back to global roles.
     */
    public static function checkContextualRole(User $user, array $roles): bool
    {
        $orgId = GetActiveOrganization::getSelected();

        if ($orgId) {
            $orgRole = $user->getRoleInOrganization($orgId);
            if ($orgRole === 'admin') {
                return true; // Admin has all permissions
            }
        }

        return $user->hasAnyRole($roles);
    }

    /**
     * Check if user can manage projects.
     */
    public static function canManageProjects(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return self::checkContextualRole($user, \App\Enums\RoleAuth::canManageProjects());
    }

    /**
     * Check if user can manage members.
     */
    public static function canManageMembers(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return self::checkContextualRole($user, \App\Enums\RoleAuth::canManageMembers());
    }
}
```

**Step 2: Commit**

```bash
git add app/Supports/UserRoleContext.php
git commit -m "feat: support organization-specific roles in UserRoleContext"
```

---

## Task 11: Update DatabaseSeeder

**Files:**

- Modify: `database/seeders/DatabaseSeeder.php`

**Step 1: Update seeder to use pivot table**

```php
<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create default organization
        $organization = Organization::create([
            'name' => 'Default Organization',
            'slug' => 'default',
            'description' => 'Default workspace for development and testing',
            'is_active' => true,
        ]);

        // Create default user
        $user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);

        // Add user to organization as admin
        $organization->addMember($user, 'admin');
    }
}
```

**Step 2: Commit**

```bash
git add database/seeders/DatabaseSeeder.php
git commit -m "feat: use pivot table in database seeder"
```

---

## Task 12: Migrate Existing Data

**Files:**

- Create: `database/migrations/2026_04_12_000002_migrate_existing_organization_users.php`

**Step 1: Create data migration**

```php
<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing organization_id from users to pivot table
        $users = DB::table('users')
            ->whereNotNull('organization_id')
            ->get(['id', 'organization_id']);

        foreach ($users as $user) {
            // Check if organization exists
            $orgExists = DB::table('organizations')->where('id', $user->organization_id)->exists();

            if ($orgExists) {
                // Insert into pivot table
                DB::table('organization_user')->insertOrIgnore([
                    'organization_id' => $user->organization_id,
                    'user_id' => $user->id,
                    'role' => 'member',
                    'joined_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Clear pivot table
        DB::table('organization_user')->truncate();
    }
};
```

**Step 2: Run migration**

Run: `php artisan migrate`
Expected: "Migration completed successfully"

**Step 3: Commit**

```bash
git add database/migrations/2026_04_12_000002_migrate_existing_organization_users.php
git commit -m "feat: add migration to port existing organization_id to pivot table"
```

---

## Task 13: Update HasOrganization Trait

**Files:**

- Modify: `app/Traits/HasOrganization.php`

**Step 1: Update trait to work with pivot**

```php
<?php

namespace App\Traits;

use App\Enums\RoleAuth;
use App\Models\Organization;
use App\Supports\GetActiveOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Scope;

/**
 * Trait for multi-tenancy support.
 * Automatically scopes all queries to the current organization.
 */
trait HasOrganization
{
    protected static function bootHasOrganization(): void
    {
        // 1. Apply Global Scope to filter all SELECT queries automatically.
        static::addGlobalScope('organization', new class implements Scope
        {
            public function apply(Builder $builder, Model $model)
            {
                $orgId = GetActiveOrganization::getSelected();

                if ($orgId) {
                    $builder->where($model->getTable().'.organization_id', $orgId);
                }
            }
        });

        // 2. Event listener that runs when model is being CREATED (INSERT).
        static::creating(function ($model) {
            if (empty($model->organization_id)) {
                $orgId = GetActiveOrganization::getSelected();
                if ($orgId) {
                    $model->organization_id = $orgId;
                }
            }
        });
    }

    /**
     * Define the relationship that this model "belongs to" one Organization.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Scope to filter by specific organization.
     */
    public function scopeFilterByOrganization(Builder $query, ?int $organizationId = null): void
    {
        $query->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId));
    }

    /**
     * Scope to include data without organization scope (bypass global scope).
     */
    public function scopeWithoutOrganizationScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('organization');
    }
}
```

**Step 2: Commit**

```bash
git add app/Traits/HasOrganization.php
git commit -m "feat: simplify HasOrganization trait for multi-org support"
```

---

## Task 14: Add Organization Switcher to Frontend

**Files:**

- Modify: `resources/js/layouts/AppLayout.vue`

**Step 1: Add organization switcher dropdown**

Add to the sidebar header area:

```vue
<!-- In the sidebar header, after the logo/title -->
<div v-if="$page.props.auth.user" class="mb-4">
    <select
        v-model="activeOrgId"
        @change="switchOrganization"
        class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
    >
        <option v-for="org in organizations" :key="org.id" :value="org.id">
            {{ org.name }}
        </option>
    </select>
</div>
```

Add script logic:

```javascript
import { router } from '@inertiajs/vue3'

const props = defineProps({
    organizations: Array,
    activeOrganizationId: Number,
})

const activeOrgId = ref(props.activeOrganizationId)

const switchOrganization = () => {
    router.post('/workspace/set', { organization_id: activeOrgId.value })
}
```

**Step 2: Commit**

```bash
git add resources/js/layouts/AppLayout.vue
git commit -m "feat: add organization switcher dropdown to sidebar"
```

---

## Task 15: Update AppLayout to Pass Organizations

**Files:**

- Modify: `app/Http/Middleware/HandleInertiaRequests.php` (if exists)
- Or create: `app/Http/Middleware/ShareInertiaData.php`

**Step 1: Create/Update middleware to share organizations**

```php
<?php

namespace App\Http\Middleware;

use App\Supports\GetActiveOrganization;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth.user.organizations' => fn () => $request->user()
                ? $request->user()->organizations()->get(['organizations.id', 'organizations.name'])
                : [],
            'auth.active_organization_id' => fn () => GetActiveOrganization::getSelected(),
        ]);
    }
}
```

**Step 2: Register middleware in bootstrap/app.php if not already**

**Step 3: Commit**

```bash
git add app/Http/Middleware/HandleInertiaRequests.php
git commit -m "feat: share user organizations and active org with Inertia"
```

---

## Task 16: Run Tests and Verify

**Step 1: Run existing tests**

Run: `php artisan test`
Expected: All tests pass

**Step 2: Manual verification**

1. Register new user - should create without organization
2. Create organization - user should be admin
3. Invite existing user - user should join as member
4. Switch between organizations (if user in multiple)
5. Verify data isolation between organizations

---

## Task 17: Optional - Remove organization_id from users table

**Files:**

- Create: `database/migrations/2026_04_12_000003_remove_organization_id_from_users.php`

**Note:** Only do this after verifying everything works. This is optional for cleanup.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        });
    }
};
```

---

## Summary

After completing all tasks:

- Users can belong to multiple organizations
- Each membership has a role (admin/member)
- Active organization stored in session
- Data properly scoped to active organization
- Organization switching supported in UI
