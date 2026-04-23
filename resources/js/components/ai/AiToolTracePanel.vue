<script setup>
import { computed } from 'vue'
import toolRegistryManifest from './toolRegistryManifest.json'

const props = defineProps({
    toolCalls: {
        type: Array,
        default: () => [],
    },
    toolResults: {
        type: Array,
        default: () => [],
    },
    formatPayload: {
        type: Function,
        required: true,
    },
})

const toolDefinitions = Object.fromEntries(
    toolRegistryManifest.map((definition) => [definition.name, definition]),
)

const resolveToolDefinition = (tool) => toolDefinitions[tool?.tool_name || tool?.name] || null

const toolCallItems = computed(() =>
    props.toolCalls.map((toolCall) => ({
        ...toolCall,
        definition: resolveToolDefinition(toolCall),
    })),
)

const toolResultItems = computed(() =>
    props.toolResults.map((toolResult) => ({
        ...toolResult,
        definition: resolveToolDefinition(toolResult),
    })),
)
</script>

<template>
    <details
        v-if="toolCallItems.length > 0 || toolResultItems.length > 0"
        class="mt-4 rounded-2xl border border-dashed border-black/10 bg-background/45"
    >
        <summary
            class="text-muted-foreground cursor-pointer list-none px-4 py-3 text-[11px] font-semibold tracking-[0.18em] uppercase"
        >
            Debug trace
            <span class="ml-2 normal-case tracking-normal">
                {{ toolCallItems.length }} call{{ toolCallItems.length === 1 ? '' : 's' }},
                {{ toolResultItems.length }} result{{ toolResultItems.length === 1 ? '' : 's' }}
            </span>
        </summary>

        <div class="space-y-4 border-t border-black/6 px-4 py-4">
            <div v-if="toolCallItems.length > 0" class="space-y-2">
                <p class="text-muted-foreground text-[11px] tracking-[0.18em] uppercase">
                    Tool Calls
                </p>
                <div
                    v-for="toolCall in toolCallItems"
                    :key="toolCall.id || toolCall.tool_id"
                    class="bg-surface/60 rounded-2xl px-4 py-3"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-foreground text-sm font-semibold">
                            {{ toolCall.definition?.label || toolCall.tool_name || toolCall.name }}
                        </p>
                        <span
                            class="text-muted-foreground rounded-full border border-black/8 px-2 py-0.5 text-[10px] tracking-[0.14em] uppercase"
                        >
                            {{ toolCall.definition?.operation || 'tool' }}
                        </span>
                        <span class="text-muted-foreground text-[11px]">
                            {{ toolCall.tool_name || toolCall.name }}
                        </span>
                    </div>
                    <p
                        v-if="toolCall.definition?.description"
                        class="text-muted-foreground mt-2 text-xs leading-6"
                    >
                        {{ toolCall.definition.description }}
                    </p>
                    <pre
                        class="text-muted-foreground mt-2 overflow-x-auto text-xs leading-6 whitespace-pre-wrap"
                    >{{ props.formatPayload(toolCall.arguments) }}</pre>
                    <p
                        v-if="toolCall.definition?.outputContract"
                        class="text-muted-foreground mt-2 text-[11px] leading-5"
                    >
                        Output: {{ toolCall.definition.outputContract }}
                    </p>
                </div>
            </div>

            <div v-if="toolResultItems.length > 0" class="space-y-2">
                <p class="text-muted-foreground text-[11px] tracking-[0.18em] uppercase">
                    Tool Results
                </p>
                <div
                    v-for="toolResult in toolResultItems"
                    :key="toolResult.id || toolResult.tool_id"
                    class="bg-surface/60 rounded-2xl px-4 py-3"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-foreground text-sm font-semibold">
                            {{ toolResult.definition?.label || toolResult.tool_name || toolResult.name }}
                        </p>
                        <span
                            class="text-muted-foreground rounded-full border border-black/8 px-2 py-0.5 text-[10px] tracking-[0.14em] uppercase"
                        >
                            {{ toolResult.definition?.operation || 'tool' }}
                        </span>
                        <span class="text-muted-foreground text-[11px]">
                            {{ toolResult.tool_name || toolResult.name }}
                        </span>
                    </div>
                    <pre
                        class="text-muted-foreground mt-2 overflow-x-auto text-xs leading-6 whitespace-pre-wrap"
                    >{{ props.formatPayload(toolResult.result) }}</pre>
                    <p
                        v-if="toolResult.definition?.outputContract"
                        class="text-muted-foreground mt-2 text-[11px] leading-5"
                    >
                        Output: {{ toolResult.definition.outputContract }}
                    </p>
                </div>
            </div>
        </div>
    </details>
</template>
