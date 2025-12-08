<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineProps<{
    status?: number;
}>();
</script>

<template>
    <Head title="404 - Page Not Found" />
    
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-background to-muted/20 px-4">
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
                <h1 class="text-9xl font-bold text-primary/20 select-none">
                    404
                </h1>
                <div class="space-y-2">
                    <h2 class="text-3xl font-bold tracking-tight">
                        Page Not Found
                    </h2>
                    <p class="text-muted-foreground text-lg max-w-md mx-auto">
                        Sorry, we couldn't find the page you're looking for. 
                        It might have been moved or deleted.
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center pt-4">
                <Button as-child size="lg">
                    <Link href="/">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                        Go Home
                    </Link>
                </Button>
                <Button as-child variant="outline" size="lg" @click="$router.back()">
                    <a href="javascript:history.back()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        Go Back
                    </a>
                </Button>
            </div>

            <!-- Helpful Links -->
            <div class="pt-8 border-t border-border/50">
                <p class="text-sm text-muted-foreground mb-4">
                    Need help? Try these:
                </p>
                <div class="flex flex-wrap gap-4 justify-center text-sm">
                    <Link href="/" class="text-primary hover:underline">
                        Dashboard
                    </Link>
                    <span class="text-muted-foreground">•</span>
                    <Link href="/settings" class="text-primary hover:underline">
                        Settings
                    </Link>
                    <span class="text-muted-foreground">•</span>
                    <a href="mailto:support@example.com" class="text-primary hover:underline">
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
