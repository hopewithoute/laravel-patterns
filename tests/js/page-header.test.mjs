import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'

const pages = [
    'resources/js/pages/Dashboard/Index.vue',
    'resources/js/pages/Task/Index.vue',
    'resources/js/pages/Project/Index.vue',
    'resources/js/pages/Team/Index.vue',
    'resources/js/pages/Settings/Index.vue',
    'resources/js/pages/Task/Show.vue',
    'resources/js/pages/Project/Show.vue',
    'resources/js/pages/Task/Form.vue',
    'resources/js/pages/Project/Form.vue',
    'resources/js/pages/Team/Invite.vue',
]

test('active AppLayout pages share the PageHeader component for header rhythm', () => {
    const header = fs.readFileSync('resources/js/components/layout/PageHeader.vue', 'utf8')

    assert.match(header, /variant === 'hero'/)
    assert.match(header, /titleSize === 'headline'/)
    assert.match(header, /slot name="actions"/)
    assert.match(header, /slot name="media"/)

    for (const path of pages) {
        const page = fs.readFileSync(path, 'utf8')

        assert.match(page, /import PageHeader from ['"]@\/components\/layout\/PageHeader\.vue['"]/)
        assert.match(page, /<PageHeader/)
    }
})
