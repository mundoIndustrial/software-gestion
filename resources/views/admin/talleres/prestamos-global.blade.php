@extends('layouts.base')

@section('title', 'Préstamos Confirmados')
@section('page-title', 'Préstamos Confirmados')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/top-nav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/talleres/talleres-admin.css') }}?v={{ time() }}">
@endpush

@section('body')
    <!-- Custom Header for Prestamos -->
    <header style="position:sticky;top:0;z-index:220;background:#fff;border-bottom:1px solid #e2e8f0;padding:16px 32px;margin-left:250px;display:flex;justify-content:space-between;align-items:center;transition:margin-left 0.25s ease;gap:20px;">
        <h1 style="margin:0;font-size:24px;font-weight:700;color:#0f172a;white-space:nowrap;">{{ $tipo === 'insumos' ? 'Préstamos de Insumos' : 'Préstamos de Contramuestras' }}</h1>
        
        <div class="gooey-search-wrapper" style="flex:1;max-width:400px;">
            <input type="text" id="searchInput" class="gooey-search-input" placeholder="Buscar por recibo, taller..." 
                   value="{{ request('search', '') }}">
            <span class="material-symbols-rounded gooey-search-icon">search</span>
            <button type="button" id="clearSearchBtn" class="gooey-search-clear">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        
        <div style="white-space:nowrap;">
            <span id="totalRegistros" style="font-size:13px;color:#64748b;background:#f1f5f9;padding:6px 12px;border-radius:8px;">{{ $registros->total() }} registros</span>
        </div>
    </header>

    <style>
        body.talleres-sidebar-collapsed header[style*="margin-left:250px"] {
            margin-left: 84px;
        }
    </style>

    <!-- Sidebar Navigation -->
    <aside class="talleres-sidebar">
        <div class="sidebar-header">
            <a href="{{ route('dashboard') }}" class="logo-wrapper" aria-label="Ir al Dashboard">
                <img src="{{ asset('images/logo2.png') }}"
                     alt="Logo Mundo Industrial"
                     class="header-logo"
                     data-logo-light="{{ asset('images/logo2.png') }}"
                     data-logo-dark="{{ asset('logo.png') }}">
            </a>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Colapsar menú">
                <span class="material-symbols-rounded">chevron_left</span>
            </button>
        </div>
        <nav class="sidebar-nav">
            @if(auth()->user()->hasRole('visualizador_talleres'))
                <!-- Menú anidado para visualizador_talleres -->
                <div class="sidebar-group">
                    <button type="button" class="sidebar-item sidebar-group-toggle" id="navTalleresGroup">
                        <span class="material-symbols-rounded">factory</span>
                        <span class="nav-label">Talleres</span>
                        <span class="material-symbols-rounded expand-icon">expand_more</span>
                    </button>
                    <div class="sidebar-submenu" id="talleresSubmenu">
                        <a href="{{ route('talleres.index', ['status' => 'activos']) }}" class="sidebar-item sidebar-subitem" data-view="viewTalleres" data-status="activos" id="navTalleres">
                            <span class="nav-label">Activos</span>
                        </a>
                        <a href="{{ route('talleres.index', ['status' => 'inactivos']) }}" class="sidebar-item sidebar-subitem" data-view="viewTalleres" data-status="inactivos" id="navTalleresInactivos">
                            <span class="nav-label">Inactivos</span>
                        </a>
                        <a href="{{ route('talleres.index', ['view' => 'ordenes']) }}" class="sidebar-item sidebar-subitem" data-view="viewOrdenes" id="navOrdenes">
                            <span class="nav-label">Órdenes</span>
                        </a>
                    </div>
                </div>
                <div class="sidebar-group">
                    <button type="button" class="sidebar-item sidebar-group-toggle" id="navPrestamosGroup">
                        <span class="material-symbols-rounded">payment</span>
                        <span class="nav-label">Préstamos</span>
                        <span class="material-symbols-rounded expand-icon">expand_more</span>
                    </button>
                    <div class="sidebar-submenu collapsed" id="prestamosSubmenu">
                        <a href="{{ route('talleres.prestamos-global', ['tipo' => 'insumos']) }}" class="sidebar-item sidebar-subitem {{ $tipo === 'insumos' ? 'active' : '' }}" id="navPrestamosInsumos">
                            <span class="nav-label">Insumos</span>
                        </a>
                        <a href="{{ route('talleres.prestamos-global', ['tipo' => 'contramuestras']) }}" class="sidebar-item sidebar-subitem {{ $tipo === 'contramuestras' ? 'active' : '' }}" id="navPrestamosContramuestras">
                            <span class="nav-label">Contramuestras</span>
                        </a>
                    </div>
                </div>
                <a href="{{ route('seguimiento-lavanderia.index') }}"
                   class="sidebar-item"
                   aria-label="Ir a Lavandería">
                    <span class="material-symbols-rounded">local_laundry_service</span>
                    <span class="nav-label">Lavandería</span>
                </a>
            @endif
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-container" data-csrf-token="{{ csrf_token() }}">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Acciones</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">N° Recibo</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Costurero/Taller</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Fecha salida</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Fecha entrada</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Estado</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Novedad</th>
                    </tr>
                </thead>
                <tbody id="prestamosTableBody">
                @forelse($registros as $r)
                    <tr>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">
                            <button type="button" class="btn-ver-prestamo" data-tipo="{{ $tipo }}" data-id="{{ $r->id }}" style="background:#3b82f6;color:#fff;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;">Ver</button>
                        </td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">#{{ $r->numero_orden }}</td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">{{ $r->nombre_costurero }}</td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">{{ \Carbon\Carbon::parse($r->fecha_salida)->format('d/m/Y h:i A') }}</td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">{{ $r->fecha_entrada ? \Carbon\Carbon::parse($r->fecha_entrada)->format('d/m/Y h:i A') : '-' }}</td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">
                            @if($r->anulado)
                                <span style="background:#fee2e2;color:#dc2626;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:600;">ANULADO</span>
                            @elseif($r->confirmado_entrada)
                                <span style="background:#dcfce7;color:#16a34a;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:600;">CONFIRMADO</span>
                            @else
                                <span style="background:#fef3c7;color:#ea8c55;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:600;">PENDIENTE</span>
                            @endif
                        </td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">{{ $r->novedades ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:16px;text-align:center;color:#64748b;">Sin registros de préstamos confirmados.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:10px;" id="paginationContainer">
            {{ $registros->links('vendor.pagination.simple-clean') }}
        </div>
    </main>

    <!-- Modal para ver detalles del préstamo -->
    <div id="modal-overlay"
         style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: none;"
         onclick="closeModalOverlay()"></div>
    <div id="order-detail-modal-wrapper"
         style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
        <x-orders-components.order-detail-modal />
    </div>
    <style>
        #order-detail-modal-wrapper #order-pedido {
            transform: translateY(20px) !important;
        }
        #order-detail-modal-wrapper #btn-galeria {
            display: none !important;
        }
        #order-detail-modal-wrapper #btn-factura {
            display: none !important;
        }
        #floating-buttons-container {
            position: fixed !important;
            right: 30px !important;
            top: 50% !important;
            left: auto !important;
            transform: translateY(-50%) !important;
            z-index: 10000 !important;
        }
    </style>

    <script src="{{ asset('js/ordersjs/order-detail-modal-manager.js') }}"></script>
    <script src="{{ asset('js/modulos/talleres/prestamo-modal-handler.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearchBtn');
            const tipo = '{{ $tipo }}';
            const apiUrl = '{{ route("talleres.api.prestamos-global") }}';
            const debugPrefix = '[PrestamosGlobal]';

            console.log(debugPrefix, 'init', {
                tipo,
                currentUrl: window.location.href
            });

            document.querySelectorAll('.sidebar-item').forEach((item) => {
                item.addEventListener('click', function(event) {
                    console.log(debugPrefix, 'sidebar click', {
                        id: this.id || null,
                        tag: this.tagName,
                        href: this.getAttribute('href'),
                        dataView: this.dataset.view || null,
                        dataStatus: this.dataset.status || null,
                        defaultPrevented: event.defaultPrevented,
                        targetClasses: event.target?.className || null
                    });
                });
            });

            // Búsqueda con debounce
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                console.log(debugPrefix, 'search input', {
                    value: this.value
                });
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    performSearch(this.value);
                }, 300);
            });

            // Botón limpiar
            clearBtn.addEventListener('click', function() {
                console.log(debugPrefix, 'clear search');
                searchInput.value = '';
                performSearch('');
            });

            // Función para realizar búsqueda
            function performSearch(query) {
                console.log(debugPrefix, 'performSearch:start', {
                    query,
                    apiUrl
                });
                const params = new URLSearchParams({
                    tipo: tipo,
                    search: query
                });

                fetch(`${apiUrl}?${params}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log(debugPrefix, 'performSearch:success', {
                            query,
                            success: data.success,
                            total: data.total ?? null
                        });
                        if (data.success) {
                            document.getElementById('prestamosTableBody').innerHTML = data.html;
                            document.getElementById('totalRegistros').textContent = data.total + ' registros';
                            document.getElementById('paginationContainer').innerHTML = data.pagination_html;
                            clearBtn.style.display = query ? 'block' : 'none';
                            
                            // Re-attach button listeners and pagination
                            attachPrestamoBtnListeners();
                            attachPaginationListeners(apiUrl, tipo, query);
                        }
                    })
                    .catch(error => console.error(debugPrefix, 'performSearch:error', error));
            }

            // Función para attachar listeners a la paginación
            function attachPaginationListeners(apiUrl, tipo, query) {
                const paginationLinks = document.querySelectorAll('#paginationContainer a');
                console.log(debugPrefix, 'attachPaginationListeners', {
                    count: paginationLinks.length,
                    query
                });
                paginationLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log(debugPrefix, 'pagination click', {
                            href: this.href,
                            query
                        });
                        const page = new URL(this.href).searchParams.get('page');
                        const params = new URLSearchParams({
                            tipo: tipo,
                            search: query,
                            page: page || 1
                        });

                        fetch(`${apiUrl}?${params}`)
                            .then(response => response.json())
                            .then(data => {
                                console.log(debugPrefix, 'pagination success', {
                                    page: page || 1,
                                    success: data.success
                                });
                                if (data.success) {
                                    document.getElementById('prestamosTableBody').innerHTML = data.html;
                                    document.getElementById('paginationContainer').innerHTML = data.pagination_html;
                                    window.scrollTo(0, 0);
                                    attachPrestamoBtnListeners();
                                    attachPaginationListeners(apiUrl, tipo, query);
                                }
                            });
                    });
                });
            }

            attachPaginationListeners(apiUrl, tipo, searchInput.value || '');
        });
    </script>
