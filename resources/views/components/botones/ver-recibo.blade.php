{{-- Componente reutilizable para botón "Ver Recibo" --}}
<button class="btn-ver-recibos {{ $clase ?? '' }}" 
        onclick="abrirDetallesRecibos('{{ $numeroPedido }}', {{ $prendaId }}, '{{ addslashes((string)$nombrePrenda) }}', '{{ $tipoRecibo }}', {{ $idParcial ?? 'null' }}, '{{ $consecutivo }}', {{ $reciboId ?? 'null' }}); return false;">
    <span class="material-symbols-rounded">visibility</span>
    {{ $texto ?? 'VER RECIBO' }}
</button>
