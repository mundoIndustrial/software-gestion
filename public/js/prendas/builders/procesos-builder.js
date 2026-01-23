/**
 * ProcesosBuilder - Construye sección de procesos
 * 
 * Responsabilidad: Generar HTML de procesos expandible
 * Patrón: Builder + Template Method
 */

class ProcesosBuilder {
    static ICONOS = {
        'reflectivo': '<i class="fas fa-lightbulb" style="color: #f59e0b;"></i>',
        'bordado': '<i class="fas fa-gem" style="color: #8b5cf6;"></i>',
        'estampado': '<i class="fas fa-paint-brush" style="color: #ec4899;"></i>',
        'dtf': '<i class="fas fa-print" style="color: #06b6d4;"></i>',
        'sublimado': '<i class="fas fa-tint" style="color: #3b82f6;"></i>'
    };

    static construir(prenda, indice) {


        const procesos = prenda.procesos || {};
        const procesosConDatos = Object.entries(procesos).filter(
            ([_, proc]) => proc && (proc.datos !== null || proc.tipo)
        );

        if (procesosConDatos.length === 0) {
            return '';
        }

        const itemsHTML = procesosConDatos.map(([tipoProceso, proceso]) => {
            return this._construirItemProceso(tipoProceso, proceso);
        }).join('');

        return `
            <div class="seccion-expandible procesos-section">
                <button class="seccion-expandible-header" type="button" data-section="procesos" data-prenda-index="${indice}">
                    <h4>Procesos <span style="margin-left: 0.5rem; font-size: 0.8rem; color: #6b7280;">(${procesosConDatos.length})</span></h4>
                    <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
                </button>
                <div class="seccion-expandible-content procesos-content">
                    <div style="padding: 1rem;">
                        ${itemsHTML}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Construir item individual de proceso
     * @private
     */
    static _construirItemProceso(tipoProceso, proceso) {
        const datos = proceso.datos || {};
        const icono = this.ICONOS[tipoProceso] || '<i class="fas fa-cog"></i>';
        const nombreProceso = tipoProceso.charAt(0).toUpperCase() + tipoProceso.slice(1);

        const ubicacionesHTML = this._construirUbicaciones(datos);
        const tallasHTML = this._construirTallasDeProcesos(datos);
        const observacionesHTML = this._construirObservaciones(datos);
        const imagenHTML = this._construirImagenes(datos);

        return `
            <div style="background: white; border: 2px solid #e5e7eb; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 2px solid #0ea5e9;">
                    <span style="font-size: 1.5rem;">${icono}</span>
                    <h4 style="margin: 0; color: #0369a1; font-size: 1.1rem; font-weight: 700;">${nombreProceso}</h4>
                </div>
                
                ${ubicacionesHTML}
                ${tallasHTML}
                ${observacionesHTML}
                ${imagenHTML}
            </div>
        `;
    }

    /**
     * Construir sección de ubicaciones
     * @private
     */
    static _construirUbicaciones(datos) {
        if (!datos.ubicaciones || datos.ubicaciones.length === 0) {
            return '';
        }

        const ubicacionesHTML = datos.ubicaciones
            .map(ub => `<span style="background: #dbeafe; color: #0369a1; padding: 0.4rem 0.8rem; border-radius: 16px; font-size: 0.85rem; font-weight: 600;">${ub}</span>`)
            .join('');

        return `
            <div style="margin-bottom: 0.75rem;">
                <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                    <i class="fas fa-location-arrow" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Ubicaciones:
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    ${ubicacionesHTML}
                </div>
            </div>
        `;
    }

    /**
     * Construir sección de tallas de proceso
     * @private
     */
    static _construirTallasDeProcesos(datos) {
        if (!datos.tallas || Object.keys(datos.tallas).length === 0) {
            return '';
        }

        let generoHTML = '';

        Object.entries(datos.tallas).forEach(([genero, tallasData]) => {
            if (!tallasData || !tallasData.tallas || tallasData.tallas.length === 0) return;

            const tallasHTML = tallasData.tallas
                .map(talla => `<span style="background: #fef3c7; color: #92400e; padding: 0.3rem 0.6rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">${talla}</span>`)
                .join('');

            generoHTML += `
                <div style="margin-bottom: 0.5rem;">
                    <strong style="color: #0369a1; font-size: 0.9rem;">${genero}:</strong>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.25rem;">
                        ${tallasHTML}
                    </div>
                </div>
            `;
        });

        return `
            <div style="margin-bottom: 0.75rem;">
                <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                    <i class="fas fa-ruler" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Tallas:
                </strong>
                ${generoHTML}
            </div>
        `;
    }

    /**
     * Construir sección de observaciones
     * @private
     */
    static _construirObservaciones(datos) {
        if (!datos.observaciones) {
            return '';
        }

        return `
            <div style="margin-bottom: 0.75rem; background: #eff6ff; padding: 0.75rem; border-radius: 6px; border-left: 3px solid #0ea5e9;">
                <strong style="color: #0369a1; display: block; margin-bottom: 0.25rem;">
                    <i class="fas fa-sticky-note" style="margin-right: 0.5rem;"></i>Observaciones:
                </strong>
                <p style="margin: 0; color: #374151; font-size: 0.9rem;">${datos.observaciones}</p>
            </div>
        `;
    }

    /**
     * Construir sección de imágenes
     * @private
     */
    static _construirImagenes(datos) {
        const imagenes = datos.imagenes || (datos.imagen ? [datos.imagen] : []);
        if (imagenes.length === 0) {
            return '';
        }

        const imagenesHTML = imagenes
            .map((img, idx) => {
                const url = ImageProcessor.procesarImagen(img);
                return url ? `<img src="${url}" alt="Proceso ${idx}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 2px solid #e5e7eb; cursor: pointer;" />` : '';
            })
            .filter(html => html)
            .join('');

        return `
            <div style="margin-top: 0.75rem;">
                <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                    <i class="fas fa-images" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Imágenes:
                </strong>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    ${imagenesHTML}
                </div>
            </div>
        `;
    }
}

window.ProcesosBuilder = ProcesosBuilder;

