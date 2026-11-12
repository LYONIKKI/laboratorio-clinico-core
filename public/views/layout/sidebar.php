<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$rol_actual = strtolower(trim($_SESSION['user_rol'] ?? 'tecnico_lab'));
$es_admin   = in_array($rol_actual, ['admin_lab', 'admin', 'administrador']);
$es_medico  = ($rol_actual === 'medico_lab');

$uri_actual = $_SERVER['REQUEST_URI'] ?? '';

function claseItemSidebar($ruta, $uri) {
    $activo = (strpos($uri, '/' . $ruta) !== false);
    if ($activo) {
        return 'bg-neutral-800 text-white font-bold border-l-2 border-red-500 shadow-xs';
    }
    return 'text-gray-400 hover:bg-neutral-900 hover:text-gray-200 border-l-2 border-transparent';
}

function claseIconoSidebar($ruta, $uri, $colorActivo = 'text-red-400') {
    $activo = (strpos($uri, '/' . $ruta) !== false);
    return $activo ? $colorActivo : 'text-gray-500 group-hover:text-gray-300';
}
?>

<!-- CSS Desacoplado del Sidebar -->
<link rel="stylesheet" href="/laboratorio/public/css/sidebar.css">

<aside class="w-64 sidebar-bg-black text-gray-300 flex flex-col min-h-screen border-r border-neutral-800 select-none flex-shrink-0" style="min-width: 16rem; max-width: 16rem;">
    
    <!-- HEADER -->
    <div class="p-4 bg-black flex items-center border-b border-neutral-800">
        <div class="badge-plata-brillante p-2 rounded-lg mr-3 flex-shrink-0 flex items-center justify-center">
            <i class="fa-solid fa-microscope text-lg text-slate-900"></i>
        </div>
        <div class="min-w-0">
            <h2 class="text-white font-bold text-sm tracking-wide leading-tight truncate">LABORATORIO CLÍNICO</h2>
            <p class="text-[10px] text-slate-400 font-semibold tracking-wider uppercase">HRDAC - PASCO</p>
        </div>
    </div>

    <!-- NAVEGACIÓN SEGÚN ROL -->
    <nav class="flex-1 p-3 space-y-2 overflow-y-auto font-mono text-xs">
        
        <?php if (!$es_medico): ?>
        <details id="acc-lab-resumen" class="sidebar-accordion group/acc [&_summary::-webkit-details-marker]:hidden" open>
            <summary class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider cursor-pointer hover:text-slate-200 rounded-lg hover:bg-neutral-900 transition-colors">
                <span>General y Métricas</span>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200 group-open/acc:rotate-180 text-gray-500"></i>
            </summary>
            <div class="mt-1 pl-2 space-y-1">
                <a href="/laboratorio/public/panel" class="flex items-center px-3 py-2 rounded-r-lg text-xs transition duration-150 group <?= claseItemSidebar('panel', $uri_actual); ?>">
                    <span class="w-6 text-center flex-shrink-0"><i class="fa-solid fa-chart-pie <?= claseIconoSidebar('panel', $uri_actual, 'text-red-400'); ?>"></i></span>
                    <span class="truncate">Estadísticas</span>
                </a>
            </div>
        </details>
        <?php endif; ?>

        <details id="acc-lab-analisis" class="sidebar-accordion group/acc [&_summary::-webkit-details-marker]:hidden" open>
            <summary class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider cursor-pointer hover:text-slate-200 rounded-lg hover:bg-neutral-900 transition-colors">
                <span>Gestión de Análisis</span>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200 group-open/acc:rotate-180 text-gray-500"></i>
            </summary>
            <div class="mt-1 pl-2 space-y-1">
                <?php if (!$es_medico): ?>
                <a href="/laboratorio/public/solicitudes" class="flex items-center px-3 py-2 rounded-r-lg text-xs transition duration-150 group <?= claseItemSidebar('solicitudes', $uri_actual); ?>">
                    <span class="w-6 text-center flex-shrink-0"><i class="fa-solid fa-file-waveform <?= claseIconoSidebar('solicitudes', $uri_actual, 'text-red-400'); ?>"></i></span>
                    <span class="truncate">Solicitudes</span>
                </a>
                <?php endif; ?>

                <a href="/laboratorio/public/muestras" class="flex items-center px-3 py-2 rounded-r-lg text-xs transition duration-150 group <?= claseItemSidebar('muestras', $uri_actual); ?>">
                    <span class="w-6 text-center flex-shrink-0"><i class="fa-solid fa-vial <?= claseIconoSidebar('muestras', $uri_actual, 'text-green-400'); ?>"></i></span>
                    <span class="truncate">Seguimiento Muestras</span>
                </a>

                <?php if (!$es_medico): ?>
                <a href="/laboratorio/public/resultados" class="flex items-center px-3 py-2 rounded-r-lg text-xs transition duration-150 group <?= claseItemSidebar('resultados', $uri_actual); ?>">
                    <span class="w-6 text-center flex-shrink-0"><i class="fa-solid fa-vials <?= claseIconoSidebar('resultados', $uri_actual, 'text-blue-400'); ?>"></i></span>
                    <span class="truncate">Resultados</span>
                </a>
                <?php endif; ?>
            </div>
        </details>

        <?php if ($es_admin): ?>
        <details id="acc-lab-admin" class="sidebar-accordion group/acc [&_summary::-webkit-details-marker]:hidden" open>
            <summary class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-amber-500 uppercase tracking-wider cursor-pointer hover:text-amber-300 rounded-lg hover:bg-neutral-900 transition-colors">
                <span>Administración</span>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200 group-open/acc:rotate-180 text-gray-500"></i>
            </summary>
            <div class="mt-1 pl-2 space-y-1">
                <a href="/laboratorio/public/usuarios" class="flex items-center px-3 py-2 rounded-r-lg text-xs transition duration-150 group <?= claseItemSidebar('usuarios', $uri_actual); ?>">
                    <span class="w-6 text-center flex-shrink-0"><i class="fa-solid fa-users-gear <?= claseIconoSidebar('usuarios', $uri_actual, 'text-amber-400'); ?>"></i></span>
                    <span class="truncate text-amber-200">Gestión de Usuarios</span>
                </a>
            </div>
        </details>
        <?php endif; ?>

    </nav>

    <!-- FOOTER PERFIL -->
    <div class="p-3 bg-black border-t border-neutral-800 flex items-center justify-between">
        <div class="flex items-center min-w-0 flex-1 mr-1">
            <div class="avatar-plata-brillante w-8 h-8 rounded-full flex items-center justify-center text-[11px] uppercase flex-shrink-0 mr-2.5">
                <?= isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 2)) : 'US'; ?>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-white truncate leading-none mb-1">
                    <?= $_SESSION['user_name'] ?? 'Usuario Sistema'; ?>
                </p>
                <p class="text-[9px] text-slate-400 truncate font-semibold uppercase tracking-wider">
                    <?= $_SESSION['user_rol'] ?? 'TECNICO_LAB'; ?>
                </p>
            </div>
        </div>
        
        <div class="flex items-center space-x-1 flex-shrink-0">
            <button type="button" onclick="abrirModalCambioPassword()" 
                    class="text-gray-400 hover:text-amber-400 p-1.5 rounded-lg hover:bg-neutral-900 transition cursor-pointer focus:outline-none" 
                    title="Cambiar Mi Contraseña">
                <i class="fa-solid fa-key text-xs"></i>
            </button>

            <a href="../../logout.php" 
               class="text-gray-400 hover:text-red-400 p-1.5 rounded-lg hover:bg-neutral-900 transition" 
               title="Cerrar Sesión Central">
                <i class="fa-solid fa-power-off text-xs"></i>
            </a>
        </div>
    </div>
</aside>

<!-- JS Desacoplado del Sidebar -->
<script src="/laboratorio/public/js/sidebar.js?v=3"></script>