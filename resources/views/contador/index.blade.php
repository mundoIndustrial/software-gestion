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
                    // Paginar manualmente: 25 por página
                    // (El filtrado ya se hace en el controlador)
                    $perPage = 25;
                    $currentPage = request()->get('page', 1);
                    $total = $cotizaciones->count();
                    $totalPages = ceil($total / $perPage);
                    $offset = ($currentPage - 1) * $perPage;
                    $cotizacionesPaginadas = $cotizaciones->slice($offset, $perPage);
                @endphp
                
                @forelse($cotizacionesPaginadas as $cotizacion)
                    <tr>
                        <td><strong>COT-{{ str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT) }}</strong></td>
                        <td>{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                        <td>{{ $cotizacion->cliente ?? 'N/A' }}</td>
                        <td>{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? 'N/A') }}</td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                                <button class="btn btn-primary" onclick="openCotizacionModal({{ $cotizacion->id }})" style="padding: 0.6rem 0.8rem; background: #1e5ba8; color: white; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" title="Ver Detalles" onmouseover="this.style.background='#1e40af'" onmouseout="this.style.background='#1e5ba8'">
                                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">visibility</span>
                                </button>
                                <button class="btn btn-danger" onclick="eliminarCotizacion({{ $cotizacion->id }}, '{{ $cotizacion->cliente }}')" style="padding: 0.6rem 0.8rem; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" title="Eliminar" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">delete</span>
                                </button>
                            </div>
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

<!-- Modal de Cotización -->
<div id="cotizacionModal" class="modal fullscreen" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <img src="{{ asset('images/logo2.png') }}" alt="Logo Mundo Industrial" class="modal-header-logo">
            <div style="display: flex; gap: 3rem; align-items: center; flex: 1; margin-left: 2rem; color: white; font-size: 0.85rem;">
                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                    <span style="font-weight: 600; opacity: 0.9;">COTIZACIÓN #</span>
                    <span style="font-size: 1rem; font-weight: 700;" id="modalHeaderNumber">-</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                    <span style="font-weight: 600; opacity: 0.9;">FECHA</span>
                    <span style="font-size: 1rem; font-weight: 700;" id="modalHeaderDate">-</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                    <span style="font-weight: 600; opacity: 0.9;">CLIENTE</span>
                    <span style="font-size: 1rem; font-weight: 700;" id="modalHeaderClient">-</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                    <span style="font-weight: 600; opacity: 0.9;">ASESORA</span>
                    <span style="font-size: 1rem; font-weight: 700;" id="modalHeaderAdvisor">-</span>
                </div>
            </div>
            <button class="modal-back-btn" onclick="closeCotizacionModal()">
                <span class="material-symbols-rounded">arrow_back</span>
                VOLVER
            </button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Contenido dinámico -->
        </div>
    </div>
</div>

@endsection
