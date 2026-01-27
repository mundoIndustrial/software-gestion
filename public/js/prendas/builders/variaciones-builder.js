/**
 * VariacionesBuilder - Construye sección de variaciones
 * 
 * Responsabilidad: Generar HTML de variaciones expandible
 * Patrón: Builder + Strategy
 */

console.log('[DEBUG]  VariacionesBuilder.js cargado correctamente');

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
            // Mostrar si:
            // - Es true (boolean)
            // - Es un string no vacío y no es "No aplica"
            return valor === true || (typeof valor === 'string' && valor.trim() !== '' && valor !== 'No aplica');
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
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px 10px; text-align: center; border-right: 1px solid #eee; word-break: break-word;">
                        <i class="fas fa-check" style="color: #10b981;"></i>
                    </td>
                    <td style="padding: 12px 10px; border-right: 1px solid #eee; font-weight: 500; color: #0066cc; white-space: normal; word-break: break-word;">
                        ${label}
                    </td>
                    <td style="padding: 12px 10px; word-break: break-word; overflow-wrap: break-word;">
                        ${esBooleano ? '-' : valor}
                    </td>
                    <td style="padding: 12px 10px; word-break: break-word; overflow-wrap: break-word;">
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
                    <!-- Desktop: Tabla normal -->
                    <div class="variaciones-tabla-desktop" style="overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 6px; border: 1px solid #ddd;">
                        <table style="width: 100%; border-collapse: collapse; background: white; min-width: 600px;">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #0066cc, #0052a3);">
                                    <th style="padding: 14px 12px; text-align: center; font-weight: 600; color: white; border-right: 1px solid #0052a3; width: 8%; min-width: 50px;">
                                        <i class="fas fa-check-circle"></i>
                                    </th>
                                    <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: white; border-right: 1px solid #0052a3; width: 25%; min-width: 120px;">
                                        VARIACIÓN
                                    </th>
                                    <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: white; width: 32%; min-width: 150px;">
                                        ESPECIFICACIÓN
                                    </th>
                                    <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: white; width: 35%; min-width: 150px;">
                                        OBSERVACIONES
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                ${filasHTML}
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Mobile: Cards -->
                    <div class="variaciones-cards-mobile" style="display: none; width: 100%;">
                        ${variacionesAplicadas.map(({ label, valKey, obsKey }) => {
                            const valor = variantes[valKey];
                            const observaciones = variantes[obsKey] || '';
                            const esBooleano = typeof valor === 'boolean';
                            return `
                                <div class="variacion-card" style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-bottom: 12px; overflow: hidden; word-wrap: break-word; box-sizing: border-box;">
                                    <div class="variacion-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; flex-wrap: wrap;">
                                        <i class="fas fa-check" style="color: #10b981; flex-shrink: 0;"></i>
                                        <span style="font-weight: 600; color: #0066cc; flex: 1; overflow-wrap: break-word; word-break: break-word;">${label}</span>
                                    </div>
                                    <div class="variacion-row" style="margin-bottom: 8px; overflow-wrap: break-word;">
                                        <span class="variacion-label" style="font-size: 0.85rem; color: #666; display: block; margin-bottom: 4px; font-weight: 500;">Especificación:</span>
                                        <div class="variacion-valor" style="background: #f9f9f9; padding: 8px 10px; border-radius: 4px; border-left: 3px solid #0066cc; word-break: break-word; overflow-wrap: break-word;">
                                            ${esBooleano ? '-' : valor}
                                        </div>
                                    </div>
                                    ${observaciones ? `
                                        <div class="variacion-row" style="overflow-wrap: break-word;">
                                            <span class="variacion-label" style="font-size: 0.85rem; color: #666; display: block; margin-bottom: 4px; font-weight: 500;">Observaciones:</span>
                                            <div class="variacion-observaciones" style="background: #f0f7ff; padding: 8px 10px; border-radius: 4px; border-left: 3px solid #9ca3af; word-break: break-word; overflow-wrap: break-word;">
                                                ${observaciones}
                                            </div>
                                        </div>
                                    ` : ''}
                                </div>
                            `;
                        }).join('')}
                    </div>

                    <style>
                        * { box-sizing: border-box; }
                        
                        .variaciones-tabla-desktop table th,
                        .variaciones-tabla-desktop table td {
                            overflow-wrap: break-word;
                            word-wrap: break-word;
                            word-break: break-word;
                        }

                        .variaciones-tabla-desktop input,
                        .variaciones-tabla-desktop select {
                            max-width: 100%;
                            box-sizing: border-box;
                        }

                        .variaciones-cards-mobile {
                            width: 100%;
                        }

                        .variaciones-cards-mobile > div {
                            width: 100%;
                            box-sizing: border-box;
                            overflow-wrap: break-word;
                            word-wrap: break-word;
                        }

                        @media (max-width: 640px) {
                            .variaciones-tabla-desktop {
                                display: none !important;
                            }
                            .variaciones-cards-mobile {
                                display: block !important;
                            }
                        }

                        @media (min-width: 641px) {
                            .variaciones-tabla-desktop {
                                display: block !important;
                            }
                            .variaciones-cards-mobile {
                                display: none !important;
                            }
                        }
                    </style>
                </div>
            </div>
        `;
    }
}

window.VariacionesBuilder = VariacionesBuilder;

