<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Mpdf\Mpdf;
use Illuminate\Http\Request;

class PDFCotizacionController extends Controller
{
    /**
     * Genera el PDF de una cotización
     */
    public function generarPDF($cotizacionId)
    {
        try {
            $cotizacion = Cotizacion::with([
                'prendasCotizaciones',
                'usuario',
                'cliente',
                'prendas.fotos',
                'prendas.telaFotos',
                'prendas.tallas',
                'prendas.variantes'
            ])->findOrFail($cotizacionId);
            
            // Generar HTML del PDF
            $html = $this->generarHTML($cotizacion);
            
            // Crear PDF con mPDF
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 10,
                'margin_header' => 0,
                'margin_footer' => 0,
            ]);
            
            // Configurar propiedades del documento
            $mpdf->SetTitle('Cotización #' . $cotizacion->id);
            $mpdf->SetAuthor('Mundo Industrial');
            
            // Escribir HTML
            $mpdf->WriteHTML($html);
            
            // Nombre del archivo
            $filename = 'Cotizacion_' . $cotizacion->id . '_' . date('Y-m-d') . '.pdf';
            
            // Verificar si es una solicitud de descarga
            $descargar = request()->query('descargar', false);
            
            if ($descargar) {
                return response()->streamDownload(
                    function () use ($mpdf) {
                        echo $mpdf->Output('', 'S');
                    },
                    $filename,
                    ['Content-Type' => 'application/pdf']
                );
            } else {
                return response()->make(
                    $mpdf->Output('', 'S'),
                    200,
                    [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="' . $filename . '"'
                    ]
                );
            }
            
        } catch (\Exception $e) {
            \Log::error('Error en generarPDF', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
        html, body { width: 100%; margin: 0; padding: 0; height: auto; }
        body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.4; margin: 0; padding: 0; }
        .container { width: 100%; margin: 0; padding: 0; }
        .header-wrapper { width: 100%; margin: 0; padding: 0; margin-bottom: 0; }
        .header { text-align: center; border-bottom: 2px solid #000; padding: 15px 12mm; background: #000; color: #fff; display: flex; align-items: flex-start; gap: 15px; }
        .header-logo { width: 120px; height: auto; flex-shrink: 0; }
        .header-content { flex: 1; text-align: center; }
        .header-title { font-size: 14px; font-weight: bold; margin: 0; }
        .header-subtitle { font-size: 10px; margin: 2px 0; }
        .info-wrapper { width: 100%; margin: 0; padding: 0; margin-bottom: 8px; }
        .info-table { width: 100%; border-collapse: collapse; table-layout: fixed; padding: 0 12mm; }
        .info-table td { padding: 5px; border: 1px solid #000; word-wrap: break-word; }
        .info-table .label { background: #f0f0f0; font-weight: bold; }
        .content-wrapper { padding: 0 12mm; }
        .prenda { margin-bottom: 10px; page-break-inside: avoid; }
        .prenda-nombre { font-size: 11px; font-weight: bold; margin-bottom: 3px; }
        .prenda-descripcion { font-size: 8px; margin-bottom: 3px; color: #333; line-height: 1.3; word-wrap: break-word; max-width: 90%; }
        .prenda-tallas { font-size: 11px; font-weight: bold; margin-bottom: 3px; color: #e74c3c; }
        .prenda-imagenes { display: flex; gap: 15px; margin-bottom: 8px; flex-wrap: wrap; justify-content: center; }
        .prenda-imagen { width: 140px; height: 140px; border: 1px solid #ddd; object-fit: cover; }
        .spec-wrapper { width: 100%; margin: 0; padding: 0 12mm; margin-top: 4px; }
        .spec-table { width: 100%; border-collapse: collapse; table-layout: fixed; page-break-inside: avoid; }
        .spec-table th { background: #FFC107; padding: 4px 3px; border: 1px solid #000; font-weight: bold; text-align: left; font-size: 8px; }
        .spec-table td { padding: 3px; border: 1px solid #000; font-size: 7px; word-wrap: break-word; }
        .spec-table .label { background: #f9f9f9; font-weight: bold; width: 22%; }
    </style>
</head>
<body>';
        
        // Encabezado
        $html .= $this->generarEncabezadoHTML($cotizacion);
        
        // Información cliente y fecha
        $html .= $this->generarInfoClienteHTML($cotizacion);
        
        // Prendas
        $html .= '<div class="content-wrapper">';
        $html .= $this->generarPrendasHTML($cotizacion);
        $html .= '</div>';
        
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
        $logoPath = public_path('images/logo3.png');
        return '
        <div class="header-wrapper">
            <div class="header">
                <img src="' . $logoPath . '" class="header-logo" alt="Logo">
                <div class="header-content">
                    <div class="header-title">Uniformes Mundo Industrial</div>
                    <div class="header-subtitle">Leonis Ruth Mahecha Acosta</div>
                    <div class="header-subtitle">NIT: 1.093.738.433-3 Régimen Común</div>
                    <div style="font-size: 12px; font-weight: bold; margin-top: 4px;">COTIZACIÓN</div>
                </div>
            </div>
        </div>';
    }
    
    /**
     * Genera el HTML de información cliente
     */
    private function generarInfoClienteHTML($cotizacion)
    {
        $nombreCliente = 'N/A';
        if ($cotizacion->cliente) {
            $nombreCliente = $cotizacion->cliente->nombre ?? 'N/A';
        }
        
        $fecha = 'N/A';
        if ($cotizacion->created_at) {
            $fecha = $cotizacion->created_at->format('d/m/Y');
        }
        
        return '
        <div class="info-wrapper">
            <table class="info-table">
                <tr>
                    <td class="label" style="width: 20%;">CLIENTE</td>
                    <td style="color: #e74c3c; font-weight: bold; width: 30%;">' . htmlspecialchars($nombreCliente) . '</td>
                    <td class="label" style="width: 20%;">Fecha</td>
                    <td style="color: #e74c3c; font-weight: bold; width: 30%;">' . htmlspecialchars($fecha) . '</td>
                </tr>
            </table>
        </div>';
    }
    
    /**
     * Genera el HTML de prendas
     */
    private function generarPrendasHTML($cotizacion)
    {
        $html = '';
        
        // Usar la estructura DDD: prendas_cot en lugar de prendas_cotizaciones
        $prendas = $cotizacion->prendas ?? [];
        
        if (empty($prendas)) {
            return '';
        }
        
        foreach ($prendas as $prenda) {
            $html .= '<div class="prenda">';
            
            // Nombre
            $html .= '<div class="prenda-nombre">' . strtoupper($prenda->nombre_producto ?? 'N/A') . '</div>';
            
            // Descripción
            if ($prenda->descripcion) {
                $html .= '<div class="prenda-descripcion">' . htmlspecialchars($prenda->descripcion) . '</div>';
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
            
            // Imágenes - Mantener como colecciones de Eloquent
            $imagenes = $prenda->fotos ?? [];
            $imagenesTela = $prenda->telaFotos ?? [];
            
            $todasLasImagenes = [];
            // Iterar directamente sobre las colecciones de Eloquent
            foreach ($imagenes as $img) {
                $todasLasImagenes[] = $img;
            }
            foreach ($imagenesTela as $img) {
                $todasLasImagenes[] = $img;
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
                    // Extraer ruta según el tipo de objeto/dato
                    $rutaImagen = $imagen;
                    
                    // Si es un objeto, obtener la propiedad ruta_webp o ruta_original
                    if (is_object($imagen)) {
                        $rutaImagen = $imagen->ruta_webp ?? $imagen->ruta_original ?? null;
                    }
                    
                    // Si no hay ruta, saltar
                    if (!$rutaImagen) {
                        continue;
                    }
                    
                    // Convertir ruta relativa a absoluta
                    if (strpos($rutaImagen, '/') === 0 && strpos($rutaImagen, 'http') !== 0) {
                        $rutaImagen = public_path($rutaImagen);
                    }
                    
                    if (file_exists($rutaImagen)) {
                        // mPDF soporta WebP nativamente
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
     * mPDF maneja WebP nativamente, así que solo retornamos la ruta
     * 
     * @param string $rutaImagen Ruta de la imagen
     * @return string Ruta de la imagen
     */
    private function convertirWebPaJPGParaPDF($rutaImagen)
    {
        // mPDF soporta WebP nativamente, retornar la ruta tal cual
        return $rutaImagen;
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
        <div class="spec-wrapper">
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
            
            // Convertir valores a string de forma segura
            $valoresLimpios = [];
            foreach ($valores as $v) {
                if (is_array($v)) {
                    // Si es un array, convertirlo a string
                    $valoresLimpios[] = implode(', ', array_map('strval', $v));
                } elseif (!empty($v)) {
                    $valoresLimpios[] = (string)$v;
                }
            }
            $valoresText = count($valoresLimpios) > 0 ? implode(', ', $valoresLimpios) : '-';
            
            $html .= '
                    <tr>
                        <td class="label">' . $nombreCategoria . '</td>
                        <td>' . $valoresText . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
        
        return $html;
    }
}
