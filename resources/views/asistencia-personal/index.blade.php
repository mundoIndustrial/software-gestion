@extends('layouts.asistencia-clean')

@section('page-title', 'Asistencia Personal')

@section('content')
<div class="asistencia-content">
    <!-- Sección de Controles -->
    <div class="controls-section">
        <h2 class="section-title">Gestión de Asistencia</h2>
        <div class="buttons-grid">
            <button class="btn btn-primary" id="insertReportBtn">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                    <polyline points="13 2 13 9 20 9"></polyline>
                </svg>
                <span>Insertar Reporte (PDF)</span>
            </button>
            <button class="btn btn-success" id="saveReportBtn">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>Guardar Reporte</span>
            </button>
            <button class="btn btn-info" id="verPersonalBtn">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Ver Personal</span>
            </button>
            <button class="btn btn-warning" id="gestionHorariosBtn">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>Gestión de Horarios</span>
            </button>
        </div>
    </div>

    <!-- Input oculto para PDF -->
    <input type="file" id="pdfInput" accept=".pdf" style="display: none;">

    <!-- Sección de Tabla de Reportes Guardados -->
    <div class="reports-list-section">
        <h3 class="subsection-title">Reportes Guardados</h3>
        <div class="table-wrapper">
            <table class="reports-list-table">
                <thead>
                    <tr>
                        <th>Número de Reporte</th>
                        <th>Nombre</th>
                        <th>Fecha de Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="reportsListBody">
                    @forelse ($reportes as $reporte)
                        <tr>
                            <td>{{ $reporte->numero_reporte }}</td>
                            <td>{{ $reporte->nombre_reporte }}</td>
                            <td>{{ $reporte->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <button class="btn-action btn-view" data-id="{{ $reporte->id }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-cell">No hay reportes guardados aún</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Detalles del Reporte -->
    <div id="reportDetailModal" class="modal-overlay modal-detail-overlay" style="display: none;">
        <div class="modal-content modal-detail-content">
            <div class="modal-detail-header">
                <h2 id="reportModalTitle"></h2>
                <button class="btn-modal-close-detail">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-detail-body">
                <div class="modal-controls">
                <button class="btn btn-info" id="btnHorasTrabajadas">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Horas Trabajadas</span>
                </button>
                <button class="btn btn-warning" id="btnAusenciasDelDia">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"></path>
                    </svg>
                    <span>Personal Inasistente</span>
                </button>
                <button class="btn btn-danger" id="btnTotalHorasExtras">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Total Horas Extras</span>
                </button>
                <button class="btn btn-secondary" id="btnCerrarReporte">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    <span>Cerrar</span>
                </button>
                </div>
                
                <!-- Botón para exportar JSON (solo visible cuando hay tabla de horas extras) -->
                <div id="exportButtonContainer" style="display: none; margin-top: 10px;">
                    <button class="btn btn-primary" onclick="exportarDatosTotalHorasExtras()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span>Descargar JSON</span>
                    </button>
                </div>
                
                <!-- Tabs por fecha -->
                <div class="tabs-container">
                    <div class="tabs-header" id="tabsHeader">
                        <!-- Los tabs se generarán dinámicamente con JavaScript -->
                    </div>
                    
                    <!-- Barra de búsqueda -->
                    <div class="search-bar-container">
                        <input 
                            type="text" 
                            id="searchInput" 
                            class="search-bar-input" 
                            placeholder="Buscar por número de persona..."
                            autocomplete="off"
                        >
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </div>
                    
                    <div class="tabs-content">
                        <div class="tab-pane active" id="tabContent">
                            <div class="records-table-wrapper">
                                <table class="records-table" id="recordsTable">
                                    <thead>
                                        <tr id="recordsTableHeader">
                                            <th>Persona</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recordsTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Ausencias del Día -->
    <div id="absenciasModal" class="modal-overlay modal-detail-overlay" style="display: none;">
        <div class="modal-content modal-detail-content">
            <div class="modal-detail-header">
                <h2>Personal Inasistente</h2>
                <button class="btn-modal-close-detail" id="btnCloseAbsencias">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-detail-body">
                <div class="ausencias-table-wrapper">
                    <table class="ausencias-table" id="ausenciasTable">
                        <thead>
                            <tr>
                                <th>Persona</th>
                                <th>ID</th>
                                <th>Total Inasistencias</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="ausenciasTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Emergente - Ver Inasistencias -->
    <div id="verInasistenciasModal" class="modal-overlay modal-detail-overlay" style="display: none;">
        <div class="modal-content modal-detail-content modal-inasistencias">
            <div class="modal-detail-header">
                <h2 id="inasistenciasTitle"></h2>
                <button class="btn-modal-close-detail" id="btnCloseVerInasistencias">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-detail-body">
                <div class="inasistencias-list" id="inasistenciasList">
                </div>
            </div>
        </div>
    </div>

    <!-- Modal - Ver y Gestionar Personal -->
    <div id="verPersonalModal" class="modal-overlay modal-detail-overlay" style="display: none;">
        <div class="modal-content modal-detail-content" style="max-width: 900px;">
            <div class="modal-detail-header">
                <h2>Gestión de Roles del Personal (<span id="totalPersonal">0</span> personas)</h2>
                <button class="btn-modal-close-detail" id="btnCloseVerPersonal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-detail-body">
                <!-- Barra de búsqueda -->
                <div class="search-bar-container" style="margin-bottom: 20px;">
                    <input 
                        type="text" 
                        id="personalSearchInput" 
                        class="search-bar-input" 
                        placeholder="Buscar por código o nombre..."
                        autocomplete="off"
                    >
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </div>

                <div class="personal-table-wrapper">
                    <table class="personal-table" id="personalTable">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="personalTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal - Gestión de Horarios por Roles -->
    <div id="gestionHorariosModal" class="modal-overlay modal-detail-overlay" style="display: none;">
        <div class="modal-content modal-detail-content" style="max-width: 700px;">
            <div class="modal-detail-header">
                <h2>Gestión de Horarios por Roles</h2>
                <button class="btn-modal-close-detail" id="btnCloseGestionHorarios">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-detail-body">
                <div class="horarios-table-wrapper">
                    <table class="horarios-table" id="horariosTable">
                        <thead>
                            <tr>
                                <th>Rol</th>
                                <th>Entrada Mañana</th>
                                <th>Salida Mañana</th>
                                <th>Entrada Tarde</th>
                                <th>Salida Tarde</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="horariosTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    </div>

</div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/asistencia-personal.css') }}">
@endsection

@section('scripts')
    <!-- Módulos del sistema de Asistencia Personal -->
    <script src="{{ asset('js/asistencia-personal/utilidades.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/pdf-handler.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/filtros-horas.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/busqueda.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/horas-trabajadas.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/report-details.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/absencias.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/total-horas-extras.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/personal-roles.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/gestion-horarios.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/init.js') }}"></script>
@endsection