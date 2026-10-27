// public/js/solicitudes/orina.js
// Manejo exclusivo de Examen Completo de Orina (Físico, Químico y Sedimento)

const ModuloOrina = {

    esOrina: function(nombreExamen, botonPresionado, cpt) {
        const examen = (nombreExamen || '').toUpperCase();
        const boton  = (botonPresionado || '').toUpperCase();
        const codigoCpt = parseInt(cpt || 0);

        return codigoCpt === 3339 || 
               examen.includes('ORINA') || 
               boton.includes('ORINA');
    },

    renderizar: function(data, botonPresionado, nombreExamenOriginal, idMovimiento, idOrden) {
        const cpt = data[0]?.idProductoCPT || 3339;

        // Mapeo de valores previos si ya existen en la BD
        const mapa = {};
        data.forEach(r => {
            const k = (r.SubPrueba || '').toUpperCase().trim();
            if (k) mapa[k] = (r.Resultado_Actual || '').trim();
        });

        const plantilla = [
            // EXAMEN FISICO
            { grupo: 'EXAMEN FÍSICO', analito: 'ASPECTO',  ref: 'Claro / Transparente', met: 'Organoléptico', default: 'TRANSPARENTE' },
            { grupo: 'EXAMEN FÍSICO', analito: 'COLOR',    ref: 'Amarillo / Pajizo',    met: 'Organoléptico', default: 'AMARILLO' },
            { grupo: 'EXAMEN FÍSICO', analito: 'ESPUMA',   ref: 'Blanca, Fugaz',        met: 'Organoléptico', default: 'BLANCA FUGAZ' },
            { grupo: 'EXAMEN FÍSICO', analito: 'REACCIÓN', ref: 'Ácida',                met: 'Tira Reactiva', default: 'ÁCIDA' },

            // EXAMEN QUIMICO
            { grupo: 'EXAMEN QUÍMICO', analito: 'DENSIDAD',          ref: '1.005 - 1.030',       met: 'Tira Reactiva', default: '1.020' },
            { grupo: 'EXAMEN QUÍMICO', analito: 'PH',                ref: '5.0 - 8.0',           met: 'Tira Reactiva', default: '6.0' },
            { grupo: 'EXAMEN QUÍMICO', analito: 'SANGRE',            ref: 'Negativo',            met: 'Tira Reactiva', default: 'NEGATIVO' },
            { grupo: 'EXAMEN QUÍMICO', analito: 'PROTEINAS',         ref: 'Negativo',            met: 'Tira Reactiva', default: 'NEGATIVO' },
            { grupo: 'EXAMEN QUÍMICO', analito: 'BILIRRUBINA',       ref: 'Negativo',            met: 'Tira Reactiva', default: 'NEGATIVO' },
            { grupo: 'EXAMEN QUÍMICO', analito: 'UROBILINOGENO',     ref: 'Normal (< 1 mg/dL)',  met: 'Tira Reactiva', default: 'NORMAL' },
            { grupo: 'EXAMEN QUÍMICO', analito: 'NITRITOS',          ref: 'Negativo',            met: 'Tira Reactiva', default: 'NEGATIVO' },
            { grupo: 'EXAMEN QUÍMICO', analito: 'CUERPOS CETOGENOS', ref: 'Negativo',            met: 'Tira Reactiva', default: 'NEGATIVO' },
            { grupo: 'EXAMEN QUÍMICO', analito: 'AC. ASCORBICO',     ref: 'Negativo / Conforme', met: 'Tira Reactiva', default: 'NEGATIVO' },
            { grupo: 'EXAMEN QUÍMICO', analito: 'GLUCOSA',           ref: 'Negativo',            met: 'Tira Reactiva', default: 'NEGATIVO' },

            // EXAMEN DE SEDIMENTO
            { grupo: 'EXAMEN DE SEDIMENTO', analito: 'HEMATIES',            ref: '0 - 2 por campo',   met: 'Microscopía', default: '0 - 1 x campo' },
            { grupo: 'EXAMEN DE SEDIMENTO', analito: 'CELULAS EPITELIALES', ref: 'Escasas / Campo',   met: 'Microscopía', default: 'ESCASAS' },
            { grupo: 'EXAMEN DE SEDIMENTO', analito: 'LEUCOCITOS',          ref: '0 - 5 por campo',   met: 'Microscopía', default: '1 - 2 x campo' },
            { grupo: 'EXAMEN DE SEDIMENTO', analito: 'CRISTALES',           ref: 'No se observan',    met: 'Microscopía', default: 'NO SE OBSERVAN' },
            { grupo: 'EXAMEN DE SEDIMENTO', analito: 'CILINDROS',           ref: 'No se observan',    met: 'Microscopía', default: 'NO SE OBSERVAN' },
            { grupo: 'EXAMEN DE SEDIMENTO', analito: 'BACTERIAS',           ref: 'Escasas',           met: 'Microscopía', default: 'ESCASAS' },
            { grupo: 'EXAMEN DE SEDIMENTO', analito: 'PIOCITOS',            ref: 'No se observan',    met: 'Microscopía', default: 'NO SE OBSERVAN' },
            { grupo: 'EXAMEN DE SEDIMENTO', analito: 'HILOS MUCOSOS',       ref: 'Negativo / Escasos',met: 'Microscopía', default: 'ESCASOS' },
            { grupo: 'EXAMEN DE SEDIMENTO', analito: 'LEVADURAS',           ref: 'Negativo',          met: 'Microscopía', default: 'NO SE OBSERVAN' },
            { grupo: 'EXAMEN DE SEDIMENTO', analito: 'OTROS',               ref: '---',               met: 'Microscopía', default: '---' }
        ];

        let html = '';
        let ultimoGrupo = '';

        plantilla.forEach(item => {
            if (item.grupo !== ultimoGrupo) {
                ultimoGrupo = item.grupo;
                let colorBarra = 'bg-blue-100 text-blue-900 border-blue-200';
                if (ultimoGrupo.includes('QUÍMICO')) colorBarra = 'bg-yellow-100 text-yellow-900 border-yellow-200';
                if (ultimoGrupo.includes('SEDIMENTO')) colorBarra = 'bg-gray-200 text-gray-800 border-gray-300';

                html += `
                    <tr class="${colorBarra} font-bold text-center border-t border-b text-[11px]">
                        <td colspan="3" class="py-1.5 uppercase tracking-wider">${ultimoGrupo}</td>
                    </tr>`;
            }

            const val = mapa[item.analito] !== undefined && mapa[item.analito] !== '' 
                ? mapa[item.analito] 
                : item.default;

            html += `
                <tr class="hover:bg-gray-50 border-b border-gray-100 text-xs">
                    <td class="p-2 font-bold text-gray-700">${item.analito}</td>
                    <td class="p-1 text-center w-52">
                        <input type="text" class="w-full bg-blue-50 border border-gray-300 rounded px-2.5 py-1 text-center font-bold text-blue-700 input-resultado text-xs outline-none focus:ring-1 focus:ring-blue-500 uppercase"
                               value="${val}"
                               data-movimiento="${idMovimiento}"
                               data-orden="${idOrden}"
                               data-cpt="${cpt}"
                               data-nombre="${item.analito}"
                               data-referencial="${item.ref}">
                    </td>
                    <td class="p-2 text-center text-gray-500 text-[11px]">
                        ${item.ref}
                        <span class="text-[9px] text-gray-400 block font-mono">${item.met}</span>
                    </td>
                </tr>`;
        });

        return `
            <div class="border border-gray-200 rounded-xl overflow-hidden font-mono text-xs">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-700 font-bold border-b border-gray-200 sticky top-0 z-10">
                        <tr>
                            <th class="p-2.5">Componente / Analito</th>
                            <th class="p-2.5 text-center w-52">Resultado</th>
                            <th class="p-2.5 text-center">Referencial</th>
                        </tr>
                    </thead>
                    <tbody>${html}</tbody>
                </table>
            </div>`;
    }
};