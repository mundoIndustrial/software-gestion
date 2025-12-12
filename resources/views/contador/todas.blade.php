@extends('layouts.contador')

@section('content')
@push('styles')
    <style>
        /* ====================== ESTILOS GENERALES ====================== */
        :root {
            --primary-color: #1e5ba8;
            --primary-hover: #1e40af;
            --secondary-color: #ecf0f1;
            --danger-color: #ef4444;
            --success-color: #10b981;
            --warning-color: #f39c12;
            --light-bg: #f5f7fa;
            --light-gray: #f8f9fa;
            --border-color: #e0e6ed;
            --text-primary: #2c3e50;
            --text-secondary: #7f8c8d;
            --radius: 8px;
            --transition: all 0.3s ease;
        }

        /* ====================== TABLA ====================== */
        .table-container {
            width: 95%;
            max-width: 1400px;
            background: white;
            border-radius: 12px;
            overflow: visible;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 0 auto 1.5rem auto;
            padding: 1.5rem;
            box-sizing: border-box;
            border: 1px solid #e0e6ed;
        }

        .table-header {
            padding: 1.25rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
        }

        .table-header h2 {
            margin: 0 0 1rem 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .search-bar {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.95rem;
            transition: var(--transition);
            box-sizing: border-box;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(30, 91, 168, 0.1);
        }

        .btn-secondary-clear {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
            background: #6b7280;
            color: white;
        }

        .btn-secondary-clear:hover {
            background: #4b5563;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background: var(--light-gray);
            border-bottom: 2px solid var(--border-color);
        }

        table th {
            padding: 0.75rem 0.875rem;
            text-align: left;
            font-weight: 700;
            font-size: 0.75rem;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: background 0.3s ease;
        }

        table tbody tr:hover {
            background: var(--light-gray);
        }

        table td {
            padding: 0.75rem 0.875rem;
            font-size: 0.875rem;
            color: var(--text-primary);
        }

        table td:last-child {
            padding-right: 1.5rem;
        }

        /* Ajustar ancho de columnas específicas */
        table th:nth-child(5),
        table td:nth-child(5) {
            width: 140px;
            white-space: nowrap;
        }

        table th:nth-child(6),
        table td:nth-child(6) {
            width: 80px;
            text-align: center;
        }

        /* ====================== ACCIONES ====================== */
        .actions-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            justify-content: center;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1.2rem;
        }

        .btn-view {
            background: #e8f4f8;
            color: var(--primary-color);
        }

        .btn-view:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        /* ====================== DROPDOWN MENU ====================== */
        .view-dropdown {
            position: fixed;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            min-width: 180px;
            animation: slideDown 0.2s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .view-dropdown button {
            width: 100%;
            padding: 0.75rem 1rem;
            text-align: left;
            border: none;
            background: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .view-dropdown button:hover {
            background: var(--light-gray);
            color: var(--primary-color);
        }

        .view-dropdown button:not(:last-child) {
            border-bottom: 1px solid var(--border-color);
        }

        .view-dropdown button:first-child {
            border-radius: 6px 6px 0 0;
        }

        .view-dropdown button:last-child {
            border-radius: 0 0 6px 6px;
        }

        /* ====================== PAGINACIÓN ====================== */
        .paginacion {
            padding: 2rem 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .pagination-wrapper {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        /* Estilos para el componente de paginación personalizado */
        .pagination {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin: 0;
            padding: 0;
            list-style: none;
            flex-wrap: wrap;
            justify-content: center;
        }

        .pagination .page-item {
            display: inline-block;
        }

        .pagination .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            text-decoration: none;
            color: var(--text-primary);
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            background: white;
        }

        .pagination .page-link .material-symbols-rounded {
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pagination .page-item:not(.disabled) .page-link:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(30, 91, 168, 0.2);
        }

        .pagination .page-item:first-child .page-link,
        .pagination .page-item:last-child .page-link {
            font-weight: 600;
        }

        .pagination .page-item.active .page-link {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            font-weight: 600;
            cursor: default;
        }

        .pagination .page-item.disabled .page-link {
            color: #bdc3c7;
            cursor: not-allowed;
            opacity: 0.5;
            background: #f8f9fa;
        }

        .pagination .page-item.disabled .page-link:hover {
            background: #f8f9fa;
            border-color: var(--border-color);
            transform: none;
            box-shadow: none;
        }

        .pagination-info {
            text-align: center;
            margin-top: 1rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* ====================== EMPTY STATE ====================== */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .empty-state .material-symbols-rounded {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* ====================== RESPONSIVE ====================== */
        @media (max-width: 1024px) {
            .table-container {
                padding: 0 0.5rem;
            }

            table th,
            table td {
                padding: 0.6rem 0.5rem;
                font-size: 0.8rem;
            }

            .btn-action {
                width: 32px;
                height: 32px;
                font-size: 0.9rem;
            }

            .actions-group {
                gap: 0.25rem;
            }
        }

        @media (max-width: 768px) {
            .table-container {
                padding: 0;
                margin: 0 0 1.5rem 0;
                border-radius: 0;
            }

            .table-header {
                padding: 1rem;
            }

            .table-header h2 {
                font-size: 1.1rem;
                margin-bottom: 0.75rem;
            }

            .search-bar {
                flex-direction: column;
            }

            .search-input {
                width: 100%;
                min-width: unset;
            }

            .btn-secondary-clear {
                width: 100%;
            }

            table {
                font-size: 0.75rem;
            }

            table th,
            table td {
                padding: 0.5rem 0.4rem;
            }

            table th {
                font-size: 0.7rem;
            }

            .actions-group {
                flex-wrap: wrap;
                justify-content: center;
            }

            .btn-action {
                width: 30px;
                height: 30px;
                font-size: 0.85rem;
            }

            .paginacion {
                padding: 1rem;
            }

            .pagination {
                gap: 0.2rem;
            }

            .pagination .page-link {
                min-width: 32px;
                height: 32px;
                padding: 0.3rem 0.5rem;
                font-size: 0.75rem;
            }

            .pagination .page-link .material-symbols-rounded {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .table-container {
                padding: 0;
                margin: 0 0 1.5rem 0;
            }

            .table-header {
                padding: 0.75rem;
            }

            .table-header h2 {
                font-size: 1rem;
                margin-bottom: 0.5rem;
            }

            .search-input {
                font-size: 0.85rem;
                padding: 0.6rem 0.75rem;
            }

            .btn-secondary-clear {
                padding: 0.6rem 1rem;
                font-size: 0.8rem;
            }

            table {
                font-size: 0.7rem;
            }

            table th,
            table td {
                padding: 0.4rem 0.25rem;
            }

            table th {
                font-size: 0.65rem;
                padding: 0.5rem 0.2rem;
            }

            .actions-group {
                gap: 0.15rem;
            }

            .btn-action {
                width: 28px;
                height: 28px;
                font-size: 0.8rem;
            }

            .paginacion {
                padding: 0.75rem;
            }

            .pagination {
                gap: 0.15rem;
            }

            .pagination .page-link {
                min-width: 28px;
                height: 28px;
                padding: 0.2rem 0.4rem;
                font-size: 0.7rem;
            }

            .pagination .page-link .material-symbols-rounded {
                font-size: 0.8rem;
            }
        }
    </style>
@endpush

<script>
// Función global para toggle del dropdown de Ver
window.toggleViewDropdown = function(button) {
    const dropdown = button.closest('div').querySelector('.view-dropdown');
    const allDropdowns = document.querySelectorAll('.view-dropdown');
    
    allDropdowns.forEach(d => {
        if (d !== dropdown) {
            d.style.display = 'none';
        }
    });
    
    if (dropdown.style.display === 'none' || dropdown.style.display === '') {
        // Calcular posición del botón
        const rect = button.getBoundingClientRect();
        dropdown.style.display = 'block';
        dropdown.style.top = (rect.bottom + 4) + 'px';
        dropdown.style.left = rect.left + 'px';
    } else {
        dropdown.style.display = 'none';
    }
};

// Cerrar dropdowns al hacer clic afuera
document.addEventListener('click', function(event) {
    if (!event.target.closest('.view-dropdown') && !event.target.closest('button[onclick*="toggleViewDropdown"]')) {
        document.querySelectorAll('.view-dropdown').forEach(d => {
            d.style.display = 'none';
        });
    }
});
</script>

<!-- Sección de Todas las Cotizaciones -->
<section id="todas-section" class="section-content active" style="display: block;">
    <div class="table-container">
        <div class="table-header">
            <h2>📋 Todas las Cotizaciones</h2>
            <div class="search-bar">
                <input type="text" id="inputBusqueda" placeholder="🔍 Buscar por número de cotización o cliente..." class="search-input" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='var(--border-color)'">
                <button onclick="limpiarFiltros()" class="btn-secondary-clear">
                    <span class="material-symbols-rounded">clear</span>
                    Limpiar
                </button>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Asesora</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Paginar manualmente: 25 por página
                    $perPage = 25;
                    $currentPage = request()->get('page', 1);
                    $total = $todasLasCotizaciones->count();
                    $totalPages = ceil($total / $perPage);
                    $offset = ($currentPage - 1) * $perPage;
                    $cotizacionesPaginadas = $todasLasCotizaciones->slice($offset, $perPage);
                @endphp
                
                @forelse($cotizacionesPaginadas as $cotizacion)
                    <tr>
                        <td><strong>COT-{{ str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT) }}</strong></td>
                        <td>{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y h:i A') : 'N/A' }}</td>
                        <td>{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? 'N/A') }}</td>
                        <td>{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? 'N/A') }}</td>
                        <td>
                            <div class="actions-group">
                                <!-- Dropdown de Ver (Primero) -->
                                <div style="position: relative; display: inline-block;">
                                    <button class="btn-action btn-view" onclick="toggleViewDropdown(this)" title="Ver Opciones">
                                        <span class="material-symbols-rounded">visibility</span>
                                    </button>
                                    <div class="view-dropdown" style="display: none;">
                                        <button onclick="openCotizacionModal({{ $cotizacion->id }}); this.closest('.view-dropdown').style.display='none';">
                                            <span class="material-symbols-rounded">description</span>
                                            Ver Cotización
                                        </button>
                                        <button onclick="abrirModalVisorCostos({{ $cotizacion->id }}, '{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? '') }}'); this.closest('.view-dropdown').style.display='none';">
                                            <span class="material-symbols-rounded">assessment</span>
                                            Ver Costos
                                        </button>
                                        <button onclick="abrirModalPDF({{ $cotizacion->id }}); this.closest('.view-dropdown').style.display='none';">
                                            <span class="material-symbols-rounded">picture_as_pdf</span>
                                            Ver PDF
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem;">
                            <div class="empty-state">
                                <span class="material-symbols-rounded">inbox</span>
                                <p>No hay cotizaciones disponibles</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        <!-- Paginación -->
        @if($totalPages > 1)
        <div class="paginacion">
            <nav role="navigation" aria-label="Pagination Navigation" class="pagination-wrapper">
                <ul class="pagination">
                    {{-- Primera Página --}}
                    @if($currentPage > 1)
                        <li class="page-item">
                            <a class="page-link" href="?page=1" title="Primera página">
                                <span class="material-symbols-rounded">first_page</span>
                            </a>
                        </li>
                    @else
                        <li class="page-item disabled">
                            <span class="page-link" aria-hidden="true">
                                <span class="material-symbols-rounded">first_page</span>
                            </span>
                        </li>
                    @endif

                    {{-- Página Anterior --}}
                    @if($currentPage > 1)
                        <li class="page-item">
                            <a class="page-link" href="?page={{ $currentPage - 1 }}" rel="prev" title="Página anterior">
                                <span class="material-symbols-rounded">chevron_left</span>
                            </a>
                        </li>
                    @else
                        <li class="page-item disabled">
                            <span class="page-link" aria-hidden="true">
                                <span class="material-symbols-rounded">chevron_left</span>
                            </span>
                        </li>
                    @endif

                    {{-- Números de Páginas --}}
                    @php
                        $start = max(1, $currentPage - 2);
                        $end = min($totalPages, $currentPage + 2);
                    @endphp

                    @if($start > 1)
                        <li class="page-item">
                            <a class="page-link" href="?page=1">1</a>
                        </li>
                        @if($start > 2)
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        @endif
                    @endif

                    @for($i = $start; $i <= $end; $i++)
                        @if($i === $currentPage)
                            <li class="page-item active">
                                <span class="page-link" aria-current="page">{{ $i }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="?page={{ $i }}">{{ $i }}</a>
                            </li>
                        @endif
                    @endfor

                    @if($end < $totalPages)
                        @if($end < $totalPages - 1)
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        @endif
                        <li class="page-item">
                            <a class="page-link" href="?page={{ $totalPages }}">{{ $totalPages }}</a>
                        </li>
                    @endif

                    {{-- Página Siguiente --}}
                    @if($currentPage < $totalPages)
                        <li class="page-item">
                            <a class="page-link" href="?page={{ $currentPage + 1 }}" rel="next" title="Página siguiente">
                                <span class="material-symbols-rounded">chevron_right</span>
                            </a>
                        </li>
                    @else
                        <li class="page-item disabled">
                            <span class="page-link" aria-hidden="true">
                                <span class="material-symbols-rounded">chevron_right</span>
                            </span>
                        </li>
                    @endif

                    {{-- Última Página --}}
                    @if($currentPage < $totalPages)
                        <li class="page-item">
                            <a class="page-link" href="?page={{ $totalPages }}" title="Última página">
                                <span class="material-symbols-rounded">last_page</span>
                            </a>
                        </li>
                    @else
                        <li class="page-item disabled">
                            <span class="page-link" aria-hidden="true">
                                <span class="material-symbols-rounded">last_page</span>
                            </span>
                        </li>
                    @endif
                </ul>
            </nav>
        </div>
        @endif
    </div>
</section>

<!-- Script de Búsqueda y Filtros -->
<script src="{{ asset('js/contador/busqueda-filtros.js') }}"></script>

@endsection
