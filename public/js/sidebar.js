// public/js/sidebar.js
document.addEventListener('DOMContentLoaded', () => {
    const accordions = document.querySelectorAll('.sidebar-accordion');

    accordions.forEach(acc => {
        const id = acc.id;
        if (!id) return;

        // Mantener abierto por defecto; solo contrae si el usuario explícitamente lo cerró antes
        const savedState = localStorage.getItem('sidebar_lab_' + id);
        if (savedState === 'closed') {
            acc.open = false;
        } else {
            acc.open = true;
        }

        acc.addEventListener('toggle', () => {
            localStorage.setItem('sidebar_lab_' + id, acc.open ? 'open' : 'closed');
        });
    });

    // Inyectar el modal institucional de cambio de contraseña si aún no existe en el DOM
    inyectarModalCambioPassword();
});

function inyectarModalCambioPassword() {
    if (document.getElementById('modalCambioPasswordGlobal')) return;

    const modalHTML = `
        <div id="modalCambioPasswordGlobal" class="fixed inset-0 flex items-center justify-center hidden z-50 p-4" style="background-color: rgba(17, 24, 39, 0.7);">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-300 flex flex-col font-mono text-xs">
                
                <!-- Cabecera Azul Institucional -->
                <div class="bg-blue-700 px-5 py-3 text-white flex justify-between items-center shadow flex-shrink-0">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-key text-yellow-300 mr-1.5 text-sm"></i>
                        <h3 class="text-xs font-bold uppercase tracking-wider">Cambiar Mi Contraseña</h3>
                    </div>
                    <button type="button" onclick="cerrarModalCambioPassword()" class="text-blue-200 hover:text-white transition cursor-pointer focus:outline-none">
                        <i class="fa-solid fa-times text-lg"></i>
                    </button>
                </div>

                <!-- Cuerpo del Formulario -->
                <div class="p-5 space-y-4">
                    <div class="bg-blue-50/70 border border-blue-200 rounded-lg p-3 text-blue-900 text-[11px] flex items-start space-x-2">
                        <i class="fa-solid fa-circle-info text-blue-600 mt-0.5"></i>
                        <span>Puedes usar el ícono del ojo para visualizar tu nueva clave antes de guardarla.</span>
                    </div>

                    <!-- Contraseña Actual -->
                    <div>
                        <label class="block font-bold text-gray-600 uppercase mb-1" style="font-size: 10px;">Contraseña Actual:</label>
                        <div class="relative flex items-center">
                            <input type="password" id="cp_pass_actual" autocomplete="off"
                                   class="w-full bg-white border border-gray-300 rounded-lg pl-3 pr-10 py-2 text-gray-800 font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 transition" 
                                   placeholder="Ingrese su contraseña actual">
                            <button type="button" onclick="toggleVisibilidadPass('cp_pass_actual', 'icon_cp_actual')" tabindex="-1"
                                    class="absolute right-3 text-gray-400 hover:text-blue-600 transition cursor-pointer focus:outline-none">
                                <i class="fa-solid fa-eye text-xs" id="icon_cp_actual"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Nueva Contraseña -->
                    <div>
                        <label class="block font-bold text-gray-600 uppercase mb-1" style="font-size: 10px;">Nueva Contraseña:</label>
                        <div class="relative flex items-center">
                            <input type="password" id="cp_pass_nueva" autocomplete="off"
                                   class="w-full bg-white border border-gray-300 rounded-lg pl-3 pr-10 py-2 text-gray-800 font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 transition" 
                                   placeholder="Mínimo 4 caracteres">
                            <button type="button" onclick="toggleVisibilidadPass('cp_pass_nueva', 'icon_cp_nueva')" tabindex="-1"
                                    class="absolute right-3 text-gray-400 hover:text-blue-600 transition cursor-pointer focus:outline-none">
                                <i class="fa-solid fa-eye text-xs" id="icon_cp_nueva"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Confirmar Nueva Contraseña -->
                    <div>
                        <label class="block font-bold text-gray-600 uppercase mb-1" style="font-size: 10px;">Confirmar Nueva Contraseña:</label>
                        <div class="relative flex items-center">
                            <input type="password" id="cp_pass_repetir" autocomplete="off"
                                   onkeydown="if(event.key === 'Enter') procesarCambioPasswordGlobal()"
                                   class="w-full bg-white border border-gray-300 rounded-lg pl-3 pr-10 py-2 text-gray-800 font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 transition" 
                                   placeholder="Repita la nueva contraseña">
                            <button type="button" onclick="toggleVisibilidadPass('cp_pass_repetir', 'icon_cp_repetir')" tabindex="-1"
                                    class="absolute right-3 text-gray-400 hover:text-blue-600 transition cursor-pointer focus:outline-none">
                                <i class="fa-solid fa-eye text-xs" id="icon_cp_repetir"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pie de Modal Fijo -->
                <div class="bg-gray-50 px-5 py-3 border-t border-gray-200 flex justify-between items-center flex-shrink-0">
                    <button type="button" onclick="cerrarModalCambioPassword()" 
                            class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-1.5 rounded-lg transition cursor-pointer">
                        Cancelar
                    </button>
                    <button type="button" id="btnGuardarPasswordGlobal" onclick="procesarCambioPasswordGlobal()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-1.5 rounded-lg transition shadow flex items-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-check-circle"></i>
                        <span>Guardar Clave</span>
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Alternar visibilidad de contraseña con el ícono de ojito
function toggleVisibilidadPass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye', 'text-gray-400');
        icon.classList.add('fa-eye-slash', 'text-blue-600');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash', 'text-blue-600');
        icon.classList.add('fa-eye', 'text-gray-400');
    }
}

function abrirModalCambioPassword() {
    inyectarModalCambioPassword();

    // Limpiar campos y resetear ojitos a oculto
    ['cp_pass_actual', 'cp_pass_nueva', 'cp_pass_repetir'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.value = '';
            el.type = 'password';
        }
    });
    ['icon_cp_actual', 'icon_cp_nueva', 'icon_cp_repetir'].forEach(id => {
        const ic = document.getElementById(id);
        if (ic) {
            ic.classList.remove('fa-eye-slash', 'text-blue-600');
            ic.classList.add('fa-eye', 'text-gray-400');
        }
    });

    document.getElementById('modalCambioPasswordGlobal').classList.remove('hidden');
    setTimeout(() => document.getElementById('cp_pass_actual')?.focus(), 100);
}

function cerrarModalCambioPassword() {
    const modal = document.getElementById('modalCambioPasswordGlobal');
    if (modal) modal.classList.add('hidden');
}

async function procesarCambioPasswordGlobal() {
    const actual  = document.getElementById('cp_pass_actual')?.value.trim() || '';
    const nueva   = document.getElementById('cp_pass_nueva')?.value.trim() || '';
    const repetir = document.getElementById('cp_pass_repetir')?.value.trim() || '';

    if (!actual || !nueva || !repetir) {
        Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Todos los campos son obligatorios.' });
        return;
    }
    if (nueva.length < 4) {
        Swal.fire({ icon: 'warning', title: 'Clave muy corta', text: 'La nueva contraseña debe tener mínimo 4 caracteres.' });
        return;
    }
    if (nueva !== repetir) {
        Swal.fire({ icon: 'error', title: 'No coinciden', text: 'La nueva contraseña y su confirmación no coinciden.' });
        return;
    }

    const btn = document.getElementById('btnGuardarPasswordGlobal');
    const txtOriginal = btn ? btn.innerHTML : 'Guardar Clave';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Guardando...';
    }

    try {
        const res = await fetch('/laboratorio/public/api/usuarios/cambiar-mi-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pass_actual: actual, pass_nueva: nueva })
        });

        const json = await res.json();

        if (res.ok && json.status === 'success') {
            cerrarModalCambioPassword();
            Swal.fire({
                icon: 'success',
                title: '¡Clave Actualizada!',
                text: json.message,
                timer: 1800,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo cambiar',
                text: json.message || 'Verifica tu contraseña actual.'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error de Red',
            text: 'Falla de comunicación con el servidor.'
        });
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = txtOriginal;
        }
    }
}