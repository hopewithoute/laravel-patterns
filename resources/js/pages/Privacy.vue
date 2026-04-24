<script>
export default {
    layout: null,
}
</script>

<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft, ShieldCheck } from 'lucide-vue-next'
import { useTheme } from '@/lib/theme'

const { initTheme } = useTheme()

onMounted(() => {
    initTheme()
})
</script>

<template>
    <Head title="Privacy Policy - TaskFlow" />

    <div
        class="bg-background gradient-mesh text-foreground selection:bg-primary/25 grain relative flex min-h-screen flex-col overflow-hidden"
    >
        <div class="orb-cyan right-[-10%] bottom-[-15%] opacity-20"></div>

        <header class="relative z-10 container mx-auto max-w-4xl px-6 py-10">
            <Link
                href="/"
                class="group text-muted-foreground hover:text-foreground mb-12 inline-flex items-center gap-2 text-sm transition-colors"
            >
                <ArrowLeft class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                Back to Home
            </Link>

            <div class="mb-6 flex items-center gap-4">
                <div
                    class="glow-cyan-sm flex h-12 w-12 items-center justify-center rounded-2xl border border-cyan-500/20 bg-cyan-500/10"
                >
                    <ShieldCheck class="h-6 w-6 text-cyan-400" />
                </div>
                <h1 class="font-display text-4xl tracking-tight sm:text-5xl">
                    Privacy <span class="text-gradient-cyan italic">Policy</span>
                </h1>
            </div>
        </header>

        <main class="relative z-10 container mx-auto max-w-4xl flex-1 px-6 py-12">
            <div
                class="glass-elevated border-border/50 relative min-h-[400px] overflow-hidden rounded-3xl border p-8 sm:p-12"
            >
                <div
                    class="absolute top-0 right-0 h-64 w-64 translate-x-1/2 -translate-y-1/2 rounded-full bg-cyan-500/5 blur-3xl"
                ></div>

                <div class="prose prose-invert max-w-none space-y-8">
                    <section>
                        <h2 class="font-display text-foreground mb-4 text-2xl">
                            1. Data Collection
                        </h2>
                        <p class="text-muted-foreground leading-relaxed">
                            We collect only the essential information needed to provide our tactile
                            workspace experience. Your data is encrypted and stored using
                            industry-standard protocols.
                        </p>
                    </section>

                    <section>
                        <h2 class="font-display text-foreground mb-4 text-2xl">
                            2. Information Usage
                        </h2>
                        <p class="text-muted-foreground leading-relaxed">
                            TaskFlow uses your data to synchronize your tasks and team
                            collaborations across devices. We never sell your personal information
                            to third parties.
                        </p>
                    </section>

                    <section>
                        <h2 class="font-display text-foreground mb-4 text-2xl">3. Security</h2>
                        <p class="text-muted-foreground leading-relaxed">
                            We implement multi-tenant isolation at the database level to ensure your
                            workspace remains private and secure.
                        </p>
                    </section>

                    <div class="border-border/40 mt-12 border-t pt-12">
                        <p class="text-muted-foreground font-mono text-xs">
                            LAST UPDATED: APRIL 14, 2026
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <footer
            class="text-muted-foreground container mx-auto px-6 py-10 text-center font-mono text-xs"
        >
            <p>© 2026 TaskFlow Inc. Secure Tactile Systems.</p>
        </footer>
    </div>
</template>
