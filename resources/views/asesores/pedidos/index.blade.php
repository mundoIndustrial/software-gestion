@extends('asesores.layout')

@section('title', 'Mis Pedidos')
@section('page-title', 'Mis Pedidos')

@section('content')

<style>
    .top-nav {
        display: none !important;
    }
</style>

<div style="padding: 0 0.5rem 0 0; max-width: 100%; margin: 0 auto;">
    <!-- HEADER PROFESIONAL -->
    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); border-radius: 12px; padding: 20px 30px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(30, 58, 138, 0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 30px;">
            <!-- TÍTULO CON ICONO -->
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: rgba(255,255,255,0.15); padding: 10px 12px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-list" style="color: white; font-size: 24px;"></i>
                </div>
                <div>
                    <h1 style="margin: 0; font-size: 1.5rem; color: white; font-weight: 700;">Lista de Pedidos</h1>
                    <p style="margin: 0; color: rgba(255,255,255,0.7); font-size: 0.85rem;">Gestiona tus pedidos de producción</p>
                </div>
            </div>

            <!-- BOTÓN REGISTRAR -->
            <a href="{{ route('asesores.pedidos-produccion.crear') }}" style="background: white; color: #1e40af; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; box-shadow: 0 2px 8px rgba(0,0,0,0.1); white-space: nowrap;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Registrar
            </a>
        </div>
    </div>

    <!-- Tabla con Scroll Horizontal -->
    <div style="background: #e5e7eb; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 0.75rem;">
        <!-- Contenedor con Scroll -->
        <div class="table-scroll-container">
            <!-- Header Azul -->
            <div style="
                background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
                color: white;
                padding: 1rem 1.5rem;
                display: grid;
                grid-template-columns: 130px 110px 110px 130px 90px 160px 220px 90px 130px 130px;
                gap: 1.2rem;
                font-weight: 600;
                font-size: 0.875rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                min-width: min-content;
                border-radius: 6px;
            ">
                <div>Acciones</div>
                <div>Estado</div>
                <div>Área</div>
                <div>Fecha Estimada</div>
                <div>Pedido</div>
                <div>Cliente</div>
                <div>Descripción</div>
                <div>Cantidad</div>
                <div>Forma Pago</div>
                <div>Fecha Creación</div>
            </div>

            <!-- Filas -->
            @if($pedidos->isEmpty())
                <div style="padding: 3rem 2rem; text-align: center; color: #6b7280;">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                    <p style="font-size: 1rem; margin: 0;">No hay pedidos registrados</p>
                </div>
            @else
                @foreach($pedidos as $pedido)
                    <div style="
                        display: grid;
                        grid-template-columns: 130px 110px 110px 130px 90px 160px 220px 90px 130px 130px;
                        gap: 1.2rem;
                        padding: 1rem 1.5rem;
                        align-items: center;
                        transition: all 0.3s ease;
                        min-width: min-content;
                        background: white;
                        border-radius: 6px;
                        margin-bottom: 0.75rem;
                        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                    " onmouseover="this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.1)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='0 2px 4px rgba(0, 0, 0, 0.05)'; this.style.transform='translateY(0)'">
                    
                    <!-- Acciones -->
                    <div style="position: relative; display: inline-block;">
                        <button onclick="toggleDropdown(event)" data-menu-id="menu-{{ $pedido->numero_pedido }}" style="
                            background: linear-gradient(135deg, #10b981, #059669);
                            color: white;
                            border: none;
                            padding: 0.5rem 1rem;
                            border-radius: 6px;
                            cursor: pointer;
                            font-weight: 600;
                            font-size: 0.75rem;
                            transition: all 0.2s ease;
                            display: flex;
                            align-items: center;
                            gap: 0.5rem;
                            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
                        " onmouseover="this.style.boxShadow='0 4px 8px rgba(16, 185, 129, 0.3)'; this.style.transform='translateY(-1px)'" onmouseout="this.style.boxShadow='0 2px 4px rgba(16, 185, 129, 0.2)'; this.style.transform='translateY(0)'">
                            <i class="fas fa-eye"></i> Ver
                        </button>
                        <div class="dropdown-menu" id="menu-{{ $pedido->numero_pedido }}" style="
                            position: fixed;
                            background: white;
                            border: 2px solid #e5e7eb;
                            border-radius: 8px;
                            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
                            min-width: 160px;
                            display: none;
                            z-index: 99999 !important;
                            overflow: visible !important;
                        ">
                            <button onclick="verFactura({{ $pedido->numero_pedido }}); closeDropdown()" style="
                                width: 100%;
                                text-align: left;
                                padding: 0.875rem 1rem;
                                border: none;
                                background: transparent;
                                cursor: pointer;
                                color: #374151;
                                font-size: 0.875rem;
                                transition: background 0.2s ease;
                                display: flex;
                                align-items: center;
                                gap: 0.75rem;
                                font-weight: 500;
                            " onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background='transparent'">
                                <i class="fas fa-eye" style="color: #2563eb;"></i> Detalle
                            </button>
                            <div style="height: 1px; background: #e5e7eb;"></div>
                            <button onclick="verSeguimiento({{ $pedido->numero_pedido }}); closeDropdown()" style="
                                width: 100%;
                                text-align: left;
                                padding: 0.875rem 1rem;
                                border: none;
                                background: transparent;
                                cursor: pointer;
                                color: #374151;
                                font-size: 0.875rem;
                                transition: background 0.2s ease;
                                display: flex;
                                align-items: center;
                                gap: 0.75rem;
                                font-weight: 500;
                            " onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background='transparent'">
                                <i class="fas fa-tasks" style="color: #10b981;"></i> Seguimiento
                            </button>
                        </div>
                    </div>

                    <!-- Estado -->
                    <div>
                        <span style="
                            background: #fef3c7;
                            color: #92400e;
                            padding: 0.25rem 0.75rem;
                            border-radius: 12px;
                            font-size: 0.75rem;
                            font-weight: 600;
                            display: inline-block;
                        ">
                            {{ $pedido->estado ?? 'Pendiente' }}
                        </span>
                    </div>

                    <!-- Área -->
                    <div style="color: #374151; font-size: 0.875rem;">
                        {{ $pedido->procesoActualOptimizado() ?? '-' }}
                    </div>

                    <!-- Fecha Estimada de Entrega -->
                    <div style="color: #374151; font-size: 0.875rem;">
                        {{ $pedido->fecha_estimada_de_entrega ?? '-' }}
                    </div>

                    <!-- Pedido -->
                    <div style="color: #2563eb; font-weight: 700; font-size: 0.875rem;">
                        #{{ $pedido->numero_pedido }}
                    </div>

                    <!-- Cliente -->
                    <div style="color: #374151; font-size: 0.875rem; font-weight: 500;">
                        {{ $pedido->cliente }}
                    </div>

                    <!-- Descripción -->
                    <div style="color: #6b7280; font-size: 0.875rem;">
                        @if($pedido->prendas->first())
                            {{ $pedido->prendas->first()->nombre_prenda }}
                        @else
                            <span style="color: #d1d5db;">-</span>
                        @endif
                    </div>

                    <!-- Cantidad -->
                    <div style="color: #374151; font-weight: 600; font-size: 0.875rem;">
                        @if($pedido->prendas->first())
                            {{ $pedido->prendas->first()->cantidad }} <small style="color: #9ca3af;">und</small>
                        @else
                            <span style="color: #d1d5db;">-</span>
                        @endif
                    </div>

                    <!-- Forma Pago -->
                    <div style="color: #374151; font-size: 0.875rem;">
                        {{ $pedido->forma_de_pago ?? '-' }}
                    </div>

                    <!-- Fecha Creación -->
                    <div style="color: #6b7280; font-size: 0.75rem;">
                        {{ $pedido->fecha_de_creacion_de_orden ? $pedido->fecha_de_creacion_de_orden->format('d/m/Y') : '-' }}
                    </div>
                </div>
                @endforeach
            @endif
        </div>
    </div>

    <!-- Paginación -->
    @if($pedidos->hasPages())
        <div style="margin-top: 1.5rem; display: flex; justify-content: center;">
            {{ $pedidos->links() }}
        </div>
    @endif
</div>

<!-- Modal de Detalle de Orden -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>

<div style="width: 90%; max-width: 672px; position: fixed; top: 60%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto;">
    <x-orders-components.order-detail-modal />
</div>


@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
@endpush

@push('scripts')
<script>
    // Configurar variables globales para los modales
    window.fetchUrl = '/registros';
    window.modalContext = 'pedidos';
</script>
<script src="{{ asset('js/asesores/pedidos-list.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-modal.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-dropdown.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
<script src="{{ asset('js/orderTracking.js') }}"></script>
@endpush
