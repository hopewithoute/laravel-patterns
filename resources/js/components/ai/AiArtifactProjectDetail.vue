<script setup>
import { computed } from 'vue'
import { CheckCircle2, CircleDashed, FolderKanban, Hash, ListChecks } from 'lucide-vue-next'
import Badge from '@/components/ui/Badge.vue'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'

const props = defineProps({
    artifact: {
        type: Object,
        required: true,
    },
})

const project = computed(() => props.artifact.data?.project || props.artifact.data || {})

const statusLabel = computed(() => (project.value.is_active ? 'Active' : 'Inactive'))
const statusTone = computed(() => (project.value.is_active ? 'emerald' : 'slate'))
const projectColor = computed(() => project.value.color || '#3B82F6')
const completion = computed(() => clamp(Number(project.value.completion_percentage) || 0, 0, 100))

const metricItems = computed(() => [
    {
        label: 'Tasks',
        value: formatNumber(project.value.tasks_count),
        icon: ListChecks,
    },
    {
        label: 'Completed',
        value: formatNumber(project.value.completed_tasks_count),
        icon: CheckCircle2,
    },
    {
        label: 'Progress',
        value: `${formatNumber(completion.value)}%`,
        icon: CircleDashed,
    },
])

const detailItems = computed(() => [
    {
        label: 'Project ID',
        value: project.value.project_id || project.value.id,
        mono: true,
    },
    {
        label: 'Description',
        value: project.value.description,
        wide: true,
    },
    {
        label: 'Color',
        value: project.value.color,
    },
])

function formatNumber(value) {
    const number = Number(value)

    return Number.isFinite(number) ? number.toLocaleString() : '0'
}

function formatValue(value) {
    if (value === null || value === undefined || value === '') {
        return '—'
    }

    return String(value)
}

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max)
}
</script>

<template>
    <Card class="border-border/50 bg-card/95 overflow-hidden rounded-2xl shadow-none">
        <CardHeader class="border-border/40 border-b p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex min-w-0 items-start gap-3.5">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-black/6 shadow-sm"
                        :style="{ backgroundColor: projectColor }"
                    >
                        <FolderKanban class="h-6 w-6 text-white" aria-hidden="true" />
                    </div>

                    <div class="min-w-0">
                        <CardTitle class="text-foreground text-lg leading-6 break-words">
                            {{ project.name || 'Untitled project' }}
                        </CardTitle>
                        <div
                            class="text-muted-foreground mt-2 flex min-w-0 items-center gap-2 text-xs"
                        >
                            <Hash class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                            <span class="truncate font-mono">
                                {{ project.project_id || project.id || '—' }}
                            </span>
                        </div>
                    </div>
                </div>

                <Badge variant="soft" :tone="statusTone" class="shrink-0">
                    {{ statusLabel }}
                </Badge>
            </div>
        </CardHeader>

        <CardContent class="space-y-4 p-4">
            <div v-if="project.description" class="bg-muted/35 rounded-xl px-3.5 py-3">
                <p class="text-muted-foreground text-sm leading-6">
                    {{ project.description }}
                </p>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between gap-3 text-xs">
                    <span class="text-muted-foreground">Completion</span>
                    <span class="text-foreground font-mono font-semibold">{{ completion }}%</span>
                </div>
                <div class="bg-muted h-2 overflow-hidden rounded-full">
                    <div
                        class="h-full rounded-full transition-all"
                        :style="{ width: `${completion}%`, backgroundColor: projectColor }"
                    />
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div
                    v-for="item in metricItems"
                    :key="item.label"
                    class="bg-background/75 rounded-xl border border-black/6 px-3 py-3"
                >
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-muted-foreground text-[11px] tracking-[0.16em] uppercase">
                            {{ item.label }}
                        </p>
                        <component :is="item.icon" class="text-muted-foreground h-4 w-4" />
                    </div>
                    <p class="text-foreground mt-2 text-xl font-semibold">
                        {{ item.value }}
                    </p>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div
                    v-for="item in detailItems"
                    :key="item.label"
                    class="bg-background/70 rounded-xl border border-black/6 px-3 py-3"
                    :class="{ 'sm:col-span-2': item.wide }"
                >
                    <p class="text-muted-foreground text-[11px] tracking-[0.16em] uppercase">
                        {{ item.label }}
                    </p>
                    <p
                        class="text-foreground mt-1 text-sm font-semibold break-words"
                        :class="{ 'font-mono text-xs': item.mono }"
                    >
                        {{ formatValue(item.value) }}
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
