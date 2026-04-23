<script setup>
import { provide, ref, computed } from 'vue'

const SIDEBAR_STATE_KEY = 'sidebar:state'

const props = defineProps({
    defaultOpen: { type: Boolean, default: true },
})

// Initialize from localStorage if available
const getStoredState = () => {
    const stored = localStorage.getItem(SIDEBAR_STATE_KEY)
    if (stored === null) return props.defaultOpen
    return stored === 'true'
}

const isOpen = ref(getStoredState())

const toggle = () => {
    isOpen.value = !isOpen.value
    localStorage.setItem(SIDEBAR_STATE_KEY, isOpen.value)
}

const open = () => {
    isOpen.value = true
    localStorage.setItem(SIDEBAR_STATE_KEY, 'true')
}

const close = () => {
    isOpen.value = false
    localStorage.setItem(SIDEBAR_STATE_KEY, 'false')
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
