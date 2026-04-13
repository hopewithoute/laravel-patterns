<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import Button from '@/components/ui/Button.vue'
import TaskKanbanColumn from '@/components/task/TaskKanbanColumn.vue'
import { buildBoardMatrix, getDateColumnScrollLeft } from '@/lib/task-kanban'
import { getLoadEdgeThreshold, getVisibleColumnRange } from '@/lib/task-kanban-drag'

const COLUMN_WIDTH = 300

const props = defineProps({
    columns: {
        type: Array,
        default: () => [],
    },
    tasksByColumn: {
        type: Object,
        default: () => ({}),
    },
    loading: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
    prependShiftPx: {
        type: Number,
        default: 0,
    },
    resetToken: {
        type: Number,
        default: 0,
    },
    todayLabel: {
        type: String,
        default: 'Today',
    },
    focusDateKey: {
        type: String,
        default: '',
    },
    todayDateKey: {
        type: String,
        default: '',
    },
})

const emit = defineEmits([
    'ready',
    'moveToToday',
    'requestPrevious',
    'requestNext',
    'retry',
    'prependShiftApplied',
    'task-moved',
])

const viewport = ref(null)
const scrollLeft = ref(0)
const viewportWidth = ref(0)
const isDragging = ref(false)

const matrix = computed(() => buildBoardMatrix(props.columns, props.tasksByColumn))
const noDueDateColumn = computed(
    () =>
        matrix.value.find((column) => column.key === 'no_due_date') ?? {
            key: 'no_due_date',
            label: 'No Due Date',
            tasks: [],
        },
)
const dateColumns = computed(() => matrix.value.filter((column) => column.key !== 'no_due_date'))
const visibleRange = computed(() =>
    getVisibleColumnRange({
        scrollLeft: scrollLeft.value,
        viewportWidth: viewportWidth.value,
        totalColumns: dateColumns.value.length,
        isDragging: isDragging.value,
        columnWidth: COLUMN_WIDTH,
    }),
)

const startIndex = computed(() => visibleRange.value.startIndex)
const endIndex = computed(() => visibleRange.value.endIndex)

const visibleDateColumns = computed(() => dateColumns.value.slice(startIndex.value, endIndex.value))

const leadingSpace = computed(() => startIndex.value * COLUMN_WIDTH)
const trailingSpace = computed(() =>
    Math.max(0, (dateColumns.value.length - endIndex.value) * COLUMN_WIDTH),
)

/**
 * Safety Unlock: Force-release the virtualization lock if something goes wrong
 * during the drag operation (e.g. drop happens outside the board).
 */
const handleGlobalMouseUp = () => {
    if (!isDragging.value) return

    // Small delay to let the SortableJS onEnd fire first
    setTimeout(() => {
        // Force reset the dragging state
        if (isDragging.value) {
            isDragging.value = false
        }

        // NUCLEAR CLEANUP: Remove any zombie fallback elements attached to body.
        // This ensures no cards are ever left floating if a SortableJS instance
        // was interrupted or destroyed during a drag.
        document.querySelectorAll('.kanban-drag-fallback').forEach((el) => {
            el.remove()
        })
    }, 150)
}

watch(isDragging, (val) => {
    if (val) {
        window.addEventListener('mouseup', handleGlobalMouseUp)
    } else {
        window.removeEventListener('mouseup', handleGlobalMouseUp)
    }
})

const handleDragStart = () => {
    isDragging.value = true
}

const handleDragEnd = () => {
    // Delay unlock to let SortableJS finish its own animations and DOM cleanup.
    // The SortableJS 'animation' is set to 200, so 300ms is a safe window.
    setTimeout(() => {
        isDragging.value = false
    }, 300)
}

const scrollToColumnKey = (dateKey) => {
    if (!viewport.value || !dateKey) {
        return
    }

    viewport.value.scrollLeft = getDateColumnScrollLeft(props.columns, dateKey)
    syncViewport()
}

const syncViewport = () => {
    if (!viewport.value) {
        return
    }

    scrollLeft.value = viewport.value.scrollLeft
    viewportWidth.value = viewport.value.clientWidth
}

const handleScroll = () => {
    if (!viewport.value) {
        return
    }

    syncViewport()
    const edgeThreshold = getLoadEdgeThreshold(isDragging.value)

    // Infinite load triggers
    if (viewport.value.scrollLeft <= edgeThreshold) {
        emit('requestPrevious')
    }

    if (
        viewport.value.scrollLeft + viewport.value.clientWidth >=
        viewport.value.scrollWidth - edgeThreshold
    ) {
        emit('requestNext')
    }
}

const handleWheel = (e) => {
    if (!viewport.value) return

    // If the scroll is mostly vertical, translate it to horizontal movement
    if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
        viewport.value.scrollLeft += e.deltaY
        // Check if we actually moved to prevent blocking page scroll if at the very edges
        // (Optional: for now we just capture it to feel consistent)
        e.preventDefault()
    }
}

const showLeftShadow = computed(() => scrollLeft.value > 10)
const showRightShadow = computed(() => {
    if (!viewport.value) return false
    return scrollLeft.value + viewportWidth.value < viewport.value.scrollWidth - 10
})

watch(
    () => props.prependShiftPx,
    (shift) => {
        if (!shift || !viewport.value) {
            return
        }

        viewport.value.scrollLeft += shift
        syncViewport()
        emit('prependShiftApplied')
    },
    { flush: 'post' },
)

watch(
    () => props.resetToken,
    () => {
        if (!viewport.value) {
            return
        }

        scrollToColumnKey(props.focusDateKey)
    },
    { flush: 'post' },
)

watch(
    () => props.focusDateKey,
    (dateKey) => {
        scrollToColumnKey(dateKey)
    },
    { flush: 'post' },
)

onMounted(() => {
    syncViewport()
    window.addEventListener('resize', syncViewport)
    emit('ready')
    scrollToColumnKey(props.focusDateKey)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', syncViewport)
})
</script>

<template>
    <section class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-foreground text-sm font-semibold">Weekly Task Kanban</p>
                <p class="text-muted-foreground text-xs">
                    Scroll sideways to load more weeks. Cards stay grouped by due date.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" @click="emit('moveToToday')">
                    {{ todayLabel }}
                </Button>
            </div>
        </div>

        <div
            v-if="error"
            class="flex items-center justify-between gap-3 rounded-2xl border border-rose-500/20 bg-rose-500/8 px-4 py-3 text-sm text-rose-200"
        >
            <span>{{ error }}</span>
            <Button
                variant="outline"
                size="sm"
                class="border-rose-400/25 text-rose-100 hover:bg-rose-500/10"
                @click="emit('retry')"
            >
                Retry
            </Button>
        </div>

        <div class="grid gap-4 xl:grid-cols-[18rem_minmax(0,1fr)]">
            <TaskKanbanColumn
                key="col-fixed-no-due-date"
                :column="noDueDateColumn"
                :is-today="noDueDateColumn.key === todayDateKey"
                :tasks="noDueDateColumn.tasks"
                @task-moved="emit('task-moved', $event)"
                @drag-start="handleDragStart"
                @drag-end="handleDragEnd"
            />

            <div class="border-border/50 bg-card/50 relative rounded-3xl border p-3">
                <!-- Left Shadow -->
                <div
                    class="from-card/80 pointer-events-none absolute top-3 bottom-5 left-3 z-30 w-12 bg-linear-to-r to-transparent transition-opacity duration-300"
                    :class="{
                        'opacity-100': showLeftShadow && !isDragging,
                        'opacity-0': !showLeftShadow || isDragging,
                    }"
                />

                <!-- Right Shadow -->
                <div
                    class="from-card/80 pointer-events-none absolute top-3 right-3 bottom-5 z-30 w-12 bg-linear-to-l to-transparent transition-opacity duration-300"
                    :class="{
                        'opacity-100': showRightShadow && !isDragging,
                        'opacity-0': !showRightShadow || isDragging,
                    }"
                />

                <div
                    ref="viewport"
                    class="overflow-x-auto overflow-y-hidden pb-2"
                    @scroll="handleScroll"
                    @wheel="handleWheel"
                >
                    <div
                        class="flex min-h-128"
                        :style="{ width: `${dateColumns.length * COLUMN_WIDTH}px` }"
                    >
                        <div :style="{ width: `${leadingSpace}px` }" class="shrink-0" />

                        <div class="flex gap-3">
                            <TaskKanbanColumn
                                v-for="column in visibleDateColumns"
                                :key="`col-date-${column.key}`"
                                :column="column"
                                :is-today="column.key === todayDateKey"
                                :tasks="column.tasks"
                                class="shrink-0"
                                @task-moved="emit('task-moved', $event)"
                                @drag-start="handleDragStart"
                                @drag-end="handleDragEnd"
                            />
                        </div>

                        <div :style="{ width: `${trailingSpace}px` }" class="shrink-0" />
                    </div>
                </div>

                <div
                    v-if="loading"
                    class="text-muted-foreground mt-3 flex items-center gap-2 text-xs"
                >
                    <span class="h-2 w-2 animate-pulse rounded-full bg-cyan-400" />
                    Loading more weeks...
                </div>
            </div>
        </div>
    </section>
</template>
