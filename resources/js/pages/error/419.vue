<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineProps<{
    status?: number;
}>();
</script>

<template>
    <Head title="419 - Session Expired" />
    
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-background to-blue-500/5 px-4">
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
                        <h1 class="text-9xl font-bold text-blue-500/20 select-none">
                            419
                        </h1>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-blue-500/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <h2 class="text-3xl font-bold tracking-tight">
                        Session Expired
                    </h2>
                    <p class="text-muted-foreground text-lg max-w-md mx-auto">
                        Your session has expired due to inactivity. 
                        Please refresh the page to continue.
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center pt-4">
                <Button size="lg" @click="() => window.location.reload()">
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
                        Why did this happen?
                    </h3>
                    <ul class="text-sm text-muted-foreground space-y-2">
                        <li>• Your session expired due to inactivity</li>
                        <li>• Security token has been invalidated</li>
                        <li>• Page was open for too long</li>
                    </ul>
                    <div class="mt-4 pt-4 border-t border-border">
                        <p class="text-sm text-muted-foreground">
                            Simply refresh the page to start a new session.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
