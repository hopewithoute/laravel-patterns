<script setup>
import { reactiveOmit } from '@vueuse/core'
import { X } from 'lucide-vue-next'
import { DialogClose, DialogContent, DialogPortal, useForwardPropsEmits } from 'reka-ui'
import { cva } from 'class-variance-authority'
import { cn } from '@/lib/utils'
import SheetOverlay from './SheetOverlay.vue'

defineOptions({
    inheritAttrs: false,
})

const props = defineProps({
    class: { type: null, required: false },
    side: { type: String, required: false, default: 'right' },
    showCloseButton: { type: Boolean, required: false, default: true },
})

const emits = defineEmits([
    'escapeKeyDown',
    'pointerDownOutside',
    'focusOutside',
    'interactOutside',
    'openAutoFocus',
    'closeAutoFocus',
])

const sheetVariants = cva(
    'bg-background data-[state=open]:animate-in data-[state=closed]:animate-out fixed z-50 gap-4 p-6 shadow-lg transition ease-in-out data-[state=closed]:duration-300 data-[state=open]:duration-500',
    {
        variants: {
            side: {
                top: 'inset-x-0 top-0 border-b data-[state=closed]:slide-out-to-top data-[state=open]:slide-in-from-top',
                bottom: 'inset-x-0 bottom-0 border-t data-[state=closed]:slide-out-to-bottom data-[state=open]:slide-in-from-bottom',
                left: 'inset-y-0 left-0 h-full w-3/4 border-r data-[state=closed]:slide-out-to-left data-[state=open]:slide-in-from-left sm:max-w-sm',
                right: 'inset-y-0 right-0 h-full w-3/4 border-l data-[state=closed]:slide-out-to-right data-[state=open]:slide-in-from-right sm:max-w-sm',
            },
        },
        defaultVariants: {
            side: 'right',
        },
    },
)

const delegatedProps = reactiveOmit(props, 'class', 'side')
const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
    <DialogPortal>
        <SheetOverlay />
        <DialogContent
            v-bind="{ ...$attrs, ...forwarded }"
            :class="cn(sheetVariants({ side }), props.class)"
        >
            <slot />

            <DialogClose
                v-if="showCloseButton"
                class="ring-offset-background focus:ring-ring data-[state=open]:bg-accent data-[state=open]:text-muted-foreground absolute top-4 right-4 rounded-xs opacity-70 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-offset-2 focus:outline-hidden disabled:pointer-events-none [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4"
            >
                <X />
                <span class="sr-only">Close</span>
            </DialogClose>
        </DialogContent>
    </DialogPortal>
</template>
