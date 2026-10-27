// public/js/solicitudes/microbiologia.js
// Manejo exclusivo de Cultivos, Urocultivo, Sedimento y Antibiograma Oficial SIGH

const ModuloMicrobiologia = {

    // 1. Evaluador
    esMicrobiologia: function(nombreExamen, botonPresionado) {
        const examen = (nombreExamen || '').toUpperCase();
        const boton  = (botonPresionado || '').toUpperCase();

        return examen.includes('UROCULTIVO') || examen.includes('CULTIVO') ||
               examen.includes('ANTIBIOGRAMA') || boton.includes('CULTIVO');
    },

    // 2. Renderizado de la plantilla oficial
    renderizar: function(data, botonPresionado, nombreExamenOriginal, idMovimiento, idOrden) {
        const cpt = data[0]?.idProductoCPT || 0;

        // Mapeo de valores previos ya guardados en dbo.LabResultadoDetalle
        const valoresGuardados = {};
        data.forEach(item => {
            const nom = (item.SubPrueba || '').trim().toUpperCase();
            valoresGuardados[nom] = (item.Resultado_Actual || '').trim();
        });

        const getVal = (clave) => valoresGuardados[clave] || '';

        // Separación de antibióticos guardados (si existen, separados por salto de línea)
        const sensPrevios = (getVal('ANTIBIOTICOS SENSIBLES') || '').split('\n').filter(x => x.trim() !== '');
        const intPrevios  = (getVal('ANTIBIOTICOS INTERMEDIOS') || '').split('\n').filter(x => x.trim() !== '');
        const resPrevios  = (getVal('ANTIBIOTICOS RESISTENTES') || '').split('\n').filter(x => x.trim() !== '');

        const filasMax = Math.max(sensPrevios.length, intPrevios.length, resPrevios.length, 4);

        let filasAntibiogramaHtml = '';
        for (let i = 0; i < filasMax; i++) {
            const s = sensPrevios[i] || '';
            const inter = intPrevios[i] || '';
            const r = resPrevios[i] || '';

            filasAntibiogramaHtml += `
                <tr class="fila-atb border-b border-gray-200">
                    <td class="p-1 border-r border-gray-200">
                        <input type="text" class="w-full bg-green-50 border border-transparent focus:border-green-400 rounded px-2 py-1 text-xs font-mono font-bold text-green-800 atb-sensible outline-none" value="${s}" placeholder="Ej: Amikacina">
                    </td>
                    <td class="p-1 border-r border-gray-200">
                        <input type="text" class="w-full bg-yellow-50 border border-transparent focus:border-yellow-400 rounded px-2 py-1 text-xs font-mono font-bold text-yellow-800 atb-intermedio outline-none" value="${inter}" placeholder="Ej: Nitrofurantoína">
                    </td>
                    <td class="p-1 border-r border-gray-200">
                        <input type="text" class="w-full bg-red-50 border border-transparent focus:border-red-400 rounded px-2 py-1 text-xs font-mono font-bold text-red-800 atb-resistente outline-none" value="${r}" placeholder="Ej: Ampicilina">
                    </td>
                    <td class="p-1 text-center w-10">
                        <button type="button" onclick="this.closest('tr').remove(); ModuloMicrobiologia.sincronizarAntibiograma();" class="text-gray-400 hover:text-red-600 transition p-1 cursor-pointer" title="Quitar fila"><i class="fa-solid fa-xmark"></i></button>
                    </td>
                </tr>`;
        }

        return `
            <div class="space-y-4 font-mono text-xs" id="contenedor-urocultivo-form">

                <!-- 1. CABECERA UROCULTIVO -->
                <div class="border border-gray-300 rounded-xl overflow-hidden bg-white shadow-xs">
                    <div class="bg-blue-800 text-white font-black px-4 py-2 uppercase tracking-wider text-center text-xs">
                        UROCULTIVO
                    </div>
                    <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-gray-700 uppercase mb-1" style="font-size: 11px;">Bacteria Aislada:</label>
                            <input type="text" class="w-full bg-blue-50 border border-gray-300 rounded-lg px-3 py-1.5 font-bold text-blue-900 input-resultado text-xs outline-none focus:ring-2 focus:ring-blue-500"
                                   value="${getVal('BACTERIA AISLADA')}"
                                   data-movimiento="${idMovimiento}" data-orden="${idOrden}" data-cpt="${cpt}"
                                   data-nombre="BACTERIA AISLADA" data-referencial="NEGATIVO"
                                   placeholder="Ej: Escherichia coli">
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 uppercase mb-1" style="font-size: 11px;">N° Colonia (Recuento UFC/mL):</label>
                            <input type="text" class="w-full bg-blue-50 border border-gray-300 rounded-lg px-3 py-1.5 font-bold text-blue-900 input-resultado text-xs outline-none focus:ring-2 focus:ring-blue-500"
                                   value="${getVal('N° COLONIA') || getVal('RECUENTO COLONIAS')}"
                                   data-movimiento="${idMovimiento}" data-orden="${idOrden}" data-cpt="${cpt}"
                                   data-nombre="N° COLONIA" data-referencial="< 10,000 UFC/mL"
                                   placeholder="Ej: > 100,000 UFC/mL">
                        </div>
                    </div>
                </div>

                <!-- 2. ANÁLISIS DE SEDIMENTO -->
                <div class="border border-gray-300 rounded-xl overflow-hidden bg-white shadow-xs">
                    <div class="bg-gray-200 text-gray-800 font-black px-4 py-1.5 uppercase tracking-wider text-center text-xs">
                        ANÁLISIS DE SEDIMENTO
                    </div>
                    <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 uppercase mb-1" style="font-size: 10px;">Leucocitos:</label>
                            <input type="text" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-2.5 py-1.5 font-bold text-gray-800 input-resultado outline-none focus:ring-1 focus:ring-blue-500"
                                   value="${getVal('LEUCOCITOS')}"
                                   data-movimiento="${idMovimiento}" data-orden="${idOrden}" data-cpt="${cpt}"
                                   data-nombre="LEUCOCITOS" data-referencial="0 - 2 x campo"
                                   placeholder="Ej: 20 - 30 x campo">
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 uppercase mb-1" style="font-size: 10px;">Células Epiteliales:</label>
                            <input type="text" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-2.5 py-1.5 font-bold text-gray-800 input-resultado outline-none focus:ring-1 focus:ring-blue-500"
                                   value="${getVal('CELULAS EPITELIALES')}"
                                   data-movimiento="${idMovimiento}" data-orden="${idOrden}" data-cpt="${cpt}"
                                   data-nombre="CELULAS EPITELIALES" data-referencial="Escasas"
                                   placeholder="Ej: Escasas / Moderadas">
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 uppercase mb-1" style="font-size: 10px;">Bacterias:</label>
                            <input type="text" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-2.5 py-1.5 font-bold text-gray-800 input-resultado outline-none focus:ring-1 focus:ring-blue-500"
                                   value="${getVal('BACTERIAS')}"
                                   data-movimiento="${idMovimiento}" data-orden="${idOrden}" data-cpt="${cpt}"
                                   data-nombre="BACTERIAS" data-referencial="Escasas"
                                   placeholder="Ej: Abundantes (+++)">
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 uppercase mb-1" style="font-size: 10px;">Hematíes:</label>
                            <input type="text" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-2.5 py-1.5 font-bold text-gray-800 input-resultado outline-none focus:ring-1 focus:ring-blue-500"
                                   value="${getVal('HEMATIES')}"
                                   data-movimiento="${idMovimiento}" data-orden="${idOrden}" data-cpt="${cpt}"
                                   data-nombre="HEMATIES" data-referencial="0 - 2 x campo"
                                   placeholder="Ej: 1 - 2 x campo">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block font-bold text-gray-700 uppercase mb-1" style="font-size: 10px;">Otros:</label>
                            <input type="text" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-2.5 py-1.5 font-bold text-gray-800 input-resultado outline-none focus:ring-1 focus:ring-blue-500"
                                   value="${getVal('OTROS')}"
                                   data-movimiento="${idMovimiento}" data-orden="${idOrden}" data-cpt="${cpt}"
                                   data-nombre="OTROS" data-referencial="---"
                                   placeholder="Ej: Filamentos mucoides (+), Cristales...">
                        </div>
                    </div>
                </div>

                <!-- 3. ANTIBIOGRAMA -->
                <div class="border border-gray-300 rounded-xl overflow-hidden bg-white shadow-xs">
                    <div class="bg-gray-200 text-gray-800 font-black px-4 py-1.5 uppercase tracking-wider flex justify-between items-center text-xs">
                        <span>ANTIBIOGRAMA</span>
                        <button type="button" onclick="ModuloMicrobiologia.agregarFilaAtb()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] px-2.5 py-1 rounded transition cursor-pointer">
                            <i class="fa-solid fa-plus mr-1"></i> Fila
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse" id="tabla-antibiograma">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700 border-b border-gray-300 font-black uppercase text-[10px] text-center">
                                    <th class="p-2 border-r border-gray-300 w-1/3 text-green-700">Antibióticos Sensibles</th>
                                    <th class="p-2 border-r border-gray-300 w-1/3 text-yellow-700">Antibióticos Intermedios</th>
                                    <th class="p-2 border-r border-gray-300 w-1/3 text-red-700">Antibióticos Resistentes</th>
                                    <th class="p-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody id="tbody-antibiograma">
                                ${filasAntibiogramaHtml}
                            </tbody>
                        </table>
                    </div>

                    <!-- Campos ocultos de sincronización para guardar en dbo.LabResultadoDetalle -->
                    <textarea id="hidden-atb-sensibles" class="hidden input-resultado" data-movimiento="${idMovimiento}" data-orden="${idOrden}" data-cpt="${cpt}" data-nombre="ANTIBIOTICOS SENSIBLES" data-referencial="SENSIBLE"></textarea>
                    <textarea id="hidden-atb-intermedios" class="hidden input-resultado" data-movimiento="${idMovimiento}" data-orden="${idOrden}" data-cpt="${cpt}" data-nombre="ANTIBIOTICOS INTERMEDIOS" data-referencial="INTERMEDIO"></textarea>
                    <textarea id="hidden-atb-resistentes" class="hidden input-resultado" data-movimiento="${idMovimiento}" data-orden="${idOrden}" data-cpt="${cpt}" data-nombre="ANTIBIOTICOS RESISTENTES" data-referencial="RESISTENTE"></textarea>
                </div>
            </div>`;
    },

    // Agregar fila dinámica a la grilla de antibiograma
    agregarFilaAtb: function() {
        const tbody = document.getElementById('tbody-antibiograma');
        if (!tbody) return;

        const tr = document.createElement('tr');
        tr.className = "fila-atb border-b border-gray-200";
        tr.innerHTML = `
            <td class="p-1 border-r border-gray-200">
                <input type="text" class="w-full bg-green-50 border border-transparent focus:border-green-400 rounded px-2 py-1 text-xs font-mono font-bold text-green-800 atb-sensible outline-none" placeholder="Antibiótico sensible...">
            </td>
            <td class="p-1 border-r border-gray-200">
                <input type="text" class="w-full bg-yellow-50 border border-transparent focus:border-yellow-400 rounded px-2 py-1 text-xs font-mono font-bold text-yellow-800 atb-intermedio outline-none" placeholder="Antibiótico intermedio...">
            </td>
            <td class="p-1 border-r border-gray-200">
                <input type="text" class="w-full bg-red-50 border border-transparent focus:border-red-400 rounded px-2 py-1 text-xs font-mono font-bold text-red-800 atb-resistente outline-none" placeholder="Antibiótico resistente...">
            </td>
            <td class="p-1 text-center w-10">
                <button type="button" onclick="this.closest('tr').remove(); ModuloMicrobiologia.sincronizarAntibiograma();" class="text-gray-400 hover:text-red-600 transition p-1 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
            </td>`;
        tbody.appendChild(tr);
    },

    // Empaqueta los fármacos por columna para guardarlos consolidados en LabResultadoDetalle
    sincronizarAntibiograma: function() {
        const sInputs = document.querySelectorAll('.atb-sensible');
        const iInputs = document.querySelectorAll('.atb-intermedio');
        const rInputs = document.querySelectorAll('.atb-resistente');

        const sList = [];
        const iList = [];
        const rList = [];

        sInputs.forEach(el => { if (el.value.trim() !== '') sList.push(el.value.trim()); });
        iInputs.forEach(el => { if (el.value.trim() !== '') iList.push(el.value.trim()); });
        rInputs.forEach(el => { if (el.value.trim() !== '') rList.push(el.value.trim()); });

        const hSens = document.getElementById('hidden-atb-sensibles');
        const hInt  = document.getElementById('hidden-atb-intermedios');
        const hRes  = document.getElementById('hidden-atb-resistentes');

        if (hSens) hSens.value = sList.join('\n');
        if (hInt)  hInt.value  = iList.join('\n');
        if (hRes)  hRes.value  = rList.join('\n');
    }
};

// Sincronizar antibióticos antes de disparar el guardado general
document.addEventListener('input', (e) => {
    if (e.target.matches('.atb-sensible, .atb-intermedio, .atb-resistente')) {
        ModuloMicrobiologia.sincronizarAntibiograma();
    }
});