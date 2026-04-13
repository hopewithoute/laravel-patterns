<script setup>
import { AvatarFallback } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps({
    delayMs: { type: Number, required: false },
    asChild: { type: Boolean, required: false },
    as: { type: null, required: false },
    class: { type: null, required: false },
})
</script>

<template>
    <AvatarFallback
        v-bind="props"
        :class="
            cn('bg-muted flex h-full w-full items-center justify-center rounded-full', props.class)
        "
    >
        <slot />
    </AvatarFallback>
</template>
