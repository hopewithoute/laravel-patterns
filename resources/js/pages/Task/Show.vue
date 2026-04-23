<script setup>
import { ref } from 'vue'
import { Link, router, Head } from '@inertiajs/vue3'
import PageHeader from '@/components/layout/PageHeader.vue'
import PageWidth from '@/components/layout/PageWidth.vue'
import Button from '@/components/ui/Button.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import TaskDetailContent from '@/components/task/TaskDetailContent.vue'

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
</script>

<template>
    <Head :title="task.title" />
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

            <!-- Task Content -->
            <div class="border-border bg-card rounded-2xl border p-6">
                <TaskDetailContent :task="task" />
            </div>
        </PageWidth>

        <ConfirmDialog
            v-model:open="showDeleteConfirm"
            title="Delete Task"
            :description="`Are you sure you want to delete '${task.title}'? This action cannot be undone.`"
            :loading="isDeleting"
            @confirm="deleteTask"
        />
</template>
