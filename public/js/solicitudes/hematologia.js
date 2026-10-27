// public/js/solicitudes/hematologia.js
// Manejo exclusivo de Hematología, Coagulación, Hemoglobina, Frotis y Banco Sanguíneo

const ModuloHematologia = {

    // 1. EVALUAR SI EL EXAMEN PERTENECE A HEMATOLOGÍA
    esHematologia: function(nombreExamen, botonPresionado) {
        const examen = (nombreExamen || '').toUpperCase();
        const boton = (botonPresionado || '').toUpperCase();

        return boton.includes('HEMATOCRITO') ||
               boton.includes('HEMOGLOBINA') ||
               boton.includes('SANGRIA') ||
               boton.includes('PROTROMBINA') ||
               boton.includes('LAMINA PERIFERICA') ||
               boton.includes('SEDIMENTACION') ||
               boton.includes('VSG') ||
               boton.includes('PLAQUETA') ||
               boton.includes('GRUPO SANGUINEO') ||
               boton.includes('FACTOR RH') ||
               examen.includes('GRUPO SANGUINEO') ||
               examen.includes('PLAQUETA') ||
               examen.includes('HEMOGRAMA') ||
               examen.includes('COAGULACION');
    },

    // 2. CONSTRUCTOR PRINCIPAL DE PLANTILLA
    renderizar: function(data, botonPresionado, nombreExamenOriginal, idMovimiento, idOrden) {
        const boton = (botonPresionado || '').toUpperCase().trim();
        const examen = (nombreExamenOriginal || '').toUpperCase().trim();
        const cptPadre = data[0]?.idProductoCPT || 0;

        // Mapeo de resultados previos guardados en BD
        const mapaGuardados = {};
        data.forEach(r => {
            const k = (r.SubPrueba || '').toUpperCase().trim();
            if (k) mapaGuardados[k] = (r.Resultado_Actual || '').trim();
        });

        // A. HEMOGLOBINA INDIVIDUAL
        if (boton.includes('HEMOGLOBINA') && !examen.includes('HEMOGRAMA')) {
            const item = data.find(r => (r.SubPrueba || '').toUpperCase().includes('HEMOGLOBINA')) || data[0];
            const resultadoActual = (item.Resultado_Actual || '').trim();
            const valRef = (item.ValorReferencial && item.ValorReferencial !== 'N/A') 
                ? item.ValorReferencial 
                : 'Varones: 14.0 - 18.0 g/dL | Mujeres: 12.0 - 16.0 g/dL';
            const metodo = item.Metodo || 'Automatizado';

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-gray-100 text-gray-800 font-bold px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                        <span><i class="fa-solid fa-droplet text-red-600 mr-2"></i> DOSAJE DE HEMOGLOBINA</span>
                        <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded font-bold">${metodo}</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Componente</th>
                                <th class="p-2.5 text-center w-40">Resultado (g/dL)</th>
                                <th class="p-2.5 text-center">Referencial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="p-3 font-bold text-gray-700">HEMOGLOBINA</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
                                           value="${resultadoActual}"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="HEMOGLOBINA"
                                           data-referencial="${valRef}"
                                           placeholder="Ej: 14.2">
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs">${valRef}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // B. HEMATOCRITO INDIVIDUAL
        if (boton.includes('HEMATOCRITO') && !examen.includes('HEMOGRAMA')) {
            const item = data.find(r => (r.SubPrueba || '').toUpperCase().includes('HEMATOCRITO')) || data[0];
            const resultadoActual = (item.Resultado_Actual || '').trim();
            const valRef = (item.ValorReferencial && item.ValorReferencial !== 'N/A') 
                ? item.ValorReferencial 
                : 'Varones: 42 - 54 % | Mujeres: 37 - 47 %';
            const metodo = item.Metodo || 'Automatizado';

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-red-50 text-red-800 font-bold px-4 py-2 border-b border-red-100 flex items-center justify-between">
                        <span><i class="fa-solid fa-droplet text-red-600 mr-2"></i> DOSAJE DE HEMATOCRITO</span>
                        <span class="text-xs bg-red-200 text-red-900 px-2 py-0.5 rounded font-bold">${metodo}</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Componente</th>
                                <th class="p-2.5 text-center w-40">Resultado (%)</th>
                                <th class="p-2.5 text-center">Referencial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="p-3 font-bold text-gray-700">HEMATOCRITO</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
                                           value="${resultadoActual}"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="HEMATOCRITO"
                                           data-referencial="${valRef}"
                                           placeholder="Ej: 43 %">
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs">${valRef}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // C. TIEMPO DE SANGRÍA (UNA SOLA FILA LIMPIA, EVITA DUPLICADOS)
        if (boton.includes('SANGRIA') && !examen.includes('COAGULACION')) {
            const item = data[0];
            const resultadoActual = (item.Resultado_Actual || '').trim();
            const valRef = 'Hasta 3.5 minutos';
            const metodo = item.Metodo || 'Método de Duke';

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-yellow-50 text-yellow-900 font-bold px-4 py-2 border-b border-yellow-200 flex items-center justify-between">
                        <span><i class="fa-solid fa-stopwatch text-yellow-600 mr-2"></i> TIEMPO DE SANGRÍA</span>
                        <span class="text-xs bg-yellow-200 text-yellow-900 px-2 py-0.5 rounded font-bold">${metodo}</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Parámetro</th>
                                <th class="p-2.5 text-center w-48">Resultado</th>
                                <th class="p-2.5 text-center">Referencial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="p-3 font-bold text-gray-700">Tiempo de sangría</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
                                           value="${resultadoActual}"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="TIEMPO DE SANGRIA"
                                           data-referencial="${valRef}"
                                           placeholder="Ej: 2 min 00 seg">
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs">${valRef}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // D. GRUPO SANGUÍNEO Y FACTOR RH (CON SELECT NATIVO)
        if (boton.includes('GRUPO SANGUINEO') || boton.includes('FACTOR RH')) {
            const item = data[0];
            const resultadoActual = (item.Resultado_Actual || '').trim().toUpperCase();
            const metodo = item.Metodo || 'Aglutinación Directa';
            const opciones = [
                'O POSITIVO (O+)',
                'O NEGATIVO (O-)',
                'A POSITIVO (A+)',
                'A NEGATIVO (A-)',
                'B POSITIVO (B+)',
                'B NEGATIVO (B-)',
                'AB POSITIVO (AB+)',
                'AB NEGATIVO (AB-)'
            ];

            let optionsHtml = `<option value="">-- Seleccionar Grupo y Factor --</option>`;
            opciones.forEach(opt => {
                const selected = resultadoActual.includes(opt.split(' ')[0]) || resultadoActual === opt ? 'selected' : '';
                optionsHtml += `<option value="${opt}" ${selected}>${opt}</option>`;
            });

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-indigo-50 text-indigo-900 font-bold px-4 py-2 border-b border-indigo-200 flex items-center justify-between">
                        <span><i class="fa-solid fa-shield-virus text-indigo-600 mr-2"></i> GRUPO SANGUÍNEO Y FACTOR RH</span>
                        <span class="text-xs bg-indigo-200 text-indigo-900 px-2 py-0.5 rounded font-bold">${metodo}</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Determinación</th>
                                <th class="p-2.5 text-center w-64">Resultado Inmunohematológico</th>
                                <th class="p-2.5 text-center">Referencial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="p-3 font-bold text-gray-700">GRUPO SANGUÍNEO Y FACTOR RH</td>
                                <td class="p-2.5 text-center">
                                    <select class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                                            data-movimiento="${idMovimiento}"
                                            data-orden="${idOrden}"
                                            data-cpt="${cptPadre}"
                                            data-nombre="GRUPO SANGUINEO Y FACTOR RH"
                                            data-referencial="Sistema ABO / Factor Rh">
                                        ${optionsHtml}
                                    </select>
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs">Sistema ABO / Factor Rh</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // E. TIEMPO DE PROTROMBINA INDIVIDUAL (TP)
        if (boton.includes('PROTROMBINA') && !examen.includes('COAGULACION')) {
            const item = data[0];
            const resultadoActual = (item.Resultado_Actual || '').trim();
            const valRef = item.ValorReferencial && item.ValorReferencial !== 'N/A' ? item.ValorReferencial : '< 14.0 segundos';
            const metodo = item.Metodo || 'Manual';

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <div class="bg-blue-50 text-blue-900 font-bold px-4 py-2 border-b border-blue-200 flex items-center justify-between">
                        <span><i class="fa-solid fa-vial text-blue-600 mr-2"></i> TIEMPO DE PROTROMBINA (TP)</span>
                        <span class="text-xs bg-blue-200 text-blue-900 px-2 py-0.5 rounded font-bold">${metodo}</span>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                            <tr>
                                <th class="p-2.5">Parámetro</th>
                                <th class="p-2.5 text-center w-48">Resultado</th>
                                <th class="p-2.5 text-center">Referencial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="p-3 font-bold text-gray-700">Tiempo de protrombina</td>
                                <td class="p-2 w-48 text-center">
                                    <input type="text" class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
                                           value="${resultadoActual}"
                                           data-movimiento="${idMovimiento}"
                                           data-orden="${idOrden}"
                                           data-cpt="${cptPadre}"
                                           data-nombre="TIEMPO DE PROTROMBINA"
                                           data-referencial="${valRef}"
                                           placeholder="Ej: 12.5 seg">
                                </td>
                                <td class="p-3 text-center text-gray-500 text-xs">${valRef}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
        }

        // F. HEMOGRAMA COMPLETO SÁBANA
        if (examen.includes('HEMOGRAMA')) {
            const estructuraHemograma = [
                { tipo: 'header', label: 'EXAMEN DE HEMOGRAMA', bg: 'bg-gray-100 text-gray-800' },
                { tipo: 'row', label: 'HEMOGLOBINA', ref: '14.0 - 18.0 g/dL (Hombres)', met: 'Automatizado', key: 'HEMOGLOBINA' },
                { tipo: 'row', label: 'HEMATOCRITO', ref: '42.0 - 54.0 % (Hombres)', met: 'Automatizado', key: 'HEMATOCRITO' },
                { tipo: 'row', label: 'HEMATIES', ref: '3 800 000 - 6 300 000 /mm3', met: 'Automatizado', key: 'HEMATIES' },
                { tipo: 'row', label: 'LEUCOCITOS', ref: '4 000 - 10 000 /mm3', met: 'Automatizado', key: 'LEUCOCITOS' },

                { tipo: 'header', label: 'RECUENTO DIFERENCIAL', bg: 'bg-blue-50 text-blue-900' },
                { tipo: 'row', label: 'ABASTONADOS', ref: '0.0 - 5.0 %', met: 'Manual', key: 'ABASTONADOS' },
                { tipo: 'row', label: 'SEGMENTADOS', ref: '55.0 - 75.0 %', met: 'Manual', key: 'SEGMENTADOS' },
                { tipo: 'row', label: 'MONOCITOS', ref: '0.0 - 8.0 %', met: 'Manual', key: 'MONOCITOS' },
                { tipo: 'row', label: 'LINFOCITOS', ref: '25.0 - 35.0 %', met: 'Manual', key: 'LINFOCITOS' },
                { tipo: 'row', label: 'EOSINOFILOS', ref: '0.0 - 4.0 %', met: 'Manual', key: 'EOSINOFILOS' },
                { tipo: 'row', label: 'BASOFILOS', ref: '0.0 - 2.0 %', met: 'Manual', key: 'BASOFILOS' },

                { tipo: 'header', label: 'SERIE PLAQUETARIA Y CONSTANTES CORPUSCULARES', bg: 'bg-green-50 text-green-900' },
                { tipo: 'row', label: 'PLAQUETAS', ref: '150 000 - 450 000 /mm3', met: 'Automatizado', key: 'PLAQUETAS' },
                { tipo: 'row', label: 'VCM', ref: '80.0 - 100.0 fL', met: 'Calculado', key: 'VCM' },
                { tipo: 'row', label: 'CHM', ref: '27.0 - 33.0 pg', met: 'Calculado', key: 'CHM' },
                { tipo: 'row', label: 'CHCM', ref: '32.0 - 36.0 g/dL', met: 'Calculado', key: 'CHCM' }
            ];

            let htmlHemo = '';
            estructuraHemograma.forEach(item => {
                if (item.tipo === 'header') {
                    htmlHemo += `
                        <tr class="${item.bg} font-bold text-center border-t border-b border-gray-200">
                            <td colspan="3" class="py-1.5 uppercase tracking-wider text-xs">${item.label}</td>
                        </tr>`;
                } else {
                    const val = mapaGuardados[item.key] || '';
                    htmlHemo += `
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="p-2 font-bold text-gray-700">${item.label}</td>
                            <td class="p-1.5 text-center w-36">
                                <input type="text" class="w-full bg-blue-50 border border-gray-300 rounded px-2 py-1 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
                                       value="${val}"
                                       data-movimiento="${idMovimiento}"
                                       data-orden="${idOrden}"
                                       data-cpt="${cptPadre}"
                                       data-nombre="${item.label}"
                                       data-referencial="${item.ref}"
                                       placeholder="0.00">
                            </td>
                            <td class="p-2 text-center text-gray-500 text-xs">
                                ${item.ref}
                                <span class="text-xs text-gray-400 block font-mono" style="font-size: 10px;">${item.met}</span>
                            </td>
                        </tr>`;
                }
            });

            return `
                <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-100 text-gray-700 font-bold border-b border-gray-200 sticky top-0 z-10 text-xs">
                            <tr>
                                <th class="p-2.5">Analito</th>
                                <th class="p-2.5 text-center w-36">Resultado</th>
                                <th class="p-2.5 text-center">Referencial</th>
                            </tr>
                        </thead>
                        <tbody>${htmlHemo}</tbody>
                    </table>
                </div>`;
        }

        // FALLBACK GENERAL DE HEMATOLOGÍA
        let htmlRows = '';
        data.forEach(item => {
            const analito = item.SubPrueba || 'ANALITO';
            const val = (item.Resultado_Actual || '').trim();
            const ref = item.ValorReferencial || 'N/A';

            htmlRows += `
                <tr class="hover:bg-gray-50 border-b border-gray-100">
                    <td class="p-2.5 font-bold text-gray-700 uppercase">${analito}</td>
                    <td class="p-1.5 text-center w-40">
                        <input type="text" class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1.5 text-center font-bold text-blue-700 input-resultado text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
                               value="${val}"
                               data-movimiento="${idMovimiento}"
                               data-orden="${idOrden}"
                               data-cpt="${cptPadre}"
                               data-nombre="${analito}"
                               data-referencial="${ref}"
                               placeholder="0.00">
                    </td>
                    <td class="p-2.5 text-center text-gray-500 text-xs">${ref}</td>
                </tr>`;
        });

        return `
            <div class="border border-gray-200 rounded-lg overflow-hidden font-mono text-xs">
                <div class="bg-gray-100 text-gray-800 font-bold px-4 py-2 border-b border-gray-200 flex justify-between items-center">
                    <span><i class="fa-solid fa-vials text-blue-600 mr-2"></i> ${nombreExamenOriginal}</span>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 text-xs">
                        <tr>
                            <th class="p-2.5">Analito</th>
                            <th class="p-2.5 text-center w-40">Resultado</th>
                            <th class="p-2.5 text-center">Referencial</th>
                        </tr>
                    </thead>
                    <tbody>${htmlRows}</tbody>
                </table>
            </div>`;
    }
};