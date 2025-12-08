<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineProps<{
    message?: string;
    retryAfter?: number;
}>();
</script>

<template>
    <Head title="Under Maintenance" />
    
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-background to-primary/5 px-4">
        <div class="max-w-2xl w-full text-center space-y-8">
            <!-- Logo -->
            <div class="flex justify-center">
                <div class="flex items-center gap-2">
                    <AppLogoIcon class="text-5xl text-primary" />
                    <span class="text-2xl font-bold">VICIS ProDash</span>
                </div>
            </div>

            <!-- Maintenance Icon -->
            <div class="flex justify-center">
                <div class="relative">
                    <div class="w-32 h-32 rounded-full bg-primary/10 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="absolute -top-2 -right-2">
                        <div class="relative flex h-6 w-6">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-6 w-6 bg-primary"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="space-y-4">
                <div class="space-y-2">
                    <h1 class="text-4xl font-bold tracking-tight">
                        We'll Be Right Back
                    </h1>
                    <p class="text-muted-foreground text-lg max-w-md mx-auto">
                        {{ message || 'Our application is currently undergoing scheduled maintenance. We\'ll be back online shortly.' }}
                    </p>
                </div>
            </div>

            <!-- Status Indicator -->
            <div class="flex justify-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-primary/10 border border-primary/20 rounded-full">
                    <div class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-primary"></span>
                    </div>
                    <span class="text-sm font-medium">Maintenance in Progress</span>
                </div>
            </div>

            <!-- Estimated Time -->
            <div v-if="retryAfter" class="pt-4">
                <div class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    <span>Expected back online in approximately <strong class="text-foreground">{{ retryAfter }} minutes</strong></span>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="grid sm:grid-cols-3 gap-4 pt-8 max-w-3xl mx-auto">
                <div class="bg-muted/50 border border-border rounded-lg p-4 text-center">
                    <div class="flex justify-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-1">Your Data is Safe</h3>
                    <p class="text-xs text-muted-foreground">All your information is secure during maintenance</p>
                </div>

                <div class="bg-muted/50 border border-border rounded-lg p-4 text-center">
                    <div class="flex justify-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-1">Performance Boost</h3>
                    <p class="text-xs text-muted-foreground">We're making improvements for better experience</p>
                </div>

                <div class="bg-muted/50 border border-border rounded-lg p-4 text-center">
                    <div class="flex justify-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-1">Quick Return</h3>
                    <p class="text-xs text-muted-foreground">We'll be back online as soon as possible</p>
                </div>
            </div>

            <!-- Contact -->
            <div class="pt-8 border-t border-border/50">
                <p class="text-sm text-muted-foreground">
                    Need urgent assistance? 
                    <a href="mailto:support@example.com" class="text-primary hover:underline ml-1">
                        Contact our support team
                    </a>
                </p>
            </div>
        </div>
    </div>
</template>
