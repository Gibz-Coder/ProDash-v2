<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineProps<{
    status?: number;
}>();
</script>

<template>
    <Head title="503 - Service Unavailable" />
    
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-background to-yellow-500/5 px-4">
        <div class="max-w-2xl w-full text-center space-y-8">
            <!-- Logo -->
            <div class="flex justify-center">
                <div class="flex items-center gap-2">
                    <AppLogoIcon class="text-5xl text-primary" />
                    <span class="text-2xl font-bold">VICIS ProDash</span>
                </div>
            </div>

            <!-- Error Code -->
            <div class="space-y-4">
                <div class="flex justify-center">
                    <div class="relative">
                        <h1 class="text-9xl font-bold text-yellow-500/20 select-none">
                            503
                        </h1>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-yellow-500/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <h2 class="text-3xl font-bold tracking-tight">
                        Service Unavailable
                    </h2>
                    <p class="text-muted-foreground text-lg max-w-md mx-auto">
                        The service is temporarily unavailable. 
                        We're working hard to get things back up and running.
                    </p>
                </div>
            </div>

            <!-- Status Indicator -->
            <div class="flex justify-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500/10 border border-yellow-500/20 rounded-full">
                    <div class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-500 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-yellow-500"></span>
                    </div>
                    <span class="text-sm font-medium">System Under Maintenance</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center pt-4">
                <Button variant="outline" size="lg" @click="() => window.location.reload()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                    </svg>
                    Refresh Page
                </Button>
            </div>

            <!-- Info Box -->
            <div class="pt-8">
                <div class="bg-muted/50 border border-border rounded-lg p-6 text-left max-w-md mx-auto">
                    <h3 class="font-semibold mb-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        What's happening?
                    </h3>
                    <ul class="text-sm text-muted-foreground space-y-2">
                        <li>• The service is temporarily down for maintenance</li>
                        <li>• We're performing important updates</li>
                        <li>• Normal service will resume shortly</li>
                    </ul>
                    <div class="mt-4 pt-4 border-t border-border">
                        <p class="text-sm text-muted-foreground">
                            Expected resolution: <span class="font-medium text-foreground">Within 30 minutes</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
