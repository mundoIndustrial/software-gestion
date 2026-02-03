<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Entregas - {{ $pedido->numero_pedido }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            background: white;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20mm;
            background: white;
        }

        /* Encabezado */
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #1e293b;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .header p {
            font-size: 12px;
            color: #64748b;
        }

        /* Información del pedido */
        .info-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .info-box {
            padding: 15px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            background: #f8fafc;
        }

        .info-box label {
            font-size: 11px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            display: block;
            margin-bottom: 5px;
        }

        .info-box value {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
            display: block;
        }

        /* Tabla de despacho */
        .table-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .table-title {
            font-size: 14px;
            font-weight: bold;
            color: white;
            background: #334155;
            padding: 12px 15px;
            margin-bottom: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        thead {
            background: #e2e8f0;
            border-top: 2px solid #64748b;
            border-bottom: 2px solid #64748b;
        }

        th {
            padding: 10px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
        }

        td {
            padding: 10px;
            font-size: 11px;
            border-bottom: 1px solid #e2e8f0;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        tbody tr:hover {
            background: #eff6ff;
        }

        /* Secciones */
        .section-prendas {
            background: #dbeafe;
            font-weight: bold;
            color: #0c4a6e;
        }

        .section-epp {
            background: #dbeafe;
            font-weight: bold;
            color: #0c4a6e;
        }

        /* Valores númericos */
        .numeric {
            text-align: center;
            font-variant-numeric: tabular-nums;
            font-weight: bold;
        }

        /* Pie de página */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #cbd5e1;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            page-break-inside: avoid;
        }

        .signature-box {
            text-align: center;
            padding: 40px 20px 0 20px;
        }

        .signature-line {
            border-top: 1px solid #1e293b;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 11px;
            font-weight: bold;
        }

        .signature-label {
            font-size: 11px;
            color: #64748b;
            margin-top: 5px;
        }

        /* Impresión */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .container {
                max-width: 100%;
                padding: 10mm;
            }

            .no-print {
                display: none;
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            table {
                page-break-inside: avoid;
            }

            tr {
                page-break-inside: avoid;
            }

            /* Ocultar contenido de celdas de datos (mantener estructura) */
            tbody td {
                color: transparent;
                min-height: 30px;
                padding: 15px 10px;
            }

            tbody tr:nth-child(even) {
                background: white;
            }

            tbody tr:hover {
                background: white;
            }

            /* Mantener visible solo los headers */
            thead th {
                color: #334155;
            }

            /* Mantener visible la información del pedido */
            .info-box value {
                color: #1e293b;
            }

            .header h1,
            .header p,
            .table-title {
                color: inherit;
            }
        }

        /* Botones (solo en pantalla) */
        .print-buttons {
            text-align: center;
            margin-bottom: 20px;
            padding: 20px;
            background: #f1f5f9;
            border-radius: 8px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
        }

        .btn:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #64748b;
        }

        .btn-secondary:hover {
            background: #475569;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Botones de impresión (solo pantalla) -->
        <div class="no-print print-buttons">
            <button class="btn" onclick="window.print()"> Imprimir</button>
            <a href="{{ route('despacho.show', $pedido->id) }}" class="btn btn-secondary">← Volver</a>
        </div>

        <!-- Encabezado -->
        <div class="header">
            <h1>📦 CONTROL DE ENTREGAS</h1>
            <p>Documento de despacho y control de mercancía</p>
        </div>

        <!-- Información del pedido -->
        <div class="info-section">
            <div class="info-box">
                <label>Nº Pedido</label>
                <value>{{ $pedido->numero_pedido }}</value>
            </div>
            <div class="info-box">
                <label>Cliente</label>
                <value>{{ $pedido->cliente ?? '—' }}</value>
            </div>
            <div class="info-box">
                <label>Fecha Despacho</label>
                <value>{{ now()->format('d/m/Y H:i') }}</value>
            </div>
        </div>

        <!-- PRENDAS -->
        @php
            $prendas = $filas->filter(fn($f) => $f->tipo === 'prenda');
            $epps = $filas->filter(fn($f) => $f->tipo === 'epp');
        @endphp

        @if($prendas->count() > 0)
            <div class="table-section">
                <div class="table-title">PRENDAS</div>
                <table>
                    <thead>
                            <tr>
                                <th>Descripción</th>
                                <th class="numeric">Género</th>
                                <th class="numeric">Talla</th>
                                <th class="numeric">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prendas as $fila)
                                <tr>
                                    <td>
                                        <div style="font-weight: bold; margin-bottom: 4px;">
                                            {{ $fila->objetoPrenda['nombre'] ?? $fila->descripcion }}
                                            @if(isset($fila->objetoPrenda['de_bodega']) && $fila->objetoPrenda['de_bodega'])
                                                <span style="color: #ea580c; font-weight: bold;"> - SE SACA DE BODEGA</span>
                                            @endif
                                        </div>
                                        @if($fila->objetoPrenda && isset($fila->objetoPrenda['variantes']) && is_array($fila->objetoPrenda['variantes']) && count($fila->objetoPrenda['variantes']) > 0)
                                            @php
                                                $primeraVariante = $fila->objetoPrenda['variantes'][0];
                                                $manga = $primeraVariante->manga ?? $primeraVariante['manga'] ?? null;
                                                $manga_obs = $primeraVariante->manga_obs ?? $primeraVariante['manga_obs'] ?? '';
                                                $broche = $primeraVariante->broche ?? $primeraVariante['broche'] ?? null;
                                                $broche_obs = $primeraVariante->broche_obs ?? $primeraVariante['broche_obs'] ?? '';
                                                $bolsillos = $primeraVariante->bolsillos ?? $primeraVariante['bolsillos'] ?? false;
                                                $bolsillos_obs = $primeraVariante->bolsillos_obs ?? $primeraVariante['bolsillos_obs'] ?? '';
                                            @endphp
                                            <div style="font-size: 11px; color: #64748b; line-height: 1.3; font-weight: bold;">
                                                @if($manga)
                                                    <div>• Manga:{{ $manga }}{{ $manga_obs && trim($manga_obs) !== '' ? " ($manga_obs)" : '' }}</div>
                                                @endif
                                                @if($broche)
                                                    <div>• {{ $broche }}{{ $broche_obs && trim($broche_obs) !== '' ? " ($broche_obs)" : '' }}</div>
                                                @endif
                                                @if($bolsillos)
                                                    <div>• Bolsillos{{ $bolsillos_obs && trim($bolsillos_obs) !== '' ? " ($bolsillos_obs)" : '' }}</div>
                                                @endif
                                            </div>
                                            
                                            @if($fila->objetoPrenda && isset($fila->objetoPrenda['procesos']) && is_array($fila->objetoPrenda['procesos']) && count($fila->objetoPrenda['procesos']) > 0)
                                            <div style="font-size: 11px; color: #64748b; margin-top: 4px; font-weight: bold;">
                                                @foreach($fila->objetoPrenda['procesos'] as $proc)
                                                        @php
                                                            $ubicaciones = $proc->ubicaciones ?? $proc['ubicaciones'] ?? [];
                                                            $ubicacionesStr = is_array($ubicaciones) ? implode(', ', $ubicaciones) : $ubicaciones;
                                                        @endphp
                                                        <div>• {{ $proc->nombre ?? $proc->tipo_proceso ?? $proc['tipo_proceso'] ?? 'Proceso' }}{{ $ubicacionesStr && trim($ubicacionesStr) !== '' ? " ($ubicacionesStr)" : '' }}</div>
                                                @endforeach
                                            </div>
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="numeric">{{ $fila->genero ?? '—' }}</td>
                                    <td class="numeric">{{ $fila->talla }}</td>
                                    <td class="numeric">{{ $fila->cantidadTotal }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- EPP -->
        @if($epps->count() > 0)
            <div class="table-section">
                <div class="table-title"> EPP (ELEMENTOS DE PROTECCIÓN PERSONAL)</div>
                <table>
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th class="numeric">Género</th>
                            <th class="numeric">Talla</th>
                            <th class="numeric">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($epps as $fila)
                            <tr>
                                <td>
                                    <div style="font-weight: bold; margin-bottom: 4px;"> {{ $fila->objetoEpp['nombre'] ?? $fila->objetoEpp['nombre_completo'] ?? $fila->descripcion }}</div>
                                    @if($fila->objetoEpp && isset($fila->objetoEpp['observaciones']) && $fila->objetoEpp['observaciones'] && $fila->objetoEpp['observaciones'] !== '—' && $fila->objetoEpp['observaciones'] !== '-')
                                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">{{ $fila->objetoEpp['observaciones'] }}</div>
                                    @endif
                                </td>
                                <td class="numeric">—</td>
                                <td class="numeric">—</td>
                                <td class="numeric">{{ $fila->cantidadTotal }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Pie de página con firmas -->
        <div class="footer">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Recibido por</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Autorizado por</div>
            </div>
        </div>

   
    </div>
</body>
</html>
