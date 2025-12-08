<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from 'lucide-vue-next';
import UserMenuContent from './UserMenuContent.vue';

const page = usePage();
const user = page.props.auth.user;
const { isMobile, state } = useSidebar();
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-r from-primary/5 to-primary/10 p-3 shadow-lg transition-all duration-300 hover:border-primary/30 hover:from-primary/10 hover:to-primary/20 hover:shadow-xl hover:shadow-primary/10 hover:-translate-y-0.5 data-[state=open]:bg-gradient-to-r data-[state=open]:from-primary/15 data-[state=open]:to-primary/25 data-[state=open]:border-primary/40 data-[state=open]:shadow-xl data-[state=open]:shadow-primary/15"
                        data-test="sidebar-menu-button"
                    >
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <UserInfo :user="user" />
                        <ChevronsUpDown class="ml-auto size-4 text-primary/70 group-hover:text-primary transition-colors duration-200" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    :side="
                        isMobile
                            ? 'bottom'
                            : state === 'collapsed'
                              ? 'left'
                              : 'bottom'
                    "
                    align="end"
                    :side-offset="4"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
