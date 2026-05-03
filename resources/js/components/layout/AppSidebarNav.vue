<script setup>
import { usePage } from '@inertiajs/vue3'
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuItem,
    SidebarMenuButton,
} from '@/components/ui/sidebar'

defineProps({
    isOpen: {
        type: Boolean,
        default: true,
    },
})

const page = usePage()

const navItems = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: `<circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" />`,
        description: 'Overview & analytics',
    },
    {
        title: 'Projects',
        href: '/projects',
        icon: `<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z" />`,
        description: 'Your workspaces',
    },
    {
        title: 'Tasks',
        href: '/tasks',
        icon: `<path d="M9 11l3 3L22 4" /><path d="M21 12v7a2 2 0 0 1-2-2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />`,
        description: 'Action items',
    },
    {
        title: 'Team',
        href: '/team',
        icon: `<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />`,
        description: 'Members & roles',
    },
    {
        title: 'Settings',
        href: '/settings',
        icon: `<circle cx="12" cy="12" r="3" /><path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m7.08 7.08l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m7.08-7.08l4.24-4.24" />`,
        description: 'Configuration',
    },
]

const isActive = (href) => {
    if (href === '/dashboard') {
        return page.url === '/dashboard' || page.url === '/'
    }
    return page.url.startsWith(href)
}
</script>

<template>
    <SidebarGroup>
        <SidebarGroupContent>
            <SidebarMenu class="space-y-1">
                <SidebarMenuItem v-for="item in navItems" :key="item.href">
                    <SidebarMenuButton
                        :href="item.href"
                        :is-active="isActive(item.href)"
                        :tooltip="item.title"
                        :class="[
                            'group relative rounded-xl px-3 py-2.5 transition-all duration-200',
                            isActive(item.href)
                                ? 'bg-linear-to-r from-amber-500/10 to-transparent text-amber-400 hover:text-amber-300'
                                : 'text-muted-foreground hover:text-foreground hover:bg-surface/50',
                        ]"
                    >
                        <!-- Active Indicator -->
                        <div
                            v-if="isActive(item.href)"
                            class="absolute top-1/2 left-0 h-5 w-0.5 -translate-y-1/2 rounded-full bg-amber-500"
                        ></div>

                        <!-- Icon -->
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            :class="[
                                'shrink-0 transition-transform duration-200',
                                isActive(item.href) ? '' : 'group-hover:scale-110',
                            ]"
                        >
                            <g v-html="item.icon" />
                        </svg>

                        <!-- Label -->
                        <div
                            :class="[
                                'overflow-hidden transition-all duration-200',
                                isOpen ? 'w-auto opacity-100' : 'w-0 opacity-0',
                            ]"
                        >
                            <span class="block text-sm font-medium">{{ item.title }}</span>
                            <span
                                class="text-muted-foreground/70 block font-mono text-[10px] tracking-wider uppercase"
                                >{{ item.description }}</span
                            >
                        </div>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
