<script setup>
import { Link, Head } from '@inertiajs/vue3'
import PageWidth from '@/components/layout/PageWidth.vue'
import Badge from '@/components/ui/Badge.vue'
import { TASK_STATUS_TONES } from '@/lib/badges'

defineProps({
    auth: Object,
    stats: {
        type: Object,
        default: () => ({
            totalProjects: 0,
            activeProjects: 0,
            totalTasks: 0,
            completedTasks: 0,
            pendingTasks: 0,
            overdueTasks: 0,
        }),
    },
    recentProjects: {
        type: Array,
        default: () => [],
    },
    upcomingTasks: {
        type: Array,
        default: () => [],
    },
})
</script>

<template>
    <Head title="Dashboard" />
    <PageWidth size="wide" class="space-y-8">
            <!-- ═══════════════════════════════════════════════════════════════════
                 HERO — Editorial greeting
                 ═══════════════════════════════════════════════════════════════════ -->
            <section class="relative overflow-hidden">
                <!-- Decorative orbs -->
                <div
                    class="absolute top-0 right-0 h-80 w-80 rounded-full bg-amber-500/4 blur-3xl"
                ></div>
                <div
                    class="absolute bottom-0 left-1/4 h-64 w-64 rounded-full bg-cyan-500/2.5 blur-3xl"
                ></div>

                <div class="relative z-10">
                    <div class="flex flex-col items-start justify-between gap-6 lg:flex-row">
                        <div class="space-y-4">
                            <div
                                class="section-badge section-badge-amber inline-flex items-center gap-2"
                            >
                                <div
                                    class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-400"
                                ></div>
                                <span
                                    class="font-mono text-[11px] tracking-widest text-amber-400 uppercase"
                                    >Dashboard</span
                                >
                            </div>
                            <h1
                                class="font-display text-5xl leading-[0.95] tracking-tight sm:text-6xl"
                            >
                                <span class="text-foreground">Welcome back, </span>
                                <em class="text-gradient">{{
                                    auth?.user?.name?.split(' ')[0] || 'User'
                                }}</em>
                            </h1>
                            <p
                                class="text-muted-foreground max-w-xl text-lg leading-relaxed font-light"
                            >
                                Your workspace is active. Here's what's happening across your
                                projects today.
                            </p>
                        </div>

                        <!-- Quick Actions -->
                        <div class="hidden items-start gap-3 pt-2 lg:flex">
                            <Link
                                href="/projects/create"
                                class="group relative overflow-hidden rounded-xl bg-linear-to-r from-amber-500 to-orange-500 px-5 py-3 text-sm font-semibold text-black shadow-lg shadow-amber-500/15 transition-all duration-300 hover:shadow-amber-500/25"
                            >
                                <div
                                    class="absolute inset-0 -translate-x-full bg-linear-to-r from-transparent via-white/20 to-transparent transition-transform duration-700 group-hover:translate-x-full"
                                ></div>
                                <span class="relative flex items-center gap-2">
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
                                    New Project
                                </span>
                            </Link>
                            <Link
                                href="/tasks/create"
                                class="border-border/40 bg-surface/40 text-foreground hover:bg-surface/60 hover:border-border/60 flex items-center gap-2 rounded-xl border px-5 py-3 text-sm font-medium transition-all duration-200"
                            >
                                <svg
                                    class="h-4 w-4 text-cyan-400"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M9 11l3 3L22 4" />
                                    <path
                                        d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"
                                    />
                                </svg>
                                Add Task
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════════
                 STATS GRID — refined cards with luminous icons
                 ═══════════════════════════════════════════════════════════════════ -->
            <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <!-- Total Projects -->
                <div
                    class="group bg-card border-border/40 hover:border-border/60 animate-fade-in-up stagger-1 relative overflow-hidden rounded-2xl border p-5 transition-all duration-300"
                >
                    <div
                        class="absolute top-0 right-0 h-32 w-32 rounded-full bg-amber-500/4 opacity-0 blur-3xl transition-opacity duration-500 group-hover:opacity-100"
                    ></div>
                    <div class="relative z-10">
                        <div class="mb-3 flex items-center justify-between">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl border border-amber-500/20 bg-amber-500/10"
                            >
                                <svg
                                    class="h-5 w-5 text-amber-400"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                >
                                    <path
                                        d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"
                                    />
                                </svg>
                            </div>
                            <span
                                class="text-muted-foreground font-mono text-[10px] tracking-widest uppercase"
                                >Total</span
                            >
                        </div>
                        <div class="font-display text-foreground mb-0.5 text-3xl">
                            {{ stats.totalProjects || 0 }}
                        </div>
                        <div class="text-muted-foreground text-sm">Projects</div>
                    </div>
                </div>

                <!-- Completed Tasks -->
                <div
                    class="group bg-card border-border/40 hover:border-border/60 animate-fade-in-up stagger-2 relative overflow-hidden rounded-2xl border p-5 transition-all duration-300"
                >
                    <div
                        class="absolute top-0 right-0 h-32 w-32 rounded-full bg-emerald-500/4 opacity-0 blur-3xl transition-opacity duration-500 group-hover:opacity-100"
                    ></div>
                    <div class="relative z-10">
                        <div class="mb-3 flex items-center justify-between">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-500/20 bg-emerald-500/10"
                            >
                                <svg
                                    class="h-5 w-5 text-emerald-400"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                >
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
                            </div>
                            <span
                                class="text-muted-foreground font-mono text-[10px] tracking-widest uppercase"
                                >Done</span
                            >
                        </div>
                        <div class="font-display text-foreground mb-0.5 text-3xl">
                            {{ stats.completedTasks || 0 }}
                        </div>
                        <div class="text-muted-foreground text-sm">Completed</div>
                    </div>
                </div>

                <!-- In Progress -->
                <div
                    class="group bg-card border-border/40 hover:border-border/60 animate-fade-in-up stagger-3 relative overflow-hidden rounded-2xl border p-5 transition-all duration-300"
                >
                    <div
                        class="absolute top-0 right-0 h-32 w-32 rounded-full bg-cyan-500/4 opacity-0 blur-3xl transition-opacity duration-500 group-hover:opacity-100"
                    ></div>
                    <div class="relative z-10">
                        <div class="mb-3 flex items-center justify-between">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl border border-cyan-500/20 bg-cyan-500/10"
                            >
                                <svg
                                    class="h-5 w-5 text-cyan-400"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                >
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 6v6l4 2" />
                                </svg>
                            </div>
                            <span
                                class="text-muted-foreground font-mono text-[10px] tracking-widest uppercase"
                                >Active</span
                            >
                        </div>
                        <div class="font-display text-foreground mb-0.5 text-3xl">
                            {{ stats.pendingTasks || 0 }}
                        </div>
                        <div class="text-muted-foreground text-sm">In Progress</div>
                    </div>
                </div>

                <!-- Overdue -->
                <div
                    class="group bg-card border-border/40 hover:border-border/60 animate-fade-in-up stagger-4 relative overflow-hidden rounded-2xl border p-5 transition-all duration-300"
                >
                    <div
                        class="absolute top-0 right-0 h-32 w-32 rounded-full bg-rose-500/4 opacity-0 blur-3xl transition-opacity duration-500 group-hover:opacity-100"
                    ></div>
                    <div class="relative z-10">
                        <div class="mb-3 flex items-center justify-between">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl border border-rose-500/20 bg-rose-500/10"
                            >
                                <svg
                                    class="h-5 w-5 text-rose-400"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                >
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="12" x2="12" y1="8" y2="12" />
                                    <line x1="12" x2="12.01" y1="16" y2="16" />
                                </svg>
                            </div>
                            <span
                                class="text-muted-foreground font-mono text-[10px] tracking-widest uppercase"
                                >Alert</span
                            >
                        </div>
                        <div class="font-display text-foreground mb-0.5 text-3xl">
                            {{ stats.overdueTasks || 0 }}
                        </div>
                        <div class="text-muted-foreground text-sm">Overdue</div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════════
                 CONTENT GRID
                 ═══════════════════════════════════════════════════════════════════ -->
            <section class="grid gap-6 lg:grid-cols-3">
                <!-- Recent Projects - Takes 2 columns -->
                <div class="animate-fade-in-up stagger-5 space-y-4 lg:col-span-2">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display text-foreground text-2xl">Recent Projects</h2>
                        <Link
                            href="/projects"
                            class="text-muted-foreground group flex items-center gap-1 text-sm transition-colors hover:text-amber-400"
                        >
                            View all
                            <svg
                                class="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M9 18l6-6-6-6" />
                            </svg>
                        </Link>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="project in recentProjects.length > 0
                                ? recentProjects
                                : [
                                      {
                                          id: 1,
                                          name: 'Website Redesign',
                                          description: 'Complete overhaul of the marketing site',
                                          total_tasks: 12,
                                          completed_tasks: 8,
                                          color: '#f59e0b',
                                      },
                                      {
                                          id: 2,
                                          name: 'Mobile App',
                                          description: 'iOS and Android development',
                                          total_tasks: 24,
                                          completed_tasks: 16,
                                          color: '#06b6d4',
                                      },
                                      {
                                          id: 3,
                                          name: 'API Integration',
                                          description: 'Third-party service connections',
                                          total_tasks: 8,
                                          completed_tasks: 5,
                                          color: '#8b5cf6',
                                      },
                                  ]"
                            :key="project.id"
                            class="group bg-card border-border/40 hover:border-border/60 relative overflow-hidden rounded-xl border p-4 transition-all duration-300"
                        >
                            <div
                                class="absolute top-0 right-0 left-0 h-16 opacity-0 transition-opacity duration-500 group-hover:opacity-100"
                                :style="{
                                    background: `linear-gradient(180deg, ${project.color || '#f59e0b'}08 0%, transparent 100%)`,
                                }"
                            ></div>
                            <div class="relative z-10 flex items-start gap-4">
                                <div
                                    class="mt-1.5 h-3 w-3 shrink-0 rounded-full"
                                    :style="{
                                        backgroundColor: project.color || '#f59e0b',
                                        boxShadow: `0 0 10px ${project.color || '#f59e0b'}35`,
                                    }"
                                ></div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <Link
                                                :href="`/projects/${project.id}`"
                                                class="text-foreground font-semibold transition-colors hover:text-amber-400"
                                            >
                                                {{ project.name }}
                                            </Link>
                                            <p
                                                class="text-muted-foreground mt-0.5 line-clamp-1 text-sm"
                                            >
                                                {{ project.description || 'No description' }}
                                            </p>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <div class="text-foreground font-mono text-sm">
                                                {{ project.completed_tasks || 0 }}/{{
                                                    project.total_tasks || 0
                                                }}
                                            </div>
                                            <div
                                                class="text-muted-foreground text-[10px] tracking-wider uppercase"
                                            >
                                                tasks
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Progress bar -->
                                    <div class="mt-3 flex items-center gap-3">
                                        <div
                                            class="bg-surface h-1.5 flex-1 overflow-hidden rounded-full"
                                        >
                                            <div
                                                class="h-full rounded-full transition-all duration-700"
                                                :style="{
                                                    width: `${project.total_tasks > 0 ? Math.round((project.completed_tasks / project.total_tasks) * 100) : 0}%`,
                                                    backgroundColor: project.color || '#f59e0b',
                                                }"
                                            ></div>
                                        </div>
                                        <span
                                            class="text-muted-foreground w-10 text-right font-mono text-xs"
                                        >
                                            {{
                                                project.total_tasks > 0
                                                    ? Math.round(
                                                          (project.completed_tasks /
                                                              project.total_tasks) *
                                                              100,
                                                      )
                                                    : 0
                                            }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Tasks -->
                <div class="animate-fade-in-up stagger-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display text-foreground text-2xl">Upcoming</h2>
                        <Link
                            href="/tasks"
                            class="text-muted-foreground group flex items-center gap-1 text-sm transition-colors hover:text-cyan-400"
                        >
                            All tasks
                            <svg
                                class="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M9 18l6-6-6-6" />
                            </svg>
                        </Link>
                    </div>

                    <div class="space-y-2">
                        <div
                            v-for="task in upcomingTasks.length > 0
                                ? upcomingTasks
                                : [
                                      {
                                          id: 1,
                                          title: 'Design system audit',
                                          status: 'In Progress',
                                          due_date: new Date(Date.now() + 86400000).toISOString(),
                                          priority: 'High',
                                      },
                                      {
                                          id: 2,
                                          title: 'User research synthesis',
                                          status: 'Todo',
                                          due_date: new Date(Date.now() + 172800000).toISOString(),
                                          priority: 'Medium',
                                      },
                                      {
                                          id: 3,
                                          title: 'Performance optimization',
                                          status: 'Review',
                                          due_date: new Date(Date.now() + 259200000).toISOString(),
                                          priority: 'Low',
                                      },
                                      {
                                          id: 4,
                                          title: 'Documentation update',
                                          status: 'Todo',
                                          due_date: new Date(Date.now() + 345600000).toISOString(),
                                          priority: 'Medium',
                                      },
                                  ]"
                            :key="task.id"
                            class="group bg-card border-border/40 hover:border-border/60 rounded-xl border p-3 transition-all duration-300"
                        >
                            <Link :href="`/tasks/${task.id}`" class="block">
                                <div class="flex items-start gap-3">
                                    <div
                                        :class="[
                                            'mt-2 h-1.5 w-1.5 shrink-0 rounded-full transition-transform group-hover:scale-125',
                                            task.status === 'Done'
                                                ? 'bg-emerald-500'
                                                : task.status === 'In Progress'
                                                  ? 'bg-cyan-500'
                                                  : task.status === 'Review'
                                                    ? 'bg-amber-500'
                                                    : 'bg-slate-500',
                                        ]"
                                    ></div>
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="text-foreground line-clamp-1 text-sm font-medium transition-colors group-hover:text-cyan-400"
                                        >
                                            {{ task.title }}
                                        </div>
                                        <div class="mt-1.5 flex items-center gap-2">
                                            <Badge
                                                variant="compact"
                                                :tone="
                                                    TASK_STATUS_TONES[task.status] ||
                                                    TASK_STATUS_TONES.Todo
                                                "
                                            >
                                                {{ task.status }}
                                            </Badge>
                                            <span
                                                class="text-muted-foreground font-mono text-[10px]"
                                            >
                                                {{
                                                    new Date(task.due_date).toLocaleDateString(
                                                        'en-US',
                                                        { month: 'short', day: 'numeric' },
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </PageWidth>
</template>
