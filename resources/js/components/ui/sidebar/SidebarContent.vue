<script setup>
import { cn } from '@/lib/utils'

defineProps({
    class: { type: String, default: '' },
})
</script>

<template>
    <div :class="cn('flex h-full flex-col', $attrs.class)">
        <slot />
    </div>
</template>
