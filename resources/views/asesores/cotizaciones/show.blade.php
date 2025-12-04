@extends('layouts.asesores')

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
    
    body, p, h1, h2, h3, h4, h5, h6, span, div, a {
        user-select: text !important;
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        -ms-user-select: text !important;
    }

    .page-wrapper {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        min-height: 100vh;
        padding: 2rem 0;
        transform: scale(0.75);
        transform-origin: top center;
        width: 133.33%;
        margin-left: -16.665%;
        user-select: auto;
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
        padding: 0.8rem 2.5rem;
        margin-bottom: 1rem;
        margin-top: -2rem;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(30, 64, 175, 0.15);
        position: relative;
        overflow: hidden;
        user-select: text;
        -webkit-user-select: text;
        -moz-user-select: text;
        -ms-user-select: text;
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
        font-size: 1.4rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        position: relative;
        z-index: 1;
        user-select: text;
    }

    .cotizacion-header p {
        font-size: 0.95rem;
        opacity: 0.95;
        position: relative;
        z-index: 1;
        user-select: text;
    }

    .cotizacion-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .info-card {
        background: white;
        padding: 0.8rem 1.2rem;
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
        <p style="margin: 0; opacity: 0.9;">
            @if($cotizacion->numero_cotizacion)
                Cotización: {{ $cotizacion->numero_cotizacion }}
            @else
                Cotización #{{ $cotizacion->id }} (Borrador)
            @endif
        </p>
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
            <div class="value" style="font-size: 1rem;">{{ $cotizacion->fecha_inicio ? $cotizacion->fecha_inicio->format('d/m/Y h:i A') : '-' }}</div>
        </div>
        @if($cotizacion->fecha_envio)
            <div class="info-card">
                <label><i class="fas fa-calendar-check"></i> Fecha Envío</label>
                <div class="value" style="font-size: 1rem;">{{ $cotizacion->fecha_envio->format('d/m/Y h:i A') }}</div>
            </div>
        @endif
    </div>

    <!-- TABS NAVIGATION -->
    <div class="tabs-container">
        @php
            // Determinar si es una cotización de logo o prendas
            $tipoNombre = $cotizacion->tipoCotizacion ? strtolower($cotizacion->tipoCotizacion->nombre) : '';
            $esLogo = strpos($tipoNombre, 'logo') !== false;
            $tienePrendas = $cotizacion->prendasCotizaciones && count($cotizacion->prendasCotizaciones) > 0;
            \Log::info('DEBUG TABS:', ['tipoNombre' => $tipoNombre, 'esLogo' => $esLogo, 'tienePrendas' => $tienePrendas]);
        @endphp
        
        @if(!$esLogo || $tienePrendas)
            <button class="tab-button {{ $esLogo && !$tienePrendas ? '' : 'active' }}" onclick="cambiarTab('prendas', this)">
                <i class="fas fa-box"></i> PRENDAS
            </button>
        @endif
        
        @if($logo)
            <button class="tab-button {{ $esLogo ? 'active' : '' }}" onclick="cambiarTab('bordado', this)">
                <i class="fas fa-tools"></i> {{ $esLogo ? 'LOGO' : 'LOGO' }}
            </button>
        @endif
    </div>

    <!-- TAB CONTENT WRAPPER -->
    <div class="tab-content-wrapper">

        <!-- TAB 1: PRENDAS -->
        <div id="tab-prendas" class="tab-content {{ (!$esLogo || $tienePrendas) && !$esLogo ? 'active' : '' }}">
            @if($cotizacion->prendasCotizaciones && count($cotizacion->prendasCotizaciones) > 0)
                <table class="productos-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Prenda</th>
                            <th style="width: 35%;">Descripción & Tallas</th>
                            <th style="width: 25%;">Color, Tela & Variaciones</th>
                            <th style="width: 25%; text-align: center;">Imagen Prenda & Tela</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cotizacion->prendasCotizaciones as $index => $prenda)
                            @php
                                $variante = $prenda->variantes->first();
                            @endphp
                            <tr>
                                <td>
                                    <div class="producto-nombre">{{ $prenda->nombre_producto ?? 'Sin nombre' }}</div>
                                </td>
                                <td>
                                    <div class="producto-descripcion">
                                        <p style="margin: 0 0 8px 0; color: #475569; font-size: 0.95rem;">{{ $prenda->descripcion ?? '-' }}</p>
                                        @if($prenda->genero)
                                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                                                <span style="font-size: 0.85rem; font-weight: 600; color: #64748b;">Género:</span>
                                                <span style="font-size: 0.9rem; color: #1e293b; background: #f0f4f8; padding: 2px 8px; border-radius: 4px; text-transform: uppercase;">{{ $prenda->genero }}</span>
                                            </div>
                                        @endif
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
                                <td>
                                    @if($variante)
                                        <div style="font-size: 0.9rem;">
                                            <!-- Color -->
                                            <div style="margin-bottom: 8px;">
                                                <span style="font-weight: 600; color: #0066cc;">Color:</span>
                                                <span style="color: #1e293b;">{{ $variante->color ? $variante->color->nombre : '-' }}</span>
                                            </div>
                                            <!-- Tela -->
                                            <div style="margin-bottom: 8px;">
                                                <span style="font-weight: 600; color: #0066cc;">Tela:</span>
                                                <span style="color: #1e293b;">{{ $variante->tela ? $variante->tela->nombre : '-' }}</span>
                                                @if($variante->tela && $variante->tela->referencia)
                                                    <div style="color: #64748b; font-size: 0.8rem; margin-top: 2px;">Ref: {{ $variante->tela->referencia }}</div>
                                                @endif
                                            </div>
                                            <!-- Manga -->
                                            <div style="margin-bottom: 8px;">
                                                <span style="font-weight: 600; color: #0066cc;">Manga:</span>
                                                <span style="color: #1e293b;">{{ $variante->tipoManga ? $variante->tipoManga->nombre : '-' }}</span>
                                                @php
                                                    $obsArray = $variante->descripcion_adicional ? explode(' | ', $variante->descripcion_adicional) : [];
                                                    $obsManga = null;
                                                    foreach ($obsArray as $obs) {
                                                        if (strpos($obs, 'Manga:') === 0) {
                                                            $obsManga = trim(str_replace('Manga:', '', $obs));
                                                        }
                                                    }
                                                @endphp
                                                @if($obsManga)
                                                    <div style="color: #64748b; font-size: 0.8rem; margin-top: 2px;">{{ $obsManga }}</div>
                                                @endif
                                            </div>
                                            <!-- Bolsillos -->
                                            <div style="margin-bottom: 8px;">
                                                <span style="font-weight: 600; color: #0066cc;">Bolsillos:</span>
                                                @if($variante->tiene_bolsillos)
                                                    <span style="background: #10b981; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; font-weight: 600;">Sí</span>
                                                @else
                                                    <span style="background: #cbd5e1; color: #475569; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; font-weight: 600;">No</span>
                                                @endif
                                                @php
                                                    $obsArray = $variante->descripcion_adicional ? explode(' | ', $variante->descripcion_adicional) : [];
                                                    $obsBolsillos = null;
                                                    foreach ($obsArray as $obs) {
                                                        if (strpos($obs, 'Bolsillos:') === 0) {
                                                            $obsBolsillos = trim(str_replace('Bolsillos:', '', $obs));
                                                        }
                                                    }
                                                @endphp
                                                @if($obsBolsillos)
                                                    <div style="color: #64748b; font-size: 0.8rem; margin-top: 2px;">{{ $obsBolsillos }}</div>
                                                @endif
                                            </div>
                                            <!-- Broche -->
                                            @if($variante->tipoBroche)
                                                <div style="margin-bottom: 8px;">
                                                    <span style="font-weight: 600; color: #0066cc;">Broche:</span>
                                                    <span style="color: #1e293b;">{{ $variante->tipoBroche->nombre }}</span>
                                                    @php
                                                        $obsBroche = null;
                                                        foreach ($obsArray as $obs) {
                                                            if (strpos($obs, 'Broche:') === 0) {
                                                                $obsBroche = trim(str_replace('Broche:', '', $obs));
                                                            }
                                                        }
                                                    @endphp
                                                    @if($obsBroche)
                                                        <div style="color: #64748b; font-size: 0.8rem; margin-top: 2px;">{{ $obsBroche }}</div>
                                                    @endif
                                                </div>
                                            @endif
                                            <!-- Reflectivo -->
                                            <div style="margin-bottom: 8px;">
                                                <span style="font-weight: 600; color: #0066cc;">Reflectivo:</span>
                                                @if($variante->tiene_reflectivo)
                                                    <span style="background: #10b981; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; font-weight: 600;">Sí</span>
                                                @else
                                                    <span style="background: #cbd5e1; color: #475569; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; font-weight: 600;">No</span>
                                                @endif
                                                @php
                                                    $obsReflectivo = null;
                                                    foreach ($obsArray as $obs) {
                                                        if (strpos($obs, 'Reflectivo:') === 0) {
                                                            $obsReflectivo = trim(str_replace('Reflectivo:', '', $obs));
                                                        }
                                                    }
                                                @endphp
                                                @if($obsReflectivo)
                                                    <div style="color: #64748b; font-size: 0.8rem; margin-top: 2px;">{{ $obsReflectivo }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span style="color: #cbd5e1;">Sin variaciones</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: flex; gap: 1rem; justify-content: center; align-items: center;">
                                        <!-- Imagen de Prenda -->
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                            <small style="font-size: 0.75rem; color: #64748b; font-weight: 600;">PRENDA ({{ $prenda->fotos && is_array($prenda->fotos) ? count($prenda->fotos) : 0 }})</small>
                                            @if($prenda->fotos && is_array($prenda->fotos) && count($prenda->fotos) > 0)
                                                <div style="display: flex; gap: 0.3rem; flex-wrap: wrap; justify-content: center;">
                                                    @foreach($prenda->fotos as $index => $foto)
                                                        <img src="{{ asset($foto) }}" alt="Prenda {{ $index + 1 }}"
                                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid #e2e8f0;"
                                                             onclick="abrirModalImagen('{{ asset($foto) }}', '{{ $prenda->nombre_producto ?? 'Prenda' }} - Foto {{ $index + 1 }}', {{ json_encode($prenda->fotos) }}, {{ $index }})">
                                                    @endforeach
                                                </div>
                                            @else
                                                <div style="width: 60px; height: 60px; background: #f1f5f9; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #cbd5e1;">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Imagen de Tela -->
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                            <small style="font-size: 0.75rem; color: #64748b; font-weight: 600;">TELA ({{ $prenda->telas && is_array($prenda->telas) ? count($prenda->telas) : 0 }})</small>
                                            @if($prenda->telas && is_array($prenda->telas) && count($prenda->telas) > 0)
                                                <div style="display: flex; gap: 0.3rem; flex-wrap: wrap; justify-content: center;">
                                                    @foreach($prenda->telas as $index => $tela)
                                                        <img src="{{ asset($tela) }}" alt="Tela {{ $index + 1 }}"
                                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid #e2e8f0;"
                                                             onclick="abrirModalImagen('{{ asset($tela) }}', '{{ $prenda->nombre_producto ?? 'Tela' }} - Tela {{ $index + 1 }}', {{ json_encode($prenda->telas) }}, {{ $index }})">
                                                    @endforeach
                                                </div>
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
                    
                    // Convertir especificaciones a array si es string JSON
                    $especificacionesData = $cotizacion->especificaciones;
                    if (is_string($especificacionesData)) {
                        $especificacionesData = json_decode($especificacionesData, true) ?? [];
                    } elseif (!is_array($especificacionesData)) {
                        $especificacionesData = [];
                    }
                    
                    $especificacionesExisten = false;
                    if($especificacionesData && is_array($especificacionesData)) {
                        $especificacionesExisten = count($especificacionesData) > 0;
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
                                        @if(isset($especificacionesData[$categoriaKey]) && !empty($especificacionesData[$categoriaKey]))
                                            <!-- Encabezado de categoría -->
                                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                                <td colspan="3" style="font-weight: 600; background: #1e40af; padding: 12px; color: white;">
                                                    <span style="font-size: 1.1rem; margin-right: 8px;">{{ $info['emoji'] }}</span>
                                                    <span>{{ $info['label'] }}</span>
                                                </td>
                                            </tr>
                                            <!-- Valores de la categoría -->
                                            @foreach($especificacionesData[$categoriaKey] as $valor)
                                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                                    <td style="padding: 12px; color: #333; font-weight: 500; border-right: 1px solid #e2e8f0;">
                                                        @if(is_array($valor))
                                                            {{ implode(', ', $valor) ?? '-' }}
                                                        @else
                                                            {{ $valor ?? '-' }}
                                                        @endif
                                                    </td>
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

        <!-- TAB 2: LOGO / LOGO -->
        <div id="tab-bordado" class="tab-content {{ $esLogo ? 'active' : '' }}">
            @if($logo)
                <!-- 1. IMÁGENES -->
                @if($logo->imagenes && is_array($logo->imagenes) && count($logo->imagenes) > 0)
                    <div class="section-title">
                        <i class="fas fa-images"></i> Imágenes
                    </div>
                    <div class="imagenes-bordado">
                        @foreach($logo->imagenes as $imagen)
                            <div class="imagen-bordado">
                                <img src="{{ asset($imagen) }}" alt="{{ $esLogo ? 'Logo' : 'Bordado' }}" 
                                     style="width: 100%; height: 150px; object-fit: cover;" 
                                     onclick="abrirModalImagen('{{ asset($imagen) }}', '{{ $esLogo ? 'Logo' : 'LOGO' }}')">
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="sin-contenido">
                        <i class="fas fa-images" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                        {{ $esLogo ? 'Sin imágenes de logo' : 'Sin imágenes de LOGO' }}
                    </div>
                @endif

                <!-- 2. TÉCNICAS Y OBSERVACIONES TÉCNICAS -->
                @if($logo->tecnicas && is_array($logo->tecnicas) && count($logo->tecnicas) > 0)
                    <div class="section-title" style="margin-top: 2rem;">
                        <i class="fas fa-tools"></i> Técnicas Disponibles
                    </div>
                    <table class="productos-table">
                        <thead>
                            <tr>
                                <th style="width: 70%;">Técnica</th>
                                <th style="width: 30%;">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logo->tecnicas as $index => $tecnica)
                                <tr>
                                    <td>
                                        <div class="producto-descripcion">
                                            <p style="margin: 0; color: #475569; font-size: 0.95rem;">
                                                @if(is_array($tecnica))
                                                    {{ implode(', ', $tecnica) ?? '-' }}
                                                @else
                                                    {{ $tecnica ?? '-' }}
                                                @endif
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        @if($logo->observaciones_tecnicas && $index === 0)
                                            <div class="producto-descripcion">
                                                <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                                    @if(is_array($logo->observaciones_tecnicas))
                                                        {{ implode(', ', $logo->observaciones_tecnicas) ?? '-' }}
                                                    @else
                                                        {{ $logo->observaciones_tecnicas ?? '-' }}
                                                    @endif
                                                </p>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @elseif($logo->observaciones_tecnicas)
                    <div class="section-title" style="margin-top: 2rem;">
                        <i class="fas fa-wrench"></i> Observaciones Técnicas
                    </div>
                    <div class="observaciones-box">
                        <p>
                            @if(is_array($logo->observaciones_tecnicas))
                                {{ implode(', ', $logo->observaciones_tecnicas) ?? '-' }}
                            @else
                                {{ $logo->observaciones_tecnicas ?? '-' }}
                            @endif
                        </p>
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
                                <th style="width: 30%;">Sección</th>
                                <th style="width: 40%;">Ubicaciones Seleccionadas</th>
                                <th style="width: 30%;">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logo->ubicaciones as $item)
                                @php
                                    // Manejar diferentes formatos de ubicaciones
                                    if (is_array($item)) {
                                        // Nuevo formato con estructura definida
                                        if (isset($item['ubicacion']) && isset($item['opciones'])) {
                                            // Formato: {ubicacion: "...", opciones: [...], observaciones: "..."}
                                            $seccion = $item['ubicacion'] ?? 'GENERAL';
                                            $ubicacionesSeleccionadas = $item['opciones'] ?? [];
                                            $observaciones = $item['observaciones'] ?? '';
                                        } elseif (isset($item['ubicaciones_seleccionadas'])) {
                                            // Formato antiguo: {seccion: "...", ubicaciones_seleccionadas: [...]}
                                            $seccion = $item['seccion'] ?? 'GENERAL';
                                            $ubicacionesSeleccionadas = $item['ubicaciones_seleccionadas'] ?? [];
                                            $observaciones = $item['observaciones'] ?? '';
                                        } else {
                                            // Fallback: es un array pero no tiene estructura conocida
                                            $seccion = 'GENERAL';
                                            $ubicacionesSeleccionadas = is_array($item) ? array_values($item) : [$item];
                                            $observaciones = '';
                                        }
                                    } else {
                                        // Formato antiguo: solo string
                                        $seccion = 'GENERAL';
                                        $ubicacionesSeleccionadas = [$item];
                                        $observaciones = '';
                                    }
                                @endphp
                                <tr>
                                    <td style="font-weight: 600; color: #1e40af; vertical-align: top;">
                                        <i class="fas fa-folder"></i> {{ $seccion }}
                                    </td>
                                    <td style="vertical-align: top;">
                                        <div class="producto-descripcion">
                                            @foreach($ubicacionesSeleccionadas as $ubicacion)
                                                <p style="margin: 4px 0; color: #475569; font-size: 0.95rem;">
                                                    • 
                                                    @if(is_array($ubicacion))
                                                        {{ implode(', ', $ubicacion) ?? '-' }}
                                                    @else
                                                        {{ $ubicacion ?? '-' }}
                                                    @endif
                                                </p>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td style="vertical-align: top;">
                                        @if($observaciones)
                                            <div class="producto-descripcion">
                                                <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                                    @if(is_array($observaciones))
                                                        {{ implode(', ', $observaciones) ?? '-' }}
                                                    @else
                                                        {{ $observaciones ?? '-' }}
                                                    @endif
                                                </p>
                                            </div>
                                        @else
                                            <p style="margin: 0; color: #999; font-size: 0.9rem; font-style: italic;">-</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <!-- 4. OBSERVACIONES GENERALES -->
                @php
                    \Log::info('🔍 DEBUG OBSERVACIONES:', [
                        'logo_existe' => $logo ? 'sí' : 'no',
                        'obs_generales' => $logo ? $logo->observaciones_generales : null,
                        'es_array' => $logo && is_array($logo->observaciones_generales) ? 'sí' : 'no',
                        'count' => $logo && is_array($logo->observaciones_generales) ? count($logo->observaciones_generales) : 0
                    ]);
                @endphp
                @if($logo && $logo->observaciones_generales && is_array($logo->observaciones_generales) && count($logo->observaciones_generales) > 0)
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
                                    
                                    // Asegurar que texto y valor no sean arrays
                                    if (is_array($texto)) {
                                        $texto = implode(', ', $texto);
                                    }
                                    if (is_array($valor)) {
                                        $valor = implode(', ', $valor);
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <div class="producto-descripcion">
                                            <p style="margin: 0; color: #475569; font-size: 0.95rem;">{{ $texto ?? '-' }}</p>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        @if($tipo === 'checkbox')
                                            <span style="color: #2e7d32; font-weight: 600; font-size: 1.5rem; display: inline-block;">✓</span>
                                        @elseif(!empty($valor))
                                            <span style="background: #f5f5f5; padding: 8px 14px; border-radius: 4px; font-size: 0.9rem; color: #333; font-weight: 600; display: inline-block;">{{ $valor }}</span>
                                        @else
                                            <span style="color: #999; font-size: 0.9rem; font-style: italic;">-</span>
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
                    {{ $esLogo ? 'Sin información de logo' : 'Sin información de LOGO' }}
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

function abrirModalImagen(src, titulo, imagenes = null, indiceActual = 0) {
    console.log('Abriendo imagen:', src);
    console.log('Imágenes disponibles:', imagenes, 'Índice:', indiceActual);
    
    // Guardar imágenes en window para navegación
    if (imagenes && Array.isArray(imagenes)) {
        window.imagenesModal = imagenes;
        window.indiceImagenModal = indiceActual;
    } else {
        window.imagenesModal = [src];
        window.indiceImagenModal = 0;
    }
    
    // Crear modal si no existe
    let modal = document.getElementById('modalImagen');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'modalImagen';
        modal.style.cssText = `
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            animation: fadeIn 0.3s ease-in;
            overflow: hidden;
        `;
        
        const ahora = new Date();
        const fecha = ahora.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
        const hora = ahora.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        
        modal.innerHTML = `
            <div style="position: relative; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                <!-- Botón cerrar -->
                <button onclick="cerrarModalImagen()" style="
                    position: absolute;
                    top: 20px;
                    right: 40px;
                    background: white;
                    border: none;
                    font-size: 28px;
                    cursor: pointer;
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    transition: all 0.3s;
                " onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='white'">
                    ✕
                </button>
                
                <!-- Controles de zoom -->
                <div style="
                    position: absolute;
                    top: 20px;
                    left: 40px;
                    background: rgba(0, 0, 0, 0.7);
                    color: white;
                    padding: 12px 16px;
                    border-radius: 6px;
                    font-size: 13px;
                    font-weight: 600;
                    z-index: 10000;
                    display: flex;
                    gap: 12px;
                    align-items: center;
                ">
                    <button onclick="zoomOut()" style="
                        background: white;
                        color: #333;
                        border: none;
                        width: 28px;
                        height: 28px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-weight: bold;
                        transition: all 0.2s;
                    " onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='white'">−</button>
                    <span id="zoomLevel" style="min-width: 40px; text-align: center;">100%</span>
                    <button onclick="zoomIn()" style="
                        background: white;
                        color: #333;
                        border: none;
                        width: 28px;
                        height: 28px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-weight: bold;
                        transition: all 0.2s;
                    " onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='white'">+</button>
                </div>
                
                <!-- Contenedor de imagen con drag -->
                <div id="imagenContenedor" style="
                    position: relative;
                    width: 600px;
                    height: 400px;
                    overflow: hidden;
                    border-radius: 8px;
                    background: transparent;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">
                    <img id="imagenModal" src="" alt="Imagen ampliada" style="
                        width: 600px;
                        height: 400px;
                        object-fit: contain;
                        border-radius: 8px;
                        box-shadow: 0 0 30px rgba(255, 255, 255, 0.2);
                        cursor: grab;
                        transition: transform 0.1s ease-out;
                        position: relative;
                    ">
                </div>
                
                <!-- Botones de navegación -->
                <button onclick="imagenAnterior()" style="
                    position: absolute;
                    left: 20px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: rgba(255, 255, 255, 0.9);
                    border: none;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    cursor: pointer;
                    font-size: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10001;
                    transition: all 0.3s;
                " onmouseover="this.style.background='white'" onmouseout="this.style.background='rgba(255, 255, 255, 0.9)'">
                    ◀
                </button>
                
                <button onclick="imagenSiguiente()" style="
                    position: absolute;
                    right: 20px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: rgba(255, 255, 255, 0.9);
                    border: none;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    cursor: pointer;
                    font-size: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10001;
                    transition: all 0.3s;
                " onmouseover="this.style.background='white'" onmouseout="this.style.background='rgba(255, 255, 255, 0.9)'">
                    ▶
                </button>
                
                <!-- Información -->
                <div style="
                    position: absolute;
                    bottom: 30px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: rgba(0, 0, 0, 0.7);
                    color: white;
                    padding: 12px 24px;
                    border-radius: 6px;
                    font-size: 13px;
                    font-weight: 600;
                    max-width: 80%;
                    text-align: center;
                    z-index: 10000;
                ">
                    <div id="tituloModal" style="margin-bottom: 6px;"></div>
                    <div style="font-size: 11px; opacity: 0.8;">
                        📅 ${fecha} | 🕐 ${hora}
                    </div>
                </div>
            </div>
            
            <style>
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
            </style>
        `;
        
        document.body.appendChild(modal);
        
        // Cerrar al hacer click fuera de la imagen
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                cerrarModalImagen();
            }
        });
        
        // Cerrar con tecla ESC y navegar con flechas
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalImagen();
            } else if (e.key === 'ArrowLeft') {
                imagenAnterior();
            } else if (e.key === 'ArrowRight') {
                imagenSiguiente();
            }
        });
        
        // Zoom con rueda del mouse
        const imagenContenedor = document.getElementById('imagenContenedor');
        imagenContenedor.addEventListener('wheel', function(e) {
            e.preventDefault();
            if (e.deltaY < 0) {
                zoomIn();
            } else {
                zoomOut();
            }
        });
        
        // Drag and drop para mover imagen
        window.isDragging = false;
        window.startX = 0;
        window.startY = 0;
        window.offsetX = 0;
        window.offsetY = 0;
        
        const imagenModal = document.getElementById('imagenModal');
        
        imagenContenedor.addEventListener('mousedown', function(e) {
            window.isDragging = true;
            window.startX = e.clientX;
            window.startY = e.clientY;
            imagenModal.style.cursor = 'grabbing';
            e.preventDefault();
        });
        
        document.addEventListener('mousemove', function(e) {
            if (!window.isDragging) return;
            
            const deltaX = e.clientX - window.startX;
            const deltaY = e.clientY - window.startY;
            
            window.offsetX += deltaX;
            window.offsetY += deltaY;
            
            window.startX = e.clientX;
            window.startY = e.clientY;
            
            const scale = window.zoomActual / 100;
            imagenModal.style.transform = `scale(${scale}) translate(${window.offsetX}px, ${window.offsetY}px)`;
        });
        
        document.addEventListener('mouseup', function() {
            window.isDragging = false;
            imagenModal.style.cursor = 'grab';
        });
    }
    
    // Mostrar imagen
    document.getElementById('imagenModal').src = src;
    document.getElementById('tituloModal').textContent = titulo;
    
    // Resetear zoom y posición
    window.zoomActual = 100;
    window.offsetX = 0;
    window.offsetY = 0;
    document.getElementById('zoomLevel').textContent = '100%';
    document.getElementById('imagenModal').style.transform = 'scale(1)';
    
    modal.style.display = 'flex';
}

function zoomIn() {
    if (!window.zoomActual) window.zoomActual = 100;
    window.zoomActual = Math.min(window.zoomActual + 10, 300);
    document.getElementById('zoomLevel').textContent = window.zoomActual + '%';
    
    // Obtener offset actual
    const imagenModal = document.getElementById('imagenModal');
    const transform = imagenModal.style.transform;
    const translateMatch = transform.match(/translate\(([^,]+)px,\s*([^)]+)px\)/);
    const offsetX = translateMatch ? parseFloat(translateMatch[1]) : 0;
    const offsetY = translateMatch ? parseFloat(translateMatch[2]) : 0;
    
    imagenModal.style.transform = `scale(${window.zoomActual / 100}) translate(${offsetX}px, ${offsetY}px)`;
}

function zoomOut() {
    if (!window.zoomActual) window.zoomActual = 100;
    window.zoomActual = Math.max(window.zoomActual - 10, 50);
    document.getElementById('zoomLevel').textContent = window.zoomActual + '%';
    
    // Obtener offset actual
    const imagenModal = document.getElementById('imagenModal');
    const transform = imagenModal.style.transform;
    const translateMatch = transform.match(/translate\(([^,]+)px,\s*([^)]+)px\)/);
    const offsetX = translateMatch ? parseFloat(translateMatch[1]) : 0;
    const offsetY = translateMatch ? parseFloat(translateMatch[2]) : 0;
    
    imagenModal.style.transform = `scale(${window.zoomActual / 100}) translate(${offsetX}px, ${offsetY}px)`;
}

function imagenAnterior() {
    if (!window.imagenesModal || window.imagenesModal.length <= 1) return;
    
    window.indiceImagenModal = (window.indiceImagenModal - 1 + window.imagenesModal.length) % window.imagenesModal.length;
    const imagen = window.imagenesModal[window.indiceImagenModal];
    
    document.getElementById('imagenModal').src = imagen;
    
    // Resetear zoom y posición
    window.zoomActual = 100;
    window.offsetX = 0;
    window.offsetY = 0;
    document.getElementById('zoomLevel').textContent = '100%';
    document.getElementById('imagenModal').style.transform = 'scale(1)';
}

function imagenSiguiente() {
    if (!window.imagenesModal || window.imagenesModal.length <= 1) return;
    
    window.indiceImagenModal = (window.indiceImagenModal + 1) % window.imagenesModal.length;
    const imagen = window.imagenesModal[window.indiceImagenModal];
    
    document.getElementById('imagenModal').src = imagen;
    
    // Resetear zoom y posición
    window.zoomActual = 100;
    window.offsetX = 0;
    window.offsetY = 0;
    document.getElementById('zoomLevel').textContent = '100%';
    document.getElementById('imagenModal').style.transform = 'scale(1)';
}

function cerrarModalImagen() {
    const modal = document.getElementById('modalImagen');
    if (modal) {
        modal.style.display = 'none';
        window.zoomActual = 100;
    }
}
</script>

@endsection
