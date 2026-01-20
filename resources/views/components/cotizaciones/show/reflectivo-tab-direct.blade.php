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
                            // Debug: Log foto data
                            if ($fotosPrenda->count() > 0) {
                                \Log::info(' Fotos de reflectivo en vista', [
                                    'prenda_id' => $prenda->id,
                                    'reflectivo_id' => $reflectivoPrenda ? $reflectivoPrenda->id : null,
                                    'fotos_count' => $fotosPrenda->count(),
                                    'fotos_data' => $fotosPrenda->map(function($f) {
                                        return [
                                            'id' => $f->id,
                                            'ruta_original' => $f->ruta_original,
                                            'ruta_webp' => $f->ruta_webp,
                                            'url' => $f->url
                                        ];
                                    })->toArray()
                                ]);
                            }
                        @endphp
                        @if($fotosPrenda->count() > 0)
                            <div style="margin-bottom: 2rem;">
                                <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">
                                    Fotos del Reflectivo ({{ $fotosPrenda->count() }})
                                </h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                                    @foreach($fotosPrenda as $fotoIndex => $foto)
                                        <div onclick="abrirGaleriaReflectivo({{ $index }}, {{ $fotoIndex }})" 
                                             style="position: relative; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); background: #f3f4f6; cursor: pointer; transition: transform 0.2s;"
                                             onmouseover="this.style.transform='scale(1.05)'" 
                                             onmouseout="this.style.transform='scale(1)'">
                                            <img src="{{ $foto->url }}" 
                                                 alt="Foto reflectivo" 
                                                 style="width: 100%; height: 200px; object-fit: cover;"
                                                 onerror="this.parentElement.innerHTML='<div style=\'padding:1rem;text-align:center;color:#ef4444;\'><p> Error cargando imagen</p></div>';">
                                            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0); transition: background 0.2s;" 
                                                 onmouseover="this.style.background='rgba(0,0,0,0.3)'" 
                                                 onmouseout="this.style.background='rgba(0,0,0,0)'">
                                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 2rem; opacity: 0; transition: opacity 0.2s;"
                                                     onmouseover="this.style.opacity='1'" 
                                                     onmouseout="this.style.opacity='0'">
                                                    🔍
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div style="margin-bottom: 2rem;">
                                <h4 style="color: #1e40af; font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Fotos del Reflectivo</h4>
                                <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 1.5rem;">
                                    <p style="color: #92400e; font-size: 0.9rem; margin: 0;">
                                        ⚠️ Sin fotos
                                        @if($reflectivoPrenda)
                                            (Reflectivo ID: {{ $reflectivoPrenda->id }})
                                        @else
                                            (Sin reflectivo asociado)
                                        @endif
                                    </p>
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
                                            'disponibilidad' => ['emoji' => '', 'label' => 'DISPONIBILIDAD'],
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

{{-- Modal de Galería de Imágenes Reflectivo --}}
<style>
    .modal-galeria-reflectivo {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.95);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    .modal-galeria-reflectivo.activo {
        display: flex;
    }
    .modal-galeria-contenido {
        position: relative;
        background: white;
        border-radius: 12px;
        width: 95vw;
        max-width: 1400px;
        height: 90vh;
        max-height: 900px;
        overflow: visible;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: column;
    }
    .modal-galeria-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%);
        color: white;
        border-radius: 12px 12px 0 0;
        flex-shrink: 0;
    }
    .modal-galeria-titulo {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
    }
    .modal-galeria-cerrar {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        font-size: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    .modal-galeria-cerrar:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }
    .modal-galeria-body {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #1f2937;
        padding: 2rem;
        flex: 1;
        overflow: hidden;
    }
    .modal-galeria-imagen {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    }
    .modal-galeria-flecha {
        position: absolute;
        background: rgba(30, 64, 175, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        cursor: pointer;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        z-index: 10;
    }
    .modal-galeria-flecha:hover {
        background: rgba(30, 64, 175, 1);
        transform: scale(1.1);
    }
    .modal-galeria-flecha-izq {
        left: 1rem;
    }
    .modal-galeria-flecha-der {
        right: 1rem;
    }
    .modal-galeria-contador {
        text-align: center;
        padding: 1rem;
        background: white;
        border-top: 1px solid #e2e8f0;
        color: #1e40af;
        font-weight: 700;
        font-size: 1rem;
    }
</style>

<div id="modalGaleriaReflectivo" class="modal-galeria-reflectivo">
    <div class="modal-galeria-contenido">
        <div class="modal-galeria-header">
            <h2 class="modal-galeria-titulo" id="tituloGaleriaReflectivo">Foto de Reflectivo</h2>
            <button class="modal-galeria-cerrar" onclick="cerrarGaleriaReflectivo()">✕</button>
        </div>
        
        <div class="modal-galeria-body">
            <button id="flechaIzqReflectivo" class="modal-galeria-flecha modal-galeria-flecha-izq" onclick="navegarGaleriaReflectivo(-1)">‹</button>
            <img id="imagenGaleriaReflectivo" src="" alt="Foto reflectivo" class="modal-galeria-imagen">
            <button id="flechaDerReflectivo" class="modal-galeria-flecha modal-galeria-flecha-der" onclick="navegarGaleriaReflectivo(1)">›</button>
        </div>
        
        <div class="modal-galeria-contador" id="contadorGaleriaReflectivo">1 / 1</div>
    </div>
</div>

<script>
// Datos de fotos por prenda
const fotosPorPrendaReflectivo = {
    @foreach($cotizacion->prendas as $prendaIndex => $prenda)
        @php
            $reflectivoPrenda = $prenda->reflectivo ? $prenda->reflectivo->first() : null;
            $fotosPrenda = $reflectivoPrenda && $reflectivoPrenda->fotos ? $reflectivoPrenda->fotos : collect();
        @endphp
        {{ $prendaIndex }}: [
            @foreach($fotosPrenda as $foto)
                "{{ $foto->url }}",
            @endforeach
        ],
    @endforeach
};

let galeriaReflectivoActual = {
    prendaIndex: 0,
    imagenIndex: 0,
    imagenes: []
};

function abrirGaleriaReflectivo(prendaIndex, fotoIndex) {
    const imagenes = fotosPorPrendaReflectivo[prendaIndex] || [];
    
    if (imagenes.length === 0) {
        return;
    }
    
    galeriaReflectivoActual = {
        prendaIndex: prendaIndex,
        imagenIndex: fotoIndex,
        imagenes: imagenes
    };
    
    actualizarGaleriaReflectivo();
    
    const modal = document.getElementById('modalGaleriaReflectivo');
    if (modal) {
        modal.classList.add('activo');
    }
}

function actualizarGaleriaReflectivo() {
    const imagen = document.getElementById('imagenGaleriaReflectivo');
    const contador = document.getElementById('contadorGaleriaReflectivo');
    const flechaIzq = document.getElementById('flechaIzqReflectivo');
    const flechaDer = document.getElementById('flechaDerReflectivo');
    
    if (!imagen) return;
    
    imagen.src = galeriaReflectivoActual.imagenes[galeriaReflectivoActual.imagenIndex];
    
    if (contador) {
        contador.textContent = `${galeriaReflectivoActual.imagenIndex + 1} / ${galeriaReflectivoActual.imagenes.length}`;
    }
    
    if (flechaIzq) {
        flechaIzq.style.display = galeriaReflectivoActual.imagenIndex > 0 ? 'flex' : 'none';
    }
    if (flechaDer) {
        flechaDer.style.display = galeriaReflectivoActual.imagenIndex < galeriaReflectivoActual.imagenes.length - 1 ? 'flex' : 'none';
    }
}

function navegarGaleriaReflectivo(direccion) {
    const nuevoIndex = galeriaReflectivoActual.imagenIndex + direccion;
    
    if (nuevoIndex >= 0 && nuevoIndex < galeriaReflectivoActual.imagenes.length) {
        galeriaReflectivoActual.imagenIndex = nuevoIndex;
        actualizarGaleriaReflectivo();
    }
}

function cerrarGaleriaReflectivo() {
    const modal = document.getElementById('modalGaleriaReflectivo');
    if (modal) {
        modal.classList.remove('activo');
    }
    
    galeriaReflectivoActual = {
        prendaIndex: 0,
        imagenIndex: 0,
        imagenes: []
    };
}

// Event listeners para navegación con teclado
document.addEventListener('keydown', function(event) {
    const modal = document.getElementById('modalGaleriaReflectivo');
    if (modal && modal.classList.contains('activo')) {
        if (event.key === 'Escape') {
            cerrarGaleriaReflectivo();
        } else if (event.key === 'ArrowLeft') {
            navegarGaleriaReflectivo(-1);
        } else if (event.key === 'ArrowRight') {
            navegarGaleriaReflectivo(1);
        }
    }
});

// Cerrar al hacer click fuera del modal
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalGaleriaReflectivo');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarGaleriaReflectivo();
            }
        });
    }
});

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
</script>
