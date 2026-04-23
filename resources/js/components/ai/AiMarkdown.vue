<script setup>
import { computed } from 'vue'
import { renderMarkdownToHtml } from './renderMarkdown.js'

const props = defineProps({
    content: {
        type: String,
        default: '',
    },
    tone: {
        type: String,
        default: 'default',
    },
})

const renderedHtml = computed(() => renderMarkdownToHtml(props.content))
const toneClass = computed(() =>
    props.tone === 'inverse' ? 'text-black/88 [&_a]:text-black [&_blockquote]:text-black/72' : '',
)
</script>

<template>
    <div
        class="ai-markdown text-sm leading-7 [&_code]:break-words [&_ol]:my-3 [&_pre]:my-4 [&_ul]:my-3 [&>*+*]:mt-4"
        :class="toneClass"
        v-html="renderedHtml"
    />
</template>
