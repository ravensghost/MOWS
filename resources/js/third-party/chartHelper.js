// resources/js/third-party/chartHelper.js
import Chart from 'chart.js/auto';

/**
 * Alpine component for Chart.js integration with dynamic theme variable support.
 */
export default function chartHelper(config = {}) {
    return {
        chartInstance: null,
        init() {
            const ctx = this.$el.getContext('2d');
            if (!ctx) return;

            // Dynamically resolve theme colours from CSS variables for canvas rendering
            const textColor = window.getThemeColour ? window.getThemeColour('--theme-text-p') : '#f3f4f6';
            const borderColor = window.getThemeColour ? window.getThemeColour('--theme-border-colour') : '#374151';
            const accentColor = window.getThemeColour ? window.getThemeColour('--theme-bg-accent-colour') : '#3b82f6';

            // Merge theme-aware defaults into Chart.js config options
            const themeAwareOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: textColor
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: textColor },
                        grid: { color: borderColor }
                    },
                    y: {
                        ticks: { color: textColor },
                        grid: { color: borderColor }
                    }
                },
                ...(config.options || {})
            };

            // Ensure dataset colors fallback to theme accent if not explicitly provided
            const datasets = (config.data?.datasets || []).map(dataset => ({
                backgroundColor: accentColor,
                borderColor: accentColor,
                ...dataset
            }));

            this.chartInstance = new Chart(ctx, {
                ...config,
                data: {
                    ...(config.data || {}),
                    datasets
                },
                options: themeAwareOptions
            });
        },
        updateData(newData, datasetIndex = 0) {
            if (this.chartInstance) {
                this.chartInstance.data.datasets[datasetIndex].data = newData;
                this.chartInstance.update();
            }
        },
        destroy() {
            if (this.chartInstance) {
                this.chartInstance.destroy();
            }
        }
    };
}