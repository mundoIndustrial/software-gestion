@extends('layouts.visualizador-logo')

@section('title', 'Pedidos Logo - Bordado/Estampado')

@section('page-title', 'Pedidos Logo')

@section('content')
<div style="padding: 1rem 1rem 2rem 1rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); min-height: calc(100vh - 60px); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <!-- Tabla de Pedidos Logo -->
    <div style="display: flex; justify-content: center;">
        <div style="width: 100%; max-width: 900px;">
            <!-- Container -->
            <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0;">
                <!-- Header -->
                <div style="
                    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
                    color: white;
                    padding: 1rem 1.5rem;
                    display: grid;
                    grid-template-columns: 100px 150px 300px 150px;
                    gap: 1rem;
                    font-weight: 700;
                    font-size: 0.9rem;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                ">
                    <div style="color: #cbd5e1;">Acciones</div>
                    <div style="color: #cbd5e1;">Número Pedido</div>
                    <div style="color: #cbd5e1;">Cliente</div>
                    <div style="color: #cbd5e1;">Fecha Creación</div>
                </div>

                <!-- Filas -->
                <div id="pedidos-body">
                    <div style="padding: 3rem 2rem; text-align: center; color: #64748b; background: #f8fafc;">
                        <div style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 1rem;">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                        <p style="margin: 0; font-size: 1rem; font-weight: 500;">Cargando pedidos...</p>
                    </div>
                </div>
            </div>
            
            <!-- Paginación -->
            <div id="paginacion-container" style="margin-top: 1.5rem; text-align: center;"></div>
        </div>
    </div>
</div>

<style>
.pagination {
    list-style: none;
    display: flex;
    gap: 0.5rem;
    padding: 0;
    margin: 0;
    justify-content: center;
    flex-wrap: wrap;
}

.page-item.active .page-link {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    border-color: #0ea5e9;
    color: white;
}

.page-link {
    color: #0ea5e9;
    text-decoration: none;
    padding: 0.5rem 0.8rem;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    transition: all 0.3s;
    font-weight: 500;
}

.page-link:hover:not(.disabled) {
    background: #f0f9ff;
    border-color: #0ea5e9;
    transform: translateY(-2px);
}

.page-item.disabled .page-link {
    color: #cbd5e1;
    cursor: not-allowed;
    opacity: 0.5;
}
</style>

<script>
// Función global para ver pedido - debe estar fuera del DOMContentLoaded
window.verPedido = function(pedidoId) {
    // Abrir el modal selector de recibos de producción
    if (typeof abrirSelectorRecibos === 'function') {
        abrirSelectorRecibos(pedidoId);
    } else {
        console.error('La función abrirSelectorRecibos no está disponible');
        alert('Error: El modal de recibos no está disponible. Por favor recargue la página.');
    }
};

document.addEventListener('DOMContentLoaded', function() {
    let paginaActual = 1;
    let searchTimeout;
    let pedidosOriginales = [];
    
    // Cargar pedidos
    cargarPedidos();
    
    // Event listeners para la barra de búsqueda
    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            
            // Mostrar/ocultar botón de limpiar
            if (searchTerm) {
                clearSearchBtn.style.display = 'block';
            } else {
                clearSearchBtn.style.display = 'none';
            }
            
            // Búsqueda en tiempo real con debounce
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                paginaActual = 1;
                cargarPedidos(searchTerm);
            }, 300);
        });
    }
    
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            this.style.display = 'none';
            paginaActual = 1;
            cargarPedidos('');
            searchInput.focus();
        });
    }
    
    function cargarPedidos(searchTerm = '') {
        const params = new URLSearchParams({
            page: paginaActual
        });
        
        if (searchTerm) {
            params.append('search', searchTerm);
        }
        
        fetch(`{{ route("visualizador-logo.pedidos-logo.data") }}?${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Guardar datos originales para filtrado local si es necesario
                    if (pedidosOriginales.length === 0 && !searchTerm) {
                        pedidosOriginales = data.pedidos.data;
                    }
                    renderizarPedidos(data.pedidos, searchTerm);
                }
            })
            .catch(error => {
                mostrarError();
            });
    }
    
    function renderizarPedidos(pedidos, searchTerm = '') {
        const tbody = document.getElementById('pedidos-body');
        
        if (pedidos.data.length === 0) {
            tbody.innerHTML = `
                <div style="padding: 3rem 2rem; text-align: center; color: #64748b; background: #f8fafc;">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem; display: block;"></i>
                    <p style="margin: 0; font-size: 1rem; font-weight: 500;">
                        ${searchTerm ? 'No se encontraron pedidos para tu búsqueda' : 'No se encontraron pedidos'}
                    </p>
                </div>
            `;
            return;
        }
        
        tbody.innerHTML = pedidos.data.map((pedido, index) => {
            // Extraer nombre del cliente
            let nombreCliente = pedido.cliente_nombre || pedido.cliente || '-';
            
            // Resaltar término de búsqueda si existe
            let numeroPedido = pedido.numero_pedido || '-';
            if (searchTerm) {
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                numeroPedido = numeroPedido.replace(regex, '<mark style="background: #fef3c7; color: #92400e; padding: 2px 4px; border-radius: 3px;">$1</mark>');
                nombreCliente = nombreCliente.replace(regex, '<mark style="background: #fef3c7; color: #92400e; padding: 2px 4px; border-radius: 3px;">$1</mark>');
            }
            
            return `
                <div style="
                    display: grid;
                    grid-template-columns: 100px 150px 300px 150px;
                    gap: 1rem;
                    padding: 1rem 1.5rem;
                    align-items: center;
                    transition: all 0.3s ease;
                    background: white;
                    border-bottom: 1px solid #e2e8f0;
                " onmouseover="this.style.background='#f8fafc'; this.style.boxShadow='inset 0 0 0 1px #e2e8f0'" onmouseout="this.style.background='white'; this.style.boxShadow='none'">
                    
                    <div style="display: flex; justify-content: center; gap: 0.5rem;">
                        <button 
                           onclick="verPedido(${pedido.id})"
                           title="Ver detalles"
                           style="
                               background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
                               color: white;
                               border: none;
                               padding: 0.6rem;
                               border-radius: 8px;
                               cursor: pointer;
                               font-size: 1rem;
                               transition: all 0.3s ease;
                               display: flex;
                               align-items: center;
                               justify-content: center;
                               width: 40px;
                               height: 40px;
                               box-shadow: 0 2px 8px rgba(14, 165, 233, 0.2);
                           " onmouseover="this.style.transform='translateY(-3px) scale(1.1)'; this.style.boxShadow='0 6px 16px rgba(14, 165, 233, 0.35)'" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 2px 8px rgba(14, 165, 233, 0.2)'">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div style="font-weight: 700; color: #0ea5e9; font-size: 0.95rem;">${numeroPedido}</div>
                    <div style="color: #334155; font-size: 0.95rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${nombreCliente}</div>
                    <div style="color: #64748b; font-size: 0.95rem;">${formatearFecha(pedido.created_at)}</div>
                </div>
            `;
        }).join('');
        
        renderizarPaginacion(pedidos);
    }
    
    function formatearFecha(fecha) {
        if (!fecha) return '-';
        const date = new Date(fecha);
        return date.toLocaleDateString('es-ES', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    }
    
    function renderizarPaginacion(pedidos) {
        const container = document.getElementById('paginacion-container');
        
        if (pedidos.last_page <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = '<nav><ul class="pagination">';
        
        // Anterior
        html += `<li class="page-item ${pedidos.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${pedidos.current_page - 1}"><i class="fas fa-chevron-left" style="margin-right: 0.3rem;"></i>Anterior</a>
        </li>`;
        
        // Páginas
        for (let i = 1; i <= pedidos.last_page; i++) {
            if (i === 1 || i === pedidos.last_page || (i >= pedidos.current_page - 2 && i <= pedidos.current_page + 2)) {
                html += `<li class="page-item ${i === pedidos.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
            } else if (i === pedidos.current_page - 3 || i === pedidos.current_page + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        // Siguiente
        html += `<li class="page-item ${pedidos.current_page === pedidos.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${pedidos.current_page + 1}">Siguiente <i class="fas fa-chevron-right" style="margin-left: 0.3rem;"></i></a>
        </li>`;
        
        html += '</ul></nav>';
        container.innerHTML = html;
        
        // Event listeners para paginación
        container.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (page && page !== paginaActual) {
                    paginaActual = page;
                    const searchTerm = document.getElementById('search-input').value.trim();
                    cargarPedidos(searchTerm);
                }
            });
        });
    }
    
    function mostrarError() {
        const tbody = document.getElementById('pedidos-body');
        tbody.innerHTML = `
            <div style="padding: 3rem 2rem; text-align: center; color: #64748b; background: #f8fafc;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ef4444; margin-bottom: 1rem; display: block;"></i>
                <p style="margin: 0; font-size: 1rem; font-weight: 500;">Error al cargar los pedidos</p>
            </div>
        `;
    }
});
</script>

<!-- MODALES DE RECIBOS DE PRODUCCIÓN -->
@include('components.modals.recibos-process-selector')
@include('components.modals.recibos-intermediate-modal')
@include('components.modals.recibo-dinamico-modal')

<!-- MODAL WRAPPER PARA RECIBOS -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 60%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

<!-- MÓDULO DE RECIBOS -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}?v={{ time() }}"></script>

@endsection
