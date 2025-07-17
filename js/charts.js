document.addEventListener('DOMContentLoaded', function() {
    if (typeof petMeasurements !== 'undefined' && petMeasurements.length > 0) {
        const dates = petMeasurements.map(m => new Date(m.fecha_medicion).toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' }));

        const datasets = [];

        // Peso
        const weights = petMeasurements.map(m => parseFloat(m.peso)).filter(val => !isNaN(val));
        if (weights.length > 0) {
            datasets.push({
                label: 'Peso (kg)',
                data: petMeasurements.map(m => parseFloat(m.peso) || null),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)', // Added background color
                tension: 0.3, // Slightly more tension for smoother curves
                fill: false,
                pointRadius: 5, // Larger points
                pointBackgroundColor: 'rgb(75, 192, 192)', // Point color
                pointBorderColor: '#fff',
                pointHoverRadius: 7,
                pointHoverBackgroundColor: 'rgb(75, 192, 192)',
                pointHoverBorderColor: 'rgba(220,220,220,1)'
            });
        }

        // Altura
        const heights = petMeasurements.map(m => parseFloat(m.altura)).filter(val => !isNaN(val));
        if (heights.length > 0) {
            datasets.push({
                label: 'Altura (cm)',
                data: petMeasurements.map(m => parseFloat(m.altura) || null),
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.3,
                fill: false,
                pointRadius: 5,
                pointBackgroundColor: 'rgb(255, 99, 132)',
                pointBorderColor: '#fff',
                pointHoverRadius: 7,
                pointHoverBackgroundColor: 'rgb(255, 99, 132)',
                pointHoverBorderColor: 'rgba(220,220,220,1)'
            });
        }

        // Longitud
        const lengths = petMeasurements.map(m => parseFloat(m.longitud)).filter(val => !isNaN(val));
        if (lengths.length > 0) {
            datasets.push({
                label: 'Longitud (cm)',
                data: petMeasurements.map(m => parseFloat(m.longitud) || null),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.3,
                fill: false,
                pointRadius: 5,
                pointBackgroundColor: 'rgb(54, 162, 235)',
                pointBorderColor: '#fff',
                pointHoverRadius: 7,
                pointHoverBackgroundColor: 'rgb(54, 162, 235)',
                pointHoverBorderColor: 'rgba(220,220,220,1)'
            });
        }

        // Circunferencia del Cuello
        const neckCircumferences = petMeasurements.map(m => parseFloat(m.circunferencia_cuello)).filter(val => !isNaN(val));
        if (neckCircumferences.length > 0) {
            datasets.push({
                label: 'Cuello (cm)',
                data: petMeasurements.map(m => parseFloat(m.circunferencia_cuello) || null),
                borderColor: 'rgb(255, 206, 86)',
                backgroundColor: 'rgba(255, 206, 86, 0.2)',
                tension: 0.3,
                fill: false,
                pointRadius: 5,
                pointBackgroundColor: 'rgb(255, 206, 86)',
                pointBorderColor: '#fff',
                pointHoverRadius: 7,
                pointHoverBackgroundColor: 'rgb(255, 206, 86)',
                pointHoverBorderColor: 'rgba(220,220,220,1)'
            });
        }

        if (datasets.length > 0) {
            const ctx = document.getElementById('weightChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { // Added interaction for better tooltips
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'Medida'
                            },
                            grid: { // Added grid lines for better readability
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Fecha'
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Historial de Medidas',
                            font: {
                                size: 18
                            },
                            color: '#333'
                        },
                        tooltip: { // Enhanced tooltips
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y;
                                    }
                                    return label;
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',
                            titleFont: { size: 14 },
                            bodyFont: { size: 12 },
                            padding: 10,
                            cornerRadius: 5
                        },
                        legend: { // Enhanced legend
                            display: true,
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 12
                                },
                                color: '#333',
                                usePointStyle: true // Use point style in legend
                            }
                        }
                    }
                }
            });
        } else {
            // Si no hay datos numéricos para graficar, mostrar un mensaje
            const chartContainer = document.getElementById('weightChart').parentNode;
            chartContainer.innerHTML = '<p class="text-muted text-center">No hay datos de medidas numéricas para mostrar en la gráfica.</p>';
        }
    }
});