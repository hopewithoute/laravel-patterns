/**
 * Map a column key to the due_date value for the PATCH payload.
 * 'no_due_date' → null, '2026-04-14' → '2026-04-14'
 */
export function buildMovePayload(toColumnKey) {
    return toColumnKey === 'no_due_date' ? null : toColumnKey
}

/**
 * Optimistically move a task between columns in local state.
 * Returns the updated tasksByColumn object.
 */
export function applyLocalMove(tasksByColumn, taskId, fromKey, toKey, newIndex) {
    const updated = { ...tasksByColumn }

    // Find the source column if not provided
    let sourceKey = fromKey
    if (!sourceKey) {
        for (const key in updated) {
            if (updated[key].some((t) => t.id === taskId)) {
                sourceKey = key
                break
            }
        }
    }

    if (!sourceKey || !updated[sourceKey]) {
        return updated
    }

    // Find the task in the source column
    const sourceTasks = [...updated[sourceKey]]
    const taskIndex = sourceTasks.findIndex((t) => t.id === taskId)

    if (taskIndex === -1) {
        return updated
    }

    const [task] = sourceTasks.splice(taskIndex, 1)
    updated[sourceKey] = sourceTasks

    // Insert task into the target column
    const targetTasks = sourceKey === toKey ? sourceTasks : [...(updated[toKey] || [])]

    // Update the task's due_date and sort_order in local state
    const updatedTask = {
        ...task,
        due_date: buildMovePayload(toKey),
        sort_order: newIndex,
    }

    targetTasks.splice(newIndex, 0, updatedTask)
    updated[toKey] = targetTasks

    // Recalculate sort_order for all tasks in the target column to keep them in sync
    updated[toKey] = updated[toKey].map((t, index) => ({
        ...t,
        sort_order: index,
    }))

    return updated
}
