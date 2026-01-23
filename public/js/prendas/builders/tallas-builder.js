/**
 * TallasBuilder - Construye sección de tallas y cantidades
 * 
 * Responsabilidad: Generar HTML de tallas expandible
 * Patrón: Builder
 */

class TallasBuilder {
    static construir(prenda, indice) {


        const generosConTallas = prenda.generosConTallas || {};
        const cantidadesPorTalla = prenda.cantidadesPorTalla || {};
        
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

        if (totalTallas === 0) {
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

                return `
                    <div style="background: ${colorFondo}; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; color: ${colorTexto}; font-weight: 600; border: 1px solid #cbd5e1; text-align: center; min-width: 60px;">
                        <div>${talla}</div>
                        <div style="font-size: 0.75rem; opacity: 0.8;">×${cantidad}</div>
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

        return `
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
    }
}

window.TallasBuilder = TallasBuilder;

