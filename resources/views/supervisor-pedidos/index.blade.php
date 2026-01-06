@extends('supervisor-pedidos.layout')

@section('title', 'Supervisión de Pedidos')
@section('page-title', 'Supervisión de Pedidos')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/edit-pedido.css') }}">
@endpush

@section('content')
<div class="supervisor-pedidos-container">

    <!-- Tabla de Órdenes - Nuevo Diseño -->
    <div class="table-container">
        <div class="modern-table-wrapper">
            <div class="table-scroll-container">
                <div class="table-head">
                    <div style="display: flex; align-items: center; width: 100%; gap: 12px; padding: 14px 12px;">
                        @php
                            $columns = [
                                ['key' => 'acciones', 'label' => 'Acciones', 'flex' => '0 0 200px', 'justify' => 'flex-start'],
                                ['key' => 'numero', 'label' => 'Número', 'flex' => '0 0 140px', 'justify' => 'center'],
                                ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '0 0 200px', 'justify' => 'center'],
                                ['key' => 'fecha', 'label' => 'Fecha', 'flex' => '0 0 160px', 'justify' => 'center'],
                                ['key' => 'estado', 'label' => 'Estado', 'flex' => '0 0 150px', 'justify' => 'center'],
                                ['key' => 'asesora', 'label' => 'Asesora', 'flex' => '0 0 150px', 'justify' => 'center'],
                                ['key' => 'forma_pago', 'label' => 'Forma Pago', 'flex' => '0 0 140px', 'justify' => 'center'],
                                ['key' => 'fecha_estimada', 'label' => 'Entrega Est.', 'flex' => '0 0 160px', 'justify' => 'center'],
                            ];
                        @endphp
                        @foreach($columns as $column)
                            <div class="table-header-cell{{ $column['key'] === 'acciones' ? ' acciones-column' : '' }}" style="flex: {{ $column['flex'] }}; justify-content: {{ $column['justify'] }};">
                                <div class="th-wrapper" style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                    <span class="header-text">{{ $column['label'] }}</span>
                                    @if($column['key'] !== 'acciones')
                                        <button type="button" class="btn-filter-column" onclick="abrirModalFiltro('{{ $column['key'] }}')" title="Filtrar {{ $column['label'] }}">
                                            <span class="material-symbols-rounded">filter_alt</span>
                                            <div class="filter-badge"></div>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modern-table">
                    <div class="table-body">
                        @if($ordenes->count() > 0)
                            @foreach($ordenes as $orden)
                                <div class="table-row" data-orden-id="{{ $orden->id }}" data-numero="{{ $orden->numero_pedido }}" data-cliente="{{ $orden->cliente }}" data-fecha="{{ $orden->fecha_de_creacion_de_orden->format('d/m/Y') }}" data-estado="{{ $orden->estado }}" data-asesora="{{ $orden->asesora?->name ?? 'N/A' }}" data-forma_pago="{{ $orden->forma_de_pago ?? 'N/A' }}" data-fecha_estimada="{{ $orden->fecha_estimada_de_entrega ? $orden->fecha_estimada_de_entrega->format('d/m/Y') : 'N/A' }}">
                                    
                                    <!-- Acciones -->
                                    <div class="table-cell acciones-column" style="flex: 0 0 200px; justify-content: center; position: relative; display: flex; gap: 0.5rem;">
                                        <button class="action-view-btn" title="Ver opciones" onclick="toggleAcciones(event, {{ $orden->id }})">
                                            <span class="material-symbols-rounded">visibility</span>
                                        </button>
                                        <div class="action-menu" id="menu-{{ $orden->id }}" style="display: none;">
                                            <button class="action-menu-item" onclick="verOrdenDetalles({{ $orden->id }})">
                                                <span class="material-symbols-rounded">description</span>
                                                <span>Detalles</span>
                                            </button>
                                            <button class="action-menu-item" onclick="abrirSeguimiento({{ $orden->id }})">
                                                <span class="material-symbols-rounded">local_shipping</span>
                                                <span>Seguimiento</span>
                                            </button>
                                        </div>
                                        
                                        @if(request('aprobacion') === 'pendiente')
                                            <button class="btn-action btn-success" title="Aprobar orden" onclick="aprobarOrden({{ $orden->id }}, '{{ $orden->numero_pedido }}')">
                                                <span class="material-symbols-rounded">check_circle</span>
                                            </button>
                                        @endif
                                        
                                        <button class="btn-action btn-edit" title="Editar orden" onclick="abrirModalEditar({{ $orden->id }}, '{{ $orden->numero_pedido }}')">
                                            <span class="material-symbols-rounded">edit</span>
                                        </button>
                                        
                                        @if($orden->estado !== 'Anulada' && !$orden->aprobado_por_supervisor_en && (request('aprobacion') !== 'pendiente' && !request()->filled('estado')))
                                            <button class="btn-action btn-danger" title="Anular orden" onclick="abrirModalAnulacion({{ $orden->id }}, '{{ $orden->numero_pedido }}')">
                                                <span class="material-symbols-rounded">cancel</span>
                                            </button>
                                        @endif
                                    </div>
                                    
                                    <!-- Número -->
                                    <div class="table-cell" style="flex: 0 0 140px;" data-numero="{{ $orden->numero_pedido }}">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span style="font-weight: 600; color: #1e5ba8;">#{{ $orden->numero_pedido }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Cliente -->
                                    <div class="table-cell" style="flex: 0 0 200px;" data-cliente="{{ $orden->cliente }}">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span>{{ $orden->cliente }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Fecha -->
                                    <div class="table-cell" style="flex: 0 0 160px;" data-fecha="{{ $orden->fecha_de_creacion_de_orden->format('d/m/Y') }}">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span>{{ $orden->fecha_de_creacion_de_orden->format('d/m/Y') }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Estado -->
                                    <div class="table-cell" style="flex: 0 0 150px;" data-estado="{{ $orden->estado }}">
                                        <div class="cell-content" style="justify-content: center;">
                                            @php
                                                $estadoColors = [
                                                    'No iniciado' => ['bg' => '#ecf0f1', 'color' => '#7f8c8d'],
                                                    'En Ejecución' => ['bg' => '#fff3cd', 'color' => '#856404'],
                                                    'Entregado' => ['bg' => '#d4edda', 'color' => '#155724'],
                                                    'Anulada' => ['bg' => '#f8d7da', 'color' => '#721c24'],
                                                    'PENDIENTE_SUPERVISOR' => ['bg' => '#fff3cd', 'color' => '#856404'],
                                                ];
                                                $colors = $estadoColors[$orden->estado] ?? ['bg' => '#e3f2fd', 'color' => '#1e40af'];
                                            @endphp
                                            <span style="background: {{ $colors['bg'] }}; color: {{ $colors['color'] }}; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; white-space: nowrap;">
                                                {{ $orden->estado }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Asesora -->
                                    <div class="table-cell" style="flex: 0 0 150px;" data-asesora="{{ $orden->asesora?->name ?? 'N/A' }}">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span>{{ $orden->asesora?->name ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Forma Pago -->
                                    <div class="table-cell" style="flex: 0 0 140px;" data-forma_pago="{{ $orden->forma_de_pago ?? 'N/A' }}">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span>{{ $orden->forma_de_pago ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Fecha Estimada -->
                                    <div class="table-cell" style="flex: 0 0 160px;" data-fecha_estimada="{{ $orden->fecha_estimada_de_entrega ? $orden->fecha_estimada_de_entrega->format('d/m/Y') : 'N/A' }}">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span>{{ $orden->fecha_estimada_de_entrega ? $orden->fecha_estimada_de_entrega->format('d/m/Y') : 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div style="padding: 40px; text-align: center; color: #9ca3af; width: 100%;">
                                <p>No hay órdenes disponibles</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Paginación -->
            <div class="table-pagination">
                <div class="pagination-info">
                    <span id="paginationInfo">Mostrando 1-15 de {{ $ordenes->total() }} registros</span>
                </div>
                <div class="pagination-controls">
                    {{ $ordenes->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Filtro Dinámico -->
<div id="modalFiltro" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 90%; max-width: 400px;">
        <div class="modal-header">
            <h2 id="modalFiltroTitulo">Filtrar</h2>
            <button class="btn-close" onclick="cerrarModalFiltro()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="formFiltroColumna" onsubmit="aplicarFiltroColumna(event)">
                <div class="form-group" id="filtroContenido">
                    <!-- Contenido dinámico según la columna -->
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalFiltro()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" style="background: var(--primary-color); color: white;">
                        Aplicar Filtro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Orden -->
<div id="modalVerOrden" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2>Detalle de Orden</h2>
            <button class="btn-close" onclick="cerrarModalVerOrden()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="modal-body" id="modalVerOrdenContent">
            <!-- Contenido cargado dinámicamente -->
        </div>
    </div>
</div>

<!-- Modal Anulación -->
<div id="modalAnulacion" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-anulacion">
        <div class="modal-header">
            <div class="header-icon">
                <span class="material-symbols-rounded">warning</span>
            </div>
            <h2>¿Anular Orden <span id="ordenNumero"></span>?</h2>
        </div>

        <div class="modal-body">
            <p class="advertencia-texto">
                Esta acción cancelará la orden y no se podrá revertir. Por favor ingresa el motivo de la anulación.
            </p>

            <form id="formAnulacion" onsubmit="confirmarAnulacion(event)">
                @csrf
                <div class="form-group">
                    <label for="motivoAnulacion">Motivo de anulación *</label>
                    <textarea 
                        id="motivoAnulacion" 
                        name="motivo_anulacion" 
                        class="form-control" 
                        rows="4" 
                        placeholder="Ej: El cliente solicitó reembolso, error en precios..."
                        required
                        minlength="10"
                        maxlength="500">
                    </textarea>
                    <small class="contador-caracteres">
                        <span id="contadorActual">0</span>/500 caracteres
                    </small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalAnulacion()">
                        Cancelar
                    </button>
                    <button type="submit" id="btnConfirmarAnulacion" class="btn btn-danger">
                        <span class="material-symbols-rounded">delete</span>
                        Confirmar Anulación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Pedido -->
<div id="modalEditarPedido" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 95%; max-width: 1400px; max-height: 95vh; overflow-y: auto;">
        <div class="modal-header">
            <h2>Editar Pedido <span id="editarNumeroOrden"></span></h2>
            <button class="btn-close" onclick="cerrarModalEditar()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <div class="modal-body">
            <form id="formEditarPedido" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="editarOrdenId" name="orden_id">

                <!-- Información General del Pedido -->
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                    <h3 style="margin: 0 0 1.5rem 0; color: #2c3e50; font-size: 1.1rem; border-bottom: 2px solid #e0e6ed; padding-bottom: 0.75rem;">
                        <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 0.5rem;">info</span>
                        Información General
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                        <div class="form-group">
                            <label for="editarCliente">Cliente *</label>
                            <input type="text" id="editarCliente" name="cliente" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="editarFormaPago">Forma de Pago</label>
                            <input type="text" id="editarFormaPago" name="forma_de_pago" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="editarDiaEntrega">Días de Entrega</label>
                            <div style="display: flex; gap: 0.75rem; align-items: flex-end;">
                                <input type="number" id="editarDiaEntrega" name="dia_de_entrega" class="form-control" min="1" style="flex: 1;">
                                <button type="button" class="btn" style="background: #3498db; color: white; padding: 0.5rem 1rem; white-space: nowrap; display: flex; align-items: center; gap: 0.5rem;" onclick="calcularFechaEstimada()">
                                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">event</span>
                                    Calcular
                                </button>
                            </div>
                            <!-- Campo oculto para enviar fecha estimada -->
                            <input type="hidden" id="fechaEstimadaOculta" name="fecha_estimada_de_entrega" value="">
                            <div id="fechaEstimadaContainer" style="margin-top: 0.75rem; display: none;">
                                <label style="font-size: 0.9rem; color: #27ae60; font-weight: 600;">Fecha Estimada:</label>
                                <div id="fechaEstimadaMostrada" style="padding: 0.5rem; background: #d5f4e6; border: 1px solid #27ae60; border-radius: 4px; color: #27ae60; font-weight: 500;">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="editarNovedades">Novedades</label>
                        <textarea id="editarNovedades" name="novedades" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <!-- Prendas del Pedido -->
                <div id="prendasContainer">
                    <h3 style="margin: 0 0 1.5rem 0; color: #2c3e50; font-size: 1.1rem; border-bottom: 2px solid #e0e6ed; padding-bottom: 0.75rem;">
                        <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 0.5rem;">checkroom</span>
                        Prendas del Pedido
                    </h3>
                    <!-- Las prendas se cargarán dinámicamente aquí -->
                </div>

                <!-- Botones de Acción -->
                <div class="form-actions" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid #e0e6ed;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalEditar()">
                        <span class="material-symbols-rounded">close</span>
                        Cancelar
                    </button>
                    <button type="submit" class="btn" style="background: #27ae60; color: white;">
                        <span class="material-symbols-rounded">save</span>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    // ===== VARIABLES GLOBALES =====
    let filtroActual = null;

    // ===== TOGGLE MENU ACCIONES =====
    function toggleAcciones(event, ordenId) {
        event.stopPropagation();
        const menu = document.getElementById(`menu-${ordenId}`);
        
        // Cerrar otros menús abiertos
        document.querySelectorAll('.action-menu:not([style*="display: none"])').forEach(m => {
            if (m.id !== `menu-${ordenId}`) {
                m.style.display = 'none';
            }
        });
        
        // Toggle del menú actual
        if (menu.style.display === 'none' || menu.style.display === '') {
            menu.style.display = 'block';
        } else {
            menu.style.display = 'none';
        }
    }

    // Cerrar menús al hacer clic afuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.action-menu') && !e.target.closest('.action-view-btn')) {
            document.querySelectorAll('.action-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });

    // ===== MENU VER ORDEN =====
    function toggleVerMenu(event, ordenId) {
        event.stopPropagation();
        const menu = document.getElementById(`ver-menu-${ordenId}`);
        
        // Cerrar otros menús abiertos
        document.querySelectorAll('.ver-submenu[style*="display: block"]').forEach(m => {
            if (m.id !== `ver-menu-${ordenId}`) {
                m.style.display = 'none';
            }
        });
        
        // Toggle del menú actual
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }

    // Cerrar menús al hacer clic afuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.ver-menu-container')) {
            document.querySelectorAll('.ver-submenu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });

    // ===== FILTROS DE COLUMNAS =====

    function abrirModalFiltro(columna) {
        filtroActual = columna;
        const modalTitulo = document.getElementById('modalFiltroTitulo');
        const filtroContenido = document.getElementById('filtroContenido');
        const modal = document.getElementById('modalFiltro');

        let titulo = '';
        let campoNombre = '';

        // Configurar según la columna
        switch(columna) {
            case 'id-orden':
                titulo = 'Filtrar por ID Orden';
                campoNombre = 'numero';
                break;
            case 'cliente':
                titulo = 'Filtrar por Cliente';
                campoNombre = 'cliente';
                break;
            case 'fecha':
                modalTitulo.textContent = 'Filtrar por Fecha';
                filtroContenido.innerHTML = `
                    <label for="filtroDesde">Desde:</label>
                    <input type="date" id="filtroDesde" name="fecha_desde" class="form-control">
                    <label for="filtroHasta" style="margin-top: 1rem;">Hasta:</label>
                    <input type="date" id="filtroHasta" name="fecha_hasta" class="form-control">
                `;
                modal.style.display = 'flex';
                return;
            case 'estado':
                titulo = 'Filtrar por Estado';
                campoNombre = 'estado';
                // Estados predefinidos
                const estados = ['No iniciado', 'En Ejecución', 'Entregado', 'Anulada'];
                filtroContenido.innerHTML = `
                    <div class="form-group">
                        <input type="text" id="buscadorEstado" class="form-control" placeholder="Buscar estado..." style="margin-bottom: 1rem;">
                        <div id="listaEstados">
                            ${estados.map(estado => `
                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px;">
                                    <input type="checkbox" name="estado" value="${estado}" class="filtro-checkbox">
                                    <span>${estado}</span>
                                </label>
                            `).join('')}
                        </div>
                    </div>
                `;
                
                // Agregar funcionalidad de búsqueda
                setTimeout(() => {
                    document.getElementById('buscadorEstado')?.addEventListener('input', function(e) {
                        const valor = e.target.value.toLowerCase();
                        document.querySelectorAll('#listaEstados label').forEach(label => {
                            const texto = label.textContent.toLowerCase();
                            label.style.display = texto.includes(valor) ? 'flex' : 'none';
                        });
                    });
                }, 0);
                
                modal.style.display = 'flex';
                return;
            case 'asesora':
                titulo = 'Filtrar por Asesora';
                campoNombre = 'asesora';
                break;
            case 'forma-pago':
                titulo = 'Filtrar por Forma de Pago';
                campoNombre = 'forma_pago';
                break;
        }

        // Para columnas que necesitan cargar datos de la BD
        if (campoNombre && columna !== 'fecha' && columna !== 'estado') {
            cargarOpcionesFiltro(campoNombre, titulo, modal, filtroContenido);
        }
    }

    function cargarOpcionesFiltro(campo, titulo, modal, filtroContenido) {
        // Mapear campos a columnas de la BD
        const endpoint = `/supervisor-pedidos/filtro-opciones/${campo}`;
        
        fetch(endpoint)
            .then(response => response.json())
            .then(data => {
                modalTitulo = document.getElementById('modalFiltroTitulo');
                modalTitulo.textContent = titulo;
                
                // Crear HTML con buscador y checkboxes
                filtroContenido.innerHTML = `
                    <div class="form-group">
                        <input type="text" id="buscadorFiltro" class="form-control" placeholder="Buscar..." style="margin-bottom: 1rem;">
                        <div id="listaOpciones" style="max-height: 300px; overflow-y: auto;">
                            ${data.opciones.map(opcion => `
                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px; transition: background 0.2s;">
                                    <input type="checkbox" name="${campo}" value="${opcion}" class="filtro-checkbox">
                                    <span>${opcion || '(Sin especificar)'}</span>
                                </label>
                            `).join('')}
                        </div>
                    </div>
                `;
                
                // Agregar funcionalidad de búsqueda
                setTimeout(() => {
                    document.getElementById('buscadorFiltro')?.addEventListener('input', function(e) {
                        const valor = e.target.value.toLowerCase();
                        document.querySelectorAll('#listaOpciones label').forEach(label => {
                            const texto = label.textContent.toLowerCase();
                            label.style.display = texto.includes(valor) ? 'flex' : 'none';
                        });
                    });
                }, 0);
                
                modal.style.display = 'flex';
            })
            .catch(error => {
                console.error('Error cargando opciones:', error);
                filtroContenido.innerHTML = `<p style="color: red;">Error cargando opciones de filtro</p>`;
                modal.style.display = 'flex';
            });
    }

    function cerrarModalFiltro() {
        document.getElementById('modalFiltro').style.display = 'none';
        filtroActual = null;
    }

    function aplicarFiltroColumna(event) {
        event.preventDefault();
        
        // Construir URL con parámetros actuales
        const url = new URL(window.location);
        
        // Obtener todos los checkboxes seleccionados
        const checkboxes = document.querySelectorAll('.filtro-checkbox:checked');
        const valoresSeleccionados = Array.from(checkboxes).map(cb => cb.value);
        
        // Limpiar parámetros anteriores según el filtro actual
        if (filtroActual === 'id-orden') {
            url.searchParams.delete('numero');
            if (valoresSeleccionados.length > 0) url.searchParams.set('numero', valoresSeleccionados.join(','));
        } else if (filtroActual === 'cliente') {
            url.searchParams.delete('cliente');
            if (valoresSeleccionados.length > 0) url.searchParams.set('cliente', valoresSeleccionados.join(','));
        } else if (filtroActual === 'fecha') {
            url.searchParams.delete('fecha_desde');
            url.searchParams.delete('fecha_hasta');
            const desde = document.getElementById('filtroDesde')?.value;
            const hasta = document.getElementById('filtroHasta')?.value;
            if (desde) url.searchParams.set('fecha_desde', desde);
            if (hasta) url.searchParams.set('fecha_hasta', hasta);
        } else if (filtroActual === 'estado') {
            url.searchParams.delete('estado');
            if (valoresSeleccionados.length > 0) url.searchParams.set('estado', valoresSeleccionados.join(','));
        } else if (filtroActual === 'asesora') {
            url.searchParams.delete('asesora');
            if (valoresSeleccionados.length > 0) url.searchParams.set('asesora', valoresSeleccionados.join(','));
        } else if (filtroActual === 'forma-pago') {
            url.searchParams.delete('forma_pago');
            if (valoresSeleccionados.length > 0) url.searchParams.set('forma_pago', valoresSeleccionados.join(','));
        }
        
        window.location.href = url.toString();
    }

    // Cerrar modal al hacer clic fuera
    document.getElementById('modalFiltro')?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalFiltro();
    });

    // ===== MODALES DE ÓRDENES =====
    function verOrdenComparar(ordenId) {
        document.getElementById(`ver-menu-${ordenId}`).style.display = 'none';
        abrirModalComparar(ordenId);
    }

    function cerrarModalVerOrden() {
        document.getElementById('modalVerOrden').style.display = 'none';
    }

    function abrirModalAnulacion(ordenId, numeroOrden) {
        document.getElementById('ordenNumero').textContent = '#' + numeroOrden;
        document.getElementById('formAnulacion').dataset.ordenId = ordenId;
        document.getElementById('motivoAnulacion').value = '';
        document.getElementById('contadorActual').textContent = '0';
        document.getElementById('modalAnulacion').style.display = 'flex';
    }

    function cerrarModalAnulacion() {
        document.getElementById('modalAnulacion').style.display = 'none';
    }

    function confirmarAnulacion(event) {
        event.preventDefault();
        
        const ordenId = document.getElementById('formAnulacion').dataset.ordenId;
        const motivo = document.getElementById('motivoAnulacion').value;

        fetch(`/supervisor-pedidos/${ordenId}/anular`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                motivo_anulacion: motivo,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Orden anulada correctamente');
                // Recargar notificaciones si la función existe
                if (typeof cargarNotificacionesPendientes === 'function') {
                    cargarNotificacionesPendientes();
                }
                // Cerrar modal y recargar después de 1 segundo
                cerrarModalAnulacion();
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al anular la orden');
        });
    }

    // Contador de caracteres
    document.getElementById('motivoAnulacion')?.addEventListener('input', function() {
        document.getElementById('contadorActual').textContent = this.value.length;
        const btnConfirmar = document.getElementById('btnConfirmarAnulacion');
        if (btnConfirmar) {
            btnConfirmar.disabled = this.value.length < 10 || this.value.length > 500;
        }
    });

    // Cerrar modales al hacer clic fuera
    document.getElementById('modalVerOrden')?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalVerOrden();
    });

    document.getElementById('modalAnulacion')?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalAnulacion();
    });

    // Función para aprobar orden
    function aprobarOrden(ordenId, numeroOrden) {
        if (!confirm(`¿Confirmar aprobación de orden #${numeroOrden}?`)) {
            return;
        }

        fetch(`/supervisor-pedidos/${ordenId}/aprobar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({}),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Orden aprobada correctamente');
                // Recargar notificaciones si la función existe
                if (typeof cargarNotificacionesPendientes === 'function') {
                    cargarNotificacionesPendientes();
                }
                // Recargar la página después de 1 segundo
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al aprobar la orden');
        });
    }

    // Función para ver detalles de orden (orden-detail-modal)
    // Cierra el menú y abre el modal de detalles
    function verOrdenDetalles(ordenId) {
        // Cerrar el menú ver
        const menu = document.getElementById(`ver-menu-${ordenId}`);
        if (menu) {
            menu.style.display = 'none';
        }
        
        // Abrir el modal de detalles usando la función externa
        openOrderDetailModal(ordenId);
    }

    // Función para abrir el seguimiento
    function abrirSeguimiento(ordenId) {
        // Cerrar el menú ver
        const menu = document.getElementById(`ver-menu-${ordenId}`);
        if (menu) {
            menu.style.display = 'none';
        }
        
        // Abrir el modal de seguimiento usando la función externa
        if (typeof openOrderTrackingModal === 'function') {
            openOrderTrackingModal(ordenId);
        }
    }
</script>

<!-- Modal Overlay y Wrapper para Detalles de Orden -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 90vw; position: fixed; top: 55%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

<!-- Modal Wrapper para Detalles de Orden - LOGO -->
<div id="order-detail-modal-wrapper-logo" style="width: 90%; max-width: 90vw; position: fixed; top: 55%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal-logo />
</div>

<!-- Modal Comparar Pedido y Cotización -->
<x-supervisor-pedidos.modal-comparar-pedido />

<!-- Modal Seguimiento del Pedido -->
<x-orders-components.order-tracking-modal />

@push('scripts')
    <script src="{{ asset('js/supervisor-pedidos/supervisor-pedidos-detail-modal.js') }}"></script>
    <script src="{{ asset('js/supervisor-pedidos/edit-pedido.js') }}"></script>
@endpush

@endsection
