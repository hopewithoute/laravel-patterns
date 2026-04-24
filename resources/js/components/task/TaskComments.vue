<script setup>
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/ui/Button.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { MessageSquare, Send } from 'lucide-vue-next'

const props = defineProps({
    taskId: {
        type: [String, Number],
        required: true,
    },
    comments: {
        type: Array,
        default: () => [],
    },
})

const commentForm = useForm({
    content: '',
    task_id: props.taskId,
})

const submitComment = () => {
    commentForm.post(`/tasks/${props.taskId}/comments`, {
        preserveScroll: true,
        onSuccess: () => commentForm.reset(),
    })
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    })
}
</script>

<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <div
                class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-500/10 shadow-sm ring-1 shadow-amber-500/5 ring-amber-500/20"
            >
                <MessageSquare class="h-4 w-4 text-amber-500" />
            </div>
            <h3
                class="font-display text-foreground pt-0.5 text-sm font-bold tracking-[0.15em] uppercase"
            >
                Activity & Comments
                <span class="text-muted-foreground/60 ml-1 font-mono text-[10px]"
                    >({{ comments.length }})</span
                >
            </h3>
        </div>

        <!-- Comment Form -->
        <form class="group relative space-y-4" @submit.prevent="submitComment">
            <div class="relative">
                <textarea
                    v-model="commentForm.content"
                    rows="3"
                    placeholder="Add a comment..."
                    class="border-border/50 bg-surface-elevated/20 text-foreground focus:bg-surface-elevated/40 placeholder:text-muted-foreground/40 w-full rounded-2xl border px-5 py-4 text-sm shadow-xs transition-all focus:border-amber-500/40 focus:ring-4 focus:ring-amber-500/5"
                />
                <!-- Form Interaction Glow -->
                <div
                    class="absolute inset-0 -z-10 rounded-2xl bg-linear-to-br from-amber-500/5 to-transparent opacity-0 transition-opacity group-focus-within:opacity-100"
                ></div>
            </div>

            <div class="flex justify-end">
                <Button
                    type="submit"
                    size="sm"
                    :disabled="commentForm.processing || !commentForm.content.trim()"
                    class="gap-2.5 rounded-xl bg-linear-to-r from-amber-500 to-orange-500 font-bold text-black shadow-lg shadow-amber-500/15 transition-all hover:-translate-y-0.5 hover:shadow-amber-500/25 active:translate-y-0"
                >
                    <Send class="h-3.5 w-3.5" />
                    Post Comment
                </Button>
            </div>
        </form>

        <!-- Comments List -->
        <div v-if="comments.length > 0" class="space-y-5">
            <div
                v-for="comment in comments"
                :key="comment.id"
                class="group animate-scale-in flex gap-4"
            >
                <div class="relative shrink-0">
                    <Avatar
                        class="ring-border/40 h-9 w-9 shadow-sm ring-2 transition-all group-hover:ring-amber-500/40"
                    >
                        <AvatarImage v-if="comment.user?.avatar" :src="comment.user.avatar" />
                        <AvatarFallback
                            class="bg-surface-elevated text-[10px] font-black text-amber-500"
                        >
                            {{ comment.user?.name?.charAt(0).toUpperCase() || '?' }}
                        </AvatarFallback>
                    </Avatar>
                    <div
                        class="border-background absolute -right-1 -bottom-1 h-3.5 w-3.5 rounded-full border-2 bg-emerald-500 shadow-sm"
                    ></div>
                </div>

                <div class="flex-1 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-foreground text-[13px] font-bold tracking-tight">
                            {{ comment.user?.name || 'Unknown User' }}
                        </span>
                        <span
                            class="text-muted-foreground/50 font-mono text-[9px] font-medium tracking-tighter uppercase"
                        >
                            {{ formatDate(comment.created_at) }}
                        </span>
                    </div>
                    <div
                        class="bg-surface-elevated/40 border-border/40 text-foreground/80 group-hover:bg-surface-elevated/60 group-hover:border-border/60 relative rounded-2xl border px-5 py-3.5 text-sm leading-relaxed shadow-2xs transition-all"
                    >
                        {{ comment.content }}
                        <!-- Decorative corner element -->
                        <div
                            class="border-border/0 group-hover:border-border/40 absolute top-0 right-0 h-2 w-2 border-t border-r transition-colors"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="border-border/40 bg-surface/10 flex flex-col items-center justify-center rounded-3xl border border-dashed py-12 text-center"
        >
            <div
                class="bg-surface-elevated/40 ring-border/20 mb-5 flex h-14 w-14 items-center justify-center rounded-2xl shadow-inner ring-1"
            >
                <MessageSquare class="text-muted-foreground/30 h-6 w-6" />
            </div>
            <h4 class="text-foreground text-sm font-bold tracking-tight">No Comments</h4>
            <p
                class="text-muted-foreground/60 mt-1 max-w-50 text-[11px] leading-relaxed font-medium tracking-wider uppercase"
            >
                No discussions recorded for this task yet.
            </p>
        </div>
    </div>
</template>
