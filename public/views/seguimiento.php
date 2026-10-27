<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio Clínico - Consulta Médica de Resultados</title>
    
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
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                <div>
                    <h1 class="text-lg font-bold text-gray-900 tracking-wide uppercase flex items-center">
                        <i class="fa-solid fa-stethoscope text-green-600 mr-2"></i> Portal Médico - Seguimiento de Muestras y Resultados
                    </h1>
                    <p class="text-xs text-gray-500 font-medium">Consulta clínica de solo lectura para médicos prescriptores y servicios hospitalarios</p>
                </div>
            </div>

            <!-- Buscador Clínico Hero -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-center space-y-4">
                <div class="inline-flex p-3 bg-green-50 text-green-600 rounded-xl mb-1">
                    <i class="fa-solid fa-notes-medical text-3xl"></i>
                </div>
                <h2 class="text-base font-bold text-gray-800 uppercase tracking-tight">Consulta Médica de Análisis Clínicos</h2>
                <p class="text-xs text-gray-500 max-w-xl mx-auto font-mono">
                    Ingrese el N° de Cuenta, Atención médica, DNI o apellidos del paciente para visualizar los resultados analíticos validados e imprimir el informe oficial A4.
                </p>

                <!-- Input Group Centrado -->
                <div class="max-w-xl mx-auto flex items-center shadow-sm rounded-lg overflow-hidden border border-gray-300">
                    <input type="text" id="inputBuscarMedico" placeholder="Ingrese N° Cuenta, Atención, DNI o Apellidos..." 
                           class="w-full bg-white px-4 py-2 font-mono text-xs text-gray-800 focus:outline-none">
                    <button type="button" onclick="ejecutarBusquedaMedico()" 
                            class="bg-green-600 hover:bg-green-700 text-white font-bold text-xs px-5 py-2.5 transition flex items-center cursor-pointer">
                        <i class="fa-solid fa-magnifying-glass text-yellow-300 mr-1.5"></i>
                        <span>Consultar</span>
                    </button>
                </div>
            </div>

            <!-- Grilla de Órdenes Localizadas -->
            <div id="contenedorResultadosMedico" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden hidden">
                <div class="p-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-list-check text-green-600 mr-2"></i> Órdenes Clínicas Encontradas
                    </h3>
                    <span id="contador-resultados-medico" class="text-xs font-mono font-bold bg-green-100 text-green-800 px-2 py-0.5 rounded">0 Órdenes</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 border-b border-gray-200 font-bold uppercase tracking-wider text-xs font-mono">
                                <th class="p-3 text-center w-28">N° Cuenta</th>
                                <th class="p-3 w-32">Fecha/Hora</th>
                                <th class="p-3 w-40">Identificación</th>
                                <th class="p-3">Paciente</th>
                                <th class="p-3 w-44">Servicio Procedencia</th>
                                <th class="p-3 text-center w-28">Estado</th>
                                <th class="p-3 text-center w-28">Avance</th>
                                <th class="p-3 text-center w-28">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyResultadosMedico" class="divide-y divide-gray-200">
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- MODAL CLÍNICO DE SOLO LECTURA (PARA MÉDICOS) - TAILWIND V2.2.19 COMPLIANT -->
    <div id="modalLecturaMedico" class="fixed inset-0 flex items-center justify-center hidden z-50 p-4" style="background-color: rgba(17, 24, 39, 0.7);">
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full border border-gray-300 flex flex-col overflow-hidden font-mono text-xs" style="max-height: 90vh;">
            
            <!-- Cabecera Institucional Sólida -->
            <div class="bg-green-700 px-5 py-3 text-white flex justify-between items-center flex-shrink-0">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-notes-medical text-yellow-300 mr-2 text-sm"></i>
                    <h4 class="text-xs font-bold uppercase tracking-wider">
                        Informe Analítico de Laboratorio (Solo Lectura)
                    </h4>
                </div>
                <button type="button" onclick="cerrarModalLectura()" class="text-green-200 hover:text-white transition cursor-pointer focus:outline-none">
                    <i class="fa-solid fa-times text-lg"></i>
                </button>
            </div>

            <!-- Ficha Clínica Fija del Paciente -->
            <div class="bg-gray-50 p-4 border-b border-gray-200 flex-shrink-0 space-y-2">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block" style="font-size: 10px;">Paciente</span>
                        <h4 id="medPaciente" class="text-sm font-bold text-gray-800 uppercase leading-snug">---</h4>
                    </div>
                    <div class="text-right">
                        <span id="medDni" class="bg-green-100 text-green-800 font-bold px-2 py-0.5 rounded inline-block text-xs">DNI: ---</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2 border-t border-gray-200 text-xs">
                    <div>
                        <span class="text-gray-400 block uppercase font-bold" style="font-size: 10px;">N° Cuenta / Mov:</span>
                        <span id="medCuenta" class="font-bold text-gray-700">---</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block uppercase font-bold" style="font-size: 10px;">Seguro:</span>
                        <span id="medSeguro" class="font-bold text-gray-700">---</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block uppercase font-bold" style="font-size: 10px;">Servicio / Cama:</span>
                        <span id="medServicio" class="font-bold text-gray-700">---</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block uppercase font-bold" style="font-size: 10px;">Médico Prescriptor:</span>
                        <span id="medMedico" class="font-bold text-gray-700">---</span>
                    </div>
                </div>
            </div>

            <!-- Cuerpo Scrolleable de Resultados -->
            <div class="p-4 overflow-y-auto flex-1 space-y-4" id="medCuerpoAnalitos" style="max-height: calc(90vh - 190px);">
                <!-- Inyección dinámica de tablas -->
            </div>

            <!-- Pie de Modal Fijo -->
            <div class="bg-gray-50 px-5 py-3 border-t border-gray-200 flex justify-between items-center flex-shrink-0">
                <span class="text-gray-400 italic text-xs">
                    <i class="fa-solid fa-lock text-gray-400 mr-1"></i> Modo Solo Lectura: Información validada por el Servicio de Laboratorio Clínico.
                </span>
                <button type="button" onclick="cerrarModalLectura()" 
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-1.5 rounded-lg transition cursor-pointer focus:outline-none">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts Locales Offline -->
    <script src="/laboratorio/public/js/sweetalert2.all.min.js"></script>
    <script src="/laboratorio/public/js/solicitudes/impresion.js"></script>
    
    <!-- Orquestador del Portal Médico -->
    <script src="/laboratorio/public/js/seguimiento.js"></script>
</body>
</html>