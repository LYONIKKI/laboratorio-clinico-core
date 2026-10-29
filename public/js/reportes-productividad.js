// public/js/reportes-productividad.js

function abrirModalReportes() {
    const modal = document.getElementById("modalReportesLab");
    if (modal) modal.classList.remove("is-hidden");
}

function cerrarModalReportes() {
    const modal = document.getElementById("modalReportesLab");
    if (modal) modal.classList.add("is-hidden");
}

async function ejecutarGeneracionReporte(e) {
    e.preventDefault();

    const inicio = document.getElementById("repFechaInicio").value;
    const horaInicio = document.getElementById("repHoraInicio").value || '00:00:00';
    const fin = document.getElementById("repFechaFin").value;
    const horaFin = document.getElementById("repHoraFin").value || '23:59:59';
    const tipo = document.querySelector('input[name="tipoReporte"]:checked').value;

    if (!inicio || !fin) {
        Swal.fire({ icon: 'warning', title: 'Atención', text: 'Seleccione las fechas de inicio y fin.' });
        return;
    }

    if (`${inicio} ${horaInicio}` > `${fin} ${horaFin}`) {
        Swal.fire({ icon: 'warning', title: 'Rango Inválido', text: 'La fecha y hora de inicio no pueden ser posteriores a las de fin.' });
        return;
    }

    let endpoint = '/laboratorio/public/api/reportes/pacientes';
    if (tipo === 'procedimientos') {
        endpoint = '/laboratorio/public/api/reportes/procedimientos';
    } else if (tipo === 'con_resultados') {
        endpoint = '/laboratorio/public/api/reportes/con-resultados';
    } else if (tipo === 'consolidado_gastos') {
        endpoint = '/laboratorio/public/api/reportes/consolidado-gastos';
    } else if (tipo === 'resultados_sisgalen') {
        endpoint = '/laboratorio/public/api/reportes/resultados-sisgalen';
    }

    const queryParams = new URLSearchParams({
        inicio: inicio,
        hora_inicio: horaInicio,
        fin: fin,
        hora_fin: horaFin
    });

    const url = `${endpoint}?${queryParams.toString()}`;

    cerrarModalReportes();

    const advertirSalida = (event) => {
        event.preventDefault();
        event.returnValue = 'Se está generando el reporte de laboratorio. Si sales o actualizas la página, la descarga se cancelará.';
        return event.returnValue;
    };
    window.addEventListener('beforeunload', advertirSalida);

    Swal.fire({
        title: 'Generando Reporte Oficial',
        html: `
            <div style="font-size: 13px; color: #475569; margin-top: 8px;">
                <p>Consultando base de datos hospitalaria y maquetando libro Excel...</p>
                <p style="font-size: 11px; color: #94a3b8; margin-top: 5px;">
                    Para rangos mensuales esto puede tomar unos segundos. Por favor espere.
                </p>
            </div>
        `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(url);

        if (!response.ok) {
            const errorTxt = await response.text();
            let msg = 'Error interno en la generación del reporte.';
            try {
                const errJson = JSON.parse(errorTxt);
                msg = errJson.message || msg;
            } catch (e) {
                if (errorTxt.includes('Maximum execution time')) {
                    msg = 'Tiempo de espera agotado al consultar la base de datos. Intente con un rango de fechas más acotado.';
                } else if (errorTxt.includes('Allowed memory size')) {
                    msg = 'Límite de memoria alcanzado en el servidor para este volumen de datos.';
                }
            }
            throw new Error(msg);
        }

        let filename = "Reporte_Productividad.xlsx";
        const disposition = response.headers.get('Content-Disposition');
        if (disposition && disposition.indexOf('filename=') !== -1) {
            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
            if (matches != null && matches[1]) {
                filename = matches[1].replace(/['"]/g, '');
            }
        }

        const blob = await response.blob();
        const downloadUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(downloadUrl);

        Swal.fire({
            icon: 'success',
            title: '¡Descarga Completa!',
            text: 'El archivo Excel se generó correctamente.',
            timer: 2000,
            showConfirmButton: false
        });

    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error de Generación',
            text: err.message
        });
    } finally {
        window.removeEventListener('beforeunload', advertirSalida);
    }
}