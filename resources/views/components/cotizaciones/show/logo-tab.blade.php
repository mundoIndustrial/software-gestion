{{-- Logo/Bordado Tab --}}
@php
    $idLogo = \App\Models\TipoCotizacion::getIdPorCodigo('L');
    $idCombinada = \App\Models\TipoCotizacion::getIdPorCodigo('PL');
    $tabActivoPorDefecto = 'prendas';
    if ($cotizacion->tipo_cotizacion_id === $idLogo || $cotizacion->tipo_cotizacion_id === $idCombinada) {
        $tabActivoPorDefecto = 'bordado';
    }
@endphp
<div id="tab-bordado" class="tab-content {{ $tabActivoPorDefecto === 'bordado' ? 'active' : '' }}">
    @if($logo)
        {{-- Tipo Venta del Logo --}}
        @if($logo->tipo_venta)
            <div style="
                margin-bottom: 2rem;
                display: flex;
                align-items: center;
                gap: 1rem;
            ">
                <span style="
                    display: inline-block;
                    padding: 0.5rem 1rem;
                    border-radius: 6px;
                    font-weight: 700;
                    font-size: 0.85rem;
                    background: linear-gradient(135deg, #0066cc, #0052a3);
                    color: white;
                    box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
                ">
                    <i class="fas fa-tag"></i> Tipo Venta: <strong>{{ $logo->tipo_venta }}</strong>
                </span>
            </div>
        @endif
        
        {{-- Descripción del Logo --}}
        @if($logo)
        {{-- Descripción y Secciones --}}
        @if($logo->descripcion)
            <div class="info-card">
                <div class="info-section no-border">
                    <h4 class="section-title"><i class="fas fa-pen"></i> Descripción</h4>
                    <p>{{ $logo->descripcion }}</p>
                </div>
            </div>
        @endif

        @if ($logo->secciones && is_array($logo->secciones) && count($logo->secciones) > 0)
            <div class="info-card">
                <div class="info-section no-border">
                    <h4 class="section-title"><i class="fas fa-tshirt"></i> Secciones de Prendas</h4>
                    @foreach ($logo->secciones as $seccion)
                        <div class="seccion-item">
                            <p class="seccion-prenda"><strong>Prenda:</strong> {{ $seccion['ubicacion'] }}</p>
                            @if (!empty($seccion['tallas']))
                                <div class="tag-group">
                                    <strong>Tallas:</strong>
                                    @foreach ($seccion['tallas'] as $talla)
                                        <span class="tag tag-green">{{ $talla['talla'] }}: {{ $talla['cantidad'] }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if (!empty($seccion['opciones']))
                                <div class="tag-group">
                                    <strong>Ubicaciones:</strong>
                                    @foreach ($seccion['opciones'] as $opcion)
                                        <span class="tag tag-blue">{{ $opcion }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if (!empty($seccion['observaciones']))
                                <p><strong>Observaciones:</strong> {{ $seccion['observaciones'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Técnicas y Observaciones Técnicas --}}
        @php
            $tecnicas = $logo->tecnicas;
            if (is_string($tecnicas)) { $tecnicas = json_decode($tecnicas, true) ?? []; }
            $tecnicas = is_array($tecnicas) ? $tecnicas : [];
        @endphp
        @if(!empty($tecnicas) || $logo->observaciones_tecnicas)
            <div class="info-card">
                <div class="info-section no-border">
                    <h4 class="section-title"><i class="fas fa-tools"></i> Técnicas y Observaciones</h4>
                    @if(!empty($tecnicas))
                        <div class="tag-group">
                            <strong>Técnicas:</strong>
                            @foreach($tecnicas as $tecnica)
                                <span class="tag tag-blue">{{ is_array($tecnica) ? implode(', ', $tecnica) : $tecnica }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if($logo->observaciones_tecnicas)
                        <p style="margin-top: 1rem;"><strong>Observaciones Técnicas:</strong> {{ is_array($logo->observaciones_tecnicas) ? implode(', ', $logo->observaciones_tecnicas) : $logo->observaciones_tecnicas }}</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Observaciones Generales --}}
        @php
            $observaciones_generales = $logo->observaciones_generales;
            if (is_string($observaciones_generales)) { $observaciones_generales = json_decode($observaciones_generales, true) ?? []; }
            $observaciones_generales = is_array($observaciones_generales) ? $observaciones_generales : [];
        @endphp
        @if(!empty($observaciones_generales))
            <div class="info-card">
                <div class="info-section no-border">
                    <h4 class="section-title"><i class="fas fa-comment"></i> Observaciones Generales</h4>
                    <ul class="obs-list">
                        @foreach($observaciones_generales as $obs)
                            @php
                                $texto = is_array($obs) ? ($obs['texto'] ?? '') : $obs;
                                $tipo = is_array($obs) ? ($obs['tipo'] ?? 'texto') : 'texto';
                                $valor = is_array($obs) ? ($obs['valor'] ?? '') : '';
                            @endphp
                            <li>
                                <span>{{ $texto }}</span>
                                @if($tipo === 'checkbox' && $valor)
                                    <i class="fas fa-check-square obs-check"></i>
                                @elseif($tipo === 'texto' && !empty($valor))
                                    <span class="tag tag-gray">{{ $valor }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    @endif

        {{-- Imágenes desde fotos --}}
        @if($logo->fotos && $logo->fotos->count() > 0)
            <div style="
                font-size: 1.4rem;
                font-weight: 800;
                color: #1e293b;
                margin-top: 2rem;
                margin-bottom: 1.75rem;
                padding-bottom: 1rem;
                border-bottom: 3px solid #0ea5e9;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            ">
                <i class="fas fa-images" style="color: #0ea5e9; font-size: 1.4rem;"></i> Imágenes ({{ $logo->fotos->count() }})
            </div>
            <div style="
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            ">
                @php
                    $fotosArray = $logo->fotos->map(fn($f) => $f->url)->toArray();
                    $fotosJson = json_encode($fotosArray);
                @endphp
                @foreach($logo->fotos as $index => $foto)
                    <div style="border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 6px 16px rgba(0, 0, 0, 0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)'">
                        <img src="{{ $foto->url }}" alt="Logo" 
                             width="300" height="150"
                             style="width: 100%; height: 150px; object-fit: cover; cursor: pointer; transition: transform 0.3s ease;"
                             onmouseover="this.style.transform='scale(1.05)'"
                             onmouseout="this.style.transform=''"
                             onclick="abrirModalImagen('{{ $foto->url }}', 'Logo - Imagen {{ $index + 1 }}', {{ $fotosJson }}, {{ $index }})">
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 3rem 2rem; color: #94a3b8; font-style: italic; font-size: 0.95rem;">
                <i class="fas fa-images" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                Sin imágenes de logo
            </div>
        @endif
    @else
        <div style="text-align: center; padding: 3rem 2rem; color: #94a3b8; font-style: italic; font-size: 0.95rem;">
            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
            {{ $esLogo ? 'Sin información de logo' : 'Sin información de LOGO' }}
        </div>
    @endif
</div>
