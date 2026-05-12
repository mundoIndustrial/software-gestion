{{-- Componente reutilizable para botón "Ver Distribución" --}}
<button class="btn-ver-distribucion {{ $clase ?? '' }}"
        data-visible-filtro="{{ $filtro ?? '' }}"
        id="btn-distribucion-{{ $tipoRecibo }}-{{ $prendaId }}"
        data-recibo-id="{{ $reciboId }}"
        data-prenda-id="{{ $prendaId }}"
        data-numero-recibo="{{ $numeroRecibo }}"
        onclick="abrirDistribucionRecibo(this)">
    <span class="material-symbols-rounded">share</span>
    {{ $texto ?? 'VER DISTRIBUCIÓN' }}
</button>
