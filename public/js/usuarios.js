// public/js/usuarios.js
let listaUsuariosMemoria = [];
let listaUsuariosFiltrados = [];
let paginaActualUsuarios = 1;
const registrosPorPagina = 10;

document.addEventListener("DOMContentLoaded", () => {
    cargarUsuarios();

    const inputBuscar = document.getElementById("busquedaUsuarios");
    if (inputBuscar) {
        inputBuscar.addEventListener("input", (e) => {
            const termino = e.target.value.toLowerCase().trim();
            paginaActualUsuarios = 1;
            filtrarTablaUsuarios(termino);
        });
    }
});

async function cargarUsuarios() {
    const tbody = document.getElementById("tbodyUsuarios");
    tbody.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-gray-400 font-mono text-xs"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Obteniendo usuarios...</td></tr>`;

    try {
        const res = await fetch('/laboratorio/public/api/usuarios/listar');
        const json = await res.json();

        if (json.status !== 'success') throw new Error(json.message);
        listaUsuariosMemoria = json.data || [];
        listaUsuariosFiltrados = [...listaUsuariosMemoria];

        paginaActualUsuarios = 1;
        renderizarPaginadoUsuarios(listaUsuariosFiltrados);

    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-red-500 font-mono text-xs font-bold">Error al cargar usuarios: ${e.message}</td></tr>`;
    }
}

// Filtro en tiempo real
function filtrarTablaUsuarios(termino) {
    if (!termino) {
        listaUsuariosFiltrados = [...listaUsuariosMemoria];
    } else {
        listaUsuariosFiltrados = listaUsuariosMemoria.filter(u => {
            const cadena = `${u.id} ${u.nombre} ${u.usuario} ${u.rol}`.toLowerCase();
            return cadena.includes(termino);
        });
    }
    renderizarPaginadoUsuarios(listaUsuariosFiltrados);
}

// Renderizado con Paginación
function renderizarPaginadoUsuarios(lista) {
    const tbody = document.getElementById("tbodyUsuarios");
    const contador = document.getElementById("contador-usuarios");
    const paginadorContainer = document.getElementById("contenedorPaginacionUsuarios");

    const total = lista.length;
    if (contador) contador.textContent = `${total} Usuarios`;

    if (total === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-gray-400 font-mono text-xs"><i class="fa-solid fa-inbox mr-1.5"></i> No se encontraron usuarios coincidentes.</td></tr>`;
        if (paginadorContainer) paginadorContainer.innerHTML = '';
        return;
    }

    const totalPaginas = Math.ceil(total / registrosPorPagina);
    const desde = (paginaActualUsuarios - 1) * registrosPorPagina;
    const hasta = Math.min(desde + registrosPorPagina, total);
    const dataPagina = lista.slice(desde, hasta);

    let html = '';
    dataPagina.forEach(u => {
        const badgeEstado = u.estado === 1
            ? `<span class="bg-green-100 text-green-800 font-bold px-2 py-0.5 rounded-full text-[10px]">ACTIVO</span>`
            : `<span class="bg-red-100 text-red-800 font-bold px-2 py-0.5 rounded-full text-[10px]">INACTIVO</span>`;

        const btnEstadoColor = u.estado === 1 ? 'text-amber-600 hover:bg-amber-50' : 'text-green-600 hover:bg-green-50';
        const btnEstadoIcon  = u.estado === 1 ? 'fa-user-slash' : 'fa-user-check';
        const btnEstadoTitle = u.estado === 1 ? 'Desactivar Usuario' : 'Activar Usuario';

        html += `
            <tr class="hover:bg-gray-50 border-b border-gray-100 text-xs font-mono">
                <td class="p-3 font-bold text-gray-400 text-center">#${u.id}</td>
                <td class="p-3 font-bold text-gray-800 font-sans">${u.nombre}</td>
                <td class="p-3 font-bold text-blue-700">${u.usuario}</td>
                <td class="p-3 text-center"><span class="border border-gray-300 bg-gray-50 px-2 py-0.5 rounded text-[10px] font-bold text-gray-700">${u.rol.toUpperCase()}</span></td>
                <td class="p-3 text-center">${badgeEstado}</td>
                <td class="p-3 text-center">
                    <div class="flex items-center justify-center space-x-1.5">
                        <button onclick="abrirModalEditar(${u.id})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition cursor-pointer" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button onclick="cambiarEstadoUsuario(${u.id})" class="p-1.5 ${btnEstadoColor} rounded-lg transition cursor-pointer" title="${btnEstadoTitle}"><i class="fa-solid ${btnEstadoIcon}"></i></button>
                        <button onclick="eliminarUsuario(${u.id}, '${u.usuario}')" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer" title="Eliminar definitivamente"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                </td>
            </tr>`;
    });
    tbody.innerHTML = html;

    // Generar botones de paginación
    if (paginadorContainer) {
        let pagHtml = `<div class="text-gray-500 font-bold">Mostrando <span class="text-gray-900">${desde + 1}</span> a <span class="text-gray-900">${hasta}</span> de <span class="text-gray-900">${total}</span></div>`;
        pagHtml += `<div class="flex items-center space-x-1">`;
        for (let i = 1; i <= totalPaginas; i++) {
            pagHtml += `<button onclick="cambiarPaginaUsuarios(${i})" class="px-2.5 py-1 ${i === paginaActualUsuarios ? 'bg-blue-600 text-white font-bold' : 'bg-white text-gray-600 hover:bg-gray-200 border border-gray-300'} rounded-lg transition cursor-pointer">${i}</button>`;
        }
        pagHtml += `</div>`;
        paginadorContainer.innerHTML = pagHtml;
    }
}

function cambiarPaginaUsuarios(numero) {
    paginaActualUsuarios = numero;
    renderizarPaginadoUsuarios(listaUsuariosFiltrados);
}

// ==========================================
// FUNCIONES DE CREACIÓN
// ==========================================
function abrirModalCrear() {
    document.getElementById("crearNombre").value = "";
    document.getElementById("crearLogin").value = "";
    document.getElementById("crearPassword").value = "";
    document.getElementById("crearRol").value = "tecnico_lab";
    document.getElementById("modalCrearUsuario").classList.remove("hidden");
}

function cerrarModalCrear() {
    document.getElementById("modalCrearUsuario").classList.add("hidden");
}

async function ejecutarCrearUsuario(e) {
    e.preventDefault();

    const payload = {
        nombre: document.getElementById("crearNombre").value.trim(),
        usuario: document.getElementById("crearLogin").value.trim(),
        password: document.getElementById("crearPassword").value.trim(),
        rol: document.getElementById("crearRol").value
    };

    try {
        const res = await fetch('/laboratorio/public/api/usuarios/crear', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();

        if (json.status !== 'success') throw new Error(json.message);

        Swal.fire({ icon: 'success', title: 'Creado', text: json.message, timer: 1500, showConfirmButton: false });
        cerrarModalCrear();
        cargarUsuarios();

    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    }
}

// ==========================================
// FUNCIONES DE EDICIÓN
// ==========================================
function abrirModalEditar(id) {
    const user = listaUsuariosMemoria.find(u => u.id === id);
    if (!user) return;

    document.getElementById("editarId").value = user.id;
    document.getElementById("editarNombre").value = user.nombre;
    document.getElementById("editarLogin").value = user.usuario;
    document.getElementById("editarPassword").value = "";
    document.getElementById("editarRol").value = user.rol;
    document.getElementById("tituloModalEditar").textContent = `Editar Usuario #${user.id}`;
    document.getElementById("modalEditarUsuario").classList.remove("hidden");
}

function cerrarModalEditar() {
    document.getElementById("modalEditarUsuario").classList.add("hidden");
}

async function ejecutarActualizarUsuario(e) {
    e.preventDefault();

    const payload = {
        id: parseInt(document.getElementById("editarId").value),
        nombre: document.getElementById("editarNombre").value.trim(),
        usuario: document.getElementById("editarLogin").value.trim(),
        password: document.getElementById("editarPassword").value.trim(),
        rol: document.getElementById("editarRol").value
    };

    try {
        const res = await fetch('/laboratorio/public/api/usuarios/actualizar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();

        if (json.status !== 'success') throw new Error(json.message);

        Swal.fire({ icon: 'success', title: 'Actualizado', text: json.message, timer: 1500, showConfirmButton: false });
        cerrarModalEditar();
        cargarUsuarios();

    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    }
}

// ==========================================
// CAMBIO DE ESTADO (ACTIVO/INACTIVO)
// ==========================================
async function cambiarEstadoUsuario(id) {
    try {
        const res = await fetch('/laboratorio/public/api/usuarios/cambiar-estado', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const json = await res.json();

        if (json.status === 'success') {
            cargarUsuarios();
        }
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Error al cambiar estado del usuario.' });
    }
}

// ==========================================
// ELIMINAR USUARIO DEFINITIVAMENTE
// ==========================================
async function eliminarUsuario(id, username) {
    const confirm = await Swal.fire({
        title: `¿Eliminar a '${username}'?`,
        text: "Esta acción borrará al usuario de PostgreSQL permanentemente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (!confirm.isConfirmed) return;

    try {
        const res = await fetch('/laboratorio/public/api/usuarios/eliminar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const json = await res.json();

        if (json.status !== 'success') throw new Error(json.message);

        Swal.fire({ icon: 'success', title: 'Eliminado', text: json.message, timer: 1500, showConfirmButton: false });
        cargarUsuarios();

    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Atención', text: err.message });
    }
}