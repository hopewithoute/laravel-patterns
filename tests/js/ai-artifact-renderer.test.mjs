import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'

const renderer = fs.readFileSync('resources/js/components/ai/AiArtifactRenderer.vue', 'utf8')
const chatPage = fs.readFileSync('resources/js/pages/Ai/Index.vue', 'utf8')
const manifest = JSON.parse(
    fs.readFileSync('resources/js/components/ai/artifactRegistryManifest.json', 'utf8'),
)

test('artifact renderer uses the shared artifact manifest and explicit renderer registry', () => {
    assert.match(
        renderer,
        /import artifactRegistryManifest from '\.\/artifactRegistryManifest\.json'/,
    )
    assert.match(renderer, /const artifactDefinitions = Object\.fromEntries/)
    assert.match(renderer, /'task-summary': AiArtifactTaskSummary/)
    assert.match(renderer, /'table': AiArtifactTable/)
    assert.match(renderer, /'checklist': AiArtifactChecklist/)
    assert.match(renderer, /'key-value': AiArtifactKeyValue/)
    assert.match(renderer, /'stats-card': AiArtifactStatsCard/)
    assert.match(renderer, /'approval-card': AiArtifactApprovalCard/)
    assert.match(renderer, /'markdown': AiArtifactMarkdown/)
    assert.match(renderer, /'bar-chart': AiArtifactBarChart/)
    assert.match(renderer, /'line-chart': AiArtifactLineChart/)
    assert.match(renderer, /'json-fallback': AiArtifactJsonFallback/)
    assert.match(
        renderer,
        /props\.artifact\.meta\?\.renderer \|\| artifactDefinition\.value\?\.renderer/,
    )
})

test('artifact manifest stays aligned with the renderer registry contract', () => {
    assert.ok(Array.isArray(manifest))
    assert.ok(manifest.some((definition) => definition.type === 'task_summary'))
    assert.ok(manifest.some((definition) => definition.type === 'bar_chart'))
    assert.ok(manifest.some((definition) => definition.type === 'line_chart'))
    assert.ok(manifest.some((definition) => definition.type === 'markdown'))
    assert.ok(
        manifest.every(
            (definition) =>
                typeof definition.renderer === 'string' && definition.renderer.length > 0,
        ),
    )
})

test('AI chat page consumes artifact events and renders artifacts as first-class blocks', () => {
    assert.match(
        chatPage,
        /import AiArtifactRenderer from ['"]@\/components\/ai\/AiArtifactRenderer\.vue['"]/,
    )
    assert.match(
        chatPage,
        /import AiToolTracePanel from ['"]@\/components\/ai\/AiToolTracePanel\.vue['"]/,
    )
    assert.match(chatPage, /v-if="aiDebugEnabled" class="flex flex-wrap gap-2"/)
    assert.match(chatPage, /v-for="mode in availableArtifactModes"/)
    assert.match(chatPage, /@click="artifactMode = mode\.value"/)
    assert.match(chatPage, /artifacts: \[\.\.\.\(message\.artifacts \|\| \[\]\)\]/)
    assert.match(chatPage, /if \(event\.type === 'artifact'\)/)
    assert.match(
        chatPage,
        /assistantMessage\.artifacts = \[\.\.\.assistantMessage\.artifacts, event\]/,
    )
    assert.match(chatPage, /v-if="message\.artifacts\?\.length > 0"/)
    assert.match(chatPage, /<AiToolTracePanel/)
    assert.doesNotMatch(chatPage, /AiToolResultBlock/)
    assert.doesNotMatch(chatPage, /messageUsesArtifactAnswer/)
})
