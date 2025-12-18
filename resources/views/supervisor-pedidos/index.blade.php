@extends('supervisor-pedidos.layout')

@section('title', 'Supervisión de Pedidos')
@section('page-title', 'Supervisión de Pedidos')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/edit-pedido.css') }}">
@endpush

@section('content')
<div class="supervisor-pedidos-container">
    <!-- Buscador General -->
    <div style="margin-bottom: 2rem;">
        <form method="GET" action="{{ route('supervisor-pedidos.index') }}" style="display: flex; gap: 1rem;">
            <!-- Preservar parámetros importantes -->
            @if(request('aprobacion'))
                <input type="hidden" name="aprobacion" value="{{ request('aprobacion') }}">
            @endif
            @if(request('estado'))
                <input type="hidden" name="estado" value="{{ request('estado') }}">
            @endif
            @if(request('asesora'))
                <input type="hidden" name="asesora" value="{{ request('asesora') }}">
            @endif
            @if(request('forma_pago'))
                <input type="hidden" name="forma_pago" value="{{ request('forma_pago') }}">
            @endif
            @if(request('fecha_desde'))
                <input type="hidden" name="fecha_desde" value="{{ request('fecha_desde') }}">
            @endif
            @if(request('fecha_hasta'))
                <input type="hidden" name="fecha_hasta" value="{{ request('fecha_hasta') }}">
            @endif
            
            <div style="flex: 1;">
                <input type="text" 
                       name="busqueda" 
                       id="busqueda" 
                       class="filtro-input" 
                       placeholder="Buscar por pedido o cliente..." 
                       value="{{ request('busqueda') }}"
                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem;">
            </div>
            <button type="submit" class="btn-filtrar" style="padding: 0.75rem 2rem; display: flex; align-items: center; gap: 0.5rem;">
                <span class="material-symbols-rounded">search</span>
                Buscar
            </button>
            <a href="{{ route('supervisor-pedidos.index', request('aprobacion') ? ['aprobacion' => request('aprobacion')] : []) }}" class="btn-limpiar" style="padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span class="material-symbols-rounded">clear</span>
                Limpiar
            </a>
        </form>
    </div>

    <!-- Filtros Rápidos -->
    <div class="filtros-rapidos-section">
        <span class="filtros-rapidos-label">Filtrar por estado:</span>
        <a href="{{ route('supervisor-pedidos.index', array_filter(array_merge(request()->query(), ['estado' => null]))) }}" class="btn-filtro-rapido {{ !request('estado') ? 'active' : '' }}">
            <span class="material-symbols-rounded">home</span>
            Todos
        </a>
        <a href="{{ route('supervisor-pedidos.index', array_merge(request()->query(), ['estado' => 'No iniciado'])) }}" class="btn-filtro-rapido {{ request('estado') === 'No iniciado' ? 'active' : '' }}">
            <span class="material-symbols-rounded">schedule</span>
            Pendientes
        </a>
        <a href="{{ route('supervisor-pedidos.index', array_merge(request()->query(), ['estado' => 'En Ejecución'])) }}" class="btn-filtro-rapido {{ request('estado') === 'En Ejecución' ? 'active' : '' }}">
            <span class="material-symbols-rounded">build</span>
            En ejecución
        </a>
        <a href="{{ route('supervisor-pedidos.index', array_merge(request()->query(), ['estado' => 'Entregado'])) }}" class="btn-filtro-rapido {{ request('estado') === 'Entregado' ? 'active' : '' }}">
            <span class="material-symbols-rounded">check_circle</span>
            Aprobados
        </a>
        <a href="{{ route('supervisor-pedidos.index', array_merge(request()->query(), ['estado' => 'Anulada'])) }}" class="btn-filtro-rapido {{ request('estado') === 'Anulada' ? 'active' : '' }}">
            <span class="material-symbols-rounded">cancel</span>
            Anulados
        </a>
    </div>

    <!-- Tabla de Órdenes -->
    <div class="tabla-section">
        <div class="tabla-header">
            <h2>Órdenes de Producción</h2>
            <span class="total-ordenes">Total: {{ $ordenes->total() }}</span>
        </div>

        @if($ordenes->count() > 0)
            <div class="tabla-responsive">
                <table class="tabla-ordenes">
                    <thead>
                        <tr>
                            <th>
                                <div class="th-wrapper">
                                    <span>ID ORDEN</span>
                                    <button type="button" class="btn-filter-column" onclick="abrirModalFiltro('id-orden')" title="Filtrar ID Orden">
                                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th>
                                <div class="th-wrapper">
                                    <span>CLIENTE</span>
                                    <button type="button" class="btn-filter-column" onclick="abrirModalFiltro('cliente')" title="Filtrar Cliente">
                                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th>
                                <div class="th-wrapper">
                                    <span>FECHA</span>
                                    <button type="button" class="btn-filter-column" onclick="abrirModalFiltro('fecha')" title="Filtrar Fecha">
                                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th>
                                <div class="th-wrapper">
                                    <span>ESTADO</span>
                                    <button type="button" class="btn-filter-column" onclick="abrirModalFiltro('estado')" title="Filtrar Estado">
                                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th>
                                <div class="th-wrapper">
                                    <span>ASESORA</span>
                                    <button type="button" class="btn-filter-column" onclick="abrirModalFiltro('asesora')" title="Filtrar Asesora">
                                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th>
                                <div class="th-wrapper">
                                    <span>FORMA PAGO</span>
                                    <button type="button" class="btn-filter-column" onclick="abrirModalFiltro('forma-pago')" title="Filtrar Forma Pago">
                                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th>
                                <div class="th-wrapper">
                                    <span>FECHA ESTIMADA</span>
                                    <button type="button" class="btn-filter-column" onclick="abrirModalFiltro('fecha-estimada')" title="Filtrar Fecha Estimada">
                                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th style="text-align: center; white-space: normal;">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ordenes as $orden)
                            <tr class="orden-row" data-orden-id="{{ $orden->id }}" data-estado="{{ $orden->estado }}">
                                <td class="id-orden">
                                    <strong>#{{ $orden->numero_pedido }}</strong>
                                </td>
                                <td class="cliente">{{ $orden->cliente }}</td>
                                <td class="fecha">{{ \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden)->format('d/m/Y') }}</td>
                                <td class="estado">
                                    <span class="badge badge-{{ strtolower(str_replace(' ', '-', $orden->estado)) }}">
                                        {{ $orden->estado }}
                                    </span>
                                </td>
                                <td class="asesora">{{ $orden->asesora?->name ?? 'N/A' }}</td>
                                <td class="forma-pago">{{ $orden->forma_de_pago ?? 'N/A' }}</td>
                                <td class="fecha-estimada" data-fecha-estimada="{{ $orden->fecha_estimada_de_entrega ? $orden->fecha_estimada_de_entrega->format('d/m/Y') : 'N/A' }}">
                                    {{ $orden->fecha_estimada_de_entrega ? $orden->fecha_estimada_de_entrega->format('d/m/Y') : 'N/A' }}
                                </td>
                                <td class="acciones">
                                    <div class="acciones-group">
                                        <!-- Ver Orden - Menu -->
                                        <div class="ver-menu-container">
                                            <button class="btn-accion btn-ver" 
                                                    title="Ver orden"
                                                    onclick="toggleVerMenu(event, {{ $orden->id }})">
                                                <span class="material-symbols-rounded">visibility</span>
                                            </button>
                                            <div class="ver-submenu" id="ver-menu-{{ $orden->id }}" style="display: none;">
                                                <button class="submenu-item" onclick="verOrdenDetalles({{ $orden->id }})">
                                                    <span class="material-symbols-rounded">description</span>
                                                    Detalles
                                                </button>
                                                <button class="submenu-item" onclick="abrirSeguimiento({{ $orden->id }})">
                                                    <span class="material-symbols-rounded">local_shipping</span>
                                                    Seguimiento
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Aprobar Orden (Enviar a Producción) -->
                                        @if($orden->estado === 'PENDIENTE_SUPERVISOR' && !$orden->aprobado_por_supervisor_en && (request('aprobacion') === 'pendiente' || !request()->filled('estado')))
                                            <button class="btn-accion btn-aprobar" 
                                                    title="Aprobar orden"
                                                    onclick="aprobarOrden({{ $orden->id }}, '{{ $orden->numero_pedido }}')">
                                                <span class="material-symbols-rounded">check_circle</span>
                                            </button>
                                        @endif

                                        <!-- Editar Orden -->
                                        <button class="btn-accion btn-editar" 
                                                title="Editar orden"
                                                onclick="abrirModalEditar({{ $orden->id }}, '{{ $orden->numero_pedido }}')">
                                            <span class="material-symbols-rounded">edit</span>
                                        </button>

                                        <!-- Anular Orden -->
                                        @if($orden->estado !== 'Anulada' && !$orden->aprobado_por_supervisor_en && (request('aprobacion') !== 'pendiente' && !request()->filled('estado')))
                                            <button class="btn-accion btn-anular" 
                                                    title="Anular orden"
                                                    onclick="abrirModalAnulacion({{ $orden->id }}, '{{ $orden->numero_pedido }}')">
                                                <span class="material-symbols-rounded">cancel</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="paginacion">
                {{ $ordenes->links('components.pagination') }}
            </div>
        @else
            <div class="sin-datos">
                <span class="material-symbols-rounded">inbox</span>
                <p>No hay órdenes que mostrar</p>
            </div>
        @endif
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

<!-- Modal Comparar Pedido y Cotización -->
<x-supervisor-pedidos.modal-comparar-pedido />

<!-- Modal Seguimiento del Pedido -->
<x-orders-components.order-tracking-modal />

@push('scripts')
    <script src="{{ asset('js/supervisor-pedidos/supervisor-pedidos-detail-modal.js') }}"></script>
    <script src="{{ asset('js/supervisor-pedidos/edit-pedido.js') }}"></script>
@endpush

@endsection
