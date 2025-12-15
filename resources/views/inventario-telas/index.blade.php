{{-- Vista General de Inventario de Telas - Usable en cualquier layout --}}

<link rel="stylesheet" href="{{ asset('css/inventario-telas/inventario.css') }}">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="inventario-telas-container">
    <!-- Barra de Acciones -->
    <div class="list-header">
        @if(auth()->user()->hasRole('insumos'))
        <button type="button" class="btn btn-create" onclick="abrirModalCrearTela()">
            <span class="material-symbols-rounded">add_circle</span>
            Nueva Tela
        </button>
        <button type="button" class="btn btn-historial" onclick="abrirModalHistorial()">
            <span class="material-symbols-rounded">analytics</span>
            Historial y Estadísticas
        </button>
        @endif
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Buscar por nombre o categoría...">
        </div>
        
        <div class="filter-group">
            <select id="filterCategoria" class="filter-select">
                <option value="">Todas las Categorías</option>
                @foreach($telas->unique('categoria')->pluck('categoria')->sort() as $categoria)
                    <option value="{{ $categoria }}">{{ $categoria }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Tabla de Inventario -->
    <div class="table-container">
        @if($telas->count() > 0)
            <table class="inventario-table">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Nombre de Tela</th>
                        <th>Stock</th>
                        <th>Metraje Sugerido</th>
                        <th>Fecha de Registro</th>
                        @if(auth()->user()->hasRole('insumos'))
                        <th>Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="telasTableBody">
                    @foreach($telas as $tela)
                        <tr data-categoria="{{ $tela->categoria }}" data-nombre="{{ $tela->nombre_tela }}" data-tela-id="{{ $tela->id }}">
                            <td class="categoria-cell">{{ $tela->categoria }}</td>
                            <td class="nombre-tela">{{ $tela->nombre_tela }}</td>
                            <td>
                                <span class="stock-badge {{ $tela->stock < 10 ? 'stock-bajo' : ($tela->stock < 50 ? 'stock-medio' : 'stock-alto') }}">
                                    {{ $tela->stock == floor($tela->stock) ? number_format($tela->stock, 0) : number_format($tela->stock, 2) }} m
                                </span>
                            </td>
                            <td class="metraje-cell">
                                {{ $tela->metraje_sugerido ? ($tela->metraje_sugerido == floor($tela->metraje_sugerido) ? number_format($tela->metraje_sugerido, 0) : number_format($tela->metraje_sugerido, 2)) . ' m' : '-' }}
                            </td>
                            <td class="fecha-cell">{{ $tela->fecha_registro ? \Carbon\Carbon::parse($tela->fecha_registro)->format('d/m/Y h:i A') : '-' }}</td>
                            @if(auth()->user()->hasRole('insumos'))
                            <td class="actions-cell">
                                <button type="button" 
                                        class="btn-action btn-adjust" 
                                        onclick="abrirModalAjustarStock({{ $tela->id }}, '{{ $tela->nombre_tela }}', {{ $tela->stock }})"
                                        title="Ajustar Stock">
                                    <span class="material-symbols-rounded">tune</span>
                                </button>
                                <button type="button" 
                                        class="btn-action btn-delete" 
                                        onclick="eliminarTela({{ $tela->id }}, '{{ $tela->nombre_tela }}')"
                                        title="Eliminar Tela">
                                    <span class="material-symbols-rounded">delete</span>
                                </button>
                            </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>No hay telas en el inventario</h3>
                <p>Aún no se han registrado telas en el sistema.</p>
            </div>
        @endif
    </div>
</div>

<!-- Incluir Modales - Desde carpeta de asesores -->
@include('asesores.componentes.modal-ajustar-stock')
@include('asesores.componentes.modal-crear-tela', ['categorias' => $telas->unique('categoria')->pluck('categoria')->sort()])
@include('asesores.componentes.modal-historial-telas')

<!-- Script de Inventario de Telas -->
<script src="{{ asset('js/inventario-telas/inventario.js') }}"></script>
