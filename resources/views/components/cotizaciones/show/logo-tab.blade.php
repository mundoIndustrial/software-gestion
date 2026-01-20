{{-- Logo/Bordado Tab --}}
@php
    $idLogo = \App\Models\TipoCotizacion::getIdPorCodigo('L');
    $idCombinada = \App\Models\TipoCotizacion::getIdPorCodigo('PL');
    $tabActivoPorDefecto = 'prendas';
    // Solo activar Logo por defecto cuando la cotización es SOLO logo.
    // En combinadas (PL) el tab inicial debe ser Prendas para evitar que ambos contenidos queden activos.
    if ($cotizacion->tipo_cotizacion_id === $idLogo) {
        $tabActivoPorDefecto = 'bordado';
    }

    // Normalizar secciones: vienen como JSON string o array
    $secciones = $logo ? ($logo->secciones ?? []) : [];
    if (is_string($secciones)) {
        $secciones = json_decode($secciones, true) ?? [];
    }
    $secciones = is_array($secciones) ? $secciones : [];
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

        @if (!empty($secciones))
            <div class="info-card">
                <div class="info-section no-border">
                    <h4 class="section-title"><i class="fas fa-tshirt"></i> Secciones de Prendas</h4>
                    @foreach ($secciones as $seccion)
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
            // Obtener técnicas de las prendas técnicas (nuevo sistema)
            $prendas_tecnicas = $logo->prendas ?? [];
            $tecnicas = [];
            foreach ($prendas_tecnicas as $prenda_tecnica) {
                if ($prenda_tecnica->tipo_logo) {
                    $nombreProducto = $prenda_tecnica->logoPrendaCot 
                        ? $prenda_tecnica->logoPrendaCot->nombre_producto 
                        : 'Producto desconocido';
                    $tecnicas[] = [
                        'tipo' => $prenda_tecnica->tipo_logo->nombre ?? 'Desconocido',
                        'prenda' => $prenda_tecnica->prendaCot?->nombre_producto ?? 'Prenda sin nombre',
                        'observaciones' => $prenda_tecnica->observaciones
                    ];
                }
            }
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

        {{-- TARJETAS DE TÉCNICAS - NUEVO DISEÑO --}}
        @php
            // Obtener las prendas que tienen imágenes (fotos) usando el ID de LogoCotizacion
            $prendasConTecnicas = $logo 
                ? \App\Models\LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logo->id)
                    ->with(['fotos', 'tipoLogo', 'prendaCot'])
                    ->orderBy('id')
                    ->orderBy('grupo_combinado')
                    ->get()
                : collect();

            // Agrupar por logo_prenda_cot_id (para crear una tarjeta por prenda)
            $prendasMap = [];
            foreach ($prendasConTecnicas as $prenda) {
                $nombrePrenda = $prenda->prendaCot?->nombre_producto ?? 'Prenda sin nombre';
                if (!isset($prendasMap[$nombrePrenda])) {
                    $prendasMap[$nombrePrenda] = [];
                }
                $prendasMap[$logoPrendaCotId][] = $prenda;
            }
        @endphp
        @if($prendasConTecnicas->count() > 0)
            <div style="margin-top: 2rem;">
                <h4 class="section-title" style="margin-bottom: 1.5rem;"><i class="fas fa-layer-group"></i> Detalles de Técnicas</h4>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                    @foreach($prendasMap as $logoPrendaCotId => $prendas)
                        @php
                            $esCombinada = count($prendas) > 1;
                            $prenda1 = $prendas[0];
                            $nombrePrenda = $prenda1->logoPrendaCot 
                                ? $prenda1->logoPrendaCot->nombre_producto 
                                : 'Producto desconocido';
                        @endphp
                        {{-- TARJETA DE PRENDA --}}
                        <div style="
                            background: white;
                            border-radius: 12px;
                            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
                            overflow: hidden;
                            border: 1px solid #e2e8f0;
                            transition: all 0.3s ease;
                        "
                        onmouseover="this.style.boxShadow='0 8px 24px rgba(0, 0, 0, 0.15)'; this.style.transform='translateY(-4px)';"
                        onmouseout="this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.08)'; this.style.transform='translateY(0)';">
                            
                            {{-- Header de la Tarjeta --}}
                            <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white; padding: 1rem; border-bottom: 4px solid #0ea5e9;">
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; text-transform: uppercase;">
                                    {{ $nombrePrenda }}
                                </h3>
                            </div>
                            
                            {{-- Cuerpo de la Tarjeta --}}
                            <div style="padding: 1.5rem;">
                                
                                {{-- TÉCNICAS --}}
                                <div style="margin-bottom: 1.5rem;">
                                    <h5 style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 0.8rem; letter-spacing: 0.5px;">
                                        <i class="fas fa-tools"></i> Técnicas:
                                    </h5>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
                                        @foreach($prendas as $prenda)
                                            @php
                                                $tipoLogo = $prenda->tipoLogo;
                                                // Determinar color según técnica
                                                $colores = [
                                                    'BORDADO' => ['bg' => '#0ea5e9', 'text' => 'white'],
                                                    'ESTAMPADO' => ['bg' => '#10b981', 'text' => 'white'],
                                                    'SUBLIMACIÓN' => ['bg' => '#f59e0b', 'text' => 'white'],
                                                    'SERIGRAFÍA' => ['bg' => '#8b5cf6', 'text' => 'white'],
                                                ];
                                                $color = $colores[$tipoLogo->nombre] ?? ['bg' => '#6b7280', 'text' => 'white'];
                                            @endphp
                                            <span style="
                                                background: {{ $color['bg'] }};
                                                color: {{ $color['text'] }};
                                                padding: 0.5rem 1rem;
                                                border-radius: 6px;
                                                font-weight: 600;
                                                font-size: 0.9rem;
                                            ">
                                                {{ $tipoLogo->nombre ?? 'Técnica' }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- IMÁGENES --}}
                                <div style="margin-bottom: 1.5rem;">
                                    <h5 style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 0.8rem; letter-spacing: 0.5px;">
                                        <i class="fas fa-images"></i> Imágenes:
                                    </h5>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; max-width: 220px;">
                                        @php
                                            $todasLasFotos = collect();
                                            foreach ($prendas as $p) {
                                                if ($p->fotos && $p->fotos->count() > 0) {
                                                    $todasLasFotos = $todasLasFotos->merge($p->fotos);
                                                }
                                            }
                                        @endphp
                                        @if($todasLasFotos->count() > 0)
                                            @foreach($todasLasFotos as $foto)
                                                <img src="{{ asset('storage/' . $foto->ruta_webp) }}" 
                                                     alt="Imagen técnica"
                                                     style="
                                                         width: 65px;
                                                         height: 65px;
                                                         object-fit: cover;
                                                         border-radius: 6px;
                                                         border: 1px solid #e2e8f0;
                                                         cursor: pointer;
                                                         transition: all 0.3s ease;
                                                     "
                                                     onmouseover="this.style.borderColor='#0ea5e9'; this.style.transform='scale(1.1)';"
                                                     onmouseout="this.style.borderColor='#e2e8f0'; this.style.transform='scale(1)';"
                                                     ondblclick="mostrarImagenFullscreen(this.src);">
                                            @endforeach
                                        @else
                                            <div style="
                                                grid-column: 1 / -1;
                                                background: #f1f5f9;
                                                border-radius: 6px;
                                                padding: 0.8rem;
                                                text-align: center;
                                                color: #94a3b8;
                                                font-size: 0.85rem;
                                            ">
                                                <i class="fas fa-image" style="font-size: 1.2rem; margin-bottom: 0.3rem; display: block;"></i>
                                                Sin imágenes
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- UBICACIONES --}}
                                <div style="margin-bottom: 1.5rem;">
                                    <h5 style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 0.8rem; letter-spacing: 0.5px;">
                                        <i class="fas fa-map-pin"></i> Ubicaciones:
                                    </h5>
                                    <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                                        @foreach($prendas as $prenda)
                                            @php
                                                $tipoLogo = $prenda->tipoLogo;
                                                $ubicaciones = is_string($prenda->ubicaciones) ? json_decode($prenda->ubicaciones, true) ?? [] : $prenda->ubicaciones;
                                            @endphp
                                            <div style="border-left: 3px solid #0ea5e9; padding-left: 0.8rem;">
                                                <div style="font-size: 0.8rem; color: #64748b; font-weight: 600; margin-bottom: 0.4rem;">
                                                    {{ $tipoLogo->nombre }}:
                                                </div>
                                                @if(!empty($ubicaciones))
                                                    <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                                                        @foreach($ubicaciones as $ubicacion)
                                                            <span style="
                                                                background: #dbeafe;
                                                                color: #0369a1;
                                                                padding: 0.3rem 0.7rem;
                                                                border-radius: 4px;
                                                                font-size: 0.8rem;
                                                                font-weight: 600;
                                                            ">
                                                                {{ $ubicacion }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span style="color: #94a3b8; font-size: 0.8rem;">—</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- OBSERVACIONES --}}
                                @if($prenda1->observaciones)
                                    <div style="margin-bottom: 1.5rem;">
                                        <h5 style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 0.8rem; letter-spacing: 0.5px;">
                                            <i class="fas fa-comment"></i> Observaciones:
                                        </h5>
                                        <div style="
                                            background: #fef3c7;
                                            border-left: 4px solid #f59e0b;
                                            padding: 0.8rem;
                                            border-radius: 4px;
                                            color: #78350f;
                                            font-size: 0.9rem;
                                            line-height: 1.5;
                                            display: inline-block;
                                            max-width: 100%;
                                        ">
                                            {{ $prenda1->observaciones }}
                                        </div>
                                    </div>
                                @endif

                                {{-- VARIACIONES --}}
                                @php
                                    $variacionesCombinadas = [];
                                    foreach ($prendas as $p) {
                                        $variaciones = is_string($p->variaciones_prenda) ? json_decode($p->variaciones_prenda, true) ?? [] : $p->variaciones_prenda;
                                        if (!empty($variaciones) && is_array($variaciones)) {
                                            foreach ($variaciones as $clave => $valor) {
                                                if (!isset($variacionesCombinadas[$clave])) {
                                                    $variacionesCombinadas[$clave] = [];
                                                }
                                                // Extraer el valor de 'opcion' si es un array
                                                $valorFinal = is_array($valor) ? ($valor['opcion'] ?? $valor) : $valor;
                                                if (!in_array($valorFinal, $variacionesCombinadas[$clave])) {
                                                    $variacionesCombinadas[$clave][] = $valorFinal;
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                @if(!empty($variacionesCombinadas))
                                    <div style="margin-bottom: 1.5rem;">
                                        <h5 style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 0.8rem; letter-spacing: 0.5px;">
                                            <i class="fas fa-palette"></i> Variaciones:
                                        </h5>
                                        <table style="
                                            width: 100%;
                                            border-collapse: collapse;
                                            background: white;
                                            border: 1px solid #e5e7eb;
                                            border-radius: 6px;
                                            overflow: hidden;
                                        ">
                                            <thead style="background: #f3e8ff; border-bottom: 2px solid #d8b4fe;">
                                                <tr>
                                                    <th style="padding: 0.75rem; text-align: left; font-weight: 700; color: #6b21a8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.4px;">Opción</th>
                                                    <th style="padding: 0.75rem; text-align: left; font-weight: 700; color: #6b21a8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.4px;">Observación</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($variacionesCombinadas as $opcion => $valores)
                                                    @foreach($valores as $valor)
                                                        <tr style="border-bottom: 1px solid #e5e7eb;" onmouseover="this.style.background='#faf5ff'" onmouseout="this.style.background=''">
                                                            <td style="padding: 0.75rem; font-size: 0.9rem; color: #374151; font-weight: 600;">
                                                                {{ ucfirst($opcion) }}:
                                                            </td>
                                                            <td style="padding: 0.75rem; font-size: 0.9rem; color: #6b7280;">
                                                                @if(!empty($valor) && $valor !== 'null')
                                                                    <span style="background: #fef3c7; color: #92400e; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.85rem;">
                                                                        {{ $valor }}
                                                                    </span>
                                                                @else
                                                                    <span style="color: #d1d5db;">—</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                {{-- TALLAS --}}
                                <div>
                                    <h5 style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 0.8rem; letter-spacing: 0.5px;">
                                        <i class="fas fa-ruler"></i> Tallas:
                                    </h5>
                                    @php
                                        $tallasCombinadas = [];
                                        foreach ($prendas as $p) {
                                            $tallas = is_string($p->talla_cantidad) ? json_decode($p->talla_cantidad, true) ?? [] : $p->talla_cantidad;
                                            foreach ($tallas as $talla) {
                                                $tallaKey = is_array($talla) ? $talla['talla'] : $talla;
                                                if (!isset($tallasCombinadas[$tallaKey])) {
                                                    $tallasCombinadas[$tallaKey] = is_array($talla) ? ($talla['cantidad'] ?? 0) : 0;
                                                }
                                            }
                                        }
                                    @endphp
                                    @if(!empty($tallasCombinadas))
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
                                            @foreach($tallasCombinadas as $tallaNombre => $cantidad)
                                                <span style="
                                                    background: #dbeafe;
                                                    color: #0369a1;
                                                    padding: 0.5rem 0.8rem;
                                                    border-radius: 6px;
                                                    font-size: 0.9rem;
                                                    font-weight: 600;
                                                ">
                                                    {{ $tallaNombre }}: {{ $cantidad }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color: #94a3b8; font-size: 0.9rem;">—</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
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

    @else
        <div style="text-align: center; padding: 3rem 2rem; color: #94a3b8; font-style: italic; font-size: 0.95rem;">
            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
            {{ $esLogo ? 'Sin información de logo' : 'Sin información de LOGO' }}
        </div>
    @endif
</div>

<!-- MODAL DE FOTOS FULLSCREEN -->
<div id="modalFotos" style="
    display: none !important;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.98);
    z-index: 999999;
    align-items: center;
    justify-content: center;
">
    <!-- Cerrar (X) - SIEMPRE VISIBLE -->
    <button onclick="cerrarModalFotos()" style="
        position: fixed;
        top: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        background: rgba(255, 59, 48, 0.9) !important;
        color: white;
        border: 3px solid white;
        border-radius: 50%;
        font-size: 2.5rem;
        cursor: pointer;
        z-index: 9999999 !important;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        box-shadow: 0 6px 20px rgba(255, 59, 48, 0.8);
        padding: 0;
    "
    onmouseover="this.style.background='rgba(255, 59, 48, 1)'; this.style.transform='scale(1.15) rotate(90deg)'; this.style.boxShadow='0 8px 30px rgba(255, 59, 48, 1)';"
    onmouseout="this.style.background='rgba(255, 59, 48, 0.9)'; this.style.transform='scale(1) rotate(0deg)'; this.style.boxShadow='0 6px 20px rgba(255, 59, 48, 0.8)';">
        ✕
    </button>

    <!-- Contenedor Principal -->
    <div style="
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        gap: 2rem;
        padding: 6rem 2rem 2rem 2rem;
    ">
        <!-- Botón Anterior -->
        <button onclick="anteriorFoto()" style="
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
            border-radius: 50%;
            font-size: 2.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        "
        onmouseover="this.style.background='rgba(14, 165, 233, 0.8)'; this.style.transform='scale(1.15)';"
        onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.transform='scale(1)';">
            ❮
        </button>

        <!-- Imagen -->
        <div style="
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            max-width: 90%;
            max-height: 85vh;
        ">
            <img id="imagenPrincipal" src="" alt="" style="
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
                border-radius: 8px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
            ">
            <div style="
                margin-top: 2rem;
                color: white;
                font-size: 1.3rem;
                font-weight: 600;
            ">
                <span id="contadorFoto">1 / 1</span>
            </div>
        </div>

        <!-- Botón Siguiente -->
        <button onclick="siguienteFoto()" style="
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
            border-radius: 50%;
            font-size: 2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            flex-shrink: 0;
        "
        onmouseover="this.style.background='rgba(14, 165, 233, 0.8)'; this.style.transform='scale(1.15)';"
        onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.transform='scale(1)';">
            ❯
        </button>
    </div>
</div>

<script>
    let fotosActuales = [];
    let indiceActual = 0;

    function abrirModalFotos(prendaId, indiceInicial = 0) {
        console.log('🔵 abrirModalFotos() - prendaId:', prendaId);
        
        // Obtener todas las fotos de esta prenda
        const prendasFotos = document.querySelectorAll(`img[onclick*="${prendaId}"]`);
        console.log('📸 Fotos encontradas:', prendasFotos.length);
        
        fotosActuales = Array.from(prendasFotos).map(img => img.src);
        console.log(' Fotos actuales:', fotosActuales);
        
        if (fotosActuales.length === 0) {
            console.error(' No hay fotos para mostrar');
            return;
        }
        
        indiceActual = indiceInicial;
        const modalFotos = document.getElementById('modalFotos');
        console.log(' Modal encontrado:', modalFotos);
        console.log(' Display anterior:', modalFotos.style.display);
        
        modalFotos.style.display = 'flex';
        console.log(' Display nuevo:', modalFotos.style.display);
        console.log(' Visible:', window.getComputedStyle(modalFotos).display);
        
        mostrarFoto();
        document.body.style.overflow = 'hidden';
        console.log(' Modal abierto');
    }

    function cerrarModalFotos() {
        console.log('🔴 cerrarModalFotos()');
        const modalFotos = document.getElementById('modalFotos');
        modalFotos.style.display = 'none';
        document.body.style.overflow = 'auto';
        console.log(' Modal cerrado');
    }

    function mostrarFoto() {
        console.log(' mostrarFoto() - indiceActual:', indiceActual, 'total:', fotosActuales.length);
        if (fotosActuales.length === 0) {
            console.warn(' No hay fotos para mostrar');
            return;
        }
        
        const imgElement = document.getElementById('imagenPrincipal');
        const contadorElement = document.getElementById('contadorFoto');
        
        console.log(' Imagen element:', imgElement);
        console.log(' Contador element:', contadorElement);
        
        if (imgElement && contadorElement) {
            imgElement.src = fotosActuales[indiceActual];
            contadorElement.textContent = (indiceActual + 1) + ' / ' + fotosActuales.length;
            console.log(' Foto mostrada:', fotosActuales[indiceActual]);
        } else {
            console.error(' Elementos no encontrados');
        }
    }

    function anteriorFoto() {
        indiceActual = (indiceActual - 1 + fotosActuales.length) % fotosActuales.length;
        mostrarFoto();
    }

    function siguienteFoto() {
        indiceActual = (indiceActual + 1) % fotosActuales.length;
        mostrarFoto();
    }

    // Soporte de teclado
    document.addEventListener('keydown', (e) => {
        const modalFotos = document.getElementById('modalFotos');
        if (modalFotos.style.display !== 'flex') return;
        
        if (e.key === 'Escape') cerrarModalFotos();
        if (e.key === 'ArrowLeft') anteriorFoto();
        if (e.key === 'ArrowRight') siguienteFoto();
    });

    // Cerrar con click fuera
    document.getElementById('modalFotos')?.addEventListener('click', (e) => {
        if (e.target.id === 'modalFotos') cerrarModalFotos();
    });

    // FUNCIÓN PARA MOSTRAR IMAGEN EN FULLSCREEN CON DOBLE CLICK
    function mostrarImagenFullscreen(srcImagen) {
        const modal = document.createElement('div');
        modal.id = 'modalImagenFullscreen';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            animation: fadeIn 0.3s ease;
        `;

        const img = document.createElement('img');
        img.src = srcImagen;
        img.style.cssText = `
            max-width: 90vw;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            animation: zoomIn 0.3s ease;
        `;

        const btnCerrar = document.createElement('button');
        btnCerrar.type = 'button';
        btnCerrar.style.cssText = `
            position: absolute;
            top: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: rgba(255, 59, 48, 0.9);
            color: white;
            border: 2px solid white;
            border-radius: 50%;
            font-size: 2rem;
            cursor: pointer;
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        `;
        btnCerrar.textContent = '✕';
        btnCerrar.onmouseover = function() {
            this.style.background = 'rgba(255, 59, 48, 1)';
            this.style.transform = 'scale(1.15)';
            this.style.boxShadow = '0 6px 20px rgba(255, 59, 48, 0.8)';
        };
        btnCerrar.onmouseout = function() {
            this.style.background = 'rgba(255, 59, 48, 0.9)';
            this.style.transform = 'scale(1)';
            this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.4)';
        };
        btnCerrar.onclick = () => cerrarImagenFullscreen();

        modal.appendChild(img);
        modal.appendChild(btnCerrar);
        document.body.appendChild(modal);

        // Cerrar con ESC
        const cerrarConTecla = (e) => {
            if (e.key === 'Escape') {
                cerrarImagenFullscreen();
                document.removeEventListener('keydown', cerrarConTecla);
            }
        };
        document.addEventListener('keydown', cerrarConTecla);

        // Cerrar con click fuera de la imagen
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                cerrarImagenFullscreen();
            }
        });
    }

    function cerrarImagenFullscreen() {
        const modal = document.getElementById('modalImagenFullscreen');
        if (modal) {
            modal.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => modal.remove(), 300);
        }
    }

    // ESTILOS DE ANIMACIÓN
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        @keyframes zoomIn {
            from { 
                opacity: 0;
                transform: scale(0.8);
            }
            to { 
                opacity: 1;
                transform: scale(1);
            }
        }
    `;
    document.head.appendChild(style);
</script>
