@extends('layouts.despacho-standalone')

@section('title', 'Gestion de Despacho')
@section('page-title', 'Gestion de Despacho')

@push('styles')
<style>
  * { box-sizing: border-box; }
  .gd-wrap { max-width: 980px; margin: 0 auto; padding: 24px 20px; }
  .gd-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; }
  .gd-header { display: flex; justify-content: space-between; align-items: center; gap: 14px; padding: 18px 22px; margin-bottom: 14px; }
  .gd-kicker { display: inline-flex; gap: 8px; align-items: center; margin-bottom: 4px; }
  .gd-pill { font-size: 11px; font-weight: 700; letter-spacing: .06em; color: #4338ca; background: #eef2ff; border-radius: 999px; padding: 3px 10px; }
  .gd-sub { font-size: 12px; color: #6b7280; }
  .gd-title { font-size: 20px; font-weight: 700; color: #111827; }
  .gd-btn { border-radius: 10px; border: 1px solid #e5e7eb; background: #fff; color: #374151; padding: 8px 14px; font-size: 12px; font-weight: 600; text-decoration: none; }
  .gd-btn-dark { border: none; background: #111827; color: #fff; }
  .gd-count-bar { display: flex; justify-content: flex-end; margin: 10px 0 14px; }
  .gd-count { font-size: 12px; color: #9ca3af; }
  .gd-list { display: flex; flex-direction: column; gap: 8px; }
  .gd-item { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
  .gd-row { display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: center; padding: 14px 18px; }
  .gd-left { display: flex; align-items: center; min-width: 0; }
  .gd-article { font-size: 13px; color: #111827; font-weight: 600; line-height: 1.35; }
  .gd-meta { display: flex; gap: 8px; align-items: center; margin-top: 4px; }
  .gd-size { font-size: 11px; color: #4338ca; background: #eef2ff; border-radius: 999px; padding: 2px 8px; font-weight: 700; }
  .gd-small { font-size: 11px; color: #9ca3af; }
  .gd-qty { font-size: 14px; font-weight: 800; color: #dc2626; letter-spacing: .01em; }
  .gd-right { display: flex; align-items: center; gap: 10px; }
  .gd-timeline { display: flex; align-items: center; gap: 0; }
  .gd-step { min-width: 86px; display: flex; flex-direction: column; align-items: center; gap: 4px; }
  .gd-bubble { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; }
  .gd-bubble.on { background: #111827; color: #fff; }
  .gd-bubble.off { background: #e5e7eb; color: #9ca3af; }
  .gd-step-label { font-size: 10px; color: #9ca3af; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
  .gd-step-date { font-size: 11px; color: #111827; font-weight: 600; text-align: center; }
  .gd-step-date.off { color: #d1d5db; }
  .gd-conn { width: 24px; height: 2px; margin-bottom: 30px; background: #e5e7eb; }
  .gd-conn.on { background: #111827; }
  .gd-status { border-radius: 999px; padding: 3px 10px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; }
  .gd-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
  .gd-status.entregado { background: #f0fdf4; color: #15803d; }
  .gd-status.entregado .gd-dot { background: #22c55e; }
  .gd-status.pendiente { background: #fffbeb; color: #b45309; }
  .gd-status.pendiente .gd-dot { background: #f59e0b; }
  .gd-status.parcial { background: #eff6ff; color: #1d4ed8; }
  .gd-status.parcial .gd-dot { background: #3b82f6; }
  .gd-item.has-obs .gd-row { cursor: pointer; }
  .gd-item .gd-obs { display: none; border-top: 1px solid #f3f4f6; background: #fafafa; color: #6b7280; font-size: 12px; line-height: 1.6; padding: 10px 18px 12px 66px; }
  .gd-item.open .gd-obs { display: block; }
  @media (max-width: 1024px) {
    .gd-row { grid-template-columns: 1fr; }
    .gd-right { justify-content: flex-start; overflow-x: auto; padding-bottom: 4px; }
  }
</style>
@endpush

@section('content')
@php
  $fmt = static function ($value) {
      if (empty($value)) return null;
      try { return \Carbon\Carbon::parse($value)->format('d/m/Y h:i a'); } catch (\Throwable $e) { return null; }
  };
@endphp

<div class="gd-wrap">
  <div class="gd-card gd-header">
    <div>
      <div class="gd-kicker">
        <span class="gd-pill">PEDIDO #{{ $pedido['numero_pedido'] ?? '-' }}</span>
        <span class="gd-sub">Asesor: <strong>{{ $pedido['asesor'] ?? 'N/A' }}</strong></span>
      </div>
      <div class="gd-title">{{ $pedido['cliente'] ?? 'Cliente no especificado' }}</div>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <a class="gd-btn" href="{{ $historialBackUrl ?? route('despacho.historial-pendientes') }}">← Volver</a>
    </div>
  </div>

  <div class="gd-count-bar">
    <span class="gd-count"><span id="gdCount">{{ count($items ?? []) }}</span> articulo(s)</span>
  </div>

  <div class="gd-list" id="gdList">
    @forelse(($items ?? []) as $item)
      @php
        $pend = (int) ($item['pendientes'] ?? 0);
        $estadoRaw = strtoupper((string) ($item['estado_bodega'] ?? 'PENDIENTE'));
        $estadoVista = ($estadoRaw === 'ENTREGADO' && $pend > 0) ? 'PARCIAL' : (($estadoRaw === 'ENTREGADO') ? 'ENTREGADO' : 'PENDIENTE');

        $fechaPedido = $fmt($item['fecha_pedido'] ?? ($pedido['created_at'] ?? null));
        $fechaPendiente = $fmt($item['fecha_pendiente'] ?? null);
        $fechaEntrega = $fmt($item['fecha_entrega_bodega'] ?? null);
      @endphp

      @php $tieneObs = !empty($item['observaciones_bodega'] ?? null); @endphp
      <div class="gd-item {{ $tieneObs ? 'has-obs' : '' }}" data-estado="{{ $estadoVista }}">
        <div class="gd-row" @if($tieneObs) data-toggle-obs="1" aria-expanded="false" @endif>
          <div class="gd-left">
            <div style="min-width:0;">
              <div class="gd-article">{{ $item['prenda_nombre'] ?? ($item['descripcion']['nombre_prenda'] ?? 'Articulo') }}</div>
              <div class="gd-meta">
                <span class="gd-size">Talla {{ (($item['tipo'] ?? '') === 'EPP' || ($item['area'] ?? '') === 'EPP') ? '—' : ($item['talla'] ?? '—') }}</span>
                <span class="gd-qty">{{ (int)($item['cantidad'] ?? 0) }} uds</span>
                @if($pend > 0)<span class="gd-small" style="color:#f59e0b;font-weight:700;">· {{ $pend }} pend.</span>@endif
                @if(!empty($item['nota_bodega'] ?? null) || !empty($item['observaciones_bodega'] ?? null))<span class="gd-small">· nota ▲</span>@endif
              </div>
            </div>
          </div>

          <div class="gd-right">
            <div class="gd-timeline">
              <div class="gd-step">
                <div class="gd-bubble {{ $fechaPedido ? 'on' : 'off' }}">📝</div>
                <div class="gd-step-label">Pedido</div>
                <div class="gd-step-date {{ $fechaPedido ? '' : 'off' }}">{{ $fechaPedido ?? '—' }}</div>
              </div>
              <div class="gd-conn {{ ($fechaPendiente || $fechaEntrega) ? 'on' : '' }}"></div>
              <div class="gd-step">
                <div class="gd-bubble {{ $fechaPendiente ? 'on' : 'off' }}">⏳</div>
                <div class="gd-step-label">Pendiente</div>
                <div class="gd-step-date {{ $fechaPendiente ? '' : 'off' }}">{{ $fechaPendiente ?? '-' }}</div>
              </div>
              <div class="gd-conn {{ $fechaEntrega ? 'on' : '' }}"></div>
              <div class="gd-step">
                <div class="gd-bubble {{ $fechaEntrega ? 'on' : 'off' }}">✅</div>
                <div class="gd-step-label">Entrega</div>
                <div class="gd-step-date {{ $fechaEntrega ? '' : 'off' }}">{{ $fechaEntrega ?? '—' }}</div>
              </div>
            </div>
            <span class="gd-status {{ strtolower($estadoVista) }}">
              <span class="gd-dot"></span>{{ ucfirst(strtolower($estadoVista)) }}
            </span>
          </div>
        </div>

        @if(!empty($item['observaciones_bodega'] ?? null))
          <div class="gd-obs"><strong style="color:#374151;">Observacion:</strong> {{ $item['observaciones_bodega'] }}</div>
        @endif
      </div>
    @empty
      <div class="gd-card" style="padding:28px;text-align:center;color:#9ca3af;">No hay articulos para este pedido.</div>
    @endforelse
  </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.gd-item.has-obs .gd-row[data-toggle-obs="1"]').forEach(function (row) {
    row.addEventListener('click', function () {
      const item = row.closest('.gd-item');
      if (!item) return;
      const isOpen = item.classList.toggle('open');
      row.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  });
});
</script>
@endpush

