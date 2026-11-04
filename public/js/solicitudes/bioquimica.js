// public/js/solicitudes/bioquimica.js
// Manejo exclusivo de Bioquímica Clínica con enrutamiento prioritario y cálculos reactivos:
// 1. Perfil Hepático Completo
// 2. Perfil Lipídico (Friedewald)
// 3. Bilirrubinas (Total, Directa, Indirecta)
// 4. Proteínas Totales y Fraccionadas
// 5. Transaminasas Duales (TGO y TGP)
// 6. Parámetros Generales de Bioquímica

const ModuloBioquimica = {

    esBioquimica: function(nombreExamen, botonPresionado, idCpt) {
        const examen = (nombreExamen || '').toUpperCase();
        const boton = (botonPresionado || '').toUpperCase();

        return boton.includes('CREATININA') ||
               boton.includes('GLUCOSA') ||
               boton.includes('UREA') ||
               boton.includes('COLESTEROL') ||
               boton.includes('TRIGLICERID') ||
               boton.includes('TRIGLICÉRID') ||
               boton.includes('TRANSAMINASA') ||
               boton.includes('ASPARTATO') ||
               boton.includes('ALANINA') ||
               boton.includes('SGOT') ||
               boton.includes('SGPT') ||
               boton.includes('BILIRRUBINA') ||
               boton.includes('ACIDO URICO') ||
               boton.includes('ÁCIDO ÚRICO') ||
               boton.includes('PERFIL') ||
               boton.includes('LIPID') ||
               boton.includes('LIPOPROTEIN') ||
               boton.includes('HEPATIC') ||
               boton.includes('HEPÁTIC') ||
               boton.includes('PROTEINA') ||
               boton.includes('PROTEÍNA') ||
               boton.includes('ALBUMINA') ||
               boton.includes('ALBÚMINA') ||
               boton.includes('GLOBULINA') ||
               boton.includes('FOSFATASA') ||
               boton.includes('GGTP') ||
               boton.includes('AMILASA') ||
               boton.includes('LIPASA') ||
               boton.includes('DESHIDROGENASA') ||
               boton.includes('CKMB') ||
               boton.includes('GLICOSILADA') ||
               examen.includes('BILIRRUBINA') ||
               examen.includes('TRANSFERASA') ||
               examen.includes('CREATININA') ||
               examen.includes('LIPID') ||
               examen.includes('LIPOPROTEIN') ||
               examen.includes('HEPATIC') ||
               examen.includes('HEPÁTIC') ||
               examen.includes('PROTEINA') ||
               examen.includes('PROTEÍNA') ||
               examen.includes('ALBUMINA') ||
               examen.includes('ALBÚMINA') ||
               examen.includes('TRANSAMINASA');
    },

    renderizar: function(data, botonPresionado, nombreExamenOriginal, idMovimiento, idOrden) {
        const cptPadre = data[0]?.idProductoCPT || 0;
        const metodoGeneral = data[0]?.Metodo || 'Automatizado';
        const examenUpper = (nombreExamenOriginal || '').toUpperCase();
        const botonUpper = (botonPresionado || '').toUpperCase();

        // ---------------------------------------------------------------------
        // 1. PRIORIDAD: PERFIL HEPÁTICO COMPLETO (CON CÁLCULOS AUTOMÁTICOS)
        // ---------------------------------------------------------------------
        if (botonUpper.includes('HEPATIC') || examenUpper.includes('HEPATIC') || botonUpper.includes('HEPÁTIC') || examenUpper.includes('HEPÁTIC')) {
            let filasHep = '';

            data.forEach(item => {
                const analito = item.SubPrueba || item.Examen_Principal || 'ANALITO';
                const aUpper = analito.toUpperCase();
                const val = (item.Resultado_Actual || '').trim();
                const ref = (item.ValorReferencial && item.ValorReferencial !== 'N/A') ? item.ValorReferencial : '---';
                const met = item.Metodo || 'Cinético UV';

                let idAttr = '';
                let oninputAttr = '';
                let extraNota = '';
                let bgClase = 'bg-blue-50';

                if (aUpper.includes('ALB')) {
                    idAttr = 'id="input_hep_albumina"';
                    oninputAttr = 'oninput="ModuloBioquimica.calcularHepaticoProteinas()"';
                } else if (aUpper.includes('GLOB')) {
                    idAttr = 'id="input_hep_globulina"';
                    oninputAttr = 'oninput="ModuloBioquimica.calcularHepaticoProteinas()"';
                } else if (aUpper.includes('PROTEINA') || aUpper.includes('PROTEÍNA')) {
                    idAttr = 'id="input_hep_pt"';
                    oninputAttr = 'oninput="ModuloBioquimica.calcularHepaticoGlobulinaPorPT()"';
                    extraNota = '<span class="text-xs text-blue-600 block font-normal" style="font-size: 10px;">(Albúmina + Globulinas)</span>';
                    bgClase = 'bg-white border-blue-400 text-blue-800';
                } else if (aUpper.includes('DIRECTA')) {
                    idAttr = 'id="input_hep_bili_directa"';
                    oninputAttr = 'oninput="ModuloBioquimica.calcularHepaticoBilirrubinas()"';
                } else if (aUpper.includes('INDIRECTA')) {
                    idAttr = 'id="input_hep_bili_indirecta"';
                    oninputAttr = 'oninput="ModuloBioquimica.calcularHepaticoBilirrubinas()"';
                } else if (aUpper.includes('TOTAL') && aUpper.includes('BILIRRUBINA')) {
                    idAttr = 'id="input_hep_bili_total"';
                    extraNota = '<span class="text-xs text-blue-600 block font-normal" style="font-size: 10px;">(Directa + Indirecta)</span>';
                    bgClase = 'bg-white border-blue-400 text-blue-800';
                }

                filasHep += `
                    <tr class="hover:bg-gray-50 border-b border-gray-100">
                        <td class="p-3 font-bold text-gray-700">
                            ${analito}
                            ${extraNota}
                        </td>
                        <td class="p-2 w-48 text-center">
                            <input type="text" ${idAttr} ${oninputAttr}
                                   class="w-full ${bgClase} border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
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
                    <div class="bg-emerald-50 text-emerald-900 font-bold px-4 py-2 border-b border-emerald-200 flex items-center justify-between">
                        <span class="flex items-center"><i class="fa-solid fa-notes-medical text-emerald-600 mr-2"></i> PERFIL HEPÁTICO COMPLETO</span>
                        <span class="text-xs bg-emerald-200 text-emerald-900 px-2 py-0.5 rounded font-bold">Automatizado / Cinético</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Analito</th>
                                <th class="p-2.5 text-center w-48">Resultado</th>
                                <th class="p-2.5 text-center">Rango Referencial</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            ${filasHep}
                        </tbody>
                    </table>
                </div>`;
        }

        // ---------------------------------------------------------------------
        // 2. PRIORIDAD: PERFIL LIPÍDICO COMPLETO (FRIEDEWALD)
        // ---------------------------------------------------------------------
        if (botonUpper.includes('LIPID') || examenUpper.includes('LIPID') || botonUpper.includes('LIPOPROTEIN') || examenUpper.includes('LIPOPROTEIN')) {
            let valCol = '', valTri = '', valHdl = '', valLdl = '', valVldl = '';

            data.forEach(item => {
                const n = (item.SubPrueba || '').toUpperCase();
                const r = (item.Resultado_Actual || '').trim();
                
                if (n.includes('TOTAL') || n === 'COLESTEROL') valCol = r;
                else if (n.includes('TRIGLICERID') || n.includes('TRIGLICÉRID')) valTri = r;
                else if (n.includes('HDL')) valHdl = r;
                else if (n.includes('LDL')) valLdl = r;
                else if (n.includes('VLDL')) valVldl = r;
            });

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-blue-50 text-blue-900 font-bold px-4 py-2 border-b border-blue-200 flex items-center justify-between">
                        <span class="flex items-center"><i class="fa-solid fa-heart-pulse text-blue-600 mr-2"></i> PERFIL LIPÍDICO COMPLETO</span>
                        <span class="text-xs bg-blue-200 text-blue-900 px-2 py-0.5 rounded font-bold">Enzimático Colorimétrico</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Analito</th>
                                <th class="p-2.5 text-center w-48">Resultado (mg/dL)</th>
                                <th class="p-2.5 text-center">Valores Referenciales</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">COLESTEROL TOTAL</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" id="input_lip_colesterol"
                                           class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${valCol}"
                                           oninput="ModuloBioquimica.calcularPerfilLipidico()"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="COLESTEROL TOTAL"
                                           data-referencial="Deseable: < 200 | Mod. Alto: 200 - 239 mg/dL"
                                           placeholder="0.00">
                                </td>
                                <td class="p-2 text-center text-gray-600 text-xs font-mono">Deseable: &lt; 200 | Mod. Alto: 200 - 239 mg/dL</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">COLESTEROL HDL</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" id="input_lip_hdl"
                                           class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${valHdl}"
                                           oninput="ModuloBioquimica.calcularPerfilLipidico()"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="COLESTEROL HDL"
                                           data-referencial="V: 35 - 55 | M: 45 - 65 mg/dL"
                                           placeholder="0.00">
                                </td>
                                <td class="p-2 text-center text-gray-600 text-xs font-mono">V: 35 - 55 | M: 45 - 65 mg/dL</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">TRIGLICÉRIDOS</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" id="input_lip_trigliceridos"
                                           class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${valTri}"
                                           oninput="ModuloBioquimica.calcularPerfilLipidico()"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="TRIGLICÉRIDOS"
                                           data-referencial="Deseable: < 150 | Mod: 150 - 199 mg/dL"
                                           placeholder="0.00">
                                </td>
                                <td class="p-2 text-center text-gray-600 text-xs font-mono">Deseable: &lt; 150 | Mod: 150 - 199 mg/dL</td>
                            </tr>
                            <tr class="hover:bg-gray-50 bg-blue-50/20">
                                <td class="p-3 font-bold text-gray-700">
                                    COLESTEROL LDL
                                    <span class="text-xs text-blue-600 block font-normal" style="font-size: 10px;">(Colesterol - HDL - VLDL)</span>
                                </td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" id="input_lip_ldl"
                                           class="w-full bg-white border border-blue-400 rounded px-2.5 py-1.5 text-center font-bold text-blue-800 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${valLdl}"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="COLESTEROL LDL"
                                           data-referencial="Óptimo: < 100 | Elevado: 130 - 159 mg/dL"
                                           placeholder="0.00">
                                </td>
                                <td class="p-2 text-center text-gray-600 text-xs font-mono">Óptimo: &lt; 100 | Elevado: 130 - 159 mg/dL</td>
                            </tr>
                            <tr class="hover:bg-gray-50 bg-blue-50/20">
                                <td class="p-3 font-bold text-gray-700">
                                    COLESTEROL VLDL
                                    <span class="text-xs text-blue-600 block font-normal" style="font-size: 10px;">(Triglicéridos / 5)</span>
                                </td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" id="input_lip_vldl"
                                           class="w-full bg-white border border-blue-400 rounded px-2.5 py-1.5 text-center font-bold text-blue-800 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${valVldl}"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="COLESTEROL VLDL"
                                           data-referencial="2 - 30 mg/dL"
                                           placeholder="0.00">
                                </td>
                                <td class="p-2 text-center text-gray-600 text-xs font-mono">2 - 30 mg/dL</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // ---------------------------------------------------------------------
        // 3. PRIORIDAD: BILIRRUBINAS
        // ---------------------------------------------------------------------
        if (botonUpper.includes('BILIRRUBINA') || examenUpper.includes('BILIRRUBINA')) {
            let valTotal = '', valDirecta = '', valIndirecta = '';

            data.forEach(item => {
                const nombre = (item.SubPrueba || '').toUpperCase();
                const res = (item.Resultado_Actual || '').trim();
                if (nombre.includes('DIRECTA')) valDirecta = res;
                else if (nombre.includes('INDIRECTA')) valIndirecta = res;
                else if (nombre.includes('TOTAL')) valTotal = res;
                else if (!valTotal) valTotal = res;
            });

            if (valDirecta && valIndirecta && (!valTotal || valTotal === '0.00' || valTotal === '0')) {
                const d = parseFloat(valDirecta);
                const i = parseFloat(valIndirecta);
                if (!isNaN(d) && !isNaN(i)) valTotal = (d + i).toFixed(2);
            } else if (valTotal && valDirecta && (!valIndirecta || valIndirecta === '0.00' || valIndirecta === '0')) {
                const t = parseFloat(valTotal);
                const d = parseFloat(valDirecta);
                if (!isNaN(t) && !isNaN(d)) valIndirecta = (t - d >= 0 ? (t - d).toFixed(2) : '0.00');
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

        // ---------------------------------------------------------------------
        // 4. PRIORIDAD: PROTEÍNAS TOTALES Y FRACCIONADAS
        // ---------------------------------------------------------------------
        const esProteinaFraccionada = (
            botonUpper.includes('PROTEINA') || examenUpper.includes('PROTEINA') ||
            botonUpper.includes('PROTEÍNA') || examenUpper.includes('PROTEÍNA') ||
            botonUpper.includes('ALBUMINA') || botonUpper.includes('ALBÚMINA')
        ) && !examenUpper.includes('HEPATIC') && !examenUpper.includes('HEPÁTIC') && !botonUpper.includes('HEPATIC') && !botonUpper.includes('HEPÁTIC');

        if (esProteinaFraccionada) {
            let valPT = '', valAlb = '', valGlob = '';

            data.forEach(item => {
                const n = (item.SubPrueba || '').toUpperCase();
                const r = (item.Resultado_Actual || '').trim();
                if (n.includes('TOTAL')) valPT = r;
                else if (n.includes('ALB')) valAlb = r;
                else if (n.includes('GLOB')) valGlob = r;
            });

            if (valAlb && valGlob && (!valPT || valPT === '0.00' || valPT === '0')) {
                const a = parseFloat(valAlb);
                const g = parseFloat(valGlob);
                if (!isNaN(a) && !isNaN(g)) valPT = (a + g).toFixed(2);
            } else if (valPT && valAlb && (!valGlob || valGlob === '0.00' || valGlob === '0')) {
                const pt = parseFloat(valPT);
                const a = parseFloat(valAlb);
                if (!isNaN(pt) && !isNaN(a)) valGlob = (pt - a >= 0 ? (pt - a).toFixed(2) : '0.00');
            }

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-indigo-50 text-indigo-900 font-bold px-4 py-2 border-b border-indigo-200 flex items-center justify-between">
                        <span class="flex items-center"><i class="fa-solid fa-flask-vial text-indigo-600 mr-2"></i> PROTEÍNAS TOTALES Y FRACCIONADAS</span>
                        <span class="text-xs bg-indigo-200 text-indigo-900 px-2 py-0.5 rounded font-bold">Colorimétrico</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Fracción</th>
                                <th class="p-2.5 text-center w-48">Resultado (g/dL)</th>
                                <th class="p-2.5 text-center">Referencial</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">ALBÚMINA</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" id="input_prot_albumina"
                                           class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${valAlb}"
                                           oninput="ModuloBioquimica.calcularProteinasPorSuma()"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="ALBÚMINA"
                                           data-referencial="Adultos: 3.97 - 4.94 g/dL"
                                           placeholder="0.00">
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs font-mono">Adultos: 3.97 - 4.94 g/dL</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">GLOBULINAS</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" id="input_prot_globulina"
                                           class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${valGlob}"
                                           oninput="ModuloBioquimica.calcularProteinasPorSuma()"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="GLOBULINAS"
                                           data-referencial="Adultos: 2.6 - 3.8 g/dL"
                                           placeholder="0.00">
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs font-mono">Adultos: 2.6 - 3.8 g/dL</td>
                            </tr>
                            <tr class="hover:bg-gray-50 bg-blue-50/30">
                                <td class="p-3 font-bold text-gray-700">
                                    PROTEÍNAS TOTALES
                                    <span class="text-xs text-blue-600 block font-normal" style="font-size: 10px;">(Suma = Albúmina + Globulinas)</span>
                                </td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" id="input_prot_total"
                                           class="w-full bg-white border border-blue-400 rounded px-2.5 py-1.5 text-center font-bold text-blue-800 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono shadow-xs"
                                           value="${valPT}"
                                           oninput="ModuloBioquimica.calcularGlobulinasPorResta()"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="PROTEÍNAS TOTALES"
                                           data-referencial="Adultos: 6.6 - 8.7 g/dL"
                                           placeholder="0.00">
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs font-mono">Adultos: 6.6 - 8.7 g/dL</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // ---------------------------------------------------------------------
        // 5. PRIORIDAD: TRANSAMINASAS DUALES (TGO Y TGP)
        // ---------------------------------------------------------------------
        const esTransaminasas = (botonUpper.includes('TRANSAMINASA') || examenUpper.includes('TRANSAMINASA') || (botonUpper.includes('GOT') && botonUpper.includes('GPT'))) &&
                                !botonUpper.includes('HEPATIC') && !examenUpper.includes('HEPATIC') && !botonUpper.includes('HEPÁTIC') && !examenUpper.includes('HEPÁTIC');

        if (esTransaminasas) {
            let valTgo = '', valTgp = '';

            data.forEach(item => {
                const n = (item.SubPrueba || '').toUpperCase();
                const r = (item.Resultado_Actual || '').trim();
                if (n.includes('T.G.O') || n.includes('TGO') || n.includes('AST') || n.includes('ASPARTATO')) valTgo = r;
                else if (n.includes('T.G.P') || n.includes('TGP') || n.includes('ALT') || n.includes('ALANINA')) valTgp = r;
            });

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-blue-50 text-blue-900 font-bold px-4 py-2 border-b border-blue-200 flex items-center justify-between">
                        <span class="flex items-center"><i class="fa-solid fa-flask text-blue-600 mr-2"></i> DOSAJE DE TRANSAMINASAS (TGO / TGP)</span>
                        <span class="text-xs bg-blue-200 text-blue-900 px-2 py-0.5 rounded font-bold">Cinético UV (IFCC)</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Enzima</th>
                                <th class="p-2.5 text-center w-48">Resultado (U/L)</th>
                                <th class="p-2.5 text-center">Referencial</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">TRANSAMINASA T.G.O. (AST)</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text"
                                           class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${valTgo}"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="TRANSAMINASA T.G.O. (AST)"
                                           data-referencial="Varones: Hasta 38 U/L | Mujeres: Hasta 32 U/L"
                                           placeholder="0.00">
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs font-mono">Varones: Hasta 38 | Mujeres: Hasta 32 U/L</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-700">TRANSAMINASA T.G.P. (ALT)</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text"
                                           class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                           value="${valTgp}"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="TRANSAMINASA T.G.P. (ALT)"
                                           data-referencial="Varones: Hasta 41 U/L | Mujeres: Hasta 33 U/L"
                                           placeholder="0.00">
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs font-mono">Varones: Hasta 41 | Mujeres: Hasta 33 U/L</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // ---------------------------------------------------------------------
        // 6. PARÁMETROS GENERALES DE BIOQUÍMICA (GLUCOSA, UREA, CREATININA, ETC.)
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
            let ref = (item.ValorReferencial && item.ValorReferencial !== 'N/A') ? item.ValorReferencial : 'N/A';
            let met = item.Metodo || metodoGeneral;

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
                    <span class="flex items-center"><i class="fa-solid fa-flask text-blue-600 mr-2"></i> DOSAJE DE ${tituloLimpio}</span>
                    <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded font-bold">${metodoGeneral}</span>
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

    // -------------------------------------------------------------------------
    // CÁLCULOS AUTOMÁTICOS REACTIVOS
    // -------------------------------------------------------------------------

    // FÓRMULA FRIEDELWALD (PERFIL LIPÍDICO)
    calcularPerfilLipidico: function() {
        const inputCol  = document.getElementById('input_lip_colesterol');
        const inputTri  = document.getElementById('input_lip_trigliceridos');
        const inputHdl  = document.getElementById('input_lip_hdl');
        const inputLdl  = document.getElementById('input_lip_ldl');
        const inputVldl = document.getElementById('input_lip_vldl');

        if (!inputTri || !inputVldl) return;

        const col = parseFloat(inputCol?.value);
        const tri = parseFloat(inputTri?.value);
        const hdl = parseFloat(inputHdl?.value);

        let vldl = 0;
        if (!isNaN(tri) && tri > 0) {
            vldl = parseFloat((tri / 5).toFixed(2));
            inputVldl.value = vldl.toFixed(2);
        }

        if (inputLdl && !isNaN(col) && !isNaN(hdl) && vldl > 0) {
            const ldl = col - hdl - vldl;
            inputLdl.value = ldl >= 0 ? ldl.toFixed(2) : '0.00';
        }
    },

    // FÓRMULA PROTEÍNAS 1: Total = Albúmina + Globulinas
    calcularProteinasPorSuma: function() {
        const inputPT   = document.getElementById('input_prot_total');
        const inputAlb  = document.getElementById('input_prot_albumina');
        const inputGlob = document.getElementById('input_prot_globulina');

        if (!inputPT || !inputAlb || !inputGlob) return;

        const a = parseFloat(inputAlb.value);
        const g = parseFloat(inputGlob.value);

        if (!isNaN(a) && !isNaN(g)) {
            inputPT.value = (a + g).toFixed(2);
        } else if (!isNaN(a) && isNaN(g)) {
            inputPT.value = a.toFixed(2);
        } else if (isNaN(a) && !isNaN(g)) {
            inputPT.value = g.toFixed(2);
        }
    },

    // FÓRMULA PROTEÍNAS 2: Globulinas = Total - Albúmina
    calcularGlobulinasPorResta: function() {
        const inputPT   = document.getElementById('input_prot_total');
        const inputAlb  = document.getElementById('input_prot_albumina');
        const inputGlob = document.getElementById('input_prot_globulina');

        if (!inputPT || !inputAlb || !inputGlob) return;

        const pt = parseFloat(inputPT.value);
        const a  = parseFloat(inputAlb.value);

        if (!isNaN(pt) && !isNaN(a)) {
            const glob = pt - a;
            inputGlob.value = glob >= 0 ? glob.toFixed(2) : '0.00';
        }
    },

    // FÓRMULAS DE BILIRRUBINAS
    calcularBilirrubinasPorFraccion: function() {
        const inputTot = document.getElementById('input_bili_total');
        const inputDir = document.getElementById('input_bili_directa');
        const inputInd = document.getElementById('input_bili_indirecta');

        if (!inputTot || !inputDir || !inputInd) return;

        const d = parseFloat(inputDir.value);
        const i = parseFloat(inputInd.value);

        if (!isNaN(d) && !isNaN(i)) inputTot.value = (d + i).toFixed(2);
        else if (!isNaN(d) && isNaN(i)) inputTot.value = d.toFixed(2);
        else if (isNaN(d) && !isNaN(i)) inputTot.value = i.toFixed(2);
    },

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
    },

    // FÓRMULAS DEL PERFIL HEPÁTICO EN TIEMPO REAL
    calcularHepaticoProteinas: function() {
        const a = parseFloat(document.getElementById('input_hep_albumina')?.value);
        const g = parseFloat(document.getElementById('input_hep_globulina')?.value);
        const ptInput = document.getElementById('input_hep_pt');

        if (!ptInput) return;
        if (!isNaN(a) && !isNaN(g)) ptInput.value = (a + g).toFixed(2);
        else if (!isNaN(a) && isNaN(g)) ptInput.value = a.toFixed(2);
        else if (isNaN(a) && !isNaN(g)) ptInput.value = g.toFixed(2);
    },

    calcularHepaticoGlobulinaPorPT: function() {
        const pt = parseFloat(document.getElementById('input_hep_pt')?.value);
        const a  = parseFloat(document.getElementById('input_hep_albumina')?.value);
        const gInput = document.getElementById('input_hep_globulina');

        if (!gInput) return;
        if (!isNaN(pt) && !isNaN(a)) {
            const glob = pt - a;
            gInput.value = glob >= 0 ? glob.toFixed(2) : '0.00';
        }
    },

    calcularHepaticoBilirrubinas: function() {
        const d = parseFloat(document.getElementById('input_hep_bili_directa')?.value);
        const i = parseFloat(document.getElementById('input_hep_bili_indirecta')?.value);
        const totInput = document.getElementById('input_hep_bili_total');

        if (!totInput) return;
        if (!isNaN(d) && !isNaN(i)) totInput.value = (d + i).toFixed(2);
        else if (!isNaN(d) && isNaN(i)) totInput.value = d.toFixed(2);
        else if (isNaN(d) && !isNaN(i)) totInput.value = i.toFixed(2);
    }
};