/**
 * TallasBuilder - Construye sección de tallas y cantidades
 * 
 * Responsabilidad: Generar HTML de tallas expandible
 * Patrón: Builder
 */

class TallasBuilder {
    static construir(prenda, indice) {
        console.log('[TallasBuilder.construir] ===== INICIO CONSTRUCCIÓN TALLAS =====');
        console.log('[TallasBuilder.construir] prenda.nombre:', prenda.nombre || 'SIN NOMBRE');
        console.log('[TallasBuilder.construir] generosConTallas:', prenda.generosConTallas);
        console.log('[TallasBuilder.construir] cantidadesPorTalla:', prenda.cantidadesPorTalla);
        console.log('[TallasBuilder.construir] asignacionesColores (tipo):', typeof prenda.asignacionesColores);
        console.log('[TallasBuilder.construir] asignacionesColores (keys):', prenda.asignacionesColores ? Object.keys(prenda.asignacionesColores) : 'UNDEFINED');
        console.log('[TallasBuilder.construir] asignacionesColores (completo):', prenda.asignacionesColores);

        const generosConTallas = prenda.generosConTallas || {};
        const cantidadesPorTalla = prenda.cantidadesPorTalla || {};
        const asignacionesColores = prenda.asignacionesColores || {}; // Datos de colores asignados
        
        // Estructurar por género
        const tallasByGeneroMap = {};
        const cantidadesPorGenero = {};

        Object.entries(generosConTallas).forEach(([genero, data]) => {
            tallasByGeneroMap[genero] = data.tallas || [];
            cantidadesPorGenero[genero] = {};
        });

        // Procesar cantidades
        Object.entries(cantidadesPorTalla).forEach(([clave, cantidad]) => {
            const [genero, talla] = clave.split('-');
            if (genero && talla && cantidadesPorGenero[genero]) {
                cantidadesPorGenero[genero][talla] = cantidad;
            }
        });

        const totalTallas = Object.values(tallasByGeneroMap).reduce((sum, tallas) => sum + tallas.length, 0);
        console.log('[TallasBuilder.construir]  totalTallas:', totalTallas, 'tallasByGeneroMap:', tallasByGeneroMap);

        if (totalTallas === 0) {
            console.log('[TallasBuilder.construir]  SIN TALLAS - RETORNANDO VACÍO');

            return '';
        }

        // Construir por género
        let generoHTML = '';
        
        Object.keys(tallasByGeneroMap).forEach((genero) => {
            const tallas = tallasByGeneroMap[genero] || [];
            const cantidades = cantidadesPorGenero[genero] || {};

            if (tallas.length === 0) return;

            const tallasHTML = tallas.map(talla => {
                const cantidad = cantidades[talla] || 0;
                const colorFondo = cantidad > 0 ? '#dbeafe' : '#f5f5f5';
                const colorTexto = cantidad > 0 ? '#0369a1' : '#9ca3af';

                // Buscar colores asignados para esta talla-género
                // Las claves pueden ser: "dama-S", "dama-Letra-S", o directamente como objeto con genero/talla
                let asignacion = null;
                
                console.log(`[TallasBuilder] Buscando colores para genero="${genero}", talla="${talla}"`);
                
                // Método 1: Buscar por objeto con genero y talla
                const claveBuscada = Object.keys(asignacionesColores).find(clave => {
                    const asignacion = asignacionesColores[clave];
                    return asignacion && asignacion.genero && asignacion.genero.toLowerCase() === genero.toLowerCase() && asignacion.talla === talla;
                });
                
                if (claveBuscada) {
                    console.log(`[TallasBuilder]   ✅ Encontrado por Método 1 (objeto). Clave: "${claveBuscada}"`);
                    asignacion = asignacionesColores[claveBuscada];
                } else {
                    console.log(`[TallasBuilder]   ⚠️ Método 1 no encontró`);
                    // Método 2: Buscar por clave en formato "genero-tipo-talla" (ignorando tipo)
                    const claveAlternativa = Object.keys(asignacionesColores).find(clave => {
                        // Clave formato: "dama-Letra-S" o "dama-S"
                        const partes = clave.split('-');
                        if (partes.length >= 2) {
                            const clavGenero = partes[0].toLowerCase();
                            const claveTalla = partes[partes.length - 1]; // Última parte es siempre la talla
                            return clavGenero === genero.toLowerCase() && claveTalla === talla;
                        }
                        return false;
                    });
                    
                    if (claveAlternativa) {
                        console.log(`[TallasBuilder]   ✅ Encontrado por Método 2 (clave). Clave: "${claveAlternativa}"`);
                        // Si encontramos por clave, transformar a formato de asignacion si no lo está ya
                        const valor = asignacionesColores[claveAlternativa];
                        if (valor.genero) {
                            asignacion = valor; // Ya es un objeto asignacion válido
                        } else {
                            // Podría ser solo un array de colores, envolver en objeto
                            asignacion = { genero, talla, colores: Array.isArray(valor) ? valor : [valor] };
                        }
                    } else {
                        console.log(`[TallasBuilder]   ❌ No encontrado por Método 2`);
                    }
                }
                
                const coloresHTML = this._generarColoresHTML(asignacion);

                return `
                    <div style="background: ${colorFondo}; padding: 0.75rem; border-radius: 8px; border: 1px solid #cbd5e1; min-width: 100px;">
                        <div style="color: ${colorTexto}; font-weight: 600; font-size: 0.9rem; text-align: center;">
                            <div>${talla}</div>
                            <div style="font-size: 0.75rem; opacity: 0.8;">×${cantidad}</div>
                        </div>
                        ${coloresHTML}
                    </div>
                `;
            }).join('');

            generoHTML += `
                <div style="margin-bottom: 1rem;">
                    <div style="font-weight: 700; color: #1e293b; margin-bottom: 0.75rem; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-users" style="color: #0ea5e9; font-size: 0.9rem;"></i>
                        ${genero}
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                        ${tallasHTML}
                    </div>
                </div>
            `;
        });

        const htmlCompleto = `
            <div class="seccion-expandible tallas-y-cantidades-section">
                <button class="seccion-expandible-header" type="button" data-section="tallas-y-cantidades" data-prenda-index="${indice}">
                    <h4 style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-th" style="color: #0ea5e9;"></i>
                        Tallas & Cantidades
                        <span style="margin-left: 0.5rem; font-size: 0.8rem; color: #6b7280; font-weight: 500;">(${totalTallas})</span>
                    </h4>
                    <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
                </button>
                <div class="seccion-expandible-content tallas-y-cantidades-content">
                    <div style="padding: 1.25rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 8px; border-left: 4px solid #0ea5e9;">
                        ${generoHTML}
                    </div>
                </div>
            </div>
        `;
        
        console.log('[TallasBuilder.construir] RETORNANDO HTML CON TALLAS:', htmlCompleto.substring(0, 100) + '...');
        return htmlCompleto;
    }

    /**
     * Generar HTML de colores asignados a una talla
     * @private
     */
    static _generarColoresHTML(asignacion) {
        if (!asignacion || !asignacion.colores || asignacion.colores.length === 0) {
            return '';
        }

        const coloresItems = asignacion.colores.map(color => {
            const colorName = color.nombre || color.color || 'Sin nombre';
            const cantidadColor = color.cantidad || 0;
            return `
                <div style="font-size: 0.7rem; color: #475569; margin-top: 0.35rem; padding: 0.25rem 0.5rem; background: rgba(255,255,255,0.6); border-radius: 3px; display: flex; align-items: center; gap: 0.3rem;">
                    <span style="display: inline-block; width: 6px; height: 6px; background: #0ea5e9; border-radius: 50%;"></span>
                    <span style="flex: 1;">${colorName}</span>
                    <span style="color: #6b7280; font-weight: 500;">×${cantidadColor}</span>
                </div>
            `;
        }).join('');

        return `
            <div style="margin-top: 0.5rem; border-top: 1px solid rgba(203, 213, 225, 0.3); padding-top: 0.35rem;">
                ${coloresItems}
            </div>
        `;
    }
}

window.TallasBuilder = TallasBuilder;
