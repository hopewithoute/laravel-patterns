<script setup>
import { cn } from '@/lib/utils'

defineProps({
    class: { type: String, default: '' },
})
</script>

<template>
    <main :class="cn('flex-1 overflow-auto', $attrs.class)">
        <slot />
    </main>
</template>
