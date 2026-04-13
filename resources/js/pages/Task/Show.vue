<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import PageWidth from '@/components/layout/PageWidth.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import { TASK_PRIORITY_TONES, TASK_STATUS_TONES } from '@/lib/badges'

const props = defineProps({
    task: Object,
})

const isDeleting = ref(false)
const showDeleteConfirm = ref(false)

const deleteTask = () => {
    isDeleting.value = true
    router.delete(`/tasks/${props.task.id}`, {
        // We rely on the backend's smart redirect logic to return to
        // the Project page if this task was deleted from its detail view.
        onFinish: () => {
            isDeleting.value = false
            showDeleteConfirm.value = false
        },
    })
}

const commentForm = useForm({
    content: '',
})

const submitComment = () => {
    commentForm.post(`/tasks/${props.task.id}/comments`, {
        onSuccess: () => commentForm.reset(),
    })
}

const isOverdue = () => {
    if (!props.task.due_date || props.task.status === 'Done') return false
    return new Date(props.task.due_date) < new Date()
}
</script>

<template>
    <AppLayout>
        <PageWidth size="wide" class="space-y-8">
            <PageHeader
                variant="stacked"
                tone="cyan"
                title-size="headline"
                :title="task.title"
                :description="task.description || 'No description provided'"
                description-class="text-lg max-w-xl"
                back-href="/tasks"
                back-label="All Tasks"
                glow-class="top-0 right-0 w-80 h-80 bg-cyan-500/[0.04]"
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

                <template #actions>
                    <Link
                        :href="`/tasks/${task.id}/edit`"
                        class="border-border bg-card text-foreground hover:bg-muted rounded-xl border px-4 py-2.5 text-sm font-medium"
                    >
                        Edit
                    </Link>
                    <Button
                        variant="destructive"
                        class="rounded-xl px-4 py-2.5"
                        @click="showDeleteConfirm = true"
                    >
                        Delete
                    </Button>
                </template>
            </PageHeader>

            <!-- Task Header -->
            <div class="border-border bg-card rounded-2xl border p-6">
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <Badge :tone="TASK_STATUS_TONES[task.status] || TASK_STATUS_TONES.Todo">
                        {{ task.status }}
                    </Badge>
                    <Badge :tone="TASK_PRIORITY_TONES[task.priority] || TASK_PRIORITY_TONES.Low">
                        {{ task.priority }}
                    </Badge>
                </div>

                <!-- Task Meta -->
                <div class="mt-6 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div>
                        <p class="text-muted-foreground font-medium">Project</p>
                        <p v-if="task.project" class="text-foreground">{{ task.project.name }}</p>
                        <p v-else class="text-muted-foreground">-</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground font-medium">Assignee</p>
                        <p v-if="task.assignee" class="text-foreground">{{ task.assignee.name }}</p>
                        <p v-else class="text-muted-foreground">Unassigned</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground font-medium">Due Date</p>
                        <p :class="[isOverdue() ? 'font-medium text-red-600' : 'text-foreground']">
                            <span v-if="task.due_date">
                                {{ new Date(task.due_date).toLocaleDateString() }}
                                <span v-if="isOverdue()">(Overdue)</span>
                            </span>
                            <span v-else class="text-muted-foreground">-</span>
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground font-medium">Created</p>
                        <p class="text-foreground">
                            {{ new Date(task.created_at).toLocaleDateString() }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Comments -->
            <div class="border-border bg-card rounded-2xl border">
                <div class="border-border border-b px-6 py-4">
                    <h2 class="text-foreground text-lg font-medium">Comments</h2>
                </div>

                <!-- Comment Form -->
                <div class="border-border border-b p-6">
                    <form @submit.prevent="submitComment">
                        <textarea
                            v-model="commentForm.content"
                            rows="2"
                            placeholder="Add a comment..."
                            class="border-border bg-background text-foreground focus:border-primary focus:ring-primary block w-full rounded-md border px-3 py-2 shadow-sm focus:ring-1"
                        />
                        <div class="mt-2 flex justify-end">
                            <button
                                type="submit"
                                :disabled="commentForm.processing || !commentForm.content"
                                class="bg-primary text-primary-foreground hover:bg-primary/90 rounded-md px-3 py-1.5 text-sm font-medium shadow-sm disabled:opacity-50"
                            >
                                Add Comment
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Comments List -->
                <ul class="divide-border divide-y">
                    <li v-for="comment in task.comments" :key="comment.id" class="px-6 py-4">
                        <div class="flex items-start space-x-3">
                            <div class="shrink-0">
                                <div
                                    class="bg-muted text-muted-foreground flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium"
                                >
                                    {{ comment.user?.name?.charAt(0).toUpperCase() || '?' }}
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-foreground text-sm font-medium">
                                        {{ comment.user?.name || 'Unknown' }}
                                    </p>
                                    <p class="text-muted-foreground text-xs">
                                        {{ new Date(comment.created_at).toLocaleString() }}
                                    </p>
                                </div>
                                <p class="text-muted-foreground mt-1 text-sm">
                                    {{ comment.content }}
                                </p>
                            </div>
                        </div>
                    </li>
                    <li
                        v-if="!task.comments?.length"
                        class="text-muted-foreground px-6 py-8 text-center"
                    >
                        No comments yet. Be the first to comment.
                    </li>
                </ul>
            </div>
        </PageWidth>

        <ConfirmDialog
            v-model:open="showDeleteConfirm"
            title="Delete Task"
            :description="`Are you sure you want to delete '${task.title}'? This action cannot be undone.`"
            :loading="isDeleting"
            @confirm="deleteTask"
        />
    </AppLayout>
</template>
