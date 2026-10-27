// public/js/solicitudes/bioquimica.js
// Manejo exclusivo de Bioquímica Clínica con cálculo automático de Bilirrubinas

const ModuloBioquimica = {

    esBioquimica: function(nombreExamen, botonPresionado, idCpt) {
        const examen = (nombreExamen || '').toUpperCase();
        const boton = (botonPresionado || '').toUpperCase();

        return boton.includes('CREATININA') ||
               boton.includes('GLUCOSA') ||
               boton.includes('UREA') ||
               boton.includes('COLESTEROL') ||
               boton.includes('TRIGLICERIDO') ||
               boton.includes('TRANSAMINASA') ||
               boton.includes('ASPARTATO') ||
               boton.includes('ALANINA') ||
               boton.includes('SGOT') ||
               boton.includes('SGPT') ||
               boton.includes('BILIRRUBINA') ||
               boton.includes('ACIDO URICO') ||
               boton.includes('PERFIL') ||
               examen.includes('BILIRRUBINA') ||
               examen.includes('TRANSFERASA') ||
               examen.includes('CREATININA');
    },

    renderizar: function(data, botonPresionado, nombreExamenOriginal, idMovimiento, idOrden) {
        const cptPadre = data[0]?.idProductoCPT || 0;
        const metodoGeneral = data[0]?.Metodo || 'Automatizado';
        const examenUpper = (nombreExamenOriginal || '').toUpperCase();
        const botonUpper = (botonPresionado || '').toUpperCase();

        // 1. PLANTILLA COMPLETA DE BILIRRUBINAS (TOTAL, DIRECTA E INDIRECTA)
        if (botonUpper.includes('BILIRRUBINA') || examenUpper.includes('BILIRRUBINA')) {
            // Mapear valores actuales
            let valTotal = '';
            let valDirecta = '';
            let valIndirecta = '';

            data.forEach(item => {
                const nombre = (item.SubPrueba || '').toUpperCase();
                const res = (item.Resultado_Actual || '').trim();
                if (nombre.includes('DIRECTA')) valDirecta = res;
                else if (nombre.includes('INDIRECTA')) valIndirecta = res;
                else if (nombre.includes('TOTAL')) valTotal = res;
                else if (!valTotal) valTotal = res;
            });

            // Si hay Directa e Indirecta pero no Total: Total = Directa + Indirecta
            if (valDirecta && valIndirecta && (!valTotal || valTotal === '0.00' || valTotal === '0')) {
                const d = parseFloat(valDirecta);
                const i = parseFloat(valIndirecta);
                if (!isNaN(d) && !isNaN(i)) {
                    valTotal = (d + i).toFixed(2);
                }
            } 
            // Si hay Total y Directa pero no Indirecta: Indirecta = Total - Directa
            else if (valTotal && valDirecta && (!valIndirecta || valIndirecta === '0.00' || valIndirecta === '0')) {
                const t = parseFloat(valTotal);
                const d = parseFloat(valDirecta);
                if (!isNaN(t) && !isNaN(d)) {
                    valIndirecta = (t - d >= 0 ? (t - d).toFixed(2) : '0.00');
                }
            }

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-yellow-50 text-yellow-900 font-bold px-4 py-2 border-b border-yellow-200 flex items-center justify-between">
                        <span class="flex items-center"><i class="fa-solid fa-droplet text-yellow-600 mr-2"></i> PERFIL DE BILIRRUBINAS</span>
                        <span class="text-xs bg-yellow-200 text-yellow-900 px-2 py-0.5 rounded font-bold">Jendrassik - Grof</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Fracción</th>
                                <th class="p-2.5 text-center w-48">Resultado (mg/dL)</th>
                                <th class="p-2.5 text-center">Referencial</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <!-- Bilirrubina Directa -->
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">BILIRRUBINA DIRECTA</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" id="input_bili_directa"
                                           class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${valDirecta}"
                                           oninput="ModuloBioquimica.calcularBilirrubinasPorFraccion()"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="BILIRRUBINA DIRECTA"
                                           data-referencial="Hasta 0.30 mg/dL"
                                           placeholder="0.00">
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs font-mono">
                                    Hasta 0.30 mg/dL
                                    <span class="text-xs text-gray-400 block" style="font-size: 10px;">Jendrassik - Grof</span>
                                </td>
                            </tr>
                            <!-- Bilirrubina Indirecta -->
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">BILIRRUBINA INDIRECTA</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" id="input_bili_indirecta"
                                           class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${valIndirecta}"
                                           oninput="ModuloBioquimica.calcularBilirrubinasPorFraccion()"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="BILIRRUBINA INDIRECTA"
                                           data-referencial="Hasta 0.90 mg/dL"
                                           placeholder="0.00">
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs font-mono">
                                    Hasta 0.90 mg/dL
                                    <span class="text-xs text-gray-400 block" style="font-size: 10px;">Jendrassik - Grof</span>
                                </td>
                            </tr>
                            <!-- Bilirrubina Total -->
                            <tr class="hover:bg-gray-50 bg-blue-50/30">
                                <td class="p-3 font-bold text-gray-700">
                                    BILIRRUBINA TOTAL
                                    <span class="text-xs text-blue-600 block font-normal" style="font-size: 10px;">(Calculada = Directa + Indirecta)</span>
                                </td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" id="input_bili_total"
                                           class="w-full bg-white border border-blue-400 rounded px-2.5 py-1.5 text-center font-bold text-blue-800 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono shadow-xs"
                                           value="${valTotal}"
                                           oninput="ModuloBioquimica.calcularBilirrubinaIndirectaPorTotal()"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="BILIRRUBINA TOTAL"
                                           data-referencial="Adultos: Hasta 1.20 mg/dL | Neonatos: Hasta 12.0 mg/dL"
                                           placeholder="0.00">
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs font-mono">
                                    Adultos: Hasta 1.20 mg/dL | Neonatos: Hasta 12.0 mg/dL
                                    <span class="text-xs text-gray-400 block" style="font-size: 10px;">Jendrassik - Grof</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // 2. PARÁMETROS GENERALES DE BIOQUÍMICA (CREATININA, TGO, TGP, GLUCOSA, UREA)
        const tituloLimpio = (nombreExamenOriginal || 'EXAMEN')
            .toUpperCase()
            .replace(/^DOSAJE DE\s+/i, '')
            .replace(/^DOSAJE\s+/i, '')
            .trim();

        let filasHtml = '';
        data.forEach(item => {
            const analito = item.SubPrueba || item.Examen_Principal || 'ANALITO';
            const val = (item.Resultado_Actual || '').trim();
            
            let ref = (item.ValorReferencial && item.ValorReferencial !== 'N/A') ? item.ValorReferencial : 'N/A';
            let met = item.Metodo || metodoGeneral;

            const aUpper = analito.toUpperCase();
            if (ref === 'N/A' || ref === '') {
                if (aUpper.includes('ASPARTATO') || aUpper.includes('AST') || aUpper.includes('SGOT')) {
                    ref = 'Hasta 38 U/L (Varones) | Hasta 32 U/L (Mujeres)';
                    met = 'Cinético UV (IFCC)';
                } else if (aUpper.includes('ALANINA') || aUpper.includes('ALT') || aUpper.includes('SGPT')) {
                    ref = 'Hasta 41 U/L (Varones) | Hasta 33 U/L (Mujeres)';
                    met = 'Cinético UV (IFCC)';
                } else if (aUpper.includes('CREATININA')) {
                    ref = 'Adultos: 0.70 - 1.30 mg/dL (Varones) | 0.60 - 1.10 mg/dL (Mujeres)';
                    met = 'Jaffé Cinético';
                } else if (aUpper.includes('PROTEINA C') || aUpper.includes('PCR')) {
                    ref = 'Menor a 6.0 mg/L (Negativo)';
                    met = 'Inmunoturbidimetría / Látex';
                }
            }

            filasHtml += `
                <tr class="hover:bg-gray-50 border-b border-gray-100">
                    <td class="p-3 font-bold text-gray-700">${analito}</td>
                    <td class="p-2 w-48 text-center">
                        <input type="text" 
                               class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                               value="${val}"
                               data-movimiento="${idMovimiento}"
                               data-orden="${idOrden}"
                               data-cpt="${cptPadre}"
                               data-nombre="${analito}"
                               data-referencial="${ref}"
                               placeholder="0.00">
                    </td>
                    <td class="p-3 text-center text-gray-500 text-xs font-mono">
                        ${ref}
                        ${ref !== 'N/A' ? `<span class="text-xs text-gray-400 block" style="font-size: 10px;">${met}</span>` : ''}
                    </td>
                </tr>`;
        });

        return `
            <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                <div class="bg-gray-100 text-gray-800 font-bold px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="flex items-center">
                        <i class="fa-solid fa-flask text-blue-600 mr-2"></i> DOSAJE DE ${tituloLimpio}
                    </span>
                    <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded font-bold">
                        ${metodoGeneral}
                    </span>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                        <tr>
                            <th class="p-2.5">Parámetro</th>
                            <th class="p-2.5 text-center w-48">Resultado</th>
                            <th class="p-2.5 text-center">Referencial</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        ${filasHtml}
                    </tbody>
                </table>
            </div>`;
    },

    // 3. FÓRMULA AUTOMÁTICA A: Total = Directa + Indirecta
    calcularBilirrubinasPorFraccion: function() {
        const inputTot = document.getElementById('input_bili_total');
        const inputDir = document.getElementById('input_bili_directa');
        const inputInd = document.getElementById('input_bili_indirecta');

        if (!inputTot || !inputDir || !inputInd) return;

        const d = parseFloat(inputDir.value);
        const i = parseFloat(inputInd.value);

        if (!isNaN(d) && !isNaN(i)) {
            inputTot.value = (d + i).toFixed(2);
        } else if (!isNaN(d) && isNaN(i)) {
            inputTot.value = d.toFixed(2);
        } else if (isNaN(d) && !isNaN(i)) {
            inputTot.value = i.toFixed(2);
        }
    },

    // 4. FÓRMULA AUTOMÁTICA B: Indirecta = Total - Directa (si el operador escribe primero la Total)
    calcularBilirrubinaIndirectaPorTotal: function() {
        const inputTot = document.getElementById('input_bili_total');
        const inputDir = document.getElementById('input_bili_directa');
        const inputInd = document.getElementById('input_bili_indirecta');

        if (!inputTot || !inputDir || !inputInd) return;

        const t = parseFloat(inputTot.value);
        const d = parseFloat(inputDir.value);

        if (!isNaN(t) && !isNaN(d)) {
            const ind = t - d;
            inputInd.value = ind >= 0 ? ind.toFixed(2) : '0.00';
        }
    }
};