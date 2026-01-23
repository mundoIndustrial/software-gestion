/**
 * PrendaCardService - Servicio orquestador de tarjetas de prenda
 * 
 * Responsabilidad: Orquestar la generación completa de tarjeta de prenda
 * Patrón: Facade + Factory
 */

class PrendaCardService {
    /**
     * Generar HTML completo de tarjeta de prenda
     * @param {Object} prendaRaw - Datos crudos de prenda
     * @param {number} indice - Índice de la prenda
     * @returns {string} HTML de tarjeta
     */
    static generar(prendaRaw, indice) {


        // 1. Transformar datos
        const prenda = PrendaDataTransformer.transformar(prendaRaw);
        if (!prenda) {

            return '';
        }

        // 2. Obtener elementos visuales
        const fotoPrincipal = PrendaDataTransformer.obtenerFotoPrincipal(prenda);
        const fotoTela = PrendaDataTransformer.obtenerFotoTela(prenda);
        const infoTela = PrendaDataTransformer.obtenerInfoTela(prenda);

        // 3. Construir secciones expandibles
        const variacionesHTML = VariacionesBuilder.construir(prenda, indice);
        const tallasHTML = TallasBuilder.construir(prenda, indice);
        const procesosHTML = ProcesosBuilder.construir(prenda, indice);

        // 4. Generar HTML completo
        return this._generarHTMLTarjeta(prenda, indice, fotoPrincipal, fotoTela, infoTela, variacionesHTML, tallasHTML, procesosHTML);
    }

    /**
     * Generar HTML de tarjeta
     * @private
     */
    static _generarHTMLTarjeta(prenda, indice, fotoPrincipal, fotoTela, infoTela, variacionesHTML, tallasHTML, procesosHTML) {
        return `
            <div class="prenda-card-readonly" data-prenda-index="${indice}" data-prenda-id="${prenda.id || ''}">
                <!-- Header con menú -->
                ${this._generarHeader(prenda, indice)}

                <!-- Contenedor principal: Foto + Info -->
                <div class="prenda-card-content">
                    <!-- Foto izquierda -->
                    ${this._generarFotoPrincipal(prenda, indice, fotoPrincipal)}

                    <!-- Info derecha -->
                    <div class="prenda-card-info">
                        ${prenda.descripcion ? `<p class="prenda-descripcion">${prenda.descripcion}</p>` : ''}
                        
                        <!-- Specs: Tela, Color, Referencia -->
                        ${this._generarSpecs(indice, infoTela, fotoTela)}

                        <!-- Secciones expandibles -->
                        ${variacionesHTML}
                        ${tallasHTML}
                        ${procesosHTML}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Generar header
     * @private
     */
    static _generarHeader(prenda, indice) {
        return `
            <div class="prenda-card-header">
                <div class="prenda-card-title-section">
                    <span class="prenda-label">Prenda ${indice + 1}</span>
                    <h3 class="prenda-name">${prenda.nombre_producto || 'Sin nombre'}</h3>
                </div>
                
                <div class="prenda-menu-contextual">
                    <button class="btn-menu-tres-puntos" type="button" data-prenda-index="${indice}">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="submenu-prenda" style="display: none;">
                        <button class="submenu-option btn-editar-prenda" type="button" data-prenda-index="${indice}">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button class="submenu-option btn-eliminar-prenda" type="button" data-prenda-index="${indice}">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Generar foto principal
     * @private
     */
    static _generarFotoPrincipal(prenda, indice, fotoPrincipal) {
        const imagenes = prenda.imagenes || [];

        if (fotoPrincipal) {
            return `
                <div class="foto-prenda-izquierda">
                    <div style="position: relative; display: inline-block;">
                        <img 
                            src="${fotoPrincipal}" 
                            alt="${prenda.nombre_producto}" 
                            class="foto-principal-readonly"
                            data-prenda-index="${indice}"
                            style="cursor: pointer; width: 120px; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                            onmouseover="this.style.boxShadow='0 4px 16px rgba(14,165,233,0.3)'; this.style.borderColor='#0ea5e9';"
                            onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'; this.style.borderColor='#e5e7eb';"
                        />
                        ${imagenes.length > 1 ? `<span style="position: absolute; top: 5px; right: 5px; background: rgba(14,165,233,0.9); color: white; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700;"><i class="fas fa-images"></i> ${imagenes.length}</span>` : ''}
                    </div>
                </div>
            `;
        }

        return `
            <div class="foto-prenda-izquierda">
                <div style="width: 120px; height: 150px; background: #f3f4f6; border-radius: 8px; border: 2px dashed #d1d5db; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #9ca3af; gap: 0.5rem;">
                    <i class="fas fa-image" style="font-size: 2rem;"></i>
                    <small>Sin foto</small>
                </div>
            </div>
        `;
    }

    /**
     * Generar specs (tela, color, ref)
     * @private
     */
    static _generarSpecs(indice, infoTela, fotoTela) {
        return `
            <div class="prenda-specs-horizontal">
                <div class="specs-content">
                    <div class="spec-item"><strong>Tela:</strong> <span>${infoTela.tela}</span></div>
                    <div class="spec-item"><strong>Color:</strong> <span>${infoTela.color}</span></div>
                    <div class="spec-item"><strong>Ref:</strong> <span>${infoTela.referencia}</span></div>
                </div>
                
                <div class="foto-tela-pequena">
                    ${fotoTela ? `
                        <img 
                            src="${fotoTela}" 
                            alt="Tela" 
                            class="foto-tela-readonly"
                            data-prenda-index="${indice}"
                            style="cursor: pointer; width: 70px; height: 70px; object-fit: cover; border-radius: 6px; border: 2px solid #e5e7eb; transition: all 0.2s;"
                        />
                    ` : `
                        <div style="width: 70px; height: 70px; background: #f3f4f6; border-radius: 6px; border: 2px dashed #d1d5db; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 1rem;">
                            <i class="fas fa-image"></i>
                        </div>
                    `}
                </div>
            </div>
        `;
    }
}

window.PrendaCardService = PrendaCardService;

