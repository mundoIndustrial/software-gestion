{{-- Componente reutilizable para botón "Agregar Novedad" --}}
<button class="btn-agregar-novedad {{ $clase ?? '' }}" 
        onclick="abrirModalNovedad('{{ $numeroPedido }}', {{ $prendaId }}, '{{ $nombrePrenda }}', {{ $consecutivo }})">
    <span class="material-symbols-rounded">comment</span>
    {{ $texto ?? 'AGREGAR NOVEDAD' }}
</button>
