<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/ui/Button.vue'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'

const props = defineProps({
    inviteCode: String,
    organization: Object,
})

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    invite_code: props.inviteCode || '',
})

const submit = () => {
    form.post('/register')
}
</script>

<template>
    <div
        class="bg-background relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-12"
    >
        <!-- Background depth -->
        <div class="pointer-events-none fixed inset-0">
            <div
                class="absolute top-1/4 -right-32 h-96 w-96 rounded-full bg-cyan-500/[0.04] blur-3xl"
            ></div>
            <div
                class="absolute bottom-1/4 -left-32 h-80 w-80 rounded-full bg-amber-500/[0.03] blur-3xl"
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

                <div
                    v-if="organization"
                    class="section-badge section-badge-cyan mb-2 inline-flex items-center gap-2"
                >
                    <div class="h-1.5 w-1.5 rounded-full bg-cyan-400"></div>
                    <span class="font-mono text-[11px] tracking-widest text-cyan-400 uppercase"
                        >Joining {{ organization.name }}</span
                    >
                </div>
            </div>

            <!-- Auth Card -->
            <Card class="border-border/40">
                <CardHeader class="space-y-2 pb-6">
                    <CardTitle class="font-display text-foreground text-3xl"
                        >Create account</CardTitle
                    >
                    <CardDescription class="text-muted-foreground">
                        <span v-if="organization">Join your team's workspace</span>
                        <span v-else>Get started with your free account</span>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-5" @submit.prevent="submit">
                        <!-- Show invite info if joining org -->
                        <div
                            v-if="organization"
                            class="rounded-xl border border-cyan-500/20 bg-cyan-500/10 p-4"
                        >
                            <p class="text-foreground text-sm">
                                You're joining <strong>{{ organization.name }}</strong>
                            </p>
                        </div>

                        <input v-model="form.invite_code" type="hidden" />

                        <div class="space-y-2">
                            <Label for="name" class="text-foreground text-sm font-medium"
                                >Name</Label
                            >
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Your full name"
                                required
                                autofocus
                                autocomplete="name"
                            />
                            <p v-if="form.errors.name" class="text-xs text-rose-400">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="email" class="text-foreground text-sm font-medium"
                                >Email</Label
                            >
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="name@example.com"
                                required
                                autocomplete="email"
                            />
                            <p v-if="form.errors.email" class="text-xs text-rose-400">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="password" class="text-foreground text-sm font-medium"
                                >Password</Label
                            >
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="At least 8 characters"
                                required
                                autocomplete="new-password"
                            />
                            <p v-if="form.errors.password" class="text-xs text-rose-400">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label
                                for="password_confirmation"
                                class="text-foreground text-sm font-medium"
                                >Confirm Password</Label
                            >
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                placeholder="Repeat your password"
                                required
                                autocomplete="new-password"
                            />
                        </div>

                        <Button
                            type="submit"
                            variant="amber"
                            size="lg"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Creating account...</span>
                            <span v-else>{{ organization ? 'Join Team' : 'Create Account' }}</span>
                        </Button>

                        <div class="text-muted-foreground pt-2 text-center text-sm">
                            Already have an account?
                            <Link
                                href="/login"
                                class="font-medium text-amber-400 transition-colors hover:text-amber-300"
                            >
                                Sign in
                            </Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
