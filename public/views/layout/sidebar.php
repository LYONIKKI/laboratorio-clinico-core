<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$rol_actual = strtolower(trim($_SESSION['user_rol'] ?? 'tecnico_lab'));
$es_admin   = in_array($rol_actual, ['admin_lab', 'admin', 'administrador']);
$es_medico  = ($rol_actual === 'medico_lab');
?>
<style>
    .sidebar-bg-black { background-color: #000000 !important; }
    .badge-plata-brillante {
        background: linear-gradient(135deg, #718096 0%, #cbd5e1 35%, #ffffff 50%, #94a3b8 65%, #475569 100%) !important;
        border: 1px solid rgba(255, 255, 255, 0.8) !important;
        box-shadow: 0 0 10px rgba(203, 213, 225, 0.4) !important;
    }
    .avatar-plata-brillante {
        background: linear-gradient(135deg, #718096 0%, #cbd5e1 35%, #ffffff 50%, #94a3b8 65%, #475569 100%) !important;
        color: #0f172a !important;
        font-weight: 800 !important;
        border: 1px solid rgba(255, 255, 255, 0.9) !important;
    }
</style>

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
        <!-- SECCIÓN 1: PANEL Y ESTADÍSTICAS (NO MÉDICOS) -->
        <details id="acc-lab-resumen" class="sidebar-accordion group/acc [&_summary::-webkit-details-marker]:hidden" <?= isLabGroupOpen(['panel']); ?>>
            <summary class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider cursor-pointer hover:text-slate-200 rounded-lg hover:bg-neutral-900 transition-colors">
                <span>General y Métricas</span>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200 group-open/acc:rotate-180 text-gray-500"></i>
            </summary>
            <div class="mt-1 pl-2 space-y-1">
                <a href="/laboratorio/public/panel" class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition duration-150 group <?= setLabActive('panel'); ?>">
                    <span class="w-6 text-center flex-shrink-0"><i class="fa-solid fa-chart-pie <?= setLabIcon('panel'); ?>"></i></span>
                    <span class="truncate">Estadísticas</span>
                </a>
            </div>
        </details>
        <?php endif; ?>

        <!-- SECCIÓN 2: GESTIÓN DE ANÁLISIS -->
        <details id="acc-lab-analisis" class="sidebar-accordion group/acc [&_summary::-webkit-details-marker]:hidden" open>
            <summary class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider cursor-pointer hover:text-slate-200 rounded-lg hover:bg-neutral-900 transition-colors">
                <span>Gestión de Análisis</span>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200 group-open/acc:rotate-180 text-gray-500"></i>
            </summary>
            <div class="mt-1 pl-2 space-y-1">
                <?php if (!$es_medico): ?>
                <a href="/laboratorio/public/solicitudes" class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition duration-150 group <?= setLabActive('solicitudes'); ?>">
                    <span class="w-6 text-center flex-shrink-0"><i class="fa-solid fa-file-waveform <?= setLabIcon('solicitudes'); ?>"></i></span>
                    <span class="truncate">Solicitudes</span>
                </a>
                <?php endif; ?>

                <!-- PERMITIDO PARA MÉDICOS Y TÉCNICOS -->
                <a href="/laboratorio/public/muestras" class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition duration-150 group <?= setLabActive('muestras'); ?>">
                    <span class="w-6 text-center flex-shrink-0"><i class="fa-solid fa-vial <?= setLabIcon('muestras'); ?>"></i></span>
                    <span class="truncate">Seguimiento Muestras</span>
                </a>

                <?php if (!$es_medico): ?>
                <a href="/laboratorio/public/resultados" class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition duration-150 group <?= setLabActive('resultados'); ?>">
                    <span class="w-6 text-center flex-shrink-0"><i class="fa-solid fa-vials <?= setLabIcon('resultados'); ?>"></i></span>
                    <span class="truncate">Resultados</span>
                </a>
                <?php endif; ?>
            </div>
        </details>

        <!-- SECCIÓN 3: ADMINISTRACIÓN DEL SISTEMA (SOLO ADMIN_LAB / ADMIN) -->
        <?php if ($es_admin): ?>
        <details id="acc-lab-admin" class="sidebar-accordion group/acc [&_summary::-webkit-details-marker]:hidden" <?= isLabGroupOpen(['usuarios']); ?>>
            <summary class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-amber-500 uppercase tracking-wider cursor-pointer hover:text-amber-300 rounded-lg hover:bg-neutral-900 transition-colors">
                <span>Administración</span>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200 group-open/acc:rotate-180 text-gray-500"></i>
            </summary>
            <div class="mt-1 pl-2 space-y-1">
                <a href="/laboratorio/public/usuarios" class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition duration-150 group <?= setLabActive('usuarios'); ?>">
                    <span class="w-6 text-center flex-shrink-0"><i class="fa-solid fa-users-gear text-amber-400"></i></span>
                    <span class="truncate text-amber-200 font-bold">Gestión de Usuarios</span>
                </a>
            </div>
        </details>
        <?php endif; ?>

    </nav>

    <!-- FOOTER PERFIL -->
    <div class="p-3 bg-black border-t border-neutral-800 flex items-center justify-between">
        <div class="flex items-center min-w-0 flex-1 mr-2">
            <div class="avatar-plata-brillante w-8 h-8 rounded-full flex items-center justify-center text-[11px] uppercase flex-shrink-0 mr-2.5">
                <?= isset($_SESSION['user_name']) ? substr($_SESSION['user_name'], 0, 2) : 'TE'; ?>
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
        <a href="../../logout.php" class="text-gray-500 hover:text-slate-200 p-1.5 transition flex-shrink-0" title="Cerrar Sesión Central">
            <i class="fa-solid fa-power-off text-sm"></i>
        </a>
    </div>
</aside>