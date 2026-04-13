import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'

const badgeComponentPath = 'resources/js/components/ui/Badge.vue'
const statusPages = [
    'resources/js/pages/Dashboard.vue',
    'resources/js/pages/Dashboard/Index.vue',
    'resources/js/pages/Task/Index.vue',
    'resources/js/pages/Task/Show.vue',
    'resources/js/pages/Project/Index.vue',
    'resources/js/pages/Project/Show.vue',
    'resources/js/pages/Team/Index.vue',
]

test('status badges use the shared Badge component with explicit variants', () => {
    const badge = fs.readFileSync(badgeComponentPath, 'utf8')

    assert.match(badge, /import\s+\{\s*cva\s*\}\s+from\s+['"]class-variance-authority['"]/)
    assert.match(badge, /variant:\s*\{/)
    assert.match(badge, /tone:\s*\{/)
    assert.match(badge, /pill:/)
    assert.match(badge, /emerald:/)
    assert.match(badge, /surface:/)

    for (const path of statusPages) {
        const page = fs.readFileSync(path, 'utf8')

        assert.match(page, /import Badge from ['"]@\/components\/ui\/Badge\.vue['"]/)
        assert.match(page, /<Badge[\s>]/)
        assert.doesNotMatch(page, /status-todo|status-progress|status-review|status-done/)
        assert.doesNotMatch(page, /priority-low|priority-medium|priority-high|priority-urgent/)
        assert.doesNotMatch(page, /badge-pill/)
    }
})
