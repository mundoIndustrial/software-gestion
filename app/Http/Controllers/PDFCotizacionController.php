<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PDFCotizacionController extends Controller
{
    /**
     * Genera el PDF de una cotización
     */
    public function generarPDF($cotizacionId)
    {
        try {
            $cotizacion = Cotizacion::with(['prendasCotizaciones', 'usuario'])->findOrFail($cotizacionId);
            
            // Generar HTML del PDF
            $html = $this->generarHTML($cotizacion);
            
            // Crear PDF con DomPDF
            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4', 'portrait')
                ->setOption('margin-top', 8)
                ->setOption('margin-bottom', 8)
                ->setOption('margin-left', 8)
                ->setOption('margin-right', 8)
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true)
                ->setOption('enable_php', true)
                ->setOption('dpi', 96)
                ->setOption('font_subsetting', false);
            
            // Nombre del archivo
            $filename = 'Cotizacion_' . $cotizacion->id . '_' . date('Y-m-d') . '.pdf';
            
            // Verificar si es una solicitud de descarga
            $descargar = request()->query('descargar', false);
            
            if ($descargar) {
                // Descargar el PDF
                return $pdf->download($filename);
            } else {
                // Mostrar el PDF en el navegador
                return $pdf->stream($filename);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Genera el HTML del PDF
     */
    private function generarHTML($cotizacion)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; }
        body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.4; overflow-x: hidden; }
        .container { width: 100%; padding: 8mm; min-width: 100%; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #000; padding-bottom: 8px; }
        .header-logo { width: 50px; height: auto; margin-bottom: 3px; }
        .header-title { font-size: 14px; font-weight: bold; margin: 2px 0; }
        .header-subtitle { font-size: 10px; color: #666; margin: 1px 0; }
        .info-table { width: 100%; margin-bottom: 12px; border-collapse: collapse; table-layout: fixed; }
        .info-table td { padding: 5px; border: 1px solid #000; word-wrap: break-word; }
        .info-table .label { background: #f0f0f0; font-weight: bold; width: 15%; }
        .prenda { margin-bottom: 15px; page-break-inside: avoid; }
        .prenda-nombre { font-size: 11px; font-weight: bold; margin-bottom: 3px; }
        .prenda-descripcion { font-size: 8px; margin-bottom: 3px; color: #333; line-height: 1.3; }
        .prenda-tallas { font-size: 8px; font-weight: bold; margin-bottom: 3px; }
        .prenda-imagenes { display: flex; gap: 8px; margin-bottom: 8px; flex-wrap: wrap; }
        .prenda-imagen { width: 100px; height: 100px; border: 1px solid #ddd; flex-shrink: 0; }
        .spec-table { width: 100%; border-collapse: collapse; margin-top: 12px; table-layout: fixed; }
        .spec-table th { background: #FFC107; padding: 6px; border: 1px solid #000; font-weight: bold; text-align: left; font-size: 9px; }
        .spec-table td { padding: 5px; border: 1px solid #000; font-size: 8px; word-wrap: break-word; }
        .spec-table .label { background: #f9f9f9; font-weight: bold; width: 22%; }
    </style>
</head>
<body>
<div class="container">';
        
        // Encabezado
        $html .= $this->generarEncabezadoHTML($cotizacion);
        
        // Información cliente y fecha
        $html .= $this->generarInfoClienteHTML($cotizacion);
        
        // Prendas
        $html .= $this->generarPrendasHTML($cotizacion);
        
        // Tabla de especificaciones
        $html .= $this->generarTablaEspecificacionesHTML($cotizacion);
        
        $html .= '</div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Genera el HTML del encabezado
     */
    private function generarEncabezadoHTML($cotizacion)
    {
        return '
        <div class="header">
            <img src="' . public_path('images/logo2.png') . '" class="header-logo" alt="Logo">
            <div class="header-title">Uniformes Mundo Industrial</div>
            <div class="header-subtitle">Leonis Rulith Mahecha Acosta</div>
            <div class="header-subtitle">NIT: 1.093.738.633-3 Régimen Común</div>
            <div class="header-title" style="margin-top: 5px;">COTIZACIÓN</div>
        </div>';
    }
    
    /**
     * Genera el HTML de información cliente
     */
    private function generarInfoClienteHTML($cotizacion)
    {
        return '
        <table class="info-table">
            <tr>
                <td class="label">CLIENTE</td>
                <td>' . ($cotizacion->cliente ?? 'N/A') . '</td>
                <td class="label">Fecha</td>
                <td>' . ($cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y') : 'N/A') . '</td>
            </tr>
        </table>';
    }
    
    /**
     * Genera el HTML de prendas
     */
    private function generarPrendasHTML($cotizacion)
    {
        $html = '';
        
        foreach ($cotizacion->prendasCotizaciones as $prenda) {
            $html .= '<div class="prenda">';
            
            // Nombre
            $html .= '<div class="prenda-nombre">' . strtoupper($prenda->nombre_producto ?? 'N/A') . '</div>';
            
            // Descripción
            if ($prenda->descripcion) {
                $html .= '<div class="prenda-descripcion">' . $prenda->descripcion . '</div>';
            }
            
            // Tallas
            $tallas = $prenda->tallas ?? [];
            
            // Convertir a array si es JSON
            if (is_string($tallas)) {
                $tallas = json_decode($tallas, true) ?? [];
            }
            
            $tallasTexto = '';
            if (is_array($tallas) && count($tallas) > 0) {
                $tallasTexto = implode(', ', array_filter($tallas));
            }
            
            // Imágenes
            $imagenes = $prenda->fotos ?? [];
            $imagenTela = $prenda->imagen_tela ?? null;
            
            $todasLasImagenes = [];
            if (is_array($imagenes) && count($imagenes) > 0) {
                foreach ($imagenes as $img) {
                    $todasLasImagenes[] = $img;
                }
            }
            if ($imagenTela) {
                $todasLasImagenes[] = $imagenTela;
            }
            
            // Contenedor con tallas e imágenes lado a lado
            $html .= '<div style="display: flex; gap: 10px; align-items: flex-start;">';
            
            // Columna izquierda: Tallas
            $html .= '<div style="flex: 0 0 auto;">';
            if ($tallasTexto) {
                $html .= '<div class="prenda-tallas">Tallas:<br>' . $tallasTexto . '</div>';
            }
            $html .= '</div>';
            
            // Columna derecha: Imágenes
            if (count($todasLasImagenes) > 0) {
                $html .= '<div class="prenda-imagenes">';
                foreach ($todasLasImagenes as $imagen) {
                    $rutaImagen = $imagen;
                    if (strpos($imagen, '/') === 0 && strpos($imagen, 'http') !== 0) {
                        $rutaImagen = public_path($imagen);
                    }
                    
                    if (file_exists($rutaImagen)) {
                        $html .= '<img src="' . $rutaImagen . '" class="prenda-imagen" alt="Imagen">';
                    }
                }
                $html .= '</div>';
            }
            
            $html .= '</div>';
            
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * Genera el HTML de la tabla de especificaciones
     */
    private function generarTablaEspecificacionesHTML($cotizacion)
    {
        $html = '
        <table class="spec-table">
            <thead>
                <tr>
                    <th>Especificación</th>
                    <th>Opciones Seleccionadas</th>
                    <th>OBSERVACIONES</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="label">DISPONIBILIDAD DE LA PRENDA</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="label">FORMA DE PAGO</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="label">RÉGIMEN</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="label">FLETE DE ENVÍO</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="label">SE HA VENDIDO</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="label">ÚLTIMA VENTA</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <th colspan="3" style="text-align: center; background: #FFC107;">Nombre asesor / Asesora Comercial</th>
                </tr>
                <tr>
                    <td colspan="3">' . ($cotizacion->asesora ?? ($cotizacion->usuario->name ?? 'N/A')) . '</td>
                </tr>
            </tbody>
        </table>';
        
        return $html;
    }
}
