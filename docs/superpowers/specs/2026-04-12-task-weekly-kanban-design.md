# Task Weekly Kanban Design

**Date:** 2026-04-12

**Goal:** Replace the current task index's single presentation with a switchable dual-view experience: `Quicklist` and `Kanban`. `Quicklist` stays on the existing paginated list flow. `Kanban` introduces a weekly, date-column board with horizontal virtualized scroll and vertically stacked task cards.

## Constraints

- Keep the implementation inside the existing task index page.
- Preserve the current paginated `Quicklist` behavior and payload contract.
- Implement `Kanban` as a separate data flow and endpoint.
- Use the existing shadcn-vue style UI primitives already present in the repo wherever an off-the-shelf component exists.
- Build custom components only for kanban-specific pieces that do not exist as reusable primitives, especially the board viewport, weekly virtualization, and matrix layout.
- Follow existing backend patterns already used in the repo:
    - controller action in `TaskController`
    - query object in `app/QueryBuilders`
    - Inertia page for the main screen
    - JSON endpoint for async kanban range loading
- Weekly kanban starts at the beginning of the week that contains today.
- Provide a `Today` quick action that re-centers the board on the current week.
- Tasks without `due_date` must remain visible in a dedicated `No Due Date` column.

## User Experience

### View Switching

The task index header gains a view switch with two modes:

- `Quicklist`: the current searchable, filterable, paginated task list.
- `Kanban`: a horizontally scrollable weekly board.

Switching views does not attempt to normalize the two data sources. Each mode keeps its own behavior:

- `Quicklist` continues to rely on paginated server-side data from `TaskController@index`.
- `Kanban` loads date ranges on demand from a dedicated async endpoint.

### Kanban Layout

The kanban board contains:

- one leading `No Due Date` column
- daily columns for the visible weekly range
- task cards stacked vertically within each column

The initial visible range is:

- the start of the current week
- through six additional days

The board includes:

- a `Today` button that resets the board to the week containing today's date
- loading placeholders while additional weeks are fetched
- an inline retry state for failed range fetches

### Filters

The existing filters remain available on the task index page:

- search
- status
- priority

They apply to both views, but the loading mechanics differ:

- `Quicklist` refreshes the Inertia page as it does today
- `Kanban` re-fetches range chunks from the async endpoint using the same filter values

## Backend Design

### Existing Index Flow

The existing `TaskController@index` and `TaskIndexQuery` stay responsible for the quicklist response. This prevents regression in the current list behavior and keeps pagination untouched.

### New Kanban Endpoint

Add a dedicated controller action on `TaskController`, for example `kanban`, that returns JSON rather than a separate Inertia page.

Expected request parameters:

- `start_date`
- `end_date`
- `filter[search]`
- `filter[status]`
- `filter[priority]`

Expected response shape:

```json
{
    "meta": {
        "start_date": "2026-04-06",
        "end_date": "2026-04-12",
        "today": "2026-04-12"
    },
    "columns": [
        { "key": "no_due_date", "label": "No Due Date", "date": null },
        { "key": "2026-04-06", "label": "Mon 6", "date": "2026-04-06" }
    ],
    "tasks_by_column": {
        "no_due_date": [],
        "2026-04-06": []
    }
}
```

### Query Layer

Add a dedicated query object for kanban, separate from `TaskIndexQuery`, so the endpoint follows the repo's query-builder pattern without overloading the paginated list query.

Recommended responsibilities for the new query object:

- reuse the same allowed filters as the list flow where practical
- eagerly load only fields needed for task cards
- constrain date-based records to the requested inclusive range
- also collect tasks where `due_date` is `null` for the `No Due Date` column
- return data already grouped for predictable serialization

This keeps the controller thin and the filtering logic aligned with the existing backend structure.

## Frontend Design

### Page Composition

The existing `resources/js/pages/Task/Index.vue` remains the page entry. It becomes the host for both view modes.

Standard UI elements should prefer the repo's existing shadcn-vue style components and patterns, such as shared button, card, badge, dialog, popover, and layout primitives. The kanban board shell, horizontal viewport behavior, and virtualized weekly matrix should be implemented as custom components because they are domain-specific and do not map cleanly to an existing off-the-shelf primitive.

Recommended component split:

- `TaskViewSwitch`
- `TaskQuickList`
- `TaskKanbanBoard`
- `TaskKanbanColumn`
- `TaskKanbanCard`

The exact file layout can be adjusted to match repo conventions, but the page should delegate most kanban-specific logic into focused components or helpers.

### Kanban State Model

The kanban mode maintains local client state for:

- active filters
- current anchor week
- loaded week chunks
- in-flight requests
- failed chunks
- board matrix merged from all loaded chunks

### Virtualized Weekly Scroll

The board should not render an unbounded timeline all at once. Instead, it uses chunked weekly virtualization:

- render only the currently visible weekly columns plus a small buffer
- fetch the next or previous week when the horizontal scroll approaches an edge
- merge new weeks into local state without duplicating columns
- avoid repeat requests for an already loaded week

This is virtualization by timeline windowing rather than by card recycling, which fits the requested weekly board and keeps implementation complexity under control.

### Today Reset

The `Today` action:

- computes the week start for today's date
- resets the visible window to that week
- ensures the corresponding chunk is loaded
- scrolls the board back to the anchored position

## Error Handling

- Invalid or missing `start_date` and `end_date` should return a validation error from Laravel.
- Frontend should keep already loaded weeks intact if a later chunk request fails.
- Failed chunk requests should expose a local retry control.
- Duplicate requests for the same week should be ignored while a fetch is already in progress.

## Testing Strategy

### Backend

Add feature tests for the kanban endpoint to verify:

- date range validation
- tasks in range are grouped under the matching date key
- tasks without `due_date` are grouped under `no_due_date`
- existing filters affect kanban results correctly

### Frontend

Add JS tests for the kanban helpers and state behavior:

- week start calculation
- chunk merge logic
- matrix generation from fetched columns
- `Today` reset behavior
- view switch state between `Quicklist` and `Kanban`

## Non-Goals

- Converting quicklist pagination into infinite scroll
- Creating a separate kanban page
- Adding drag-and-drop task movement between dates
- Changing the existing task detail route structure
