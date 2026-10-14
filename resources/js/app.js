import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();


document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('responseTimeChart');

    if (!canvas) {
        return;
    }

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const responseTimes = JSON.parse(canvas.dataset.values || '[]');

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Response time',
                data: responseTimes,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.10)',
                borderWidth: 2,
                fill: true,
                tension: 0.25,
                pointRadius: labels.length > 60 ? 0 : 3,
                pointHoverRadius: 5,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            return `Response time: ${context.parsed.y.toLocaleString()} ms`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Check time',
                    },
                    ticks: {
                        maxTicksLimit: 10,
                        maxRotation: 0,
                    },
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Milliseconds (ms)',
                    },
                    ticks: {
                        callback(value) {
                            return `${Number(value).toLocaleString()} ms`;
                        },
                    },
                },
            },
        },
    });
});
