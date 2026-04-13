export function toDateKey(value) {
    const date = value instanceof Date ? value : new Date(value)
    const year = date.getUTCFullYear()
    const month = String(date.getUTCMonth() + 1).padStart(2, '0')
    const day = String(date.getUTCDate()).padStart(2, '0')

    return `${year}-${month}-${day}`
}

export function startOfWeek(value) {
    const date = new Date(value instanceof Date ? value.getTime() : value)
    const day = date.getUTCDay()
    const offset = day === 0 ? -6 : 1 - day

    date.setUTCHours(0, 0, 0, 0)
    date.setUTCDate(date.getUTCDate() + offset)

    return date
}

export function addDays(value, amount) {
    const date = new Date(value instanceof Date ? value.getTime() : value)
    date.setUTCDate(date.getUTCDate() + amount)
    return date
}

export function buildWeekColumns(anchorDate) {
    const weekStart = startOfWeek(anchorDate)
    const columns = [{ key: 'no_due_date', label: 'No Due Date', date: null }]

    for (let index = 0; index < 7; index += 1) {
        const current = addDays(weekStart, index)
        const label = current.toLocaleDateString('en-US', {
            weekday: 'short',
            day: 'numeric',
            timeZone: 'UTC',
        })

        columns.push({
            key: toDateKey(current),
            label,
            date: toDateKey(current),
        })
    }

    return columns
}

export function mergeKanbanColumns(currentColumns, incomingColumns) {
    const deduped = new Map()

    for (const column of [...currentColumns, ...incomingColumns]) {
        deduped.set(column.key, column)
    }

    const columns = [...deduped.values()].filter((column) => column.key !== 'no_due_date')
    columns.sort((left, right) => left.key.localeCompare(right.key))

    return [
        deduped.get('no_due_date') ?? { key: 'no_due_date', label: 'No Due Date', date: null },
        ...columns,
    ]
}

export function mergeTasksByColumn(currentTasksByColumn, incomingTasksByColumn) {
    return {
        ...currentTasksByColumn,
        ...incomingTasksByColumn,
    }
}

export function getWeekRange(anchorDate) {
    const start = startOfWeek(anchorDate)
    const end = addDays(start, 6)

    return {
        startDate: toDateKey(start),
        endDate: toDateKey(end),
    }
}

export function getWeekOffset(anchorDate, offset) {
    return addDays(startOfWeek(anchorDate), offset * 7)
}

export function buildBoardMatrix(columns, tasksByColumn) {
    return columns.map((column) => ({
        ...column,
        tasks: tasksByColumn[column.key] ?? [],
    }))
}

export const KANBAN_COLUMN_WIDTH = 288
export const KANBAN_COLUMN_GAP = 12
export const KANBAN_COLUMN_STRIDE = KANBAN_COLUMN_WIDTH + KANBAN_COLUMN_GAP

export function getDateColumnScrollLeft(columns, dateKey) {
    const dateColumns = columns.filter((column) => column.key !== 'no_due_date')
    const index = dateColumns.findIndex((column) => column.key === dateKey)

    if (index < 0) {
        return 0
    }

    return index * KANBAN_COLUMN_STRIDE
}
