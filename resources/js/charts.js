/**
 * charts.js — initialisation des graphiques Chart.js déclarés dans les vues.
 *
 * Chaque canvas porte `data-chart` (type) et `data-series` (JSON).
 * Types pris en charge : `bar` (progression par compétence) et `line` (évolution).
 */
document.addEventListener('DOMContentLoaded', () => {
    if (!window.Chart) return;

    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        let series = {};
        try {
            series = JSON.parse(canvas.dataset.series || '{}');
        } catch (e) {
            series = {};
        }

        build(canvas, canvas.dataset.chart, series);
    });
});

function build(canvas, type, series) {
    const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        animation: reduceMotion ? false : { duration: 800, easing: 'easeOutQuart' },
        plugins: { legend: { display: type === 'line' } },
        scales: { y: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + ' %' } } },
    };

    if (type === 'bar') {
        new window.Chart(canvas, {
            type: 'bar',
            data: {
                labels: series.labels || [],
                datasets: [{
                    label: 'Progression (%)',
                    data: series.values || [],
                    backgroundColor: ['#2563eb', '#0ea5e9', '#14b8a6', '#8b5cf6'],
                    borderRadius: 6,
                }],
            },
            options: { ...baseOptions, plugins: { legend: { display: false } } },
        });
        return;
    }

    // line (évolution par Modelltest)
    new window.Chart(canvas, {
        type: 'line',
        data: {
            labels: series.labels || [],
            datasets: (series.datasets || []).map((dataset) => ({
                label: dataset.label,
                data: dataset.data,
                borderColor: dataset.color || '#2563eb',
                backgroundColor: 'transparent',
                tension: 0.3,
                pointRadius: 3,
            })),
        },
        options: baseOptions,
    });
}