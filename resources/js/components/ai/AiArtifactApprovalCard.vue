<script setup>
const props = defineProps({
    artifact: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <div class="space-y-3">
        <div class="bg-background/70 rounded-2xl px-4 py-4">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-foreground text-base font-semibold">
                        {{ props.artifact.data.headline || props.artifact.title }}
                    </p>
                    <p
                        v-if="props.artifact.data.summary"
                        class="text-muted-foreground mt-2 text-sm leading-6"
                    >
                        {{ props.artifact.data.summary }}
                    </p>
                </div>

                <span
                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em]"
                    :class="
                        props.artifact.data.status === 'approved'
                            ? 'bg-emerald-500/12 text-emerald-600'
                            : props.artifact.data.status === 'rejected'
                              ? 'bg-rose-500/12 text-rose-600'
                              : 'bg-amber-500/12 text-amber-600'
                    "
                >
                    {{ props.artifact.data.status || 'pending' }}
                </span>
            </div>
        </div>

        <div
            v-if="(props.artifact.data.fields || []).length > 0"
            class="grid gap-3 sm:grid-cols-2"
        >
            <div
                v-for="(field, index) in props.artifact.data.fields"
                :key="`${field.label || 'field'}-${index}`"
                class="bg-background/70 rounded-2xl px-3 py-3"
            >
                <p class="text-muted-foreground text-[11px] tracking-[0.16em] uppercase">
                    {{ field.label || 'Field' }}
                </p>
                <p class="text-foreground mt-1 text-sm font-semibold">
                    {{ field.value || '—' }}
                </p>
            </div>
        </div>

        <div
            v-if="props.artifact.data.next_step"
            class="rounded-2xl border border-black/6 bg-background/70 px-4 py-3"
        >
            <p class="text-muted-foreground text-[11px] tracking-[0.16em] uppercase">Next step</p>
            <p class="text-foreground mt-1 text-sm font-medium">
                {{ props.artifact.data.next_step }}
            </p>
        </div>
    </div>
</template>
