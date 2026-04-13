# Layout Width Normalization Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Normalize content widths across AppLayout-backed pages so list, detail, and form views follow one layout system.

**Architecture:** Introduce a shared page-width wrapper component with named size presets and reuse it across AppLayout pages. Keep gutter ownership in AppLayout, remove duplicate inner horizontal padding, and map pages to a small set of width intents: wide, content, and form.

**Tech Stack:** Laravel, Inertia, Vue 3, Tailwind CSS v4, Node test runner

---

### Task 1: Lock the width contract with a failing test

**Files:**

- Create: `tests/js/layout-width.test.mjs`
- [ ] Assert a shared width wrapper exists and key pages use standardized presets instead of ad-hoc inner max-width + px wrappers.

### Task 2: Introduce a shared width wrapper

**Files:**

- Create: `resources/js/components/layout/PageWidth.vue`
- [ ] Implement a small wrapper component that maps width presets to classes and only owns max-width centering.

### Task 3: Migrate AppLayout-backed pages

**Files:**

- Modify: `resources/js/pages/Dashboard/Index.vue`
- Modify: `resources/js/pages/Dashboard.vue`
- Modify: `resources/js/pages/Task/Index.vue`
- Modify: `resources/js/pages/Task/Show.vue`
- Modify: `resources/js/pages/Task/Form.vue`
- Modify: `resources/js/pages/Project/Index.vue`
- Modify: `resources/js/pages/Project/Show.vue`
- Modify: `resources/js/pages/Project/Form.vue`
- Modify: `resources/js/pages/Team/Index.vue`
- Modify: `resources/js/pages/Team/Invite.vue`
- Modify: `resources/js/pages/Settings/Index.vue`
- [ ] Replace local width wrappers with the shared wrapper and remove duplicate horizontal padding inside AppLayout pages.

### Task 4: Verify regression tests and production build

**Files:**

- Modify: `tests/js/layout-width.test.mjs`
- [ ] Run the targeted node test and full build.
