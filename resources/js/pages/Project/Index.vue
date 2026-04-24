<script setup>
import { Link, Head } from '@inertiajs/vue3'
import PageHeader from '@/components/layout/PageHeader.vue'
import PaginationNav from '@/components/layout/PaginationNav.vue'
import PageWidth from '@/components/layout/PageWidth.vue'
import Badge from '@/components/ui/Badge.vue'
import { ACTIVE_STATE_TONES } from '@/lib/badges'

defineProps({
    projects: Object,
})
</script>

<template>
    <Head title="Projects" />
    <PageWidth size="wide" class="space-y-8">
        <PageHeader
            badge="Workspace"
            title="Projects"
            description="Organize your work into focused workspaces. Track progress and collaborate with your team."
            tone="amber"
            glow-class="top-0 right-1/3 w-80 h-80 bg-amber-500/4"
        >
            <template #badge-icon>
                <svg
                    class="h-3.5 w-3.5 text-amber-400"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                >
                    <path
                        d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"
                    />
                </svg>
            </template>

            <template #actions>
                <Link
                    href="/projects/create"
                    class="group relative inline-flex items-center gap-2 overflow-hidden rounded-xl bg-linear-to-r from-amber-500 to-orange-500 px-5 py-3 text-sm font-semibold text-black shadow-lg shadow-amber-500/15 transition-all duration-300 hover:shadow-amber-500/25"
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
                    <span class="relative">New Project</span>
                </Link>
            </template>
        </PageHeader>

        <!-- ═══════════════════════════════════════════════════════════════════
                 PROJECTS GRID
                 ═══════════════════════════════════════════════════════════════════ -->
        <section>
            <div v-if="projects.data.length > 0" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="(project, index) in projects.data"
                    :key="project.id"
                    :href="`/projects/${project.id}`"
                    class="group border-border/40 bg-card hover:border-border/70 animate-fade-in-up relative overflow-hidden rounded-2xl border transition-all duration-300"
                    :class="[`stagger-${Math.min(index + 1, 8)}`]"
                >
                    <!-- Color accent gradient -->
                    <div
                        class="absolute top-0 right-0 left-0 h-28 opacity-60 transition-opacity duration-500 group-hover:opacity-100"
                        :style="{
                            background: `linear-gradient(180deg, ${project.color || '#f59e0b'}12 0%, transparent 100%)`,
                        }"
                    ></div>

                    <!-- Shimmer effect on hover -->
                    <div
                        class="shimmer absolute inset-0 opacity-0 transition-opacity duration-500 group-hover:opacity-100"
                    ></div>

                    <div class="relative z-10 p-5">
                        <!-- Header -->
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-xl shadow-md ring-1 ring-white/10"
                                    :style="{ backgroundColor: project.color || '#f59e0b' }"
                                >
                                    <span class="text-base font-bold text-black">{{
                                        project.name?.charAt(0)?.toUpperCase()
                                    }}</span>
                                </div>
                                <div>
                                    <h3
                                        class="text-foreground font-semibold transition-colors group-hover:text-amber-400"
                                    >
                                        {{ project.name }}
                                    </h3>
                                    <p
                                        class="text-muted-foreground font-mono text-[10px] tracking-wider uppercase"
                                    >
                                        {{ project.total_tasks || 0 }} tasks
                                    </p>
                                </div>
                            </div>

                            <!-- Status indicator -->
                            <div
                                :class="[
                                    'mt-1 h-2 w-2 rounded-full',
                                    project.is_active ? 'bg-emerald-500' : 'bg-slate-600',
                                ]"
                                :style="
                                    project.is_active
                                        ? 'box-shadow: 0 0 8px hsl(var(--emerald) / 0.5)'
                                        : ''
                                "
                            ></div>
                        </div>

                        <!-- Description -->
                        <p class="text-muted-foreground mb-4 line-clamp-2 min-h-10 text-sm">
                            {{ project.description || 'No description provided' }}
                        </p>

                        <!-- Progress -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-muted-foreground">Progress</span>
                                <span class="text-foreground font-mono"
                                    >{{
                                        project.total_tasks > 0
                                            ? Math.round(
                                                  (project.completed_tasks / project.total_tasks) *
                                                      100,
                                              )
                                            : 0
                                    }}%</span
                                >
                            </div>
                            <div class="progress-bar">
                                <div
                                    class="progress-bar-fill"
                                    :style="{
                                        width: `${project.total_tasks > 0 ? Math.round((project.completed_tasks / project.total_tasks) * 100) : 0}%`,
                                        backgroundColor: project.color || '#f59e0b',
                                    }"
                                ></div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="border-border/30 mt-4 flex items-center justify-between border-t pt-4"
                        >
                            <div class="flex items-center gap-1.5">
                                <Badge
                                    :tone="
                                        project.is_active
                                            ? ACTIVE_STATE_TONES.active
                                            : ACTIVE_STATE_TONES.inactive
                                    "
                                >
                                    {{ project.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </div>
                            <div
                                class="text-muted-foreground flex items-center gap-1 transition-colors group-hover:text-amber-400"
                            >
                                <span class="text-xs">View</span>
                                <svg
                                    class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M9 18l6-6-6-6" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="empty-state">
                <div
                    class="absolute inset-0 bg-linear-to-br from-amber-500/4 via-transparent to-cyan-500/3"
                ></div>
                <div class="relative z-10">
                    <div class="empty-state-icon border border-amber-500/20 bg-amber-500/10">
                        <svg
                            class="h-8 w-8 text-amber-400"
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
                    <h3 class="font-display text-foreground mb-2 text-xl">No projects yet</h3>
                    <p class="text-muted-foreground mx-auto mb-6 max-w-sm text-sm">
                        Create your first project to start organizing your tasks and tracking
                        progress.
                    </p>
                    <Link
                        href="/projects/create"
                        class="inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-amber-500 to-orange-500 px-5 py-3 text-sm font-semibold text-black shadow-lg shadow-amber-500/15 transition-all duration-300 hover:shadow-amber-500/25"
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
                        Create Project
                    </Link>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════════
                 PAGINATION
                 ═══════════════════════════════════════════════════════════════════ -->
        <PaginationNav :resource="projects" noun="projects" tone="amber" />
    </PageWidth>
</template>
