@extends('layouts.base')

@section('title', 'Gestión de Talleres')
@section('page-title', 'Gestión de Talleres')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/top-nav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/talleres/talleres-admin.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/talleres/talleres-spa.css') }}?v={{ time() }}">
@endpush

@section('body')
    <!-- Dashboard Top Nav -->
    @include('components.top-nav')

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
        @php
            $currentView = request('view');
            $isOrdenesView = $currentView === 'ordenes';
            $isTalleresView = !$isOrdenesView;
            $isVisualizador = auth()->user()->hasRole('visualizador_talleres');
        @endphp
        <nav class="sidebar-nav">
            @if($isVisualizador)
                <!-- Menú anidado para visualizador_talleres -->
                <div class="sidebar-group">
                    <button class="sidebar-item sidebar-group-toggle" id="navTalleresGroup">
                        <span class="material-symbols-rounded">factory</span>
                        <span class="nav-label">Talleres</span>
                        <span class="material-symbols-rounded expand-icon">expand_more</span>
                    </button>
                    <div class="sidebar-submenu" id="talleresSubmenu">
                        <button class="sidebar-item sidebar-subitem {{ $isTalleresView && $status !== 'inactivos' ? 'active' : '' }}" data-view="viewTalleres" data-status="activos" id="navTalleres">
                            <span class="nav-label">Activos</span>
                        </button>
                        <button class="sidebar-item sidebar-subitem {{ $isTalleresView && $status === 'inactivos' ? 'active' : '' }}" data-view="viewTalleres" data-status="inactivos" id="navTalleresInactivos">
                            <span class="nav-label">Inactivos</span>
                        </button>
                        <button class="sidebar-item sidebar-subitem {{ $isOrdenesView ? 'active' : '' }}" data-view="viewOrdenes" id="navOrdenes">
                            <span class="nav-label">Órdenes</span>
                        </button>
                    </div>
                </div>
                <div class="sidebar-group">
                    <button class="sidebar-item sidebar-group-toggle" id="navPrestamosGroup">
                        <span class="material-symbols-rounded">payment</span>
                        <span class="nav-label">Préstamos</span>
                        <span class="material-symbols-rounded expand-icon">expand_more</span>
                    </button>
                    <div class="sidebar-submenu collapsed" id="prestamosSubmenu">
                        <a href="{{ route('talleres.prestamos-global', ['tab' => 'insumos']) }}" class="sidebar-item sidebar-subitem" id="navPrestamosInsumos">
                            <span class="nav-label">Insumos</span>
                        </a>
                        <a href="{{ route('talleres.prestamos-global', ['tab' => 'contramuestra']) }}" class="sidebar-item sidebar-subitem" id="navPrestamosContramuestras">
                            <span class="nav-label">Contramuestras</span>
                        </a>
                    </div>
                </div>
                <a href="{{ route('entrada.index') }}"
                   class="sidebar-item {{ request()->routeIs('entrada.*') ? 'active' : '' }}"
                   id="navEntradaCostura"
                   aria-label="Ir a Entrada Costura">
                    <span class="material-symbols-rounded">assignment_return</span>
                    <span class="nav-label">Entrada Costura</span>
                </a>
                <a href="{{ route('seguimiento-lavanderia.index') }}"
                   class="sidebar-item {{ request()->routeIs('seguimiento-lavanderia.*') ? 'active' : '' }}"
                   aria-label="Ir a Lavandería">
                    <span class="material-symbols-rounded">local_laundry_service</span>
                    <span class="nav-label">Lavandería</span>
                </a>
            @else
                <!-- Menú plano para otros roles -->
                <button class="sidebar-item {{ !$isOrdenesView && $status !== 'inactivos' ? 'active' : '' }}" data-view="viewTalleres" data-status="activos" id="navTalleres">
                    <span class="material-symbols-rounded">factory</span>
                    <span class="nav-label">Talleres Activos</span>
                </button>
                <button class="sidebar-item {{ !$isOrdenesView && $status === 'inactivos' ? 'active' : '' }}" data-view="viewTalleres" data-status="inactivos" id="navTalleresInactivos">
                    <span class="material-symbols-rounded">cancel</span>
                    <span class="nav-label">Talleres Inactivos</span>
                </button>
                <button class="sidebar-item {{ $isOrdenesView ? 'active' : '' }}" data-view="viewOrdenes" id="navOrdenes">
                    <span class="material-symbols-rounded">assignment</span>
                    <span class="nav-label">Órdenes</span>
                </button>
            @endif
        </nav>
        @if(!auth()->user()->hasRole('visualizador_talleres'))
        <div class="sidebar-footer">
            <a href="{{ route('dashboard') }}" class="btn-volver">
                <span class="material-symbols-rounded">arrow_back</span>
                <span class="nav-label">Volver</span>
            </a>
        </div>
        @endif
    </aside>

    <!-- Main Content -->
    <main class="main-container" 
          data-csrf-token="{{ csrf_token() }}"
          data-route-toggle-status="{{ route('talleres.toggle-status', ':id') }}"
          data-route-api-search="{{ route('talleres.api.search') }}"
          data-route-api-recibos="{{ route('talleres.api.recibos', ':id') }}"
          data-route-api-entregas="{{ route('talleres.api.entregas', [':taller_id', ':recibo_id', ':es_parcial']) }}"
          data-route-actualizar-precio="{{ route('talleres.actualizar-precio', ':id') }}"
          data-route-store="{{ route('talleres.store') }}"
          data-route-update="{{ route('talleres.update', ':id') }}"
          data-route-api-ordenes="{{ route('talleres.api.ordenes') }}"
          data-route-api-recibo-completo="{{ route('talleres.api.recibo-completo') }}">
          
        <!-- Vista 1: Grid de Talleres -->
        <div id="viewTalleres" class="view-container">
            <div class="page-header">
            </div>


            <div class="table-container" id="talleresGrid">
                <table class="table-talleres">
                    <thead>
                        <tr>
                            <th>Taller</th>
                            <th>Estado</th>
                            <th>Completados</th>
                            <th>Pendientes</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="talleresRows">
                        @forelse($talleres as $taller)
                            <tr class="{{ !$taller->activo ? 'inactive' : '' }}" data-name="{{ strtolower($taller->name) }}" data-taller-id="{{ $taller->id }}">
                                <td class="col-taller-name">{{ $taller->name }}</td>
                                <td>
                                    <div class="taller-status-toggle">
                                        <label class="switch">
                                            <input type="checkbox" class="toggle-taller-status" 
                                                data-id="{{ $taller->id }}" 
                                                {{ $taller->activo ? 'checked' : '' }}>
                                            <span class="slider round"></span>
                                        </label>
                                        <span class="status-label {{ $taller->activo ? 'active' : 'inactive' }}">
                                            {{ $taller->activo ? 'ACTIVO' : 'INACTIVO' }}
                                        </span>
                                    </div>
                                </td>
                                <td><span class="stat-value stat-completed" data-taller-id="{{ $taller->id }}">-</span></td>
                                <td><span class="stat-value stat-pending" data-taller-id="{{ $taller->id }}">-</span></td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn-edit-icon btn-edit-taller" data-id="{{ $taller->id }}" data-name="{{ $taller->name }}" title="Editar nombre">
                                            <span class="material-symbols-rounded">edit</span>
                                        </button>
                                        <button class="btn-view btn-view-recibos" data-taller-id="{{ $taller->id }}" data-taller-name="{{ $taller->name }}">
                                            Ver Recibos <span style="font-size: 10px; margin-left: 5px;">&#10095;</span>
                                        </button>
                                        <a class="btn-view" href="{{ route('talleres.prestamos', ['id' => $taller->id]) }}" style="text-decoration:none;">
                                            Ver Préstamos <span style="font-size: 10px; margin-left: 5px;">&#10095;</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="table-empty-state">No hay talleres disponibles en este momento.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginacion -->
            <div class="pagination-container">
                @if($talleres instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    {{ $talleres->appends(['search' => $search, 'status' => $status])->links('vendor.pagination.simple-clean') }}
                @endif
            </div>
        </div>

        <!-- Vista 2: Recibos -->
        <div id="viewRecibos" class="view-container" style="display: none;">
            <div class="page-header-recibos">
                <div class="header-left">
                    <button class="btn-back" id="backFromRecibos" title="Volver a Talleres">
                        <span class="material-symbols-rounded">arrow_back</span>
                    </button>
                    <div class="title-group">
                        <span class="subtitle">Recibos Asignados a:</span>
                        <h1 id="recibosTitle">Taller</h1>
                    </div>
                </div>
            </div>

            <div class="recibos-card">
                <div class="card-header">
                    <div class="icon">
                        <span class="material-symbols-rounded" style="font-size: 18px;">receipt_long</span>
                    </div>
                    <h2>Listado de Recibos Asignados</h2>
                </div>
                
                <div id="recibosContent">
                    <div class="loading">
                        <div class="loading-spinner"></div>
                        <p>Cargando recibos...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista 3: Entregas -->
        <div id="viewEntregas" class="view-container" style="display: none;">
            <div class="page-header-recibos">
                <div class="header-left">
                    <button class="btn-back" id="backFromEntregas" title="Volver a Recibos">
                        <span class="material-symbols-rounded">arrow_back</span>
                    </button>
                    <div class="title-group">
                        <span class="subtitle">Detalle de Entregas</span>
                        <h1 id="entregasTitle">Entregas</h1>
                    </div>
                </div>
                
                <div class="header-stats">
                    <div class="stat-box blue">
                        <span class="stat-label">TOTAL</span>
                        <span class="stat-number" id="entregasTotalValue">0</span>
                    </div>
                </div>
            </div>

            <div class="recibos-card">
                <div class="card-header">
                    <div class="icon">
                        <span class="material-symbols-rounded" style="font-size: 18px;">inventory_2</span>
                    </div>
                    <h2 id="entregasCardTitle">Historial de Entregas Semanales</h2>
                </div>
                
                <div id="entregasContent" style="padding: 20px;">
                    <div class="loading">
                        <div class="loading-spinner"></div>
                        <p>Cargando entregas...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista 4: ordenes (Todos los Recibos)-->
        <div id="viewOrdenes" class="view-container" style="display: none;">
            <div class="recibos-card">
                <div class="card-header">
                    <div class="icon">
                        <span class="material-symbols-rounded" style="font-size: 18px;">assignment</span>
                    </div>
                    <h2>Listado de Órdenes Asignadas a Talleres</h2>
                </div>
                
                <div id="ordenesContent" style="padding: 20px;">
                    <div class="loading">
                        <div class="loading-spinner"></div>
                        <p>Cargando órdenes...</p>
                    </div>
                </div>
            </div>
        </div>

    </main>

    @include('components.orders-components.recibo-corte-bodega-detail-modal')

    <!-- Modal detalle de recibo/pedido (COSTURA) -->
    <div id="modal-overlay"
         style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;"
         onclick="closeModalOverlay()"></div>
    <div id="order-detail-modal-wrapper"
         style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
        <x-orders-components.order-detail-modal />
    </div>
    <x-orders-components.order-tracking-modal />
    <div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none; width: 0; height: 0; overflow: visible;"></div>

    <!-- Modal Nuevo Taller -->
    <div id="modalNewTaller" class="modal-talleres">
        <div class="modal-content-talleres">
            <div class="modal-header-talleres">
                <h2><span class="material-symbols-rounded">factory</span> Registrar Nuevo Taller</h2>
                <span class="close-modal-talleres">&times;</span>
            </div>
            <form id="formNewTaller">
                <div class="form-body-talleres">
                    <div class="form-group-talleres">
                        <label>Nombre del Taller</label>
                        <div class="input-with-icon">
                            <span class="material-symbols-rounded">person</span>
                            <input type="text" name="name" required placeholder="Nombre completo">
                        </div>
                    </div>
                </div>
                <div class="modal-footer-talleres">
                    <button type="button" class="btn-cancel close-modal-btn">Cancelar</button>
                    <button type="submit" class="btn-submit">
                        <span class="material-symbols-rounded">save</span>
                        GUARDAR TALLER
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal Editar Taller -->
    <div id="modalEditTaller" class="modal-talleres">
        <div class="modal-content-talleres">
            <div class="modal-header-talleres">
                <h2><span class="material-symbols-rounded">edit</span> Editar Nombre del Taller</h2>
                <span class="close-modal-talleres-edit">&times;</span>
            </div>
            <form id="formEditTaller">
                <input type="hidden" name="taller_id" id="editTallerId">
                <div class="form-body-talleres">
                    <div class="form-group-talleres">
                        <label>Nombre del Taller</label>
                        <div class="input-with-icon">
                            <span class="material-symbols-rounded">person</span>
                            <input type="text" name="name" id="editTallerName" required placeholder="Nombre completo o comercial">
                        </div>
                    </div>
                </div>
                <div class="modal-footer-talleres">
                    <button type="button" class="btn-cancel close-modal-btn-edit">Cancelar</button>
                    <button type="submit" class="btn-submit">
                        <span class="material-symbols-rounded">save</span>
                        GUARDAR CAMBIOS
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/ordersjs/order-detail-modal-manager.js') }}"></script>
    <script src="{{ asset('js/modulos/talleres/talleres-admin.js') }}?v={{ time() }}"></script>
@endpush
