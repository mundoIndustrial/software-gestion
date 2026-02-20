/**
 * EppItemManager - Gestiona los items de EPP en el pedido
 * Patrón: Item Manager
 */

class EppItemManager {
    constructor() {
        this.listaItemsId = 'lista-items-pedido';
    }

    _obtenerMiniatura(imagenes) {
        if (!imagenes || !Array.isArray(imagenes) || imagenes.length === 0) return '';

        const img = imagenes[0];
        if (typeof img === 'string') return img;
        if (img?.previewUrl) return img.previewUrl;
        if (img?.url) return img.url;
        if (img?.ruta_web) return img.ruta_web;
        if (img?.base64) return img.base64;
        return '';
    }

    /**
     * Crear item visual de EPP
     */
    crearItem(id, nombre, categoria, cantidad, observaciones, imagenes = [], pedidoEppId = null, valorUnitario = null, total = null) {
        const listaItems = document.getElementById(this.listaItemsId);
        if (!listaItems) {

            return;
        }

        const miniatura = this._obtenerMiniatura(imagenes);

        const formatearNumero = (num) => {
            if (!Number.isFinite(num)) return '0';
            if (Number.isInteger(num)) return String(num);
            const s = num.toFixed(2);
            return s.replace(/\.00$/, '').replace(/(\.[0-9])0$/, '$1');
        };

        const vu = (valorUnitario !== undefined && valorUnitario !== null && String(valorUnitario).trim() !== '')
            ? Number(valorUnitario)
            : null;
        const tot = (total !== undefined && total !== null && String(total).trim() !== '')
            ? Number(total)
            : null;
        
        const itemHTML = `
            <div class="item-epp" data-item-id="${id}" data-pedido-epp-id="${pedidoEppId || id}" style="padding: 0.75rem; background: white; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 0.5rem;">
                <!-- Header con menú contextual -->
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                    <div style="flex: 1;">
                        <div style="display: flex; gap: 1.1rem; align-items: flex-start;">
                            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                <h4 style="margin: 0; margin-top: -4px; font-size: 0.9rem; font-weight: 600; color: #1f2937;">${nombre}</h4>

                                <div class="epp-detalles" style="display: grid; grid-template-columns: 120px 1fr; gap: 0.6rem; align-items: start;">
                                    <div>
                                        <p style="margin: 0 0 0.1rem 0; font-size: 0.65rem; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Cantidad</p>
                                        <p style="margin: 0; font-size: 0.85rem; font-weight: 500; color: #1f2937;">${cantidad}</p>
                                    </div>
                                    <div>
                                        <p style="margin: 0 0 0.1rem 0; font-size: 0.65rem; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Observaciones</p>
                                        <p style="margin: 0; font-size: 0.85rem; font-weight: 500; color: #1f2937;">${observaciones || 'N/A'}</p>
                                    </div>
                                </div>

                                ${(vu !== null || tot !== null) ? `
                                    <div class="epp-detalles" style="display: grid; grid-template-columns: 120px 1fr; gap: 0.6rem; align-items: start; margin-top: 0.45rem;">
                                        <div>
                                            <p style="margin: 0 0 0.1rem 0; font-size: 0.65rem; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Valor Unitario</p>
                                            <p style="margin: 0; font-size: 0.85rem; font-weight: 600; color: #1f2937;">${vu !== null ? formatearNumero(vu) : 'N/A'}</p>
                                        </div>
                                        <div>
                                            <p style="margin: 0 0 0.1rem 0; font-size: 0.65rem; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Total</p>
                                            <p style="margin: 0; font-size: 0.85rem; font-weight: 800; color: #1f2937;">${tot !== null ? formatearNumero(tot) : ((vu !== null && cantidad) ? formatearNumero(vu * Number(cantidad)) : '0')}</p>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>

                            ${miniatura ? `
                                <div class="epp-miniatura-wrapper" style="width: 135px; height: 140px; margin-left: 0.25rem; border-radius: 14px; overflow: hidden; border: 1px solid #e5e7eb; background: #f3f4f6; flex-shrink: 0;">
                                    <img class="epp-miniatura" src="${miniatura}" alt="${nombre}" style="width: 100%; height: 100%; object-fit: cover; display: block; cursor: pointer;" ondblclick="event.preventDefault(); event.stopPropagation(); if (window.mostrarImagenProcesoGrande) window.mostrarImagenProcesoGrande('${miniatura}');" />
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    <!-- Menú contextual (posicionado como en prenda) -->
                    <div style="position: relative;">
                        <button class="btn-menu-epp" data-item-id="${id}" type="button" style="background: none; border: none; cursor: pointer; font-size: 1.25rem; color: #6b7280; padding: 0.25rem; border-radius: 4px; transition: all 0.2s ease;" 
                            onmouseover="this.style.background='#f3f4f6'; this.style.color='#1f2937';"
                            onmouseout="this.style.background='none'; this.style.color='#6b7280';">
                            ⋮
                        </button>
                        <div class="submenu-epp" data-item-id="${id}" style="display: none; position: absolute; top: 100%; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; flex-direction: column; min-width: 120px;">
                            <button 
                                type="button"
                                class="btn-editar-epp"
                                data-item-id="${id}"
                                style="display: block; width: 100%; padding: 0.5rem 0.75rem; text-align: left; background: none; border: none; cursor: pointer; font-size: 0.8rem; color: #1f2937; transition: background 0.2s ease; border-bottom: 1px solid #e5e7eb;"
                                onmouseover="this.style.background = '#f3f4f6';"
                                onmouseout="this.style.background = 'transparent';"
                            >
                                Editar
                            </button>
                            <button 
                                type="button"
                                class="btn-eliminar-epp"
                                data-item-id="${id}"
                                style="display: block; width: 100%; padding: 0.5rem 0.75rem; text-align: left; background: none; border: none; cursor: pointer; font-size: 0.8rem; color: #dc2626; transition: background 0.2s ease; border-radius: 0 0 6px 6px;"
                                onmouseover="this.style.background = '#fef2f2';"
                                onmouseout="this.style.background = 'transparent';"
                            >
                                Eliminar
                            </button>
                        </div>
                    </div>
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
        
        console.log('[EppItemManager]  obtenerItem buscando ID:', id, 'encontrado:', !!item);
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

        console.log('[EppItemManager]  Actualizando item:', id, datos);

        // Actualizar cantidad
        if (datos.cantidad !== undefined) {
            const detallesDiv = item.querySelector('.epp-detalles') || item.querySelector('div[style*="grid-template-columns"]');
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

        // Actualizar valor unitario / total (si vienen)
        if (datos.valor_unitario !== undefined || datos.total !== undefined) {
            const formatearNumero = (num) => {
                if (!Number.isFinite(num)) return '0';
                if (Number.isInteger(num)) return String(num);
                const s = num.toFixed(2);
                return s.replace(/\.00$/, '').replace(/(\.[0-9])0$/, '$1');
            };

            const vu = (datos.valor_unitario !== undefined && datos.valor_unitario !== null && String(datos.valor_unitario).trim() !== '')
                ? Number(datos.valor_unitario)
                : null;
            const tot = (datos.total !== undefined && datos.total !== null && String(datos.total).trim() !== '')
                ? Number(datos.total)
                : null;

            const cantidadActual = (datos.cantidad !== undefined)
                ? Number(datos.cantidad)
                : (() => {
                    const detallesDiv = item.querySelector('.epp-detalles') || item.querySelector('div[style*="grid-template-columns"]');
                    const etiquetas = detallesDiv ? detallesDiv.querySelectorAll('p') : [];
                    return etiquetas && etiquetas[1] ? Number(etiquetas[1].textContent) : 0;
                })();

            const totalCalc = (tot !== null)
                ? tot
                : ((vu !== null && cantidadActual) ? (vu * Number(cantidadActual)) : 0);

            // Buscar si ya existe la sección (la segunda .epp-detalles) dentro del bloque principal
            const bloques = item.querySelectorAll('.epp-detalles');
            let seccionVU = null;
            if (bloques && bloques.length > 1) {
                seccionVU = bloques[1];
            }

            if (!seccionVU) {
                // Crear sección debajo del primer bloque de detalles
                const primerBloque = bloques && bloques.length > 0 ? bloques[0] : null;
                if (primerBloque && (vu !== null || tot !== null)) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'epp-detalles';
                    wrapper.setAttribute('style', 'display: grid; grid-template-columns: 120px 1fr; gap: 0.6rem; align-items: start; margin-top: 0.45rem;');
                    wrapper.innerHTML = `
                        <div>
                            <p style="margin: 0 0 0.1rem 0; font-size: 0.65rem; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Valor Unitario</p>
                            <p style="margin: 0; font-size: 0.85rem; font-weight: 600; color: #1f2937;">${vu !== null ? formatearNumero(vu) : 'N/A'}</p>
                        </div>
                        <div>
                            <p style="margin: 0 0 0.1rem 0; font-size: 0.65rem; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Total</p>
                            <p style="margin: 0; font-size: 0.85rem; font-weight: 800; color: #1f2937;">${Number.isFinite(totalCalc) ? formatearNumero(totalCalc) : '0'}</p>
                        </div>
                    `;
                    primerBloque.insertAdjacentElement('afterend', wrapper);
                }
            } else {
                // Actualizar valores dentro de la sección existente
                const ps = seccionVU.querySelectorAll('p');
                // [0]=label vu, [1]=valor vu, [2]=label total, [3]=valor total
                if (ps && ps[1]) ps[1].textContent = (vu !== null ? formatearNumero(vu) : 'N/A');
                if (ps && ps[3]) ps[3].textContent = (Number.isFinite(totalCalc) ? formatearNumero(totalCalc) : '0');

                // Si ya no hay datos, podrías ocultar; por ahora lo dejamos visible si existe.
            }
        }

        // Actualizar observaciones
        if (datos.observaciones !== undefined) {
            const detallesDiv = item.querySelector('.epp-detalles') || item.querySelector('div[style*="grid-template-columns"]');
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
        if (datos.imagenes !== undefined) {
            console.log('[EppItemManager] 🖼️ Procesando imágenes:', datos.imagenes.length);

            const nuevaMiniatura = this._obtenerMiniatura(datos.imagenes);
            const wrapper = item.querySelector('.epp-miniatura-wrapper');
            const imgEl = item.querySelector('.epp-miniatura');

            if (nuevaMiniatura) {
                if (imgEl) {
                    imgEl.src = nuevaMiniatura;
                } else {
                    const titleEl = item.querySelector('h4');
                    if (titleEl) {
                        const nuevoWrapper = document.createElement('div');
                        nuevoWrapper.className = 'epp-miniatura-wrapper';
                        nuevoWrapper.style.cssText = 'width: 135px; height: 140px; margin-left: 0.25rem; border-radius: 14px; overflow: hidden; border: 1px solid #e5e7eb; background: #f3f4f6; flex-shrink: 0;';
                        nuevoWrapper.innerHTML = `<img class="epp-miniatura" src="${nuevaMiniatura}" alt="${titleEl.textContent || 'EPP'}" style="width: 100%; height: 100%; object-fit: cover; display: block; cursor: pointer;" ondblclick="event.preventDefault(); event.stopPropagation(); if (window.mostrarImagenProcesoGrande) window.mostrarImagenProcesoGrande('${nuevaMiniatura}');" />`;
                        titleEl.insertAdjacentElement('afterend', nuevoWrapper);
                    }
                }
            } else {
                if (wrapper) wrapper.remove();
            }
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
                    ${imagenes.map(img => {
                        // Soportar múltiples formatos de imagen
                        // PRIORIZAR URLs DEL SERVIDOR con fallback a previewUrl
                        let imgUrl = '';
                        let imgAlt = nombre;
                        
                        if (typeof img === 'string') {
                            imgUrl = img;
                        } else if (img.previewUrl) {
                            // PRIORIDAD 1: URL blob (funciona siempre)
                            imgUrl = img.previewUrl;
                            imgAlt = img.nombre || nombre;
                        } else if (img.url || img.ruta_web) {
                            // PRIORIDAD 2: URLs del servidor (si existen y funcionan)
                            // TEMPORAL: Como las URLs simuladas no funcionan, usar previewUrl
                            if (img.previewUrl) {
                                imgUrl = img.previewUrl;
                                imgAlt = img.nombre || nombre;
                            } else {
                                imgUrl = img.url || img.ruta_web;
                                imgAlt = img.nombre || nombre;
                            }
                        } else if (img.base64) {
                            // PRIORIDAD 3: Base64 (fallback)
                            imgUrl = img.base64;
                            imgAlt = img.nombre || nombre;
                        }
                        
                        // Solo renderizar si tenemos una URL válida
                        if (imgUrl && !imgUrl.includes('placeholder')) {
                            return `
                                <div style="position: relative; border-radius: 4px; overflow: hidden; background: #f3f4f6; border: 1px solid #e5e7eb; aspect-ratio: 1;">
                                    <img src="${imgUrl}" alt="${imgAlt}" style="width: 100%; height: 100%; object-fit: cover; display: block;" title="${imgAlt}"
                                         onerror="console.warn('Imagen no cargada, usando fallback'); this.src='${img.previewUrl || ''}';">
                                </div>
                            `;
                        }
                        return '';
                    }).filter(html => html !== '').join('')}
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
