# Kanban Drag Scroll Resilience Design

**Date:** 2026-04-13

**Goal:** Make kanban drag-and-drop remain seamless when users drag tasks across long horizontal distances while the board scrolls and lazily loads additional week columns.

## Problem Summary

The current kanban board virtualizes date columns and locks the rendered window during drag. That prevents far-away target columns from being mounted once the user scrolls past the initial drag buffer. In practice:

- dragging across more than roughly 20 date columns can fail because target columns are not rendered
- dropping into the middle of a column body can be interpreted as a cancel instead of an estimated insert position
- empty or partially filled columns require overly precise pointer placement

## Constraints

- Keep the existing weekly lazy-loading model.
- Preserve normal virtualization when not dragging.
- Avoid rendering the entire loaded timeline during drag.
- Keep Sortable-based drag-and-drop as the interaction primitive.
- Maintain optimistic local move behavior and existing PATCH persistence flow.

## User Experience

### Long-Distance Drag

When a drag starts, the board enters a temporary drag expansion mode:

- the rendered date-column window is no longer fixed to the initial drag viewport
- the rendered window follows the live scroll position
- drag mode uses a larger forward/backward buffer than normal scrolling
- week prefetching is triggered earlier while dragging so upcoming columns are ready before the pointer reaches the edge

This allows a user to drag continuously across far-away dates without the destination disappearing from the DOM.

### Tolerant Drop Inside Columns

Dropping into a column should not require precise placement between cards:

- if the pointer lands inside the column body, the board estimates the nearest insertion index
- dropping near the top inserts at the beginning
- dropping near the bottom inserts at the end
- dropping anywhere in an empty column inserts at index `0`
- dropping into the vertical middle of a sparse column is treated as a valid estimate, not a cancel

## Frontend Design

### Board Windowing

`TaskKanbanBoard.vue` will replace the current drag-time window lock with drag-aware expansion:

- normal mode keeps the existing lightweight virtualization buffer
- drag mode computes start/end indexes from live `scrollLeft`
- drag mode uses larger asymmetric buffers so columns behind and ahead of the pointer remain mounted
- drag mode uses a larger edge threshold to request previous/next week chunks sooner

### Column Drop Estimation

`TaskKanbanColumn.vue` will attach a drop-target estimation layer:

- estimate the intended insertion index from the pointer Y position relative to current card bounding boxes
- use the estimated index when Sortable reports an unusable or missing insertion slot
- expose the final estimated `newIndex` through the existing `task-moved` event contract

Pure math/helpers for this logic should live in a testable JS module rather than inside the component body.

## Error Handling

- If a far target week is not loaded yet, the board continues requesting adjacent weeks while dragging instead of canceling the interaction.
- If the drag ends outside the board, existing cleanup behavior still releases drag mode.
- If the estimated drop index cannot be derived, the column falls back to append-to-end rather than canceling the move.

## Testing Strategy

Add JS tests for:

- drag mode window calculation following live scroll position
- drag mode edge threshold selection
- drop index estimation for top, middle, bottom, and empty-column cases
- structural assertions ensuring the board and column components use the new helper APIs

## Non-Goals

- Replacing Sortable or the drag library
- Rendering an unbounded full timeline
- Changing backend move semantics
