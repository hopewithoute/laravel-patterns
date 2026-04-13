import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'

const pages = [
    ['resources/js/pages/Dashboard/Index.vue', 'wide'],
    ['resources/js/pages/Task/Index.vue', 'wide'],
    ['resources/js/pages/Project/Index.vue', 'wide'],
    ['resources/js/pages/Team/Index.vue', 'wide'],
    ['resources/js/pages/Task/Show.vue', 'wide'],
    ['resources/js/pages/Project/Show.vue', 'wide'],
    ['resources/js/pages/Team/Invite.vue', 'content'],
    ['resources/js/pages/Settings/Index.vue', 'wide'],
    ['resources/js/pages/Task/Form.vue', 'content'],
    ['resources/js/pages/Project/Form.vue', 'content'],
]

test('active AppLayout pages use the shared PageWidth wrapper', () => {
    const wrapper = fs.readFileSync('resources/js/components/layout/PageWidth.vue', 'utf8')

    assert.match(wrapper, /max-w-7xl/)
    assert.match(wrapper, /max-w-4xl/)
    assert.match(wrapper, /max-w-2xl/)

    for (const [path, size] of pages) {
        const page = fs.readFileSync(path, 'utf8')

        assert.match(page, /import PageWidth from ['"]@\/components\/layout\/PageWidth\.vue['"]/)
        assert.match(page, new RegExp(`<PageWidth\\s+size=\\"${size}\\"`))
        assert.doesNotMatch(page, /mx-auto max-w-\w/)
        assert.doesNotMatch(page, /px-4 sm:px-6 lg:px-8/)
    }
})
