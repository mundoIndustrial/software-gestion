@extends('layouts.visualizador-pedidos')

@section('title', 'Visualizador de Pedidos')

@section('page-title', 'Visualizador de Pedidos')

@section('content')
<div style="padding: 1rem 1rem 2rem 1rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); min-height: calc(100vh - 60px); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; width: 100%; display: flex; justify-content: center;">
    <!-- Tabla de Pedidos -->
    <div style="width: auto; max-width: 90%; transform: scale(0.9); transform-origin: top center;">
        <!-- Container -->
        <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0; position: relative; display: flex; flex-direction: column; height: fit-content;">
            <!-- Header -->
            <div style="
                background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
                color: white;
                padding: 0.75rem 1rem;
                display: grid;
                grid-template-columns: 60px minmax(80px, auto) minmax(200px, auto) minmax(150px, auto) minmax(120px, auto);
                gap: 2rem;
                font-weight: 700;
                font-size: 0.85rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                flex-shrink: 0;
            ">
                <div style="text-align: center; color: #cbd5e1;">Acciones</div>
                <div style="color: #cbd5e1;">Número</div>
                <div style="color: #cbd5e1;">Cliente</div>
                <div style="color: #cbd5e1;">Asesora</div>
                <div style="color: #cbd5e1;">Fecha</div>
            </div>

            <!-- Filas con altura fija para 7 filas -->
            <div id="pedidos-body" style="overflow-y: auto; max-height: calc(7 * 60px); flex-grow: 1;">
                <div style="padding: 3rem 2rem; text-align: center; color: #64748b; background: #f8fafc;">
                    <div style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 1rem;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <p style="margin: 0; font-size: 1rem; font-weight: 500;">Cargando pedidos...</p>
                </div>
            </div>
        </div>
        
        <!-- Paginación -->
        <div id="paginacion-container" style="margin-top: 1.5rem; text-align: center; display: block; visibility: visible;"></div>
    </div>
</div>

<style>
.badge-estado {
    padding: 0.4rem 0.8rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 6px;
    display: inline-block;
}

#paginacion-container {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.pagination {
    list-style: none;
    display: flex;
    gap: 0.5rem;
    padding: 0;
    margin: 0;
    justify-content: center;
    flex-wrap: wrap;
}

.page-item {
    display: inline-block;
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
    display: inline-block;
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

/* Altura fija para las filas */
#pedidos-body > div {
    height: 60px;
    display: flex;
    align-items: center;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let paginaActual = 1;
    let searchTimeout;
    
    // Obtener parámetros de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const pageFromUrl = parseInt(urlParams.get('page')) || 1;
    const searchFromUrl = urlParams.get('busqueda') || '';
    
    paginaActual = pageFromUrl;
    
    // Cargar pedidos
    cargarPedidos(searchFromUrl);
    
    // Event listeners para la barra de búsqueda del layout
    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search');
    
    if (searchInput) {
        searchInput.value = searchFromUrl;
        
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
            page: paginaActual,
            perPage: 20
        });
        
        if (searchTerm) {
            params.append('busqueda', searchTerm);
        }
        
        // Actualizar la URL sin recargar la página
        const newUrl = `{{ route("visualizador-pedidos.index") }}?${params.toString()}`;
        window.history.pushState({ page: paginaActual, search: searchTerm }, '', newUrl);
        
        console.log('Cargando pedidos con params:', params.toString());
        
        fetch(`{{ route("visualizador-pedidos.data") }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta recibida:', data);
                if (data.success) {
                    renderizarPedidos(data.ordenes, searchTerm);
                } else {
                    mostrarError();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError();
            });
    }
    
    function renderizarPedidos(pedidos, searchTerm = '') {
        const tbody = document.getElementById('pedidos-body');
        
        console.log('Renderizando pedidos:', pedidos);
        
        if (!pedidos.data || pedidos.data.length === 0) {
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
            // Extraer información del pedido
            let numeroPedido = String(pedido.numero_pedido || pedido.id || '-');
            let cliente = String(pedido.cliente || '-');
            let asesora = String(pedido.asesora?.name || pedido.asesor?.name || pedido.asesor_nombre || '-');
            
            // Resaltar término de búsqueda si existe
            if (searchTerm) {
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                numeroPedido = numeroPedido.replace(regex, '<mark style="background: #fef3c7; color: #92400e; padding: 2px 4px; border-radius: 3px;">$1</mark>');
                cliente = cliente.replace(regex, '<mark style="background: #fef3c7; color: #92400e; padding: 2px 4px; border-radius: 3px;">$1</mark>');
            }
            
            return `
                <div style="
                    display: grid;
                    grid-template-columns: 60px minmax(80px, auto) minmax(200px, auto) minmax(150px, auto) minmax(120px, auto);
                    gap: 2rem;
                    padding: 0.75rem 1rem;
                    align-items: center;
                    transition: all 0.3s ease;
                    background: white;
                    border-bottom: 1px solid #e2e8f0;
                    height: 60px;
                " onmouseover="this.style.background='#f8fafc'; this.style.boxShadow='inset 0 0 0 1px #e2e8f0'" onmouseout="this.style.background='white'; this.style.boxShadow='none'">
                    
                    <div style="display: flex; justify-content: center; gap: 0.5rem; position: relative;">
                        <button 
                           class="btn-accion btn-accion--ver btn-ver-dropdown"
                           data-menu-id="menu-ver-${pedido.id}"
                           data-pedido="${pedido.numero_pedido}"
                           data-pedido-id="${pedido.id}"
                           title="Ver Opciones"
                           style="
                               position: relative;
                               overflow: visible;
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
                            <span class="btn-ver-bodega-badge" data-bodega-button-badge="" style="display: none; position: absolute; top: -7px; right: -7px; min-width: 18px; height: 18px; padding: 0px 5px; border-radius: 999px; background: rgb(220, 38, 38); color: rgb(255, 255, 255); font-size: 10px; font-weight: 700; line-height: 18px; text-align: center; box-shadow: rgba(0, 0, 0, 0.25) 0px 2px 6px;">0</span>
                        </button>
                        <div id="menu-ver-${pedido.id}" class="dropdown-menu" style="display: none; position: absolute; top: 100%; right: 0; background: white; border: 1px solid #d1d5db; border-radius: 12px; box-shadow: rgba(15, 23, 42, 0.22) 0px 14px 30px; z-index: 1000; min-width: 220px; margin-top: 0.5rem; overflow: hidden;">
                            <button type="button" onclick="openPedidoModal(${pedido.id})" style="width: 100%; border: none; background: white; padding: 12px 14px; text-align: left; cursor: pointer; color: #111827; font-size: 15px; font-weight: 600; line-height: 1.2; transition: background-color 0.15s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='white'">
                                <span style="display: inline-flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-eye" style="width: 16px; text-align: center; color: #374151;"></i>
                                    <span>Ver Pedido</span>
                                </span>
                            </button>
                        </div>
                    </div>
                    
                    <div style="font-weight: 700; color: #0ea5e9; font-size: 0.95rem; white-space: nowrap;">${numeroPedido}</div>
                    <div style="color: #334155; font-size: 0.95rem; font-weight: 500; white-space: nowrap;">${cliente}</div>
                    <div style="color: #64748b; font-size: 0.95rem; white-space: nowrap;">${asesora}</div>
                    <div style="color: #64748b; font-size: 0.95rem; white-space: nowrap;">${formatearFecha(pedido.fecha_pedido || pedido.created_at)}</div>
                </div>
            `;
        }).join('');
        
        console.log('Llamando a renderizarPaginacion con:', pedidos);
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
        
        console.log('renderizarPaginacion - Container:', container);
        console.log('renderizarPaginacion - Pedidos:', pedidos);
        
        if (!container) {
            console.error('No se encontró el contenedor de paginación');
            return;
        }
        
        if (!pedidos || !pedidos.last_page || pedidos.last_page <= 1) {
            console.log('No hay múltiples páginas, ocultando paginación');
            container.innerHTML = '';
            return;
        }
        
        console.log('Renderizando paginación para página', pedidos.current_page, 'de', pedidos.last_page);
        
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
        
        console.log('HTML de paginación generado:', html);
        
        container.innerHTML = html;
        container.style.display = 'block';
        container.style.visibility = 'visible';
        container.style.opacity = '1';
        
        // Event listeners para paginación
        container.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                console.log('Click en página:', page, 'Página actual:', paginaActual);
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

// Funciones para el modal de pedido
function openPedidoModal(pedidoId) {
    // Usar la misma función que supervisor-pedidos
    if (typeof window.verFacturaDelPedido === 'function') {
        window.verFacturaDelPedido(String(pedidoId), Number(pedidoId));
    } else {
        alert('El modal de factura no está disponible. Por favor recarga la página.');
    }
}

function closePedidoModal() {
    // Usar la función de cierre del modal de supervisor-pedidos
    if (typeof window.closeOrderDetailModal === 'function') {
        window.closeOrderDetailModal();
    }
}

// Manejar dropdown menus
document.addEventListener('click', function(e) {
    // Cerrar todos los dropdowns si se hace clic fuera
    if (!e.target.closest('.btn-ver-dropdown') && !e.target.closest('.dropdown-menu')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.style.display = 'none';
        });
    }
    
    // Abrir/cerrar dropdown al hacer clic en el botón
    if (e.target.closest('.btn-ver-dropdown')) {
        const btn = e.target.closest('.btn-ver-dropdown');
        const menuId = btn.getAttribute('data-menu-id');
        const menu = document.getElementById(menuId);
        
        if (menu) {
            // Cerrar otros menus
            document.querySelectorAll('.dropdown-menu').forEach(m => {
                if (m.id !== menuId) {
                    m.style.display = 'none';
                }
            });
            
            // Toggle el menu actual
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }
    }
});
</script>

@endsection

<!-- Usar los mismos elementos del modal de supervisor-pedidos -->
<!-- Modal Overlay y Wrapper para Detalles de Orden -->
<div id="modal-overlay" aria-label="Cerrar modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeOrderDetailModal()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 90vw; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none; border-radius: 8px;">
    <x-orders-components.order-detail-modal />
</div>

@push('scripts')
    <!-- Modal de Selector de Recibos -->
    @include('components.modals.recibos-process-selector')
    
    <!-- Módulo de Recibos - Carga la función openOrderDetailModalWithProcess -->
    <script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}?v={{ filemtime(public_path('js/modulos/pedidos-recibos/loader.js')) }}"></script>
    
    <!-- Scripts para Vista de Factura desde Lista - Lazy Loading -->
    <script defer src="{{ asset('js/modulos/invoice/InvoiceLazyLoader.js') }}?v={{ filemtime(public_path('js/modulos/invoice/InvoiceLazyLoader.js')) }}"></script>
    <script defer src="{{ asset('js/modulos/invoice/InvoiceRenderer.js') }}?v={{ filemtime(public_path('js/modulos/invoice/InvoiceRenderer.js')) }}"></script>
    <script defer src="{{ asset('js/modulos/invoice/ModalManager.js') }}?v={{ filemtime(public_path('js/modulos/invoice/ModalManager.js')) }}"></script>
    <script defer src="{{ asset('js/modulos/invoice/FormDataCaptureService.js') }}?v={{ filemtime(public_path('js/modulos/invoice/FormDataCaptureService.js')) }}"></script>
    <script defer src="{{ asset('js/modulos/invoice/ImageGalleryManager.js') }}?v={{ filemtime(public_path('js/modulos/invoice/ImageGalleryManager.js')) }}"></script>
    <script defer src="{{ asset('js/ordersjs/order-detail-modal-manager.js') }}"></script>
@endpush
