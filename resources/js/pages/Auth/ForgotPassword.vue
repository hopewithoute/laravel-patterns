<script setup>
import { Link, useForm, Head } from '@inertiajs/vue3'
import Button from '@/components/ui/Button.vue'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'

const form = useForm({
    email: '',
})

const submit = () => {
    form.post('/forgot-password')
}
</script>

<template>
    <Head title="Forgot Password" />
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
            </div>

            <!-- Card -->
            <Card class="border-border/40">
                <CardHeader class="space-y-2 pb-6">
                    <CardTitle class="font-display text-foreground text-3xl"
                        >Reset password</CardTitle
                    >
                    <CardDescription class="text-muted-foreground">
                        Enter your email and we'll send you a reset link
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-5" @submit.prevent="submit">
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
                                autofocus
                                autocomplete="email"
                            />
                            <p v-if="form.errors.email" class="text-xs text-rose-400">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <Button
                            type="submit"
                            variant="amber"
                            size="lg"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Sending...</span>
                            <span v-else>Send Reset Link</span>
                        </Button>

                        <div class="text-muted-foreground pt-2 text-center text-sm">
                            Remember your password?
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
