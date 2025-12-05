<!-- Modal de Visualización de Detalles del Pedido (Solo lectura, Profesional) -->
<div id="orderDetailViewModal" class="order-detail-view-modal" style="display: none;">
    <div class="modal-overlay" onclick="closeOrderDetailViewModal()"></div>
    <div class="modal-container">
        <!-- Header -->
        <div class="modal-header">
            <div class="header-left">
                <h2 class="modal-title">Detalles del Pedido</h2>
                <span class="order-number" id="modalOrderNumber">#0000</span>
            </div>
            <button class="close-btn" onclick="closeOrderDetailViewModal()" title="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Content -->
        <div class="modal-content">
            <!-- Información General -->
            <div class="info-section">
                <h3 class="section-title">Información General</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Cliente</label>
                        <p id="modalCliente">-</p>
                    </div>
                    <div class="info-item">
                        <label>Asesor</label>
                        <p id="modalAsesor">-</p>
                    </div>
                    <div class="info-item">
                        <label>Forma de Pago</label>
                        <p id="modalFormaPago">-</p>
                    </div>
                    <div class="info-item">
                        <label>Estado</label>
                        <p id="modalEstado">-</p>
                    </div>
                </div>
            </div>

            <!-- Fechas -->
            <div class="info-section">
                <h3 class="section-title">Fechas</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Fecha de Creación</label>
                        <p id="modalFechaCreacion">-</p>
                    </div>
                    <div class="info-item">
                        <label>Fecha Estimada de Entrega</label>
                        <p id="modalFechaEstimada">-</p>
                    </div>
                    <div class="info-item">
                        <label>Día de Entrega</label>
                        <p id="modalDiaEntrega">-</p>
                    </div>
                    <div class="info-item">
                        <label>Total de Días</label>
                        <p id="modalTotalDias">-</p>
                    </div>
                </div>
            </div>

            <!-- Área y Encargado -->
            <div class="info-section">
                <h3 class="section-title">Producción</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Área Actual</label>
                        <p id="modalArea">-</p>
                    </div>
                    <div class="info-item">
                        <label>Encargado de Orden</label>
                        <p id="modalEncargado">-</p>
                    </div>
                    <div class="info-item">
                        <label>Cantidad Total</label>
                        <p id="modalCantidad">-</p>
                    </div>
                </div>
            </div>

            <!-- Descripción de Prendas -->
            <div class="info-section">
                <h3 class="section-title">Descripción de Prendas</h3>
                <div class="descripcion-container" id="modalDescripcion">
                    -
                </div>
            </div>

            <!-- Novedades -->
            <div class="info-section">
                <h3 class="section-title">Novedades</h3>
                <div class="novedades-container" id="modalNovedades">
                    -
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
            <button class="btn-close" onclick="closeOrderDetailViewModal()">Cerrar</button>
        </div>
    </div>
</div>

<style>
.order-detail-view-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.order-detail-view-modal .modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    cursor: pointer;
}

.order-detail-view-modal .modal-container {
    position: relative;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    max-width: 900px;
    width: 90%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.order-detail-view-modal .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    border-bottom: 2px solid #f0f0f0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0;
}

.order-detail-view-modal .header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.order-detail-view-modal .modal-title {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

.order-detail-view-modal .order-number {
    background: rgba(255, 255, 255, 0.2);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
}

.order-detail-view-modal .close-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.order-detail-view-modal .close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.order-detail-view-modal .modal-content {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
}

.order-detail-view-modal .info-section {
    margin-bottom: 32px;
}

.order-detail-view-modal .section-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin: 0 0 16px 0;
    padding-bottom: 8px;
    border-bottom: 2px solid #667eea;
    display: inline-block;
}

.order-detail-view-modal .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.order-detail-view-modal .info-item {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.order-detail-view-modal .info-item label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.order-detail-view-modal .info-item p {
    margin: 0;
    font-size: 14px;
    color: #333;
    font-weight: 500;
    word-break: break-word;
}

.order-detail-view-modal .descripcion-container {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 8px;
    border-left: 4px solid #667eea;
    white-space: pre-wrap;
    word-break: break-word;
    font-size: 13px;
    line-height: 1.6;
    color: #333;
    max-height: 300px;
    overflow-y: auto;
}

.order-detail-view-modal .novedades-container {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 8px;
    border-left: 4px solid #667eea;
    white-space: pre-wrap;
    word-break: break-word;
    font-size: 13px;
    line-height: 1.6;
    color: #333;
    max-height: 200px;
    overflow-y: auto;
}

.order-detail-view-modal .modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: #f8f9fa;
    border-radius: 0 0 12px 12px;
}

.order-detail-view-modal .btn-close {
    background: #667eea;
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.order-detail-view-modal .btn-close:hover {
    background: #5568d3;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* Scrollbar personalizado */
.order-detail-view-modal .modal-content::-webkit-scrollbar,
.order-detail-view-modal .descripcion-container::-webkit-scrollbar,
.order-detail-view-modal .novedades-container::-webkit-scrollbar {
    width: 8px;
}

.order-detail-view-modal .modal-content::-webkit-scrollbar-track,
.order-detail-view-modal .descripcion-container::-webkit-scrollbar-track,
.order-detail-view-modal .novedades-container::-webkit-scrollbar-track {
    background: #f0f0f0;
    border-radius: 10px;
}

.order-detail-view-modal .modal-content::-webkit-scrollbar-thumb,
.order-detail-view-modal .descripcion-container::-webkit-scrollbar-thumb,
.order-detail-view-modal .novedades-container::-webkit-scrollbar-thumb {
    background: #667eea;
    border-radius: 10px;
}

.order-detail-view-modal .modal-content::-webkit-scrollbar-thumb:hover,
.order-detail-view-modal .descripcion-container::-webkit-scrollbar-thumb:hover,
.order-detail-view-modal .novedades-container::-webkit-scrollbar-thumb:hover {
    background: #5568d3;
}

/* Responsive */
@media (max-width: 768px) {
    .order-detail-view-modal .modal-container {
        width: 95%;
        max-height: 95vh;
    }

    .order-detail-view-modal .modal-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .order-detail-view-modal .header-left {
        width: 100%;
    }

    .order-detail-view-modal .close-btn {
        position: absolute;
        top: 12px;
        right: 12px;
    }

    .order-detail-view-modal .info-grid {
        grid-template-columns: 1fr;
    }

    .order-detail-view-modal .modal-content {
        padding: 16px;
    }
}
</style>

<script>
function showOrderDetailViewModal(orderData) {
    // Llenar datos generales
    document.getElementById('modalOrderNumber').textContent = '#' + String(orderData.numero_pedido).padStart(4, '0');
    document.getElementById('modalCliente').textContent = orderData.cliente || '-';
    document.getElementById('modalAsesor').textContent = orderData.asesora || '-';
    document.getElementById('modalFormaPago').textContent = orderData.forma_de_pago || '-';
    document.getElementById('modalEstado').textContent = orderData.estado || '-';
    
    // Llenar fechas
    document.getElementById('modalFechaCreacion').textContent = orderData.fecha_de_creacion_de_orden ? 
        new Date(orderData.fecha_de_creacion_de_orden).toLocaleDateString('es-ES') : '-';
    document.getElementById('modalFechaEstimada').textContent = orderData.fecha_estimada_de_entrega ? 
        new Date(orderData.fecha_estimada_de_entrega).toLocaleDateString('es-ES') : '-';
    document.getElementById('modalDiaEntrega').textContent = orderData.dia_de_entrega ? orderData.dia_de_entrega + ' días' : '-';
    document.getElementById('modalTotalDias').textContent = orderData.total_de_dias_ || '-';
    
    // Llenar producción
    document.getElementById('modalArea').textContent = orderData.area || '-';
    document.getElementById('modalEncargado').textContent = orderData.encargado_orden || '-';
    document.getElementById('modalCantidad').textContent = orderData.cantidad_total || '-';
    
    // Llenar descripción
    const descripcionEl = document.getElementById('modalDescripcion');
    if (orderData.descripcion_prendas) {
        descripcionEl.textContent = orderData.descripcion_prendas;
    } else {
        descripcionEl.textContent = '-';
    }
    
    // Llenar novedades
    const novedadesEl = document.getElementById('modalNovedades');
    if (orderData.novedades) {
        novedadesEl.textContent = orderData.novedades;
    } else {
        novedadesEl.textContent = '-';
    }
    
    // Mostrar modal
    document.getElementById('orderDetailViewModal').style.display = 'flex';
}

function closeOrderDetailViewModal() {
    document.getElementById('orderDetailViewModal').style.display = 'none';
}

// Cerrar modal al presionar ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeOrderDetailViewModal();
    }
});
</script>
