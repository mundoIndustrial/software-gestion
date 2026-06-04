{{-- Componente reutilizable para botón "Ver Recibo" --}}
<button class="btn-ver-recibos {{ $clase ?? '' }}" 
        onclick="abrirDetallesRecibos(@js($numeroPedido), {{ $prendaId ?? 'null' }}, @js($nombrePrenda), @js($tipoRecibo), {{ $idParcial ?? 'null' }}, @js($consecutivoParcial ?? $consecutivo ?? ''), {{ $reciboId ?? 'null' }}); return false;">
    <span class="material-symbols-rounded">visibility</span>
    {{ $texto ?? 'VER RECIBO' }}
</button>
