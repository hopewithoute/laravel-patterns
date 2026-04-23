export function createCharacterStreamRenderer({
    charsPerTick = 1,
    interval = 16,
    onChunk = () => {},
    onComplete = () => {},
    schedule = (callback, delay) => setTimeout(callback, delay),
    cancel = clearTimeout,
} = {}) {
    let buffer = ''
    let timerId = null
    let completionPayload = {}
    let completionRequested = false
    let destroyed = false
    let resolveFinished = () => {}
    let finishedResolved = false

    const finished = new Promise((resolve) => {
        resolveFinished = resolve
    })

    function resolveFinishedOnce() {
        if (finishedResolved) {
            return
        }

        finishedResolved = true
        resolveFinished()
    }

    function maybeComplete() {
        if (destroyed || timerId !== null || buffer !== '' || !completionRequested) {
            return
        }

        onComplete(completionPayload)
        resolveFinishedOnce()
    }

    function drainBuffer() {
        timerId = null

        if (destroyed) {
            return
        }

        if (buffer === '') {
            maybeComplete()
            return
        }

        const chunk = buffer.slice(0, charsPerTick)
        buffer = buffer.slice(charsPerTick)
        onChunk(chunk)

        timerId = schedule(drainBuffer, interval)
    }

    function ensureDrain() {
        if (destroyed || timerId !== null) {
            return
        }

        if (buffer === '') {
            maybeComplete()
            return
        }

        timerId = schedule(drainBuffer, interval)
    }

    return {
        push(value) {
            if (destroyed || !value) {
                return
            }

            buffer += value
            ensureDrain()
        },
        complete(payload = {}) {
            if (destroyed) {
                return finished
            }

            completionRequested = true
            completionPayload = payload
            ensureDrain()
            maybeComplete()

            return finished
        },
        destroy() {
            if (destroyed) {
                return
            }

            destroyed = true

            if (timerId !== null) {
                cancel(timerId)
                timerId = null
            }

            buffer = ''
            resolveFinishedOnce()
        },
    }
}
