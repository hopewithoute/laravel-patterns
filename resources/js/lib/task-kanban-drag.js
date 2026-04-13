export const NORMAL_BUFFER_COLUMNS = 4
export const DRAG_BACK_BUFFER_COLUMNS = 12
export const DRAG_FORWARD_BUFFER_COLUMNS = 22
export const NORMAL_EDGE_THRESHOLD = 240
export const DRAG_EDGE_THRESHOLD = 540
export const DEFAULT_COLUMN_WIDTH = 300

export function getVisibleColumnRange({
    scrollLeft = 0,
    viewportWidth = 0,
    totalColumns = 0,
    isDragging = false,
    columnWidth = DEFAULT_COLUMN_WIDTH,
}) {
    if (totalColumns <= 0) {
        return { startIndex: 0, endIndex: 0 }
    }

    const visibleStart = Math.max(0, Math.floor(scrollLeft / columnWidth))
    const visibleEnd = Math.min(totalColumns, Math.ceil((scrollLeft + viewportWidth) / columnWidth))

    if (!isDragging) {
        const startIndex = Math.max(0, visibleStart - NORMAL_BUFFER_COLUMNS)
        const visibleCount = Math.ceil(viewportWidth / columnWidth) + NORMAL_BUFFER_COLUMNS * 2
        const endIndex = Math.min(totalColumns, startIndex + visibleCount)

        return { startIndex, endIndex }
    }

    return {
        startIndex: Math.max(0, visibleStart - DRAG_BACK_BUFFER_COLUMNS),
        endIndex: Math.min(totalColumns, visibleEnd + DRAG_FORWARD_BUFFER_COLUMNS),
    }
}

export function getLoadEdgeThreshold(isDragging = false) {
    return isDragging ? DRAG_EDGE_THRESHOLD : NORMAL_EDGE_THRESHOLD
}

export function estimateDropIndex(taskRects, pointerY) {
    if (!Array.isArray(taskRects) || taskRects.length === 0) {
        return 0
    }

    for (let index = 0; index < taskRects.length; index += 1) {
        const rect = taskRects[index]
        const midpoint = rect.top + rect.height / 2

        if (pointerY < midpoint) {
            return index
        }
    }

    return taskRects.length
}
