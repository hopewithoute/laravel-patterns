import test from 'node:test'
import assert from 'node:assert/strict'

import { createCharacterStreamRenderer } from '../../resources/js/pages/Ai/characterStreamRenderer.js'

function createManualScheduler() {
    let nextId = 0
    const tasks = new Map()

    return {
        schedule(callback) {
            nextId += 1
            tasks.set(nextId, callback)

            return nextId
        },
        cancel(id) {
            tasks.delete(id)
        },
        runNext() {
            const nextTask = tasks.entries().next().value

            if (!nextTask) {
                return false
            }

            const [id, callback] = nextTask
            tasks.delete(id)
            callback()

            return true
        },
        runAll() {
            while (this.runNext()) {
                continue
            }
        },
    }
}

test('assistant text is rendered one character at a time and completes after the buffer drains', async () => {
    const scheduler = createManualScheduler()
    const chunks = []
    const completed = []

    const renderer = createCharacterStreamRenderer({
        charsPerTick: 1,
        schedule: scheduler.schedule,
        cancel: scheduler.cancel,
        onChunk(chunk) {
            chunks.push(chunk)
        },
        onComplete(payload) {
            completed.push(payload)
        },
    })

    renderer.push('Halo')

    assert.deepEqual(chunks, [])

    scheduler.runNext()
    assert.deepEqual(chunks, ['H'])

    const finished = renderer.complete({
        usage: { output_tokens: 4 },
    })

    assert.deepEqual(completed, [])

    scheduler.runAll()
    await finished

    assert.equal(chunks.join(''), 'Halo')
    assert.deepEqual(completed, [{ usage: { output_tokens: 4 } }])
})

test('new deltas can be appended while the renderer is already draining', async () => {
    const scheduler = createManualScheduler()
    const chunks = []

    const renderer = createCharacterStreamRenderer({
        charsPerTick: 1,
        schedule: scheduler.schedule,
        cancel: scheduler.cancel,
        onChunk(chunk) {
            chunks.push(chunk)
        },
    })

    renderer.push('He')

    scheduler.runNext()
    renderer.push('llo')
    scheduler.runAll()
    await renderer.complete()

    assert.equal(chunks.join(''), 'Hello')
})
