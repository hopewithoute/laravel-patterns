# Kanban Drag Scroll Fix Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix kanban drag-and-drop so long horizontal drags remain seamless and drops inside a column body resolve to an estimated insertion index instead of canceling.

**Architecture:** Keep the current board virtualization and lazy-loading model, but make it drag-aware. Move the fragile math for drag window expansion and drop index estimation into pure JS helpers so the Vue components stay thin and the bugfix is covered with targeted tests.

**Tech Stack:** Vue 3, vue-draggable-plus / SortableJS, Node test runner, Vite

---

### Task 1: Lock drag helper behavior with failing tests

**Files:**

- Modify: `tests/js/task-kanban.test.mjs`
- Create: `resources/js/lib/task-kanban-drag.js`

- [ ] **Step 1: Write the failing test**

Add tests that assert:

- normal and drag render windows are calculated differently
- drag mode uses larger buffers and follows live scroll movement
- drag mode edge detection requests adjacent weeks earlier
- drop index estimation returns `0`, middle indexes, or append-to-end as expected

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test tests/js/task-kanban.test.mjs`
Expected: FAIL because the new helper module and assertions do not exist yet.

- [ ] **Step 3: Write minimal implementation**

Add pure helpers for:

- drag-aware visible window calculation
- drag-aware edge threshold selection
- insertion index estimation from card rectangles and pointer Y

- [ ] **Step 4: Run test to verify it passes**

Run: `node --test tests/js/task-kanban.test.mjs`
Expected: PASS for the new helper-level behavior.

### Task 2: Integrate drag expansion into the board

**Files:**

- Modify: `resources/js/components/task/TaskKanbanBoard.vue`
- Modify: `tests/js/task-kanban.test.mjs`

- [ ] **Step 1: Extend the failing test**

Add structural assertions that the board:

- imports the new drag helper module
- uses drag-aware window computation
- uses a larger threshold during drag for previous/next week prefetch

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test tests/js/task-kanban.test.mjs`
Expected: FAIL on missing board integration.

- [ ] **Step 3: Write minimal implementation**

Update the board so:

- drag mode no longer freezes the visible window to the initial range
- scroll updates continue to move the render window during drag
- request thresholds are more aggressive during drag

- [ ] **Step 4: Run test to verify it passes**

Run: `node --test tests/js/task-kanban.test.mjs`
Expected: PASS.

### Task 3: Integrate tolerant drop estimation into columns

**Files:**

- Modify: `resources/js/components/task/TaskKanbanColumn.vue`
- Modify: `tests/js/task-kanban.test.mjs`

- [ ] **Step 1: Extend the failing test**

Add structural assertions that the column:

- imports the drop estimation helper
- uses estimated `newIndex` on drag end
- keeps empty-column drops valid

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test tests/js/task-kanban.test.mjs`
Expected: FAIL on missing column integration.

- [ ] **Step 3: Write minimal implementation**

Update the column so:

- pointer Y is captured during drag
- final index is estimated against rendered card positions when needed
- emitted move payload uses the estimated index instead of canceling the move

- [ ] **Step 4: Run test to verify it passes**

Run: `node --test tests/js/task-kanban.test.mjs`
Expected: PASS.

### Task 4: Verify the fix

**Files:**

- Modify: `resources/js/components/task/TaskKanbanBoard.vue`
- Modify: `resources/js/components/task/TaskKanbanColumn.vue`
- Modify: `resources/js/lib/task-kanban-drag.js`
- Modify: `tests/js/task-kanban.test.mjs`

- [ ] **Step 1: Run targeted tests**

Run: `node --test tests/js/task-kanban.test.mjs`
Expected: PASS.

- [ ] **Step 2: Run build verification**

Run: `npm run build`
Expected: Vite build exits `0`.

- [ ] **Step 3: Manual spot check**

Verify in the browser:

- dragging past 20+ days keeps destination columns available
- horizontal drag near the board edge preloads upcoming weeks
- dropping in the middle of a populated column inserts near the intended vertical position
- dropping into an empty column inserts at the first position
