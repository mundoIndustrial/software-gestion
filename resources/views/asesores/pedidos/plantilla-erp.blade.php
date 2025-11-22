<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Costura #{{ $pedido->numero_pedido }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border: 3px solid black;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid black;
            padding-bottom: 15px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .logo-text {
            font-size: 14px;
            letter-spacing: 2px;
        }

        .fecha-info {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
            font-size: 12px;
        }

        .fecha-box {
            border: 2px solid black;
            padding: 8px 12px;
            font-weight: bold;
            text-align: center;
        }

        .fecha-box label {
            display: block;
            font-size: 10px;
            margin-bottom: 3px;
        }

        /* Título */
        .titulo {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .numero-pedido {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            color: #d32f2f;
            margin-bottom: 20px;
        }

        /* Información General */
        .info-general {
            margin-bottom: 20px;
            font-size: 12px;
            line-height: 1.8;
        }

        .info-general p {
            margin: 5px 0;
        }

        .label {
            font-weight: bold;
        }

        /* Prendas */
        .prendas-section {
            margin-bottom: 20px;
        }

        .prendas-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 10px;
            border-bottom: 1px solid black;
            padding-bottom: 5px;
        }

        .prenda-item {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: flex;
            gap: 15px;
        }

        .prenda-foto {
            flex-shrink: 0;
        }

        .prenda-foto img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 1px solid #ccc;
            border-radius: 3px;
        }

        .prenda-info {
            flex-grow: 1;
            font-size: 11px;
        }

        .prenda-nombre {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .prenda-descripcion {
            color: #555;
            margin-bottom: 3px;
        }

        .prenda-cantidad {
            font-weight: bold;
            color: #d32f2f;
        }

        /* Footer */
        .footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid black;
            font-size: 12px;
        }

        .footer-box {
            border: 1px solid black;
            padding: 15px;
            text-align: center;
        }

        .footer-label {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .footer-value {
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Botones */
        .botones {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-imprimir {
            background-color: #2196F3;
            color: white;
        }

        .btn-imprimir:hover {
            background-color: #1976D2;
        }

        .btn-volver {
            background-color: #757575;
            color: white;
        }

        .btn-volver:hover {
            background-color: #616161;
        }

        @media print {
            body {
                background-color: white;
                padding: 0;
            }

            .container {
                box-shadow: none;
                border: 1px solid #ccc;
            }

            .botones {
                display: none;
            }
        }

        @media (max-width: 600px) {
            .container {
                padding: 15px;
                border-radius: 5px;
            }

            .prenda-item {
                flex-direction: column;
            }

            .footer {
                grid-template-columns: 1fr;
            }

            .fecha-info {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                🏭<br>
                <div class="logo-text">MUNDO INDUSTRIAL</div>
            </div>
            <div class="fecha-info">
                <div class="fecha-box">
                    <label>FECHA</label>
                    <div>{{ \Carbon\Carbon::parse($pedido->fecha_de_creacion_de_orden)->format('d') }}</div>
                </div>
                <div class="fecha-box">
                    <label>MES</label>
                    <div>{{ \Carbon\Carbon::parse($pedido->fecha_de_creacion_de_orden)->format('m') }}</div>
                </div>
                <div class="fecha-box">
                    <label>AÑO</label>
                    <div>{{ \Carbon\Carbon::parse($pedido->fecha_de_creacion_de_orden)->format('y') }}</div>
                </div>
            </div>
        </div>

        <!-- Información General -->
        <div class="info-general">
            <p><span class="label">ASESORA:</span> {{ $pedido->asesora }}</p>
            <p><span class="label">FORMA DE PAGO:</span> {{ $pedido->forma_de_pago ?? 'No especificada' }}</p>
        </div>

        <!-- Título y Número -->
        <div class="titulo">RECIBO DE COSTURA</div>
        <div class="numero-pedido">Nº {{ $pedido->numero_pedido }}</div>
        @if($pedido->numero_cotizacion)
            <div style="text-align: right; font-size: 12px; color: #666; margin-bottom: 10px;">
                Cotización: {{ $pedido->numero_cotizacion }}
            </div>
        @endif

        <!-- Prendas -->
        <div class="prendas-section">
            <div class="prendas-title">PRENDAS</div>
            
            @forelse($prendas as $prenda)
                @php
                    // Buscar la prenda en cotización para obtener foto
                    $prendaCotizacion = $prendasCotizacion->firstWhere('nombre_producto', $prenda->nombre_prenda);
                    $foto = $prendaCotizacion?->fotos[0] ?? null;
                @endphp
                
                <div class="prenda-item">
                    @if($foto)
                        <div class="prenda-foto">
                            <img src="{{ asset($foto) }}" alt="{{ $prenda->nombre_prenda }}">
                        </div>
                    @endif
                    
                    <div class="prenda-info">
                        <div class="prenda-nombre">{{ $prenda->nombre_prenda }}</div>
                        @if($prenda->descripcion)
                            <div class="prenda-descripcion">{{ $prenda->descripcion }}</div>
                        @endif
                        <div class="prenda-cantidad">CANTIDAD: {{ $prenda->cantidad }} unidades</div>
                    </div>
                </div>
            @empty
                <p style="text-align: center; color: #999;">No hay prendas en este pedido</p>
            @endforelse
        </div>

        <!-- Cliente -->
        <div class="info-general" style="margin-top: 20px;">
            <p><span class="label">CLIENTE:</span> {{ strtoupper($pedido->cliente) }}</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-box">
                <div class="footer-label">ENCARGADO DE ORDEN:</div>
                <div class="footer-value">
                    {{ $pedido->encargado ?? '_______________' }}
                </div>
            </div>
            <div class="footer-box">
                <div class="footer-label">PRENDAS ENTREGADAS:</div>
                <div class="footer-value">
                    0 de {{ $prendas->count() }} VER ENTREGAS
                </div>
            </div>
        </div>

        <!-- Botones -->
        <div class="botones">
            <button class="btn btn-imprimir" onclick="window.print()">🖨️ Imprimir</button>
            <button class="btn btn-volver" onclick="window.history.back()">← Volver</button>
        </div>
    </div>
</body>
</html>
