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
            background: #dcfce7;
            font-weight: bold;
            color: #166534;
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
            <button class="btn" onclick="window.print()">🖨️ Imprimir</button>
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
                <div class="table-title">👕 PRENDAS</div>
                <table>
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th class="numeric">Talla</th>
                            <th class="numeric">Cantidad Total</th>
                            <th class="numeric">Parcial 1</th>
                            <th class="numeric">Pendiente 1</th>
                            <th class="numeric">Parcial 2</th>
                            <th class="numeric">Pendiente 2</th>
                            <th class="numeric">Parcial 3</th>
                            <th class="numeric">Pendiente 3</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prendas as $fila)
                            <tr>
                                <td>{{ $fila->descripcion }}</td>
                                <td class="numeric">{{ $fila->talla }}</td>
                                <td class="numeric">{{ $fila->cantidadTotal }}</td>
                                <td class="numeric">—</td>
                                <td class="numeric">{{ $fila->cantidadTotal }}</td>
                                <td class="numeric">—</td>
                                <td class="numeric">{{ $fila->cantidadTotal }}</td>
                                <td class="numeric">—</td>
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
                <div class="table-title">🛡️ EPP (ELEMENTOS DE PROTECCIÓN PERSONAL)</div>
                <table>
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th class="numeric">Talla</th>
                            <th class="numeric">Cantidad Total</th>
                            <th class="numeric">Parcial 1</th>
                            <th class="numeric">Pendiente 1</th>
                            <th class="numeric">Parcial 2</th>
                            <th class="numeric">Pendiente 2</th>
                            <th class="numeric">Parcial 3</th>
                            <th class="numeric">Pendiente 3</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($epps as $fila)
                            <tr>
                                <td>{{ $fila->descripcion }}</td>
                                <td class="numeric">—</td>
                                <td class="numeric">{{ $fila->cantidadTotal }}</td>
                                <td class="numeric">—</td>
                                <td class="numeric">{{ $fila->cantidadTotal }}</td>
                                <td class="numeric">—</td>
                                <td class="numeric">{{ $fila->cantidadTotal }}</td>
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
                <div class="signature-label">Preparado por</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Recibido por</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Autorizado por</div>
            </div>
        </div>

        <!-- Notas finales -->
        <div style="margin-top: 40px; padding: 15px; background: #f1f5f9; border-radius: 4px; font-size: 11px; color: #64748b; border-left: 4px solid #3b82f6;">
            <strong>Notas importantes:</strong>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Verificar que todas las cantidades sean correctas antes de firmar</li>
                <li>Cada parcial representa un envío o fase de entrega</li>
                <li>Conservar este documento para auditoría y control</li>
                <li>Para prendas: verificar talles por cantidad. Para EPP: verificar códigos y marcas</li>
            </ul>
        </div>
    </div>
</body>
</html>
