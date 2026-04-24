import './bootstrap'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { MotionPlugin } from '@vueuse/motion'

createInertiaApp({
    title: (title) => (title ? `${title} - TaskFlow` : 'TaskFlow'),
    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob('./pages/**/*.vue'),
        )

        if (page.default.layout === undefined) {
            const excludePatterns = [
                'Auth/',
                'Workspace/',
                'Welcome',
                'Landing',
                'Terms',
                'Privacy',
                'Api',
                'Contact',
            ]
            if (!excludePatterns.some((pattern) => name.startsWith(pattern))) {
                page.default.layout = AppLayout
            }
        }

        return page
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(MotionPlugin)
            .mount(el)
    },
    progress: {
        color: '#4B5563',
    },
})
