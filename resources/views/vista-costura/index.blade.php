@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/vista-costura.css') }}">

    <div class="costura-container">
        <div class="costura-header">
            <h1>
                <i class="{{ $icon }}"></i>
                {{ $title }}
            </h1>
        </div>

        <!-- Barra de búsqueda -->
        <div class="search-container">
            <div class="search-bar">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" placeholder="Buscar por número de pedido..." value="{{ $query }}" autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search-btn" style="display: {{ $query ? 'block' : 'none' }};">
                    <i class="fas fa-times"></i>
                </button>
            </div>

        </div>

        <div id="results-container">
            @if($registros->isEmpty())
                <div class="no-data">
                    <h3>No hay registros disponibles</h3>
                    <p>No se encontraron registros de costura para mostrar.</p>
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

                <div class="pagination-container">
                    <div class="pagination-info" id="pagination-info">
                        Mostrando {{ $registros->firstItem() }}-{{ $registros->lastItem() }} de {{ $registros->total() }} registros
                    </div>
                </div>

                @if($registros->hasPages())
                    <div id="pagination-container" style="display: flex; justify-content: center; margin-top: 20px;">
                        {{ $registros->appends(request()->query())->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>







    <script>
        const tipoVista = '{{ $tipo }}';

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const clearSearch = document.getElementById('clearSearch');



            // Función para búsqueda AJAX en tiempo real
            function performAjaxSearch(query) {
                const resultsContainer = document.getElementById('results-container');
                const paginationInfo = document.getElementById('pagination-info');

                // Mostrar indicador de carga
                resultsContainer.innerHTML = '<div class="no-data"><h3>Buscando...</h3></div>';

                // Construir URL con parámetros
                let url = '/api/vista-costura/search?q=' + encodeURIComponent(query) + '&tipo=' + encodeURIComponent(tipoVista);

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

                    // Actualizar información de paginación
                    if (paginationInfo) {
                        paginationInfo.textContent = data.info;
                    }

                    // Actualizar paginación si existe
                    const paginationContainer = document.getElementById('pagination-container');
                    if (paginationContainer && data.pagination) {
                        paginationContainer.innerHTML = data.pagination;
                    }
                })
                .catch(error => {
                    console.error('Error en búsqueda:', error);
                    resultsContainer.innerHTML = '<div class="no-data"><h3>Error en la búsqueda</h3><p>Por favor, intenta de nuevo.</p></div>';
                });
            }

            // Event listener para búsqueda en tiempo real
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                const query = this.value;
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    performAjaxSearch(query);
                }, 300); // Esperar 300ms después de que el usuario deje de escribir

                // Mostrar/ocultar botón de limpiar
                clearSearch.style.display = query ? 'block' : 'none';
            });

            // Event listener para limpiar búsqueda
            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                clearSearch.style.display = 'none';
                performAjaxSearch('');
            });
        });
    </script>
@endsection
