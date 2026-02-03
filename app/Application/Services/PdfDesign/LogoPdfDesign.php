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

            // Telas
            $telasHtml = '';
            if (isset($logoCot->telasPrendas)) {
                $telasFiltradas = [];
                foreach ($logoCot->telasPrendas as $t) {
                    if ($t->prenda_cot_id == $prenda->id) {
                        $telasFiltradas[] = $t;
                    }
                }

                if (count($telasFiltradas) > 0) {
                    $telasHtml .= '<div class="prenda-telas">';
                    $contador = 1;
                    foreach ($telasFiltradas as $tela) {
                        $telaDesc = htmlspecialchars($tela->tela ?? '');
                        $color = htmlspecialchars($tela->color ?? '');
                        $ref = htmlspecialchars($tela->ref ?? '');
                        
                        $telasHtml .= '<div class="tela-item">' . $contador . '. TELA: ' . $telaDesc;
                        if ($color) {
                            $telasHtml .= ' | COLOR: ' . $color;
                        }
                        if ($ref) {
                            $telasHtml .= ' | REF: ' . $ref;
                        }
                        $telasHtml .= '</div>';
                        $contador++;
                    }
                    $telasHtml .= '</div>';
                }
            }

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

            // Descripción - concatenar ubicaciones del logo
            if ($tecnicaPrenda && !empty($tecnicaPrenda->ubicaciones)) {
                $ubicacionesData = [];
                if (is_string($tecnicaPrenda->ubicaciones)) {
                    $decoded = json_decode($tecnicaPrenda->ubicaciones, true, 10);
                    if (is_array($decoded)) {
                        $ubicacionesData = $decoded;
                    }
                } elseif (is_array($tecnicaPrenda->ubicaciones)) {
                    $ubicacionesData = $tecnicaPrenda->ubicaciones;
                }

                if (!empty($ubicacionesData)) {
                    $ubicacionesTexto = [];
                    foreach ($ubicacionesData as $ubicacion) {
                        if (is_array($ubicacion) && isset($ubicacion['ubicacion'])) {
                            $ubicacionesTexto[] = htmlspecialchars($ubicacion['ubicacion']);
                        } elseif (is_string($ubicacion)) {
                            $ubicacionesTexto[] = htmlspecialchars($ubicacion);
                        }
                    }
                    
                    if (!empty($ubicacionesTexto)) {
                        $descripcionHtml .= '<div class="prenda-descripcion" style="margin-top: 8px; padding: 8px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 3px; font-size: 9px; line-height: 1.4;">' . implode(', ', $ubicacionesTexto) . '</div>';
                    }
                }
            }

            // Variaciones - usar solo el primer registro técnico de la prenda
            $variacionesHtml = '';
            if (isset($logoCot->tecnicasPrendas)) {
                $tecnica = null;
                foreach ($logoCot->tecnicasPrendas as $t) {
                    if ($t->prenda_cot_id == $prenda->id) {
                        $tecnica = $t;
                        break; // Solo el primero
                    }
                }

                if ($tecnica && !empty($tecnica->variaciones_prenda)) {
                    $variacionesHtml .= '<table class="variaciones-table"><thead><tr><th>Variación</th><th>Opción</th><th>Observación</th></tr></thead><tbody>';
                    
                    $variacionesData = [];
                    if (is_string($tecnica->variaciones_prenda)) {
                        $decoded = json_decode($tecnica->variaciones_prenda, true, 10);
                        if (is_array($decoded)) {
                            $variacionesData = $decoded;
                        }
                    } elseif (is_array($tecnica->variaciones_prenda)) {
                        $variacionesData = $tecnica->variaciones_prenda;
                    }

                    $contador = 0;
                    foreach ($variacionesData as $nombreVar => $varData) {
                        if ($contador > 50) break;
                        if (!is_array($varData)) continue;
                        
                        $nombreVar = htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$nombreVar)));
                        $opcion = htmlspecialchars((string)($varData['opcion'] ?? ''));
                        $obs = htmlspecialchars((string)($varData['observacion'] ?? ''));
                        
                        $variacionesHtml .= '<tr><td>' . $nombreVar . '</td><td>' . $opcion . '</td><td>' . $obs . '</td></tr>';
                        $contador++;
                    }
                    
                    $variacionesHtml .= '</tbody></table>';
                }
            }

            // Imágenes
            $imagenesHtml = $this->renderImagenesLogo($prenda, $logoCot);

            return '<div class="prenda-card"><div class="prenda-header"><div class="prenda-nombre">' . $nombre . '</div>' . $telasHtml . $tallasHtml . $descripcionHtml . '</div><div class="prenda-contenido">' . $variacionesHtml . $imagenesHtml . '</div></div>';

        } catch (\Exception $e) {
            \Log::error('Error en renderPrendaCard', ['error' => $e->getMessage()]);
            return '<div class="prenda-card"><p>Error procesando prenda</p></div>';
        }
    }

    /**
     * Renderiza las imágenes de logo y tela
     */
    private function renderImagenesLogo($prenda, $logoCot): string
    {
        $imagenesPorTipo = [
            'Logo' => [],
            'Tela' => []
        ];

        // Obtener imágenes de los logos (tecnicas) - evitar duplicados por ruta
        $rutasLogoVistas = [];
        if (isset($logoCot->tecnicasPrendas)) {
            foreach ($logoCot->tecnicasPrendas as $tecnica) {
                if ($tecnica->prenda_cot_id == $prenda->id && $tecnica->fotos) {
                    foreach ($tecnica->fotos as $foto) {
                        // Usar el accessor url que maneja las rutas correctamente
                        $imagenUrl = $foto->url ?? $foto->ruta_original ?? '';
                        
                        // Evitar duplicados
                        if (!empty($imagenUrl) && !in_array($imagenUrl, $rutasLogoVistas)) {
                            $rutasLogoVistas[] = $imagenUrl;
                            $imagenesPorTipo['Logo'][] = [
                                'url' => $imagenUrl,
                                'titulo' => 'Logo'
                            ];
                        }
                    }
                }
            }
        }

        // Obtener imágenes de las telas
        if (isset($logoCot->telasPrendas)) {
            foreach ($logoCot->telasPrendas as $tela) {
                if ($tela->prenda_cot_id == $prenda->id && !empty($tela->img)) {
                    // Usar directamente el campo img para evitar duplicación de /storage
                    $imagenUrl = $tela->img;
                    $imagenesPorTipo['Tela'][] = [
                        'url' => $imagenUrl,
                        'titulo' => 'Tela'
                    ];
                    break; // Solo una tela
                }
            }
        }

        // Verificar si hay al menos una imagen
        $tieneImagenes = false;
        foreach ($imagenesPorTipo as $tipoImagenes) {
            if (!empty($tipoImagenes)) {
                $tieneImagenes = true;
                break;
            }
        }

        if (!$tieneImagenes) {
            return '';
        }

        // Crear tabla horizontal con columnas por tipo
        $html = '<div style="margin-top: 15px; border-top: 1px solid #e0e0e0; padding-top: 10px;">';
        $html .= '<table style="width: 100%; border-collapse: collapse;">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th style="width: 50%; background: #e8eef7; font-weight: bold; padding: 8px; border: 1px solid #000; text-align: center; font-size: 10px;">Logo</th>';
        $html .= '<th style="width: 50%; background: #e8eef7; font-weight: bold; padding: 8px; border: 1px solid #000; text-align: center; font-size: 10px;">Tela</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        $html .= '<tr>';

        // Procesar Logo
        $html .= '<td style="width: 50%; padding: 8px; border: 1px solid #000; vertical-align: middle; text-align: center; min-height: 100px;">';
        if (!empty($imagenesPorTipo['Logo'])) {
            $img = $imagenesPorTipo['Logo'][0];
            $imagenUrl = $img['url'];
            
            // Convertir ruta a formato absoluto para mPDF
            $imagenSrc = '';
            if (!str_starts_with($imagenUrl, 'http')) {
                // Es una ruta relativa, normalizar y convertirla a absoluta
                $ruta = $imagenUrl;
                
                // Si ya empieza con /storage, usarla directamente
                if (str_starts_with($ruta, '/storage/')) {
                    $rutaRelativa = ltrim($ruta, '/');
                } else {
                    // Si no empieza con /storage, agregarlo
                    $rutaRelativa = 'storage/' . ltrim($ruta, '/');
                }
                
                $rutaCompleta = public_path($rutaRelativa);
                $rutaCompleta = str_replace('/', DIRECTORY_SEPARATOR, $rutaCompleta);
                
                if (file_exists($rutaCompleta)) {
                    // Para mPDF usar la ruta absoluta al archivo
                    $imagenSrc = $rutaCompleta;
                } else {
                    $html .= '<div style="color: #999; font-size: 9px; padding: 10px;">Imagen no encontrada</div>';
                }
            } else {
                // Es URL, verificar si se puede acceder
                $html .= '<div style="color: #999; font-size: 9px; padding: 10px;">URL no soportada en PDF</div>';
            }
            
            if (!empty($imagenSrc)) {
                $html .= '<img src="' . $imagenSrc . '" alt="' . htmlspecialchars($img['titulo']) . '" style="max-width: 100%; max-height: 100px; display: block; margin: 0 auto;">';
            }
        } else {
            $html .= '<div style="color: #ccc; font-size: 9px; padding: 10px;">Sin logo</div>';
        }
        $html .= '</td>';

        // Procesar Tela
        $html .= '<td style="width: 50%; padding: 8px; border: 1px solid #000; vertical-align: middle; text-align: center; min-height: 100px;">';
        if (!empty($imagenesPorTipo['Tela'])) {
            $img = $imagenesPorTipo['Tela'][0];
            $imagenUrl = $img['url'];
            
            // Convertir ruta a formato absoluto para mPDF
            $imagenSrc = '';
            if (!str_starts_with($imagenUrl, 'http')) {
                // Es una ruta relativa, normalizar y convertirla a absoluta
                $ruta = $imagenUrl;
                
                // Si ya empieza con /storage, usarla directamente
                if (str_starts_with($ruta, '/storage/')) {
                    $rutaRelativa = ltrim($ruta, '/');
                } else {
                    // Si no empieza con /storage, agregarlo
                    $rutaRelativa = 'storage/' . ltrim($ruta, '/');
                }
                
                $rutaCompleta = public_path($rutaRelativa);
                $rutaCompleta = str_replace('/', DIRECTORY_SEPARATOR, $rutaCompleta);
                
                if (file_exists($rutaCompleta)) {
                    // Para mPDF usar la ruta absoluta al archivo
                    $imagenSrc = $rutaCompleta;
                } else {
                    $html .= '<div style="color: #999; font-size: 9px; padding: 10px;">Imagen no encontrada</div>';
                }
            } else {
                // Es URL, verificar si se puede acceder
                $html .= '<div style="color: #999; font-size: 9px; padding: 10px;">URL no soportada en PDF</div>';
            }
            
            if (!empty($imagenSrc)) {
                $html .= '<img src="' . $imagenSrc . '" alt="' . htmlspecialchars($img['titulo']) . '" style="max-width: 100%; max-height: 100px; display: block; margin: 0 auto;">';
            }
        } else {
            $html .= '<div style="color: #ccc; font-size: 9px; padding: 10px;">Sin tela</div>';
        }
        $html .= '</td>';

        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
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
