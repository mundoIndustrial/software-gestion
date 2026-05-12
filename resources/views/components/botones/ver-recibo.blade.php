{{-- Componente reutilizable para botón "Ver Recibo" --}}
<button class="btn-ver-recibos {{ $clase ?? '' }}" 
        onclick="abrirDetallesRecibos('{{ $numeroPedido }}', {{ $prendaId }}, '{{ $nombrePrenda }}', '{{ $tipoRecibo }}', {{ $idParcial ?? 'null' }}, '{{ $consecutivo }}'); return false;">
    <span class="material-symbols-rounded">visibility</span>
    {{ $texto ?? 'VER RECIBO' }}
</button>
