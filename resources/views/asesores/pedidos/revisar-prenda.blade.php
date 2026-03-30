@extends('layouts.asesores')

@section('title', 'Revisar Prenda')
@section('page-title', 'Revisar Prenda')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos/index.css') }}">
<style>
    .revisar-toolbar {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }
    .revisar-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    }
    .revisar-table th,
    .revisar-table td {
        padding: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
        font-size: 0.875rem;
        text-align: left;
    }
    .revisar-table th {
        background: #0f4aa0;
        color: #fff;
        font-weight: 600;
        white-space: nowrap;
    }
    .revisar-acciones {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    .btn-revisar {
        border: none;
        border-radius: 6px;
        padding: 0.4rem 0.65rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-revisar-ver {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .btn-revisar-editar {
        background: #dcfce7;
        color: #166534;
    }
    .revisar-empty {
        padding: 2rem;
        text-align: center;
        color: #6b7280;
        background: #fff;
        border-radius: 12px;
        border: 1px dashed #cbd5e1;
    }
    .modal-ver-recibo-overlay {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.6);
        z-index: 1200000;
        padding: 1rem;
    }
    .modal-ver-recibo {
        width: min(900px, 100%);
        max-height: 90vh;
        overflow-y: auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    }
    .modal-ver-recibo-header {
        background: #0f4aa0;
        color: #fff;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .modal-ver-recibo-body {
        padding: 1rem 1.25rem 1.5rem;
    }
    .ver-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .ver-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.75rem;
        background: #f8fafc;
    }
    .ver-card-label {
        font-size: 0.75rem;
        color: #64748b;
        margin-bottom: 0.25rem;
        text-transform: uppercase;
        font-weight: 700;
    }
    .ver-card-value {
        color: #1f2937;
        font-weight: 600;
        word-break: break-word;
    }
    .ver-imagenes {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 0.75rem;
    }
    .ver-imagenes img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        background: #f3f4f6;
    }
</style>
@endpush

@section('content')
<div class="revisar-toolbar">
    <form method="GET" action="{{ route('asesores.pedidos.revisar-prenda') }}" style="display:flex;gap:0.5rem;flex-wrap:wrap;">
        <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por pedido, prenda o recibo"
               style="padding:0.65rem 0.8rem;border:1px solid #d1d5db;border-radius:8px;min-width:260px;">
        <button type="submit" class="btn btn-primary">Buscar</button>
        @if($search !== '')
            <a href="{{ route('asesores.pedidos.revisar-prenda') }}" class="btn btn-secondary">Limpiar</a>
        @endif
    </form>
</div>

@if($recibos->count() === 0)
    <div class="revisar-empty">
        No hay prendas para revisar con recibo de costura en estado <strong>DEVUELTO A ASESOR</strong>.
    </div>
@else
    <div style="overflow:auto;">
        <table class="revisar-table">
            <thead>
                <tr>
                    <th>Acciones</th>
                    <th>N° Pedido</th>
                    <th>Prenda</th>
                    <th>Recibo</th>
                    <th>Estado</th>
                    <th>Última actualización</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recibos as $item)
                    <tr>
                        <td>
                            <div class="revisar-acciones">
                                <button type="button" class="btn-revisar btn-revisar-ver" onclick="abrirModalVerRecibo({{ $item['id'] }})">Ver</button>
                                <button type="button" class="btn-revisar btn-revisar-editar"
                                        onclick="editarPrendaDesdeRevision({{ $item['pedido_produccion_id'] }}, {{ $item['prenda_id'] }}, @js($item['nombre_prenda']))">
                                    Editar
                                </button>
                            </div>
                        </td>
                        <td>#{{ $item['numero_pedido'] }}</td>
                        <td>{{ $item['nombre_prenda'] }}</td>
                        <td>{{ $item['tipo_recibo'] }}-{{ $item['consecutivo_actual'] }}</td>
                        <td>{{ $item['estado'] }}</td>
                        <td>{{ $item['updated_at'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top:1rem;">
        {{ $recibos->links() }}
    </div>
@endif

@include('asesores.pedidos.components.modals')

<div id="modalVerReciboPrenda" class="modal-ver-recibo-overlay" onclick="cerrarModalVerRecibo(event)">
    <div class="modal-ver-recibo" onclick="event.stopPropagation()">
        <div class="modal-ver-recibo-header">
            <h3 style="margin:0;">Recibo de la Prenda</h3>
            <button type="button" onclick="cerrarModalVerRecibo()" style="background:transparent;border:none;color:#fff;font-size:1.5rem;cursor:pointer;">×</button>
        </div>
        <div class="modal-ver-recibo-body">
            <div class="ver-grid" id="verReciboInfo"></div>
            <div>
                <h4 style="margin:0 0 0.75rem 0;color:#1f2937;">Imágenes de la prenda</h4>
                <div id="verReciboImagenes" class="ver-imagenes"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.revisionPrendasMap = @json($recibos->getCollection()->keyBy('id'));

    function abrirModalVerRecibo(reciboId) {
        const registro = window.revisionPrendasMap[String(reciboId)];
        if (!registro) return;

        const info = [
            ['Pedido', `#${registro.numero_pedido || '-'}`],
            ['Prenda', registro.nombre_prenda || '-'],
            ['Recibo', `${registro.tipo_recibo || '-'}-${registro.consecutivo_actual || '-'}`],
            ['Estado', registro.estado || '-'],
            ['Área', registro.area || '-'],
            ['Fecha envío', registro.fecha_envio || '-'],
            ['Fecha llegada', registro.fecha_llegada || '-'],
            ['Notas', registro.notas || '-']
        ];

        const infoHtml = info.map(([label, value]) => `
            <div class="ver-card">
                <div class="ver-card-label">${label}</div>
                <div class="ver-card-value">${String(value)}</div>
            </div>
        `).join('');

        const imagenes = Array.isArray(registro.imagenes_prenda) ? registro.imagenes_prenda : [];
        const imagenesHtml = imagenes.length > 0
            ? imagenes.map(url => `<img src="${url}" alt="Imagen prenda">`).join('')
            : '<div style="color:#6b7280;">Sin imágenes registradas para esta prenda.</div>';

        document.getElementById('verReciboInfo').innerHTML = infoHtml;
        document.getElementById('verReciboImagenes').innerHTML = imagenesHtml;
        document.getElementById('modalVerReciboPrenda').style.display = 'flex';
    }

    function cerrarModalVerRecibo(event) {
        if (!event || event.target.id === 'modalVerReciboPrenda') {
            document.getElementById('modalVerReciboPrenda').style.display = 'none';
        }
    }

    function editarPrendaDesdeRevision(pedidoId, prendaId, nombrePrenda) {
        if (typeof window.editarPrendaDePedido !== 'function') {
            alert('No está disponible el editor de prendas. Recarga la página.');
            return;
        }

        const prendaBase = {
            id: Number(prendaId),
            prenda_pedido_id: Number(prendaId),
            nombre_prenda: nombrePrenda || 'PRENDA'
        };

        window.datosEdicionPedido = { id: Number(pedidoId), prendas: [prendaBase] };
        window.editarPrendaDePedido(prendaBase, 0, Number(pedidoId));
    }

    window.addEventListener('prendaActualizada', function () {
        window.location.reload();
    });
</script>

<script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/prenda-editor.js') }}?v={{ config('app.asset_version', time()) }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}?v={{ config('app.asset_version', time()) }}"></script>
<script defer src="{{ js_asset('js/modulos/crear-pedido/telas/telas-module/manejo-imagenes.js') }}?v={{ config('app.asset_version', time()) }}"></script>
<script defer src="{{ js_asset('js/componentes/prenda-form-collector.js') }}?v={{ config('app.asset_version', time()) }}"></script>
<script defer src="{{ js_asset('js/componentes/prenda-editor-pedidos-adapter.js') }}?v={{ config('app.asset_version', time()) }}"></script>

<script src="{{ asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/inicializadores/init-storage-servicios.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/selector-modo-proceso.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-por-tallas.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/extension-editor-tallas-multiproducto.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/extension-guardar-datos-tallas-extendida.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/proceso-galeria-service.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/proceso-delete-service.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/proceso-modal-loader-service.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/proceso-card-renderer-service.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/componentes/procesos-imagenes-storage.js') }}"></script>
<script src="{{ asset('js/componentes/manejo-imagenes-proceso.js') }}"></script>
<script src="{{ asset('js/componentes/manejador-imagen-proceso-con-indice.js') }}"></script>
@endpush
