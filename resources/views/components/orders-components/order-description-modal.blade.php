<!-- Modal de Descripción de Prendas (Solo descripción, con estilos del sistema) -->
<div id="orderDescriptionModal" class="order-description-modal" style="display: none;">
    <div class="modal-overlay" onclick="closeOrderDescriptionModal()"></div>
    <div class="modal-container">
        <!-- Header -->
        <div class="modal-header">
            <h2 class="modal-title">Descripción de Prendas</h2>
            <button class="close-btn" onclick="closeOrderDescriptionModal()" title="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Content -->
        <div class="modal-content">
            <div class="descripcion-container" id="modalDescripcionContent">
                -
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
            <button class="btn-close" onclick="closeOrderDescriptionModal()">Cerrar</button>
        </div>
    </div>
</div>

<style>
.order-description-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.7);
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.order-description-modal .modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    cursor: pointer;
}

.order-description-modal .modal-container {
    position: relative;
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 700px;
    width: 85%;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    animation: slideIn 0.3s ease-out;
    border: 1px solid rgba(255, 152, 0, 0.2);
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-40px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.order-description-modal .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 28px 28px 20px 28px;
    border-bottom: 2px solid #ffe4cc;
    background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
    color: white;
    border-radius: 16px 16px 0 0;
}

.order-description-modal .modal-title {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.order-description-modal .close-btn {
    background: rgba(255, 255, 255, 0.15);
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.order-description-modal .close-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
    transform: rotate(90deg);
}

.order-description-modal .modal-content {
    flex: 1 1 auto;
    overflow: hidden;
    padding: 0 !important;
    background: white;
    display: flex;
    flex-direction: column;
    width: 100% !important;
    height: 100% !important;
    min-height: 0;
    max-width: 100%;
}

.order-description-modal .descripcion-container,
#modalDescripcionContent {
    background: white;
    padding: 28px;
    border-radius: 0;
    border: none;
    border-left: 5px solid #ff9800;
    white-space: pre-wrap;
    word-break: break-word;
    word-wrap: break-word;
    overflow-wrap: break-word;
    font-size: 14px;
    line-height: 1.1;
    color: #2c3e50;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    box-shadow: none;
    width: 100% !important;
    height: 100% !important;
    box-sizing: border-box !important;
    flex: 1 1 auto !important;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex !important;
    flex-direction: column !important;
    min-width: 0 !important;
    min-height: 0 !important;
    max-width: 100% !important;
}

.order-description-modal .descripcion-container strong,
#modalDescripcionContent strong {
    font-weight: 700;
    color: #1a1a1a;
    display: block;
    margin-top: 8px;
    margin-bottom: 4px;
}

.order-description-modal .modal-footer {
    padding: 20px 28px;
    border-top: 1px solid #ffe0b2;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: #f8f9fa;
    border-radius: 0 0 16px 16px;
}

.order-description-modal .btn-close {
    background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
    color: white;
    border: none;
    padding: 12px 28px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
}

.order-description-modal .btn-close:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
}

.order-description-modal .btn-close:active {
    transform: translateY(0);
}

/* Scrollbar personalizado */
.order-description-modal .modal-content::-webkit-scrollbar {
    width: 10px;
}

.order-description-modal .modal-content::-webkit-scrollbar-track {
    background: #f0f0f0;
    border-radius: 10px;
}

.order-description-modal .modal-content::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #ff9800 0%, #f57c00 100%);
    border-radius: 10px;
}

.order-description-modal .modal-content::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #f57c00 0%, #e65100 100%);
}

/* Responsive */
@media (max-width: 768px) {
    .order-description-modal {
        background: rgba(0, 0, 0, 0.8);
    }

    .order-description-modal .modal-container {
        width: 95%;
        max-height: 90vh;
        border-radius: 12px;
    }

    .order-description-modal .modal-header {
        padding: 20px 20px 16px 20px;
        border-radius: 12px 12px 0 0;
    }

    .order-description-modal .modal-title {
        font-size: 18px;
    }

    .order-description-modal .close-btn {
        width: 40px;
        height: 40px;
        font-size: 20px;
    }

    .order-description-modal .modal-content {
        padding: 20px;
    }

    .order-description-modal .descripcion-container {
        font-size: 13px;
        padding: 16px;
        border-radius: 8px;
    }

    .order-description-modal .modal-footer {
        padding: 16px 20px;
        border-radius: 0 0 12px 12px;
    }

    .order-description-modal .btn-close {
        padding: 10px 20px;
        font-size: 13px;
    }
}
</style>

<script>
function showOrderDescriptionModal(descripcion, esCotizacion = false, prendasData = null) {
    const contentEl = document.getElementById('modalDescripcionContent');
    
    if (esCotizacion && prendasData && prendasData.length > 0) {
        // Usar plantilla de cotización
        let html = '';
        prendasData.forEach((prenda, index) => {
            html += `<strong style="font-size: 15px;">PRENDA ${prenda.numero}: ${prenda.nombre}</strong><br>
${prenda.atributos}<br>
<strong>DESCRIPCION:</strong> ${prenda.descripcion}<br>
`;
            
            // Agregar detalles si existen
            if (prenda.detalles && prenda.detalles.length > 0) {
                prenda.detalles.forEach(detalle => {
                    html += `<br>. <strong style="color: #666;">${detalle.tipo}:</strong> ${detalle.valor}<br>`;
                });
            }
            
            html += `<br><strong>Tallas:</strong> <span style="color: red; font-weight: bold;">${prenda.tallas}</span>`;
            
            // Agregar salto de línea solo entre prendas (no después de la última)
            if (index < prendasData.length - 1) {
                html += `<br><br>`;
            }
        });
        contentEl.innerHTML = html;
    } else if (descripcion && descripcion.trim()) {
        // Procesar asteriscos para convertir en negrita
        let html = descripcion
            .replace(/\*\*\*(.+?)\*\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
        contentEl.innerHTML = html;
    } else {
        contentEl.textContent = 'No hay descripción disponible';
    }
    document.getElementById('orderDescriptionModal').style.display = 'flex';
}

function closeOrderDescriptionModal() {
    document.getElementById('orderDescriptionModal').style.display = 'none';
}

// Cerrar modal al presionar ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeOrderDescriptionModal();
    }
});
</script>
