<div class="main-wrapper">
    <div class="container-fluid px-4 pt-3">
        
        <div class="card shadow-sm mb-4" style="border-top: 4px solid #ffc107; border-radius: 10px;">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold" style="color: #0072bc;">
                    <i class="fa-solid fa-magnifying-glass me-2"></i> Buscar Historial del Paciente
                </h5>
                <div class="input-group input-group-lg mt-3">
                    <input type="text" id="inputBusqueda" class="form-control border-primary" 
                           placeholder="Escriba nombre del paciente y presione Enter..." 
                           style="background-color: #fffde7; font-size: 1.1rem;">
                    <button class="btn btn-primary px-5 shadow-sm" onclick="buscarHistorial()">
                        <i class="fa-solid fa-search me-2"></i> BUSCAR
                    </button>
                </div>
                <small class="text-muted mt-2 d-block">
                    <i class="fa-solid fa-circle-info me-1"></i> Puede buscar por apellidos o nombres completos.
                </small>
            </div>
        </div>

        <div class="card shadow-sm border-0" style="border-radius: 10px; overflow: hidden;">
            <div class="card-header d-flex justify-content-between align-items-center py-3" 
                 style="background-color: #0072bc; color: white;">
                <h6 class="mb-0 fw-bold">
                    <i class="fa-solid fa-hospital-user me-2"></i> PACIENTES ATENDIDOS HOY
                </h6>
                <span class="badge rounded-pill bg-warning text-dark px-3 fw-bold">
                    <i class="fa-solid fa-clock fa-spin-pulse me-1"></i> EN TIEMPO REAL
                </span>
            </div>
            <div class="card-body bg-white p-4">
                <div class="table-responsive">
                    <table id="tablaAtenciones" class="table table-hover align-middle w-100">
                        <thead class="table-light">
                            <tr class="text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                                <th class="border-bottom-0">Hora</th>
                                <th class="border-bottom-0">Nombre del Paciente</th>
                                <th class="border-bottom-0">Servicio de Procedencia</th>
                                <th class="border-bottom-0">Médico Solicitante</th>
                                <th class="text-center border-bottom-0">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tablaAtencionesHoy">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHistorial" tabindex="-1" aria-labelledby="tituloModal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white border-0" style="background-color: #0072bc;">
                <h5 class="modal-title fw-bold" id="tituloModal">
                    <i class="fa-solid fa-file-medical me-2"></i> HISTORIAL DE EXÁMENES SIGH
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light p-4" id="cuerpoModal">
                </div>
            <div class="modal-footer bg-white border-top-0">
                <button type="button" class="btn btn-secondary fw-bold px-4" data-bs-dismiss="modal">CERRAR</button>
            </div>
        </div>
    </div>
</div>