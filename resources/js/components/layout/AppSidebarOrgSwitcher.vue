<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
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

const props = defineProps({
    organizations: {
        type: Array,
        required: true,
    },
    activeOrganizationId: {
        type: [String, Number, null],
        default: null,
    },
    isOpen: {
        type: Boolean,
        default: true,
    },
})

const activeOrganization = computed(() => {
    return props.organizations.find((org) => org.id === props.activeOrganizationId)
})

const selectOrganization = (orgId) => {
    if (orgId !== props.activeOrganizationId) {
        router.post('/workspace/set', { organization_id: orgId })
    }
}
</script>

<template>
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
                            {{ activeOrganization?.name?.charAt(0)?.toUpperCase() || 'O' }}
                        </AvatarFallback>
                    </Avatar>
                    <div
                        :class="[
                            'flex-1 text-left transition-all duration-200',
                            isOpen ? 'opacity-100' : 'w-0 opacity-0',
                        ]"
                    >
                        <span class="text-foreground block truncate text-sm font-medium">{{
                            activeOrganization?.name || 'Select workspace'
                        }}</span>
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
                        <CommandEmpty class="text-muted-foreground py-6 text-center text-sm"
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
                                    <AvatarImage v-if="org.logo" :src="org.logo" :alt="org.name" />
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
                                <span class="text-muted-foreground text-sm">Create workspace</span>
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
</template>
