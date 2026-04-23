<script setup>
import PageHeader from '@/components/layout/PageHeader.vue'
import PageWidth from '@/components/layout/PageWidth.vue'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Badge from '@/components/ui/Badge.vue'
import { TASK_STATUS_TONES } from '@/lib/badges'
import { Link, Head } from '@inertiajs/vue3'

defineProps({
    projects: {
        type: Object,
        default: () => ({ total: 0, active: 0 }),
    },
    tasks: {
        type: Object,
        default: () => ({ total: 0, todo: 0, in_progress: 0, review: 0, done: 0, overdue: 0 }),
    },
    recentTasks: {
        type: Array,
        default: () => [],
    },
    upcomingDeadlines: {
        type: Array,
        default: () => [],
    },
})
</script>

<template>
    <Head title="Dashboard" />
    <PageWidth size="wide" class="space-y-8">
        <PageHeader
            badge="Overview"
            title="Dashboard"
            description="Track project health, task flow, and the work that needs attention this week."
            tone="amber"
            glow-class="top-0 right-1/4 w-80 h-80 bg-amber-500/[0.04]"
        >
            <template #badge-icon>
                <svg
                    class="h-3.5 w-3.5 text-amber-400"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                >
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 7v5l3 2" />
                </svg>
            </template>

            <template #actions>
                <Link
                    href="/projects/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-3 text-sm font-semibold text-black shadow-lg shadow-amber-500/15 transition-all duration-300 hover:shadow-amber-500/25"
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
                    New Project
                </Link>
                <Link
                    href="/tasks/create"
                    class="border-border/40 bg-surface/40 text-foreground hover:bg-surface/60 hover:border-border/60 inline-flex items-center gap-2 rounded-xl border px-5 py-3 text-sm font-medium transition-all duration-200"
                >
                    <svg
                        class="h-4 w-4 text-cyan-400"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M9 11l3 3L22 4" />
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                    </svg>
                    New Task
                </Link>
            </template>
        </PageHeader>

        <!-- Stats Cards -->
        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <!-- Total Projects -->
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">
                        Total Projects
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-foreground text-3xl font-bold">{{ projects?.total ?? 0 }}</p>
                    <p class="text-muted-foreground mt-1 text-xs">
                        {{ projects?.active ?? 0 }} active
                    </p>
                </CardContent>
            </Card>

            <!-- Total Tasks -->
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">
                        Total Tasks
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-foreground text-3xl font-bold">{{ tasks?.total ?? 0 }}</p>
                    <p class="text-muted-foreground mt-1 text-xs">
                        {{ tasks?.done ?? 0 }} completed
                    </p>
                </CardContent>
            </Card>

            <!-- Overdue Tasks -->
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">
                        Overdue
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p
                        class="text-3xl font-bold"
                        :class="(tasks?.overdue ?? 0) > 0 ? 'text-red-600' : 'text-foreground'"
                    >
                        {{ tasks?.overdue ?? 0 }}
                    </p>
                    <p class="text-muted-foreground mt-1 text-xs">tasks past due</p>
                </CardContent>
            </Card>

            <!-- Upcoming Deadlines -->
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">
                        Due This Week
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-foreground text-3xl font-bold">
                        {{ upcoming_deadlines?.length ?? 0 }}
                    </p>
                    <p class="text-muted-foreground mt-1 text-xs">upcoming tasks</p>
                </CardContent>
            </Card>
        </div>

        <!-- Task Status Breakdown -->
        <div class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Task Status</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground text-sm">To Do</span>
                            <span class="font-medium">{{ tasks?.todo ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground text-sm">In Progress</span>
                            <span class="font-medium">{{ tasks?.in_progress ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground text-sm">Review</span>
                            <span class="font-medium">{{ tasks?.review ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground text-sm">Done</span>
                            <span class="font-medium text-green-600">{{ tasks?.done ?? 0 }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                    <CardTitle>Quick Actions</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col gap-3">
                        <Link
                            href="/projects/create"
                            class="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium"
                        >
                            <svg
                                class="mr-2 -ml-1 h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            New Project
                        </Link>
                        <Link
                            href="/tasks/create"
                            class="border-border bg-card text-foreground hover:bg-muted inline-flex items-center justify-center rounded-md border px-4 py-2 text-sm font-medium"
                        >
                            <svg
                                class="mr-2 -ml-1 h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            New Task
                        </Link>
                        <Link
                            href="/ai"
                            class="inline-flex items-center justify-center rounded-md border border-cyan-500/20 bg-cyan-500/8 px-4 py-2 text-sm font-medium text-cyan-500 transition-colors hover:bg-cyan-500/14"
                        >
                            <svg
                                class="mr-2 -ml-1 h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M12 3l1.9 4.6L19 9.5l-4 3.4 1.2 5.1L12 15.5 7.8 18l1.2-5.1-4-3.4 5.1-1.9L12 3z"
                                />
                            </svg>
                            Open AI Chat
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Recent Tasks -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <CardTitle>Recent Tasks</CardTitle>
                    <Link href="/tasks" class="text-primary hover:text-primary/80 text-sm">
                        View all
                    </Link>
                </div>
            </CardHeader>
            <CardContent>
                <div v-if="recent_tasks?.length > 0" class="divide-border divide-y">
                    <div
                        v-for="task in recent_tasks"
                        :key="task.id"
                        class="flex items-center justify-between py-3"
                    >
                        <div class="flex items-center gap-3">
                            <Badge
                                variant="compact"
                                :tone="TASK_STATUS_TONES[task.status] || TASK_STATUS_TONES.Todo"
                            >
                                {{ task.status }}
                            </Badge>
                            <Link
                                :href="`/tasks/${task.id}`"
                                class="text-foreground hover:text-primary text-sm"
                            >
                                {{ task.title }}
                            </Link>
                        </div>
                        <span v-if="task.project" class="text-muted-foreground text-xs">
                            {{ task.project.name }}
                        </span>
                    </div>
                </div>
                <div v-else class="text-muted-foreground py-8 text-center">
                    No recent tasks. Create your first task to get started.
                </div>
            </CardContent>
        </Card>
    </PageWidth>
</template>
