# Task Weekly Kanban Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a switchable `Quicklist` and weekly `Kanban` view to the task index, where kanban loads tasks by visible date range with horizontal weekly virtualization and a `Today` jump action.

**Architecture:** Keep the existing Inertia `TaskController@index` paginated flow for `Quicklist`, and add a separate JSON kanban endpoint plus dedicated query object for weekly range loading. Implement kanban as custom board components and helpers inside the existing task index page, while reusing existing shadcn-vue style UI primitives for shared controls and cards.

**Tech Stack:** Laravel 13, Inertia, Vue 3, Tailwind CSS v4, Node test runner, PHPUnit

---

## File Map

- Modify: `routes/web/tasks.php`
  Purpose: add the async kanban JSON route alongside the existing task resource routes.
- Modify: `app/Http/Controllers/TaskController.php`
  Purpose: keep `index()` for quicklist and add a dedicated `kanban()` action that validates range input and returns grouped JSON data.
- Create: `app/QueryBuilders/TaskKanbanQuery.php`
  Purpose: encapsulate kanban filters, date-range constraints, eager loads, and grouping logic following the existing query-builder pattern.
- Create: `tests/Feature/TaskKanbanEndpointTest.php`
  Purpose: cover range validation, date grouping, no-due-date grouping, and filter behavior for the new endpoint.
- Create: `resources/js/lib/task-kanban.js`
  Purpose: hold pure helpers for week math, column generation, chunk merge, and matrix shaping so they can be tested without Vue rendering.
- Create: `tests/js/task-kanban.test.mjs`
  Purpose: cover kanban helper behavior and page-level structural expectations.
- Modify: `resources/js/pages/Task/Index.vue`
  Purpose: host the view switch, keep quicklist intact, wire kanban state/fetching, and render the new board.
- Create: `resources/js/components/task/TaskViewSwitch.vue`
  Purpose: isolated two-mode switch using existing button/toggle styling patterns.
- Create: `resources/js/components/task/TaskKanbanBoard.vue`
  Purpose: own viewport, horizontal scroll observers, week fetch triggers, and board-level states.
- Create: `resources/js/components/task/TaskKanbanColumn.vue`
  Purpose: render a single kanban column shell and stacked task cards.
- Create: `resources/js/components/task/TaskKanbanCard.vue`
  Purpose: render a single task card using existing badge/card primitives and task metadata.

### Task 1: Lock the backend contract with failing endpoint tests

**Files:**

- Create: `tests/Feature/TaskKanbanEndpointTest.php`
- Modify: `routes/web/tasks.php`
- Modify: `app/Http/Controllers/TaskController.php`

- [ ] **Step 1: Write the failing test**

Create feature tests that assert:

- authenticated users can call a new kanban endpoint on the task area
- the endpoint rejects missing or invalid `start_date` / `end_date`
- tasks with due dates in the requested range are grouped under their `Y-m-d` keys
- tasks with `null` due dates are grouped under `no_due_date`
- existing filters such as `filter[status]`, `filter[priority]`, and `filter[search]` affect results

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/TaskKanbanEndpointTest.php`
Expected: FAIL because the route/action/query logic does not exist yet.

- [ ] **Step 3: Write minimal implementation**

Add the new kanban route and controller action with just enough validation/response structure to satisfy the first endpoint assertions before full grouping logic is added.

- [ ] **Step 4: Run test to verify it passes or fails for the next missing behavior**

Run: `php artisan test tests/Feature/TaskKanbanEndpointTest.php`
Expected: the initial missing-route failure is replaced by narrower failures around grouping or filtering.

### Task 2: Implement the kanban query object following existing backend patterns

**Files:**

- Create: `app/QueryBuilders/TaskKanbanQuery.php`
- Modify: `app/Http/Controllers/TaskController.php`
- Modify: `tests/Feature/TaskKanbanEndpointTest.php`

- [ ] **Step 1: Extend the failing backend test with exact grouping expectations**

Add assertions for:

- `meta.start_date`, `meta.end_date`, and `meta.today`
- `columns` containing `no_due_date` plus each daily key in the requested range
- task records including only the fields the kanban card needs

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/TaskKanbanEndpointTest.php`
Expected: FAIL on the missing query/grouping behavior.

- [ ] **Step 3: Write minimal implementation**

Implement `TaskKanbanQuery` to:

- start from `Task::query()->with(['project:id,name,color', 'assignee:id,name,avatar'])`
- follow the same allowed filter style as `TaskIndexQuery` where applicable
- return tasks that either fall inside the inclusive date range or have `due_date = null`
- serialize a stable response grouped into `tasks_by_column`
- order tasks predictably within a column, prioritizing dated tasks by `due_date`, then `priority`, then `title`

Keep grouping logic in the query class or a focused helper method so the controller remains thin.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/TaskKanbanEndpointTest.php`
Expected: PASS.

### Task 3: Lock the frontend kanban helpers and page structure with failing tests

**Files:**

- Create: `resources/js/lib/task-kanban.js`
- Create: `tests/js/task-kanban.test.mjs`
- Modify: `resources/js/pages/Task/Index.vue`

- [ ] **Step 1: Write the failing test**

Create node tests that assert:

- helper functions compute the week start for a given date
- a requested weekly range produces daily column keys plus `no_due_date`
- chunk merge logic deduplicates columns and keeps stable order
- `Task/Index.vue` imports the new kanban helpers/components
- `Task/Index.vue` contains both `Quicklist` and `Kanban` view affordances plus a `Today` action

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test tests/js/task-kanban.test.mjs`
Expected: FAIL because the helpers/components do not exist yet.

- [ ] **Step 3: Write minimal implementation**

Add `resources/js/lib/task-kanban.js` with small pure functions for:

- `startOfWeek`
- `buildWeekColumns`
- `mergeKanbanColumns`
- `mergeTasksByColumn`

Only implement enough for the first helper tests to pass.

- [ ] **Step 4: Run test to verify it passes or narrows**

Run: `node --test tests/js/task-kanban.test.mjs`
Expected: helper tests pass, page-structure assertions still fail until UI integration is added.

### Task 4: Build the switchable task index UI and weekly kanban components

**Files:**

- Modify: `resources/js/pages/Task/Index.vue`
- Create: `resources/js/components/task/TaskViewSwitch.vue`
- Create: `resources/js/components/task/TaskKanbanBoard.vue`
- Create: `resources/js/components/task/TaskKanbanColumn.vue`
- Create: `resources/js/components/task/TaskKanbanCard.vue`
- Modify: `tests/js/task-kanban.test.mjs`

- [ ] **Step 1: Extend the failing frontend test with UI integration expectations**

Add assertions that:

- `Task/Index.vue` keeps the existing quicklist/pagination path
- the kanban board component is rendered only in kanban mode
- kanban uses shared UI primitives such as `Badge`, `Card`, and `Button` where appropriate
- custom components exist for the board viewport and column/card layout

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test tests/js/task-kanban.test.mjs`
Expected: FAIL on the missing kanban component integration.

- [ ] **Step 3: Write minimal implementation**

Implement the page and components so that:

- view mode defaults to `Quicklist`
- quicklist markup remains intact
- kanban mode shows a weekly board beginning at the current week's start
- the board includes a `Today` button
- the board fetches the current weekly chunk, then lazily fetches adjacent chunks as horizontal scroll approaches the edges
- task cards stack vertically within each column
- a `No Due Date` column is always present
- loading, empty-column, and retry states are visible

Prefer existing shadcn-vue style primitives already in the repo for standard controls and surfaces. Use custom code only for board-specific viewport, virtualization, and matrix rendering.

- [ ] **Step 4: Run test to verify it passes**

Run: `node --test tests/js/task-kanban.test.mjs`
Expected: PASS.

### Task 5: Verify end-to-end integration and prevent regressions

**Files:**

- Modify: `tests/Feature/TaskKanbanEndpointTest.php`
- Modify: `tests/js/task-kanban.test.mjs`
- Modify: `resources/js/pages/Task/Index.vue`
- Modify: `resources/js/components/task/TaskKanbanBoard.vue`

- [ ] **Step 1: Run targeted backend and frontend tests**

Run: `php artisan test tests/Feature/TaskKanbanEndpointTest.php`
Expected: PASS.

Run: `node --test tests/js/task-kanban.test.mjs tests/js/list-pagination.test.mjs tests/js/status-badge.test.mjs`
Expected: PASS.

- [ ] **Step 2: Run full build verification**

Run: `npm run build`
Expected: Vite build exits 0.

- [ ] **Step 3: Run broader application test suite**

Run: `php artisan test`
Expected: PASS, or capture any unrelated pre-existing failures explicitly before claiming completion.

- [ ] **Step 4: Manual spot-check checklist**

Verify in the browser:

- switching between `Quicklist` and `Kanban` does not break filters
- `Today` returns the board to the current week
- horizontal scrolling loads more weeks
- `No Due Date` tasks remain visible
- quicklist pagination still renders
