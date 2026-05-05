<script setup>
import { ref } from 'vue'
import { useForm, Head, router } from '@inertiajs/vue3'
import PageHeader from '@/components/layout/PageHeader.vue'
import PageWidth from '@/components/layout/PageWidth.vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogClose } from '@/components/ui/dialog'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const props = defineProps({
    organization: Object,
    tokens: Array,
    newToken: {
        type: String,
        default: null,
    },
})

const activeTab = ref('profile')

// Show modal if newToken prop exists
const showTokenModal = ref(!!props.newToken)

// Revoke confirm dialog
const showRevokeDialog = ref(false)
const tokenToRevoke = ref(null)

const confirmRevoke = (id) => {
    tokenToRevoke.value = id
    showRevokeDialog.value = true
}

// Revoke form
const revokeForm = useForm({
    _method: 'DELETE',
})

const revokeToken = () => {
    if (tokenToRevoke.value) {
        revokeForm.post(`/settings/tokens/${tokenToRevoke.value}`, {
            onFinish: () => {
                showRevokeDialog.value = false
                tokenToRevoke.value = null
                revokeForm.reset()
            },
        })
    }
}

// Token form
const tokenForm = useForm({
    name: '',
    abilities: ['*'],
    expires_at: '',
})

const createToken = () => {
    tokenForm.post('/settings/tokens', {
        preserveState: true,
        onSuccess: () => {
            showTokenModal.value = true
            tokenForm.reset()
        },
    })
}

const closeTokenModal = () => {
    showTokenModal.value = false
}

const copied = ref(false)

const copyToken = () => {
    if (props.newToken) {
        navigator.clipboard.writeText(props.newToken)
        copied.value = true
        setTimeout(() => {
            copied.value = false
        }, 2000)
    }
}

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
    {
        id: 'tokens',
        label: 'API Tokens',
        icon: '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
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

        <!-- ═══════════════════════════════════════════════════════════════════
                 API TOKENS TAB
                 ═══════════════════════════════════════════════════════════════════ -->
        <section v-show="activeTab === 'tokens'" class="space-y-6">
            <!-- Token Created Modal -->
            <Dialog :open="showTokenModal" @update:open="closeTokenModal">
                <DialogContent class="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            Token Created Successfully
                        </DialogTitle>
                    </DialogHeader>
                    <div class="space-y-4">
                        <p class="text-muted-foreground text-sm">
                            Copy this token now. For security, it will not be shown again.
                        </p>
                        <div class="bg-surface border-border/50 rounded-lg border p-4">
                            <code class="font-mono text-sm break-all select-all">{{ props.newToken }}</code>
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="copyToken"
                                :class="copied ? 'bg-emerald-600' : 'bg-emerald-500 hover:bg-emerald-400'"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-black transition-all"
                            >
                                <svg v-if="!copied" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                </svg>
                                <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                {{ copied ? 'Copied!' : 'Copy Token' }}
                            </button>
                            <button
                                @click="closeTokenModal"
                                class="inline-flex items-center justify-center rounded-xl border border-border/50 px-4 py-2.5 text-sm font-medium transition-all hover:bg-surface"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            <!-- Create Token Form -->
            <div class="border-border/40 bg-card rounded-2xl border p-6">
                <div class="mb-6 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-violet-500/20 bg-violet-500/10">
                        <svg class="h-5 w-5 text-violet-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-foreground font-semibold">Create API Token</h2>
                        <p class="text-muted-foreground text-sm">
                            Generate a new token for API access
                        </p>
                    </div>
                </div>

                <form class="space-y-5" @submit.prevent="createToken">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="token_name" class="text-foreground block text-sm font-medium">Token Name</label>
                            <input
                                id="token_name"
                                v-model="tokenForm.name"
                                type="text"
                                placeholder="e.g., My App, CI/CD, Mobile App"
                                class="border-border/50 focus:ring-primary/20 focus:border-primary/35 bg-surface/60 text-foreground placeholder:text-muted-foreground h-11 w-full rounded-xl border px-4 transition-all duration-200 focus:ring-2 focus:outline-none"
                            />
                            <p v-if="tokenForm.errors.name" class="text-xs text-rose-400">{{ tokenForm.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <label for="token_expires" class="text-foreground block text-sm font-medium">Expires At (optional)</label>
                            <input
                                id="token_expires"
                                v-model="tokenForm.expires_at"
                                type="date"
                                class="border-border/50 focus:ring-primary/20 focus:border-primary/35 bg-surface/60 text-foreground h-11 w-full rounded-xl border px-4 transition-all duration-200 focus:ring-2 focus:outline-none"
                            />
                            <p v-if="tokenForm.errors.expires_at" class="text-xs text-rose-400">{{ tokenForm.errors.expires_at }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            :disabled="tokenForm.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-500 to-purple-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-violet-500/15 transition-all duration-300 hover:shadow-violet-500/25 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Create Token
                        </button>
                    </div>
                </form>
            </div>

            <!-- Token List -->
            <div class="border-border/40 bg-card rounded-2xl border p-6">
                <div class="mb-6 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-amber-500/20 bg-amber-500/10">
                        <svg class="h-5 w-5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a4 4 0 0 0-4 4c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2 4 4 0 0 0-4-4Z"/>
                            <path d="M12 8v13"/>
                            <path d="M5 21h14"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-foreground font-semibold">Your Tokens</h2>
                        <p class="text-muted-foreground text-sm">
                            Manage your API tokens
                        </p>
                    </div>
                </div>

                <div v-if="tokens && tokens.length > 0" class="space-y-3">
                    <div
                        v-for="token in tokens"
                        :key="token.id"
                        class="bg-surface/50 border-border/30 flex items-center justify-between rounded-xl border p-4"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <h4 class="text-foreground truncate font-medium">{{ token.name }}</h4>
                                <span
                                    v-if="token.expires_at"
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="new Date(token.expires_at) < new Date() ? 'bg-rose-500/10 text-rose-400' : 'bg-emerald-500/10 text-emerald-400'"
                                >
                                    {{ new Date(token.expires_at) < new Date() ? 'Expired' : 'Expires ' + token.expires_at }}
                                </span>
                            </div>
                            <div class="text-muted-foreground mt-1 flex items-center gap-3 text-xs">
                                <span>Created {{ token.created_at }}</span>
                                <span v-if="token.last_used_at">Last used {{ token.last_used_at }}</span>
                            </div>
                        </div>
                        <button
                            @click="confirmRevoke(token.id)"
                            class="ml-4 inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-rose-400 transition-colors hover:bg-rose-500/10"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                            </svg>
                            Revoke
                        </button>
                    </div>
                </div>

                <div v-else class="py-12 text-center">
                    <div class="bg-surface-elevated mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl">
                        <svg class="text-muted-foreground h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                        </svg>
                    </div>
                    <p class="text-muted-foreground">No API tokens yet</p>
                    <p class="text-muted-foreground mt-1 text-sm">Create one above to get started with the API.</p>
                </div>
            </div>
        </section>

        <!-- Revoke Confirm Dialog -->
        <ConfirmDialog
            v-model:open="showRevokeDialog"
            title="Revoke Token"
            description="Are you sure you want to revoke this token? This action cannot be undone."
            confirm-label="Revoke"
            cancel-label="Cancel"
            variant="destructive"
            @confirm="revokeToken"
        />
    </PageWidth>
</template>
