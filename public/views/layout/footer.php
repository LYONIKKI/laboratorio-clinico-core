<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>


<script>
    $(document).ready(function() {
        const path = window.location.pathname;

        // --- LÓGICA DE RUTAS ---
        if (path.includes('pacientes')) {
            cargarPacientesHoy();
            // Evento Enter en Buscador de pacientes
            $('#inputBusqueda').on('keypress', function(e) {
                if (e.which == 13) buscarHistorial();
            });
        }
        
        if (path.includes('solicitudes')) {
            // Carga inicial de solicitudes
            actualizarSolicitudes(); 
        }

        if (path.includes('panel')) {
            if (typeof actualizarPanel === "function") actualizarPanel();
        }
    });

    // --- MÓDULO SOLICITUDES (OPTIMIZADO CON DNI Y HC) ---
   function actualizarSolicitudes() {
    if ($('#busquedaRapida').length > 0) $('#busquedaRapida').val('');

    $('#tbodySolicitudes').html('<tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>');

    if ($.fn.DataTable.isDataTable('#tablaSolicitudes')) {
        $('#tablaSolicitudes').DataTable().destroy();
    }

    let modoFiltro = $('#filtroTiempo').val() || '3';

    $.getJSON('/laboratorio/public/api/get-solicitudes-hoy', { rango: modoFiltro }, function(data) {
        let html = '';
        
        if (!Array.isArray(data) || data.length === 0) {
            html = '<tr><td colspan="8" class="text-center py-4 text-muted">No se encontraron solicitudes pendientes en el rango de control.</td></tr>';
            $('#tbodySolicitudes').html(html);
            $('#contador-solicitudes').text('0 Solicitudes');
        } else {
            data.forEach(row => {
                let estadoLimpio = (row.Estado || 'PENDIENTE').toUpperCase().trim();

                let badgeEstado = '';
                if (estadoLimpio === 'ENTREGADO') {
                    badgeEstado = '<span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i> ENTREGADO</span>';
                } else {
                    badgeEstado = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold px-2 py-1"><i class="fa-solid fa-clock me-1"></i> PENDIENTE</span>';
                }

                let btnClass = '';
                let btnIcon = '';
                let btnTitle = '';

                if (estadoLimpio === 'ENTREGADO') {
                    btnClass = 'btn-success';
                    btnIcon = 'fa-solid fa-arrow-rotate-left';
                    btnTitle = 'Revertir orden a Pendiente';
                } else {
                    btnClass = 'btn-outline-success';
                    btnIcon = 'fa-solid fa-file-circle-check';
                    btnTitle = 'Confirmar entrega de Resultados';
                }

                let servicio = row.Servicio_Procedencia ? row.Servicio_Procedencia.trim().toUpperCase() : 'CONSULTA EXTERNA';
                let badgeClass = 'bg-light text-dark border'; 
                if (servicio.includes('HOSPITALIZACION') || servicio.includes('UCI') || servicio.includes('PISO')) {
                    badgeClass = 'bg-danger-subtle text-danger fw-bold border border-danger-subtle';
                } else if (servicio.includes('EMERGENCIA')) {
                    badgeClass = 'bg-warning-subtle text-warning-emphasis fw-bold border border-warning-subtle';
                }

                let badgeDNI = row.DNI ? `<span class="badge bg-light text-primary border border-primary-subtle mb-1 px-2 py-1" style="font-size: 0.85rem;"><i class="fa-solid fa-id-card me-1"></i> ${row.DNI}</span>` : '<span class="badge bg-light text-danger border border-danger-subtle mb-1 px-2 py-1" style="font-size: 0.85rem;">S. DNI</span>';
                let badgeHC = row.HC ? `<span class="badge bg-light text-success border border-success-subtle px-2 py-1" style="font-size: 0.85rem;"><i class="fa-solid fa-file-medical me-1"></i> HC: ${row.HC}</span>` : '<span class="badge bg-light text-warning border border-warning-subtle px-2 py-1" style="font-size: 0.85rem;">S. HC</span>';

                let fechaHoraRow = row.hora_format || row.Hora_Format || '---';

                html += `
                <tr>
                    <td class="ps-4 fw-bold text-dark font-monospace">#${row.Nro_Movimiento}</td>
                    <td class="text-primary fw-bold font-monospace text-nowrap" style="font-size: 0.8rem;">
                        <i class="fa-regular fa-calendar-days text-muted me-1"></i> ${fechaHoraRow}
                    </td>
                    <td>
                        <div class="d-flex flex-column font-monospace">
                            ${badgeDNI}
                            ${badgeHC}
                        </div>
                    </td>
                    <td class="fw-bold text-uppercase" style="font-size: 0.75rem; color: #1a202c; letter-spacing: 0.3px;">${row.Nombre_Lab}</td>
                    <td>
                        <span class="badge ${badgeClass} text-uppercase px-2 py-1 mb-1 d-inline-block" style="font-size: 0.62rem;">
                            <i class="fa-solid fa-hospital-user me-1"></i> ${servicio}
                        </span>
                    </td>
                    <td class="text-center align-middle font-monospace" style="font-size: 0.72rem;">
                        ${badgeEstado}
                    </td>
                    <td class="text-center align-middle">
                        <span class="badge rounded-pill bg-info text-dark px-3 shadow-sm font-monospace" style="font-size: 0.72rem;">${row.Cantidad_Examenes} items</span>
                    </td>
                    <td class="text-center align-middle">
                        <div class="d-flex justify-content-center gap-1">
                            <button class="btn btn-sm btn-outline-primary shadow-sm rounded-circle" 
                                    onclick="verExamenesOrden(${row.Nro_Movimiento}, '${servicio.split("'").join("\\'")}')" title="Ver Detalle de Orden">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                            <button class="btn btn-sm ${btnClass} shadow-sm rounded-circle" 
                                    onclick="conmutarEstadoEntrega(${row.Nro_Movimiento}, '${estadoLimpio}')" title="${btnTitle}">
                                <i class="${btnIcon}"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning shadow-sm rounded-circle" 
                                    onclick="editarOrden(${row.Nro_Movimiento})" title="Editar Orden">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger shadow-sm rounded-circle" 
                                    onclick="eliminarOrden(${row.Nro_Movimiento})" title="Eliminar Orden">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
            });
            
            $('#tbodySolicitudes').html(html);
            $('#contador-solicitudes').text(data.length + ' Solicitudes');

            $('#tablaSolicitudes').DataTable({
                language: {
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
                    "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
                    "infoFiltered": "(filtrado de _MAX_ entradas totales)",
                    "zeroRecords": "No se encontraron resultados",
                    "paginate": { "first": "Primero", "last": "Último", "next": "Siguiente", "previous": "Anterior" }
                },
                pageLength: 10,
                order: [[1, 'desc']],
                dom: 'lrtip'
            });
        }
    }).fail(function(jqXHR) {
        console.error("Error crítico:", jqXHR.responseText);
        $('#tbodySolicitudes').html('<tr><td colspan="8" class="text-center text-danger py-4 font-monospace small"><i class="fa-solid fa-triangle-exclamation me-2"></i> Error al sincronizar registros de microbiología con SQL Server.</td></tr>');
    });
}

    function conmutarEstadoEntrega(idMovimiento, estadoActual) {
        if (!idMovimiento) return;
        
        let mensajeConfirmacion = (estadoActual === 'ENTREGADO') 
            ? `¿Deseas revertir la orden #${idMovimiento} y marcarla nuevamente como PENDIENTE?`
            : `¿Confirmar la entrega definitiva de resultados para la orden #${idMovimiento}?`;
            
        if (confirm(mensajeConfirmacion)) {
            // SOLUCIÓN: Quitamos el '/api' si tu Slim no usa ese prefijo intermedio gggg
            $.post('/laboratorio/public/cambiar-estado-entrega', { id: idMovimiento }, function(res) {
                if (res.success) {
                    console.log(`[SIGH] Orden #${idMovimiento} conmutada`);
                    actualizarSolicitudes();
                } else {
                    alert('Error al procesar el cambio de estado.');
                }
            }, 'json').fail(function() {
                alert('Error crítico de comunicación con el servidor.');
            });
        }
    }

    function editarOrden(idMovimiento) {
        alert('Abriendo editor para la orden #' + idMovimiento);
        // Aquí puedes levantar otro modal con los inputs cargados dinámicamente gggg
    }

    function eliminarOrden(idMovimiento) {
        if(confirm('¿Está seguro de anular la orden #' + idMovimiento + '? Esta acción es crítica.')) {
            // Tu lógica de borrado AJAX
        }
    }

    // --- FUNCIÓN PARA LA LUPA (DETALLE DE EXÁMENES DE LA ORDEN) ---
function verExamenesOrden(idMovimiento, servicioProcedencia) {
    if (!idMovimiento) return;

    let servicio = servicioProcedencia ? servicioProcedencia.trim().toUpperCase() : 'CONSULTA EXTERNA';

    $('#detIdMovimiento').val(idMovimiento);
    $('#detPaciente').text('CARGANDO...');
    $('#detDni').text('DNI: ---');
    $('#detEdad').text('EDAD: ---'); 
    $('#detProcedencia').text(servicio);
    $('#detMedico').text('BUSCANDO MÉDICO SOLICITANTE...'); 
    $('#detHcValue').val('---');
    $('#listaExamenesSolicitud').html('<div class="text-center p-4"><div class="spinner-border text-primary spinner-border-sm" role="status"></div><br><small class="text-muted">Buscando datos...</small></div>');
    
    if (servicio.includes('HOSPITALIZACION') || servicio.includes('UCI') || servicio.includes('PISO')) {
        $('#bloqueHospitalizacion').removeClass('d-none');
    } else {
        $('#bloqueHospitalizacion').addClass('d-none');
        $('#detCamaInput').val('');
        $('#detCuartoInput').val('');
    }

    $('#modalDetalleSolicitud').modal('show');

    $.getJSON('/laboratorio/public/api/get-examenes-solicitud', { id: idMovimiento }, function(data) {
        if(!data || data.length === 0) return;

        let maestro = Array.isArray(data) ? data[0] : data;
        let listaAnalitos = Array.isArray(data) ? data : (data.examenes || []);

        $('#detPaciente').text(maestro.NombrePaciente || maestro.paciente || 'PACIENTE NO ENCONTRADO');
        $('#detDni').text('DNI: ' + (maestro.DNI || maestro.dni || '---'));
        $('#detMedico').text(maestro.medico || maestro.MedicoSolicitante || 'POR ASIGNAR');
        $('#detFecha').text(maestro.FechaRecepcion || maestro.fecha || '---');
        $('#detHcValue').val(maestro.HC || maestro.hc || '---'); 

        // 🛡️ CAPTURA MAESTRA DE LA CUENTA ATENCIÓN gggg
        // La guardamos en el input oculto del modal para usarla de respaldo global
        let cuentaAtencionMaestra = maestro.IdCuentaAtencion || maestro.idCuentaAtencion || 0;
        $('#detIdCuentaAtencion').val(cuentaAtencionMaestra);

        let fechaNac = maestro.FechaNacimiento || maestro.fecha_nacimiento || maestro.FECHANACIMIENTO || null;
        if (fechaNac) {
            let edadFormateada = calcularEdadClinica(fechaNac);
            $('#detEdad').text(`EDAD: ${edadFormateada}`); 
        } else {
            $('#detEdad').text(`EDAD: S. FECHA`);
        }

        if (maestro.NroCama && maestro.NroCama !== '---') {
            $('#detCamaInput').val(maestro.NroCama);
        } else {
            $('#detCamaInput').val('');
        }

        if (maestro.ProcedenciaServicio && maestro.ProcedenciaServicio !== 'CONSULTA EXTERNA') {
            $('#detCuartoInput').val(maestro.ProcedenciaServicio);
        } else {
            $('#detCuartoInput').val('');
        }

        let html = '';

        if (listaAnalitos && listaAnalitos.length > 0) {
            listaAnalitos.forEach(ex => {
                try {
                    // 1. Normalización de nombres
                    let examenNombreRaw = ex.Examen || ex.nombre_examen || ex.Nombre || ex.nombre || '';
                    let examenNombre = examenNombreRaw.trim();
                    
                    // 2. Lógica de Validación
                    let estaValidado = false;
                    if (ex.estado_entrega === 'VALIDADO' || ex.validado == 1 || ex.Estado === 'V') {
                        estaValidado = true;
                    }
                    
                    // 3. UI Dinámica
                    let checkHtml = estaValidado 
                        ? `<i class="fa-solid fa-circle-check text-success ms-2 shadow-sm" style="font-size: 1rem;" title="Resultado Validado y Entregado"></i>` 
                        : '';

                    let btnClass = estaValidado ? 'btn-outline-success' : 'btn-warning text-dark';
                    let btnIcon  = estaValidado ? 'fa-eye' : 'fa-pen-to-square';
                    let btnText  = estaValidado ? 'Ver' : 'Digitar';
                    let borderClass = estaValidado ? 'border-success' : 'border-info';
                    let iconColor = estaValidado ? 'text-success' : 'text-primary';

                    // Sanitización de comillas
                    let nombreSanitizado = examenNombre.split("'").join("\\'");
                    
                    // 4. Construcción del Item - ELIMINADO EL TERCER PARÁMETRO FANTASMA gggg
                    html += `
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-start border-4 ${borderClass} mb-1 shadow-sm">
                        <div class="d-flex flex-column">
                            <div class="fw-bold text-dark" style="font-size: 0.9rem;">
                                <i class="fa-solid fa-vial ${iconColor} me-2"></i> ${examenNombre}
                            </div>
                            ${ex.Observacion ? `<small class="text-muted ms-4"><i class="fa-solid fa-comment-dots me-1"></i> ${ex.Observacion}</small>` : ''}
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="position-relative d-flex align-items-center">
                                <button class="btn btn-xs ${btnClass} fw-bold px-2 py-1 shadow-xs" 
                                        style="font-size: 0.65rem; border-radius: 4px;"
                                        onclick="digitarOrden(${idMovimiento}, '${nombreSanitizado}')"
                                        title="${estaValidado ? 'Revisar resultado' : 'Ingresar resultados'}">
                                    <i class="fa-solid ${btnIcon} me-1"></i> ${btnText}
                                </button>
                                ${checkHtml}
                            </div>
                            <span class="badge bg-primary text-white rounded-pill px-3 shadow-sm" style="font-size: 0.7 roll;">Cant: ${ex.cantidad || 1}</span>
                        </div>
                    </li>`;
                } catch (err) {
                    console.error("Error crítico renderizando analito gggg:", err);
                }
            });
        } else {
            html = `
                <div class="text-center p-4 border rounded bg-light">
                    <i class="fa-solid fa-magnifying-glass text-muted fs-4 mb-2"></i>
                    <p class="text-muted small mb-0">No se encontraron exámenes registrados para esta orden.</p>
                </div>`;
        }

        // Inyectamos la lista en el búnker gggg
        $('#listaExamenesSolicitud').html(html);

    }).fail(function(jqXHR) {
        console.error("Error en get-examenes-solicitud:", jqXHR.responseText);
        $('#detPaciente').text('ERROR DE CARGA');
        $('#listaExamenesSolicitud').html('<li class="list-group-item text-danger text-center"><i class="fa-solid fa-circle-exclamation me-2"></i> No se pudo conectar con el servidor SIGH.</li>');
    });
}

function ejecutarEntregaFinal() {
    // 1. Capturamos el ID de la orden (desde el primer input disponible)
    let primerInput = $('.input-resultado').first();
    let idOrdenActual = primerInput.data('orden');
    
    // 2. Capturamos el ID del movimiento del modal azul para el refresco posterior
    let idMovimiento = $('#detIdMovimiento').val(); 

    // Validación de seguridad por si el DOM se puso lento gggg
    if (!idOrdenActual) {
        console.error("Error: No se detectó el ID de la orden para la entrega.");
        return;
    }

    // 3. Confirmación clínica (Evita entregas por error gggg)
    if (!confirm("¿Desea cerrar y entregar este resultado de forma definitiva?\nUna vez entregado, el examen se marcará como VALIDADO.")) {
        return;
    }

    // 4. GUARDADO PREVIO: Aseguramos que lo que está en pantalla se asiente en la BD
    // Así no hay pérdida de datos al momento de la entrega.
    procesarGuardadoBloqueManual();

    // 5. SEÑAL AL BÚNKER: Disparamos al endpoint que creaste en index.php
    $.post('/laboratorio/public/api/validar-entrega', { 
        idOrden: idOrdenActual 
    }, function(res) {
        
        console.log("Respuesta del servidor:", res.message);

        // 6. CIERRE DE INTERFAZ: Escondemos el modal verde de digitación
        $('#modalDigitacion').modal('hide');

        // 7. REFRESCO REACTIVO: Volvemos a cargar la lista de exámenes del paciente
        // Esto hará que el forEach detecte el estado 'VALIDADO' y pinte el Check verde.
        if (typeof verExamenesOrden === 'function' && idMovimiento) {
            // Un pequeño delay de 300ms para dejar que SQL Server asiente el cambio gggg
            setTimeout(function() {
                verExamenesOrden(idMovimiento);
                console.log("¡Listo! Examen entregado y Check visual activado en el modal azul.");
            }, 300);
        }

    }).fail(function(jqXHR) {
        console.error("Falla en la entrega:", jqXHR.responseText);
        alert('Error al intentar validar la entrega. Por favor, verifique la conexión con el búnker.');
    });
}


function calcularEdadClinica(fechaNacimientoStr) {
    if (!fechaNacimientoStr) return "---";
    
    let hoy = new Date();
    let cumple = new Date(fechaNacimientoStr);
    
    if (isNaN(cumple.getTime())) return "---";

    let anos = hoy.getFullYear() - cumple.getFullYear();
    let mesDiferencia = hoy.getMonth() - cumple.getMonth();
    let diaDiferencia = hoy.getDate() - cumple.getDate();

    if (mesDiferencia < 0 || (mesDiferencia === 0 && diaDiferencia < 0)) {
        anos--;
    }

    if (anos > 0) {
        return anos + (anos === 1 ? " AÑO" : " AÑOS");
    }

    let meses = (hoy.getFullYear() - cumple.getFullYear()) * 12 + hoy.getMonth() - cumple.getMonth();
    if (diaDiferencia < 0) {
        meses--;
    }

    if (meses > 0) {
        return meses + (meses === 1 ? " MES" : " MESES");
    }

    let diferenciaTiempo = hoy.getTime() - cumple.getTime();
    let dias = Math.floor(diferenciaTiempo / (1000 * 60 * 60 * 24));

    if (dias <= 0) {
        return "RECIÉN NACIDO";
    }
    return dias + (dias === 1 ? " DÍA" : " DÍAS");
}

    // --- MÓDULO PACIENTES (ATENCIONES DE HOY) ---
    function cargarPacientesHoy() {
        $.get('/laboratorio/public/api/get-atenciones-hoy', function(data) {
            let html = '';
            data.forEach(row => {
                html += `
                <tr>
                    <td class="fw-bold text-primary">${row.Hora_Format}</td>
                    <td>${row.Paciente}</td>
                    <td><small class="text-muted">${row.Servicio}</small></td>
                    <td><small>${row.Medico}</small></td>
                    <td class="text-center">
                        <button onclick="verHistorial('${row.Paciente}')" class="btn btn-sm btn-warning shadow-sm">
                            <i class="fa-solid fa-eye"></i> Pruebas
                        </button>
                    </td>
                </tr>`;
            });

            if ($.fn.DataTable.isDataTable('#tablaAtenciones')) {
                $('#tablaAtenciones').DataTable().destroy();
            }

            $("#tablaAtencionesHoy").html(html);

            $('#tablaAtenciones').DataTable({
                language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" },
                order: [[0, 'desc']],
                pageLength: 10
            });
        });
    }

    function verHistorial(nombre) {
        // Si existe el input de búsqueda, le asignamos el nombre para que el modal funcione
        if ($('#inputBusqueda').length > 0) {
            $('#inputBusqueda').val(nombre);
        }
        buscarHistorial(nombre);
    }

    // --- FUNCIÓN BUSCAR HISTORIAL GENERAL (CON FIX DE SEGURIDAD) ---
    function buscarHistorial(nombreAlternativo = null) {
        let input = $('#inputBusqueda');
        let query = (input.length > 0 && input.val().length > 0) ? input.val() : nombreAlternativo;
        
        if(!query || query.length < 3) {
            return alert("Por favor, ingrese al menos 3 caracteres (DNI o Apellidos)");
        }

        $('#cuerpoModal').html(`
            <div class="text-center p-5">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <br><span class="text-muted fw-bold">Consultando historial en SIGH...</span>
            </div>
        `);
        $('#modalHistorial').modal('show');

        $.get('/laboratorio/public/api/get-paciente-detalle', { q: query }, function(data) {
            let html = '';
            if (!data || Object.keys(data).length === 0) {
                html = '<div class="alert alert-warning m-3 border-0 shadow-sm">No se encontraron registros históricos.</div>';
            } else {
                for (let fecha in data) {
                    html += `
                    <div class="card mb-3 border-0 shadow-sm overflow-hidden">
                        <div class="card-header bg-primary-subtle d-flex justify-content-between align-items-center border-0">
                            <span class="fw-bold text-primary"><i class="fa-solid fa-calendar-day me-2"></i> ATENCIÓN: ${fecha}</span>
                        </div>
                        <ul class="list-group list-group-flush">`;
                    
                    data[fecha].forEach(ex => {
                        html += `
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <div class="d-flex flex-column">
                                <span class="fw-semibold" style="font-size: 0.95rem;">${ex.Descripcion_Examen}</span>
                                <small class="text-muted" style="font-size: 0.75rem;">MOV: #${ex.Nro_Movimiento || 'N/A'}</small>
                            </div>
                            <span class="badge rounded-pill bg-info text-dark shadow-sm" style="font-size: 0.7rem;">${ex.Servicio}</span>
                        </li>`;
                    });
                    html += `</ul></div>`;
                }
            }
            $('#cuerpoModal').html(html);
        });
    }

    function imprimirEtiquetaTermica() {
    const mov = $('#detIdMovimiento').val(); 
    const paciente = $('#detPaciente').text();
    const dni = $('#detDni').text().replace('DNI: ', '');
    
    // Si no jala la HC, intentaremos sacarla del texto de la tabla o el modal
    // Asegúrate que en verExamenesOrden hagas: $('#detHcValue').val(data.hc);
    const hc = $('#detHcValue').val() || '---'; 
    const fecha = $('#detFecha').text();

    const ventanaPrenta = window.open('', 'PRINT', 'height=500,width=700');
    
    ventanaPrenta.document.write(`
        <html>
            <head>
                <style>
                    @page { size: 50mm 25mm; margin: 0; }
                    body { 
                        font-family: Arial, sans-serif; 
                        margin: 0; padding: 1mm; 
                        text-align: center;
                        display: flex; flex-direction: column;
                        align-items: center; justify-content: center;
                    }
                    .paciente { 
                        font-size: 8pt; font-weight: bold; 
                        white-space: nowrap; overflow: hidden; 
                        width: 100%; margin-bottom: 0.5mm;
                    }
                    .info { font-size: 6.5pt; margin-bottom: 1mm; }
                    #barcode { max-width: 45mm; height: 12mm; }
                </style>
            </head>
            <body>
                <div class="paciente">${paciente}</div>
                <div class="info">DNI: ${dni} | HC: ${hc} | ${fecha.split(' ')[0]}</div>
                <svg id="barcode"></svg>
                
                <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
                <script>
                    window.onload = function() {
                        JsBarcode("#barcode", "${mov}", {
                            format: "CODE128",
                            width: 1.3, // Barras más finas para mejor lectura en tubos
                            height: 40,
                            displayValue: true,
                            fontSize: 12,
                            margin: 0
                        });
                        
                        setTimeout(() => { 
                            window.print(); 
                            // Eliminamos window.close() para que no se cierre sola
                        }, 500);
                    };
                <\/script>
            </body>
        </html>
    `);
    ventanaPrenta.document.close();
}

$(document).on('keyup', '#busquedaRapida', function() {
    let valor = $(this).val().toLowerCase();
    
    $("#tbodySolicitudes tr").filter(function() {
        // Buscamos el match en todo el texto de la fila (Movimiento, Paciente, DNI)
        $(this).toggle($(this).text().toLowerCase().indexOf(valor) > -1);
    });

    // Actualizamos el contador visual con las filas que quedan visibles
    let visibles = $("#tbodySolicitudes tr:visible").length;
    $('#contador-solicitudes').text(visibles + ' Encontrados');
});
</script>

<!-- ssripts del modulo seguimiento< -->
<script>
    // public/js/modulos/seguimiento.js (Pegado directamente aquí en el bloque inferior)
// 1. Escuchador de Eventos (Se activa cuando el DOM se termina de renderizar)
$(document).ready(function() {
    // Escuchar evento Enter en el input de DNI dentro de la vista muestras
    $(document).on('keypress', '#dni_seguimiento', function(e) {
        if (e.which == 13) {
            buscarMuestrasPaciente();
        }
    });
});

// 2. FUNCIÓN GLOBAL (Al estar libre y declarada como function, el botón HTML la encuentra al instante)
function buscarMuestrasPaciente() {
    let dniPaciente = $('#dni_seguimiento').val().trim();

    if (!dniPaciente || dniPaciente.length < 3) {
        alert("Por favor, ingrese un número de DNI válido para iniciar el rastreo global.");
        return;
    }

    // Activamos el spinner azul de carga institucional
    $('#loadingSeguimiento').removeClass('d-none');

    // Reset estructural de seguridad para el modal
    $('#histPaciente').text('CARGANDO HISTORIAL...');
    $('#histDni').text(dniPaciente);
    $('#histHc').text('---');
    $('#histEdad').text('---');
    $('#tbodyHistorialPaciente').html('<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div> Buscando récord histórico...</td></tr>');

    // Petición AJAX limpia a tu backend de Slim
    $.getJSON('/laboratorio/public/api/get-historial-paciente', { dni: dniPaciente }, function(data) {
        $('#loadingSeguimiento').addClass('d-none');

        if (!data || !data.historial || data.historial.length === 0) {
            alert("No se encontraron registros de laboratorio asociados al DNI ingresado.");
            return;
        }

        // Rellenamos la cabecera con la info del paciente
        $('#histPaciente').text(data.paciente);
        $('#histDni').text(data.dni);
        $('#histHc').text(data.hc);
        $('#histEdad').text(data.edad);

        // Renderizamos las filas agrupadas por fecha con el toque amarillo institucional
        let html = '';
        let ultimaFecha = ''; // Variable de control para detectar cambios de día

        data.historial.forEach(item => {
            // Extraemos solo la fecha (dd/mm/aaaa) obviando la hora para comparar
            let fechaSolo = item.fecha.split(' ')[0];
            let horaSolo = item.fecha.split(' ')[1] || '---';

            // Si la fecha es diferente a la anterior, inyectamos la cabecera divisoria amarilla
            if (fechaSolo !== ultimaFecha) {
                ultimaFecha = fechaSolo; // Actualizamos el marcador

                html += `
                <tr class="table-warning border-start border-4 border-warning font-monospace">
                    <td colspan="4" class="ps-3 py-2 fw-bold text-dark" style="font-size: 0.8rem; background-color: #fff3cd;">
                        <i class="fa-solid fa-calendar-day me-2 text-warning-emphasis"></i> ATENCIÓN DE FECHA: ${ultimaFecha} 
                        <span class="badge bg-dark text-white ms-2 float-end me-2" style="font-size: 0.65rem;">MOVIMIENTO: #${item.movimiento}</span>
                    </td>
                </tr>`;
            }

            // Inyectamos la fila del examen con el formato limpio sin saturar la fecha
            html += `
            <tr class="align-middle">
                <td class="ps-3 text-muted font-monospace" style="font-size: 0.75rem;">#${item.movimiento}</td>
                <td class="text-primary-emphasis fw-bold font-monospace" style="font-size: 0.8rem;">
                    <i class="fa-solid fa-clock text-secondary me-1"></i> ${horaSolo}
                </td>
                <td class="fw-bold text-dark" style="letter-spacing: 0.2px;">
                    <i class="fa-solid fa-vial text-primary me-2" style="font-size: 0.85rem;"></i> ${item.examen}
                </td>
                <td>
                    <span class="badge text-dark border bg-white shadow-sm" style="font-size: 0.7rem; border-color: #dee2e6!important;">
                        <i class="fa-solid fa-hospital-user text-muted me-1"></i> ${item.servicio}
                    </span>
                </td>
            </tr>`;
        });
        
        $('#tbodyHistorialPaciente').html(html);
        
        // Desplegamos el modal en pantalla
        $('#modalHistorialLaboratorio').modal('show');

    }).fail(function(jqXHR) {
        $('#loadingSeguimiento').addClass('d-none');
        console.error("Error en conexión SIGH:", jqXHR.responseText);
        alert("Error de sincronización con el servidor local del hospital.");
    });
}
</script>

<!-- script para los resultados -->
<script>
// =========================================================================
// 1. DECLARACIÓN GLOBAL (Al inicio de tu tag <script>, fuera de las funciones)
// =========================================================================
let cacheDataResultados = []; // <-- ESTA LÍNEA ES EL TRUCO SÉNIOR DE ORO

$(document).ready(function() {
    // Escuchar el evento Enter en el input de búsqueda de resultados
    $(document).on('keypress', '#buscar_paciente_resultado', function(e) {
        if (e.which == 13) {
            cargarAtencionesResultados();
        }
    });
});

function cargarAtencionesResultados() {
    let criterio = $('#buscar_paciente_resultado').val().trim();
    if (criterio.length < 3) {
        alert("Por favor, ingrese un criterio de búsqueda válido (Mínimo 3 caracteres).");
        return;
    }
    verDetalleResultado(criterio); 
}

// =========================================================================
// 2. FUNCIÓN VER DETALLE (Modificada para guardar los datos en el caché)
// =========================================================================
function verDetalleResultado(idMovimiento) {
    $('#tbodySubPruebasResultados').html('<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div> Sincronizando con base de datos SIGH...</td></tr>');
    $('#modalDetalleResultados').modal('show');

    $.getJSON('/laboratorio/public/api/get-resultados-orden', { id: idMovimiento }, function(data) {
        if (!data || data.length === 0 || data.error) {
            $('#tbodySubPruebasResultados').html('<tr><td colspan="4" class="text-center py-4 text-muted"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i> No se encontraron registros clínicos para este paciente.</td></tr>');
            return;
        }

        // --- AQUÍ GUARDAMOS LA DATA EN EL CACHÉ GLOBAL ANTES DE ITERAR ---
        cacheDataResultados = data; // <-- SIN EL "LET", directo a la variable global

        // Cabecera principal del Modal
        $('#resModalPaciente').text(data[0].NombrePaciente);
        $('#resModalFecha').text(data[0].Fecha_Examen);

        let html = '';
        let ultimaFecha = '';
        let ultimoExamenPrincipal = '';

        data.forEach(item => {
            // GRUPO 1: Cambio de Fecha (Barra Amarilla de Atención Maestro)
            if (item.Fecha_Examen !== ultimaFecha) {
                ultimaFecha = item.Fecha_Examen;
                html += `
                <tr class="table-warning font-monospace fw-bold" style="font-size: 0.85rem;">
                    <td colspan="4" class="text-dark ps-3 py-2 align-middle" style="background-color: #fff3cd; border-left: 5px solid #ffc107;">
                        <i class="fa-solid fa-calendar-check me-2 text-warning-emphasis"></i> FECHA DE ATENCIÓN SIGH: ${ultimaFecha}
                        
                        <div class="float-end d-flex gap-2 align-items-center me-2">
                            <span class="badge bg-dark text-white" style="font-size: 0.65rem;">MOV: #${item.Nro_Movimiento}</span>
                            <button class="btn btn-sm btn-outline-dark py-0 px-2 fw-bold" style="font-size: 0.7rem; border-radius: 4px;" onclick="imprimirTicketResultado('${item.Nro_Movimiento}')">
                                <i class="fa-solid fa-print me-1 text-primary"></i> IMPRIMIR
                            </button>
                        </div>
                    </td>
                </tr>`;
                ultimoExamenPrincipal = ''; 
            }

            // GRUPO 2: Cambio de Examen Principal
            if (item.Examen_Principal !== ultimoExamenPrincipal) {
                ultimoExamenPrincipal = item.Examen_Principal;
                html += `
                <tr class="table-light font-monospace fw-bold text-secondary" style="font-size: 0.8rem;">
                    <td colspan="4" class="ps-4 py-2 bg-light text-uppercase">
                        <i class="fa-solid fa-vial text-primary me-2"></i> ${ultimoExamenPrincipal}
                    </td>
                </tr>`;
            }

            let resultado = item.Resultado_Obtenido ? item.Resultado_Obtenido.trim() : 'Pendiente';
            let textClass = 'text-primary fw-bold';
            
            if (resultado === 'Pendiente' || resultado === '0.00' || resultado === '0') {
                textClass = 'text-muted fw-normal';
            }

            html += `
            <tr class="align-middle">
                <td class="ps-5 text-secondary fw-semibold" style="font-size: 0.85rem;">${item.SubPrueba_Item}</td>
                <td class="text-center ${textClass} font-monospace" style="font-size: 0.9rem;">
                    ${resultado}
                </td>
                <td class="text-center font-monospace text-muted" style="font-size: 0.8rem;">${item.ValorReferencial}</td>
                <td class="text-center">
                    <small class="badge bg-light text-dark border font-monospace d-block" style="font-size: 0.65rem;">${item.Metodo}</small>
                    <small class="text-muted d-block font-monospace" style="font-size: 0.6rem; margin-top: 2px;"><i class="fa-solid fa-clock"></i> ${item.Fecha_Resultado}</small>
                </td>
            </tr>`;
        });

        $('#tbodySubPruebasResultados').html(html);
    }).fail(function(jqXHR) {
        console.error("Error crítico en Resultados SIGH:", jqXHR.responseText);
        $('#tbodySubPruebasResultados').html('<tr><td colspan="4" class="text-center text-danger py-4">Error de red local o VPN.</td></tr>');
    });
}

// =========================================================================
// 3. EVALUACIÓN DE RANGOS
// =========================================================================
function evaluarRangoLaboratorio(valorObtenido, rangoTexto) {
    let num = parseFloat(valorObtenido);
    if (isNaN(num)) return { critico: false, alerta: '' }; 

    let match = rangoTexto.match(/([0-9.]+)\s*-\s*([0-9.]+)/);
    if (match) {
        let min = parseFloat(match[1]);
        let max = parseFloat(match[2]);

        if (num < min) return { critico: true, alerta: ' (L)' }; // Low - Bajo
        if (num > max) return { critico: true, alerta: ' (H)' }; // High - Alto
    }
    return { critico: false, alerta: '' }; 
}

// =========================================================================
// 4. IMPRESIÓN DEL REPORT TICKET
// =========================================================================
function imprimirTicketResultado(nroMovimiento) {
    let analitosOrden = cacheDataResultados.filter(x => x.Nro_Movimiento == nroMovimiento);
    
    if(!analitosOrden || analitosOrden.length === 0) {
        alert("No hay datos disponibles para imprimir de este movimiento.");
        return;
    }

    // EXTRAEMOS TODO DIRECTAMENTE DESDE LA DATA DEL BACKEND gggg
    let paciente = analitosOrden[0].NombrePaciente;
    let fechaResultado = analitosOrden[0].Fecha_Resultado || '---';
    let servicio = analitosOrden[0].Servicio;
    
    // Cambiado: Ahora jalan limpios desde tu query optimizada
    let dni = analitosOrden[0].PacienteDni || '---';
    let hc = analitosOrden[0].PacienteHc || '---';
    let edad = analitosOrden[0].PacienteEdad ? analitosOrden[0].PacienteEdad + ' AÑOS' : '---';
    
    let examenTitulo = analitosOrden[0].Examen_Principal;

    let ventanaImpresion = window.open('', '_blank', 'width=800,height=600');
    
    let htmlPrint = `
    <html>
    <head>
        <title>Reporte_Laboratorio_${dni}</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 30px; color: #333; font-size: 12px; }
            .header-title { text-align: center; font-size: 15px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; color: #000; }
            .header-subtitle { text-align: center; font-size: 11px; color: #555; margin-bottom: 25px; text-transform: uppercase; letter-spacing: 0.5px; }
            
            /* TÍTULO DEL EXAMEN CENTRALIZADO */
            .examen-maestro-title { text-align: center; font-size: 13px; font-weight: bold; text-transform: uppercase; margin-bottom: 15px; color: #0072bc; border-bottom: 2px solid #0072bc; padding-bottom: 5px; }
            
            .info-box { width: 100%; border: 1px solid #bbb; padding: 12px; border-radius: 6px; margin-bottom: 25px; background-color: #fafafa; }
            .info-table { width: 100%; }
            .info-table td { padding: 4px 6px; vertical-align: middle; font-size: 11.5px; }
            
            .results-table { width: 100%; border-collapse: collapse; margin-top: 10px; text-transform: uppercase; }
            .results-table th { border-bottom: 2px solid #222; padding: 9px; text-align: left; font-size: 11px; background-color: #f0f0f0; color: #111; }
            .results-table td { border-bottom: 1px solid #ddd; padding: 8px; font-size: 11px; }
            
            .critico { font-weight: bold; text-decoration: underline; color: #000; }
            .footer-sign { margin-top: 70px; text-align: center; float: right; width: 250px; border-top: 1px solid #333; padding-top: 6px; font-size: 11px; font-weight: bold; }
            @media print { .no-print { display: none; } body { margin: 15px; } }
        </style>
    </head>
    <body>
        <div class="no-print" style="margin-bottom: 25px; text-align: right;">
            <button onclick="window.print();" style="padding: 9px 18px; font-weight: bold; background: #0072bc; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">CONFIRMAR IMPRESIÓN</button>
        </div>

        <div class="header-title">Hospital Regional Daniel Alcides Carrión</div>
        <div class="header-subtitle">Área de Laboratorio, Patología Cl{inica y Banco de Sangre</div>
        
        <div class="examen-maestro-title">EXAMEN REALIZADO: ${examenTitulo}</div>
        
        <div class="info-box">
            <table class="info-table">
                <tr>
                    <td style="width: 15%;"><strong>PACIENTE:</strong></td>
                    <td style="width: 45%; font-size: 12px; font-weight: bold;">${paciente}</td>
                    <td style="width: 15%;"><strong>DNI:</strong></td>
                    <td style="width: 25%; font-family: monospace;">${dni}</td>
                </tr>
                <tr>
                    <td><strong>EDAD:</strong></td>
                    <td>${edad}</td>
                    <td><strong>NRO. H.C.:</strong></td>
                    <td style="font-family: monospace;">${hc}</td>
                </tr>
                <tr>
                    <td><strong>FECHA RESULTADO:</strong></td>
                    <td>${fechaResultado}</td>
                    <td><strong>SERVICIO:</strong></td>
                    <td>${servicio}</td>
                </tr>
            </table>
        </div>

        <table class="results-table">
            <thead>
                <tr>
                    <th style="width: 40%;">COMPONENTE / SUB-PRUEBA</th>
                    <th style="text-align: center; width: 20%;">RESULTADO</th>
                    <th style="text-align: center; width: 20%;">VALOR REFERENCIAL</th>
                    <th style="text-align: center; width: 20%;">MÉTODO / CORRIDA</th>
                </tr>
            </thead>
            <tbody>`;

    analitosOrden.forEach(item => {
        let resultado = item.Resultado_Obtenido ? item.Resultado_Obtenido.trim() : 'PENDIENTE';
        let evaluacion = evaluarRangoLaboratorio(resultado, item.ValorReferencial);
        
        // Formateamos el resultado inyectando el asterisco clínico si sale alterado
        let renderResultado = evaluacion.critico ? `<span class="critico">${resultado}${evaluacion.alerta}</span>` : resultado;

        htmlPrint += `
        <tr>
            <td style="padding-left: 5px; color: #333; font-weight: 500;">${item.SubPrueba_Item}</td>
            <td style="text-align: center; font-size: 11.5px;">${renderResultado}</td>
            <td style="text-align: center; color: #555; font-family: monospace;">${item.ValorReferencial}</td>
            <td style="text-align: center; color: #666; font-size: 10px;">${item.Metodo}</td>
        </tr>`;
    });

    htmlPrint += `
            </tbody>
        </table>

        <div class="footer-sign">
            <br><br>
            Firma y Sello <br> Tecnólogo Médico de Turno
        </div>
    </body>
    </html>`;

    ventanaImpresion.document.write(htmlPrint);
    ventanaImpresion.document.close();
}

</script>


<style>
    #infoPacienteOrden {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}
#detPaciente {
    letter-spacing: 0.5px;
    color: #0072bc;
}

#tbodyHistorialPaciente tr:hover {
    background-color: rgba(0, 114, 188, 0.03) !important; /* Sutil azul al pasar el mouse */
}
#tbodyHistorialPaciente td {
    padding-top: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f3f5;
}
.sticky-top {
    z-index: 1020;
}

</style>
</body>
</html>