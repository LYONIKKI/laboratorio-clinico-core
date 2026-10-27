let chartExamenesInstancia = null;
let chartServiciosInstancia = null;
let chartMensualInstancia = null;

document.addEventListener("DOMContentLoaded", () => {
    cargarMetricasDashboard();
});

async function cargarMetricasDashboard() {
    try {
        const response = await fetch('/laboratorio/public/api/dashboard/metricas');
        if (!response.ok) throw new Error("Fallo en la comunicación con el servidor");

        const data = await response.json();

        // 1. Números Maestros
        const elSol = document.getElementById('w-solicitudes');
        const elProc = document.getElementById('w-procesados');
        const elEsp = document.getElementById('w-espera');

        if (elSol) elSol.textContent = data.widgets.solicitudes || 0;
        if (elProc) elProc.textContent = data.widgets.procesados || 0;
        if (elEsp) elEsp.textContent = data.widgets.espera || 0;

        // 2. Gráficos Limpios
        renderizarChartExamenes(data.top_examenes || []);
        renderizarChartServicios(data.servicios || []);
        renderizarChartMensual(data.acumulado_mensual || []);

    } catch (error) {
        console.error("Error al cargar dashboard:", error);
    }
}

function renderizarChartExamenes(lista) {
    const canvas = document.getElementById('chartExamenes');
    if (!canvas || typeof Chart === 'undefined') return;

    // Recorta nombres largos para que no compriman la gráfica
    const labels = lista.map(x => {
        const str = (x.Examen || x.examen || '').trim();
        return str.length > 28 ? str.substring(0, 26) + '...' : str;
    });
    const valores = lista.map(x => x.Cantidad || x.cantidad);

    if (chartExamenesInstancia) chartExamenesInstancia.destroy();

    chartExamenesInstancia = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: valores,
                backgroundColor: '#2563eb',
                hoverBackgroundColor: '#1d4ed8',
                borderRadius: 4,
                barThickness: 20
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: (ctx) => lista[ctx[0].dataIndex].Examen || lista[ctx[0].dataIndex].examen
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: '#f8fafc' },
                    ticks: { font: { size: 10 } }
                },
                y: {
                    grid: { display: false },
                    ticks: { font: { size: 10, weight: '600' } }
                }
            }
        }
    });
}

function renderizarChartServicios(lista) {
    const canvas = document.getElementById('chartServicios');
    if (!canvas || typeof Chart === 'undefined') return;

    // Top 5 servicios + consolidado en Otros
    let labels = [];
    let valores = [];

    if (lista.length > 5) {
        const top5 = lista.slice(0, 5);
        labels = top5.map(x => x.Servicio || x.servicio);
        valores = top5.map(x => Number(x.Cantidad || x.cantidad));

        const otrosTotal = lista.slice(5).reduce((sum, item) => sum + Number(item.Cantidad || item.cantidad), 0);
        labels.push('OTROS SERVICIOS');
        valores.push(otrosTotal);
    } else {
        labels = lista.map(x => x.Servicio || x.servicio);
        valores = lista.map(x => Number(x.Cantidad || x.cantidad));
    }

    if (chartServiciosInstancia) chartServiciosInstancia.destroy();

    chartServiciosInstancia = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: valores,
                backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#94a3b8'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 8,
                        padding: 8,
                        font: { size: 9, weight: '600' }
                    }
                }
            }
        }
    });
}

function renderizarChartMensual(lista) {
    const canvas = document.getElementById('chartMensual');
    if (!canvas || typeof Chart === 'undefined') return;

    const labels = lista.map(x => x.Mes || x.mes);
    const valores = lista.map(x => Number(x.Cantidad || x.cantidad || 0));

    if (chartMensualInstancia) chartMensualInstancia.destroy();

    chartMensualInstancia = new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Atenciones / Día',
                data: valores,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.08)',
                fill: true,
                tension: 0.2,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 1.5,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f8fafc' },
                    ticks: { font: { size: 10 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 9, weight: 'bold' } }
                }
            }
        }
    });
}