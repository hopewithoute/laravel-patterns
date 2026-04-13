<script setup>
import { cn } from '@/lib/utils'

defineProps({
    class: { type: String, default: '' },
})
</script>

<template>
    <div :class="cn('bg-border mx-2 my-1 h-px', $attrs.class)" />
</template>
