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
            // Tamaño personalizado: A4 con altura aumentada (297mm → 420mm)
            $pdf = Pdf::loadHTML($html)
                ->setPaper([0, 0, 595, 1190], 'portrait')  // Ancho A4 (595pt) x Altura aumentada (1190pt ≈ 420mm)
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
        html, body { width: 100%; margin: 0; padding: 0; height: auto; min-height: 100%; }
        body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.4; overflow-x: hidden; margin: 0; padding: 0; height: auto; }
        .container { width: 100%; max-width: 100%; padding: 8mm 12mm; box-sizing: border-box; margin: 0; }
        .header { text-align: center; margin-bottom: 8px; border-bottom: 2px solid #000; padding-bottom: 6px; }
        .header-logo { width: 50px; height: auto; margin-bottom: 3px; }
        .header-title { font-size: 14px; font-weight: bold; margin: 2px 0; }
        .header-subtitle { font-size: 10px; color: #666; margin: 1px 0; }
        .info-table { width: 100%; margin-bottom: 8px; border-collapse: collapse; table-layout: fixed; }
        .info-table td { padding: 5px; border: 1px solid #000; word-wrap: break-word; overflow-wrap: break-word; }
        .info-table .label { background: #f0f0f0; font-weight: bold; width: 15%; }
        table { width: 100%; }
        .prenda { margin-bottom: 10px; page-break-inside: avoid; margin-left: -25; margin-right: 0; padding-left: -25; padding-right: 0; }
        .prenda-nombre { font-size: 11px; font-weight: bold; margin-bottom: 3px; }
        .prenda-descripcion { font-size: 8px; margin-bottom: 3px; color: #333; line-height: 1.3; word-wrap: break-word; overflow-wrap: break-word; max-width: 90%; white-space: normal; }
        .prenda-tallas { font-size: 11px; font-weight: bold; margin-bottom: 3px; color: #e74c3c; }
        .prenda-imagenes { display: flex; gap: 20px; margin-bottom: 8px; flex-wrap: wrap; max-width: 100%; }
        .prenda-imagen { width: 160px; height: 160px; border: 1px solid #ddd; flex-shrink: 0; object-fit: cover; }
        .spec-table { width: 95%; border-collapse: collapse; margin-top: 4px; table-layout: fixed; margin-left: -25; margin-right: 0; padding-left: -25; padding-right: 0; page-break-inside: avoid; }
        .spec-table th { background: #FFC107; padding: 4px 3px; border: 1px solid #000; font-weight: bold; text-align: left; font-size: 8px; word-wrap: break-word; overflow-wrap: break-word; }
        .spec-table td { padding: 3px; border: 1px solid #000; font-size: 7px; word-wrap: break-word; overflow-wrap: break-word; }
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
        <div style="width: 100%; background: #000; color: #fff; padding: 15px 12px; margin: -10mm -12mm 0 -12mm; display: flex; align-items: flex-start; gap: 15px;">
            <!-- Logo a la izquierda -->
            <div style="flex-shrink: 0;">
                <img src="' . public_path('images/logo3.png') . '" style="width: 120px; height: auto;" alt="Logo">
            </div>
            
            <!-- Texto a la derecha -->
            <div style="flex: 1; text-align: center; padding-top: 0; margin-top: -45px;">
                <div style="font-size: 14px; font-weight: bold; margin: 0;">Uniformes Mundo Industrial</div>
                <div style="font-size: 10px; margin: 2px 0;">Leonis Ruth Mahecha Acosta</div>
                <div style="font-size: 10px; margin: 2px 0;">NIT: 1.093.738.433-3 Régimen Común</div>
                <div style="font-size: 12px; font-weight: bold; margin-top: 4px;">COTIZACIÓN</div>
            </div>
        </div>';
    }
    
    /**
     * Genera el HTML de información cliente
     */
    private function generarInfoClienteHTML($cotizacion)
    {
        return '
        <table class="info-table" style="margin-top: 0; margin-bottom: 12px; margin-left: -12mm; margin-right: -12mm; width: calc(100% + 24mm); padding: 0;">
            <tr>
                <td class="label" style="background: #333; color: #fff; font-weight: bold; width: 20%; padding: 6px;">CLIENTE</td>
                <td style="border: 1px solid #000; padding: 6px; color: #e74c3c; font-weight: bold; width: 30%;">' . ($cotizacion->cliente ?? 'N/A') . '</td>
                <td class="label" style="background: #333; color: #fff; font-weight: bold; width: 20%; padding: 6px;">Fecha</td>
                <td style="border: 1px solid #000; padding: 6px; color: #e74c3c; font-weight: bold; width: 30%;">' . ($cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y') : 'N/A') . '</td>
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
            
            // Obtener notas de tallas si existen
            $notasTallas = $prenda->notas_tallas ?? null;
            
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
            
            // Tallas (arriba) - Mostrar notas si existen, sino mostrar tallas base
            if ($notasTallas) {
                $html .= '<div class="prenda-tallas" style="margin-bottom: 30px;">' . $notasTallas . '</div>';
            } elseif ($tallasTexto) {
                $html .= '<div class="prenda-tallas" style="margin-bottom: 30px;">TALLAS: (' . $tallasTexto . ')</div>';
            }
            
            // Imágenes (abajo, separadas)
            if (count($todasLasImagenes) > 0) {
                $html .= '<div class="prenda-imagenes" style="margin-top: 40px;">';
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
        }
        
        return $html;
    }
    
    /**
     * Genera el HTML de la tabla de especificaciones
     */
    private function generarTablaEspecificacionesHTML($cotizacion)
    {
        // Mapeo de claves de especificaciones a nombres legibles
        $especificacionesMap = [
            'disponibilidad' => 'DISPONIBILIDAD',
            'forma_pago' => 'FORMA DE PAGO',
            'regimen' => 'RÉGIMEN',
            'se_ha_vendido' => 'SE HA VENDIDO',
            'ultima_venta' => 'ÚLTIMA VENTA',
            'flete' => 'FLETE DE ENVÍO'
        ];
        
        // Obtener especificaciones desde la tabla cotizaciones
        $especificacionesData = $cotizacion->especificaciones ?? [];
        
        // Convertir a array si es necesario
        if (!is_array($especificacionesData)) {
            $especificacionesData = (array) $especificacionesData;
        }
        
        $html = '
        <table class="spec-table">
            <thead>
                <tr>
                    <th>Especificación</th>
                    <th>Opciones Seleccionadas</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($especificacionesMap as $clave => $nombreCategoria) {
            $valores = $especificacionesData[$clave] ?? [];
            
            // Asegurar que sea un array
            if (!is_array($valores)) {
                $valores = (array) $valores;
            }
            
            // Convertir valores a string
            $valoresText = count($valores) > 0 ? implode(', ', $valores) : '-';
            
            $html .= '
                <tr>
                    <td class="label">' . $nombreCategoria . '</td>
                    <td>' . $valoresText . '</td>
                </tr>';
        }
        
        $html .= '
            </tbody>
        </table>';
        
        return $html;
    }
}
