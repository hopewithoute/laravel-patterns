<script setup>
import { Link, useForm, Head } from '@inertiajs/vue3'

defineProps({
    organizations: Array,
})

const form = useForm({
    organization_id: '',
})

const selectWorkspace = (id) => {
    form.organization_id = id
    form.post('/workspace/set')
}
</script>

<template>
    <Head title="Select Workspace" />
    <div
        class="bg-background relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-12"
    >
        <!-- Background depth -->
        <div class="pointer-events-none fixed inset-0">
            <div
                class="absolute top-1/4 -right-32 h-96 w-96 rounded-full bg-amber-500/[0.04] blur-3xl"
            ></div>
            <div
                class="absolute bottom-1/4 -left-32 h-80 w-80 rounded-full bg-cyan-500/[0.03] blur-3xl"
            ></div>
        </div>

        <div class="relative z-10 w-full max-w-md">
            <!-- Logo -->
            <div class="mb-8 text-center">
                <Link href="/" class="mb-6 inline-flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 via-amber-500 to-orange-500 shadow-lg shadow-amber-500/20"
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
                    <span class="font-display text-foreground text-xl tracking-tight"
                        >TaskFlow</span
                    >
                </Link>

                <div class="space-y-2">
                    <div class="section-badge section-badge-amber inline-flex items-center gap-2">
                        <div class="h-1.5 w-1.5 rounded-full bg-amber-400"></div>
                        <span class="font-mono text-[11px] tracking-widest text-amber-400 uppercase"
                            >Welcome</span
                        >
                    </div>
                    <h1 class="font-display text-foreground text-3xl">Select workspace</h1>
                    <p class="text-muted-foreground">Choose an organization to work with</p>
                </div>
            </div>

            <!-- Organization List -->
            <div class="space-y-3">
                <button
                    v-for="org in organizations"
                    :key="org.id"
                    class="group border-border/40 bg-card hover:bg-card/80 hover:border-border/60 flex w-full items-center gap-4 rounded-xl border p-4 text-left transition-all duration-200"
                    @click="selectWorkspace(org.id)"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-lg font-bold text-black shadow-md"
                    >
                        {{ org.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-foreground font-medium transition-colors group-hover:text-amber-400"
                        >
                            {{ org.name }}
                        </p>
                        <p class="text-muted-foreground font-mono text-sm">{{ org.slug }}</p>
                    </div>
                    <svg
                        class="text-muted-foreground h-5 w-5 shrink-0 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-amber-400"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M9 18l6-6-6-6" />
                    </svg>
                </button>

                <div v-if="organizations.length === 0" class="py-8 text-center">
                    <div
                        class="bg-surface mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
                    >
                        <svg
                            class="text-muted-foreground h-7 w-7"
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
                    <p class="text-muted-foreground mb-4 text-sm">
                        You don't belong to any organization yet.
                    </p>
                </div>
            </div>

            <!-- Create New -->
            <div class="mt-8 text-center">
                <Link
                    href="/workspace/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-3 text-sm font-semibold text-black shadow-lg shadow-amber-500/15 transition-all duration-300 hover:shadow-amber-500/25"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2.5"
                    >
                        <line x1="12" x2="12" y1="5" y2="19" />
                        <line x1="5" x2="19" y1="12" y2="12" />
                    </svg>
                    Create New Workspace
                </Link>
            </div>
        </div>
    </div>
</template>
