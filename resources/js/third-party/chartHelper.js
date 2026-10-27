// resources/js/third-party/chartHelper.js
import Chart from 'chart.js/auto';

/**
 * Alpine component for Chart.js integration
 */
export default function chartHelper(config = {}) {
    return {
        chartInstance: null,
        init() {
            const ctx = this.$el.getContext('2d');
            if (!ctx) return;

            this.chartInstance = new Chart(ctx, {
                ...config,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    ...(config.options || {})
                }
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