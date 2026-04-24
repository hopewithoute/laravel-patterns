<script setup>
import { Link } from '@inertiajs/vue3'
import AppLogo from '@/components/ui/AppLogo.vue'

defineProps({
    isOpen: {
        type: Boolean,
        default: true,
    },
})
</script>

<template>
    <SidebarHeader class="relative z-10">
        <Link href="/" class="group flex items-center gap-3 px-3 py-4">
            <!-- Logo Mark -->
            <div
                class="relative flex items-center justify-center transition-transform duration-300 group-hover:scale-105"
            >
                <AppLogo class="text-foreground h-8 w-8" />
                <!-- Glow effect -->
                <div
                    class="absolute inset-0 rounded-full bg-amber-500/20 opacity-0 blur-xl transition-opacity duration-300 group-hover:opacity-100"
                ></div>
            </div>
            <!-- Logo Text -->
            <div
                :class="[
                    'overflow-hidden transition-all duration-200',
                    isOpen ? 'w-auto opacity-100' : 'w-0 opacity-0',
                ]"
            >
                <span class="font-display text-foreground text-lg font-bold tracking-tight"
                    >TaskFlow</span
                >
                <span
                    class="text-muted-foreground block font-mono text-[10px] tracking-widest uppercase"
                    >Workspace</span
                >
            </div>
        </Link>
    </SidebarHeader>
</template>

<style scoped>
/* Scoped imports if needed, but we rely on global/tailwind */
</style>
