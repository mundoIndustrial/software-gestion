{{-- Tabs Navigation --}}
@php
    // Obtener IDs de tipos de cotización
    $idPrenda = \App\Models\TipoCotizacion::getIdPorCodigo('P');
    $idLogo = \App\Models\TipoCotizacion::getIdPorCodigo('L');
    $idCombinada = \App\Models\TipoCotizacion::getIdPorCodigo('PL');
    
    // Determinar qué tabs mostrar basado en el tipo_cotizacion_id
    $tipoCotizacionId = $cotizacion->tipo_cotizacion_id;
    $esPrenda = $tipoCotizacionId === $idPrenda;
    $esLogo = $tipoCotizacionId === $idLogo;
    $esCombinada = $tipoCotizacionId === $idCombinada;
    $tienePrendas = $cotizacion->prendas && count($cotizacion->prendas) > 0;

    //  MOSTRAR TAB DE LOGO SI:
    // 1. Es tipo logo O combinada
    // 2. Y tiene registros en la tabla logo_cotizacion_tecnica_prendas
    $mostrarTabLogo = false;
    if (($esLogo || $esCombinada) && $logo) {
        $tieneRegistrosTecnicas = \App\Models\LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logo->id)->exists();
        if ($tieneRegistrosTecnicas) {
            $mostrarTabLogo = true;
        }
    }
    
    // Determinar qué tab debe estar activo por defecto
    // Logo puro (L): tab de Logo
    // Combinada (PL): tab de Prendas primero
    // Prenda pura (P): tab de Prendas
    $tabActivoPorDefecto = 'prendas';
    if ($tipoCotizacionId === $idLogo) {
        $tabActivoPorDefecto = 'bordado'; // Logo
    } else if ($tipoCotizacionId === $idCombinada) {
        $tabActivoPorDefecto = 'prendas'; // Combinada empieza en prendas
    }
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
        <button class="tab-button {{ $tabActivoPorDefecto === 'prendas' ? 'active' : '' }}" onclick="cambiarTab('prendas', this)" style="
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
    
    @if($mostrarTabLogo)
        <button class="tab-button {{ $tabActivoPorDefecto === 'bordado' ? 'active' : '' }}" onclick="cambiarTab('bordado', this)" style="
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
</div>