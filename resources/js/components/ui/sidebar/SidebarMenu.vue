<script setup>
import { cn } from '@/lib/utils'

defineProps({
    class: { type: String, default: '' },
})
</script>

<template>
    <ul :class="cn('flex w-full min-w-0 flex-col gap-1', $attrs.class)">
        <slot />
    </ul>
</template>
