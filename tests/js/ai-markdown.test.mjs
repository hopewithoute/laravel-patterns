import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'

import { renderMarkdownToHtml } from '../../resources/js/components/ai/renderMarkdown.js'

test('markdown renderer uses battle-tested libraries instead of the old custom parser', () => {
    const source = fs.readFileSync('resources/js/components/ai/renderMarkdown.js', 'utf8')

    assert.match(source, /import createDOMPurify from 'dompurify'/)
    assert.match(source, /import MarkdownIt from 'markdown-it'/)
    assert.doesNotMatch(source, /function escapeHtml/)
    assert.doesNotMatch(source, /function looksLikeMarkdown/)
})

test('markdown renderer escapes raw html and adds safe link attributes', () => {
    const html = renderMarkdownToHtml(
        '# Release Plan\n\nSee [docs](https://example.com).\n\n<script>alert(1)</script>',
    )

    assert.match(html, /<h1>Release Plan<\/h1>/)
    assert.match(html, /href="https:\/\/example.com"/)
    assert.match(html, /target="_blank"/)
    assert.match(html, /rel="noreferrer noopener"/)
    assert.match(html, /&lt;script&gt;alert\(1\)&lt;\/script&gt;/)
})

test('markdown renderer preserves fenced code blocks', () => {
    const html = renderMarkdownToHtml('```json\n{\"ok\":true}\n```')

    assert.match(html, /<pre><code class="language-json">\{&quot;ok&quot;:true\}\n<\/code><\/pre>/)
})
