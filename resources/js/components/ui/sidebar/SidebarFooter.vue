<script setup>
import { cn } from '@/lib/utils'

defineProps({
    class: { type: String, default: '' },
})
</script>

<template>
    <div :class="cn('mt-auto p-2', $attrs.class)">
        <slot />
    </div>
</template>
