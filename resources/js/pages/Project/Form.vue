<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import PageWidth from '@/components/layout/PageWidth.vue'

const props = defineProps({
    project: Object,
})

const form = useForm({
    name: props.project?.name || '',
    description: props.project?.description || '',
    color: props.project?.color || '#f59e0b',
    is_active: props.project?.is_active ?? true,
})

const submit = () => {
    if (props.project) {
        form.put(`/projects/${props.project.id}`)
    } else {
        form.post('/projects')
    }
}

const colors = [
    '#ef4444',
    '#f97316',
    '#f59e0b',
    '#eab308',
    '#84cc16',
    '#22c55e',
    '#10b981',
    '#14b8a6',
    '#06b6d4',
    '#0ea5e9',
    '#3b82f6',
    '#6366f1',
    '#8b5cf6',
    '#a855f7',
    '#d946ef',
    '#ec4899',
]
</script>

<template>
    <AppLayout>
        <PageWidth size="content" class="space-y-8">
            <PageHeader
                variant="stacked"
                tone="amber"
                title-size="headline"
                :title="project ? 'Edit Project' : 'New Project'"
                :description="
                    project ? 'Update project details' : 'Create a new workspace for your tasks'
                "
                back-href="/projects"
                back-label="All Projects"
                glow-class="top-0 right-0 w-64 h-64 bg-amber-500/5"
            >
                <template #media>
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl ring-2 ring-white/10"
                        :style="{ backgroundColor: form.color }"
                    >
                        <span class="text-lg font-bold text-black">{{
                            form.name?.charAt(0)?.toUpperCase() || 'P'
                        }}</span>
                    </div>
                </template>
            </PageHeader>

            <!-- Form -->
            <form class="space-y-6" @submit.prevent="submit">
                <div class="border-border/50 bg-card space-y-5 rounded-2xl border p-6">
                    <!-- Name -->
                    <div class="space-y-2">
                        <label for="name" class="text-foreground block text-sm font-medium">
                            Project Name <span class="text-rose-400">*</span>
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="e.g., Website Redesign"
                            class="border-border/60 bg-surface/50 text-foreground placeholder:text-muted-foreground h-11 w-full rounded-xl border px-4 transition-all duration-200 focus:border-amber-500/50 focus:ring-2 focus:ring-amber-500/30 focus:outline-none"
                        />
                        <p v-if="form.errors.name" class="text-xs text-rose-400">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <label for="description" class="text-foreground block text-sm font-medium">
                            Description <span class="text-muted-foreground">(optional)</span>
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            placeholder="What is this project about?"
                            class="border-border/60 bg-surface/50 text-foreground placeholder:text-muted-foreground w-full resize-none rounded-xl border px-4 py-3 transition-all duration-200 focus:border-amber-500/50 focus:ring-2 focus:ring-amber-500/30 focus:outline-none"
                        />
                    </div>

                    <!-- Color -->
                    <div class="space-y-3">
                        <label class="text-foreground block text-sm font-medium"> Color </label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="c in colors"
                                :key="c"
                                type="button"
                                :class="[
                                    'h-8 w-8 rounded-xl border-2 transition-all duration-200 hover:scale-110',
                                    form.color === c
                                        ? 'border-foreground scale-110 shadow-lg'
                                        : 'hover:border-border border-transparent',
                                ]"
                                :style="{
                                    backgroundColor: c,
                                    boxShadow: form.color === c ? `0 4px 12px ${c}40` : 'none',
                                }"
                                @click="form.color = c"
                            />
                        </div>
                    </div>

                    <!-- Active Toggle -->
                    <div class="bg-surface/50 flex items-center gap-3 rounded-xl p-3">
                        <button
                            type="button"
                            :class="[
                                'relative h-6 w-11 rounded-full transition-colors duration-200',
                                form.is_active ? 'bg-amber-500' : 'bg-border',
                            ]"
                            @click="form.is_active = !form.is_active"
                        >
                            <span
                                :class="[
                                    'absolute top-1 left-1 h-4 w-4 rounded-full bg-white shadow transition-transform duration-200',
                                    form.is_active && 'translate-x-5',
                                ]"
                            />
                        </button>
                        <div>
                            <span class="text-foreground text-sm font-medium">Active project</span>
                            <span class="text-muted-foreground block text-xs"
                                >Inactive projects won't appear in searches</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        href="/projects"
                        class="border-border/60 bg-surface/50 text-foreground hover:bg-surface hover:border-border rounded-xl border px-5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-2.5 text-sm font-semibold text-black shadow-lg shadow-amber-500/20 transition-all duration-300 hover:shadow-amber-500/30 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg
                            v-if="!project"
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                        >
                            <line x1="12" x2="12" y1="5" y2="19" />
                            <line x1="5" x2="19" y1="12" y2="12" />
                        </svg>
                        {{ project ? 'Update' : 'Create' }} Project
                    </button>
                </div>
            </form>
        </PageWidth>
    </AppLayout>
</template>
