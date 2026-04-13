import { ref } from 'vue'

const THEME_KEY = 'theme'
const DARK = 'dark'
const LIGHT = 'light'

const theme = ref(DARK)

// Apply theme to document
function applyTheme(value) {
    const html = document.documentElement

    html.classList.remove(DARK, LIGHT)
    html.classList.add(value === DARK ? DARK : LIGHT)
}

// Save theme to localStorage
function saveTheme(value) {
    localStorage.setItem(THEME_KEY, value)
}

// Initialize theme from localStorage or system preference
function initTheme() {
    const stored = localStorage.getItem(THEME_KEY)

    if (stored === LIGHT || stored === DARK) {
        theme.value = stored
    } else {
        // Check system preference
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
        theme.value = prefersDark ? DARK : LIGHT
    }

    applyTheme(theme.value)
}

// Toggle theme
function toggleTheme() {
    theme.value = theme.value === DARK ? LIGHT : DARK
    saveTheme(theme.value)
    applyTheme(theme.value)
}

// Set specific theme
function setTheme(value) {
    if (value === DARK || value === LIGHT) {
        theme.value = value
        saveTheme(theme.value)
        applyTheme(theme.value)
    }
}

// Export composable
export function useTheme() {
    return {
        theme,
        isDark: () => theme.value === DARK,
        isLight: () => theme.value === LIGHT,
        toggleTheme,
        setTheme,
        initTheme,
    }
}
