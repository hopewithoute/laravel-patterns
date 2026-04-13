<script setup>
import { cn } from '@/lib/utils'

defineProps({
    class: { type: String, default: '' },
})
</script>

<template>
    <div :class="cn('text-muted-foreground px-2 py-1 text-xs font-medium', $attrs.class)">
        <slot />
    </div>
</template>
