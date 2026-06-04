{{-- Componente reutilizable para botón "Agregar Novedad" --}}
<button class="btn-agregar-novedad {{ $clase ?? '' }}" 
        data-numero-pedido="{{ $numeroPedido ?? ($consecutivo ?? '') }}"
        data-prenda-id="{{ $prendaId ?? '' }}"
        data-prenda-bodega-id="{{ $prendaBodegaId ?? '' }}"
        data-pedido-id="{{ $pedidoId ?? '' }}"
        data-nombre-prenda="{{ $nombrePrenda ?? '' }}"
        data-numero-recibo="{{ $consecutivo ?? '' }}"
        data-recibo-id="{{ $reciboId ?? '' }}"
        onclick="abrirModalNovedadDesdeElemento(this)">
    <span class="material-symbols-rounded">comment</span>
    {{ $texto ?? 'AGREGAR NOVEDAD' }}
</button>
