<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineProps<{
    status?: number;
}>();
</script>

<template>
    <Head title="401 - Unauthorized" />
    
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-background to-orange-500/5 px-4">
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
                        <h1 class="text-9xl font-bold text-orange-500/20 select-none">
                            401
                        </h1>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-orange-500/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <h2 class="text-3xl font-bold tracking-tight">
                        Authentication Required
                    </h2>
                    <p class="text-muted-foreground text-lg max-w-md mx-auto">
                        You need to be logged in to access this page. 
                        Please sign in to continue.
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center pt-4">
                <Button as-child size="lg">
                    <Link href="/login">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Sign In
                    </Link>
                </Button>
                <Button as-child variant="outline" size="lg">
                    <Link href="/">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                        Go Home
                    </Link>
                </Button>
            </div>

            <!-- Info Box -->
            <div class="pt-8">
                <div class="bg-muted/50 border border-border rounded-lg p-6 max-w-md mx-auto">
                    <div class="text-left space-y-4">
                        <div>
                            <h3 class="font-semibold mb-2 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                Don't have an account?
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Create a new account to get started with VICIS ProDash.
                            </p>
                        </div>
                        <Button as-child variant="secondary" class="w-full">
                            <Link href="/register">
                                Create Account
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
