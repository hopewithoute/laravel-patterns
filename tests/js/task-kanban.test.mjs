import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'
import { pathToFileURL } from 'node:url'
import path from 'node:path'

const helperModuleUrl = pathToFileURL(path.resolve('resources/js/lib/task-kanban.js')).href
const dragHelperModuleUrl = pathToFileURL(path.resolve('resources/js/lib/task-kanban-drag.js')).href

test('task kanban helpers compute the active week and merge board data predictably', async () => {
    const kanban = await import(helperModuleUrl)

    assert.equal(kanban.toDateKey(new Date('2026-04-12T12:00:00Z')), '2026-04-12')
    assert.equal(
        kanban.startOfWeek(new Date('2026-04-12T12:00:00Z')).toISOString().slice(0, 10),
        '2026-04-06',
    )

    const columns = kanban.buildWeekColumns(new Date('2026-04-12T12:00:00Z'))

    assert.equal(columns[0].key, 'no_due_date')
    assert.equal(columns[1].key, '2026-04-06')
    assert.equal(columns[7].key, '2026-04-12')
    assert.equal(columns.length, 8)

    const mergedColumns = kanban.mergeKanbanColumns(
        columns,
        kanban.buildWeekColumns(new Date('2026-04-19T12:00:00Z')),
    )

    assert.equal(mergedColumns[0].key, 'no_due_date')
    assert.equal(mergedColumns[1].key, '2026-04-06')
    assert.equal(mergedColumns.at(-1).key, '2026-04-19')

    const tasksByColumn = kanban.mergeTasksByColumn(
        {
            no_due_date: [{ id: 1 }],
            '2026-04-06': [{ id: 2 }],
        },
        {
            '2026-04-06': [{ id: 3 }],
            '2026-04-07': [{ id: 4 }],
        },
    )

    assert.deepEqual(tasksByColumn.no_due_date, [{ id: 1 }])
    assert.deepEqual(tasksByColumn['2026-04-06'], [{ id: 3 }])
    assert.deepEqual(tasksByColumn['2026-04-07'], [{ id: 4 }])

    assert.equal(
        kanban.getDateColumnScrollLeft(mergedColumns, '2026-04-12'),
        6 * kanban.KANBAN_COLUMN_STRIDE,
    )
})

test('task index hosts switchable quicklist and kanban structures', () => {
    const page = fs.readFileSync('resources/js/pages/Task/Index.vue', 'utf8')
    const board = fs.readFileSync('resources/js/components/task/TaskKanbanBoard.vue', 'utf8')
    const column = fs.readFileSync('resources/js/components/task/TaskKanbanColumn.vue', 'utf8')

    assert.match(page, /TaskViewSwitch/)
    assert.match(page, /TaskKanbanBoard/)
    assert.match(page, /Quicklist/)
    assert.match(page, /Kanban/)
    assert.match(page, /Today/)
    assert.match(page, /viewMode/)
    assert.match(page, /kanbanState/)
    assert.match(page, /focusDateKey/)
    assert.match(page, /todayDateKey/)
    assert.match(page, /@ready="ensureKanbanLoaded"/)
    assert.match(board, /focusDateKey/)
    assert.match(board, /scrollToColumnKey/)
    assert.match(board, /emit\('ready'\)/)
    assert.match(board, /todayDateKey/)
    assert.match(board, /:is-today="column\.key === todayDateKey"/)
    assert.match(column, /Today/)
})

test('kanban dnd helpers handle local state moves predictably', async () => {
    const dndModuleUrl = pathToFileURL(path.resolve('resources/js/lib/task-kanban-dnd.js')).href
    const dnd = await import(dndModuleUrl)

    assert.equal(dnd.buildMovePayload('no_due_date'), null)
    assert.equal(dnd.buildMovePayload('2026-04-14'), '2026-04-14')

    const tasksByColumn = {
        '2026-04-14': [
            { id: 1, title: 'T1' },
            { id: 2, title: 'T2' },
        ],
        '2026-04-15': [{ id: 3, title: 'T3' }],
    }

    // Move T1 from 14th to 15th at index 0
    const updated = dnd.applyLocalMove(tasksByColumn, 1, '2026-04-14', '2026-04-15', 0)

    assert.equal(updated['2026-04-14'].length, 1)
    assert.equal(updated['2026-04-14'][0].id, 2)
    assert.equal(updated['2026-04-15'].length, 2)
    assert.equal(updated['2026-04-15'][0].id, 1)
    assert.equal(updated['2026-04-15'][0].due_date, '2026-04-15')
    assert.equal(updated['2026-04-15'][0].sort_order, 0)
    assert.equal(updated['2026-04-15'][1].sort_order, 1) // Sort order recalculated
})

test('kanban drag helpers expand the render window and estimate drop indexes predictably', async () => {
    const drag = await import(dragHelperModuleUrl)

    assert.deepEqual(
        drag.getVisibleColumnRange({
            scrollLeft: 0,
            viewportWidth: 900,
            totalColumns: 40,
            isDragging: false,
        }),
        { startIndex: 0, endIndex: 11 },
    )

    assert.deepEqual(
        drag.getVisibleColumnRange({
            scrollLeft: 3600,
            viewportWidth: 900,
            totalColumns: 40,
            isDragging: true,
        }),
        { startIndex: 0, endIndex: 37 },
    )

    assert.equal(drag.getLoadEdgeThreshold(false), 240)
    assert.equal(drag.getLoadEdgeThreshold(true), 540)

    assert.equal(drag.estimateDropIndex([], 250), 0)
    assert.equal(
        drag.estimateDropIndex(
            [
                { top: 100, height: 80 },
                { top: 200, height: 80 },
                { top: 300, height: 80 },
            ],
            90,
        ),
        0,
    )
    assert.equal(
        drag.estimateDropIndex(
            [
                { top: 100, height: 80 },
                { top: 200, height: 80 },
                { top: 300, height: 80 },
            ],
            250,
        ),
        2,
    )
    assert.equal(
        drag.estimateDropIndex(
            [
                { top: 100, height: 80 },
                { top: 200, height: 80 },
                { top: 300, height: 80 },
            ],
            500,
        ),
        3,
    )
})

test('kanban board and columns use drag-aware windowing and tolerant drop estimation', () => {
    const board = fs.readFileSync('resources/js/components/task/TaskKanbanBoard.vue', 'utf8')
    const column = fs.readFileSync('resources/js/components/task/TaskKanbanColumn.vue', 'utf8')

    assert.match(board, /getVisibleColumnRange/)
    assert.match(board, /getLoadEdgeThreshold/)
    assert.match(column, /estimateDropIndex/)
    assert.match(column, /data-task-id/)
    assert.match(column, /data-column-body/)
})
