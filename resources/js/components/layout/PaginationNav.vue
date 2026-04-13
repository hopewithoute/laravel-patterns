<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    resource: {
        type: Object,
        required: true,
    },
    noun: {
        type: String,
        required: true,
    },
    tone: {
        type: String,
        default: 'amber',
    },
})

const toneClasses = {
    amber: 'bg-amber-500/10 text-amber-400 border border-amber-500/25',
    cyan: 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/25',
}

const activeClasses = computed(() => toneClasses[props.tone] ?? toneClasses.amber)
</script>

<template>
    <section
        v-if="resource.last_page > 1"
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-muted-foreground text-sm">
            Showing <span class="text-foreground font-mono">{{ resource.from }}</span> to
            <span class="text-foreground font-mono">{{ resource.to }}</span> of
            <span class="text-foreground font-mono">{{ resource.total }}</span> {{ noun }}
        </p>

        <div class="flex flex-wrap items-center gap-1">
            <Link
                v-for="page in resource.links"
                :key="`${page.label}-${page.url}`"
                :href="page.url || '#'"
                :class="[
                    'rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200',
                    page.active
                        ? activeClasses
                        : 'text-muted-foreground hover:text-foreground hover:bg-surface/50',
                    !page.url && 'pointer-events-none cursor-not-allowed opacity-50',
                ]"
            >
                <span v-html="page.label" />
            </Link>
        </div>
    </section>
</template>
