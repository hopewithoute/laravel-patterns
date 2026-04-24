function createPendingSession(cancel, finished) {
    return {
        cancel,
        finished,
    }
}

function parseStreamDescriptor(payload) {
    const stream = payload?.stream

    if (!stream || typeof stream !== 'object' || Array.isArray(stream)) {
        throw new Error('Invalid AI stream transport response.')
    }

    if (typeof stream.driver !== 'string' || stream.driver === '') {
        throw new Error('Missing AI stream driver.')
    }

    return {
        stream,
    }
}

function parseStreamPayload(rawPayload) {
    const payload = typeof rawPayload === 'string' ? JSON.parse(rawPayload) : rawPayload

    if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
        throw new Error('Invalid AI stream payload.')
    }

    return payload
}

async function consumeSseResponse(response, onEvent, onDebug) {
    if (!response.body) {
        throw new Error('Streaming is not supported by this browser.')
    }

    onDebug('stream response opened', {
        status: response.status,
        headers: Object.fromEntries(response.headers.entries()),
    })

    const reader = response.body.getReader()
    const decoder = new TextDecoder()
    let buffer = ''

    const finished = (async () => {
        while (true) {
            const { value, done } = await reader.read()
            const decodedChunk = decoder.decode(value || new Uint8Array(), { stream: !done })

            onDebug('raw reader chunk', {
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

                    if (data !== '') {
                        const payload = parseStreamPayload(data)
                        onDebug('parsed event', payload)
                        onEvent(payload)
                    }
                }

                separatorIndex = buffer.indexOf('\n\n')
            }

            if (done) {
                return
            }
        }
    })()

    return createPendingSession(() => reader.cancel(), finished)
}

function createMercureSubscriptionUrl(hubUrl, topic) {
    const url = new URL(hubUrl, globalThis.window?.location?.href ?? 'http://localhost')
    url.searchParams.append('topic', topic)

    return url.toString()
}

function subscribeMercureStream(descriptor, onEvent, onDebug, createEventSource) {
    const topic = descriptor.stream.subscription?.topic
    const hubUrl = descriptor.stream.meta?.hub_url

    if (typeof topic !== 'string' || topic === '') {
        throw new Error('Mercure stream requires a subscription topic.')
    }

    if (typeof hubUrl !== 'string' || hubUrl === '') {
        throw new Error('Mercure stream requires a hub URL.')
    }

    if (typeof EventSource === 'undefined') {
        throw new Error('Mercure streaming is not supported by this browser.')
    }

    const mercureUrl = createMercureSubscriptionUrl(hubUrl, topic)
    const eventSource = createEventSource(mercureUrl)
    let settled = false

    const finished = new Promise((resolve, reject) => {
        const finish = () => {
            if (settled) {
                return
            }

            settled = true
            eventSource.close()
            resolve()
        }

        const fail = (message) => {
            if (settled) {
                return
            }

            settled = true
            eventSource.close()
            reject(new Error(message))
        }

        eventSource.onopen = () => {
            onDebug('mercure stream opened', {
                topic,
                url: mercureUrl,
            })
        }

        eventSource.onmessage = (messageEvent) => {
            try {
                const payload = parseStreamPayload(messageEvent.data)
                onDebug('mercure event received', payload)

                if (payload.type === 'done') {
                    finish()
                    return
                }

                onEvent(payload)
            } catch (error) {
                fail(error.message || 'Invalid Mercure stream payload.')
            }
        }

        eventSource.onerror = () => {
            fail('Mercure stream connection failed.')
        }
    })

    return createPendingSession(() => {
        if (settled) {
            return
        }

        settled = true
        eventSource.close()
    }, finished)
}

export function createAiStreamTransportRegistry({
    onDebug = () => {},
    createEventSource = (url) => new EventSource(url),
} = {}) {
    return {
        async open({ url, payload, headers = {}, credentials = 'same-origin', onEvent }) {
            const response = await fetch(url, {
                method: 'POST',
                credentials,
                headers,
                body: JSON.stringify(payload),
            })

            if (!response.ok) {
                throw new Error('Unable to stream the assistant response.')
            }

            const contentType = response.headers.get('content-type')?.toLowerCase() || ''

            if (contentType.includes('text/event-stream')) {
                return consumeSseResponse(response, onEvent, onDebug)
            }

            const descriptor = parseStreamDescriptor(await response.json())

            if (descriptor.stream.driver === 'mercure') {
                return subscribeMercureStream(descriptor, onEvent, onDebug, createEventSource)
            }

            if (descriptor.stream.driver === 'redis') {
                throw new Error(
                    'Redis AI stream transport is backend-only and not available in the browser.',
                )
            }

            throw new Error(`Unsupported AI stream driver: ${descriptor.stream.driver}`)
        },
    }
}
