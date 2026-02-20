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

    private function getDocumentStructure(): string
    {
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

        $html .= '</body>
</html>';

        return $html;
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
        $eppCot = $this->data['epp_cotizacion'] ?? null;
        $items = $this->data['items'] ?? [];

        $tipoVenta = null;
        if (is_object($eppCot) && isset($eppCot->tipo_venta)) {
            $tipoVenta = $eppCot->tipo_venta;
        } elseif ($this->cotizacion->tipo_venta) {
            $tipoVenta = $this->cotizacion->tipo_venta;
        }

        $obs = null;
        if (is_object($eppCot) && isset($eppCot->observaciones_generales)) {
            $obs = $eppCot->observaciones_generales;
        }
        if (is_string($obs)) {
            $decoded = json_decode($obs, true);
            $obs = is_array($decoded) ? $decoded : null;
        }
        $iva = (is_array($obs) && array_key_exists('valor_iva', $obs)) ? (float)$obs['valor_iva'] : 0.0;

        $html = '<div class="content-wrapper">';

        if ($tipoVenta) {
            $tv = htmlspecialchars((string)$tipoVenta);
            $html .= '<div class="tipo-venta"><span class="t1">Por favor cotizar al</span><span class="t2">' . $tv . '</span></div>';
        }

        $subtotal = 0.0;

        foreach ($items as $item) {
            $nombre = htmlspecialchars((string)($item['nombre'] ?? 'Sin nombre'));
            $cantidad = (int)($item['cantidad'] ?? 1);
            $observaciones = htmlspecialchars((string)($item['observaciones'] ?? ''));
            $vu = isset($item['valor_unitario']) && $item['valor_unitario'] !== null ? (float)$item['valor_unitario'] : null;
            $totalItem = ($vu !== null) ? ($vu * $cantidad) : 0.0;
            $subtotal += $totalItem;

            $imgPath = null;
            $imgs = $item['imagenes'] ?? [];
            if (is_array($imgs) && count($imgs) > 0) {
                $imgPath = $imgs[0];
            }
            $imgTag = '';
            if ($imgPath && is_string($imgPath)) {
                $src = str_replace('\\', '/', $imgPath);
                $imgTag = '<div class="img-box"><img src="' . $src . '" alt="img"></div>';
            }

            $html .= '<div class="item-card">'
                . '<div class="item-row">'
                . '<div class="item-col left">'
                . '<div class="item-title">' . $nombre . '</div>'
                . '<div class="grid">'
                . '<div class="cell">'
                . '<div class="label-small">Cantidad</div>'
                . '<div class="value">' . $cantidad . '</div>'
                . '</div>'
                . '<div class="cell">'
                . '<div class="label-small">Observaciones</div>'
                . '<div class="value-soft">' . ($observaciones !== '' ? $observaciones : 'N/A') . '</div>'
                . '</div>'
                . '</div>'
                . ($vu !== null
                    ? ('<div class="grid" style="margin-top:8px;">'
                        . '<div class="cell">'
                        . '<div class="label-small">Valor unitario</div>'
                        . '<div class="value">' . $this->formatMoney($vu) . '</div>'
                        . '</div>'
                        . '<div class="cell">'
                        . '<div class="label-small">Total</div>'
                        . '<div class="value">' . $this->formatMoney($totalItem) . '</div>'
                        . '</div>'
                        . '</div>')
                    : '')
                . '</div>'
                . '<div class="item-col right">' . $imgTag . '</div>'
                . '</div>'
                . '</div>';
        }

        $totalConIva = $subtotal + $iva;

        $html .= '<div class="resumen">'
            . '<div class="resumen-header"><span class="title">Resumen</span></div>'
            . '<div class="resumen-body">'
            . '<div class="resumen-row"><span class="resumen-label">Subtotal</span><span class="resumen-val">' . $this->formatMoney($subtotal) . '</span></div>'
            . '<div class="resumen-row"><span class="resumen-label">IVA</span><span class="resumen-val">' . $this->formatMoney($iva) . '</span></div>'
            . '</div>'
            . '<div class="resumen-total"><div class="trow"><span class="tlabel">Total</span><span class="tval">' . $this->formatMoney($totalConIva) . '</span></div></div>'
            . '</div>';

        $html .= '</div>';

        return $html;
    }

    private function formatMoney(float $value): string
    {
        $entero = (int)round($value);
        $str = number_format($entero, 0, '', '.');
        return '$ ' . $str;
    }
}
