import { onMounted, onBeforeUnmount, shallowRef } from 'vue';
import { initUtilizationGauge, initUtilizationTrendChart, chartData, getChartJsThemeColors } from '@/lib/mems-charts';
import type { Chart as ChartType } from 'chart.js';
import type { ECharts } from 'echarts';

export function useMemsCharts(gaugeId: string, trendId: string) {
    const gaugeChart = shallowRef<ECharts | null>(null);
    const trendChart = shallowRef<ChartType | null>(null);
    let themeObserver: MutationObserver | null = null;
    let resizeObserver: ResizeObserver | null = null;

    const handleResize = () => {
        // Force Chart.js to resize by updating its internal size
        if (trendChart.value) {
            // Get the canvas element
            const canvas = trendChart.value.canvas;
            const container = canvas.parentElement;
            
            if (container) {
                // Force the canvas to recalculate its size
                canvas.style.width = '0px';
                canvas.style.height = '0px';
                
                // Trigger a reflow
                void container.offsetHeight;
                
                // Restore the size
                canvas.style.width = '100%';
                canvas.style.height = '100%';
                
                // Trigger another reflow
                void container.offsetHeight;
                
                // Now resize the chart multiple times to ensure it takes
                requestAnimationFrame(() => {
                    trendChart.value?.resize();
                    requestAnimationFrame(() => {
                        trendChart.value?.resize();
                    });
                });
            }
        }
        
        // Resize the gauge chart
        gaugeChart.value?.resize();
    };

    const updateTrendChart = (timeRange: string, trendData?: any[]) => {
        if (!trendChart.value) return;
        
        const colors = getChartJsThemeColors();
        
        // If we have actual trend data from the API, use it with real timestamps
        if (trendData && trendData.length > 0) {
            // Extract labels (actual time stamps like "14:30", "15:00", etc.)
            const labels = trendData.map(point => point.label);
            
            // Store raw counts for tooltip display
            const runCounts = trendData.map(point => point.run);
            const waitCounts = trendData.map(point => point.wait);
            const idleCounts = trendData.map(point => point.idle);
            const totals = trendData.map(point => point.total);
            
            // Calculate percentages for each status
            const runData = trendData.map(point => {
                const total = point.total || 1;
                return Math.round((point.run / total) * 100);
            });
            const waitData = trendData.map(point => {
                const total = point.total || 1;
                return Math.round((point.wait / total) * 100);
            });
            const idleData = trendData.map(point => {
                const total = point.total || 1;
                return Math.round((point.idle / total) * 100);
            });
            
            const updatedData = {
                labels,
                datasets: [
                    {
                        label: 'Running',
                        data: runData,
                        backgroundColor: colors.runColor,
                        borderColor: colors.runColor,
                        borderWidth: 0,
                        // Store raw counts for tooltip
                        rawCounts: runCounts,
                        totals: totals,
                    },
                    {
                        label: 'Waiting',
                        data: waitData,
                        backgroundColor: colors.waitColor,
                        borderColor: colors.waitColor,
                        borderWidth: 0,
                        // Store raw counts for tooltip
                        rawCounts: waitCounts,
                        totals: totals,
                    },
                    {
                        label: 'Idle',
                        data: idleData,
                        backgroundColor: colors.idleColor,
                        borderColor: colors.idleColor,
                        borderWidth: 0,
                        // Store raw counts for tooltip
                        rawCounts: idleCounts,
                        totals: totals,
                    }
                ]
            };
            
            trendChart.value.data = updatedData;
            trendChart.value.update('active');
        } else if (chartData[timeRange]) {
            // Fallback to static data if no API data available
            const updatedData = {
                ...chartData[timeRange],
                datasets: chartData[timeRange].datasets.map(dataset => ({
                    ...dataset,
                    backgroundColor: dataset.label === 'Running' ? colors.runColor :
                                    dataset.label === 'Waiting' ? colors.waitColor :
                                    colors.idleColor,
                    borderColor: dataset.label === 'Running' ? colors.runColor :
                                dataset.label === 'Waiting' ? colors.waitColor :
                                colors.idleColor,
                }))
            };
            
            trendChart.value.data = updatedData;
            trendChart.value.update('active');
        }
    };

    const updateGaugeChart = (utilizationData: any, lineName?: string) => {
        if (!gaugeChart.value) return;
        
        const runPercent = utilizationData.runPercent || 0;
        const waitPercent = utilizationData.waitPercent || 0;
        const idlePercent = utilizationData.idlePercent || 0;
        
        // Get theme colors
        const isDark = document.documentElement.classList.contains('dark');
        const runColor = isDark ? '#00dc82' : '#00c16a';
        const waitColor = isDark ? '#5B7FDB' : '#4169E1';
        const idleColor = isDark ? '#e96a24' : '#ea580c';
        
        // Determine text color based on utilization percentage
        let textColor: string;
        if (runPercent >= 90) {
            // Green for 90% and above
            textColor = isDark ? '#00dc82' : '#00c16a';
        } else if (runPercent >= 80) {
            // Yellow for 80% ~ 89.99%
            textColor = isDark ? '#fbbf24' : '#f59e0b';
        } else {
            // Red for below 80%
            textColor = isDark ? '#ef4444' : '#dc2626';
        }
        
        // Calculate cumulative percentages for color segments
        const runEnd = runPercent / 100;
        const waitEnd = (runPercent + waitPercent) / 100;
        
        // Format the label based on line name
        const formatter = lineName && lineName !== 'ALL' 
            ? `Line ${lineName}: {value}%` 
            : '{value}%';
        
        gaugeChart.value.setOption({
            series: [{
                data: [{
                    value: runPercent,
                    name: '' // Clear the title since we're using detail formatter
                }],
                detail: {
                    valueAnimation: true,
                    formatter: formatter,
                    color: textColor, // Apply color coding to the text
                    fontSize: 22,
                    fontWeight: 'bold',
                    offsetCenter: [0, '60%']
                },
                progress: {
                    itemStyle: {
                        color: runColor
                    }
                },
                axisLine: {
                    lineStyle: {
                        width: 22,
                        color: [
                            [runEnd, runColor],
                            [waitEnd, waitColor],
                            [1, idleColor]
                        ]
                    }
                }
            }]
        });
    };

    const refreshChartsForTheme = () => {
        // Reinitialize charts with new theme colors
        if (gaugeChart.value) {
            gaugeChart.value.dispose();
            gaugeChart.value = initUtilizationGauge(gaugeId);
        }
        if (trendChart.value) {
            trendChart.value.destroy();
            trendChart.value = initUtilizationTrendChart(trendId);
            
            // Note: After theme change, the parent component should call updateTrendChart
            // with the current trend data to repopulate the chart
        }
    };

    const observeThemeChanges = () => {
        // Watch for dark class changes on html element
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'class') {
                    refreshChartsForTheme();
                }
            });
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });

        return observer;
    };

    const observeSidebarChanges = () => {
        // Watch for sidebar state changes on the peer element
        const sidebar = document.querySelector('.peer[data-state]');
        
        if (!sidebar) {
            // Fallback: watch the wrapper for class changes
            const wrapper = document.querySelector('[data-slot="sidebar-wrapper"]');
            if (!wrapper) return () => {};
            
            const observer = new MutationObserver(() => {
                setTimeout(() => handleResize(), 250);
                setTimeout(() => handleResize(), 500);
            });
            
            observer.observe(wrapper, {
                attributes: true,
                subtree: true,
                attributeFilter: ['data-state', 'data-collapsible']
            });
            
            return () => observer.disconnect();
        }

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && 
                    (mutation.attributeName === 'data-state' || mutation.attributeName === 'data-collapsible')) {
                    // Sidebar state changed, wait for transition to complete (200ms) + buffer
                    setTimeout(() => {
                        handleResize();
                    }, 250);
                    // Additional resize after a longer delay to ensure everything settled
                    setTimeout(() => {
                        handleResize();
                    }, 500);
                }
            });
        });

        observer.observe(sidebar, {
            attributes: true,
            attributeFilter: ['data-state', 'data-collapsible']
        });
        
        return () => observer.disconnect();
    };

    const observeContainerResize = () => {
        // Watch the main content area for size changes
        const mainContent = document.querySelector('[data-slot="sidebar-inset"]') as HTMLElement;
        
        if (!mainContent) {
            return null;
        }

        let lastWidth = mainContent.offsetWidth;
        let resizeTimeout: number | null = null;

        const observer = new ResizeObserver((entries) => {
            for (const entry of entries) {
                const currentWidth = entry.contentRect.width;
                // Only resize if the width actually changed significantly (more than 5px)
                if (Math.abs(currentWidth - lastWidth) > 5) {
                    lastWidth = currentWidth;
                    
                    // Clear any pending resize
                    if (resizeTimeout) {
                        clearTimeout(resizeTimeout);
                    }
                    
                    // Debounce resize calls
                    resizeTimeout = window.setTimeout(() => {
                        handleResize();
                        resizeTimeout = null;
                    }, 50);
                }
            }
        });

        observer.observe(mainContent);
        
        // Also observe the chart container directly
        const chartContainer = document.querySelector('.mems-ongoing-lots .mems-panel-content') as HTMLElement;
        if (chartContainer) {
            observer.observe(chartContainer);
        }
        
        return observer;
    };

    const initCharts = () => {
        try {
            gaugeChart.value = initUtilizationGauge(gaugeId);
            trendChart.value = initUtilizationTrendChart(trendId);
            window.addEventListener('resize', handleResize);
            resizeObserver = observeContainerResize();
            
            // Listen for CSS transition end events on the sidebar
            const sidebar = document.querySelector('.peer[data-state]');
            if (sidebar) {
                sidebar.addEventListener('transitionend', (e) => {
                    // Only handle width transitions
                    if ((e as TransitionEvent).propertyName === 'width' || 
                        (e as TransitionEvent).propertyName === 'left' ||
                        (e as TransitionEvent).propertyName === 'right') {
                        setTimeout(() => handleResize(), 50);
                        setTimeout(() => handleResize(), 150);
                    }
                });
            }
            
            // Also listen on the chart container itself
            const chartPanel = document.querySelector('.mems-ongoing-lots');
            if (chartPanel) {
                chartPanel.addEventListener('transitionend', () => {
                    setTimeout(() => handleResize(), 50);
                });
            }
        } catch (error) {
            // Chart initialization failed
        }
    };

    const cleanupCharts = () => {
        window.removeEventListener('resize', handleResize);

        if (gaugeChart.value) {
            gaugeChart.value.dispose();
            gaugeChart.value = null;
        }

        if (trendChart.value) {
            trendChart.value.destroy();
            trendChart.value = null;
        }
    };

    let sidebarCleanup: (() => void) | null = null;

    onMounted(() => {
        initCharts();
        themeObserver = observeThemeChanges();
        sidebarCleanup = observeSidebarChanges();
    });

    onBeforeUnmount(() => {
        cleanupCharts();
        themeObserver?.disconnect();
        resizeObserver?.disconnect();
        sidebarCleanup?.();
    });

    return {
        gaugeChart,
        trendChart,
        updateTrendChart,
        updateGaugeChart,
        handleResize, // Expose for manual testing
    };
}

