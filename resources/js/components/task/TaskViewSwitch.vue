<script setup>
import Button from '@/components/ui/Button.vue'

defineProps({
    modelValue: {
        type: String,
        required: true,
    },
})

const emit = defineEmits(['update:modelValue'])

const modes = [
    { value: 'quicklist', label: 'Quicklist' },
    { value: 'kanban', label: 'Kanban' },
]
</script>

<template>
    <div class="border-border/50 bg-card/70 inline-flex items-center gap-1 rounded-2xl border p-1">
        <Button
            v-for="mode in modes"
            :key="mode.value"
            variant="ghost"
            size="sm"
            :class="
                modelValue === mode.value
                    ? 'bg-cyan-500 text-black hover:bg-cyan-400'
                    : 'text-muted-foreground'
            "
            @click="emit('update:modelValue', mode.value)"
        >
            {{ mode.label }}
        </Button>
    </div>
</template>
