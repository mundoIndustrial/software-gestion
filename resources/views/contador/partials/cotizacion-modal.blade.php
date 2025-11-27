<div class="cotizacion-detail">
    <!-- Cotizar Según Indicaciones -->
    @if($cotizacion->cotizar_segun_indicaciones)
    <div class="detail-section">
        <div class="detail-row">
            <div class="detail-label">Cotizar Según Indicaciones:</div>
            <div class="detail-value" style="font-size: 1.1rem; font-weight: 700; color: #1e5ba8;">
                {{ $cotizacion->cotizar_segun_indicaciones }}
            </div>
        </div>
    </div>
    @endif

    <!-- Productos de la Cotización (desde relación prendasCotizaciones) -->
    @php
        // Obtener prendas desde la relación (tabla prendas_cotizaciones_friendly)
        $prendas = $cotizacion->prendasCotizaciones ?? [];
        $tieneProductos = count($prendas) > 0;
    @endphp
    
    @if($tieneProductos)
    <div class="detail-section">
        @foreach($prendas as $productoIndex => $prenda)
            <!-- Nombre del Producto -->
            <h3 style="color: #1e40af; font-size: 1rem; font-weight: 700; margin: 1.5rem 0 0.5rem 0;">
                {{ strtoupper($prenda->nombre_producto ?? 'N/A') }}:
            </h3>
            
            <!-- Descripción + Especificaciones (unidas como en modal de orden) -->
            @php
                // Obtener información de variantes desde la cotización si está disponible
                $cotizacionProductos = [];
                if ($cotizacion->productos) {
                    $cotizacionProductos = is_string($cotizacion->productos) 
                        ? json_decode($cotizacion->productos, true) 
                        : $cotizacion->productos;
                }
                
                $descripcionCompleta = $prenda->descripcion ?? '';
                $especificaciones = '';
                
                // Si hay información de variantes en la cotización, obtener especificaciones
                if (!empty($cotizacionProductos) && isset($cotizacionProductos[$productoIndex])) {
                    $producto = $cotizacionProductos[$productoIndex];
                    $variantes = $producto['variantes'] ?? [];
                    
                    if (!empty($variantes['descripcion_adicional'])) {
                        $especificaciones = $variantes['descripcion_adicional'];
                    }
                }
                
                // Construir línea final: Descripción | Especificaciones
                $lineaCompleta = $descripcionCompleta;
                if ($especificaciones) {
                    // Hacer negrilla los títulos "Bolsillos:" y "Reflectivo:"
                    $especificacionesFormato = $especificaciones;
                    $especificacionesFormato = str_replace('Bolsillos:', '<strong>Bolsillos:</strong>', $especificacionesFormato);
                    $especificacionesFormato = str_replace('Reflectivo:', '<strong>Reflectivo:</strong>', $especificacionesFormato);
                    $lineaCompleta .= ' | ' . $especificacionesFormato;
                }
            @endphp
            
            <p style="color: #333; font-size: 0.9rem; line-height: 1.6; margin: 0 0 1rem 0; word-wrap: break-word;">
                {!! $lineaCompleta ?: '-' !!}
            </p>
            
            <!-- DETALLES COMPLETOS DE LA PRENDA (Color, Tela, Manga) -->
            @php
                $detallesPrenda = [];
                
                // Si hay información de variantes en la cotización
                if (!empty($cotizacionProductos) && isset($cotizacionProductos[$productoIndex])) {
                    $producto = $cotizacionProductos[$productoIndex];
                    $variantes = $producto['variantes'] ?? [];
                    
                    if (!empty($variantes['color'])) {
                        $detallesPrenda['Color'] = $variantes['color'];
                    }
                    
                    if (!empty($variantes['tela'])) {
                        $tela = $variantes['tela'];
                        if (!empty($variantes['tela_referencia'])) {
                            $tela .= " (Ref: {$variantes['tela_referencia']})";
                        }
                        $detallesPrenda['Tela'] = $tela;
                    }
                    
                    if (!empty($variantes['manga_nombre'])) {
                        $detallesPrenda['Manga'] = $variantes['manga_nombre'];
                    }
                }
            @endphp
            
            @if(count($detallesPrenda) > 0)
            <div style="background-color: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; border-left: 4px solid #2b7ec9;">
                <div style="font-weight: 700; color: #1e5ba8; margin-bottom: 0.75rem; font-size: 0.9rem;">📋 Detalles de la Prenda:</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    @foreach($detallesPrenda as $label => $valor)
                    <div>
                        <div style="font-weight: 600; color: #333; font-size: 0.85rem; margin-bottom: 0.25rem;">{{ $label }}:</div>
                        <div style="color: #666; font-size: 0.9rem; word-wrap: break-word;">{{ $valor }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Sección de Imágenes de Prenda y Tela -->
            @php
                // Obtener imágenes desde la relación (tabla prendas_cotizaciones_friendly)
                $imagenesPrenda = $prenda->fotos ?? [];
                if (!is_array($imagenesPrenda)) {
                    $imagenesPrenda = [];
                }
                
                // Obtener imágenes de tela desde el JSON array
                $imagenesTela = $prenda->telas ?? [];
                if (!is_array($imagenesTela)) {
                    $imagenesTela = [];
                }
                
                $totalImagenes = count($imagenesPrenda);
                $imagenesVisibles = array_slice($imagenesPrenda, 0, 2);
                $tieneVerMas = $totalImagenes > 2;
            @endphp
            
            @if($totalImagenes > 0 || count($imagenesTela) > 0)
            <div style="margin-bottom: 1.5rem;">
                <div style="font-size: 0.85rem; font-weight: 600; color: #333; margin-bottom: 0.75rem;">Imágenes:</div>
                
                <!-- Grid de imágenes -->
                <div style="display: flex; gap: 1rem; align-items: flex-start; flex-wrap: wrap;" data-producto-index="{{ $productoIndex }}" data-todas-imagenes="{{ json_encode($imagenesPrenda) }}">
                    <!-- Imágenes de prenda (máx 2) -->
                    @foreach($imagenesVisibles as $imagen)
                    <div style="position: relative; cursor: pointer;" ondblclick="abrirImagenFullscreen('{{ $imagen }}')">
                        <img src="{{ $imagen }}" alt="Prenda" 
                             style="width: 150px; height: 150px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; transition: all 0.2s;"
                             onmouseover="this.style.opacity='0.8'; this.style.transform='scale(1.02)'"
                             onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'"
                             onclick="abrirModalImagenes({{ $productoIndex }}, '{{ $prenda->nombre_producto ?? 'Producto' }}')">
                    </div>
                    @endforeach
                    
                    <!-- Imágenes de tela si existen -->
                    @foreach($imagenesTela as $imagenTela)
                    <div style="position: relative; cursor: pointer;" ondblclick="abrirImagenFullscreen('{{ $imagenTela }}')">
                        <img src="{{ $imagenTela }}" alt="Tela" 
                             style="width: 150px; height: 150px; object-fit: cover; border: 2px solid #8B4513; border-radius: 4px; transition: all 0.2s;"
                             title="Tela"
                             onmouseover="this.style.opacity='0.8'; this.style.transform='scale(1.02)'"
                             onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'">
                    </div>
                    @endforeach
                    
                    <!-- Botón VER MAS si hay más de 2 imágenes de prenda -->
                    @if($tieneVerMas)
                    <div style="width: 150px; height: 150px; background: #999; border: 1px solid #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;"
                         onmouseover="this.style.backgroundColor='#777'; this.style.transform='scale(1.02)'"
                         onmouseout="this.style.backgroundColor='#999'; this.style.transform='scale(1)'"
                         onclick="abrirModalImagenes({{ $productoIndex }}, '{{ $prenda->nombre_producto ?? 'Producto' }}')">
                        <div style="text-align: center; color: white; font-weight: 700; font-size: 1rem;">
                            VER MAS...
                        </div>
                    </div>
                    @endif
                    
                    <!-- Tallas -->
                    <div style="display: flex; flex-direction: column; gap: 0.75rem; justify-content: center;" data-prenda-id="{{ $prenda->id }}">
                        @php
                            // Obtener tallas desde la relación (tabla prendas_cotizaciones_friendly)
                            $tallas = $prenda->tallas ?? [];
                            if (is_string($tallas)) {
                                $tallas = explode(',', $tallas);
                                $tallas = array_map('trim', $tallas);
                            }
                        @endphp
                        
                        <!-- Mostrar Tallas con edición -->
                        @if(count($tallas) > 0)
                            @php
                                // Mostrar notas guardadas si existen, sino mostrar tallas base
                                $tallasDisplay = $prenda->notas_tallas ?? ('TALLAS: (' . implode('-', array_map('strtoupper', $tallas)) . ')');
                            @endphp
                            <div style="font-weight: bold; color: #e74c3c; font-size: 0.95rem; cursor: pointer; padding: 0.5rem; border-radius: 4px; transition: all 0.2s;"
                                 ondblclick="editarTallas(this, {{ $prenda->id }}, '{{ implode('-', array_map('strtoupper', $tallas)) }}')"
                                 onmouseover="this.style.backgroundColor='rgba(231, 76, 60, 0.1)'"
                                 onmouseout="this.style.backgroundColor='transparent'"
                                 data-prenda-id="{{ $prenda->id }}"
                                 data-tallas-base="{{ implode('-', array_map('strtoupper', $tallas)) }}"
                                 id="tallas-display-{{ $prenda->id }}">
                                {{ $tallasDisplay }}
                            </div>
                        @else
                            <div style="color: #999; font-size: 0.85rem;">Sin tallas</div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        @endforeach
    </div>
    @endif

    <!-- ESPECIFICACIONES DE LA ORDEN -->
    <div class="detail-section">
        <div class="detail-header">📋 Especificaciones de la Orden</div>
        
        @php
            // Mapeo de claves de especificaciones a nombres legibles
            $especificacionesMap = [
                'disponibilidad' => 'DISPONIBILIDAD',
                'forma_pago' => 'FORMA DE PAGO',
                'regimen' => 'RÉGIMEN',
                'se_ha_vendido' => 'SE HA VENDIDO',
                'ultima_venta' => 'ÚLTIMA VENTA',
                'flete' => 'FLETE DE ENVÍO'
            ];
            
            // Obtener especificaciones desde la tabla cotizaciones
            $especificacionesData = $cotizacion->especificaciones ?? [];
            
            // Convertir a array si es necesario
            if (!is_array($especificacionesData)) {
                $especificacionesData = (array) $especificacionesData;
            }
            
            // Construir estructura para la tabla
            $especificacionesPorCategoria = [];
            foreach ($especificacionesMap as $clave => $nombreCategoria) {
                $valores = $especificacionesData[$clave] ?? [];
                
                // Asegurar que sea un array
                if (!is_array($valores)) {
                    $valores = (array) $valores;
                }
                
                $especificacionesPorCategoria[$nombreCategoria] = [
                    'valores' => $valores,
                    'cantidad' => count($valores)
                ];
            }
        @endphp
        
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white;">
                    <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd; font-weight: 700; width: 30%;">Especificación</th>
                    <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd; font-weight: 700; width: 70%;">Opciones Seleccionadas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($especificacionesPorCategoria as $categoria => $datos)
                    @php
                        $valores = $datos['valores'] ?? [];
                        $valoresText = count($valores) > 0 ? implode(', ', $valores) : '-';
                        $tieneValores = count($valores) > 0;
                    @endphp
                    <tr style="background-color: #ffffff; border-bottom: 1px solid #ddd;">
                        <td style="padding: 0.75rem; border: 1px solid #ddd; color: #333; font-weight: 600;">
                            {{ $categoria }}
                        </td>
                        <td style="padding: 0.75rem; border: 1px solid #ddd; color: {{ $tieneValores ? '#333' : '#999' }};">
                            {{ $valoresText }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Modal de Imágenes Completo -->
    <div id="modalImagenesProducto" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 5000; overflow-y: auto;">
        <div style="padding: 2rem; max-width: 1200px; margin: 0 auto;">
            <!-- Header del Modal -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; color: white;">
                <h2 id="modalImagenesTitle" style="margin: 0; font-size: 1.5rem;"></h2>
                <button onclick="cerrarModalImagenes()" style="background: none; border: none; color: white; font-size: 2rem; cursor: pointer; padding: 0;">✕</button>
            </div>
            
            <!-- Grid de Imágenes -->
            <div id="modalImagenesGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem;"></div>
        </div>
    </div>
    
    <!-- Modal Fullscreen para Imagen Individual -->
    <div id="modalImagenFullscreen" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.95); z-index: 6000; align-items: center; justify-content: center;">
        <button onclick="cerrarImagenFullscreen()" style="position: absolute; top: 2rem; right: 2rem; background: none; border: none; color: white; font-size: 2rem; cursor: pointer; z-index: 6001;">✕</button>
        <img id="imagenFullscreen" src="" alt="Imagen" style="max-width: 90vw; max-height: 90vh; object-fit: contain;">
    </div>

    <!-- Técnicas (desde logo_cotizaciones) -->
    @php
        $logoCotizacion = $cotizacion->logoCotizacion;
        $tecnicas = $logoCotizacion->tecnicas ?? [];
    @endphp
    @if(!empty($tecnicas) && count($tecnicas) > 0)
    <div class="detail-section">
        <div class="detail-header">🎨 Técnicas</div>
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
            @foreach($tecnicas as $tecnica)
            <span style="background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 700;">
                {{ $tecnica }}
            </span>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Observaciones Técnicas (desde logo_cotizaciones) -->
    @if($logoCotizacion && $logoCotizacion->observaciones_tecnicas)
    <div class="detail-section">
        <div class="detail-header">📝 Observaciones Técnicas</div>
        <div style="padding: 1rem; background-color: #f8f9fa; border-radius: 4px; border-left: 4px solid #1e5ba8;">
            <p style="color: #333; line-height: 1.6; margin: 0;">
                {{ $logoCotizacion->observaciones_tecnicas }}
            </p>
        </div>
    </div>
    @endif

    <!-- Observaciones Generales (desde logo_cotizaciones) -->
    @php
        $observacionesGenerales = $logoCotizacion->observaciones_generales ?? [];
    @endphp
    @if(!empty($observacionesGenerales) && count($observacionesGenerales) > 0)
    <div class="detail-section">
        <div class="detail-header">💬 Observaciones Generales</div>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            @foreach($observacionesGenerales as $obs)
            <div style="padding: 0.75rem; background-color: #f8f9fa; border-radius: 4px; border-left: 4px solid #1e5ba8; color: #333;">
                • {{ $obs }}
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Prendas de la Cotización (si existen en relación) -->
    @if($cotizacion->prendas && count($cotizacion->prendas) > 0)
    <div class="detail-section">
        <div class="detail-header">👕 Prendas Detalladas</div>
        @foreach($cotizacion->prendas as $index => $prenda)
        <div style="margin-bottom: 1.5rem; padding: 1rem; background-color: #f8f9fa; border-radius: 8px; border: 1px solid #e0e0e0;">
            <h4 style="color: #1e5ba8; margin: 0 0 1rem 0;">Prenda {{ $index + 1 }}</h4>
            
            <!-- Imagen de la Prenda -->
            @if($prenda->imagen_url)
            <div style="margin-bottom: 1rem; text-align: center;">
                <img src="{{ $prenda->imagen_url }}" alt="Prenda" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            </div>
            @endif

            <!-- Descripción -->
            @if($prenda->descripcion)
            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 600; color: #1e5ba8; display: block; margin-bottom: 0.5rem;">Descripción:</label>
                <p style="color: #333; line-height: 1.6; margin: 0;">{{ $prenda->descripcion }}</p>
            </div>
            @endif

            <!-- Aspectos a Verificar -->
            @if($prenda->aspectos_a_verificar && count($prenda->aspectos_a_verificar) > 0)
            <div>
                <label style="font-weight: 600; color: #1e5ba8; display: block; margin-bottom: 0.5rem;">Aspectos a Verificar:</label>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="background-color: #1e5ba8;">
                            <th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd; font-weight: 700; color: white;">Aspecto</th>
                            <th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd; font-weight: 700; color: white;">Descripción</th>
                            <th style="padding: 0.5rem; text-align: left; border: 1px solid #ddd; font-weight: 700; color: white;">Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prenda->aspectos_a_verificar as $aspecto)
                        <tr style="background-color: #ffffff;">
                            <td style="padding: 0.5rem; border: 1px solid #ddd; color: #333;">{{ $aspecto['aspecto'] ?? '-' }}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; color: #333;">{{ $aspecto['descripcion'] ?? '-' }}</td>
                            <td style="padding: 0.5rem; border: 1px solid #ddd; color: #333;">{{ $aspecto['observacion'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

</div>