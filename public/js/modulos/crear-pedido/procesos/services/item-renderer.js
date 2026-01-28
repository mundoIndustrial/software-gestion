/**
 * ItemRenderer - Renderizador de Ítems
 * 
 * Responsabilidad única: Renderizar UI de ítems en el DOM
 * 
 * Principios SOLID aplicados:
 * - SRP: Solo renderiza
 * - DIP: Inyecta servicio de API para obtener HTML
 * - OCP: Fácil de extender con nuevas plantillas
 */
class ItemRenderer {
    constructor(options = {}) {
        this.apiService = options.apiService;
        this.containerId = options.containerId || 'lista-items-pedido';
        this.templates = options.templates || {};
    }

    /**
     * Actualizar vista de ítems
     */
    async actualizar(items) {
        const container = document.getElementById(this.containerId);
        if (!container) {

            return;
        }

        if (!items || items.length === 0) {
            container.innerHTML = this.obtenerPlantillaVacia();
            return;
        }

        await this.renderizar(items, container);
    }

    /**
     * Renderizar lista de ítems con agrupación
     */
    async renderizar(items, container) {
        container.innerHTML = '';

        // Separar items por tipo
        const prendas = [];
        const epps = [];
        
        items.forEach((item, idx) => {
            // Detectar tipo: si tiene 'telasAgregadas' o 'generosConTallas' es prenda, si no es EPP
            if (item.telasAgregadas || item.generosConTallas || item.variantes) {
                prendas.push({ item, index: idx });
            } else {
                epps.push({ item, index: idx });
            }
        });

        // Renderizar grupo PRENDAS
        if (prendas.length > 0) {
            const headerPrendas = this._crearEncabezadoGrupo(' Prendas', prendas.length);
            container.appendChild(headerPrendas);
            
            for (const { item, index } of prendas) {
                try {
                    const html = await this.obtenerHTMLItem(item, index);
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    container.appendChild(tempDiv.firstElementChild);
                } catch (error) {

                }
            }
        }

        // Renderizar grupo EPPs
        if (epps.length > 0) {
            const headerEPPs = this._crearEncabezadoGrupo(' EPPs', epps.length);
            container.appendChild(headerEPPs);
            
            for (const { item, index } of epps) {
                try {
                    const html = await this.obtenerHTMLItem(item, index);
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    container.appendChild(tempDiv.firstElementChild);
                } catch (error) {

                }
            }
        }

        this.actualizarInteractividad();
    }

    /**
     * Crear encabezado de grupo
     */
    _crearEncabezadoGrupo(titulo, cantidad) {
        const header = document.createElement('div');
        header.className = 'grupo-items-header';
        header.style.cssText = `
            padding: 1rem 1.5rem;
            margin: 1.5rem 0 1rem 0;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-left: 4px solid #0ea5e9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        `;
        
        header.innerHTML = `
            <h3 style="margin: 0; color: #0369a1; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 0.75rem;">
                ${titulo}
            </h3>
            <span style="background: #0ea5e9; color: white; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 700;">
                ${cantidad}
            </span>
        `;
        
        return header;
    }

    /**
     * Obtener HTML de un ítem usando la tarjeta ReadOnly
     */
    async obtenerHTMLItem(item, index) {
        // Detectar si es un EPP
        if (item.tipo === 'epp' || item.epp_id) {

            return this._generarTarjetaEPP(item, index);
        }

        // Usar la función generarTarjetaPrendaReadOnly para prendas
        if (typeof window.generarTarjetaPrendaReadOnly === 'function') {

            return window.generarTarjetaPrendaReadOnly(item, index);
        }

        throw new Error('generarTarjetaPrendaReadOnly no está disponible');
    }

    /**
     * Generar tarjeta de EPP
     */
    _generarTarjetaEPP(epp, index) {
        const galeriaHTML = this._generarGaleriaEPP(epp.imagenes || []);
        
        // Calcular número de EPP (contar solo items-epp-card, no prendas)
        const eppCount = document.querySelectorAll('.item-epp-card').length;
        const numeroItem = eppCount + 1;
        
        return `
            <div class="item-epp-card" data-epp-index="${index}" data-epp-id="${epp.epp_id}" data-pedido-epp-id="${epp.id || epp.pedido_epp_id || epp.epp_id}" style="padding: 1.5rem; background: white; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 1rem;">
                <!-- Header -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                    <div>
                        <span style="display: inline-block; background: #e0f2fe; color: #0066cc; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.5rem;">EPP ${numeroItem}</span>
                        <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 600; color: #1f2937;">${epp.nombre_epp || epp.nombre || ''}</h4>
                    </div>
                    <!-- Contenedor del botón y menú con posicionamiento relativo -->
                    <div style="position: relative;">
                        <button class="btn-menu-epp" data-item-id="${epp.epp_id}" type="button" style="background: none; border: none; cursor: pointer; font-size: 1.5rem; color: #6b7280;">⋮</button>
                        
                        <!-- Menú -->
                        <div class="submenu-epp" data-item-id="${epp.epp_id}" style="display: none; position: absolute; top: 100%; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); min-width: 140px; z-index: 1000; flex-direction: column;">
                            <button 
                                type="button"
                                class="btn-editar-epp"
                                data-item-id="${epp.epp_id}"
                                style="display: block; width: 100%; padding: 0.75rem 1rem; text-align: left; background: none; border: none; cursor: pointer; font-size: 0.9rem; color: #1f2937; transition: background 0.2s ease; border-bottom: 1px solid #f3f4f6;"
                                onmouseover="this.style.background = '#f9fafb';"
                                onmouseout="this.style.background = 'transparent';"
                            >
                                Editar
                            </button>
                            <button 
                                type="button"
                                class="btn-eliminar-epp"
                                data-item-id="${epp.epp_id}"
                                style="display: block; width: 100%; padding: 0.75rem 1rem; text-align: left; background: none; border: none; cursor: pointer; font-size: 0.9rem; color: #dc2626; transition: background 0.2s ease;"
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
                        <p style="margin: 0; font-size: 1rem; font-weight: 500; color: #1f2937;">${epp.cantidad || 0}</p>
                    </div>
                    <div>
                        <p style="margin: 0 0 0.25rem 0; font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Observaciones</p>
                        <p style="margin: 0; font-size: 1rem; font-weight: 500; color: #1f2937;">${epp.observaciones || 'N/A'}</p>
                    </div>
                </div>

                ${galeriaHTML}
            </div>
        `;
    }

    /**
     * Generar galería de imágenes para EPP
     */
    _generarGaleriaEPP(imagenes) {
        if (!imagenes || imagenes.length === 0) {
            return '';
        }

        return `
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #bfdbfe;">
                <p style="margin: 0 0 0.75rem 0; font-size: 0.8rem; font-weight: 600; color: #0066cc; text-transform: uppercase; letter-spacing: 0.5px;">Imágenes</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 0.5rem;">
                    ${imagenes.map(img => `
                        <div style="position: relative; border-radius: 4px; overflow: hidden; background: #f3f4f6; border: 1px solid #e5e7eb; aspect-ratio: 1;">
                            <img src="${img.preview || img.url || ''}" alt="Imagen EPP" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    /**
     * Actualizar interactividad de tarjetas
     */
    actualizarInteractividad() {
        if (window.updateItemCardInteractions) {
            window.updateItemCardInteractions();
        }
    }

    /**
     * Renderizar vista previa de pedido
     */
    renderizarVistaPreviaFactura(pedidoData, estilos = {}) {
        const modal = document.createElement('div');
        modal.style.cssText = estilos.modal || this.getEstiloModal();
        
        const contenedor = document.createElement('div');
        contenedor.style.cssText = estilos.contenedor || this.getEstiloContenedor();
        
        // Header
        const header = this.crearHeaderModal(modal, estilos);
        contenedor.appendChild(header);
        
        // Contenido
        const contenido = this.crearContenidoVistaPreviaFactura(pedidoData, estilos);
        contenedor.appendChild(contenido);
        
        // Footer
        const footer = this.crearFooterVistaPreviaFactura(modal, estilos);
        contenedor.appendChild(footer);
        
        modal.appendChild(contenedor);
        document.body.appendChild(modal);
        
        // Cerrar al click fuera
        modal.onclick = (e) => {
            if (e.target === modal) modal.remove();
        };
        
        return modal;
    }

    /**
     * Crear header del modal
     * @private
     */
    crearHeaderModal(modal, estilos) {
        const header = document.createElement('div');
        header.style.cssText = estilos.header || this.getEstiloHeader();
        
        const titulo = document.createElement('h2');
        titulo.textContent = 'Vista Previa del Pedido';
        titulo.style.cssText = estilos.titulo || this.getEstiloTitulo();
        header.appendChild(titulo);
        
        const btnCerrar = document.createElement('button');
        btnCerrar.innerHTML = '✕';
        btnCerrar.style.cssText = estilos.btnCerrar || this.getEstiloBtnCerrar();
        btnCerrar.onclick = () => modal.remove();
        header.appendChild(btnCerrar);
        
        return header;
    }

    /**
     * Crear contenido de vista previa
     * @private
     */
    crearContenidoVistaPreviaFactura(pedidoData, estilos) {
        const contenido = document.createElement('div');
        contenido.style.cssText = estilos.contenido || this.getEstiloContenido();
        
        // Información del pedido
        const infoPedido = document.createElement('div');
        infoPedido.style.cssText = estilos.infoPedido || this.getEstiloInfoPedido();
        infoPedido.innerHTML = this.crearHTMLInfoPedido(pedidoData);
        contenido.appendChild(infoPedido);
        
        // Título de ítems
        const tituloItems = document.createElement('h3');
        tituloItems.textContent = 'Ítems del Pedido';
        tituloItems.style.cssText = estilos.tituloItems || this.getEstiloTituloItems();
        contenido.appendChild(tituloItems);
        
        // Lista de ítems
        const itemsContainer = this.crearListaItems(pedidoData.items || [], estilos);
        contenido.appendChild(itemsContainer);
        
        return contenido;
    }

    /**
     * Crear HTML de información del pedido
     * @private
     */
    crearHTMLInfoPedido(pedidoData) {
        const cliente = pedidoData.cliente || 'No especificado';
        const asesora = pedidoData.asesora || 'No especificado';
        const forma = pedidoData.forma_de_pago || 'No especificado';
        
        return `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <strong>Cliente:</strong>
                    <p>${cliente}</p>
                </div>
                <div>
                    <strong>Asesor:</strong>
                    <p>${asesora}</p>
                </div>
                <div>
                    <strong>Forma de Pago:</strong>
                    <p>${forma}</p>
                </div>
            </div>
        `;
    }

    /**
     * Crear lista de ítems para vista previa
     * @private
     */
    crearListaItems(items, estilos) {
        const container = document.createElement('div');
        container.style.cssText = estilos.itemsContainer || this.getEstiloItemsContainer();
        
        if (items.length === 0) {
            const vacio = document.createElement('p');
            vacio.textContent = 'No hay ítems agregados';
            container.appendChild(vacio);
            return container;
        }
        
        items.forEach((item, idx) => {
            const itemDiv = this.crearElementoItem(item, idx, estilos);
            container.appendChild(itemDiv);
        });
        
        return container;
    }

    /**
     * Crear elemento de ítem individual
     * @private
     */
    crearElementoItem(item, idx, estilos) {
        const itemDiv = document.createElement('div');
        itemDiv.style.cssText = estilos.itemDiv || this.getEstiloItemDiv();
        
        const nombre = item.nombre_prenda || item.prenda?.nombre || item.nombre || 'Prenda';
        const cantidadTotal = item.cantidad || (item.cantidad_talla ? Object.values(item.cantidad_talla).reduce((sum, cant) => sum + (cant || 0), 0) : 0) || (item.tallas?.reduce((sum, t) => sum + (t.cantidad || 0), 0) || 0);
        const origen = item.origen === 'bodega' ? '📦 BODEGA' : '🏭 CONFECCIÓN';
        
        // Helper para validar y renderizar procesos
        console.log('[item-renderer] 🔍 Procesando procesos para item:', {
            nombre: nombre,
            tieneProcesos: !!item.procesos,
            tipoProcesos: typeof item.procesos,
            esArray: Array.isArray(item.procesos),
            procesos: item.procesos
        });
        
        let procesosHTML = '';
        if (item.procesos) {
            if (Array.isArray(item.procesos) && item.procesos.length > 0) {
                console.log('[item-renderer] 📋 Procesos es array:', item.procesos);
                procesosHTML = `<span>Procesos: ${item.procesos.join(', ')}</span>`;
            } else if (!Array.isArray(item.procesos) && typeof item.procesos === 'object') {
                console.log('[item-renderer] 📦 Procesos es objeto, extrayendo keys...');
                const todasLasKeys = Object.keys(item.procesos);
                console.log('[item-renderer]   - Todas las keys:', todasLasKeys);
                
                const procesosArray = todasLasKeys.filter(key => {
                    const proceso = item.procesos[key];
                    const esValido = proceso && (proceso.datos || proceso.tipo);
                    console.log(`[item-renderer]   - Key "${key}":`, {
                        proceso: proceso,
                        tieneDatos: !!(proceso && proceso.datos),
                        tieneTipo: !!(proceso && proceso.tipo),
                        esValido: esValido
                    });
                    return esValido;
                });
                
                console.log('[item-renderer]   - Procesos válidos filtrados:', procesosArray);
                
                if (procesosArray && procesosArray.length > 0) {
                    console.log('[item-renderer]   - Mapeando nombres...');
                    const nombresProcesos = procesosArray.map(p => {
                        const nombre = p.charAt(0).toUpperCase() + p.slice(1);
                        console.log(`[item-renderer]     - "${p}" -> "${nombre}"`);
                        return nombre;
                    });
                    console.log('[item-renderer]   - Array de nombres:', nombresProcesos);
                    console.log('[item-renderer]   - Tipo de nombresProcesos:', typeof nombresProcesos, 'esArray:', Array.isArray(nombresProcesos));
                    
                    try {
                        procesosHTML = `<span>Procesos: ${nombresProcesos.join(', ')}</span>`;
                        console.log('[item-renderer]   HTML generado:', procesosHTML);
                    } catch (joinError) {
                        console.error('[item-renderer]  ERROR EN JOIN:', joinError);
                        console.error('[item-renderer]  nombresProcesos era:', nombresProcesos);
                        console.error('[item-renderer]  Stack:', joinError.stack);
                        // Fallback
                        procesosHTML = `<span>Procesos: ${procesosArray.join(', ')}</span>`;
                    }
                }
            }
        }
        
        console.log('[item-renderer] 📝 procesosHTML final:', procesosHTML);

        itemDiv.innerHTML = `
            <div style="${estilos.itemTitulo || this.getEstiloItemTitulo()}">
                <strong>#${idx + 1} - ${nombre}</strong>
            </div>
            <div style="${estilos.itemMetadata || this.getEstiloItemMetadata()}">
                <span>${origen}</span>
                ${procesosHTML}
            </div>
            <div style="margin-top: 10px; padding: 10px; background-color: #f5f5f5; border-radius: 4px;">
                <strong>Cantidad total: ${cantidadTotal} unidades</strong>
            </div>
        `;
        
        return itemDiv;
    }

    /**
     * Crear footer con botones de acción
     * @private
     */
    crearFooterVistaPreviaFactura(modal, estilos) {
        const footer = document.createElement('div');
        footer.style.cssText = estilos.footer || this.getEstiloFooter();
        
        const btnImpreso = document.createElement('button');
        btnImpreso.textContent = '🖨️ Imprimir';
        btnImpreso.style.cssText = estilos.btnAccion || this.getEstiloBtnAccion();
        btnImpreso.onclick = () => window.print();
        footer.appendChild(btnImpreso);
        
        const btnContinuar = document.createElement('button');
        btnContinuar.textContent = '✓ Continuar y Crear Pedido';
        btnContinuar.style.cssText = (estilos.btnContinuar || this.getEstiloBtnContinuar()) + 
                                     (estilos.btnAccion || this.getEstiloBtnAccion());
        btnContinuar.onclick = () => {
            modal.remove();
            document.getElementById('formCrearPedidoEditable')?.submit();
        };
        footer.appendChild(btnContinuar);
        
        return footer;
    }

    /**
     * Obtener plantilla de contenedor vacío
     * @private
     */
    obtenerPlantillaVacia() {
        return '<p style="text-align: center; color: #999; padding: 20px;">No hay ítems agregados</p>';
    }

    // ============ ESTILOS ============

    getEstiloModal() {
        return `position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                background-color: rgba(0,0,0,0.5); display: flex; align-items: center; 
                justify-content: center; z-index: 9999;`;
    }

    getEstiloContenedor() {
        return `background-color: white; border-radius: 8px; max-width: 800px; 
                max-height: 90vh; overflow-y: auto; width: 90%; padding: 0;`;
    }

    getEstiloHeader() {
        return `display: flex; justify-content: space-between; align-items: center; 
                padding: 20px; border-bottom: 1px solid #e0e0e0; background-color: #f5f5f5;`;
    }

    getEstiloTitulo() {
        return `margin: 0; font-size: 20px; font-weight: 600; color: #333;`;
    }

    getEstiloBtnCerrar() {
        return `background: none; border: none; font-size: 24px; cursor: pointer; color: #666;`;
    }

    getEstiloContenido() {
        return `padding: 20px; overflow-y: auto; max-height: calc(90vh - 140px);`;
    }

    getEstiloInfoPedido() {
        return `background-color: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 20px;`;
    }

    getEstiloTituloItems() {
        return `margin: 20px 0 15px 0; font-size: 16px; font-weight: 600; color: #333;`;
    }

    getEstiloItemsContainer() {
        return `display: flex; flex-direction: column; gap: 15px;`;
    }

    getEstiloItemDiv() {
        return `border: 1px solid #ddd; border-radius: 4px; padding: 15px; 
                background-color: #fafafa;`;
    }

    getEstiloItemTitulo() {
        return `font-size: 14px; font-weight: 600; margin-bottom: 10px; color: #333;`;
    }

    getEstiloItemMetadata() {
        return `font-size: 12px; color: #666; display: flex; gap: 15px;`;
    }

    getEstiloFooter() {
        return `display: flex; gap: 10px; justify-content: flex-end; padding: 15px 20px; 
                border-top: 1px solid #e0e0e0; background-color: #f5f5f5;`;
    }

    getEstiloBtnAccion() {
        return `padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; 
                font-size: 14px; font-weight: 500; transition: all 0.3s;`;
    }

    getEstiloBtnContinuar() {
        return `background-color: #4CAF50; color: white;`;
    }
}

window.ItemRenderer = ItemRenderer;
