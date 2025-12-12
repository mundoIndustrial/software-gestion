{{-- Especificaciones Generales --}}
@php
    $categoriasInfo = [
        'disponibilidad' => ['emoji' => '📦', 'label' => 'DISPONIBILIDAD'],
        'forma_pago' => ['emoji' => '💳', 'label' => 'FORMA DE PAGO'],
        'regimen' => ['emoji' => '🏛️', 'label' => 'RÉGIMEN'],
        'se_ha_vendido' => ['emoji' => '📊', 'label' => 'SE HA VENDIDO'],
        'ultima_venta' => ['emoji' => '💰', 'label' => 'ÚLTIMA VENTA'],
        'flete' => ['emoji' => '🚚', 'label' => 'FLETE DE ENVÍO']
    ];
    
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
        <div style="
            font-size: 1.4rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 1.75rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid #0ea5e9;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        ">
            <i class="fas fa-clipboard-check" style="color: #0ea5e9; font-size: 1.4rem;"></i> Especificaciones Generales
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
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td colspan="3" style="font-weight: 600; background: #1e40af; padding: 12px; color: white;">
                                    <span style="font-size: 1.1rem; margin-right: 8px;">{{ $info['emoji'] }}</span>
                                    <span>{{ $info['label'] }}</span>
                                </td>
                            </tr>
                            @foreach($especificacionesData[$categoriaKey] as $valor)
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 12px; color: #333; font-weight: 500; border-right: 1px solid #e2e8f0;">
                                        @if(is_array($valor) && isset($valor['valor']))
                                            {{ $valor['valor'] ?? '-' }}
                                        @elseif(is_array($valor))
                                            {{ implode(', ', $valor) ?? '-' }}
                                        @else
                                            {{ $valor ?? '-' }}
                                        @endif
                                    </td>
                                    <td style="padding: 12px; text-align: center; color: #1e40af; font-weight: 700; font-size: 1.2rem; border-right: 1px solid #e2e8f0;">✕</td>
                                    <td style="padding: 12px; color: #64748b; font-size: 0.9rem;">
                                        @if(is_array($valor) && isset($valor['observacion']) && !empty($valor['observacion']))
                                            {{ $valor['observacion'] }}
                                        @else
                                            Sin observaciones
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
