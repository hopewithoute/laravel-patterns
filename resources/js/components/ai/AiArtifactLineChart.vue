<script setup>
import { computed } from 'vue'

const props = defineProps({
    artifact: {
        type: Object,
        required: true,
    },
})

const chartWidth = 320
const chartHeight = 140
const chartPadding = 18

const points = computed(() => {
    const series = props.artifact.data?.series

    if (!Array.isArray(series)) {
        return []
    }

    return series
        .map((item, index) => {
            const label = item?.label ?? item?.x ?? item?.name ?? `Point ${index + 1}`
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

const plottedPoints = computed(() => {
    if (points.value.length === 0) {
        return []
    }

    const usableWidth = chartWidth - chartPadding * 2
    const usableHeight = chartHeight - chartPadding * 2

    return points.value.map((point, index) => {
        const x =
            points.value.length === 1
                ? chartWidth / 2
                : chartPadding + (usableWidth / (points.value.length - 1)) * index
        const y = chartHeight - chartPadding - (point.value / maxValue.value) * usableHeight

        return {
            ...point,
            x,
            y,
        }
    })
})

const polylinePoints = computed(() =>
    plottedPoints.value.map((point) => `${point.x},${point.y}`).join(' '),
)
</script>

<template>
    <div class="space-y-3">
        <div class="bg-muted/40 rounded-2xl px-3 py-3">
            <svg
                :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                class="h-40 w-full overflow-visible"
                fill="none"
                role="img"
                aria-label="Line chart"
            >
                <line
                    :x1="chartPadding"
                    :y1="chartHeight - chartPadding"
                    :x2="chartWidth - chartPadding"
                    :y2="chartHeight - chartPadding"
                    stroke="currentColor"
                    class="text-border"
                    stroke-width="1"
                />
                <polyline
                    v-if="polylinePoints"
                    :points="polylinePoints"
                    class="text-foreground"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2.5"
                />
                <circle
                    v-for="(point, index) in plottedPoints"
                    :key="`${point.label}-${index}`"
                    :cx="point.x"
                    :cy="point.y"
                    r="4"
                    class="fill-background text-foreground"
                    stroke="currentColor"
                    stroke-width="2"
                />
            </svg>
        </div>
        <div class="grid gap-2 sm:grid-cols-2">
            <div
                v-for="(point, index) in plottedPoints"
                :key="`${point.label}-${index}`"
                class="bg-background/70 rounded-2xl px-3 py-2"
            >
                <p class="text-foreground text-sm font-medium">{{ point.label }}</p>
                <p class="text-muted-foreground mt-1 text-xs">{{ point.value }}</p>
            </div>
        </div>
        <div class="text-muted-foreground flex justify-between text-[11px] uppercase tracking-[0.2em]">
            <span>{{ props.artifact.data?.xLabel || 'X Axis' }}</span>
            <span>{{ props.artifact.data?.yLabel || 'Y Axis' }}</span>
        </div>
    </div>
</template>
