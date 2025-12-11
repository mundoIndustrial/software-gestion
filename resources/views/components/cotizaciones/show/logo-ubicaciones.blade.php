{{-- Logo Ubicaciones --}}
@php
    $ubicaciones = $logo->ubicaciones;
    if (is_string($ubicaciones)) {
        $ubicaciones = json_decode($ubicaciones, true) ?? [];
    }
    $ubicaciones = is_array($ubicaciones) ? $ubicaciones : [];
@endphp
@if(!empty($ubicaciones) && count($ubicaciones) > 0)
    <div style="
        font-size: 1.4rem;
        font-weight: 800;
        color: #1e293b;
        margin-top: 2rem;
        margin-bottom: 1.75rem;
        padding-bottom: 1rem;
        border-bottom: 3px solid #0ea5e9;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    ">
        <i class="fas fa-map-marker-alt" style="color: #0ea5e9; font-size: 1.4rem;"></i> Ubicación
    </div>
    <table style="
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2.5rem;
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    ">
        <thead style="background: #1e40af; color: white;">
            <tr>
                <th style="width: 30%; padding: 1rem; text-align: left; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0ea5e9;">Sección</th>
                <th style="width: 40%; padding: 1rem; text-align: left; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0ea5e9;">Ubicaciones Seleccionadas</th>
                <th style="width: 30%; padding: 1rem; text-align: left; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0ea5e9;">Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ubicaciones as $item)
                @php
                    if (is_array($item)) {
                        if (isset($item['ubicacion']) && isset($item['opciones'])) {
                            $seccion = $item['ubicacion'] ?? 'GENERAL';
                            $ubicacionesSeleccionadas = $item['opciones'] ?? [];
                            $observaciones = $item['observaciones'] ?? '';
                        } elseif (isset($item['ubicaciones_seleccionadas'])) {
                            $seccion = $item['seccion'] ?? 'GENERAL';
                            $ubicacionesSeleccionadas = $item['ubicaciones_seleccionadas'] ?? [];
                            $observaciones = $item['observaciones'] ?? '';
                        } else {
                            $seccion = 'GENERAL';
                            $ubicacionesSeleccionadas = is_array($item) ? array_values($item) : [$item];
                            $observaciones = '';
                        }
                    } else {
                        $seccion = 'GENERAL';
                        $ubicacionesSeleccionadas = [$item];
                        $observaciones = '';
                    }
                @endphp
                <tr style="border-bottom: 1px solid #e2e8f0;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                    <td style="padding: 1.2rem; font-weight: 600; color: #1e40af; vertical-align: top; font-size: 1.05rem;">
                        <i class="fas fa-folder"></i> {{ $seccion }}
                    </td>
                    <td style="padding: 1.2rem; font-size: 1.05rem; vertical-align: top;">
                        <div style="color: #64748b; font-size: 0.9rem; line-height: 1.6;">
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
                    <td style="padding: 1.2rem; font-size: 1.05rem; vertical-align: top;">
                        @if($observaciones)
                            <div style="color: #64748b; font-size: 0.9rem; line-height: 1.6;">
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
