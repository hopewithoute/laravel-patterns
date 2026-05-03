<script setup>
import { computed } from 'vue'
import { FolderKanban, ListChecks } from 'lucide-vue-next'
import Badge from '@/components/ui/Badge.vue'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'

const props = defineProps({
    artifact: {
        type: Object,
        required: true,
    },
})

const columns = computed(() => {
    const artifactColumns = props.artifact.data?.columns

    return Array.isArray(artifactColumns) && artifactColumns.length > 0
        ? artifactColumns
        : ['name', 'is_active', 'tasks_count', 'completed_tasks_count', 'completion_percentage']
})

const rows = computed(() => {
    const artifactRows = props.artifact.data?.rows

    if (Array.isArray(artifactRows)) {
        return artifactRows
    }

    const projects = props.artifact.data?.projects

    return Array.isArray(projects) ? projects : []
})

const normalizedRows = computed(() => rows.value.map((row) => normalizeRow(row)))

const summary = computed(() => {
    const active = normalizedRows.value.filter((row) => Boolean(row.is_active)).length
    const tasks = normalizedRows.value.reduce(
        (total, row) => total + numberValue(row.tasks_count),
        0,
    )
    const completed = normalizedRows.value.reduce(
        (total, row) => total + numberValue(row.completed_tasks_count),
        0,
    )

    return {
        total: normalizedRows.value.length,
        active,
        tasks,
        completed,
    }
})

function normalizeRow(row) {
    if (Array.isArray(row)) {
        return Object.fromEntries(columns.value.map((column, index) => [column, row[index]]))
    }

    return row !== null && typeof row === 'object' ? row : {}
}

function columnLabel(column) {
    return String(column).replaceAll('_', ' ')
}

function cellValue(row, column) {
    const value = row[column]

    if (column === 'is_active') {
        return value ? 'Active' : 'Inactive'
    }

    if (
        column === 'completion_percentage' &&
        value !== null &&
        value !== undefined &&
        value !== ''
    ) {
        return `${value}%`
    }

    if (value === null || value === undefined || value === '') {
        return '—'
    }

    return String(value)
}

function projectColor(row) {
    return row.color || '#3B82F6'
}

function numberValue(value) {
    const number = Number(value)

    return Number.isFinite(number) ? number : 0
}

function completionValue(row) {
    return Math.min(Math.max(numberValue(row.completion_percentage), 0), 100)
}
</script>

<template>
    <Card class="border-border/50 bg-card/95 overflow-hidden rounded-2xl shadow-none">
        <CardContent class="space-y-4 p-4">
            <div class="grid gap-3 sm:grid-cols-4">
                <div class="bg-background/75 rounded-xl border border-black/6 px-3 py-3">
                    <p class="text-muted-foreground text-[11px] tracking-[0.16em] uppercase">
                        Projects
                    </p>
                    <p class="text-foreground mt-1 text-xl font-semibold">{{ summary.total }}</p>
                </div>
                <div class="bg-background/75 rounded-xl border border-black/6 px-3 py-3">
                    <p class="text-muted-foreground text-[11px] tracking-[0.16em] uppercase">
                        Active
                    </p>
                    <p class="text-foreground mt-1 text-xl font-semibold">{{ summary.active }}</p>
                </div>
                <div class="bg-background/75 rounded-xl border border-black/6 px-3 py-3">
                    <p class="text-muted-foreground text-[11px] tracking-[0.16em] uppercase">
                        Tasks
                    </p>
                    <p class="text-foreground mt-1 text-xl font-semibold">{{ summary.tasks }}</p>
                </div>
                <div class="bg-background/75 rounded-xl border border-black/6 px-3 py-3">
                    <p class="text-muted-foreground text-[11px] tracking-[0.16em] uppercase">
                        Done
                    </p>
                    <p class="text-foreground mt-1 text-xl font-semibold">
                        {{ summary.completed }}
                    </p>
                </div>
            </div>

            <div v-if="normalizedRows.length === 0" class="bg-background/70 rounded-xl px-4 py-6">
                <div class="flex items-center gap-3">
                    <div
                        class="bg-muted text-muted-foreground flex h-10 w-10 items-center justify-center rounded-xl"
                    >
                        <FolderKanban class="h-5 w-5" aria-hidden="true" />
                    </div>
                    <p class="text-muted-foreground text-sm">No projects found.</p>
                </div>
            </div>

            <div v-else class="hidden overflow-hidden rounded-xl border border-black/6 md:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto text-left text-sm">
                        <thead class="bg-muted/40">
                            <tr>
                                <th
                                    v-for="column in columns"
                                    :key="column"
                                    class="text-muted-foreground px-3 py-2 font-semibold whitespace-nowrap capitalize"
                                >
                                    {{ columnLabel(column) }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, rowIndex) in normalizedRows"
                                :key="row.project_id || row.id || rowIndex"
                                class="hover:bg-muted/25 border-t border-black/6 transition-colors"
                            >
                                <td
                                    v-for="column in columns"
                                    :key="`${rowIndex}-${column}`"
                                    class="text-foreground px-3 py-3 align-middle whitespace-nowrap"
                                >
                                    <div
                                        v-if="column === 'name'"
                                        class="flex min-w-56 items-center gap-2.5"
                                    >
                                        <span
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white"
                                            :style="{ backgroundColor: projectColor(row) }"
                                        >
                                            <FolderKanban class="h-4 w-4" aria-hidden="true" />
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold whitespace-normal">
                                                {{ cellValue(row, column) }}
                                            </p>
                                            <p class="text-muted-foreground font-mono text-[11px]">
                                                {{ row.project_id || row.id || '—' }}
                                            </p>
                                        </div>
                                    </div>

                                    <Badge
                                        v-else-if="column === 'is_active'"
                                        variant="compact"
                                        :tone="row.is_active ? 'emerald' : 'slate'"
                                    >
                                        {{ cellValue(row, column) }}
                                    </Badge>

                                    <div
                                        v-else-if="column === 'completion_percentage'"
                                        class="min-w-36 space-y-1.5"
                                    >
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="font-mono text-xs">
                                                {{ cellValue(row, column) }}
                                            </span>
                                            <ListChecks class="text-muted-foreground h-3.5 w-3.5" />
                                        </div>
                                        <div class="bg-muted h-1.5 overflow-hidden rounded-full">
                                            <div
                                                class="h-full rounded-full"
                                                :style="{
                                                    width: `${completionValue(row)}%`,
                                                    backgroundColor: projectColor(row),
                                                }"
                                            />
                                        </div>
                                    </div>

                                    <div
                                        v-else-if="column === 'color'"
                                        class="flex items-center gap-2"
                                    >
                                        <span
                                            class="h-4 w-4 rounded-full border border-black/8"
                                            :style="{ backgroundColor: projectColor(row) }"
                                        />
                                        <span class="font-mono text-xs">
                                            {{ cellValue(row, column) }}
                                        </span>
                                    </div>

                                    <span v-else class="break-words">
                                        {{ cellValue(row, column) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="normalizedRows.length > 0" class="grid gap-3 md:hidden">
                <div
                    v-for="(row, rowIndex) in normalizedRows"
                    :key="`card-${row.project_id || row.id || rowIndex}`"
                    class="bg-background/75 rounded-xl border border-black/6 px-3 py-3"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-white"
                                :style="{ backgroundColor: projectColor(row) }"
                            >
                                <FolderKanban class="h-4 w-4" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-foreground font-semibold break-words">
                                    {{ row.name || 'Untitled project' }}
                                </p>
                                <p class="text-muted-foreground font-mono text-[11px] break-all">
                                    {{ row.project_id || row.id || '—' }}
                                </p>
                            </div>
                        </div>

                        <Badge variant="compact" :tone="row.is_active ? 'emerald' : 'slate'">
                            {{ row.is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>

                    <div class="mt-3 grid grid-cols-3 gap-2 text-sm">
                        <div>
                            <p
                                class="text-muted-foreground text-[10px] tracking-[0.14em] uppercase"
                            >
                                Tasks
                            </p>
                            <p class="text-foreground font-semibold">
                                {{ numberValue(row.tasks_count) }}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-muted-foreground text-[10px] tracking-[0.14em] uppercase"
                            >
                                Done
                            </p>
                            <p class="text-foreground font-semibold">
                                {{ numberValue(row.completed_tasks_count) }}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-muted-foreground text-[10px] tracking-[0.14em] uppercase"
                            >
                                Progress
                            </p>
                            <p class="text-foreground font-semibold">{{ completionValue(row) }}%</p>
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
