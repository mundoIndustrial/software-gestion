/**
 * PrendaCardVariationsService
 * Render de variaciones para PrendaCardService.
 */
globalThis.PrendaCardVariationsService = {
    construirVariaciones(prenda, indice) {
        const variantes = prenda.variantes || {};

const variacionesMapeo = [
            { label: 'Manga', valKey: 'tipo_manga', obsKey: 'obs_manga' },
            { label: 'Bolsillos', valKey: 'tiene_bolsillos', obsKey: 'obs_bolsillos' },
            { label: 'Broche/Botón', valKey: 'tipo_broche', obsKey: 'obs_broche' },
            { label: 'Reflectivo', valKey: 'tiene_reflectivo', obsKey: 'obs_reflectivo' }
        ];
        
        const variacionesAplicadas = variacionesMapeo.filter(({ valKey, obsKey }) => {
            const valor = variantes[valKey];
            return valor && valor !== 'No aplica' && valor !== false;
        });

if (variacionesAplicadas.length === 0) {

            return '';
        }

        let tablasFilasHTML = '';
        variacionesAplicadas.forEach(({ label, valKey, obsKey }) => {
            const valor = variantes[valKey];
            const observaciones = variantes[obsKey] || '';
            // Detectar si debe mostrar "-" en especificación
            // Para campos booleanos (tiene_bolsillos, tiene_reflectivo) nunca mostrar valor numérico
            const esBooleano = typeof valor === 'boolean' || valKey.startsWith('tiene_');
            
            tablasFilasHTML += `
                <tr>
                    <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb; text-align: center;">
                        <i class="fas fa-check" style="color: #10b981; font-weight: bold;"></i>
                    </td>
                    <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb; color: #0369a1; font-weight: 500;">
                        ${label}
                    </td>
                    <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb; color: #374151;">
                        ${esBooleano ? '-' : valor}
                    </td>
                    <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 0.9rem;">
                        ${observaciones || '-'}
                    </td>
                </tr>
            `;
        });

        return `
            <div class="seccion-expandible variaciones-section">
                <button class="seccion-expandible-header" type="button" data-section="variaciones" data-prenda-index="${indice}">
                    <h4>Variaciones <span style="margin-left: 0.5rem; font-size: 0.8rem; color: #6b7280;">(<span class="variaciones-count">${variacionesAplicadas.length}</span>)</span></h4>
                    <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
                </button>
                <div class="seccion-expandible-content variaciones-content">
                    <table style="width: 100%; border-collapse: collapse; margin: 0;">
                        <thead>
                            <tr style="background: #0ea5e9; color: white;">
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.85rem;">APLICA</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">VARIACIÓN</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">ESPECIFICACIÓN</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">OBSERVACIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tablasFilasHTML}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    
    }
};
