<script setup>
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui'

defineProps({
    title: { type: String, default: 'Are you sure?' },
    description: {
        type: String,
        default: 'This action cannot be undone and will permanently delete the data.',
    },
    confirmLabel: { type: String, default: 'Delete' },
    cancelLabel: { type: String, default: 'Cancel' },
    variant: { type: String, default: 'destructive' },
    loading: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'cancel'])
const open = defineModel('open', { type: Boolean, default: false })

const handleConfirm = () => {
    emit('confirm')
}

const handleCancel = () => {
    emit('cancel')
    open.value = false
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-106.25">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-2">
                <Button variant="outline" :disabled="loading" @click="handleCancel">
                    {{ cancelLabel }}
                </Button>
                <Button :variant="variant" :disabled="loading" @click="handleConfirm">
                    <span
                        v-if="loading"
                        class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
                    />
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
