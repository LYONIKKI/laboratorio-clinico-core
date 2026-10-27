// public/js/solicitudes.js
// Orquestador reactivo Vanilla: Paginación, Avance %, Botones Verdes y Guardado Atómico

let listaSolicitudesMemoria = [];
let idMovimientoActivo = null;
let timerBusqueda = null;
// Paginación
let paginaActual = 1;
const registrosPorPagina = 10;

document.addEventListener("DOMContentLoaded", () => {
    actualizarSolicitudes();

    // Filtro reactivo en vivo
    const inputBuscar = document.getElementById("busquedaRapida");
    if (inputBuscar) {
        inputBuscar.addEventListener("input", (e) => {
            const termino = e.target.value.toLowerCase().trim();
            paginaActual = 1;
            filtrarTablaSolicitudes(termino);
        });
    }

    // Selector de Días
    const selectRango = document.getElementById("filtroTiempo");
    if (selectRango) {
        selectRango.addEventListener("change", () => {
            paginaActual = 1;
            actualizarSolicitudes();
        });
    }
});

// =========================================================================
// 1. CARGA DE LA TABLA PRINCIPAL DE ÓRDENES
// =========================================================================
async function actualizarSolicitudes() {
    const tbody = document.getElementById("tbodySolicitudes");
    const rango = document.getElementById("filtroTiempo")?.value || "3";

    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="8" class="p-8 text-center text-gray-400 font-mono text-xs"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Sincronizando órdenes con SIGH...</td></tr>`;
    }

    try {
        const res = await fetch(`/laboratorio/public/api/solicitudes/listar?rango=${rango}`);
        const data = await res.json();
        
        listaSolicitudesMemoria = Array.isArray(data) ? data : [];
        renderizarPaginado(listaSolicitudesMemoria);
    } catch (err) {
        if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="p-6 text-center text-red-500 font-bold font-mono text-xs"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Falla de comunicación con el servidor.</td></tr>`;
    }
}

function filtrarTablaSolicitudes(termino) {
    if (!termino) {
        renderizarPaginado(listaSolicitudesMemoria);
        return;
    }
    const filtrados = listaSolicitudesMemoria.filter(row => {
        return `${row.Nro_Atencion} ${row.Nro_Cuenta} ${row.Nro_Movimiento} ${row.Nombre_Lab} ${row.DNI} ${row.HC} ${row.Servicio_Procedencia}`.toLowerCase().includes(termino);
    });
    renderizarPaginado(filtrados);
}

// Copiado universal compatible con HTTP (sin requerir HTTPS)
function copiarTextoPortapapeles(texto) {
    if (!texto) return;

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(texto).then(() => notificarCopiado(texto)).catch(() => fallbackCopiar(texto));
    } else {
        fallbackCopiar(texto);
    }
}

function fallbackCopiar(texto) {
    const textArea = document.createElement("textarea");
    textArea.value = texto;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    textArea.style.top = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
        notificarCopiado(texto);
    } catch (err) {
        console.error('No se pudo copiar:', err);
    }
    document.body.removeChild(textArea);
}

function notificarCopiado(texto) {
    if (typeof Swal !== 'undefined') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: false
        });
        Toast.fire({
            icon: 'success',
            title: `Copiado: ${texto}`
        });
    }
}

// =========================================================================
// 2. RENDERIZADO CON BADGES DE PORCENTAJE (%) Y PAGINACIÓN
// =========================================================================
function renderizarPaginado(lista) {
    const tbody = document.getElementById("tbodySolicitudes");
    const contador = document.getElementById("contador-solicitudes");
    
    let paginadorContainer = document.getElementById("contenedor-paginacion");
    if (!paginadorContainer) {
        const divPrincipal = tbody.closest('.bg-white');
        paginadorContainer = document.createElement('div');
        paginadorContainer.id = "contenedor-paginacion";
        paginadorContainer.className = "p-3 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-mono";
        divPrincipal.appendChild(paginadorContainer);
    }

    const total = lista.length;
    if (contador) contador.textContent = `${total} Solicitudes`;

    if (total === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="p-6 text-center text-gray-400 font-mono text-xs"><i class="fa-solid fa-inbox mr-1.5"></i> No hay órdenes registradas.</td></tr>`;
        paginadorContainer.innerHTML = '';
        return;
    }

    const totalPaginas = Math.ceil(total / registrosPorPagina);
    const desde = (paginaActual - 1) * registrosPorPagina;
    const hasta = Math.min(desde + registrosPorPagina, total);
    const dataPagina = lista.slice(desde, hasta);

    let html = '';
    dataPagina.forEach(row => {
        const entregado = (row.Estado || '').toUpperCase().trim() === 'ENTREGADO';
        const badgeEstado = entregado 
            ? `<span class="bg-green-100 text-green-800 font-bold px-2 py-0.5 rounded-full text-[10px] inline-flex items-center"><i class="fa-solid fa-circle-check mr-1"></i>ENTREGADO</span>` 
            : `<span class="bg-yellow-100 text-yellow-800 font-bold px-2 py-0.5 rounded-full text-[10px] inline-flex items-center"><i class="fa-solid fa-clock mr-1"></i>PENDIENTE</span>`;

        const btnEntregar = entregado 
            ? `<button onclick="conmutarEstadoEntrega(${row.Nro_Movimiento}, 'ENTREGADO')" class="p-1.5 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg transition cursor-pointer" title="Revertir a Pendiente"><i class="fa-solid fa-rotate-left"></i></button>` 
            : `<button onclick="conmutarEstadoEntrega(${row.Nro_Movimiento}, 'PENDIENTE')" class="p-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition cursor-pointer" title="Confirmar Entrega"><i class="fa-solid fa-file-circle-check"></i></button>`;

        // Botones de acción organizados
        const btnVer = `<button onclick="verExamenesOrden(${row.Nro_Movimiento})" class="p-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition cursor-pointer" title="Ver Exámenes y Digitar"><i class="fa-solid fa-magnifying-glass"></i></button>`;
        const btnTicket = `<button onclick="imprimirTicketDesdeGrilla(${row.Nro_Movimiento})" class="p-1.5 bg-cyan-50 hover:bg-cyan-100 text-cyan-700 rounded-lg transition cursor-pointer" title="Imprimir Ticket / Orden Médica"><i class="fa-solid fa-receipt"></i></button>`;
        const btnEliminar = `<button onclick="eliminarOrdenClinica(${row.Nro_Movimiento})" class="p-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition cursor-pointer" title="Eliminar Solicitud #${row.Nro_Movimiento}"><i class="fa-solid fa-trash-can"></i></button>`;

        let servClass = 'bg-gray-100 text-gray-700 border-gray-200';
        const serv = (row.Servicio_Procedencia || 'CONSULTA EXTERNA').toUpperCase();
        if (serv.includes('EMERGENCIA')) servClass = 'bg-red-50 text-red-800 border-red-200';
        else if (serv.includes('HOSPITALIZACION') || serv.includes('UCI')) servClass = 'bg-purple-50 text-purple-800 border-purple-200';

        // Dinámica de progreso (%)
        const pct = row.Porcentaje_Avance || 0;
        const comp = row.Cantidad_Completados || 0;
        const tot = row.Cantidad_Examenes || 0;

        let badgeProgreso = '';
        if (pct === 100) {
            badgeProgreso = `<span class="bg-green-100 text-green-800 font-bold px-2.5 py-1 rounded-lg text-[10px] border border-green-200 inline-flex items-center"><i class="fa-solid fa-circle-check mr-1.5"></i>100% (${tot}/${tot})</span>`;
        } else if (pct > 0) {
            badgeProgreso = `<span class="bg-blue-50 text-blue-700 font-bold px-2.5 py-1 rounded-lg text-[10px] border border-blue-200 inline-flex items-center"><i class="fa-solid fa-spinner fa-spin mr-1.5"></i>${pct}% (${comp}/${tot})</span>`;
        } else {
            badgeProgreso = `<span class="bg-gray-100 text-gray-500 font-bold px-2.5 py-1 rounded-lg text-[10px] border border-gray-200">0% (0/${tot})</span>`;
        }

        const nroMostrar = row.Nro_Atencion || row.Nro_Cuenta || row.Nro_Movimiento;

        html += `
            <tr class="hover:bg-gray-50 border-b border-gray-200 text-xs font-mono">
                <td class="p-3 text-center">
                    <span class="font-black text-blue-700 text-xs block cursor-pointer hover:underline" title="Clic para copiar N° Cuenta / Atención" onclick="copiarTextoPortapapeles('${nroMostrar}')">${nroMostrar}</span>
                    <span class="text-gray-400 text-[10px] block">#${row.Nro_Movimiento}</span>
                </td>
                <td class="p-3 text-gray-600 text-[11px]">${row.hora_format || '---'}</td>
                <td class="p-3">
                    <span class="font-bold text-blue-700 block text-[11px]"><i class="fa-solid fa-id-card mr-1 text-[10px]"></i>${row.DNI}</span>
                    <span class="text-gray-400 block text-[10px]">HC: ${row.HC}</span>
                </td>
                <td class="p-3 font-bold uppercase text-gray-900 font-sans tracking-tight">${row.Nombre_Lab}</td>
                <td class="p-3"><span class="border px-2 py-0.5 rounded text-[10px] font-bold ${servClass}">${serv}</span></td>
                <td class="p-3 text-center">${badgeEstado}</td>
                <td class="p-3 text-center">${badgeProgreso}</td>
                <td class="p-3 text-center">
                    <div class="flex items-center justify-center space-x-1.5">
                        ${btnVer}
                        ${btnTicket}
                        ${btnEntregar}
                        ${btnEliminar}
                    </div>
                </td>
            </tr>`;
    });
    tbody.innerHTML = html;

    let pagHtml = `<div class="text-gray-500 font-bold">Mostrando <span class="text-gray-900">${desde + 1}</span> a <span class="text-gray-900">${hasta}</span> de <span class="text-gray-900">${total}</span></div>`;
    pagHtml += `<div class="flex items-center space-x-1">`;
    for (let i = 1; i <= totalPaginas; i++) {
        pagHtml += `<button onclick="cambiarPagina(${i})" class="px-2.5 py-1 ${i === paginaActual ? 'bg-red-600 text-white font-bold' : 'bg-white text-gray-600 hover:bg-gray-200 border border-gray-300'} rounded-lg transition cursor-pointer">${i}</button>`;
    }
    pagHtml += `</div>`;
    paginadorContainer.innerHTML = pagHtml;
}

function cambiarPagina(numero) {
    paginaActual = numero;
    renderizarPaginado(listaSolicitudesMemoria);
}

// =========================================================================
// 3. CAMBIAR ESTADO DE ORDEN
// =========================================================================
async function conmutarEstadoEntrega(idMovimiento, estadoActual) {
    const esEntregado = (estadoActual === 'ENTREGADO');
    const confirmacion = await Swal.fire({
        title: esEntregado ? '¿Revertir a Pendiente?' : '¿Confirmar Entrega?',
        text: `Orden de laboratorio #${idMovimiento}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar'
    });

    if (confirmacion.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('id', idMovimiento);
            const res = await fetch('/laboratorio/public/api/solicitudes/cambiar-estado', { method: 'POST', body: formData });
            const respuesta = await res.json();
            if (respuesta.success) { 
                Swal.fire({ icon: 'success', title: 'Actualizado', timer: 1200, showConfirmButton: false }); 
                actualizarSolicitudes(); 
            }
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de red.' }); }
    }
}

function activarAutocompletadoClinico() {
    const inputDiag = document.getElementById('detDiagnosticoInput');
    const boxDiag   = document.getElementById('sugerenciasDiagnostico');

    const inputMed  = document.getElementById('detMedicoInput');
    const boxMed    = document.getElementById('sugerenciasMedico');

    const inputServ = document.getElementById('detProcedenciaInput');
    const boxServ   = document.getElementById('sugerenciasServicio');

    // 1. Diagnósticos CIE-10
    if (inputDiag && boxDiag) {
        inputDiag.addEventListener('input', (e) => {
            clearTimeout(timerBusqueda);
            const term = e.target.value.trim();
            if (term.length < 2) { boxDiag.classList.add('hidden'); return; }

            timerBusqueda = setTimeout(async () => {
                try {
                    const res = await fetch(`/laboratorio/public/api/clinica/buscar-diagnostico?term=${encodeURIComponent(term)}`);
                    const resJson = await res.json();
                    const lista = resJson.data || [];

                    if (lista.length === 0) {
                        boxDiag.innerHTML = `<div class="p-2 text-gray-400 text-center">No se hallaron coincidencias CIE-10.</div>`;
                    } else {
                        boxDiag.innerHTML = lista.map(item => `
                            <div class="p-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 flex justify-between"
                                 onclick="seleccionarDiagnostico('${item.codigo}', '${item.descripcion.replace(/'/g, "\\'")}')">
                                <span class="font-bold text-gray-800">${item.descripcion}</span>
                                <span class="bg-blue-100 text-blue-800 font-bold px-1.5 py-0.5 rounded text-[10px]">${item.codigo}</span>
                            </div>
                        `).join('');
                    }
                    boxDiag.classList.remove('hidden');
                } catch (err) { console.error(err); }
            }, 250);
        });
    }

    // 2. Médicos
    if (inputMed && boxMed) {
        inputMed.addEventListener('input', (e) => {
            clearTimeout(timerBusqueda);
            const term = e.target.value.trim();
            if (term.length < 2) { boxMed.classList.add('hidden'); return; }

            timerBusqueda = setTimeout(async () => {
                try {
                    const res = await fetch(`/laboratorio/public/api/clinica/buscar-medico?term=${encodeURIComponent(term)}`);
                    const resJson = await res.json();
                    const lista = resJson.data || [];

                    if (lista.length === 0) {
                        boxMed.innerHTML = `<div class="p-2 text-gray-400 text-center">Médico no encontrado.</div>`;
                    } else {
                        boxMed.innerHTML = lista.map(item => `
                            <div class="p-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 font-bold text-gray-800"
                                 onclick="seleccionarMedico('${item.nombre.replace(/'/g, "\\'")}')">
                                <i class="fa-solid fa-user-doctor text-blue-600 mr-1.5"></i> ${item.nombre}
                            </div>
                        `).join('');
                    }
                    boxMed.classList.remove('hidden');
                } catch (err) { console.error(err); }
            }, 250);
        });
    }

    // 3. Servicios
    if (inputServ && boxServ) {
        inputServ.addEventListener('input', (e) => {
            clearTimeout(timerBusqueda);
            const term = e.target.value.trim();
            if (term.length < 2) { boxServ.classList.add('hidden'); return; }

            timerBusqueda = setTimeout(async () => {
                try {
                    const res = await fetch(`/laboratorio/public/api/clinica/buscar-servicio?term=${encodeURIComponent(term)}`);
                    const resJson = await res.json();
                    const lista = resJson.data || [];

                    if (lista.length === 0) {
                        boxServ.innerHTML = `<div class="p-2 text-gray-400 text-center">Servicio no encontrado.</div>`;
                    } else {
                        boxServ.innerHTML = lista.map(item => `
                            <div class="p-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 font-bold text-gray-800"
                                 onclick="seleccionarServicio('${item.nombre.replace(/'/g, "\\'")}')">
                                <i class="fa-solid fa-hospital text-emerald-600 mr-1.5"></i> ${item.nombre}
                            </div>
                        `).join('');
                    }
                    boxServ.classList.remove('hidden');
                } catch (err) { console.error(err); }
            }, 250);
        });
    }

    // Ocultar listas si se hace clic fuera
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#detDiagnosticoInput') && !e.target.closest('#sugerenciasDiagnostico')) boxDiag?.classList.add('hidden');
        if (!e.target.closest('#detMedicoInput') && !e.target.closest('#sugerenciasMedico')) boxMed?.classList.add('hidden');
        if (!e.target.closest('#detProcedenciaInput') && !e.target.closest('#sugerenciasServicio')) boxServ?.classList.add('hidden');
    });
}

function seleccionarDiagnostico(codigo, descripcion) {
    document.getElementById('detDiagnosticoInput').value = `[${codigo}] ${descripcion}`;
    document.getElementById('sugerenciasDiagnostico').classList.add('hidden');
    guardarCabeceraClinica();
}

function seleccionarMedico(nombre) {
    document.getElementById('detMedicoInput').value = nombre;
    document.getElementById('sugerenciasMedico').classList.add('hidden');
    guardarCabeceraClinica();
}

function seleccionarServicio(nombre) {
    document.getElementById('detProcedenciaInput').value = nombre;
    document.getElementById('sugerenciasServicio').classList.add('hidden');
    guardarCabeceraClinica();
}

// Iniciar eventos al cargar DOM
document.addEventListener("DOMContentLoaded", () => {
    actualizarSolicitudes();
    activarAutocompletadoClinico();
});

// =========================================================================
// 4. MODAL AZUL: LISTA DE EXÁMENES Y CABECERA CLÍNICA
// =========================================================================
async function verExamenesOrden(idMovimiento) {
    idMovimientoActivo = idMovimiento;

    // 1. Respaldo inmediato desde la memoria de la tabla
    const ordenMemoria = listaSolicitudesMemoria.find(r => parseInt(r.Nro_Movimiento) === parseInt(idMovimiento));

    document.getElementById('detIdMovimiento').value = idMovimiento;
    document.getElementById('detPaciente').textContent = ordenMemoria ? ordenMemoria.Nombre_Lab : 'CARGANDO...';
    document.getElementById('detDni').textContent = ordenMemoria ? `DNI: ${ordenMemoria.DNI}` : 'DNI: ---';
    document.getElementById('detProcedenciaInput').value = ordenMemoria ? ordenMemoria.Servicio_Procedencia : 'CONSULTA EXTERNA';
    document.getElementById('detMedicoInput').value = 'POR ASIGNAR';
    document.getElementById('detCamaInput').value = '---';
    document.getElementById('detDiagnosticoInput').value = '---';
    document.getElementById('listaExamenesSolicitud').innerHTML = `<div class="p-6 text-center text-gray-400 font-mono text-xs"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Obteniendo orden médica desde SIGH...</div>`;
    
    // Abrir modal azul
    document.getElementById('modalDetalleSolicitud').classList.remove('hidden');

    try {
        const res = await fetch(`/laboratorio/public/api/solicitudes/detalle?id=${idMovimiento}`);
        const data = await res.json();

        // 2. Asignación segura con la data del servidor (AHORA SÍ EXISTE data)
        document.getElementById('detPaciente').textContent = (data.paciente && data.paciente !== 'PACIENTE NO REGISTRADO') ? data.paciente : (ordenMemoria?.Nombre_Lab || 'PACIENTE SIGH');
        document.getElementById('detDni').textContent = (data.dni && data.dni !== '---') ? `DNI: ${data.dni}` : `DNI: ${ordenMemoria?.DNI || '---'}`;
        document.getElementById('detHcValue').value = (data.hc && data.hc !== '---') ? data.hc : (ordenMemoria?.HC || '---');
        document.getElementById('detEdadValue').value = data.edad || '---';
        document.getElementById('detResponsableValue').value = data.responsable || 'TECNÓLOGO DE TURNO';
        document.getElementById('detIdCuentaAtencion').value = data.idCuentaAtencion || 0;
        
        // Seguro asignado dinámicamente según la cuenta de admisión
        if (document.getElementById('detSeguroValue')) {
            document.getElementById('detSeguroValue').value = data.seguro || 'PARTICULAR / PAGANTE';
        }

        // Cargar inputs editables
        document.getElementById('detCamaInput').value = (data.cama && data.cama !== '---') ? data.cama : '';
        document.getElementById('detDiagnosticoInput').value = (data.diagnostico && data.diagnostico !== '---') ? data.diagnostico : '';
        document.getElementById('detProcedenciaInput').value = data.servicio || ordenMemoria?.Servicio_Procedencia || 'CONSULTA EXTERNA';
        document.getElementById('detMedicoInput').value = (data.medico && data.medico !== 'POR ASIGNAR') ? data.medico : '';

        const listaContainer = document.getElementById('listaExamenesSolicitud');
        listaContainer.innerHTML = '';
        if (!data.examenes || data.examenes.length === 0) {
            listaContainer.innerHTML = '<p class="text-gray-400 text-center py-4 font-mono text-xs">No hay exámenes registrados para esta orden.</p>';
            return;
        }

        data.examenes.forEach(ex => {
            const nombreSanitizado = (ex.Examen || '').replace(/'/g, "\\'");
            const completado = (ex.completado === true);

            const btnColor = completado 
                ? "bg-green-600 hover:bg-green-700 text-white" 
                : "bg-blue-600 hover:bg-blue-700 text-white";

            const btnIcon = completado ? "fa-solid fa-circle-check" : "fa-solid fa-pen-to-square";
            const btnTexto = completado ? "Completado" : "Digitar";
            const borderCard = completado ? "border-green-300 bg-green-50" : "border-gray-200 bg-white";

            const itemDiv = document.createElement('div');
            itemDiv.className = `border rounded-xl p-3 flex justify-between items-center shadow-sm text-xs font-mono transition ${borderCard}`;
            itemDiv.innerHTML = `
                <div>
                    <h5 class="font-bold text-gray-800 flex items-center">
                        <i class="fa-solid fa-vial ${completado ? 'text-green-600' : 'text-blue-600'} mr-2"></i> ${ex.Examen}
                    </h5>
                    ${ex.Observacion ? `<p class="text-[10px] text-gray-400 ml-5">${ex.Observacion}</p>` : ''}
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="abrirDigitacionManual(${idMovimiento}, '${nombreSanitizado}', ${ex.idProductoCPT || 0})"
                            class="px-3 py-1.5 rounded-lg font-bold text-xs ${btnColor} transition flex items-center space-x-1.5 shadow cursor-pointer"
                            title="${completado ? 'Clic para editar el resultado' : 'Ingresar resultado'}">
                        <i class="${btnIcon}"></i>
                        <span>${btnTexto}</span>
                    </button>
                </div>`;
            listaContainer.appendChild(itemDiv);
        });
    } catch (e) {
        console.error("Error al cargar detalle:", e);
        document.getElementById('detPaciente').textContent = ordenMemoria?.Nombre_Lab || 'PACIENTE SIGH';
    }
}

async function guardarCabeceraClinica() {
    const idMovimiento = idMovimientoActivo || document.getElementById('detIdMovimiento')?.value;
    if (!idMovimiento) return;

    const cama     = document.getElementById('detCamaInput')?.value || '---';
    const diag     = document.getElementById('detDiagnosticoInput')?.value || '---';
    const medico   = document.getElementById('detMedicoInput')?.value || '';
    const servicio = document.getElementById('detProcedenciaInput')?.value || '';

    try {
        await fetch('/laboratorio/public/api/clinica/actualizar-cabecera', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                idMovimiento: parseInt(idMovimiento), 
                cama: cama, 
                diagnostico: diag,
                medico: medico,
                servicio: servicio
            })
        });
    } catch (err) {
        console.error("No se pudo guardar la cabecera clínica:", err);
    }
}

// =========================================================================
// 7. IMPRESIÓN DIRECTA DESDE EL MODAL DE DIGITACIÓN (A4 CON LOGOS)
// =========================================================================
function imprimirDesdeModalDigitacion() {
    const idMovimiento = idMovimientoActivo || document.getElementById('detIdMovimiento')?.value || '';
    if (!idMovimiento) {
        Swal.fire({ icon: 'warning', title: 'Atención', text: 'No se identificó el número de movimiento.' });
        return;
    }

    const ordenMemoria = listaSolicitudesMemoria.find(r => parseInt(r.Nro_Movimiento) === parseInt(idMovimiento));

    let paciente = (document.getElementById('detPaciente')?.textContent || '').trim();
    if (!paciente || paciente === 'CARGANDO...' || paciente === 'PACIENTE NO REGISTRADO' || paciente === 'PACIENTE NO IDENTIFICADO') {
        paciente = ordenMemoria?.Nombre_Lab || 'PACIENTE SIGH';
    }

    let dniRaw = (document.getElementById('detDni')?.textContent || '').trim();
    let dni = dniRaw.replace('DNI: ', '').trim();
    if (!dni || dni === '---') {
        dni = ordenMemoria?.DNI || '---';
    }

    let hc = (document.getElementById('detHcValue')?.value || '').trim();
    if (!hc || hc === '---') {
        hc = ordenMemoria?.HC || '---';
    }

    const servicio    = document.getElementById('detProcedenciaInput')?.value?.trim() || ordenMemoria?.Servicio_Procedencia || 'CONSULTA EXTERNA';
    const medico      = document.getElementById('detMedicoInput')?.value?.trim() || 'POR ASIGNAR';
    const camaEditada = document.getElementById('detCamaInput')?.value?.trim() || '---';
    const diagEditado = document.getElementById('detDiagnosticoInput')?.value?.trim() || '---';
    const edad        = document.getElementById('detEdadValue')?.value?.trim() || '---';
    const responsable = document.getElementById('detResponsableValue')?.value?.trim() || 'TECNÓLOGO DE TURNO';
    const seguroReal  = document.getElementById('detSeguroValue')?.value || 'PARTICULAR / PAGANTE';

    // Persistir cambios antes de imprimir
    guardarCabeceraClinica();

    let examenTitulo = document.getElementById('modalDigitacionLabel')?.textContent || 'EXAMEN DE LABORATORIO';
    examenTitulo = examenTitulo.replace('DIGITACIÓN - ', '').replace(/\(#\d+\)/, '').trim();

    const filas = document.querySelectorAll('#modalDigitacionBody table tbody tr');
    let resultadosHTML = '';

    filas.forEach(tr => {
        if (tr.querySelector('td[colspan]')) {
            const textoSeccion = tr.textContent.trim();
            resultadosHTML += `
                <tr style="background-color: #f1f5f9; font-weight: bold; text-align: center;">
                    <td colspan="3" style="padding: 6px 10px; border-bottom: 1.5px solid #cbd5e1; text-transform: uppercase; font-size: 11px;">
                        ${textoSeccion}
                    </td>
                </tr>`;
            return;
        }

        const celdas = tr.querySelectorAll('td');
        if (celdas.length >= 2) {
            const nombreAnalito = celdas[0]?.textContent?.trim() || '';
            const input = tr.querySelector('.input-resultado');
            
            let valorResultado = '---';
            if (input) {
                valorResultado = input.value?.trim() || '---';
            }

            const valRef = celdas[2] ? celdas[2].childNodes[0]?.textContent?.trim() : 'N/A';

            resultadosHTML += `
                <tr>
                    <td style="padding: 6px 10px; border-bottom: 1px dotted #000; font-weight: bold;">${nombreAnalito}</td>
                    <td style="padding: 6px 10px; border-bottom: 1px dotted #000; text-align: center; color: #1d4ed8; font-weight: bold; font-size: 12px;">${valorResultado}</td>
                    <td style="padding: 6px 10px; border-bottom: 1px dotted #000; text-align: center; color: #475569; font-size: 10px;">${valRef}</td>
                </tr>`;
        }
    });

    const infoBase = {
        paciente: paciente,
        dni: dni,
        hc: hc,
        servicio: servicio,
        cama: camaEditada,
        diagnostico: diagEditado,
        edad: edad,
        medico: medico,
        seguro: seguroReal,
        responsable: responsable
    };

    if (typeof ImpresionLaboratorio !== 'undefined' && typeof ImpresionLaboratorio.reporteA4 === 'function') {
        ImpresionLaboratorio.reporteA4(idMovimiento, infoBase, resultadosHTML, examenTitulo);
    } else {
        alert("El módulo de impresión no está disponible.");
    }
}

function cerrarModalDetalle() { document.getElementById('modalDetalleSolicitud').classList.add('hidden'); }

// =========================================================================
// 5. MODAL DE DIGITACIÓN MANUAL DINÁMICA
// =========================================================================
async function abrirDigitacionManual(idMovimiento, nombreExamen, idCpt) {
    const modal = document.getElementById('modalDigitacion');
    const modalBody = document.getElementById('modalDigitacionBody');
    const modalTitulo = document.getElementById('modalDigitacionLabel');

    if (!modal || !modalBody) return;

    modalTitulo.textContent = `DIGITACIÓN - ${nombreExamen} (#${idMovimiento})`;
    modalBody.innerHTML = `<div class="p-8 text-center text-gray-400 font-mono text-xs"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Construyendo plantilla analítica desde BD...</div>`;
    modal.classList.remove('hidden');

    try {
        const url = `/laboratorio/public/api/get-plantilla-examen?idMovimiento=${idMovimiento}&examen=${encodeURIComponent(nombreExamen)}`;
        const res = await fetch(url);
        
        if (!res.ok) throw new Error("Error HTTP " + res.status);
        
        const data = await res.json();
        if (!data || data.length === 0 || data.error) {
            modalBody.innerHTML = '<div class="p-6 bg-red-50 text-red-700 rounded-lg font-bold text-center border border-red-200">No se encontraron sub-pruebas para este examen en la base de datos SIGH.</div>';
            return;
        }

        const idOrden = data[0].idOrden || 0;
        let plantillaHtml = '';

        // Prioridad de enrutamiento a submódulos clínicos
        if (typeof ModuloOrina !== 'undefined' && ModuloOrina.esOrina(nombreExamen, nombreExamen, idCpt)) {
            plantillaHtml = ModuloOrina.renderizar(data, nombreExamen, nombreExamen, idMovimiento, idOrden);
        } else if (typeof ModuloMicrobiologia !== 'undefined' && ModuloMicrobiologia.esMicrobiologia(nombreExamen, nombreExamen)) {
            plantillaHtml = ModuloMicrobiologia.renderizar(data, nombreExamen, nombreExamen, idMovimiento, idOrden);
        } else if (typeof ModuloInmunologia !== 'undefined' && ModuloInmunologia.esInmunologia(nombreExamen, nombreExamen)) {
            plantillaHtml = ModuloInmunologia.renderizar(data, nombreExamen, nombreExamen, idMovimiento, idOrden);
        } else if (typeof ModuloHematologia !== 'undefined' && ModuloHematologia.esHematologia(nombreExamen, nombreExamen)) {
            plantillaHtml = ModuloHematologia.renderizar(data, nombreExamen, nombreExamen, idMovimiento, idOrden);
        } else if (typeof ModuloOtros !== 'undefined' && ModuloOtros.esOtroExamen(nombreExamen, nombreExamen)) {
            plantillaHtml = ModuloOtros.renderizar(data, nombreExamen, nombreExamen, idMovimiento, idOrden);
        } else if (typeof ModuloBioquimica !== 'undefined' && ModuloBioquimica.esBioquimica(nombreExamen, nombreExamen, idCpt)) {
            plantillaHtml = ModuloBioquimica.renderizar(data, nombreExamen, nombreExamen, idMovimiento, idOrden);
        } else {
            plantillaHtml = ModuloBioquimica.renderizar(data, nombreExamen, nombreExamen, idMovimiento, idOrden);
        }

        modalBody.innerHTML = plantillaHtml;

    } catch (e) {
        console.error("Error al abrir digitación:", e);
        modalBody.innerHTML = '<div class="p-6 bg-red-50 text-red-700 rounded-lg font-bold text-center border border-red-200"><i class="fa-solid fa-triangle-exclamation mr-2"></i>Falla al cargar la plantilla clínica. Comuníquese con soporte de Sistemas.</div>';
    }
}


function cerrarModalDigitacion() { document.getElementById('modalDigitacion').classList.add('hidden'); }

// =========================================================================
// 6. GUARDADO DE RESULTADOS (ATÓMICO CON SINCRONIZACIÓN DE UROCULTIVO)
// =========================================================================
async function procesarGuardadoResultados() {
    // 1. Sincronizar antibióticos si el examen abierto es Urocultivo / Microbiología
    if (typeof ModuloMicrobiologia !== 'undefined' && typeof ModuloMicrobiologia.sincronizarAntibiograma === 'function') {
        ModuloMicrobiologia.sincronizarAntibiograma();
    }

    const inputs = document.querySelectorAll('#modalDigitacionBody .input-resultado');
    if (!inputs || inputs.length === 0) return;

    const btn = document.getElementById('btnGuardarResultadosManual');
    const textoOriginal = btn ? btn.innerHTML : 'Guardar';

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Guardando...';
    }

    // Persistir cabecera clínica editada (Cama, Diagnóstico, Médico, Servicio)
    if (typeof guardarCabeceraClinica === 'function') {
        await guardarCabeceraClinica();
    }

    const cuentaGlobal = document.getElementById('detIdCuentaAtencion')?.value || 0;
    const peticiones = [];

    inputs.forEach(el => {
        const valor = (el.value || '').trim();
        const idMov = el.getAttribute('data-movimiento') || idMovimientoActivo;
        const idCpt = el.getAttribute('data-cpt');
        const nombrePrueba = el.getAttribute('data-nombre');
        const ref = el.getAttribute('data-referencial') || 'NO REACTIVO';
        const atencion = el.getAttribute('data-atencion') || cuentaGlobal;

        const tr = el.closest('tr');
        const obs = tr ? (tr.querySelector('.input-observacion')?.value || '').trim() : '';

        if (idMov && nombrePrueba) {
            const payload = {
                idMovimiento: parseInt(idMov),
                idCuentaAtencion: parseInt(atencion) || 0,
                idProductoCpt: parseInt(idCpt) || 0,
                nombrePrueba: nombrePrueba,
                resultado: valor,
                referencial: ref,
                observaciones: obs
            };

            peticiones.push(
                fetch('/laboratorio/public/api/solicitudes/guardar-resultado', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                }).then(async res => {
                    const json = await res.json();
                    if (!res.ok || !json.success) throw new Error(json.error || json.message || 'Error al guardar');
                    return json;
                })
            );
        }
    });

    try {
        await Promise.all(peticiones);

        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-check-double mr-1"></i> ¡Guardado!';
            btn.className = "bg-green-600 text-white font-bold px-4 py-2 rounded-xl text-xs transition";
        }

        Swal.fire({
            icon: 'success',
            title: '¡Resultados Guardados!',
            text: 'Se asentaron los analitos correctamente en SIGH.',
            timer: 1500,
            showConfirmButton: false
        });

        setTimeout(() => {
            cerrarModalDigitacion();
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = textoOriginal;
                btn.className = "bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-xl transition shadow flex items-center space-x-1.5 cursor-pointer";
            }
            if (idMovimientoActivo) {
                verExamenesOrden(idMovimientoActivo);
            }
            actualizarSolicitudes(); // Refresca grilla principal de 72h
        }, 800);

    } catch (e) {
        console.error("Fallo al guardar:", e);
        Swal.fire({
            icon: 'error',
            title: 'Falla al Guardar',
            text: e.message || 'No se pudo registrar en la base de datos.'
        });
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = textoOriginal;
        }
    }
}

// =========================================================================
// 8. IMPRESIÓN DE ETIQUETA TÉRMICA PARA TUBO (DNI + CTA - SIN LISTA DE EXÁMENES)
// =========================================================================
function imprimirEtiquetaTubo() {
    const idMovimiento = idMovimientoActivo || document.getElementById('detIdMovimiento')?.value || '';
    if (!idMovimiento) {
        Swal.fire({ icon: 'warning', title: 'Atención', text: 'No hay una orden activa seleccionada.' });
        return;
    }

    const paciente = (document.getElementById('detPaciente')?.textContent || 'PACIENTE SIGH').trim();
    let dni = (document.getElementById('detDni')?.textContent || '').replace('DNI: ', '').trim();
    const cuenta = (document.getElementById('detIdCuentaAtencion')?.value || '').trim();
    const servicio = (document.getElementById('detProcedenciaInput')?.value || 'CONSULTA EXTERNA').trim();

    const ventanaEtiqueta = window.open('', '_blank', 'width=480,height=320');
    if (!ventanaEtiqueta) {
        alert("Por favor permita las ventanas emergentes para visualizar e imprimir la etiqueta.");
        return;
    }

    const htmlEtiqueta = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Etiqueta Tubo #${idMovimiento}</title>
            <style>
                @page { 
                    size: 50mm 30mm; 
                    margin: 0; 
                }
                body {
                    margin: 0;
                    padding: 5px 6px;
                    font-family: Arial, Helvetica, sans-serif;
                    color: #000;
                    width: 50mm;
                    box-sizing: border-box;
                    background-color: #fff;
                }
                .paciente { 
                    font-weight: 900; 
                    font-size: 10.5px; 
                    text-transform: uppercase; 
                    white-space: nowrap; 
                    overflow: hidden; 
                    text-overflow: ellipsis;
                    letter-spacing: -0.2px;
                }
                .datos { 
                    display: flex; 
                    justify-content: space-between; 
                    align-items: center;
                    font-weight: bold; 
                    font-size: 9.5px; 
                    margin-top: 2px;
                    font-family: 'Courier New', Courier, monospace;
                }
                .cuenta-badge { 
                    background-color: #000; 
                    color: #fff; 
                    padding: 1px 4px; 
                    border-radius: 3px; 
                    font-size: 9px;
                }
                .servicio { 
                    font-size: 8.5px; 
                    font-weight: 600;
                    margin-top: 2px; 
                    white-space: nowrap; 
                    overflow: hidden; 
                    text-overflow: ellipsis;
                    text-transform: uppercase;
                }
                .barcode-box { 
                    text-align: center; 
                    margin-top: 3px; 
                }
                .barcode-box svg { 
                    width: 100%; 
                    max-height: 38px; 
                }
                .no-print-bar {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 6px;
                    padding-bottom: 4px;
                    border-bottom: 1px dashed #ccc;
                }
                .btn-accion {
                    background: #2563eb;
                    color: white;
                    border: none;
                    padding: 3px 8px;
                    font-size: 10px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: bold;
                }
                @media print {
                    .no-print-bar { display: none; }
                    body { padding: 4px 5px; }
                }
            </style>
            <!-- Script offline local o CDN con fallback -->
            <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
        </head>
        <body>
            <div class="no-print-bar">
                <button class="btn-accion" onclick="window.print()">Reimprimir</button>
                <button class="btn-accion" style="background: #64748b;" onclick="window.close()">Cerrar</button>
            </div>

            <div class="paciente">${paciente}</div>
            
            <div class="datos">
                <span>DNI: ${dni || '---'}</span>
                <span class="cuenta-badge">CTA: ${cuenta || idMovimiento}</span>
            </div>

            <div class="servicio">${servicio}</div>

            <div class="barcode-box">
                <svg id="barcode"></svg>
            </div>

            <script>
                window.onload = function() {
                    try {
                        JsBarcode("#barcode", "${idMovimiento}", {
                            format: "CODE128",
                            height: 32,
                            displayValue: true,
                            fontSize: 10,
                            margin: 0,
                            textMargin: 1
                        });
                    } catch(e) {}
                    // Se dispara la impresión sin forzar window.close()
                    window.print();
                };
            </script>
        </body>
        </html>
    `;

    ventanaEtiqueta.document.write(htmlEtiqueta);
    ventanaEtiqueta.document.close();
}

async function eliminarOrdenClinica(idMovimiento) {
    const confirm = await Swal.fire({
        title: `¿Eliminar Orden #${idMovimiento}?`,
        text: "Se anularán los exámenes solicitados y la orden de servicio asociada en SIGH.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (!confirm.isConfirmed) return;

    try {
        const res = await fetch('/laboratorio/public/api/nueva-orden/eliminar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idMovimiento: idMovimiento })
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || json.message);

        Swal.fire({ icon: 'success', title: 'Eliminado', text: json.message, timer: 1500, showConfirmButton: false });
        if (typeof actualizarSolicitudes === 'function') actualizarSolicitudes();

    } catch (e) {
        Swal.fire({ icon: 'error', title: 'No se pudo eliminar', text: e.message });
    }
}