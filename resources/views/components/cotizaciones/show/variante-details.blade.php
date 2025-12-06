{{-- Variante Details --}}
<div style="font-size: 0.9rem;">
    <div style="margin-bottom: 8px;">
        <span style="font-weight: 600; color: #0066cc;">Color:</span>
        <span style="color: #1e293b;">{{ $variante->color ? $variante->color->nombre : '-' }}</span>
    </div>
    <div style="margin-bottom: 8px;">
        <span style="font-weight: 600; color: #0066cc;">Tela:</span>
        <span style="color: #1e293b;">{{ $variante->tela ? $variante->tela->nombre : '-' }}</span>
        @if($variante->tela && $variante->tela->referencia)
            <div style="color: #64748b; font-size: 0.8rem; margin-top: 2px;">Ref: {{ $variante->tela->referencia }}</div>
        @endif
    </div>
    <div style="margin-bottom: 8px;">
        <span style="font-weight: 600; color: #0066cc;">Manga:</span>
        <span style="color: #1e293b;">{{ $variante->tipoManga ? $variante->tipoManga->nombre : '-' }}</span>
        @php
            $obsArray = $variante->descripcion_adicional ? explode(' | ', $variante->descripcion_adicional) : [];
            $obsManga = null;
            foreach ($obsArray as $obs) {
                if (strpos($obs, 'Manga:') === 0) {
                    $obsManga = trim(str_replace('Manga:', '', $obs));
                }
            }
        @endphp
        @if($obsManga)
            <div style="color: #64748b; font-size: 0.8rem; margin-top: 2px;">{{ $obsManga }}</div>
        @endif
    </div>
    <div style="margin-bottom: 8px;">
        <span style="font-weight: 600; color: #0066cc;">Bolsillos:</span>
        @if($variante->tiene_bolsillos)
            <span style="background: #10b981; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; font-weight: 600;">Sí</span>
            @php
                $obsBolsillos = null;
                foreach ($obsArray as $obs) {
                    if (strpos($obs, 'Bolsillos:') === 0) {
                        $obsBolsillos = trim(str_replace('Bolsillos:', '', $obs));
                    }
                }
            @endphp
            @if($obsBolsillos)
                <div style="color: #64748b; font-size: 0.8rem; margin-top: 2px;">{{ $obsBolsillos }}</div>
            @endif
        @else
            <span style="background: #cbd5e1; color: #475569; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; font-weight: 600;">No</span>
        @endif
    </div>
    @if($variante->tipoBroche)
        <div style="margin-bottom: 8px;">
            <span style="font-weight: 600; color: #0066cc;">{{ $variante->tipoBroche->nombre }}</span>
            @php
                $obsBroche = null;
                foreach ($obsArray as $obs) {
                    if (strpos($obs, 'Broche:') === 0) {
                        $obsBroche = trim(str_replace('Broche:', '', $obs));
                    }
                }
            @endphp
            @if($obsBroche)
                <div style="color: #64748b; font-size: 0.8rem; margin-top: 2px;">{{ $obsBroche }}</div>
            @endif
        </div>
    @endif
    <div style="margin-bottom: 8px;">
        <span style="font-weight: 600; color: #0066cc;">Reflectivo:</span>
        @if($variante->tiene_reflectivo)
            <span style="background: #10b981; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; font-weight: 600;">Sí</span>
            @php
                $obsReflectivo = null;
                foreach ($obsArray as $obs) {
                    if (strpos($obs, 'Reflectivo:') === 0) {
                        $obsReflectivo = trim(str_replace('Reflectivo:', '', $obs));
                    }
                }
            @endphp
            @if($obsReflectivo)
                <div style="color: #64748b; font-size: 0.8rem; margin-top: 2px;">{{ $obsReflectivo }}</div>
            @endif
        @else
            <span style="background: #cbd5e1; color: #475569; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; font-weight: 600;">No</span>
        @endif
    </div>
</div>
