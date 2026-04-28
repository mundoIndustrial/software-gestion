@extends('layouts.base')

@section('module', 'produccion')

@section('body')
<div class="app-container">
    @if(auth()->check() && auth()->user()->hasRole('supervisor_pedidos'))
        @include('components.sidebars.sidebar-supervisor-pedidos')
    @else
        @include('layouts.sidebar')
    @endif

    <div class="main-content" id="mainContent">
        @include('components.top-nav')

        <!-- Page Content -->
        <main class="page-content">
            @yield('content')
        </main>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/top-nav.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/contador/cotizacion.js') }}"></script>
    <script src="{{ asset('js/nav-search.js') }}"></script>
    
    {{-- Echo para tiempo real en bodega --}}
    @if(request()->is('gestion-bodega/*'))
        @auth
            <!-- Laravel Echo para actualizaciones en tiempo real -->
            <script>
                // Echo ya está inicializado vía Vite en app.js
                console.log('[Layout App] Echo disponible para bodega:', !!window.EchoInstance);
            </script>
        @endauth
    @endif
    
    @if(Route::currentRouteName() === 'cotizaciones.pendientes')
        <script src="{{ asset('js/realtime-cotizaciones.js') }}?v={{ time() }}"></script>
        <script src="{{ asset('js/contador/busqueda-header.js') }}"></script>
        <script>
            // Inicializar búsqueda para cotizaciones pendientes
            function initSearchBar() {

                // Buscar el input de múltiples formas
                let searchInput = document.getElementById('searchInput');
                if (!searchInput) {
                    // Intentar buscar por clase
                    searchInput = document.querySelector('.nav-search-input');
                }
                
                if (!searchInput) {
                    // Listar todos los inputs en el nav
                    const allInputs = document.querySelectorAll('input');
                    const navInputs = document.querySelectorAll('.nav-search-wrapper input, .nav-search-container input');
                    if (navInputs.length > 0) {
                        searchInput = navInputs[0];
                    }
                }
                if (searchInput && typeof aplicarBusquedaYFiltros === 'function') {
                    searchInput.addEventListener('input', aplicarBusquedaYFiltros);
                    return true;
                } else {
                    return false;
                }
            }
            
            // Intentar múltiples veces para asegurar que el DOM esté listo
            document.addEventListener('DOMContentLoaded', function() {
                if (!initSearchBar()) {
                    // Si falla, intentar después de un pequeño delay
                    setTimeout(function() {
                        initSearchBar();
                    }, 100);
                }
            });
            
            // También intentar cuando la ventana esté completamente cargada
            window.addEventListener('load', function() {
                const searchInput = document.getElementById('searchInput');
                if (searchInput && !searchInput.hasAttribute('data-initialized')) {
                    searchInput.setAttribute('data-initialized', 'true');
                    initSearchBar();
                }
            });
        </script>
    @elseif(request()->is('recibos-costura') || request()->is('recibos-bodega'))
        <script>
            // Función de búsqueda específica para recibos de costura
            function initSearchBar() {
                let searchInput = document.getElementById('navSearchInput');
                if (!searchInput) {
                    searchInput = document.querySelector('.nav-search-input');
                }
                
                if (searchInput) {
                    // Limpiar cualquier evento anterior para evitar duplicación
                    searchInput.removeEventListener('input', performRecibosSearch);
                    searchInput.addEventListener('input', function(e) {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        performRecibosSearch(searchTerm);
                    });
                    
                    // Agregar evento para el botón de limpiar
                    const clearBtn = document.getElementById('navSearchClear');
                    if (clearBtn) {
                        clearBtn.addEventListener('click', function() {
                            searchInput.value = '';
                            performRecibosSearch(''); // Limpiar búsqueda
                            clearBtn.style.display = 'none';
                        });
                    }
                    
                    return true;
                }
                return false;
            }
            
            function performRecibosSearch(searchTerm) {
                // Solo ejecutar si estamos en la página de recibos-costura/recibos-bodega o registros
                if (!window.location.pathname.includes('recibos-costura') && !window.location.pathname.includes('recibos-bodega') && !window.location.pathname.includes('/registros')) {
                    return;
                }
                
                const tbody = document.getElementById('tablaRecibosBody');
                if (!tbody) return;
                
                const rows = tbody.querySelectorAll('tr');
                let visibleCount = 0;
                
                // Determinar columna de búsqueda según la vista
                const isRecibosCostura = window.location.pathname.includes('recibos-costura');
                const isRecibosBodega = window.location.pathname.includes('recibos-bodega');
                // Para registros: Pedido está en columna 5 (después de Área)
                // Para recibos-costura: N° Recibo está en columna 4
                const searchColumnIndex = isRecibosCostura ? 4 : 5;
                
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    let match = false;
                    
                    if (!searchTerm) {
                        match = true;
                    } else {
                        // Buscar específicamente en las columnas correspondientes
                        if (isRecibosCostura) {
                            // Para recibos-costura: buscar por N° Recibo (columna 4) y Cliente (columna 5)
                            
                            // Columna 4: N° Recibo
                            if (!match && cells.length > 4) {
                                const numeroRecibo = cells[4].textContent.trim();
                                if (numeroRecibo === searchTerm || numeroRecibo.startsWith(searchTerm)) {
                                    match = true;
                                }
                            }
                            
                            // Columna 5: Cliente
                            if (!match && cells.length > 5) {
                                const cliente = cells[5].textContent.trim();
                                if (cliente.toLowerCase().includes(searchTerm.toLowerCase())) {
                                    match = true;
                                }
                            }
                        } else if (isRecibosBodega) {
                            // Para recibos-bodega: buscar solo por N° Recibo (sin columna cliente)
                            if (!match && cells.length > 4) {
                                const numeroRecibo = cells[4].textContent.trim();
                                if (numeroRecibo === searchTerm || numeroRecibo.startsWith(searchTerm)) {
                                    match = true;
                                }
                            }
                        } else {
                            // Para registros: buscar por N° Pedido (columna 5) y Cliente (columna 6)
                            // IGNORANDO completamente la columna Área (columna 2) para alinear con header
                            
                            // Mapeo real:
                            // Header: 0-Acciones, 1-Estado, 2-Día entrega, 3-Total días, 4-Pedido, 5-Cliente
                            // Filas:  0-Acciones, 1-Estado, 2-Área(IGNORAR), 3-Día entrega, 4-Total días, 5-Pedido, 6-Cliente
                            
                            // Columna 5: Pedido (corresponde a header columna 4)
                            if (!match && cells.length > 5) {
                                const pedido = cells[5].textContent.trim();
                                if (pedido === searchTerm || pedido.startsWith(searchTerm)) {
                                    match = true;
                                }
                            }
                            
                            // Columna 6: Cliente (corresponde a header columna 5)
                            if (!match && cells.length > 6) {
                                const cliente = cells[6].textContent.trim();
                                if (cliente.toLowerCase().includes(searchTerm.toLowerCase())) {
                                    match = true;
                                }
                            }
                        }
                    }
                    
                    // IMPORTANTE: Solo modificar la visibilidad de la fila completa, no la estructura
                    if (match) {
                        row.style.display = '';
                        row.style.visibility = 'visible';
                        row.style.opacity = '1';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                        row.style.visibility = 'hidden';
                        row.style.opacity = '0';
                    }
                });
                
                const searchType = (isRecibosCostura || isRecibosBodega) ? 'Recibo' : 'Pedido';
                console.log(`[Búsqueda ${searchType}] Término: "${searchTerm}" - Filas visibles: ${visibleCount}/${rows.length}`);
            }
            
            // Función para limpiar búsqueda específica de recibos
            function clearRecibosSearch() {
                if (!window.location.pathname.includes('recibos-costura') && !window.location.pathname.includes('recibos-bodega')) {
                    return;
                }
                
                const tbody = document.getElementById('tablaRecibosBody');
                if (tbody) {
                    const rows = tbody.querySelectorAll('tr');
                    rows.forEach(row => {
                        row.style.display = '';
                    });
                    console.log('[Búsqueda Recibos] Búsqueda limpiada - Todas las filas visibles');
                }
            }
            
            // Inicializar búsqueda
            document.addEventListener('DOMContentLoaded', function() {
                if (!initSearchBar()) {
                    setTimeout(initSearchBar, 100);
                }
            });
            
            // También intentar cuando la ventana esté completamente cargada
            window.addEventListener('load', function() {
                const searchInput = document.getElementById('navSearchInput');
                if (searchInput && !searchInput.hasAttribute('data-initialized')) {
                    searchInput.setAttribute('data-initialized', 'true');
                    initSearchBar();
                }
            });
            
            // Limpiar búsqueda cuando se cambia de página
            window.addEventListener('beforeunload', function() {
                clearRecibosSearch();
            });
        </script>
    @endif
@endpush

<!-- Modal de Alerta para Novedades de Recibos -->
<div id="modalAlerta" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-9999" style="z-index: 100003;">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div id="alertaHeader" class="px-6 py-4 border-b">
            <h3 id="alertaTitulo" class="text-lg font-semibold text-white flex items-center gap-2">
                <span id="alertaIcono" class="material-symbols-rounded">info</span>
                Mensaje
            </h3>
        </div>
        <div class="px-6 py-4">
            <p id="alertaMensaje" class="text-gray-700">Mensaje del sistema</p>
        </div>
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
            <button type="button" onclick="cerrarModalAlerta()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                Entendido
            </button>
        </div>
    </div>
</div>

<!-- Función para limpiar filtros (disponible globalmente) -->
<script>
function limpiarTodosFiltros() {
    console.log('[Filtros] Limpiando todos los filtros');
    
    // Limpiar filtros activos
    window.activeFilters = {};
    
    // Limpiar búsqueda específica si estamos en recibos-costura/recibos-bodega o registros
    if (window.location.pathname.includes('recibos-costura') || window.location.pathname.includes('recibos-bodega') || window.location.pathname.includes('/registros')) {
        const searchInput = document.getElementById('navSearchInput');
        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
        }
        const clearBtn = document.getElementById('navSearchClear');
        if (clearBtn) {
            clearBtn.style.display = 'none';
        }
    }
    
    // Limpiar checkboxes del modal (si está abierto)
    const checkboxes = document.querySelectorAll('#filterOptions input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = false);
    
    // Mostrar notificación
    showFilterNotification('Todos los filtros han sido limpiados');
}

// Función para mostrar notificación de filtros
function showFilterNotification(message) {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = 'filter-notification';
    notification.textContent = message;
    
    // Estilos
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        z-index: 10000;
        font-weight: 500;
        font-size: 14px;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Mostrar notificación
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Ocultar y eliminar después de 3 segundos
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Mostrar botón de limpiar filtros cuando hay filtros activos
document.addEventListener('DOMContentLoaded', function() {
    const updateFilterButton = function() {
        const btn = document.getElementById('btnLimpiarFiltros');
        if (!btn) return;
        
        const hasActiveFilters = window.activeFilters && Object.keys(window.activeFilters).length > 0;
        
        if (hasActiveFilters) {
            btn.style.opacity = '1';
            btn.style.visibility = 'visible';
            btn.style.transform = 'scale(1)';
        } else {
            btn.style.opacity = '0';
            btn.style.visibility = 'hidden';
            btn.style.transform = 'scale(0)';
        }
    };
    
    // Actualizar cuando se aplican filtros
    const originalApplyFilters = window.applyFilters;
    if (originalApplyFilters) {
        window.applyFilters = function() {
            const result = originalApplyFilters.apply(this, arguments);
            updateFilterButton();
            return result;
        };
    }
    
    // Actualizar cuando se reinician filtros
    const originalResetFilters = window.resetFilters;
    if (originalResetFilters) {
        window.resetFilters = function() {
            const result = originalResetFilters.apply(this, arguments);
            updateFilterButton();
            return result;
        };
    }
    
    // Inicializar estado
    setTimeout(updateFilterButton, 500);
});
</script>

<!-- Meta tags para usuario actual -->
@if(auth()->check())
    <meta name="user-id" content="{{ auth()->id() }}">
@endif
