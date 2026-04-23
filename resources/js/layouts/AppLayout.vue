<script setup>
import { computed, watch, ref, onMounted, nextTick } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'

// UI Components
import Sonner from '@/components/ui/Sonner.vue'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'
import TaskDetailSheet from '@/components/task/TaskDetailSheet.vue'
import {
    Sidebar,
    SidebarContent,
    SidebarInset,
    SidebarProvider,
    SidebarSeparator,
    SidebarTrigger,
} from '@/components/ui/sidebar'

// Layout Parts
import AppSidebarHeader from '@/components/layout/AppSidebarHeader.vue'
import AppSidebarOrgSwitcher from '@/components/layout/AppSidebarOrgSwitcher.vue'
import AppSidebarNav from '@/components/layout/AppSidebarNav.vue'
import AppSidebarFooter from '@/components/layout/AppSidebarFooter.vue'

// Utils
import { useTheme } from '@/lib/theme'

const props = defineProps({
    mainClass: {
        type: String,
        default: 'min-h-[calc(100vh-3.5rem)]',
    },
    contentClass: {
        type: String,
        default: 'p-5 lg:p-8',
    },
})

const page = usePage()
const user = computed(() => page.props.auth.user)

// Theme management
const { initTheme } = useTheme()
onMounted(() => initTheme())

// Organization switcher data
const organizations = computed(() => page.props.organizations || [])
const activeOrganizationId = computed(() => page.props.activeOrganizationId || null)

// Handle flash messages
const showToast = (flash) => {
    if (!flash) return

    // 1. Handle primary success/error keys (standard strings)
    if (typeof flash.success === 'string') toast.success(flash.success)
    if (typeof flash.error === 'string') toast.error(flash.error)
    if (typeof flash.warning === 'string') toast.warning(flash.warning)
    if (typeof flash.info === 'string') toast.info(flash.info)

    // 2. Handle structured message object (common pattern)
    if (flash.message && typeof flash.message === 'object') {
        const { type, text, message } = flash.message
        const content = text || message
        const method = type || 'info'

        if (content && typeof toast[method] === 'function') {
            toast[method](content)
        }
    }
}

watch(
    () => page.props.flash,
    (flash) => {
        nextTick(() => showToast(flash))
    },
    { immediate: true, deep: true },
)

// Handle validation errors
watch(
    () => page.props.errors,
    (errors) => {
        if (errors && Object.keys(errors).length > 0) {
            const firstError = Object.values(errors)[0]
            toast.error(Array.isArray(firstError) ? firstError[0] : firstError)
        }
    },
    { deep: true },
)
</script>

<template>
    <SidebarProvider>
        <template #default="{ isOpen }">
            <Sidebar
                :data-state="isOpen ? 'open' : 'closed'"
                class="sidebar-glow border-border/50 border-r"
            >
                <!-- Branding Header -->
                <AppSidebarHeader :is-open="isOpen" />

                <SidebarContent class="relative z-10">
                    <!-- Organization / Workspace Switcher -->
                    <AppSidebarOrgSwitcher
                        :organizations="organizations"
                        :active-organization-id="activeOrganizationId"
                        :is-open="isOpen"
                    />

                    <!-- Main Navigation -->
                    <AppSidebarNav :is-open="isOpen" />
                </SidebarContent>

                <SidebarSeparator class="bg-border/50" />

                <!-- User Actions Footer -->
                <AppSidebarFooter :user="user" :is-open="isOpen" />
            </Sidebar>

            <SidebarInset class="bg-background">
                <!-- Header — refined with subtle backdrop blur -->
                <header
                    class="border-border/30 bg-background/70 sticky top-0 z-30 flex h-14 items-center gap-4 border-b px-4 backdrop-blur-xl"
                >
                    <SidebarTrigger class="hover:bg-surface/60 rounded-lg transition-colors" />

                    <div class="flex-1" />

                    <ThemeToggle />

                    <!-- User Info Section -->
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
                <main class="gradient-mesh-subtle" :class="props.mainClass">
                    <div :class="props.contentClass">
                        <slot />
                    </div>
                </main>
            </SidebarInset>
        </template>
    </SidebarProvider>

    <!-- Global Layout Assets -->
    <Sonner />
    <TaskDetailSheet />
</template>

<style>
.sidebar-glow {
    position: relative;
}

.sidebar-glow::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 0% 0%, rgba(245, 158, 11, 0.03), transparent 70%);
    pointer-events: none;
}

.gradient-mesh-subtle {
    background-image: 
        radial-gradient(at 0% 0%, rgba(245, 158, 11, 0.02) 0px, transparent 50%),
        radial-gradient(at 100% 0%, rgba(6, 182, 212, 0.02) 0px, transparent 50%);
}
</style>
