<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Entrega - Despacho {{ $pedido->numero_pedido }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: letter portrait;
            margin: 0;
        }

        html,
        body {
            width: 100%;
            background: #ffffff;
            color: #333333;
            font-family: Arial, Helvetica, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            padding: 0;
        }

        .sheet {
            width: 100%;
            min-height: 262mm;
            border: 1.4px solid #555555;
            border-radius: 24px;
            padding: 4mm 5mm 4mm;
            background: #ffffff;
        }

        .header-brand {
            text-align: center;
        }

        .date-box,
        .title-box-inner,
        .number-box-inner,
        .date-label {
            font-family: "Arial Narrow", "Roboto Condensed", Arial, Helvetica, sans-serif;
        }

        .logo {
            display: block;
            width: 42mm;
            max-width: 100%;
            margin: 0 auto 2mm;
        }

        .nit {
            font-size: 10pt;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 1mm;
        }

        .description {
            font-size: 8.7pt;
            line-height: 1.25;
            text-transform: uppercase;
        }

        .contact-bar {
            margin-top: 3mm;
            background: #ffe100;
            border: 1px solid #5a5a5a;
            border-radius: 14px;
            padding: 2.4mm 4mm;
            text-align: center;
            font-size: 8.7pt;
            font-weight: 700;
            line-height: 1.3;
        }

        .top-layout {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0.6mm 0;
            margin-top: 2mm;
        }

        .top-layout td {
            vertical-align: top;
        }

        .date-box,
        .number-box,
        .title-box {
            border: 1.4px solid #555555;
            border-radius: 4px;
            background: #ffffff;
            overflow: hidden;
        }

        .date-box {
            min-height: 14mm;
        }

        .date-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .date-table th,
        .date-table td {
            width: 33.3333%;
            text-align: center;
            padding: 0;
            margin: 0;
        }

        .date-table th {
            background: #ffe100;
            border-bottom: 1.4px solid #555555;
            border-right: 0.8px solid #8a8a8a;
            font-size: 7pt;
            font-weight: 700;
            font-style: italic;
            letter-spacing: 0.2px;
            padding: 0.9mm 0 0.75mm;
            line-height: 1;
        }

        .date-table th:last-child,
        .date-table td:last-child {
            border-right: none;
        }

        .date-table td {
            background: #ffffff;
            border-right: 0.8px solid #8a8a8a;
            padding: 0.8mm 1mm 1mm;
            font-size: 7.5pt;
            font-weight: 700;
            line-height: 1.1;
        }

        .date-table tr:last-child td {
            border-bottom: none;
        }

        .date-label {
            display: block;
            font-size: 6pt;
            font-weight: 700;
            font-style: italic;
            text-transform: uppercase;
            margin-bottom: 0.4mm;
        }

        .date-value {
            display: block;
            min-height: 3.8mm;
            font-size: 7.5pt;
            font-weight: 700;
        }

        .title-box {
            min-height: 14mm;
            display: flex;
            width: 100%;
            border: none;
            border-radius: 0;
            padding-left: 4mm;
        }

        .title-box-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 14mm;
            width: 100%;
            text-align: center;
            font-size: 9.5pt;
            font-weight: 900;
            line-height: 0.9;
            letter-spacing: 0.1px;
            text-transform: uppercase;
            padding: 0.6mm 0.2mm 0.6mm 0;
            color: #4f4f4f;
        }

        .number-box {
            min-height: 14mm;
            display: table;
            width: 100%;
            color: #b55448;
            border-radius: 6px;
            box-shadow: -1px 1px 0 #555555;
        }

        .number-box-inner {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            font-size: 9.5pt;
            font-weight: 800;
            font-family: "Times New Roman", Times, serif;
            letter-spacing: 0.3px;
            color: #b55448;
        }

        .client-section {
            margin-top: 4mm;
        }

        .client-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 2.5mm;
            table-layout: fixed;
        }

        .client-table td {
            width: 50%;
            padding-right: 3mm;
            vertical-align: bottom;
        }

        .client-field {
            display: flex;
            align-items: flex-end;
            gap: 2mm;
            font-size: 9pt;
            line-height: 1;
        }

        .client-label {
            white-space: nowrap;
            font-weight: 700;
        }

        .client-line {
            flex: 1;
            min-height: 5.5mm;
            border-bottom: 1px solid #555555;
            display: flex;
            align-items: flex-end;
            padding-bottom: 1mm;
            overflow: hidden;
            font-size: 9pt;
        }

        .main-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 4mm;
            border: 1.4px solid #555555;
            border-radius: 18px;
            overflow: hidden;
            table-layout: fixed;
        }

        .main-table thead th {
            background: #ffe100;
            border-bottom: 1.4px solid #555555;
            border-right: 1px solid #555555;
            padding: 2.2mm 2.5mm;
            font-size: 9pt;
            font-weight: 800;
            text-transform: uppercase;
            text-align: center;
        }

        .main-table thead th:last-child {
            border-right: none;
        }

        .main-table tbody td {
            border-right: 1px solid #777777;
            border-bottom: 1px solid #777777;
            padding: 2mm 2.5mm;
            min-height: 8.5mm;
            font-size: 10px;
            vertical-align: top;
        }

        .main-table tbody tr:last-child td {
            border-bottom: none;
        }

        .main-table tbody td:last-child {
            border-right: none;
        }

        .qty-col {
            width: 22mm;
            text-align: center;
            font-weight: 700;
        }

        .article-col {
            width: auto;
            line-height: 1.2;
            font-size: 10px;
            white-space: pre-line;
            word-break: break-word;
        }

        .empty-row td {
            color: transparent;
        }

        .observations {
            margin-top: 4mm;
            border: 1.4px solid #555555;
            border-radius: 18px;
            min-height: 0;
            padding: 3mm 4mm 4mm;
        }

        .observations-label {
            font-size: 10pt;
            font-weight: 700;
            margin-bottom: 2mm;
        }

        .observations-area {
            min-height: 7mm;
            border-radius: 10px;
            font-size: 10px;
            line-height: 1.25;
            white-space: pre-wrap;
            word-break: break-word;
            background:
                linear-gradient(to bottom, transparent 0, transparent 5.8mm, rgba(0, 0, 0, 0.12) 5.9mm, transparent 6mm);
            background-size: 100% 6mm;
        }

        .signatures {
            margin-top: 4mm;
        }

        .signature-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8mm 3mm;
            table-layout: fixed;
        }

        .signature-table td {
            width: 50%;
            vertical-align: top;
        }

        .signature-block {
            min-height: 22mm;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .signature-line {
            height: 2mm;
            border-bottom: 1px solid #555555;
        }

        .signature-image {
            height: 24mm;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0;
        }

        .signature-image img {
            max-height: 24mm;
            max-width: 100%;
            object-fit: contain;
        }

        .signature-written {
            min-height: 0;
            height: auto;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
            margin-bottom: 0;
            text-transform: uppercase;
            padding-bottom: 0;
            line-height: 1;
        }

        .signature-label {
            margin-top: 1.2mm;
            font-size: 9pt;
            font-weight: 700;
        }

        .footer {
            margin-top: 4mm;
            text-align: center;
            font-size: 8.6pt;
            font-style: italic;
            font-weight: 600;
            line-height: 1.35;
        }

        @media screen {
            body {
                background: #f3f4f6;
                padding: 10mm;
            }

            .sheet {
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            }
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }

            .sheet {
                width: 100%;
                min-height: 279.4mm;
                border-radius: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    @php
        $clienteModel = $pedido->cliente ?? null;
        $clienteNombre = $comprobante->cliente_nombre
            ?? (is_object($clienteModel)
                ? ($clienteModel->nombre ?? $pedido->getAttribute('cliente') ?? '')
                : (is_string($pedido->getAttribute('cliente') ?? null) ? $pedido->getAttribute('cliente') : ''));
        $clienteEmail = $comprobante->cliente_email
            ?? (is_object($clienteModel) ? ($clienteModel->email ?? '') : '');
        $compFactura = $comprobante->comp_factura_no ?? $pedido->factura_numero ?? $pedido->orden_compra ?? '';
        $fechaComprobante = $comprobante->created_at ?? now();
        $numeroComprobante = $comprobante->id ?? 1;
        $fechaEntrega = $comprobante->fecha_entrega?->format('d/m/Y') ?? '';
        $observacionesComprobante = trim((string) ($comprobante->observaciones ?? ''));
        $firmasComprobante = data_get($comprobante, 'snapshot.firmas', []);
        $obtenerFirma = function (string $key) use ($firmasComprobante): array {
            $firma = data_get($firmasComprobante, $key, []);

            return [
                'texto' => trim((string) data_get($firma, 'texto', '')),
                'imagen' => trim((string) data_get($firma, 'imagen', '')),
            ];
        };

        $tableRows = collect($tableRows ?? [])
            ->filter(function ($item) {
                return trim((string) data_get($item, 'articulo', '')) !== ''
                    || trim((string) data_get($item, 'cantidad', '')) !== '';
            })
            ->values()
            ->all();
        while (count($tableRows) < 10) {
            $tableRows[] = ['cantidad' => '', 'articulo' => ''];
        }

        $formatearArticuloComprobante = function ($articulo): string {
            $texto = trim((string) $articulo);
            if ($texto === '') {
                return '';
            }

            $lineas = preg_split('/\R/u', $texto) ?: [];
            $segmentos = [];

            foreach ($lineas as $index => $linea) {
                $linea = trim((string) $linea);
                if ($linea === '') {
                    continue;
                }

                $linea = preg_replace('/^[•\-\s]+/u', '', $linea) ?? $linea;

                if ($index > 0) {
                    if (preg_match('/^[A-Za-zÁÉÍÓÚÑáéíóúñ]+\s+(.+)$/u', $linea, $matches)) {
                        $linea = trim($matches[1]);
                    }
                    if ($linea !== '' && $linea[0] !== '(') {
                        $linea = '(' . $linea . ')';
                    }
                }

                $segmentos[] = $linea;
            }

            return implode(', ', $segmentos);
        };
    @endphp

    <div class="sheet">
        <div class="header-brand">
            <img src="{{ asset('images/logo2.png') }}" alt="Mundo Industrial" class="logo">
            <div class="nit">NIT. 1.093.738.433-3</div>
            <div class="description">
                Fabricación, confección y comercialización de dotación industrial, elementos de protección personal,
                uniformes empresariales y soluciones textiles para la industria
            </div>
            <div class="contact-bar">
                Avenida 3 No. 4-34 B. Latino - Celular: 3163853956 - Cúcuta - Col.
                <br>
                ventas@mundoindustrial.com - www.mundoindustrial.co
            </div>
        </div>

        <table class="top-layout" aria-hidden="true">
            <tr>
                <td style="width: 31%;">
                    <div class="date-box">
                        <table class="date-table" aria-label="Fecha del comprobante">
                            <tr>
                                <th>Día</th>
                                <th>Mes</th>
                                <th>Año</th>
                            </tr>
                            <tr>
                                <td><span class="date-value">{{ $fechaComprobante->format('d') }}</span></td>
                                <td><span class="date-value">{{ $fechaComprobante->format('m') }}</span></td>
                                <td><span class="date-value">{{ $fechaComprobante->format('Y') }}</span></td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style="width: 34%;">
                    <div class="title-box">
                        <div class="title-box-inner">
                            COMPROBANTE<br>
                            DE ENTREGA - DESPACHO
                        </div>
                    </div>
                </td>
                <td style="width: 35%;">
                    <div class="number-box">
                        <div class="number-box-inner">
                            Nº&nbsp;{{ $numeroComprobante }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="client-section">
            <table class="client-table">
                <tr>
                    <td>
                        <div class="client-field">
                            <span class="client-label">Cliente</span>
                            <span class="client-line">{{ $clienteNombre }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="client-field">
                            <span class="client-label">Comp. de Factura No.</span>
                            <span class="client-line">{{ $compFactura }}</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="client-field">
                            <span class="client-label">Email</span>
                            <span class="client-line">{{ $clienteEmail }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="client-field">
                            <span class="client-label">Fecha de Entrega</span>
                            <span class="client-line">{{ $fechaEntrega }}</span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="main-table">
            <thead>
                <tr>
                    <th class="qty-col">Cant.</th>
                    <th class="article-col">Articulo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tableRows as $row)
                    <tr class="{{ trim((string) $row['articulo']) === '' && trim((string) $row['cantidad']) === '' ? 'empty-row' : '' }}">
                        <td class="qty-col">{{ $row['cantidad'] }}</td>
                        <td class="article-col">{{ $formatearArticuloComprobante($row['articulo']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="observations">
            <div class="observations-label">Observación:</div>
            <div class="observations-area">{{ $observacionesComprobante }}</div>
        </div>

        <div class="signatures">
            <table class="signature-table">
                <tr>
                    <td>
                        <div class="signature-block">
                            @php($firmaRecibido = $obtenerFirma('recibido_por'))
                            @if($firmaRecibido['imagen'] !== '')
                                <div class="signature-image"><img src="{{ $firmaRecibido['imagen'] }}" alt="Firma recibida"></div>
                            @elseif($firmaRecibido['texto'] !== '')
                                <div class="signature-written">{{ $firmaRecibido['texto'] }}</div>
                            @endif
                        </div>
                        <div class="signature-line"></div>
                        <div class="signature-label">Recibido por</div>
                    </td>
                    <td>
                        <div class="signature-block">
                            @php($firmaEntregado = $obtenerFirma('entregado_por'))
                            @if($firmaEntregado['imagen'] !== '')
                                <div class="signature-image"><img src="{{ $firmaEntregado['imagen'] }}" alt="Firma entregado"></div>
                            @elseif($firmaEntregado['texto'] !== '')
                                <div class="signature-written">{{ $firmaEntregado['texto'] }}</div>
                            @endif
                        </div>
                        <div class="signature-line"></div>
                        <div class="signature-label">Entregado por</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="signature-block">
                            @php($firmaCc = $obtenerFirma('cc'))
                            @if($firmaCc['texto'] !== '')
                                <div class="signature-written">{{ $firmaCc['texto'] }}</div>
                            @endif
                        </div>
                        <div class="signature-line"></div>
                        <div class="signature-label">C.C.</div>
                    </td>
                    <td>
                        <div class="signature-block">
                            @php($firmaRevisado = $obtenerFirma('revisado_por'))
                            @if($firmaRevisado['imagen'] !== '')
                                <div class="signature-image"><img src="{{ $firmaRevisado['imagen'] }}" alt="Firma revisada"></div>
                            @elseif($firmaRevisado['texto'] !== '')
                                <div class="signature-written">{{ $firmaRevisado['texto'] }}</div>
                            @endif
                        </div>
                        <div class="signature-line"></div>
                        <div class="signature-label">Revisado por</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            "Acepto que la mercancía recibida se encuentra en buen estado y en las cantidades requeridas."
        </div>
    </div>
</body>
</html>
