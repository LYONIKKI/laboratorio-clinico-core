<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio Clínico - Gestión de Usuarios</title>
    <link rel="stylesheet" href="/laboratorio/public/css/tailwind.min.css">
    <link rel="stylesheet" href="/laboratorio/public/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans text-gray-800 h-screen flex overflow-hidden antialiased">

    <!-- Sidebar institucional -->
    <?php include __DIR__ . '/layout/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        <?php include __DIR__ . '/layout/header.php'; ?>

        <main class="p-6 flex-1 overflow-y-auto space-y-4">
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h1 class="text-lg font-black text-gray-900 tracking-wide uppercase flex items-center">
                        <i class="fa-solid fa-users-gear text-blue-600 mr-2.5"></i> Control de Usuarios de Laboratorio
                    </h1>
                    <p class="text-xs text-gray-500 font-medium">Gestión de accesos clínicos y credenciales en PostgreSQL</p>
                </div>
                <button type="button" onclick="abrirModalCrear()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-4 py-2 rounded-xl transition shadow flex items-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Nuevo Usuario</span>
                </button>
            </div>

            <!-- FILTRO DE BÚSQUEDA REACTIVO -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-mono">
                <div class="relative w-full sm:max-w-md flex items-center">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 text-gray-400"></i>
                    <input type="text" id="busquedaUsuarios" placeholder="Buscar por nombre, usuario (login) o rol..." 
                           class="w-full bg-gray-50 border border-gray-300 rounded-xl pl-9 pr-3 py-2 font-mono text-xs outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                </div>
                <div class="flex items-center space-x-2 w-full sm:w-auto justify-end">
                    <button type="button" onclick="document.getElementById('busquedaUsuarios').value = ''; filtrarTablaUsuarios('');" 
                            class="px-4 py-2 font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl border border-gray-300 transition flex items-center justify-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        <span>Limpiar</span>
                    </button>
                    <span id="contador-usuarios" class="text-xs font-black bg-blue-50 text-blue-700 px-3 py-2 rounded-xl border border-blue-200 shadow-xs">
                        0 Usuarios
                    </span>
                </div>
            </div>

            <!-- TABLA DE USUARIOS CON PAGINACIÓN -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs font-mono">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 border-b border-gray-200 font-bold uppercase tracking-wider text-[11px]">
                                <th class="p-3 text-center w-16">ID</th>
                                <th class="p-3">Nombre Completo</th>
                                <th class="p-3">Usuario (Login)</th>
                                <th class="p-3 text-center">Rol Asignado</th>
                                <th class="p-3 text-center">Estado</th>
                                <th class="p-3 text-center w-28">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyUsuarios" class="divide-y divide-gray-200">
                            <tr><td colspan="6" class="p-6 text-center text-gray-400">Cargando usuarios...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- CONTENEDOR DE PAGINACIÓN -->
                <div id="contenedorPaginacionUsuarios" class="p-3 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-mono"></div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: CREAR USUARIO (COMPATIBLE 100% TAILWIND V2.2.19)                -->
    <!-- ========================================================================= -->
    <div id="modalCrearUsuario" class="fixed inset-0 flex items-center justify-center hidden z-50 p-4" style="background-color: rgba(17, 24, 39, 0.75);">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-gray-300 flex flex-col overflow-hidden font-mono text-xs" style="max-height: 90vh;">
            
            <div class="bg-blue-700 px-5 py-3 text-white flex justify-between items-center flex-shrink-0">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-user-plus mr-2 text-sm text-yellow-300"></i>
                    <h4 class="text-xs font-bold uppercase tracking-wider">Registrar Nuevo Usuario</h4>
                </div>
                <button type="button" onclick="cerrarModalCrear()" class="text-blue-100 hover:text-white transition cursor-pointer focus:outline-none">
                    <i class="fa-solid fa-times text-base"></i>
                </button>
            </div>

            <form id="formCrearUsuario" onsubmit="ejecutarCrearUsuario(event)" class="p-5 overflow-y-auto flex-1 space-y-3" style="max-height: calc(90vh - 120px);">
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1" style="font-size: 10px;">Nombre y Apellidos</label>
                    <input type="text" id="crearNombre" required 
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3 py-1.5 text-xs text-gray-800 font-sans focus:outline-none focus:ring-1 focus:ring-blue-500 focus:bg-white" 
                        placeholder="Ej: Dr. Kiwaki Gomez Julio">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1" style="font-size: 10px;">Usuario de Acceso (Login)</label>
                    <input type="text" id="crearLogin" required 
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3 py-1.5 text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:bg-white" 
                        placeholder="Ej: jkiwaki">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1" style="font-size: 10px;">Contraseña</label>
                    <input type="password" id="crearPassword" required 
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3 py-1.5 text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:bg-white" 
                        placeholder="••••••••">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1" style="font-size: 10px;">Rol de Acceso Clínico</label>
                    <select id="crearRol" required 
                            class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3 py-1.5 text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:bg-white cursor-pointer">
                        <option value="tecnico_lab">TECNICO_LAB (Mesa analítica, digitación y muestras)</option>
                        <option value="medico_lab">MEDICO_LAB (Médicos: Solo consulta y seguimiento)</option>
                        <option value="admin_lab">ADMIN_LAB (Administrador total de laboratorio)</option>
                    </select>
                </div>
            </form>

            <div class="bg-gray-100 px-5 py-3 border-t border-gray-200 flex justify-end space-x-2 flex-shrink-0">
                <button type="button" onclick="cerrarModalCrear()" 
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-1.5 rounded-lg transition cursor-pointer focus:outline-none">
                    Cancelar
                </button>
                <button type="submit" form="formCrearUsuario" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-1.5 rounded-lg transition shadow cursor-pointer flex items-center space-x-1.5 focus:outline-none">
                    <i class="fa-solid fa-user-plus mr-1"></i>
                    <span>Crear Usuario</span>
                </button>
            </div>

        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: EDITAR USUARIO (COMPATIBLE 100% TAILWIND V2.2.19)               -->
    <!-- ========================================================================= -->
    <div id="modalEditarUsuario" class="fixed inset-0 flex items-center justify-center hidden z-50 p-4" style="background-color: rgba(17, 24, 39, 0.75);">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-gray-300 flex flex-col overflow-hidden font-mono text-xs" style="max-height: 90vh;">
            
            <div class="bg-yellow-600 px-5 py-3 text-white flex justify-between items-center flex-shrink-0">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-user-pen mr-2 text-sm text-yellow-200"></i>
                    <h4 id="tituloModalEditar" class="text-xs font-bold uppercase tracking-wider">Editar Usuario</h4>
                </div>
                <button type="button" onclick="cerrarModalEditar()" class="text-yellow-100 hover:text-white transition cursor-pointer focus:outline-none">
                    <i class="fa-solid fa-times text-base"></i>
                </button>
            </div>

            <form id="formEditarUsuario" onsubmit="ejecutarActualizarUsuario(event)" class="p-5 overflow-y-auto flex-1 space-y-3" style="max-height: calc(90vh - 120px);">
                <input type="hidden" id="editarId">
                
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1" style="font-size: 10px;">Nombre y Apellidos</label>
                    <input type="text" id="editarNombre" required 
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3 py-1.5 text-xs text-gray-800 font-sans focus:outline-none focus:ring-1 focus:ring-yellow-500 focus:bg-white">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1" style="font-size: 10px;">Usuario de Acceso (Login)</label>
                    <input type="text" id="editarLogin" required 
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3 py-1.5 text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-yellow-500 focus:bg-white">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1" style="font-size: 10px;">Nueva Contraseña</label>
                    <input type="password" id="editarPassword" 
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3 py-1.5 text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-yellow-500 focus:bg-white" 
                        placeholder="••••••••">
                    <span class="text-gray-400 block mt-1 font-sans" style="font-size: 10px;">
                        <i class="fa-solid fa-info-circle mr-1"></i>Dejar en blanco para conservar la contraseña actual.
                    </span>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1" style="font-size: 10px;">Rol de Acceso Clínico</label>
                    <select id="editarRol" required 
                            class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3 py-1.5 text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-yellow-500 focus:bg-white cursor-pointer">
                        <option value="tecnico_lab">TECNICO_LAB (Mesa analítica, digitación y muestras)</option>
                        <option value="medico_lab">MEDICO_LAB (Médicos: Solo consulta y seguimiento)</option>
                        <option value="admin_lab">ADMIN_LAB (Administrador total de laboratorio)</option>
                    </select>
                </div>
            </form>

            <div class="bg-gray-100 px-5 py-3 border-t border-gray-200 flex justify-end space-x-2 flex-shrink-0">
                <button type="button" onclick="cerrarModalEditar()" 
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-1.5 rounded-lg transition cursor-pointer focus:outline-none">
                    Cancelar
                </button>
                <button type="submit" form="formEditarUsuario" 
                        class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold px-4 py-1.5 rounded-lg transition shadow cursor-pointer flex items-center space-x-1.5 focus:outline-none">
                    <i class="fa-solid fa-check mr-1"></i>
                    <span>Guardar Cambios</span>
                </button>
            </div>

        </div>
    </div>

    <script src="/laboratorio/public/js/sweetalert2.all.min.js"></script>
    <script src="/laboratorio/public/js/usuarios.js"></script>
</body>
</html>