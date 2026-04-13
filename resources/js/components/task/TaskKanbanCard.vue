<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { GripVertical, Trash2 } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import { TASK_PRIORITY_TONES, TASK_STATUS_TONES } from '@/lib/badges'

const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
})

const isDeleting = ref(false)
const showDeleteConfirm = ref(false)

const deleteTask = () => {
    isDeleting.value = true
    router.delete(`/tasks/${props.task.id}`, {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false
            showDeleteConfirm.value = false
        },
    })
}

const navigateToTask = (e) => {
    // If we're clicking the handle or delete button, don't navigate
    if (e.target.closest('.drag-handle') || e.target.closest('.delete-button')) {
        return
    }

    router.visit(`/tasks/${props.task.id}`)
}
</script>

<template>
    <div
        class="draggable-item group relative block cursor-pointer transition-all duration-200"
        :data-id="task.id"
        :data-task-id="task.id"
        @click="navigateToTask"
    >
        <!-- Delete Button (Top Right, Hidden by default, shown on group hover) -->
        <div
            class="absolute top-2 right-2 z-20 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
        >
            <Button
                variant="ghost"
                size="icon-sm"
                class="delete-button h-7 w-7 rounded-lg text-slate-400 hover:bg-rose-500/10 hover:text-rose-500"
                @click.stop="showDeleteConfirm = true"
            >
                <Trash2 class="h-3.5 w-3.5" />
            </Button>
        </div>

        <Card
            class="border-border/50 bg-card/95 p-3 transition-all duration-200 hover:border-slate-400/50 hover:shadow-md hover:shadow-black/5 dark:hover:border-slate-500/50"
        >
            <div class="flex gap-2">
                <div
                    class="drag-handle text-muted-foreground/40 hover:bg-surface-elevated hover:text-muted-foreground/70 mt-0.5 -ml-1 flex h-6 w-4 cursor-grab items-center justify-center rounded transition-colors active:cursor-grabbing"
                    @click.stop
                >
                    <GripVertical class="h-3.5 w-3.5" />
                </div>
                <div class="flex-1 space-y-3 overflow-hidden">
                    <div class="space-y-2">
                        <div class="flex items-start justify-between gap-3">
                            <h4
                                class="text-foreground line-clamp-2 text-sm font-semibold transition-colors duration-200"
                            >
                                {{ task.title }}
                            </h4>
                            <span
                                :class="[
                                    'mt-1 h-2.5 w-2.5 shrink-0 rounded-full',
                                    task.status === 'Done'
                                        ? 'bg-emerald-500'
                                        : task.status === 'In Progress'
                                          ? 'bg-cyan-500'
                                          : task.status === 'Review'
                                            ? 'bg-amber-500'
                                            : 'bg-slate-500',
                                ]"
                            />
                        </div>

                        <p
                            v-if="task.description"
                            class="dark:text-muted-foreground line-clamp-2 text-xs leading-5 text-slate-600"
                        >
                            {{ task.description }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Badge :tone="TASK_STATUS_TONES[task.status] || TASK_STATUS_TONES.Todo">
                            {{ task.status }}
                        </Badge>
                        <Badge
                            :tone="TASK_PRIORITY_TONES[task.priority] || TASK_PRIORITY_TONES.Medium"
                        >
                            {{ task.priority }}
                        </Badge>
                    </div>

                    <div class="dark:text-muted-foreground space-y-2 text-[11px] text-slate-600">
                        <div v-if="task.project" class="flex items-center gap-2">
                            <span
                                class="h-2.5 w-2.5 rounded-full"
                                :style="{ backgroundColor: task.project.color || '#64748b' }"
                            />
                            <span class="truncate">{{ task.project.name }}</span>
                        </div>

                        <div v-if="task.assignee" class="flex items-center gap-2">
                            <span
                                class="bg-surface text-foreground flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-semibold"
                            >
                                {{ task.assignee.name?.charAt(0)?.toUpperCase() }}
                            </span>
                            <span class="truncate">{{ task.assignee.name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </Card>

        <ConfirmDialog
            v-model:open="showDeleteConfirm"
            title="Delete Task"
            :description="`Are you sure you want to delete '${task.title}'? This action cannot be undone.`"
            :loading="isDeleting"
            @confirm="deleteTask"
        />
    </div>
</template>
