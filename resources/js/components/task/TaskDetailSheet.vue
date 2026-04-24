<script setup>
import { computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import TaskDetailContent from '@/components/task/TaskDetailContent.vue'
import Button from '@/components/ui/Button.vue'
import { Loader2, ExternalLink, X } from 'lucide-vue-next'

const page = usePage()
const task = computed(() => page.props.task_detail)

const lastTaskId = ref(null)
const isLoading = ref(false)

// Determine if sheet should be open based on URL query
const isOpen = computed({
    get: () => {
        const searchParams = new URLSearchParams(page.url.split('?')[1] || '')
        return !!searchParams.get('task')
    },
    set: (value) => {
        if (!value) close()
    },
})

const getTaskIdFromUrl = () => {
    const searchParams = new URLSearchParams(page.url.split('?')[1] || '')
    return searchParams.get('task')
}

const close = () => {
    const url = new URL(window.location.href)
    url.searchParams.delete('task')
    router.get(
        url.toString(),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    )
}

function fetchTask(id) {
    if (!id) return
    lastTaskId.value = id
    isLoading.value = true

    router.reload({
        data: { task_detail_id: id },
        only: ['task_detail'],
        onFinish: () => {
            isLoading.value = false
        },
    })
}

// Watch for URL changes to trigger data fetching
watch(
    () => getTaskIdFromUrl(),
    (newId) => {
        if (newId && newId !== lastTaskId.value) {
            fetchTask(newId)
        }
    },
    { immediate: true },
)
</script>

<template>
    <Sheet v-model:open="isOpen">
        <SheetContent
            :show-close-button="false"
            class="glass-overlay border-border/50 flex w-full flex-col overflow-hidden border-l p-0 sm:max-w-md md:max-w-lg lg:max-w-xl"
        >
            <!-- Decorative background orbs -->
            <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
                <div class="orb-amber -top-24 -right-24 opacity-20"></div>
                <div class="orb-cyan -bottom-48 -left-48 opacity-10"></div>
            </div>

            <!-- Header -->
            <SheetHeader
                class="border-border/50 bg-background/40 relative border-b px-6 py-6 backdrop-blur-xl"
            >
                <div class="flex items-start justify-between">
                    <div class="space-y-1.5 pr-6">
                        <div class="flex items-center gap-2">
                            <div
                                v-if="task && task.project"
                                class="text-muted-foreground/80 flex items-center gap-1.5 font-mono text-[9px] font-bold tracking-[0.2em] uppercase"
                            >
                                <span
                                    class="h-1 w-1 animate-pulse rounded-full"
                                    :style="{ backgroundColor: task.project.color || '#f59e0b' }"
                                ></span>
                                {{ task.project.name }}
                            </div>
                        </div>

                        <SheetTitle
                            v-if="task"
                            class="font-display text-foreground text-2xl leading-tight font-bold tracking-tight"
                        >
                            {{ task.title }}
                        </SheetTitle>
                        <SheetTitle
                            v-else-if="isLoading"
                            class="font-display text-muted-foreground/60 animate-pulse text-2xl font-medium"
                        >
                            Loading Detail...
                        </SheetTitle>
                        <SheetTitle v-else class="font-display text-foreground text-2xl font-bold">
                            Task Detail
                        </SheetTitle>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <Button
                            v-if="task"
                            variant="surface"
                            size="icon-sm"
                            as-child
                            class="border-border/40 bg-surface/40 hover:bg-surface/60 h-9 w-9 rounded-xl shadow-sm backdrop-blur-sm transition-all hover:scale-105 active:scale-95"
                            title="Open in new tab"
                        >
                            <a :href="`/tasks/${task.id}`" target="_blank">
                                <ExternalLink class="h-3.5 w-3.5" />
                            </a>
                        </Button>

                        <Button
                            variant="surface"
                            size="icon-sm"
                            class="border-border/40 bg-surface/40 hover:bg-surface/60 h-9 w-9 rounded-xl shadow-sm backdrop-blur-sm transition-all hover:scale-105 active:scale-95"
                            title="Close"
                            @click="close"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </SheetHeader>

            <!-- Content Area -->
            <div class="custom-scrollbar flex-1 overflow-y-auto px-6 py-8">
                <div
                    v-if="isLoading"
                    class="flex flex-col items-center justify-center py-24 text-center"
                >
                    <div class="relative mb-6">
                        <div
                            class="absolute inset-0 animate-ping rounded-full bg-amber-500/20"
                        ></div>
                        <Loader2 class="relative z-10 h-10 w-10 animate-spin text-amber-500" />
                    </div>
                    <p
                        class="text-muted-foreground font-mono text-[10px] font-bold tracking-[0.3em] uppercase"
                    >
                        Accessing Task Data...
                    </p>
                </div>

                <div v-else-if="task" class="pb-24">
                    <TaskDetailContent :task="task" />
                </div>

                <div v-else class="flex flex-col items-center justify-center py-24 text-center">
                    <div
                        class="bg-surface-elevated/50 mb-6 flex h-16 w-16 items-center justify-center rounded-2xl shadow-inner"
                    >
                        <X class="text-muted-foreground/40 h-8 w-8" />
                    </div>
                    <h3 class="font-display text-foreground text-xl font-bold">Task Not Found</h3>
                    <p class="text-muted-foreground mt-2 max-w-60 text-sm leading-relaxed">
                        The requested task record could not be found in the current datastream.
                    </p>
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: hsl(var(--border) / 0.5);
    border-radius: 10px;
}
</style>
