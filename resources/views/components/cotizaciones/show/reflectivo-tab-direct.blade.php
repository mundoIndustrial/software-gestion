{{-- Reflectivo Tab Content - Versión Direct (sin clase tab-content) --}}
<div id="tab-content-reflectivo-direct" style="padding: 0; background: transparent; border-radius: 0;">
    @if($cotizacion->reflectivoCotizacion)
        @php
            $reflectivo = $cotizacion->reflectivoCotizacion;
        @endphp
        
        {{-- Prendas con Reflectivo Individual --}}
        @if($cotizacion->prendas && count($cotizacion->prendas) > 0)
            <div style="margin-bottom: 2rem;">
                <h3 style="color: #1e40af; font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">
                    <i class="fas fa-tshirt" style="margin-right: 0.5rem;"></i> Prendas con Reflectivo
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                    @foreach($cotizacion->prendas as $prenda)
                        <div style="border: 1px solid #e2e8f0; border-left: 4px solid #0ea5e9; border-radius: 8px; padding: 1.5rem; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <h4 style="color: #1e40af; font-weight: 600; margin: 0 0 0.75rem 0; font-size: 1rem;">
                                {{ $prenda->nombre_producto ?? 'Prenda' }}
                            </h4>
                            @if($prenda->descripcion)
                                <div style="background: #f0f7ff; border-radius: 6px; padding: 0.75rem; border-left: 3px solid #0284c7;">
                                    <p style="color: #475569; font-size: 0.9rem; line-height: 1.5; margin: 0;">{{ $prenda->descripcion }}</p>
                                </div>
                            @else
                                <p style="color: #94a3b8; font-size: 0.9rem; margin: 0; font-style: italic;">Sin descripción del reflectivo</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        
        {{-- Descripción del Reflectivo (General) --}}
        <div style="margin-bottom: 2rem;">
            <h3 style="color: #1e40af; font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Descripción General</h3>
            <div style="background: #f0f7ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1.5rem;">
                <p style="color: #475569; line-height: 1.6; margin: 0;">{{ $reflectivo->descripcion ?? 'Sin descripción' }}</p>
            </div>
        </div>

        {{-- Especificaciones Generales --}}
        @php
            $especificaciones = is_array($cotizacion->especificaciones) ? $cotizacion->especificaciones : json_decode($cotizacion->especificaciones, true) ?? [];
        @endphp
        @if(!empty($especificaciones))
            <div style="margin-bottom: 2rem;">
                <h3 style="color: #1e40af; font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Especificaciones</h3>
                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem;">
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        @foreach($especificaciones as $key => $valor)
                            <li style="padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #64748b; font-weight: 500;">{{ is_array($valor) ? ($valor['texto'] ?? $key) : $key }}</span>
                                @if(is_array($valor))
                                    <span style="color: #1e40af; font-weight: 600;">{{ $valor['valor'] ?? $valor }}</span>
                                @else
                                    <span style="color: #1e40af; font-weight: 600;">{{ $valor }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Ubicaciones del Reflectivo (de la tabla cotizaciones) --}}
        @php
            $ubicaciones = $cotizacion->ubicaciones ?? [];
            if (is_string($ubicaciones)) {
                $ubicaciones = json_decode($ubicaciones, true) ?? [];
            }
        @endphp
        @if(!empty($ubicaciones))
            <div style="margin-bottom: 2rem;">
                <h3 style="color: #1e40af; font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Ubicaciones (Cotización)</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
                    @foreach($ubicaciones as $ubicacion)
                        <div style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 1rem; background: #f8fafc;">
                            <h4 style="color: #0f172a; font-weight: 600; margin-bottom: 0.5rem;">
                                {{ $ubicacion['ubicacion'] ?? $ubicacion }}
                            </h4>
                            @if(is_array($ubicacion) && isset($ubicacion['descripcion']))
                                <p style="color: #64748b; font-size: 0.9rem; margin: 0;">{{ $ubicacion['descripcion'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Ubicaciones del Reflectivo (de la tabla reflectivo_cotizacion) --}}
        @php
            $ubicacionesReflectivo = $reflectivo->ubicacion ?? [];
            if (is_string($ubicacionesReflectivo)) {
                $ubicacionesReflectivo = json_decode($ubicacionesReflectivo, true) ?? [];
            }
        @endphp
        @if(!empty($ubicacionesReflectivo))
            <div style="margin-bottom: 2rem;">
                <h3 style="color: #1e40af; font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Ubicaciones del Reflectivo</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
                    @foreach($ubicacionesReflectivo as $ubicacion)
                        <div style="border: 1px solid #e2e8f0; border-left: 4px solid #0ea5e9; border-radius: 8px; padding: 1rem; background: white;">
                            <h4 style="color: #1e40af; font-weight: 600; margin-bottom: 0.5rem;">
                                {{ $ubicacion['ubicacion'] ?? $ubicacion }}
                            </h4>
                            @if(is_array($ubicacion) && isset($ubicacion['descripcion']))
                                <p style="color: #64748b; font-size: 0.9rem; margin: 0;">{{ $ubicacion['descripcion'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Observaciones Generales de la Cotización --}}
        @php
            $obsGenerales = is_array($cotizacion->observaciones_generales) ? $cotizacion->observaciones_generales : json_decode($cotizacion->observaciones_generales, true) ?? [];
        @endphp
        @if(!empty($obsGenerales))
            <div style="margin-bottom: 2rem;">
                <h3 style="color: #1e40af; font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Observaciones de Cotización</h3>
                <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 1.5rem;">
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        @foreach($obsGenerales as $obs)
                            <li style="padding: 0.75rem; background: white; border-left: 4px solid #f59e0b; margin-bottom: 0.75rem; border-radius: 4px;">
                                @if(is_array($obs) && $obs['tipo'] === 'checkbox' && $obs['valor'] === true)
                                    <strong style="color: #92400e;">{{ $obs['texto'] }}</strong>
                                    <span style="color: #f59e0b; font-size: 1.2rem; font-weight: bold; margin-left: 0.5rem;">✓</span>
                                @elseif(is_array($obs) && isset($obs['valor']))
                                    <strong style="color: #92400e;">{{ $obs['texto'] }} = {{ $obs['valor'] }}</strong>
                                @else
                                    <strong style="color: #92400e;">{{ is_array($obs) ? ($obs['texto'] ?? $obs) : $obs }}</strong>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Observaciones del Reflectivo --}}
        @php
            $obsReflectivo = is_array($reflectivo->observaciones_generales) ? $reflectivo->observaciones_generales : json_decode($reflectivo->observaciones_generales, true) ?? [];
        @endphp
        @if(!empty($obsReflectivo))
            <div style="margin-bottom: 2rem;">
                <h3 style="color: #1e40af; font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Observaciones del Reflectivo</h3>
                <div style="background: white; border: 1px solid #e2e8f0; border-left: 4px solid #0ea5e9; border-radius: 8px; padding: 1.5rem;">
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        @foreach($obsReflectivo as $obs)
                            <li style="padding: 0.75rem; background: transparent; border-left: none; margin-bottom: 0.75rem; border-radius: 4px;">
                                @if(is_array($obs) && $obs['tipo'] === 'checkbox' && $obs['valor'] === true)
                                    <strong style="color: #1e293b;">{{ $obs['texto'] }}</strong>
                                    <span style="color: #0ea5e9; font-size: 1.2rem; font-weight: bold; margin-left: 0.5rem;">✓</span>
                                @elseif(is_array($obs) && isset($obs['valor']))
                                    <strong style="color: #1e293b;">{{ $obs['texto'] }} = {{ $obs['valor'] }}</strong>
                                @else
                                    <strong style="color: #1e293b;">{{ is_array($obs) ? ($obs['texto'] ?? $obs) : $obs }}</strong>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Imágenes del Reflectivo (de la tabla reflectivo_fotos_cotizacion) --}}
        @if($reflectivo && $reflectivo->fotos && count($reflectivo->fotos) > 0)
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
                <i class="fas fa-images" style="color: #0ea5e9; font-size: 1.4rem;"></i> Imágenes Reflectivo ({{ $reflectivo->fotos->count() }})
            </div>
            <div style="
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            ">
                @php
                    $fotosArray = $reflectivo->fotos->map(fn($f) => asset('storage/' . $f->ruta_original))->toArray();
                    $fotosJson = json_encode($fotosArray);
                @endphp
                @foreach($reflectivo->fotos as $index => $foto)
                    @if($foto->ruta_original)
                        <div style="border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 6px 16px rgba(0, 0, 0, 0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)'">
                            <img src="{{ asset('storage/' . $foto->ruta_original) }}" alt="Reflectivo" 
                                 width="300" height="150"
                                 style="width: 100%; height: 150px; object-fit: cover; cursor: pointer; transition: transform 0.3s ease;"
                                 onmouseover="this.style.transform='scale(1.05)'"
                                 onmouseout="this.style.transform=''"
                                 onclick="abrirModalImagen('{{ asset('storage/' . $foto->ruta_original) }}', 'Reflectivo - Imagen {{ $index + 1 }}', {{ $fotosJson }}, {{ $index }})">
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 3rem 2rem; color: #94a3b8; font-style: italic; font-size: 0.95rem;">
                <i class="fas fa-images" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                Sin imágenes de reflectivo
            </div>
        @endif

        {{-- Imágenes Generales --}}
        @php
            $imagenesGenerales = is_array($cotizacion->imagenes) ? $cotizacion->imagenes : json_decode($cotizacion->imagenes, true) ?? [];
        @endphp
        @if(!empty($imagenesGenerales))
            <div style="margin-bottom: 2rem;">
                <h3 style="color: #1e40af; font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Imágenes Generales</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                    @foreach($imagenesGenerales as $imagen)
                        @php
                            $rutaImagen = $imagen['ruta'] ?? $imagen;
                            if (is_array($imagen)) {
                                $rutaImagen = $imagen['ruta'] ?? null;
                            }
                        @endphp
                        @if($rutaImagen)
                            <div style="border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.3s ease;">
                                <a href="{{ asset('storage/' . $rutaImagen) }}" target="_blank" style="display: block;">
                                    <img src="{{ asset('storage/' . $rutaImagen) }}" alt="Imagen" style="width: 100%; height: 200px; object-fit: cover; display: block;">
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

    @else
        <div style="text-align: center; padding: 3rem; color: #94a3b8;">
            <p style="font-size: 1.1rem;">No hay información de reflectivo disponible</p>
        </div>
    @endif
</div>
