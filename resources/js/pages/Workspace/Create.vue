<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'

const currentStep = ref(1)
const totalSteps = 3

const form = useForm({
    name: '',
    description: '',
    invite_emails: '',
})

const stepTitles = ['Workspace Details', 'Invite Your Team', 'Ready to Go!']

const stepDescriptions = [
    'Name your workspace and describe its purpose',
    'Add collaborators now or skip for later',
    'Review and create your workspace',
]

const canProceed = computed(() => {
    if (currentStep.value === 1) {
        return form.name.trim().length >= 2
    }
    return true
})

const nextStep = () => {
    if (currentStep.value < totalSteps && canProceed.value) {
        currentStep.value++
    }
}

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--
    }
}

const submit = () => {
    form.post('/workspace', {
        onSuccess: () => {
            // Redirect is handled by the controller
        },
    })
}

const skipInvites = () => {
    currentStep.value = 3
}
</script>

<template>
    <div class="bg-background grain flex min-h-screen items-center justify-center px-4 py-12">
        <!-- Background effects -->
        <div class="pointer-events-none fixed inset-0">
            <div
                class="absolute top-0 left-1/4 h-96 w-96 rounded-full bg-amber-500/[0.04] blur-3xl"
            ></div>
            <div
                class="absolute right-1/4 bottom-0 h-96 w-96 rounded-full bg-cyan-500/[0.03] blur-3xl"
            ></div>
        </div>

        <div class="relative z-10 w-full max-w-2xl">
            <!-- Progress Steps -->
            <div class="mb-10 flex items-center justify-center">
                <div class="flex items-center gap-3">
                    <template v-for="step in totalSteps" :key="step">
                        <div
                            :class="[
                                'flex h-10 w-10 items-center justify-center rounded-xl text-sm font-semibold transition-all duration-300',
                                step === currentStep
                                    ? 'bg-gradient-to-br from-amber-500 to-orange-500 text-black shadow-lg shadow-amber-500/30'
                                    : step < currentStep
                                      ? 'bg-amber-500 text-black'
                                      : 'bg-surface border-border/40 text-muted-foreground border',
                            ]"
                        >
                            <svg
                                v-if="step < currentStep"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            <span v-else>{{ step }}</span>
                        </div>
                        <div
                            v-if="step < totalSteps"
                            :class="[
                                'h-0.5 w-12 rounded-full transition-all duration-300',
                                step < currentStep ? 'bg-amber-500' : 'bg-border/40',
                            ]"
                        />
                    </template>
                </div>
            </div>

            <!-- Main Card -->
            <div class="glass-elevated border-border/40 overflow-hidden rounded-2xl border">
                <!-- Header -->
                <div class="border-border/40 border-b px-8 pt-8 pb-6 text-center">
                    <div class="section-badge section-badge-amber mb-4">
                        <div class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"></div>
                        <span>Setup</span>
                    </div>
                    <h1
                        class="font-display text-foreground mb-2 text-3xl leading-[0.95] tracking-tight sm:text-4xl"
                    >
                        {{ stepTitles[currentStep - 1] }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ stepDescriptions[currentStep - 1] }}
                    </p>
                </div>

                <!-- Content -->
                <div class="space-y-6 p-8">
                    <!-- Step 1: Organization Details -->
                    <div v-show="currentStep === 1" class="space-y-5">
                        <div class="space-y-2">
                            <label for="name" class="text-foreground block text-sm font-medium">
                                Workspace Name <span class="text-rose-400">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="e.g., Acme Design Team"
                                class="border-border/50 bg-surface/60 text-foreground placeholder:text-muted-foreground focus:ring-primary/20 focus:border-primary/35 h-12 w-full rounded-xl border px-4 transition-all duration-200 focus:ring-2 focus:outline-none"
                            />
                            <p v-if="form.errors.name" class="text-xs text-rose-400">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label
                                for="description"
                                class="text-foreground block text-sm font-medium"
                            >
                                Description <span class="text-muted-foreground">(optional)</span>
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                placeholder="What is this workspace for?"
                                class="border-border/50 bg-surface/60 text-foreground placeholder:text-muted-foreground focus:ring-primary/20 focus:border-primary/35 w-full resize-none rounded-xl border px-4 py-3 transition-all duration-200 focus:ring-2 focus:outline-none"
                            />
                        </div>

                        <!-- Workspace Type Selection -->
                        <div class="space-y-3">
                            <label class="text-foreground block text-sm font-medium">
                                Workspace Type
                            </label>
                            <div class="grid grid-cols-3 gap-3">
                                <button
                                    type="button"
                                    class="group border-border/40 bg-surface/30 hover:bg-surface/50 rounded-xl border p-4 text-center transition-all duration-200 hover:border-amber-500/40"
                                >
                                    <div
                                        class="bg-surface-elevated mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-lg group-hover:bg-amber-500/10"
                                    >
                                        <svg
                                            class="text-muted-foreground h-5 w-5 group-hover:text-amber-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-5 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                            />
                                        </svg>
                                    </div>
                                    <span
                                        class="text-muted-foreground group-hover:text-foreground text-xs"
                                        >Company</span
                                    >
                                </button>
                                <button
                                    type="button"
                                    class="rounded-xl border border-amber-500/40 bg-amber-500/5 p-4 text-center transition-all duration-200"
                                >
                                    <div
                                        class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/10"
                                    >
                                        <svg
                                            class="h-5 w-5 text-amber-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M17 20h5v-2a3 3 0 00-3-3H5a3 3 0 00-3 3v2h5m10 0v-2a3 3 0 00-3-3H8a3 3 0 00-3 3v2m10 0H8m4-8a3 3 0 100-6 3 3 0 000 6z"
                                            />
                                        </svg>
                                    </div>
                                    <span class="text-xs text-amber-400">Team</span>
                                </button>
                                <button
                                    type="button"
                                    class="group border-border/40 bg-surface/30 hover:bg-surface/50 rounded-xl border p-4 text-center transition-all duration-200 hover:border-amber-500/40"
                                >
                                    <div
                                        class="bg-surface-elevated mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-lg group-hover:bg-amber-500/10"
                                    >
                                        <svg
                                            class="text-muted-foreground h-5 w-5 group-hover:text-amber-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                            />
                                        </svg>
                                    </div>
                                    <span
                                        class="text-muted-foreground group-hover:text-foreground text-xs"
                                        >Personal</span
                                    >
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Invite Team -->
                    <div v-show="currentStep === 2" class="space-y-5">
                        <div class="py-4 text-center">
                            <div
                                class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500/20 to-cyan-500/5"
                            >
                                <svg
                                    class="h-8 w-8 text-cyan-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM5 20a4 4 0 018 0v-1M3 16a4 4 0 014-4h4a4 4 0 014 4"
                                    />
                                </svg>
                            </div>
                            <h3 class="font-display text-foreground mb-2 text-lg font-bold">
                                Invite Team Members
                            </h3>
                            <p class="text-muted-foreground text-sm">
                                Add collaborators by email address
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label for="emails" class="text-foreground block text-sm font-medium">
                                Email Addresses
                            </label>
                            <textarea
                                id="emails"
                                v-model="form.invite_emails"
                                rows="4"
                                placeholder="Enter email addresses, one per line&#10;e.g.,&#10;john@example.com&#10;jane@example.com"
                                class="border-border/50 bg-surface/60 text-foreground placeholder:text-muted-foreground focus:ring-primary/20 focus:border-primary/35 w-full resize-none rounded-xl border px-4 py-3 font-mono text-sm transition-all duration-200 focus:ring-2 focus:outline-none"
                            />
                            <p class="text-muted-foreground text-xs">
                                Separate multiple emails with new lines or commas
                            </p>
                        </div>
                    </div>

                    <!-- Step 3: Confirmation -->
                    <div v-show="currentStep === 3" class="space-y-5">
                        <div class="py-4 text-center">
                            <div
                                class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 text-3xl font-bold text-black shadow-lg shadow-amber-500/30"
                            >
                                {{ form.name.charAt(0).toUpperCase() }}
                            </div>
                            <h3 class="font-display text-foreground mb-1 text-xl font-bold">
                                {{ form.name }}
                            </h3>
                            <p v-if="form.description" class="text-muted-foreground text-sm">
                                {{ form.description }}
                            </p>
                        </div>

                        <!-- Summary -->
                        <div class="bg-surface/50 border-border/40 space-y-4 rounded-xl border p-5">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Workspace</span>
                                <span class="text-foreground font-medium">{{ form.name }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Your Role</span>
                                <span
                                    class="rounded-lg bg-amber-500/10 px-2.5 py-1 text-xs font-medium text-amber-400"
                                    >Admin</span
                                >
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Team Members</span>
                                <span class="text-foreground font-medium">
                                    {{
                                        form.invite_emails
                                            ? form.invite_emails
                                                  .split(/[,.\n]/)
                                                  .filter((e) => e.trim()).length
                                            : 0
                                    }}
                                    invited
                                </span>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div
                            class="flex items-start gap-3 rounded-xl border border-amber-500/20 bg-amber-500/10 p-4"
                        >
                            <svg
                                class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <div class="text-sm">
                                <p class="text-foreground font-medium">You're all set!</p>
                                <p class="text-muted-foreground">
                                    Your workspace will be ready immediately after creation.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div
                    class="bg-surface/30 border-border/40 flex items-center justify-between border-t px-8 py-6"
                >
                    <div>
                        <Link
                            v-if="currentStep === 1"
                            href="/workspace/select"
                            class="text-muted-foreground hover:text-foreground flex items-center gap-1 text-sm transition-colors"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M19 12H5M12 19l-7-7 7-7" />
                            </svg>
                            Back to workspaces
                        </Link>
                        <button
                            v-else
                            type="button"
                            class="text-muted-foreground hover:text-foreground flex items-center gap-1 text-sm transition-colors"
                            @click="prevStep"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M19 12H5M12 19l-7-7 7-7" />
                            </svg>
                            Previous step
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            v-if="currentStep === 2"
                            type="button"
                            class="text-muted-foreground hover:text-foreground px-4 py-2 text-sm font-medium transition-colors"
                            @click="skipInvites"
                        >
                            Skip for now
                        </button>

                        <button
                            v-if="currentStep < totalSteps"
                            type="button"
                            :disabled="!canProceed"
                            class="rounded-xl px-6 py-2.5 text-sm font-semibold transition-all duration-300"
                            :class="
                                canProceed
                                    ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-black shadow-lg shadow-amber-500/15 hover:shadow-amber-500/25'
                                    : 'bg-surface text-muted-foreground cursor-not-allowed'
                            "
                            @click="nextStep"
                        >
                            Continue
                        </button>

                        <button
                            v-if="currentStep === totalSteps"
                            type="button"
                            :disabled="form.processing"
                            class="rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-8 py-2.5 text-sm font-semibold text-black shadow-lg shadow-amber-500/15 transition-all duration-300 hover:shadow-amber-500/25 disabled:opacity-50"
                            @click="submit"
                        >
                            <span v-if="form.processing">Creating...</span>
                            <span v-else>Create Workspace</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <p class="text-muted-foreground mt-6 text-center text-xs">
                By creating a workspace, you agree to our
                <a href="#" class="text-foreground transition-colors hover:text-amber-400"
                    >Terms of Service</a
                >
                and
                <a href="#" class="text-foreground transition-colors hover:text-amber-400"
                    >Privacy Policy</a
                >
            </p>
        </div>
    </div>
</template>
