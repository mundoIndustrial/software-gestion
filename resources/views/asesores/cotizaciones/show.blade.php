@extends('asesores.layout')

@push('styles')
<style>
    .top-nav {
        display: none !important;
    }
</style>
@endpush

@section('content')
<style>
    * {
        --primary: #1e40af;
        --secondary: #0ea5e9;
        --accent: #06b6d4;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
    }

    .page-wrapper {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        min-height: 100vh;
        padding: 2rem 0;
        transform: scale(0.75);
        transform-origin: top center;
        width: 133.33%;
        margin-left: -16.665%;
    }

    .cotizacion-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        padding: 1.5rem 2.5rem;
        margin-bottom: 1rem;
        margin-top: -2rem;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(30, 64, 175, 0.15);
        position: relative;
        overflow: hidden;
    }

    .cotizacion-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 500px;
        height: 500px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        pointer-events: none;
    }

    .cotizacion-header h1 {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        position: relative;
        z-index: 1;
    }

    .cotizacion-header p {
        font-size: 0.95rem;
        opacity: 0.95;
        position: relative;
        z-index: 1;
    }

    .cotizacion-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .info-card {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        border-top: 4px solid var(--secondary);
        transition: all 0.3s ease;
    }

    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    .info-card label {
        font-size: 0.7rem;
        color: #64748b;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 0.75rem;
        display: block;
    }

    .info-card .value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
    }

    .section-title {
        font-size: 1.4rem;
        font-weight: 800;
        color: #1e293b;
        margin-top: 2.5rem;
        margin-bottom: 1.75rem;
        padding-bottom: 1rem;
        border-bottom: 3px solid var(--secondary);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .section-title i {
        color: var(--secondary);
        font-size: 1.4rem;
    }

    .productos-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2.5rem;
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .productos-table thead {
        background: var(--primary);
        color: white;
    }

    .productos-table th {
        padding: 1rem;
        text-align: left;
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid var(--secondary);
    }

    .productos-table td {
        padding: 1.2rem;
        border-bottom: 1px solid #e2e8f0;
        font-size: 1.05rem;
    }

    .productos-table tbody tr:hover {
        background: #f8fafc;
    }

    .productos-table tbody tr:last-child td {
        border-bottom: 2px solid var(--primary);
    }

    .producto-nombre {
        font-weight: 700;
        color: var(--primary);
    }

    .producto-tela {
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .producto-cantidad {
        text-align: center;
        font-weight: 600;
        color: var(--secondary);
    }

    .producto-descripcion {
        color: #64748b;
        font-size: 0.9rem;
        line-height: 1.6;
        max-width: 100%;
        word-wrap: break-word;
        padding: 1rem 0.5rem;
    }

    .tecnicas-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 2.5rem;
    }

    .tecnica-badge {
        background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%);
        color: white;
        padding: 0.5rem 1.1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);
    }

    .observaciones-box {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        border-left: 4px solid var(--secondary);
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .observaciones-box label {
        font-weight: 800;
        color: var(--primary);
        margin-bottom: 0.75rem;
        display: block;
        font-size: 0.9rem;
    }

    .observaciones-box p {
        color: #475569;
        margin: 0;
        line-height: 1.7;
        font-size: 0.95rem;
    }

    .estado-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.85rem;
    }

    .estado-borrador {
        background: #fef3c7;
        color: #92400e;
    }

    .estado-enviada {
        background: #cffafe;
        color: #164e63;
    }

    .estado-aceptada {
        background: #dcfce7;
        color: #166534;
    }

    .estado-rechazada {
        background: #fee2e2;
        color: #7f1d1d;
    }

    .footer-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2.5rem;
        padding-top: 2rem;
        border-top: 2px solid #e2e8f0;
    }

    .btn-custom {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 700;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.95rem;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-volver {
        background: #64748b;
        color: white;
    }

    .btn-volver:hover {
        background: #475569;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }

    .btn-editar {
        background: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%);
        color: white;
    }

    .btn-editar:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
    }

    .sin-contenido {
        text-align: center;
        padding: 3rem 2rem;
        color: #94a3b8;
        font-style: italic;
        font-size: 0.95rem;
    }

    .container-fluid {
        padding-left: 1rem;
        padding-right: 1rem;
    }
</style>

<div class="page-wrapper">

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="cotizacion-header">
        <h1>
            <i class="fas fa-file-invoice"></i> Detalle de Cotización
        </h1>
        <p style="margin: 0; opacity: 0.9;">Cotización #{{ $cotizacion->id }}</p>
    </div>

    <!-- Información Principal -->
    <div class="cotizacion-info">
        <div class="info-card">
            <label><i class="fas fa-user"></i> Cliente</label>
            <div class="value">{{ $cotizacion->cliente }}</div>
        </div>
        <div class="info-card">
            <label><i class="fas fa-tag"></i> Estado</label>
            <div class="value">
                <span class="estado-badge estado-{{ $cotizacion->es_borrador ? 'borrador' : ($cotizacion->estado === 'aceptada' ? 'aceptada' : ($cotizacion->estado === 'rechazada' ? 'rechazada' : 'enviada')) }}">
                    {{ $cotizacion->es_borrador ? 'Borrador' : ucfirst($cotizacion->estado) }}
                </span>
            </div>
        </div>
        <div class="info-card">
            <label><i class="fas fa-calendar-plus"></i> Fecha Inicio</label>
            <div class="value" style="font-size: 1rem;">{{ $cotizacion->fecha_inicio ? $cotizacion->fecha_inicio->format('d/m/Y H:i') : '-' }}</div>
        </div>
        @if($cotizacion->fecha_envio)
            <div class="info-card">
                <label><i class="fas fa-calendar-check"></i> Fecha Envío</label>
                <div class="value" style="font-size: 1rem;">{{ $cotizacion->fecha_envio->format('d/m/Y H:i') }}</div>
            </div>
        @endif
    </div>

    <!-- Prendas -->
    <div class="section-title">
        <i class="fas fa-box"></i> Prendas
    </div>
    @if($cotizacion->prendasCotizaciones && count($cotizacion->prendasCotizaciones) > 0)
        <table class="productos-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Prenda</th>
                    <th style="width: 50%;">Descripción & Tallas</th>
                    <th style="width: 30%; text-align: center;">Imagen Prenda & Tela</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cotizacion->prendasCotizaciones as $index => $prenda)
                    <tr>
                        <td>
                            <div class="producto-nombre">{{ $prenda->nombre_producto ?? 'Sin nombre' }}</div>
                        </td>
                        <td>
                            <div class="producto-descripcion">
                                <p style="margin: 0 0 8px 0; color: #475569; font-size: 0.95rem;">{{ $prenda->descripcion ?? '-' }}</p>
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span style="font-size: 0.85rem; font-weight: 600; color: #64748b;">Tallas:</span>
                                    <span style="font-size: 0.9rem; color: #1e293b;">
                                        @if($prenda->tallas && is_array($prenda->tallas) && count($prenda->tallas) > 0)
                                            {{ implode(', ', $prenda->tallas) }}
                                        @else
                                            -
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 1rem; justify-content: center; align-items: center;">
                                <!-- Imagen de Prenda -->
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                    <small style="font-size: 0.75rem; color: #64748b; font-weight: 600;">PRENDA</small>
                                    @if($prenda->fotos && is_array($prenda->fotos) && count($prenda->fotos) > 0)
                                        <img src="{{ $prenda->fotos[0] }}" alt="Prenda"
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer;"
                                             onclick="abrirModalImagen('{{ $prenda->fotos[0] }}', '{{ $prenda->nombre_producto ?? 'Prenda' }}')">
                                    @else
                                        <div style="width: 60px; height: 60px; background: #f1f5f9; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #cbd5e1;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @endif
                                </div>

                                <!-- Imagen de Tela -->
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                    <small style="font-size: 0.75rem; color: #64748b; font-weight: 600;">TELA</small>
                                    @if($prenda->imagen_tela)
                                        <img src="{{ $prenda->imagen_tela }}" alt="Tela"
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer;"
                                             onclick="abrirModalImagen('{{ $prenda->imagen_tela }}', 'Tela - {{ $prenda->nombre_producto ?? 'Tela' }}')">
                                    @else
                                        <div style="width: 60px; height: 60px; background: #f1f5f9; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #cbd5e1;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="sin-contenido">
            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
            Sin prendas agregadas
        </div>
    @endif

    <!-- Especificaciones de la Orden (Tabla Modal) -->
    @php
        // Procesar especificaciones GENERALES del modal
        $categoriasInfo = [
            'disponibilidad' => ['emoji' => '📦', 'label' => 'DISPONIBILIDAD'],
            'forma_pago' => ['emoji' => '💳', 'label' => 'FORMA DE PAGO'],
            'regimen' => ['emoji' => '🏛️', 'label' => 'RÉGIMEN'],
            'se_ha_vendido' => ['emoji' => '📊', 'label' => 'SE HA VENDIDO'],
            'ultima_venta' => ['emoji' => '💰', 'label' => 'ÚLTIMA VENTA'],
            'flete' => ['emoji' => '🚚', 'label' => 'FLETE DE ENVÍO']
        ];
        
        $especificacionesExisten = false;
        if($cotizacion->especificaciones && is_array($cotizacion->especificaciones)) {
            $especificacionesExisten = count($cotizacion->especificaciones) > 0;
        }
    @endphp
    
    @if($especificacionesExisten)
        <div style="margin-top: 2.5rem;">
            <div class="section-title">
                <i class="fas fa-clipboard-check"></i> Especificaciones de la Orden
            </div>
            <div style="background: white; border-radius: 8px; padding: 0; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #1e40af; border-bottom: 2px solid #1e40af;">
                            <th style="padding: 14px; text-align: left; font-weight: 700; color: white; width: 35%; border-right: 1px solid #163a8f;">CATEGORÍA</th>
                            <th style="padding: 14px; text-align: center; font-weight: 700; color: white; width: 15%; border-right: 1px solid #163a8f;">ESTADO</th>
                            <th style="padding: 14px; text-align: left; font-weight: 700; color: white; width: 50%;">OBSERVACIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categoriasInfo as $categoriaKey => $info)
                            @if(isset($cotizacion->especificaciones[$categoriaKey]) && !empty($cotizacion->especificaciones[$categoriaKey]))
                                <!-- Encabezado de categoría -->
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td colspan="3" style="font-weight: 600; background: #1e40af; padding: 12px; color: white;">
                                        <span style="font-size: 1.1rem; margin-right: 8px;">{{ $info['emoji'] }}</span>
                                        <span>{{ $info['label'] }}</span>
                                    </td>
                                </tr>
                                <!-- Valores de la categoría -->
                                @foreach($cotizacion->especificaciones[$categoriaKey] as $valor)
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td style="padding: 12px; color: #333; font-weight: 500; border-right: 1px solid #e2e8f0;">{{ $valor }}</td>
                                        <td style="padding: 12px; text-align: center; color: #1e40af; font-weight: 700; font-size: 1.2rem; border-right: 1px solid #e2e8f0;">✕</td>
                                        <td style="padding: 12px; color: #64748b; font-size: 0.9rem;">Sin observaciones</td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Logo/Bordado/Estampado -->
    @if($cotizacion->logoCotizacion)
        @php
            $logo = $cotizacion->logoCotizacion;
        @endphp

        <!-- Técnicas -->
        @if($logo->tecnicas && is_array($logo->tecnicas) && count($logo->tecnicas) > 0)
            <div class="section-title">
                <i class="fas fa-tools"></i> Técnicas
            </div>
            <div class="tecnicas-list">
                @foreach($logo->tecnicas as $tecnica)
                    <span class="tecnica-badge">{{ $tecnica }}</span>
                @endforeach
            </div>
        @endif

        <!-- Observaciones Técnicas -->
        @if($logo->observaciones_tecnicas)
            <div class="section-title">
                <i class="fas fa-wrench"></i> Observaciones Técnicas
            </div>
            <div class="observaciones-box">
                <p>{{ $logo->observaciones_tecnicas }}</p>
            </div>
        @endif

        <!-- Observaciones Generales -->
        @if($logo->observaciones_generales && is_array($logo->observaciones_generales) && count($logo->observaciones_generales) > 0)
            <div class="section-title">
                <i class="fas fa-comment"></i> Observaciones Generales
            </div>
            @foreach($logo->observaciones_generales as $obs)
                <div class="observaciones-box">
                    <p>{{ $obs }}</p>
                </div>
            @endforeach
        @endif

        <!-- Imágenes de Bordado/Estampado -->
        @if($logo->imagenes && is_array($logo->imagenes) && count($logo->imagenes) > 0)
            <div class="section-title">
                <i class="fas fa-image"></i> Imágenes de Bordado/Estampado
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                @foreach($logo->imagenes as $imagen)
                    <div style="position: relative; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); cursor: pointer; transition: transform 0.3s ease;">
                        <img src="{{ $imagen }}" alt="Bordado" 
                             style="width: 100%; height: 150px; object-fit: cover;" 
                             onclick="abrirModalImagen('{{ $imagen }}', 'Bordado/Estampado')">
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    <!-- Acciones -->
    <div class="footer-actions">
        <a href="{{ route('asesores.cotizaciones.index') }}" class="btn-custom btn-volver">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        @if($cotizacion->es_borrador)
            <a href="{{ route('asesores.cotizaciones.edit-borrador', $cotizacion->id) }}" class="btn-custom btn-editar">
                <i class="fas fa-edit"></i> Editar Borrador
            </a>
        @endif
    </div>
</div>
</div>

<!-- Modal para ver imágenes en grande -->
<div id="modalImagen" style="display: none !important; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.95); z-index: 9999; align-items: center; justify-content: center; padding: 0; margin: 0; overflow: hidden;">
    <div style="position: relative; width: calc(100vw - 160px); height: calc(100vh - 120px); display: flex; align-items: center; justify-content: center; overflow: auto;">
        <!-- Imagen con zoom -->
        <img id="imagenModal" src="" alt="Imagen" style="width: 70vw; height: 70vh; object-fit: contain; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); cursor: zoom-in; transition: transform 0.2s ease;" onwheel="zoomImagen(event)" onclick="toggleZoomClick(event)">
        
        <!-- Botones de zoom -->
        <button onclick="zoomMas()" style="position: absolute; top: 20px; left: 20px; background: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center; color: #333; box-shadow: 0 2px 10px rgba(0,0,0,0.3); font-weight: bold;">
            +
        </button>
        
        <button onclick="zoomMenos()" style="position: absolute; top: 70px; left: 20px; background: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center; color: #333; box-shadow: 0 2px 10px rgba(0,0,0,0.3); font-weight: bold;">
            −
        </button>
        
        <button onclick="resetZoom()" style="position: absolute; top: 120px; left: 20px; background: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; color: #333; box-shadow: 0 2px 10px rgba(0,0,0,0.3); font-weight: bold;">
            1:1
        </button>
        
        <!-- Botón cerrar -->
        <button onclick="cerrarModalImagen()" style="position: absolute; top: 20px; right: 20px; background: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center; color: #333; box-shadow: 0 2px 10px rgba(0,0,0,0.3);">
            ✕
        </button>
        
        <!-- Botón anterior -->
        <button id="btnAnterior" onclick="imagenAnterior()" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); background: rgba(255, 255, 255, 0.8); border: none; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center; color: #333; transition: all 0.3s; box-shadow: 0 2px 10px rgba(0,0,0,0.3);">
            ‹
        </button>
        
        <!-- Botón siguiente -->
        <button id="btnSiguiente" onclick="imagenSiguiente()" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: rgba(255, 255, 255, 0.8); border: none; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center; color: #333; transition: all 0.3s; box-shadow: 0 2px 10px rgba(0,0,0,0.3);">
            ›
        </button>
        
        <!-- Contador de imágenes -->
        <div id="contadorImagenes" style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); background: rgba(0, 0, 0, 0.6); color: white; padding: 10px 20px; border-radius: 20px; font-size: 14px; font-weight: 600;">
            <span id="imagenActual">1</span> / <span id="totalImagenes">1</span>
        </div>
    </div>
</div>

<script>
let todasLasImagenes = [];
let imagenActualIndex = 0;

function abrirModalImagen(src, titulo) {
    console.log('🔵 abrirModalImagen llamado con:', src);
    
    // Obtener todas las imágenes de la tabla
    const imagenes = document.querySelectorAll('.productos-table img[src*="/storage/cotizaciones/"]');
    console.log('📸 Imágenes encontradas:', imagenes.length);
    
    todasLasImagenes = Array.from(imagenes).map(img => img.src);
    console.log('📸 Array de imágenes:', todasLasImagenes);
    
    // Encontrar el índice de la imagen clickeada
    // Buscar por coincidencia parcial (última parte de la URL) para evitar problemas con protocolo/dominio
    imagenActualIndex = 0;
    const srcParts = src.split('/');
    const srcFileName = srcParts[srcParts.length - 1]; // Obtener nombre del archivo
    
    for (let i = 0; i < todasLasImagenes.length; i++) {
        const imgParts = todasLasImagenes[i].split('/');
        const imgFileName = imgParts[imgParts.length - 1];
        
        if (imgFileName === srcFileName) {
            imagenActualIndex = i;
            console.log('✅ Imagen encontrada en índice:', i);
            break;
        }
    }
    
    console.log('📍 Índice actual:', imagenActualIndex);
    
    mostrarImagen();
    
    const modal = document.getElementById('modalImagen');
    console.log('🎬 Modal encontrado:', modal);
    
    if (modal) {
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        console.log('✅ Modal mostrado');
    }
    
    document.body.style.overflow = 'hidden';
}

function mostrarImagen() {
    const img = document.getElementById('imagenModal');
    const contador = document.getElementById('imagenActual');
    const total = document.getElementById('totalImagenes');
    const btnAnterior = document.getElementById('btnAnterior');
    const btnSiguiente = document.getElementById('btnSiguiente');
    
    if (todasLasImagenes.length > 0) {
        img.src = todasLasImagenes[imagenActualIndex];
        contador.textContent = imagenActualIndex + 1;
        total.textContent = todasLasImagenes.length;
        
        // Mostrar/ocultar botones según corresponda
        btnAnterior.style.display = imagenActualIndex > 0 ? 'flex' : 'none';
        btnSiguiente.style.display = imagenActualIndex < todasLasImagenes.length - 1 ? 'flex' : 'none';
    }
}

function imagenAnterior() {
    if (imagenActualIndex > 0) {
        imagenActualIndex--;
        mostrarImagen();
    }
}

function imagenSiguiente() {
    if (imagenActualIndex < todasLasImagenes.length - 1) {
        imagenActualIndex++;
        mostrarImagen();
    }
}

function cerrarModalImagen() {
    const modal = document.getElementById('modalImagen');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    todasLasImagenes = [];
    imagenActualIndex = 0;
}

// Cerrar modal al hacer clic fuera de la imagen
document.getElementById('modalImagen')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalImagen();
    }
});

// Cerrar modal con tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalImagen();
    }
    // Navegar con flechas
    if (document.getElementById('modalImagen').style.display === 'flex') {
        if (e.key === 'ArrowLeft') {
            imagenAnterior();
        } else if (e.key === 'ArrowRight') {
            imagenSiguiente();
        }
    }
});

// Hover effects
document.getElementById('btnAnterior')?.addEventListener('mouseover', function() {
    this.style.background = 'rgba(255, 255, 255, 1)';
});
document.getElementById('btnAnterior')?.addEventListener('mouseout', function() {
    this.style.background = 'rgba(255, 255, 255, 0.8)';
});

document.getElementById('btnSiguiente')?.addEventListener('mouseover', function() {
    this.style.background = 'rgba(255, 255, 255, 1)';
});
document.getElementById('btnSiguiente')?.addEventListener('mouseout', function() {
    this.style.background = 'rgba(255, 255, 255, 0.8)';
});

// Variables para zoom
let zoomLevel = 1;
const maxZoom = 10;
const minZoom = 0.5;
const zoomStep = 0.1;

// Función para hacer zoom con rueda del mouse
function zoomImagen(event) {
    event.preventDefault();
    const img = document.getElementById('imagenModal');
    if (!img) return;
    
    const delta = event.deltaY > 0 ? -zoomStep : zoomStep;
    zoomLevel = Math.max(minZoom, Math.min(maxZoom, zoomLevel + delta));
    img.style.transform = `scale(${zoomLevel})`;
    img.style.cursor = zoomLevel < maxZoom ? 'zoom-in' : 'zoom-out';
}

// Función para zoom más
function zoomMas() {
    const img = document.getElementById('imagenModal');
    if (!img) return;
    zoomLevel = Math.min(maxZoom, zoomLevel + zoomStep);
    img.style.transform = `scale(${zoomLevel})`;
    img.style.cursor = zoomLevel < maxZoom ? 'zoom-in' : 'zoom-out';
}

// Función para zoom menos
function zoomMenos() {
    const img = document.getElementById('imagenModal');
    if (!img) return;
    zoomLevel = Math.max(minZoom, zoomLevel - zoomStep);
    img.style.transform = `scale(${zoomLevel})`;
    img.style.cursor = zoomLevel < maxZoom ? 'zoom-in' : 'zoom-out';
}

// Función para resetear zoom
function resetZoom() {
    const img = document.getElementById('imagenModal');
    if (!img) return;
    zoomLevel = 1;
    img.style.transform = `scale(1)`;
    img.style.cursor = 'zoom-in';
}

// Función para toggle zoom al hacer clic en la imagen
function toggleZoomClick(event) {
    const img = document.getElementById('imagenModal');
    if (!img) return;
    
    // Si está en zoom normal, hacer zoom a 2x
    if (zoomLevel === 1) {
        zoomLevel = 2;
        img.style.transform = `scale(2)`;
        img.style.cursor = 'zoom-out';
    } else {
        // Si está en zoom, volver a normal
        zoomLevel = 1;
        img.style.transform = `scale(1)`;
        img.style.cursor = 'zoom-in';
    }
}

// Ocultar navbar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    const topNav = document.querySelector('.top-nav');
    if (topNav) {
        topNav.style.display = 'none';
    }
    
    // Ocultar también la barra de navegación secundaria (page-header)
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) {
        pageHeader.style.display = 'none';
    }
});

// Mostrar navbar cuando se vuelve a la lista
window.addEventListener('beforeunload', function() {
    const topNav = document.querySelector('.top-nav');
    if (topNav) {
        topNav.style.display = '';
    }
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) {
        pageHeader.style.display = '';
    }
});

</script>

@endsection
