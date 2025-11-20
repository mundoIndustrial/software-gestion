@extends('layouts.contador')

@section('content')
<!-- Sección de Pedidos -->
<section id="pedidos-section" class="section-content active">
    <div class="table-container">
        <div class="table-header">
            <h2>📋 Mis Cotizaciones</h2>
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
                    // Filtrar solo cotizaciones ENVIADAS (no borradores)
                    $cotizacionesFiltradas = $cotizaciones->where('es_borrador', false)->values();
                    // Paginar manualmente: 25 por página
                    $perPage = 25;
                    $currentPage = request()->get('page', 1);
                    $total = $cotizacionesFiltradas->count();
                    $totalPages = ceil($total / $perPage);
                    $offset = ($currentPage - 1) * $perPage;
                    $cotizacionesPaginadas = $cotizacionesFiltradas->slice($offset, $perPage);
                @endphp
                
                @forelse($cotizacionesPaginadas as $cotizacion)
                    <tr>
                        <td><strong>COT-{{ str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT) }}</strong></td>
                        <td>{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                        <td>{{ $cotizacion->cliente ?? 'N/A' }}</td>
                        <td>{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? 'N/A') }}</td>
                        <td>
                            <button class="btn btn-primary" onclick="openCotizacionModal({{ $cotizacion->id }})">
                                <span class="material-symbols-rounded">visibility</span>
                                Ver Detalles
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem; color: #999;">
                            <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">inbox</span>
                            No hay cotizaciones disponibles
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        <!-- Paginación -->
        @if($totalPages > 1)
        <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
            <!-- Botón Anterior -->
            @if($currentPage > 1)
                <a href="?page=1" class="pagination-btn" style="padding: 0.5rem 0.75rem; background: #1e5ba8; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-weight: 600;">
                    « Primera
                </a>
                <a href="?page={{ $currentPage - 1 }}" class="pagination-btn" style="padding: 0.5rem 0.75rem; background: #1e5ba8; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-weight: 600;">
                    ‹ Anterior
                </a>
            @else
                <span style="padding: 0.5rem 0.75rem; background: #e0e0e0; color: #999; border-radius: 4px; font-weight: 600;">
                    « Primera
                </span>
                <span style="padding: 0.5rem 0.75rem; background: #e0e0e0; color: #999; border-radius: 4px; font-weight: 600;">
                    ‹ Anterior
                </span>
            @endif
            
            <!-- Números de página -->
            <div style="display: flex; gap: 0.25rem; align-items: center;">
                @for($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++)
                    @if($i == $currentPage)
                        <span style="padding: 0.5rem 0.75rem; background: #1e5ba8; color: white; border-radius: 4px; font-weight: 700; min-width: 2.5rem; text-align: center;">
                            {{ $i }}
                        </span>
                    @else
                        <a href="?page={{ $i }}" style="padding: 0.5rem 0.75rem; background: white; color: #1e5ba8; border: 1px solid #1e5ba8; border-radius: 4px; text-decoration: none; font-weight: 600; min-width: 2.5rem; text-align: center; transition: all 0.2s;">
                            {{ $i }}
                        </a>
                    @endif
                @endfor
            </div>
            
            <!-- Botón Siguiente -->
            @if($currentPage < $totalPages)
                <a href="?page={{ $currentPage + 1 }}" class="pagination-btn" style="padding: 0.5rem 0.75rem; background: #1e5ba8; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-weight: 600;">
                    Siguiente ›
                </a>
                <a href="?page={{ $totalPages }}" class="pagination-btn" style="padding: 0.5rem 0.75rem; background: #1e5ba8; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-weight: 600;">
                    Última »
                </a>
            @else
                <span style="padding: 0.5rem 0.75rem; background: #e0e0e0; color: #999; border-radius: 4px; font-weight: 600;">
                    Siguiente ›
                </span>
                <span style="padding: 0.5rem 0.75rem; background: #e0e0e0; color: #999; border-radius: 4px; font-weight: 600;">
                    Última »
                </span>
            @endif
        </div>
        
        <!-- Info de paginación -->
        <div style="text-align: center; margin-top: 1rem; color: #666; font-size: 0.9rem;">
            Mostrando {{ ($offset + 1) }} a {{ min($offset + $perPage, $total) }} de {{ $total }} cotizaciones
        </div>
        @endif
    </div>
</section>

<!-- Sección de Formatos -->
<section id="formatos-section" class="section-content">
    <div class="table-container">
        <div class="table-header">
            <h2>📄 Formatos de Cotización</h2>
        </div>
        <div id="formatos-list" style="padding: 2rem; display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2rem;">
            <!-- Se cargará dinámicamente -->
        </div>
    </div>
</section>

<!-- Modal de Cotización -->
<div id="cotizacionModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <img src="{{ asset('images/logo2.png') }}" alt="Logo Mundo Industrial" class="modal-header-logo">
            <button class="modal-close" onclick="closeCotizacionModal()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Contenido dinámico -->
        </div>
    </div>
</div>


<script src="{{ asset('js/contador/contador.js') }}"></script>
@endsection
