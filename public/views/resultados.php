<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio Clínico - Historial y Consulta de Resultados</title>
    
    <!-- CSS 100% Locales (Offline) Tailwind v2.2.19 -->
    <link rel="stylesheet" href="/laboratorio/public/css/tailwind.min.css">
    <link rel="stylesheet" href="/laboratorio/public/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans text-gray-800 h-screen flex overflow-hidden antialiased">

    <!-- Sidebar Institucional -->
    <?php include __DIR__ . '/layout/sidebar.php'; ?>

    <!-- Área de Trabajo Principal -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        
        <!-- Header Institucional -->
        <?php include __DIR__ . '/layout/header.php'; ?>

        <!-- Contenido Central Scrolleable -->
        <main class="p-6 flex-1 overflow-y-auto space-y-4">
            
            <!-- Barra Superior y Título -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h1 class="text-lg font-black text-gray-900 tracking-wide uppercase flex items-center">
                        <i class="fa-solid fa-square-poll-horizontal text-blue-600 mr-2.5"></i> Módulo de Resultados de Laboratorio
                    </h1>
                    <p class="text-xs text-gray-500 font-medium">Búsqueda histórica de órdenes por Cuenta de Atención, DNI o N° Historia Clínica</p>
                </div>
            </div>

            <!-- Buscador Clínico Hero -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 text-center space-y-4">
                <div class="inline-flex p-3 bg-blue-50 text-blue-600 rounded-2xl mb-1">
                    <i class="fa-solid fa-vials text-3xl"></i>
                </div>
                <h2 class="text-base font-black text-gray-800 uppercase tracking-tight">Buscador Clínico de Resultados e Historial</h2>
                <p class="text-xs text-gray-500 max-w-xl mx-auto font-mono">
                    Ingrese el número de Cuenta de Atención, DNI o apellidos del paciente para localizar órdenes pasadas, procesar cultivos de 7 a 15 días o reimprimir informes oficiales.
                </p>

                <!-- Input Group Centrado -->
                <div class="max-w-xl mx-auto flex items-center shadow-sm rounded-xl overflow-hidden border border-gray-300 focus-within:ring-2 focus-within:ring-blue-500">
                    <input type="text" id="inputBuscarHistorial" placeholder="Ingrese N° Cuenta, DNI o Historia Clínica..." 
                           class="w-full bg-white px-4 py-2.5 font-mono text-xs text-gray-800 outline-none">
                    <button type="button" onclick="ejecutarBusquedaResultados()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-5 py-2.5 transition flex items-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-magnifying-glass text-yellow-300"></i>
                        <span>Buscar</span>
                    </button>
                </div>
            </div>

            <!-- Grilla de Resultados de Órdenes Localizadas -->
            <div id="contenedorResultadosBusqueda" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden hidden">
                <div class="p-3.5 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-list-check text-blue-600 mr-2"></i> Órdenes Clínicas Encontradas
                    </h3>
                    <span id="contador-resultados-busqueda" class="text-[10px] font-mono font-bold bg-blue-100 text-blue-800 px-2 py-0.5 rounded">0 Órdenes</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 border-b border-gray-200 font-bold uppercase tracking-wider text-[10px] font-mono">
                                <th class="p-3 text-center w-28">N° Cuenta</th>
                                <th class="p-3 w-32">Fecha/Hora</th>
                                <th class="p-3 w-40">Identificación</th>
                                <th class="p-3">Paciente</th>
                                <th class="p-3 w-44">Servicio Procedencia</th>
                                <th class="p-3 text-center w-28">Estado</th>
                                <th class="p-3 text-center w-28">Exámenes</th>
                                <th class="p-3 text-center w-28">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyResultadosBusqueda" class="divide-y divide-gray-200">
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: DETALLE DE EXÁMENES DE LA ORDEN (CON SCROLL FIJO GARANTIZADO) -->
    <!-- ========================================================================= -->
    <div id="modalDetalleSolicitud" class="fixed inset-0 flex items-center justify-center hidden z-50 p-4" style="background-color: rgba(17, 24, 39, 0.7);">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-300 flex flex-col" style="max-height: 90vh;">
            
            <!-- Cabecera Azul Fija -->
            <div class="bg-blue-700 px-5 py-3 text-white flex justify-between items-center shadow flex-shrink-0">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-microscope text-yellow-300 mr-2"></i>
                    <h3 class="text-xs font-bold uppercase tracking-wider">Detalle de Orden Clínica</h3>
                </div>
                <button type="button" onclick="cerrarModalDetalle()" class="text-blue-200 hover:text-white transition cursor-pointer focus:outline-none">
                    <i class="fa-solid fa-times text-lg"></i>
                </button>
            </div>

            <!-- Ficha Clínica Fija -->
            <div class="bg-gray-50 p-4 border-b border-gray-200 text-xs space-y-2 flex-shrink-0">
                <input type="hidden" id="detIdMovimiento">
                <input type="hidden" id="detHcValue">
                <input type="hidden" id="detIdCuentaAtencion">
                <input type="hidden" id="detEdadValue">
                <input type="hidden" id="detResponsableValue">
                <input type="hidden" id="detSeguroValue">
                
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-gray-400 uppercase tracking-wider block font-bold" style="font-size: 10px;">Paciente</span>
                        <h4 id="detPaciente" class="text-sm font-bold text-gray-800 uppercase leading-snug">---</h4>
                    </div>
                    <div class="text-right">
                        <span id="detDni" class="bg-blue-100 text-blue-800 font-mono font-bold px-2 py-0.5 rounded text-xs inline-block">DNI: ---</span>
                    </div>
                </div>

                <!-- Cama y Diagnóstico con Autocompletado CIE-10 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 pt-2 border-t border-gray-200 text-xs font-mono">
                    <div>
                        <label class="block font-bold text-gray-500 uppercase" style="font-size: 10px;">Cama / Ubicación:</label>
                        <input type="text" id="detCamaInput" onchange="guardarCabeceraClinica()"
                               class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-gray-800 font-bold focus:outline-none focus:ring-1 focus:ring-blue-500" 
                               placeholder="Ej: Cama 12 / Observación">
                    </div>
                    <div class="relative">
                        <label class="block font-bold text-gray-500 uppercase" style="font-size: 10px;">Diagnóstico Clínico (CIE-10):</label>
                        <input type="text" id="detDiagnosticoInput" autocomplete="off"
                               class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-gray-800 font-bold focus:outline-none focus:ring-1 focus:ring-blue-500" 
                               placeholder="Escribe código o nombre (Ej: N39.0)...">
                        <div id="sugerenciasDiagnostico" class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-300 rounded shadow-lg hidden z-50 max-h-40 overflow-y-auto font-mono text-xs"></div>
                    </div>
                </div>

                <!-- Servicio y Médico Prescriptor con Autocompletado -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 pt-1 border-t border-gray-200 text-xs font-mono">
                    <div class="relative">
                        <label class="block font-bold text-gray-500 uppercase" style="font-size: 10px;">Procedencia / Servicio:</label>
                        <input type="text" id="detProcedenciaInput" autocomplete="off"
                               class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-gray-800 font-bold focus:outline-none focus:ring-1 focus:ring-blue-500" 
                               placeholder="Buscar servicio...">
                        <div id="sugerenciasServicio" class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-300 rounded shadow-lg hidden z-50 max-h-40 overflow-y-auto font-mono text-xs"></div>
                    </div>
                    <div class="relative">
                        <label class="block font-bold text-gray-500 uppercase" style="font-size: 10px;">Médico Prescriptor:</label>
                        <input type="text" id="detMedicoInput" autocomplete="off"
                               class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-gray-800 font-bold focus:outline-none focus:ring-1 focus:ring-blue-500" 
                               placeholder="Buscar médico...">
                        <div id="sugerenciasMedico" class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-300 rounded shadow-lg hidden z-50 max-h-40 overflow-y-auto font-mono text-xs"></div>
                    </div>
                </div>
            </div>

            <!-- Lista de Analitos con Scroll Forzado -->
            <div class="p-4 overflow-y-auto flex-1 space-y-2 text-xs" id="listaExamenesSolicitud" style="max-height: calc(90vh - 250px);"></div>

            <!-- Pie de Modal Fijo -->
            <div class="bg-gray-50 px-5 py-3 border-t border-gray-200 flex justify-between items-center text-xs flex-shrink-0">
                <button type="button" onclick="imprimirEtiquetaTubo()" 
                        class="bg-gray-800 hover:bg-gray-900 text-white font-bold px-3.5 py-1.5 rounded-lg flex items-center transition cursor-pointer">
                    <i class="fa-solid fa-barcode mr-1.5"></i>
                    <span>Etiqueta Tubo</span>
                </button>
                <button type="button" onclick="cerrarModalDetalle()" 
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-1.5 rounded-lg transition cursor-pointer">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: DIGITACIÓN MANUAL DINÁMICA CON SCROLL INTERNO FIJO -->
    <!-- ========================================================================= -->
    <div id="modalDigitacion" class="fixed inset-0 flex items-center justify-center hidden z-50 p-4" style="background-color: rgba(17, 24, 39, 0.7);">
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full border border-gray-300 flex flex-col overflow-hidden font-mono text-xs" style="max-height: 85vh;">
            
            <!-- Cabecera Fija -->
            <div class="bg-blue-700 px-5 py-3 text-white flex justify-between items-center flex-shrink-0">
                <h4 id="modalDigitacionLabel" class="text-xs font-bold uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-pen-to-square mr-2"></i> Digitación de Resultados
                </h4>
                <button type="button" onclick="cerrarModalDigitacion()" class="text-blue-200 hover:text-white transition cursor-pointer focus:outline-none">
                    <i class="fa-solid fa-times text-lg"></i>
                </button>
            </div>

            <!-- Cuerpo con Scroll Forzado -->
            <div class="p-4 overflow-y-auto flex-1 space-y-3" id="modalDigitacionBody" style="max-height: calc(85vh - 110px);">
                <!-- Inyección dinámica de plantillas -->
            </div>

            <!-- Pie de Modal Fijo -->
            <div class="bg-gray-50 px-5 py-3 border-t border-gray-200 flex justify-between items-center text-xs flex-shrink-0">
                <button type="button" onclick="cerrarModalDigitacion()" 
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-2 rounded-lg transition cursor-pointer">
                    Cerrar
                </button>
                <div class="flex items-center space-x-2">
                    <button type="button" id="btnImprimirModalManual" onclick="imprimirDesdeModalDigitacion()" 
                            class="bg-red-600 hover:bg-red-700 text-white font-bold px-4 py-2 rounded-lg transition shadow flex items-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-print"></i>
                        <span>Imprimir</span>
                    </button>
                    <button type="button" id="btnGuardarResultadosManual" onclick="procesarGuardadoResultados()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-lg transition shadow flex items-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Guardar Resultados</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts Locales Offline (100% Vanilla JS, Sin jQuery) -->
    <script src="/laboratorio/public/js/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    <!-- Módulos Clínicos Desacoplados Reutilizados -->
    <script src="/laboratorio/public/js/solicitudes/hematologia.js"></script>
    <script src="/laboratorio/public/js/solicitudes/bioquimica.js"></script>
    <script src="/laboratorio/public/js/solicitudes/microbiologia.js"></script>
    <script src="/laboratorio/public/js/solicitudes/inmunologia.js"></script>
    <script src="/laboratorio/public/js/solicitudes/orina.js"></script>
    <script src="/laboratorio/public/js/solicitudes/otros.js"></script>
    <script src="/laboratorio/public/js/solicitudes/impresion.js"></script>

    <!-- Orquestador del Módulo Resultados -->
    <script src="/laboratorio/public/js/resultados.js"></script>
</body>
</html>