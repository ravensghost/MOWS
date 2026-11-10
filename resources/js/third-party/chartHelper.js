// resources/js/third-party/chartHelper.js
import { Chart, registerables } from 'chart.js';

// Register all Chart.js components
Chart.register(...registerables);

/**
 * Global Chart.js Helper Wrapper
 */
window.ChartHelper = {
    instances: {},

    create(canvasId, config) {
        const ctx = typeof canvasId === 'string' ? document.getElementById(canvasId) : canvasId;
        if (!ctx) return null;

        // Destroy existing instance on this canvas if it exists
        if (this.instances[canvasId]) {
            this.instances[canvasId].destroy();
        }

        const chartInstance = new Chart(ctx, config);
        if (typeof canvasId === 'string') {
            this.instances[canvasId] = chartInstance;
        }
        return chartInstance;
    },

    destroy(canvasId) {
        if (this.instances[canvasId]) {
            this.instances[canvasId].destroy();
            delete this.instances[canvasId];
        }
    }
};

window.Chart = Chart;

export default Chart;