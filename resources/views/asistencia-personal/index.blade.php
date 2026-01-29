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
                <button class="btn-modal-close-detail" aria-label="Cerrar modal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
                <div class="header-content">
                    <h2 id="reportModalTitle"></h2>
                </div>
                <div class="header-controls">
                    <button class="btn-hamburger-menu" id="btnMenuHamburguesa" aria-label="Menú de navegación">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
            
            <!-- Menú Hamburguesa Desplegable -->
            <nav id="navigationMenu" class="navigation-menu" style="display: none;">
                <button class="menu-item" id="menuHorasTrabajadas" data-tab="horas-trabajadas">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Horas Trabajadas</span>
                </button>
                <button class="menu-item" id="menuAusenciasDelDia" data-tab="ausencias">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"></path>
                    </svg>
                    <span>Personal Inasistente</span>
                </button>
                <button class="menu-item" id="menuMarcasFaltantes" data-tab="marcas-faltantes">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                    <span>Marcas Faltantes</span>
                </button>
                <button class="menu-item" id="menuTotalHorasExtras" data-tab="horas-extras">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                        <path d="M12 2v20"></path>
                        <path d="M2 12h20"></path>
                    </svg>
                    <span>Total Horas Extras</span>
                </button>
                <button class="menu-item" id="menuRegistros" data-tab="registros">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <span>Registros</span>
                </button>
                <div class="menu-divider"></div>
                <button class="menu-item btn-success" id="btnDescargarPDFMenu" style="display: none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10z"></path>
                        <polyline points="14 2 14 10 22 10"></polyline>
                        <line x1="12" y1="19" x2="12" y2="5"></line>
                        <polyline points="9 16 12 19 15 16"></polyline>
                    </svg>
                    <span>Descargar PDF</span>
                </button>
            </nav>
            
            <div class="modal-detail-body">
                
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
                    <div class="search-bar-container" style="display: flex; gap: 12px; align-items: center; margin-bottom: 20px;">
                        <div style="flex: 1; max-width: 400px; position: relative;">
                            <input 
                                type="text" 
                                id="searchInput" 
                                class="search-bar-input" 
                                placeholder="Buscar por número de persona..."
                                autocomplete="off"
                                style="width: 100%; padding: 10px 12px 10px 40px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 14px; background: #fafafa; transition: all 0.3s ease;"
                            >
                            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; opacity: 0.5;">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </div>
                        <div id="btnEditarRegistroContainer" style="display: none;">
                        </div>
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

    <!-- Modal - Marcas Faltantes -->
    <div id="marcasFaltantesModal" class="modal-overlay modal-detail-overlay" style="display: none; align-items: center; justify-content: center; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 9999;">
        <div class="modal-content modal-detail-content" style="max-width: 1200px; width: 90%; max-height: 85vh; display: flex; flex-direction: column; position: relative; margin: 0 auto; margin-top: 50px;">
            <!-- Botón de cierre en esquina superior -->
            <button id="btnCerrarMarcasFaltantes" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 28px; cursor: pointer; color: #666; transition: color 0.2s; z-index: 10;" onmouseover="this.style.color='#000'" onmouseout="this.style.color='#666'">
                ×
            </button>
            
            <div class="modal-detail-body" style="flex: 1; overflow-y: auto; overflow-x: auto;">
                <div id="marcasFaltantesContainer">
                    <!-- Se generará dinámicamente -->
                </div>
            </div>
            <div style="padding: 20px; border-top: 1px solid #e0e0e0; background-color: #f8f9fa; text-align: center;">
                <button id="btnGuardarTodasMarcas" style="padding: 12px 32px; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0, 123, 255, 0.25);">
                    Guardar Cambios
                </button>
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
                                <th>Entrada Sábado</th>
                                <th>Salida Sábado</th>
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

    <!-- Modal de Edición de Registros - Total Horas Extras -->
    <div id="editarRegistroModal" class="modal-overlay modal-detail-overlay" style="display: none;">
        <div class="modal-content modal-detail-content" style="max-width: 700px;">
            <div class="modal-detail-header">
                <button class="btn-modal-close-detail" id="btnCloseEditarRegistro" aria-label="Cerrar modal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
                <div class="header-content">
                    <h2>Editar Registro de Horas</h2>
                </div>
            </div>
            
            <div class="modal-detail-body" style="padding: 20px;">
                <!-- Búsqueda de Persona -->
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Buscar Persona:</label>
                    <div style="position: relative;">
                        <input 
                            type="text" 
                            id="editarRegistroBusquedaPersona" 
                            class="search-bar-input" 
                            placeholder="Código o nombre..."
                            autocomplete="off"
                            style="width: 100%;"
                        >
                        <div id="editarRegistroResultadosBusqueda" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; max-height: 200px; overflow-y: auto; display: none; z-index: 1000;">
                        </div>
                    </div>
                </div>

                <!-- Persona Seleccionada -->
                <div id="editarRegistroPersonaSeleccionada" style="display: none; margin-bottom: 20px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
                    <strong id="editarRegistroNombrePersona"></strong> (Código: <span id="editarRegistroCodigoPersona"></span>)
                </div>

                <!-- Selección de Fecha -->
                <div id="editarRegistroFechaContainer" style="display: none; margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Seleccionar Fecha:</label>
                    <select id="editarRegistroFechaSelect" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">-- Seleccionar fecha --</option>
                    </select>
                </div>

                <!-- Datos del Día Seleccionado -->
                <div id="editarRegistroDatosDelDia" style="display: none; margin-bottom: 20px;">
                    <div style="background: #f9f9f9; padding: 15px; border-radius: 4px;">
                        <h4 style="margin-top: 0;">Marcas del Día:</h4>
                        
                        <!-- Lista de Marcas -->
                        <div id="editarRegistroMarcas" style="margin-bottom: 20px; padding: 10px; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 250px; overflow-y: auto;">
                            <p style="color: #999; text-align: center; margin: 20px 0;">Sin marcas registradas</p>
                        </div>
                        
                        <!-- Agregar Nueva Marca -->
                        <div style="margin-bottom: 20px; padding: 15px; background: #e8f5e9; border: 1px solid #81c784; border-radius: 4px;">
                            <h5 style="margin-top: 0; margin-bottom: 15px; color: #2e7d32;">✚ Agregar Nueva Marca</h5>
                            <div style="display: flex; gap: 10px; align-items: flex-end;">
                                <div style="flex: 1;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 12px;">Hora:</label>
                                    <input 
                                        type="time" 
                                        id="editarRegistroNuevaMarca" 
                                        placeholder="HH:MM:SS"
                                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                                    >
                                </div>
                                <button id="editarRegistroAgregarMarcaBtn" class="btn btn-success" style="padding: 8px 16px;">Agregar</button>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 15px; padding: 10px; background: #e3f2fd; border-radius: 4px;">
                            <strong style="color: #1976d2;">Total Horas Trabajadas: <span id="editarRegistroTotalHoras">0:00:00</span></strong>
                        </div>

                        <!-- Campo Agregar Hora Extra -->
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: bold;">Agregar Hora Extra (horas):</label>
                            <input 
                                type="number" 
                                id="editarRegistroAgregarHoraExtra" 
                                placeholder="0" 
                                min="0" 
                                step="0.01"
                                style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                            >
                        </div>

                        <!-- Campo Novedad -->
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: bold;">Novedad / Motivo:</label>
                            <textarea 
                                id="editarRegistroNovedad" 
                                placeholder="Motivo por el cual se agregó hora extra..."
                                style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; min-height: 80px; resize: vertical;"
                            ></textarea>
                        </div>

                        <!-- Botones de Acción -->
                        <div style="display: flex; gap: 10px; margin-top: 20px;">
                            <button id="editarRegistroGuardarBtn" class="btn btn-primary" style="flex: 1;">Guardar Cambios</button>
                            <button id="editarRegistroCancelarBtn" class="btn btn-secondary" style="flex: 1;">Cancelar</button>
                        </div>
                    </div>
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
    <script src="{{ asset('js/asistencia-personal/pdf-generator.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/filtros-horas.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/busqueda.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/horas-trabajadas.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/report-details.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/absencias.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/total-horas-extras.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/editar-registro.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/marcas-faltantes.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/personal-roles.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/gestion-horarios.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/init.js') }}"></script>
@endsection