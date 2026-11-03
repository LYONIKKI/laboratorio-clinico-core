// public/js/solicitudes/otros.js
// Manejo de pruebas especiales, marcadores tumorales (PSA), parasitología (Thevenon) e Inmunohematología (Pruebas Cruzadas)

const ModuloOtros = {

    esOtroExamen: function(nombreExamen, botonPresionado) {
        const ex = (nombreExamen || '').toUpperCase();
        const btn = (botonPresionado || '').toUpperCase();

        return btn.includes('PSA') ||
               btn.includes('PROSTATICO') ||
               btn.includes('THEVENON') ||
               btn.includes('SANGRE OCULTA') ||
               btn.includes('GUAYACOL') ||
               btn.includes('PROTEINA C') ||
               btn.includes('PCR') ||
               btn.includes('CRUZADA') ||
               btn.includes('TRANSFUSION') ||
               ex.includes('THEVENON') ||
               ex.includes('PROSTATICO') ||
               ex.includes('PROTEINA C') ||
               ex.includes('CRUZADA') ||
               ex.includes('TRANSFUSION');
    },

    renderizar: function(data, botonPresionado, nombreExamenOriginal, idMovimiento, idOrden) {
        const cptPadre = data[0]?.idProductoCPT || 0;
        const nombreUpper = (nombreExamenOriginal || '').toUpperCase();
        const botonUpper = (botonPresionado || '').toUpperCase();

        // ---------------------------------------------------------------------
        // 1. PRUEBAS CRUZADAS (BANCO DE SANGRE / COMPATIBILIDAD TRANSFUSIONAL)
        // ---------------------------------------------------------------------
        if (botonUpper.includes('CRUZADA') || nombreUpper.includes('CRUZADA')) {
            let valCompat = 'COMPATIBLE';
            let valHemocomp = 'PAQUETE GLOBULAR (PG)';
            let valGrupoUnidad = 'O POSITIVO (O+)';
            let valBolsa = '';

            data.forEach(item => {
                const n = (item.SubPrueba || '').toUpperCase();
                const r = (item.Resultado_Actual || '').trim();
                if (n.includes('COMPATIBILIDAD')) valCompat = r || 'COMPATIBLE';
                else if (n.includes('HEMOCOMPONENTE') || n.includes('PRODUCTO')) valHemocomp = r || 'PAQUETE GLOBULAR (PG)';
                else if (n.includes('GRUPO') || n.includes('FACTOR')) valGrupoUnidad = r || 'O POSITIVO (O+)';
                else if (n.includes('BOLSA') || n.includes('UNIDAD')) valBolsa = r;
                else if (!valBolsa) valBolsa = r;
            });

            const optCompat = ['COMPATIBLE', 'INCOMPATIBLE'];
            const optHemocomp = [
                'PAQUETE GLOBULAR (PG)',
                'PLASMA FRESCO CONGELADO (PFC)',
                'CONCENTRADO PLAQUETARIO (CP)',
                'CRIOPRECIPITADO',
                'PLAQUETAS POR AFÉRESIS'
            ];
            const optGrupos = [
                'O POSITIVO (O+)',
                'O NEGATIVO (O-)',
                'A POSITIVO (A+)',
                'A NEGATIVO (A-)',
                'B POSITIVO (B+)',
                'B NEGATIVO (B-)',
                'AB POSITIVO (AB+)',
                'AB NEGATIVO (AB-)'
            ];

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-red-50 text-red-900 font-bold px-4 py-2 border-b border-red-200 flex items-center justify-between">
                        <span class="flex items-center"><i class="fa-solid fa-droplet text-red-600 mr-2"></i> PRUEBAS CRUZADAS / BANCO DE SANGRE</span>
                        <span class="text-xs bg-red-200 text-red-900 px-2 py-0.5 rounded font-bold">Aglutinación en Tubo / Gel</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Determinación</th>
                                <th class="p-2.5 text-center w-72">Resultado Asignado</th>
                                <th class="p-2.5 text-center">Referencial</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <!-- 1. Compatibilidad -->
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">COMPATIBILIDAD TRANSFUSIONAL</td>
                                <td class="p-2 text-center">
                                    <select class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                                            data-movimiento="${idMovimiento}"
                                            data-orden="${idOrden}"
                                            data-cpt="${cptPadre}"
                                            data-nombre="COMPATIBILIDAD TRANSFUSIONAL"
                                            data-referencial="COMPATIBLE">
                                        ${optCompat.map(o => `<option value="${o}" ${valCompat === o ? 'selected' : ''}>${o}</option>`).join('')}
                                    </select>
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs">COMPATIBLE</td>
                            </tr>

                            <!-- 2. Hemocomponente -->
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">HEMOCOMPONENTE ASIGNADO</td>
                                <td class="p-2 text-center">
                                    <select class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                                            data-movimiento="${idMovimiento}"
                                            data-orden="${idOrden}"
                                            data-cpt="${cptPadre}"
                                            data-nombre="HEMOCOMPONENTE ASIGNADO"
                                            data-referencial="Hemocomponente Certificado">
                                        ${optHemocomp.map(o => `<option value="${o}" ${valHemocomp === o ? 'selected' : ''}>${o}</option>`).join('')}
                                    </select>
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs">Unidad Homologada</td>
                            </tr>

                            <!-- 3. Grupo y Factor de la Unidad Donada -->
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">GRUPO Y FACTOR RH DE LA UNIDAD</td>
                                <td class="p-2 text-center">
                                    <select class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                                            data-movimiento="${idMovimiento}"
                                            data-orden="${idOrden}"
                                            data-cpt="${cptPadre}"
                                            data-nombre="GRUPO Y FACTOR DE LA UNIDAD"
                                            data-referencial="Sistema ABO / Rh">
                                        ${optGrupos.map(o => `<option value="${o}" ${valGrupoUnidad === o ? 'selected' : ''}>${o}</option>`).join('')}
                                    </select>
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs">Sistema ABO / Rh</td>
                            </tr>

                            <!-- 4. N° de Bolsa / Unidad -->
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">N° DE BOLSA / CÓDIGO DE UNIDAD</td>
                                <td class="p-2 text-center">
                                    <input type="text" 
                                           class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${valBolsa}"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="N° DE BOLSA / UNIDAD ASIGNADA"
                                           data-referencial="Verificar sello de Banco de Sangre"
                                           placeholder="Ej: B-2026-00458">
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs">Sello Banco de Sangre</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // ---------------------------------------------------------------------
        // 2. REACCIÓN DE THEVENON (SANGRE OCULTA EN HECES CON SELECT)
        // ---------------------------------------------------------------------
        if (botonUpper.includes('THEVENON') || nombreUpper.includes('THEVENON') || nombreUpper.includes('SANGRE OCULTA')) {
            const item = data[0];
            const valorActual = (item.Resultado_Actual || '').trim().toUpperCase();
            const opciones = ['NEGATIVO', 'POSITIVO (+)', 'POSITIVO (++)', 'POSITIVO (+++)'];

            let optionsHtml = `<option value="">-- Seleccionar Resultado --</option>`;
            opciones.forEach(opt => {
                const selected = (valorActual === opt || (valorActual === '' && opt === 'NEGATIVO')) ? 'selected' : '';
                optionsHtml += `<option value="${opt}" ${selected}>${opt}</option>`;
            });

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-yellow-50 text-yellow-900 font-bold px-4 py-2 border-b border-yellow-200 flex items-center justify-between">
                        <span><i class="fa-solid fa-vial-virus text-yellow-600 mr-2"></i> REACCIÓN DE THEVENON (SANGRE OCULTA EN HECES)</span>
                        <span class="text-xs bg-yellow-200 text-yellow-900 px-2 py-0.5 rounded font-bold">Guayacol</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Determinación</th>
                                <th class="p-2.5 text-center w-64">Resultado</th>
                                <th class="p-2.5 text-center">Referencial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">INVESTIGACIÓN DE SANGRE OCULTA EN HECES</td>
                                <td class="p-2.5 text-center">
                                    <select class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                                            data-movimiento="${idMovimiento}"
                                            data-orden="${idOrden}"
                                            data-cpt="${cptPadre}"
                                            data-nombre="THEVENON (SANGRE OCULTA EN HECES)"
                                            data-referencial="NEGATIVO">
                                        ${optionsHtml}
                                    </select>
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs">NEGATIVO</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // ---------------------------------------------------------------------
        // 3. ANTÍGENO PROSTÁTICO ESPECÍFICO (PSA TOTAL O LIBRE)
        // ---------------------------------------------------------------------
        if (botonUpper.includes('PSA') || nombreUpper.includes('PROSTATICO')) {
            const item = data[0];
            const esLibre = nombreUpper.includes('LIBRE');
            const titulo = esLibre ? 'ANTÍGENO PROSTÁTICO ESPECÍFICO LIBRE (PSA LIBRE)' : 'ANTÍGENO PROSTÁTICO ESPECÍFICO (PSA TOTAL)';
            const valRef = esLibre 
                ? 'Menor a 0.50 ng/mL | Relación L/T: > 25%' 
                : 'Hasta 4.00 ng/mL';

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-blue-50 text-blue-900 font-bold px-4 py-2 border-b border-blue-200 flex items-center justify-between">
                        <span><i class="fa-solid fa-dna text-blue-600 mr-2"></i> ${titulo}</span>
                        <span class="text-xs bg-blue-200 text-blue-900 px-2 py-0.5 rounded font-bold">Quimioluminiscencia</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Marcador</th>
                                <th class="p-2.5 text-center w-48">Resultado (ng/mL)</th>
                                <th class="p-2.5 text-center">Referencial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">${titulo}</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" 
                                           class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${item.Resultado_Actual || ''}"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="${titulo}"
                                           data-referencial="${valRef}"
                                           placeholder="Ej: 0.35">
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs font-mono">${valRef}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // ---------------------------------------------------------------------
        // 4. PROTEÍNA C REACTIVA (PCR)
        // ---------------------------------------------------------------------
        if (botonUpper.includes('PROTEINA C') || botonUpper.includes('PCR') || nombreUpper.includes('PROTEINA C')) {
            const item = data[0];
            const valRef = 'Menor a 6.0 mg/L (Negativo)';

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-indigo-50 text-indigo-900 font-bold px-4 py-2 border-b border-indigo-200 flex items-center justify-between">
                        <span><i class="fa-solid fa-vial text-indigo-600 mr-2"></i> DOSAJE DE PROTEÍNA C REACTIVA</span>
                        <span class="text-xs bg-indigo-200 text-indigo-900 px-2 py-0.5 rounded font-bold">Látex / Inmunoturbidimetría</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Determinación</th>
                                <th class="p-2.5 text-center w-48">Resultado</th>
                                <th class="p-2.5 text-center">Referencial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">PROTEÍNA C REACTIVA (PCR)</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" 
                                           class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${item.Resultado_Actual || ''}"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="PROTEINA C REACTIVA"
                                           data-referencial="${valRef}"
                                           placeholder="Ej: < 6.0 mg/L">
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs font-mono">${valRef}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // ---------------------------------------------------------------------
        // 5. FALLBACK PARA OTRAS PRUEBAS
        // ---------------------------------------------------------------------
        let filas = '';
        data.forEach(item => {
            filas += `
                <tr class="hover:bg-gray-50 border-b border-gray-100">
                    <td class="p-3 font-bold text-gray-700">${item.SubPrueba || item.Examen_Principal}</td>
                    <td class="p-2 w-48 text-center">
                        <input type="text" 
                               class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                               value="${item.Resultado_Actual || ''}"
                               data-movimiento="${idMovimiento}"
                               data-orden="${idOrden}"
                               data-cpt="${cptPadre}"
                               data-nombre="${item.SubPrueba || item.Examen_Principal}"
                               data-referencial="${item.ValorReferencial || 'N/A'}"
                               placeholder="0.00">
                    </td>
                    <td class="p-3 text-center text-gray-500 text-xs font-mono">${item.ValorReferencial || 'N/A'}</td>
                </tr>`;
        });

        return `
            <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                <div class="bg-gray-100 text-gray-800 font-bold px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span><i class="fa-solid fa-vial text-blue-600 mr-2"></i> ${nombreExamenOriginal}</span>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                        <tr>
                            <th class="p-2.5">Parámetro</th>
                            <th class="p-2.5 text-center w-48">Resultado</th>
                            <th class="p-2.5 text-center">Referencial</th>
                        </tr>
                    </thead>
                    <tbody>${filas}</tbody>
                </table>
            </div>`;
    }
};