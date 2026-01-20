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
            console.warn(`Contenedor ${this.containerId} no encontrado`);
            return;
        }

        if (!items || items.length === 0) {
            container.innerHTML = this.obtenerPlantillaVacia();
            return;
        }

        await this.renderizar(items, container);
    }

    /**
     * Renderizar lista de ítems
     */
    async renderizar(items, container) {
        container.innerHTML = '';

        for (let index = 0; index < items.length; index++) {
            const item = items[index];
            try {
                const html = await this.obtenerHTMLItem(item, index);
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                container.appendChild(tempDiv.firstElementChild);
            } catch (error) {
                console.error(`Error al renderizar ítem ${index}:`, error);
            }
        }

        this.actualizarInteractividad();
    }

    /**
     * Obtener HTML de un ítem desde el servidor
     */
    async obtenerHTMLItem(item, index) {
        if (!this.apiService) {
            throw new Error('API Service no configurado');
        }

        const resultado = await this.apiService.renderizarItemCard(item, index);
        
        if (!resultado.success || !resultado.html) {
            throw new Error(resultado.error || 'Error al renderizar componente');
        }

        return resultado.html;
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
                    <strong>Asesora:</strong>
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
        
        const nombre = item.prenda?.nombre || item.nombre || 'Prenda';
        const cantidadTotal = item.cantidad || (item.tallas?.reduce((sum, t) => sum + (t.cantidad || 0), 0) || 0);
        const origen = item.origen === 'bodega' ? '📦 BODEGA' : '🏭 CONFECCIÓN';
        
        itemDiv.innerHTML = `
            <div style="${estilos.itemTitulo || this.getEstiloItemTitulo()}">
                <strong>#${idx + 1} - ${nombre}</strong>
            </div>
            <div style="${estilos.itemMetadata || this.getEstiloItemMetadata()}">
                <span>${origen}</span>
                ${item.procesos?.length > 0 ? `<span>Procesos: ${item.procesos.join(', ')}</span>` : ''}
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
