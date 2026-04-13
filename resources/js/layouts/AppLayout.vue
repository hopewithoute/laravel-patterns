<script setup>
import { computed, watch, ref, onMounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import Sonner from '@/components/ui/Sonner.vue'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'
import { useTheme } from '@/lib/theme'
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarSeparator,
    SidebarTrigger,
} from '@/components/ui/sidebar'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
} from '@/components/ui/command'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'

const page = usePage()
const user = computed(() => page.props.auth.user)

// Theme management
const { initTheme } = useTheme()

onMounted(() => {
    initTheme()
})

// Organization switcher
const organizations = computed(() => page.props.organizations || [])
const activeOrganizationId = computed(() => page.props.activeOrganizationId || null)
const selectedOrgId = ref(activeOrganizationId.value)

// Get active organization object
const activeOrganization = computed(() => {
    return organizations.value.find((org) => org.id === activeOrganizationId.value)
})

// Watch for changes in activeOrganizationId prop
watch(activeOrganizationId, (newVal) => {
    selectedOrgId.value = newVal
})

// Select organization
const selectOrganization = (orgId) => {
    if (orgId !== activeOrganizationId.value) {
        router.post('/workspace/set', { organization_id: orgId })
    }
}

// Navigation items with enhanced icons
const navItems = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: 'dashboard',
        description: 'Overview & analytics',
    },
    {
        title: 'Projects',
        href: '/projects',
        icon: 'folder',
        description: 'Your workspaces',
    },
    {
        title: 'Tasks',
        href: '/tasks',
        icon: 'check-square',
        description: 'Action items',
    },
    {
        title: 'Team',
        href: '/team',
        icon: 'users',
        description: 'Members & roles',
    },
    {
        title: 'Settings',
        href: '/settings',
        icon: 'settings',
        description: 'Configuration',
    },
]

const icons = {
    dashboard: `<circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" />`,
    folder: `<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z" />`,
    'check-square': `<path d="M9 11l3 3L22 4" /><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />`,
    users: `<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />`,
    settings: `<circle cx="12" cy="12" r="3" /><path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m7.08 7.08l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m7.08-7.08l4.24-4.24" />`,
    logout: `<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" x2="9" y1="12" y2="12" />`,
    plus: `<line x1="12" x2="12" y1="5" y2="19" /><line x1="5" x2="19" y1="12" y2="12" />`,
    chevron: `<path d="m6 9 6 6 6-6"/>`,
}

const isActive = (href) => {
    if (href === '/dashboard') {
        return page.url === '/dashboard' || page.url === '/'
    }
    return page.url.startsWith(href)
}

// Handle flash messages
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return
        if (flash.success) toast.success(flash.success)
        if (flash.error) toast.error(flash.error)
        if (flash.warning) toast.warning(flash.warning)
        if (flash.info) toast.info(flash.info)
        if (flash.message && typeof flash.message === 'object') {
            const { type, text } = flash.message
            if (type && text) toast[type](text)
        }
    },
    { immediate: true },
)

// Handle validation errors
watch(
    () => page.props.errors,
    (errors) => {
        if (errors && Object.keys(errors).length > 0) {
            const firstError = Object.values(errors)[0]
            toast.error(firstError)
        }
    },
)
</script>

<template>
    <SidebarProvider>
        <template #default="{ isOpen }">
            <!-- ═══════════════════════════════════════════════════════════════════
           SIDEBAR - Lunar Obsidian Design
           ═══════════════════════════════════════════════════════════════════ -->
            <Sidebar
                :data-state="isOpen ? 'open' : 'closed'"
                class="sidebar-glow border-border/50 border-r"
            >
                <!-- Header with Logo -->
                <SidebarHeader class="relative z-10">
                    <Link href="/" class="group flex items-center gap-3 px-3 py-4">
                        <!-- Logo Mark -->
                        <div class="relative">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-linear-to-br from-amber-400 via-amber-500 to-orange-500 shadow-lg shadow-amber-500/20 transition-shadow duration-300 group-hover:shadow-amber-500/40"
                            >
                                <svg
                                    class="h-5 w-5 text-black"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <path
                                        d="M12 2L2 7l10 5 10-5-10-5z"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M2 17l10 5 10-5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M2 12l10 5 10-5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                            <!-- Glow effect -->
                            <div
                                class="absolute inset-0 rounded-xl bg-amber-500/20 opacity-0 blur-xl transition-opacity duration-300 group-hover:opacity-100"
                            ></div>
                        </div>
                        <!-- Logo Text -->
                        <div
                            :class="[
                                'overflow-hidden transition-all duration-200',
                                isOpen ? 'w-auto opacity-100' : 'w-0 opacity-0',
                            ]"
                        >
                            <span
                                class="font-display text-foreground text-lg font-bold tracking-tight"
                                >TaskFlow</span
                            >
                            <span
                                class="text-muted-foreground block font-mono text-[10px] tracking-widest uppercase"
                                >Workspace</span
                            >
                        </div>
                    </Link>
                </SidebarHeader>

                <SidebarContent class="relative z-10">
                    <!-- Organization Switcher -->
                    <div v-if="organizations.length > 0" class="mb-4 px-3">
                        <Popover>
                            <PopoverTrigger as-child>
                                <button
                                    class="group border-border/60 bg-surface/50 hover:bg-surface hover:border-border flex w-full items-center gap-2.5 rounded-xl border px-3 py-2.5 text-sm transition-all duration-200"
                                >
                                    <Avatar class="h-7 w-7 shrink-0 ring-2 ring-amber-500/20">
                                        <AvatarImage
                                            v-if="activeOrganization?.logo"
                                            :src="activeOrganization.logo"
                                            :alt="activeOrganization.name"
                                        />
                                        <AvatarFallback
                                            class="bg-linear-to-br from-amber-400 to-orange-500 text-[11px] font-bold text-black"
                                        >
                                            {{
                                                activeOrganization?.name
                                                    ?.charAt(0)
                                                    ?.toUpperCase() || 'O'
                                            }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div
                                        :class="[
                                            'flex-1 text-left transition-all duration-200',
                                            isOpen ? 'opacity-100' : 'w-0 opacity-0',
                                        ]"
                                    >
                                        <span
                                            class="text-foreground block truncate text-sm font-medium"
                                            >{{
                                                activeOrganization?.name || 'Select workspace'
                                            }}</span
                                        >
                                        <span
                                            class="text-muted-foreground block font-mono text-[10px] tracking-wider uppercase"
                                            >Active</span
                                        >
                                    </div>
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="14"
                                        height="14"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        :class="[
                                            'text-muted-foreground group-hover:text-foreground shrink-0 transition-transform duration-200',
                                            isOpen ? 'opacity-100' : 'opacity-0',
                                        ]"
                                    >
                                        <path d="m6 9 6 6 6-6" />
                                    </svg>
                                </button>
                            </PopoverTrigger>
                            <PopoverContent
                                class="glass-elevated w-[--radix-popover-trigger-width] rounded-xl p-0"
                                align="start"
                                :side-offset="8"
                            >
                                <Command class="bg-transparent">
                                    <div class="border-border/50 border-b p-3">
                                        <CommandInput
                                            placeholder="Search workspace..."
                                            class="bg-surface/50 border-border/50 h-9 text-sm"
                                        />
                                    </div>
                                    <CommandList class="max-h-70 p-2">
                                        <CommandEmpty
                                            class="text-muted-foreground py-6 text-center text-sm"
                                            >No workspace found.</CommandEmpty
                                        >
                                        <CommandGroup
                                            heading="Workspaces"
                                            class="**:[[cmdk-group-heading]]:text-muted-foreground **:[[cmdk-group-heading]]:px-2 **:[[cmdk-group-heading]]:py-1.5 **:[[cmdk-group-heading]]:font-mono **:[[cmdk-group-heading]]:text-[10px] **:[[cmdk-group-heading]]:tracking-wider **:[[cmdk-group-heading]]:uppercase"
                                        >
                                            <CommandItem
                                                v-for="org in organizations"
                                                :key="org.id"
                                                :value="org.name"
                                                class="aria-selected:bg-surface hover:bg-surface/50 flex items-center gap-2.5 rounded-lg px-2 py-2 transition-colors"
                                                @select="() => selectOrganization(org.id)"
                                            >
                                                <Avatar class="h-6 w-6 shrink-0">
                                                    <AvatarImage
                                                        v-if="org.logo"
                                                        :src="org.logo"
                                                        :alt="org.name"
                                                    />
                                                    <AvatarFallback
                                                        :class="[
                                                            'text-[10px] font-bold',
                                                            org.id === activeOrganizationId
                                                                ? 'bg-amber-500 text-black'
                                                                : 'bg-surface text-muted-foreground',
                                                        ]"
                                                    >
                                                        {{ org.name.charAt(0).toUpperCase() }}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <span class="flex-1 text-sm">{{ org.name }}</span>
                                                <svg
                                                    v-if="org.id === activeOrganizationId"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    width="14"
                                                    height="14"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2.5"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    class="shrink-0 text-amber-500"
                                                >
                                                    <path d="M20 6 9 17l-5-5" />
                                                </svg>
                                            </CommandItem>
                                        </CommandGroup>
                                        <CommandSeparator class="bg-border/50 my-2" />
                                        <CommandGroup>
                                            <CommandItem
                                                class="hover:bg-surface/50 flex items-center gap-2.5 rounded-lg px-2 py-2"
                                                @select="() => router.visit('/workspace/create')"
                                            >
                                                <div
                                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-linear-to-br from-cyan-400 to-cyan-500 shadow-sm shadow-cyan-500/20"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        width="12"
                                                        height="12"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2.5"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        class="text-black"
                                                    >
                                                        <line x1="12" x2="12" y1="5" y2="19" />
                                                        <line x1="5" x2="19" y1="12" y2="12" />
                                                    </svg>
                                                </div>
                                                <span class="text-muted-foreground text-sm"
                                                    >Create workspace</span
                                                >
                                            </CommandItem>
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>
                    </div>

                    <!-- Create Workspace Button (shown when no orgs) -->
                    <div v-else class="mb-6 px-3">
                        <Link
                            href="/workspace/create"
                            class="group relative flex w-full items-center justify-center gap-2 overflow-hidden rounded-xl bg-linear-to-r from-amber-500 via-amber-400 to-orange-500 px-4 py-3 text-sm font-semibold text-black shadow-lg shadow-amber-500/25 transition-all duration-300 hover:shadow-amber-500/40"
                        >
                            <div
                                class="absolute inset-0 -translate-x-full bg-linear-to-r from-transparent via-white/20 to-transparent transition-transform duration-700 group-hover:translate-x-full"
                            ></div>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <line x1="12" x2="12" y1="5" y2="19" />
                                <line x1="5" x2="19" y1="12" y2="12" />
                            </svg>
                            <span class="relative">New Workspace</span>
                        </Link>
                    </div>

                    <!-- Navigation -->
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
                                            <g v-html="icons[item.icon]" />
                                        </svg>

                                        <!-- Label -->
                                        <div
                                            :class="[
                                                'overflow-hidden transition-all duration-200',
                                                isOpen ? 'w-auto opacity-100' : 'w-0 opacity-0',
                                            ]"
                                        >
                                            <span class="block text-sm font-medium">{{
                                                item.title
                                            }}</span>
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
                </SidebarContent>

                <SidebarSeparator class="bg-border/50" />

                <!-- Footer -->
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
            </Sidebar>

            <!-- ═══════════════════════════════════════════════════════════════════
           MAIN CONTENT AREA
           ═══════════════════════════════════════════════════════════════════ -->
            <SidebarInset class="bg-background">
                <!-- Header — refined with more subtle backdrop blur -->
                <header
                    class="border-border/30 bg-background/70 sticky top-0 z-30 flex h-14 items-center gap-4 border-b px-4 backdrop-blur-xl"
                >
                    <SidebarTrigger class="hover:bg-surface/60 rounded-lg transition-colors" />

                    <!-- Breadcrumb spacer -->
                    <div class="flex-1" />

                    <!-- Theme Toggle -->
                    <ThemeToggle />

                    <!-- User Info -->
                    <div v-if="user" class="flex items-center gap-3">
                        <div class="hidden text-right sm:block">
                            <span class="text-foreground block text-sm font-medium">{{
                                user.name
                            }}</span>
                            <span
                                class="text-muted-foreground block font-mono text-[10px] tracking-wider uppercase"
                                >{{ user.email?.split('@')[0] }}</span
                            >
                        </div>
                        <div class="relative">
                            <div
                                class="from-surface-elevated to-surface ring-border/40 flex h-9 w-9 items-center justify-center rounded-xl bg-linear-to-br ring-1"
                            >
                                <span class="text-sm font-bold text-amber-400">{{
                                    user.name?.charAt(0)?.toUpperCase()
                                }}</span>
                            </div>
                            <div
                                class="ring-background absolute -right-0.5 -bottom-0.5 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2"
                            ></div>
                        </div>
                    </div>
                </header>

                <!-- Page Content — subtle gradient mesh -->
                <main class="gradient-mesh-subtle min-h-[calc(100vh-3.5rem)]">
                    <div class="p-5 lg:p-8">
                        <slot />
                    </div>
                </main>
            </SidebarInset>
        </template>
    </SidebarProvider>

    <!-- Toast Notifications -->
    <Sonner />
</template>
