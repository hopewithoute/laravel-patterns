<script setup>
import { cn } from '@/lib/utils'

defineProps({
    class: {
        type: String,
        default: '',
    },
})
</script>

<template>
    <div
        :class="
            cn(
                'border-border/40 bg-card text-card-foreground rounded-2xl border shadow-sm transition-all duration-200',
                $props.class,
            )
        "
    >
        <slot />
    </div>
</template>
