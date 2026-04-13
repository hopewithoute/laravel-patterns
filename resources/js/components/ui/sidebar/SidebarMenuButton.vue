<script setup>
import { cn } from '@/lib/utils'
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    as: { type: String, default: 'button' },
    href: { type: String, default: undefined },
    isActive: { type: Boolean, default: false },
    tooltip: { type: String, default: undefined },
    class: { type: String, default: '' },
})

const component = computed(() => {
    if (props.href) return Link
    return props.as
})
</script>

<template>
    <component
        :is="component"
        :href="href"
        :class="
            cn(
                'group/sidebar-btn flex w-full items-center gap-3 overflow-hidden rounded-xl p-2.5 text-sm font-medium outline-none',
                'hover:bg-surface/60 transition-all duration-200',
                'focus-visible:ring-offset-card focus-visible:ring-2 focus-visible:ring-amber-500/50 focus-visible:ring-offset-2',
                'data-[active=true]:bg-gradient-to-r data-[active=true]:from-amber-500/15 data-[active=true]:to-transparent',
                'data-[active=true]:text-amber-400',
                'data-[state=closed]:justify-center data-[state=closed]:px-2',
                isActive
                    ? 'bg-gradient-to-r from-amber-500/15 to-transparent text-amber-400'
                    : 'text-muted-foreground',
                $attrs.class,
            )
        "
        :data-active="isActive"
    >
        <slot />
    </component>
</template>
