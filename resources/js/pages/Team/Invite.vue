<script setup>
import { useForm, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import PageWidth from '@/components/layout/PageWidth.vue'
import { ref } from 'vue'

const props = defineProps({
    organization: Object,
})

const copied = ref(false)

const inviteLink = `${window.location.origin}/register?invite=${props.organization.invite_code}`

const copyLink = () => {
    navigator.clipboard.writeText(inviteLink)
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
}

const regenerateForm = useForm({})
</script>

<template>
    <Head title="Invite to Team" />
    <AppLayout>
        <PageWidth size="content" class="space-y-8">
            <PageHeader
                variant="stacked"
                tone="cyan"
                title-size="headline"
                title="Invite to Team"
                description="Share a reusable invite link or code so new collaborators can join this workspace."
                back-href="/team"
                back-label="All Team Members"
                glow-class="top-0 right-0 w-72 h-72 bg-cyan-500/[0.04]"
            />

            <div class="border-border bg-card space-y-6 rounded-2xl border p-6">
                <!-- Invite Link -->
                <div>
                    <label class="text-foreground mb-2 block text-sm font-medium">
                        Invite Link
                    </label>
                    <div class="flex gap-2">
                        <input
                            :value="inviteLink"
                            readonly
                            class="border-border bg-muted text-muted-foreground flex-1 rounded-md border px-3 py-2 text-sm"
                        />
                        <button
                            class="bg-primary text-primary-foreground hover:bg-primary/90 rounded-md px-4 py-2 text-sm font-medium"
                            @click="copyLink"
                        >
                            {{ copied ? 'Copied!' : 'Copy' }}
                        </button>
                    </div>
                    <p class="text-muted-foreground mt-2 text-sm">
                        Share this link with your team members. They'll be able to create an account
                        and join your organization.
                    </p>
                </div>

                <!-- Invite Code -->
                <div class="border-border border-t pt-6">
                    <label class="text-foreground mb-2 block text-sm font-medium">
                        Invite Code
                    </label>
                    <div class="flex items-center gap-4">
                        <code
                            class="bg-muted text-foreground rounded-md px-4 py-2 font-mono text-lg"
                        >
                            {{ organization.invite_code }}
                        </code>
                        <button
                            :disabled="regenerateForm.processing"
                            class="text-muted-foreground hover:text-foreground text-sm"
                            @click="regenerateForm.post('/team/regenerate-code')"
                        >
                            Regenerate
                        </button>
                    </div>
                </div>
            </div>
        </PageWidth>
    </AppLayout>
</template>
