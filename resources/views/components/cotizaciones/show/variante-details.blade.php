{{-- Variante Details --}}
<div style="font-size: 0.9rem;">
    @php
        // Decodificar múltiples telas si existen
        $telasMultiples = null;
        if ($variante->telas_multiples) {
            $telasMultiples = json_decode($variante->telas_multiples, true);
        }
    @endphp

    @if($telasMultiples && is_array($telasMultiples) && count($telasMultiples) > 0)
        {{-- MOSTRAR MÚLTIPLES TELAS --}}
        <div style="margin-bottom: 12px; padding: 10px; background: #f0f7ff; border-left: 3px solid #0066cc; border-radius: 4px;">
            <div style="font-weight: 700; color: #0066cc; margin-bottom: 8px; font-size: 0.95rem;">
                <i class="fas fa-palette"></i> Telas ({{ count($telasMultiples) }})
            </div>
            @foreach($telasMultiples as $index => $telaData)
                <div style="margin-bottom: 10px; padding: 8px; background: white; border-radius: 3px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-weight: 600; color: #1e293b; font-size: 0.9rem;">
                            <i class="fas fa-circle" style="color: #0066cc; font-size: 0.6rem; margin-right: 4px;"></i>
                            Tela {{ $index + 1 }}
                        </span>
                        @if($telaData['color'])
                            <span style="background: #dbeafe; color: #0066cc; padding: 2px 8px; border-radius: 3px; font-size: 0.75rem; font-weight: 600;">
                                {{ $telaData['color'] }}
                            </span>
                        @endif
                    </div>
                    <div style="margin-left: 16px; font-size: 0.85rem;">
                        @if($telaData['tela'])
                            <div style="margin-bottom: 4px;">
                                <span style="font-weight: 600; color: #475569;">Tela:</span>
                                <span style="color: #1e293b;">{{ $telaData['tela'] }}</span>
                            </div>
                        @endif
                        @if($telaData['referencia'])
                            <div style="margin-bottom: 4px;">
                                <span style="font-weight: 600; color: #475569;">Ref:</span>
                                <span style="color: #0066cc; font-weight: 600;">{{ $telaData['referencia'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- MOSTRAR TELA ÚNICA (COMPATIBILIDAD) --}}
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
    @endif
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
