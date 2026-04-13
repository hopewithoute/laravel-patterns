<script setup>
import { onMounted } from 'vue'
import { useTheme } from '@/lib/theme'

const { theme, toggleTheme, initTheme } = useTheme()

onMounted(() => {
    initTheme()
})
</script>

<template>
    <button
        class="bg-surface/50 hover:bg-surface border-border/50 hover:border-border group relative flex h-9 w-9 items-center justify-center rounded-lg border transition-all duration-200"
        :title="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
        @click="toggleTheme"
    >
        <!-- Sun Icon (shown in dark mode) -->
        <svg
            v-if="theme === 'dark'"
            xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="text-amber-400 transition-colors duration-200 group-hover:text-amber-300"
        >
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2" />
            <path d="M12 20v2" />
            <path d="m4.93 4.93 1.41 1.41" />
            <path d="m17.66 17.66 1.41 1.41" />
            <path d="M2 12h2" />
            <path d="M20 12h2" />
            <path d="m6.34 17.66-1.41 1.41" />
            <path d="m19.07 4.93-1.41 1.41" />
        </svg>

        <!-- Moon Icon (shown in light mode) -->
        <svg
            v-else
            xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="text-slate-600 transition-colors duration-200 group-hover:text-slate-800"
        >
            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
        </svg>
    </button>
</template>
