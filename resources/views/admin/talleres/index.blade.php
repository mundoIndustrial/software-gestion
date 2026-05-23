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
        <nav class="sidebar-nav">
            <button class="sidebar-item active" data-view="viewTalleres" id="navTalleres">
                <span class="material-symbols-rounded">factory</span>
                <span class="nav-label">Gestión Talleres</span>
            </button>
            <button class="sidebar-item" data-view="viewOrdenes" id="navOrdenes">
                <span class="material-symbols-rounded">assignment</span>
                <span class="nav-label">Órdenes</span>
            </button>
        </nav>
        <div class="sidebar-footer">
            <a href="{{ route('dashboard') }}" class="btn-volver">
                <span class="material-symbols-rounded">arrow_back</span>
                <span class="nav-label">Volver</span>
            </a>
        </div>
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
          data-route-api-ordenes="{{ route('talleres.api.ordenes') }}">
          
        <!-- Vista 1: Grid de Talleres -->
        <div id="viewTalleres" class="view-container">
            <div class="page-header">
                <div class="page-title-group">
                    <div class="subtitle" id="talleresSubtitle">{{ $status === 'inactivos' ? 'TALLERES INACTIVOS' : 'TALLERES ACTIVOS' }}</div>
                </div>
                <div class="page-actions">
                    <form action="{{ route('talleres.index') }}" method="GET" class="gooey-search-wrapper">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <span class="material-symbols-rounded gooey-search-icon">search</span>
                        <input type="text" name="search" class="gooey-search-input" placeholder="Buscar taller..." id="searchInput" value="{{ $search ?? '' }}">
                        <button class="gooey-search-clear" id="clearSearch" type="button" onclick="window.location.href='{{ route('talleres.index', ['status' => $status]) }}'">
                            <span class="material-symbols-rounded">close</span>
                        </button>
                    </form>
                    <button class="btn-primary-gradient btn-new-taller" id="btnNewTaller">
                        <span class="material-symbols-rounded">add</span>
                        NUEVO TALLER
                    </button>
                </div>
            </div>

            <!-- Tabs de Talleres -->
            <div class="talleres-tabs-container">
                <div class="talleres-tabs">
                    <button class="taller-tab-btn {{ $status === 'activos' ? 'active' : '' }}" data-status="activos">
                        <span class="material-symbols-rounded">check_circle</span>
                        TALLERES ACTIVOS
                    </button>
                    <button class="taller-tab-btn {{ $status === 'inactivos' ? 'active' : '' }}" data-status="inactivos">
                        <span class="material-symbols-rounded">cancel</span>
                        TALLERES INACTIVOS
                    </button>
                </div>
            </div>

            <div class="cards-grid" id="talleresGrid">
                @forelse($talleres as $taller)
                    <div class="taller-card {{ !$taller->activo ? 'inactive' : '' }}" data-name="{{ strtolower($taller->name) }}" data-taller-id="{{ $taller->id }}">
                        <div class="card-header-info">
                            <h2 class="taller-name">{{ $taller->name }}</h2>
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
                        </div>
                        <p class="taller-role">RESPONSABLE DE TALLER</p>
                        
                        <div class="stats-container">
                            <div class="stat-row">
                                <span>Completados:</span>
                                <span class="stat-value stat-completed" data-taller-id="{{ $taller->id }}">-</span>
                            </div>
                            <div class="stat-row">
                                <span>Pendientes:</span>
                                <span class="stat-value stat-pending" data-taller-id="{{ $taller->id }}">-</span>
                            </div>
                        </div>
                        
                        <div class="card-footer-actions">
                            <button class="btn-edit-icon btn-edit-taller" data-id="{{ $taller->id }}" data-name="{{ $taller->name }}" title="Editar nombre">
                                <span class="material-symbols-rounded">edit</span>
                            </button>
                            <button class="btn-view btn-view-recibos" data-taller-id="{{ $taller->id }}">
                                Ver Recibos <span style="font-size: 10px; margin-left: 5px;">&#10095;</span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div style="width: 100%; padding: 40px; text-align: center; color: #64748b; background: white; border-radius: 12px; border: 1px dashed #cbd5e1;">
                        <span class="material-symbols-rounded" style="font-size: 40px; color: #cbd5e1; margin-bottom: 10px;">inbox</span>
                        <p>No hay talleres disponibles en este momento.</p>
                    </div>
                @endforelse
            </div>

            <!-- Paginación -->
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

        <!-- Vista 4: Órdenes (Todos los Recibos) -->
        <div id="viewOrdenes" class="view-container" style="display: none;">
            <div class="page-header">
                <div class="page-title-group">
                    <div class="subtitle">TODAS LAS ÓRDENES</div>
                </div>
                <div class="page-actions">
                    <form action="{{ route('talleres.index') }}" method="GET" class="gooey-search-wrapper">
                        <span class="material-symbols-rounded gooey-search-icon">search</span>
                        <input type="text" name="search" class="gooey-search-input" placeholder="Buscar orden..." id="searchOrdenesInput">
                        <button class="gooey-search-clear" id="clearSearchOrdenes" type="button">
                            <span class="material-symbols-rounded">close</span>
                        </button>
                    </form>
                </div>
            </div>

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
                            <input type="text" name="name" required placeholder="Nombre completo o comercial">
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/modulos/talleres/talleres-admin.js') }}?v={{ time() }}"></script>
@endpush
