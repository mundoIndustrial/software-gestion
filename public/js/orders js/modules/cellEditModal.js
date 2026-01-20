/**
 * MÓDULO: cellEditModal.js
 * Responsabilidad: Gestionar el modal de edición de celdas
 * Principios SOLID: SRP (Single Responsibility)
 */

console.log(' Cargando CellEditModal...');

const CellEditModal = {
    /**
     * Configuración del modal
     */
    config: {
        modalId: 'cell-edit-modal',
        overlayId: 'cell-edit-overlay',
        inputId: 'cell-edit-input',
        saveButtonId: 'cell-edit-save',
        cancelButtonId: 'cell-edit-cancel',
    },

    /**
     * Estado actual del modal
     */
    state: {
        isOpen: false,
        currentCell: null,
        currentColumn: null,
        currentOrderId: null,
        originalValue: null,
    },

    /**
     * Inicializar el módulo
     */
    initialize() {
        console.log(' Inicializando CellEditModal...');
        this._createModalHTML();
        this._attachEventListeners();
        console.log(' CellEditModal inicializado');
    },

    /**
     * Crear el HTML del modal si no existe
     */
    _createModalHTML() {
        if (document.getElementById(this.config.modalId)) {
            return; // Ya existe
        }

        const modalHTML = `
            <div id="${this.config.overlayId}" class="cell-edit-overlay" style="display: none;"></div>
            <div id="${this.config.modalId}" class="cell-edit-modal" style="display: none;">
                <div class="cell-edit-modal-content">
                    <div class="cell-edit-modal-header">
                        <h3 id="cell-edit-title">Ver contenido</h3>
                        <button class="cell-edit-close" id="${this.config.cancelButtonId}">&times;</button>
                    </div>
                    <div class="cell-edit-modal-body">
                        <div id="${this.config.inputId}" class="cell-view-content"></div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this._addModalStyles();
    },

    /**
     * Agregar estilos CSS al modal
     */
    _addModalStyles() {
        const styleId = 'cell-edit-modal-styles';
        if (document.getElementById(styleId)) return;

        const styles = `
            <style id="${styleId}">
                .cell-edit-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 9997;
                }

                .cell-edit-modal {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                    z-index: 9998;
                    min-width: 400px;
                    max-width: 600px;
                }

                .cell-edit-modal-content {
                    display: flex;
                    flex-direction: column;
                    height: 100%;
                }

                .cell-edit-modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px;
                    border-bottom: 1px solid #e5e7eb;
                }

                .cell-edit-modal-header h3 {
                    margin: 0;
                    font-size: 18px;
                    font-weight: 600;
                    color: #1f2937;
                }

                .cell-edit-close {
                    background: none;
                    border: none;
                    font-size: 28px;
                    cursor: pointer;
                    color: #6b7280;
                    padding: 0;
                    width: 32px;
                    height: 32px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .cell-edit-close:hover {
                    color: #1f2937;
                }

                .cell-edit-modal-body {
                    padding: 20px;
                    flex: 1;
                    overflow-y: auto;
                }

                .cell-view-content {
                    width: 100%;
                    padding: 15px;
                    border: 1px solid #d1d5db;
                    border-radius: 6px;
                    font-size: 14px;
                    font-family: inherit;
                    min-height: 120px;
                    max-height: 400px;
                    overflow-y: auto;
                    background: #f9fafb;
                    color: #000000;
                    font-weight: 500;
                    line-height: 1.6;
                    white-space: pre-wrap;
                    word-wrap: break-word;
                }

                .cell-edit-modal-footer {
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                    padding: 20px;
                    border-top: 1px solid #e5e7eb;
                    background: #f9fafb;
                }

                .btn {
                    padding: 8px 16px;
                    border: none;
                    border-radius: 6px;
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .btn-primary {
                    background: #3b82f6;
                    color: white;
                }

                .btn-primary:hover {
                    background: #2563eb;
                }

                .btn-secondary {
                    background: #e5e7eb;
                    color: #374151;
                }

                .btn-secondary:hover {
                    background: #d1d5db;
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);
    },

    /**
     * Adjuntar event listeners
     */
    _attachEventListeners() {
        const modal = document.getElementById(this.config.modalId);
        const overlay = document.getElementById(this.config.overlayId);
        const cancelBtn = document.getElementById(this.config.cancelButtonId);

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.close());
        }

        if (overlay) {
            overlay.addEventListener('click', () => this.close());
        }

        // Cerrar con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.state.isOpen) {
                this.close();
            }
        });
    },

    /**
     * Abrir el modal de visualización
     */
    open(orderId, column, currentValue) {
        console.log(`👁️ Abriendo modal de visualización para orden ${orderId}, columna ${column}`);

        this.state.currentOrderId = orderId;
        this.state.currentColumn = column;
        this.state.originalValue = currentValue;

        const modal = document.getElementById(this.config.modalId);
        const overlay = document.getElementById(this.config.overlayId);
        const contentDiv = document.getElementById(this.config.inputId);
        const title = document.getElementById('cell-edit-title');

        if (title) {
            title.textContent = `${this._getColumnLabel(column)}`;
        }

        // Si es descripción, obtener datos de prendas para mostrar plantilla
        if (column === 'descripcion') {
            this._loadOrderDataAndRender(orderId, contentDiv, currentValue);
        } else {
            if (contentDiv) {
                contentDiv.textContent = currentValue || '(vacío)';
            }
        }

        if (modal) {
            modal.style.display = 'block';
        }

        if (overlay) {
            overlay.style.display = 'block';
        }

        this.state.isOpen = true;
    },

    /**
     * Cargar datos de orden y renderizar plantilla si es cotización
     */
    _loadOrderDataAndRender(orderId, contentDiv, currentValue) {
        fetch(`/orders/${orderId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Error fetching order data');
            return response.json();
        })
        .then(data => {
            console.log(' Datos de orden obtenidos:', data);
            
            if (data.prendas && data.prendas.length > 0) {
                // Guardar estado para paginación
                window.cellModalPrendasState = {
                    todasLasPrendas: data.prendas,
                    currentPage: 0,
                    prendasPorPagina: 2,
                    esCotizacion: data.es_cotizacion || false,
                    contentDiv: contentDiv
                };
                
                // Renderizar primera página
                this._renderCellModalPrendasPage();
            } else {
                // Mostrar descripción simple
                if (contentDiv) {
                    contentDiv.textContent = currentValue || '(vacío)';
                }
            }
        })
        .catch(error => {
            console.error(' Error obteniendo datos de orden:', error);
            if (contentDiv) {
                contentDiv.textContent = currentValue || '(vacío)';
            }
        });
    },

    /**
     * Renderizar página actual de prendas en modal de celda
     */
    _renderCellModalPrendasPage() {
        const state = window.cellModalPrendasState;
        if (!state) return;

        const { todasLasPrendas, currentPage, prendasPorPagina, esCotizacion, contentDiv } = state;
        
        if (!todasLasPrendas || todasLasPrendas.length === 0) {
            if (contentDiv) {
                contentDiv.textContent = '-';
            }
            return;
        }
        
        // Calcular índices de inicio y fin
        const startIndex = currentPage * prendasPorPagina;
        const endIndex = startIndex + prendasPorPagina;
        const prendasActuales = todasLasPrendas.slice(startIndex, endIndex);
        
        let html = '';
        
        if (esCotizacion) {
            // Usar plantilla de cotización
            prendasActuales.forEach((prenda, index) => {
                html += `<strong style="font-size: 15px;">PRENDA ${prenda.numero}: ${prenda.nombre}</strong><br>
${prenda.atributos}<br>
<strong>DESCRIPCION:</strong> ${prenda.descripcion}`;
                
                // Agregar detalles si existen
                if (prenda.detalles && prenda.detalles.length > 0) {
                    prenda.detalles.forEach(detalle => {
                        html += `<br>&nbsp;&nbsp;&nbsp;. <strong style="color: #666;">${detalle.tipo}:</strong> ${detalle.valor}`;
                    });
                }
                
                html += `<br><strong>Tallas:</strong> <span style="color: red; font-weight: bold;">${prenda.tallas}</span>`;
                
                // Agregar línea separadora solo entre prendas mostradas
                if (index < prendasActuales.length - 1) {
                    html += `<br><hr style="border: none; border-top: 2px solid #ccc; margin: 16px 0;">`;
                }
            });
        } else {
            // Usar formato simple para pedidos sin cotización
            prendasActuales.forEach(prenda => {
                // Parsear y formatear tallas
                let tallasFormato = '-';
                try {
                    if (typeof prenda.cantidad_talla === 'string') {
                        const tallasObj = JSON.parse(prenda.cantidad_talla);
                        tallasFormato = Object.entries(tallasObj)
                            .map(([talla, cantidad]) => `${talla}: ${cantidad}`)
                            .join(', ');
                    } else if (typeof prenda.cantidad_talla === 'object' && prenda.cantidad_talla !== null) {
                        tallasFormato = Object.entries(prenda.cantidad_talla)
                            .map(([talla, cantidad]) => `${talla}: ${cantidad}`)
                            .join(', ');
                    } else {
                        tallasFormato = prenda.cantidad_talla || '-';
                    }
                } catch (e) {
                    tallasFormato = prenda.cantidad_talla || '-';
                }
                
                html += `<strong>PRENDA ${prenda.numero}: ${prenda.nombre}</strong><br>
<span>DESCRIPCION: ${prenda.descripcion}</span><br>
<span>TALLAS: <span style="color: red; font-weight: bold;">${tallasFormato}</span></span>`;
                
                // Agregar línea separadora solo entre prendas mostradas
                if (prendasActuales.indexOf(prenda) < prendasActuales.length - 1) {
                    html += `<br><hr style="border: none; border-top: 2px solid #ccc; margin: 16px 0;">`;
                }
            });
        }
        
        if (contentDiv) {
            contentDiv.innerHTML = html;
        }
        
        // Actualizar visibilidad de flechas
        this._updateCellModalNavigationArrows();
    },

    /**
     * Actualizar visibilidad de flechas de navegación en modal de celda
     */
    _updateCellModalNavigationArrows() {
        const state = window.cellModalPrendasState;
        if (!state) return;

        const { todasLasPrendas, currentPage, prendasPorPagina } = state;
        const totalPages = Math.ceil(todasLasPrendas.length / prendasPorPagina);
        
        // Aquí podrías agregar lógica para mostrar/ocultar botones si los tienes
        // Por ahora solo registramos el estado
        console.log(`📄 Página ${currentPage + 1} de ${totalPages}`);
    },

    /**
     * Renderizar plantilla de cotización en el modal
     */
    _renderPlantillaCotizacion(prendas, contentDiv) {
        if (!contentDiv) return;

        let html = '';
        prendas.forEach((prenda, index) => {
            html += `<strong style="font-size: 15px;">PRENDA ${prenda.numero}: ${prenda.nombre}</strong><br>
${prenda.atributos}<br>
<strong>DESCRIPCION:</strong> ${prenda.descripcion}`;
            
            // Agregar detalles si existen
            if (prenda.detalles && prenda.detalles.length > 0) {
                prenda.detalles.forEach(detalle => {
                    html += `<br>&nbsp;&nbsp;&nbsp;. <strong style="color: #666;">${detalle.tipo}:</strong> ${detalle.valor}`;
                });
            }
            
            html += `<br><strong>Tallas:</strong> <span style="color: red; font-weight: bold;">${prenda.tallas}</span>`;
            
            // Agregar línea separadora solo entre prendas (no después de la última)
            if (index < prendas.length - 1) {
                html += `<br><hr style="border: none; border-top: 2px solid #ccc; margin: 16px 0;">`;
            }
        });

        contentDiv.innerHTML = html;
        console.log(' Plantilla de cotización renderizada');
    },

    /**
     * Cerrar el modal
     */
    close() {
        console.log(' Cerrando modal de edición');

        const modal = document.getElementById(this.config.modalId);
        const overlay = document.getElementById(this.config.overlayId);

        if (modal) {
            modal.style.display = 'none';
        }

        if (overlay) {
            overlay.style.display = 'none';
        }

        this.state.isOpen = false;
        this.state.currentCell = null;
        this.state.currentColumn = null;
        this.state.currentOrderId = null;
        this.state.originalValue = null;
    },

    /**
     * Obtener etiqueta de columna
     */
    _getColumnLabel(column) {
        const labels = {
            novedades: 'Novedades',
            descripcion: 'Descripción',
            cliente: 'Cliente',
            asesor: 'Asesor',
            forma_de_pago: 'Forma de Pago',
        };

        return labels[column] || column;
    },
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    CellEditModal.initialize();
});

console.log(' CellEditModal cargado');
