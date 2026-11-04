// =============================================================================
// HOSPITAL REGIONAL DANIEL ALCIDES CARRIÓN - LABORATORIO CLÍNICO
// MÓDULO: public/js/solicitudes/bioquimica2.js
// OBJETIVO: Gestión de pruebas bioquímicas enzimáticas, metabólicas y especiales.
// 
// EXÁMENES YA IMPLEMENTADOS EN "bioquimica.js" (NO REPETIR AQUÍ):
// - Perfil Lipídico Completo (Colesterol Total, HDL, LDL, VLDL, Triglicéridos)
// - Perfil Hepático Completo (TGO, TGP, FA, BT, BD, BI, GGTP)
// - Bilirrubinas Totales y Fraccionadas (Total, Directa, Indirecta)
// - Proteínas Totales y Fraccionadas (Albúmina, Globulinas, Proteínas Totales)
// - Transaminasas Duales (T.G.O. / AST y T.G.P. / ALT)
// - Pruebas Básicas: Glucosa, Urea, Creatinina
//
// EXÁMENES GESTIONADOS EN ESTE ARCHIVO (bioquimica2.js):
// [X] GAMMA - GLUTAMIL TRANSFERASA (GGTP / GGT)
// [X] FOSFATASA ALCALINA
// [X] AMILASA Y LIPASA (Enzimas Pancreáticas)
// [X] LACTATO DESHIDROGENASA (LDH)
// [X] ÁCIDO ÚRICO
// [X] PROTEINURIA DE 24 HORAS
// [X] CREATINA QUINASA MB (CK-MB)
// [X] HEMOGLOBINA GLICOSILADA (HbA1c)
//
// NOTA PARA DESARROLLADORES:
// Para añadir un nuevo examen analítico aquí, registre su palabra clave en 
// esBioquimica2() y agregue su bloque en renderizar() si requiere diseño o 
// cálculo específico. De lo contrario, caerá al renderizador general dinámico.
// =============================================================================

const ModuloBioquimica2 = {

    esBioquimica2: function(nombreExamen, botonPresionado, idCpt) {
        const examen = (nombreExamen || '').toUpperCase();
        const boton = (botonPresionado || '').toUpperCase();

        return boton.includes('GGTP') ||
               boton.includes('GGT') ||
               boton.includes('FOSFATASA') ||
               boton.includes('AMILASA') ||
               boton.includes('LIPASA') ||
               boton.includes('DESHIDROGENASA') ||
               boton.includes('LDH') ||
               boton.includes('ACIDO URICO') ||
               boton.includes('ÁCIDO ÚRICO') ||
               boton.includes('PROTEINURIA') ||
               boton.includes('CKMB') ||
               boton.includes('CK-MB') ||
               boton.includes('GLICOSILADA') ||
               boton.includes('HBA1C') ||
               examen.includes('GGTP') ||
               examen.includes('FOSFATASA') ||
               examen.includes('AMILASA') ||
               examen.includes('LIPASA') ||
               examen.includes('DESHIDROGENASA') ||
               examen.includes('ACIDO URICO') ||
               examen.includes('ÁCIDO ÚRICO') ||
               examen.includes('PROTEINURIA') ||
               examen.includes('CKMB') ||
               examen.includes('GLICOSILADA');
    },

    renderizar: function(data, botonPresionado, nombreExamenOriginal, idMovimiento, idOrden) {
        const cptPadre = data[0]?.idProductoCPT || 0;
        const metodoGeneral = data[0]?.Metodo || 'Cinético Colorimétrico';
        const examenUpper = (nombreExamenOriginal || '').toUpperCase();
        const botonUpper = (botonPresionado || '').toUpperCase();

        // ---------------------------------------------------------------------
        // 1. HEMOGLOBINA GLICOSILADA (HBA1C)
        // ---------------------------------------------------------------------
        if (botonUpper.includes('GLICOSILADA') || examenUpper.includes('GLICOSILADA') || botonUpper.includes('HBA1C')) {
            const item = data[0] || {};
            const val = (item.Resultado_Actual || '').trim();
            const ref = item.ValorReferencial || 'Prediabetes: 5.7 - 6.4 % | Control bueno: 6.5 - 8.0 % | Alto: > 8 %';

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-teal-50 text-teal-900 font-bold px-4 py-2 border-b border-teal-200 flex items-center justify-between">
                        <span class="flex items-center"><i class="fa-solid fa-chart-line text-teal-600 mr-2"></i> DOSAJE DE HEMOGLOBINA GLICOSILADA (HbA1c)</span>
                        <span class="text-xs bg-teal-200 text-teal-900 px-2 py-0.5 rounded font-bold">${item.Metodo || 'Inmunoanálisis / HPLC'}</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Determinación</th>
                                <th class="p-2.5 text-center w-48">Resultado (%)</th>
                                <th class="p-2.5 text-center">Criterios de Referencia Clínica</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">HEMOGLOBINA GLICOSILADA (HbA1c)</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text"
                                           class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${val}"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="HEMOGLOBINA GLICOCILADA - HBA1C"
                                           data-referencial="${ref}"
                                           placeholder="Ej: 6.2">
                                </td>
                                <td class="p-3 text-center text-gray-600 text-xs font-mono">
                                    ${ref}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // ---------------------------------------------------------------------
        // 2. PROTEINURIA DE 24 HORAS
        // ---------------------------------------------------------------------
        if (botonUpper.includes('PROTEINURIA') || examenUpper.includes('PROTEINURIA')) {
            const item = data[0] || {};
            const val = (item.Resultado_Actual || '').trim();
            const ref = item.ValorReferencial || '< 140 mg/24hrs';

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-amber-50 text-amber-900 font-bold px-4 py-2 border-b border-amber-200 flex items-center justify-between">
                        <span class="flex items-center"><i class="fa-solid fa-flask-vial text-amber-600 mr-2"></i> PROTEINURIA EN ORINA DE 24 HORAS</span>
                        <span class="text-xs bg-amber-200 text-amber-900 px-2 py-0.5 rounded font-bold">${item.Metodo || 'Rojo Pirogalol'}</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Determinación</th>
                                <th class="p-2.5 text-center w-48">Resultado (mg/24hrs)</th>
                                <th class="p-2.5 text-center">Valor Referencial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">PROTEINURIA DE 24 HORAS</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text"
                                           class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${val}"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="PROTEINURIA DE 24 HORAS"
                                           data-referencial="${ref}"
                                           placeholder="Ej: 85.0">
                                </td>
                                <td class="p-3 text-center text-gray-600 text-xs font-mono">${ref}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // ---------------------------------------------------------------------
        // 3. RENDERIZADOR GENERAL DINÁMICO PARA BIOQUÍMICA 2
        // (GGT, Amilasa, Lipasa, LDH, Fosfatasa Alcalina, Ácido Úrico, CK-MB)
        // ---------------------------------------------------------------------
        const tituloLimpio = (nombreExamenOriginal || 'EXAMEN')
            .toUpperCase()
            .replace(/^DOSAJE DE\s+/i, '')
            .replace(/^DOSAJE\s+/i, '')
            .trim();

        let filasHtml = '';
        data.forEach(item => {
            const analito = item.SubPrueba || item.Examen_Principal || 'ANALITO';
            const val = (item.Resultado_Actual || '').trim();
            const ref = (item.ValorReferencial && item.ValorReferencial !== 'N/A') ? item.ValorReferencial : '---';
            const met = item.Metodo || metodoGeneral;

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
                        <span class="text-xs text-gray-400 block" style="font-size: 10px;">${met}</span>
                    </td>
                </tr>`;
        });

        return `
            <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                <div class="bg-gray-100 text-gray-800 font-bold px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="flex items-center"><i class="fa-solid fa-flask text-teal-600 mr-2"></i> DOSAJE DE ${tituloLimpio}</span>
                    <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded font-bold">${metodoGeneral}</span>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                        <tr>
                            <th class="p-2.5">Parámetro</th>
                            <th class="p-2.5 text-center w-48">Resultado</th>
                            <th class="p-2.5 text-center">Rango Referencial</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        ${filasHtml}
                    </tbody>
                </table>
            </div>`;
    }
};