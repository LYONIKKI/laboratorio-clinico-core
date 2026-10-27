document.addEventListener("DOMContentLoaded", function () {
    
    // ==========================================
    // INTERFAZ Y SELECTORES COMPONENTES
    // ==========================================
    const btnAbrirModalExamen = document.getElementById("btnAbrirModalExamen");
    const txtBuscarCPT = document.getElementById("txtBuscarExamenExclusivo"); 
    const tbodyResultadosCPT = document.getElementById("tbodyResultadosExamenesExclusivo"); 
    const tbodyPruebasRealizar = document.getElementById("tbodyPruebasRealizar");
    const radioExterno = document.getElementById("radioExterno");

    const elModalBuscar = document.getElementById("modalBuscarCPT");
    let bootstrapModalBuscar = null;
    
    if (elModalBuscar) {
        bootstrapModalBuscar = new bootstrap.Modal(elModalBuscar);

        // Limpieza automática preventiva al cerrar la interfaz del buscador
        elModalBuscar.addEventListener('hidden.bs.modal', function () {
            if (txtBuscarCPT) txtBuscarCPT.value = "";
            if (tbodyResultadosCPT) tbodyResultadosCPT.innerHTML = "";
        });
    }

    if (btnAbrirModalExamen && bootstrapModalBuscar) {
        btnAbrirModalExamen.addEventListener("click", () => bootstrapModalBuscar.show());
    }

    // ==========================================
    // 2. BUSCADOR ASÍNCRONO EN TIEMPO REAL (DEBOUNCE 300ms)
    // ==========================================
    let timeoutBuscar = null;
    if (txtBuscarCPT) {
        txtBuscarCPT.addEventListener("keyup", function () {
            clearTimeout(timeoutBuscar);
            const query = txtBuscarCPT.value.trim();

            if (query.length < 2) {
                if (tbodyResultadosCPT) tbodyResultadosCPT.innerHTML = "";
                return;
            }

            // Capturamos el ID de la tarifa cargada dinámicamente en la cabecera
            let idTarifaActiva = document.getElementById("regIdTarifa") ? document.getElementById("regIdTarifa").value : 0;
            
            // Salvavidas de control por si no se ha mapeado aún
            if (!idTarifaActiva || idTarifaActiva == 0) {
                const textoSeguro = document.getElementById("regSeguro") ? document.getElementById("regSeguro").value.toUpperCase() : "";
                if (textoSeguro.includes("SIS")) {
                    idTarifaActiva = 2; // Fuerza tarifa SIS
                } else if (textoSeguro.includes("SOAT")) {
                    idTarifaActiva = 19; // Fuerza tarifa SOAT
                } else {
                    idTarifaActiva = 1; // Particular por defecto
                }
            }

            timeoutBuscar = setTimeout(() => {
                fetch(`/laboratorio/public/api/buscar-examenes-cpt?q=${encodeURIComponent(query)}&idTarifa=${idTarifaActiva}`)
                    .then(response => {
                        if (!response.ok) throw new Error("Error en la respuesta del búnker");
                        return response.json();
                    })
                    .then(res => {
                        if (res.success) {
                            if (!tbodyResultadosCPT) return;
                            tbodyResultadosCPT.innerHTML = "";

                            if (!res.data || res.data.length === 0) {
                                tbodyResultadosCPT.innerHTML = '<tr><td colspan="2" class="text-center py-2 text-danger fw-bold">No se encontraron exámenes para esta tarifa</td></tr>';
                                return;
                            }

                            res.data.forEach(serv => {
                                const tr = document.createElement("tr");
                                tr.style.cursor = "pointer";
                                tr.className = "fila-cpt-seleccionable";
                                tr.setAttribute("data-id", serv.IdServicio);
                                tr.setAttribute("data-codigo", serv.Codigo);
                                tr.setAttribute("data-nombre", serv.Examen || serv.Nombre);
                                tr.setAttribute("data-precio", serv.PrecioUnitario);

                                tr.innerHTML = `
                                    <td style="text-align: center;"><strong>${serv.Codigo}</strong></td>
                                    <td>${serv.Examen || serv.Nombre} <span class="badge bg-light text-primary float-end">S/. ${parseFloat(serv.PrecioUnitario).toFixed(2)}</span></td>
                                `;
                                tbodyResultadosCPT.appendChild(tr);
                            });
                        }
                    })
                    .catch(err => console.error("Error crítico en fetch de CPT:", err));
            }, 300);
        });
    }

    // ==========================================
    // 3. DELEGACIÓN DE EVENTOS E INYECCIÓN CON 6 COLUMNAS
    // ==========================================
    if (tbodyResultadosCPT) {
        tbodyResultadosCPT.addEventListener("click", function (e) {
            const fila = e.target.closest(".fila-cpt-seleccionable");
            if (!fila) return;

            const idProducto = fila.getAttribute("data-id");
            const codigo = fila.getAttribute("data-codigo");
            const nombre = fila.getAttribute("data-nombre");
            const precioUnitario = parseFloat(fila.getAttribute("data-precio")) || 0.00;

            const rowMensajeInicial = document.getElementById("rowMensajeInicial");
            if (rowMensajeInicial) rowMensajeInicial.remove();

            // Validación robusta contra duplicaciones
            const existe = tbodyPruebasRealizar ? tbodyPruebasRealizar.querySelector(`tr[data-id-producto="${idProducto}"]`) : null;
            if (existe) {
                alert("Este examen ya ha sido agregado a la lista.");
                if (bootstrapModalBuscar) bootstrapModalBuscar.hide();
                return;
            }

            let cantidadInicial = 1;
            let subTotal = precioUnitario * cantidadInicial;

            // Evaluamos si el tipo de atención en cabecera es externo para inicializar la receta
            const esExterno = radioExterno ? radioExterno.checked : false;

            const nuevaFila = document.createElement("tr");
            nuevaFila.setAttribute("data-id-producto", idProducto);
            nuevaFila.className = "fila-examen-ejecucion";
            
            // 🔥 CORREGIDO: Mapeado estructural exacto a las celdas de tu grilla
            nuevaFila.innerHTML = `
                <td style="text-align: center; vertical-align: middle;"><strong>${codigo}</strong></td>
                <td style="vertical-align: middle;">${nombre}</td>
                <td style="text-align: center; vertical-align: middle;">
                    <input type="number" class="form-control form-control-sm text-center txt-cantidad-tabla" value="${cantidadInicial}" min="1" style="width: 55px; margin: 0 auto; height: 22px; padding: 2px;">
                </td>
                <td style="text-align: right; vertical-align: middle;" class="pu-valor">S/. ${precioUnitario.toFixed(2)}</td>
                <td style="text-align: right; font-weight: bold; vertical-align: middle;" class="subtotal-valor">S/. ${subTotal.toFixed(2)}</td>
                <td style="text-align: center; vertical-align: middle;">
                    <span class="badge badge-receta-dinamica ${esExterno ? 'bg-success' : 'bg-secondary'}">${esExterno ? 'SÍ' : 'NO'}</span>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                    <button type="button" class="btn btn-sm btn-danger btn-eliminar-fila" style="padding: 1px 6px; font-size: 11px;">❌</button>
                </td>
            `;

            if (tbodyPruebasRealizar) tbodyPruebasRealizar.appendChild(nuevaFila);
            if (bootstrapModalBuscar) bootstrapModalBuscar.hide();

            // ==========================================
            // 4. VINCULACIÓN DE CALCULOS MATEMÁTICOS INTERNOS
            // ==========================================
            const inputCantidad = nuevaFila.querySelector(".txt-cantidad-tabla");
            const tdSubtotal = nuevaFila.querySelector(".subtotal-valor");

            if (inputCantidad) {
                inputCantidad.addEventListener("input", function() {
                    let cant = parseInt(this.value) || 0;
                    if (cant < 1) { 
                        cant = 1; 
                        this.value = 1; 
                    }
                    if (tdSubtotal) tdSubtotal.innerText = `S/. ${(cant * precioUnitario).toFixed(2)}`;
                });
            }

            nuevaFila.querySelector(".btn-eliminar-fila").addEventListener("click", function() {
                nuevaFila.remove();
                
                // 🔥 CORREGIDO: Si vacían la tabla, restauramos el string de instrucción por defecto
                if (tbodyPruebasRealizar && tbodyPruebasRealizar.querySelectorAll(".fila-examen-ejecucion").length === 0) {
                    tbodyPruebasRealizar.innerHTML = `
                        <tr id="rowMensajeInicial">
                            <td colspan="7" class="text-center text-muted py-3" style="font-size: 12px;">
                                Seleccione una atención del listado histórico inferior para desglosar productos CPT o agregue uno nuevo...
                            </td>
                        </tr>
                    `;
                }
            });
        });
    }
});