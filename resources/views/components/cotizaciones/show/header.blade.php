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
">
    <div style="position: absolute; top: -50%; right: -50%; width: 500px; height: 500px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; pointer-events: none;"></div>
    
    <h1 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 0.5rem; position: relative; z-index: 1;">
        <i class="fas fa-file-invoice"></i> Detalle de Cotización
    </h1>
    <p style="margin: 0; opacity: 0.95; position: relative; z-index: 1; font-size: 0.95rem;">
        @if($cotizacion->numeroCotizacion)
            Cotización: {{ $cotizacion->numeroCotizacion }}
        @elseif($cotizacion->numero_cotizacion)
            Cotización #{{ $cotizacion->numero_cotizacion }}
        @else
            Cotización #{{ $cotizacion->id }}
        @endif
    </p>
</div>
