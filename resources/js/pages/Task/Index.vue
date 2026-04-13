<script setup>
import axios from 'axios'
import { reactive, ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { Link, router, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import PaginationNav from '@/components/layout/PaginationNav.vue'
import PageWidth from '@/components/layout/PageWidth.vue'
import Badge from '@/components/ui/Badge.vue'
import { toast } from 'vue-sonner'
import TaskKanbanBoard from '@/components/task/TaskKanbanBoard.vue'
import TaskViewSwitch from '@/components/task/TaskViewSwitch.vue'
import { TASK_PRIORITY_TONES, TASK_STATUS_TONES } from '@/lib/badges'
import {
    buildWeekColumns,
    getWeekOffset,
    getWeekRange,
    KANBAN_COLUMN_STRIDE,
    mergeKanbanColumns,
    mergeTasksByColumn,
    startOfWeek,
    toDateKey,
} from '@/lib/task-kanban'
import { applyLocalMove, buildMovePayload } from '@/lib/task-kanban-dnd'

const BOARD_COLUMN_WIDTH = KANBAN_COLUMN_STRIDE

defineProps({
    tasks: Object,
    filters: Object,
})

const query = new URLSearchParams(window.location.search)

const viewMode = ref('quicklist')
const search = ref(query.get('filter[search]') || '')
const statusFilter = ref(query.get('filter[status]') || '')
const priorityFilter = ref(query.get('filter[priority]') || '')
const todayDateKey = toDateKey(new Date())

const createTaskMap = (columns) => Object.fromEntries(columns.map((column) => [column.key, []]))
const countDateColumns = (columns) =>
    columns.filter((column) => column.key !== 'no_due_date').length
const sortWeekStarts = (values) =>
    [...new Set(values)].sort((left, right) => left.localeCompare(right))

const initialAnchorDate = startOfWeek(new Date())
const initialColumns = buildWeekColumns(initialAnchorDate)

const kanbanState = reactive({
    anchorDate: initialAnchorDate,
    columns: initialColumns,
    tasksByColumn: createTaskMap(initialColumns),
    loadedWeekStarts: [],
    loadingWeekStarts: [],
    loading: false,
    error: '',
    failedWeekStart: '',
    prependShiftPx: 0,
    resetToken: 0,
    requestToken: 0,
    focusDateKey: toDateKey(new Date()),
})

const buildFilterPayload = () => ({
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    priority: priorityFilter.value || undefined,
})

const syncQuicklist = () => {
    router.get(
        '/tasks',
        {
            filter: buildFilterPayload(),
        },
        {
            preserveState: true,
            replace: true,
        },
    )
}

const resetKanbanBoard = (anchorDate = new Date()) => {
    const normalizedAnchor = startOfWeek(anchorDate)
    const columns = buildWeekColumns(normalizedAnchor)

    kanbanState.anchorDate = normalizedAnchor
    kanbanState.columns = columns
    kanbanState.tasksByColumn = createTaskMap(columns)
    kanbanState.loadedWeekStarts = []
    kanbanState.loadingWeekStarts = []
    kanbanState.loading = false
    kanbanState.error = ''
    kanbanState.failedWeekStart = ''
    kanbanState.prependShiftPx = 0
    kanbanState.focusDateKey = toDateKey(new Date(anchorDate))
    kanbanState.resetToken += 1
    kanbanState.requestToken += 1
}

const loadKanbanWeek = async (anchorDate, options = {}) => {
    const weekStart = toDateKey(startOfWeek(anchorDate))

    if (
        kanbanState.loadedWeekStarts.includes(weekStart) ||
        kanbanState.loadingWeekStarts.includes(weekStart)
    ) {
        return
    }

    const requestToken = kanbanState.requestToken
    const { startDate, endDate } = getWeekRange(anchorDate)
    const beforeDateColumnCount = countDateColumns(kanbanState.columns)

    kanbanState.loading = true
    kanbanState.loadingWeekStarts = [...kanbanState.loadingWeekStarts, weekStart]

    try {
        const response = await axios.get('/tasks/kanban', {
            params: {
                start_date: startDate,
                end_date: endDate,
                filter: buildFilterPayload(),
            },
        })

        if (requestToken !== kanbanState.requestToken) {
            return
        }

        kanbanState.columns = mergeKanbanColumns(kanbanState.columns, response.data.columns)
        kanbanState.tasksByColumn = mergeTasksByColumn(
            kanbanState.tasksByColumn,
            response.data.tasks_by_column,
        )
        kanbanState.loadedWeekStarts = sortWeekStarts([...kanbanState.loadedWeekStarts, startDate])
        kanbanState.error = ''
        kanbanState.failedWeekStart = ''

        if (options.prepend) {
            const addedDateColumns = countDateColumns(kanbanState.columns) - beforeDateColumnCount

            if (addedDateColumns > 0) {
                kanbanState.prependShiftPx += addedDateColumns * BOARD_COLUMN_WIDTH
            }
        }
        // eslint-disable-next-line no-unused-vars
    } catch (_error) {
        if (requestToken !== kanbanState.requestToken) {
            return
        }

        kanbanState.error = 'Unable to load another week right now.'
        kanbanState.failedWeekStart = weekStart
    } finally {
        if (requestToken === kanbanState.requestToken) {
            kanbanState.loadingWeekStarts = kanbanState.loadingWeekStarts.filter(
                (value) => value !== weekStart,
            )
            kanbanState.loading = kanbanState.loadingWeekStarts.length > 0
        }
    }
}

const ensureKanbanLoaded = () => {
    if (kanbanState.loadedWeekStarts.length > 0 || kanbanState.loadingWeekStarts.length > 0) {
        return
    }

    resetKanbanBoard(kanbanState.anchorDate)
    loadKanbanWeek(kanbanState.anchorDate)
}

const queueFilterSync = useDebounceFn(() => {
    if (viewMode.value === 'kanban') {
        const currentAnchor = kanbanState.anchorDate
        resetKanbanBoard(currentAnchor)
        loadKanbanWeek(currentAnchor)
        return
    }

    syncQuicklist()
}, 250)

watch([search, statusFilter, priorityFilter], () => {
    queueFilterSync()
})

watch(viewMode, (mode) => {
    if (mode === 'kanban') {
        ensureKanbanLoaded()
        return
    }

    syncQuicklist()
})

const loadPreviousWeek = () => {
    const firstLoadedWeek = kanbanState.loadedWeekStarts[0] || toDateKey(kanbanState.anchorDate)
    loadKanbanWeek(getWeekOffset(new Date(`${firstLoadedWeek}T00:00:00Z`), -1), { prepend: true })
}

const loadNextWeek = () => {
    const lastLoadedWeek = kanbanState.loadedWeekStarts.at(-1) || toDateKey(kanbanState.anchorDate)
    loadKanbanWeek(getWeekOffset(new Date(`${lastLoadedWeek}T00:00:00Z`), 1))
}

const moveKanbanToToday = () => {
    const today = new Date()
    const todayAnchor = startOfWeek(today)
    resetKanbanBoard(todayAnchor)
    kanbanState.focusDateKey = toDateKey(today)
    loadKanbanWeek(todayAnchor)
}

const retryKanbanLoad = () => {
    const retryWeek = kanbanState.failedWeekStart
        ? new Date(`${kanbanState.failedWeekStart}T00:00:00Z`)
        : kanbanState.anchorDate

    loadKanbanWeek(retryWeek)
}

const clearPrependShift = () => {
    kanbanState.prependShiftPx = 0
}

const moveTask = async ({ taskId, fromColumnKey, toColumnKey, newIndex }) => {
    // Create a safe deep copy for potential rollback on failure
    const originalTasksByColumn = JSON.parse(JSON.stringify(kanbanState.tasksByColumn))

    // Apply optimistic UI update
    kanbanState.tasksByColumn = applyLocalMove(
        kanbanState.tasksByColumn,
        taskId,
        fromColumnKey,
        toColumnKey,
        newIndex,
    )

    const dueDate = buildMovePayload(toColumnKey)

    try {
        await axios.patch(`/tasks/${taskId}/move`, {
            due_date: dueDate,
            sort_order: newIndex,
        })

        toast.success(`Task moved successfully`)
        // eslint-disable-next-line no-unused-vars
    } catch (_error) {
        // Rollback
        kanbanState.tasksByColumn = originalTasksByColumn
        toast.error('Failed to move task. Reverting changes.')
    }
}

const isOverdue = (task) => {
    if (!task.due_date || task.status === 'Done') {
        return false
    }

    return new Date(task.due_date) < new Date()
}
</script>

<template>
    <Head title="Tasks" />
    <AppLayout>
        <PageWidth size="wide" class="space-y-8">
            <PageHeader
                badge="Work Queue"
                title="Tasks"
                description="Switch between the fast quicklist and a weekly kanban timeline without leaving the task index."
                tone="cyan"
                glow-class="top-0 right-1/4 w-80 h-80 bg-cyan-500/[0.04]"
            >
                <template #badge-icon>
                    <svg
                        class="h-3.5 w-3.5 text-cyan-400"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                    >
                        <path d="M9 11l3 3L22 4" />
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                    </svg>
                </template>

                <template #actions>
                    <Link
                        href="/tasks/create"
                        class="group relative inline-flex items-center gap-2 overflow-hidden rounded-xl bg-linear-to-r from-cyan-500 to-cyan-400 px-5 py-3 text-sm font-semibold text-black shadow-lg shadow-cyan-500/15 transition-all duration-300 hover:shadow-cyan-500/25"
                    >
                        <div
                            class="absolute inset-0 -translate-x-full bg-linear-to-r from-transparent via-white/20 to-transparent transition-transform duration-700 group-hover:translate-x-full"
                        ></div>
                        <svg
                            class="relative h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                        >
                            <line x1="12" x2="12" y1="5" y2="19" />
                            <line x1="5" x2="19" y1="12" y2="12" />
                        </svg>
                        <span class="relative">New Task</span>
                    </Link>
                </template>
            </PageHeader>

            <section
                class="border-border/40 bg-card/40 flex flex-col gap-4 rounded-3xl border p-4 sm:p-5"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <TaskViewSwitch v-model="viewMode" />
                    <p class="text-muted-foreground text-xs">
                        Quicklist keeps the current paginated flow. Kanban loads weekly columns as
                        you scroll.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative min-w-60 flex-1">
                        <svg
                            class="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search tasks..."
                            class="border-border/50 bg-surface/50 text-foreground placeholder:text-muted-foreground/70 focus:ring-primary/20 focus:border-primary/35 focus:bg-surface/60 h-10 w-full rounded-xl border pr-4 pl-10 text-sm transition-all duration-200 focus:ring-2 focus:outline-none"
                        />
                    </div>

                    <select
                        v-model="statusFilter"
                        class="border-border/50 bg-surface/50 text-foreground focus:ring-primary/20 focus:border-primary/35 h-10 cursor-pointer appearance-none rounded-xl border px-4 pr-10 text-sm transition-all duration-200 focus:ring-2 focus:outline-none"
                        style="
                            background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2371717a%22 stroke-width=%222%22><path d=%22m6 9 6 6 6-6%22/></svg>');
                            background-position: right 12px center;
                            background-repeat: no-repeat;
                            background-size: 16px;
                        "
                    >
                        <option value="">All Statuses</option>
                        <option v-for="s in filters.statuses" :key="s.value" :value="s.value">
                            {{ s.text }}
                        </option>
                    </select>

                    <select
                        v-model="priorityFilter"
                        class="border-border/50 bg-surface/50 text-foreground focus:ring-primary/20 focus:border-primary/35 h-10 cursor-pointer appearance-none rounded-xl border px-4 pr-10 text-sm transition-all duration-200 focus:ring-2 focus:outline-none"
                        style="
                            background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2371717a%22 stroke-width=%222%22><path d=%22m6 9 6 6 6-6%22/></svg>');
                            background-position: right 12px center;
                            background-repeat: no-repeat;
                            background-size: 16px;
                        "
                    >
                        <option value="">All Priorities</option>
                        <option v-for="p in filters.priorities" :key="p.value" :value="p.value">
                            {{ p.text }}
                        </option>
                    </select>
                </div>
            </section>

            <section v-if="viewMode === 'quicklist'" class="space-y-6">
                <div v-if="tasks.data.length > 0" class="space-y-2">
                    <Link
                        v-for="task in tasks.data"
                        :key="task.id"
                        :href="`/tasks/${task.id}`"
                        class="group bg-card border-border/40 hover:border-border/60 block rounded-xl border p-4 transition-all duration-300"
                    >
                        <div class="flex items-start gap-4">
                            <div class="shrink-0 pt-1">
                                <div
                                    :class="[
                                        'h-3 w-3 rounded-full transition-transform duration-200 group-hover:scale-110',
                                        task.status === 'Done'
                                            ? 'bg-emerald-500'
                                            : task.status === 'In Progress'
                                              ? 'bg-cyan-500'
                                              : task.status === 'Review'
                                                ? 'bg-amber-500'
                                                : 'bg-slate-500',
                                    ]"
                                ></div>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="space-y-1">
                                        <h3
                                            class="text-foreground font-medium transition-colors group-hover:text-cyan-400"
                                        >
                                            {{ task.title }}
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span
                                                v-if="task.project"
                                                class="bg-surface text-muted-foreground inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs"
                                            >
                                                <span
                                                    class="h-2 w-2 shrink-0 rounded-full"
                                                    :style="{
                                                        backgroundColor:
                                                            task.project.color || '#6b7280',
                                                    }"
                                                ></span>
                                                {{ task.project.name }}
                                            </span>
                                            <span
                                                v-if="task.assignee"
                                                class="text-muted-foreground inline-flex items-center gap-1.5 text-xs"
                                            >
                                                <div
                                                    class="bg-surface-elevated flex h-4 w-4 items-center justify-center rounded"
                                                >
                                                    <span
                                                        class="text-[8px] font-bold text-amber-400"
                                                        >{{
                                                            task.assignee.name
                                                                ?.charAt(0)
                                                                ?.toUpperCase()
                                                        }}</span
                                                    >
                                                </div>
                                                {{ task.assignee.name }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-2">
                                        <Badge
                                            :tone="
                                                TASK_STATUS_TONES[task.status] ||
                                                TASK_STATUS_TONES.Todo
                                            "
                                        >
                                            {{ task.status }}
                                        </Badge>
                                        <Badge
                                            v-if="task.priority"
                                            :tone="
                                                TASK_PRIORITY_TONES[task.priority] ||
                                                TASK_PRIORITY_TONES.Low
                                            "
                                        >
                                            {{ task.priority }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            <div class="shrink-0 pt-0.5 text-right">
                                <div
                                    v-if="task.due_date"
                                    :class="[
                                        'font-mono text-xs',
                                        isOverdue(task) ? 'text-rose-400' : 'text-muted-foreground',
                                    ]"
                                >
                                    {{
                                        new Date(task.due_date).toLocaleDateString('en-US', {
                                            month: 'short',
                                            day: 'numeric',
                                        })
                                    }}
                                </div>
                                <div
                                    v-if="isOverdue(task)"
                                    class="mt-0.5 text-[10px] tracking-wider text-rose-400 uppercase"
                                >
                                    Overdue
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center">
                                <svg
                                    class="text-muted-foreground h-4 w-4 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-cyan-400"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M9 18l6-6-6-6" />
                                </svg>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-else class="empty-state">
                    <div
                        class="absolute inset-0 bg-linear-to-br from-cyan-500/4 via-transparent to-amber-500/3"
                    ></div>
                    <div class="relative z-10">
                        <div class="empty-state-icon border border-cyan-500/20 bg-cyan-500/10">
                            <svg
                                class="h-8 w-8 text-cyan-400"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path d="M9 11l3 3L22 4" />
                                <path
                                    d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"
                                />
                            </svg>
                        </div>
                        <h3 class="font-display text-foreground mb-2 text-xl">No tasks found</h3>
                        <p class="text-muted-foreground mx-auto mb-6 max-w-sm text-sm">
                            Create your first task or adjust your filters to see results.
                        </p>
                        <Link
                            href="/tasks/create"
                            class="inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-cyan-500 to-cyan-400 px-5 py-3 text-sm font-semibold text-black shadow-lg shadow-cyan-500/15 transition-all duration-300 hover:shadow-cyan-500/25"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                            >
                                <line x1="12" x2="12" y1="5" y2="19" />
                                <line x1="5" x2="19" y1="12" y2="12" />
                            </svg>
                            Create Task
                        </Link>
                    </div>
                </div>

                <PaginationNav :resource="tasks" noun="tasks" tone="cyan" />
            </section>

            <TaskKanbanBoard
                v-else
                :columns="kanbanState.columns"
                :tasks-by-column="kanbanState.tasksByColumn"
                :loading="kanbanState.loading"
                :error="kanbanState.error"
                :prepend-shift-px="kanbanState.prependShiftPx"
                :reset-token="kanbanState.resetToken"
                :focus-date-key="kanbanState.focusDateKey"
                :today-date-key="todayDateKey"
                today-label="Today"
                @ready="ensureKanbanLoaded"
                @move-to-today="moveKanbanToToday"
                @request-previous="loadPreviousWeek"
                @request-next="loadNextWeek"
                @retry="retryKanbanLoad"
                @prepend-shift-applied="clearPrependShift"
                @task-moved="moveTask"
            />
        </PageWidth>
    </AppLayout>
</template>
