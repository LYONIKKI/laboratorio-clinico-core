// public/js/solicitudes/inmunologia.js
// Manejo de Serología, Inmunología, HIV, RPR/VDRL y Proteína C Reactiva

const ModuloInmunologia = {

    esInmunologia: function(nombreExamen, botonPresionado) {
        const examen = (nombreExamen || '').toUpperCase();
        const boton = (botonPresionado || '').toUpperCase();

        return boton.includes('HIV') ||
               boton.includes('SIFILIS') ||
               boton.includes('VDRL') ||
               boton.includes('RPR') ||
               boton.includes('PROTEINA C') ||
               boton.includes('PCR') ||
               boton.includes('AGLUTINACION') ||
               boton.includes('WIDAL') ||
               examen.includes('HIV') ||
               examen.includes('SIFILIS') ||
               examen.includes('VDRL') ||
               examen.includes('PROTEINA C');
    },

    renderizar: function(data, botonPresionado, nombreExamenOriginal, idMovimiento, idOrden) {
        const boton = (botonPresionado || '').toUpperCase().trim();
        const cptPadre = data[0]?.idProductoCPT || 0;
        const item = data[0];
        const analitoNombre = item.SubPrueba || item.Examen_Principal || 'PRUEBA INMUNOLÓGICA';
        const valorActual = (item.Resultado_Actual || '').trim().toUpperCase();

        // 1. ANTICUERPOS PARA HIV I-II (SELECT NATIVO)
        if (boton.includes('HIV') || analitoNombre.toUpperCase().includes('HIV')) {
            const valRef = 'NO REACTIVO';
            const metodo = item.Metodo || 'Inmunocromatografía / Quimioluminiscencia';
            const opciones = ['NO REACTIVO', 'REACTIVO', 'INDETERMINADO'];

            let selectHtml = `<option value="">-- Seleccionar --</option>`;
            opciones.forEach(opt => {
                const selected = (valorActual === opt || (valorActual === '' && opt === 'NO REACTIVO')) ? 'selected' : '';
                selectHtml += `<option value="${opt}" ${selected}>${opt}</option>`;
            });

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-indigo-50 text-indigo-900 font-bold px-4 py-2 border-b border-indigo-200 flex items-center justify-between">
                        <span><i class="fa-solid fa-shield-virus text-indigo-600 mr-2"></i> ANTICUERPOS PARA HIV I-II</span>
                        <span class="text-xs bg-indigo-200 text-indigo-900 px-2 py-0.5 rounded font-bold">${metodo}</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Determinación</th>
                                <th class="p-2.5 text-center w-56">Resultado Serológico</th>
                                <th class="p-2.5 text-center">Referencial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="p-3 font-bold text-gray-700">ANTICUERPOS PARA HIV I-II</td>
                                <td class="p-2.5 text-center">
                                    <select class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                                            data-movimiento="${idMovimiento}"
                                            data-orden="${idOrden}"
                                            data-cpt="${cptPadre}"
                                            data-nombre="ANTICUERPOS PARA HIV I-II"
                                            data-referencial="${valRef}">
                                        ${selectHtml}
                                    </select>
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs">${valRef}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // 2. PRUEBA DE SÍFILIS (VDRL / RPR)
        if (boton.includes('SIFILIS') || boton.includes('VDRL') || boton.includes('RPR')) {
            const valRef = 'NO REACTIVO';
            const metodo = item.Metodo || 'Floculación / RPR Carbón';
            const opciones = ['NO REACTIVO', 'REACTIVO', 'REACTIVO 1 DIL', 'REACTIVO 2 DIL', 'REACTIVO 4 DIL', 'REACTIVO 8 DIL', 'REACTIVO 16 DIL'];

            let selectHtml = `<option value="">-- Seleccionar --</option>`;
            opciones.forEach(opt => {
                const selected = (valorActual === opt || (valorActual === '' && opt === 'NO REACTIVO')) ? 'selected' : '';
                selectHtml += `<option value="${opt}" ${selected}>${opt}</option>`;
            });

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-indigo-50 text-indigo-900 font-bold px-4 py-2 border-b border-indigo-200 flex items-center justify-between">
                        <span><i class="fa-solid fa-virus text-indigo-600 mr-2"></i> PRUEBA NO TREPONÉMICA (VDRL / RPR)</span>
                        <span class="text-xs bg-indigo-200 text-indigo-900 px-2 py-0.5 rounded font-bold">${metodo}</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Determinación</th>
                                <th class="p-2.5 text-center w-56">Resultado Serológico</th>
                                <th class="p-2.5 text-center">Referencial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="p-3 font-bold text-gray-700">PRUEBA DE SÍFILIS (RPR / VDRL)</td>
                                <td class="p-2.5 text-center">
                                    <select class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                                            data-movimiento="${idMovimiento}"
                                            data-orden="${idOrden}"
                                            data-cpt="${cptPadre}"
                                            data-nombre="PRUEBA DE SIFILIS (RPR/VDRL)"
                                            data-referencial="${valRef}">
                                        ${selectHtml}
                                    </select>
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs">${valRef}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // 3. PROTEÍNA C REACTIVA (PCR) CUANTITATIVA / CUALITATIVA
        const valRefPcr = (item.ValorReferencial && item.ValorReferencial !== 'N/A') 
            ? item.ValorReferencial 
            : 'Menor a 6.0 mg/L (Negativo)';
        const metodoPcr = item.Metodo || 'Inmunoturbidimetría / Látex';

        return `
            <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                <div class="bg-indigo-50 text-indigo-900 font-bold px-4 py-2 border-b border-indigo-200 flex items-center justify-between">
                    <span><i class="fa-solid fa-vial text-indigo-600 mr-2"></i> PROTEÍNA C REACTIVA (PCR)</span>
                    <span class="text-xs bg-indigo-200 text-indigo-900 px-2 py-0.5 rounded font-bold">${metodoPcr}</span>
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
                        <tr>
                            <td class="p-3 font-bold text-gray-700">${analitoNombre}</td>
                            <td class="p-2 w-48 text-center">
                                <input type="text" class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 font-mono"
                                       value="${item.Resultado_Actual || ''}"
                                       data-movimiento="${idMovimiento}"
                                       data-orden="${idOrden}"
                                       data-cpt="${cptPadre}"
                                       data-nombre="${analitoNombre}"
                                       data-referencial="${valRefPcr}"
                                       placeholder="Ej: < 6.0 mg/L">
                            </td>
                            <td class="p-3 text-center text-gray-500 text-xs">${valRefPcr}</td>
                        </tr>
                    </tbody>
                </table>
            </div>`;
    }
};