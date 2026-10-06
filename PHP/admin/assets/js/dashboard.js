document.addEventListener('DOMContentLoaded', function() {
    // Verificar si existen los datos de la gráfica en el objeto global
    if (typeof window.dashboardChartData !== 'undefined') {
        const ctx = document.getElementById('salesTrendChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: window.dashboardChartData.labels,
                    datasets: [
                        {
                            label: 'Monto de Ventas ($)',
                            data: window.dashboardChartData.montos,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.15)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointBackgroundColor: '#6366f1',
                            pointRadius: 5
                        },
                        {
                            label: 'Cantidad de Transacciones',
                            data: window.dashboardChartData.ventas,
                            borderColor: '#ffd93d',
                            backgroundColor: 'transparent',
                            borderDash: [5, 5],
                            tension: 0.4,
                            borderWidth: 2,
                            pointBackgroundColor: '#ffd93d',
                            pointRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#94a3b8',
                                font: {
                                    family: 'Plus Jakarta Sans',
                                    size: 12
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { color: '#94a3b8' }
                        },
                        y: {
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { color: '#94a3b8' }
                        }
                    }
                }
            });
        }
    }
});