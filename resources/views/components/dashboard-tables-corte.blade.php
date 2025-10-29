@php
    // Los datos dinámicos de horas y operarios se pasan desde el controlador
    $totalCantidadHoras = array_sum(array_column($horasData, 'cantidad'));
    $totalMetaHoras = array_sum(array_column($horasData, 'meta'));

    $totalCantidadOperarios = array_sum(array_column($operariosData, 'cantidad'));
    $totalMetaOperarios = array_sum(array_column($operariosData, 'meta'));
@endphp



<div class="records-table-container">
    <div class="table-scroll-container">
        <div style="display: flex; gap: 24px; padding: 24px; background: rgba(255, 255, 255, 0.03); border-radius: 16px; box-shadow: 0 8px 20px rgba(0,0,0,0.3); border: 1px solid rgba(255, 107, 53, 0.15);">
            <!-- Tabla de Horas -->
            <div style="flex: 1; background: rgba(26,29,41,0.8); border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); border: 1px solid rgba(255,107,53,0.1);">
                <h3 style="color: #ffffff; font-size: 20px; font-weight: 700; margin-bottom: 20px; text-align: center;">Producción por Horas</h3>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0; font-size: 14px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                        <thead>
                            <tr style="background: linear-gradient(135deg, #374151, #4b5563);">
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: left; color: #ffffff; border-radius: 8px 0 0 0;">HORA</th>
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: center; color: #ffffff;">CANTIDAD</th>
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: center; color: #ffffff;">META</th>
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: center; color: #ffffff; border-radius: 0 8px 0 0;">EFICIENCIA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($horasData as $row)
                            <tr style="background: rgba(255,255,255,0.02); transition: background-color 0.2s ease;">
                                <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #ffffff; font-weight: 500;">{{ $row['hora'] }}</td>
                                <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">{{ number_format($row['cantidad']) }}</td>
                                <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">{{ number_format($row['meta']) }}</td>
                                <td style="padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; background: {{ $row['eficiencia'] < 70 ? '#7f1d1d' : ($row['eficiencia'] >= 70 && $row['eficiencia'] < 80 ? '#92400e' : ($row['eficiencia'] >= 80 && $row['eficiencia'] < 100 ? '#166534' : ($row['eficiencia'] >= 100 ? '#0c4a6e' : '#374151'))) }}; color: #ffffff; font-weight: 600; font-size: 13px;">
                                    <div style="padding: 14px 20px; width: 100%; height: 100%;">{{ $row['eficiencia'] > 0 ? number_format($row['eficiencia'], 1) . '%' : '-' }}</div>
                                </td>
                            </tr>
                            @endforeach
                            <tr style="background: linear-gradient(135deg, #1f2937, #374151); font-weight: 600; border-radius: 0 0 8px 8px;">
                                <td style="padding: 16px 20px; border-bottom: none; color: #ffffff; border-radius: 0 0 0 8px;">TOTAL</td>
                                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;">{{ number_format($totalCantidadHoras) }}</td>
                                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;">{{ number_format($totalMetaHoras) }}</td>
                                <td style="padding: 16px 20px; border-bottom: none; border-radius: 0 0 8px 0;"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla de Operarios -->
            <div style="flex: 1; background: rgba(26,29,41,0.8); border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); border: 1px solid rgba(255,107,53,0.1);">
                <h3 style="color: #ffffff; font-size: 20px; font-weight: 700; margin-bottom: 20px; text-align: center;">Producción por Operarios</h3>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0; font-size: 14px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                        <thead>
                            <tr style="background: linear-gradient(135deg, #374151, #4b5563);">
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: left; color: #ffffff; border-radius: 8px 0 0 0;">OPERARIO</th>
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: center; color: #ffffff;">CANTIDAD</th>
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: center; color: #ffffff;">META</th>
                                <th style="padding: 16px 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.8px; border-bottom: 2px solid rgba(255,255,255,0.1); text-align: center; color: #ffffff; border-radius: 0 8px 0 0;">EFICIENCIA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($operariosData as $row)
                            <tr style="background: rgba(255,255,255,0.02); transition: background-color 0.2s ease;">
                                <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #ffffff; font-weight: 500;">{{ $row['operario'] }}</td>
                                <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">{{ number_format($row['cantidad']) }}</td>
                                <td style="padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; color: #94a3b8; font-weight: 500;">{{ number_format($row['meta']) }}</td>
                                <td style="padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; background: {{ $row['eficiencia'] < 70 ? '#7f1d1d' : ($row['eficiencia'] >= 70 && $row['eficiencia'] < 80 ? '#92400e' : ($row['eficiencia'] >= 80 && $row['eficiencia'] < 100 ? '#166534' : ($row['eficiencia'] >= 100 ? '#0c4a6e' : '#374151'))) }}; color: #ffffff; font-weight: 600; font-size: 13px;">
                                    <div style="padding: 14px 20px; width: 100%; height: 100%;">{{ $row['eficiencia'] > 0 ? number_format($row['eficiencia'], 1) . '%' : '-' }}</div>
                                </td>
                            </tr>
                            @endforeach
                            <tr style="background: linear-gradient(135deg, #1f2937, #374151); font-weight: 600; border-radius: 0 0 8px 8px;">
                                <td style="padding: 16px 20px; border-bottom: none; color: #ffffff; border-radius: 0 0 0 8px;">TOTAL</td>
                                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;">{{ number_format($totalCantidadOperarios) }}</td>
                                <td style="padding: 16px 20px; border-bottom: none; text-align: center; color: #ffffff;">{{ number_format($totalMetaOperarios) }}</td>
                                <td style="padding: 16px 20px; border-bottom: none; border-radius: 0 0 8px 0;"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
