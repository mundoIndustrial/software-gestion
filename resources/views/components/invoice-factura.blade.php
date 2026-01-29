@props([
    'orden' => null,
    'mostrarProcesos' => true,
    'mostrarEPP' => true
])

@php
    // Asegurarse de que tenemos datos de la orden
    $numeroFactura = $orden ? 'MI-PEDIDO-' . date('Y') . '-' . str_pad($orden->numero_pedido, 4, '0', STR_PAD_LEFT) : 'MI-PEDIDO-2026-0001';
    $numeroPedido = $orden->numero_pedido ?? '45703';
    $cliente = $orden->cliente ?? 'CLIENTE';
    $asesor = $orden->asesor->nombre ?? 'No asignado';
    $fechaCreacion = $orden->fecha_de_creacion_de_orden ? \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden)->format('d \d\e F \d\e Y') : date('d \d\e F \d\e Y');
    $fechaEntrega = $orden->dia_de_entrega ? \Carbon\Carbon::parse($orden->dia_de_entrega)->format('d \d\e F \d\e Y') : 'Por definir';
    $estado = $orden->estado ?? 'Pendiente';
    $formaPago = $orden->forma_de_pago ?? 'No especificada';
    
    // Procesos disponibles (estos pueden venir del modelo o ser estáticos)
    $procesos = [
        'corte' => 'Corte',
        'confeccion' => 'Confección',
        'bordado' => 'Bordado',
        'estampado' => 'Estampado',
        'lavanderia' => 'Lavandería',
        'planchado' => 'Planchado',
        'control_calidad' => 'Control de Calidad',
        'empaque' => 'Empaque'
    ];
    
    // EPP estándar (Equipo de Protección Personal)
    $epp = [
        'guantes' => 'Guantes de Seguridad',
        'gafas' => 'Gafas Protectoras',
        'casco' => 'Casco de Seguridad',
        'chaleco' => 'Chaleco Reflectivo',
        'botas' => 'Botas de Seguridad',
        'mascarilla' => 'Mascarilla N95',
        'arnés' => 'Arnés de Seguridad'
    ];
    
    // Calcular totales (ejemplo)
    $subtotal = 0;
    $iva = 0;
    $total = 0;
@endphp

<style>
    .invoice-wrapper {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f5f5;
        padding: 0;
        margin: 0;
    }

    .invoice-container {
        max-width: 100%;
        margin: 0;
        background: white;
        padding: 40px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        border-radius: 0;
    }

    /* Header */
    .invoice-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 40px;
        border-bottom: 3px solid #2c3e50;
        padding-bottom: 20px;
    }

    .company-info h1 {
        color: #2c3e50;
        font-size: 28px;
        margin-bottom: 5px;
    }

    .company-info p {
        color: #666;
        font-size: 13px;
        margin: 3px 0;
    }

    .invoice-details {
        text-align: right;
    }

    .invoice-number {
        font-size: 24px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 10px;
    }

    .invoice-number .prefix {
        font-size: 13px;
        display: block;
        color: #666;
        margin-bottom: 5px;
    }

    /* Meta Sections */
    .invoice-meta {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
        font-size: 13px;
        margin-bottom: 30px;
    }

    .meta-section {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 4px;
        border-left: 4px solid #2c3e50;
    }

    .meta-section h3 {
        color: #2c3e50;
        font-size: 11px;
        text-transform: uppercase;
        margin-bottom: 10px;
        letter-spacing: 1px;
        font-weight: 700;
    }

    .meta-section p {
        margin: 5px 0;
        color: #555;
    }

    .meta-section .label {
        color: #999;
        font-size: 11px;
        font-weight: 600;
    }

    .meta-section .value {
        font-weight: 600;
        color: #2c3e50;
        font-size: 14px;
    }

    /* Section Titles */
    .section-title {
        color: #2c3e50;
        font-size: 14px;
        text-transform: uppercase;
        margin: 30px 0 15px 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #2c3e50;
        letter-spacing: 1px;
        font-weight: 700;
    }

    /* Tables */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .items-table thead {
        background-color: #2c3e50;
        color: white;
    }

    .items-table th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        font-size: 12px;
    }

    .items-table td {
        padding: 12px;
        border-bottom: 1px solid #ddd;
        font-size: 13px;
    }

    .items-table tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }

    /* Prendas */
    .prenda-block {
        background: #f9f9f9;
        border-left: 4px solid #3498db;
        padding: 12px;
        margin-bottom: 10px;
        border-radius: 4px;
        word-wrap: break-word;
        overflow-wrap: break-word;
        overflow: hidden;
    }

    .prenda-nombre {
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .prenda-detalles {
        font-size: 12px;
        color: #666;
        margin-bottom: 8px;
    }

    .prenda-detalles strong {
        color: #333;
    }

    .prenda-tallas {
        font-size: 11px;
        background: white;
        padding: 8px;
        border-radius: 3px;
        margin-top: 8px;
        word-wrap: break-word;
        overflow-wrap: break-word;
        word-break: break-word;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        align-items: center;
    }

    .prenda-tallas strong {
        width: 100%;
        margin-bottom: 4px;
    }

    .prenda-tallas span {
        white-space: nowrap;
        flex-shrink: 1;
    }

    /* Procesos */
    .procesos-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
        gap: 4px;
        margin-top: 8px;
    }

    .proceso-badge {
        background: white;
        border: 1px solid #ddd;
        padding: 4px 6px;
        border-radius: 3px;
        font-size: 10px;
        text-align: center;
        color: #666;
        word-break: break-word;
        min-width: 0;
    }

    .proceso-badge.activo {
        background: #d4edda;
        border-color: #28a745;
        color: #155724;
        font-weight: 600;
    }

    /* EPP */
    .epp-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .epp-item {
        background: white;
        border: 1px solid #ddd;
        padding: 12px;
        border-radius: 4px;
        text-align: center;
        font-size: 12px;
    }

    .epp-item label {
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .epp-item input[type="checkbox"] {
        margin-right: 6px;
    }

    /* Summary */
    .summary-section {
        display: flex;
        justify-content: flex-end;
        margin-top: 30px;
    }

    .summary-box {
        width: 350px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }

    .summary-row.total {
        background-color: #2c3e50;
        color: white;
        padding: 15px;
        border: none;
        border-radius: 4px;
        font-weight: bold;
        font-size: 15px;
        margin-top: 10px;
    }

    /* Status */
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-pendiente {
        background-color: #fff3cd;
        color: #856404;
    }

    .status-confirmado {
        background-color: #d4edda;
        color: #155724;
    }

    .status-en-proceso {
        background-color: #cce5ff;
        color: #004085;
    }

    .status-completado {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    /* Footer */
    .invoice-footer {
        margin-top: 40px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
        text-align: center;
        font-size: 11px;
        color: #999;
    }

    .notes-section {
        background-color: #f9f9f9;
        padding: 15px;
        border-left: 4px solid #2c3e50;
        margin: 20px 0;
        border-radius: 4px;
    }

    .notes-section h4 {
        color: #2c3e50;
        margin-bottom: 8px;
        font-size: 12px;
        font-weight: 700;
    }

    .notes-section p {
        color: #666;
        font-size: 12px;
        line-height: 1.6;
        margin: 5px 0;
    }

    /* Print */
    @media print {
        body {
            background: white;
            padding: 0;
            margin: 0;
        }

        /* Ocultar barra de navegación */
        .top-nav {
            display: none !important;
        }

        /* Ocultar elemento padre de la navegación si existe */
        header, nav {
            display: none !important;
        }

        .invoice-wrapper {
            background: white;
            padding: 0;
            margin: 0;
        }

        .invoice-container {
            box-shadow: none;
            max-width: 100%;
            padding: 15px;
            margin: 0;
        }

        .print-button {
            display: none;
        }

        /* Optimizaciones de tamaño para impresión */
        .prenda-block {
            padding: 10px;
            margin-bottom: 8px;
        }

        .prenda-tallas {
            font-size: 10px;
            padding: 6px;
            gap: 3px;
        }

        .prenda-tallas span {
            padding: 1px 4px !important;
            margin: 1px !important;
            font-size: 9px;
        }

        .procesos-container {
            grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));
            gap: 2px;
        }

        .proceso-badge {
            padding: 3px 5px;
            font-size: 9px;
        }

        .prenda-nombre {
            font-size: 13px;
            margin-bottom: 6px;
        }

        .prenda-detalles {
            font-size: 11px;
            margin-bottom: 6px;
        }

        .prenda-detalles p {
            margin: 3px 0;
        }

        /* Optimizaciones adicionales para impresión */
        * {
            box-shadow: none !important;
        }
    }

    .print-button {
        background-color: #2c3e50;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 4px;
        font-size: 13px;
        cursor: pointer;
        margin-bottom: 20px;
        transition: background-color 0.3s;
        font-weight: 600;
    }

    .print-button:hover {
        background-color: #1a252f;
    }
</style>

<div class="invoice-wrapper">
    <button class="print-button" onclick="window.print()">
         Imprimir Factura
    </button>

    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-info">
                <h1>🏭 MUNDO INDUSTRIAL</h1>
                <p><strong>NIT:</strong> 123.456.789-0</p>
                <p><strong>Dirección:</strong> Calle Principal 123, Ciudad</p>
                <p><strong>Teléfono:</strong> +57 (1) 1234-5678</p>
                <p><strong>Email:</strong> ventas@mundoindustrial.com</p>
            </div>
            <div class="invoice-details">
                <div class="invoice-number">
                    <span class="prefix">FACTURA / PEDIDO</span>
                    {{ $numeroFactura }}
                </div>
            </div>
        </div>

        <!-- Metadata Section -->
        <div class="invoice-meta">
            <!-- Cliente -->
            <div class="meta-section">
                <h3> Información del Cliente</h3>
                <p><span class="label">CLIENTE:</span><br><span class="value">{{ $cliente }}</span></p>
                <p style="margin-top: 10px;"><span class="label">ASESOR:</span><br><span class="value">{{ $asesor }}</span></p>
            </div>

            <!-- Pedido -->
            <div class="meta-section">
                <h3> Información del Pedido</h3>
                <p><span class="label">NÚMERO:</span><br><span class="value">#{{ $numeroPedido }}</span></p>
                <p style="margin-top: 10px;"><span class="label">FORMA DE PAGO:</span><br><span class="value">{{ $formaPago }}</span></p>
            </div>

            <!-- Fechas -->
            <div class="meta-section">
                <h3>📅 Fechas</h3>
                <p><span class="label">CREACIÓN:</span><br><span class="value" style="font-size: 12px;">{{ $fechaCreacion }}</span></p>
                <p style="margin-top: 10px;"><span class="label">ENTREGA:</span><br><span class="value" style="font-size: 12px;">{{ $fechaEntrega }}</span></p>
            </div>
        </div>

        <!-- Prendas Section -->
        <div class="section-title"> Detalles de Prendas</div>
        
        @if($orden && $orden->prendas && $orden->prendas->count() > 0)
            @foreach($orden->prendas as $index => $prenda)
                <div class="prenda-block">
                    <div class="prenda-nombre">
                        Prenda {{ $index + 1 }}: {{ $prenda->nombre ?? 'Sin nombre' }}@if(isset($prenda->de_bodega) && $prenda->de_bodega) - SE SACA DE BODEGA@endif
                    </div>
                    
                    <div class="prenda-detalles">
                        @if($prenda->color)
                            <p><strong>Color:</strong> {{ $prenda->color }}</p>
                        @endif
                        @if($prenda->tela)
                            <p><strong>Tela:</strong> {{ $prenda->tela }}</p>
                        @endif
                    </div>

                    <!-- Variantes con Especificaciones (Manga, Broche, Bolsillos) -->
                    @if($prenda->variantes && is_array($prenda->variantes) && count($prenda->variantes) > 0)
                        <div style="margin: 12px 0; padding: 0; background: #ffffff; border-radius: 6px; border: 1px solid #e0e7ff; overflow: hidden;">
                            <div style="font-size: 11px; font-weight: 700; color: #1e40af; background: #eff6ff; margin: 0; padding: 12px 12px; border-bottom: 2px solid #bfdbfe;">
                                VARIANTES (ESPECIFICACIONES)
                            </div>
                            <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f0f9ff; border-bottom: 2px solid #bfdbfe;">
                                        <th style="padding: 8px 12px; text-align: left; font-weight: 600; color: #1e40af;">Talla</th>
                                        <th style="padding: 8px 12px; text-align: center; font-weight: 600; color: #1e40af;">Cantidad</th>
                                        <th style="padding: 8px 12px; text-align: left; font-weight: 600; color: #1e40af;">Manga</th>
                                        <th style="padding: 8px 12px; text-align: left; font-weight: 600; color: #1e40af;">Broche</th>
                                        <th style="padding: 8px 12px; text-align: left; font-weight: 600; color: #1e40af;">Bolsillos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($prenda->variantes as $idx => $variante)
                                        <tr style="background: {{ $idx % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom: 1px solid #e0e7ff;">
                                            <td style="padding: 8px 12px; font-weight: 600; color: #334155;">{{ $variante->talla ?? '—' }}</td>
                                            <td style="padding: 8px 12px; text-align: center; color: #475569;">{{ $variante->cantidad ?? 0 }}</td>
                                            <td style="padding: 8px 12px; color: #475569; font-size: 9px;">
                                                @if($variante->manga)
                                                    <strong>{{ $variante->manga }}</strong>
                                                    @if($variante->manga_obs)
                                                        <br><em style="color: #64748b; font-size: 8px;">{{ $variante->manga_obs }}</em>
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td style="padding: 8px 12px; color: #475569; font-size: 9px;">
                                                @if($variante->broche)
                                                    <strong>{{ $variante->broche }}</strong>
                                                    @if($variante->broche_obs)
                                                        <br><em style="color: #64748b; font-size: 8px;">{{ $variante->broche_obs }}</em>
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td style="padding: 8px 12px; color: #475569; font-size: 9px;">
                                                @if($variante->bolsillos)
                                                    <strong>Sí</strong>
                                                    @if($variante->bolsillos_obs)
                                                        <br><em style="color: #64748b; font-size: 8px;">{{ $variante->bolsillos_obs }}</em>
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <!-- Procesos -->
                    @if($mostrarProcesos)
                        <div style="margin-top: 10px;">
                            <strong style="font-size: 12px; color: #2c3e50;">Procesos:</strong>
                            <div class="procesos-container">
                                @foreach($procesos as $key => $nombre)
                                    <div class="proceso-badge">{{ $nombre }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        @else
            <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; text-align: center; color: #666;">
                No hay prendas registradas en este pedido.
            </div>
        @endif

        <!-- EPP Section -->
        @if($mostrarEPP)
            <div class="section-title">🦺 Equipo de Protección Personal (EPP)</div>
            <div class="epp-grid">
                @foreach($epp as $key => $nombre)
                    <div class="epp-item">
                        <label>
                            <input type="checkbox" name="epp_{{ $key }}" value="1">
                            {{ $nombre }}
                        </label>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Notas -->
        @if($orden)
            <div class="notes-section">
                <h4> Observaciones</h4>
                @if($orden->novedades)
                    <p><strong>Novedades:</strong> {{ $orden->novedades }}</p>
                @endif
                <p><strong>Estado:</strong> 
                    <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $estado)) }}">
                        {{ $estado }}
                    </span>
                </p>
                @if($orden->aprobado_por_supervisor_en)
                    <p><strong>Aprobado por supervisor:</strong> {{ \Carbon\Carbon::parse($orden->aprobado_por_supervisor_en)->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        @endif

        <!-- Summary -->
        <div class="summary-section">
            <div class="summary-box">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>$0.00</span>
                </div>
                <div class="summary-row">
                    <span>IVA (19%):</span>
                    <span>$0.00</span>
                </div>
                <div class="summary-row total">
                    <span>TOTAL A PAGAR:</span>
                    <span>$0.00</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <p>Esta factura fue generada automáticamente por el sistema MundoIndustrial.</p>
            <p>Gracias por su compra. Para consultas, contacte a nuestro equipo de servicio al cliente.</p>
            <hr style="margin: 10px 0; border: none; border-top: 1px solid #ddd;">
            <p>© 2026 Mundo Industrial S.A.S. Todos los derechos reservados.</p>
        </div>
    </div>
</div>
