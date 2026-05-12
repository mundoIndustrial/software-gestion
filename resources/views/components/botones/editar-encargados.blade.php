{{-- Componente reutilizable para botón "Editar Encargados" --}}
<button class="btn-ver-distribucion {{ $clase ?? '' }}"
        data-visible-filtro="{{ $filtro ?? '' }}"
        id="btn-editar-encargados-{{ $tipoRecibo }}-{{ $prendaId }}"
        data-recibo-id="{{ $reciboId }}"
        data-pedido-id="{{ $pedidoId }}"
        data-prenda-id="{{ $prendaId }}"
        data-numero-recibo="{{ $numeroRecibo }}"
        data-numero-pedido="{{ $numeroPedido }}"
        data-nombre="{{ $nombrePrenda }}"
        data-tipo-recibo="{{ $tipoRecibo }}"
        onclick="abrirEditarEncargados(this)">
    <span class="material-symbols-rounded">edit</span>
    {{ $texto ?? 'EDITAR ENCARGADOS' }}
</button>
