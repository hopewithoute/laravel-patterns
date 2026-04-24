<script>
export default {
    layout: null,
}
</script>

<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft, Code2, Terminal } from 'lucide-vue-next'
import { useTheme } from '@/lib/theme'

const { initTheme } = useTheme()

onMounted(() => {
    initTheme()
})
</script>

<template>
    <Head title="API Documentation - TaskFlow" />

    <div
        class="bg-background gradient-mesh text-foreground selection:bg-primary/25 grain relative flex min-h-screen flex-col overflow-hidden"
    >
        <div class="orb-amber top-[20%] right-[-5%] opacity-15"></div>

        <header class="relative z-10 container mx-auto max-w-5xl px-6 py-10">
            <Link
                href="/"
                class="group text-muted-foreground hover:text-foreground mb-12 inline-flex items-center gap-2 text-sm transition-colors"
            >
                <ArrowLeft class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                Back to Home
            </Link>

            <div class="mb-6 flex items-center gap-4">
                <div
                    class="bg-primary/10 border-primary/20 glow-amber-sm flex h-12 w-12 items-center justify-center rounded-2xl border"
                >
                    <Code2 class="text-primary h-6 w-6" />
                </div>
                <h1 class="font-display text-4xl tracking-tight sm:text-5xl">
                    Developer <span class="text-gradient italic">API</span>
                </h1>
            </div>
            <p class="text-muted-foreground max-w-2xl text-lg font-light">
                Integrate TaskFlow’s tactile engine into your own workflows with our
                high-performance REST API.
            </p>
        </header>

        <main
            class="text-foreground relative z-10 container mx-auto grid max-w-5xl flex-1 gap-8 px-6 py-12 md:grid-cols-3"
        >
            <!-- Sidebar Info -->
            <div class="space-y-6 md:col-span-1">
                <div class="glass border-border/40 rounded-2xl border p-6">
                    <h3 class="font-display mb-3 text-xl">Authentication</h3>
                    <p class="text-muted-foreground text-sm">
                        Bearer token required for all requests. Tokens can be generated in your
                        dashboard settings.
                    </p>
                </div>
                <div class="glass border-border/40 rounded-2xl border p-6">
                    <h3 class="font-display mb-3 text-xl">Rate Limits</h3>
                    <p class="text-muted-foreground text-sm">
                        1,000 requests per minute per workspace. Enterprise plans have higher
                        limits.
                    </p>
                </div>
            </div>

            <!-- Main Code Area -->
            <div class="md:col-span-2">
                <div
                    class="glass-elevated border-border/50 group relative overflow-hidden rounded-3xl border"
                >
                    <div
                        class="bg-surface/50 border-border/40 flex items-center justify-between border-b px-6 py-4"
                    >
                        <div class="flex items-center gap-2">
                            <Terminal class="text-muted-foreground h-4 w-4" />
                            <span
                                class="text-muted-foreground font-mono text-xs tracking-widest uppercase"
                                >GET /v1/tasks</span
                            >
                        </div>
                        <div class="flex gap-1.5">
                            <div class="h-2 w-2 rounded-full bg-rose-500/50"></div>
                            <div class="h-2 w-2 rounded-full bg-amber-500/50"></div>
                            <div class="h-2 w-2 rounded-full bg-emerald-500/50"></div>
                        </div>
                    </div>
                    <div
                        class="overflow-x-auto p-8 font-mono text-sm leading-relaxed whitespace-pre sm:text-base"
                    >
                        <span class="text-primary italic">curl</span> -X GET
                        <span class="text-cyan-400">"https://api.taskflow.com/v1/tasks"</span> \ -H
                        <span class="text-cyan-400">"Authorization: Bearer YOUR_TOKEN"</span>

                        <span class="text-muted-foreground mt-4 block">// Response</span>
                        {
                        <span class="text-amber-300">"data"</span>: [ {
                        <span class="text-amber-300">"id"</span>:
                        <span class="text-cyan-300">"9b1deb4d-3b7d-4bad-9bdd"</span>,
                        <span class="text-amber-300">"title"</span>:
                        <span class="text-cyan-300">"Refactor Landing Layout"</span>,
                        <span class="text-amber-300">"status"</span>:
                        <span class="text-cyan-300">"in_progress"</span> } ] }
                    </div>
                </div>
                <p class="text-muted-foreground mt-6 text-center text-sm">
                    Detailed documentation coming soon. Please reach out for early access.
                </p>
            </div>
        </main>

        <footer
            class="text-muted-foreground container mx-auto px-6 py-10 text-center font-mono text-xs"
        >
            <p>© 2026 TaskFlow Inc. Programmable Tactile Systems.</p>
        </footer>
    </div>
</template>
