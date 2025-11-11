@extends('asesores.layout')

@section('title', 'Mis Pedidos')
@section('page-title', 'Gestión de Pedidos')

@section('content')
<div class="erp-form-container">
    
    <!-- Header con Pestañas -->
    <div class="erp-tabs" style="margin-bottom: 2rem;">
        <a href="{{ route('asesores.pedidos.index', ['tipo' => 'confirmados']) }}" 
           class="erp-tab {{ $tipo === 'confirmados' ? 'active' : '' }}">
            <span class="material-symbols-rounded">check_circle</span>
            Pedidos Confirmados
            <span class="erp-tab-badge">{{ $cantidadConfirmados }}</span>
        </a>
        <a href="{{ route('asesores.pedidos.index', ['tipo' => 'borradores']) }}" 
           class="erp-tab {{ $tipo === 'borradores' ? 'active' : '' }}">
            <span class="material-symbols-rounded">edit_note</span>
            Borradores
            <span class="erp-tab-badge badge-warning">{{ $cantidadBorradores }}</span>
        </a>
    </div>

    <!-- Barra de Acciones -->
    <div class="erp-section">
        <div class="erp-section-body" style="padding: 1.5rem;">
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <div class="search-box" style="position: relative;">
                        <span class="material-symbols-rounded" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary);">search</span>
                        <input type="text" 
                               id="searchInput" 
                               class="erp-input" 
                               style="padding-left: 3rem;" 
                               placeholder="Buscar por número o cliente..." 
                               value="{{ request('search') }}">
                    </div>
                </div>
                
                <select id="filterEstado" class="erp-select" style="min-width: 180px;">
                    <option value="">Todos los Estados</option>
                    @foreach($estados as $estado)
                        <option value="{{ $estado }}" {{ request('estado') == $estado ? 'selected' : '' }}>
                            {{ $estado }}
                        </option>
                    @endforeach
                </select>

                <select id="filterArea" class="erp-select" style="min-width: 180px;">
                    <option value="">Todas las Áreas</option>
                    @foreach($areas as $area)
                        <option value="{{ $area }}" {{ request('area') == $area ? 'selected' : '' }}>
                            {{ $area }}
                        </option>
                    @endforeach
                </select>

                <a href="{{ route('asesores.pedidos.create') }}" class="erp-btn erp-btn-primary">
                    <span class="material-symbols-rounded">add_circle</span>
                    Nuevo Pedido
                </a>
            </div>
        </div>
    </div>

    <!-- Lista de Pedidos con Cards ERP -->
    <div style="margin-top: 2rem;">
        @if($pedidos->count() > 0)
            <div style="display: grid; gap: 1.5rem;">
                @foreach($pedidos as $pedido)
                    <div class="erp-product-card" style="padding: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                                    @if($pedido->es_borrador)
                                        <span style="background: linear-gradient(135deg, #F77F00, #FFA726); color: white; padding: 0.375rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem;">
                                            <span class="material-symbols-rounded" style="font-size: 1rem;">edit_note</span>
                                            BORRADOR
                                        </span>
                                        <h3 style="margin: 0; color: var(--text-primary);">BORRADOR-{{ $pedido->id }}</h3>
                                    @else
                                        <span style="background: linear-gradient(135deg, #00A86B, #00C97D); color: white; padding: 0.375rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem;">
                                            <span class="material-symbols-rounded" style="font-size: 1rem;">check_circle</span>
                                            CONFIRMADO
                                        </span>
                                        <h3 style="margin: 0; color: var(--text-primary);">PEDIDO #{{ $pedido->pedido }}</h3>
                                    @endif
                                </div>
                                <p style="margin: 0; color: var(--text-secondary); font-size: 0.875rem;">
                                    <span class="material-symbols-rounded" style="font-size: 1rem; vertical-align: middle;">business</span>
                                    {{ $pedido->cliente }}
                                </p>
                            </div>
                            
                            <div style="text-align: right;">
                                <p style="margin: 0; color: var(--text-secondary); font-size: 0.875rem;">
                                    <span class="material-symbols-rounded" style="font-size: 1rem; vertical-align: middle;">calendar_today</span>
                                    {{ $pedido->created_at ? $pedido->created_at->format('d/m/Y') : '-' }}
                                </p>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem; padding: 1rem; background: var(--bg-primary); border-radius: 8px;">
                            <div>
                                <p style="margin: 0; color: var(--text-secondary); font-size: 0.75rem;">Productos</p>
                                <p style="margin: 0; color: var(--text-primary); font-weight: 600;">{{ $pedido->productos ? $pedido->productos->count() : 0 }} items</p>
                            </div>
                            <div>
                                <p style="margin: 0; color: var(--text-secondary); font-size: 0.75rem;">Cantidad Total</p>
                                <p style="margin: 0; color: var(--text-primary); font-weight: 600;">{{ $pedido->cantidad_prendas ?? 0 }} unidades</p>
                            </div>
                            <div>
                                <p style="margin: 0; color: var(--text-secondary); font-size: 0.75rem;">Estado</p>
                                <span style="background: {{ 
                                    $pedido->estado == 'Entregado' ? 'var(--success-color)' : 
                                    ($pedido->estado == 'En Ejecución' ? 'var(--warning-color)' : 
                                    ($pedido->estado == 'Anulada' ? 'var(--danger-color)' : 'var(--text-secondary)'))
                                }}; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                    {{ $pedido->estado ?? 'Sin estado' }}
                                </span>
                            </div>
                            @if($pedido->area)
                            <div>
                                <p style="margin: 0; color: var(--text-secondary); font-size: 0.75rem;">Área</p>
                                <p style="margin: 0; color: var(--text-primary); font-weight: 600;">{{ $pedido->area }}</p>
                            </div>
                            @endif
                        </div>

                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            @if($pedido->es_borrador)
                                <a href="{{ route('asesores.pedidos.edit', $pedido->id) }}" 
                                   class="erp-btn erp-btn-sm erp-btn-primary">
                                    <span class="material-symbols-rounded">edit</span>
                                    Continuar Editando
                                </a>
                                <button onclick="confirmarPedido({{ $pedido->id }})" 
                                        class="erp-btn erp-btn-sm erp-btn-success">
                                    <span class="material-symbols-rounded">check</span>
                                    Confirmar Pedido
                                </button>
                                <button onclick="eliminarBorrador({{ $pedido->id }})" 
                                        class="erp-btn erp-btn-sm erp-btn-danger">
                                    <span class="material-symbols-rounded">delete</span>
                                    Eliminar
                                </button>
                            @else
                                <a href="{{ route('asesores.pedidos.show', $pedido->pedido) }}" 
                                   class="erp-btn erp-btn-sm erp-btn-secondary">
                                    <span class="material-symbols-rounded">visibility</span>
                                    Ver Detalles
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Paginación -->
            <div style="margin-top: 2rem;">
                {{ $pedidos->links() }}
            </div>
        @else
            <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-card); border-radius: 12px; border: 2px dashed var(--border-color);">
                <span class="material-symbols-rounded" style="font-size: 4rem; color: var(--text-tertiary);">inbox</span>
                <h3 style="margin: 1rem 0 0.5rem; color: var(--text-primary);">
                    @if($tipo === 'borradores')
                        No hay borradores guardados
                    @else
                        No hay pedidos confirmados
                    @endif
                </h3>
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                    @if($tipo === 'borradores')
                        Los pedidos que guardes como borrador aparecerán aquí
                    @else
                        Aún no has confirmado ningún pedido. ¡Crea tu primer pedido ahora!
                    @endif
                </p>
                <a href="{{ route('asesores.pedidos.create') }}" class="erp-btn erp-btn-primary erp-btn-lg">
                    <span class="material-symbols-rounded">add_circle</span>
                    Crear Primer Pedido
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/asesores/pedidos-list.js') }}"></script>
@endpush
