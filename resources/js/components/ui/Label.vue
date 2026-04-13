<script setup>
import { cn } from '@/lib/utils'

defineProps({
    for: {
        type: String,
        default: '',
    },
    class: {
        type: String,
        default: '',
    },
})
</script>

<template>
    <label
        :for="$props.for"
        :class="
            cn(
                'text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
                $props.class,
            )
        "
    >
        <slot />
    </label>
</template>
