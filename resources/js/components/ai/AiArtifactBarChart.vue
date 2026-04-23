<script setup>
import { computed } from 'vue'

const props = defineProps({
    artifact: {
        type: Object,
        required: true,
    },
})

const points = computed(() => {
    const series = props.artifact.data?.series

    if (!Array.isArray(series)) {
        return []
    }

    return series
        .map((item, index) => {
            const label = item?.label ?? item?.x ?? item?.name ?? `Item ${index + 1}`
            const rawValue = item?.value ?? item?.y
            const value = Number(rawValue)

            if (!Number.isFinite(value)) {
                return null
            }

            return {
                label: String(label),
                value,
            }
        })
        .filter(Boolean)
})

const maxValue = computed(() => Math.max(...points.value.map((point) => point.value), 1))
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-end gap-2 overflow-x-auto pb-1">
            <div
                v-for="(point, index) in points"
                :key="`${point.label}-${index}`"
                class="flex min-w-16 flex-1 flex-col items-center gap-2"
            >
                <span class="text-foreground text-xs font-semibold">{{ point.value }}</span>
                <div class="bg-muted/60 flex h-40 w-full items-end rounded-2xl px-2 py-2">
                    <div
                        class="from-foreground via-foreground/80 to-foreground/60 w-full rounded-xl bg-linear-to-t transition-all"
                        :style="{ height: `${Math.max((point.value / maxValue) * 100, 6)}%` }"
                    />
                </div>
                <span class="text-muted-foreground text-center text-xs leading-5">{{ point.label }}</span>
            </div>
        </div>
        <div class="text-muted-foreground flex justify-between text-[11px] uppercase tracking-[0.2em]">
            <span>{{ props.artifact.data?.xLabel || 'X Axis' }}</span>
            <span>{{ props.artifact.data?.yLabel || 'Y Axis' }}</span>
        </div>
    </div>
</template>
