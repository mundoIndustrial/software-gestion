@extends('layouts.base')

@section('title', 'Seguimiento de Lavanderia')
@section('page-title', 'Seguimiento de Lavanderia')
@section('module', 'produccion')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/top-nav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/talleres/talleres-admin.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/talleres/talleres-spa.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/seguimiento-lavanderia.css') }}?v={{ time() }}">
@endpush

@section('body')
    @include('components.top-nav')

    <aside class="talleres-sidebar">
        <div class="sidebar-header">
            <a href="{{ route('dashboard') }}" class="logo-wrapper" aria-label="Ir al Dashboard">
                <img src="{{ asset('images/logo2.png') }}"
                     alt="Logo Mundo Industrial"
                     class="header-logo"
                     data-logo-light="{{ asset('images/logo2.png') }}"
                     data-logo-dark="{{ asset('logo.png') }}">
            </a>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Colapsar menu">
                <span class="material-symbols-rounded">chevron_left</span>
            </button>
        </div>

        <nav class="sidebar-nav">
            <button class="sidebar-item active" data-view="viewOrdenes" id="navOrdenes">
                <span class="material-symbols-rounded">assignment</span>
                <span class="nav-label">Ordenes</span>
            </button>
            <button class="sidebar-item" data-view="viewHistorialMovimientos" id="navHistorialMovimientos">
                <span class="material-symbols-rounded">history</span>
                <span class="nav-label">Historial de Movimientos</span>
            </button>
        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('dashboard') }}" class="btn-volver">
                <span class="material-symbols-rounded">arrow_back</span>
                <span class="nav-label">Volver</span>
            </a>
        </div>
    </aside>

    <main class="main-container seguimiento-lavanderia-main" data-api-ordenes="{{ route('seguimiento-lavanderia.api.ordenes') }}">
        <div id="viewOrdenes" class="view-container">
            <div class="page-header">
                <div>
                    <h1 class="section-title" style="margin: 0;">Órdenes de Lavandería</h1>
                    <p class="section-subtitle" style="margin: 6px 0 0 0;"></p>
                </div>

                <div class="page-actions">
                    <form class="gooey-search-wrapper" id="ordenesSearchForm">
                        <span class="material-symbols-rounded gooey-search-icon">search</span>
                        <input
                            type="text"
                            id="ordenesSearchInput"
                            class="gooey-search-input"
                            placeholder="Buscar por recibo, cliente o prenda..."
                        >
                        <button
                            class="gooey-search-clear"
                            id="ordenesSearchClear"
                            type="button"
                            style="display: none;"
                        >
                            <span class="material-symbols-rounded">close</span>
                        </button>
                    </form>
                </div>
            </div>

           <div id="ordenesLoadingState" class="text-center py-4" style="display: none;">
    <div style="color: #64748b; font-size: 14px;">Cargando órdenes...</div>
</div>

<div class="table-responsive">
    <table class="table-talleres" style="width: 100%;">
        <thead>
            <tr>
                <th>#Recibo - Tipo</th>
                <th>Cliente</th>
                <th>Prenda</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="ordenesTableBody">
            <tr>
                <td colspan="4" style="text-align: center; padding: 24px; color: #94a3b8;">
                    No hay órdenes para mostrar
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div id="ordenesEmptyState" style="display: none; text-align: center; padding: 24px; color: #94a3b8;">
    No se encontraron órdenes
</div>

<div id="ordenesPagination" class="mt-4" style="display: flex; justify-content: center; gap: 8px;"></div> 
        </div>

        <div id="viewHistorialMovimientos" class="view-container" style="display: none;">
            <div class="page-header">
                <div>
                    <h1 class="section-title" style="margin: 0;">Historial de Movimientos</h1>
                    <p class="section-subtitle" style="margin: 6px 0 0 0;"></p>
                </div>
            </div>

            <!-- Tabs de Entrada/Salida + Búsqueda -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; gap: 24px;">
                <div class="historial-tabs" style="display: flex; gap: 0; flex: 1;">
                    <button class="historial-tab active" data-tab="todos" style="padding: 12px 24px; border: none; background: none; cursor: pointer; font-size: 14px; font-weight: 500; color: #64748b; border-bottom: 3px solid transparent; transition: all 0.3s ease;">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">dashboard</span>
                            Todos
                        </span>
                    </button>
                    <button class="historial-tab" data-tab="entrada" style="padding: 12px 24px; border: none; background: none; cursor: pointer; font-size: 14px; font-weight: 500; color: #64748b; border-bottom: 3px solid transparent; transition: all 0.3s ease;">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">input</span>
                            Entrada
                        </span>
                    </button>
                    <button class="historial-tab" data-tab="salida" style="padding: 12px 24px; border: none; background: none; cursor: pointer; font-size: 14px; font-weight: 500; color: #64748b; border-bottom: 3px solid transparent; transition: all 0.3s ease;">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">output</span>
                            Salida
                        </span>
                    </button>
                </div>

                <form class="gooey-search-wrapper" id="historialSearchForm" style="flex-shrink: 0;">
                    <span class="material-symbols-rounded gooey-search-icon">search</span>
                    <input
                        type="text"
                        id="historialSearchInput"
                        class="gooey-search-input"
                        placeholder="Buscar por número de movimiento..."
                    >
                    <button
                        class="gooey-search-clear"
                        id="historialSearchClear"
                        type="button"
                        style="display: none;"
                    >
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </form>
            </div>

            <div id="historialLoadingState" class="text-center py-4" style="display: none;">
                <div style="color: #64748b; font-size: 14px;">Cargando movimientos...</div>
            </div>

            <div class="table-responsive">
                <table class="table-talleres" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>#Movimiento</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Firma</th>
                            <th>Novedades</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody id="historialTableBody">
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 24px; color: #94a3b8;">
                                No hay movimientos para mostrar
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="historialEmptyState" style="display: none; text-align: center; padding: 24px; color: #94a3b8;">
                No se encontraron movimientos
            </div>

            <div id="historialPagination" class="mt-4" style="display: flex; justify-content: center; gap: 8px;"></div>
        </div>
    </main>

    <!-- Modal de Firma -->
    <div id="firmaModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2 id="firmaModalTitle">Firma del Movimiento</h2>
                <button class="modal-close-btn" onclick="window.cerrarFirmaModal()">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="modal-body" id="firmaModalBody">
                <div style="text-align: center; padding: 32px;">
                    <div class="loading-spinner"></div>
                    <p style="margin-top: 16px; color: #64748b;">Cargando firma...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalles -->
    <div id="detallesModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="detallesModalTitle">Detalles del Recibo</h2>
                <button class="modal-close-btn" onclick="window.cerrarDetallesModal()">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            
            <div class="modal-body" id="detallesModalBody">
                <div style="text-align: center; padding: 32px;">
                    <div class="loading-spinner"></div>
                    <p style="margin-top: 16px; color: #64748b;">Cargando detalles...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Novedades -->
    <div id="novedadesModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <div>
                    <h2 id="novedadesModalTitle">Novedades del Movimiento</h2>
                    <p id="novedadesModalFecha" style="margin: 4px 0 0 0; font-size: 12px; color: #94a3b8; font-weight: 500;"></p>
                </div>
                <button class="modal-close-btn" onclick="window.cerrarNovedadesModal()">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="modal-body" id="novedadesModalBody">
                <div style="text-align: center; padding: 32px;">
                    <div class="loading-spinner"></div>
                    <p style="margin-top: 16px; color: #64748b;">Cargando novedades...</p>
                </div>
            </div>
        </div>
    </div>

    <script type="module" src="{{ asset('js/seguimiento-lavanderia/index.js') }}?v={{ time() }}"></script>
@endsection
