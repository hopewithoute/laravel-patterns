<script setup>
import AiMarkdown from './AiMarkdown.vue'

const props = defineProps({
    artifact: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <AiMarkdown :content="props.artifact.data.content || ''" />
</template>
