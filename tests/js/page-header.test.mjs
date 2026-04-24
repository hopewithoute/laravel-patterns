import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'

const standardPages = [
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

    for (const path of standardPages) {
        const page = fs.readFileSync(path, 'utf8')

        assert.match(page, /import PageHeader from ['"]@\/components\/layout\/PageHeader\.vue['"]/)
        assert.match(page, /<PageHeader/)
    }
})

test('AI chat page intentionally uses a fullscreen chat layout instead of PageHeader', () => {
    const page = fs.readFileSync('resources/js/pages/Ai/Index.vue', 'utf8')

    assert.doesNotMatch(
        page,
        /import PageHeader from ['"]@\/components\/layout\/PageHeader\.vue['"]/,
    )
    assert.doesNotMatch(page, /<PageHeader/)
    assert.match(page, /setLayoutProps\(\{/)
    assert.match(page, /mainClass: 'h-\[calc\(100dvh-3\.5rem\)\] overflow-hidden'/)
    assert.match(page, /contentClass: 'h-full p-0'/)
    assert.match(page, /Workspace AI/)
})
