<header class="bg-white border-b border-slate-200 px-6 py-3 flex justify-between items-center shadow-xs flex-shrink-0">
    <div class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shadow-xs">
            <i class="fa-solid fa-hospital-user text-lg"></i>
        </div>
        <div>
            <span class="text-xs font-black tracking-wider uppercase text-slate-800 leading-tight block">
                Hospital Regional Daniel Alcides Carrión
            </span>
            <p class="text-[10px] text-slate-500 font-semibold tracking-wide">
                Unidad de Laboratorio y Microbiología Clínica
            </p>
        </div>
    </div>

    <div class="flex items-center space-x-4 text-xs">
        <div class="bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg flex items-center space-x-2 text-slate-700 font-bold">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <i class="fa-solid fa-id-badge text-blue-600"></i>
            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Personal de Turno') ?></span>
        </div>

        <div class="text-right pl-3 border-l border-slate-200">
            <span class="text-[9px] uppercase font-bold text-slate-400 block tracking-widest">Operación</span>
            <span class="font-mono font-bold text-slate-700 text-xs flex items-center justify-end">
                <i class="fa-regular fa-calendar-check text-blue-600 mr-1.5"></i> <?= date('d/m/Y') ?>
            </span>
        </div>
    </div>
</header>