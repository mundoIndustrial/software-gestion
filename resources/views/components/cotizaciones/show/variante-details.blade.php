{{-- Variante Details --}}
<div style="font-size: 0.9rem;">
    @php
        // Extraer todas las telas de telas_multiples (ya decodificado por el modelo)
        $telasMultiples = [];
        if ($variante->telas_multiples && is_array($variante->telas_multiples) && !empty($variante->telas_multiples)) {
            $telasMultiples = $variante->telas_multiples;
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

    {{-- Prenda de Bodega --}}
    @if($variante->prenda_bodega)
        <div style="margin-bottom: 8px; padding: 8px 12px; background: #dcfce7; border-radius: 6px; border-left: 3px solid #16a34a;">
            <span style="font-weight: 600; color: #15803d;">
                <i class="fas fa-warehouse" style="margin-right: 6px;"></i> Prenda de Bodega:
            </span>
            <span style="color: #166534; font-weight: 600;">✅ Sí</span>
        </div>
    @endif

    {{-- Telas Múltiples --}}
    @if(!empty($telasMultiples))
        <div style="margin-bottom: 12px;">
            <span style="font-weight: 600; color: #0066cc; display: block; margin-bottom: 8px;">Telas:</span>
            @foreach($telasMultiples as $index => $telaData)
                <div style="background: #f8fafc; padding: 8px 12px; border-radius: 6px; margin-bottom: 8px; border-left: 3px solid #0066cc;">
                    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                        <div>
                            <span style="font-size: 0.85rem; color: #64748b;">Color:</span>
                            <span style="color: #1e293b; font-weight: 500;">{{ $telaData['color'] ?? '-' }}</span>
                        </div>
                        <div>
                            <span style="font-size: 0.85rem; color: #64748b;">Tela:</span>
                            <span style="color: #1e293b; font-weight: 500;">{{ $telaData['tela'] ?? '-' }}</span>
                        </div>
                        <div>
                            <span style="font-size: 0.85rem; color: #64748b;">Referencia:</span>
                            <span style="color: #1e293b; font-weight: 500;">{{ $telaData['referencia'] ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Fallback si no hay telas_multiples --}}
        <div style="margin-bottom: 8px;">
            <span style="font-weight: 600; color: #0066cc;">Tela:</span>
            <span style="color: #1e293b;">-</span>
        </div>
        <div style="margin-bottom: 8px;">
            <span style="font-weight: 600; color: #0066cc;">Referencia:</span>
            <span style="color: #1e293b;">-</span>
        </div>
    @endif

    {{-- Tipo de Jean/Pantalón --}}
    @if($variante->es_jean_pantalon && $variante->tipo_jean_pantalon)
        @php
            $nombrePrenda = strtoupper($variante->prenda->nombre_producto ?? '');
            $esJean = str_contains($nombrePrenda, 'JEAN');
            $tipoLabel = $esJean ? 'Jean' : 'Pantalón';
        @endphp
        <div style="margin-bottom: 8px;">
            <span style="font-weight: 600; color: #0066cc;">Tipo de {{ $tipoLabel }}:</span>
            <span style="color: #1e293b; background: #f0f4f8; padding: 2px 8px; border-radius: 4px; font-weight: 500;">{{ $variante->tipo_jean_pantalon }}</span>
        </div>
    @endif

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
