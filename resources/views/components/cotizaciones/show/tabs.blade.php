{{-- Tabs Navigation --}}
@php
    $tipoNombre = $cotizacion->tipoCotizacion ? strtolower($cotizacion->tipoCotizacion->nombre) : '';
    $esLogo = strpos($tipoNombre, 'logo') !== false;
    $tienePrendas = $cotizacion->prendasCotizaciones && count($cotizacion->prendasCotizaciones) > 0;
@endphp

<div style="
    display: flex;
    gap: 0;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e2e8f0;
    background: white;
    border-radius: 12px 12px 0 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    overflow: hidden;
">
    @if(!$esLogo || $tienePrendas)
        <button class="tab-button {{ $esLogo && !$tienePrendas ? '' : 'active' }}" onclick="cambiarTab('prendas', this)" style="
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
        ">
            <i class="fas fa-box"></i> PRENDAS
        </button>
    @endif
    
    @if($logo)
        <button class="tab-button {{ $esLogo ? 'active' : '' }}" onclick="cambiarTab('bordado', this)" style="
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
        ">
            <i class="fas fa-tools"></i> {{ $esLogo ? 'LOGO' : 'LOGO' }}
        </button>
    @endif
</div>
