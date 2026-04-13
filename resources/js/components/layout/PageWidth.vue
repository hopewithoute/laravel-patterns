<script setup>
import { computed } from 'vue'

import { cn } from '@/lib/utils'

const props = defineProps({
    size: {
        type: String,
        default: 'wide',
    },
})

const widths = {
    wide: 'max-w-7xl',
    content: 'max-w-4xl',
    form: 'max-w-2xl',
}

const classes = computed(() => cn('w-full mx-auto', widths[props.size] ?? widths.wide))
</script>

<template>
    <div :class="classes">
        <slot />
    </div>
</template>
