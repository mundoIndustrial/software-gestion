@extends('layouts.base')

@section('title', 'Préstamos de Taller')
@section('page-title', 'Préstamos de Taller')

@section('body')
<div style="max-width:1100px;margin:0 auto;padding:16px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <div>
            <h2 style="margin:0;">{{ $taller->name }}</h2>
            <p style="margin:4px 0 0;color:#64748b;">Recibos enviados al taller</p>
        </div>
        <a href="{{ route('talleres.index') }}" style="border:1px solid #cbd5e1;border-radius:8px;padding:8px 12px;text-decoration:none;color:#0f172a;">Volver</a>
    </div>

    <div style="display:flex;gap:8px;margin-bottom:12px;">
        <a href="{{ route('talleres.prestamos', ['id' => $taller->id, 'tab' => 'insumos']) }}"
           style="padding:8px 12px;border-radius:8px;text-decoration:none;border:1px solid #cbd5e1;{{ $tab === 'insumos' ? 'background:#0f172a;color:#fff;border-color:#0f172a;' : 'background:#fff;color:#0f172a;' }}">
            Recibos Préstamo Insumos
        </a>
        <a href="{{ route('talleres.prestamos', ['id' => $taller->id, 'tab' => 'contramuestra']) }}"
           style="padding:8px 12px;border-radius:8px;text-decoration:none;border:1px solid #cbd5e1;{{ $tab === 'contramuestra' ? 'background:#0f172a;color:#fff;border-color:#0f172a;' : 'background:#fff;color:#0f172a;' }}">
            Préstamo Contramuestra
        </a>
    </div>

    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">N° Recibo</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Costurero</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Fecha salida</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Fecha entrada</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Estado</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Novedad</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($registros as $r)
                <tr>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;">#{{ $r->numero_orden }}</td>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;">{{ $r->nombre_costurero }}</td>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;">{{ \Carbon\Carbon::parse($r->fecha_salida)->format('d/m/Y') }}</td>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;">{{ $r->fecha_entrada ? \Carbon\Carbon::parse($r->fecha_entrada)->format('d/m/Y H:i') : '-' }}</td>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;">
                        @if($r->anulado)
                            ANULADO
                        @elseif($r->confirmado_entrada)
                            ENTRADA CONFIRMADA
                        @else
                            PENDIENTE
                        @endif
                    </td>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;">{{ $r->novedades ?: '-' }}</td>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;">
                        <button type="button"
                                class="btn-ver-prestamo"
                                data-tipo="{{ $tab }}"
                                data-id="{{ $r->id }}"
                                style="display:inline-block;border:1px solid #cbd5e1;border-radius:8px;padding:6px 10px;background:#fff;color:#0f172a;cursor:pointer;">
                            Ver
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding:16px;text-align:center;color:#64748b;">Sin registros para este servicio.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:10px;">
        {{ $registros->links('vendor.pagination.simple-clean') }}
    </div>
</div>

<div id="modal-overlay"
     style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;"
     onclick="closeModalOverlay()"></div>
<div id="order-detail-modal-wrapper"
     style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>
<style>
    #order-detail-modal-wrapper #order-pedido {
        transform: translateY(20px) !important;
    }
    #order-detail-modal-wrapper #btn-galeria {
        display: none !important;
    }
</style>

<script src="{{ asset('js/ordersjs/order-detail-modal-manager.js') }}"></script>
<script src="{{ asset('js/modulos/talleres/prestamo-modal-handler.js') }}"></script>
@endsection
