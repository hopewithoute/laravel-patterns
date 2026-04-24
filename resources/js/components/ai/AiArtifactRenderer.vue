<script setup>
import { computed } from 'vue'
import AiArtifactApprovalCard from './AiArtifactApprovalCard.vue'
import AiArtifactBarChart from './AiArtifactBarChart.vue'
import AiArtifactChecklist from './AiArtifactChecklist.vue'
import AiArtifactJsonFallback from './AiArtifactJsonFallback.vue'
import AiArtifactKeyValue from './AiArtifactKeyValue.vue'
import AiArtifactLineChart from './AiArtifactLineChart.vue'
import AiArtifactMarkdown from './AiArtifactMarkdown.vue'
import AiArtifactStatsCard from './AiArtifactStatsCard.vue'
import AiArtifactTable from './AiArtifactTable.vue'
import AiArtifactTaskSummary from './AiArtifactTaskSummary.vue'
import artifactRegistryManifest from './artifactRegistryManifest.json'

const props = defineProps({
    artifact: {
        type: Object,
        required: true,
    },
})

const artifactDefinitions = Object.fromEntries(
    artifactRegistryManifest.map((definition) => [definition.type, definition]),
)

const rendererRegistry = {
    'task-summary': AiArtifactTaskSummary,
    table: AiArtifactTable,
    checklist: AiArtifactChecklist,
    'key-value': AiArtifactKeyValue,
    'stats-card': AiArtifactStatsCard,
    'approval-card': AiArtifactApprovalCard,
    markdown: AiArtifactMarkdown,
    'bar-chart': AiArtifactBarChart,
    'line-chart': AiArtifactLineChart,
    'json-fallback': AiArtifactJsonFallback,
}

const artifactDefinition = computed(
    () => artifactDefinitions[props.artifact.artifactType] || artifactDefinitions.json_fallback,
)

const artifactRenderer = computed(
    () => props.artifact.meta?.renderer || artifactDefinition.value?.renderer || 'json-fallback',
)

const artifactComponent = computed(
    () => rendererRegistry[artifactRenderer.value] || AiArtifactJsonFallback,
)
</script>

<template>
    <div class="bg-surface/60 rounded-2xl px-4 py-3">
        <p class="text-foreground text-sm font-semibold">
            {{ props.artifact.title }}
        </p>

        <component :is="artifactComponent" class="mt-3" :artifact="props.artifact" />
    </div>
</template>
