<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio Clínico - Gestión de Solicitudes</title>
    
    <!-- CSS 100% Locales de Laboratorio (Offline) -->
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
            
            <!-- Barra Superior y Acciones -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h1 class="text-lg font-black text-gray-900 tracking-wide uppercase flex items-center">
                        <i class="fa-solid fa-file-medical text-red-600 mr-2.5"></i> Gestión y Control de Solicitudes
                    </h1>
                    <p class="text-xs text-gray-500 font-medium">Recepción de órdenes médicas, validación por analitos y entrega analítica</p>
                </div>
                
                <div class="flex items-center space-x-2">
                    
                    <button type="button" onclick="abrirModalNuevaSolicitud()" 
                            class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-3 py-2 rounded-xl shadow-xs transition flex items-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-plus-circle text-yellow-300"></i>
                        <span>Nueva Solicitud</span>
                    </button>
                    
                    <button type="button" onclick="actualizarSolicitudes()" 
                            class="bg-white hover:bg-gray-50 text-gray-700 text-xs font-bold px-3 py-2 rounded-xl border border-gray-300 shadow-xs transition flex items-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-arrows-rotate text-red-600"></i>
                        <span>Sincronizar</span>
                    </button>
                    <span id="contador-solicitudes" class="text-xs font-black bg-red-50 text-red-700 px-3 py-2 rounded-xl border border-red-200 shadow-xs">
                        0 Solicitudes
                    </span>
                </div>
            </div>

            <!-- Filtros y Búsqueda Rápida -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-4 grid grid-cols-1 md:grid-cols-4 gap-3 items-end text-xs font-mono">
                <div class="space-y-1 md:col-span-2">
                    <label class="block font-bold text-gray-600 uppercase tracking-wider text-[10px]">Búsqueda de Paciente / DNI / Orden</label>
                    <div class="relative flex items-center">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 text-gray-400"></i>
                        <input type="text" id="busquedaRapida" placeholder="Buscar por DNI, apellidos, número de movimiento..." 
                               class="w-full bg-gray-50 border border-gray-300 rounded-xl pl-9 pr-3 py-2 font-mono text-xs outline-none focus:ring-2 focus:ring-red-500 focus:bg-white transition">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block font-bold text-gray-600 uppercase tracking-wider text-[10px]">Rango de Auditoría</label>
                    <select id="filtroTiempo" 
                            class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-xs font-bold text-gray-700 outline-none focus:ring-2 focus:ring-red-500 focus:bg-white transition cursor-pointer">
                        <option value="3" selected>Últimos 3 Días</option>
                        <option value="5">Últimos 5 Días</option>
                        <option value="15_PENDIENTES">15 Días (Solo Pendientes)</option>
                    </select>
                </div>

                <div>
                    <button type="button" onclick="document.getElementById('busquedaRapida').value = ''; actualizarSolicitudes();" 
                            class="w-full px-4 py-2 font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl border border-gray-300 transition flex items-center justify-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        <span>Limpiar Filtro</span>
                    </button>
                </div>
            </div>

            <!-- Grilla Principal de Órdenes -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                <div class="p-3.5 bg-gray-50/70 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-list-check text-red-600 mr-2"></i> Registro de Órdenes Clínicas SIGH
                    </h3>
                    <span class="text-[10px] font-mono text-gray-400">Orden cronológico descendente</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100/70 text-gray-600 border-b border-gray-200 font-bold uppercase tracking-wider text-[10px] font-mono">
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
                        <tbody id="tbodySolicitudes" class="divide-y divide-gray-200">
                            <tr>
                                <td colspan="8" class="p-8 text-center text-gray-400 font-mono text-xs">
                                    <i class="fa-solid fa-spinner fa-spin mr-2"></i> Sincronizando órdenes con base de datos SQL Server...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: DETALLE DE EXÁMENES DE LA ORDEN (SCROLL FIJO GARANTIZADO) -->
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

    <!-- MODAL 2: DIGITACIÓN MANUAL DINÁMICA CON SCROLL INTERNO FIJO -->
    <div id="modalDigitacion" class="fixed inset-0 bg-gray-900 bg-opacity-60 flex items-center justify-center hidden z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full border border-gray-200 flex flex-col max-h-[85vh] overflow-hidden">
            
            <!-- Cabecera Fija -->
            <div class="bg-blue-700 px-5 py-3 text-white flex justify-between items-center flex-shrink-0">
                <h4 id="modalDigitacionLabel" class="text-xs font-black uppercase tracking-wider flex items-center font-mono">
                    <i class="fa-solid fa-pen-to-square mr-2"></i> Digitación de Resultados
                </h4>
                <button type="button" onclick="cerrarModalDigitacion()" class="text-blue-200 hover:text-white transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Cuerpo con Scroll Forzado -->
            <div class="p-4 overflow-y-auto flex-1 space-y-3" id="modalDigitacionBody" style="max-height: calc(85vh - 110px);">
                <!-- Inyección dinámica de plantillas -->
            </div>

            <!-- Pie de Modal Fijo (Siempre Visible) -->
            <div class="bg-gray-50 px-5 py-3 border-t border-gray-200 flex justify-between items-center text-xs flex-shrink-0">
                <button type="button" onclick="cerrarModalDigitacion()" 
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-2 rounded-xl transition cursor-pointer">
                    Cerrar
                </button>
                <div class="flex items-center space-x-2">
                    <!-- BOTÓN IMPRIMIR RESULTADOS RESTAURADO -->
                    <button type="button" id="btnImprimirModalManual" onclick="imprimirDesdeModalDigitacion()" 
                            class="bg-red-600 hover:bg-red-700 text-white font-bold px-4 py-2 rounded-xl transition shadow flex items-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-print"></i>
                        <span>Imprimir</span>
                    </button>

                    <button type="button" id="btnGuardarResultadosManual" onclick="procesarGuardadoResultados()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-xl transition shadow flex items-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Guardar Resultados</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 3: REGISTRO DE NUEVA SOLICITUD CLÍNICA (CON AUTOCOMPLETADOS CLÍNICOS) -->
    <!-- ========================================================================= -->
    <div id="modalNuevaSolicitud" class="fixed inset-0 flex items-center justify-center hidden z-50 p-4" style="background-color: rgba(17, 24, 39, 0.7);">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-300 flex flex-col font-mono text-xs" style="max-height: 90vh;">
            
            <!-- Cabecera Verde Institucional -->
            <div class="bg-green-700 px-5 py-3 text-white flex justify-between items-center shadow flex-shrink-0">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-file-circle-plus text-yellow-300 mr-2 text-sm"></i>
                    <h3 class="text-xs font-bold uppercase tracking-wider">Generar Nueva Solicitud de Laboratorio</h3>
                </div>
                <button type="button" onclick="cerrarModalNuevaSolicitud()" class="text-green-200 hover:text-white transition cursor-pointer focus:outline-none">
                    <i class="fa-solid fa-times text-lg"></i>
                </button>
            </div>

            <!-- Cuerpo del Formulario con Scroll -->
            <div class="p-5 overflow-y-auto flex-1 space-y-4" style="max-height: calc(90vh - 120px);">
                
                <!-- Paso 1: Localizar Cuenta o Paciente -->
                <div class="bg-gray-50 p-3.5 rounded-lg border border-gray-200 space-y-2">
                    <label class="block font-bold text-gray-700 uppercase" style="font-size: 10.5px;">
                        <i class="fa-solid fa-search text-green-600 mr-1"></i> Paso 1: Buscar Cuenta de Atención
                    </label>
                    <div class="flex space-x-2">
                        <input type="text" id="inputBuscarCuentaNueva" placeholder="Ingrese N° Cuenta, Atención, DNI o Apellidos..." 
                               onkeydown="if(event.key === 'Enter') buscarCuentaNuevaSolicitud()"
                               class="w-full bg-white border border-gray-300 rounded px-3 py-1.5 font-bold text-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500">
                        <button type="button" onclick="buscarCuentaNuevaSolicitud()" 
                                class="bg-green-600 hover:bg-green-700 text-white font-bold px-4 py-1.5 rounded transition cursor-pointer flex items-center">
                            <span>Buscar</span>
                        </button>
                    </div>

                    <!-- Lista de Coincidencias si hay varias atenciones -->
                    <div id="contenedorSugerenciasCuentas" class="hidden bg-white border border-gray-300 rounded max-h-40 overflow-y-auto"></div>

                    <!-- Ficha del Paciente Localizado -->
                    <div id="fichaPacienteNueva" class="hidden mt-2 p-3 bg-white border border-green-200 rounded text-xs space-y-1.5">
                        <input type="hidden" id="nuevoIdCuentaAtencion">
                        <div class="flex justify-between font-bold">
                            <span id="nuevoNombrePaciente" class="text-gray-900 uppercase">---</span>
                            <span id="nuevoDniPaciente" class="text-green-700">DNI: ---</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-gray-600 pt-1 border-t border-gray-100">
                            <div><span class="text-gray-400">Cuenta:</span> <b id="nuevoNroCuenta" class="text-gray-800">---</b></div>
                            <div><span class="text-gray-400">Seguro:</span> <b id="nuevoSeguro" class="text-gray-800">---</b></div>
                        </div>
                    </div>
                </div>

                <!-- Paso 2: Datos Clínicos de la Orden (Conectados a ClinicaController) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="relative">
                        <label class="block font-bold text-gray-600 uppercase" style="font-size: 10px;">Servicio de Procedencia:</label>
                        <input type="text" id="nuevoServicioInput" autocomplete="off"
                               class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-gray-800 font-bold focus:outline-none focus:ring-1 focus:ring-green-500" 
                               placeholder="Ej: CONSULTA EXTERNA">
                        <div id="sugerenciasServNuevo" class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-300 rounded shadow-lg hidden z-50 max-h-40 overflow-y-auto font-mono text-xs"></div>
                    </div>
                    <div class="relative">
                        <label class="block font-bold text-gray-600 uppercase" style="font-size: 10px;">Médico Prescriptor:</label>
                        <input type="text" id="nuevoMedicoInput" autocomplete="off"
                               class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-gray-800 font-bold focus:outline-none focus:ring-1 focus:ring-green-500" 
                               placeholder="Ej: DR. PEREZ">
                        <div id="sugerenciasMedNuevo" class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-300 rounded shadow-lg hidden z-50 max-h-40 overflow-y-auto font-mono text-xs"></div>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-600 uppercase" style="font-size: 10px;">Cama / Ubicación:</label>
                        <input type="text" id="nuevaCamaInput" 
                               class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-gray-800 font-bold focus:outline-none focus:ring-1 focus:ring-green-500" 
                               placeholder="Ej: Cama 04 / Observación">
                    </div>
                    <div class="relative">
                        <label class="block font-bold text-gray-600 uppercase" style="font-size: 10px;">Diagnóstico Clínico (CIE-10):</label>
                        <input type="text" id="nuevoDiagnosticoInput" autocomplete="off"
                               class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-gray-800 font-bold focus:outline-none focus:ring-1 focus:ring-green-500" 
                               placeholder="Ej: Infección urinaria">
                        <div id="sugerenciasDiagNuevo" class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-300 rounded shadow-lg hidden z-50 max-h-40 overflow-y-auto font-mono text-xs"></div>
                    </div>
                </div>

                <!-- Paso 3: Selección de Exámenes CPT -->
                <div class="space-y-2">
                    <label class="block font-bold text-gray-700 uppercase" style="font-size: 10.5px;">
                        <i class="fa-solid fa-vials text-green-600 mr-1"></i> Paso 2: Agregar Exámenes CPT de Laboratorio
                    </label>
                    <div class="relative">
                        <input type="text" id="inputBuscarExamenCpt" placeholder="Escriba el nombre del examen (Ej: Hemograma, Orina, Glucosa)..." 
                               class="w-full bg-white border border-gray-300 rounded px-3 py-1.5 font-bold text-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500"
                               autocomplete="off">
                        <div id="sugerenciasExamenesCpt" class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-300 rounded shadow-lg hidden z-50 max-h-44 overflow-y-auto font-mono text-xs"></div>
                    </div>

                    <!-- Canasta de Exámenes Seleccionados -->
                    <div class="border border-gray-200 rounded-lg p-2.5 bg-gray-50 min-h-[70px] space-y-1.5" id="listaExamenesSeleccionados">
                        <p class="text-gray-400 text-center py-2" id="msgSinExamenes">No ha seleccionado ningún análisis.</p>
                    </div>
                </div>

            </div>

            <!-- Pie de Modal Fijo -->
            <div class="bg-gray-50 px-5 py-3 border-t border-gray-200 flex justify-between items-center flex-shrink-0">
                <button type="button" onclick="cerrarModalNuevaSolicitud()" 
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-1.5 rounded-lg transition cursor-pointer">
                    Cancelar
                </button>
                <button type="button" id="btnGuardarNuevaOrden" onclick="procesarNuevaOrdenWeb()" 
                        class="bg-green-600 hover:bg-green-700 text-white font-bold px-4 py-1.5 rounded-lg transition shadow flex items-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>Crear Orden Médica</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts Locales Offline (100% JavaScript Vanilla, Sin jQuery) -->
    <script src="/laboratorio/public/js/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    <!-- Módulos Clínicos Desacoplados -->
    <script src="/laboratorio/public/js/solicitudes/hematologia.js"></script>
    <script src="/laboratorio/public/js/solicitudes/bioquimica.js"></script>
    <script src="/laboratorio/public/js/solicitudes/microbiologia.js"></script>
    <script src="/laboratorio/public/js/solicitudes/otros.js"></script>
    <script src="/laboratorio/public/js/solicitudes/orina.js"></script>
    <script src="/laboratorio/public/js/solicitudes/inmunologia.js"></script>
    <script src="/laboratorio/public/js/solicitudes/impresion.js"></script>
    
    <!-- Módulo de Nueva Solicitud (Desacoplado) -->
    <script src="/laboratorio/public/js/solicitudes/nueva_solicitud.js"></script>

    <!-- Orquestador Principal de la Vista -->
    <script src="/laboratorio/public/js/solicitudes.js"></script>
</body>
</html>