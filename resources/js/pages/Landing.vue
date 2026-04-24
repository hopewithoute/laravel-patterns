<script>
export default {
    layout: null,
}
</script>

<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowRight, Activity, CheckCircle2, Shield } from 'lucide-vue-next'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'
import AppLogo from '@/components/ui/AppLogo.vue'
import { useTheme } from '@/lib/theme'

const { initTheme } = useTheme()

onMounted(() => {
    initTheme()
})
</script>

<template>
    <Head title="Welcome to TaskFlow" />

    <div
        class="bg-background gradient-mesh text-foreground selection:bg-primary/25 grain relative min-h-screen overflow-hidden"
    >
        <!-- Ambient Background Orbs -->
        <div class="orb-amber top-[-15%] left-[-10%] opacity-40"></div>
        <div class="orb-cyan right-[-10%] bottom-[-15%] opacity-30"></div>

        <main class="relative z-10 container mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-16 lg:px-8">
            <!-- Top Navigation -->
            <nav
                v-motion
                class="mb-12 flex items-center justify-between sm:mb-16"
                :initial="{ opacity: 0, y: -20 }"
                :enter="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 600, type: 'spring', bounce: 0.2 },
                }"
            >
                <div class="flex items-center gap-3">
                    <AppLogo
                        class="text-foreground h-10 w-10 drop-shadow-md transition-transform duration-300 hover:scale-105"
                    />
                    <span class="font-display text-2xl font-semibold tracking-tight"
                        >TaskFlow<span class="text-primary italic">.</span></span
                    >
                </div>
                <div class="flex items-center gap-3 sm:gap-6">
                    <ThemeToggle />
                    <Link
                        v-if="$page.props.auth?.user"
                        href="/dashboard"
                        class="hover:text-primary text-sm font-medium transition-colors"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            href="/login"
                            class="text-muted-foreground hover:text-foreground hidden text-sm font-medium transition-colors delay-100 ease-out sm:block"
                        >
                            Log in
                        </Link>
                        <Link
                            href="/register"
                            class="bg-foreground text-background hover:bg-primary hover:text-primary-foreground rounded-full px-5 py-2.5 text-sm font-medium shadow-[0_0_20px_-5px_hsl(var(--foreground)/0.3)] transition-all duration-300 hover:scale-105"
                        >
                            Get Started
                        </Link>
                    </template>
                </div>
            </nav>

            <!-- Bento Grid Container -->
            <div
                class="grid auto-rows-[minmax(200px,auto)] grid-cols-1 gap-5 sm:gap-6 md:grid-cols-12"
            >
                <!-- Hero Cell (Spans 8 cols on desktop) -->
                <div
                    v-motion
                    class="glass-elevated gradient-border card-hover relative overflow-hidden rounded-4xl p-8 sm:p-12 md:col-span-8 md:row-span-2 lg:p-14"
                    :initial="{ opacity: 0, x: -30 }"
                    :enter="{
                        opacity: 1,
                        x: 0,
                        transition: { duration: 800, delay: 100, type: 'spring' },
                    }"
                >
                    <!-- Decorative Right Gradient -->
                    <div
                        class="from-primary/5 via-primary/5 pointer-events-none absolute top-0 right-0 h-full w-1/2 bg-linear-to-l to-transparent"
                    ></div>

                    <div class="section-badge section-badge-amber mb-8 w-fit">
                        <span
                            class="text-xs font-semibold tracking-widest text-amber-600 uppercase dark:text-amber-500"
                            >Tactile Productivity</span
                        >
                    </div>

                    <h1
                        class="font-display mb-6 text-5xl leading-[1.05] tracking-tight sm:text-6xl lg:text-7xl"
                    >
                        Master Your Work.<br />
                        <span class="text-gradient font-display pr-2 italic"
                            >Without the Noise.</span
                        >
                    </h1>

                    <p
                        class="text-muted-foreground mb-12 max-w-xl font-sans text-lg leading-relaxed font-light sm:text-xl md:text-2xl"
                    >
                        An artisanal approach to project management. Designed with deep Obsidian
                        aesthetics and premium tactile feedback to keep you in flow.
                    </p>

                    <div class="flex flex-wrap items-center gap-4">
                        <Link
                            href="/register"
                            class="bg-primary text-primary-foreground hover:bg-primary/90 glow-amber group inline-flex items-center justify-center rounded-full px-8 py-3.5 font-medium transition-all"
                        >
                            Start Building
                            <ArrowRight
                                class="ml-2 h-5 w-5 transition-transform group-hover:translate-x-1.5"
                            />
                        </Link>
                        <Link
                            href="/login"
                            class="border-border bg-card/50 hover:bg-surface inline-flex items-center justify-center rounded-full border px-8 py-3.5 font-medium backdrop-blur-md transition-all"
                        >
                            See Workspace
                        </Link>
                    </div>
                </div>

                <!-- Metric Cell 1 (Spans 4 cols) -->
                <div
                    v-motion
                    class="glass gradient-border card-hover group relative flex flex-col justify-between overflow-hidden rounded-4xl p-6 sm:p-8 md:col-span-4"
                    :initial="{ opacity: 0, y: 30 }"
                    :enter="{
                        opacity: 1,
                        y: 0,
                        transition: { duration: 800, delay: 200, type: 'spring' },
                    }"
                >
                    <div
                        class="absolute top-0 right-0 h-32 w-32 translate-x-1/2 -translate-y-1/2 rounded-full bg-cyan-500/10 opacity-50 blur-2xl transition-opacity group-hover:opacity-100"
                    ></div>

                    <div class="relative z-10 flex items-center justify-between">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl border border-cyan-500/20 bg-cyan-500/10 shadow-inner"
                        >
                            <Activity class="h-6 w-6 text-cyan-600 dark:text-cyan-400" />
                        </div>
                        <span
                            class="rounded-lg border border-cyan-500/20 bg-cyan-500/10 px-3 py-1.5 font-mono text-xs font-semibold text-cyan-700 shadow-sm dark:text-cyan-400"
                        >
                            +24% Velocity
                        </span>
                    </div>

                    <div class="relative z-10 mt-8">
                        <div class="font-display text-foreground mb-2 text-5xl sm:text-6xl">
                            1,204
                        </div>
                        <div class="text-muted-foreground text-sm font-medium sm:text-base">
                            Tasks Completed This Sprint
                        </div>
                    </div>

                    <div
                        class="bg-surface border-border/50 relative z-10 mt-6 h-2 w-full overflow-hidden rounded-full border"
                    >
                        <div
                            class="shimmer h-full w-[78%] rounded-full bg-linear-to-r from-cyan-600 via-cyan-400 to-cyan-300"
                        ></div>
                    </div>
                </div>

                <!-- Feature / Abstract UI Cell (Spans 4 cols) -->
                <div
                    v-motion
                    class="glass-overlay gradient-border card-hover group relative overflow-hidden rounded-4xl p-6 sm:p-8 md:col-span-4"
                    :initial="{ opacity: 0, y: 30 }"
                    :enter="{
                        opacity: 1,
                        y: 0,
                        transition: { duration: 800, delay: 300, type: 'spring' },
                    }"
                >
                    <h3
                        class="font-display text-foreground relative z-10 mb-2 text-2xl sm:text-3xl"
                    >
                        Fluid Kanban
                    </h3>
                    <p
                        class="text-muted-foreground relative z-10 mb-8 max-w-50 text-sm sm:text-base"
                    >
                        Drag and drop with atomic state commitment.
                    </p>

                    <!-- Abstract Kanban UI -->
                    <div class="relative z-10 mt-auto flex gap-3">
                        <!-- Column 1 -->
                        <div class="flex flex-1 flex-col gap-3">
                            <div class="bg-border/80 h-1.5 w-8 rounded-full"></div>
                            <div
                                class="bg-surface border-border/60 h-16 w-full rounded-xl border shadow-sm transition-transform delay-75 duration-500 group-hover:-translate-y-2"
                            ></div>
                            <div
                                class="bg-surface border-border/60 relative h-24 w-full overflow-hidden rounded-xl border shadow-sm transition-transform delay-150 duration-500 group-hover:-translate-y-1"
                            >
                                <div
                                    class="bg-primary/20 absolute top-2 left-2 h-4 w-4 rounded-full"
                                ></div>
                                <div
                                    class="bg-border absolute right-2 bottom-2 h-1.5 w-1/2 rounded-full"
                                ></div>
                            </div>
                        </div>
                        <!-- Column 2 -->
                        <div class="flex flex-1 flex-col gap-3">
                            <div class="bg-border/80 h-1.5 w-8 rounded-full"></div>
                            <div
                                class="bg-surface border-border/60 relative h-20 w-full overflow-hidden rounded-xl border shadow-sm"
                            >
                                <div class="absolute top-2 left-2 flex w-full gap-1 px-2">
                                    <div class="h-3 w-3 rounded-full bg-cyan-500/50"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ambient bottom glow -->
                    <div
                        class="bg-primary/10 group-hover:bg-primary/20 absolute -right-16 -bottom-16 h-56 w-56 rounded-full blur-[3rem] transition-colors duration-1000"
                    ></div>
                </div>

                <!-- Deep Integration Cell (Spans 8 cols) -->
                <div
                    v-motion
                    class="glass gradient-border card-hover relative flex flex-col items-start justify-between overflow-hidden rounded-4xl p-6 sm:flex-row sm:items-center sm:p-10 md:col-span-8"
                    :initial="{ opacity: 0, x: 30 }"
                    :enter="{
                        opacity: 1,
                        x: 0,
                        transition: { duration: 800, delay: 400, type: 'spring' },
                    }"
                >
                    <div
                        class="absolute top-0 left-0 h-px w-full bg-linear-to-r from-transparent via-emerald-500/20 to-transparent"
                    ></div>

                    <div class="relative z-10 mb-8 max-w-lg sm:mb-0">
                        <div class="section-badge section-badge-emerald mb-5">
                            <Shield class="h-4 w-4 text-emerald-600 dark:text-emerald-500" />
                            <span
                                class="text-xs font-semibold tracking-widest text-emerald-600 uppercase dark:text-emerald-500"
                                >Enterprise Ready</span
                            >
                        </div>
                        <h3 class="font-display text-foreground mb-4 text-3xl sm:text-4xl">
                            Secure by design.
                        </h3>
                        <p class="text-muted-foreground text-base leading-relaxed sm:text-lg">
                            Built on a robust Laravel backend with UUID schema, polymorphic
                            relations, and strict multi-tenant data isolation. Every request is
                            verified.
                        </p>
                    </div>

                    <!-- Decorative elements right side -->
                    <div class="relative z-10 flex w-full flex-col space-y-3 sm:w-auto">
                        <div
                            v-motion
                            class="bg-surface-elevated border-border/80 flex transform items-center justify-between gap-4 rounded-2xl border py-3 pr-4 pl-5 shadow-md transition-transform hover:scale-[1.02] sm:justify-start"
                            :initial="{ x: 20, opacity: 0 }"
                            :enter="{
                                x: 0,
                                opacity: 1,
                                transition: { delay: 600, type: 'spring' },
                            }"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-2.5 w-2.5 animate-pulse rounded-full bg-emerald-500"
                                ></div>
                                <span
                                    class="font-mono text-xs font-medium text-emerald-700 sm:text-sm dark:text-emerald-100"
                                    >API_LATENCY</span
                                >
                            </div>
                            <div
                                class="flex items-center gap-2 rounded bg-emerald-500/10 px-2 py-1"
                            >
                                <span
                                    class="font-mono text-xs text-emerald-600 dark:text-emerald-400"
                                    >12ms</span
                                >
                                <CheckCircle2
                                    class="h-4 w-4 text-emerald-600 dark:text-emerald-500"
                                />
                            </div>
                        </div>

                        <div
                            v-motion
                            class="bg-surface-elevated border-border/80 flex transform items-center justify-between gap-4 rounded-2xl border py-3 pr-4 pl-5 shadow-md transition-transform hover:scale-[1.02] sm:justify-start"
                            :initial="{ x: 20, opacity: 0 }"
                            :enter="{
                                x: 0,
                                opacity: 1,
                                transition: { delay: 700, type: 'spring' },
                            }"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-2.5 w-2.5 animate-pulse rounded-full bg-cyan-500"
                                ></div>
                                <span
                                    class="font-mono text-xs font-medium text-cyan-700 sm:text-sm dark:text-cyan-100"
                                    >CACHE_LAYER</span
                                >
                            </div>
                            <div class="flex items-center gap-2 rounded bg-cyan-500/10 px-2 py-1">
                                <span class="font-mono text-xs text-cyan-600 dark:text-cyan-400"
                                    >SYNCED</span
                                >
                                <CheckCircle2 class="h-4 w-4 text-cyan-600 dark:text-cyan-500" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Details -->
            <footer
                v-motion
                class="border-border text-muted-foreground mt-20 flex flex-col items-center justify-between gap-6 border-t pt-8 font-mono text-sm sm:flex-row"
                :initial="{ opacity: 0 }"
                :enter="{ opacity: 1, transition: { delay: 800, duration: 1000 } }"
            >
                <p>
                    © 2026 TaskFlow Inc.
                    <span class="hidden sm:inline">Crafted with Lunar Obsidian.</span>
                </p>
                <div class="flex gap-6">
                    <Link href="/terms" class="hover:text-foreground transition-colors">Terms</Link>
                    <Link href="/privacy" class="hover:text-foreground transition-colors"
                        >Privacy</Link
                    >
                    <Link href="/api" class="hover:text-foreground transition-colors">API</Link>
                    <Link href="/contact" class="transition-colors hover:text-amber-500"
                        >Contact</Link
                    >
                </div>
            </footer>
        </main>
    </div>
</template>

<style scoped>
/* Scoped overrides if necessary */
</style>
