<div class="main">
    <div class="container-fluid px-4 pt-3">
        
        <div class="d-flex align-items-center justify-content-between mb-4 bg-white p-3 rounded shadow-sm border-start border-4 border-warning">
            <div>
                <span class="badge bg-warning-subtle text-warning mb-1 text-uppercase fw-bold" style="font-size: 0.75rem;">Control de Almacén</span>
                <h4 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-boxes-stacked text-warning me-2"></i> Gestión de Insumos y Reactivos
                </h4>
                <p class="text-muted small mb-0">Monitoreo de stock mínimo, códigos de lote y alertas de caducidad biológica local en PostgreSQL.</p>
            </div>
            <button class="btn btn-warning fw-bold text-dark px-3 btn-sm shadow-sm" onclick="abrirModalNuevoInsumo()">
                <i class="fa-solid fa-plus-circle me-1"></i> NUEVO INSUMO
            </button>
        </div>

        <div class="card shadow-sm border-0 bg-white">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark" style="background-color: #212529;">
                            <tr style="font-size: 0.8rem; text-transform: uppercase;">
                                <th class="ps-3 py-3">Cód. Interno / Insumo</th>
                                <th class="py-3">Lote</th>
                                <th class="py-3">Presentación</th>
                                <th class="text-center py-3">Stock Actual</th>
                                <th class="text-center py-3">Vencimiento</th>
                                <th class="text-center py-3">Estado</th>
                                <th class="text-center py-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyInsumosLocal" style="font-size: 0.85rem;">
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <div class="spinner-border text-warning spinner-border-sm me-2"></div> Conectando a PostgreSQL...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="modalNuevoInsumo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-flask-vial me-2"></i> Registrar Insumo Clínico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNuevoInsumo" onsubmit="guardarInsumoLocal(event)">
                <div class="modal-body p-4 text-uppercase font-monospace" style="font-size: 0.8rem;">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">Código Interno</label>
                            <input type="text" id="ins_codigo" class="form-control form-control-sm shadow-none" placeholder="EJ: REAC-HEM-01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">Código de Lote</label>
                            <input type="text" id="ins_lote" class="form-control form-control-sm shadow-none" placeholder="EJ: LOT2026X" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-secondary">Nombre del Insumo / Reactivo</label>
                            <input type="text" id="ins_nombre" class="form-control form-control-sm shadow-none" placeholder="EJ: REACTIVO LIOFILIZADO DE HEMOGLOBINA" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-secondary">Presentación</label>
                            <input type="text" id="ins_presentacion" class="form-control form-control-sm shadow-none" placeholder="EJ: FRASCO 500ML, CAJA X 100" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">Stock Inicial</label>
                            <input type="number" id="ins_stock" class="form-control form-control-sm shadow-none" min="0" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">Stock Mínimo Alerta</label>
                            <input type="number" id="ins_minimo" class="form-control form-control-sm shadow-none" min="1" value="10" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-danger">Fecha de Vencimiento (Caducidad)</label>
                            <input type="date" id="ins_vencimiento" class="form-control form-control-sm shadow-none text-danger fw-bold" required>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm fw-bold px-3" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold px-4 text-dark">GUARDAR REACTIVO</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarInsumo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i> Modificar Insumo Clínico</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarInsumo" onsubmit="actualizarInsumoLocal(event)">
                <input type="hidden" id="edit_id">
                
                <div class="modal-body p-4 text-uppercase font-monospace" style="font-size: 0.8rem;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">Código Interno</label>
                            <input type="text" id="edit_codigo" class="form-control form-control-sm shadow-none" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">Código de Lote</label>
                            <input type="text" id="edit_lote" class="form-control form-control-sm shadow-none" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-secondary">Nombre del Insumo / Reactivo</label>
                            <input type="text" id="edit_nombre" class="form-control form-control-sm shadow-none" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">Presentación</label>
                            <input type="text" id="edit_presentacion" class="form-control form-control-sm shadow-none">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">Estado Operativo</label>
                            <select id="edit_estado" class="form-select form-select-sm shadow-none fw-bold">
                                <option value="ACTIVO">ACTIVO</option>
                                <option value="AGOTADO">AGOTADO</option>
                                <option value="VENCIDO">VENCIDO</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">Stock Actual</label>
                            <input type="number" id="edit_stock" class="form-control form-control-sm shadow-none" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">Stock Mínimo</label>
                            <input type="number" id="edit_minimo" class="form-control form-control-sm shadow-none" min="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-danger">Fecha de Vencimiento</label>
                            <input type="date" id="edit_vencimiento" class="form-control form-control-sm shadow-none text-danger fw-bold" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm fw-bold px-3" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">ACTUALIZAR DATOS</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    listarInsumosPostgres();
});

// 1. CHUPAR DATOS DESDE TU API POSTGRESQL gggg
function listarInsumosPostgres() {
    $.getJSON('/laboratorio/public/api/insumos/listar', function(data) {
        let html = '';
        
        if (!data || data.length === 0) {
            html = `<tr><td colspan="7" class="text-center py-4 text-muted"><i class="fa-solid fa-box-open me-2"></i> No hay insumos registrados en el almacén local.</td></tr>`;
            $('#tbodyInsumosLocal').html(html);
            return;
        }

        data.forEach(insumo => {
            // Formateo de alertas de Stock Mínimo
            let stockNum = parseInt(insumo.stock_actual);
            let minNum = parseInt(insumo.stock_minimo);
            let stockBadge = stockNum <= minNum 
                ? `<span class="badge bg-danger-subtle text-danger fw-bold p-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> ${stockNum} (CRÍTICO)</span>`
                : `<span class="badge bg-light text-dark border p-2 fw-semibold" style="font-size:0.85rem; min-width:55px;">${stockNum}</span>`;

            // Formateo dinámico de Alertas de Vencimiento (Días para vencer en PostgreSQL)
            let dias = parseInt(insumo.dias_para_vencer);
            let vencimientoText = insumo.fecha_vencimiento; // Ya viene formateado YYYY-MM-DD
            
            if (dias <= 0) {
                insumo.estado = 'VENCIDO';
            } else if (dias <= 30) {
                vencimientoText += ` <br><small class="text-warning fw-bold"><i class="fa-solid fa-clock"></i> VENCE EN ${dias} DÍAS</small>`;
            }

            // Clases de badge para el Estado
            let estadoClass = 'bg-success';
            if (insumo.estado === 'VENCIDO') estadoClass = 'bg-danger';
            if (insumo.estado === 'AGOTADO') estadoClass = 'bg-dark';

            html += `
            <tr class="align-middle">
                <td class="ps-3">
                    <strong class="text-primary font-monospace">${insumo.codigo_interno}</strong>
                    <div class="text-secondary fw-semibold mt-1" style="font-size:0.85rem;">${insumo.nombre_insumo}</div>
                </td>
                <td class="font-monospace fw-bold">${insumo.lote_codigo}</td>
                <td><small class="text-muted fw-bold">${insumo.presentacion}</small></td>
                <td class="text-center font-monospace">${stockBadge}</td>
                <td class="text-center font-monospace small fw-bold">${vencimientoText}</td>
                <td class="text-center">
                    <span class="badge ${estadoClass} text-uppercase px-2 py-1" style="font-size:0.65rem; letter-spacing:0.5px;">${insumo.estado}</span>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary py-0 px-2 me-1" title="Editar Registro" 
                            onclick="prepararEdicionInsumo('${insumo.id}', '${insumo.codigo_interno}', '${insumo.lote_codigo}', '${insumo.nombre_insumo}', '${insumo.presentacion}', '${insumo.stock_actual}', '${insumo.stock_minimo}', '${insumo.fecha_vencimiento}', '${insumo.estado}')">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger py-0 px-2" title="Eliminar Insumo" 
                            onclick="eliminarInsumoLocal('${insumo.id}', '${insumo.codigo_interno}')">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </td>
            </tr>`;
        });

        $('#tbodyInsumosLocal').html(html);
    }).fail(function() {
        $('#tbodyInsumosLocal').html(`<tr><td colspan="7" class="text-center py-4 text-danger fw-bold"><i class="fa-solid fa-circle-exclamation me-2"></i> Error de sincronización con la base de datos PostgreSQL local.</td></tr>`);
    });
}

// 2. DISPARADOR DEL MODAL NUEVO
function abrirModalNuevoInsumo() {
    $('#formNuevoInsumo')[0].reset(); // Limpia campos heredados gggg
    $('#modalNuevoInsumo').modal('show');
}

// 3. CAPTURA Y ENVÍO POR AJAX (Por implementar la API POST de guardado)
// 3. CAPTURA Y ENVÍO REAL A TU POSTGRESQL LOCAL gggg
function guardarInsumoLocal(e) {
    e.preventDefault(); // Evitamos que la página se recargue

    // Recolectamos las variables limpias de tu modal
    let datosInsumo = {
        codigo: $('#ins_codigo').val().trim(),
        lote: $('#ins_lote').val().trim(),
        nombre: $('#ins_nombre').val().trim(),
        presentacion: $('#ins_presentacion').val().trim(),
        stock: $('#ins_stock').val(),
        minimo: $('#ins_minimo').val(),
        vencimiento: $('#ins_vencimiento').val()
    };

    // Deshabilitamos el botón para evitar múltiples clics por error gggg
    let btnSubmit = $(e.target).find('button[type="submit"]');
    btnSubmit.prop('disabled', true).text('GUARDANDO...');

    // Petición POST asíncrona hacia Slim
    $.post('/laboratorio/public/api/insumos/guardar', datosInsumo, function(res) {
        // Cerramos el modal de Bootstrap de forma limpia
        $('#modalNuevoInsumo').modal('hide');
        
        // Refrescamos la grilla al instante llamando a tu función de PostgreSQL gggg
        listarInsumosPostgres();
        
    }).fail(function(jqXHR) {
        let respuesta = jqXHR.responseJSON;
        alert("Error al registrar: " + (respuesta.error || "Fallo de comunicación de red."));
    }).always(function() {
        // Devolvemos el botón a su estado normal
        btnSubmit.prop('disabled', false).text('GUARDAR REACTIVO');
    });
}

// =========================================================================
// FLUJO EXCLUSIVO DE EDICIÓN (AISLADO DE GUARDAR) gggg
// =========================================================================
function prepararEdicionInsumo(id, codigo, lote, nombre, presentacion, stock, minimo, vencimiento, estado) {
    // Seteamos los datos de la fila de forma atómica gggg
    $('#edit_id').val(id);
    $('#edit_codigo').val(codigo);
    $('#edit_lote').val(lote);
    $('#edit_nombre').val(nombre);
    $('#edit_presentacion').val(presentacion);
    $('#edit_stock').val(stock);
    $('#edit_minimo').val(minimo);
    $('#edit_vencimiento').val(vencimiento);
    $('#edit_estado').val(estado);

    // Desplegamos el modal exclusivo de modificación
    $('#modalEditarInsumo').modal('show');
}

function actualizarInsumoLocal(e) {
    e.preventDefault();

    // Recolectamos los datos de forma tradicional para que viajen como formulario gggg
    let datosEditados = {
        id: parseInt($('#edit_id').val()) || 0,
        codigo: $('#edit_codigo').val().trim(),
        lote: $('#edit_lote').val().trim(),
        nombre: $('#edit_nombre').val().trim(),
        presentacion: $('#edit_presentacion').val().trim(),
        stock: $('#edit_stock').val(),
        minimo: $('#edit_minimo').val(),
        vencimiento: $('#edit_vencimiento').val(),
        estado: $('#edit_estado').val()
    };

    console.log("Enviando datos por POST tradicional:", datosEditados);

    let btnSubmit = $(e.target).find('button[type="submit"]');
    btnSubmit.prop('disabled', true).text('ACTUALIZANDO...');

    // Petición POST directa para saltar cualquier restricción de Apache
    $.post('/laboratorio/public/api/insumos/actualizar', datosEditados, function(res) {
        // Si todo va bien, cerramos el modal y refrescamos la grilla
        $('#modalEditarInsumo').modal('hide');
        listarInsumosPostgres(); 
        
    }).fail(function(jqXHR) {
        let errorMsg = jqXHR.responseJSON ? jqXHR.responseJSON.error : "Fallo de comunicación.";
        alert("Error al actualizar: " + errorMsg);
        
    }).always(function() {
        btnSubmit.prop('disabled', false).text('ACTUALIZAR DATOS');
    });
}
// =========================================================================
// FLUJO EXCLUSIVO DE ELIMINACIÓN
// =========================================================================
function eliminarInsumoLocal(id, codigo) {
    if (!confirm(`¿Está completamente seguro de eliminar el insumo [${codigo}] de PostgreSQL?`)) {
        return;
    }

    $.ajax({
        url: '/laboratorio/public/api/insumos/eliminar/' + id,
        type: 'DELETE',
        success: function(res) {
            listarInsumosPostgres(); // Recarga la grilla al instante gggg
        },
        error: function(jqXHR) {
            alert("Error al eliminar: " + (jqXHR.responseJSON.error || "Fallo del servidor."));
        }
    });
}
</script>