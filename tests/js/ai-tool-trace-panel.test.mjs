import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'

const panel = fs.readFileSync('resources/js/components/ai/AiToolTracePanel.vue', 'utf8')
const chatPage = fs.readFileSync('resources/js/pages/Ai/Index.vue', 'utf8')
const manifest = JSON.parse(
    fs.readFileSync('resources/js/components/ai/toolRegistryManifest.json', 'utf8'),
)

test('tool trace panel uses the shared tool manifest for labels and contracts', () => {
    assert.match(panel, /import toolRegistryManifest from '\.\/toolRegistryManifest\.json'/)
    assert.match(panel, /const toolDefinitions = Object\.fromEntries/)
    assert.match(panel, /definition\?\.label \|\| toolCall\.tool_name \|\| toolCall\.name/)
    assert.match(panel, /definition\?\.operation \|\| 'tool'/)
    assert.match(panel, /definition\?\.outputContract/)
})

test('tool manifest stays aligned with the debug panel contract', () => {
    assert.ok(Array.isArray(manifest))
    assert.ok(manifest.some((definition) => definition.uiIdentifier === 'create_task'))
    assert.ok(manifest.some((definition) => definition.uiIdentifier === 'lookup_projects'))
    assert.ok(manifest.some((definition) => definition.uiIdentifier === 'lookup_workspace_users'))
    assert.ok(
        manifest.every(
            (definition) =>
                typeof definition.name === 'string' &&
                typeof definition.label === 'string' &&
                typeof definition.operation === 'string',
        ),
    )
})

test('AI chat page derives visible tool labels from the shared manifest', () => {
    assert.match(
        chatPage,
        /import toolRegistryManifest from ['"]@\/components\/ai\/toolRegistryManifest\.json['"]/,
    )
    assert.match(chatPage, /const toolDefinitions = Object\.fromEntries/)
    assert.match(chatPage, /const availableToolDefinitions = computed/)
    assert.match(chatPage, /definition\.label\)\.join\(', '\)/)
})
