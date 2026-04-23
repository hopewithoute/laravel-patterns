<script setup>
import { computed } from 'vue'

const props = defineProps({
    artifact: {
        type: Object,
        required: true,
    },
})

const entries = computed(() => Object.entries(props.artifact.data || {}))

function formatValue(value) {
    if (value === null || value === undefined || value === '') {
        return '—'
    }

    return typeof value === 'object' ? JSON.stringify(value) : String(value)
}
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2">
        <div
            v-for="[key, value] in entries"
            :key="key"
            class="bg-background/70 rounded-2xl px-3 py-3"
        >
            <p class="text-muted-foreground text-[11px] tracking-[0.16em] uppercase">
                {{ key.replaceAll('_', ' ') }}
            </p>
            <p class="text-foreground mt-1 text-sm font-semibold">
                {{ formatValue(value) }}
            </p>
        </div>
    </div>
</template>
