<!-- public/views/modales/modalReporteProductividad.php -->
<div id="modalReportesLab" class="modal-backdrop is-hidden">
    <div class="modal-card">
        
        <!-- Header -->
        <div class="modal-header-blue">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-file-excel" style="font-size: 1.1rem; color: #86efac;"></i>
                <div>
                    <h4>Reportes de Productividad</h4>
                    <p>Extracción asistencial y analítica de datos SIGH</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalReportes()" class="modal-btn-close">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>

        <!-- Formulario -->
        <form id="formReportesProductividad" onsubmit="ejecutarGeneracionReporte(event)" class="modal-body">
            
            <!-- Rango Fechas y Horas -->
            <div>
                <label class="modal-label">
                    <i class="fa-solid fa-clock text-blue-600 mr-1"></i> Rango de Fechas y Horas de Turno
                </label>
                
                <!-- Fila Desde -->
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0.5rem; margin-bottom: 0.4rem;">
                    <div>
                        <span style="font-size: 0.65rem; color: #64748b; font-family: sans-serif; display: block; margin-bottom: 2px;">Fecha Inicio:</span>
                        <input type="date" id="repFechaInicio" required value="<?= date('Y-m-d') ?>" class="modal-input">
                    </div>
                    <div>
                        <span style="font-size: 0.65rem; color: #64748b; font-family: sans-serif; display: block; margin-bottom: 2px;">Hora Inicio:</span>
                        <input type="time" id="repHoraInicio" step="1" required value="00:00:00" class="modal-input">
                    </div>
                </div>

                <!-- Fila Hasta -->
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0.5rem;">
                    <div>
                        <span style="font-size: 0.65rem; color: #64748b; font-family: sans-serif; display: block; margin-bottom: 2px;">Fecha Fin:</span>
                        <input type="date" id="repFechaFin" required value="<?= date('Y-m-d') ?>" class="modal-input">
                    </div>
                    <div>
                        <span style="font-size: 0.65rem; color: #64748b; font-family: sans-serif; display: block; margin-bottom: 2px;">Hora Fin:</span>
                        <input type="time" id="repHoraFin" step="1" required value="23:59:59" class="modal-input">
                    </div>
                </div>
            </div>

            <!-- Tipo de Reporte -->
            <div>
                <label class="modal-label">
                    <i class="fa-solid fa-filter text-blue-600 mr-1"></i> Tipo de Productividad
                </label>
                <div class="modal-radio-box">
                    <label class="modal-radio-item">
                        <input type="radio" name="tipoReporte" value="pacientes" checked>
                        <div>
                            <div class="modal-radio-title">1. Órdenes por Paciente</div>
                            <span class="modal-radio-desc">Movimiento, cuenta, paciente, servicio, médico y total exámenes</span>
                        </div>
                    </label>

                    <label class="modal-radio-item">
                        <input type="radio" name="tipoReporte" value="procedimientos">
                        <div>
                            <div class="modal-radio-title">2. Procedimientos Solicitados por Paciente</div>
                            <span class="modal-radio-desc">Desglose fila por fila con nombre de prueba (CPT), código e importe</span>
                        </div>
                    </label>

                    <label class="modal-radio-item">
                        <input type="radio" name="tipoReporte" value="con_resultados">
                        <div>
                            <div class="modal-radio-title">3. Productividad con Resultados Validados</div>
                            <span class="modal-radio-desc">Prueba, resultado digitado, referencial y profesional que validó</span>
                        </div>
                    </label>
                </div>
            </div>
        </form>

        <!-- Footer -->
        <div class="modal-footer">
            <button type="button" onclick="cerrarModalReportes()" class="btn-ui btn-ui-gray">
                Cancelar
            </button>
            <button type="submit" form="formReportesProductividad" id="btnGenerarReporte" class="btn-ui btn-ui-green">
                <i class="fa-solid fa-file-excel"></i> Descargar Excel
            </button>
        </div>

    </div>
</div>