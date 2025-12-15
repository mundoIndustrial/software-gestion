{{-- Cotización Info Cards --}}
<div style="
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
">
    <div style="
        background: white;
        padding: 0.8rem 1.2rem;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        border-top: 4px solid #0ea5e9;
        transition: all 0.3s ease;
    " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(0, 0, 0, 0.1)'" onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.06)'">
        <label style="font-size: 0.7rem; color: #64748b; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.75rem; display: block;">
            <i class="fas fa-user"></i> Cliente
        </label>
        <div style="font-size: 1.25rem; font-weight: 700; color: #1e293b;">
            @if($cotizacion->cliente && is_object($cotizacion->cliente))
                {{ $cotizacion->cliente->nombre ?? 'Sin nombre' }}
            @elseif($cotizacion->cliente && is_string($cotizacion->cliente))
                {{ $cotizacion->cliente }}
            @else
                -
            @endif
        </div>
    </div>

    <div style="
        background: white;
        padding: 0.8rem 1.2rem;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        border-top: 4px solid #0ea5e9;
        transition: all 0.3s ease;
    " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(0, 0, 0, 0.1)'" onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.06)'">
        <label style="font-size: 0.7rem; color: #64748b; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.75rem; display: block;">
            <i class="fas fa-tag"></i> Estado
        </label>
        <div style="font-size: 1.25rem; font-weight: 700; color: #1e293b;">
            <span style="
                display: inline-block;
                padding: 0.5rem 1rem;
                border-radius: 6px;
                font-weight: 700;
                font-size: 0.85rem;
                background: {{ $cotizacion->es_borrador ? '#fef3c7' : ($cotizacion->estado === 'ENVIADA_CONTADOR' ? '#cffafe' : ($cotizacion->estado === 'aceptada' ? '#cffafe' : ($cotizacion->estado === 'rechazada' ? '#fee2e2' : '#cffafe'))) }};
                color: {{ $cotizacion->es_borrador ? '#92400e' : ($cotizacion->estado === 'ENVIADA_CONTADOR' ? '#164e63' : ($cotizacion->estado === 'aceptada' ? '#164e63' : ($cotizacion->estado === 'rechazada' ? '#7f1d1d' : '#164e63'))) }};
            ">
                @if($cotizacion->es_borrador)
                    Borrador
                @elseif($cotizacion->estado === 'ENVIADA_CONTADOR')
                    Enviada a Contador
                @elseif($cotizacion->estado === 'aceptada')
                    Aceptada
                @elseif($cotizacion->estado === 'rechazada')
                    Rechazada
                @else
                    {{ str_replace('_', ' ', ucfirst($cotizacion->estado)) }}
                @endif
            </span>
        </div>
    </div>

    <div style="
        background: white;
        padding: 0.8rem 1.2rem;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        border-top: 4px solid #0ea5e9;
        transition: all 0.3s ease;
    " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(0, 0, 0, 0.1)'" onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.06)'">
        <label style="font-size: 0.7rem; color: #64748b; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.75rem; display: block;">
            <i class="fas fa-calendar-check"></i> Fecha Envío
        </label>
        <div style="font-size: 1rem; font-weight: 700; color: #1e293b;">{{ $cotizacion->fecha_envio ? $cotizacion->fecha_envio->format('d/m/Y') : '-' }}</div>
    </div>
</div>
