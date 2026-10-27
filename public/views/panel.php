<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio Clínico - Panel de Control</title>
    
    <!-- CSS 100% Locales (Offline) -->
    <link rel="stylesheet" href="/laboratorio/public/css/tailwind.min.css">
    <link rel="stylesheet" href="/laboratorio/public/css/all.min.css">
    <link rel="stylesheet" href="/laboratorio/public/css/modales.css">
</head>
<body class="bg-slate-100 font-sans text-slate-800 h-screen flex overflow-hidden antialiased">

    <!-- Sidebar institucional -->
    <?php include __DIR__ . '/layout/sidebar.php'; ?>

    <!-- Modal Modular de Reportes -->
    <?php include __DIR__ . '/modales/modalReporteProductividad.php'; ?>

    <!-- Área de Trabajo -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        
        <!-- Header institucional -->
        <?php include __DIR__ . '/layout/header.php'; ?>

        <!-- Contenido Central con Scroll Interno -->
        <main class="p-6 flex-1 overflow-y-auto space-y-4">
            
            <!-- Barra de Título -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h1 class="text-lg font-black text-slate-900 tracking-wide uppercase flex items-center">
                        <i class="fa-solid fa-chart-simple text-blue-600 mr-2.5"></i> Tablero de Control de Turno
                    </h1>
                    <p class="text-xs text-slate-500 font-medium">Monitoreo de producción analítica y demanda de atención</p>
                </div>
                
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="abrirModalReportes()" class="btn-ui btn-ui-green">
                        <i class="fa-solid fa-file-excel text-white"></i> Exportar Reportes
                    </button>
                    <div class="text-xs font-bold text-blue-800 bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-200 flex items-center shadow-sm">
                        <i class="fa-solid fa-clock-rotate-left mr-1.5 text-blue-600"></i> Ventana: Últimas 72 Horas
                    </div>
                </div>
            </div>
            <!-- TARJETAS DE IMPACTO (KPIs) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Órdenes Registradas -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center justify-between shadow-xs">
                    <div class="space-y-1">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Órdenes Totales</p>
                        <h3 id="w-solicitudes" class="text-2xl font-black text-blue-600 font-mono">0</h3>
                        <span class="text-[10px] text-slate-400">Pacientes ingresados</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600">
                        <i class="fa-solid fa-file-medical text-xl"></i>
                    </div>
                </div>

                <!-- Resultados Entregados -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center justify-between shadow-xs">
                    <div class="space-y-1">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Resultados Validados</p>
                        <h3 id="w-procesados" class="text-2xl font-black text-emerald-600 font-mono">0</h3>
                        <span class="text-[10px] text-emerald-600 font-bold flex items-center">
                            <i class="fa-solid fa-circle-check mr-1"></i> Entregados a piso
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                        <i class="fa-solid fa-square-check text-xl"></i>
                    </div>
                </div>

                <!-- Pendientes -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center justify-between shadow-xs">
                    <div class="space-y-1">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pendientes de Validación</p>
                        <h3 id="w-espera" class="text-2xl font-black text-amber-500 font-mono">0</h3>
                        <span class="text-[10px] text-amber-600 font-bold flex items-center">
                            <i class="fa-solid fa-hourglass-half mr-1"></i> En procesamiento
                        </span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-500">
                        <i class="fa-solid fa-clock text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- GRÁFICOS (Fila Doble) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                
                <!-- Top Exámenes -->
                <div class="lg:col-span-7 bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center">
                            <i class="fa-solid fa-vial text-blue-600 mr-2"></i> Top 5 Exámenes Más Demandados
                        </h3>
                        <span class="text-[10px] font-mono text-slate-400 font-bold">72h</span>
                    </div>
                    <div class="relative w-full" style="height: 260px;">
                        <canvas id="chartExamenes"></canvas>
                    </div>
                </div>

                <!-- Procedencia -->
                <div class="lg:col-span-5 bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center">
                            <i class="fa-solid fa-hospital-user text-indigo-600 mr-2"></i> Distribución de Procedencia
                        </h3>
                        <span class="text-[10px] font-mono text-slate-400 font-bold">Servicios</span>
                    </div>
                    <div class="relative w-full flex items-center justify-center" style="height: 260px;">
                        <canvas id="chartServicios"></canvas>
                    </div>
                </div>
            </div>

            <!-- Evolución Mensual -->
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                    <div>
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center">
                            <i class="fa-solid fa-chart-area text-emerald-600 mr-2"></i> Evolución Cronológica de Atenciones
                        </h3>
                        <p class="text-[10px] text-slate-400">Total de movimientos diarios registrados durante el mes en curso</p>
                    </div>
                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Mes Actual</span>
                </div>
                <div class="relative w-full" style="height: 210px;">
                    <canvas id="chartMensual"></canvas>
                </div>
            </div>

        </main>
    </div>

    <!-- Scripts Locales Offline -->
    <script src="/laboratorio/public/js/sweetalert2.all.min.js"></script>
    <script src="/laboratorio/public/js/chart.min.js"></script>
    <script src="/laboratorio/public/js/dashboard.js"></script>
    <script src="/laboratorio/public/js/reportes-productividad.js"></script>
</body>
</html>