// public/js/solicitudes/impresion.js
// Generación limpia de tickets térmicos e informe analítico A4 oficial con trazabilidad

const ImpresionLaboratorio = {

    // 1. Etiqueta Térmica de Tubo (Código de Barras)
    etiquetaTermica: function(mov, paciente, dni, hc) {
        const ventana = window.open('', 'PRINT', 'height=500,width=700');
        ventana.document.write(`
            <html>
                <head>
                    <title>Etiqueta_${mov}</title>
                    <style>
                        @page { size: 50mm 25mm; margin: 0; }
                        body { font-family: Arial, sans-serif; margin: 0; padding: 1mm; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; }
                        .paciente { font-size: 8pt; font-weight: bold; white-space: nowrap; overflow: hidden; width: 100%; margin-bottom: 0.5mm; text-transform: uppercase; }
                        .info { font-size: 6.5pt; margin-bottom: 1mm; font-family: monospace; }
                        #barcode { max-width: 45mm; height: 12mm; }
                    </style>
                </head>
                <body>
                    <div class="paciente">${paciente}</div>
                    <div class="info">DNI: ${dni} | HC: ${hc}</div>
                    <svg id="barcode"></svg>
                    <!-- Script local offline con fallback a CDN -->
                    <script src="/laboratorio/public/js/JsBarcode.all.min.js" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js';"><\/script>
                    <script>
                        window.onload = function() {
                            if (typeof JsBarcode !== 'undefined') {
                                JsBarcode("#barcode", "${mov}", { format: "CODE128", width: 1.3, height: 40, displayValue: true, fontSize: 12, margin: 0 });
                            }
                            setTimeout(() => { window.print(); }, 400);
                        };
                    <\/script>
                </body>
            </html>
        `);
        ventana.document.close();
    },

    // 2. Reporte A4 Oficial con Logos Hospitalarios y Firma de Responsable
    reporteA4: function(idMovimiento, infoBase, resultadosHTML, examenMaestro) {
        const ahora = new Date();
        const fechaString = ahora.toLocaleDateString('es-PE');
        const horaString = ahora.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });

        const nombreResponsable = (infoBase.responsable || 'TECNÓLOGO DE TURNO').toUpperCase();
        const medicoSolicitante = (infoBase.medico || 'POR ASIGNAR').toUpperCase();

        const ventana = window.open('', '_blank', 'width=850,height=700');
        ventana.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Reporte_Laboratorio_${idMovimiento}</title>
            <style>
                body { font-family: "Courier New", Courier, monospace; margin: 25px; color: #000; font-size: 12px; line-height: 1.4; }
                .header-table-logos { width: 100%; border-collapse: collapse; margin-bottom: 15px; table-layout: fixed; }
                .td-logo-left { width: 140px; text-align: left; vertical-align: middle; }
                .td-logo-right { width: 140px; text-align: right; vertical-align: middle; }
                .logo-minsa { height: 42px; width: auto; object-fit: contain; }
                .logo-carrion { height: 55px; width: auto; object-fit: contain; }
                .header-center-title { text-align: center; font-family: 'Arial', sans-serif; font-size: 13px; font-weight: bold; line-height: 1.4; }
                .sub-header-bar { width: 100%; text-align: center; font-family: 'Arial', sans-serif; font-size: 13px; font-weight: bold; color: #155724; background-color: #d4edda !important; border: 1.5px solid #c3e6cb; padding: 7px 0; margin-bottom: 15px; text-transform: uppercase; }
                .patient-box { width: 100%; border: 2px solid #000; padding: 12px 10px; margin-bottom: 15px; box-sizing: border-box; }
                .patient-table { width: 100%; border-collapse: collapse; }
                .lbl-clinico { font-weight: bold; font-family: 'Arial', sans-serif; font-size: 11px; white-space: nowrap; }
                .linea-puntos { border-bottom: 1px dotted #000; font-weight: bold; font-size: 12px; padding-left: 5px; text-transform: uppercase; display: block; width: 100%; }
                .results-table { width: 100%; border-collapse: collapse; margin-top: 5px; text-transform: uppercase; }
                .results-table th { background-color: #f2f2f2; padding: 8px; border: 1.5px solid #000; font-size: 11px; font-family: 'Arial', sans-serif; }
                .results-table td { padding: 6px 10px; border-bottom: 1px dotted #000; font-size: 11px; }
                .footer-section { margin-top: 45px; width: 100%; page-break-inside: avoid; }
                @media print { 
                    .no-print { display: none; } 
                    body { margin: 15px; } 
                    .sub-header-bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; } 
                    tr { page-break-inside: avoid; }
                }
            </style>
        </head>
        <body>
            <div class="no-print" style="margin-bottom: 15px; text-align: right;">
                <button onclick="window.print();" style="padding: 10px 20px; font-family: Arial; font-weight: bold; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer;">CONFIRMAR IMPRESIÓN</button>
            </div>

            <table class="header-table-logos">
                <tr>
                    <td class="td-logo-left"><img src="/laboratorio/public/img/logo_minsa.png" class="logo-minsa" alt="MINSA"></td>
                    <td class="header-center-title">MINISTERIO DE SALUD - HOSPITAL REGIONAL<br>DR. DANIEL ALCIDES CARRIÓN GARCÍA</td>
                    <td class="td-logo-right"><img src="/laboratorio/public/img/logo_hospital.png" class="logo-carrion" alt="Hospital Carrión"></td>
                </tr>
            </table>

            <div class="sub-header-bar">RESULTADOS DE: ${examenMaestro}</div>

            <div class="patient-box">
                <table class="patient-table">
                    <tr>
                        <td class="lbl-clinico" style="width: 140px;">Paciente:</td>
                        <td><span class="linea-puntos">${infoBase.paciente || '---'}</span></td>
                        <td class="lbl-clinico" style="width: 100px;">Seguro:</td>
                        <td style="width: 160px;"><span class="linea-puntos">${infoBase.seguro || 'SIS'}</span></td>
                    </tr>
                    <tr style="height: 6px;"></tr>
                    <tr>
                        <td class="lbl-clinico">Diagnóstico:</td>
                        <td><span class="linea-puntos">${infoBase.diagnostico || '---'}</span></td>
                        <td class="lbl-clinico">DNI / HC:</td>
                        <td><span class="linea-puntos">${infoBase.dni || '---'} / ${infoBase.hc || '---'}</span></td>
                    </tr>
                    <tr style="height: 6px;"></tr>
                    <tr>
                        <td class="lbl-clinico">Servicio / Cama:</td>
                        <td><span class="linea-puntos">${infoBase.servicio || 'CONSULTA EXTERNA'} / ${infoBase.cama || '---'}</span></td>
                        <td class="lbl-clinico">Edad / Fecha:</td>
                        <td><span class="linea-puntos">${infoBase.edad || '---'} AÑOS / ${fechaString}</span></td>
                    </tr>
                    <tr style="height: 6px;"></tr>
                    <tr>
                        <td class="lbl-clinico">Médico Prescriptor:</td>
                        <td colspan="3"><span class="linea-puntos">${medicoSolicitante}</span></td>
                    </tr>
                </table>
            </div>

            <table class="results-table">
                <thead>
                    <tr>
                        <th style="text-align: left; width: 45%;">Componente / Analito</th>
                        <th style="width: 25%; text-align: center;">Resultado</th>
                        <th style="width: 30%; text-align: center;">Valores Referenciales</th>
                    </tr>
                </thead>
                <tbody>${resultadosHTML}</tbody>
            </table>

            <div class="footer-section">
                <table style="width: 100%; text-align: center; font-size: 12px; font-family: Arial, sans-serif;">
                    <tr>
                        <td style="width: 50%; vertical-align: bottom;">
                            <span style="font-size: 11px;">Cerro de Pasco, ${fechaString} ${horaString}</span><br><br>
                            <span style="display: inline-block; width: 220px; border-top: 1px solid #000; padding-top: 4px;">
                                <strong>LUGAR Y FECHA DE EMISIÓN</strong>
                            </span>
                        </td>
                        <td style="width: 50%; vertical-align: bottom;">
                            <div style="display: inline-block; width: 240px; border-top: 1px solid #000; padding-top: 4px;">
                                <strong style="display: block; font-size: 11px; text-transform: uppercase;">${nombreResponsable}</strong>
                                <span style="font-size: 10px; color: #334155; display: block; font-weight: bold;">LABORATORIO CLÍNICO</span>
                                <span style="font-size: 9px; color: #64748b; display: block;">Firma y Sello del Responsable</span>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </body>
        </html>`);
        ventana.document.close();
    }
};