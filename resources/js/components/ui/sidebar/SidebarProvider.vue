<script setup>
import { provide, ref, computed } from 'vue'

const props = defineProps({
    defaultOpen: { type: Boolean, default: true },
})

const isOpen = ref(props.defaultOpen)

const toggle = () => {
    isOpen.value = !isOpen.value
}

const open = () => {
    isOpen.value = true
}

const close = () => {
    isOpen.value = false
}

provide('sidebar', {
    isOpen: computed(() => isOpen.value),
    toggle,
    open,
    close,
})
</script>

<template>
    <div class="flex min-h-screen w-full">
        <slot :is-open="isOpen" :toggle="toggle" />
    </div>
</template>
