<script setup>
import { cn } from '@/lib/utils'

const props = defineProps({
    class: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'text',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    modelValue: {
        type: [String, Number],
        default: '',
    },
})

const emit = defineEmits(['update:modelValue'])

const handleInput = (event) => {
    emit('update:modelValue', event.target.value)
}
</script>

<template>
    <input
        :type="type"
        :disabled="disabled"
        :value="modelValue"
        :class="
            cn(
                'border-border/50 bg-surface/50 text-foreground flex h-11 w-full rounded-xl border px-4 py-2.5 text-sm',
                'placeholder:text-muted-foreground/70',
                'focus:ring-primary/20 focus:border-primary/35 focus:bg-surface/60 focus:ring-2 focus:outline-none',
                'disabled:cursor-not-allowed disabled:opacity-50',
                'transition-all duration-200',
                props.class,
            )
        "
        @input="handleInput"
    />
</template>
