<script setup>
import { Link, router } from '@inertiajs/vue3'
import Badge from '@/components/ui/Badge.vue'
import { TASK_PRIORITY_TONES, TASK_STATUS_TONES } from '@/lib/badges'
import TaskComments from '@/components/task/TaskComments.vue'
import { Calendar, User, Folder, Layout, Clock, CheckCircle2, PlayCircle, Search, Circle, Zap } from 'lucide-vue-next'

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
    router.patch(`/tasks/${props.task.id}/status`, { status }, {
        preserveScroll: true,
    })
}
</script>

<template>
    <div class="space-y-10 pb-10">
        <!-- Task Header: Status & Priority -->
        <div class="flex flex-wrap items-center gap-4">
            <Badge 
                variant="soft" 
                :tone="TASK_STATUS_TONES[task.status] || TASK_STATUS_TONES.Todo"
                class="px-5 py-1.5 text-[11px] font-black uppercase tracking-[0.2em] shadow-md ring-1 ring-white/10"
            >
                {{ task.status }}
            </Badge>
            <Badge 
                variant="pill" 
                :tone="TASK_PRIORITY_TONES[task.priority] || TASK_PRIORITY_TONES.Low"
                class="px-3.5 py-1.5 text-[10px] font-bold uppercase tracking-wider shadow-sm"
            >
                <div class="mr-1.5 h-1.5 w-1.5 rounded-full bg-current opacity-70"></div>
                {{ task.priority }}
            </Badge>
        </div>

        <!-- Task Description -->
        <div class="space-y-4">
            <div class="flex items-center gap-2.5 text-muted-foreground/50">
                <div class="h-px w-4 bg-border/40"></div>
                <h4 class="font-mono text-[9px] font-bold tracking-[0.25em] uppercase">Task Description</h4>
                <div class="flex-1 h-px bg-border/40"></div>
            </div>
            <div v-if="task.description" class="grain rounded-3xl border border-border/50 bg-linear-to-b from-surface-elevated/40 to-surface/40 px-6 py-5 text-[14px] leading-relaxed text-foreground/90 shadow-2xs">
                {{ task.description }}
            </div>
            <div v-else class="rounded-3xl border border-dashed border-border/30 bg-surface/10 px-6 py-5 text-xs italic text-muted-foreground/60">
                No description provided for this task.
            </div>
        </div>

        <!-- Task Meta Info Grid -->
        <div class="grid grid-cols-2 gap-5">
            <!-- Project -->
            <div class="group relative overflow-hidden rounded-2xl border border-border/40 bg-surface-elevated/30 p-4 transition-all hover:bg-surface-elevated/50 hover:border-border/60 hover:-translate-y-1">
                <div class="mb-3 flex items-center gap-2 text-muted-foreground/40">
                    <Folder class="h-3.5 w-3.5" />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase">Project</span>
                </div>
                <div v-if="task.project" class="flex items-center gap-2.5">
                    <div class="h-2 w-2 rounded-full shadow-[0_0_8px_rgba(var(--amber),0.5)]" :style="{ backgroundColor: task.project.color || '#f59e0b' }"></div>
                    <span class="text-sm font-bold text-foreground truncate tracking-tight">{{ task.project.name }}</span>
                </div>
                <span v-else class="text-xs text-muted-foreground/60 italic">Unassigned</span>
            </div>

            <!-- Assignee -->
            <div class="group relative overflow-hidden rounded-2xl border border-border/40 bg-surface-elevated/30 p-4 transition-all hover:bg-surface-elevated/50 hover:border-border/60 hover:-translate-y-1">
                <div class="mb-3 flex items-center gap-2 text-muted-foreground/40">
                    <User class="h-3.5 w-3.5" />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase">Assignee</span>
                </div>
                <div v-if="task.assignee" class="flex items-center gap-2.5">
                    <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-linear-to-br from-amber-400/80 to-orange-500/80 shadow-inner ring-1 ring-white/10">
                        <span class="text-[10px] font-black text-black">{{ task.assignee.name?.charAt(0).toUpperCase() }}</span>
                    </div>
                    <span class="text-sm font-bold text-foreground truncate tracking-tight">{{ task.assignee.name }}</span>
                </div>
                <span v-else class="text-xs text-muted-foreground/60 italic">Open Position</span>
            </div>

            <!-- Due Date -->
            <div class="group relative overflow-hidden rounded-2xl border border-border/40 bg-surface-elevated/30 p-4 transition-all hover:bg-surface-elevated/50 hover:border-border/60 hover:-translate-y-1">
                <div class="mb-3 flex items-center gap-2 text-muted-foreground/40">
                    <Calendar class="h-3.5 w-3.5" />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase">Due Date</span>
                </div>
                <div 
                    :class="[
                        'text-sm font-bold flex items-center gap-2 tracking-tight',
                        isOverdue() ? 'text-rose-500 dark:text-rose-400' : 'text-foreground'
                    ]"
                >
                    {{ formatDate(task.due_date) }}
                    <Badge v-if="isOverdue()" tone="rose" variant="compact" class="text-[8px] px-1.5 py-0 font-black animate-pulse">OVERDUE</Badge>
                </div>
            </div>

            <!-- Created -->
            <div class="group relative overflow-hidden rounded-2xl border border-border/40 bg-surface-elevated/30 p-4 transition-all hover:bg-surface-elevated/50 hover:border-border/60 hover:-translate-y-1">
                <div class="mb-3 flex items-center gap-2 text-muted-foreground/40">
                    <Clock class="h-3.5 w-3.5" />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase">Created At</span>
                </div>
                <div class="text-sm font-bold text-foreground/80 tracking-tight">
                    {{ formatDate(task.created_at) }}
                </div>
            </div>
        </div>

        <!-- Strategic Operations Section -->
        <div class="space-y-5">
            <div class="flex items-center gap-3">
                <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-500/10 text-amber-500 ring-1 ring-amber-500/20">
                    <Zap class="h-3.5 w-3.5 shadow-[0_0_8px_rgba(var(--amber),0.4)]" />
                </div>
                <h4 class="font-mono text-[10px] font-black tracking-[0.3em] uppercase text-foreground/70">Task Status</h4>
                <div class="h-px flex-1 bg-linear-to-r from-border/60 to-transparent"></div>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <!-- Todo -->
                <button 
                    @click="updateStatus('Todo')"
                    :disabled="task.status === 'Todo'"
                    class="group relative flex flex-col items-center justify-center gap-2 rounded-2xl border p-4 transition-all duration-300"
                    :class="[
                        task.status === 'Todo' 
                            ? 'bg-slate-500/10 border-slate-500/30 text-slate-400 opacity-50 cursor-default' 
                            : 'bg-surface-elevated/40 border-border/40 hover:bg-surface-elevated/80 hover:border-slate-500/40 hover:scale-[1.02] active:scale-95'
                    ]"
                >
                    <Circle class="h-5 w-5" :class="task.status === 'Todo' ? 'text-slate-500' : 'text-slate-400 group-hover:text-slate-300'" />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase">To Do</span>
                    <div v-if="task.status === 'Todo'" class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-slate-500 shadow-[0_0_8px_rgba(var(--muted-foreground),0.5)]"></div>
                </button>

                <!-- In Progress -->
                <button 
                    @click="updateStatus('In Progress')"
                    :disabled="task.status === 'In Progress'"
                    class="group relative flex flex-col items-center justify-center gap-2 rounded-2xl border p-4 transition-all duration-300"
                    :class="[
                        task.status === 'In Progress' 
                            ? 'bg-cyan-500/10 border-cyan-500/30 text-cyan-400 opacity-50 cursor-default' 
                            : 'bg-surface-elevated/40 border-border/40 hover:bg-surface-elevated/80 hover:border-cyan-500/40 hover:scale-[1.02] active:scale-95'
                    ]"
                >
                    <PlayCircle class="h-5 w-5" :class="task.status === 'In Progress' ? 'text-cyan-500' : 'text-cyan-400 group-hover:text-cyan-300'" />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase">In Progress</span>
                    <div v-if="task.status === 'In Progress'" class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-cyan-500 shadow-[0_0_8px_rgba(var(--cyan),0.5)] animate-pulse"></div>
                </button>

                <!-- Review -->
                <button 
                    @click="updateStatus('Review')"
                    :disabled="task.status === 'Review'"
                    class="group relative flex flex-col items-center justify-center gap-2 rounded-2xl border p-4 transition-all duration-300"
                    :class="[
                        task.status === 'Review' 
                            ? 'bg-amber-500/10 border-amber-500/30 text-amber-400 opacity-50 cursor-default' 
                            : 'bg-surface-elevated/40 border-border/40 hover:bg-surface-elevated/80 hover:border-amber-500/40 hover:scale-[1.02] active:scale-95'
                    ]"
                >
                    <Search class="h-5 w-5" :class="task.status === 'Review' ? 'text-amber-500' : 'text-amber-400 group-hover:text-amber-300'" />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase">Review</span>
                    <div v-if="task.status === 'Review'" class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-amber-500 shadow-[0_0_8px_rgba(var(--amber),0.5)]"></div>
                </button>

                <!-- Done -->
                <button 
                    @click="updateStatus('Done')"
                    :disabled="task.status === 'Done'"
                    class="group relative flex flex-col items-center justify-center gap-2 rounded-2xl border p-4 transition-all duration-300"
                    :class="[
                        task.status === 'Done' 
                            ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400 opacity-50 cursor-default' 
                            : 'bg-surface-elevated/40 border-border/40 hover:bg-surface-elevated/80 hover:border-emerald-500/40 hover:scale-[1.02] active:scale-95 shadow-[0_0_15px_rgba(16,185,129,0.05)]'
                    ]"
                >
                    <CheckCircle2 class="h-5 w-5" :class="task.status === 'Done' ? 'text-emerald-500' : 'text-emerald-400 group-hover:text-emerald-300'" />
                    <span class="font-mono text-[9px] font-bold tracking-widest uppercase">Done</span>
                    <div v-if="task.status === 'Done'" class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(var(--emerald),0.5)]"></div>
                </button>
            </div>
        </div>

        <!-- Comments Section (Extracted) -->
        <TaskComments 
            :task-id="task.id" 
            :comments="task.comments || []"
            class="pt-4"
        />
    </div>
</template>
