import * as echarts from 'echarts';
import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

/**
 * Get theme-appropriate colors for ECharts based on current theme mode
 * @returns Object containing color values for chart elements
 */
function getThemeColors() {
  const isDark = document.documentElement.classList.contains('dark');

  return {
    text: isDark ? '#ffffff' : '#1a1a1a',
    background: 'transparent',
    axisLine: isDark ? '#ffffff' : '#333333',
    splitLine: isDark ? '#ffffff' : '#666666',
    pointer: isDark ? '#00dc82' : '#00c16a',
    detail: isDark ? '#ffffff' : '#1a1a1a',
    runColor: isDark ? '#00dc82' : '#00c16a',
    waitColor: isDark ? '#e96a24' : '#ea580c',
  };
}

/**
 * Get theme-appropriate colors for Chart.js based on current theme mode
 * @returns Object containing color values for Chart.js elements
 */
export function getChartJsThemeColors() {
  const isDark = document.documentElement.classList.contains('dark');
  
  return {
    text: isDark ? '#ffffff' : '#1a1a1a',
    secondaryText: isDark ? '#b0bec5' : '#666666',
    grid: isDark ? 'rgba(176, 190, 197, 0.1)' : 'rgba(0, 0, 0, 0.1)',
    border: isDark ? 'rgba(176, 190, 197, 0.2)' : 'rgba(0, 0, 0, 0.1)',
    tooltipBg: isDark ? 'rgba(13, 33, 55, 0.95)' : 'rgba(255, 255, 255, 0.95)',
    tooltipBorder: isDark ? '#00dc82' : '#00c16a',
    tooltipTitle: isDark ? '#00dc82' : '#00c16a',
    tooltipBody: isDark ? '#ffffff' : '#1a1a1a',
    runColor: isDark ? '#00dc82' : '#00c16a',
    waitColor: isDark ? '#5B7FDB' : '#4169E1',
    idleColor: isDark ? '#e96a24' : '#ea580c',
  };
}

// Register Chart.js components
Chart.register(...registerables, ChartDataLabels);

export interface ChartDataset {
  label: string;
  data: number[];
  backgroundColor: string;
  borderColor: string;
  borderWidth: number;
}

export interface ChartTimeData {
  labels: string[];
  datasets: ChartDataset[];
}

export const chartData: Record<string, ChartTimeData> = {
  '1h': {
    labels: ['1h ago', '55m', '50m', '45m', '40m', '35m', '30m', '25m', '20m', '15m', '10m', 'Now'],
    datasets: [
      {
        label: "Running",
        data: [85, 83, 81, 84, 88, 85, 82, 79, 81, 83, 86, 84],
        backgroundColor: "#0f6b3d",
        borderColor: "#0f6b3d",
        borderWidth: 0,
      },
      {
        label: "Waiting",
        data: [12, 14, 16, 13, 10, 12, 15, 18, 16, 14, 11, 13],
        backgroundColor: "#5B7FDB",
        borderColor: "#5B7FDB",
        borderWidth: 0,
      },
      {
        label: "Idle",
        data: [3, 3, 3, 3, 2, 3, 3, 3, 3, 3, 3, 3],
        backgroundColor: "#e96a24",
        borderColor: "#e96a24",
        borderWidth: 0,
      }
    ],
  },
  '2h': {
    labels: ['2h ago', '1h50m', '1h40m', '1h30m', '1h20m', '1h10m', '1h', '50m', '40m', '30m', '20m', 'Now'],
    datasets: [
      {
        label: "Running",
        data: [80, 82, 85, 83, 81, 84, 88, 85, 82, 79, 81, 83],
        backgroundColor: "#0f6b3d",
        borderColor: "#0f6b3d",
        borderWidth: 0,
      },
      {
        label: "Waiting",
        data: [17, 15, 12, 14, 16, 13, 10, 12, 15, 18, 16, 14],
        backgroundColor: "#5B7FDB",
        borderColor: "#5B7FDB",
        borderWidth: 0,
      },
      {
        label: "Idle",
        data: [3, 3, 3, 3, 3, 3, 2, 3, 3, 3, 3, 3],
        backgroundColor: "#e96a24",
        borderColor: "#e96a24",
        borderWidth: 0,
      }
    ],
  },
  '4h': {
    labels: ['4h ago', '3.5h', '3h', '2.5h', '2h', '1.5h', '1h', '45m', '30m', '15m', '5m', 'Now'],
    datasets: [
      {
        label: "Running",
        data: [75, 78, 82, 85, 80, 77, 83, 88, 85, 82, 79, 81],
        backgroundColor: "#0f6b3d",
        borderColor: "#0f6b3d",
        borderWidth: 0,
      },
      {
        label: "Waiting",
        data: [20, 18, 15, 12, 17, 20, 14, 10, 12, 15, 18, 16],
        backgroundColor: "#5B7FDB",
        borderColor: "#5B7FDB",
        borderWidth: 0,
      },
      {
        label: "Idle",
        data: [5, 4, 3, 3, 3, 3, 3, 2, 3, 3, 3, 3],
        backgroundColor: "#e96a24",
        borderColor: "#e96a24",
        borderWidth: 0,
      }
    ],
  },
  '12h': {
    labels: ['12h ago', '11h', '10h', '9h', '8h', '7h', '6h', '5h', '4h', '3h', '2h', 'Now'],
    datasets: [
      {
        label: "Running",
        data: [78, 80, 82, 79, 81, 83, 85, 82, 84, 86, 83, 85],
        backgroundColor: "#0f6b3d",
        borderColor: "#0f6b3d",
        borderWidth: 0,
      },
      {
        label: "Waiting",
        data: [18, 16, 15, 17, 16, 14, 12, 15, 13, 11, 14, 12],
        backgroundColor: "#5B7FDB",
        borderColor: "#5B7FDB",
        borderWidth: 0,
      },
      {
        label: "Idle",
        data: [4, 4, 3, 4, 3, 3, 3, 3, 3, 3, 3, 3],
        backgroundColor: "#e96a24",
        borderColor: "#e96a24",
        borderWidth: 0,
      }
    ],
  },
  '1d': {
    labels: ['24h ago', '22h', '20h', '18h', '16h', '14h', '12h', '10h', '8h', '6h', '4h', 'Now'],
    datasets: [
      {
        label: "Running",
        data: [76, 78, 80, 82, 79, 81, 83, 85, 82, 84, 86, 83],
        backgroundColor: "#0f6b3d",
        borderColor: "#0f6b3d",
        borderWidth: 0,
      },
      {
        label: "Waiting",
        data: [19, 18, 16, 14, 17, 15, 13, 11, 14, 12, 10, 13],
        backgroundColor: "#5B7FDB",
        borderColor: "#5B7FDB",
        borderWidth: 0,
      },
      {
        label: "Idle",
        data: [5, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
        backgroundColor: "#e96a24",
        borderColor: "#e96a24",
        borderWidth: 0,
      }
    ],
  }
};

export function initUtilizationGauge(elementId: string) {
  const element = document.getElementById(elementId);
  if (!element) {
    return null;
  }

  const chart = echarts.init(element);
  const colors = getThemeColors();
  
  // Get idle color
  const isDark = document.documentElement.classList.contains('dark');
  const idleColor = isDark ? '#e96a24' : '#ea580c';
  
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
          width: 24,
          itemStyle: {
            color: colors.runColor
          }
        },
        axisLine: {
          lineStyle: {
            width: 22,
            color: [
              [0.8, colors.runColor],
              [0.95, colors.waitColor],
              [1, idleColor]
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
            color: colors.runColor
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
            name: ''
          }
        ]
      }
    ]
  };
  
  chart.setOption(option);
  
  return chart;
}

export function initUtilizationTrendChart(canvasId: string) {
  const canvas = document.getElementById(canvasId) as HTMLCanvasElement;
  if (!canvas) {
    return null;
  }

  const ctx = canvas.getContext('2d');
  if (!ctx) {
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
      resizeDelay: 0, // Resize immediately
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
              const dataset = context.dataset as any;
              const dataIndex = context.dataIndex;
              const percentage = context.parsed.y;
              
              // Check if we have raw counts stored
              if (dataset.rawCounts && dataset.rawCounts[dataIndex] !== undefined) {
                const count = dataset.rawCounts[dataIndex];
                return context.dataset.label + ': ' + count + ' (' + percentage + '%)';
              }
              
              // Fallback to percentage only
              return context.dataset.label + ': ' + percentage + '%';
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
