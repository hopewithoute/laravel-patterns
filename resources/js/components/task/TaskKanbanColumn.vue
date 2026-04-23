<script setup>
import { ref, watch } from 'vue'
import { useDraggable } from 'vue-draggable-plus'
import TaskKanbanCard from '@/components/task/TaskKanbanCard.vue'
import { estimateDropIndex } from '@/lib/task-kanban-drag'

const props = defineProps({
    column: {
        type: Object,
        required: true,
    },
    isToday: {
        type: Boolean,
        default: false,
    },
    tasks: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['task-moved', 'drag-start', 'drag-end'])

const localTasks = ref([...props.tasks])
const draggableContainer = ref(null)
const pointerPosition = ref({ x: null, y: null })

watch(
    () => props.tasks,
    (newTasks) => {
        localTasks.value = [...newTasks]
    },
    { deep: true },
)

const updatePointerPosition = (event) => {
    const point = event?.touches?.[0] ?? event

    if (typeof point?.clientX !== 'number' || typeof point?.clientY !== 'number') {
        return
    }

    pointerPosition.value = {
        x: point.clientX,
        y: point.clientY,
    }
}

const getDropTargetBody = (fallbackContainer) => {
    const { x, y } = pointerPosition.value

    if (typeof x === 'number' && typeof y === 'number') {
        const hovered = document.elementFromPoint(x, y)
        const hoveredBody = hovered?.closest?.('[data-column-body]')

        if (hoveredBody) {
            return hoveredBody
        }
    }

    return fallbackContainer ?? draggableContainer.value
}

const buildTaskRects = (targetBody, taskId) =>
    [...targetBody.querySelectorAll('[data-task-id]')]
        .filter((element) => element.getAttribute('data-task-id') !== taskId)
        .map((element) => {
            const rect = element.getBoundingClientRect()

            return {
                top: rect.top,
                height: rect.height,
            }
        })

useDraggable(draggableContainer, localTasks, {
    group: 'kanban-tasks',
    handle: '.drag-handle',
    draggable: '.draggable-item',
    animation: 200,
    forceFallback: true,
    fallbackOnBody: true,
    scroll: true,
    fallbackTolerance: 3,
    ghostClass: 'kanban-ghost',
    dragClass: 'opacity-0',
    fallbackClass: 'kanban-drag-fallback',
    emptyInsertThreshold: 40,
    onStart: (e) => {
        updatePointerPosition(e.originalEvent)
        emit('drag-start')
    },
    onMove: (_e, originalEvent) => {
        updatePointerPosition(originalEvent)
    },
    onEnd: (e) => {
        // 1. Fail-safe: Manually restore opacity if SortableJS is interrupted
        if (e.item) {
            e.item.classList.remove('opacity-0')
            e.item.style.opacity = ''
        }

        const taskId = e.item.getAttribute('data-id')
        const targetBody = getDropTargetBody(e.to)
        const toColumnKey =
            targetBody?.getAttribute('data-column-key') ??
            e.to?.getAttribute('data-column-key') ??
            props.column.key
        const taskRects = targetBody ? buildTaskRects(targetBody, taskId) : []
        const canEstimateIndex = typeof pointerPosition.value.y === 'number'
        const estimatedIndex = canEstimateIndex
            ? estimateDropIndex(taskRects, pointerPosition.value.y)
            : null
        const hasSortableIndex = Number.isInteger(e.newIndex) && e.newIndex >= 0
        const resolvedIndex = estimatedIndex ?? (hasSortableIndex ? e.newIndex : taskRects.length)

        // 2. Atomic Commitment: Identify movement from the final dropped state
        const fromColumnKey = props.column.key

        emit('task-moved', {
            taskId,
            fromColumnKey,
            toColumnKey,
            newIndex: resolvedIndex,
        })

        emit('drag-end')
    },
})
</script>

<template>
    <section
        :class="[
            'bg-card/70 flex h-full min-h-[32rem] w-72 shrink-0 flex-col rounded-3xl border transition-all duration-200',
            isToday
                ? 'border-cyan-400/60 bg-cyan-500/[0.08] shadow-lg shadow-cyan-500/12'
                : 'border-border/50',
        ]"
    >
        <header
            :class="[
                'sticky top-0 z-10 rounded-t-3xl border-b px-4 py-3 backdrop-blur',
                isToday ? 'border-cyan-400/35 bg-cyan-500/[0.12]' : 'border-border/50 bg-card/95',
            ]"
        >
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="text-foreground text-sm font-semibold">
                            {{ column.label }}
                        </p>
                        <span
                            v-if="isToday"
                            class="rounded-full border border-cyan-400/35 bg-cyan-400/15 px-2 py-0.5 text-[10px] font-semibold tracking-[0.18em] text-cyan-700 uppercase dark:text-cyan-200"
                        >
                            Today
                        </span>
                    </div>
                    <p
                        :class="[
                            'text-[11px] tracking-[0.18em] uppercase',
                            isToday
                                ? 'text-cyan-700/70 dark:text-cyan-200/75'
                                : 'text-muted-foreground',
                        ]"
                    >
                        {{ column.key === 'no_due_date' ? 'Unscheduled' : 'Due Date' }}
                    </p>
                </div>
                <div
                    :class="[
                        'rounded-full px-2.5 py-1 text-xs font-medium',
                        isToday
                            ? 'bg-cyan-400/15 text-cyan-700 dark:text-cyan-100'
                            : 'bg-surface text-muted-foreground',
                    ]"
                >
                    {{ tasks.length }}
                </div>
            </div>
        </header>

        <div
            ref="draggableContainer"
            :data-column-key="column.key"
            data-column-body
            class="flex-1 space-y-3 overflow-y-auto p-3"
        >
            <TaskKanbanCard v-for="task in localTasks" :key="task.id" :task="task" />

            <div
                v-if="localTasks.length === 0"
                class="border-border/60 bg-surface/20 text-muted-foreground flex min-h-36 items-center justify-center rounded-2xl border border-dashed px-4 text-center text-xs"
            >
                No tasks in this column.
            </div>
        </div>
    </section>
</template>

<style scoped>
@reference "../../../css/app.css";

:deep(.drag-handle) {
    touch-action: none;
}
</style>

<style>
/* --- Kanban Drag and Drop Styles --- */

/**
 * Ghost: The placeholder left behind in the list
 */
.kanban-ghost {
    opacity: 0.4;
    border: 2px dashed rgba(6, 182, 212, 0.5); /* cyan-500/50 */
    background-color: rgba(6, 182, 212, 0.05); /* cyan-500/5 */
    border-radius: 1rem;
}

/**
 * Fallback: The element moving with the mouse (lift-up effect)
 */
.kanban-drag-fallback {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
    transform: rotate(2deg) scale(1.02);
    cursor: grabbing !important;
    opacity: 1 !important;
    z-index: 9999 !important;
    pointer-events: none;
}

/**
 * Functional Resets for Draggable items
 */
.draggable-item {
    user-select: none;
    -webkit-user-drag: none;
    -webkit-tap-highlight-color: transparent;
}

.drag-handle {
    cursor: grab !important;
}

.drag-handle:active {
    cursor: grabbing !important;
}
</style>
