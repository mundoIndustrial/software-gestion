/**
 * EppItemManagerNuevo - Gestión exclusiva para vista de crear nuevo pedido
 * Patrón: Item Manager - Versión independiente para /asesores/pedidos-editable/crear-nuevo
 */

class EppItemManagerNuevo {
    constructor() {
        this.listaItemsId = 'lista-items-pedido';
        // Cache para mantener referencias a las imágenes y archivos
        this.imagenesCache = new Map();
    }

    _obtenerMiniatura(imagenes) {
        if (!imagenes || !Array.isArray(imagenes) || imagenes.length === 0) return '';

        const img = imagenes[0];
        if (typeof img === 'string') return img;
        
        // En modo cotización, usar previewUrl (blob URLs); en otros modos usar ruta_web
        const esModoCotizacion = !!window.__EPP_COTIZACION_MODE__;
        
        if (esModoCotizacion) {
            // Modo cotización: usar previewUrl (blob URLs), luego fallbacks
            if (img?.previewUrl) return img.previewUrl;
            if (img?.ruta_web && img.ruta_web.trim()) return img.ruta_web;
        } else {
            // Otros modos: priorizar ruta_web (URLs guardadas)
            if (img?.ruta_web && img.ruta_web.trim()) return img.ruta_web;
            if (img?.previewUrl) return img.previewUrl;
        }
        
        if (img?.url) return img.url;
        if (img?.base64) return img.base64;
        return '';
    }

    /**
     * Crear tarjeta de EPP para vista de nuevo pedido
     */
    crearItem(id, nombre, categoria, cantidad, observaciones, imagenes = [], pedidoEppId = null, valorUnitario = null, total = null) {
        const listaItems = document.getElementById(this.listaItemsId);
        if (!listaItems) {
            console.warn('[EppItemManagerNuevo] Contenedor no encontrado:', this.listaItemsId);
            return;
        }

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

        // Calcular número de EPP (contar solo items-epp-card-nuevo)
        const eppCount = document.querySelectorAll('.item-epp-card-nuevo').length;
        const numeroItem = eppCount + 1;

        // Crear tarjeta de EPP
        const card = document.createElement('div');
        card.className = 'item-epp-card-nuevo';
        
        // Generar ID único para esta tarjeta (usar timestamp para evitar duplicados)
        const tarjetaId = `epp-${id}-${Date.now()}-${Math.random().toString(36).substring(2, 8)}`;
        card.setAttribute('data-epp-id', tarjetaId);
        card.setAttribute('data-pedido-epp-id', pedidoEppId || id);
        card.setAttribute('data-epp-original-id', id); // Mantener referencia al ID original del EPP
        card.style.cssText = 'padding: 1.5rem; background: white; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);';
        
        card.innerHTML = `
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div>
                    <span style="display: inline-block; background: #e0f2fe; color: #0066cc; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.5rem;">EPP ${numeroItem}</span>
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 600; color: #1f2937;">${nombre}</h4>
                </div>
                <!-- Contenedor del botón y menú con posicionamiento relativo -->
                <div style="position: relative;">
                    <button class="btn-menu-epp-nuevo" data-item-id="${tarjetaId}" type="button" style="background: none; border: none; cursor: pointer; font-size: 1.5rem; color: #6b7280; padding: 4px; border-radius: 4px; transition: all 0.2s ease;" 
                        onmouseover="this.style.background='#f3f4f6'; this.style.color='#1f2937';"
                        onmouseout="this.style.background='none'; this.style.color='#6b7280';">⋮</button>
                    
                    <!-- Menú -->
                    <div class="submenu-epp-nuevo" data-item-id="${tarjetaId}" style="display: none; position: absolute; top: 100%; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); min-width: 140px; z-index: 1000; flex-direction: column;">
                        <button 
                            type="button"
                            class="btn-editar-epp-nuevo"
                            data-item-id="${tarjetaId}"
                            style="display: block; width: 100%; padding: 0.75rem 1rem; text-align: left; background: none; border: none; cursor: pointer; font-size: 0.9rem; color: #1f2937; transition: background 0.2s ease; border-bottom: 1px solid #f3f4f6;"
                            onmouseover="this.style.background = '#f9fafb';"
                            onmouseout="this.style.background = 'transparent';"
                        >
                            Editar
                        </button>
                        <button 
                            type="button"
                            class="btn-eliminar-epp-nuevo"
                            data-item-id="${tarjetaId}"
                            style="display: block; width: 100%; padding: 0.75rem 1rem; text-align: left; background: none; border: none; cursor: pointer; font-size: 0.9rem; color: #dc2626; transition: background 0.2s ease;"
                            onmouseover="this.style.background = '#fef2f2';"
                            onmouseout="this.style.background = 'transparent';"
                        >
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Detalles principales -->
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

            ${vu !== null || tot !== null ? `
                <!-- Detalles de precio -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <p style="margin: 0 0 0.25rem 0; font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Valor Unitario</p>
                        <p style="margin: 0; font-size: 1rem; font-weight: 600; color: #1f2937;">${vu !== null ? formatearNumero(vu) : 'N/A'}</p>
                    </div>
                    <div>
                        <p style="margin: 0 0 0.25rem 0; font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Total</p>
                        <p style="margin: 0; font-size: 1rem; font-weight: 700; color: #1f2937;">${tot !== null ? formatearNumero(tot) : ((vu !== null && cantidad) ? formatearNumero(vu * Number(cantidad)) : '0')}</p>
                    </div>
                </div>
            ` : ''}

            <!-- Imágenes -->
            ${this._crearGaleriaHTML(nombre, imagenes)}
        `;
        
        listaItems.appendChild(card);
        
        // Guardar referencia a las imágenes en cache para mantenerlas vivas
        if (imagenes && imagenes.length > 0) {
            this.imagenesCache.set(id, imagenes);
            console.log(`[EppItemManagerNuevo] Imágenes cacheadas para item: ${id}, total: ${imagenes.length}`);
        }
        
        console.log('[EppItemManagerNuevo] Tarjeta EPP creada:', id);
        this._inicializarInteractividad();
    }

    /**
     * Eliminar item visual
     */
    eliminarItem(id) {
        let item = document.querySelector(`.item-epp-card-nuevo[data-epp-id="${id}"]`);
        
        if (item) {
            item.remove();
            
            // Limpiar y revocar blob URLs del cache
            if (this.imagenesCache.has(id)) {
                const imagenes = this.imagenesCache.get(id);
                imagenes.forEach(imagen => {
                    if (imagen.previewUrl && imagen.previewUrl.startsWith('blob:')) {
                        URL.revokeObjectURL(imagen.previewUrl);
                        console.log(`[EppItemManagerNuevo] Blob URL revocada: ${imagen.previewUrl}`);
                    }
                });
            }
            
            this.imagenesCache.delete(id);
            
            // Renumerar las tarjetas restantes
            this._renumerarItems();
            
            console.log('[EppItemManagerNuevo] Item eliminado:', id, '- cache limpiado y URLs revocadas');
        } else {
            console.warn('[EppItemManagerNuevo] Item no encontrado para eliminar:', id);
        }
    }

    /**
     * Renumerar las tarjetas después de una eliminación
     */
    _renumerarItems() {
        const listaItems = document.getElementById(this.listaItemsId);
        if (!listaItems) return;
        
        const tarjetas = listaItems.querySelectorAll('.item-epp-card-nuevo');
        tarjetas.forEach((tarjeta, index) => {
            // Encontrar el span que contiene el número "EPP X"
            const spanNumero = tarjeta.querySelector('span[style*="background: #e0f2fe"]');
            if (spanNumero) {
                spanNumero.textContent = `EPP ${index + 1}`;
                console.log(`[EppItemManagerNuevo] Tarjeta renumerada a: EPP ${index + 1}`);
            }
        });
    }

    /**
     * Obtener item por ID
     */
    obtenerItem(id) {
        let item = document.querySelector(`.item-epp-card-nuevo[data-epp-id="${id}"]`);
        console.log('[EppItemManagerNuevo] obtenerItem buscando ID:', id, 'encontrado:', !!item);
        return item;
    }

    /**
     * Actualizar item
     */
    actualizarItem(id, datos) {
        const item = this.obtenerItem(id);
        if (!item) {
            console.warn('[EppItemManagerNuevo] Item no encontrado para actualizar:', id);
            return;
        }

        console.log('[EppItemManagerNuevo] Actualizando item:', id, datos);

        // Actualizar nombre
        if (datos.nombre !== undefined && datos.nombre !== null) {
            const h4 = item.querySelector('h4');
            if (h4) {
                h4.textContent = String(datos.nombre);
            }
        }

        // Actualizar cantidad
        if (datos.cantidad !== undefined) {
            const detallesDiv = item.querySelector('div[style*="grid-template-columns"]');
            if (detallesDiv) {
                const etiquetas = detallesDiv.querySelectorAll('p');
                if (etiquetas[1]) {
                    etiquetas[1].textContent = datos.cantidad;
                    console.log('[EppItemManagerNuevo] Cantidad actualizada:', datos.cantidad);
                }
            }
        }

        // Actualizar observaciones
        if (datos.observaciones !== undefined) {
            const detallesDiv = item.querySelector('div[style*="grid-template-columns"]');
            if (detallesDiv) {
                const etiquetas = detallesDiv.querySelectorAll('p');
                if (etiquetas[3]) {
                    etiquetas[3].textContent = datos.observaciones || 'N/A';
                    console.log('[EppItemManagerNuevo] Observaciones actualizadas:', datos.observaciones);
                }
            }
        }

        // Actualizar valor unitario / total
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
                    const detallesDiv = item.querySelector('div[style*="grid-template-columns"]');
                    const etiquetas = detallesDiv ? detallesDiv.querySelectorAll('p') : [];
                    return etiquetas && etiquetas[1] ? Number(etiquetas[1].textContent) : 0;
                })();

            const totalCalc = (tot !== null)
                ? tot
                : ((vu !== null && cantidadActual) ? (vu * Number(cantidadActual)) : 0);

            // Buscar sección de precios
            let seccionPrecio = item.querySelector('div:nth-child(4)[style*="grid-template-columns"]');
            if (!seccionPrecio) {
                // Crear sección de precios si no existe
                const detallesPrincipales = item.querySelector('div:nth-child(3)[style*="grid-template-columns"]');
                if (detallesPrincipales && (vu !== null || tot !== null)) {
                    const wrapper = document.createElement('div');
                    wrapper.style.cssText = 'display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;';
                    wrapper.innerHTML = `
                        <div>
                            <p style="margin: 0 0 0.25rem 0; font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Valor Unitario</p>
                            <p style="margin: 0; font-size: 1rem; font-weight: 600; color: #1f2937;">${vu !== null ? formatearNumero(vu) : 'N/A'}</p>
                        </div>
                        <div>
                            <p style="margin: 0 0 0.25rem 0; font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Total</p>
                            <p style="margin: 0; font-size: 1rem; font-weight: 700; color: #1f2937;">${Number.isFinite(totalCalc) ? formatearNumero(totalCalc) : '0'}</p>
                        </div>
                    `;
                    detallesPrincipales.insertAdjacentElement('afterend', wrapper);
                }
            } else {
                // Actualizar valores existentes
                const ps = seccionPrecio.querySelectorAll('p');
                if (ps && ps[1]) ps[1].textContent = (vu !== null ? formatearNumero(vu) : 'N/A');
                if (ps && ps[3]) ps[3].textContent = (Number.isFinite(totalCalc) ? formatearNumero(totalCalc) : '0');
            }
        }

        // Actualizar imágenes
        if (datos.imagenes !== undefined && Array.isArray(datos.imagenes)) {
            // Buscar o crear sección de imágenes
            let seccionImagenes = item.querySelector('div[style*="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;"]');
            
            if (seccionImagenes) {
                // Actualizar sección existente
                const galeriaHTML = this._crearGaleriaHTML(datos.nombre || 'EPP', datos.imagenes);
                seccionImagenes.innerHTML = galeriaHTML;
                console.log('[EppItemManagerNuevo] Imágenes actualizadas:', datos.imagenes.length);
            } else {
                // Crear nueva sección de imágenes si hay imágenes
                if (datos.imagenes.length > 0) {
                    const galeriaHTML = this._crearGaleriaHTML(datos.nombre || 'EPP', datos.imagenes);
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = galeriaHTML;
                    item.appendChild(wrapper);
                    console.log('[EppItemManagerNuevo] Sección de imágenes creada:', datos.imagenes.length);
                }
            }
        }

        console.log('[EppItemManagerNuevo] Item actualizado correctamente');
    }

    /**
     * Crear galería HTML
     */
    _crearGaleriaHTML(nombre, imagenes) {
        if (!imagenes || !Array.isArray(imagenes) || imagenes.length === 0) {
            return '';
        }

        return `
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <p style="margin: 0 0 0.75rem 0; font-size: 0.8rem; font-weight: 600; color: #0066cc; text-transform: uppercase; letter-spacing: 0.5px;">Imágenes</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 0.5rem;">
                    ${imagenes.map(img => {
                        let imgUrl = '';
                        let imgAlt = nombre;
                        
                        if (typeof img === 'string') {
                            imgUrl = img;
                        } else if (img.previewUrl) {
                            imgUrl = img.previewUrl;
                            imgAlt = img.nombre || nombre;
                        } else if (img.url || img.ruta_web) {
                            imgUrl = img.url || img.ruta_web;
                            imgAlt = img.nombre || nombre;
                        } else if (img.base64) {
                            imgUrl = img.base64;
                            imgAlt = img.nombre || nombre;
                        }
                        
                        if (imgUrl && !imgUrl.includes('placeholder')) {
                            return `
                                <div style="position: relative; border-radius: 4px; overflow: hidden; background: #f3f4f6; border: 1px solid #e5e7eb; aspect-ratio: 1;">
                                    <img src="${imgUrl}" alt="${imgAlt}" style="width: 100%; height: 100%; object-fit: cover; display: block; cursor: pointer;" 
                                         onclick="event.preventDefault(); event.stopPropagation(); if (window.mostrarImagenProcesoGrande) window.mostrarImagenProcesoGrande('${imgUrl}');"
                                         onerror="console.warn('Imagen no cargada'); this.style.display='none';">
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
     * Inicializar interactividad de menús
     */
    _inicializarInteractividad() {
        const listaItems = document.getElementById(this.listaItemsId);
        if (!listaItems) return;

        // Event delegation para menús desplegables
        listaItems.removeEventListener('click', this._handleMenuClick);
        this._handleMenuClick = (e) => {
            const btn = e.target.closest('.btn-menu-epp-nuevo');
            if (!btn) return;
            
            e.stopPropagation();
            const itemId = btn.getAttribute('data-item-id');
            if (!itemId) return;
            
            const submenu = document.querySelector(`.submenu-epp-nuevo[data-item-id="${itemId}"]`);
            if (!submenu) return;
            
            // Cerrar otros menús
            document.querySelectorAll('.submenu-epp-nuevo').forEach(menu => {
                if (menu !== submenu) {
                    menu.style.display = 'none';
                }
            });
            
            // Toggle menú actual
            submenu.style.display = submenu.style.display === 'none' ? 'flex' : 'none';
        };
        listaItems.addEventListener('click', this._handleMenuClick);

        // Event delegation para botones eliminar
        listaItems.removeEventListener('click', this._handleDeleteClick);
        this._handleDeleteClick = (e) => {
            const btn = e.target.closest('.btn-eliminar-epp-nuevo');
            if (!btn) return;
            
            e.stopPropagation();
            const itemId = btn.getAttribute('data-item-id');
            if (!itemId) return;
            
            if (window.Swal) {
                Swal.fire({
                    title: '¿Eliminar este EPP?',
                    text: 'Esta acción no se puede deshacer',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // 🔴 Obtener datos de la tarjeta ANTES de eliminar del DOM
                        const card = document.querySelector(`.item-epp-card-nuevo[data-epp-id="${itemId}"]`);
                        const eppOriginalId = card ? card.getAttribute('data-epp-original-id') : null;
                        
                        // Calcular posición visual de esta tarjeta entre todas las EPP cards
                        let posicionVisual = -1;
                        const todasLasTarjetas = document.querySelectorAll('.item-epp-card-nuevo');
                        todasLasTarjetas.forEach((tarjeta, idx) => {
                            if (tarjeta.getAttribute('data-epp-id') === itemId) {
                                posicionVisual = idx;
                            }
                        });
                        
                        console.log(`[EppItemManagerNuevo] Eliminando EPP - tarjetaId: ${itemId}, eppOriginalId: ${eppOriginalId}, posición: ${posicionVisual}`);
                        
                        // 1. Eliminar de gestionItemsUI ANTES de eliminar del DOM
                        if (window.gestionItemsUI && typeof window.gestionItemsUI.eliminarEPPPorTarjetaId === 'function') {
                            window.gestionItemsUI.eliminarEPPPorTarjetaId(itemId);
                            console.log('[EppItemManagerNuevo] EPP eliminado de gestionItemsUI');
                        }
                        
                        // 2. Eliminar del DOM
                        window.eppItemManagerNuevo.eliminarItem(itemId);
                        
                        // 3. Eliminar del array global window.itemsPedido usando posición correcta
                        if (window.itemsPedido) {
                            const longitudAntes = window.itemsPedido.length;
                            
                            if (posicionVisual >= 0) {
                                // Encontrar el EPP en la posición visual correcta dentro de itemsPedido
                                let eppCount = 0;
                                const idxToRemove = window.itemsPedido.findIndex(item => {
                                    if (item.tipo === 'epp') {
                                        if (eppCount === posicionVisual) {
                                            return true;
                                        }
                                        eppCount++;
                                    }
                                    return false;
                                });
                                
                                if (idxToRemove >= 0) {
                                    window.itemsPedido.splice(idxToRemove, 1);
                                    console.log(`[EppItemManagerNuevo] EPP eliminado por posición ${posicionVisual}. Items: ${longitudAntes} → ${window.itemsPedido.length}`);
                                }
                            }
                            
                            // Fallback: si no se eliminó por posición, intentar por epp_id
                            if (window.itemsPedido.length === longitudAntes && eppOriginalId) {
                                const fallbackIdx = window.itemsPedido.findIndex(item => 
                                    item.tipo === 'epp' && String(item.epp_id) === String(eppOriginalId)
                                );
                                if (fallbackIdx >= 0) {
                                    window.itemsPedido.splice(fallbackIdx, 1);
                                    console.log(`[EppItemManagerNuevo] EPP eliminado por epp_id fallback. Items: ${longitudAntes} → ${window.itemsPedido.length}`);
                                }
                            }
                            
                            console.log('[EppItemManagerNuevo] window.itemsPedido después de eliminar:', window.itemsPedido.length, 'items');
                        }
                        
                        Swal.fire('Eliminado', 'EPP eliminado correctamente', 'success');
                    }
                });
            }
        };
        listaItems.addEventListener('click', this._handleDeleteClick);

        // Event delegation para botones editar
        listaItems.removeEventListener('click', this._handleEditClick);
        this._handleEditClick = (e) => {
            const btn = e.target.closest('.btn-editar-epp-nuevo');
            if (!btn) return;
            
            e.stopPropagation();
            const itemId = btn.getAttribute('data-item-id');
            if (!itemId) return;
            
            const card = document.querySelector(`.item-epp-card-nuevo[data-epp-id="${itemId}"]`);
            if (!card) return;
            
            // Extraer datos del item
            const eppData = {
                id: card.getAttribute('data-epp-original-id'),
                nombre: card.querySelector('h4')?.textContent || '',
                cantidad: parseInt(card.querySelector('div:nth-child(2) p:nth-of-type(2)')?.textContent || 0),
                observaciones: card.querySelector('div:nth-child(2) p:nth-of-type(4)')?.textContent || '-'
            };
            
            // Obtener datos completos si existe en window.itemsPedido
            if (window.itemsPedido) {
                const item = window.itemsPedido.find(i => i.tipo === 'epp' && String(i.id) === String(eppData.id));
                if (item) {
                    Object.assign(eppData, item);
                }
            }
            
            // Si no hay imágenes en los datos del item, intentar extraer de la tarjeta
            if (!eppData.imagenes || eppData.imagenes.length === 0) {
                const imagenesEnTarjeta = [];
                // Buscar todas las imágenes en la sección de imágenes de la tarjeta
                const seccionImagenes = card.querySelector('div[style*="margin-top: 1rem"][style*="padding-top"]');
                if (seccionImagenes) {
                    const imgElements = seccionImagenes.querySelectorAll('img');
                    imgElements.forEach(img => {
                        if (img.src && !imagenesEnTarjeta.some(i => i.previewUrl === img.src)) {
                            imagenesEnTarjeta.push({
                                previewUrl: img.src,
                                url: img.src,
                                nombre: img.alt || 'Imagen EPP'
                            });
                        }
                    });
                }
                if (imagenesEnTarjeta.length > 0) {
                    eppData.imagenes = imagenesEnTarjeta;
                    console.log('[EppItemManagerNuevo] Imágenes extraídas de tarjeta:', imagenesEnTarjeta.length);
                }
            }
            
            // Llamar función de edición si existe
            if (typeof window.editarEPPAgregado === 'function') {
                window.editarEPPAgregado(eppData);
            } else {
                console.warn('[EppItemManagerNuevo] Función editarEPPAgregado no encontrada');
            }
        };
        listaItems.addEventListener('click', this._handleEditClick);

        // Cerrar menús al hacer clic fuera
        document.removeEventListener('click', this._handleOutsideClick);
        this._handleOutsideClick = (e) => {
            if (!e.target.closest('.btn-menu-epp-nuevo')) {
                document.querySelectorAll('.submenu-epp-nuevo').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        };
        document.addEventListener('click', this._handleOutsideClick);
    }

    /**
     * Contar items
     */
    contarItems() {
        return document.querySelectorAll('.item-epp-card-nuevo').length;
    }

    /**
     * Obtener todos los items
     */
    obtenerTodos() {
        return document.querySelectorAll('.item-epp-card-nuevo');
    }
}

// Exportar instancia global exclusiva para nuevo pedido
window.eppItemManagerNuevo = new EppItemManagerNuevo();
