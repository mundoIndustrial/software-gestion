{{-- Mostrar Reflectivo PASO 4 - Reflectivo por Prenda (Cotizaciones Combinadas) --}}
<div id="tab-reflectivo" class="tab-content" style="padding: 2rem; background: transparent; border-radius: 0 0 12px 12px;">
    @php
        // Obtener todos los reflectivos por prenda de esta cotización
        $reflectivoPrendas = \App\Models\ReflectivoCotizacion::where('cotizacion_id', $cotizacion->id)
            ->with(['prenda', 'prenda.tallas', 'fotos'])
            ->get();
        
        // Obtener también prenda_cot_reflectivo para ubicaciones y variaciones
        $prendaReflectivos = \App\Models\PrendaCotReflectivo::where('cotizacion_id', $cotizacion->id)
            ->get()
            ->keyBy('prenda_cot_id');
    @endphp

    @if($reflectivoPrendas && count($reflectivoPrendas) > 0)
        <div style="margin-bottom: 2rem;">
            <h2 style="color: #1e40af; font-size: 1.4rem; font-weight: 700; margin-bottom: 2rem; border-bottom: 3px solid #3b82f6; padding-bottom: 1rem;">
                <i class="fas fa-reflectivo" style="margin-right: 0.5rem;"></i> Detalles de Reflectivo por Prenda (PASO 4)
            </h2>

            {{-- Acordeón de Prendas --}}
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                @foreach($reflectivoPrendas as $reflectivo)
                    @php
                        $prenda = $reflectivo->prenda;
                        $prendasNombre = $prenda ? ($prenda->nombre_producto ?? 'Prenda sin nombre') : 'Prenda desconocida';
                        
                        // Obtener datos de prenda_cot_reflectivo
                        $prendaReflectivo = $prendaReflectivos[$prenda->id] ?? null;
                        $ubicacionesDeTabla = [];
                        $variaciones = [];
                        
                        if ($prendaReflectivo) {
                            $ubicacionesDeTabla = $prendaReflectivo->ubicaciones ? 
                                (is_string($prendaReflectivo->ubicaciones) ? json_decode($prendaReflectivo->ubicaciones, true) : $prendaReflectivo->ubicaciones) : [];
                            
                            $variaciones = $prendaReflectivo->variaciones ? 
                                (is_string($prendaReflectivo->variaciones) ? json_decode($prendaReflectivo->variaciones, true) : $prendaReflectivo->variaciones) : [];
                            
                            // Asegurar que variaciones es un array
                            if (!is_array($variaciones)) {
                                $variaciones = [];
                            }
                        }
                    @endphp
                    
                    <div style="border: 2px solid #3b82f6; border-radius: 12px; overflow: hidden; background: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);">
                        {{-- Header del Acordeón - Solo nombre de prenda --}}
                        <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 1.5rem; cursor: pointer; display: flex; align-items: center; justify-content: space-between; color: white;" onclick="this.parentElement.querySelector('.reflectivo-contenido').style.display = this.parentElement.querySelector('.reflectivo-contenido').style.display === 'none' ? 'block' : 'none';">
                            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; flex-grow: 1;">
                                <div style="display: flex; align-items: center; gap: 1rem; width: 100%;">
                                    <i class="fas fa-tshirt" style="font-size: 1.3rem;"></i>
                                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600;">{{ $prendasNombre }}</h3>
                                </div>
                                @if($prenda && $prenda->prenda_bodega == 1)
                                    <span style="display: inline-block; background: rgba(16, 185, 129, 0.2); color: #d1fae5; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.3px; border: 1px solid rgba(16, 185, 129, 0.4); margin-left: 1rem;">
                                        <i class="fas fa-box" style="margin-right: 0.4rem;"></i> Prenda viene de bodega: SI
                                    </span>
                                @endif
                            </div>
                            <i class="fas fa-chevron-down" style="transition: transform 0.3s ease; font-size: 1rem;"></i>
                        </div>

                        {{-- Contenido del Acordeón --}}
                        <div class="reflectivo-contenido" style="padding: 2rem; display: block;">
                            {{-- Card de Información Rápida (Tallas y Ubicaciones) --}}
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                                {{-- Tallas Card --}}
                                <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1px solid #bae6fd; border-radius: 8px; padding: 1.5rem;">
                                    <h4 style="color: #0284c7; font-size: 0.95rem; font-weight: 600; margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-ruler" style="color: #0284c7;"></i> Tallas
                                    </h4>
                                    @php
                                        $tallas = $prenda && $prenda->tallas ? $prenda->tallas : [];
                                    @endphp
                                    @if($tallas && count($tallas) > 0)
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                            @foreach($tallas as $talla)
                                                <span style="background: #0284c7; color: white; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.85rem; font-weight: 500;">
                                                    {{ $talla->talla }} ({{ $talla->cantidad }})
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <p style="color: #0369a1; font-size: 0.9rem; margin: 0;">No hay tallas registradas</p>
                                    @endif
                                </div>

                                {{-- Ubicaciones de Reflectivo Card --}}
                                <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #10b981; border-radius: 8px; padding: 1.5rem;">
                                    <h4 style="color: #059669; font-size: 0.95rem; font-weight: 600; margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-location-dot" style="color: #10b981;"></i> Ubicaciones de Reflectivo
                                    </h4>
                                    @if(!empty($ubicacionesDeTabla))
                                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                                            @foreach($ubicacionesDeTabla as $ubi)
                                                <div style="background: white; border-left: 4px solid #10b981; border-radius: 6px; padding: 1rem;">
                                                    @if(isset($ubi['ubicacion']) && !empty($ubi['ubicacion']))
                                                        <p style="color: #059669; font-weight: 600; margin: 0 0 0.5rem 0; font-size: 0.95rem;">
                                                            <i class="fas fa-map-pin" style="margin-right: 0.5rem;"></i>{{ $ubi['ubicacion'] }}
                                                        </p>
                                                    @endif
                                                    @if(isset($ubi['descripcion']) && !empty($ubi['descripcion']))
                                                        <p style="color: #6b7280; margin: 0; font-size: 0.9rem; line-height: 1.4;">
                                                            <i class="fas fa-note-sticky" style="margin-right: 0.5rem; color: #10b981;"></i>{{ $ubi['descripcion'] }}
                                                        </p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p style="color: #047857; font-size: 0.9rem; margin: 0;">No hay ubicaciones registradas</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Variaciones (del PASO 2) --}}
                            @php
                                $variacionesFormateadas = [];
                                
                                if (is_array($variaciones) && count($variaciones) > 0) {
                                    // El JSON contiene un array de objetos con variaciones
                                    foreach ($variaciones as $variacion) {
                                        if (is_array($variacion)) {
                                            // Color
                                            if (isset($variacion['color']) && !empty($variacion['color'])) {
                                                $variacionesFormateadas['Color'] = [
                                                    'valor' => $variacion['color'],
                                                    'observacion' => ''
                                                ];
                                            }
                                            
                                            // Tela (de telas_multiples)
                                            if (isset($variacion['telas_multiples']) && is_array($variacion['telas_multiples']) && count($variacion['telas_multiples']) > 0) {
                                                $telaObj = $variacion['telas_multiples'][0];
                                                if (is_array($telaObj) && isset($telaObj['tela'])) {
                                                    $variacionesFormateadas['Tela'] = [
                                                        'valor' => $telaObj['tela'],
                                                        'observacion' => $telaObj['referencia'] ?? ''
                                                    ];
                                                }
                                            }
                                            
                                            // Manga - Traer nombre de tipos_manga
                                            if (isset($variacion['tipo_manga_id'])) {
                                                $tipoManga = \App\Models\TipoManga::find($variacion['tipo_manga_id']);
                                                $nombreManga = $tipoManga ? $tipoManga->nombre : 'Tipo ' . $variacion['tipo_manga_id'];
                                                $variacionesFormateadas['Manga'] = [
                                                    'valor' => $nombreManga,
                                                    'observacion' => ''
                                                ];
                                            }
                                            
                                            // Bolsillo
                                            if (isset($variacion['tiene_bolsillos'])) {
                                                $bolsilloValor = $variacion['tiene_bolsillos'] ? 'Sí' : 'No';
                                                $bolsilloObs = $variacion['obs_bolsillos'] ?? '';
                                                $variacionesFormateadas['Bolsillo'] = [
                                                    'valor' => $bolsilloValor,
                                                    'observacion' => $bolsilloObs
                                                ];
                                            }
                                            
                                            // Broche/Botón - Traer nombre de tipos_broche_boton
                                            if (isset($variacion['tipo_broche_id'])) {
                                                $tipoBroche = \App\Models\TipoBrocheBoton::find($variacion['tipo_broche_id']);
                                                $nombreBroche = $tipoBroche ? $tipoBroche->nombre : 'Tipo ' . $variacion['tipo_broche_id'];
                                                $brocheObs = $variacion['obs_broche'] ?? '';
                                                $variacionesFormateadas['Broche/Botón'] = [
                                                    'valor' => $nombreBroche,
                                                    'observacion' => $brocheObs
                                                ];
                                            }
                                        }
                                    }
                                }
                            @endphp
                            @if(!empty($variacionesFormateadas))
                                <div style="margin-bottom: 1.5rem;">
                                    <h5 style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 0.8rem; letter-spacing: 0.5px;">
                                        <i class="fas fa-palette"></i> Variaciones:
                                    </h5>
                                    <div style="overflow-x: auto;">
                                        <table style="
                                            width: 100%;
                                            border-collapse: collapse;
                                            background: white;
                                            border: 1px solid #e5e7eb;
                                            border-radius: 6px;
                                            overflow: hidden;
                                        ">
                                            <thead style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); border-bottom: 2px solid #1e3a8a;">
                                                <tr>
                                                    <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: white; font-size: 0.8rem; text-transform: capitalize; letter-spacing: 0.3px;">Tipo</th>
                                                    <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: white; font-size: 0.8rem; text-transform: capitalize; letter-spacing: 0.3px;">Valor</th>
                                                    <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: white; font-size: 0.8rem; text-transform: capitalize; letter-spacing: 0.3px;">Observación</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($variacionesFormateadas as $tipo => $datos)
                                                    <tr style="border-bottom: 1px solid #e5e7eb;">
                                                        <td style="padding: 0.75rem 1rem; font-size: 0.9rem; color: #374151; font-weight: 600;">
                                                            {{ $tipo }}
                                                        </td>
                                                        <td style="padding: 0.75rem 1rem; font-size: 0.9rem; color: #0369a1; font-weight: 500;">
                                                            {{ $datos['valor'] }}
                                                        </td>
                                                        <td style="padding: 0.75rem 1rem; font-size: 0.9rem; color: #6b7280;">
                                                            {{ !empty($datos['observacion']) ? $datos['observacion'] : '-' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                {{-- Sin variaciones --}}
                                <div style="color: #9ca3af; font-size: 0.9rem; margin-bottom: 1.5rem;">
                                    <em>No hay variaciones registradas para esta prenda</em>
                                </div>
                            @endif

                            {{-- Descripción --}}
                            @if($reflectivo->descripcion)
                                <div style="margin-bottom: 2rem;">
                                    <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-align-left" style="color: #f59e0b;"></i> Descripción
                                    </h4>
                                    <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 1.5rem;">
                                        <p style="color: #92400e; line-height: 1.6; margin: 0;">{{ $reflectivo->descripcion }}</p>
                                    </div>
                                </div>
                            @endif

                            {{-- Observaciones Generales --}}
                            @php
                                $obsGenerales = $reflectivo->observaciones_generales ?? [];
                                if (is_string($obsGenerales)) {
                                    $obsGenerales = json_decode($obsGenerales, true) ?? [];
                                }
                            @endphp
                            @if(!empty($obsGenerales))
                                <div style="margin-bottom: 2rem;">
                                    <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-sticky-note" style="color: #1e40af;"></i> Observaciones
                                    </h4>
                                    <div style="background: #f3e8ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 1.5rem;">
                                        <ul style="list-style: none; padding: 0; margin: 0;">
                                            @foreach($obsGenerales as $obs)
                                                <li style="padding: 0.75rem; background: white; border-left: 4px solid #1e40af; margin-bottom: 0.75rem; border-radius: 4px;">
                                                    @if(is_array($obs) && $obs['tipo'] === 'checkbox' && $obs['valor'] === true)
                                                        <strong style="color: #6b21a8;">{{ $obs['texto'] }}</strong>
                                                        <span style="color: #1e40af; font-size: 1.2rem; font-weight: bold; margin-left: 0.5rem;">✓</span>
                                                    @elseif(is_array($obs) && isset($obs['valor']))
                                                        <strong style="color: #6b21a8;">{{ $obs['texto'] }}</strong>: <span style="color: #7c3aed;">{{ $obs['valor'] }}</span>
                                                    @else
                                                        <strong style="color: #6b21a8;">{{ is_array($obs) ? ($obs['texto'] ?? $obs) : $obs }}</strong>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif

                            {{-- Imágenes del Reflectivo y de la Prenda (PASO 2) --}}
                            @php
                                $todasLasImagenes = [];
                                
                                // Agregar fotos de la prenda (PASO 2)
                                if ($prenda && $prenda->fotos && count($prenda->fotos) > 0) {
                                    foreach ($prenda->fotos as $foto) {
                                        $todasLasImagenes[] = [
                                            'url' => isset($foto->ruta_webp) ? '/storage/' . $foto->ruta_webp : null,
                                            'tipo' => 'Prenda (PASO 2)',
                                            'orden' => $foto->orden ?? 'N/A',
                                            'fecha' => $foto->created_at
                                        ];
                                    }
                                }
                                
                                // Agregar fotos del reflectivo
                                if ($reflectivo->fotos && count($reflectivo->fotos) > 0) {
                                    foreach ($reflectivo->fotos as $foto) {
                                        if ($foto->ruta_original) {
                                            $todasLasImagenes[] = [
                                                'url' => $foto->url,
                                                'tipo' => 'Reflectivo (PASO 4)',
                                                'orden' => $foto->orden ?? 'N/A',
                                                'fecha' => $foto->created_at
                                            ];
                                        }
                                    }
                                }
                            @endphp
                            @if(!empty($todasLasImagenes))
                                <div style="margin-bottom: 2rem;">
                                    <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-images" style="color: #ec4899;"></i> Imágenes Adjuntas ({{ count($todasLasImagenes) }})
                                    </h4>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1.5rem;">
                                        @foreach($todasLasImagenes as $imagen)
                                            @if($imagen['url'])
                                                <div style="position: relative; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: transform 0.3s ease, box-shadow 0.3s ease; background: #f3f4f6;">
                                                    <a href="{{ $imagen['url'] }}" target="_blank" style="display: block; text-decoration: none;">
                                                        <div style="position: relative; width: 100%; height: 180px; background: #e5e7eb;">
                                                            <img src="{{ $imagen['url'] }}" alt="{{ $imagen['tipo'] }}" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                                                            
                                                            {{-- Overlay al pasar mouse --}}
                                                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); opacity: 0; transition: opacity 0.3s ease; display: flex; align-items: center; justify-content: center;" class="imagen-overlay">
                                                                <i class="fas fa-search-plus" style="color: white; font-size: 1.8rem;"></i>
                                                            </div>
                                                        </div>
                                                    </a>
                                                    
                                                    {{-- Información de la imagen --}}
                                                    <div style="padding: 0.75rem; background: white;">
                                                        <p style="color: #64748b; font-size: 0.8rem; margin: 0; word-break: break-word;">
                                                            <strong>{{ $imagen['tipo'] }}</strong>
                                                        </p>
                                                        <p style="color: #94a3b8; font-size: 0.75rem; margin: 0.25rem 0 0 0;">
                                                            Orden: {{ $imagen['orden'] }}
                                                        </p>
                                                        @if($imagen['fecha'])
                                                            <p style="color: #94a3b8; font-size: 0.75rem; margin: 0.25rem 0 0 0;">
                                                                {{ $imagen['fecha']->format('d/m/Y H:i') }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div style="background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 8px; padding: 1.5rem; text-align: center;">
                                    <i class="fas fa-image" style="color: #0284c7; font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                                    <p style="color: #0369a1; margin: 0;">No hay imágenes adjuntas para esta prenda</p>
                                </div>
                            @endif

                            {{-- Información Adicional --}}
                            @if($reflectivo->tipo_venta)
                                <div style="margin-top: 2rem; padding: 1rem; background: #f1f5f9; border-left: 4px solid #0284c7; border-radius: 4px;">
                                    <p style="color: #334155; margin: 0;"><strong>Tipo de Venta:</strong> {{ $reflectivo->tipo_venta }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Especificaciones de la Cotización --}}
            @php
                $especificaciones = $cotizacion->especificaciones ?? [];
                if (is_string($especificaciones)) {
                    $especificaciones = json_decode($especificaciones, true) ?? [];
                }
            @endphp
            @if(!empty($especificaciones))
                <div style="margin-top: 3rem; margin-bottom: 2rem;">
                    <h2 style="color: #1e40af; font-size: 1.4rem; font-weight: 700; margin-bottom: 2rem; border-bottom: 3px solid #3b82f6; padding-bottom: 1rem;">
                        <i class="fas fa-list-check" style="margin-right: 0.5rem;"></i> Especificaciones
                    </h2>
                    
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);">
                        <div style="overflow-x: auto;">
                            <table style="
                                width: 100%;
                                border-collapse: collapse;
                                background: white;
                            ">
                                <thead style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); border-bottom: 2px solid #1e3a8a;">
                                    <tr>
                                        <th style="padding: 1rem; text-align: left; font-weight: 700; color: white; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.3px;">Especificación</th>
                                        <th style="padding: 1rem; text-align: left; font-weight: 700; color: white; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.3px;">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($especificaciones as $clave => $valor)
                                        @php
                                            $nombreEspec = ucfirst(str_replace('_', ' ', $clave));
                                            $valorEspec = '';
                                            
                                            // Procesar el valor según su tipo
                                            if (is_array($valor)) {
                                                // Si es un array vacío, no mostrar
                                                if (empty($valor)) {
                                                    continue;
                                                }
                                                
                                                // Si es un array de objetos, extraer valores
                                                if (isset($valor[0]) && is_array($valor[0])) {
                                                    $items = [];
                                                    foreach ($valor as $item) {
                                                        if (isset($item['valor'])) {
                                                            $items[] = $item['valor'];
                                                        } elseif (isset($item['nombre'])) {
                                                            $items[] = $item['nombre'];
                                                        } else {
                                                            // Si no tiene campos específicos, tomar el primer valor
                                                            $firstValue = array_values($item)[0] ?? '';
                                                            if (!empty($firstValue)) {
                                                                $items[] = $firstValue;
                                                            }
                                                        }
                                                    }
                                                    $valorEspec = implode(', ', $items);
                                                } else {
                                                    // Array simple, unir con comas
                                                    $items = array_filter($valor, function($v) { 
                                                        return !empty($v) && $v !== 'null'; 
                                                    });
                                                    $valorEspec = implode(', ', $items);
                                                }
                                            } else if (is_bool($valor)) {
                                                $valorEspec = $valor ? 'Sí' : 'No';
                                            } else if (is_null($valor)) {
                                                continue;
                                            } else {
                                                $valorEspec = (string)$valor;
                                            }
                                            
                                            // No mostrar si está vacío después del procesamiento
                                            if (empty($valorEspec)) {
                                                continue;
                                            }
                                        @endphp
                                        <tr style="border-bottom: 1px solid #e5e7eb; transition: background-color 0.2s ease;">
                                            <td style="padding: 1rem; font-size: 0.95rem; color: #374151; font-weight: 600;">
                                                {{ $nombreEspec }}
                                            </td>
                                            <td style="padding: 1rem; font-size: 0.95rem; color: #0369a1; font-weight: 500;">
                                                {{ $valorEspec }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <style>
            .imagen-overlay:hover {
                opacity: 1 !important;
            }
        </style>
    @else
        {{-- Mensaje cuando no hay reflectivos por prenda --}}
        <div style="text-align: center; padding: 4rem 2rem; color: #94a3b8;">
            <i class="fas fa-lightbulb" style="font-size: 4rem; margin-bottom: 1rem; display: block; color: #cbd5e1; opacity: 0.5;"></i>
            <h3 style="font-size: 1.3rem; margin: 1rem 0; color: #64748b;">No hay información de Reflectivo</h3>
            <p style="font-size: 1rem; color: #94a3b8;">El reflectivo (PASO 4) no fue agregado a esta cotización.</p>
        </div>
    @endif
</div>
