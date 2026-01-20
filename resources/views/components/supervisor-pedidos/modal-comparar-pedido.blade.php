<style>
.modal-comparar-pedido-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9995;
    display: none;
    align-items: center;
    justify-content: center;
}

.modal-comparar-pedido-overlay.active {
    display: flex;
}

.modal-comparar-pedido-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    width: 95%;
    max-width: 1400px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 0;
    z-index: 9996;
}

.modal-comparar-header {
    background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%);
    color: white;
    padding: 24px;
    border-radius: 16px 16px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-comparar-header h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
}

.modal-comparar-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.modal-comparar-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.modal-comparar-content {
    padding: 32px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
}

.comparar-column {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.comparar-header-card {
    background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
}

.comparar-header-card h3 {
    margin: 0 0 12px 0;
    font-size: 18px;
    font-weight: 600;
}

.comparar-header-card .numero {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 16px;
}

.comparar-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    font-size: 14px;
}

.comparar-info-item {
    background: #f3f4f6;
    padding: 12px;
    border-radius: 8px;
}

.comparar-info-item label {
    display: block;
    color: #6b7280;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.comparar-info-item strong {
    display: block;
    color: #1f2937;
    font-size: 14px;
    word-break: break-word;
}

.comparar-prendas-section {
    flex: 1;
}

.comparar-prendas-title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.comparar-prendas-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-height: 500px;
    overflow-y: auto;
    padding-right: 8px;
}

.comparar-prendas-list::-webkit-scrollbar {
    width: 6px;
}

.comparar-prendas-list::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 3px;
}

.comparar-prendas-list::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

.comparar-prendas-list::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

.prenda-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 16px;
    transition: all 0.3s ease;
}

.prenda-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

.prenda-nombre {
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 12px;
    font-size: 14px;
}

.prenda-descripcion {
    font-size: 12px;
    color: #4b5563;
    line-height: 1.5;
    margin-bottom: 12px;
    max-height: 120px;
    overflow-y: auto;
    padding-right: 4px;
}

.prenda-descripcion::-webkit-scrollbar {
    width: 4px;
}

.prenda-descripcion::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 2px;
}

.prenda-tallas {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 10px;
    font-size: 12px;
}

.prenda-tallas-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
    display: block;
}

.tallas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));
    gap: 6px;
}

.talla-badge {
    background: #dbeafe;
    color: #1e40af;
    padding: 6px 8px;
    border-radius: 4px;
    text-align: center;
    font-weight: 600;
    font-size: 11px;
}

.talla-badge.con-cantidad {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.talla-badge.con-cantidad .talla {
    font-size: 10px;
}

.talla-badge.con-cantidad .cantidad {
    font-size: 9px;
    opacity: 0.8;
}

@media (max-width: 1024px) {
    .modal-comparar-content {
        grid-template-columns: 1fr;
        gap: 24px;
    }
}

@media (max-width: 768px) {
    .modal-comparar-container {
        width: 98%;
        max-height: 95vh;
    }

    .modal-comparar-header {
        padding: 16px;
    }

    .modal-comparar-header h2 {
        font-size: 18px;
    }

    .modal-comparar-content {
        padding: 16px;
        gap: 16px;
    }

    .comparar-info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div id="modal-comparar-pedido-overlay" class="modal-comparar-pedido-overlay">
    <div class="modal-comparar-pedido-container">
        <div class="modal-comparar-header">
            <h2>Comparar Pedido y Cotización</h2>
            <button class="modal-comparar-close" onclick="cerrarModalComparar()">&times;</button>
        </div>

        <div class="modal-comparar-content" id="modal-comparar-contenido">
            <!-- Se llena dinámicamente con JavaScript -->
        </div>
    </div>
</div>

<script>
function abrirModalComparar(ordenId) {
    const overlay = document.getElementById('modal-comparar-pedido-overlay');
    const contenido = document.getElementById('modal-comparar-contenido');

    fetch(`/supervisor-pedidos/${ordenId}/comparar`)
        .then(response => {
            if (!response.ok) throw new Error('Error al cargar datos');
            return response.json();
        })
        .then(data => {
            renderizarComparacion(data, contenido);
            overlay.classList.add('active');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar la comparación');
        });
}

function cerrarModalComparar() {
    const overlay = document.getElementById('modal-comparar-pedido-overlay');
    overlay.classList.remove('active');
}

function renderizarComparacion(data, contenedor) {
    const { pedido, cotizacion } = data;

    let html = `
        <!-- COLUMNA PEDIDO -->
        <div class="comparar-column">
            <div class="comparar-header-card">
                <h3>Pedido</h3>
                <div class="numero">#${pedido.numero}</div>
                <div class="comparar-info-grid">
                    <div class="comparar-info-item">
                        <label>Cliente</label>
                        <strong>${pedido.cliente}</strong>
                    </div>
                    <div class="comparar-info-item">
                        <label>Asesora</label>
                        <strong>${pedido.asesora}</strong>
                    </div>
                    <div class="comparar-info-item">
                        <label>Estado</label>
                        <strong>${pedido.estado}</strong>
                    </div>
                    <div class="comparar-info-item">
                        <label>Fecha</label>
                        <strong>${new Date(pedido.fecha).toLocaleDateString('es-CO')}</strong>
                    </div>
                </div>
            </div>

            <div class="comparar-prendas-section">
                <div class="comparar-prendas-title">
                    <span> Prendas del Pedido</span>
                    <span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">${pedido.prendas.length}</span>
                </div>
                <div class="comparar-prendas-list">
                    ${pedido.prendas.map(prenda => `
                        <div class="prenda-card">
                            <div class="prenda-nombre">${prenda.nombre}</div>
                            <div class="prenda-descripcion">${formatearDescripcion(prenda.descripcion)}</div>
                            <div class="prenda-tallas">
                                <span class="prenda-tallas-title">Tallas (con cantidades):</span>
                                <div class="tallas-grid">
                                    ${renderizarTallasPedido(prenda.tallas)}
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>

        <!-- COLUMNA COTIZACIÓN -->
        <div class="comparar-column">
            <div class="comparar-header-card">
                <h3>Cotización</h3>
                <div class="numero">${cotizacion ? cotizacion.numero : 'Sin cotización'}</div>
                ${cotizacion ? `
                    <div class="comparar-info-grid">
                        <div class="comparar-info-item">
                            <label>Cliente</label>
                            <strong>${cotizacion.cliente}</strong>
                        </div>
                        <div class="comparar-info-item">
                            <label>Asesora</label>
                            <strong>${cotizacion.asesora}</strong>
                        </div>
                        <div class="comparar-info-item">
                            <label>Estado</label>
                            <strong>${cotizacion.estado}</strong>
                        </div>
                        <div class="comparar-info-item">
                            <label>Fecha</label>
                            <strong>${new Date(cotizacion.fecha).toLocaleDateString('es-CO')}</strong>
                        </div>
                    </div>
                ` : '<div style="padding: 20px; text-align: center; color: #6b7280;">No hay cotización asociada</div>'}
            </div>

            ${cotizacion ? `
                <div class="comparar-prendas-section">
                    <div class="comparar-prendas-title">
                        <span> Prendas de la Cotización</span>
                        <span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">${cotizacion.prendas.length}</span>
                    </div>
                    <div class="comparar-prendas-list">
                        ${cotizacion.prendas.map(prenda => `
                            <div class="prenda-card">
                                <div class="prenda-nombre">${prenda.nombre}</div>
                                <div class="prenda-descripcion">${formatearDescripcion(prenda.descripcion)}</div>
                                <div class="prenda-tallas">
                                    <span class="prenda-tallas-title">Tallas:</span>
                                    <div class="tallas-grid">
                                        ${renderizarTallasCotizacion(prenda.tallas)}
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}
        </div>
    `;

    contenedor.innerHTML = html;
}

function formatearDescripcion(descripcion) {
    if (!descripcion || descripcion === 'N/A') {
        return '<em style="color: #999;">Sin descripción</em>';
    }

    return descripcion
        .split('\n')
        .map(linea => {
            const trimmed = linea.trim();
            if (!trimmed) return '';
            if (trimmed.startsWith('PRENDA')) {
                return `<strong style="display: block; margin-top: 6px;">${trimmed}</strong>`;
            }
            if (trimmed.startsWith('***') || trimmed.includes(':')) {
                return `<strong style="display: block; margin-top: 4px; font-size: 11px;">${trimmed}</strong>`;
            }
            if (trimmed.startsWith('•') || trimmed.startsWith('-')) {
                return `<div style="margin-left: 12px; font-size: 11px;">${trimmed}</div>`;
            }
            return `<div style="font-size: 11px;">${trimmed}</div>`;
        })
        .join('');
}

function renderizarTallasPedido(tallas) {
    if (!tallas || Object.keys(tallas).length === 0) {
        return '<div style="color: #999; font-size: 11px;">Sin tallas</div>';
    }

    return Object.entries(tallas)
        .map(([talla, cantidad]) => `
            <div class="talla-badge con-cantidad">
                <span class="talla">${talla}</span>
                <span class="cantidad">${cantidad}</span>
            </div>
        `)
        .join('');
}

function renderizarTallasCotizacion(tallas) {
    if (!tallas || tallas.length === 0) {
        return '<div style="color: #999; font-size: 11px;">Sin tallas</div>';
    }

    return tallas
        .map(talla => `<div class="talla-badge">${talla}</div>`)
        .join('');
}

// Cerrar modal al hacer clic en el overlay
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('modal-comparar-pedido-overlay');
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalComparar();
            }
        });
    }
});
</script>
