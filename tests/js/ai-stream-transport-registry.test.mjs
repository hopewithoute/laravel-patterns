import test from 'node:test'
import assert from 'node:assert/strict'
import { TextEncoder } from 'node:util'

import { createAiStreamTransportRegistry } from '../../resources/js/pages/Ai/streamTransportRegistry.js'

test('registry consumes SSE responses and emits parsed events', async (t) => {
    const chunks = [
        'data: {"type":"text_delta","delta":"Hel"}\n\n',
        'data: {"type":"text_delta","delta":"lo"}\n\n',
        'data: [DONE]\n\n',
    ]
    const encoder = new TextEncoder()
    const events = []
    let cancelled = false
    const previousFetch = globalThis.fetch

    t.after(() => {
        globalThis.fetch = previousFetch
    })

    globalThis.fetch = async () => ({
        ok: true,
        headers: {
            get(name) {
                return name === 'content-type' ? 'text/event-stream; charset=utf-8' : null
            },
            entries() {
                return [['content-type', 'text/event-stream; charset=utf-8']]
            },
        },
        body: {
            getReader() {
                return {
                    async read() {
                        const chunk = chunks.shift()

                        if (chunk === undefined) {
                            return { done: true, value: undefined }
                        }

                        return { done: false, value: encoder.encode(chunk) }
                    },
                    cancel() {
                        cancelled = true
                    },
                }
            },
        },
    })

    const registry = createAiStreamTransportRegistry()
    const session = await registry.open({
        url: '/ai/sessions/session-1/messages/stream',
        payload: { prompt: 'Hello' },
        onEvent(event) {
            events.push(event)
        },
    })

    await session.finished

    assert.deepEqual(events, [
        { type: 'text_delta', delta: 'Hel' },
        { type: 'text_delta', delta: 'lo' },
    ])

    session.cancel()
    assert.equal(cancelled, true)
})

test('registry subscribes to Mercure descriptors and closes on done', async (t) => {
    const events = []
    const closings = []
    let mercureClient = null
    const previousEventSource = globalThis.EventSource
    const previousFetch = globalThis.fetch
    const previousWindow = globalThis.window

    t.after(() => {
        globalThis.EventSource = previousEventSource
        globalThis.fetch = previousFetch
        globalThis.window = previousWindow
    })

    globalThis.EventSource = class EventSourceMock {}
    globalThis.window = {
        location: {
            href: 'https://app.test/ai',
        },
    }
    globalThis.fetch = async () => ({
        ok: true,
        headers: {
            get() {
                return 'application/json'
            },
        },
        async json() {
            return {
                stream: {
                    driver: 'mercure',
                    session_id: 'session-123',
                    subscription: {
                        topic: 'workspace-ai/session-123',
                    },
                    meta: {
                        hub_url: '/.well-known/mercure',
                    },
                },
            }
        },
    })

    const registry = createAiStreamTransportRegistry({
        createEventSource(url) {
            mercureClient = {
                url,
                onopen: null,
                onmessage: null,
                onerror: null,
                close() {
                    closings.push(url)
                },
            }

            return mercureClient
        },
    })

    const session = await registry.open({
        url: '/ai/sessions/session-123/messages/stream',
        payload: { prompt: 'Hello' },
        onEvent(event) {
            events.push(event)
        },
    })

    assert.equal(
        mercureClient.url,
        'https://app.test/.well-known/mercure?topic=workspace-ai%2Fsession-123',
    )

    mercureClient.onopen?.()
    mercureClient.onmessage?.({
        data: JSON.stringify({ type: 'text_delta', delta: 'Hello' }),
    })
    mercureClient.onmessage?.({
        data: JSON.stringify({ type: 'done' }),
    })

    await session.finished

    assert.deepEqual(events, [{ type: 'text_delta', delta: 'Hello' }])
    assert.equal(closings.length, 1)
})

test('registry rejects redis descriptors in the browser', async (t) => {
    const previousFetch = globalThis.fetch

    t.after(() => {
        globalThis.fetch = previousFetch
    })

    globalThis.fetch = async () => ({
        ok: true,
        headers: {
            get() {
                return 'application/json'
            },
        },
        async json() {
            return {
                stream: {
                    driver: 'redis',
                    session_id: 'session-123',
                    subscription: {
                        channel: 'workspace-ai:session-123',
                    },
                },
            }
        },
    })

    const registry = createAiStreamTransportRegistry()

    await assert.rejects(
        () =>
            registry.open({
                url: '/ai/sessions/session-123/messages/stream',
                payload: { prompt: 'Hello' },
                onEvent() {},
            }),
        /Redis AI stream transport is backend-only and not available in the browser\./,
    )
})
