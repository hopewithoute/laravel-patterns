<script setup>
import { Link, useForm, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import PaginationNav from '@/components/layout/PaginationNav.vue'
import PageWidth from '@/components/layout/PageWidth.vue'
import Badge from '@/components/ui/Badge.vue'
import { ACTIVE_STATE_TONES } from '@/lib/badges'

defineProps({
    organization: Object,
    members: Object,
})

const inviteForm = useForm({
    email: '',
})

const inviteUser = () => {
    inviteForm.post('/team/invite', {
        onSuccess: () => inviteForm.reset(),
    })
}
</script>

<template>
    <Head title="Team" />
    <AppLayout>
        <PageWidth size="wide" class="space-y-8">
            <PageHeader
                badge="Collaborators"
                title="Team"
                description="Manage your team members and invite new collaborators to your workspace."
                tone="cyan"
                glow-class="top-0 right-1/4 w-80 h-80 bg-cyan-500/[0.04]"
            >
                <template #badge-icon>
                    <svg
                        class="h-3.5 w-3.5 text-cyan-400"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </template>

                <template #actions>
                    <Link
                        href="/team/invite-code"
                        class="border-border/50 text-foreground hover:bg-surface hover:border-border/60 inline-flex items-center gap-2 rounded-xl border bg-transparent px-4 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71a2 2 0 0 1-3.54 3.54l-1.72-1.71a5 5 0 0 0-7.07 7.07l3 3a5 5 0 0 0 7.07.7z"
                            />
                        </svg>
                        Share Invite Link
                    </Link>
                </template>
            </PageHeader>

            <!-- ═══════════════════════════════════════════════════════════════════
                 INVITE FORM
                 ═══════════════════════════════════════════════════════════════════ -->
            <section class="border-border/50 bg-card rounded-2xl border p-6">
                <div class="mb-5 flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-amber-500/20 bg-amber-500/10"
                    >
                        <svg
                            class="h-5 w-5 text-amber-400"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="8.5" cy="7" r="4" />
                            <line x1="20" x2="20" y1="8" y2="14" />
                            <line x1="23" x2="17" y1="11" y2="11" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-foreground font-semibold">Invite New Member</h2>
                        <p class="text-muted-foreground text-sm">
                            Add team members by email address
                        </p>
                    </div>
                </div>

                <form class="flex gap-3" @submit.prevent="inviteUser">
                    <input
                        v-model="inviteForm.email"
                        type="email"
                        placeholder="colleague@example.com"
                        required
                        class="border-border/50 bg-surface/50 text-foreground placeholder:text-muted-foreground focus:ring-primary/20 focus:border-primary/35 h-11 flex-1 rounded-xl border px-4 transition-all duration-200 focus:ring-2 focus:outline-none"
                    />
                    <button
                        type="submit"
                        :disabled="inviteForm.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-2.5 text-sm font-semibold text-black shadow-lg shadow-amber-500/20 transition-all duration-300 hover:shadow-amber-500/30 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                        >
                            <path d="M22 2L11 13" />
                            <path d="M22 2l-7 20-4-9-9-4 20-7z" />
                        </svg>
                        Send Invite
                    </button>
                </form>
                <p v-if="inviteForm.errors.email" class="mt-2 text-xs text-rose-400">
                    {{ inviteForm.errors.email }}
                </p>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════════
                 MEMBERS LIST
                 ═══════════════════════════════════════════════════════════════════ -->
            <section>
                <div class="space-y-2">
                    <div
                        v-for="member in members.data"
                        :key="member.id"
                        class="group bg-card border-border/40 hover:border-border/60 flex items-center gap-4 rounded-xl border p-4 transition-all duration-300"
                    >
                        <!-- Avatar -->
                        <div
                            class="from-surface-elevated to-surface ring-border/30 flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br ring-2"
                        >
                            <span class="text-lg font-bold text-amber-400">{{
                                member.name.charAt(0).toUpperCase()
                            }}</span>
                        </div>

                        <!-- Info -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-foreground font-semibold">{{ member.name }}</span>
                                <span
                                    v-if="member.id === $page.props.auth.user.id"
                                    class="rounded-md bg-amber-500/10 px-2 py-0.5 font-mono text-[10px] tracking-wider text-amber-400 uppercase"
                                >
                                    You
                                </span>
                            </div>
                            <span class="text-muted-foreground font-mono text-sm">{{
                                member.email
                            }}</span>
                        </div>

                        <!-- Status -->
                        <div class="flex items-center gap-3">
                            <Badge
                                :tone="
                                    member.is_active
                                        ? ACTIVE_STATE_TONES.active
                                        : ACTIVE_STATE_TONES.inactive
                                "
                            >
                                <div
                                    :class="[
                                        'h-1.5 w-1.5 rounded-full',
                                        member.is_active ? 'bg-emerald-500' : 'bg-slate-500',
                                    ]"
                                ></div>
                                {{ member.is_active ? 'Active' : 'Inactive' }}
                            </Badge>

                            <!-- Actions -->
                            <Link
                                v-if="member.id !== $page.props.auth.user.id"
                                :href="`/team/${member.id}`"
                                method="delete"
                                as="button"
                                class="text-muted-foreground rounded-lg p-2 opacity-0 transition-colors group-hover:opacity-100 hover:bg-rose-500/10 hover:text-rose-400"
                                onclick="return confirm('Remove this member from the team?')"
                            >
                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M3 6h18" />
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <PaginationNav :resource="members" noun="members" tone="cyan" />
        </PageWidth>
    </AppLayout>
</template>
