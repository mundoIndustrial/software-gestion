<?php

namespace App\Application\Services\PdfDesign;

use App\Models\Cotizacion;

/**
 * LogoPdfDesign - Componente de diseño para PDF de cotizaciones de Logo
 * 
 * Responsabilidades:
 * - Generar estructura HTML del PDF de logos/bordados
 * - Manejar estilos y diseño visual
 * - Renderizar datos de prendas y tallas para logo
 * - Armar las secciones: encabezado, cliente, prendas con tallas, especificaciones
 * 
 * No es responsable de:
 * - Lógica de negocio
 * - Control de acceso
 * - Generación del PDF (eso lo hace mPDF)
 * - Manejo de memoria
 */
class LogoPdfDesign
{
    private Cotizacion $cotizacion;

    public function __construct(Cotizacion $cotizacion)
    {
        $this->cotizacion = $cotizacion;
    }

    /**
     * Genera el HTML completo del PDF de logo
     */
    public function build(): string
    {
        return $this->getDocumentStructure();
    }

    /**
     * Estructura completa del documento HTML
     */
    private function getDocumentStructure(): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>' . $this->getStyles() . '</style>
</head>
<body>';

        // Agregar secciones en orden
        $html .= $this->renderHeader();
        $html .= $this->renderClientInfo();
        $html .= $this->renderPrendas();
        $html .= $this->renderEspecificaciones();

        $html .= '</body>
</html>';

        return $html;
    }

    /**
     * Retorna todos los estilos CSS
     */
    private function getStyles(): string
    {
        return <<<'CSS'
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; margin: 0; padding: 0; height: auto; }
        body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.4; margin: 0; padding: 0; }
        
        .header-wrapper { width: 100%; margin: 0; padding: 0; margin-bottom: 0; }
        .header { text-align: center; border-bottom: 2px solid #000; padding: 15px 12mm; background: #000; color: #fff; display: flex; align-items: flex-start; gap: 15px; }
        .header-logo { width: 120px; height: auto; flex-shrink: 0; display: block; }
        .header-content { flex: 1; text-align: center; }
        .header-title { font-size: 14px; font-weight: bold; margin: 0; }
        .header-subtitle { font-size: 10px; margin: 2px 0; }
        
        .info-wrapper { width: 100%; margin: 0; padding: 0; margin-bottom: 8px; }
        .info-table { width: 100%; border-collapse: collapse; table-layout: fixed; padding: 0 12mm; }
        .info-table td { padding: 5px; border: 1px solid #000; word-wrap: break-word; }
        .info-table .label { background: #f0f0f0; font-weight: bold; }
        
        /* Estilos para prendas */
        .prendas-wrapper { padding: 12mm; }
        
        .prenda-card { border: 1px solid #000; margin-bottom: 15px; padding: 0; page-break-inside: avoid; }
        
        /* Header del card con nombre y tallas */
        .prenda-header { background: #fff; padding: 8px 10px; border-bottom: 1px solid #000; }
        .prenda-nombre { font-weight: bold; font-size: 11px; margin-bottom: 3px; color: #1e5ba8; }
        .prenda-detalles { font-size: 9px; margin-bottom: 2px; }
        .prenda-tallas { font-size: 10px; color: #e74c3c; font-weight: bold; }
        
        /* Telas bajo el nombre */
        .prenda-telas { font-size: 9px; margin-top: 4px; padding-top: 4px; border-top: 1px solid #e0e0e0; }
        .tela-item { margin-bottom: 3px; padding: 2px 0; }
        .tela-numero { font-weight: bold; color: #1e5ba8; }
        .tela-nombre { color: #333; }
        .tela-color { color: #666; font-size: 8px; }
        .tela-ref { color: #999; font-size: 8px; }
        
        /* Contenedor principal de la prenda */
        .prenda-contenido { padding: 10px; }
        
        /* Tabla de tallas */
        .tallas-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .tallas-table thead tr { background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white; }
        .tallas-table th { padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2); }
        .tallas-table td { padding: 0.75rem; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
        .tallas-table tr:nth-child(even) { background: #f9fafb; }
        
        /* Tabla de variaciones */
        .variaciones-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .variaciones-table thead tr { background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white; }
        .variaciones-table th { padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2); }
        .variaciones-table td { padding: 0.75rem; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
        .variaciones-table tr:nth-child(even) { background: #f9fafb; }
        
        /* Imágenes lado a lado */
        .imagenes-grupo { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 10px; margin-bottom: 10px; }
        .imagen-container { display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .imagen-box { border: 2px solid #1e5ba8; padding: 4px; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; background: #f9f9f9; flex-shrink: 0; border-radius: 4px; }
        .imagen-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .imagen-label { font-size: 8px; font-weight: bold; text-align: center; color: #333; width: 80px; word-wrap: break-word; }
        .imagen-placeholder { width: 80px; height: 80px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999; font-size: 8px; text-align: center; border: 2px solid #999; flex-shrink: 0; border-radius: 4px; }
        
        /* Observaciones generales */
        .observaciones-wrapper { width: 100%; border-top: 1px solid #000; padding: 10px; background: #f9f9f9; margin-top: 10px; }
        .observaciones-title { font-weight: bold; font-size: 10px; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 4px; color: #1e5ba8; }
        .observaciones-content { font-size: 9px; line-height: 1.6; }
        .observacion-item { margin-bottom: 6px; padding: 6px; border-left: 3px solid #1e5ba8; background: white; }
        
        /* Tabla de especificaciones */
        .especificaciones-wrapper { padding: 12mm; border-top: 2px solid #000; }
        .especificaciones-title { font-weight: bold; font-size: 11px; margin-bottom: 10px; color: #1e5ba8; text-transform: uppercase; }
        .especificaciones-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .especificaciones-table thead tr { background: #f5f5f5; border-bottom: 2px solid #1e5ba8; }
        .especificaciones-table th { padding: 0.75rem 1rem; text-align: left; color: #1e5ba8; font-weight: 700; font-size: 9px; }
        .especificaciones-table td { padding: 0.75rem 1rem; color: #666; font-size: 9px; border-bottom: 1px solid #eee; }
        .especificaciones-table .espec-label { color: #333; font-weight: 600; }
CSS;
    }

    /**
     * Renderiza el encabezado con logo de empresa
     */
    private function renderHeader(): string
    {
        $logoPath = public_path('images/logo3.png');
        
        return <<<HTML
        <div class="header-wrapper">
            <div class="header">
                <img src="{$logoPath}" class="header-logo" alt="Logo" style="display: block;">
                <div class="header-content">
                    <div class="header-title">Uniformes Mundo Industrial</div>
                    <div class="header-subtitle">Lenis Ruth Mahecha Acosta</div>
                    <div class="header-subtitle">NIT: 1.093.738.433-3 Régimen Común</div>
                    <div style="font-size: 12px; font-weight: bold; margin-top: 4px;">COTIZACIÓN DE LOGO</div>
                </div>
            </div>
        </div>
        HTML;
    }

    /**
     * Renderiza información del cliente
     */
    private function renderClientInfo(): string
    {
        $nombreCliente = $this->cotizacion->cliente?->nombre ?? 'N/A';
        $nombreAsesor = $this->cotizacion->usuario?->name ?? 'N/A';
        $fecha = $this->cotizacion->created_at?->format('d/m/Y') ?? 'N/A';
        $numero = $this->cotizacion->numero_cotizacion ?? 'Por asignar';

        $nombreCliente = htmlspecialchars($nombreCliente);
        $nombreAsesor = htmlspecialchars($nombreAsesor);
        $fecha = htmlspecialchars($fecha);
        $numero = htmlspecialchars($numero);

        return <<<HTML
        <div class="info-wrapper">
            <table class="info-table">
                <tr>
                    <td class="label" style="width: 12%;">NÚMERO</td>
                    <td style="color: #e74c3c; font-weight: bold; width: 18%;">{$numero}</td>
                    <td class="label" style="width: 12%;">CLIENTE</td>
                    <td style="color: #e74c3c; font-weight: bold; width: 20%;">{$nombreCliente}</td>
                    <td class="label" style="width: 12%;">ASESOR</td>
                    <td style="color: #e74c3c; font-weight: bold; width: 16%;">{$nombreAsesor}</td>
                    <td class="label" style="width: 10%;">Fecha</td>
                    <td style="color: #e74c3c; font-weight: bold; width: 10%;">{$fecha}</td>
                </tr>
            </table>
        </div>
        HTML;
    }

    /**
     * Renderiza las prendas con sus tallas
     */
    private function renderPrendas(): string
    {
        $html = '<div class="prendas-wrapper">';
        
        // Obtener prendas de la cotización
        $prendas = $this->cotizacion->prendas ?? collect();
        
        if ($prendas->isEmpty()) {
            $html .= '<p style="text-align: center; color: #999;">No hay prendas en esta cotización</p>';
            $html .= '</div>';
            return $html;
        }

        foreach ($prendas as $prenda) {
            try {
                $html .= $this->renderPrendaCard($prenda);
            } catch (\Exception $e) {
                \Log::error('Error renderizando prenda en LogoPdfDesign', [
                    'prenda_id' => $prenda->id ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                // Continuar con la siguiente prenda
                $html .= '<div class="prenda-card"><p style="color: red;">Error al procesar prenda</p></div>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Renderiza una tarjeta de prenda con sus telas y variaciones
     */
    private function renderPrendaCard($prenda): string
    {
        try {
            $nombre = htmlspecialchars($prenda->nombre_producto ?? 'Sin nombre');
            
            $logoCot = $this->cotizacion->logoCotizacion;
            if (!$logoCot) {
                return '<div class="prenda-card"><p>Sin información de logo</p></div>';
            }

            // Telas - ELIMINADO según solicitud
            $telasHtml = ''; // Ya no se muestran telas en PDF de logo

            // Tallas - obtener del primer registro técnico de la prenda (una sola vez)
            $tallasHtml = '';
            $descripcionHtml = '';
            $tecnicaPrenda = null;
            
            if (isset($logoCot->tecnicasPrendas)) {
                foreach ($logoCot->tecnicasPrendas as $t) {
                    if ($t->prenda_cot_id == $prenda->id) {
                        $tecnicaPrenda = $t;
                        break; // Solo el primero
                    }
                }

                if ($tecnicaPrenda && !empty($tecnicaPrenda->talla_cantidad)) {
                    $tallasData = [];
                    if (is_string($tecnicaPrenda->talla_cantidad)) {
                        $decoded = json_decode($tecnicaPrenda->talla_cantidad, true, 10);
                        if (is_array($decoded)) {
                            $tallasData = $decoded;
                        }
                    } elseif (is_array($tecnicaPrenda->talla_cantidad)) {
                        $tallasData = $tecnicaPrenda->talla_cantidad;
                    }

                    if (count($tallasData) > 0) {
                        $tallasArray = [];
                        foreach ($tallasData as $item) {
                            if (is_array($item) && isset($item['talla'])) {
                                $talla = htmlspecialchars($item['talla']);
                                $tallasArray[] = $talla;
                            }
                        }
                        if (!empty($tallasArray)) {
                            $tallasHtml .= '<div class="prenda-tallas" style="margin-top: 4px; color: #e74c3c; font-weight: bold;">Tallas: ' . implode(', ', $tallasArray) . '</div>';
                        }
                    }
                }
            }

            // Descripción - concatenar TODAS las ubicaciones de las técnicas de la prenda
            $descripcionHtml = '';
            $todasUbicaciones = [];
            
            if (isset($logoCot->tecnicasPrendas)) {
                foreach ($logoCot->tecnicasPrendas as $tecnica) {
                    if ($tecnica->prenda_cot_id == $prenda->id && !empty($tecnica->ubicaciones)) {
                        $ubicacionesData = [];
                        if (is_string($tecnica->ubicaciones)) {
                            $decoded = json_decode($tecnica->ubicaciones, true, 10);
                            if (is_array($decoded)) {
                                $ubicacionesData = $decoded;
                            }
                        } elseif (is_array($tecnica->ubicaciones)) {
                            $ubicacionesData = $tecnica->ubicaciones;
                        }

                        if (!empty($ubicacionesData)) {
                            foreach ($ubicacionesData as $ubicacion) {
                                if (is_array($ubicacion) && isset($ubicacion['ubicacion'])) {
                                    $ubicacionTexto = htmlspecialchars($ubicacion['ubicacion']);
                                    // Evitar duplicados
                                    if (!in_array($ubicacionTexto, $todasUbicaciones)) {
                                        $todasUbicaciones[] = $ubicacionTexto;
                                    }
                                } elseif (is_string($ubicacion)) {
                                    $ubicacionTexto = htmlspecialchars($ubicacion);
                                    // Evitar duplicados
                                    if (!in_array($ubicacionTexto, $todasUbicaciones)) {
                                        $todasUbicaciones[] = $ubicacionTexto;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            if (!empty($todasUbicaciones)) {
                // Concatenar todas las ubicaciones separadas por comas
                $descripcionHtml .= '<div class="prenda-descripcion" style="margin-top: 8px; padding: 8px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 3px; font-size: 9px; line-height: 1.4;">' . implode(', ', $todasUbicaciones) . '</div>';
            }

            // Variaciones - ELIMINADO según solicitud
            $variacionesHtml = ''; // Ya no se muestra tabla de variaciones

            // Imágenes
            $imagenesHtml = $this->renderImagenesLogo($prenda, $logoCot);

            return '<div class="prenda-card"><div class="prenda-header"><div class="prenda-nombre">' . $nombre . '</div>' . $telasHtml . $tallasHtml . $descripcionHtml . '</div><div class="prenda-contenido">' . $variacionesHtml . $imagenesHtml . '</div></div>';

        } catch (\Exception $e) {
            \Log::error('Error en renderPrendaCard', ['error' => $e->getMessage()]);
            return '<div class="prenda-card"><p>Error procesando prenda</p></div>';
        }
    }

    /**
     * Renderiza las imágenes de logo (usando base64 como en CombiadaPdfDesign)
     */
    private function renderImagenesLogo($prenda, $logoCot): string
    {
        try {
            \Log::info("Iniciando renderImagenesLogo con imágenes base64");
            
            // Agrupar imágenes por tipo
            $imagenesPorTipo = [
                'Logo' => []
            ];

            // Logo de cada técnica
            if (isset($logoCot->tecnicasPrendas)) {
                foreach ($logoCot->tecnicasPrendas as $tp) {
                    if ($tp->prenda_cot_id == $prenda->id && $tp->fotos && count($tp->fotos) > 0) {
                        foreach ($tp->fotos as $foto) {
                            if ($foto->url) {
                                $imagenesPorTipo['Logo'][] = [
                                    'url' => $foto->url,
                                    'titulo' => 'Logo - ' . ($tp->tipoLogo?->nombre ?? 'Logo')
                                ];
                            }
                        }
                    }
                }
            }

            // Verificar si hay imágenes de logo
            if (empty($imagenesPorTipo['Logo'])) {
                return '';
            }

            // Crear tabla solo para logo
            $html = '<table style="width: 100%; border-collapse: collapse; margin: 10px 0;">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="width: 100%; background: #e8eef7; font-weight: bold; padding: 8px; border: 1px solid #000; text-align: center;">Logo</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            $html .= '<tr>';
            $html .= '<td style="width: 100%; padding: 8px; border: 1px solid #000; vertical-align: middle; text-align: center;">';

            // Mostrar logo con base64
            if (!empty($imagenesPorTipo['Logo'])) {
                $img = $imagenesPorTipo['Logo'][0];
                $imagenUrl = $img['url'];

                // Convertir a URL absoluta para base64
                if (!str_starts_with($imagenUrl, 'http')) {
                    if (str_starts_with($imagenUrl, '/storage/')) {
                        $imagenUrl = asset($imagenUrl);
                    } else {
                        $imagenUrl = asset('storage/' . ltrim($imagenUrl, '/'));
                    }
                }

                \Log::info("Procesando imagen Logo: {$imagenUrl}");

                // Convertir a base64 para mPDF
                $base64Image = $this->convertImageToBase64($imagenUrl);
                
                if ($base64Image) {
                    // Detectar el tipo de imagen para el data URI
                    $imageType = 'image/jpeg'; // Default
                    if (str_ends_with(strtolower($img['url']), '.png')) {
                        $imageType = 'image/png';
                    } elseif (str_ends_with(strtolower($img['url']), '.webp')) {
                        $imageType = 'image/webp';
                    }
                    
                    $html .= '<img src="data:' . $imageType . ';base64,' . $base64Image . '" alt="' . htmlspecialchars($img['titulo']) . '" style="max-width: 100%; max-height: 120px; display: block; margin: 0 auto;">';
                } else {
                    $html .= '<div style="color: #999; font-size: 9px; padding: 10px;">Error al cargar imagen</div>';
                }
            } else {
                $html .= '<div style="color: #ccc; font-size: 9px; padding: 10px;">Sin logo</div>';
            }

            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</tbody>';
            $html .= '</table>';

            \Log::info("renderImagenesLogo completado con imágenes base64");
            return $html;

        } catch (\Exception $e) {
            \Log::error('Error en renderImagenesLogo: ' . $e->getMessage(), [
                'prenda_id' => $prenda->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return '<div style="color: #999; font-size: 9px; padding: 10px;">Error al cargar imágenes</div>';
        }
    }

    /**
     * Convierte una imagen URL a base64 (copiado de CombiadaPdfDesign)
     */
    private function convertImageToBase64($imageUrl): ?string
    {
        try {
            // Obtener ruta física del archivo
            $filePath = null;
            if (str_starts_with($imageUrl, 'http')) {
                // Es URL completa, extraer la ruta
                $parsedUrl = parse_url($imageUrl);
                if ($parsedUrl && isset($parsedUrl['path'])) {
                    $relativePath = ltrim($parsedUrl['path'], '/');
                    if (str_starts_with($relativePath, 'storage/')) {
                        $filePath = storage_path(str_replace('storage/', 'app/public/', $relativePath));
                    }
                }
            } else {
                // Es ruta relativa
                if (str_starts_with($imageUrl, '/storage/')) {
                    $filePath = public_path(ltrim($imageUrl, '/'));
                } else {
                    $filePath = public_path('storage/' . ltrim($imageUrl, '/'));
                }
            }

            if (!$filePath) {
                \Log::warning("No se pudo determinar la ruta física para la URL: {$imageUrl}");
                return null;
            }

            \Log::info("Ruta física determinada para imagen: {$filePath}");

            if (!file_exists($filePath)) {
                \Log::warning("Archivo de imagen no encontrado en la ruta: {$filePath}");
                return null;
            }

            \Log::info("Archivo encontrado, procesando conversión a base64...");

            // Leer y convertir a base64
            $imageData = file_get_contents($filePath);
            if ($imageData === false) {
                \Log::warning("No se pudo leer el archivo: {$filePath}");
                return null;
            }

            $base64 = base64_encode($imageData);
            \Log::info("Imagen convertida a base64: " . strlen($base64) . " bytes");
            
            return $base64;

        } catch (\Exception $e) {
            \Log::error('Error al convertir imagen a base64: ' . $e->getMessage());
            return null;
        }
    }
    /**
     * Renderiza tabla de especificaciones generales y observaciones
     */
    private function renderEspecificaciones(): string
    {
        $html = '';
        
        // 1. Renderizar observaciones primero
        $observacionesHtml = $this->renderObservaciones();
        if (!empty($observacionesHtml)) {
            $html .= $observacionesHtml;
        }
        
        // 2. Renderizar especificaciones generales
        $especificacionesHtml = $this->renderEspecificacionesGenerales();
        if (!empty($especificacionesHtml)) {
            $html .= $especificacionesHtml;
        }
        
        return $html;
    }
    
    /**
     * Renderiza las observaciones procesando JSON estructurado
     */
    private function renderObservaciones(): string
    {
        $logoCot = $this->cotizacion->logoCotizacion;
        $observaciones = '';

        if ($logoCot && isset($logoCot->observaciones_generales)) {
            $obs = $logoCot->observaciones_generales;
            
            if (is_array($obs)) {
                foreach ($obs as $item) {
                    if (!empty($item)) {
                        $textoMostrar = '';
                        
                        // Si es un string, verificar si es JSON con estructura
                        if (is_string($item)) {
                            // Verificar si parece un JSON array con objetos
                            if (preg_match('/^\s*\[.*\]\s*$/', $item)) {
                                $decoded = json_decode($item, true);
                                if (is_array($decoded) && !empty($decoded) && isset($decoded[0]['texto'])) {
                                    // Es JSON con estructura [{"texto":"...","tipo":"...","valor":...}]
                                    $textoMostrar = $decoded[0]['texto'];
                                } else {
                                    // Es texto plano que parece JSON pero no tiene la estructura esperada
                                    $textoMostrar = $item;
                                }
                            } else {
                                // Es texto plano
                                $textoMostrar = $item;
                            }
                        } 
                        // Si es array directamente, verificar si tiene la estructura esperada
                        elseif (is_array($item) && isset($item['texto'])) {
                            $textoMostrar = $item['texto'];
                        }
                        // Si es array pero no tiene la estructura, convertir a string
                        elseif (is_array($item)) {
                            $textoMostrar = implode(', ', $item);
                        }
                        // Si es otro tipo, convertir a string
                        else {
                            $textoMostrar = (string) $item;
                        }
                        
                        if (!empty($textoMostrar)) {
                            $observaciones .= '<div class="observacion-item">' . htmlspecialchars($textoMostrar) . '</div>';
                        }
                    }
                }
            } else if (!empty($obs)) {
                // Procesar el caso cuando no es array
                $textoMostrar = '';
                if (is_string($obs)) {
                    // Verificar si parece un JSON array con objetos
                    if (preg_match('/^\s*\[.*\]\s*$/', $obs)) {
                        $decoded = json_decode($obs, true);
                        if (is_array($decoded) && !empty($decoded) && isset($decoded[0]['texto'])) {
                            // Es JSON con estructura [{"texto":"...","tipo":"...","valor":...}]
                            $textoMostrar = $decoded[0]['texto'];
                        } else {
                            // Es texto plano que parece JSON pero no tiene la estructura esperada
                            $textoMostrar = $obs;
                        }
                    } else {
                        // Es texto plano
                        $textoMostrar = $obs;
                    }
                } else {
                    $textoMostrar = (string) $obs;
                }
                
                if (!empty($textoMostrar)) {
                    $observaciones .= '<div class="observacion-item">' . htmlspecialchars($textoMostrar) . '</div>';
                }
            }
        }

        return !empty($observaciones) ? 
            '<div class="especificaciones-wrapper">
            <div class="especificaciones-title">Observaciones</div>
            <div class="observaciones-wrapper">
                ' . $observaciones . '
            </div>
        </div>'
            : '';
    }
    
    /**
     * Renderiza tabla de especificaciones generales (misma lógica que CombiadaPdfDesign)
     */
    private function renderEspecificacionesGenerales(): string
    {
        if (!$this->cotizacion->especificaciones || empty($this->cotizacion->especificaciones)) {
            return '';
        }

        $especificacionesMap = [
            'disponibilidad' => 'DISPONIBILIDAD',
            'forma_pago' => 'FORMA DE PAGO',
            'regimen' => 'RÉGIMEN',
            'se_ha_vendido' => 'SE HA VENDIDO',
            'ultima_venta' => 'ÚLTIMA VENTA',
            'flete' => 'FLETE DE ENVÍO'
        ];

        $html = '<div class="especificaciones-wrapper">';
        $html .= '<div class="especificaciones-title">Especificaciones Generales</div>';
        $html .= '<table class="especificaciones-table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Especificación</th>';
        $html .= '<th>Valor</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($especificacionesMap as $clave => $nombreEspec) {
            $valor = $this->cotizacion->especificaciones[$clave] ?? null;
            $valorTexto = '-';

            if ($valor) {
                if (is_array($valor) && !empty($valor)) {
                    $valorTexto = implode(', ', array_map(function($v) {
                        $texto = $v['valor'] ?? '';
                        if (isset($v['observacion'])) {
                            $texto .= ' (' . $v['observacion'] . ')';
                        }
                        return htmlspecialchars($texto);
                    }, $valor));
                } elseif (is_string($valor)) {
                    $valorTexto = htmlspecialchars($valor);
                }
            }

            $html .= '<tr>';
            $html .= '<td class="espec-label">' . htmlspecialchars($nombreEspec) . '</td>';
            $html .= '<td>' . $valorTexto . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }
}
