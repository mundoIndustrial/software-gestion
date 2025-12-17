{{-- Reflectivo Tab Content - Versión Direct con Tabs por Prenda --}}
<div id="tab-content-reflectivo-direct" style="padding: 0; background: transparent; border-radius: 0;">
    @if($cotizacion->reflectivoCotizacion)
        @php
            $reflectivo = $cotizacion->reflectivoCotizacion;
            $especificaciones = is_array($cotizacion->especificaciones) ? $cotizacion->especificaciones : json_decode($cotizacion->especificaciones, true) ?? [];
            $ubicacionesReflectivo = is_string($reflectivo->ubicacion) ? json_decode($reflectivo->ubicacion, true) ?? [] : ($reflectivo->ubicacion ?? []);
            $categoriasMap = [
                'bodega' => 'DISPONIBILIDAD - Bodega',
                'cucuta' => 'DISPONIBILIDAD - Cúcuta',
                'lafayette' => 'DISPONIBILIDAD - Lafayette',
                'fabrica' => 'DISPONIBILIDAD - Fábrica',
                'contado' => 'FORMA DE PAGO - Contado',
                'credito' => 'FORMA DE PAGO - Crédito',
                'comun' => 'RÉGIMEN - Común',
                'simplificado' => 'RÉGIMEN - Simplificado',
                'vendido' => 'SE HA VENDIDO',
                'ultima_venta' => 'ÚLTIMA VENTA',
                'flete' => 'FLETE DE ENVÍO'
            ];
        @endphp
        
        {{-- Tabs de Prendas --}}
        @if($cotizacion->prendas && count($cotizacion->prendas) > 0)
            <div style="margin-bottom: 2rem;">
                <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid #e2e8f0; overflow-x: auto; padding-bottom: 0;">
                    @foreach($cotizacion->prendas as $index => $prenda)
                        <button onclick="mostrarPrendaReflectivo({{ $index }})" 
                                id="tab-prenda-{{ $index }}"
                                style="padding: 1rem 1.5rem; background: {{ $index === 0 ? '#1e40af' : '#f1f5f9' }}; color: {{ $index === 0 ? 'white' : '#64748b' }}; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 600; transition: all 0.2s; white-space: nowrap;">
                            <i class="fas fa-tshirt" style="margin-right: 0.5rem;"></i>{{ $prenda->nombre_producto ?? 'Prenda ' . ($index + 1) }}
                        </button>
                    @endforeach
                </div>
                
                {{-- Contenido de cada prenda --}}
                @foreach($cotizacion->prendas as $index => $prenda)
                    <div id="content-prenda-{{ $index }}" 
                         style="display: {{ $index === 0 ? 'block' : 'none' }}; background: white; border-radius: 0 8px 8px 8px; padding: 2rem; border: 1px solid #e2e8f0; border-top: none;">
                        
                        {{-- Información de la Prenda --}}
                        <div style="margin-bottom: 2rem; background: #f0f7ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1.5rem;">
                            <h4 style="color: #1e40af; font-weight: 600; margin: 0 0 0.75rem 0;">{{ $prenda->nombre_producto ?? 'Prenda' }}</h4>
                            @if($prenda->descripcion)
                                <p style="color: #475569; font-size: 0.9rem; line-height: 1.5; margin: 0;">{{ $prenda->descripcion }}</p>
                            @else
                                <p style="color: #94a3b8; font-size: 0.9rem; margin: 0; font-style: italic;">Sin descripción</p>
                            @endif
                        </div>
                        
                        {{-- Descripción del Reflectivo --}}
                        <div style="margin-bottom: 2rem;">
                            <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Descripción General</h4>
                            <div style="background: #f0f7ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1.5rem;">
                                <p style="color: #475569; line-height: 1.6; margin: 0;">{{ $reflectivo->descripcion ?? 'Sin descripción' }}</p>
                            </div>
                        </div>
                        
                        {{-- Especificaciones --}}
                        @if(!empty($especificaciones))
                            <div style="margin-bottom: 2rem;">
                                <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Especificaciones</h4>
                                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; overflow-x: auto;">
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <thead>
                                            <tr style="background: #f0f0f0; border-bottom: 2px solid #ddd;">
                                                <th style="padding: 10px; text-align: left; font-weight: 600; color: #333;">Concepto</th>
                                                <th style="padding: 10px; text-align: center; font-weight: 600; color: #333;">Aplica</th>
                                                <th style="padding: 10px; text-align: left; font-weight: 600; color: #333;">Observaciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($especificaciones as $clave => $valor)
                                                @php
                                                    if (preg_match('/tabla_orden\[([^\]]+)\]/', $clave, $matches)) {
                                                        $nombreCampo = $matches[1];
                                                        if (strpos($nombreCampo, '_obs') !== false) continue;
                                                        $nombreCategoria = $categoriasMap[$nombreCampo] ?? ucfirst(str_replace('_', ' ', $nombreCampo));
                                                        $esSeleccionado = ($valor === '1' || $valor === true) ? '✓' : '✗';
                                                        $claveObs = 'tabla_orden[' . $nombreCampo . '_obs]';
                                                        $observacion = $especificaciones[$claveObs] ?? '';
                                                    } else {
                                                        continue;
                                                    }
                                                @endphp
                                                <tr style="border-bottom: 1px solid #eee;">
                                                    <td style="padding: 10px; color: #333;">{{ $nombreCategoria }}</td>
                                                    <td style="padding: 10px; text-align: center; font-weight: 600; color: {{ $esSeleccionado === '✓' ? '#10b981' : '#999' }};">{{ $esSeleccionado }}</td>
                                                    <td style="padding: 10px; color: #666;">{{ $observacion }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                        
                        {{-- Ubicaciones del Reflectivo --}}
                        @if(!empty($ubicacionesReflectivo))
                            <div style="margin-bottom: 2rem;">
                                <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Ubicaciones del Reflectivo</h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
                                    @foreach($ubicacionesReflectivo as $ubicacion)
                                        <div style="border: 1px solid #e2e8f0; border-left: 4px solid #0ea5e9; border-radius: 8px; padding: 1rem; background: white;">
                                            <h5 style="color: #1e40af; font-weight: 600; margin-bottom: 0.5rem; margin: 0 0 0.5rem 0;">
                                                {{ $ubicacion['ubicacion'] ?? $ubicacion }}
                                            </h5>
                                            @if(is_array($ubicacion) && isset($ubicacion['descripcion']))
                                                <p style="color: #64748b; font-size: 0.9rem; margin: 0;">{{ $ubicacion['descripcion'] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        {{-- Imágenes del Reflectivo --}}
                        @if($reflectivo && $reflectivo->fotos && count($reflectivo->fotos) > 0)
                            <div style="margin-bottom: 2rem;">
                                <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Imágenes Reflectivo ({{ $reflectivo->fotos->count() }})</h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1.5rem;">
                                    @php
                                        $fotosArray = $reflectivo->fotos->map(fn($f) => $f->url)->toArray();
                                        $fotosJson = json_encode($fotosArray);
                                    @endphp
                                    @foreach($reflectivo->fotos as $fotoIndex => $foto)
                                        @if($foto->ruta_original)
                                            <div style="border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 6px 16px rgba(0, 0, 0, 0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)'">
                                                <img src="{{ $foto->url }}" alt="Reflectivo" 
                                                     style="width: 100%; height: 150px; object-fit: cover; cursor: pointer; transition: transform 0.3s ease;"
                                                     onmouseover="this.style.transform='scale(1.05)'"
                                                     onmouseout="this.style.transform=''"
                                                     onclick="abrirModalImagen('{{ $foto->url }}', 'Reflectivo - Imagen {{ $fotoIndex + 1 }}', {{ $fotosJson }}, {{ $fotoIndex }})">
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

    @else
        <div style="text-align: center; padding: 3rem; color: #94a3b8;">
            <p style="font-size: 1.1rem;">No hay información de reflectivo disponible</p>
        </div>
    @endif
</div>

<script>
function mostrarPrendaReflectivo(index) {
    // Ocultar todos los tabs
    document.querySelectorAll('[id^="content-prenda-"]').forEach(el => {
        el.style.display = 'none';
    });
    
    // Desmarcar todos los botones
    document.querySelectorAll('[id^="tab-prenda-"]').forEach(btn => {
        btn.style.background = '#f1f5f9';
        btn.style.color = '#64748b';
    });
    
    // Mostrar tab seleccionado
    document.getElementById('content-prenda-' + index).style.display = 'block';
    document.getElementById('tab-prenda-' + index).style.background = '#1e40af';
    document.getElementById('tab-prenda-' + index).style.color = 'white';
}
</script>
