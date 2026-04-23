import createDOMPurify from 'dompurify'
import MarkdownIt from 'markdown-it'

const markdown = new MarkdownIt({
    html: false,
    linkify: true,
    typographer: true,
})

const defaultLinkRenderer =
    markdown.renderer.rules.link_open ||
    ((tokens, index, options, environment, self) => self.renderToken(tokens, index, options))

markdown.renderer.rules.link_open = (tokens, index, options, environment, self) => {
    const href = tokens[index].attrGet('href') || ''

    if (!href.startsWith('#')) {
        tokens[index].attrSet('target', '_blank')
        tokens[index].attrSet('rel', 'noreferrer noopener')
    }

    return defaultLinkRenderer(tokens, index, options, environment, self)
}

const domPurify = typeof window !== 'undefined' ? createDOMPurify(window) : null

export function renderMarkdownToHtml(markdownText) {
    const renderedHtml = markdown.render(String(markdownText || ''))

    if (domPurify === null) {
        return renderedHtml
    }

    return domPurify.sanitize(renderedHtml, {
        USE_PROFILES: {
            html: true,
        },
    })
}
