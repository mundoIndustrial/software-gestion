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
    }

    /* TABS STYLING */
    .tabs-container {
        display: flex;
        gap: 0;
        margin-bottom: 2rem;
        border-bottom: 2px solid #e2e8f0;
        background: white;
        border-radius: 12px 12px 0 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        overflow: hidden;
    }

    .tab-button {
        padding: 1rem 1.5rem;
        background: none;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.95rem;
        color: #64748b;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-bottom: 3px solid transparent;
        position: relative;
        bottom: -2px;
    }

    .tab-button:hover {
        background: #f8fafc;
        color: var(--primary);
    }

    .tab-button.active {
        color: white;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        border-bottom-color: var(--secondary);
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .tab-content-wrapper {
        background: white;
        border-radius: 0 0 12px 12px;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
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

    <!-- TABS NAVIGATION -->
    <div class="tabs-container">
        <button class="tab-button active" onclick="cambiarTab('prendas', this)">
            <i class="fas fa-box"></i> PRENDAS
        </button>
        <button class="tab-button" onclick="cambiarTab('bordado', this)">
            <i class="fas fa-tools"></i> BORDADO/ESTAMPADO
        </button>
    </div>

    <!-- TAB CONTENT WRAPPER -->
    <div class="tab-content-wrapper">

        <!-- TAB 1: PRENDAS -->
        <div id="tab-prendas" class="tab-content active">
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

                <!-- ESPECIFICACIONES GENERALES (dentro del tab de prendas) -->
                @php
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
                            <i class="fas fa-clipboard-check"></i> Especificaciones Generales
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
            @else
                <div class="sin-contenido">
                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    Sin prendas agregadas
                </div>
            @endif
        </div><!-- FIN TAB PRENDAS -->

        <!-- TAB 2: BORDADO/ESTAMPADO -->
        <div id="tab-bordado" class="tab-content">
            @if($logo)
                <!-- 1. IMÁGENES -->
                @if($logo->imagenes && is_array($logo->imagenes) && count($logo->imagenes) > 0)
                    <div class="section-title">
                        <i class="fas fa-images"></i> Imágenes
                    </div>
                    <div class="imagenes-bordado">
                        @foreach($logo->imagenes as $imagen)
                            <div class="imagen-bordado">
                                <img src="{{ $imagen }}" alt="Bordado" 
                                     style="width: 100%; height: 150px; object-fit: cover;" 
                                     onclick="abrirModalImagen('{{ $imagen }}', 'Bordado/Estampado')">
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="sin-contenido">
                        <i class="fas fa-images" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                        Sin imágenes de bordado/estampado
                    </div>
                @endif

                <!-- 2. TÉCNICAS -->
                @if($logo->tecnicas && is_array($logo->tecnicas) && count($logo->tecnicas) > 0)
                    <div class="section-title" style="margin-top: 2rem;">
                        <i class="fas fa-tools"></i> Técnicas Disponibles
                    </div>
                    <table class="productos-table">
                        <thead>
                            <tr>
                                <th style="width: 100%;">Técnica</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logo->tecnicas as $tecnica)
                                <tr>
                                    <td>
                                        <div class="producto-descripcion">
                                            <p style="margin: 0; color: #475569; font-size: 0.95rem;">{{ $tecnica }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <!-- 2.1 OBSERVACIONES TÉCNICAS -->
                @if($logo->observaciones_tecnicas)
                    <div class="section-title" style="margin-top: 2rem;">
                        <i class="fas fa-wrench"></i> Observaciones Técnicas
                    </div>
                    <div class="observaciones-box">
                        <p>{{ $logo->observaciones_tecnicas }}</p>
                    </div>
                @endif

                <!-- 3. UBICACIONES -->
                @if($logo->ubicaciones && is_array($logo->ubicaciones) && count($logo->ubicaciones) > 0)
                    <div class="section-title" style="margin-top: 2rem;">
                        <i class="fas fa-map-marker-alt"></i> Ubicación
                    </div>
                    <table class="productos-table">
                        <thead>
                            <tr>
                                <th style="width: 100%;">Ubicación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logo->ubicaciones as $ubicacion)
                                <tr>
                                    <td>
                                        <div class="producto-descripcion">
                                            <p style="margin: 0; color: #475569; font-size: 0.95rem;">{{ $ubicacion }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <!-- 4. OBSERVACIONES GENERALES -->
                @if($logo->observaciones_generales && is_array($logo->observaciones_generales) && count($logo->observaciones_generales) > 0)
                    <div class="section-title" style="margin-top: 2rem;">
                        <i class="fas fa-comment"></i> Observaciones Generales
                    </div>
                    <table class="productos-table">
                        <thead>
                            <tr>
                                <th style="width: 70%;">Observación</th>
                                <th style="width: 30%; text-align: center;">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logo->observaciones_generales as $obs)
                                @php
                                    // Manejar tanto formato antiguo (string) como nuevo (array)
                                    $texto = is_array($obs) ? ($obs['texto'] ?? $obs) : $obs;
                                    $tipo = is_array($obs) ? ($obs['tipo'] ?? 'texto') : 'texto';
                                    $valor = is_array($obs) ? ($obs['valor'] ?? '') : '';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="producto-descripcion">
                                            <p style="margin: 0; color: #475569; font-size: 0.95rem;">{{ $texto }}</p>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        @if($tipo === 'checkbox')
                                            <span style="color: #2e7d32; font-weight: 600; font-size: 1.2rem;">✓</span>
                                        @else
                                            <span style="background: #f5f5f5; padding: 6px 12px; border-radius: 4px; font-size: 0.9rem; color: #333; font-weight: 500;">{{ $valor ?? '-' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @else
                <div class="sin-contenido">
                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    Sin información de bordado/estampado
                </div>
            @endif
        </div><!-- FIN TAB BORDADO -->

    </div><!-- FIN TAB CONTENT WRAPPER -->

<style>
    .imagenes-bordado {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .imagen-bordado {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    
    .imagen-bordado:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }
    
    .imagen-bordado img {
        cursor: pointer;
        transition: transform 0.3s ease;
    }
    
    .imagen-bordado:hover img {
        transform: scale(1.05);
    }
</style>

<script>
// Función para cambiar tabs
function cambiarTab(tabName, button) {
    // Ocultar todos los tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Mostrar el tab seleccionado
    const selectedTab = document.getElementById('tab-' + tabName);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    // Actualizar botones activos
    const buttons = document.querySelectorAll('.tab-button');
    buttons.forEach(btn => btn.classList.remove('active'));
    button.classList.add('active');
}

// Ocultar navbar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    const topNav = document.querySelector('.top-nav');
    if (topNav) {
        topNav.style.display = 'none';
    }
    
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

function abrirModalImagen(src, titulo) {
    console.log('Abriendo imagen:', src);
    // Implementar modal si es necesario
}
</script>

@endsection
