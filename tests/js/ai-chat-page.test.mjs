import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'

const chatPage = fs.readFileSync('resources/js/pages/Ai/Index.vue', 'utf8')
const dashboardPage = fs.readFileSync('resources/js/pages/Dashboard/Index.vue', 'utf8')

test('AI interaction lives in the dedicated chat page instead of the dashboard panel', () => {
    assert.match(chatPage, /import axios from ['"]axios['"]/)
    assert.match(
        chatPage,
        /import \{ computed, nextTick, onBeforeUnmount, reactive, ref, watch \} from ['"]vue['"]/,
    )
    assert.match(
        chatPage,
        /import \{ Head, router, setLayoutProps \} from ['"]@inertiajs\/vue3['"]/,
    )
    assert.match(
        chatPage,
        /Single-lane chat with session memory, tool calls, and streamed output\./,
    )
    assert.match(chatPage, /axios\.post\('\/ai\/sessions'/)
    assert.match(chatPage, /async function createSession\(\)/)
    assert.match(chatPage, /activeSessionId\.value = session\.id/)
    assert.match(chatPage, /messages\.value = \[\]/)
    assert.match(chatPage, /fetch\(`\/ai\/sessions\/\$\{sessionId\}\/messages\/stream`/)
    assert.match(chatPage, /consumeEventStream/)
    assert.match(chatPage, /ai_debug/)
    assert.match(chatPage, /console\.debug\(`\[ai-chat\]/)
    assert.match(chatPage, /const assistantMessage = reactive\(\{/)
    assert.match(chatPage, /const artifactMode = ref\(resolveInitialArtifactMode\(\)\)/)
    assert.match(
        chatPage,
        /const effectiveArtifactMode = computed\(\(\) => \(aiDebugEnabled \? artifactMode\.value : 'auto'\)\)/,
    )
    assert.match(
        chatPage,
        /import AiArtifactRenderer from ['"]@\/components\/ai\/AiArtifactRenderer\.vue['"]/,
    )
    assert.match(chatPage, /window\.localStorage\.getItem\('ai-chat-artifact-mode'\)/)
    assert.match(chatPage, /window\.localStorage\.setItem\('ai-chat-artifact-mode', value\)/)
    assert.match(chatPage, /if \(!isAiDebugEnabled\(\)\) {\s*return 'auto'/)
    assert.match(chatPage, /artifact_mode: effectiveArtifactMode\.value/)
    assert.match(chatPage, /v-else-if="message\.content"/)
    assert.match(chatPage, /<AiArtifactRenderer/)
    assert.match(chatPage, /v-if="aiDebugEnabled" class="flex flex-wrap gap-2"/)
    assert.match(chatPage, /v-if="aiDebugEnabled" class="text-muted-foreground text-sm"/)
    assert.match(
        chatPage,
        /setLayoutProps\(\{\s*mainClass: 'h-\[calc\(100dvh-3\.5rem\)\] overflow-hidden'/,
    )
    assert.match(chatPage, /contentClass: 'h-full p-0'/)
    assert.match(
        chatPage,
        /class="grid h-full min-h-0 flex-1 lg:grid-cols-\[19rem_minmax\(0,1fr\)\]"/,
    )
    assert.match(chatPage, /Workspace AI/)
    assert.match(chatPage, /const availableToolsLabel = computed/)
    assert.match(chatPage, /const primaryToolLabel = computed/)
    assert.match(chatPage, /const composerPlaceholder = computed/)
    assert.match(chatPage, /function normalizeList\(value\)/)
    assert.match(chatPage, /function normalizeMap\(value\)/)
    assert.match(chatPage, /tool_results: normalizeList\(message\.tool_results\)/)
    assert.doesNotMatch(chatPage, /Phase 0/)
    assert.doesNotMatch(
        chatPage,
        /import PageWidth from ['"]@\/components\/layout\/PageWidth\.vue['"]/,
    )
    assert.doesNotMatch(chatPage, /window\.setInterval/)

    assert.doesNotMatch(dashboardPage, /AI Operator/)
    assert.doesNotMatch(dashboardPage, /axios\.post\('\/ai\/runs'/)
    assert.match(dashboardPage, /href="\/ai"/)
})
