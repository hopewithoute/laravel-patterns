<script setup>
import {
    SidebarFooter,
    SidebarMenu,
    SidebarMenuItem,
    SidebarMenuButton,
} from '@/components/ui/sidebar'

defineProps({
    user: {
        type: Object,
        default: null,
    },
    isOpen: {
        type: Boolean,
        default: true,
    },
})

const icons = {
    logout: `<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" x2="9" y1="12" y2="12" />`,
}
</script>

<template>
    <SidebarFooter class="relative z-10">
        <SidebarMenu>
            <SidebarMenuItem v-if="user">
                <SidebarMenuButton
                    href="/logout"
                    method="post"
                    as="button"
                    class="text-muted-foreground w-full rounded-xl px-3 py-2.5 transition-colors duration-200 hover:bg-rose-500/10 hover:text-rose-400"
                >
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
                        class="shrink-0"
                    >
                        <g v-html="icons.logout" />
                    </svg>
                    <span
                        :class="[
                            'text-sm transition-all duration-200',
                            isOpen ? 'opacity-100' : 'w-0 opacity-0',
                        ]"
                    >
                        Sign out
                    </span>
                </SidebarMenuButton>
            </SidebarMenuItem>
            <SidebarMenuItem v-else>
                <SidebarMenuButton
                    href="/login"
                    class="text-muted-foreground w-full rounded-xl px-3 py-2.5 transition-colors duration-200 hover:bg-amber-500/10 hover:text-amber-400"
                >
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
                        class="shrink-0"
                    >
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                        <polyline points="10 17 15 12 10 7" />
                        <line x1="15" x2="3" y1="12" y2="12" />
                    </svg>
                    <span
                        :class="[
                            'text-sm transition-all duration-200',
                            isOpen ? 'opacity-100' : 'w-0 opacity-0',
                        ]"
                    >
                        Sign in
                    </span>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarFooter>
</template>
