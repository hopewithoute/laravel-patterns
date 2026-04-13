<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
})
</script>

<template>
    <div class="bg-background relative flex min-h-screen flex-col overflow-hidden">
        <!-- ═══════════════════════════════════════════════════════════════
             BACKGROUND — Layered depth with orbital gradients
             ═══════════════════════════════════════════════════════════════ -->
        <div class="pointer-events-none fixed inset-0">
            <!-- Primary warm orb — top right -->
            <div
                class="animate-float absolute -top-32 -right-32 h-[700px] w-[700px] rounded-full bg-amber-500/[0.05] blur-[120px]"
            ></div>
            <!-- Secondary cool orb — bottom left -->
            <div
                class="absolute -bottom-40 -left-40 h-[600px] w-[600px] rounded-full bg-cyan-500/[0.035] blur-[100px]"
                style="animation: float 5s ease-in-out 1s infinite"
            ></div>
            <!-- Center ambient fill -->
            <div
                class="bg-gradient-radial absolute top-1/2 left-1/2 h-[900px] w-[900px] -translate-x-1/2 -translate-y-1/2 rounded-full from-amber-500/[0.02] to-transparent blur-3xl"
            ></div>
            <!-- Grain texture -->
            <div class="grain absolute inset-0 opacity-[0.02]"></div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
             NAVIGATION — Minimal top bar
             ═══════════════════════════════════════════════════════════════ -->
        <nav class="relative z-20 flex items-center justify-between px-6 py-5 sm:px-10 lg:px-16">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 via-amber-500 to-orange-500 shadow-lg shadow-amber-500/20"
                    >
                        <svg
                            class="h-5 w-5 text-black"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                        >
                            <path
                                d="M12 2L2 7l10 5 10-5-10-5z"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M2 17l10 5 10-5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M2 12l10 5 10-5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                </div>
                <span class="font-display text-foreground text-lg tracking-tight">TaskFlow</span>
            </div>

            <!-- Auth Links -->
            <div class="flex items-center gap-3">
                <Link
                    v-if="canLogin"
                    href="/login"
                    class="text-foreground/70 hover:text-foreground inline-flex items-center rounded-xl px-4 py-2 text-sm font-medium transition-colors duration-200"
                >
                    Sign in
                </Link>
                <Link
                    v-if="canRegister"
                    href="/register"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-2.5 text-sm font-semibold text-black shadow-lg shadow-amber-500/15 transition-all duration-300 hover:shadow-amber-500/25"
                >
                    Get Started
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                    >
                        <path d="M5 12h14M12 5l7 7-7 7" />
                    </svg>
                </Link>
            </div>
        </nav>

        <!-- ═══════════════════════════════════════════════════════════════
             HERO — Editorial impact
             ═══════════════════════════════════════════════════════════════ -->
        <main
            class="relative z-10 flex flex-1 flex-col items-center justify-center px-6 py-16 sm:px-10 sm:py-24 lg:px-16"
        >
            <div class="w-full max-w-5xl space-y-10 text-center">
                <!-- Badge -->
                <div
                    class="animate-fade-in-up stagger-1 section-badge section-badge-amber inline-flex items-center gap-2"
                >
                    <div class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-400"></div>
                    <span class="font-mono text-[11px] tracking-widest text-amber-400 uppercase"
                        >Workspace, Reimagined</span
                    >
                </div>

                <!-- Headline — Serif + Sans interplay -->
                <div class="animate-fade-in-up stagger-2 space-y-4">
                    <h1
                        class="font-display text-6xl leading-[0.95] tracking-tight sm:text-7xl lg:text-8xl"
                    >
                        <span class="text-foreground">Build </span>
                        <em class="text-gradient">beautiful</em>
                    </h1>
                    <h1
                        class="font-display text-6xl leading-[0.95] tracking-tight sm:text-7xl lg:text-8xl"
                    >
                        <span class="text-foreground">things, </span>
                        <em class="text-gradient-cyan">together</em>
                    </h1>
                </div>

                <!-- Subheadline -->
                <p
                    class="animate-fade-in-up stagger-3 text-muted-foreground mx-auto max-w-2xl text-lg leading-relaxed font-light sm:text-xl"
                >
                    A refined workspace for teams who care about craft. Organize projects, track
                    progress, and ship with confidence.
                </p>

                <!-- CTA Buttons -->
                <div
                    class="animate-fade-in-up stagger-4 flex flex-col items-center justify-center gap-4 sm:flex-row"
                >
                    <Link
                        v-if="canRegister"
                        href="/register"
                        class="group relative inline-flex items-center gap-2.5 overflow-hidden rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-8 py-4 text-lg font-semibold text-black shadow-xl shadow-amber-500/15 transition-all duration-300 hover:shadow-amber-500/25"
                    >
                        <div
                            class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-700 group-hover:translate-x-full"
                        ></div>
                        <span class="relative">Start for Free</span>
                        <svg
                            class="relative h-5 w-5 transition-transform group-hover:translate-x-0.5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                        >
                            <path d="M5 12h14M12 5l7 7-7 7" />
                        </svg>
                    </Link>
                    <Link
                        href="#features"
                        class="border-border/40 bg-surface/30 text-foreground hover:bg-surface/50 hover:border-border/60 inline-flex items-center gap-2 rounded-xl border px-8 py-4 text-lg font-medium transition-all duration-200"
                    >
                        Learn More
                        <svg
                            class="text-muted-foreground h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M6 9l6 6 6-6" />
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Scroll indicator -->
            <div class="animate-fade-in-up stagger-5 mt-20">
                <div
                    class="border-border/40 mx-auto flex h-10 w-6 items-start justify-center rounded-full border-2 p-1.5"
                >
                    <div class="bg-muted-foreground/50 h-2 w-1 animate-bounce rounded-full"></div>
                </div>
            </div>
        </main>

        <!-- ═══════════════════════════════════════════════════════════════
             FEATURES — Three pillars
             ═══════════════════════════════════════════════════════════════ -->
        <section id="features" class="relative z-10 px-6 py-24 sm:px-10 sm:py-32 lg:px-16">
            <div class="mx-auto max-w-6xl">
                <!-- Section header -->
                <div class="mb-16 space-y-4 text-center">
                    <div class="section-badge section-badge-cyan inline-flex items-center gap-2">
                        <div class="h-1.5 w-1.5 rounded-full bg-cyan-400"></div>
                        <span class="font-mono text-[11px] tracking-widest text-cyan-400 uppercase"
                            >Core Features</span
                        >
                    </div>
                    <h2 class="font-display text-foreground text-4xl sm:text-5xl">
                        Everything you need, <em>nothing you don't</em>
                    </h2>
                </div>

                <!-- Feature cards -->
                <div class="grid gap-6 sm:grid-cols-3">
                    <!-- Projects -->
                    <div
                        class="group border-border/40 bg-card/60 hover:bg-card/80 hover:border-border/60 relative rounded-2xl border p-6 transition-all duration-300"
                    >
                        <div
                            class="absolute top-0 right-0 h-40 w-40 rounded-full bg-amber-500/[0.04] opacity-0 blur-3xl transition-opacity duration-500 group-hover:opacity-100"
                        ></div>
                        <div class="relative z-10 space-y-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl border border-amber-500/20 bg-amber-500/10"
                            >
                                <svg
                                    class="h-6 w-6 text-amber-400"
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
                            <h3 class="font-display text-foreground text-xl">Projects</h3>
                            <p class="text-muted-foreground text-sm leading-relaxed">
                                Structure work into focused workspaces. Track progress visually with
                                beautiful, data-rich project cards.
                            </p>
                        </div>
                    </div>

                    <!-- Tasks -->
                    <div
                        class="group border-border/40 bg-card/60 hover:bg-card/80 hover:border-border/60 relative rounded-2xl border p-6 transition-all duration-300"
                    >
                        <div
                            class="absolute top-0 right-0 h-40 w-40 rounded-full bg-cyan-500/[0.04] opacity-0 blur-3xl transition-opacity duration-500 group-hover:opacity-100"
                        ></div>
                        <div class="relative z-10 space-y-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl border border-cyan-500/20 bg-cyan-500/10"
                            >
                                <svg
                                    class="h-6 w-6 text-cyan-400"
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
                            <h3 class="font-display text-foreground text-xl">Tasks</h3>
                            <p class="text-muted-foreground text-sm leading-relaxed">
                                Powerful workflows with statuses, priorities, and due dates. Never
                                lose track of what matters.
                            </p>
                        </div>
                    </div>

                    <!-- Teams -->
                    <div
                        class="group border-border/40 bg-card/60 hover:bg-card/80 hover:border-border/60 relative rounded-2xl border p-6 transition-all duration-300"
                    >
                        <div
                            class="absolute top-0 right-0 h-40 w-40 rounded-full bg-emerald-500/[0.04] opacity-0 blur-3xl transition-opacity duration-500 group-hover:opacity-100"
                        ></div>
                        <div class="relative z-10 space-y-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl border border-emerald-500/20 bg-emerald-500/10"
                            >
                                <svg
                                    class="h-6 w-6 text-emerald-400"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                >
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </div>
                            <h3 class="font-display text-foreground text-xl">Teams</h3>
                            <p class="text-muted-foreground text-sm leading-relaxed">
                                Invite collaborators, manage roles, and build a culture of shipping
                                together.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════
             STATS STRIP — Social proof
             ═══════════════════════════════════════════════════════════════ -->
        <section
            class="border-border/30 bg-card/30 relative z-10 border-y px-6 py-16 sm:px-10 lg:px-16"
        >
            <div class="mx-auto grid max-w-5xl grid-cols-2 gap-8 text-center lg:grid-cols-4">
                <div>
                    <div class="font-display text-foreground text-3xl sm:text-4xl">
                        10k<span class="text-gradient">+</span>
                    </div>
                    <div
                        class="text-muted-foreground mt-1 font-mono text-xs tracking-widest uppercase"
                    >
                        Teams
                    </div>
                </div>
                <div>
                    <div class="font-display text-foreground text-3xl sm:text-4xl">
                        50k<span class="text-gradient-cyan">+</span>
                    </div>
                    <div
                        class="text-muted-foreground mt-1 font-mono text-xs tracking-widest uppercase"
                    >
                        Projects
                    </div>
                </div>
                <div>
                    <div class="font-display text-foreground text-3xl sm:text-4xl">
                        1M<span class="text-gradient">+</span>
                    </div>
                    <div
                        class="text-muted-foreground mt-1 font-mono text-xs tracking-widest uppercase"
                    >
                        Tasks Shipped
                    </div>
                </div>
                <div>
                    <div class="font-display text-foreground text-3xl sm:text-4xl">
                        99.9<span class="text-gradient-cyan">%</span>
                    </div>
                    <div
                        class="text-muted-foreground mt-1 font-mono text-xs tracking-widest uppercase"
                    >
                        Uptime
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════
             FINAL CTA
             ═══════════════════════════════════════════════════════════════ -->
        <section class="relative z-10 px-6 py-24 sm:px-10 sm:py-32 lg:px-16">
            <div class="mx-auto max-w-3xl space-y-8 text-center">
                <h2 class="font-display text-foreground text-4xl sm:text-5xl">
                    Ready to <em class="text-gradient">ship</em> faster?
                </h2>
                <p class="text-muted-foreground mx-auto max-w-xl text-lg">
                    Join thousands of teams already building better products with TaskFlow.
                </p>
                <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <Link
                        v-if="canRegister"
                        href="/register"
                        class="group relative inline-flex items-center gap-2.5 overflow-hidden rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-8 py-4 text-lg font-semibold text-black shadow-xl shadow-amber-500/15 transition-all duration-300 hover:shadow-amber-500/25"
                    >
                        <div
                            class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-700 group-hover:translate-x-full"
                        ></div>
                        <span class="relative">Create Free Account</span>
                    </Link>
                    <Link
                        v-if="canLogin"
                        href="/login"
                        class="border-border/40 text-foreground hover:bg-surface/50 inline-flex items-center gap-2 rounded-xl border px-8 py-4 text-lg font-medium transition-all duration-200"
                    >
                        Sign in instead
                    </Link>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════
             FOOTER
             ═══════════════════════════════════════════════════════════════ -->
        <footer class="border-border/30 relative z-10 border-t px-6 py-8 sm:px-10 lg:px-16">
            <div
                class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 sm:flex-row"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-amber-400 to-orange-500"
                    >
                        <svg
                            class="h-3.5 w-3.5 text-black"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                        >
                            <path
                                d="M12 2L2 7l10 5 10-5-10-5z"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                    <span class="text-muted-foreground text-sm">TaskFlow</span>
                </div>
                <p class="text-muted-foreground font-mono text-xs">
                    Laravel v{{ laravelVersion }} · PHP v{{ phpVersion }}
                </p>
            </div>
        </footer>
    </div>
</template>
