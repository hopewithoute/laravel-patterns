<script setup>
import { computed } from 'vue'

const props = defineProps({
    artifact: {
        type: Object,
        required: true,
    },
})

const title = computed(() => props.artifact.data?.title || props.artifact.title || 'Website design')
const html = computed(() => String(props.artifact.data?.html || ''))

const srcdoc = computed(() => {
    if (html.value.trim() === '') {
        return emptyDocument()
    }

    return html.value
})

function emptyDocument() {
    return `<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      background: #f4f1eb;
      color: #27231f;
      font-family: ui-sans-serif, system-ui, sans-serif;
    }
  </style>
</head>
<body>
  <p>No website HTML was generated.</p>
</body>
</html>`
}
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-black/10 bg-background shadow-sm">
        <div class="border-border/60 flex items-center justify-between gap-3 border-b px-4 py-3">
            <div class="min-w-0">
                <p class="text-foreground truncate text-sm font-semibold">
                    {{ title }}
                </p>
                <p class="text-muted-foreground text-xs">Sandboxed HTML preview</p>
            </div>
        </div>

        <iframe
            class="h-[680px] w-full bg-white"
            :srcdoc="srcdoc"
            sandbox="allow-scripts"
            title="Website design preview"
        />
    </section>
</template>
