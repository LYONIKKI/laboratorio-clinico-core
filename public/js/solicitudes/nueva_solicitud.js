// public/js/solicitudes/nueva_solicitud.js
// Orquestador exclusivo de nueva orden: Búsqueda estricta por Cuenta y catálogo CPT

let examenesSeleccionadosNuevaOrden = [];
let timerBuscadorCpt = null;
let timerDiagNuevo = null;
let timerMedNuevo = null;
let timerServNuevo = null;

document.addEventListener("DOMContentLoaded", () => {
    activarAutocompletadosNuevaOrden();
});

// 1. ABRIR Y CERRAR MODAL
function abrirModalNuevaSolicitud() {
    examenesSeleccionadosNuevaOrden = [];
    document.getElementById('inputBuscarCuentaNueva').value = '';
    document.getElementById('fichaPacienteNueva').classList.add('hidden');
    document.getElementById('nuevoIdCuentaAtencion').value = '';
    document.getElementById('nuevoServicioInput').value = 'CONSULTA EXTERNA';
    document.getElementById('nuevoMedicoInput').value = 'POR ASIGNAR';
    document.getElementById('nuevaCamaInput').value = '---';
    document.getElementById('nuevoDiagnosticoInput').value = '';
    document.getElementById('inputBuscarExamenCpt').value = '';
    renderizarCanastaExamenes();

    document.getElementById('modalNuevaSolicitud').classList.remove('hidden');
    setTimeout(() => document.getElementById('inputBuscarCuentaNueva').focus(), 100);
}

function cerrarModalNuevaSolicitud() {
    document.getElementById('modalNuevaSolicitud').classList.add('hidden');
}

// 2. BUSCADOR ÚNICAMENTE POR N° DE CUENTA
async function buscarCuentaNuevaSolicitud() {
    const input = document.getElementById('inputBuscarCuentaNueva');
    const cuenta = (input?.value || '').trim();

    if (!cuenta || isNaN(cuenta)) {
        Swal.fire({ icon: 'warning', title: 'Atención', text: 'Ingrese un número de cuenta válido (solo dígitos).' });
        return;
    }

    try {
        const res = await fetch(`/laboratorio/public/api/nueva-orden/buscar-cuenta?cuenta=${encodeURIComponent(cuenta)}`);
        const json = await res.json();

        if (json.status !== 'success' || !json.data) {
            document.getElementById('fichaPacienteNueva').classList.add('hidden');
            Swal.fire({ icon: 'info', title: 'Sin resultados', text: json.message || `No se halló la cuenta #${cuenta}.` });
            return;
        }

        const a = json.data;
        document.getElementById('nuevoIdCuentaAtencion').value = a.idCuentaAtencion;
        document.getElementById('nuevoNombrePaciente').textContent = a.paciente;
        document.getElementById('nuevoDniPaciente').textContent = `DNI: ${a.dni} (HC: ${a.hc})`;
        document.getElementById('nuevoNroCuenta').textContent = `${a.idCuentaAtencion} (Atención: #${a.idAtencion})`;
        document.getElementById('nuevoSeguro').textContent = a.seguro;
        document.getElementById('nuevoServicioInput').value = a.servicio || 'CONSULTA EXTERNA';
        document.getElementById('nuevoMedicoInput').value = (a.medico && a.medico !== 'POR ASIGNAR') ? a.medico : '';
        document.getElementById('nuevaCamaInput').value = (a.cama && a.cama !== '---') ? a.cama : '---';

        document.getElementById('fichaPacienteNueva').classList.remove('hidden');

    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Falla al conectar con el servidor de admisiones.' });
    }
}

// 3. AUTOCOMPLETADOS CLÍNICOS REACTIVOS
function activarAutocompletadosNuevaOrden() {
    // A. Búsqueda de Exámenes CPT (apuntando a ClinicaController)
    const inputCpt = document.getElementById('inputBuscarExamenCpt');
    const boxCpt = document.getElementById('sugerenciasExamenesCpt');

    if (inputCpt && boxCpt) {
        inputCpt.addEventListener('input', (e) => {
            clearTimeout(timerBuscadorCpt);
            const term = e.target.value.trim();
            if (term.length < 2) { boxCpt.classList.add('hidden'); return; }

            timerBuscadorCpt = setTimeout(async () => {
                try {
                    const res = await fetch(`/laboratorio/public/api/clinica/buscar-cpt?term=${encodeURIComponent(term)}`);
                    const json = await res.json();
                    const lista = json.data || [];

                    if (lista.length === 0) {
                        boxCpt.innerHTML = `<div class="p-2 text-gray-400 text-center font-mono text-xs">No se encontraron exámenes en catálogo.</div>`;
                    } else {
                        // Construcción limpia sin onclick en string que rompa comillas
                        boxCpt.innerHTML = '';
                        lista.forEach(item => {
                            const div = document.createElement('div');
                            div.className = "p-2 hover:bg-green-50 cursor-pointer border-b border-gray-100 flex justify-between items-center";
                            div.innerHTML = `
                                <div>
                                    <span class="font-bold text-gray-800 text-xs block">${item.nombre}</span>
                                </div>
                                <span class="bg-gray-100 text-gray-600 font-bold px-1.5 py-0.5 rounded text-[10px] font-mono flex-shrink-0 ml-2">
                                    CPT #${item.codigo || item.idProducto}
                                </span>
                            `;
                            // Asignación directa por referencia (Inmune a comillas simples, dobles o caracteres especiales)
                            div.addEventListener('click', () => {
                                agregarExamenCanasta(item.idProducto, item.nombre);
                            });
                            boxCpt.appendChild(div);
                        });
                    }
                    boxCpt.classList.remove('hidden');
                } catch (err) {
                    console.error("Error al buscar CPT:", err);
                }
            }, 200);
        });
    }

    // B. Diagnósticos CIE-10 (ClinicaController)
    const inputDiag = document.getElementById('nuevoDiagnosticoInput');
    const boxDiag = document.getElementById('sugerenciasDiagNuevo');
    if (inputDiag && boxDiag) {
        inputDiag.addEventListener('input', (e) => {
            clearTimeout(timerDiagNuevo);
            const term = e.target.value.trim();
            if (term.length < 2) { boxDiag.classList.add('hidden'); return; }

            timerDiagNuevo = setTimeout(async () => {
                try {
                    const res = await fetch(`/laboratorio/public/api/clinica/buscar-diagnostico?term=${encodeURIComponent(term)}`);
                    const json = await res.json();
                    const lista = json.data || [];
                    if (lista.length === 0) {
                        boxDiag.innerHTML = `<div class="p-2 text-gray-400 text-center">No hay coincidencias CIE-10.</div>`;
                    } else {
                        boxDiag.innerHTML = lista.map(item => `
                            <div class="p-2 hover:bg-green-50 cursor-pointer border-b border-gray-100 flex justify-between"
                                 onclick="document.getElementById('nuevoDiagnosticoInput').value = '[${item.codigo}] ${item.descripcion.replace(/'/g, "\\'")}'; document.getElementById('sugerenciasDiagNuevo').classList.add('hidden');">
                                <span class="font-bold text-gray-800">${item.descripcion}</span>
                                <span class="bg-blue-100 text-blue-800 font-bold px-1.5 py-0.5 rounded text-[10px]">${item.codigo}</span>
                            </div>
                        `).join('');
                    }
                    boxDiag.classList.remove('hidden');
                } catch (e) {}
            }, 200);
        });
    }

    // C. Médicos Prescriptores (ClinicaController)
    const inputMed = document.getElementById('nuevoMedicoInput');
    const boxMed = document.getElementById('sugerenciasMedNuevo');
    if (inputMed && boxMed) {
        inputMed.addEventListener('input', (e) => {
            clearTimeout(timerMedNuevo);
            const term = e.target.value.trim();
            if (term.length < 2) { boxMed.classList.add('hidden'); return; }

            timerMedNuevo = setTimeout(async () => {
                try {
                    const res = await fetch(`/laboratorio/public/api/clinica/buscar-medico?term=${encodeURIComponent(term)}`);
                    const json = await res.json();
                    const lista = json.data || [];
                    if (lista.length === 0) {
                        boxMed.innerHTML = `<div class="p-2 text-gray-400 text-center">Médico no encontrado.</div>`;
                    } else {
                        boxMed.innerHTML = lista.map(item => `
                            <div class="p-2 hover:bg-green-50 cursor-pointer border-b border-gray-100 font-bold text-gray-800"
                                 onclick="document.getElementById('nuevoMedicoInput').value = '${item.nombre.replace(/'/g, "\\'")}'; document.getElementById('sugerenciasMedNuevo').classList.add('hidden');">
                                <i class="fa-solid fa-user-doctor text-green-600 mr-1.5"></i> ${item.nombre}
                            </div>
                        `).join('');
                    }
                    boxMed.classList.remove('hidden');
                } catch (e) {}
            }, 200);
        });
    }

    // D. Servicios Hospitalarios (ClinicaController)
    const inputServ = document.getElementById('nuevoServicioInput');
    const boxServ = document.getElementById('sugerenciasServNuevo');
    if (inputServ && boxServ) {
        inputServ.addEventListener('input', (e) => {
            clearTimeout(timerServNuevo);
            const term = e.target.value.trim();
            if (term.length < 2) { boxServ.classList.add('hidden'); return; }

            timerServNuevo = setTimeout(async () => {
                try {
                    const res = await fetch(`/laboratorio/public/api/clinica/buscar-servicio?term=${encodeURIComponent(term)}`);
                    const json = await res.json();
                    const lista = json.data || [];
                    if (lista.length === 0) {
                        boxServ.innerHTML = `<div class="p-2 text-gray-400 text-center">Servicio no encontrado.</div>`;
                    } else {
                        boxServ.innerHTML = lista.map(item => `
                            <div class="p-2 hover:bg-green-50 cursor-pointer border-b border-gray-100 font-bold text-gray-800"
                                 onclick="document.getElementById('nuevoServicioInput').value = '${item.nombre.replace(/'/g, "\\'")}'; document.getElementById('sugerenciasServNuevo').classList.add('hidden');">
                                <i class="fa-solid fa-hospital text-green-600 mr-1.5"></i> ${item.nombre}
                            </div>
                        `).join('');
                    }
                    boxServ.classList.remove('hidden');
                } catch (e) {}
            }, 200);
        });
    }

    // Cerrar sugerencias al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#inputBuscarExamenCpt') && !e.target.closest('#sugerenciasExamenesCpt')) boxCpt?.classList.add('hidden');
        if (!e.target.closest('#nuevoDiagnosticoInput') && !e.target.closest('#sugerenciasDiagNuevo')) boxDiag?.classList.add('hidden');
        if (!e.target.closest('#nuevoMedicoInput') && !e.target.closest('#sugerenciasMedNuevo')) boxMed?.classList.add('hidden');
        if (!e.target.closest('#nuevoServicioInput') && !e.target.closest('#sugerenciasServNuevo')) boxServ?.classList.add('hidden');
    });
}

// 4. CANASTA DE EXÁMENES
function agregarExamenCanasta(idProducto, nombre) {
    if (!examenesSeleccionadosNuevaOrden.some(x => x.id === idProducto)) {
        examenesSeleccionadosNuevaOrden.push({ id: idProducto, nombre: nombre });
        renderizarCanastaExamenes();
    }
    document.getElementById('inputBuscarExamenCpt').value = '';
    document.getElementById('sugerenciasExamenesCpt').classList.add('hidden');
}

function quitarExamenCanasta(idProducto) {
    examenesSeleccionadosNuevaOrden = examenesSeleccionadosNuevaOrden.filter(x => x.id !== idProducto);
    renderizarCanastaExamenes();
}

function renderizarCanastaExamenes() {
    const contenedor = document.getElementById('listaExamenesSeleccionados');
    if (examenesSeleccionadosNuevaOrden.length === 0) {
        contenedor.innerHTML = '<p class="text-gray-400 text-center py-2" id="msgSinExamenes">No ha seleccionado ningún análisis.</p>';
        return;
    }

    let html = '';
    examenesSeleccionadosNuevaOrden.forEach(ex => {
        html += `
            <div class="flex justify-between items-center bg-white p-2 border border-gray-200 rounded shadow-xs">
                <span class="font-bold text-gray-800"><i class="fa-solid fa-vial text-green-600 mr-1.5"></i> ${ex.nombre}</span>
                <button type="button" onclick="quitarExamenCanasta(${ex.id})" class="text-red-500 hover:text-red-700 p-1 cursor-pointer" title="Quitar">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        `;
    });
    contenedor.innerHTML = html;
}

async function procesarNuevaOrdenWeb() {
    const idCuenta = parseInt(document.getElementById('nuevoIdCuentaAtencion')?.value || 0);
    if (!idCuenta) {
        Swal.fire({ icon: 'warning', title: 'Atención', text: 'Debe ingresar y buscar una Cuenta de Atención válida primero.' });
        return;
    }

    if (examenesSeleccionadosNuevaOrden.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Atención', text: 'Debe agregar al menos un examen de laboratorio a la lista.' });
        return;
    }

    const btn = document.getElementById('btnGuardarNuevaOrden');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Creando Orden...';

    const payload = {
        idCuentaAtencion: idCuenta,
        examenes: examenesSeleccionadosNuevaOrden.map(x => x.id),
        medico: document.getElementById('nuevoMedicoInput')?.value || 'POR ASIGNAR',
        cama: document.getElementById('nuevaCamaInput')?.value || '---',
        diagnostico: document.getElementById('nuevoDiagnosticoInput')?.value || '---',
        servicio: document.getElementById('nuevoServicioInput')?.value || 'CONSULTA EXTERNA'
    };

    try {
        const res = await fetch('/laboratorio/public/api/nueva-orden/crear', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const json = await res.json();
        if (!json.success) throw new Error(json.error || json.message || 'Error al registrar orden');

        cerrarModalNuevaSolicitud();

        Swal.fire({
            icon: 'success',
            title: `¡Orden #${json.idMovimiento} Creada!`,
            text: 'Se registró correctamente. Ya puedes imprimir su ticket o digitarla desde la grilla.',
            timer: 2000,
            showConfirmButton: false
        });

        if (typeof actualizarSolicitudes === 'function') {
            actualizarSolicitudes(); // Recarga la tabla de inmediato
        }

    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Falla al Crear Solicitud', text: e.message });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check-circle"></i><span>Crear Orden Médica</span>';
    }
}

// Impresión del Ticket de Solicitud directamente desde el botón de la grilla
async function imprimirTicketDesdeGrilla(idMovimiento) {
    try {
        const res = await fetch(`/laboratorio/public/api/solicitudes/detalle?id=${idMovimiento}`);
        const data = await res.json();

        if (!data || !data.paciente) {
            Swal.fire({ icon: 'warning', title: 'Atención', text: 'No se pudo cargar la información para imprimir el ticket.' });
            return;
        }

        const examenesAdaptados = (data.examenes || []).map(e => ({
            nombre: e.Examen || 'EXAMEN CLÍNICO'
        }));

        imprimirTicketOrdenMedica(
            idMovimiento,
            data.paciente,
            data.idCuentaAtencion || '---',
            data.servicio || 'CONSULTA EXTERNA',
            data.medico || 'POR ASIGNAR',
            examenesAdaptados
        );

    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Falla al procesar el ticket de impresión.' });
    }
}

// Ticket de Solicitud para Consultorio / Emergencia (Formato Ticket 80mm con Firma y Sello)
function imprimirTicketOrdenMedica(idMov, paciente, cuenta, servicio, medico, examenes) {
    const ventana = window.open('', '_blank', 'width=420,height=600');
    if (!ventana) return;

    const listaItemsHtml = examenes.map(e => `
        <li style="padding: 2px 0; border-bottom: 1px dashed #ddd; display: flex; justify-content: space-between;">
            <span>${e.nombre}</span>
            <span style="font-size: 9px; color: #555;">[PENDIENTE]</span>
        </li>
    `).join('');

    const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Orden Médica #${idMov}</title>
            <style>
                @page { size: 80mm auto; margin: 3mm; }
                body { font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #000; width: 74mm; margin: 0 auto; line-height: 1.2; }
                .center { text-align: center; }
                .bold { font-weight: bold; }
                .border-b { border-bottom: 1px dashed #000; padding-bottom: 4px; margin-bottom: 5px; }
                .border-t { border-top: 1px dashed #000; padding-top: 4px; margin-top: 5px; }
                ul { list-style: none; padding: 0; margin: 5px 0; }
                .sello-box { margin-top: 35px; text-align: center; }
                .linea-firma { width: 80%; margin: 0 auto; border-top: 1px solid #000; }
                @media print { body { width: 100%; } }
            </style>
            <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
        </head>
        <body>
            <div class="center border-b">
                <div class="bold" style="font-size: 12px;">HOSPITAL REGIONAL DANIEL ALCIDES CARRIÓN</div>
                <div style="font-size: 10px;">UNIDAD DE LABORATORIO CLÍNICO</div>
                <div class="bold" style="font-size: 13px; margin-top: 3px;">SOLICITUD DE ANÁLISIS</div>
            </div>

            <div style="text-align: center; margin: 4px 0;">
                <svg id="barcode"></svg>
            </div>

            <div style="font-size: 10.5px;">
                <div><b>ORDEN:</b> #${idMov} &nbsp;&nbsp; <b>CUENTA:</b> ${cuenta}</div>
                <div><b>FECHA:</b> ${new Date().toLocaleString()}</div>
                <div><b>PACIENTE:</b> ${paciente}</div>
                <div><b>PROCEDENCIA:</b> ${servicio}</div>
                <div><b>MÉDICO:</b> ${medico}</div>
            </div>

            <div class="border-t bold" style="margin-top: 6px;">EXÁMENES SOLICITADOS (${examenes.length}):</div>
            <ul>
                ${listaItemsHtml}
            </ul>

            <div class="border-t" style="font-size: 9.5px; text-align: justify; margin-top: 6px;">
                * Presentar este ticket en el área de Toma de Muestras de Laboratorio para su atención.
            </div>

            <!-- APARTADO PARA SELLO Y FIRMA DEL MÉDICO -->
            <div class="sello-box">
                <div class="linea-firma"></div>
                <div class="bold" style="font-size: 10px; margin-top: 3px;">FIRMA Y SELLO MÉDICO</div>
                <div style="font-size: 9px; color: #444;">${medico}</div>
            </div>

            <script>
                window.onload = function() {
                    JsBarcode("#barcode", "${idMov}", {
                        format: "CODE128",
                        height: 35,
                        width: 1.5,
                        displayValue: true,
                        fontSize: 11,
                        margin: 0
                    });
                    window.print();
                };
            </script>
        </body>
        </html>
    `;
    ventana.document.write(html);
    ventana.document.close();
}