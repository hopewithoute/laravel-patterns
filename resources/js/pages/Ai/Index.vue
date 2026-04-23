<script setup>
import axios from 'axios'
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { Head, router, setLayoutProps } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import AiArtifactRenderer from '@/components/ai/AiArtifactRenderer.vue'
import AiMarkdown from '@/components/ai/AiMarkdown.vue'
import AiToolTracePanel from '@/components/ai/AiToolTracePanel.vue'
import toolRegistryManifest from '@/components/ai/toolRegistryManifest.json'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import { createCharacterStreamRenderer } from './characterStreamRenderer.js'

const props = defineProps({
    sessions: {
        type: Array,
        default: () => [],
    },
    activeSessionId: {
        type: String,
        default: null,
    },
    messages: {
        type: Array,
        default: () => [],
    },
    workspace: {
        type: Object,
        default: () => ({
            id: null,
            name: 'Workspace',
        }),
    },
    role: {
        type: String,
        default: null,
    },
    availableTools: {
        type: Array,
        default: () => [],
    },
    availableArtifactModes: {
        type: Array,
        default: () => [],
    },
})

const chatLaneRef = ref(null)
const composer = ref('')
const artifactMode = ref(resolveInitialArtifactMode())
const requestError = ref('')
const streamError = ref('')
const streaming = ref(false)
const sessions = ref(cloneSessions(props.sessions))
const activeSessionId = ref(props.activeSessionId)
const messages = ref(cloneMessages(props.messages))
let activeCharacterStream = null
let queuedScrollFrame = null
const aiDebugEnabled = isAiDebugEnabled()

setLayoutProps({
    mainClass: 'h-[calc(100dvh-3.5rem)] overflow-hidden',
    contentClass: 'h-full p-0',
})

const formattedRole = computed(() => (props.role ? props.role.replaceAll('_', ' ') : 'member'))
const currentSession = computed(
    () => sessions.value.find((session) => session.id === activeSessionId.value) ?? null,
)
const hasMessages = computed(() => messages.value.length > 0)
const toolDefinitions = Object.fromEntries(
    toolRegistryManifest.map((definition) => [definition.uiIdentifier, definition]),
)
const availableToolDefinitions = computed(() =>
    props.availableTools
        .map((identifier) => toolDefinitions[identifier] || null)
        .filter(Boolean),
)
const availableToolsLabel = computed(() =>
    availableToolDefinitions.value.length > 0
        ? availableToolDefinitions.value.map((definition) => definition.label).join(', ')
        : 'No tools configured',
)
const primaryToolLabel = computed(() => availableToolDefinitions.value[0]?.label || 'Workspace tool')
const effectiveArtifactMode = computed(() => (aiDebugEnabled ? artifactMode.value : 'auto'))
const artifactModeLabel = computed(() => {
    const selectedMode = props.availableArtifactModes.find(
        (mode) => mode.value === effectiveArtifactMode.value,
    )

    return selectedMode?.label || 'Auto'
})
const composerPlaceholder = computed(
    () =>
        `Use the active workspace chat lane. Example ${primaryToolLabel.value} payload: {"project_id":"...","title":"Follow up customer feedback","description":"Summarize the issue and propose a fix.","priority":"High"}`,
)

const groupedSessions = computed(() => {
    const groups = {
        Today: [],
        Yesterday: [],
        Earlier: [],
    }

    const now = new Date()
    const todayKey = now.toDateString()
    const yesterday = new Date()
    yesterday.setDate(now.getDate() - 1)
    const yesterdayKey = yesterday.toDateString()

    for (const session of sessions.value) {
        const timestamp = session.last_message_at || session.updated_at

        if (!timestamp) {
            groups.Earlier.push(session)
            continue
        }

        const date = new Date(timestamp)
        const dateKey = date.toDateString()

        if (dateKey === todayKey) {
            groups.Today.push(session)
            continue
        }

        if (dateKey === yesterdayKey) {
            groups.Yesterday.push(session)
            continue
        }

        groups.Earlier.push(session)
    }

    return Object.entries(groups).filter(([, items]) => items.length > 0)
})

watch(
    () => [props.sessions, props.messages, props.activeSessionId],
    () => {
        if (streaming.value) {
            return
        }

        sessions.value = cloneSessions(props.sessions)
        messages.value = cloneMessages(props.messages)
        activeSessionId.value = props.activeSessionId
    },
    { deep: true },
)

watch(
    () => messages.value.length,
    () => {
        void scrollToBottom()
    },
    { immediate: true },
)

function cloneSessions(values) {
    return values.map((session) => ({ ...session }))
}

function normalizeList(value) {
    if (Array.isArray(value)) {
        return [...value]
    }

    if (!value || typeof value !== 'object') {
        return []
    }

    return Object.keys(value).length > 0 ? [{ ...value }] : []
}

function normalizeMap(value) {
    return value && typeof value === 'object' && !Array.isArray(value) ? { ...value } : {}
}

function cloneMessages(values) {
    return values.map((message) => ({
        ...message,
        artifacts: normalizeList(message.artifacts),
        tool_calls: normalizeList(message.tool_calls),
        tool_results: normalizeList(message.tool_results),
        usage: normalizeMap(message.usage),
        meta: normalizeMap(message.meta),
    }))
}

function formatTimestamp(value) {
    if (!value) {
        return 'Just now'
    }

    return new Date(value).toLocaleString()
}

function formatRelativeTimestamp(value) {
    if (!value) {
        return 'No messages yet'
    }

    return new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value))
}

function isAiDebugEnabled() {
    if (typeof window === 'undefined') {
        return false
    }

    const url = new URL(window.location.href)

    return (
        url.searchParams.get('ai_debug') === '1' ||
        window.localStorage.getItem('ai-chat-debug') === '1' ||
        window.__AI_CHAT_DEBUG__ === true
    )
}

function resolveInitialArtifactMode() {
    if (typeof window === 'undefined') {
        return 'auto'
    }

    if (!isAiDebugEnabled()) {
        return 'auto'
    }

    return window.localStorage.getItem('ai-chat-artifact-mode') || 'auto'
}

function debugAi(label, payload = undefined) {
    if (!aiDebugEnabled) {
        return
    }

    if (payload === undefined) {
        console.debug(`[ai-chat] ${label}`)

        return
    }

    console.debug(`[ai-chat] ${label}`, payload)
}

function formatPayload(value) {
    if (value === null || value === undefined || value === '') {
        return 'No payload available.'
    }

    if (typeof value === 'string') {
        try {
            return JSON.stringify(JSON.parse(value), null, 2)
        } catch {
            return value
        }
    }

    return JSON.stringify(value, null, 2)
}

function summarizeTitle(value) {
    return value.length > 56 ? `${value.slice(0, 56).trim()}...` : value
}

async function scrollToBottom(behavior = 'smooth') {
    await nextTick()

    if (!chatLaneRef.value) {
        return
    }

    chatLaneRef.value.scrollTo({
        top: chatLaneRef.value.scrollHeight,
        behavior,
    })
}

function queueScrollToBottom() {
    if (queuedScrollFrame !== null) {
        return
    }

    const schedule =
        typeof window !== 'undefined' && typeof window.requestAnimationFrame === 'function'
            ? window.requestAnimationFrame.bind(window)
            : (callback) => setTimeout(callback, 16)

    queuedScrollFrame = schedule(() => {
        queuedScrollFrame = null
        void scrollToBottom('auto')
    })
}

function cancelQueuedScroll() {
    if (queuedScrollFrame === null) {
        return
    }

    if (typeof window !== 'undefined' && typeof window.cancelAnimationFrame === 'function') {
        window.cancelAnimationFrame(queuedScrollFrame)
    } else {
        clearTimeout(queuedScrollFrame)
    }

    queuedScrollFrame = null
}

function updateBrowserUrl(sessionId) {
    const url = new URL(window.location.href)

    if (sessionId) {
        url.searchParams.set('session', sessionId)
    } else {
        url.searchParams.delete('session')
    }

    window.history.replaceState({}, '', url)
}

watch(artifactMode, (value) => {
    if (typeof window === 'undefined' || !aiDebugEnabled) {
        return
    }

    window.localStorage.setItem('ai-chat-artifact-mode', value)
})

async function ensureSession() {
    if (activeSessionId.value) {
        return activeSessionId.value
    }

    const response = await axios.post('/ai/sessions')
    const session = response.data.session

    sessions.value = [session, ...sessions.value]
    activeSessionId.value = session.id
    messages.value = []
    updateBrowserUrl(session.id)

    return session.id
}

async function createSession() {
    if (streaming.value) {
        return
    }

    try {
        const response = await axios.post('/ai/sessions')
        const session = response.data.session

        sessions.value = [session, ...sessions.value.filter((item) => item.id !== session.id)]
        activeSessionId.value = session.id
        messages.value = []
        composer.value = ''
        requestError.value = ''
        streamError.value = ''
        updateBrowserUrl(session.id)
        await nextTick()

        if (chatLaneRef.value) {
            chatLaneRef.value.scrollTo({
                top: 0,
                behavior: 'smooth',
            })
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Unable to create a new chat session.')
    }
}

function openSession(sessionId) {
    if (streaming.value || sessionId === activeSessionId.value) {
        return
    }

    router.get(
        '/ai',
        { session: sessionId },
        {
            preserveScroll: true,
            preserveState: true,
        },
    )
}

function appendOptimisticMessages(prompt) {
    const now = new Date().toISOString()
    const userMessage = reactive({
        id: `local-user-${Date.now()}`,
        role: 'user',
        content: prompt,
        artifacts: [],
        tool_calls: [],
        tool_results: [],
        usage: {},
        meta: {},
        created_at: now,
    })
    const assistantMessage = reactive({
        id: `local-assistant-${Date.now()}`,
        role: 'assistant',
        content: '',
        artifacts: [],
        tool_calls: [],
        tool_results: [],
        usage: {},
        meta: {},
        created_at: now,
        streaming: true,
        error_message: '',
    })

    messages.value = [...messages.value, userMessage, assistantMessage]

    return assistantMessage
}

function touchCurrentSession(prompt) {
    const currentTimestamp = new Date().toISOString()

    sessions.value = sessions.value
        .map((session) => {
            if (session.id !== activeSessionId.value) {
                return session
            }

            return {
                ...session,
                title: session.title === 'New chat' ? summarizeTitle(prompt) : session.title,
                last_message_at: currentTimestamp,
                updated_at: currentTimestamp,
            }
        })
        .sort((left, right) => {
            const leftValue = left.last_message_at || left.updated_at || ''
            const rightValue = right.last_message_at || right.updated_at || ''

            return rightValue.localeCompare(leftValue)
        })
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
}

function destroyActiveCharacterStream() {
    activeCharacterStream?.destroy()
    activeCharacterStream = null
}

function createAssistantCharacterStream(assistantMessage) {
    destroyActiveCharacterStream()

    activeCharacterStream = createCharacterStreamRenderer({
        onChunk(chunk) {
            assistantMessage.content += chunk
            debugAi('render chunk', {
                chunk,
                rendered_length: assistantMessage.content.length,
            })
            queueScrollToBottom()
        },
        onComplete(payload) {
            assistantMessage.streaming = false
            assistantMessage.usage = payload?.usage || {}
            debugAi('render complete', {
                usage: assistantMessage.usage,
                rendered_text: assistantMessage.content,
            })
            queueScrollToBottom()
        },
    })

    return activeCharacterStream
}

async function consumeEventStream(response, onEvent) {
    if (!response.body) {
        throw new Error('Streaming is not supported by this browser.')
    }

    debugAi('stream response opened', {
        status: response.status,
        headers: Object.fromEntries(response.headers.entries()),
    })

    const reader = response.body.getReader()
    const decoder = new TextDecoder()
    let buffer = ''

    while (true) {
        const { value, done } = await reader.read()
        const decodedChunk = decoder.decode(value || new Uint8Array(), { stream: !done })

        debugAi('raw reader chunk', {
            done,
            byte_length: value?.byteLength ?? 0,
            preview: decodedChunk.slice(0, 200),
        })

        buffer += decodedChunk
        buffer = buffer.replaceAll('\r\n', '\n')

        let separatorIndex = buffer.indexOf('\n\n')

        while (separatorIndex !== -1) {
            const rawEvent = buffer.slice(0, separatorIndex).trim()
            buffer = buffer.slice(separatorIndex + 2)

            if (rawEvent !== '') {
                const data = rawEvent
                    .split('\n')
                    .filter((line) => line.startsWith('data:'))
                    .map((line) => line.slice(5).trim())
                    .join('\n')

                if (data === '[DONE]') {
                    return
                }

                if (data) {
                    const parsedEvent = JSON.parse(data)
                    debugAi('parsed event', parsedEvent)
                    onEvent(parsedEvent)
                }
            }

            separatorIndex = buffer.indexOf('\n\n')
        }

        if (done) {
            return
        }
    }
}

function handleStreamEvent(event, assistantMessage, characterStream) {
    if (event.type === 'text_delta') {
        debugAi('text delta received', {
            delta: event.delta || '',
            delta_length: (event.delta || '').length,
        })
        characterStream.push(event.delta || '')
        return
    }

    if (event.type === 'tool_call') {
        debugAi('tool call received', event)
        assistantMessage.tool_calls = [...normalizeList(assistantMessage.tool_calls), event]
        queueScrollToBottom()
        return
    }

    if (event.type === 'tool_result') {
        debugAi('tool result received', event)
        assistantMessage.tool_results = [...normalizeList(assistantMessage.tool_results), event]
        queueScrollToBottom()
        return
    }

    if (event.type === 'artifact') {
        debugAi('artifact received', event)
        assistantMessage.artifacts = [...normalizeList(assistantMessage.artifacts), event]
        queueScrollToBottom()
        return
    }

    if (event.type === 'stream_end') {
        debugAi('stream end received', event)
        void characterStream.complete({
            usage: normalizeMap(event.usage),
        })
        return
    }

    if ('message' in event) {
        debugAi('stream error received', event)
        characterStream.destroy()
        assistantMessage.streaming = false
        assistantMessage.error_message = event.message
        streamError.value = event.message
    }
}

async function submitPrompt() {
    requestError.value = ''
    streamError.value = ''

    if (!composer.value.trim()) {
        requestError.value = 'Message is required.'
        return
    }

    const prompt = composer.value.trim()
    composer.value = ''
    streaming.value = true

    let assistantMessage = null
    let characterStream = null

    try {
        const sessionId = await ensureSession()

        assistantMessage = appendOptimisticMessages(prompt)
        characterStream = createAssistantCharacterStream(assistantMessage)
        debugAi('submit prompt', {
            session_id: sessionId,
            prompt,
        })
        touchCurrentSession(prompt)
        await scrollToBottom()

        const response = await fetch(`/ai/sessions/${sessionId}/messages/stream`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'text/event-stream',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                prompt,
                artifact_mode: effectiveArtifactMode.value,
            }),
        })

        if (!response.ok) {
            throw new Error('Unable to stream the assistant response.')
        }

        await consumeEventStream(response, (event) => {
            handleStreamEvent(event, assistantMessage, characterStream)
        })

        await characterStream.complete()
        if (activeCharacterStream === characterStream) {
            activeCharacterStream = null
        }

        if (assistantMessage.error_message) {
            throw new Error(assistantMessage.error_message)
        }

        toast.success('Assistant response completed.')
    } catch (error) {
        const message =
            error.response?.data?.message || error.message || 'Unable to stream assistant response.'

        streamError.value = message

        if (assistantMessage) {
            characterStream?.destroy()
            if (activeCharacterStream === characterStream) {
                activeCharacterStream = null
            }
            assistantMessage.streaming = false
            assistantMessage.error_message = message
        }

        toast.error(message)
    } finally {
        streaming.value = false
    }
}

onBeforeUnmount(() => {
    destroyActiveCharacterStream()
    cancelQueuedScroll()
})
</script>

<template>
    <Head title="AI Chat" />

    <div class="bg-background flex h-full min-h-0 flex-col">
        <section
            class="border-border/30 bg-card/85 flex min-h-0 flex-1 overflow-hidden border-t shadow-2xl shadow-black/5"
        >
            <div class="grid h-full min-h-0 flex-1 lg:grid-cols-[19rem_minmax(0,1fr)]">
                <aside
                    class="border-border/30 bg-background/92 flex min-h-0 flex-col border-b lg:border-r lg:border-b-0"
                >
                    <div class="border-border/30 border-b px-4 py-4 sm:px-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p
                                    class="text-muted-foreground text-[11px] font-semibold tracking-[0.18em] uppercase"
                                >
                                    Workspace AI
                                </p>
                                <p class="text-foreground mt-1 text-sm font-semibold">
                                    {{ workspace.name }}
                                </p>
                            </div>

                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="streaming"
                                @click="createSession"
                            >
                                New chat
                            </Button>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <div class="bg-surface/55 rounded-2xl px-3 py-2">
                                <p
                                    class="text-muted-foreground text-[10px] tracking-[0.18em] uppercase"
                                >
                                    Role
                                </p>
                                <p class="text-foreground mt-1 text-xs font-semibold">
                                    {{ formattedRole }}
                                </p>
                            </div>
                            <div class="bg-surface/55 rounded-2xl px-3 py-2">
                                <p
                                    class="text-muted-foreground text-[10px] tracking-[0.18em] uppercase"
                                >
                                    Tools
                                </p>
                                <p class="text-foreground mt-1 truncate text-xs font-semibold">
                                    {{ availableToolsLabel }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="groupedSessions.length > 0"
                        class="min-h-0 flex-1 space-y-5 overflow-y-auto px-4 py-4 sm:px-5"
                    >
                        <div
                            v-for="[label, items] in groupedSessions"
                            :key="label"
                            class="space-y-2"
                        >
                            <p
                                class="text-muted-foreground text-[10px] font-semibold tracking-[0.18em] uppercase"
                            >
                                {{ label }}
                            </p>

                            <div class="space-y-2">
                                <button
                                    v-for="session in items"
                                    :key="session.id"
                                    type="button"
                                    class="border-border/40 w-full rounded-2xl border px-3 py-3 text-left transition-all duration-200"
                                    :class="
                                        session.id === activeSessionId
                                            ? 'border-cyan-500/30 bg-cyan-500/[0.08]'
                                            : 'bg-surface/35 hover:bg-surface/60'
                                    "
                                    :disabled="streaming"
                                    @click="openSession(session.id)"
                                >
                                    <p class="text-foreground truncate text-sm font-semibold">
                                        {{ session.title }}
                                    </p>
                                    <p class="text-muted-foreground mt-1 text-xs">
                                        {{
                                            formatRelativeTimestamp(
                                                session.last_message_at || session.updated_at,
                                            )
                                        }}
                                    </p>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="border-border/40 bg-surface/25 mx-4 my-4 rounded-3xl border border-dashed px-4 py-10 text-center sm:mx-5"
                    >
                        <p class="text-foreground text-sm font-semibold">No sessions yet</p>
                        <p class="text-muted-foreground mt-2 text-xs leading-5">
                            Start a new conversation to keep workspace requests in one lane.
                        </p>
                    </div>
                </aside>

                <div class="flex min-h-0 flex-col">
                    <div class="border-border/30 bg-background/88 border-b px-5 py-4 sm:px-6">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="space-y-1">
                                <p
                                    class="text-muted-foreground text-[11px] font-semibold tracking-[0.18em] uppercase"
                                >
                                    Assistant
                                </p>
                                <h2 class="text-foreground text-lg font-semibold">
                                    {{ currentSession?.title || 'Workspace assistant' }}
                                </h2>
                                <p class="text-muted-foreground text-sm">
                                    Single-lane chat with session memory, tool calls, and streamed output.
                                </p>
                            </div>

                            <Badge variant="soft" :tone="streaming ? 'sky' : 'amber'">
                                {{ streaming ? 'Streaming' : 'Idle' }}
                            </Badge>
                        </div>
                    </div>

                    <div
                        ref="chatLaneRef"
                        class="min-h-0 flex-1 space-y-6 overflow-y-auto bg-linear-to-b from-transparent via-cyan-500/[0.01] to-transparent px-4 py-5 sm:px-6"
                    >
                        <div
                            v-if="!hasMessages"
                            class="border-border/40 bg-surface/30 mx-auto w-full rounded-[1.75rem] border border-dashed px-6 py-12 text-center lg:w-10/12 xl:w-8/12"
                        >
                            <div
                                class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-3xl bg-linear-to-br from-cyan-500/12 to-amber-500/12"
                            >
                                <svg
                                    class="h-7 w-7 text-cyan-400"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                >
                                    <path
                                        d="M12 3l1.9 4.6L19 9.5l-4 3.4 1.2 5.1L12 15.5 7.8 18l1.2-5.1-4-3.4 5.1-1.9L12 3z"
                                    />
                                </svg>
                            </div>

                            <h3 class="text-foreground text-xl font-semibold">
                                Start from one conversation lane
                            </h3>
                            <p
                                class="text-muted-foreground mx-auto mt-3 w-full text-sm leading-6 lg:w-9/12"
                            >
                                Session context, workspace scope, and tool execution now stay in a
                                single chat surface. Available runtime tools are exposed from the
                                active workspace registry:
                                <code class="rounded bg-black/5 px-1.5 py-0.5 text-xs">{{
                                    availableToolsLabel
                                }}</code
                                >.
                            </p>
                        </div>

                        <div
                            v-for="message in messages"
                            :key="message.id"
                            class="mx-auto w-full space-y-3 xl:w-[92%]"
                        >
                            <div
                                class="flex"
                                :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                            >
                                <div
                                    class="w-full rounded-[1.75rem] px-5 py-4 shadow-lg"
                                    :class="
                                        message.role === 'user'
                                            ? 'rounded-br-md bg-linear-to-br from-cyan-500/95 to-sky-400/90 text-black shadow-cyan-500/10 sm:w-10/12 lg:w-8/12 xl:w-7/12'
                                            : 'border-border/40 bg-background/90 rounded-bl-md border shadow-black/5 sm:w-11/12 lg:w-9/12 xl:w-8/12'
                                    "
                                >
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <span
                                            class="text-[11px] font-semibold tracking-[0.18em] uppercase"
                                            :class="
                                                message.role === 'user'
                                                    ? 'text-black/70'
                                                    : 'text-foreground'
                                            "
                                        >
                                            {{ message.role === 'user' ? 'You' : 'Assistant' }}
                                        </span>
                                        <span
                                            class="text-xs"
                                            :class="
                                                message.role === 'user'
                                                    ? 'text-black/70'
                                                    : 'text-muted-foreground'
                                            "
                                        >
                                            {{ formatTimestamp(message.created_at) }}
                                        </span>
                                    </div>

                                    <pre
                                        v-if="message.content && message.role === 'user'"
                                        class="overflow-x-auto text-sm leading-6 whitespace-pre-wrap"
                                        >{{ formatPayload(message.content) }}</pre
                                    >

                                    <AiMarkdown
                                        v-else-if="message.content"
                                        :content="message.content"
                                        :tone="message.role === 'user' ? 'inverse' : 'default'"
                                    />

                                    <p
                                        v-else-if="
                                            message.role === 'assistant' && message.streaming
                                        "
                                        class="text-muted-foreground text-sm"
                                    >
                                        Streaming response...
                                    </p>

                                    <p
                                        v-else-if="
                                            message.role === 'assistant' &&
                                            message.artifacts?.length === 0
                                        "
                                        class="text-muted-foreground text-sm"
                                    >
                                        Tool execution completed without assistant text.
                                    </p>

                                    <div
                                        v-if="message.artifacts?.length > 0"
                                        class="mt-4 space-y-2"
                                    >
                                        <AiArtifactRenderer
                                            v-for="artifact in message.artifacts"
                                            :key="artifact.id"
                                            :artifact="artifact"
                                        />
                                    </div>

                                    <AiToolTracePanel
                                        v-if="aiDebugEnabled"
                                        :tool-calls="message.tool_calls || []"
                                        :tool-results="message.tool_results || []"
                                        :format-payload="formatPayload"
                                    />

                                    <div
                                        v-if="message.error_message"
                                        class="mt-4 rounded-2xl border border-rose-500/20 bg-rose-500/[0.08] px-4 py-3 text-sm text-rose-500"
                                    >
                                        {{ message.error_message }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-border/30 bg-background/92 border-t px-4 py-4 sm:px-6">
                        <div
                            v-if="streamError"
                            class="mb-4 rounded-2xl border border-rose-500/20 bg-rose-500/[0.08] px-4 py-3 text-sm text-rose-500"
                        >
                            {{ streamError }}
                        </div>

                        <form class="space-y-4" @submit.prevent="submitPrompt">
                            <div class="space-y-2">
                                <label
                                    for="ai-chat-message"
                                    class="text-foreground/90 text-xs font-semibold tracking-[0.18em] uppercase"
                                >
                                    Message
                                </label>
                                <div v-if="aiDebugEnabled" class="flex flex-wrap gap-2">
                                    <button
                                        v-for="mode in availableArtifactModes"
                                        :key="mode.value"
                                        type="button"
                                        class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors"
                                        :class="
                                            artifactMode === mode.value
                                                ? 'border-cyan-500/40 bg-cyan-500/10 text-cyan-600'
                                                : 'border-border/60 bg-background text-muted-foreground hover:bg-surface/70'
                                        "
                                        :disabled="streaming"
                                        @click="artifactMode = mode.value"
                                    >
                                        {{ mode.label }}
                                    </button>
                                </div>
                                <textarea
                                    id="ai-chat-message"
                                    v-model="composer"
                                    rows="5"
                                    class="border-border/50 bg-surface/60 text-foreground placeholder:text-muted-foreground/70 focus:ring-primary/20 focus:border-primary/35 min-h-36 w-full rounded-[1.5rem] border px-4 py-3 text-sm leading-6 transition-all duration-200 focus:ring-2 focus:outline-none"
                                    :placeholder="composerPlaceholder"
                                />
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="space-y-1">
                                    <p class="text-muted-foreground text-sm">
                                        Active tool lane:
                                        <code class="rounded bg-black/5 px-1.5 py-0.5 text-xs">
                                            {{ availableToolsLabel }}
                                        </code>
                                    </p>
                                    <p v-if="aiDebugEnabled" class="text-muted-foreground text-sm">
                                        Output mode:
                                        <code class="rounded bg-black/5 px-1.5 py-0.5 text-xs">
                                            {{ artifactModeLabel }}
                                        </code>
                                    </p>
                                    <p v-if="requestError" class="text-sm text-rose-500">
                                        {{ requestError }}
                                    </p>
                                </div>

                                <Button
                                    type="submit"
                                    variant="cyan"
                                    size="lg"
                                    class="min-w-56"
                                    :disabled="streaming"
                                >
                                    <svg
                                        v-if="streaming"
                                        class="h-4 w-4 animate-spin"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        />
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"
                                        />
                                    </svg>
                                    {{
                                        streaming
                                            ? 'Streaming response...'
                                            : 'Send to workspace assistant'
                                    }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
