{{-- Tabs Navigation --}}
@php
    // Determinar qué tabs mostrar basado en el tipo de cotización
    // Usar tipoCotizacion->codigo si existe, si no usar tipo
    $tipo = ($cotizacion->tipoCotizacion && $cotizacion->tipoCotizacion->codigo) ? $cotizacion->tipoCotizacion->codigo : ($cotizacion->tipo ?? 'P');
    $esPrenda = $tipo === 'P' || $tipo === 'PL';
    $esLogo = $tipo === 'L' || $tipo === 'PL';
    $esReflectivo = $tipo === 'RF';
    $tienePrendas = $cotizacion->prendas && count($cotizacion->prendas) > 0;
    $tieneReflectivoCotizacion = $cotizacion->reflectivoCotizacion !== null;
    
    // Verificar si alguna prenda tiene reflectivo
    $tieneReflectivoPrenda = false;
    if ($cotizacion->prendas) {
        $tieneReflectivoPrenda = $cotizacion->prendas->contains(fn($prenda) => $prenda->tiene_reflectivo ?? false);
    }
    
    $tieneReflectivo = $tieneReflectivoCotizacion || $tieneReflectivoPrenda;
@endphp

<div style="
    display: flex;
    gap: 0;
    margin-bottom: 0;
    border-bottom: 2px solid #e2e8f0;
    background: white;
    border-radius: 12px 12px 0 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    width: 100%;
">
    @if($esPrenda && $tienePrendas)
        <button class="tab-button {{ !$esLogo ? 'active' : '' }}" onclick="cambiarTab('prendas', this)" style="
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
    
    @if($esLogo)
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
            <i class="fas fa-tools"></i> LOGO
        </button>
    @endif

    {{-- Tab Reflectivo (solo si tiene reflectivo en una prenda o en la cotización general) --}}
    @if($tieneReflectivo && !$esReflectivo)
        <button class="tab-button {{ $esReflectivo ? 'active' : '' }}" onclick="cambiarTab('reflectivo', this)" style="
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
            <i class="fas fa-lightbulb"></i> REFLECTIVO
        </button>
    @endif</div>