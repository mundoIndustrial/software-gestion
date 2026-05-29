@extends('operario.layout')

@section('title', 'Ver Préstamo de Insumos')
@section('page-title')
    <span style="display: inline-flex; align-items: center; gap: 0.6rem;">
        <span class="material-symbols-rounded">visibility</span>
        <span>VER RECIBO INSUMOS</span>
    </span>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/order-detail-modal.css') }}">
<link rel="stylesheet" href="{{ asset('css/order-detail-modal-mobile.css') }}" media="screen and (max-width: 768px)">
<link rel="stylesheet" href="{{ asset('css/print-order-detail-modal.css') }}" media="print">
<style>
    .prestamo-shell { max-width: 980px; margin: 0 auto; }
    .prestamo-top { display: flex; justify-content: flex-end; margin-bottom: .6rem; }
    .prestamo-mobile-frame {
        max-width: 100%;
        padding: 0.5rem;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: 100vh;
        background: transparent;
    }
    .prestamo-insumos-card {
        position: relative;
        width: 100%;
        max-width: 600px;
        margin: 20px auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .prestamo-insumos-card .order-descripcion {
        margin-bottom: 50px;
    }
    .recibo-field-value {
        display: block;
        margin-top: 8px;
        margin-bottom: 12px;
        color: #212529;
        font-weight: 600;
    }
    .recibo-items-wrap {
        display: inline-block;
        min-width: 220px;
        margin-top: 8px;
    }
    .recibo-item-line {
        display: block;
        color: #212529;
        font-weight: 600;
        margin-bottom: 4px;
    }
    @media (max-width: 768px) {
        .prestamo-top {
            justify-content: flex-start;
            margin-bottom: 10px;
        }
        .order-detail-modal-container--mobile-full {
            padding: 2px !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            justify-content: stretch !important;
            box-sizing: border-box !important;
        }
        .order-detail-card--mobile-full {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 auto !important;
        }
        .prestamo-shell {
            max-width: 100%;
        }
        .prestamo-mobile-frame {
            padding: 2px;
            margin: 0;
            width: 100vw;
            max-width: 100%;
            justify-content: stretch;
            box-sizing: border-box;
        }
        .prestamo-insumos-card {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }
        .prestamo-insumos-card .recibo-items-wrap {
            display: block;
            min-width: 0;
            width: 100%;
        }
        .prestamo-insumos-card .recibo-item-line {
            word-break: break-word;
            white-space: normal;
        }
    }
</style>
@endpush

@section('content')
    @php($fecha = \Carbon\Carbon::parse($recibo->fecha))
    <div class="prestamo-shell">
        <div class="prestamo-top">
            <a class="btn-soft" href="{{ route('operario.recibos-prestamo.index', ['tab' => 'insumos']) }}">Volver</a>
        </div>

        <div class="order-detail-modal-container order-detail-modal-container--mobile-full prestamo-mobile-frame">
            <div class="order-detail-card order-detail-card--mobile-full prestamo-insumos-card">
                <img src="{{ asset('images/logo2.png') }}" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">

                <div class="order-date">
                    <div class="fec-label">FECHA</div>
                    <div class="date-boxes">
                        <div class="date-box day-box">{{ $fecha->format('d') }}</div>
                        <div class="date-box month-box">{{ $fecha->format('m') }}</div>
                        <div class="date-box year-box">{{ $fecha->format('Y') }}</div>
                    </div>
                </div>

                <div class="order-descripcion">
                    <div>
                        <div id="order-asesora" class="order-asesora">ASESORA: <span>{{ $recibo->asesor ?? 'N/A' }}</span></div>
                        <div id="order-forma-pago" class="order-forma-pago">FORMA DE PAGO: <span>{{ $recibo->forma_pago ?? 'N/A' }}</span></div>
                        <div id="order-cliente" class="order-cliente">CLIENTE: <span>{{ $recibo->cliente ?? 'N/A' }}</span></div>

                        <strong>NOMBRE DEL COSTURERO ASIGNADO</strong><br>
                        <span class="recibo-field-value">{{ $recibo->nombre_costurero }}</span>

                        <strong>DETALLE</strong><br>
                        <div class="recibo-items-wrap">
                            @forelse($items as $item)
                                <span class="recibo-item-line">
                                    {{ number_format((float) $item->cantidad, 2, ',', '.') }} - {{ $item->descripcion }}
                                </span>
                            @empty
                                <span class="recibo-item-line" style="color:#64748b;">Sin items registrados.</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <h2 class="receipt-title">RECIBO DE PRÉSTAMO<br>DE INSUMOS</h2>
                <div class="pedido-number">#{{ $recibo->numero_orden }}</div>
            </div>
        </div>
    </div>
@endsection
