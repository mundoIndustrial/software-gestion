/**
 * MULTI-RECEIPT HANDLER - Lavandería
 * Maneja la selección y gestión de múltiples recibos en un movimiento
 */

class MultiReceiptHandler {
    constructor() {
        this.selectedRecibos = [];
        this.recibosData = {};
    }

    /**
     * Agrega un recibo a la selección
     */
    addRecibo(recibo) {
        // Verificar si el recibo ya está seleccionado
        const exists = this.selectedRecibos.find(r => 
            r.id === recibo.id && r.tipo_recibo_original === recibo.tipo_recibo_original
        );

        if (exists) {
            return false; // Ya está seleccionado
        }

        // Obtener prenda_id o prenda_bodega_id según el tipo
        let prendaId = null;
        let prendaBodegaId = null;

        if (recibo.tipo_recibo_original === 'CORTE-PARA-BODEGA') {
            // Para BODEGA, obtener prenda_bodega_id
            prendaBodegaId = recibo.prendaBodega?.id || null;
        } else {
            // Para COSTURA, obtener prenda_id
            prendaId = recibo.prenda?.id || null;
        }

        this.selectedRecibos.push({
            id: recibo.id,
            numero_recibo: recibo.numero_recibo,
            tipo_recibo_original: recibo.tipo_recibo_original,
            tipo_recibo_mostrar: recibo.tipo_recibo,
            cliente: recibo.cliente,
            prenda: recibo.prenda,
            prenda_id: prendaId,
            prenda_bodega_id: prendaBodegaId,
            tallas: recibo.tallas || []
        });

        // Guardar datos del recibo
        this.recibosData[recibo.id] = {
            tallas: recibo.tallas || [],
            selectedTallas: []
        };

        return true;
    }

    /**
     * Elimina un recibo de la selección
     */
    removeRecibo(reciboId) {
        this.selectedRecibos = this.selectedRecibos.filter(r => r.id !== reciboId);
        delete this.recibosData[reciboId];
    }

    /**
     * Obtiene los recibos seleccionados
     */
    getSelectedRecibos() {
        return this.selectedRecibos;
    }

    /**
     * Limpia la selección
     */
    clear() {
        this.selectedRecibos = [];
        this.recibosData = {};
    }

    /**
     * Obtiene el total de recibos seleccionados
     */
    getCount() {
        return this.selectedRecibos.length;
    }

    /**
     * Verifica si un recibo está seleccionado
     */
    isSelected(reciboId) {
        return this.selectedRecibos.some(r => r.id === reciboId);
    }

    /**
     * Obtiene las tallas seleccionadas para un recibo específico
     */
    getSelectedTallasForRecibo(reciboId) {
        return this.recibosData[reciboId]?.selectedTallas || [];
    }

    /**
     * Establece las tallas seleccionadas para un recibo
     */
    setSelectedTallasForRecibo(reciboId, tallas) {
        if (this.recibosData[reciboId]) {
            this.recibosData[reciboId].selectedTallas = tallas;
        }
    }

    /**
     * Obtiene todas las tallas de todos los recibos seleccionados
     */
    getAllSelectedTallas() {
        const allTallas = [];
        
        this.selectedRecibos.forEach(recibo => {
            const selectedTallas = this.recibosData[recibo.id]?.selectedTallas || [];
            allTallas.push(...selectedTallas);
        });

        return allTallas;
    }

    /**
     * Renderiza los recibos seleccionados
     */
    renderSelectedRecibos(container) {
        if (this.selectedRecibos.length === 0) {
            container.innerHTML = '<p style="color: #94a3b8; text-align: center; margin: 0;">No hay recibos seleccionados</p>';
            return;
        }

        container.innerHTML = this.selectedRecibos.map(recibo => {
            let colorTipo = '#2450ef';
            let bgColorTipo = '#f0f4ff';
            
            if (recibo.tipo_recibo_mostrar === 'BODEGA') {
                colorTipo = '#059669';
                bgColorTipo = '#f0fdf4';
            }

            return `
                <div class="recibo-card" data-recibo-id="${recibo.id}" style="
                    background: ${bgColorTipo};
                    border: 2px solid ${colorTipo};
                    border-radius: 12px;
                    padding: 16px;
                    margin-bottom: 12px;
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                ">
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span style="font-weight: 700; font-size: 16px; color: #1e293b;">
                                Recibo #${recibo.numero_recibo}
                            </span>
                            <span style="
                                background: ${colorTipo};
                                color: white;
                                padding: 4px 12px;
                                border-radius: 20px;
                                font-size: 12px;
                                font-weight: 600;
                            ">
                                ${recibo.tipo_recibo_mostrar}
                            </span>
                        </div>
                        
                        ${recibo.tipo_recibo_mostrar !== 'BODEGA' ? `
                            <div style="font-size: 13px; color: #64748b; margin-bottom: 4px;">
                                <strong>Cliente:</strong> ${recibo.cliente}
                            </div>
                        ` : ''}
                        
                        <div style="font-size: 13px; color: #64748b;">
                            <strong>Prenda:</strong> ${recibo.prenda}
                        </div>
                    </div>
                    
                    <button class="btn-remove-recibo" data-recibo-id="${recibo.id}" style="
                        background: none;
                        border: none;
                        color: #ef4444;
                        cursor: pointer;
                        padding: 4px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 20px;
                    ">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>
            `;
        }).join('');
    }
}

export { MultiReceiptHandler };
