# List Pagination Consistency Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make every main navigational list in the app use consistent backend and frontend pagination, while leaving dashboard widgets and task comments as intentionally compact non-paginated lists.

**Architecture:** Reuse Laravel paginator payloads across Inertia pages through one shared Vue pagination component. Keep project detail stats separate from the paginated task collection so task counts remain correct even when the visible task list is split into pages.

**Tech Stack:** Laravel, Inertia.js, Vue 3, Tailwind CSS, Node.js test runner

---

### Task 1: Lock the pagination contract with failing tests

**Files:**

- Create: `tests/js/list-pagination.test.mjs`

- [ ] **Step 1: Write the failing test**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Cover shared pagination UI plus project-detail task pagination contract**

### Task 2: Build one shared pagination UI component

**Files:**

- Create: `resources/js/components/layout/PaginationNav.vue`
- Modify: `resources/js/pages/Project/Index.vue`
- Modify: `resources/js/pages/Task/Index.vue`
- Modify: `resources/js/pages/Team/Index.vue`

- [ ] **Step 1: Add the shared paginator component**
- [ ] **Step 2: Replace duplicated pagination markup in existing list pages**
- [ ] **Step 3: Add missing pagination UI to team members page**

### Task 3: Paginate project detail tasks in backend and frontend

**Files:**

- Modify: `app/Http/Controllers/ProjectController.php`
- Modify: `resources/js/pages/Project/Show.vue`

- [ ] **Step 1: Return paginated project tasks from the show action**
- [ ] **Step 2: Preserve project-level task summary counts outside the paginator**
- [ ] **Step 3: Update the page to render `tasks.data` plus shared pagination UI**

### Task 4: Verify the full surface

**Files:**

- Test: `tests/js/*.mjs`

- [ ] **Step 1: Run `node --test tests/js/*.mjs`**
- [ ] **Step 2: Run `npm run build`**
