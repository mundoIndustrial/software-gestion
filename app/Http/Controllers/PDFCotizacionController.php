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
    public function generarPDF($cotizacionId, Request $request)
    {
        try {
            $cotizacion = Cotizacion::with([
                'prendasCotizaciones',
                'usuario',
                'cliente',
                'prendas.fotos',
                'prendas.telaFotos',
                'prendas.tallas',
                'prendas.variantes',
                'prendas.variantes.manga',
                'prendas.variantes.broche',
                'prendas.telas.tela',
                'logoCotizacion.fotos'
            ])->findOrFail($cotizacionId);
            
            // Obtener el tipo de PDF solicitado (prenda o logo)
            $tipoPDF = $request->query('tipo', 'prenda');
            
            // Validar que el tipo sea válido
            if (!in_array($tipoPDF, ['prenda', 'logo'])) {
                $tipoPDF = 'prenda';
            }
            
            // Generar HTML del PDF según el tipo
            if ($tipoPDF === 'logo' && $cotizacion->logoCotizacion) {
                $html = $this->generarHTMLLogo($cotizacion);
            } else {
                $html = $this->generarHTML($cotizacion);
            }
            
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
            $mpdf->SetTitle('Cotización #' . $cotizacion->id . ' - ' . ucfirst($tipoPDF));
            $mpdf->SetAuthor('Mundo Industrial');
            
            // Escribir HTML
            $mpdf->WriteHTML($html);
            
            // Nombre del archivo
            $filename = 'Cotizacion_' . $cotizacion->id . '_' . ucfirst($tipoPDF) . '_' . date('Y-m-d') . '.pdf';
            
            // Verificar si es una solicitud de descarga
            $descargar = $request->query('descargar', false);
            
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
        .content-wrapper { padding: 0 12mm; margin-bottom: 20px; }
        .prenda { margin-bottom: 15px; page-break-inside: avoid; border: 1px solid #ddd; padding: 10px; background: #fafafa; }
        .prenda-nombre { font-size: 12px; font-weight: bold; margin-bottom: 8px; color: #000; }
        .prenda-descripcion { font-size: 9px; margin-bottom: 4px; color: #333; line-height: 1.3; word-wrap: break-word; }
        .prenda-tallas { font-size: 10px; font-weight: bold; margin: 8px 0; color: #e74c3c; }
        .prenda-imagenes { display: flex; gap: 10px; margin: 10px 0; flex-wrap: wrap; }
        .prenda-imagen { width: 120px; height: 120px; border: 1px solid #ddd; object-fit: cover; }
        .spec-wrapper { width: 100%; margin: 20px 0 0 0; padding: 15px 12mm; border-top: 2px solid #000; page-break-inside: avoid; }
        .spec-table { width: 100%; border-collapse: collapse; table-layout: auto; page-break-inside: avoid; }
        .spec-table th { background: #FFC107; padding: 8px 5px; border: 1px solid #000; font-weight: bold; text-align: left; font-size: 9px; }
        .spec-table td { padding: 6px 5px; border: 1px solid #000; font-size: 8px; word-wrap: break-word; }
        .spec-table .label { background: #f9f9f9; font-weight: bold; }
    </style>
</head>
<body>';
        
        // Encabezado
        $html .= $this->generarEncabezadoHTML($cotizacion, 'prenda');
        
        // Información cliente y fecha
        $html .= $this->generarInfoClienteHTML($cotizacion);
        
        // Sección: Por favor para Cotizar con Tipo de Venta
        $html .= $this->generarSeccionCotizarHTML($cotizacion);
        
        // Prendas
        $html .= '<div class="content-wrapper">';
        $html .= $this->generarPrendasHTML($cotizacion);
        $html .= '</div>';
        
        // Tabla de especificaciones
        $html .= $this->generarTablaEspecificacionesHTML($cotizacion);
        
        $html .= '
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Genera el HTML del PDF de LOGO
     */
    private function generarHTMLLogo($cotizacion)
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
        .content-wrapper { padding: 0 12mm; margin-bottom: 20px; }
        .logo-section { margin-bottom: 15px; page-break-inside: avoid; border: 1px solid #ddd; padding: 10px; background: #fafafa; }
        .logo-titulo { font-size: 12px; font-weight: bold; margin-bottom: 8px; color: #000; }
        .logo-descripcion { font-size: 9px; margin-bottom: 8px; color: #333; line-height: 1.3; word-wrap: break-word; }
        .logo-imagenes { display: flex; gap: 15px; margin: 10px 0; flex-wrap: wrap; }
        .logo-imagen { width: 140px; height: 140px; border: 1px solid #ddd; object-fit: contain; background: #fff; }
        .logo-info { font-size: 8px; line-height: 1.4; margin-bottom: 8px; }
        .logo-info-item { margin-bottom: 6px; }
        .logo-info-label { font-weight: bold; color: #333; }
        .spec-wrapper { width: 100%; margin: 20px 12mm 0 12mm; padding: 0; padding-top: 15px; border-top: 2px solid #000; page-break-inside: avoid; }
        .spec-table { width: 100%; border-collapse: collapse; table-layout: auto; page-break-inside: avoid; }
        .spec-table th { background: #FFC107; padding: 8px 5px; border: 1px solid #000; font-weight: bold; text-align: left; font-size: 9px; }
        .spec-table td { padding: 6px 5px; border: 1px solid #000; font-size: 8px; word-wrap: break-word; }
        .spec-table .label { background: #f9f9f9; font-weight: bold; }
    </style>
</head>
<body>';
        
        // Encabezado
        $html .= $this->generarEncabezadoHTML($cotizacion, 'logo');
        
        // Información cliente y fecha
        $html .= $this->generarInfoClienteHTML($cotizacion);
        
        // Sección de Logo
        $html .= '<div class="content-wrapper">';
        $html .= $this->generarLogoHTML($cotizacion);
        $html .= '</div>';
        
        $html .= '
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Genera el HTML de la sección de logo
     */
    private function generarLogoHTML($cotizacion)
    {
        $html = '';
        
        if (!$cotizacion->logoCotizacion) {
            return '<div class="logo-section"><p style="color: #999; margin: 20px 0;">No hay información de logo/bordado</p></div>';
        }
        
        $logo = $cotizacion->logoCotizacion;
        
        $html .= '<div class="logo-section">';
        
        // TIPO PARA COTIZAR
        if ($logo->tipo_venta) {
            $html .= '<div style="margin-bottom: 12px; padding: 8px; background: #fffacd; border-left: 4px solid #FFC107; border-radius: 2px;">
                <div style="font-size: 9px; color: #666;">Por favor para Cotizar:</div>
                <div style="font-size: 11px; color: #333;"><strong>Tipo:</strong> ' . htmlspecialchars($logo->tipo_venta) . '</div>
            </div>';
        }
        
        // IMÁGENES DE LOGO - AL PRINCIPIO
        if ($logo->fotos && count($logo->fotos) > 0) {
            $html .= '<div style="margin-bottom: 15px;">
                <div style="display: flex; gap: 15px; flex-wrap: wrap; justify-content: center;">';
            
            foreach ($logo->fotos as $imagen) {
                $rutaImagen = $imagen->ruta_webp ?? $imagen->ruta_original ?? null;
                
                if ($rutaImagen) {
                    // Verificar si es una URL completa (http/https)
                    if (strpos($rutaImagen, 'http') === 0) {
                        // Es una URL web, usarla directamente
                        $html .= '<img src="' . htmlspecialchars($rutaImagen) . '" alt="Logo" style="width: 140px; height: 140px; border: 1px solid #ccc; object-fit: contain; background: #fff; padding: 5px;">';
                    } else {
                        // Es una ruta local, asegurar que tenga /storage/
                        if (!str_starts_with($rutaImagen, '/')) {
                            $rutaImagen = '/' . $rutaImagen;
                        }
                        if (!str_starts_with($rutaImagen, '/storage/')) {
                            if (str_starts_with($rutaImagen, '/cotizaciones/')) {
                                $rutaImagen = '/storage' . $rutaImagen;
                            }
                        }
                        
                        $rutaAbsoluta = public_path($rutaImagen);
                        
                        if (file_exists($rutaAbsoluta)) {
                            $html .= '<img src="' . $rutaAbsoluta . '" alt="Logo" style="width: 140px; height: 140px; border: 1px solid #ccc; object-fit: contain; background: #fff; padding: 5px;">';
                        }
                    }
                }
            }
            
            $html .= '</div></div>';
        }
        
        // DESCRIPCIÓN
        if ($logo->descripcion) {
            $html .= '<div style="font-size: 10px; margin-bottom: 10px; color: #333;">
                <strong>DESCRIPCIÓN:</strong><br>' . htmlspecialchars($logo->descripcion) . '</div>';
        }
        
        // TABLA DE TÉCNICAS Y OBSERVACIONES
        $tecnicas = [];
        if ($logo->tecnicas) {
            $tecnicasData = is_string($logo->tecnicas) ? json_decode($logo->tecnicas, true) : $logo->tecnicas;
            if (is_array($tecnicasData)) {
                $tecnicas = $tecnicasData;
            }
        }
        
        if (count($tecnicas) > 0 || $logo->observaciones_tecnicas) {
            $html .= '<div style="margin-bottom: 12px;">
                <div style="font-size: 10px; font-weight: bold; margin-bottom: 6px;">TÉCNICAS Y OBSERVACIONES</div>
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #999;">
                    <tr style="background: #f5f5f5; border: 1px solid #999;">
                        <th style="padding: 6px; text-align: left; font-size: 9px; font-weight: bold; border: 1px solid #999;">Técnica</th>
                        <th style="padding: 6px; text-align: left; font-size: 9px; font-weight: bold; border: 1px solid #999;">Observación</th>
                    </tr>';
            
            if (count($tecnicas) > 0) {
                foreach ($tecnicas as $tecnica) {
                    $html .= '<tr style="border: 1px solid #999;">
                            <td style="padding: 6px; font-size: 9px; border: 1px solid #999;">' . htmlspecialchars($tecnica) . '</td>
                            <td style="padding: 6px; font-size: 9px; border: 1px solid #999;">' . 
                                htmlspecialchars($logo->observaciones_tecnicas ?? '') . '</td>
                        </tr>';
                }
            } else {
                $html .= '<tr style="border: 1px solid #999;">
                        <td style="padding: 6px; font-size: 9px; border: 1px solid #999;"></td>
                        <td style="padding: 6px; font-size: 9px; border: 1px solid #999;">' . 
                            htmlspecialchars($logo->observaciones_tecnicas ?? '') . '</td>
                    </tr>';
            }
            
            $html .= '</table></div>';
        }
        
        // TABLA DE UBICACIONES
        $ubicaciones = [];
        if ($logo->ubicaciones) {
            $ubicacionesData = is_string($logo->ubicaciones) ? json_decode($logo->ubicaciones, true) : $logo->ubicaciones;
            if (is_array($ubicacionesData)) {
                $ubicaciones = $ubicacionesData;
            }
        }
        
        if (count($ubicaciones) > 0) {
            $html .= '<div style="margin-bottom: 12px;">
                <div style="font-size: 10px; font-weight: bold; margin-bottom: 6px;">UBICACIONES</div>
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #999;">
                    <tr style="background: #f5f5f5; border: 1px solid #999;">
                        <th style="padding: 6px; text-align: left; font-size: 9px; font-weight: bold; border: 1px solid #999; width: 20%;">Sección</th>
                        <th style="padding: 6px; text-align: left; font-size: 9px; font-weight: bold; border: 1px solid #999; width: 50%;">Ubicaciones Seleccionadas</th>
                        <th style="padding: 6px; text-align: left; font-size: 9px; font-weight: bold; border: 1px solid #999; width: 30%;">Observaciones</th>
                    </tr>';
            
            foreach ($ubicaciones as $ub) {
                if (is_array($ub)) {
                    $seccion = $ub['seccion'] ?? '';
                    $seleccionadas = $ub['ubicaciones_seleccionadas'] ?? [];
                    $obs = $ub['observaciones'] ?? '';
                    
                    if ($seccion) {
                        // Formatear ubicaciones como bullets
                        $ubicacionesTexto = '';
                        if (is_array($seleccionadas) && count($seleccionadas) > 0) {
                            foreach ($seleccionadas as $sel) {
                                $ubicacionesTexto .= '• ' . htmlspecialchars($sel) . '<br>';
                            }
                        }
                        
                        $html .= '<tr style="border: 1px solid #999;">
                                <td style="padding: 6px; font-size: 9px; border: 1px solid #999; font-weight: bold;">' . htmlspecialchars($seccion) . '</td>
                                <td style="padding: 6px; font-size: 9px; border: 1px solid #999;">' . $ubicacionesTexto . '</td>
                                <td style="padding: 6px; font-size: 9px; border: 1px solid #999;">' . htmlspecialchars($obs) . '</td>
                            </tr>';
                    }
                }
            }
            
            $html .= '</table></div>';
        }
        
        // OBSERVACIONES GENERALES
        $obsGenerales = $logo->observaciones_generales ?? [];
        if (!is_array($obsGenerales)) {
            $obsGenerales = is_string($obsGenerales) ? json_decode($obsGenerales, true) : [];
        }
        
        if (is_array($obsGenerales) && count($obsGenerales) > 0) {
            $html .= '<div style="margin-bottom: 12px;">
                <div style="font-size: 10px; font-weight: bold; margin-bottom: 6px;">OBSERVACIONES GENERALES</div>
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #999;">
                    <thead>
                        <tr style="background: #FFE082; border: 1px solid #999;">
                            <th style="padding: 6px; text-align: left; font-size: 9px; font-weight: bold; border: 1px solid #999; width: 25%;">Observación</th>
                            <th style="padding: 6px; text-align: center; font-size: 9px; font-weight: bold; border: 1px solid #999; width: 15%;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($obsGenerales as $obs) {
                $texto = '';
                $tipo = 'texto';
                $valor = '';
                
                if (is_array($obs)) {
                    $texto = $obs['texto'] ?? $obs['seccion'] ?? '';
                    $tipo = $obs['tipo'] ?? 'texto';
                    $valor = $obs['valor'] ?? '';
                } else {
                    $texto = $obs;
                }
                
                if ($texto) {
                    $html .= '<tr style="border: 1px solid #999;">
                            <td style="padding: 6px; font-size: 9px; border: 1px solid #999;">' . htmlspecialchars($texto) . '</td>
                            <td style="padding: 6px; font-size: 9px; border: 1px solid #999; text-align: center;">';
                    
                    if ($tipo === 'checkbox') {
                        $html .= '<span style="color: #2e7d32; font-weight: 600;">✓</span>';
                    } elseif (!empty($valor)) {
                        $html .= '<span style="background: #f5f5f5; padding: 4px 8px; border-radius: 3px; font-size: 8px; font-weight: 600;">' . htmlspecialchars($valor) . '</span>';
                    } else {
                        $html .= '<span style="color: #999;">-</span>';
                    }
                    
                    $html .= '</td>
                        </tr>';
                }
            }
            
            $html .= '</tbody></table></div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Genera el HTML del encabezado
     */
    private function generarEncabezadoHTML($cotizacion, $tipo = 'prenda')
    {
        $logoPath = public_path('images/logo3.png');
        
        // Determinar el título según el tipo
        $titulo = 'COTIZACIÓN';
        if ($tipo === 'logo') {
            $titulo = 'COTIZACIÓN BORDADOS Y ESTAMPADOS';
        }
        
        return '
        <div class="header-wrapper">
            <div class="header">
                <img src="' . $logoPath . '" class="header-logo" alt="Logo">
                <div class="header-content">
                    <div class="header-title">Uniformes Mundo Industrial</div>
                    <div class="header-subtitle">Leonis Ruth Mahecha Acosta</div>
                    <div class="header-subtitle">NIT: 1.093.738.433-3 Régimen Común</div>
                    <div style="font-size: 12px; font-weight: bold; margin-top: 4px;">' . $titulo . '</div>
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
        
        $nombreAsesor = 'N/A';
        if ($cotizacion->usuario) {
            $nombreAsesor = $cotizacion->usuario->name ?? 'N/A';
        }
        
        $fecha = 'N/A';
        if ($cotizacion->created_at) {
            $fecha = $cotizacion->created_at->format('d/m/Y');
        }
        
        return '
        <div class="info-wrapper">
            <table class="info-table">
                <tr>
                    <td class="label" style="width: 15%;">CLIENTE</td>
                    <td style="color: #e74c3c; font-weight: bold; width: 25%;">' . htmlspecialchars($nombreCliente) . '</td>
                    <td class="label" style="width: 15%;">ASESOR</td>
                    <td style="color: #e74c3c; font-weight: bold; width: 25%;">' . htmlspecialchars($nombreAsesor) . '</td>
                    <td class="label" style="width: 10%;">Fecha</td>
                    <td style="color: #e74c3c; font-weight: bold; width: 10%;">' . htmlspecialchars($fecha) . '</td>
                </tr>
            </table>
        </div>';
    }
    
    /**
     * Genera la sección "Por favor para Cotizar" con el tipo de venta
     */
    private function generarSeccionCotizarHTML($cotizacion)
    {
        $tipoVenta = $cotizacion->tipo_venta ?? 'N/A';
        
        return '<div style="padding: 0 12mm; margin: 12px 0 15px 0; background: #f5f5f5; border-left: 4px solid #000; padding-left: 15px; padding-right: 15px; padding-top: 10px; padding-bottom: 10px;">
            <div style="font-size: 11px; font-weight: bold; color: #000; margin-bottom: 6px;">Por favor para Cotizar:</div>
            <div style="font-size: 12px; font-weight: bold; color: #333;">
                Tipo: <span style="color: #e74c3c;">' . htmlspecialchars($tipoVenta) . '</span>
            </div>
        </div>';
    }
    
    /**
     * Genera el HTML de prendas
     */
    private function generarPrendasHTML($cotizacion)
    {
        $html = '';
        
        // Usar la estructura DDD: prendas_cot con eager loading de relaciones
        $prendas = $cotizacion->prendas()->with(['telas.tela', 'variantes.manga', 'variantes.broche', 'tallas', 'fotos', 'telaFotos'])->get() ?? [];
        
        if (empty($prendas)) {
            return '';
        }
        
        foreach ($prendas as $index => $prenda) {
            $html .= '<div class="prenda" style="page-break-inside: avoid; margin-bottom: 25px; border: 1px solid #ddd; padding: 12px; background: #fafafa;">';
            
            // LÍNEA 1: PRENDA N: NOMBRE
            $html .= '<div style="font-size: 11px; font-weight: bold; margin-bottom: 6px;">PRENDA ' . ($index + 1) . ': ' . strtoupper($prenda->nombre_producto ?? 'N/A') . '</div>';
            
            // Cargar variantes si existen
            $variantes = $prenda->variantes ?? [];
            $colorTelaManga = '';
            
            if ($variantes && count($variantes) > 0) {
                $variante = $variantes[0]; // Usar primera variante
                
                // LÍNEA 2: Color | Tela REF: | Manga
                $partes = [];
                
                // Color
                if ($variante->color) {
                    $partes[] = 'Color: ' . htmlspecialchars($variante->color);
                }
                
                // Telas
                $telas = $prenda->telas ?? [];
                if ($telas && count($telas) > 0) {
                    $telasTexto = [];
                    foreach ($telas as $telaPrenda) {
                        $textoTela = '';
                        
                        // Obtener nombre de tela a través de la relación
                        if ($telaPrenda->tela) {
                            $textoTela = htmlspecialchars($telaPrenda->tela->nombre ?? '');
                            
                            // Agregar referencia si existe
                            if ($telaPrenda->tela->referencia) {
                                $textoTela .= ' REF:' . htmlspecialchars($telaPrenda->tela->referencia);
                            }
                        } elseif ($telaPrenda->nombre_tela) {
                            // Fallback a campo directo si existe
                            $textoTela = htmlspecialchars($telaPrenda->nombre_tela);
                            if ($telaPrenda->referencia_tela) {
                                $textoTela .= ' REF:' . htmlspecialchars($telaPrenda->referencia_tela);
                            }
                        }
                        
                        if ($textoTela) {
                            $telasTexto[] = $textoTela;
                        }
                    }
                    if (count($telasTexto) > 0) {
                        $partes[] = 'Tela: ' . implode(', ', $telasTexto);
                    }
                }
                
                // Manga
                if ($variante->tipo_manga_id) {
                    $tipomanga = $variante->manga ? $variante->manga->nombre : 'Manga desconocida';
                    $partes[] = 'Manga: ' . htmlspecialchars($tipomanga);
                }
                
                if (count($partes) > 0) {
                    $colorTelaManga = implode(' | ', $partes);
                    $html .= '<div style="font-size: 10px; margin-bottom: 6px; color: #333;">' . $colorTelaManga . '</div>';
                }
            }
            
            // LÍNEA 3: DESCRIPCION
            if ($prenda->descripcion) {
                $html .= '<div style="font-size: 10px; margin-bottom: 6px; color: #333;"><strong>DESCRIPCION:</strong> ' . htmlspecialchars($prenda->descripcion) . '</div>';
            }
            
            // LÍNEAS CON PUNTOS: Reflectivo, Bolsillos, etc
            if ($variantes && count($variantes) > 0) {
                $variante = $variantes[0];
                
                // Reflectivo
                if ($variante->tiene_reflectivo && $variante->obs_reflectivo) {
                    $html .= '<div style="font-size: 10px; margin: 4px 0 4px 20px; color: #333;">
                        <strong>.</strong> <strong>Reflectivo:</strong> ' . htmlspecialchars($variante->obs_reflectivo) . '</div>';
                }
                
                // Bolsillos
                if ($variante->tiene_bolsillos && $variante->obs_bolsillos) {
                    $html .= '<div style="font-size: 10px; margin: 4px 0 4px 20px; color: #333;">
                        <strong>.</strong> <strong>Bolsillos:</strong> ' . htmlspecialchars($variante->obs_bolsillos) . '</div>';
                }
                
                // Manga - Observaciones
                if ($variante->aplica_manga && $variante->obs_manga) {
                    $html .= '<div style="font-size: 10px; margin: 4px 0 4px 20px; color: #333;">
                        <strong>.</strong> <strong>Manga:</strong> ' . htmlspecialchars($variante->obs_manga) . '</div>';
                }
                
                // Broche/Botón
                if ($variante->tipo_broche_id && $variante->obs_broche) {
                    $nombreBroche = $variante->broche ? $variante->broche->nombre : 'Botón';
                    $html .= '<div style="font-size: 10px; margin: 4px 0 4px 20px; color: #333;">
                        <strong>.</strong> <strong>' . htmlspecialchars($nombreBroche) . ':</strong> ' . htmlspecialchars($variante->obs_broche) . '</div>';
                }
            }
            
            // LÍNEA: TALLAS
            $tallas = $prenda->tallas ?? [];
            $tallasInfo = [];
            
            if ($tallas && count($tallas) > 0) {
                foreach ($tallas as $talla) {
                    // Solo mostrar nombres de tallas que tienen cantidad > 0
                    $cantidad = $talla->cantidad ?? 0;
                    if ($cantidad > 0 || $cantidad === null) {
                        // Si cantidad es null, es porque no se especificó, así que mostramos la talla
                        $tallasInfo[] = $talla->talla;
                    }
                }
            }
            
            if (count($tallasInfo) > 0) {
                $html .= '<div style="font-size: 10px; font-weight: bold; margin-top: 8px; margin-bottom: 12px; color: #e74c3c;">Tallas: ' . implode(', ', $tallasInfo) . '</div>';
            }
            
            // IMÁGENES: Prenda y Tela - TODAS EN UNA SOLA LÍNEA
            $imagenesPrenda = $prenda->fotos ?? [];
            $imagenesTela = $prenda->telaFotos ?? [];
            
            \Log::info('PDF: Procesando imágenes de prenda', [
                'prenda_id' => $prenda->id,
                'prenda_nombre' => $prenda->nombre_producto,
                'total_fotos_prenda' => count($imagenesPrenda),
                'total_fotos_tela' => count($imagenesTela)
            ]);
            
            // Mostrar TODAS las imágenes en un solo flex container sin wrap
            if (($imagenesPrenda && count($imagenesPrenda) > 0) || ($imagenesTela && count($imagenesTela) > 0)) {
                $html .= '<div style="display: flex; gap: 8px; margin-bottom: 10px; flex-wrap: nowrap; justify-content: center; overflow-x: auto;">';
                
                // Mostrar imágenes de prenda
                foreach ($imagenesPrenda as $idx => $imagen) {
                    $rutaImagen = $imagen->ruta_webp ?? $imagen->ruta_original ?? null;
                    
                    \Log::info('PDF: Procesando foto de prenda', [
                        'prenda_id' => $prenda->id,
                        'foto_idx' => $idx,
                        'ruta_webp' => $imagen->ruta_webp,
                        'ruta_original' => $imagen->ruta_original,
                        'ruta_final' => $rutaImagen
                    ]);
                    
                    if ($rutaImagen) {
                        // Verificar si es una URL completa (http/https)
                        if (strpos($rutaImagen, 'http') === 0) {
                            // Es una URL web, usarla directamente
                            \Log::info('PDF: Foto es URL web', ['url' => $rutaImagen]);
                            $html .= '<img src="' . htmlspecialchars($rutaImagen) . '" alt="Prenda" style="width: 100px; height: 100px; border: 1px solid #ccc; object-fit: cover; flex-shrink: 0;">';
                        } else {
                            // Es una ruta local, asegurar que tenga /storage/
                            if (!str_starts_with($rutaImagen, '/')) {
                                $rutaImagen = '/' . $rutaImagen;
                            }
                            if (!str_starts_with($rutaImagen, '/storage/')) {
                                if (str_starts_with($rutaImagen, '/cotizaciones/')) {
                                    $rutaImagen = '/storage' . $rutaImagen;
                                }
                            }
                            
                            $rutaAbsoluta = $rutaImagen;
                            if (str_starts_with($rutaImagen, '/')) {
                                $rutaAbsoluta = public_path($rutaImagen);
                            }
                            
                            $existe = file_exists($rutaAbsoluta);
                            \Log::info('PDF: Foto local', [
                                'ruta_original_recibida' => $imagen->ruta_webp ?? $imagen->ruta_original,
                                'ruta_procesada' => $rutaImagen,
                                'ruta_absoluta' => $rutaAbsoluta,
                                'existe' => $existe,
                                'es_archivo' => is_file($rutaAbsoluta),
                                'es_readable' => is_readable($rutaAbsoluta)
                            ]);
                            
                            if ($existe) {
                                $html .= '<img src="' . $rutaAbsoluta . '" alt="Prenda" style="width: 100px; height: 100px; border: 1px solid #ccc; object-fit: cover; flex-shrink: 0;">';
                            }
                        }
                    } else {
                        \Log::warning('PDF: Foto sin ruta', ['prenda_id' => $prenda->id, 'foto_idx' => $idx]);
                    }
                }
                
                // Mostrar imágenes de tela
                foreach ($imagenesTela as $idx => $imagen) {
                    $rutaImagen = $imagen->ruta_webp ?? $imagen->ruta_original ?? null;
                    
                    \Log::info('PDF: Procesando foto de tela', [
                        'prenda_id' => $prenda->id,
                        'tela_foto_idx' => $idx,
                        'ruta_webp' => $imagen->ruta_webp,
                        'ruta_original' => $imagen->ruta_original,
                        'ruta_final' => $rutaImagen
                    ]);
                    
                    if ($rutaImagen) {
                        // Verificar si es una URL completa (http/https)
                        if (strpos($rutaImagen, 'http') === 0) {
                            // Es una URL web, usarla directamente
                            \Log::info('PDF: Foto de tela es URL web', ['url' => $rutaImagen]);
                            $html .= '<img src="' . htmlspecialchars($rutaImagen) . '" alt="Tela" style="width: 100px; height: 100px; border: 1px solid #ccc; object-fit: cover; flex-shrink: 0;">';
                        } else {
                            // Es una ruta local, asegurar que tenga /storage/
                            if (!str_starts_with($rutaImagen, '/')) {
                                $rutaImagen = '/' . $rutaImagen;
                            }
                            if (!str_starts_with($rutaImagen, '/storage/')) {
                                if (str_starts_with($rutaImagen, '/cotizaciones/')) {
                                    $rutaImagen = '/storage' . $rutaImagen;
                                }
                            }
                            
                            $rutaAbsoluta = $rutaImagen;
                            if (str_starts_with($rutaImagen, '/')) {
                                $rutaAbsoluta = public_path($rutaImagen);
                            }
                            
                            $existe = file_exists($rutaAbsoluta);
                            \Log::info('PDF: Foto de tela local', [
                                'ruta_original_recibida' => $imagen->ruta_webp ?? $imagen->ruta_original,
                                'ruta_procesada' => $rutaImagen,
                                'ruta_absoluta' => $rutaAbsoluta,
                                'existe' => $existe,
                                'es_archivo' => is_file($rutaAbsoluta),
                                'es_readable' => is_readable($rutaAbsoluta)
                            ]);
                            
                            if ($existe) {
                                $html .= '<img src="' . $rutaAbsoluta . '" alt="Tela" style="width: 100px; height: 100px; border: 1px solid #ccc; object-fit: cover; flex-shrink: 0;">';
                            }
                        }
                    } else {
                        \Log::warning('PDF: Foto de tela sin ruta', ['prenda_id' => $prenda->id, 'tela_foto_idx' => $idx]);
                    }
                }
                
                $html .= '</div>';
            } else {
                \Log::info('PDF: Prenda sin fotos', ['prenda_id' => $prenda->id, 'nombre' => $prenda->nombre_producto]);
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
        // Obtener especificaciones desde la tabla cotizaciones
        $especificacionesData = $cotizacion->especificaciones ?? [];
        
        // Convertir a array si es string JSON
        if (is_string($especificacionesData)) {
            $especificacionesData = json_decode($especificacionesData, true) ?? [];
        }
        
        if (!is_array($especificacionesData) || empty($especificacionesData)) {
            return '';
        }
        
        // Mapeo de categorías
        $categoriasInfo = [
            'disponibilidad' => 'DISPONIBILIDAD',
            'forma_pago' => 'FORMA DE PAGO',
            'regimen' => 'RÉGIMEN',
            'se_ha_vendido' => 'SE HA VENDIDO',
            'ultima_venta' => 'ÚLTIMA VENTA',
            'flete' => 'FLETE DE ENVÍO'
        ];
        
        $html = '<div class="spec-wrapper" style="page-break-inside: avoid; margin: 8px 0 0 0; padding: 8px 12mm; border-top: 2px solid #FFE082;">
            <div style="font-size: 10px; font-weight: bold; margin-bottom: 6px; color: #1e293b; padding-bottom: 3px;">Especificaciones Generales</div>
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; font-size: 7.5px;">
                <thead>
                    <tr style="background: #FFE082; border: 1px solid #FFE082;">
                        <th style="padding: 3px 3px; text-align: left; font-weight: bold; font-size: 7.5px; border: 1px solid #FFE082; width: 35%; color: #000;">CATEGORÍA</th>
                        <th style="padding: 3px 3px; text-align: center; font-weight: bold; font-size: 7.5px; border: 1px solid #FFE082; width: 10%; color: #000;">ESTADO</th>
                        <th style="padding: 3px 3px; text-align: left; font-weight: bold; font-size: 7.5px; border: 1px solid #FFE082; width: 55%; color: #000;">OBSERVACIONES</th>
                    </tr>
                </thead>
                <tbody>';
        
        // Procesar especificaciones por categoría
        foreach ($categoriasInfo as $categoriaKey => $categoriaNombre) {
            if (isset($especificacionesData[$categoriaKey]) && !empty($especificacionesData[$categoriaKey])) {
                $valores = $especificacionesData[$categoriaKey];
                
                // Asegurar que es array
                if (!is_array($valores)) {
                    $valores = [$valores];
                }
                
                // Agregar encabezado de categoría
                $html .= '<tr style="border: 1px solid #ddd; background: #FFE082;">
                        <td colspan="3" style="padding: 3px 3px; font-size: 7.5px; font-weight: 600; color: #000; border: 1px solid #FFE082;">' . htmlspecialchars($categoriaNombre) . '</td>
                    </tr>';
                
                // Agregar valores dentro de la categoría
                foreach ($valores as $item) {
                    $valor = '';
                    $observacion = '';
                    
                    if (is_array($item)) {
                        $valor = $item['valor'] ?? '';
                        $observacion = $item['observacion'] ?? '';
                    } else {
                        $valor = $item;
                    }
                    
                    $html .= '<tr style="border: 1px solid #ddd; background: #f9f9f9;">
                            <td style="padding: 2px 3px; font-size: 7px; border: 1px solid #ddd; color: #333; font-weight: 500; line-height: 1.1;">' . htmlspecialchars($valor) . '</td>
                            <td style="padding: 2px 3px; text-align: center; font-weight: 700; color: #28a745; font-size: 9px; border: 1px solid #ddd;">✓</td>
                            <td style="padding: 2px 3px; font-size: 7px; border: 1px solid #ddd; color: #555; line-height: 1.1;">' . htmlspecialchars($observacion) . '</td>
                        </tr>';
                }
            }
        }
        
        $html .= '</tbody>
            </table>
        </div>';
        
        return $html;
    }
}
