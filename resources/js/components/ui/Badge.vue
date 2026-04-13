<script setup>
import { cva } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const props = defineProps({
    variant: {
        type: String,
        default: 'pill',
        validator: (value) => ['pill', 'compact', 'soft'].includes(value),
    },
    tone: {
        type: String,
        default: 'surface',
        validator: (value) =>
            ['surface', 'slate', 'cyan', 'sky', 'amber', 'emerald', 'orange', 'rose'].includes(
                value,
            ),
    },
    class: {
        type: String,
        default: '',
    },
})

const badgeVariants = cva(
    'inline-flex items-center gap-1.5 whitespace-nowrap border font-medium select-none',
    {
        variants: {
            variant: {
                pill: 'rounded-full px-2.5 py-1 text-[11px]',
                compact: 'rounded-full px-2 py-0.5 text-[10px]',
                soft: 'rounded-full px-3 py-1.5 text-xs tracking-wide',
            },
            tone: {
                surface: 'bg-surface text-muted-foreground border-border/40',
                slate: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20 dark:bg-slate-800/40 dark:border-slate-700/40',
                cyan: 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-500/20',
                sky: 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20',
                amber: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                emerald:
                    'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                orange: 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-500/20',
                rose: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
            },
        },
        defaultVariants: {
            variant: 'pill',
            tone: 'surface',
        },
    },
)
</script>

<template>
    <span :class="cn(badgeVariants({ variant: props.variant, tone: props.tone }), props.class)">
        <slot />
    </span>
</template>
