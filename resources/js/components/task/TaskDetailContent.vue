<script setup>
import { router } from '@inertiajs/vue3'
import Badge from '@/components/ui/Badge.vue'
import { TASK_PRIORITY_TONES, TASK_STATUS_TONES } from '@/lib/badges'
import TaskComments from '@/components/task/TaskComments.vue'
import {
    Calendar,
    User,
    Folder,
    Clock,
    CheckCircle2,
    PlayCircle,
    Search,
    Circle,
    Zap,
} from 'lucide-vue-next'

const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
})

const isOverdue = () => {
    if (!props.task.due_date || props.task.status === 'Done') return false
    return new Date(props.task.due_date) < new Date()
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    })
}

const updateStatus = (status) => {
    router.patch(
        `/tasks/${props.task.id}/status`,
        { status },
        {
            preserveScroll: true,
        },
    )
}
</script>

<template>
    <div class="space-y-10 pb-10">
        <!-- Task Header: Status & Priority -->
        <div class="flex flex-wrap items-center gap-4">
            <Badge
                variant="soft"
                :tone="TASK_STATUS_TONES[task.status] || TASK_STATUS_TONES.Todo"
                class="px-5 py-1.5 text-[11px] font-black tracking-[0.2em] uppercase shadow-md ring-1 ring-white/10"
            >
                {{ task.status }}
            </Badge>
            <Badge
                variant="pill"
                :tone="TASK_PRIORITY_TONES[task.priority] || TASK_PRIORITY_TONES.Low"
                class="px-3.5 py-1.5 text-[10px] font-bold tracking-wider uppercase shadow-sm"
            >
                <div class="mr-1.5 h-1.5 w-1.5 rounded-full bg-current opacity-70"></div>
                {{ task.priority }}
            </Badge>
        </div>

        <!-- Task Description -->
        <div class="space-y-4">
            <div class="text-muted-foreground/50 flex items-center gap-2.5">
                <div class="bg-border/40 h-px w-4"></div>
                <h4 class="font-mono text-[9px] font-bold tracking-[0.25em] uppercase">
                    Task Description
                </h4>
                <div class="bg-border/40 h-px flex-1"></div>
            </div>
            <div
                v-if="task.description"
                class="grain border-border/50 from-surface-elevated/40 to-surface/40 text-foreground/90 rounded-3xl border bg-linear-to-b px-6 py-5 text-[14px] leading-relaxed shadow-2xs"
            >
                {{ task.description }}
            </div>
            <div
                v-else
                class="border-border/30 bg-surface/10 text-muted-foreground/60 rounded-3xl border border-dashed px-6 py-5 text-xs italic"
            >
                No description provided for this task.
            </div>
        </div>

        <!-- Task Meta Info Grid -->
        <div class="grid grid-cols-2 gap-5">
            <!-- Project -->
            <div
                class="group border-border/40 bg-surface-elevated/30 hover:bg-surface-elevated/50 hover:border-border/60 relative overflow-hidden rounded-2xl border p-4 transition-all hover:-translate-y-1"
            >
                <div class="text-muted-foreground/40 mb-3 flex items-center gap-2">
                    <Folder class="h-3.5 w-3.5" />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase"
                        >Project</span
                    >
                </div>
                <div v-if="task.project" class="flex items-center gap-2.5">
                    <div
                        class="h-2 w-2 rounded-full shadow-[0_0_8px_rgba(var(--amber),0.5)]"
                        :style="{ backgroundColor: task.project.color || '#f59e0b' }"
                    ></div>
                    <span class="text-foreground truncate text-sm font-bold tracking-tight">{{
                        task.project.name
                    }}</span>
                </div>
                <span v-else class="text-muted-foreground/60 text-xs italic">Unassigned</span>
            </div>

            <!-- Assignee -->
            <div
                class="group border-border/40 bg-surface-elevated/30 hover:bg-surface-elevated/50 hover:border-border/60 relative overflow-hidden rounded-2xl border p-4 transition-all hover:-translate-y-1"
            >
                <div class="text-muted-foreground/40 mb-3 flex items-center gap-2">
                    <User class="h-3.5 w-3.5" />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase"
                        >Assignee</span
                    >
                </div>
                <div v-if="task.assignee" class="flex items-center gap-2.5">
                    <div
                        class="flex h-6 w-6 items-center justify-center rounded-lg bg-linear-to-br from-amber-400/80 to-orange-500/80 shadow-inner ring-1 ring-white/10"
                    >
                        <span class="text-[10px] font-black text-black">{{
                            task.assignee.name?.charAt(0).toUpperCase()
                        }}</span>
                    </div>
                    <span class="text-foreground truncate text-sm font-bold tracking-tight">{{
                        task.assignee.name
                    }}</span>
                </div>
                <span v-else class="text-muted-foreground/60 text-xs italic">Open Position</span>
            </div>

            <!-- Due Date -->
            <div
                class="group border-border/40 bg-surface-elevated/30 hover:bg-surface-elevated/50 hover:border-border/60 relative overflow-hidden rounded-2xl border p-4 transition-all hover:-translate-y-1"
            >
                <div class="text-muted-foreground/40 mb-3 flex items-center gap-2">
                    <Calendar class="h-3.5 w-3.5" />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase"
                        >Due Date</span
                    >
                </div>
                <div
                    :class="[
                        'flex items-center gap-2 text-sm font-bold tracking-tight',
                        isOverdue() ? 'text-rose-500 dark:text-rose-400' : 'text-foreground',
                    ]"
                >
                    {{ formatDate(task.due_date) }}
                    <Badge
                        v-if="isOverdue()"
                        tone="rose"
                        variant="compact"
                        class="animate-pulse px-1.5 py-0 text-[8px] font-black"
                        >OVERDUE</Badge
                    >
                </div>
            </div>

            <!-- Created -->
            <div
                class="group border-border/40 bg-surface-elevated/30 hover:bg-surface-elevated/50 hover:border-border/60 relative overflow-hidden rounded-2xl border p-4 transition-all hover:-translate-y-1"
            >
                <div class="text-muted-foreground/40 mb-3 flex items-center gap-2">
                    <Clock class="h-3.5 w-3.5" />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase"
                        >Created At</span
                    >
                </div>
                <div class="text-foreground/80 text-sm font-bold tracking-tight">
                    {{ formatDate(task.created_at) }}
                </div>
            </div>
        </div>

        <!-- Strategic Operations Section -->
        <div class="space-y-5">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-500/10 text-amber-500 ring-1 ring-amber-500/20"
                >
                    <Zap class="h-3.5 w-3.5 shadow-[0_0_8px_rgba(var(--amber),0.4)]" />
                </div>
                <h4
                    class="text-foreground/70 font-mono text-[10px] font-black tracking-[0.3em] uppercase"
                >
                    Task Status
                </h4>
                <div class="from-border/60 h-px flex-1 bg-linear-to-r to-transparent"></div>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <!-- Todo -->
                <button
                    :disabled="task.status === 'Todo'"
                    class="group relative flex flex-col items-center justify-center gap-2 rounded-2xl border p-4 transition-all duration-300"
                    :class="[
                        task.status === 'Todo'
                            ? 'cursor-default border-slate-500/30 bg-slate-500/10 text-slate-400 opacity-50'
                            : 'bg-surface-elevated/40 border-border/40 hover:bg-surface-elevated/80 hover:scale-[1.02] hover:border-slate-500/40 active:scale-95',
                    ]"
                    @click="updateStatus('Todo')"
                >
                    <Circle
                        class="h-5 w-5"
                        :class="
                            task.status === 'Todo'
                                ? 'text-slate-500'
                                : 'text-slate-400 group-hover:text-slate-300'
                        "
                    />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase"
                        >To Do</span
                    >
                    <div
                        v-if="task.status === 'Todo'"
                        class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-slate-500 shadow-[0_0_8px_rgba(var(--muted-foreground),0.5)]"
                    ></div>
                </button>

                <!-- In Progress -->
                <button
                    :disabled="task.status === 'In Progress'"
                    class="group relative flex flex-col items-center justify-center gap-2 rounded-2xl border p-4 transition-all duration-300"
                    :class="[
                        task.status === 'In Progress'
                            ? 'cursor-default border-cyan-500/30 bg-cyan-500/10 text-cyan-400 opacity-50'
                            : 'bg-surface-elevated/40 border-border/40 hover:bg-surface-elevated/80 hover:scale-[1.02] hover:border-cyan-500/40 active:scale-95',
                    ]"
                    @click="updateStatus('In Progress')"
                >
                    <PlayCircle
                        class="h-5 w-5"
                        :class="
                            task.status === 'In Progress'
                                ? 'text-cyan-500'
                                : 'text-cyan-400 group-hover:text-cyan-300'
                        "
                    />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase"
                        >In Progress</span
                    >
                    <div
                        v-if="task.status === 'In Progress'"
                        class="absolute -top-1 -right-1 h-3 w-3 animate-pulse rounded-full bg-cyan-500 shadow-[0_0_8px_rgba(var(--cyan),0.5)]"
                    ></div>
                </button>

                <!-- Review -->
                <button
                    :disabled="task.status === 'Review'"
                    class="group relative flex flex-col items-center justify-center gap-2 rounded-2xl border p-4 transition-all duration-300"
                    :class="[
                        task.status === 'Review'
                            ? 'cursor-default border-amber-500/30 bg-amber-500/10 text-amber-400 opacity-50'
                            : 'bg-surface-elevated/40 border-border/40 hover:bg-surface-elevated/80 hover:scale-[1.02] hover:border-amber-500/40 active:scale-95',
                    ]"
                    @click="updateStatus('Review')"
                >
                    <Search
                        class="h-5 w-5"
                        :class="
                            task.status === 'Review'
                                ? 'text-amber-500'
                                : 'text-amber-400 group-hover:text-amber-300'
                        "
                    />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase"
                        >Review</span
                    >
                    <div
                        v-if="task.status === 'Review'"
                        class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-amber-500 shadow-[0_0_8px_rgba(var(--amber),0.5)]"
                    ></div>
                </button>

                <!-- Done -->
                <button
                    :disabled="task.status === 'Done'"
                    class="group relative flex flex-col items-center justify-center gap-2 rounded-2xl border p-4 transition-all duration-300"
                    :class="[
                        task.status === 'Done'
                            ? 'cursor-default border-emerald-500/30 bg-emerald-500/10 text-emerald-400 opacity-50'
                            : 'bg-surface-elevated/40 border-border/40 hover:bg-surface-elevated/80 shadow-[0_0_15px_rgba(16,185,129,0.05)] hover:scale-[1.02] hover:border-emerald-500/40 active:scale-95',
                    ]"
                    @click="updateStatus('Done')"
                >
                    <CheckCircle2
                        class="h-5 w-5"
                        :class="
                            task.status === 'Done'
                                ? 'text-emerald-500'
                                : 'text-emerald-400 group-hover:text-emerald-300'
                        "
                    />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase"
                        >Done</span
                    >
                    <div
                        v-if="task.status === 'Done'"
                        class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(var(--emerald),0.5)]"
                    ></div>
                </button>
            </div>
        </div>

        <!-- Comments Section (Extracted) -->
        <TaskComments :task-id="task.id" :comments="task.comments || []" class="pt-4" />
    </div>
</template>
