# Plan: Update Pattern Documentation for API Implementation

> Updating existing patterns and adding new API-specific patterns based on the REST API implementation.

---

## Overview

The API implementation introduced several new patterns and conventions that need to be documented. Some existing patterns need updates to reflect how they work in both Web and API contexts.

---

## Changes Required

### 1. Update Existing Patterns

| Pattern | File | Changes |
|---------|------|---------|
| Action Pattern | `01-action-pattern.md` | Add API controller examples showing reuse |
| DTO Pattern | `02-data-transfer-object.md` | Add API-specific DTOs (CreateTokenData) |
| QueryBuilder | `04-query-builder-pattern.md` | Add `jsonPaginate()`, API QueryBuilder injection |
| Multi-tenancy | `05-multi-tenancy.md` | Add `ApiSetOrganization` middleware, `X-Organization` header |
| Testing | `11-testing-pattern.md` | Add API testing patterns with Sanctum |
| Route Org | `10-route-organization.md` | Add `routes/api.php` structure |
| Thin Controller | `13-thin-controller.md` | Show API controller follows same pattern |

### 2. New Pattern Document

| Pattern | File | Description |
|---------|------|-------------|
| API Resource Pattern | `14-api-resource-pattern.md` | Eloquent API Resources with `@mixin`, `whenLoaded()` |

### 3. Update Architecture Decisions

| Section | Changes |
|---------|---------|
| Sanctum over JWT | Add to architecture decisions |
| API Resources | Document why Resources, not Model toArray |

---

## Detailed Changes

### 01-action-pattern.md

Add section:

```markdown
## API Controller Reuse

Actions are reused across Web and API controllers:

### Web Controller
public function store(TaskData $data, TaskCreateAction $action): RedirectResponse
{
    $task = $action->execute($data);
    return redirect()->route('tasks.show', $task);
}

### API Controller
public function store(TaskData $data, TaskCreateAction $action): TaskResource
{
    $task = $action->execute($data);
    return new TaskResource($task);
}

Same Action, different response type.
```

### 02-data-transfer-object.md

Add section:

```markdown
## API-Specific DTOs

Some DTOs are API-only:

### CreateTokenData
- name: string
- abilities: array (default: ['*'])
- expires_at: ?DateTime (ISO 8601)

Used by TokenController for API token creation.
```

### 04-query-builder-pattern.md

Add section:

```markdown
## API Usage with jsonPaginate()

API controllers use QueryBuilder injection with jsonPaginate():

### Controller
public function index(TaskIndexQuery $query): AnonymousResourceCollection
{
    return TaskResource::collection($query->jsonPaginate());
}

### Package
Requires: spatie/laravel-json-api-paginate

### API-Specific QueryBuilders
Create separate QueryBuilders when:
- Eager loading differs
- Pagination differs (jsonPaginate vs paginate)
- Filter/sort differs
```

### 05-multi-tenancy.md

Add section:

```markdown
## API Multi-tenancy

API uses X-Organization header instead of session.

### Middleware
ApiSetOrganization middleware:
1. Reads X-Organization header
2. Sets organization_id in session
3. GetActiveOrganization works for DTOs

### Usage
Header: X-Organization: {organization_id}

### Testing
$this->withHeader('X-Organization', $org->id)
```

### 11-testing-pattern.md

Add section:

```markdown
## API Testing with Sanctum

### Setup
use Laravel\Sanctum\Sanctum;

Sanctum::actingAs($user);

### Token Tests
$token = $user->createToken('test');
$this->withHeader('Authorization', "Bearer {$token->plainTextToken}")

### Organization Header
$this->withHeader('X-Organization', $org->id)
```

### 14-api-resource-pattern.md (NEW)

```markdown
# API Resource Pattern

> Standardized API responses using Eloquent API Resources.

## Convention

- File: app/Http/Resources/Api/{Model}Resource.php
- Use @mixin for IDE support
- Use $this->whenLoaded() for relationships
- Format attributes in Resource, not Model

## Structure

#[Mixin(Model::class)]
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'created_at' => $this->created_at->toIso8601String(),
            'project' => new ProjectResource($this->whenLoaded('project')),
        ];
    }
}
```

---

## Execution Order

1. Update `04-query-builder-pattern.md` (most impactful)
2. Update `05-multi-tenancy.md` (API-specific)
3. Create `14-api-resource-pattern.md` (new pattern)
4. Update `11-testing-pattern.md` (testing patterns)
5. Update `01-action-pattern.md` (reuse examples)
6. Update `02-data-transfer-object.md` (API DTOs)
7. Update `10-route-organization.md` (API routes)
8. Update `13-thin-controller.md` (API controllers)
9. Update `00-architecture-decisions.md` (Sanctum decision)

---

## Output

- 8 updated pattern documents (EN + ID)
- 1 new pattern document (EN + ID)
- Updated architecture decisions

Total: ~20 files to edit/create
