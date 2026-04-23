<script setup>
import { DialogDescription } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps({
    class: { type: null, required: false },
})
</script>

<template>
    <DialogDescription
        v-bind="props"
        :class="cn('text-muted-foreground text-sm', props.class)"
    >
        <slot />
    </DialogDescription>
</template>
