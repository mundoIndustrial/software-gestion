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
<link rel="stylesheet" href="{{ asset('css/print-order-detail-modal.css') }}" media="print">
<style>
    .prestamo-shell { max-width: 980px; margin: 0 auto; }
    .prestamo-top { display: flex; justify-content: flex-end; margin-bottom: .6rem; }
</style>
@endpush

@section('content')
    @php($fecha = \Carbon\Carbon::parse($recibo->fecha))
    <div class="prestamo-shell">
        <div class="prestamo-top">
            <a class="btn-soft" href="{{ route('operario.recibos-prestamo.index', ['tab' => 'insumos']) }}">Volver</a>
        </div>

        <div class="order-detail-modal-container" style="display:flex;flex-direction:column;width:100%;height:100%;">
            <div class="order-detail-card" style="display:block;">
                <img src="{{ asset('images/logo2.png') }}" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">

                <div class="order-date" style="background:#000;border-radius:10px;padding:6px;min-width:128px;text-align:center;">
                    <div class="fec-label" style="color:#fff;font-weight:700;font-size:11px;letter-spacing:.5px;margin-bottom:4px;">FECHA</div>
                    <div class="date-boxes" style="display:flex;justify-content:center;gap:4px;">
                        <div class="date-box day-box" style="background:#fff;color:#111;border-radius:4px;min-width:36px;padding:4px;font-size:12px;font-weight:800;">{{ $fecha->format('d') }}</div>
                        <div class="date-box month-box" style="background:#fff;color:#111;border-radius:4px;min-width:36px;padding:4px;font-size:12px;font-weight:800;">{{ $fecha->format('m') }}</div>
                        <div class="date-box year-box" style="background:#fff;color:#111;border-radius:4px;min-width:36px;padding:4px;font-size:12px;font-weight:800;">{{ $fecha->format('Y') }}</div>
                    </div>
                </div>

                <div class="order-descripcion">
                    <div>
                        <strong style="font-size:13.4px;">TIPO DE RECIBO</strong><br>
                        <span style="display:block;margin-top:8px;margin-bottom:12px;color:#212529;font-weight:600;">Préstamo de Insumos</span>

                        <strong>NOMBRE DEL COSTURERO ASIGNADO</strong><br>
                        <span style="display:block;margin-top:8px;margin-bottom:12px;color:#212529;font-weight:600;">{{ $recibo->nombre_costurero }}</span>

                        <strong>DETALLE</strong><br>
                        <div style="display:inline-block;min-width:220px;margin-top:8px;">
                            @forelse($items as $item)
                                <span style="display:block;color:#212529;font-weight:600;margin-bottom:4px;">
                                    {{ number_format((float) $item->cantidad, 2, ',', '.') }} - {{ $item->descripcion }}
                                </span>
                            @empty
                                <span style="display:block;color:#64748b;font-weight:600;">Sin items registrados.</span>
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

