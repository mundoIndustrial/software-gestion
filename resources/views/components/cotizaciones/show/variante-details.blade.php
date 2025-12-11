{{-- Variante Details --}}
<div style="font-size: 0.9rem;">
    @php
        // Extraer tela y referencia de telas_multiples (ya decodificado por el modelo)
        $tela = null;
        $referencia = null;
        if ($variante->telas_multiples && is_array($variante->telas_multiples) && !empty($variante->telas_multiples)) {
            $primeraTela = $variante->telas_multiples[0] ?? [];
            $tela = $primeraTela['tela'] ?? null;
            $referencia = $primeraTela['referencia'] ?? null;
        }
        
        // Extraer observaciones de descripcion_adicional
        $obsArray = $variante->descripcion_adicional ? explode(' | ', $variante->descripcion_adicional) : [];
        $obsManga = null;
        $obsBolsillos = null;
        $obsBroche = null;
        $obsReflectivo = null;
        
        foreach ($obsArray as $obs) {
            if (strpos($obs, 'Manga:') === 0) {
                $obsManga = trim(str_replace('Manga:', '', $obs));
            } elseif (strpos($obs, 'Bolsillos:') === 0) {
                $obsBolsillos = trim(str_replace('Bolsillos:', '', $obs));
            } elseif (strpos($obs, 'Broche:') === 0) {
                $obsBroche = trim(str_replace('Broche:', '', $obs));
            } elseif (strpos($obs, 'Reflectivo:') === 0) {
                $obsReflectivo = trim(str_replace('Reflectivo:', '', $obs));
            }
        }
    @endphp

    {{-- Color --}}
    <div style="margin-bottom: 8px;">
        <span style="font-weight: 600; color: #0066cc;">Color:</span>
        <span style="color: #1e293b;">{{ $variante->color ?? '-' }}</span>
    </div>

    {{-- Tela --}}
    <div style="margin-bottom: 8px;">
        <span style="font-weight: 600; color: #0066cc;">Tela:</span>
        <span style="color: #1e293b;">{{ $tela ?? '-' }}</span>
    </div>

    {{-- Referencia --}}
    <div style="margin-bottom: 8px;">
        <span style="font-weight: 600; color: #0066cc;">Referencia:</span>
        <span style="color: #1e293b;">{{ $referencia ?? '-' }}</span>
    </div>

    {{-- Manga --}}
    @if($variante->tipo_manga_id)
        <div style="margin-bottom: 8px;">
            <span style="font-weight: 600; color: #0066cc;">Manga:</span>
            <span style="color: #1e293b;">{{ $variante->manga->nombre ?? $variante->tipo_manga ?? '-' }}</span>
            @if($obsManga)
                <div style="color: #64748b; font-size: 0.8rem; margin-top: 2px;">{{ $obsManga }}</div>
            @endif
        </div>
    @endif

    {{-- Bolsillos --}}
    <div style="margin-bottom: 8px;">
        <span style="font-weight: 600; color: #0066cc;">Bolsillos:</span>
        @if($obsBolsillos)
            <div style="color: #64748b; font-size: 0.8rem;">{{ $obsBolsillos }}</div>
        @else
            <span style="color: #1e293b;">-</span>
        @endif
    </div>

    {{-- Broche/Botón --}}
    @if($variante->tipo_broche_id)
        <div style="margin-bottom: 8px;">
            @php
                $nombreBroche = 'Broche';
                if ($variante->broche) {
                    $nombreBroche = $variante->broche->nombre ?? 'Broche';
                }
            @endphp
            <span style="font-weight: 600; color: #0066cc;">{{ $nombreBroche }}:</span>
            @if($obsBroche)
                <div style="color: #64748b; font-size: 0.8rem;">{{ $obsBroche }}</div>
            @else
                <span style="color: #1e293b;">-</span>
            @endif
        </div>
    @endif

    {{-- Reflectivo --}}
    <div style="margin-bottom: 8px;">
        <span style="font-weight: 600; color: #0066cc;">Reflectivo:</span>
        @if($obsReflectivo)
            <div style="color: #64748b; font-size: 0.8rem;">{{ $obsReflectivo }}</div>
        @else
            <span style="color: #1e293b;">-</span>
        @endif
    </div>
</div>
