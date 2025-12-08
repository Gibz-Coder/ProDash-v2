<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import type { User } from '@/types';
import { computed } from 'vue';
import { User as UserIcon } from 'lucide-vue-next';

interface Props {
    user: User;
    showRole?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showRole: true,
});

// Remove the useInitials since we're using an icon instead

// Compute whether we should show the avatar image
const showAvatar = computed(
    () => props.user.avatar && props.user.avatar !== '',
);

// Get the display name (prefer emp_name, fallback to name)
const displayName = computed(() => props.user.emp_name || props.user.name || 'Unknown User');

// Format the role for display
const displayRole = computed(() => {
    if (!props.user.role) return '';
    return props.user.role.charAt(0).toUpperCase() + props.user.role.slice(1);
});
</script>

<template>
    <Avatar class="h-9 w-9 overflow-hidden rounded-xl border-2 border-primary/20 shadow-md transition-all duration-200 group-hover:border-primary/40 group-hover:shadow-lg group-hover:shadow-primary/10">
        <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="displayName" />
        <AvatarFallback class="rounded-xl bg-gradient-to-br from-primary/10 to-primary/20 flex items-center justify-center">
            <UserIcon class="h-5 w-5 text-primary/80" />
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-semibold text-foreground group-hover:text-primary transition-colors duration-200">{{ displayName }}</span>
        <span v-if="showRole && displayRole" class="truncate text-xs font-medium text-muted-foreground group-hover:text-primary/70 transition-colors duration-200">{{
            displayRole
        }}</span>
    </div>
</template>
