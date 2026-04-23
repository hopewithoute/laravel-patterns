<script setup>
const props = defineProps({
    artifact: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <pre class="text-muted-foreground overflow-x-auto text-xs leading-6 whitespace-pre-wrap">{{
        JSON.stringify(props.artifact.data, null, 2)
    }}</pre>
</template>
