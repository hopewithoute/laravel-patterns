<script setup>
import { inject } from 'vue'
import { cn } from '@/lib/utils'

defineProps({
    class: { type: String, default: '' },
})

const sidebar = inject('sidebar')
</script>

<template>
    <button
        :class="
            cn(
                'text-muted-foreground hover:bg-muted hover:text-foreground inline-flex items-center justify-center rounded-md p-2',
                'focus-visible:ring-ring outline-none focus-visible:ring-2',
                $attrs.class,
            )
        "
        @click="sidebar?.toggle"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <rect width="18" height="18" x="3" y="3" rx="2" />
            <path d="M9 3v18" />
        </svg>
    </button>
</template>
