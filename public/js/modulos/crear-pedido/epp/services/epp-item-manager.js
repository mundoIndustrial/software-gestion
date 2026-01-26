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
    crearItem(id, nombre, categoria, cantidad, observaciones, imagenes = []) {
        const listaItems = document.getElementById(this.listaItemsId);
        if (!listaItems) {

            return;
        }

        const galeriaHTML = this._crearGaleriaHTML(nombre, imagenes);
        
        const itemHTML = `
            <div class="item-epp" data-item-id="${id}" style="padding: 1.5rem; background: white; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 1rem;">
                <!-- Header con menú contextual -->
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 600; color: #1f2937;">${nombre}</h4>
                        <p style="margin: 0; font-size: 0.875rem; color: #6b7280;">Categoría: ${categoria}</p>
                    </div>
                    <!-- Menú contextual (posicionado como en prenda) -->
                    <div style="position: relative;">
                        <button class="btn-menu-epp" data-item-id="${id}" type="button" style="background: none; border: none; cursor: pointer; font-size: 1.5rem; color: #6b7280; padding: 0.5rem; border-radius: 6px; transition: all 0.2s ease;" 
                            onmouseover="this.style.background='#f3f4f6'; this.style.color='#1f2937';"
                            onmouseout="this.style.background='none'; this.style.color='#6b7280';">
                            ⋮
                        </button>
                        <div class="submenu-epp" data-item-id="${id}" style="display: none; position: absolute; top: 100%; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; flex-direction: column; min-width: 140px;">
                            <button 
                                type="button"
                                class="btn-editar-epp"
                                data-item-id="${id}"
                                style="display: block; width: 100%; padding: 0.75rem 1rem; text-align: left; background: none; border: none; cursor: pointer; font-size: 0.9rem; color: #1f2937; transition: background 0.2s ease; border-bottom: 1px solid #e5e7eb;"
                                onmouseover="this.style.background = '#f3f4f6';"
                                onmouseout="this.style.background = 'transparent';"
                            >
                                Editar
                            </button>
                            <button 
                                type="button"
                                class="btn-eliminar-epp"
                                data-item-id="${id}"
                                style="display: block; width: 100%; padding: 0.75rem 1rem; text-align: left; background: none; border: none; cursor: pointer; font-size: 0.9rem; color: #dc2626; transition: background 0.2s ease; border-radius: 0 0 8px 8px;"
                                onmouseover="this.style.background = '#fef2f2';"
                                onmouseout="this.style.background = 'transparent';"
                            >
                                Eliminar
                            </button>
                        </div>
                    </div>
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
        // Intentar primero con .item-epp[data-item-id]
        let item = document.querySelector(`.item-epp[data-item-id="${id}"]`);
        
        // Si no encuentra, intentar con .item-epp-card[data-epp-id]
        if (!item) {
            item = document.querySelector(`.item-epp-card[data-epp-id="${id}"]`);
        }
        
        if (item) {
            item.remove();
            console.log('[EppItemManager] Item eliminado:', id);
        } else {
            console.warn('[EppItemManager]  Item no encontrado para eliminar:', id);
        }
    }

    /**
     * Obtener item por ID - intenta múltiples selectores
     */
    obtenerItem(id) {
        // Intentar primero con .item-epp[data-item-id]
        let item = document.querySelector(`.item-epp[data-item-id="${id}"]`);
        
        // Si no encuentra, intentar con .item-epp-card[data-epp-id]
        if (!item) {
            item = document.querySelector(`.item-epp-card[data-epp-id="${id}"]`);
        }
        
        console.log('[EppItemManager] 🔍 obtenerItem buscando ID:', id, 'encontrado:', !!item);
        return item;
    }

    /**
     * Actualizar item
     */
    actualizarItem(id, datos) {
        const item = this.obtenerItem(id);
        if (!item) {
            console.warn('[EppItemManager]  Item no encontrado para actualizar:', id);
            return;
        }

        console.log('[EppItemManager] 🔄 Actualizando item:', id, datos);

        // Actualizar cantidad
        if (datos.cantidad !== undefined) {
            // Buscar el div de detalles que contiene cantidad
            const detallesDiv = item.querySelector('div[style*="grid-template-columns"]');
            if (detallesDiv) {
                const etiquetas = detallesDiv.querySelectorAll('p');
                // Primera columna: [0] = etiqueta "Cantidad", [1] = valor cantidad
                // Segunda columna: [2] = etiqueta "Observaciones", [3] = valor observaciones
                if (etiquetas[1]) {
                    etiquetas[1].textContent = datos.cantidad;
                    console.log('[EppItemManager] Cantidad actualizada:', datos.cantidad);
                }
            }
        }

        // Actualizar observaciones
        if (datos.observaciones !== undefined) {
            const detallesDiv = item.querySelector('div[style*="grid-template-columns"]');
            if (detallesDiv) {
                const etiquetas = detallesDiv.querySelectorAll('p');
                // Primera columna: [0] = etiqueta "Cantidad", [1] = valor cantidad
                // Segunda columna: [2] = etiqueta "Observaciones", [3] = valor observaciones
                if (etiquetas[3]) {
                    etiquetas[3].textContent = datos.observaciones || 'N/A';
                    console.log('[EppItemManager] Observaciones actualizadas:', datos.observaciones);
                }
            }
        }

        // Actualizar imágenes si existen
        if (datos.imagenes && Array.isArray(datos.imagenes) && datos.imagenes.length > 0) {
            console.log('[EppItemManager] 🖼️ Actualizando imágenes:', datos.imagenes.length);
            
            // Buscar o crear el contenedor de galería
            let galeriaDiv = item.querySelector('div[style*="padding-top"]');
            
            if (!galeriaDiv) {
                // Si no existe, crear el contenedor de galería
                const ultimoDiv = item.lastElementChild;
                galeriaDiv = document.createElement('div');
                galeriaDiv.style.cssText = 'margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #bfdbfe;';
                item.appendChild(galeriaDiv);
            }

            // Limpiar galería anterior
            galeriaDiv.innerHTML = `
                <p style="margin: 0 0 0.75rem 0; font-size: 0.8rem; font-weight: 600; color: #0066cc; text-transform: uppercase; letter-spacing: 0.5px;">Imágenes</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 0.5rem;">
            `;
            
            // Agregar nuevas imágenes
            datos.imagenes.forEach((img, idx) => {
                const imgUrl = typeof img === 'string' ? img : (img.url || img.ruta_web || '');
                if (imgUrl && !imgUrl.includes('placeholder')) {
                    const imgDiv = document.createElement('div');
                    imgDiv.style.cssText = 'border-radius: 4px; overflow: hidden; background: #f3f4f6; border: 1px solid #e5e7eb;';
                    imgDiv.innerHTML = `<img src="${imgUrl}" alt="Imagen" style="width: 100%; height: 60px; object-fit: cover;">`;
                    galeriaDiv.querySelector('div').appendChild(imgDiv);
                    console.log('[EppItemManager] 📷 Imagen agregada:', idx + 1);
                }
            });
            
            galeriaDiv.querySelector('div').innerHTML += '</div>';
        }

        console.log('[EppItemManager] Item actualizado correctamente');
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
                            <img src="${img.preview || img.url || img}" alt="${nombre}" style="width: 100%; height: 100%; object-fit: cover; display: block;">
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
