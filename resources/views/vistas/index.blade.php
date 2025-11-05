@extends('layouts.app')

@section('content')
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
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
        @if($registros->isEmpty())
                <div class="no-data">
                    <h3>No hay registros disponibles</h3>
                    <p>No se encontraron registros para mostrar.</p>
                </div>
            @else
                @if($tipo === 'corte')
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
                        @php
                            $groupedRegistros = $registros->groupBy(function($item) {
                                return $item->pedido . '-' . $item->cliente;
                            });
                        @endphp

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
                                                } else {
                                                    $registro = \App\Models\TablaOriginal::where('pedido', $pedido)->first();
                                                }
                                                if ($registro && isset($registro->encargados_de_corte)) {
                                                    $encargado = $registro->encargados_de_corte;
                                                }
                                            @endphp
                                            {{ $encargado }}
                                        </span>
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
                                                <tr>
                                                    <td class="prenda-cell cell-clickable" data-content="{{ $registro->prenda ?: '-' }}">{{ $registro->prenda ?: '-' }}</td>
                                                    <td class="descripcion-cell cell-clickable" data-content="{{ $registro->descripcion ?: '-' }}">{{ $registro->descripcion ?: '-' }}</td>
                                                    <td class="talla-cell">{{ $registro->talla ?: '-' }}</td>
                                                    <td class="cantidad-cell">{{ $registro->cantidad ?: '-' }}</td>
                                                    <td class="costurero-cell cell-clickable" data-content="{{ $registro->costurero ?: '-' }}">{{ $registro->costurero ?: '-' }}</td>
                                                    <td class="total_producido_por_talla-cell">{{ $registro->total_producido_por_talla ?: '-' }}</td>
                                                    <td class="total_pendiente_por_talla-cell">{{ $registro->total_pendiente_por_talla ?: '-' }}</td>
                                                    <td class="fecha_completado-cell">
                                                        @if($registro->fecha_completado)
                                                            {{ \Carbon\Carbon::parse($registro->fecha_completado)->format('d/m/Y') }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="table-pagination" id="tablePagination">
                    <div class="pagination-info">
                        <span id="paginationInfo">Mostrando {{ $registros->firstItem() }}-{{ $registros->lastItem() }} de {{ $registros->total() }} registros</span>
                    </div>
                    <div class="pagination-controls" id="paginationControls">
                        @if($registros->hasPages())
                            <button class="pagination-btn" data-page="1" {{ $registros->currentPage() == 1 ? 'disabled' : '' }}>
                                <i class="fas fa-angle-double-left"></i>
                            </button>
                            <button class="pagination-btn" data-page="{{ $registros->currentPage() - 1 }}" {{ $registros->currentPage() == 1 ? 'disabled' : '' }}>
                                <i class="fas fa-angle-left"></i>
                            </button>
                            
                            @php
                                $start = max(1, $registros->currentPage() - 2);
                                $end = min($registros->lastPage(), $registros->currentPage() + 2);
                            @endphp
                            
                            @for($i = $start; $i <= $end; $i++)
                                <button class="pagination-btn page-number {{ $i == $registros->currentPage() ? 'active' : '' }}" data-page="{{ $i }}">
                                    {{ $i }}
                                </button>
                            @endfor
                            
                            <button class="pagination-btn" data-page="{{ $registros->currentPage() + 1 }}" {{ $registros->currentPage() == $registros->lastPage() ? 'disabled' : '' }}>
                                <i class="fas fa-angle-right"></i>
                            </button>
                            <button class="pagination-btn" data-page="{{ $registros->lastPage() }}" {{ $registros->currentPage() == $registros->lastPage() ? 'disabled' : '' }}>
                                <i class="fas fa-angle-double-right"></i>
                            </button>
                        @endif
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
            searchInput.addEventListener('input', function() {
                const query = this.value;
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    performAjaxSearch(query);
                }, 300);

                clearSearch.style.display = query ? 'block' : 'none';
            });

            // Event listener para limpiar búsqueda
            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                clearSearch.style.display = 'none';
                performAjaxSearch('');
            });

            // Función para búsqueda AJAX
            function performAjaxSearch(query) {
                const resultsContainer = document.getElementById('results-container');
                const paginationInfo = document.getElementById('paginationInfo');

                resultsContainer.innerHTML = '<div class="no-data"><h3>Buscando...</h3></div>';

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
                    resultsContainer.innerHTML = data.html;

                    if (paginationInfo) {
                        paginationInfo.textContent = data.info;
                    }

                    const paginationContainer = document.getElementById('paginationControls');
                    if (paginationContainer && data.pagination) {
                        paginationContainer.innerHTML = data.pagination;
                    }
                })
                .catch(error => {
                    console.error('Error en búsqueda:', error);
                    resultsContainer.innerHTML = '<div class="no-data"><h3>Error en la búsqueda</h3><p>Por favor, intenta de nuevo.</p></div>';
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
                console.error('Error al cargar página:', error);
                resultsContainer.style.opacity = '1';
                resultsContainer.style.pointerEvents = 'auto';
                isLoadingPagination = false;
            });
        });
    </script>
@endsection
