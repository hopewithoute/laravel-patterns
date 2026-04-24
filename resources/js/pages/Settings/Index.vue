<script setup>
import { ref } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'
import PageHeader from '@/components/layout/PageHeader.vue'
import PageWidth from '@/components/layout/PageWidth.vue'

defineProps({
    organization: Object,
})

const activeTab = ref('profile')

// Profile form
const profileForm = useForm({
    name: '',
    email: '',
})

// Password form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const updateProfile = () => {
    profileForm.put('/settings/profile', {
        onSuccess: () => {
            profileForm.reset()
        },
    })
}

const updatePassword = () => {
    passwordForm.put('/settings/password', {
        onSuccess: () => {
            passwordForm.reset()
        },
    })
}

const tabs = [
    {
        id: 'profile',
        label: 'Profile',
        icon: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    },
    {
        id: 'password',
        label: 'Password',
        icon: '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    },
    {
        id: 'workspace',
        label: 'Workspace',
        icon: '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/>',
    },
]
</script>

<template>
    <Head title="Settings" />
    <PageWidth size="wide" class="space-y-8">
        <PageHeader
            badge="Configuration"
            title="Settings"
            description="Manage your account settings and workspace preferences."
            tone="amber"
            glow-class="top-0 right-0 w-80 h-80 bg-cyan-500/[0.04]"
        >
            <template #badge-icon>
                <svg
                    class="h-3.5 w-3.5 text-amber-400"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <circle cx="12" cy="12" r="3" />
                    <path
                        d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m7.08 7.08l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m7.08-7.08l4.24-4.24"
                    />
                </svg>
            </template>
        </PageHeader>

        <!-- ═══════════════════════════════════════════════════════════════════
                 TABS
                 ═══════════════════════════════════════════════════════════════════ -->
        <section>
            <div class="bg-surface/50 border-border/40 flex gap-1 rounded-xl border p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    :class="[
                        'flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200',
                        activeTab === tab.id
                            ? 'bg-card text-foreground border-border/40 border shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                    @click="activeTab = tab.id"
                >
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        v-html="tab.icon"
                    />
                    {{ tab.label }}
                </button>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════════
                 PROFILE TAB
                 ═══════════════════════════════════════════════════════════════════ -->
        <section v-show="activeTab === 'profile'" class="space-y-6">
            <div class="border-border/40 bg-card rounded-2xl border p-6">
                <div class="mb-6 flex items-center gap-3">
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
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-foreground font-semibold">Profile Information</h2>
                        <p class="text-muted-foreground text-sm">
                            Update your name and email address
                        </p>
                    </div>
                </div>

                <form class="space-y-5" @submit.prevent="updateProfile">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="name" class="text-foreground block text-sm font-medium">
                                Name
                            </label>
                            <input
                                id="name"
                                v-model="profileForm.name"
                                type="text"
                                placeholder="Your name"
                                class="border-border/50 focus:ring-primary/20 focus:border-primary/35 bg-surface/60 text-foreground placeholder:text-muted-foreground h-11 w-full rounded-xl border px-4 transition-all duration-200 focus:ring-2 focus:outline-none"
                            />
                            <p v-if="profileForm.errors.name" class="text-xs text-rose-400">
                                {{ profileForm.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label for="email" class="text-foreground block text-sm font-medium">
                                Email
                            </label>
                            <input
                                id="email"
                                v-model="profileForm.email"
                                type="email"
                                placeholder="your@email.com"
                                class="border-border/50 focus:ring-primary/20 focus:border-primary/35 bg-surface/60 text-foreground placeholder:text-muted-foreground h-11 w-full rounded-xl border px-4 transition-all duration-200 focus:ring-2 focus:outline-none"
                            />
                            <p v-if="profileForm.errors.email" class="text-xs text-rose-400">
                                {{ profileForm.errors.email }}
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-2.5 text-sm font-semibold text-black shadow-lg shadow-amber-500/15 transition-all duration-300 hover:shadow-amber-500/25 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════════
                 PASSWORD TAB
                 ═══════════════════════════════════════════════════════════════════ -->
        <section v-show="activeTab === 'password'" class="space-y-6">
            <div class="border-border/40 bg-card rounded-2xl border p-6">
                <div class="mb-6 flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-cyan-500/20 bg-cyan-500/10"
                    >
                        <svg
                            class="h-5 w-5 text-cyan-400"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-foreground font-semibold">Change Password</h2>
                        <p class="text-muted-foreground text-sm">
                            Ensure your account stays secure
                        </p>
                    </div>
                </div>

                <form class="space-y-5" @submit.prevent="updatePassword">
                    <div class="space-y-2">
                        <label
                            for="current_password"
                            class="text-foreground block text-sm font-medium"
                        >
                            Current Password
                        </label>
                        <input
                            id="current_password"
                            v-model="passwordForm.current_password"
                            type="password"
                            class="border-border/50 focus:ring-primary/20 focus:border-primary/35 bg-surface/60 text-foreground placeholder:text-muted-foreground h-11 w-full rounded-xl border px-4 transition-all duration-200 focus:ring-2 focus:outline-none"
                        />
                        <p
                            v-if="passwordForm.errors.current_password"
                            class="text-xs text-rose-400"
                        >
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="password" class="text-foreground block text-sm font-medium">
                                New Password
                            </label>
                            <input
                                id="password"
                                v-model="passwordForm.password"
                                type="password"
                                class="border-border/50 focus:ring-primary/20 focus:border-primary/35 bg-surface/60 text-foreground placeholder:text-muted-foreground h-11 w-full rounded-xl border px-4 transition-all duration-200 focus:ring-2 focus:outline-none"
                            />
                            <p v-if="passwordForm.errors.password" class="text-xs text-rose-400">
                                {{ passwordForm.errors.password }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label
                                for="password_confirmation"
                                class="text-foreground block text-sm font-medium"
                            >
                                Confirm Password
                            </label>
                            <input
                                id="password_confirmation"
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                class="border-border/50 focus:ring-primary/20 focus:border-primary/35 bg-surface/60 text-foreground placeholder:text-muted-foreground h-11 w-full rounded-xl border px-4 transition-all duration-200 focus:ring-2 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-cyan-400 px-5 py-2.5 text-sm font-semibold text-black shadow-lg shadow-cyan-500/15 transition-all duration-300 hover:shadow-cyan-500/25 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════════
                 WORKSPACE TAB
                 ═══════════════════════════════════════════════════════════════════ -->
        <section v-show="activeTab === 'workspace'" class="space-y-6">
            <div class="border-border/40 bg-card rounded-2xl border p-6">
                <div class="mb-6 flex items-center gap-3">
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
                            <path
                                d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-foreground font-semibold">Workspace Information</h2>
                        <p class="text-muted-foreground text-sm">
                            Details about your current workspace
                        </p>
                    </div>
                </div>

                <div v-if="organization" class="space-y-4">
                    <!-- Workspace Card -->
                    <div class="border-border/40 relative overflow-hidden rounded-xl border p-5">
                        <div
                            class="absolute top-0 left-0 h-32 w-32 -translate-x-1/2 -translate-y-1/2 rounded-full bg-gradient-to-br from-amber-500/10 to-transparent"
                        ></div>

                        <div class="relative z-10 flex items-center gap-4">
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 shadow-lg shadow-amber-500/20"
                            >
                                <span class="text-xl font-bold text-black">{{
                                    organization.name.charAt(0).toUpperCase()
                                }}</span>
                            </div>
                            <div>
                                <h3 class="text-foreground text-lg font-semibold">
                                    {{ organization.name }}
                                </h3>
                                <p class="text-muted-foreground font-mono text-sm">
                                    {{ organization.slug }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Info Grid -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="bg-surface/50 border-border/30 rounded-xl border p-4">
                            <p
                                class="text-muted-foreground mb-1 font-mono text-[10px] tracking-wider uppercase"
                            >
                                Invite Code
                            </p>
                            <p class="text-foreground font-mono text-lg font-medium">
                                {{ organization.invite_code }}
                            </p>
                        </div>
                        <div class="bg-surface/50 border-border/30 rounded-xl border p-4">
                            <p
                                class="text-muted-foreground mb-1 font-mono text-[10px] tracking-wider uppercase"
                            >
                                Your Role
                            </p>
                            <p class="text-foreground text-lg font-medium">Admin</p>
                        </div>
                    </div>
                </div>

                <div v-else class="py-12 text-center">
                    <div
                        class="bg-surface-elevated mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
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
                    <p class="text-muted-foreground">No workspace selected</p>
                </div>
            </div>
        </section>
    </PageWidth>
</template>
