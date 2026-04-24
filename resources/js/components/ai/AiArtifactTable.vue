<script setup>
const props = defineProps({
    artifact: {
        type: Object,
        required: true,
    },
})

function normalizeRow(row, columns) {
    if (Array.isArray(row)) {
        return row
    }

    if (row !== null && typeof row === 'object') {
        return columns.map((column) => row[column] ?? '—')
    }

    return [row]
}
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-black/6">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-background/80">
                    <tr>
                        <th
                            v-for="column in props.artifact.data.columns || []"
                            :key="column"
                            class="text-muted-foreground px-3 py-2 font-semibold"
                        >
                            {{ column }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, rowIndex) in props.artifact.data.rows || []"
                        :key="rowIndex"
                        class="border-t border-black/6"
                    >
                        <td
                            v-for="(value, columnIndex) in normalizeRow(
                                row,
                                props.artifact.data.columns || [],
                            )"
                            :key="`${rowIndex}-${columnIndex}`"
                            class="text-foreground px-3 py-2 align-top"
                        >
                            {{ value }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
