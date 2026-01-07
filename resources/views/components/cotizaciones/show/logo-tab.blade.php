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

        {{-- TABLA DE TÉCNICAS - ESTILO FACTURA --}}
        @php
            // Obtener las prendas que tienen imágenes (fotos) usando el ID de LogoCotizacion
            $prendasConTecnicas = $logo 
                ? \App\Models\LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logo->id)
                    ->with(['fotos', 'tipoLogo'])
                    ->orderBy('tipo_logo_id')
                    ->orderBy('grupo_combinado')
                    ->get()
                : collect();
        @endphp
        @if($prendasConTecnicas->count() > 0)
            <div class="info-card">
                <div class="info-section no-border">
                    <h4 class="section-title"><i class="fas fa-layer-group"></i> Detalles de Técnicas</h4>
                    
                    <table style="
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 1rem;
                        font-size: 0.9rem;
                        background: white;
                    ">
                        <thead>
                            <tr style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white;">
                                <th style="padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid #1e3a8a; font-size: 1.1rem;">Técnica</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid #1e3a8a; font-size: 1.1rem;">Prenda</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid #1e3a8a; font-size: 1.1rem;">Ubicaciones</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid #1e3a8a; font-size: 1.1rem;">Observaciones</th>
                                <th style="padding: 1rem; text-align: center; font-weight: 600; border-bottom: 2px solid #1e3a8a; font-size: 1.1rem;">Tallas</th>
                                <th style="padding: 1rem; text-align: center; font-weight: 600; border-bottom: 2px solid #1e3a8a; font-size: 1.1rem;">Imágenes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prendasConTecnicas as $prenda)
                                @php
                                    $tipoLogo = $prenda->tipoLogo;
                                    $ubicaciones = is_string($prenda->ubicaciones) ? json_decode($prenda->ubicaciones, true) ?? [] : $prenda->ubicaciones;
                                    $tallas = is_string($prenda->talla_cantidad) ? json_decode($prenda->talla_cantidad, true) ?? [] : $prenda->talla_cantidad;
                                @endphp
                                <tr style="border-bottom: 1px solid #e2e8f0; transition: background 0.2s;">
                                    {{-- Técnica --}}
                                    <td style="padding: 1rem; vertical-align: top;">
                                        <div style="display: inline-block; padding: 0.5rem 1rem; background: #0ea5e9; color: white; border-radius: 6px; font-weight: 600; font-size: 1rem;">
                                            {{ $tipoLogo->nombre ?? 'Técnica' }}
                                            @if($prenda->grupo_combinado)
                                                <br><small style="opacity: 0.9;">Grupo {{ $prenda->grupo_combinado }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    
                                    {{-- Prenda --}}
                                    <td style="padding: 1rem; vertical-align: top; font-weight: 500; color: #1e293b; font-size: 1rem;">
                                        {{ $prenda->nombre_prenda }}
                                    </td>
                                    
                                    {{-- Ubicaciones --}}
                                    <td style="padding: 1rem; vertical-align: top; font-size: 0.95rem;">
                                        @if(!empty($ubicaciones))
                                            <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                                                @foreach($ubicaciones as $ubicacion)
                                                    <span class="tag tag-blue" style="font-size: 0.95rem;">{{ $ubicacion }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span style="color: #94a3b8; font-size: 0.85rem;">—</span>
                                        @endif
                                    </td>
                                    
                                    {{-- Observaciones --}}
                                    <td style="padding: 1rem; vertical-align: top;">
                                        @if($prenda->observaciones)
                                            <div style="font-size: 0.95rem; color: #64748b; padding: 0.4rem; background: #f1f5f9; border-left: 2px solid #f59e0b; border-radius: 3px;">
                                                {{ $prenda->observaciones }}
                                            </div>
                                        @else
                                            <span style="color: #94a3b8; font-size: 0.95rem;">—</span>
                                        @endif
                                    </td>
                                    
                                    {{-- Tallas --}}
                                    <td style="padding: 1rem; vertical-align: top; text-align: center;">
                                        @if(!empty($tallas))
                                            @foreach($tallas as $talla)
                                                <div style="background: #dbeafe; color: #0369a1; padding: 0.4rem 0.8rem; border-radius: 4px; margin-bottom: 0.3rem; font-size: 0.95rem; font-weight: 600;">
                                                    {{ is_array($talla) ? ($talla['talla'] ?? 'N/A') : $talla }}: 
                                                    <strong>{{ is_array($talla) ? ($talla['cantidad'] ?? 0) : 0 }}</strong>
                                                </div>
                                            @endforeach
                                        @else
                                            <span style="color: #94a3b8; font-size: 0.95rem;">—</span>
                                        @endif
                                    </td>
                                    
                                    {{-- Imágenes --}}
                                    <td style="padding: 1rem; vertical-align: top; text-align: center;">
                                        @if($prenda->fotos && $prenda->fotos->count() > 0)
                                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center;">
                                                @foreach($prenda->fotos as $index => $foto)
                                                    <img src="{{ asset('storage/' . $foto->ruta_webp) }}" 
                                                         alt="{{ $prenda->nombre_prenda }}"
                                                         style="
                                                             width: 80px;
                                                             height: 80px;
                                                             object-fit: cover;
                                                             border-radius: 6px;
                                                             border: 2px solid #e2e8f0;
                                                             transition: all 0.3s;
                                                             cursor: pointer;
                                                         "
                                                         onclick="abrirModalFotos('prenda-{{ $prenda->id }}', {{ $index }})"
                                                         onmouseover="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 4px 12px rgba(14,165,233,0.3)';"
                                                         onmouseout="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none';">
                                                @endforeach
                                            </div>
                                            <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #64748b; font-weight: 500;">
                                                {{ $prenda->fotos->count() }} imagen(es)
                                            </div>
                                        @else
                                            <span style="color: #94a3b8; font-size: 0.85rem;">Sin imágenes</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.95);
    z-index: 9999999;
    align-items: center;
    justify-content: center;
    padding: 2rem;
">
    <!-- Cerrar -->
    <button onclick="cerrarModalFotos()" style="
        position: absolute;
        top: 2rem;
        right: 2rem;
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid white;
        border-radius: 50%;
        font-size: 2rem;
        cursor: pointer;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    "
    onmouseover="this.style.background='rgba(255, 59, 48, 0.8)'; this.style.transform='scale(1.1)';"
    onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.transform='scale(1)';">
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
    ">
        <!-- Botón Anterior -->
        <button onclick="anteriorFoto()" style="
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
            max-height: 80vh;
        ">
            <img id="imagenPrincipal" src="" alt="" style="
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
                border-radius: 8px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            ">
            <div style="
                margin-top: 1.5rem;
                color: white;
                font-size: 1.1rem;
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
        // Obtener todas las fotos de esta prenda
        const prendasFotos = document.querySelectorAll(`img[onclick*="${prendaId}"]`);
        fotosActuales = Array.from(prendasFotos).map(img => img.src);
        
        if (fotosActuales.length === 0) return;
        
        indiceActual = indiceInicial;
        document.getElementById('modalFotos').style.display = 'flex';
        mostrarFoto();
        document.body.style.overflow = 'hidden';
    }

    function cerrarModalFotos() {
        document.getElementById('modalFotos').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function mostrarFoto() {
        if (fotosActuales.length === 0) return;
        
        document.getElementById('imagenPrincipal').src = fotosActuales[indiceActual];
        document.getElementById('contadorFoto').textContent = (indiceActual + 1) + ' / ' + fotosActuales.length;
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
</script>
