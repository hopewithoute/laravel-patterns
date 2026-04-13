# Global Font Refresh Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the global sans-serif font with a more elegant choice and remove conflicting font loaders.

**Architecture:** Keep Instrument Serif for display text, switch the global sans stack to Manrope in the Tailwind theme, and consolidate font loading in the CSS entrypoint so Blade templates do not compete with the app stylesheet. Add a small regression test to lock the selection.

**Tech Stack:** Laravel, Vite, Tailwind CSS v4, Vue, Node test runner

---

### Task 1: Lock the desired font contract

**Files:**

- Modify: `tests/js/theme.test.mjs`
- [ ] Add a failing assertion for Manrope as the global sans font and ensure Inter is no longer the selected global loader.

### Task 2: Update global font sources

**Files:**

- Modify: `resources/css/app.css`
- Modify: `resources/views/app.blade.php`
- Modify: `resources/views/welcome.blade.php`
- [ ] Switch CSS import and theme token to Manrope and remove redundant Blade font links.

### Task 3: Verify build output

**Files:**

- Modify: `tests/js/theme.test.mjs`
- [ ] Run the node regression test and production build.
