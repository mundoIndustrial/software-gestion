/**
 * VariacionesBuilder - Construye sección de variaciones
 * 
 * Responsabilidad: Generar HTML de variaciones expandible
 * Patrón: Builder + Strategy
 */

class VariacionesBuilder {
    static construir(prenda, indice) {


        const variantes = prenda.variantes || {};
        
        // Mapeo de variaciones
        const variacionesMapeo = [
            { label: 'Manga', valKey: 'tipo_manga', obsKey: 'obs_manga' },
            { label: 'Bolsillos', valKey: 'tiene_bolsillos', obsKey: 'obs_bolsillos' },
            { label: 'Broche/Botón', valKey: 'tipo_broche', obsKey: 'obs_broche' },
            { label: 'Reflectivo', valKey: 'tiene_reflectivo', obsKey: 'obs_reflectivo' }
        ];

        // Filtrar aplicadas
        const variacionesAplicadas = variacionesMapeo.filter(({ valKey }) => {
            const valor = variantes[valKey];
            return valor && valor !== 'No aplica' && valor !== false;
        });

        if (variacionesAplicadas.length === 0) {

            return '';
        }

        // Construir filas
        const filasHTML = variacionesAplicadas.map(({ label, valKey, obsKey }) => {
            const valor = variantes[valKey];
            const observaciones = variantes[obsKey] || '';
            const esBooleano = typeof valor === 'boolean';

            return `
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
        }).join('');

        return `
            <div class="seccion-expandible variaciones-section">
                <button class="seccion-expandible-header" type="button" data-section="variaciones" data-prenda-index="${indice}">
                    <h4>Variaciones <span style="margin-left: 0.5rem; font-size: 0.8rem; color: #6b7280;">(${variacionesAplicadas.length})</span></h4>
                    <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
                </button>
                <div class="seccion-expandible-content variaciones-content">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #0ea5e9; color: white;">
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.85rem;">APLICA</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">VARIACIÓN</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">ESPECIFICACIÓN</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">OBSERVACIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${filasHTML}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
}

window.VariacionesBuilder = VariacionesBuilder;

