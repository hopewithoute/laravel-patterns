<script>
export default {
    layout: null,
}
</script>

<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft, Mail, MessageSquare, MapPin } from 'lucide-vue-next'
import { useTheme } from '@/lib/theme'

const { initTheme } = useTheme()

onMounted(() => {
    initTheme()
})
</script>

<template>
    <Head title="Contact Us - TaskFlow" />

    <div
        class="bg-background gradient-mesh text-foreground selection:bg-primary/25 grain relative flex min-h-screen flex-col overflow-hidden"
    >
        <div class="orb-cyan top-[-10%] right-[-10%] opacity-20"></div>

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
                    class="glow-cyan-sm flex h-12 w-12 items-center justify-center rounded-2xl border border-cyan-500/20 bg-cyan-500/10"
                >
                    <MessageSquare class="h-6 w-6 text-cyan-400" />
                </div>
                <h1 class="font-display text-4xl tracking-tight sm:text-5xl">
                    Get in <span class="text-gradient-cyan italic">Touch</span>
                </h1>
            </div>
            <p class="text-muted-foreground max-w-2xl text-lg font-light">
                Have questions about our tactile workspace? Our team is here to help you build
                better.
            </p>
        </header>

        <main
            class="relative z-10 container mx-auto flex max-w-5xl flex-1 flex-col items-center px-6 py-12"
        >
            <div class="mb-16 grid w-full gap-8 md:grid-cols-3">
                <div
                    class="glass-elevated border-border/50 card-hover rounded-3xl border p-8 text-center"
                >
                    <div
                        class="bg-surface border-border/60 mx-auto mb-6 flex h-12 w-12 items-center justify-center rounded-xl border"
                    >
                        <Mail class="text-primary h-6 w-6" />
                    </div>
                    <h3 class="font-display mb-2 text-xl">Email</h3>
                    <p class="text-muted-foreground mb-4 text-sm">Response within 24 hours.</p>
                    <a
                        href="mailto:hello@taskflow.dev"
                        class="text-primary font-mono hover:underline"
                        >hello@taskflow.dev</a
                    >
                </div>

                <div
                    class="glass-elevated border-border/50 card-hover rounded-3xl border p-8 text-center lg:scale-105"
                >
                    <div
                        class="bg-surface border-border/60 mx-auto mb-6 flex h-12 w-12 items-center justify-center rounded-xl border"
                    >
                        <MessageSquare class="h-6 w-6 text-cyan-400" />
                    </div>
                    <h3 class="font-display mb-2 text-xl">Support Chat</h3>
                    <p class="text-muted-foreground mb-4 text-sm">Available for Enterprise.</p>
                    <button
                        class="bg-foreground text-background hover:bg-primary rounded-full px-6 py-2 text-sm font-semibold transition-colors"
                    >
                        Start Chat
                    </button>
                </div>

                <div
                    class="glass-elevated border-border/50 card-hover rounded-3xl border p-8 text-center"
                >
                    <div
                        class="bg-surface border-border/60 mx-auto mb-6 flex h-12 w-12 items-center justify-center rounded-xl border"
                    >
                        <MapPin class="h-6 w-6 text-emerald-400" />
                    </div>
                    <h3 class="font-display mb-2 text-xl">Studio</h3>
                    <p class="text-muted-foreground mb-4 text-sm">Digital-first operation.</p>
                    <span class="font-mono text-sm text-emerald-400 uppercase">Global Remote</span>
                </div>
            </div>

            <div class="glass border-border/40 w-full max-w-2xl rounded-[2rem] border p-8 sm:p-12">
                <h2 class="font-display text-gradient mb-8 text-center text-3xl italic">
                    Send a message
                </h2>
                <form class="space-y-6" @submit.prevent>
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label
                                class="text-muted-foreground ml-1 font-mono text-xs tracking-wider uppercase"
                                >Name</label
                            >
                            <input
                                type="text"
                                placeholder="John Doe"
                                class="bg-surface-elevated border-border/60 focus:ring-primary/20 w-full rounded-xl border px-4 py-3 text-sm transition-all focus:ring-2 focus:outline-none"
                            />
                        </div>
                        <div class="space-y-2">
                            <label
                                class="text-muted-foreground ml-1 font-mono text-xs tracking-wider uppercase"
                                >Email</label
                            >
                            <input
                                type="email"
                                placeholder="john@example.com"
                                class="bg-surface-elevated border-border/60 focus:ring-primary/20 w-full rounded-xl border px-4 py-3 text-sm transition-all focus:ring-2 focus:outline-none"
                            />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label
                            class="text-muted-foreground ml-1 font-mono text-xs tracking-wider uppercase"
                            >Message</label
                        >
                        <textarea
                            rows="4"
                            placeholder="How can we help?"
                            class="bg-surface-elevated border-border/60 focus:ring-primary/20 w-full resize-none rounded-xl border px-4 py-3 text-sm transition-all focus:ring-2 focus:outline-none"
                        ></textarea>
                    </div>
                    <button
                        class="bg-primary text-primary-foreground shadow-primary/20 w-full rounded-xl py-4 font-bold shadow-lg transition-all hover:scale-[1.01] active:scale-[0.99]"
                    >
                        Send Inquiry
                    </button>
                </form>
            </div>
        </main>

        <footer
            class="text-muted-foreground container mx-auto px-6 py-10 text-center font-mono text-xs"
        >
            <p>© 2026 TaskFlow Inc. Global Tactile Communications.</p>
        </footer>
    </div>
</template>
