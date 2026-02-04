@extends('layouts.app')

@section('title', 'Panel de Control - Mundo Industrial')

@section('meta')
    <meta name="description" content="Panel de control de costura y producción de Mundo Industrial. Gestiona órdenes, seguimiento de prendas y control de calidad en tiempo real.">
    <meta name="keywords" content="costura, producción, órdenes, prendas, gestión">
    <meta property="og:title" content="Panel de Control - Mundo Industrial">
    <meta property="og:description" content="Plataforma de gestión de producción textil con seguimiento en tiempo real">
@endsection

@section('content')
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Material Symbols -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
    <link rel="stylesheet" href="{{ asset('css/vista-costura.css') }}">

    <div class="costura-container">
        <div class="costura-header">
            <h1>{{ $title }}</h1>
        </div>

        <!-- Barra de búsqueda -->
        <div class="search-container">
            <div class="search-bar">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" placeholder="Buscar por pedido o cliente..." value="{{ $query }}" autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search-btn" style="display: {{ $query ? 'block' : 'none' }};">
                    <i class="fas fa-times"></i>
                </button>
            </div>

        </div>

        <div id="results-container">
        @php
            $isEmpty = false;
            if ($tipo === 'pedidos') {
                $isEmpty = $groupedRegistros->isEmpty();
            } else {
                $isEmpty = $registros->isEmpty();
            }
        @endphp
        @if($isEmpty)
                <div class="no-data">
                    <h3>No hay registros disponibles</h3>
                    <p>No se encontraron registros para mostrar.</p>
                </div>
            @else
                @if($tipo === 'corte' || $tipo === 'bodega')
                    <div class="cards-container">
                        @php
                            $groupedRegistros = $registros->groupBy('pedido');
                        @endphp

                        @foreach($groupedRegistros as $pedido => $groupRegistros)
                            <div class="pedido-card">
                                <div class="card-header">
                                    <h3>{{ $pedido ?: '-' }}</h3>
                                </div>
                                <div class="card-body">
                                    <table class="card-table">
                                        <thead>
                                            <tr>
                                                <th>Prenda</th>
                                                <th>Cortador</th>
                                                <th>Cantidad Prendas</th>
                                                <th>Piezas</th>
                                                <th>Pasadas</th>
                                                <th>Etiquetadas</th>
                                                <th>Etiquetador</th>
                                                <th>Fecha Entrega</th>
                                                <th>Mes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($groupRegistros as $registro)
                                                <tr>
                                                    <td class="prenda-cell cell-clickable" data-content="{{ $registro->prenda ?: '-' }}">{{ $registro->prenda ?: '-' }}</td>
                                                    <td class="cortador-cell">{{ $registro->cortador ?: '-' }}</td>
                                                    <td class="cantidad_prendas-cell">{{ $registro->cantidad_prendas ?: '-' }}</td>
                                                    <td class="piezas-cell">{{ $registro->piezas ?: '-' }}</td>
                                                    <td class="pasadas-cell">{{ $registro->pasadas ?: '-' }}</td>
                                                    <td class="etiqueteadas-cell">{{ $registro->etiqueteadas ?: '-' }}</td>
                                                    <td class="etiquetador-cell">{{ $registro->etiquetador ?: '-' }}</td>
                                                    <td class="fecha_entrega-cell">
                                                        @if($registro->fecha_entrega)
                                                            {{ \Carbon\Carbon::parse($registro->fecha_entrega)->format('d/m/Y') }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="mes-cell">{{ $registro->mes ?: '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="cards-container">
                        @foreach($groupedRegistros as $groupKey => $groupRegistros)
                            @php
                                $pedidoCliente = explode('-', $groupKey);
                                $pedido = $pedidoCliente[0];
                                $cliente = $pedidoCliente[1];
                            @endphp

                            <div class="pedido-card">
                                <div class="card-header">
                                    <h3>{{ $pedido ?: '-' }} - {{ $cliente ?: '-' }}</h3>
                                    <div class="encargado-corte">
                                        <span class="encargado-label">Encargado de Corte:</span>
                                        <span class="encargado-value">
                                            @php
                                                $encargado = '-';
                                                if ($tipo === 'bodega') {
                                                    $registro = \App\Models\TablaOriginalBodega::where('pedido', $pedido)->first();
                                                    if ($registro && isset($registro->encargados_de_corte)) {
                                                        $encargado = $registro->encargados_de_corte;
                                                    }
                                                }
                                                // Para Costura - Pedidos (tipo === 'pedidos'), no buscar en tabla_original
                                            @endphp
                                            {{ $encargado }}
                                        </span>
                                        <button class="btn-toggle-edit" data-card-id="{{ $pedido }}-{{ $cliente }}" title="Activar edición">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <table class="card-table">
                                        <thead>
                                            <tr>
                                                <th>Prenda</th>
                                                <th>Descripción</th>
                                                <th>Talla</th>
                                                <th>Cantidad</th>
                                                <th>Costurero</th>
                                                <th>Total Producido</th>
                                                <th>Total Pendiente</th>
                                                <th>Fecha Completado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($groupRegistros as $registro)
                                                @if($tipo === 'pedidos')
                                                    {{-- Para prendas_pedido: cargar tallas desde prenda_pedido_tallas --}}
                                                    @php
                                                        $tallaRecords = $registro->tallas ?? [];
                                                    @endphp
                                                    @if($tallaRecords && count($tallaRecords) > 0)
                                                        @foreach($tallaRecords as $tallaRecord)
                                                            @php
                                                                // Build cantidadTalla object for this prenda
                                                                $cantidadTalla = [];
                                                                foreach($tallaRecords as $t) {
                                                                    $cantidadTalla[$t->talla] = $t->cantidad;
                                                                }
                                                                
                                                                // Obtener primera variante
                                                                $primerVariante = $registro->variantes && count($registro->variantes) > 0 ? $registro->variantes[0] : null;
                                                                
                                                                // Buscar entrega_prenda_pedido relacionada
                                                                $entrega = \App\Models\EntregaPrendaPedido::where('numero_pedido', $registro->pedido->numero_pedido ?? 'P-' . $registro->pedido->id)
                                                                    ->where('nombre_prenda', $registro->nombre_prenda)
                                                                    ->where('talla', $tallaRecord->talla)
                                                                    ->first();
                                                                
                                                                $costurero = $entrega ? ($entrega->costurero ?: '-') : '-';
                                                                $totalProducido = $entrega ? $entrega->total_producido_por_talla : '-';
                                                                $totalPendiente = $entrega ? $entrega->total_pendiente_por_talla : '-';
                                                                $fechaCompletado = $entrega && $entrega->fecha_completado ? $entrega->fecha_completado->format('d/m/Y') : '-';
                                                            @endphp
                                                            <tr data-id="{{ $registro->id }}" data-tipo="{{ $tipo }}" data-talla="{{ $tallaRecord->talla }}">
                                                                <td class="prenda-cell cell-clickable" data-content="{{ $registro->nombre_prenda ?: '-' }}">{{ $registro->nombre_prenda ?: '-' }}</td>
                                                                <td class="descripcion-cell cell-clickable descripcion-dinamica" 
                                                                    data-nombre="{{ $registro->nombre_prenda ?: '' }}"
                                                                    data-numero="1"
                                                                    data-descripcion="{{ $registro->descripcion ?: '' }}"
                                                                    data-color="{{ $registro->color?->nombre ?? '' }}"
                                                                    data-tela="{{ $registro->tela?->nombre ?? '' }}"
                                                                    data-ref="{{ $registro->ref ?? '' }}"
                                                                    data-genero="{{ $registro->genero ?? 'dama' }}"
                                                                    data-tipo-manga="{{ $primerVariante?->tipoManga?->nombre ?? '' }}"
                                                                    data-obs-manga="{{ $primerVariante?->manga_obs ?? '' }}"
                                                                    data-tipo-broche="{{ $primerVariante?->tipoBroche?->nombre ?? '' }}"
                                                                    data-obs-broche="{{ $primerVariante?->broche_boton_obs ?? '' }}"
                                                                    data-tiene-bolsillos="{{ $primerVariante?->tiene_bolsillos ? 'Sí' : '' }}"
                                                                    data-obs-bolsillos="{{ $primerVariante?->bolsillos_obs ?? '' }}"
                                                                    data-cantidad-talla="{{ json_encode($cantidadTalla) }}">
                                                                    {{ $registro->descripcion ?: '-' }}
                                                                </td>
                                                                <td class="talla-cell">{{ $tallaRecord->talla }}</td>
                                                                <td class="cantidad-cell editable" data-field="cantidad" data-value="{{ $tallaRecord->cantidad }}">{{ $tallaRecord->cantidad }}</td>
                                                                <td class="costurero-cell cell-clickable editable" data-field="costurero" data-content="{{ $costurero }}" data-value="{{ $entrega ? ($entrega->costurero ?? '') : '' }}">{{ $costurero }}</td>
                                                                <td class="total_producido_por_talla-cell editable" data-field="total_producido_por_talla" data-value="{{ $entrega ? $entrega->total_producido_por_talla : '' }}">{{ $totalProducido }}</td>
                                                                <td class="total_pendiente_por_talla-cell editable" data-field="total_pendiente_por_talla" data-value="{{ $entrega ? $entrega->total_pendiente_por_talla : '' }}">{{ $totalPendiente }}</td>
                                                                <td class="fecha_completado-cell">{{ $fechaCompletado }}</td>
                                                            </tr>
                                                        @endforeach
                                                    @else
                                                        <tr data-id="{{ $registro->id }}" data-tipo="{{ $tipo }}">
                                                            <td class="prenda-cell cell-clickable" data-content="{{ $registro->nombre_prenda ?: '-' }}">{{ $registro->nombre_prenda ?: '-' }}</td>
                                                            <td class="descripcion-cell cell-clickable" data-content="{{ $registro->descripcion ?: '-' }}">{{ $registro->descripcion ?: '-' }}</td>
                                                            <td class="talla-cell">-</td>
                                                            <td class="cantidad-cell editable" data-field="cantidad" data-value="">-</td>
                                                            <td class="costurero-cell cell-clickable editable" data-field="costurero" data-content="-" data-value="">-</td>
                                                            <td class="total_producido_por_talla-cell editable" data-field="total_producido_por_talla" data-value="">-</td>
                                                            <td class="total_pendiente_por_talla-cell editable" data-field="total_pendiente_por_talla" data-value="">-</td>
                                                            <td class="fecha_completado-cell">-</td>
                                                        </tr>
                                                    @endif
                                                @else
                                                    {{-- Para registros_por_orden (bodega): mostrar como antes --}}
                                                    <tr data-id="{{ $registro->id }}" data-tipo="{{ $tipo }}">
                                                        <td style="text-align: center; padding: 0.5rem;">
                                                            <button class="btn-ver-recibos" 
                                                                data-pedido-id="{{ $registro->id }}"
                                                                data-menu-id="menu-ver-{{ $registro->pedido }}"
                                                                data-pedido="{{ $registro->pedido }}"
                                                                title="Ver Recibos" 
                                                                style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; border: none; padding: 0.4rem 0.6rem; border-radius: 6px; cursor: pointer; font-size: 0.9rem; transition: all 0.3s ease; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;">
                                                                <i class="fas fa-receipt"></i>
                                                            </button>
                                                        </td>
                                                        <td class="prenda-cell cell-clickable" data-content="{{ $registro->prenda ?: '-' }}">{{ $registro->prenda ?: '-' }}</td>
                                                        <td class="descripcion-cell cell-clickable" data-content="{{ $registro->descripcion ?: '-' }}">{{ $registro->descripcion ?: '-' }}</td>
                                                        <td class="talla-cell">{{ $registro->talla ?: '-' }}</td>
                                                        <td class="cantidad-cell editable" data-field="cantidad" data-value="{{ $registro->cantidad ?? '' }}">{{ $registro->cantidad !== null && $registro->cantidad !== '' ? $registro->cantidad : '-' }}</td>
                                                        <td class="costurero-cell cell-clickable editable" data-field="costurero" data-content="{{ $registro->costurero ?: '-' }}" data-value="{{ $registro->costurero ?? '' }}">{{ $registro->costurero ?: '-' }}</td>
                                                        <td class="total_producido_por_talla-cell editable" data-field="total_producido_por_talla" data-value="{{ $registro->total_producido_por_talla ?? '' }}">{{ $registro->total_producido_por_talla !== null && $registro->total_producido_por_talla !== '' ? $registro->total_producido_por_talla : '-' }}</td>
                                                        <td class="total_pendiente_por_talla-cell editable" data-field="total_pendiente_por_talla" data-value="{{ $registro->total_pendiente_por_talla ?? '' }}">{{ $registro->total_pendiente_por_talla !== null && $registro->total_pendiente_por_talla !== '' ? $registro->total_pendiente_por_talla : '-' }}</td>
                                                        <td class="fecha_completado-cell">
                                                            @if($registro->fecha_completado)
                                                                {{ \Carbon\Carbon::parse($registro->fecha_completado)->format('d/m/Y') }}
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Paginación deshabilitada para vista de pedidos agrupados -->
                <div class="table-pagination" id="tablePagination" style="display: none;">
                    <div class="pagination-info">
                        <span id="paginationInfo">Registros cargados</span>
                    </div>
                </div>
            @endif
        </div>
    </div>







    <script>
        const tipoVista = '{{ $tipo }}';
        const origenVista = new URLSearchParams(window.location.search).get('origen') || 'pedido';
        let isLoadingPagination = false;

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const clearSearch = document.getElementById('clearSearch');

            // Event listener para búsqueda en tiempo real
            let searchTimeout;
            let lastQuery = '';
            
            searchInput.addEventListener('input', function() {
                const query = this.value;
                clearSearch.style.display = query ? 'block' : 'none';
                
                // Solo hacer búsqueda si el query cambió
                if (query === lastQuery) return;
                
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    lastQuery = query;
                    performAjaxSearch(query);
                }, 500);  // Aumentado a 500ms para permitir escritura más fluida
            });

            // Event listener para limpiar búsqueda
            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                lastQuery = '';
                clearSearch.style.display = 'none';
                performAjaxSearch('');
            });

            // Función para búsqueda AJAX
            function performAjaxSearch(query) {
                const resultsContainer = document.getElementById('results-container');
                const paginationInfo = document.getElementById('paginationInfo');

                // No reemplazar el container, solo mostrar loader
                const loader = '<div class="no-data"><h3>Buscando...</h3></div>';
                
                // Si existe un elemento results-content, actualizar solo eso
                const resultsContent = resultsContainer.querySelector('.results-content') || resultsContainer;
                resultsContent.innerHTML = loader;

                let url = '/api/vistas/search?q=' + encodeURIComponent(query) + '&tipo=' + encodeURIComponent(tipoVista) + '&origen=' + encodeURIComponent(origenVista);

                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    resultsContent.innerHTML = data.html;

                    if (paginationInfo) {
                        paginationInfo.textContent = data.info;
                    }

                    const paginationContainer = document.getElementById('paginationControls');
                    if (paginationContainer && data.pagination) {
                        paginationContainer.innerHTML = data.pagination;
                    }
                })
                .catch(error => {
                    resultsContent.innerHTML = '<div class="no-data"><h3>Error en la búsqueda</h3><p>Por favor, intenta de nuevo.</p></div>';
                });
            }
        });

        // AJAX Pagination - Event delegation en document.body (siempre funciona)
        document.body.addEventListener('click', function(e) {
            // Verificar si el click fue en un botón de paginación
            const btn = e.target.closest('.pagination-btn');
            
            // Si no es un botón de paginación o está en la búsqueda, salir
            if (!btn) return;
            
            // Verificar que sea de paginationControls
            const paginationControls = document.getElementById('paginationControls');
            if (!paginationControls || !paginationControls.contains(btn)) return;
            
            // Prevenir si está deshabilitado o cargando
            if (btn.disabled || isLoadingPagination) {
                e.preventDefault();
                return;
            }
            
            const page = btn.dataset.page;
            if (!page) return;
            
            e.preventDefault();
            isLoadingPagination = true;
            
            // Indicador de carga rápido
            const resultsContainer = document.getElementById('results-container');
            resultsContainer.style.transition = 'opacity 0.1s';
            resultsContainer.style.opacity = '0.3';
            resultsContainer.style.pointerEvents = 'none';
            
            // Construir URL con parámetros actuales
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            
            // Hacer petición AJAX
            fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // Parsear HTML de forma más eficiente
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Actualizar contenido de forma rápida
                const newResultsContainer = doc.getElementById('results-container');
                if (newResultsContainer) {
                    resultsContainer.innerHTML = newResultsContainer.innerHTML;
                }
                
                const newPaginationControls = doc.getElementById('paginationControls');
                const currentPaginationControls = document.getElementById('paginationControls');
                if (newPaginationControls && currentPaginationControls) {
                    currentPaginationControls.innerHTML = newPaginationControls.innerHTML;
                }
                
                const newPaginationInfo = doc.getElementById('paginationInfo');
                const paginationInfo = document.getElementById('paginationInfo');
                if (newPaginationInfo && paginationInfo) {
                    paginationInfo.innerHTML = newPaginationInfo.innerHTML;
                }
                
                // Actualizar URL
                window.history.pushState({}, '', url.toString());
                
                // Restaurar inmediatamente
                resultsContainer.style.opacity = '1';
                resultsContainer.style.pointerEvents = 'auto';
                isLoadingPagination = false;
                
                // Scroll instantáneo
                document.querySelector('.costura-container').scrollIntoView({ 
                    behavior: 'auto', 
                    block: 'start' 
                });
            })
            .catch(error => {
                resultsContainer.style.opacity = '1';
                resultsContainer.style.pointerEvents = 'auto';
                isLoadingPagination = false;
            });
        });

        // Toggle edit mode for cards
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-toggle-edit');
            if (!btn) return;

            const card = btn.closest('.pedido-card');
            const isEditing = card.classList.contains('edit-mode');

            if (isEditing) {
                // Desactivar modo edición
                card.classList.remove('edit-mode');
                btn.innerHTML = '<i class="fas fa-edit"></i> Editar';
                btn.classList.remove('active');
            } else {
                // Activar modo edición
                card.classList.add('edit-mode');
                btn.innerHTML = '<i class="fas fa-save"></i> Guardar';
                btn.classList.add('active');
            }
        });

        // Inline cell editing functionality
        document.body.addEventListener('dblclick', function(e) {
            const cell = e.target.closest('td.editable');
            if (!cell || cell.querySelector('input')) return;

            // Verificar si el card está en modo edición
            const card = cell.closest('.pedido-card');
            if (!card || !card.classList.contains('edit-mode')) {
                return; // No permitir edición si no está activado el modo edición
            }

            const currentValue = cell.dataset.value || '';
            const field = cell.dataset.field;
            const row = cell.closest('tr');
            const recordId = row.dataset.id;
            const tipo = row.dataset.tipo;

            // Create input element
            const input = document.createElement('input');
            input.type = 'text';
            input.value = currentValue;
            input.className = 'cell-edit-input';
            input.style.cssText = 'width: 100%; padding: 4px; border: 2px solid #4CAF50; border-radius: 4px; font-size: inherit; color: #000; background-color: #fff;';
            
            // Store original content
            const originalContent = cell.innerHTML;
            
            // Replace cell content with input
            cell.innerHTML = '';
            cell.appendChild(input);
            input.focus();
            input.select();

            // Function to save changes
            function saveChanges() {
                const newValue = input.value.trim();
                
                // Show loading state
                cell.innerHTML = '<span style="color: #999;">Guardando...</span>';
                
                // Send AJAX request to update
                fetch('/api/vistas/update-cell', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        id: recordId,
                        field: field,
                        value: newValue,
                        tipo: tipo
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cell with new value
                        cell.dataset.value = newValue;
                        cell.innerHTML = newValue !== '' && newValue !== null ? newValue : '-';
                        
                        // Show success feedback
                        cell.style.backgroundColor = '#d4edda';
                        setTimeout(() => {
                            cell.style.backgroundColor = '';
                        }, 1000);
                    } else {
                        // Restore original content on error
                        cell.innerHTML = originalContent;
                        alert('Error al guardar: ' + (data.message || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    cell.innerHTML = originalContent;
                    alert('Error al guardar los cambios');
                });
            }

            // Function to cancel editing
            function cancelEdit() {
                cell.innerHTML = originalContent;
            }

            // Variable to track if we should save
            let shouldSave = false;

            // Save on Enter key
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    shouldSave = true;
                    saveChanges();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    shouldSave = false;
                    cancelEdit();
                }
            });

            // Cancel on blur (click outside) - NO guardar
            input.addEventListener('blur', function() {
                setTimeout(() => {
                    if (!shouldSave) {
                        cancelEdit();
                    }
                }, 100);
            });
        });

        // Add visual feedback for editable cells
        const style = document.createElement('style');
        style.textContent = `
            .btn-toggle-edit {
                background: #3B82F6;
                color: white;
                border: none;
                padding: 6px 12px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
                margin-left: 15px;
                transition: all 0.3s ease;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            .btn-toggle-edit:hover {
                background: #f57c00;
                transform: translateY(-1px);
                box-shadow: 0 2px 8px rgba(255, 152, 0, 0.3);
            }
            .btn-toggle-edit.active {
                background: #4CAF50;
            }
            .btn-toggle-edit.active:hover {
                background: #45a049;
            }
            .pedido-card.edit-mode {
                box-shadow: 0 0 0 2px #3B82F6;
            }
            .pedido-card:not(.edit-mode) td.editable {
                cursor: default;
            }
            .pedido-card.edit-mode td.editable {
                cursor: pointer;
                position: relative;
                transition: background-color 0.2s ease;
            }
            .pedido-card.edit-mode td.editable:hover {
                background-color: rgba(255, 152, 0, 0.15) !important;
                outline: 1px dashed #3B82F6;
            }
            .pedido-card.edit-mode td.editable:hover::after {
                content: '✎';
                position: absolute;
                right: 4px;
                top: 50%;
                transform: translateY(-50%);
                color: #3B82F6;
                font-size: 12px;
                opacity: 0.9;
            }
        `;
        document.head.appendChild(style);

        // ============================================================
        // CONSTRUIR DESCRIPCIONES DINÁMICAS
        // ============================================================
        let isConstructingDescripcion = false;
        
        function construirDescripcionDinamica() {
            // Evitar llamadas recursivas causadas por MutationObserver
            if (isConstructingDescripcion) {
                return;
            }
            
            isConstructingDescripcion = true;
            
            try {
                const celdas = document.querySelectorAll('.descripcion-dinamica');
                celdas.forEach((cell, idx) => {
                    const prendaData = {
                        nombre: cell.dataset.nombre || '',
                        numero: cell.dataset.numero || 1,
                        descripcion: cell.dataset.descripcion || '',
                        color: cell.dataset.color || '',
                        tela: cell.dataset.tela || '',
                        ref: cell.dataset.ref || '',
                        genero: cell.dataset.genero || 'dama'
                    };

                    // Debug en consola (solo primera celda)
                    if (idx === 0) {
                        console.log('🔍 [DESCRIPCION] Datos de la celda:', {
                            nombre: prendaData.nombre,
                            color: prendaData.color,
                            tela: prendaData.tela,
                            tipoManga: cell.dataset.tipoManga,
                            obsManga: cell.dataset.obsManga,
                            tipoBroche: cell.dataset.tipoBroche,
                            obsBroche: cell.dataset.obsBroche,
                            tieneBolsillos: cell.dataset.tieneBolsillos,
                            obsBolsillos: cell.dataset.obsBolsillos,
                            cantidadTalla: cell.dataset.cantidadTalla
                        });
                    }

                    // Construir variantes desde los datos disponibles
                    const variantes = [];
                    if (cell.dataset.tipoManga || cell.dataset.obsManga || cell.dataset.tieneBolsillos || cell.dataset.tipoBroche) {
                        variantes.push({
                            manga: cell.dataset.tipoManga || '',
                            manga_obs: cell.dataset.obsManga || '',
                            bolsillos_obs: cell.dataset.obsBolsillos || '',
                            broche: cell.dataset.tipoBroche || '',
                            broche_obs: cell.dataset.obsBroche || ''
                        });
                    }
                    prendaData.variantes = variantes;

                    // Parsear tallas
                    try {
                        const cantidadTallaJSON = cell.dataset.cantidadTalla || '{}';
                        prendaData.tallas = JSON.parse(cantidadTallaJSON);
                    } catch (e) {
                        prendaData.tallas = {};
                    }

                    // Construir HTML igual a como lo hace el recibo
                    const html = construirDescripcionFormato(prendaData);
                    cell.innerHTML = html;
                });
            } finally {
                isConstructingDescripcion = false;
            }
        }

        // Función para construir descripción con el formato del recibo
        function construirDescripcionFormato(prenda) {
            const lineas = [];

            // Línea técnica: TELA | COLOR | REF | MANGA
            const partes = [];
            if (prenda.tela) partes.push(`<strong>TELA:</strong> ${prenda.tela.toUpperCase()}`);
            if (prenda.color) partes.push(`<strong>COLOR:</strong> ${prenda.color.toUpperCase()}`);
            if (prenda.ref) partes.push(`<strong>REF:</strong> ${prenda.ref.toUpperCase()}`);
            
            // Manga desde variantes
            if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
                const primerVariante = prenda.variantes[0];
                if (primerVariante.manga) {
                    let mangaTexto = primerVariante.manga.toUpperCase();
                    if (primerVariante.manga_obs && primerVariante.manga_obs.trim()) {
                        mangaTexto += ` (${primerVariante.manga_obs.toUpperCase()})`;
                    }
                    partes.push(`<strong>MANGA:</strong> ${mangaTexto}`);
                }
            }
            
            if (partes.length > 0) {
                lineas.push(partes.join(' | '));
            }

            // Detalles técnicos: Bolsillos, Broche
            const detalles = [];
            if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
                const primerVariante = prenda.variantes[0];
                
                if (primerVariante.bolsillos_obs && primerVariante.bolsillos_obs.trim()) {
                    detalles.push(`• <strong>BOLSILLOS:</strong> ${primerVariante.bolsillos_obs.toUpperCase()}`);
                }
                
                if (primerVariante.broche_obs && primerVariante.broche_obs.trim()) {
                    let etiqueta = 'BROCHE/BOTÓN';
                    if (primerVariante.broche) {
                        etiqueta = primerVariante.broche.toUpperCase();
                    }
                    detalles.push(`• <strong>${etiqueta}:</strong> ${primerVariante.broche_obs.toUpperCase()}`);
                }
            }
            
            if (detalles.length > 0) {
                detalles.forEach((detalle) => {
                    lineas.push(detalle);
                });
            }

            return lineas.join('<br>') || '<em>Sin información</em>';
        }

        // Ejecutar al cargar la página
        document.addEventListener('DOMContentLoaded', construirDescripcionDinamica);

        // Ejecutar después de búsquedas AJAX (observar cambios en el DOM)
        // Solo reconstruir si hay nuevos elementos con la clase 'descripcion-dinamica'
        let lastObservedCount = 0;
        const observer = new MutationObserver(() => {
            const currentCount = document.querySelectorAll('.descripcion-dinamica').length;
            // Solo llamar si el número de celdas cambió (nuevas celdas agregadas)
            if (currentCount !== lastObservedCount) {
                lastObservedCount = currentCount;
                construirDescripcionDinamica();
            }
        });
        observer.observe(document.getElementById('results-container'), {
            childList: true,
            subtree: true
        });
    </script>

    <!-- Contenedor para dropdowns de recibos -->
    <div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

    <!-- Modal Overlay para recibos -->
    <div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none;" onclick="closeModalOverlay()"></div>

    <!-- Modal Selector de Recibos por Proceso -->
    @include('components.modals.recibos-process-selector')

    <!-- Script para módulo de recibos -->
    <script type="module">
        import { PedidosRecibosModule } from '/js/modulos/pedidos-recibos/index.js';
        
        // Exponer el módulo globalmente
        window.pedidosRecibosModule = new PedidosRecibosModule();
        
        // Función global para abrir modal de imagen grande (igual que en supervisor)
        window.abrirModalImagenProcesoGrande = (function() {
            let galleryManagerLoaded = false;
            let GalleryManager = null;
            
            return async function(indice, fotosJSON) {
                console.log('[GalleryManager] Intentando abrir imagen:', indice);
                
                // Si ya está cargado, usar directamente
                if (galleryManagerLoaded && GalleryManager) {
                    return GalleryManager.abrirModalImagenProcesoGrande(indice, fotosJSON);
                }
                
                try {
                    // Intentar cargar el módulo GalleryManager
                    console.log('[GalleryManager] Cargando módulo...');
                    
                    // Primero intentar con la ruta relativa
                    try {
                        const module = await import('./js/modulos/pedidos-recibos/components/GalleryManager.js');
                        GalleryManager = module.GalleryManager;
                        galleryManagerLoaded = true;
                        console.log('[GalleryManager] Módulo cargado correctamente');
                    } catch (importError) {
                        console.warn('[GalleryManager] Error con ruta relativa, intentando ruta absoluta:', importError);
                        // Si falla, intentar cargar como script global
                        if (typeof window.GalleryManager !== 'undefined') {
                            GalleryManager = window.GalleryManager;
                            galleryManagerLoaded = true;
                            console.log('[GalleryManager] Usando GalleryManager global');
                        } else {
                            throw new Error('No se pudo cargar GalleryManager');
                        }
                    }
                    
                    if (GalleryManager) {
                        return GalleryManager.abrirModalImagenProcesoGrande(indice, fotosJSON);
                    }
                } catch (err) {
                    console.error('[GalleryManager] Error cargando GalleryManager:', err);
                    galleryManagerLoaded = false;
                    
                    // Implementación fallback básica
                    console.log('[GalleryManager] Usando implementación fallback');
                    try {
                        let fotos = typeof fotosJSON === 'string' ? JSON.parse(fotosJSON) : fotosJSON;
                        if (!fotos || !fotos[indice]) {
                            console.error('Imagen no encontrada:', indice);
                            return;
                        }
                        
                        // Crear modal simple
                        const modal = document.createElement('div');
                        modal.style.cssText = `
                            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                            background: rgba(0,0,0,0.9); z-index: 9999; display: flex;
                            align-items: center; justify-content: center;
                        `;
                        modal.innerHTML = `
                            <div style="position: relative; max-width: 90%; max-height: 90%;">
                                <img src="${fotos[indice]}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                <button onclick="this.parentElement.parentElement.remove()" style="
                                    position: absolute; top: 10px; right: 10px;
                                    background: white; border: none; border-radius: 50%;
                                    width: 40px; height: 40px; cursor: pointer; font-size: 20px;
                                ">×</button>
                            </div>
                        `;
                        document.body.appendChild(modal);
                        modal.addEventListener('click', (e) => {
                            if (e.target === modal) modal.remove();
                        });
                    } catch (fallbackErr) {
                        console.error('[GalleryManager] Error en fallback:', fallbackErr);
                    }
                }
            };
        })();
        
        // Event listener para botones de recibos en registros
        document.addEventListener('click', function(e) {
            const btnRecibos = e.target.closest('.btn-ver-recibos');
            if (btnRecibos) {
                e.preventDefault();
                e.stopPropagation();
                
                const pedidoId = btnRecibos.dataset.pedidoId;
                console.log('[Registros - Ver Recibos] Botón clickeado para pedido:', pedidoId);
                
                // Abrir selector de recibos
                if (typeof abrirSelectorRecibos === 'function') {
                    abrirSelectorRecibos(pedidoId);
                } else {
                    console.error('[Registros - Ver Recibos] Función abrirSelectorRecibos no disponible');
                }
            }
        });
    </script>

    <!-- Script para dropdowns simple (compatibilidad) -->
    <script src="{{ asset('js/asesores/pedidos-dropdown-simple.js') }}"></script>
@endsection


