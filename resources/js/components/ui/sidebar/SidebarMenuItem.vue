<script setup>
import { cn } from '@/lib/utils'

defineProps({
    class: { type: String, default: '' },
})
</script>

<template>
    <li :class="cn('group/menu-item relative', $attrs.class)">
        <slot />
    </li>
</template>
