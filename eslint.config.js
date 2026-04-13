import js from '@eslint/js'
import pluginVue from 'eslint-plugin-vue'
import configPrettier from '@vue/eslint-config-prettier'
import globals from 'globals'

export default [
    {
        ignores: [
            'vendor/**',
            'public/**',
            'storage/**',
            'bootstrap/**',
            'node_modules/**',
            'dist/**',
        ],
    },
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    configPrettier,
    {
        files: ['**/*.{js,vue}'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node,
                axios: 'readonly',
                route: 'readonly',
                _: 'readonly', // lodash
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
            'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
            'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
            // Disabled for shadcn-vue component library pattern
            'vue/require-default-prop': 'off',
            // v-html is used intentionally for rendering sanitized HTML
            'vue/no-v-html': 'off',
            // Allow snake_case props for backend compatibility
            'vue/prop-name-casing': 'off',
        },
    },
]
