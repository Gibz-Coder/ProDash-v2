<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard, lot_request, mems_dashboard, endline, endtime, escalation, process_wip, mc_allocation } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: dashboard().url,
    },
];

const reveals = ref<HTMLElement[]>([]);

onMounted(() => {
    const revealElements = document.querySelectorAll('.reveal');
    reveals.value = Array.from(revealElements) as HTMLElement[];
    revealOnScroll();
    window.addEventListener('scroll', revealOnScroll);
});

function revealOnScroll() {
    const windowHeight = window.innerHeight;
    const elementVisible = 150;

    reveals.value.forEach((element) => {
        const elementTop = element.getBoundingClientRect().top;
        if (elementTop < windowHeight - elementVisible) {
            element.classList.add('active');
        }
    });
}
</script>

<template>
    <Head title="Home" />

    <div class="home-wrapper">
        <div class="background-animation"></div>
        <div class="honeycomb-bg"></div>
        
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>

        <AppLayout :breadcrumbs="breadcrumbs">
            <div class="home-container">
                <section class="gallery-section">

                    <div class="hexagon-grid">
                        <!-- Row 1: 2 hexagons -->
                        <div class="hex-row">
                            <Link :href="endtime().url" class="hexagon-link">
                                <div class="hexagon reveal">
                                    <div class="hexagon-inner">
                                        <div class="hexagon-content">
                                            <div class="hex-icon">⏳</div>
                                            <h3 class="hex-title">Endtime Dashboard</h3>
                                            <p class="hex-desc">
                                                Forecasting of lot that can be track-out every cutoff.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                            <Link :href="lot_request().url" class="hexagon-link">
                                <div class="hexagon reveal">
                                    <div class="hexagon-inner">
                                        <div class="hexagon-content">
                                            <div class="hex-icon">🛒</div>
                                            <h3 class="hex-title">Lot Request</h3>
                                            <p class="hex-desc">
                                                Fifo, LIPAS and other Urgent lot management.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <!-- Row 2: 3 hexagons -->
                        <div class="hex-row">
                            <Link :href="process_wip().url" class="hexagon-link">
                                <div class="hexagon reveal">
                                    <div class="hexagon-inner">
                                        <div class="hexagon-content">
                                            <div class="hex-icon">📜</div>
                                            <h3 class="hex-title">WIP Management</h3>
                                            <p class="hex-desc">
                                                Visual WIP Summary.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                            <Link :href="mems_dashboard().url" class="hexagon-link">
                                <div class="hexagon reveal">
                                    <div class="hexagon-inner">
                                        <div class="hexagon-content">
                                            <div class="hex-icon">📊</div>
                                            <h3 class="hex-title">MEMS Dashboard</h3>
                                            <p class="hex-desc">Machine and Endtime Monitoring System</p>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                            <Link :href="mc_allocation().url" class="hexagon-link">
                                <div class="hexagon reveal">
                                    <div class="hexagon-inner">
                                        <div class="hexagon-content">
                                            <div class="hex-icon">🏪</div>
                                            <h3 class="hex-title">Machine Allocation</h3>
                                            <p class="hex-desc">
                                                Machine allocation and CAPA management.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <!-- Row 3: 2 hexagons -->
                        <div class="hex-row">
                            <Link :href="endline().url" class="hexagon-link">
                                <div class="hexagon reveal">
                                    <div class="hexagon-inner">
                                        <div class="hexagon-content">
                                            <div class="hex-icon">🔚</div>
                                            <h3 class="hex-title">Endline Management</h3>
                                            <p class="hex-desc">
                                                Visual Endline wip management.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                            <Link :href="escalation().url" class="hexagon-link">
                                <div class="hexagon reveal">
                                    <div class="hexagon-inner">
                                        <div class="hexagon-content">
                                            <div class="hex-icon">🛠️</div>
                                            <h3 class="hex-title">Machine Escalation</h3>
                                            <p class="hex-desc">
                                                Machine escalation request and response.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                </section>
            </div>
        </AppLayout>
    </div>
</template>

<style>
/* Global styles that need to affect AppLayout */
.home-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #1a1f35 0%, #2a2f4a 50%, #3d4271 100%);
    overflow: hidden;
}

/* Override SidebarInset to add equal margins */
.home-wrapper :deep([data-slot="sidebar-inset"]) {
    margin: 0.5rem !important;
    height: calc(100vh - 1rem) !important;
    overflow: hidden !important;
}

/* Override header to remove border bottom space */
.home-wrapper :deep(header) {
    display: none !important;
}

.home-wrapper .background-animation {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    background: radial-gradient(circle at 20% 20%, rgba(0, 255, 255, 0.25) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 0, 255, 0.25) 0%, transparent 50%),
        radial-gradient(circle at 40% 60%, rgba(0, 255, 0, 0.15) 0%, transparent 50%);
    animation: backgroundPulse 8s ease-in-out infinite alternate;
    pointer-events: none;
}

.home-wrapper .honeycomb-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    background-image: linear-gradient(
            30deg,
            transparent 24%,
            rgba(0, 255, 255, 0.12) 25%,
            rgba(0, 255, 255, 0.12) 26%,
            transparent 27%,
            transparent 74%,
            rgba(0, 255, 255, 0.12) 75%,
            rgba(0, 255, 255, 0.12) 76%,
            transparent 77%,
            transparent
        ),
        linear-gradient(
            -30deg,
            transparent 24%,
            rgba(0, 255, 255, 0.12) 25%,
            rgba(0, 255, 255, 0.12) 26%,
            transparent 27%,
            transparent 74%,
            rgba(0, 255, 255, 0.12) 75%,
            rgba(0, 255, 255, 0.12) 76%,
            transparent 77%,
            transparent
        );
    background-size: 60px 104px;
    animation: honeycombMove 20s linear infinite;
    pointer-events: none;
}

.home-wrapper .floating-element {
    position: fixed;
    width: 25px;
    height: 25px;
    background: linear-gradient(45deg, #00ffff, #ff00ff);
    border-radius: 50%;
    opacity: 0.8;
    box-shadow: 0 0 20px rgba(0, 255, 255, 0.5);
    animation: float 6s ease-in-out infinite;
    pointer-events: none;
    z-index: 1;
}

.home-wrapper .floating-element:nth-child(3) {
    top: 20%;
    left: 20%;
    animation-delay: -1s;
}

.home-wrapper .floating-element:nth-child(4) {
    top: 70%;
    right: 10%;
    animation-delay: -3s;
}

.home-wrapper .floating-element:nth-child(5) {
    top: 40%;
    right: 20%;
    animation-delay: -2s;
}

@keyframes backgroundPulse {
    0% {
        opacity: 0.4;
        transform: scale(1);
    }
    100% {
        opacity: 0.7;
        transform: scale(1.1);
    }
}

@keyframes honeycombMove {
    0% {
        background-position: 0 0, 0 0;
    }
    100% {
        background-position: 60px 104px, -60px -104px;
    }
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-30px) rotate(180deg);
    }
}
</style>

<style scoped>
.home-container {
    position: relative;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
    padding: 2rem 0;
}

.gallery-section {
    position: relative;
    width: 100%;
}

.gallery-title {
    text-align: center;
    font-size: 3.5rem;
    color: #ffaa00;
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 1rem;
    text-shadow: 0 0 30px rgba(255, 170, 0, 0.5);
}

.gallery-subtitle {
    text-align: center;
    font-size: 1.2rem;
    color: #a0c4ff;
    margin-bottom: 3rem;
}

.hexagon-grid {
    display: flex;
    flex-direction: column;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 3rem 1rem;
}

.hex-row {
    display: flex;
    justify-content: center;
    margin: -25px 0;
}

.hexagon-link {
    text-decoration: none;
    color: inherit;
}

.hexagon {
    position: relative;
    width: 280px;
    height: 240px;
    margin: 0 8px;
    cursor: pointer;
    transition: all 0.4s ease;
}

.hexagon-inner {
    position: relative;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #2a2f4a 0%, #3d4271 100%);
    clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid rgba(0, 255, 255, 0.5);
    transition: all 0.4s ease;
}

/* Removed hexagon-highlight class since it's no longer needed */

.hexagon-content {
    text-align: center;
    z-index: 2;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.hexagon:hover {
    transform: scale(1.08);
    z-index: 10;
}

.hexagon:hover .hexagon-inner {
    background: linear-gradient(135deg, rgba(0, 255, 255, 0.2), rgba(255, 0, 255, 0.2));
    border-color: rgba(0, 255, 255, 0.8);
    box-shadow: 0 0 30px rgba(0, 255, 255, 0.3), inset 0 0 30px rgba(0, 255, 255, 0.1);
}

.hex-icon {
    font-size: 3rem;
    color: #00ffff;
    margin-bottom: 0.8rem;
    text-shadow: 0 0 20px rgba(0, 255, 255, 0.6);
    transition: all 0.3s ease;
}

/* Removed hex-icon-highlight class since it's no longer needed */

.hexagon:hover .hex-icon {
    transform: scale(1.1);
    color: #ffffff;
    text-shadow: 0 0 25px rgba(0, 255, 255, 0.8);
}

.hex-title {
    font-size: 1rem;
    color: #ffffff;
    font-weight: bold;
    text-shadow: 0 0 15px rgba(255, 255, 255, 0.4);
    margin-bottom: 0.4rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.hex-desc {
    font-size: 0.8rem;
    color: #a0c4ff;
    line-height: 1.3;
    max-width: 160px;
    text-align: center;
    opacity: 0.9;
}

.reveal {
    opacity: 0;
    transform: translateY(50px);
    transition: all 0.8s ease;
}

.reveal.active {
    opacity: 1;
    transform: translateY(0);
}

@media (max-width: 768px) {
    .gallery-title {
        font-size: 2.5rem;
    }

    .hexagon-grid {
        transform: scale(0.7);
        margin: 1rem auto;
    }

    .hex-row {
        flex-direction: column;
        align-items: center;
        margin: 10px 0;
    }

    .hexagon {
        margin: 8px;
    }
}

@media (max-width: 1024px) {
    .hexagon {
        width: 240px;
        height: 200px;
        margin: 0 4px;
    }

    .gallery-title {
        font-size: 3rem;
    }
}
</style>
