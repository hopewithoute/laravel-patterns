<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import PaginationNav from '@/components/layout/PaginationNav.vue'
import PageWidth from '@/components/layout/PageWidth.vue'
import Badge from '@/components/ui/Badge.vue'
import { TASK_PRIORITY_TONES, TASK_STATUS_TONES } from '@/lib/badges'

defineProps({
    project: Object,
    tasks: Object,
})
</script>

<template>
    <AppLayout>
        <PageWidth size="wide" class="space-y-8">
            <PageHeader
                variant="stacked"
                tone="amber"
                title-size="headline"
                :title="project.name"
                :description="project.description || 'No description provided'"
                description-class="text-lg max-w-xl"
                back-href="/projects"
                back-label="All Projects"
                glow-class="top-0 right-0 w-96 h-96 bg-amber-500/5"
            >
                <template #media>
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl shadow-lg ring-2 ring-white/10"
                        :style="{ backgroundColor: project.color || '#f59e0b' }"
                    >
                        <span class="text-xl font-bold text-black">{{
                            project.name?.charAt(0)?.toUpperCase()
                        }}</span>
                    </div>
                </template>

                <template #actions>
                    <Link
                        :href="`/projects/${project.id}/edit`"
                        class="border-border/60 bg-surface/50 text-foreground hover:bg-surface hover:border-border inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                        </svg>
                        Edit
                    </Link>
                    <Link
                        :href="`/tasks/create?project=${project.id}`"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-4 py-2.5 text-sm font-semibold text-black shadow-lg shadow-amber-500/20 transition-all duration-300 hover:shadow-amber-500/30"
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
                        Add Task
                    </Link>
                </template>
            </PageHeader>

            <!-- ═══════════════════════════════════════════════════════════════════
                STATS
                 ═══════════════════════════════════════════════════════════════════ -->
            <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="bg-card border-border/50 rounded-xl border p-4">
                    <div
                        class="text-muted-foreground mb-1 font-mono text-[10px] tracking-wider uppercase"
                    >
                        Total
                    </div>
                    <div class="font-display text-foreground text-2xl font-bold">
                        {{ project.tasks_count || 0 }}
                    </div>
                    <div class="text-muted-foreground text-sm">Tasks</div>
                </div>
                <div class="bg-card border-border/50 rounded-xl border p-4">
                    <div
                        class="text-muted-foreground mb-1 font-mono text-[10px] tracking-wider uppercase"
                    >
                        Done
                    </div>
                    <div class="font-display text-2xl font-bold text-emerald-400">
                        {{ project.done_tasks_count || 0 }}
                    </div>
                    <div class="text-muted-foreground text-sm">Completed</div>
                </div>
                <div class="bg-card border-border/50 rounded-xl border p-4">
                    <div
                        class="text-muted-foreground mb-1 font-mono text-[10px] tracking-wider uppercase"
                    >
                        Active
                    </div>
                    <div class="font-display text-2xl font-bold text-cyan-400">
                        {{ project.in_progress_tasks_count || 0 }}
                    </div>
                    <div class="text-muted-foreground text-sm">In Progress</div>
                </div>
                <div class="bg-card border-border/50 rounded-xl border p-4">
                    <div
                        class="text-muted-foreground mb-1 font-mono text-[10px] tracking-wider uppercase"
                    >
                        Queue
                    </div>
                    <div class="font-display text-2xl font-bold text-slate-400">
                        {{ project.todo_tasks_count || 0 }}
                    </div>
                    <div class="text-muted-foreground text-sm">Pending</div>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════════
                 TASKS LIST
                 ═══════════════════════════════════════════════════════════════════ -->
            <section>
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-display text-foreground text-xl font-bold">Tasks</h2>
                </div>

                <div v-if="tasks.data.length > 0" class="space-y-2">
                    <Link
                        v-for="task in tasks.data"
                        :key="task.id"
                        :href="`/tasks/${task.id}`"
                        class="group bg-card border-border/50 hover:border-border block rounded-xl border p-4 transition-all duration-300"
                    >
                        <div class="flex items-center gap-4">
                            <!-- Status indicator -->
                            <div
                                :class="[
                                    'h-2.5 w-2.5 shrink-0 rounded-full',
                                    task.status === 'Done'
                                        ? 'bg-emerald-500'
                                        : task.status === 'In Progress'
                                          ? 'bg-cyan-500'
                                          : task.status === 'Review'
                                            ? 'bg-amber-500'
                                            : 'bg-slate-500',
                                ]"
                            ></div>

                            <!-- Content -->
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <span
                                            class="text-foreground font-medium transition-colors group-hover:text-amber-400"
                                            >{{ task.title }}</span
                                        >
                                        <p
                                            v-if="task.description"
                                            class="text-muted-foreground mt-0.5 line-clamp-1 text-sm"
                                        >
                                            {{ task.description }}
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <Badge
                                            variant="compact"
                                            :tone="
                                                TASK_STATUS_TONES[task.status] ||
                                                TASK_STATUS_TONES.Todo
                                            "
                                        >
                                            {{ task.status }}
                                        </Badge>
                                        <Badge
                                            v-if="task.priority"
                                            variant="compact"
                                            :tone="
                                                TASK_PRIORITY_TONES[task.priority] ||
                                                TASK_PRIORITY_TONES.Low
                                            "
                                        >
                                            {{ task.priority }}
                                        </Badge>
                                    </div>
                                </div>

                                <!-- Meta -->
                                <div
                                    class="text-muted-foreground mt-2 flex items-center gap-4 text-xs"
                                >
                                    <span v-if="task.assignee" class="flex items-center gap-1.5">
                                        <div
                                            class="bg-surface-elevated flex h-4 w-4 items-center justify-center rounded"
                                        >
                                            <span class="text-[8px] font-bold text-amber-400">{{
                                                task.assignee.name?.charAt(0)?.toUpperCase()
                                            }}</span>
                                        </div>
                                        {{ task.assignee.name }}
                                    </span>
                                    <span v-if="task.due_date" class="flex items-center gap-1">
                                        <svg
                                            class="h-3 w-3"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <rect
                                                x="3"
                                                y="4"
                                                width="18"
                                                height="18"
                                                rx="2"
                                                ry="2"
                                            />
                                            <line x1="16" x2="16" y1="2" y2="6" />
                                            <line x1="8" x2="8" y1="2" y2="6" />
                                            <line x1="3" x2="21" y1="10" y2="10" />
                                        </svg>
                                        {{
                                            new Date(task.due_date).toLocaleDateString('en-US', {
                                                month: 'short',
                                                day: 'numeric',
                                            })
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="border-border/50 bg-card relative overflow-hidden rounded-2xl border p-12 text-center"
                >
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-cyan-500/5 via-transparent to-amber-500/5"
                    ></div>
                    <div class="relative z-10">
                        <div
                            class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500/20 to-cyan-500/5"
                        >
                            <svg
                                class="h-7 w-7 text-cyan-400"
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
                        <h3 class="font-display text-foreground mb-2 text-lg font-bold">
                            No tasks yet
                        </h3>
                        <p class="text-muted-foreground mx-auto mb-6 max-w-sm text-sm">
                            Add tasks to this project to start tracking your progress.
                        </p>
                        <Link
                            :href="`/tasks/create?project=${project.id}`"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-4 py-2.5 text-sm font-semibold text-black shadow-lg shadow-amber-500/20 transition-all duration-300 hover:shadow-amber-500/30"
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
                            Add Task
                        </Link>
                    </div>
                </div>
            </section>

            <PaginationNav :resource="tasks" noun="tasks" tone="amber" />
        </PageWidth>
    </AppLayout>
</template>
