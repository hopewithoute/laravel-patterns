<script setup>
import { computed, useSlots } from 'vue'
import { Link } from '@inertiajs/vue3'

import { cn } from '@/lib/utils'

const props = defineProps({
    variant: {
        type: String,
        default: 'hero',
    },
    tone: {
        type: String,
        default: 'amber',
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    badge: {
        type: String,
        default: '',
    },
    backHref: {
        type: String,
        default: '',
    },
    backLabel: {
        type: String,
        default: '',
    },
    glowClass: {
        type: String,
        default: '',
    },
    titleSize: {
        type: String,
        default: 'display',
    },
    descriptionClass: {
        type: String,
        default: '',
    },
})

const slots = useSlots()

const badgeClass = computed(() =>
    cn(
        'inline-flex items-center gap-2',
        props.tone === 'cyan'
            ? 'section-badge section-badge-cyan'
            : 'section-badge section-badge-amber',
    ),
)

const badgeTextClass = computed(() =>
    props.tone === 'cyan'
        ? 'text-[11px] font-mono uppercase tracking-widest text-cyan-400'
        : 'text-[11px] font-mono uppercase tracking-widest text-amber-400',
)

const backLinkClass = computed(() =>
    cn(
        'inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors',
        props.tone === 'cyan' ? 'hover:text-cyan-400' : 'hover:text-amber-400',
    ),
)

const titleClass = computed(() =>
    cn(
        'font-display tracking-tight text-foreground',
        props.titleSize === 'headline'
            ? 'text-3xl sm:text-4xl font-bold'
            : 'text-5xl sm:text-6xl leading-[0.95]',
    ),
)

const descriptionClasses = computed(() =>
    cn(
        'text-muted-foreground',
        props.variant === 'hero' ? 'text-lg leading-relaxed font-light max-w-xl' : '',
        props.descriptionClass,
    ),
)
</script>

<template>
    <section class="relative overflow-hidden">
        <div v-if="glowClass" :class="cn('absolute rounded-full blur-3xl', glowClass)" />

        <div class="relative z-10 space-y-4">
            <Link v-if="backHref" :href="backHref" :class="backLinkClass">
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="M19 12H5M12 19l-7-7 7-7" />
                </svg>
                {{ backLabel }}
            </Link>

            <div
                v-if="variant === 'hero'"
                class="flex flex-col justify-between gap-6 sm:flex-row sm:items-end"
            >
                <div class="space-y-3">
                    <div v-if="badge" :class="badgeClass">
                        <slot name="badge-icon" />
                        <span :class="badgeTextClass">{{ badge }}</span>
                    </div>

                    <h1 :class="titleClass">{{ title }}</h1>
                    <p v-if="description" :class="descriptionClasses">{{ description }}</p>
                </div>

                <div v-if="slots.actions" class="flex items-center gap-3">
                    <slot name="actions" />
                </div>
            </div>

            <div v-else class="flex flex-col gap-4">
                <div :class="slots.media ? 'flex items-start gap-4' : 'space-y-1'">
                    <div v-if="slots.media" class="shrink-0">
                        <slot name="media" />
                    </div>

                    <div class="space-y-1">
                        <h1 :class="titleClass">{{ title }}</h1>
                        <p v-if="description" :class="descriptionClasses">{{ description }}</p>
                    </div>
                </div>

                <div v-if="slots.actions" class="flex items-center gap-3">
                    <slot name="actions" />
                </div>
            </div>
        </div>
    </section>
</template>
