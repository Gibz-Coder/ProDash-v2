<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Monitor, Moon, Sun, Palette } from 'lucide-vue-next';
import { computed } from 'vue';

const { appearance, updateAppearance } = useAppearance();

const themes = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;

const currentTheme = computed(() => {
    return themes.find(theme => theme.value === appearance.value) || themes[2];
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="sm" class="h-9 w-9 p-0">
                <component :is="currentTheme.Icon" class="h-4 w-4" />
                <span class="sr-only">Toggle theme</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-40">
            <DropdownMenuItem
                v-for="theme in themes"
                :key="theme.value"
                @click="updateAppearance(theme.value)"
                :class="[
                    'flex items-center gap-2 cursor-pointer',
                    appearance === theme.value && 'bg-accent'
                ]"
            >
                <component :is="theme.Icon" class="h-4 w-4" />
                <span>{{ theme.label }}</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>