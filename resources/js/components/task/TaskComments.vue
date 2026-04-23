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
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-500/10 ring-1 ring-amber-500/20 shadow-sm shadow-amber-500/5">
                <MessageSquare class="h-4 w-4 text-amber-500" />
            </div>
            <h3 class="font-display text-sm font-bold tracking-[0.15em] text-foreground uppercase pt-0.5">
                Activity & Comments <span class="ml-1 text-muted-foreground/60 font-mono text-[10px]">({{ comments.length }})</span>
            </h3>
        </div>

        <!-- Comment Form -->
        <form @submit.prevent="submitComment" class="group relative space-y-4">
            <div class="relative">
                <textarea
                    v-model="commentForm.content"
                    rows="3"
                    placeholder="Add a comment..."
                    class="w-full rounded-2xl border border-border/50 bg-surface-elevated/20 px-5 py-4 text-sm text-foreground shadow-xs transition-all focus:border-amber-500/40 focus:bg-surface-elevated/40 focus:ring-4 focus:ring-amber-500/5 placeholder:text-muted-foreground/40"
                />
                <!-- Form Interaction Glow -->
                <div class="absolute inset-0 -z-10 rounded-2xl bg-linear-to-br from-amber-500/5 to-transparent opacity-0 transition-opacity group-focus-within:opacity-100"></div>
            </div>
            
            <div class="flex justify-end">
                <Button
                    type="submit"
                    size="sm"
                    :disabled="commentForm.processing || !commentForm.content.trim()"
                    class="gap-2.5 rounded-xl bg-linear-to-r from-amber-500 to-orange-500 font-bold text-black shadow-lg shadow-amber-500/15 hover:shadow-amber-500/25 hover:-translate-y-0.5 transition-all active:translate-y-0"
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
                class="group flex gap-4 animate-scale-in"
            >
                <div class="relative shrink-0">
                    <Avatar class="h-9 w-9 ring-2 ring-border/40 transition-all group-hover:ring-amber-500/40 shadow-sm">
                        <AvatarImage v-if="comment.user?.avatar" :src="comment.user.avatar" />
                        <AvatarFallback class="bg-surface-elevated text-[10px] font-black text-amber-500">
                            {{ comment.user?.name?.charAt(0).toUpperCase() || '?' }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="absolute -bottom-1 -right-1 h-3.5 w-3.5 rounded-full border-2 border-background bg-emerald-500 shadow-sm"></div>
                </div>

                <div class="flex-1 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[13px] font-bold text-foreground tracking-tight">
                            {{ comment.user?.name || 'Unknown User' }}
                        </span>
                        <span class="font-mono text-[9px] font-medium tracking-tighter text-muted-foreground/50 uppercase">
                            {{ formatDate(comment.created_at) }}
                        </span>
                    </div>
                    <div class="relative rounded-2xl bg-surface-elevated/40 border border-border/40 px-5 py-3.5 text-sm leading-relaxed text-foreground/80 group-hover:bg-surface-elevated/60 group-hover:border-border/60 transition-all shadow-2xs">
                        {{ comment.content }}
                        <!-- Decorative corner element -->
                        <div class="absolute top-0 right-0 h-2 w-2 border-t border-r border-border/0 transition-colors group-hover:border-border/40"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div 
            v-else 
            class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-border/40 bg-surface/10 py-12 text-center"
        >
            <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-surface-elevated/40 shadow-inner ring-1 ring-border/20">
                <MessageSquare class="h-6 w-6 text-muted-foreground/30" />
            </div>
            <h4 class="text-sm font-bold text-foreground tracking-tight">No Comments</h4>
            <p class="text-[11px] text-muted-foreground/60 mt-1 max-w-50 leading-relaxed uppercase tracking-wider font-medium">No discussions recorded for this task yet.</p>
        </div>
    </div>
</template>
