// public/js/seguimiento.js
// Manejo del portal de consulta y visualización médica (Solo Lectura)

let listaOrdenesMedico = [];
let datosOrdenActiva = null;

document.addEventListener("DOMContentLoaded", () => {
    const inputBuscar = document.getElementById("inputBuscarMedico");
    if (inputBuscar) {
        inputBuscar.addEventListener("keydown", (e) => {
            if (e.key === "Enter") {
                ejecutarBusquedaMedico();
            }
        });
    }
});

// 1. Ejecutar búsqueda contra el controlador
async function ejecutarBusquedaMedico() {
    const input = document.getElementById("inputBuscarMedico");
    const valor = (input?.value || '').trim();

    if (valor.length < 2) {
        Swal.fire({ icon: 'warning', title: 'Atención', text: 'Ingrese al menos 2 caracteres para consultar.' });
        return;
    }

    const contenedor = document.getElementById("contenedorResultadosMedico");
    const tbody = document.getElementById("tbodyResultadosMedico");
    const contador = document.getElementById("contador-resultados-medico");

    contenedor.classList.remove("hidden");
    tbody.innerHTML = `<tr><td colspan="8" class="p-8 text-center text-gray-400 font-mono text-xs"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Consultando historial médico en SQL Server...</td></tr>`;

    try {
        const res = await fetch(`/laboratorio/public/api/seguimiento/buscar?q=${encodeURIComponent(valor)}`);
        const json = await res.json();

        if (json.status === 'error') {
            tbody.innerHTML = `<tr><td colspan="8" class="p-6 text-center text-red-500 font-mono text-xs font-bold"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Error de consulta.</td></tr>`;
            return;
        }

        listaOrdenesMedico = json.data || [];
        contador.textContent = `${listaOrdenesMedico.length} Órdenes`;

        if (listaOrdenesMedico.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="p-6 text-center text-gray-400 font-mono text-xs font-bold"><i class="fa-solid fa-inbox mr-1.5"></i> No se hallaron órdenes para "${valor}".</td></tr>`;
            return;
        }

        renderizarTablaMedico(listaOrdenesMedico);

    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="8" class="p-6 text-center text-red-500 font-mono text-xs font-bold"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Error al conectar con el servidor.</td></tr>`;
    }
}

// 2. Renderizar tabla médica (Compatible con Tailwind v2.2.19)
function renderizarTablaMedico(lista) {
    const tbody = document.getElementById("tbodyResultadosMedico");
    let html = '';

    lista.forEach(row => {
        const entregado = (row.Estado || '').toUpperCase().trim() === 'ENTREGADO';
        const badgeEstado = entregado 
            ? `<span class="bg-green-100 text-green-800 font-bold px-2 py-0.5 rounded-full text-[10px] inline-flex items-center"><i class="fa-solid fa-circle-check mr-1"></i>ENTREGADO</span>` 
            : `<span class="bg-yellow-100 text-yellow-800 font-bold px-2 py-0.5 rounded-full text-[10px] inline-flex items-center"><i class="fa-solid fa-clock mr-1"></i>PENDIENTE</span>`;

        let servClass = 'bg-gray-100 text-gray-700 border-gray-200';
        const serv = (row.Servicio_Procedencia || 'CONSULTA EXTERNA').toUpperCase();
        if (serv.includes('EMERGENCIA')) servClass = 'bg-red-50 text-red-800 border-red-200';
        else if (serv.includes('HOSPITALIZACION') || serv.includes('UCI')) servClass = 'bg-purple-50 text-purple-800 border-purple-200';

        const pct = row.Porcentaje_Avance || 0;
        const comp = row.Cantidad_Completados || 0;
        const tot = row.Cantidad_Examenes || 0;

        let badgeProgreso = '';
        if (pct === 100 || comp > 0) {
            badgeProgreso = `<span class="bg-green-100 text-green-800 font-bold px-2.5 py-1 rounded-lg text-[10px] border border-green-200 inline-flex items-center"><i class="fa-solid fa-circle-check mr-1.5"></i>Completado (${comp}/${tot})</span>`;
        } else {
            badgeProgreso = `<span class="bg-gray-100 text-gray-500 font-bold px-2.5 py-1 rounded-lg text-[10px] border border-gray-200">En Espera (0/${tot})</span>`;
        }

        const nroMostrar = row.Nro_Atencion || row.Nro_Cuenta || row.Nro_Movimiento;

        html += `
            <tr class="hover:bg-gray-50 border-b border-gray-200 text-xs font-mono">
                <td class="p-3 text-center">
                    <span class="font-black text-blue-700 text-xs block">${nroMostrar}</span>
                    <span class="text-gray-400 text-[10px] block">#${row.Nro_Movimiento}</span>
                </td>
                <td class="p-3 text-gray-600 text-[11px]">${row.hora_format || '---'}</td>
                <td class="p-3"><span class="font-bold text-blue-700 block text-[11px]"><i class="fa-solid fa-id-card mr-1 text-[10px]"></i>${row.DNI}</span><span class="text-gray-400 block text-[10px]">HC: ${row.HC}</span></td>
                <td class="p-3 font-bold uppercase text-gray-900 font-sans tracking-tight">${row.Nombre_Lab}</td>
                <td class="p-3"><span class="border px-2 py-0.5 rounded text-[10px] font-bold ${servClass}">${serv}</span></td>
                <td class="p-3 text-center">${badgeEstado}</td>
                <td class="p-3 text-center">${badgeProgreso}</td>
                <td class="p-3 text-center">
                    <button type="button" onclick="verDetalleLecturaMedico(${row.Nro_Movimiento})" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-1.5 rounded-lg shadow transition flex items-center justify-center space-x-1 mx-auto cursor-pointer" 
                            title="Ver Resultados">
                        <i class="fa-solid fa-eye text-xs"></i>
                        <span class="text-[11px]">Ver</span>
                    </button>
                </td>
            </tr>`;
    });

    tbody.innerHTML = html;
}

// 3. Modal clínico de Solo Lectura con inyección de analitos (Tailwind v2.2.19 Compliant)
async function verDetalleLecturaMedico(idMovimiento) {
    const contenedor = document.getElementById("medCuerpoAnalitos");
    contenedor.innerHTML = `<div class="p-8 text-center text-gray-400 font-mono text-xs"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Cargando analitos clínicos de la base de datos...</div>`;
    document.getElementById("modalLecturaMedico").classList.remove("hidden");

    try {
        const res = await fetch(`/laboratorio/public/api/seguimiento/detalle?idMovimiento=${idMovimiento}`);
        const json = await res.json();

        if (json.status !== 'success' || !json.data) {
            contenedor.innerHTML = `<div class="p-6 text-center text-red-500 font-bold">No se pudo cargar la orden.</div>`;
            return;
        }

        datosOrdenActiva = json.data;
        const cab = datosOrdenActiva.cabecera;

        document.getElementById("medPaciente").textContent = cab.paciente;
        document.getElementById("medDni").textContent = `DNI: ${cab.dni} | HC: ${cab.hc}`;
        document.getElementById("medCuenta").textContent = `${cab.idAtencion || cab.cuenta} (#${cab.idMovimiento})`;
        document.getElementById("medSeguro").textContent = cab.seguro;
        document.getElementById("medServicio").textContent = `${cab.servicio} / ${cab.cama}`;
        document.getElementById("medMedico").textContent = cab.medico;

        const examenes = datosOrdenActiva.examenes || [];
        if (examenes.length === 0) {
            contenedor.innerHTML = `<div class="p-6 text-center text-gray-400 font-bold">No hay exámenes registrados para esta orden.</div>`;
            return;
        }

        let html = '';
        examenes.forEach(ex => {
            const tieneValores = ex.analitos && ex.analitos.length > 0;

            html += `
                <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm bg-white mb-4">
                    <div class="bg-gray-100 px-4 py-2 border-b border-gray-200 flex flex-wrap justify-between items-center gap-2">
                        <span class="font-bold text-gray-800 uppercase flex items-center text-xs">
                            <i class="fa-solid fa-vial text-green-600 mr-2"></i> ${ex.examen}
                        </span>
                        <div class="flex items-center space-x-2">
                            ${tieneValores 
                                ? `<span class="bg-green-100 text-green-800 font-bold px-2 py-0.5 rounded text-xs inline-flex items-center"><i class="fa-solid fa-check mr-1"></i>Completado</span>` 
                                : `<span class="bg-yellow-100 text-yellow-800 font-bold px-2 py-0.5 rounded text-xs inline-flex items-center"><i class="fa-solid fa-clock mr-1"></i>Pendiente / En Incubación</span>`
                            }
                            ${tieneValores 
                                ? `<button type="button" onclick="imprimirReporteMedico(${ex.idProductoCPT})" class="bg-red-600 hover:bg-red-700 text-white font-bold px-3 py-1 rounded-md text-xs shadow transition flex items-center cursor-pointer focus:outline-none"><i class="fa-solid fa-print mr-1"></i><span>Imprimir A4</span></button>` 
                                : ''
                            }
                        </div>
                    </div>`;

            if (tieneValores) {
                html += `
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                                <tr>
                                    <th class="p-2.5 font-bold">Analito / Componente</th>
                                    <th class="p-2.5 text-center w-40 font-bold">Resultado Obtenido</th>
                                    <th class="p-2.5 text-center w-48 font-bold">Valores Referenciales</th>
                                    <th class="p-2.5 text-center w-36 font-bold">Método</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 font-mono text-xs">`;

                ex.analitos.forEach(a => {
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="p-2.5 font-bold text-gray-700 uppercase">${a.analito}</td>
                            <td class="p-2.5 text-center font-bold text-blue-700 bg-blue-50">${a.resultado}</td>
                            <td class="p-2.5 text-center text-gray-500">${a.referencial}</td>
                            <td class="p-2.5 text-center text-gray-400 text-xs">${a.metodo}</td>
                        </tr>`;
                });

                html += `</tbody></table></div>`;
            } else {
                html += `<div class="p-4 text-center text-gray-400 text-xs font-mono">Muestra recepcionada en laboratorio. Pendiente de asentamiento o período de cultivo biológico.</div>`;
            }

            html += `</div>`;
        });

        contenedor.innerHTML = html;

    } catch (e) {
        contenedor.innerHTML = `<div class="p-6 text-center text-red-500 font-bold">Error al cargar datos clínicos.</div>`;
    }
}

function cerrarModalLectura() {
    document.getElementById("modalLecturaMedico").classList.add("hidden");
}

// 4. Reimpresión A4 oficial desde el portal médico
function imprimirReporteMedico(idCpt) {
    if (!datosOrdenActiva) return;

    const cab = datosOrdenActiva.cabecera;
    const examenObj = (datosOrdenActiva.examenes || []).find(e => e.idProductoCPT === idCpt) || datosOrdenActiva.examenes[0];

    if (!examenObj || !examenObj.analitos || examenObj.analitos.length === 0) {
        Swal.fire({ icon: 'info', title: 'Sin resultados', text: 'Este análisis aún no tiene resultados registrados.' });
        return;
    }

    let resultadosHTML = '';
    examenObj.analitos.forEach(a => {
        resultadosHTML += `
            <tr>
                <td style="padding: 6px 10px; border-bottom: 1px dotted #000; font-weight: bold;">${a.analito}</td>
                <td style="padding: 6px 10px; border-bottom: 1px dotted #000; text-align: center; color: #1d4ed8; font-weight: bold; font-size: 12px;">${a.resultado}</td>
                <td style="padding: 6px 10px; border-bottom: 1px dotted #000; text-align: center; color: #475569; font-size: 10px;">${a.referencial}</td>
            </tr>`;
    });

    const infoBase = {
        paciente:    cab.paciente,
        dni:         cab.dni,
        hc:          cab.hc,
        servicio:    cab.servicio,
        cama:        cab.cama,
        diagnostico: cab.diagnostico,
        edad:        cab.edad,
        medico:      cab.medico,
        seguro:      cab.seguro,
        responsable: examenObj.responsable || 'TECNÓLOGO DE TURNO'
    };

    if (typeof ImpresionLaboratorio !== 'undefined' && typeof ImpresionLaboratorio.reporteA4 === 'function') {
        ImpresionLaboratorio.reporteA4(cab.idMovimiento, infoBase, resultadosHTML, examenObj.examen);
    } else {
        alert("El módulo de impresión no está disponible.");
    }
}