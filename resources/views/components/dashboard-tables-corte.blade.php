@php
    // Datos dummy para horas (adaptar lógica después)
    $horasData = [
        ['hora' => 'HORA 01', 'cantidad' => 16713, 'meta' => 17852, 'eficiencia' => 93.6],
        ['hora' => 'HORA 02', 'cantidad' => 18413, 'meta' => 18291, 'eficiencia' => 100.7],
        ['hora' => 'HORA 03', 'cantidad' => 16425, 'meta' => 19687, 'eficiencia' => 98.1],
        ['hora' => 'HORA 04', 'cantidad' => 18348, 'meta' => 18690, 'eficiencia' => 98.2],
        ['hora' => 'HORA 05', 'cantidad' => 18515, 'meta' => 18775, 'eficiencia' => 98.6],
        ['hora' => 'HORA 06', 'cantidad' => 17086, 'meta' => 17530, 'eficiencia' => 97.5],
        ['hora' => 'HORA 07', 'cantidad' => 14606, 'meta' => 14961, 'eficiencia' => 97.6],
        ['hora' => 'HORA 08', 'cantidad' => 14800, 'meta' => 16110, 'eficiencia' => 91.9],
        ['hora' => 'HORA 09', 'cantidad' => 5243, 'meta' => 5407, 'eficiencia' => 97.0],
        ['hora' => 'HORA 10', 'cantidad' => 3095, 'meta' => 3271, 'eficiencia' => 94.6],
        ['hora' => 'HORA 11', 'cantidad' => 41, 'meta' => 32, 'eficiencia' => 130.1],
        ['hora' => 'HORA 12', 'cantidad' => 1, 'meta' => 1, 'eficiencia' => 0]
    ];

    // Datos dummy para operarios
    $operariosData = [
        ['operario' => 'ADRIAN', 'cantidad' => 51152, 'meta' => 49549, 'eficiencia' => 103.2],
        ['operario' => 'JHONNY', 'cantidad' => 1739, 'meta' => 4241, 'eficiencia' => 41.0],
        ['operario' => 'JULIAN', 'cantidad' => 38757, 'meta' => 44307, 'eficiencia' => 87.5],
        ['operario' => 'PAOLA', 'cantidad' => 54635, 'meta' => 52630, 'eficiencia' => 103.8]
    ];

    $totalCantidadHoras = array_sum(array_column($horasData, 'cantidad'));
    $totalMetaHoras = array_sum(array_column($horasData, 'meta'));

    $totalCantidadOperarios = array_sum(array_column($operariosData, 'cantidad'));
    $totalMetaOperarios = array_sum(array_column($operariosData, 'meta'));
@endphp



<div class="records-table-container">
    <div class="table-scroll-container">
        <div style="display: flex; gap: 20px; padding: 20px;">
            <!-- Tabla de Horas -->
            <div style="flex: 1;">
                <h3 style="color: #e0e0e0; font-size: 18px; margin-bottom: 16px; text-align: center;">Producción por Horas</h3>
                <table style="width: 100%; border-collapse: collapse; background-color: #1f2937; font-size: 14px; border-radius: 8px; overflow: hidden;">
                    <thead>
                        <tr>
                            <th style="padding: 12px 16px; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; border-bottom: 2px solid #374151; text-align: left; color: #9ca3af; background-color: #374151;">HORA</th>
                            <th style="padding: 12px 16px; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; border-bottom: 2px solid #374151; text-align: center; color: #ffffff; background-color: #000000;">CANTIDAD</th>
                            <th style="padding: 12px 16px; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; border-bottom: 2px solid #374151; text-align: center; color: #ffffff; background-color: #000000;">META</th>
                            <th style="padding: 12px 16px; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; border-bottom: 2px solid #374151; text-align: center; color: #ffffff; background-color: #1e40af;">EFICIENCIA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($horasData as $row)
                        <tr>
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151; color: #ffffff; font-weight: 500;">{{ $row['hora'] }}</td>
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151; text-align: center; color: #d1d5db;">{{ number_format($row['cantidad']) }}</td>
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151; text-align: center; color: #d1d5db;">{{ number_format($row['meta']) }}</td>
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151; text-align: center;">
                                <span style="background-color: {{ $row['eficiencia'] < 70 ? '#ef4444' : ($row['eficiencia'] >= 70 && $row['eficiencia'] < 80 ? '#eab308' : ($row['eficiencia'] >= 80 && $row['eficiencia'] < 100 ? '#22c55e' : ($row['eficiencia'] >= 100 ? '#06b6d4' : '#6b7280'))) }}; color: #000000; padding: 4px 12px; border-radius: 4px; font-weight: 600; display: inline-block; min-width: 65px;">
                                    {{ $row['eficiencia'] > 0 ? number_format($row['eficiencia'], 1) . '%' : '-' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                        <tr style="background-color: #374151; font-weight: 600;">
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151; color: #ffffff;">Suma total</td>
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151; text-align: center; color: #ffffff;">{{ number_format($totalCantidadHoras) }}</td>
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151; text-align: center; color: #ffffff;">{{ number_format($totalMetaHoras) }}</td>
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151;"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tabla de Operarios -->
            <div style="flex: 1;">
                <h3 style="color: #e0e0e0; font-size: 18px; margin-bottom: 16px; text-align: center;">Producción por Operarios</h3>
                <table style="width: 100%; border-collapse: collapse; background-color: #1f2937; font-size: 14px; border-radius: 8px; overflow: hidden;">
                    <thead>
                        <tr>
                            <th style="padding: 12px 16px; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; border-bottom: 2px solid #374151; text-align: left; color: #9ca3af; background-color: #374151;">OPERARIO</th>
                            <th style="padding: 12px 16px; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; border-bottom: 2px solid #374151; text-align: center; color: #ffffff; background-color: #000000;">CANTIDAD</th>
                            <th style="padding: 12px 16px; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; border-bottom: 2px solid #374151; text-align: center; color: #ffffff; background-color: #000000;">META</th>
                            <th style="padding: 12px 16px; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; border-bottom: 2px solid #374151; text-align: center; color: #ffffff; background-color: #1e40af;">EFICIENCIA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($operariosData as $row)
                        <tr>
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151; color: #ffffff; font-weight: 500;">{{ $row['operario'] }}</td>
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151; text-align: center; color: #d1d5db;">{{ number_format($row['cantidad']) }}</td>
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151; text-align: center; color: #d1d5db;">{{ number_format($row['meta']) }}</td>
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151; text-align: center;">
                                <span style="background-color: {{ $row['eficiencia'] < 70 ? '#ef4444' : ($row['eficiencia'] >= 70 && $row['eficiencia'] < 80 ? '#eab308' : ($row['eficiencia'] >= 80 && $row['eficiencia'] < 100 ? '#22c55e' : ($row['eficiencia'] >= 100 ? '#06b6d4' : '#6b7280'))) }}; color: #000000; padding: 4px 12px; border-radius: 4px; font-weight: 600; display: inline-block; min-width: 65px;">
                                    {{ number_format($row['eficiencia'], 1) . '%' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                        <tr style="background-color: #374151; font-weight: 600;">
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151; color: #ffffff;">Total</td>
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151; text-align: center; color: #ffffff;">{{ number_format($totalCantidadOperarios) }}</td>
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151; text-align: center; color: #ffffff;">{{ number_format($totalMetaOperarios) }}</td>
                            <td style="padding: 10px 16px; border-bottom: 1px solid #374151;"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
