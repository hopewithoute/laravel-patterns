<script setup>
import { Link, useForm, Head } from '@inertiajs/vue3'
import PageHeader from '@/components/layout/PageHeader.vue'
import PageWidth from '@/components/layout/PageWidth.vue'

const props = defineProps({
    task: Object,
    options: Object,
})

const form = useForm({
    title: props.task?.title || '',
    description: props.task?.description || '',
    project_id: props.task?.project_id || '',
    assigned_to: props.task?.assigned_to || '',
    status: props.task?.status || 'Todo',
    priority: props.task?.priority || 'Medium',
    due_date: props.task?.due_date || '',
})

const submit = () => {
    if (props.task) {
        form.put(`/tasks/${props.task.id}`)
    } else {
        form.post('/tasks')
    }
}
</script>

<template>
    <Head :title="task ? 'Edit Task' : 'New Task'" />
    <PageWidth size="content" class="space-y-8">
            <PageHeader
                variant="stacked"
                tone="cyan"
                title-size="headline"
                :title="task ? 'Edit Task' : 'New Task'"
                :description="task ? 'Update task details' : 'Add a new item to your workflow'"
                back-href="/tasks"
                back-label="All Tasks"
                glow-class="top-0 right-0 w-64 h-64 bg-cyan-500/5"
            >
                <template #media>
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-linear-to-br from-cyan-500/20 to-cyan-500/5 ring-2 ring-cyan-500/10"
                    >
                        <svg
                            class="h-6 w-6 text-cyan-400"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M9 11l3 3L22 4" />
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                        </svg>
                    </div>
                </template>
            </PageHeader>

            <!-- Form -->
            <form class="space-y-6" @submit.prevent="submit">
                <div class="border-border/50 bg-card space-y-5 rounded-2xl border p-6">
                    <!-- Title -->
                    <div class="space-y-2">
                        <label for="title" class="text-foreground block text-sm font-medium">
                            Title <span class="text-rose-400">*</span>
                        </label>
                        <input
                            id="title"
                            v-model="form.title"
                            type="text"
                            required
                            placeholder="e.g., Design homepage hero section"
                            class="border-border/60 bg-surface/50 text-foreground placeholder:text-muted-foreground h-11 w-full rounded-xl border px-4 transition-all duration-200 focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/30 focus:outline-none"
                        />
                        <p v-if="form.errors.title" class="text-xs text-rose-400">
                            {{ form.errors.title }}
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
                            placeholder="Add more details about this task..."
                            class="border-border/60 bg-surface/50 text-foreground placeholder:text-muted-foreground w-full resize-none rounded-xl border px-4 py-3 transition-all duration-200 focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/30 focus:outline-none"
                        />
                    </div>

                    <!-- Project & Assignee -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label
                                for="project_id"
                                class="text-foreground block text-sm font-medium"
                            >
                                Project <span class="text-rose-400">*</span>
                            </label>
                            <select
                                id="project_id"
                                v-model="form.project_id"
                                required
                                class="border-border/60 bg-surface/50 text-foreground h-11 w-full cursor-pointer appearance-none rounded-xl border px-4 transition-all duration-200 focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/30 focus:outline-none"
                                style='
                                    background-image: url("data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2371717a%22 stroke-width=%222%22%3E%3Cpath d=%22m6 9 6 6 6-6%22/%3E%3C/svg%3E");
                                    background-position: right 12px center;
                                    background-repeat: no-repeat;
                                    background-size: 16px;
                                '
                            >
                                <option value="">Select project</option>
                                <option
                                    v-for="project in options?.projects"
                                    :key="project.id"
                                    :value="project.id"
                                >
                                    {{ project.name }}
                                </option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label
                                for="assigned_to"
                                class="text-foreground block text-sm font-medium"
                            >
                                Assignee
                            </label>
                            <select
                                id="assigned_to"
                                v-model="form.assigned_to"
                                class="border-border/60 bg-surface/50 text-foreground h-11 w-full cursor-pointer appearance-none rounded-xl border px-4 transition-all duration-200 focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/30 focus:outline-none"
                                style='
                                    background-image: url("data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2371717a%22 stroke-width=%222%22%3E%3Cpath d=%22m6 9 6 6 6-6%22/%3E%3C/svg%3E");
                                    background-position: right 12px center;
                                    background-repeat: no-repeat;
                                    background-size: 16px;
                                '
                            >
                                <option value="">Unassigned</option>
                                <option
                                    v-for="user in options?.users"
                                    :key="user.id"
                                    :value="user.id"
                                >
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Status & Priority -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="status" class="text-foreground block text-sm font-medium">
                                Status
                            </label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="border-border/60 bg-surface/50 text-foreground h-11 w-full cursor-pointer appearance-none rounded-xl border px-4 transition-all duration-200 focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/30 focus:outline-none"
                                style='
                                    background-image: url("data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2371717a%22 stroke-width=%222%22%3E%3Cpath d=%22m6 9 6 6 6-6%22/%3E%3C/svg%3E");
                                    background-position: right 12px center;
                                    background-repeat: no-repeat;
                                    background-size: 16px;
                                '
                            >
                                <option
                                    v-for="s in options?.statuses"
                                    :key="s.value"
                                    :value="s.value"
                                >
                                    {{ s.text }}
                                </option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label for="priority" class="text-foreground block text-sm font-medium">
                                Priority
                            </label>
                            <select
                                id="priority"
                                v-model="form.priority"
                                class="border-border/60 bg-surface/50 text-foreground h-11 w-full cursor-pointer appearance-none rounded-xl border px-4 transition-all duration-200 focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/30 focus:outline-none"
                                style='
                                    background-image: url("data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2371717a%22 stroke-width=%222%22%3E%3Cpath d=%22m6 9 6 6 6-6%22/%3E%3C/svg%3E");
                                    background-position: right 12px center;
                                    background-repeat: no-repeat;
                                    background-size: 16px;
                                '
                            >
                                <option
                                    v-for="p in options?.priorities"
                                    :key="p.value"
                                    :value="p.value"
                                >
                                    {{ p.text }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Due Date -->
                    <div class="space-y-2">
                        <label for="due_date" class="text-foreground block text-sm font-medium">
                            Due Date
                        </label>
                        <input
                            id="due_date"
                            v-model="form.due_date"
                            type="date"
                            class="border-border/60 bg-surface/50 text-foreground h-11 w-full rounded-xl border px-4 transition-all duration-200 focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/30 focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        href="/tasks"
                        class="border-border/60 bg-surface/50 text-foreground hover:bg-surface hover:border-border rounded-xl border px-5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-cyan-500 to-cyan-400 px-5 py-2.5 text-sm font-semibold text-black shadow-lg shadow-cyan-500/20 transition-all duration-300 hover:shadow-cyan-500/30 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg
                            v-if="!task"
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                        >
                            <line x1="12" x2="12" y1="5" y2="19" />
                            <line x1="5" x2="19" y1="12" y2="12" />
                        </svg>
                        {{ task ? 'Update' : 'Create' }} Task
                    </button>
                </div>
            </form>
        </PageWidth>
</template>
