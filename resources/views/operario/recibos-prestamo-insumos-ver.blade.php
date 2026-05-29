@extends('operario.layout')

@section('title', 'Ver Préstamo de Insumos')
@section('page-title', '')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/order-detail-modal.css') }}">
<link rel="stylesheet" href="{{ asset('css/order-detail-modal-mobile.css') }}" media="screen and (max-width: 768px)">
<link rel="stylesheet" href="{{ asset('css/print-order-detail-modal.css') }}" media="print">
<style>
    #mobile-numero-pedido { top: 120px !important; right: 12px !important; }

    .ver-pedido-fullscreen {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        flex-direction: column;
        background: white;
        z-index: 999;
        overflow: hidden;
    }

    .pedido-header-negro {
        display: flex;
        align-items: center;
        gap: 1rem;
        background: #2c2c2c;
        color: white;
        padding: 1rem 1.5rem;
        flex-shrink: 0;
        z-index: 100;
    }

    .btn-volver-header {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: transparent;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .pedido-numero-header { margin: 0; font-size: 1.5rem; font-weight: 700; }

    .pedido-tabs { display: flex; background: white; border-bottom: 1px solid #eee; }
    .tab-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        padding: 1rem;
        background: white;
        color: #666;
        border: none;
        border-bottom: 3px solid transparent;
        font-size: .85rem;
        font-weight: 700;
        text-transform: uppercase;
        cursor: pointer;
    }
    .tab-btn.active { color: #EF5350; border-bottom-color: #EF5350; }

    .pedido-content { flex: 1; overflow-y: auto; padding: 1.5rem; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }

    .pedido-modal-section {
        margin: 0 auto;
        width: 100%;
        display: flex;
        justify-content: center;
        padding: 1rem;
    }

    .fotos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
    }

    .empty-fotos {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem 1rem;
        color: #999;
    }

    /* Mantener la descripción en el bloque central del recibo en desktop */
    #order-descripcion {
        position: absolute;
        top: 240px;
        left: 20px;
        right: 20px;
        bottom: 120px;
        overflow-y: auto;
    }

    @media (max-width: 768px) {
        .order-detail-modal-container--mobile-full {
            padding: 2px !important;
            margin: 0 !important;
            width: 100vw !important;
            max-width: 100% !important;
            justify-content: stretch !important;
            box-sizing: border-box !important;
        }
        .order-detail-card--mobile-full {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 auto !important;
        }
        .pedido-content { padding: 0; }
        .pedido-modal-section { padding: 0; }
        .tab-btn { font-size: .75rem; }
        #order-descripcion {
            position: relative;
            top: auto;
            left: auto;
            right: auto;
            bottom: auto;
            overflow: visible;
        }
    }
</style>
@endpush

@section('content')
@php($fecha = \Carbon\Carbon::parse($recibo->fecha))
<div class="ver-pedido-fullscreen">
    <div class="pedido-header-negro">
        <button class="btn-volver-header" onclick="window.location='{{ route('operario.recibos-prestamo.index', ['tab' => 'insumos']) }}'">
            <span class="material-symbols-rounded">arrow_back</span>
        </button>
        <h1 class="pedido-numero-header">#{{ $recibo->numero_orden }}</h1>
    </div>

    <div class="pedido-tabs">
        <button type="button" class="tab-btn active" data-tab="orden">
            <span class="material-symbols-rounded">description</span>
            LA ORDEN
        </button>
        <button type="button" class="tab-btn" data-tab="fotos">
            <span class="material-symbols-rounded">image</span>
            FOTOS (0)
        </button>
    </div>

    <div class="pedido-content">
        <div id="tab-orden" class="tab-content active">
            <div class="pedido-modal-section">
                <div class="order-detail-modal-container order-detail-modal-container--mobile-full" style="max-width: 100%; padding: 0.5rem; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; background: transparent;">
                    <div class="order-detail-card order-detail-card--mobile-full" style="position: relative; width: 100%; max-width: 600px; margin: 20px auto; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                        <img src="{{ asset('images/logo2.png') }}" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">

                        <div id="order-date" class="order-date">
                            <div class="fec-label">FECHA</div>
                            <div class="date-boxes">
                                <div class="date-box day-box" id="fecha-dia">{{ $fecha->format('d') }}</div>
                                <div class="date-box month-box" id="fecha-mes">{{ $fecha->format('m') }}</div>
                                <div class="date-box year-box" id="fecha-year">{{ $fecha->format('Y') }}</div>
                            </div>
                        </div>

                        <div id="order-descripcion" class="order-descripcion" style="margin-bottom: 50px;">
                            <div id="mobile-descripcion">
                                <div class="prenda-item" style="margin-bottom: 16px; line-height: 1.4; font-size: 0.75rem; color: #333;">
                                    <strong style="font-size: 13.4px;">NOMBRE DEL COSTURERO ASIGNADO</strong><br>
                                    <span style="display:block; margin-top: 6px; margin-bottom: 12px;">{{ $recibo->nombre_costurero }}</span>

                                    <strong style="font-size: 13.4px;">DETALLE</strong><br>
                                    <div style="margin-top: 8px;">
                                        @forelse($items as $item)
                                            <span style="display:block; margin-bottom: 4px;">
                                                {{ number_format((float) $item->cantidad, 2, ',', '.') }} - {{ $item->descripcion }}
                                            </span>
                                        @empty
                                            <span style="display:block; color:#64748b;">Sin items registrados.</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h2 class="receipt-title" id="receipt-title-mobile">RECIBO DE PRÉSTAMO DE INSUMOS</h2>
                        <div class="pedido-number" id="mobile-numero-pedido">#{{ $recibo->numero_orden }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-fotos" class="tab-content">
            <div class="fotos-grid" id="fotos-grid">
                <div class="empty-fotos">
                    <span class="material-symbols-rounded">image_not_supported</span>
                    <p>No hay fotos disponibles para este pedido</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const buttons = document.querySelectorAll('.tab-btn');
        const tabs = document.querySelectorAll('.tab-content');

        buttons.forEach((btn) => {
            btn.addEventListener('click', function () {
                const target = this.dataset.tab;

                buttons.forEach((b) => b.classList.remove('active'));
                tabs.forEach((t) => t.classList.remove('active'));

                this.classList.add('active');
                const pane = document.getElementById('tab-' + target);
                if (pane) pane.classList.add('active');
            });
        });
    });
</script>
@endsection
