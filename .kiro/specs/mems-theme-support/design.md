# MEMS Dashboard Theme Support - Design Document

## 1. Architecture Overview

### 1.1 Theme System Integration
The MEMS dashboard will integrate with the existing ProDash v2 theme system by:
- Reading theme preference from the `useAppearance` composable
- Using CSS custom properties for all theme-dependent colors
- Applying Tailwind's `dark:` variants where appropriate
- Updating chart configurations to be theme-aware

### 1.2 Component Structure
```
mems-dashboard.vue (root component)
├── useAppearance composable (theme state)
├── useMemsCharts composable (chart initialization)
│   ├── initUtilizationGauge (ECharts)
│   └── initUtilizationTrendChart (Chart.js)
└── mems-dashboard.css (theme variables)
```

## 2. Detailed Design

### 2.1 CSS Custom Properties Architecture

#### 2.1.1 Color Variable Structure
Create a comprehensive set of CSS custom properties in `mems-dashboard.css`:

```css
/* Light Mode (Default) */
:root {
  /* Backgrounds */
  --mems-bg-primary: oklch(0.98 0 0);
  --mems-bg-secondary: oklch(0.95 0 0);
  --mems-bg-tertiary: oklch(0.92 0 0);
  
  /* Panels */
  --mems-panel-bg: oklch(1 0 0);
  --mems-panel-header-bg: oklch(0.96 0 0);
  --mems-panel-border: oklch(0.85 0 0);
  
  /* Text */
  --mems-text-primary: oklch(0.2 0 0);
  --mems-text-secondary: oklch(0.45 0 0);
  --mems-text-tertiary: oklch(0.6 0 0);
  
  /* Accents */
  --mems-accent-cyan: oklch(0.65 0.15 220);
  --mems-accent-cyan-hover: oklch(0.55 0.15 220);
  --mems-accent-cyan-subtle: oklch(0.65 0.15 220 / 0.1);
  
  /* Status Colors */
  --mems-status-run: oklch(0.55 0.15 145);
  --mems-status-wait: oklch(0.65 0.15 35);
  --mems-status-idle: oklch(0.65 0.15 220);
  
  /* Borders & Shadows */
  --mems-border-subtle: oklch(0.85 0 0);
  --mems-border-emphasis: oklch(0.75 0 0);
  --mems-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --mems-shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --mems-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
  
  /* Chart Colors */
  --mems-chart-bg: transparent;
  --mems-chart-text: oklch(0.3 0 0);
  --mems-chart-grid: oklch(0.85 0 0 / 0.3);
  --mems-chart-tooltip-bg: oklch(1 0 0 / 0.95);
  --mems-chart-tooltip-border: oklch(0.85 0 0);
}

/* Dark Mode */
.dark {
  /* Backgrounds */
  --mems-bg-primary: #0a1929;
  --mems-bg-secondary: #0d2137;
  --mems-bg-tertiary: #132f4c;
  
  /* Panels */
  --mems-panel-bg: #0d2137;
  --mems-panel-header-bg: #132f4c;
  --mems-panel-border: rgba(0, 212, 255, 0.2);
  
  /* Text */
  --mems-text-primary: #ffffff;
  --mems-text-secondary: #b0bec5;
  --mems-text-tertiary: #78909c;
  
  /* Accents */
  --mems-accent-cyan: #00d4ff;
  --mems-accent-cyan-hover: #00b8e6;
  --mems-accent-cyan-subtle: rgba(0, 212, 255, 0.1);
  
  /* Status Colors */
  --mems-status-run: #0f6b3d;
  --mems-status-wait: #e96a24;
  --mems-status-idle: #00a0e9;
  
  /* Borders & Shadows */
  --mems-border-subtle: rgba(0, 212, 255, 0.2);
  --mems-border-emphasis: rgba(0, 212, 255, 0.4);
  --mems-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.3);
  --mems-shadow-md: 0 4px 20px rgba(0, 0, 0, 0.3);
  --mems-shadow-lg: 0 4px 20px rgba(0, 0, 0, 0.4);
  
  /* Chart Colors */
  --mems-chart-bg: transparent;
  --mems-chart-text: #ffffff;
  --mems-chart-grid: rgba(176, 190, 197, 0.1);
  --mems-chart-tooltip-bg: rgba(13, 33, 55, 0.95);
  --mems-chart-tooltip-border: #00d4ff;
}
```

#### 2.1.2 CSS Refactoring Strategy
Replace all hardcoded colors with CSS custom properties:

**Before:**
```css
.mems-dashboard {
  color: #ffffff;
  background: #0a1929;
}
```

**After:**
```css
.mems-dashboard {
  color: var(--mems-text-primary);
  background: var(--mems-bg-primary);
}
```

### 2.2 Chart Theme Integration

#### 2.2.1 ECharts Gauge Theme Function
Create a theme-aware configuration function:

```typescript
// resources/js/lib/mems-charts.ts

function getThemeColors() {
  const isDark = document.documentElement.classList.contains('dark');
  
  return {
    text: isDark ? '#ffffff' : '#1a1a1a',
    background: 'transparent',
    axisLine: isDark ? '#ffffff' : '#333333',
    splitLine: isDark ? '#ffffff' : '#666666',
    pointer: isDark ? '#ff3333' : '#cc0000',
    detail: isDark ? '#ffffff' : '#1a1a1a',
    runColor: isDark ? '#0f6b3d' : '#16a34a',
    waitColor: isDark ? '#e96a24' : '#ea580c',
  };
}

export function initUtilizationGauge(elementId: string) {
  const element = document.getElementById(elementId);
  if (!element) {
    console.warn(`Element with id "${elementId}" not found`);
    return null;
  }

  const chart = echarts.init(element);
  const colors = getThemeColors();
  
  const option = {
    series: [
      {
        name: 'Machine Utilization',
        type: 'gauge',
        min: 0,
        max: 100,
        radius: '100%',
        center: ['50%', '55%'],
        startAngle: 200,
        endAngle: -20,
        progress: {
          show: true,
          width: 21
        },
        axisLine: {
          lineStyle: {
            width: 22,
            color: [
              [0.8, colors.runColor],
              [1, colors.waitColor]
            ]
          }
        },
        axisTick: {
          distance: 12,
          length: 8,
          lineStyle: {
            color: colors.axisLine,
            width: 2
          }
        },
        splitLine: {
          distance: 12,
          length: 15,
          lineStyle: {
            color: colors.splitLine,
            width: 3
          }
        },
        axisLabel: {
          color: colors.text,
          distance: 45,
          fontSize: 11,
          fontWeight: 'bold'
        },
        pointer: {
          itemStyle: {
            color: colors.pointer
          },
          width: 5,
          length: '70%'
        },
        detail: {
          valueAnimation: true,
          formatter: '{value}%',
          color: colors.detail,
          fontSize: 22,
          fontWeight: 'bold',
          offsetCenter: [0, '50%']
        },
        title: {
          fontSize: 16,
          color: colors.text,
          fontWeight: 'bold',
          offsetCenter: [0, '70%']
        },
        data: [
          {
            value: 80,
            name: '',
            itemStyle: {
              color: colors.runColor
            }
          }
        ]
      }
    ]
  };
  
  chart.setOption(option);
  
  return chart;
}
```

#### 2.2.2 Chart.js Theme Function
Create theme-aware Chart.js configuration:

```typescript
function getChartJsThemeColors() {
  const isDark = document.documentElement.classList.contains('dark');
  
  return {
    text: isDark ? '#ffffff' : '#1a1a1a',
    secondaryText: isDark ? '#b0bec5' : '#666666',
    grid: isDark ? 'rgba(176, 190, 197, 0.1)' : 'rgba(0, 0, 0, 0.1)',
    border: isDark ? 'rgba(176, 190, 197, 0.2)' : 'rgba(0, 0, 0, 0.1)',
    tooltipBg: isDark ? 'rgba(13, 33, 55, 0.95)' : 'rgba(255, 255, 255, 0.95)',
    tooltipBorder: isDark ? '#00d4ff' : '#0ea5e9',
    tooltipTitle: isDark ? '#00d4ff' : '#0284c7',
    tooltipBody: isDark ? '#ffffff' : '#1a1a1a',
    runColor: isDark ? '#0f6b3d' : '#16a34a',
    waitColor: isDark ? '#e96a24' : '#ea580c',
    idleColor: isDark ? '#00a0e9' : '#0284c7',
  };
}

export function initUtilizationTrendChart(canvasId: string) {
  const canvas = document.getElementById(canvasId) as HTMLCanvasElement;
  if (!canvas) {
    console.warn(`Canvas with id "${canvasId}" not found`);
    return null;
  }

  const ctx = canvas.getContext('2d');
  if (!ctx) {
    console.warn(`Failed to get 2D context for canvas "${canvasId}"`);
    return null;
  }

  const colors = getChartJsThemeColors();

  const chart = new Chart(ctx, {
    type: "bar",
    data: {
      ...chartData['1h'],
      datasets: chartData['1h'].datasets.map(dataset => ({
        ...dataset,
        backgroundColor: dataset.label === 'Running' ? colors.runColor :
                        dataset.label === 'Wait' ? colors.waitColor :
                        colors.idleColor,
        borderColor: dataset.label === 'Running' ? colors.runColor :
                    dataset.label === 'Wait' ? colors.waitColor :
                    colors.idleColor,
      }))
    },
    plugins: [ChartDataLabels],
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: {
        padding: {
          top: 5,
          bottom: 15,
          left: 5,
          right: 5
        }
      },
      interaction: {
        intersect: false,
        mode: 'index'
      },
      scales: {
        x: {
          stacked: true,
          ticks: { 
            color: colors.text,
            font: { size: 9, weight: 600 },
            maxRotation: 0,
            minRotation: 0,
            padding: 2
          },
          grid: { 
            display: false 
          },
          border: {
            color: colors.border
          }
        },
        y: {
          stacked: true,
          display: true,
          beginAtZero: true,
          max: 100,
          ticks: {
            color: colors.secondaryText,
            font: { size: 8 },
            padding: 3,
            callback: function(value) {
              return value + '%';
            }
          },
          grid: {
            color: colors.grid,
            lineWidth: 1
          },
          border: {
            color: colors.border
          }
        },
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: colors.tooltipBg,
          titleColor: colors.tooltipTitle,
          bodyColor: colors.tooltipBody,
          borderColor: colors.tooltipBorder,
          borderWidth: 1,
          cornerRadius: 6,
          displayColors: true,
          position: 'nearest',
          yAlign: 'bottom',
          caretPadding: 10,
          callbacks: {
            title: function(context) {
              return 'Time: ' + context[0].label;
            },
            label: function(context) {
              return context.dataset.label + ': ' + context.parsed.y + '%';
            }
          }
        },
        datalabels: {
          display: true,
          color: 'white',
          font: {
            size: 8,
            weight: 'bold'
          },
          formatter: function(value) {
            return value > 5 ? value + '%' : '';
          },
          anchor: 'center',
          align: 'center'
        }
      },
      elements: {
        bar: {
          borderRadius: {
            topLeft: 2,
            topRight: 2,
            bottomLeft: 0,
            bottomRight: 0
          }
        }
      }
    },
  });

  return chart;
}
```

### 2.3 Composable Enhancement

#### 2.3.1 Update useMemsCharts Composable
Add theme change listener and chart refresh:

```typescript
// resources/js/composables/useMemsCharts.ts

import { onMounted, onBeforeUnmount, shallowRef, watch } from 'vue';
import { initUtilizationGauge, initUtilizationTrendChart, chartData } from '@/lib/mems-charts';
import type { Chart as ChartType } from 'chart.js';
import type { ECharts } from 'echarts';

export function useMemsCharts(gaugeId: string, trendId: string) {
    const gaugeChart = shallowRef<ECharts | null>(null);
    const trendChart = shallowRef<ChartType | null>(null);

    const handleResize = () => {
        gaugeChart.value?.resize();
    };

    const updateTrendChart = (timeRange: string) => {
        if (trendChart.value && chartData[timeRange]) {
            trendChart.value.data = chartData[timeRange];
            trendChart.value.update('active');
        }
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

    const initCharts = () => {
        try {
            gaugeChart.value = initUtilizationGauge(gaugeId);
            trendChart.value = initUtilizationTrendChart(trendId);
            window.addEventListener('resize', handleResize);
        } catch (error) {
            console.error('Failed to initialize MEMS charts:', error);
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

    let themeObserver: MutationObserver | null = null;

    onMounted(() => {
        initCharts();
        themeObserver = observeThemeChanges();
    });

    onBeforeUnmount(() => {
        cleanupCharts();
        themeObserver?.disconnect();
    });

    return {
        gaugeChart,
        trendChart,
        updateTrendChart,
    };
}
```

### 2.4 Component Updates

#### 2.4.1 MEMS Dashboard Vue Component
No changes needed to the Vue component itself - it already uses the composable and CSS classes.

#### 2.4.2 CSS Class Refactoring
Update all CSS classes in `mems-dashboard.css` to use CSS custom properties:

**Example transformations:**
```css
/* Before */
.mems-panel {
  background: #0d2137;
  border: 1px solid rgba(0, 212, 255, 0.2);
  color: #ffffff;
}

/* After */
.mems-panel {
  background: var(--mems-panel-bg);
  border: 1px solid var(--mems-panel-border);
  color: var(--mems-text-primary);
}
```

### 2.5 Gradient Handling

For gradients, create theme-aware versions:

```css
/* Light Mode */
:root {
  --mems-gradient-header: linear-gradient(90deg, oklch(0.96 0 0) 0%, oklch(0.92 0 0) 100%);
  --mems-gradient-button: linear-gradient(135deg, oklch(0.92 0 0) 0%, oklch(0.85 0.05 220) 100%);
}

/* Dark Mode */
.dark {
  --mems-gradient-header: linear-gradient(90deg, #0d2137 0%, #132f4c 100%);
  --mems-gradient-button: linear-gradient(135deg, #132f4c 0%, #1a4a6e 100%);
}
```

## 3. Implementation Strategy

### 3.1 Phase 1: CSS Variable Setup
1. Define all CSS custom properties for light and dark modes
2. Test variable definitions in browser dev tools
3. Ensure no syntax errors

### 3.2 Phase 2: CSS Refactoring
1. Replace hardcoded colors with CSS variables
2. Update gradients to use theme-aware definitions
3. Test dark mode to ensure no regressions
4. Verify all UI elements render correctly

### 3.3 Phase 3: Chart Theme Integration
1. Implement `getThemeColors()` function for ECharts
2. Implement `getChartJsThemeColors()` function for Chart.js
3. Update chart initialization functions
4. Test charts in both themes

### 3.4 Phase 4: Theme Change Reactivity
1. Update `useMemsCharts` composable with MutationObserver
2. Implement chart refresh on theme change
3. Test theme switching without page reload
4. Verify smooth transitions

### 3.5 Phase 5: Testing & Refinement
1. Test all components in light mode
2. Test all components in dark mode
3. Test system theme detection
4. Test theme switching
5. Verify contrast ratios (WCAG AA)
6. Fix any visual issues

## 4. Testing Strategy

### 4.1 Visual Testing
- [ ] All panels render correctly in light mode
- [ ] All panels render correctly in dark mode
- [ ] Tables are readable in both themes
- [ ] Charts display correctly in both themes
- [ ] Buttons and inputs are styled appropriately
- [ ] Scrollbars match theme

### 4.2 Functional Testing
- [ ] Theme switches apply immediately
- [ ] Charts refresh when theme changes
- [ ] No console errors during theme switch
- [ ] System theme detection works
- [ ] Theme preference persists across sessions

### 4.3 Accessibility Testing
- [ ] Text contrast meets WCAG AA (4.5:1 for normal text)
- [ ] Large text contrast meets WCAG AA (3:1)
- [ ] Chart labels are readable
- [ ] Focus indicators are visible in both themes

### 4.4 Performance Testing
- [ ] Theme switch is smooth (< 100ms)
- [ ] No layout shifts during theme change
- [ ] Charts reinitialize quickly
- [ ] No memory leaks from observers

## 5. Edge Cases & Considerations

### 5.1 Chart Data Updates
When chart data updates while theme is changing:
- Queue theme refresh after data update completes
- Prevent race conditions with debouncing

### 5.2 Browser Compatibility
- CSS custom properties supported in all modern browsers
- MutationObserver supported in all modern browsers
- Fallback not needed for target browsers

### 5.3 Print Styles
Consider adding print-specific styles:
```css
@media print {
  .mems-dashboard {
    background: white;
    color: black;
  }
}
```

### 5.4 High Contrast Mode
Respect user's high contrast preferences:
```css
@media (prefers-contrast: high) {
  :root {
    --mems-border-subtle: oklch(0.5 0 0);
  }
}
```

## 6. Rollback Plan

If issues arise:
1. Revert CSS changes (keep backup of original `mems-dashboard.css`)
2. Revert chart function changes
3. Revert composable changes
4. Dashboard returns to dark-only mode

## 7. Future Enhancements

### 7.1 Custom Theme Colors
- Allow users to customize accent colors
- Provide theme presets (blue, green, purple)

### 7.2 Per-Dashboard Themes
- Store theme preference per dashboard
- Override global theme for specific dashboards

### 7.3 Animated Transitions
- Add smooth color transitions during theme switch
- Use CSS transitions for color changes

## 8. Documentation

### 8.1 Developer Documentation
- Document CSS custom property naming convention
- Provide examples of adding new theme-aware components
- Explain chart theme integration

### 8.2 User Documentation
- Update user guide with theme switching instructions
- Add screenshots of light and dark modes
- Explain system theme detection

## 9. Success Criteria

The implementation is successful when:
1. ✅ MEMS dashboard displays correctly in light, dark, and system modes
2. ✅ Theme changes apply instantly without page reload
3. ✅ All text meets WCAG AA contrast requirements
4. ✅ Charts adapt to theme changes automatically
5. ✅ No visual regressions in dark mode
6. ✅ Theme preference persists across sessions
7. ✅ No console errors or warnings
8. ✅ Performance remains smooth (< 100ms theme switch)
