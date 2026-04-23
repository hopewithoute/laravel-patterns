<script setup>
const props = defineProps({
    artifact: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2">
        <div class="bg-background/70 rounded-2xl px-3 py-3">
            <p class="text-muted-foreground text-[11px] tracking-[0.16em] uppercase">Title</p>
            <p class="text-foreground mt-1 text-sm font-semibold">
                {{ props.artifact.data.title || 'Untitled task' }}
            </p>
        </div>
        <div class="bg-background/70 rounded-2xl px-3 py-3">
            <p class="text-muted-foreground text-[11px] tracking-[0.16em] uppercase">Status</p>
            <p class="text-foreground mt-1 text-sm font-semibold">
                {{ props.artifact.data.status || 'Unknown' }}
            </p>
        </div>
        <div class="bg-background/70 rounded-2xl px-3 py-3">
            <p class="text-muted-foreground text-[11px] tracking-[0.16em] uppercase">Task ID</p>
            <p class="text-foreground mt-1 break-all font-mono text-xs">
                {{ props.artifact.data.task_id || '—' }}
            </p>
        </div>
        <div class="bg-background/70 rounded-2xl px-3 py-3">
            <p class="text-muted-foreground text-[11px] tracking-[0.16em] uppercase">Project ID</p>
            <p class="text-foreground mt-1 break-all font-mono text-xs">
                {{ props.artifact.data.project_id || '—' }}
            </p>
        </div>
    </div>
</template>
