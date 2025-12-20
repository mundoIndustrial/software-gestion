<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden #{{ $orden->numero_pedido }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }

        .titulo {
            text-align: center;
            flex: 1;
        }

        .titulo h1 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .numero-orden {
            font-size: 18px;
            color: #e74c3c;
            font-weight: bold;
        }

        .fecha-impresion {
            text-align: right;
            font-size: 12px;
            color: #7f8c8d;
        }

        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #3498db;
        }

        .info-box label {
            display: block;
            font-weight: bold;
            color: #7f8c8d;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .info-box value {
            display: block;
            font-size: 14px;
            color: #2c3e50;
        }

        .prendas-section {
            margin-bottom: 30px;
        }

        .prendas-section h2 {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 15px;
            border-bottom: 2px solid #e0e6ed;
            padding-bottom: 10px;
        }

        .tabla-prendas {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .tabla-prendas thead {
            background: #2c3e50;
            color: white;
        }

        .tabla-prendas th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
        }

        .tabla-prendas td {
            padding: 12px;
            border-bottom: 1px solid #e0e6ed;
            font-size: 13px;
        }

        .tabla-prendas tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .estado-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .estado-no-iniciado {
            background: #ecf0f1;
            color: #7f8c8d;
        }

        .estado-en-ejecucion {
            background: #fff3cd;
            color: #856404;
        }

        .estado-entregado {
            background: #d4edda;
            color: #155724;
        }

        .estado-anulada {
            background: #f8d7da;
            color: #721c24;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e6ed;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            font-size: 12px;
            color: #7f8c8d;
        }

        .footer-col {
            text-align: center;
        }

        .footer-col label {
            display: block;
            font-weight: bold;
            margin-bottom: 30px;
        }

        .observaciones {
            margin-top: 30px;
            padding: 15px;
            background: #fff3cd;
            border-left: 4px solid #f39c12;
            border-radius: 4px;
        }

        .observaciones label {
            display: block;
            font-weight: bold;
            color: #856404;
            margin-bottom: 10px;
        }

        .observaciones p {
            color: #856404;
            font-size: 13px;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">MUNDO INDUSTRIAL</div>
            <div class="titulo">
                <h1>ORDEN DE PRODUCCIÓN</h1>
                <div class="numero-orden">#{{ $orden->numero_pedido }}</div>
            </div>
            <div class="fecha-impresion">
                <strong>Fecha de Impresión:</strong><br>
                {{ now()->format('d/m/Y h:i A') }}
            </div>
        </div>

        <!-- Información General -->
        <div class="info-section">
            <div class="info-box">
                <label>Cliente</label>
                <value>{{ $orden->cliente }}</value>
            </div>
            <div class="info-box">
                <label>Asesora</label>
                <value>{{ $orden->asesora ?? 'N/A' }}</value>
            </div>
            <div class="info-box">
                <label>Fecha de Creación</label>
                <value>{{ \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden)->format('d/m/Y') }}</value>
            </div>
            <div class="info-box">
                <label>Forma de Pago</label>
                <value>{{ $orden->forma_de_pago ?? 'N/A' }}</value>
            </div>
        </div>

        <!-- Estado Actual -->
        <div class="info-section">
            <div class="info-box">
                <label>Estado Actual</label>
                <value>
                    <span class="estado-badge estado-{{ strtolower(str_replace(' ', '-', $orden->estado)) }}">
                        {{ $orden->estado }}
                    </span>
                </value>
            </div>
            @if($orden->fecha_estimada_entrega)
            <div class="info-box">
                <label>Fecha Estimada de Entrega</label>
                <value>{{ \Carbon\Carbon::parse($orden->fecha_estimada_entrega)->format('d/m/Y') }}</value>
            </div>
            @endif
        </div>

        <!-- Prendas -->
        @if($orden->prendas && $orden->prendas->count() > 0)
        <div class="prendas-section">
            <h2>Prendas de la Orden</h2>
            <table class="tabla-prendas">
                <thead>
                    <tr>
                        <th>Prenda</th>
                        <th>Cantidad</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orden->prendas as $prenda)
                    <tr>
                        <td><strong>{{ $prenda->nombre_prenda }}</strong></td>
                        <td>{{ $prenda->cantidad }}</td>
                        <td style="white-space: pre-wrap;">{{ $prenda->generarDescripcionDetallada($loop->iteration) ?? 'N/A' }}</td>
                        <td>
                            @if($prenda->procesos && $prenda->procesos->count() > 0)
                                <span class="estado-badge estado-{{ strtolower(str_replace(' ', '-', $prenda->procesos->last()->estado_proceso ?? 'N/A')) }}">
                                    {{ $prenda->procesos->last()->estado_proceso ?? 'N/A' }}
                                </span>
                            @else
                                <span class="estado-badge estado-no-iniciado">Sin Procesos</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Observaciones si la orden está anulada -->
        @if($orden->estado === 'Anulada' && $orden->motivo_anulacion)
        <div class="observaciones">
            <label>⚠️ Motivo de Anulación</label>
            <p>{{ $orden->motivo_anulacion }}</p>
            <p style="margin-top: 10px; font-size: 11px;">
                <strong>Anulada por:</strong> {{ $orden->usuario_anulacion ?? 'N/A' }}<br>
                <strong>Fecha de Anulación:</strong> {{ $orden->fecha_anulacion ? \Carbon\Carbon::parse($orden->fecha_anulacion)->format('d/m/Y h:i A') : 'N/A' }}
            </p>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div class="footer-col">
                <label>Firma Supervisor</label>
                ___________________
            </div>
            <div class="footer-col">
                <label>Firma Responsable</label>
                ___________________
            </div>
        </div>
    </div>
</body>
</html>
