<script setup>
import { cn } from '@/lib/utils'
import { computed } from 'vue'

const props = defineProps({
    defaultOpen: { type: Boolean, default: true },
    open: { type: Boolean, default: undefined },
    class: { type: String, default: '' },
    side: { type: String, default: 'left' },
    variant: { type: String, default: 'sidebar' },
    collapsible: { type: String, default: 'offcanvas' },
})

const isOpen = computed(() => props.open ?? props.defaultOpen)
</script>

<template>
    <aside
        :class="
            cn(
                'bg-card border-border/50 relative z-40 flex h-screen flex-col border-r',
                'data-[state=closed]:w-[72px] data-[state=open]:w-64',
                'transition-all duration-300 ease-out',
                'grain',
                props.class,
            )
        "
        :data-state="isOpen ? 'open' : 'closed'"
    >
        <!-- Ambient glow effect -->
        <div class="pointer-events-none absolute inset-0">
            <div
                class="absolute top-0 right-0 left-0 h-48 bg-gradient-to-b from-amber-500/[0.03] to-transparent"
            ></div>
            <div
                class="to-background/50 absolute right-0 bottom-0 left-0 h-32 bg-gradient-to-t from-transparent"
            ></div>
        </div>

        <slot />
    </aside>
</template>
