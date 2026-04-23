<script setup>
import { computed } from 'vue'

const props = defineProps({
    artifact: {
        type: Object,
        required: true,
    },
})

const items = computed(() => props.artifact.data.items || [])

function formatValue(item) {
    const value = item?.value

    if (value === null || value === undefined || value === '') {
        return '—'
    }

    return typeof value === 'number' ? value.toLocaleString() : String(value)
}
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <div
            v-for="(item, index) in items"
            :key="`${item.label || 'metric'}-${index}`"
            class="from-background/90 to-background/60 rounded-2xl bg-linear-to-br px-4 py-4"
        >
            <p class="text-muted-foreground text-[11px] tracking-[0.16em] uppercase">
                {{ item.label || 'Metric' }}
            </p>
            <p class="text-foreground mt-2 text-2xl font-semibold tracking-tight">
                {{ formatValue(item) }}
            </p>
            <p v-if="item.caption" class="text-muted-foreground mt-2 text-xs leading-5">
                {{ item.caption }}
            </p>
        </div>
    </div>
</template>
