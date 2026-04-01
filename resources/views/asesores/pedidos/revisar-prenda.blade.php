@extends('layouts.asesores')

@section('title', 'Revisar Prenda')
@section('page-title', 'Revisar Prenda')

@section('extra_styles')
    {{-- Estilos necesarios para que el modal de prenda se vea igual que en crear-nuevo --}}
    <link rel="stylesheet" href="{{ asset('css/asesores/pedidos/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swal-z-index-fix.css') }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/prendas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modales/modal-exito-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/epp-modal.css') }}">
@endsection

@push('styles')
<style>
    .revisar-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
    }
    .revisar-toolbar form {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    .revisar-toolbar input {
        border-radius: 6px;
        border: 1px solid #d1d5db;
        padding: 0.4rem 0.6rem;
        min-width: 220px;
    }
    .revisar-toolbar button,
    .revisar-toolbar a.btn-secondary {
        border-radius: 6px;
        padding: 0.45rem 0.9rem;
        font-size: 0.85rem;
        min-width: 90px;
    }
    .revisar-meta {
        font-size: 0.85rem;
        color: #475569;
    }

    .revisar-table-wrapper {
        margin-top: 0.75rem;
        border-radius: 10px;
        overflow-x: auto;
        border: 1px solid #e5e7eb;
    }

    .revisar-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    .revisar-table th,
    .revisar-table td {
        padding: 0.65rem 0.75rem;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
    }
    .revisar-table th {
        background: #f8fafc;
        font-weight: 700;
        color: #0f172a;
        font-size: 0.8rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .revisar-table tbody tr:last-child td {
        border-bottom: none;
    }

    .revisar-acciones {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    .revisar-acciones button {
        border: none;
        border-radius: 6px;
        padding: 0.35rem 0.65rem;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-revisar {
        background: #e0e7ff;
        color: #1d4ed8;
    }
    .btn-revisar-edit {
        background: #dcfce7;
        color: #15803d;
    }
    .btn-revisar-approve {
        background: #fef9c3;
        color: #92400e;
    }

    .revisar-empty {
        padding: 1.5rem;
        text-align: center;
        color: #475569;
        background: #fff;
        border-radius: 12px;
        border: 1px dashed #cbd5e1;
        margin-top: 1rem;
    }

    .revisar-footer {
        margin-top: 0.5rem;
        text-align: right;
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

    .revisar-confirm-overlay {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.5);
        z-index: 1200001;
        padding: 1rem;
    }
    .revisar-confirm-card {
        background: #fff;
        border-radius: 12px;
        padding: 1.25rem 1.5rem;
        max-width: 420px;
        width: 100%;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
    }
    .revisar-confirm-card h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1rem;
        color: #0f172a;
    }
    .revisar-confirm-card p {
        margin: 0 0 1rem 0;
        color: #475569;
        font-size: 0.9rem;
    }
    .revisar-confirm-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="revisar-toolbar">
    <form method="GET" action="{{ route('asesores.pedidos.revisar-prenda') }}">
        <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por pedido, prenda o recibo">
        <button type="submit" class="btn btn-primary">Buscar</button>
        @if($search !== '')
            <a href="{{ route('asesores.pedidos.revisar-prenda') }}" class="btn btn-secondary">Limpiar</a>
        @endif
    </form>
    <span class="revisar-meta">Total: {{ $recibos->total() }} prendas</span>
</div>

@if($recibos->count() === 0)
    <div class="revisar-empty">
        No hay prendas por revisar.
    </div>
@else
    <div class="revisar-table-wrapper">
        <table class="revisar-table">
            <thead>
                <tr>
                    <th>Acciones</th>
                    <th>Pedido</th>
                    <th>Prenda / Recibo</th>
                    <th>Última actualización</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recibos as $item)
                    @php
                        $actualizado = \Illuminate\Support\Carbon::make($item['updated_at']);
                    @endphp
                    <tr>
                        <td>
                            <div class="revisar-acciones">
                                <button type="button" class="btn-revisar" onclick="abrirModalVerRecibo({{ $item['id'] }})">Ver</button>
                                <button type="button" class="btn-revisar-edit"
                                        onclick="editarPrendaDesdeRevision({{ $item['pedido_produccion_id'] }}, {{ $item['prenda_id'] }}, @js($item['nombre_prenda']))">
                                    Editar
                                </button>
                                <button type="button" class="btn-revisar-approve"
                                        onclick="aprobarReciboParaInsumos({{ $item['id'] }})">
                                    Aprobar
                                </button>
                            </div>
                        </td>
                        <td>#{{ $item['numero_pedido'] }}</td>
                        <td>
                            <div class="font-semibold">{{ $item['nombre_prenda'] }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $item['tipo_recibo'] }} · {{ $item['consecutivo_actual'] }}
                            </div>
                        </td>
                        <td>{{ $actualizado ? $actualizado->setTimezone('America/Bogota')->format('Y-m-d H:i') : '-' }}</td>
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

<div id="revisarConfirmModal" class="revisar-confirm-overlay" onclick="cerrarConfirmAprobar()">
    <div class="revisar-confirm-card" role="dialog" aria-modal="true" aria-labelledby="revisarConfirmTitle" onclick="event.stopPropagation()">
        <h3 id="revisarConfirmTitle">Confirmar aprobación</h3>
        <p>¿Estás seguro de aprobar esta prenda?</p>
        <div class="revisar-confirm-actions">
            <button type="button" class="btn btn-secondary" onclick="cerrarConfirmAprobar()">Cancelar</button>
            <button type="button" class="btn btn-primary" id="revisarConfirmAceptar">Sí, aprobar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.revisionPrendasMap = @json($recibos->getCollection()->keyBy('id'));

    // Reutilizar el mismo modal de recibos usado en Insumos (order-detail-modal)
    // Se carga bajo demanda via dynamic import para no acoplar la página al bundle completo.
    let _openOrderDetailModalHandler = null;
    async function _resolveOpenOrderDetailModalHandler() {
        if (typeof _openOrderDetailModalHandler === 'function') return _openOrderDetailModalHandler;

        const { PedidosRecibosModule } = await import('/js/modulos/pedidos-recibos/PedidosRecibosModule.js');
        const module = new PedidosRecibosModule();
        _openOrderDetailModalHandler = (pedidoId, prendaId, tipoRecibo, prendaIndex = null) =>
            module.abrirRecibo(pedidoId, prendaId, tipoRecibo, prendaIndex);

        return _openOrderDetailModalHandler;
    }

    function _abrirDetalleRecibo(pedidoId, prendaId, tipoRecibo) {
        const parsedPedidoId = parseInt(pedidoId, 10) || null;
        const parsedPrendaId = (prendaId === 'null' || prendaId === '' || !prendaId)
            ? null
            : (parseInt(prendaId, 10) || null);

        _resolveOpenOrderDetailModalHandler()
            .then((handler) => handler(parsedPedidoId, parsedPrendaId, tipoRecibo))
            .catch((error) => {
                console.error('[revisar-prenda] Error abriendo recibo:', error);
                alert('No se pudo abrir el recibo. Recarga la página e intenta de nuevo.');
            });
    }

    function abrirModalVerRecibo(reciboId) {
        const registro = window.revisionPrendasMap[String(reciboId)];
        if (!registro) return;

        // Abrir el recibo COSTURA en el modal estándar (igual que Insumos)
        _abrirDetalleRecibo(registro.pedido_produccion_id, registro.prenda_id, 'COSTURA');
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

    function abrirConfirmAprobar(onConfirm) {
        const modal = document.getElementById('revisarConfirmModal');
        const aceptar = document.getElementById('revisarConfirmAceptar');
        if (!modal || !aceptar) return;

        const nuevoAceptar = aceptar.cloneNode(true);
        aceptar.parentNode.replaceChild(nuevoAceptar, aceptar);
        nuevoAceptar.addEventListener('click', () => {
            cerrarConfirmAprobar();
            onConfirm();
        });

        modal.style.display = 'flex';
    }

    function cerrarConfirmAprobar() {
        const modal = document.getElementById('revisarConfirmModal');
        if (modal) modal.style.display = 'none';
    }

    async function ejecutarAprobarRecibo(reciboId) {
        try {
            const res = await fetch(`{{ route('asesores.pedidos.revisar-prenda.aprobar-insumos', ['reciboId' => '__RID__']) }}`.replace('__RID__', String(reciboId)), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.content || '',
                    'Accept': 'application/json'
                }
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                alert(data.message || 'No se pudo aprobar el recibo.');
                return;
            }

            window.location.reload();
        } catch (e) {
            alert('Error de red al aprobar el recibo.');
        }
    }

    async function aprobarReciboParaInsumos(reciboId) {
        abrirConfirmAprobar(() => ejecutarAprobarRecibo(reciboId));
        return;
    }

    window.addEventListener('prendaActualizada', function () {
        window.location.reload();
    });
</script>

{{-- Dependencias del modal order-detail (misma superficie que /insumos/materiales) --}}
<script defer src="{{ asset('js/ordersjs/order-detail-modal-manager.js') }}"></script>
<script defer src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
<script defer src="{{ asset('js/orders-scripts/image-gallery-zoom.js') }}"></script>
<script defer src="{{ asset('js/asesores/receipt-manager.js') }}"></script>
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>

<script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/manejadores-variaciones.js') }}?v={{ config('app.asset_version', time()) }}"></script>
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
