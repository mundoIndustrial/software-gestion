<?php

namespace App\Application\Services\PdfDesign;

use App\Models\Cotizacion;

class EppPdfDesign
{
    private Cotizacion $cotizacion;
    private array $data;

    public function __construct(Cotizacion $cotizacion, array $data)
    {
        $this->cotizacion = $cotizacion;
        $this->data = $data;
    }

    public function build(): string
    {
        return $this->getDocumentStructure();
    }

    private function getAsesorData(): array
    {
        // Obtener datos del asesor desde la cotización
        $asesor = $this->cotizacion->asesor ?? null;
        
        // Si no hay asesor, usar datos del usuario
        if (!$asesor) {
            $usuario = $this->cotizacion->usuario ?? null;
            return [
                'nombre' => $usuario->name ?? '',
                'email' => $usuario->email ?? '',
                'telefono' => '',
                'direccion' => '',
                'celular' => '',
                'empresa' => 'Mundo Industrial SAS',
                'nit' => '1.093.738.433-3'
            ];
        }
        
        return [
            'nombre' => $asesor->nombre ?? '',
            'email' => $asesor->email ?? '',
            'telefono' => $asesor->telefono ?? '',
            'direccion' => $asesor->direccion ?? '',
            'celular' => $asesor->celular ?? '',
            'empresa' => 'Mundo Industrial SAS',
            'nit' => '1.093.738.433-3'
        ];
    }

    private function getDocumentStructure(): string
    {
        try {
            $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>' . $this->getStyles() . '</style>
</head>
<body>';

            $html .= $this->renderHeader();
            $html .= $this->renderClientInfo();
            $html .= $this->renderEpp();
            $html .= $this->renderFooter();

            $html .= '</body>
</html>';

            return $html;
        } catch (Exception $e) {
            // Log del error para debugging
            error_log('Error en getDocumentStructure: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    private function renderFooter(): string
    {
        try {
            // Obtener email del asesor logueado
            $asesorEmail = '';
            $asesor = $this->cotizacion->asesor ?? null;
            if ($asesor && !empty($asesor->email)) {
                $asesorEmail = $asesor->email;
            } else {
                // Si no hay asesor, usar email del usuario
                $usuario = $this->cotizacion->usuario ?? null;
                if ($usuario && !empty($usuario->email)) {
                    $asesorEmail = $usuario->email;
                }
            }
            
            $html = '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center;">';
            $html .= '<p style="margin: 0; color: #6b7280; font-size: 9px; font-weight: bold;">';
            $html .= 'Avenida 3 No. 4-34 B. Latino – Celular: 3163853956 – Cúcuta – Col.<br>';
            $html .= 'Email: ' . htmlspecialchars($asesorEmail) . ' - www.mundoindustrial.co';
            $html .= '</p>';
            $html .= '</div>';
            
            return $html;
        } catch (Exception $e) {
            error_log('Error en renderFooter: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    private function getStyles(): string
    {
        return <<<'CSS'
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { margin: 0; }
        html, body { width: 100%; margin: 0; padding: 0; height: auto; }
        body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.4; margin: 0; padding: 0; color: #0f172a; }

        .header-wrapper { width: 100%; margin: 0; padding: 0; }
        .header { text-align: center; border-bottom: 2px solid #000; padding: 15px 12mm; background: #000; color: #fff; display: flex; align-items: flex-start; gap: 15px; }
        .header-logo { width: 120px; height: auto; flex-shrink: 0; }
        .header-content { flex: 1; text-align: center; }
        .header-title { font-size: 14px; font-weight: bold; margin: 0; }
        .header-subtitle { font-size: 10px; margin: 2px 0; }

        .info-wrapper { width: 100%; margin: 0; padding: 0; margin-bottom: 8px; }
        .info-table { width: 100%; border-collapse: collapse; table-layout: fixed; padding: 0 12mm; }
        .info-table td { padding: 5px; border: 1px solid #000; word-wrap: break-word; }
        .info-table .label { background: #f0f0f0; font-weight: bold; }

        .content-wrapper { padding: 12mm; }

        .tipo-venta { margin-bottom: 14px; padding: 10px; background: #fee2e2; border: 2px solid #ef4444; border-radius: 8px; }
        .tipo-venta .t1 { color: #000; font-size: 12px; font-weight: 600; }
        .tipo-venta .t2 { color: #dc2626; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-left: 6px; }

        .item-card { background: #f5f5f5; border-left: 5px solid #0ea5e9; padding: 10px 12px; border-radius: 4px; margin-bottom: 12px; page-break-inside: avoid; }
        .item-row { display: table; width: 100%; table-layout: fixed; }
        .item-col { display: table-cell; vertical-align: top; }
        .item-col.left { width: 70%; padding-right: 10px; }
        .item-col.right { width: 30%; }

        .item-title { font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 6px; }

        .grid { display: table; width: 100%; table-layout: fixed; margin-top: 4px; }
        .grid .cell { display: table-cell; vertical-align: top; padding-right: 8px; }
        .label-small { font-size: 8px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 2px; }
        .value { font-size: 10px; font-weight: 700; color: #0f172a; }
        .value-soft { font-size: 10px; font-weight: 500; color: #0f172a; }

        .img-box { border: 1px solid #e5e7eb; background: #f3f4f6; border-radius: 14px; overflow: hidden; width: 110px; height: 110px; margin-left: auto; }
        .img-box img { width: 110px; height: 110px; object-fit: cover; display: block; }

        .resumen { margin-top: 6px; border-radius: 14px; overflow: hidden; border: 1px solid #e5e7eb; background: #f8fafc; }
        .resumen-header { padding: 10px 12px; border-bottom: 1px solid #cbd5e1; }
        .resumen-header .title { font-size: 9px; font-weight: 900; letter-spacing: 0.8px; text-transform: uppercase; }
        .resumen-body { padding: 10px 12px; }
        .resumen-row { display: table; width: 100%; table-layout: fixed; margin-bottom: 6px; }
        .resumen-row:last-child { margin-bottom: 0; }
        .resumen-label { display: table-cell; font-size: 9px; font-weight: 800; text-transform: uppercase; color: #334155; }
        .resumen-val { display: table-cell; text-align: right; font-size: 11px; font-weight: 900; }
        .resumen-total { padding: 10px 12px; background: #000; color: #fff; border-radius: 0 0 14px 14px; }
        .resumen-total .tlabel { font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.8px; display: table-cell; }
        .resumen-total .tval { font-size: 13px; font-weight: 900; display: table-cell; text-align: right; }
        .resumen-total .trow { display: table; width: 100%; table-layout: fixed; }
CSS;
    }

    private function renderHeader(): string
    {
        $logoPath = public_path('images/logo3.png');

        return <<<HTML
        <div class="header-wrapper">
            <div class="header">
                <img src="{$logoPath}" class="header-logo" alt="Logo">
                <div class="header-content">
                    <div class="header-title">Uniformes Mundo Industrial</div>
                    <div class="header-subtitle">Lenis Ruth Mahecha Acosta</div>
                    <div class="header-subtitle">NIT: 1.093.738.433-3 Régimen Común</div>
                    <div style="font-size: 12px; font-weight: bold; margin-top: 4px;">COTIZACIÓN EPP</div>
                </div>
            </div>
        </div>
        HTML;
    }

    private function renderClientInfo(): string
    {
        $nombreCliente = htmlspecialchars($this->cotizacion->cliente?->nombre ?? 'N/A');
        $nombreAsesor = htmlspecialchars($this->cotizacion->usuario?->name ?? 'N/A');
        $fecha = htmlspecialchars($this->cotizacion->created_at?->format('d/m/Y') ?? 'N/A');

        return <<<HTML
        <div class="info-wrapper">
            <table class="info-table">
                <tr>
                    <td class="label" style="width: 15%;">CLIENTE</td>
                    <td style="color: #e74c3c; font-weight: bold; width: 25%;">{$nombreCliente}</td>
                    <td class="label" style="width: 15%;">ASESOR</td>
                    <td style="color: #e74c3c; font-weight: bold; width: 25%;">{$nombreAsesor}</td>
                    <td class="label" style="width: 10%;">Fecha</td>
                    <td style="color: #e74c3c; font-weight: bold; width: 10%;">{$fecha}</td>
                </tr>
            </table>
        </div>
        HTML;
    }

    private function renderEpp(): string
    {
        try {
            $eppCot = $this->data['epp_cotizacion'] ?? null;
            $items = $this->data['items'] ?? [];
            
            // Obtener IVA desde observaciones generales del EPP
            $obs = null;
            if ($eppCot) {
                $obs = $eppCot->observaciones_generales;
            }
            if (is_string($obs)) {
                $decoded = json_decode($obs, true);
                $obs = is_array($decoded) ? $decoded : null;
            }
            $iva = (is_array($obs) && array_key_exists('valor_iva', $obs)) ? (float)$obs['valor_iva'] : 0.0;

            $html = '<div class="content-wrapper">';

            // Contenedor principal con ancho máximo
            $html .= '<div style="max-width: 800px; margin: 0 auto;">';

            // Tabla de resumen de items EPP
            $html .= '<div style="margin-bottom: 20px;">';
            $html .= '<table style="width: 100%; border-collapse: collapse;">';
            $html .= '<thead>';
            $html .= '<tr style="background: #fef3c7; color: #000000;">';
            $html .= '<th style="padding: 10px; text-align: left; font-size: 10px; font-weight: bold; border: 1px solid #fbbf24;">ÍTEM</th>';
            $html .= '<th style="padding: 10px; text-align: left; font-size: 10px; font-weight: bold; border: 1px solid #fbbf24;">DESCRIPCIÓN</th>';
            $html .= '<th style="padding: 10px; text-align: center; font-size: 10px; font-weight: bold; border: 1px solid #fbbf24;">CANTIDAD</th>';
            $html .= '<th style="padding: 10px; text-align: left; font-size: 10px; font-weight: bold; border: 1px solid #fbbf24;">OBSERVACIONES</th>';
            $html .= '<th style="padding: 10px; text-align: right; font-size: 10px; font-weight: bold; border: 1px solid #fbbf24;">V. UNITARIO</th>';
            $html .= '<th style="padding: 10px; text-align: right; font-size: 10px; font-weight: bold; border: 1px solid #fbbf24;">TOTAL</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            $subtotal = 0.0;
            $itemIndex = 1;

            foreach ($items as $item) {
                $nombre = htmlspecialchars((string)($item['nombre'] ?? 'Sin nombre'));
                $cantidad = (int)($item['cantidad'] ?? 1);
                $observaciones = htmlspecialchars((string)($item['observaciones'] ?? ''));
                $vu = isset($item['valor_unitario']) && $item['valor_unitario'] !== null ? (float)$item['valor_unitario'] : null;
                $totalItem = ($vu !== null) ? ($vu * $cantidad) : 0.0;
                $subtotal += $totalItem;

                // Alternar colores de fila
                $rowColor = ($itemIndex % 2 === 0) ? '#f8fafc' : '#ffffff';
                
                $html .= '<tr style="background: ' . $rowColor . ';">';
                $html .= '<td style="padding: 10px; border: 1px solid #e5e7eb; font-size: 9px; text-align: center; font-weight: 500;">' . $itemIndex . '</td>';
                $html .= '<td style="padding: 10px; border: 1px solid #e5e7eb; font-size: 9px; font-weight: 500;">' . $nombre . '</td>';
                $html .= '<td style="padding: 10px; border: 1px solid #e5e7eb; font-size: 9px; text-align: center; font-weight: 500;">' . $cantidad . '</td>';
                $html .= '<td style="padding: 10px; border: 1px solid #e5e7eb; font-size: 9px; font-style: italic;">' . ($observaciones !== '' ? $observaciones : 'N/A') . '</td>';
                $html .= '<td style="padding: 10px; border: 1px solid #e5e7eb; font-size: 9px; text-align: right; font-weight: 600;">';
                $html .= ($vu !== null ? $this->formatMoney($vu) : 'N/A');
                $html .= '</td>';
                $html .= '<td style="padding: 10px; border: 1px solid #e5e7eb; font-size: 9px; text-align: right; font-weight: 700; color: #1f2937;">';
                $html .= ($vu !== null ? $this->formatMoney($totalItem) : 'N/A');
                $html .= '</td>';
                $html .= '</tr>';
                
                $itemIndex++;
            }

            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';

            // Tabla de resumen de totales
            $totalConIva = $subtotal + $iva;

            $html .= '<div class="resumen" style="margin-bottom: 20px;">';
            $html .= '<table style="width: 100%; border-collapse: collapse;">';
            $html .= '<tr>';
            $html .= '<td style="background: #f8fafc; color: #1f2937; padding: 12px; text-align: right; font-size: 11px; font-weight: 600; border-bottom: 1px solid #e5e7eb;">';
            $html .= 'SUBTOTAL: ' . $this->formatMoney($subtotal);
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '<tr>';
            $html .= '<td style="background: #f1f5f9; color: #1f2937; padding: 12px; text-align: right; font-size: 11px; font-weight: 600; border-bottom: 1px solid #e5e7eb;">';
            $html .= 'IVA: ' . $this->formatMoney($iva);
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '<tr>';
            $html .= '<td style="background: #1f2937; color: white; padding: 14px; text-align: right; font-size: 13px; font-weight: 700;">';
            $html .= 'TOTAL: ' . $this->formatMoney($totalConIva);
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</table>';
            $html .= '</div>';

            $html .= '</div>'; // Cerrar contenedor principal

            // Agregar sección de observaciones generales e información adicional
            $html .= $this->renderObservacionesAdicionales();

            $html .= '</div>'; // Cerrar content-wrapper

            return $html;
        } catch (Exception $e) {
            error_log('Error en renderEpp: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    private function formatMoney(float $value): string
    {
        $entero = (int)round($value);
        $str = number_format($entero, 0, '', '.');
        return '$ ' . $str;
    }

    private function renderObservacionesAdicionales(): string
    {
        $html = '';
        
        // Obtener observaciones generales de la cotización
        $observacionesGenerales = $this->cotizacion->observaciones_generales ?? '';
        
        // Obtener especificaciones (JSON) de la cotización
        $especificaciones = $this->cotizacion->especificaciones ?? [];
        if (is_string($especificaciones)) {
            $especificaciones = json_decode($especificaciones, true) ?? [];
        }
        
        // Función para decodificar caracteres escapados
        $decodeEscapedChars = function($text) {
            // Primero manejar doble escape (JSON string)
            $text = str_replace('\\r\\n', "\n", $text); // \r\n (doble escape) -> salto de línea
            $text = str_replace('\\r', "\n", $text); // \r (doble escape) -> salto de línea
            $text = str_replace('\\n', "\n", $text); // \n (doble escape) -> salto de línea
            
            // Luego caracteres Unicode escapados
            $text = str_replace('\\u2013', '–', $text); // Guion largo
            $text = str_replace('\\u2014', '—', $text); // Guion em dash
            $text = str_replace('\\u2019', "'", $text); // Apóstrofe
            $text = str_replace('\\u201c', '"', $text); // Comillas dobles apertura
            $text = str_replace('\\u201d', '"', $text); // Comillas dobles cierre
            $text = str_replace('\\t', "\t", $text); // Tabulaciones
            
            // Convertir saltos de línea a <br> para PDF
            $text = str_replace("\n", "<br>", $text);
            
            return $text;
        };
        
        // Sección de Observaciones Generales
        if (!empty($observacionesGenerales)) {
            $observacionesDecodificadas = $decodeEscapedChars($observacionesGenerales);
            $html .= '<div style="margin-bottom: 20px;">';
            $html .= '<table style="width: 100%; border-collapse: collapse;">';
            $html .= '<tr><td style="background: #fef3c7; color: #000000; padding: 10px; text-align: center; font-size: 10px; font-weight: bold; border: 1px solid #fbbf24;">';
            $html .= 'OBSERVACIONES GENERALES';
            $html .= '</td></tr>';
            $html .= '<tr><td style="background: #ffffff; padding: 10px; border: 1px solid #fbbf24;">';
            $html .= '<p style="margin: 0; color: #374151; font-size: 9px; text-align: justify;">' . $observacionesDecodificadas . '</p>';
            $html .= '</td></tr>';
            $html .= '</table>';
            $html .= '</div>';
        }
        
        // Sección de Información Adicional
        $tieneInformacionAdicional = false;
        $contenidoAdicional = '';
        $contenidoCuentasAutorizadas = '';
        
        // Condiciones de pago
        if (!empty($especificaciones['condiciones_pago'])) {
            $tieneInformacionAdicional = true;
            $condicionesDecodificadas = $decodeEscapedChars($especificaciones['condiciones_pago']);
            $contenidoAdicional .= '<tr>';
            $contenidoAdicional .= '<td style="background: #ffffff; padding: 10px; border: 1px solid #fbbf24; text-align: left; width: 30%; font-weight: bold; color: #000000; font-size: 9px;">';
            $contenidoAdicional .= 'CONDICIONES DE PAGO';
            $contenidoAdicional .= '</td>';
            $contenidoAdicional .= '<td style="background: #ffffff; padding: 10px; border: 1px solid #fbbf24; text-align: left; color: #000000; font-size: 9px;">';
            $contenidoAdicional .= $condicionesDecodificadas;
            $contenidoAdicional .= '</td>';
            $contenidoAdicional .= '</tr>';
        }
        
        // Tiempo de entrega
        if (!empty($especificaciones['tiempo_entrega'])) {
            $tieneInformacionAdicional = true;
            $tiempoDecodificado = $decodeEscapedChars($especificaciones['tiempo_entrega']);
            $contenidoAdicional .= '<tr>';
            $contenidoAdicional .= '<td style="background: #ffffff; padding: 10px; border: 1px solid #fbbf24; text-align: left; width: 30%; font-weight: bold; color: #000000; font-size: 9px;">';
            $contenidoAdicional .= 'TIEMPO DE ENTREGA';
            $contenidoAdicional .= '</td>';
            $contenidoAdicional .= '<td style="background: #ffffff; padding: 10px; border: 1px solid #fbbf24; text-align: left; color: #000000; font-size: 9px;">';
            $contenidoAdicional .= $tiempoDecodificado;
            $contenidoAdicional .= '</td>';
            $contenidoAdicional .= '</tr>';
        }
        
        // Cuentas autorizadas (guardar para agregar al final)
        if (!empty($especificaciones['cuentas_autorizadas'])) {
            $tieneInformacionAdicional = true;
            $cuentasDecodificadas = $decodeEscapedChars($especificaciones['cuentas_autorizadas']);
            $contenidoCuentasAutorizadas .= '<tr>';
            $contenidoCuentasAutorizadas .= '<td style="background: #ffffff; padding: 10px; border: 1px solid #fbbf24; text-align: left; width: 30%; font-weight: bold; color: #000000; font-size: 9px; vertical-align: top;">';
            $contenidoCuentasAutorizadas .= 'CUENTAS AUTORIZADAS';
            $contenidoCuentasAutorizadas .= '</td>';
            $contenidoCuentasAutorizadas .= '<td style="background: #ffffff; padding: 10px; border: 1px solid #fbbf24; text-align: left; color: #000000; font-size: 9px;">';
            $contenidoCuentasAutorizadas .= $cuentasDecodificadas;
            $contenidoCuentasAutorizadas .= '</td>';
            $contenidoCuentasAutorizadas .= '</tr>';
        }
        
        // Información adicional dinámica
        if (!empty($especificaciones['informacion_adicional']) && is_array($especificaciones['informacion_adicional'])) {
            foreach ($especificaciones['informacion_adicional'] as $info) {
                if (!empty($info['titulo']) && !empty($info['contenido'])) {
                    $tieneInformacionAdicional = true;
                    $tituloDecodificado = $decodeEscapedChars($info['titulo']);
                    $contenidoDecodificado = $decodeEscapedChars($info['contenido']);
                    
                    $contenidoAdicional .= '<tr>';
                    $contenidoAdicional .= '<td style="background: #ffffff; padding: 10px; border: 1px solid #fbbf24; text-align: left; width: 30%; font-weight: bold; color: #000000; font-size: 9px; vertical-align: top;">';
                    $contenidoAdicional .= htmlspecialchars(strtoupper($tituloDecodificado));
                    $contenidoAdicional .= '</td>';
                    $contenidoAdicional .= '<td style="background: #ffffff; padding: 10px; border: 1px solid #fbbf24; text-align: left; color: #000000; font-size: 9px;">';
                    $contenidoAdicional .= $contenidoDecodificado;
                    $contenidoAdicional .= '</td>';
                    $contenidoAdicional .= '</tr>';
                }
            }
        }
        
        // Agregar cuentas autorizadas al final
        if (!empty($contenidoCuentasAutorizadas)) {
            $contenidoAdicional .= $contenidoCuentasAutorizadas;
        }
        
        if ($tieneInformacionAdicional) {
            $html .= '<div style="margin-bottom: 20px;">';
            $html .= '<table style="width: 100%; border-collapse: collapse;">';
            $html .= '<tr><td style="background: #fef3c7; color: #000000; padding: 10px; text-align: center; font-size: 10px; font-weight: bold; border: 1px solid #fbbf24;">';
            $html .= 'INFORMACIÓN ADICIONAL';
            $html .= '</td></tr>';
            $html .= '<tr><td style="background: #ffffff; padding: 0; border: 1px solid #fbbf24;">';
            $html .= '<table style="width: 100%; border-collapse: collapse;">';
            $html .= $contenidoAdicional;
            $html .= '</table>';
            $html .= '</td></tr>';
            $html .= '</table>';
            $html .= '</div>';
        }
        
        return $html;
    }
}
