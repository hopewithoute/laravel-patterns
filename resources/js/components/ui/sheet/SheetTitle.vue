<script setup>
import { DialogTitle } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: null, required: false },
})
</script>

<template>
    <DialogTitle v-bind="props" :class="cn('text-foreground text-lg font-semibold', props.class)">
        <slot />
    </DialogTitle>
</template>
