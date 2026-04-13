import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'

function createClassList() {
    const classes = new Set()

    return {
        add(...tokens) {
            for (const token of tokens) classes.add(token)
        },
        remove(...tokens) {
            for (const token of tokens) classes.delete(token)
        },
        contains(token) {
            return classes.has(token)
        },
        toString() {
            return [...classes].sort().join(' ')
        },
    }
}

function installDomMocks({ storedTheme, prefersDark }) {
    const classList = createClassList()
    const storage = new Map()

    if (storedTheme) {
        storage.set('theme', storedTheme)
    }

    globalThis.document = {
        createElement() {
            return {}
        },
        documentElement: {
            classList,
        },
    }

    globalThis.localStorage = {
        getItem(key) {
            return storage.has(key) ? storage.get(key) : null
        },
        setItem(key, value) {
            storage.set(key, String(value))
        },
        removeItem(key) {
            storage.delete(key)
        },
    }

    globalThis.window = {
        matchMedia(query) {
            assert.equal(query, '(prefers-color-scheme: dark)')

            return {
                matches: prefersDark,
            }
        },
    }

    return { classList, storage }
}

test('theme utility applies a dark class for dark mode and removes it for light mode', async () => {
    const { classList } = installDomMocks({ storedTheme: 'dark', prefersDark: false })
    const { useTheme } = await import('../../resources/js/lib/theme.js')
    const { initTheme, setTheme } = useTheme()

    initTheme()

    assert.equal(classList.contains('dark'), true)
    assert.equal(classList.contains('light'), false)

    setTheme('light')

    assert.equal(classList.contains('dark'), false)
    assert.equal(classList.contains('light'), true)
})

test('app.css defines dark variant against the .dark selector for shadcn-vue components', () => {
    const css = fs.readFileSync('resources/css/app.css', 'utf8')

    assert.match(css, /@custom-variant\s+dark\b/)
    assert.match(css, /@theme\s+inline\b/)
    assert.match(css, /--color-background:\s*hsl\(var\(--background\)\);/)
    assert.match(css, /--color-foreground:\s*hsl\(var\(--foreground\)\);/)
})

test('global font selection uses Manrope as the sans font and removes legacy blade font loaders', () => {
    const css = fs.readFileSync('resources/css/app.css', 'utf8')
    const appBlade = fs.readFileSync('resources/views/app.blade.php', 'utf8')
    const welcomeBlade = fs.readFileSync('resources/views/welcome.blade.php', 'utf8')

    assert.match(css, /family=Cormorant\+Garamond:/)
    assert.match(css, /family=Manrope:/)
    assert.match(css, /--font-display:\s*'Cormorant Garamond',/)
    assert.match(css, /--font-sans:\s*'Manrope',/)
    assert.doesNotMatch(css, /family=Instrument\+Serif:/)
    assert.doesNotMatch(css, /family=.*Inter:/)
    assert.doesNotMatch(appBlade, /fonts\.bunny\.net/)
    assert.doesNotMatch(welcomeBlade, /fonts\.bunny\.net/)
})
