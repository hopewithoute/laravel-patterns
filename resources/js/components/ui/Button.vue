<script setup>
import { cva } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (v) =>
            [
                'default',
                'destructive',
                'outline',
                'secondary',
                'ghost',
                'link',
                'amber',
                'cyan',
                'emerald',
            ].includes(v),
    },
    size: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'sm', 'lg', 'xl', 'icon', 'icon-sm'].includes(v),
    },
    as: {
        type: String,
        default: 'button',
    },
    class: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
})

const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl text-sm font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/25 focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50 select-none',
    {
        variants: {
            variant: {
                default:
                    'bg-primary text-primary-foreground shadow-lg shadow-primary/15 hover:shadow-primary/25 hover:bg-primary/90',
                destructive:
                    'bg-destructive text-destructive-foreground shadow-lg shadow-destructive/15 hover:shadow-destructive/25 hover:bg-destructive/90',
                outline:
                    'border border-border/50 bg-transparent text-foreground hover:bg-surface/60 hover:border-border/70',
                secondary: 'bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80',
                ghost: 'text-foreground/80 hover:text-foreground hover:bg-surface/50',
                link: 'text-primary underline-offset-4 hover:underline',
                amber: 'bg-gradient-to-r from-amber-500 to-orange-500 text-black font-semibold shadow-lg shadow-amber-500/15 hover:shadow-amber-500/25 hover:from-amber-400 hover:to-orange-400',
                cyan: 'bg-gradient-to-r from-cyan-500 to-cyan-400 text-black font-semibold shadow-lg shadow-cyan-500/15 hover:shadow-cyan-500/25 hover:from-cyan-400 hover:to-cyan-300',
                emerald:
                    'bg-gradient-to-r from-emerald-500 to-emerald-400 text-black font-semibold shadow-lg shadow-emerald-500/15 hover:shadow-emerald-500/25',
            },
            size: {
                default: 'h-10 px-5 py-2',
                sm: 'h-8 rounded-lg px-3.5 text-xs',
                lg: 'h-11 rounded-xl px-6 text-base',
                xl: 'h-13 rounded-xl px-8 text-base',
                icon: 'h-10 w-10',
                'icon-sm': 'h-8 w-8',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    },
)
</script>

<template>
    <component
        :is="as"
        :class="cn(buttonVariants({ variant: props.variant, size: props.size }), props.class)"
        :disabled="disabled"
    >
        <slot />
    </component>
</template>
