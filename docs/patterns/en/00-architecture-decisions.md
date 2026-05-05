# Architecture Decisions

> Key technical decisions behind this codebase and the reasoning behind each.

---

## 1. Action over Fat Controller

**Decision:** Every write operation lives in a dedicated `Action` class, not in controllers.

**Why:**
- Single responsibility — one class, one job
- Testable in isolation without HTTP layer
- Reusable across controllers, commands, and jobs

**Trade-off:** More files. Accepted because clarity > convenience.

```
Controller → receives request
Action     → executes business logic
Model      → persists data
```

---

## 2. DTO over Form Request

**Decision:** Use Spatie Laravel Data DTOs instead of Form Request for validation.

**Why:**
- Type-safe properties (IDE autocomplete works)
- Reusable outside HTTP context (jobs, commands)
- Built-in transformation via `toModelData()`

**Trade-off:** External dependency. Accepted because the DX improvement is significant.

**Convention:**
- `{Domain}Data` for CRUD
- `{Domain}{Operation}Data` for specific operations (e.g., `TaskMoveData`)

---

## 3. Enum as Business Logic Container

**Decision:** Enums don't just hold values — they encapsulate business rules, UI metadata, and authorization groups.

**Why:**
- Single source of truth for status colors, priority weights, role permissions
- Validation rules derive from enum values automatically
- UI options (`asOptions()`) co-located with the constants

**Example:** `TaskStatus::openStatuses()` is used in queries, validation, and UI filters — defined once.

---

## 4. Multi-Tenancy via Global Scope

**Decision:** Organization isolation through `HasOrganization` trait with automatic global scope.

**Why:**
- Zero chance of forgetting `WHERE organization_id = ?`
- Transparent to developers — `Task::all()` just works
- Escape hatch via `withoutOrganizationScope()` for admin views

**Trade-off:** Implicit behavior can surprise new devs. Mitigated by documentation and naming.

**Session over URL:** Active workspace stored in session, not in route. Cleaner URLs, easier switching.

---

## 5. QueryBuilder over Raw Query Logic

**Decision:** Extend Spatie QueryBuilder into domain-specific classes (`TaskIndexQuery`, `TaskKanbanQuery`).

**Why:**
- Controller stays clean — just `$query->paginate(15)`
- Filter/sort logic reusable and testable
- Scope delegation keeps model as single source of truth

**Convention:** `{Domain}{View}Query` — e.g., `TaskIndexQuery` for list page, `TaskKanbanQuery` for board.

---

## 6. Policy with Org + Role Dual Check

**Decision:** Every policy check is `belongsToOrganization() && hasRole()`.

**Why:**
- Organization membership is always verified first
- Role permissions defined in `RoleAuth` enum, not hardcoded in policies
- Assignee override for specific operations (members can update their own tasks)

**Authorization lives in two places:**
- `Policy` — per-model authorization
- `ContextualRoleMiddleware` — per-route authorization

---

## 7. Service for Read-Only Aggregation

**Decision:** Separate `Service` classes for complex read operations (dashboard stats, reports).

**Why:**
- Actions handle writes, Services handle reads — clear separation
- `once()` memoization prevents duplicate queries in one request
- Single-query aggregation via `CASE` expressions

**When to use Service vs Action:**
- Mutates database → Action
- Reads and aggregates → Service

---

## 8. Support Classes for Cross-Cutting Concerns

**Decision:** Stateless utility classes in `app/Supports/` for things that don't belong to any domain.

**Why:**
- `GetActiveOrganization` — session management, used everywhere
- `RouteHelper` — auto-loading route files per domain
- `UserRoleContext` — role checking without model dependency

**Convention:** Static methods only. No state. No instantiation.

---

## 9. Rich Models, Not Anemic Models

**Decision:** Models contain accessors, scopes, and business methods — not just relationships and casts.

**Why:**
- `$task->is_overdue` computed once, available everywhere
- Scopes chainable and delegatable to QueryBuilder
- Business methods like `markAsCompleted()` keep domain logic close to data

**What belongs in Model vs Action:**
- Single-record operation → Model (`$task->markAsCompleted()`)
- Multi-step or external concerns → Action

---

## 10. UUID over Auto-Increment

**Decision:** All primary keys are UUIDs via `HasUuids` trait.

**Why:**
- Safe for multi-tenant (no enumeration)
- Safe for public URLs (no sequential guessing)
- No ID collision across environments

**Trade-off:** Slightly larger index. Negligible for this scale.

---

## 11. Domain-Split Routes

**Decision:** Routes split per feature in `routes/web/`, auto-loaded via `RouteHelper`.

**Why:**
- `tasks.php` is 100 lines, not 500
- Easy to find route definitions
- Middleware groups per domain (auth + workspace)

**Convention:** Custom endpoints before `Route::resource()` to avoid parameter conflicts.

---

## 12. Query-Driven Indexing

**Decision:** Every index migration documents the access pattern it serves.

**Why:**
- No mystery indexes — each one has a purpose
- Composite indexes start with `organization_id` (every query is org-scoped)
- Named indexes for readability (`tasks_org_due_date_idx`)

**Convention:** Separate migration for indexes. Schema changes ≠ performance changes.

---

## 13. Behavior Testing over Implementation Testing

**Decision:** Tests verify outcomes, not method calls.

**Why:**
- `$task->fresh()->status === TaskStatus::Done` — tests the result
- Refactoring internals doesn't break tests
- `createWorkspaceUser()` helper reduces boilerplate

**Convention:** Every test sets up workspace context (user + org + session). Multi-tenancy is not optional.

---

## 14. Thin Controller as Orchestrator

**Decision:** Controllers contain zero business logic — only routing, delegation, and response.

**Why:**
- Method injection over constructor injection (only resolve what's needed)
- Delegation map makes it clear who does what
- Flash message convention for consistent UX

**A controller method should be ≤ 10 lines.** If it's longer, something belongs in an Action or Service.

---

## 15. Sanctum for API Authentication

**Decision:** Use Laravel Sanctum for API token authentication, not JWT library.

**Why:**
- Built-in to Laravel, no extra dependency
- Database-backed tokens with instant revocation
- Fine-grained abilities per token
- Simpler than JWT for first-party SPA

**Trade-off:** Tokens stored in database (not stateless). Accepted because revocation control > statelessness.

**Implementation:**
- Custom `Token` model extends `PersonalAccessToken`
- `Sanctum::usePersonalAccessTokenModel(Token::class)` in AppServiceProvider
- Token management UI in Settings page

---

## 16. API Resources for Response Formatting

**Decision:** Use Eloquent API Resources for API responses, not Model `toArray()`.

**Why:**
- Separates API format from Model logic
- Conditional relationship loading with `whenLoaded()`
- Consistent response structure across endpoints
- IDE support with `@mixin`

**Convention:**
- Resources in `app/Http/Resources/Api/`
- One Resource per Model
- Format dates in Resource, not Model

---

## Summary

| Principle | Decision |
|-----------|----------|
| Separation of Concerns | Action (write), Service (read), Controller (orchestrate) |
| Type Safety | DTO + Enum everywhere |
| Multi-Tenancy | Global Scope, automatic, transparent |
| Reusability | QueryBuilder + Scope delegation |
| Testability | Behavior-focused, workspace helper |
| Documentation | Access patterns, delegation maps, comparison tables |
| API Auth | Sanctum tokens |
| API Response | Eloquent API Resources |
