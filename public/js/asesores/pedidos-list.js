// ========================================
// LISTA DE PEDIDOS - FILTROS Y ACCIONES
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterEstado = document.getElementById('filterEstado');
    const filterArea = document.getElementById('filterArea');
    const deleteButtons = document.querySelectorAll('.btn-delete');
    const btnEliminar = document.getElementById('btnEliminar');

    // Búsqueda
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                aplicarFiltros();
            }, 500);
        });
    }

    // Filtro de estado
    if (filterEstado) {
        filterEstado.addEventListener('change', aplicarFiltros);
    }

    // Filtro de área
    if (filterArea) {
        filterArea.addEventListener('change', aplicarFiltros);
    }

    // Botones de eliminar en la lista
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const pedido = this.dataset.pedido;
            const cliente = this.dataset.cliente;
            confirmarEliminar(pedido, cliente);
        });
    });

    // Botón eliminar en vista de detalles
    if (btnEliminar) {
        btnEliminar.addEventListener('click', function() {
            const pedido = this.dataset.pedido;
            confirmarEliminar(pedido, 'este pedido');
        });
    }
});

// ========================================
// APLICAR FILTROS
// ========================================
function aplicarFiltros() {
    const search = document.getElementById('searchInput')?.value || '';
    const estado = document.getElementById('filterEstado')?.value || '';
    const area = document.getElementById('filterArea')?.value || '';

    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (estado) params.append('estado', estado);
    if (area) params.append('area', area);

    const url = `/asesores/pedidos${params.toString() ? '?' + params.toString() : ''}`;
    window.location.href = url;
}

// ========================================
// CONFIRMAR ELIMINAR
// ========================================
function confirmarEliminar(pedido, cliente) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content modal-delete">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-exclamation-triangle"></i>
                    Confirmar Eliminación
                </h2>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar el pedido <strong>#${pedido}</strong> de <strong>${cliente}</strong>?</p>
                <p class="warning-text">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="this.closest('.modal-overlay').remove()">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button class="btn btn-danger" onclick="eliminarPedido('${pedido}')">
                    <i class="fas fa-trash"></i>
                    Eliminar
                </button>
            </div>
        </div>
    `;

    // Agregar estilos si no existen
    if (!document.getElementById('modal-styles')) {
        const style = document.createElement('style');
        style.id = 'modal-styles';
        style.textContent = `
            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                animation: fadeIn 0.2s ease;
            }
            .modal-content {
                background: var(--bg-card);
                border-radius: 12px;
                max-width: 500px;
                width: 90%;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                animation: slideUp 0.3s ease;
            }
            .modal-header {
                padding: 1.5rem;
                border-bottom: 1px solid var(--border-color);
            }
            .modal-header h2 {
                margin: 0;
                font-size: 1.25rem;
                color: var(--text-primary);
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            .modal-delete .modal-header h2 i {
                color: #ef4444;
            }
            .modal-body {
                padding: 1.5rem;
            }
            .modal-body p {
                margin: 0 0 1rem 0;
                color: var(--text-primary);
            }
            .modal-body .warning-text {
                color: #ef4444;
                font-size: 0.875rem;
                font-weight: 500;
            }
            .modal-footer {
                padding: 1.5rem;
                border-top: 1px solid var(--border-color);
                display: flex;
                justify-content: flex-end;
                gap: 0.75rem;
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slideUp {
                from {
                    transform: translateY(20px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(modal);

    // Cerrar al hacer clic fuera
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// ========================================
// ELIMINAR PEDIDO
// ========================================
async function eliminarPedido(pedido) {
    try {
        const response = await fetch(`/asesores/pedidos/${pedido}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            mostrarToast('Pedido eliminado exitosamente', 'success');
            
            // Cerrar modal
            document.querySelector('.modal-overlay')?.remove();
            
            // Redirigir después de 1 segundo
            setTimeout(() => {
                window.location.href = '/asesores/pedidos';
            }, 1000);
        } else {
            mostrarToast(result.message || 'Error al eliminar el pedido', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarToast('Error al eliminar el pedido', 'error');
    }
}

// ========================================
// MOSTRAR TOAST
// ========================================
function mostrarToast(mensaje, tipo = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    toast.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${mensaje}</span>
    `;

    if (!document.getElementById('toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            .toast {
                position: fixed;
                top: 2rem;
                right: 2rem;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                color: white;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                z-index: 10000;
                animation: slideIn 0.3s ease;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
            .toast-success {
                background: linear-gradient(135deg, #10b981, #059669);
            }
            .toast-error {
                background: linear-gradient(135deg, #ef4444, #dc2626);
            }
            .toast-info {
                background: linear-gradient(135deg, #3b82f6, #2563eb);
            }
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

