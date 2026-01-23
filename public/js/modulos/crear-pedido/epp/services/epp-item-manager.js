/**
 * EppItemManager - Gestiona los items de EPP en el pedido
 * Patrón: Item Manager
 */

class EppItemManager {
    constructor() {
        this.listaItemsId = 'lista-items-pedido';
    }

    /**
     * Crear item visual de EPP
     */
    crearItem(id, nombre, codigo, categoria, cantidad, observaciones, imagenes = []) {
        const listaItems = document.getElementById(this.listaItemsId);
        if (!listaItems) {

            return;
        }

        const galeriaHTML = this._crearGaleriaHTML(nombre, imagenes);
        
        const itemHTML = `
            <div class="item-epp" data-item-id="${id}" style="padding: 1.5rem; background: white; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 1rem; position: relative;">
                <!-- Header -->
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <div>
                        <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 600; color: #1f2937;">${nombre}</h4>
                        <p style="margin: 0; font-size: 0.875rem; color: #6b7280;">Código: ${codigo} | Categoría: ${categoria}</p>
                    </div>
                    <button class="btn-menu-epp" data-item-id="${id}" type="button" style="background: none; border: none; cursor: pointer; font-size: 1.5rem; color: #6b7280;">⋮</button>
                </div>

                <!-- Detalles -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <p style="margin: 0 0 0.25rem 0; font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Cantidad</p>
                        <p style="margin: 0; font-size: 1rem; font-weight: 500; color: #1f2937;">${cantidad}</p>
                    </div>
                    <div>
                        <p style="margin: 0 0 0.25rem 0; font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Observaciones</p>
                        <p style="margin: 0; font-size: 1rem; font-weight: 500; color: #1f2937;">${observaciones || 'N/A'}</p>
                    </div>
                </div>

                ${galeriaHTML}

                <!-- Menú -->
                <div class="submenu-epp" data-item-id="${id}" style="display: none; position: absolute; top: 2rem; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); min-width: 140px; z-index: 100;">
                    <button 
                        type="button"
                        class="btn-editar-epp"
                        data-item-id="${id}"
                        style="display: block; width: 100%; padding: 0.75rem 1rem; text-align: left; background: none; border: none; cursor: pointer; font-size: 0.9rem; color: #1f2937; transition: background 0.2s ease; border-bottom: 1px solid #f3f4f6;"
                        onmouseover="this.style.background = '#f9fafb';"
                        onmouseout="this.style.background = 'transparent';"
                    >
                        Editar
                    </button>
                    <button 
                        type="button"
                        class="btn-eliminar-epp"
                        data-item-id="${id}"
                        style="display: block; width: 100%; padding: 0.75rem 1rem; text-align: left; background: none; border: none; cursor: pointer; font-size: 0.9rem; color: #dc2626; transition: background 0.2s ease;"
                        onmouseover="this.style.background = '#fef2f2';"
                        onmouseout="this.style.background = 'transparent';"
                    >
                        Eliminar
                    </button>
                </div>
            </div>
        `;

        const itemElement = document.createElement('div');
        itemElement.innerHTML = itemHTML;
        listaItems.appendChild(itemElement.firstElementChild);


    }

    /**
     * Eliminar item visual
     */
    eliminarItem(id) {
        const item = document.querySelector(`.item-epp[data-item-id="${id}"]`);
        if (item) {
            item.remove();

        }
    }

    /**
     * Obtener item por ID
     */
    obtenerItem(id) {
        return document.querySelector(`.item-epp[data-item-id="${id}"]`);
    }

    /**
     * Actualizar item
     */
    actualizarItem(id, datos) {
        const item = this.obtenerItem(id);
        if (!item) return;

        // Actualizar valores en el DOM
        const detalles = item.querySelectorAll('div > p');
        if (detalles[1]) detalles[1].textContent = datos.cantidad;
        if (detalles[3]) detalles[3].textContent = datos.observaciones || 'N/A';


    }

    /**
     * Crear galería HTML
     */
    _crearGaleriaHTML(nombre, imagenes) {
        // Asegurar que imagenes sea un array
        if (!imagenes || !Array.isArray(imagenes) || imagenes.length === 0) {
            return '';
        }

        return `
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #bfdbfe;">
                <p style="margin: 0 0 0.75rem 0; font-size: 0.8rem; font-weight: 600; color: #0066cc; text-transform: uppercase; letter-spacing: 0.5px;">Imágenes</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 0.5rem;">
                    ${imagenes.map(img => `
                        <div style="position: relative; border-radius: 4px; overflow: hidden; background: #f3f4f6; border: 1px solid #e5e7eb; aspect-ratio: 1;">
                            <img src="${img.url || img}" alt="${nombre}" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    /**
     * Contar items
     */
    contarItems() {
        return document.querySelectorAll('.item-epp').length;
    }

    /**
     * Obtener todos los items
     */
    obtenerTodos() {
        return document.querySelectorAll('.item-epp');
    }
}

// Exportar instancia global
window.eppItemManager = new EppItemManager();
