<script setup>
import { computed } from 'vue'

const props = defineProps({
    artifact: {
        type: Object,
        required: true,
    },
})

const items = computed(() =>
    (props.artifact.data.items || []).map((item) => {
        if (typeof item === 'string') {
            return {
                label: item,
                checked: false,
                description: '',
            }
        }

        return {
            label: item?.label || 'Untitled item',
            checked: Boolean(item?.checked),
            description: typeof item?.description === 'string' ? item.description : '',
        }
    }),
)
</script>

<template>
    <div class="space-y-2">
        <div
            v-for="(item, index) in items"
            :key="`${item.label}-${index}`"
            class="bg-background/70 rounded-2xl px-3 py-3"
        >
            <div class="flex items-start gap-3">
                <div
                    class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border"
                    :class="
                        item.checked
                            ? 'border-emerald-500/30 bg-emerald-500/15 text-emerald-600'
                            : 'bg-background text-muted-foreground border-black/8'
                    "
                >
                    <span class="text-xs font-semibold">{{ item.checked ? '✓' : '•' }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-foreground text-sm font-medium">{{ item.label }}</p>
                    <p v-if="item.description" class="text-muted-foreground mt-1 text-xs leading-6">
                        {{ item.description }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
