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
                            
                            {{-- Tallas --}}
                            @if($prenda->tallas && count($prenda->tallas) > 0)
                                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #93c5fd;">
                                    <p style="color: #1e40af; font-size: 0.9rem; font-weight: 600; margin: 0 0 0.5rem 0;">Tallas:</p>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                        @foreach($prenda->tallas as $talla)
                                            <span style="background: #dbeafe; color: #1e40af; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                                {{ $talla->talla }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #93c5fd;">
                                    <p style="color: #94a3b8; font-size: 0.85rem; margin: 0; font-style: italic;">Sin tallas definidas</p>
                                </div>
                            @endif
                        </div>
                        
                        {{-- Descripción del Reflectivo --}}
                        <div style="margin-bottom: 2rem;">
                            <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Descripción General</h4>
                            <div style="background: #f0f7ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1.5rem;">
                                <p style="color: #475569; line-height: 1.6; margin: 0;">{{ $reflectivo->descripcion ?? 'Sin descripción' }}</p>
                            </div>
                        </div>
                        
                        {{-- Ubicaciones del Reflectivo --}}
                        @php
                            // Obtener ubicaciones del reflectivo de ESTA prenda específica
                            $reflectivoPrenda = $prenda->reflectivo ? $prenda->reflectivo->first() : null;
                            $ubicacionesPrenda = [];
                            if ($reflectivoPrenda && $reflectivoPrenda->ubicacion) {
                                $ubicacionesPrenda = is_string($reflectivoPrenda->ubicacion) 
                                    ? json_decode($reflectivoPrenda->ubicacion, true) ?? [] 
                                    : ($reflectivoPrenda->ubicacion ?? []);
                            }
                        @endphp
                        @if(!empty($ubicacionesPrenda))
                            <div style="margin-bottom: 2rem;">
                                <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Ubicaciones del Reflectivo</h4>
                                <div style="display: grid; gap: 1rem;">
                                    @foreach($ubicacionesPrenda as $ubi)
                                        @if(isset($ubi['ubicacion']))
                                            <div style="background: white; border: 2px solid #0ea5e9; border-radius: 8px; padding: 1rem;">
                                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                                    <span style="color: #0ea5e9; font-weight: 700; font-size: 1rem;">📍</span>
                                                    <span style="font-weight: 700; color: #1e40af; font-size: 0.95rem;">{{ $ubi['ubicacion'] }}</span>
                                                </div>
                                                @if(!empty($ubi['descripcion']))
                                                    <p style="margin: 0; color: #64748b; font-size: 0.9rem; padding-left: 1.5rem;">{{ $ubi['descripcion'] }}</p>
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div style="margin-bottom: 2rem;">
                                <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Ubicaciones del Reflectivo</h4>
                                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem;">
                                    <p style="color: #94a3b8; font-size: 0.9rem; margin: 0; font-style: italic;">Sin ubicaciones definidas</p>
                                </div>
                            </div>
                        @endif
                        
                        {{-- Fotos del Reflectivo --}}
                        @php
                            $fotosPrenda = $reflectivoPrenda && $reflectivoPrenda->fotos ? $reflectivoPrenda->fotos : collect();
                        @endphp
                        @if($fotosPrenda->count() > 0)
                            <div style="margin-bottom: 2rem;">
                                <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Fotos del Reflectivo</h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                                    @foreach($fotosPrenda as $foto)
                                        <div style="position: relative; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                            <img src="{{ asset('storage/' . $foto->ruta_original) }}" 
                                                 alt="Foto reflectivo" 
                                                 style="width: 100%; height: 200px; object-fit: cover;">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div style="margin-bottom: 2rem;">
                                <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Fotos del Reflectivo</h4>
                                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem;">
                                    <p style="color: #94a3b8; font-size: 0.9rem; margin: 0; font-style: italic;">Sin fotos</p>
                                </div>
                            </div>
                        @endif
                        
                        {{-- Especificaciones (al final) --}}
                        @if(!empty($especificaciones))
                            <div style="margin-bottom: 2rem;">
                                <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Especificaciones</h4>
                                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; overflow-x: auto;">
                                    @php
                                        $categoriasInfo = [
                                            'disponibilidad' => ['emoji' => '📦', 'label' => 'DISPONIBILIDAD'],
                                            'forma_pago' => ['emoji' => '💳', 'label' => 'FORMA DE PAGO'],
                                            'regimen' => ['emoji' => '🏛️', 'label' => 'RÉGIMEN'],
                                            'se_ha_vendido' => ['emoji' => '📊', 'label' => 'SE HA VENDIDO'],
                                            'ultima_venta' => ['emoji' => '💰', 'label' => 'ÚLTIMA VENTA'],
                                            'flete' => ['emoji' => '🚚', 'label' => 'FLETE DE ENVÍO']
                                        ];
                                    @endphp
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <thead>
                                            <tr style="background: #1e40af; border-bottom: 2px solid #1e40af;">
                                                <th style="padding: 10px; text-align: left; font-weight: 600; color: white;">Valor</th>
                                                <th style="padding: 10px; text-align: left; font-weight: 600; color: white;">Observaciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($categoriasInfo as $categoriaKey => $info)
                                                @if(isset($especificaciones[$categoriaKey]) && !empty($especificaciones[$categoriaKey]))
                                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                                        <td colspan="2" style="font-weight: 600; background: #1e40af; padding: 10px; color: white;">
                                                            <span style="font-size: 1rem; margin-right: 8px;">{{ $info['emoji'] }}</span>
                                                            <span>{{ $info['label'] }}</span>
                                                        </td>
                                                    </tr>
                                                    @foreach($especificaciones[$categoriaKey] as $item)
                                                        <tr style="border-bottom: 1px solid #e2e8f0;">
                                                            <td style="padding: 10px; color: #333; font-weight: 500;">
                                                                @if(is_array($item) && isset($item['valor']))
                                                                    {{ $item['valor'] ?? '-' }}
                                                                @else
                                                                    {{ $item ?? '-' }}
                                                                @endif
                                                            </td>
                                                            <td style="padding: 10px; color: #64748b; font-size: 0.9rem;">
                                                                @if(is_array($item) && isset($item['observacion']) && !empty($item['observacion']))
                                                                    {{ $item['observacion'] }}
                                                                @else
                                                                    Sin observaciones
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div style="margin-bottom: 2rem;">
                                <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Especificaciones</h4>
                                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem;">
                                    <p style="color: #94a3b8; font-size: 0.9rem; margin: 0; font-style: italic;">Sin especificaciones</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 1.5rem; text-align: center;">
                <p style="color: #92400e; margin: 0; font-weight: 500;">No hay prendas registradas en esta cotización</p>
            </div>
        @endif
    @else
        <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 1.5rem; text-align: center;">
            <p style="color: #92400e; margin: 0; font-weight: 500;">No hay información de reflectivo disponible</p>
        </div>
    @endif
</div>

<script>
function mostrarPrendaReflectivo(index) {
    // Ocultar todos los contenidos
    document.querySelectorAll('[id^="content-prenda-"]').forEach(el => {
        el.style.display = 'none';
    });
    
    // Remover estilo activo de todos los tabs
    document.querySelectorAll('[id^="tab-prenda-"]').forEach(el => {
        el.style.background = '#f1f5f9';
        el.style.color = '#64748b';
    });
    
    // Mostrar el contenido seleccionado
    document.getElementById('content-prenda-' + index).style.display = 'block';
    
    // Activar el tab seleccionado
    const tab = document.getElementById('tab-prenda-' + index);
    tab.style.background = '#1e40af';
    tab.style.color = 'white';
}
