{{-- Cotización Header --}}
<div style="
    background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%);
    color: white;
    padding: 0.8rem 2.5rem;
    margin-bottom: 1rem;
    margin-top: -2rem;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(30, 64, 175, 0.15);
    position: relative;
    overflow: hidden;
    user-select: text;
    display: flex;
    justify-content: space-between;
    align-items: center;
">
    <div style="position: absolute; top: -50%; right: -50%; width: 500px; height: 500px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; pointer-events: none;"></div>
    
    <div style="position: relative; z-index: 1;">
        <h1 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 0.5rem;">
            <i class="fas fa-file-invoice"></i> Detalle de Cotización
        </h1>
        <p style="margin: 0; opacity: 0.95; font-size: 0.95rem;">
            @if($cotizacion->numeroCotizacion)
                Cotización: {{ $cotizacion->numeroCotizacion }}
            @elseif($cotizacion->numero_cotizacion)
                Cotización #{{ $cotizacion->numero_cotizacion }}
            @else
                Cotización #{{ $cotizacion->id }}
            @endif
        </p>
    </div>

    {{-- Botón Volver --}}
    <a href="{{ route('asesores.cotizaciones.index') }}" style="
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.4);
        padding: 0.6rem 1.2rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        z-index: 2;
    " 
    onmouseover="this.style.background = 'rgba(255, 255, 255, 0.3)'; this.style.borderColor = 'white'; this.style.transform = 'translateX(-2px)';"
    onmouseout="this.style.background = 'rgba(255, 255, 255, 0.2)'; this.style.borderColor = 'rgba(255, 255, 255, 0.4)'; this.style.transform = 'translateX(0)';">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>
