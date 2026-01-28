<?php

namespace App\Application\Services\PdfDesign;

use App\Models\Cotizacion;

/**
 * ReflectivoPdfDesign - Componente de diseño para PDF de reflectivo
 * 
 * Muestra prendas con sus variaciones, colores, telas, tallas e imágenes
 */
class ReflectivoPdfDesign
{
    private Cotizacion $cotizacion;

    public function __construct(Cotizacion $cotizacion)
    {
        $this->cotizacion = $cotizacion;
    }

    /**
     * Genera el HTML completo del PDF de reflectivo
     */
    public function build(): string
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
        $html .= $this->renderPrendas();

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
        .header-logo { width: 120px; height: auto; flex-shrink: 0; }
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
        
        /* Header del card con nombre y detalles */
        .prenda-header { background: #fff; padding: 8px 10px; border-bottom: 1px solid #000; }
        .prenda-nombre { font-weight: bold; font-size: 11px; margin-bottom: 3px; }
        .prenda-detalles { font-size: 9px; margin-bottom: 2px; }
        .prenda-tallas { font-size: 10px; color: #e74c3c; font-weight: bold; }
        
        /* Contenedor principal de la prenda */
        .prenda-contenido { display: flex; gap: 10px; padding: 10px; }
        
        /* Columna izquierda: tabla de variaciones */
        .prenda-info { flex: 1; }
        .variaciones-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .variaciones-table td { border: 1px solid #000; padding: 6px; font-size: 9px; }
        .variaciones-table .var-header { background: #e0e0e0; font-weight: bold; }
        .variaciones-table .var-label { background: #f5f5f5; font-weight: bold; width: 35%; }
        
        /* Columna derecha: imágenes de variaciones */
        .prenda-imagenes { display: flex; flex-wrap: nowrap; gap: 8px; align-items: flex-start; overflow-x: auto; }
        .prenda-img { border: 2px solid #999; padding: 4px; width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; background: #f9f9f9; }
        .prenda-img img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .prenda-img-placeholder { width: 100px; height: 100px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999; font-size: 8px; text-align: center; border: 2px solid #999; }
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
                <img src="{$logoPath}" class="header-logo" alt="Logo">
                <div class="header-content">
                    <div class="header-title">Uniformes Mundo Industrial</div>
                    <div class="header-subtitle">Lenis Ruth Mahecha Acosta</div>
                    <div class="header-subtitle">NIT: 1.093.738.433-3 Régimen Común</div>
                    <div style="font-size: 12px; font-weight: bold; margin-top: 4px;">COTIZACIÓN COMBINADA (REFLECTIVO)</div>
                </div>
            </div>
        </div>
        HTML;
    }

    /**
     * Renderiza la información del cliente
     */
    private function renderClientInfo(): string
    {
        $nombreCliente = $this->cotizacion->cliente?->nombre ?? 'N/A';
        $nombreAsesor = $this->cotizacion->usuario?->name ?? 'N/A';
        $fecha = $this->cotizacion->created_at?->format('d/m/Y') ?? 'N/A';

        $nombreCliente = htmlspecialchars($nombreCliente);
        $nombreAsesor = htmlspecialchars($nombreAsesor);
        $fecha = htmlspecialchars($fecha);

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

    /**
     * Renderiza las prendas en cards con variaciones, colores, telas, tallas e imágenes
     */
    private function renderPrendas(): string
    {
        $html = '<div class="prendas-wrapper">';

        // Obtener las prendas de reflectivo
        if ($this->cotizacion->prendaCotReflectivos && count($this->cotizacion->prendaCotReflectivos) > 0) {
            foreach ($this->cotizacion->prendaCotReflectivos as $refPrenda) {
                $prenda = $refPrenda->prendaCot;
                
                if (!$prenda) continue;

                // Decodificar variaciones JSON
                $variaciones = [];
                if ($refPrenda->variaciones) {
                    $variaciones = is_string($refPrenda->variaciones) 
                        ? json_decode($refPrenda->variaciones, true) 
                        : $refPrenda->variaciones;
                }

                $html .= '<div class="prenda-card">';

                // Header del card con nombre y detalles
                $html .= '<div class="prenda-header">';
                $html .= '<div class="prenda-nombre">' . htmlspecialchars($prenda->nombre_producto) . '</div>';

                // Información de color, tela y referencia (de la primera variación)
                if (!empty($variaciones) && is_array($variaciones)) {
                    $primeraVar = $variaciones[0];
                    
                    $color = $primeraVar['color'] ?? 'N/A';
                    $tela = '';
                    $referencia = '';
                    
                    if (isset($primeraVar['telas_multiples']) && !empty($primeraVar['telas_multiples'])) {
                        $primeraTela = $primeraVar['telas_multiples'][0];
                        $tela = $primeraTela['tela'] ?? '';
                        $referencia = $primeraTela['referencia'] ?? '';
                    }
                    
                    $html .= '<div class="prenda-detalles">';
                    $html .= 'Color: ' . htmlspecialchars($color) . ' | Tela: ' . htmlspecialchars($tela);
                    if ($referencia) {
                        $html .= ' Ref: ' . htmlspecialchars($referencia);
                    }
                    $html .= '</div>';
                }

                // Tallas en rojo
                $tallas = $prenda->tallas ? $prenda->tallas->pluck('talla')->implode(', ') : 'Sin tallas';
                $html .= '<div class="prenda-tallas">Tallas: ' . htmlspecialchars($tallas) . '</div>';

                $html .= '</div>';

                // Contenido: tabla de variaciones + imágenes
                $html .= '<div class="prenda-contenido">';

                // Columna izquierda: tabla de variaciones
                $html .= '<div class="prenda-info">';
                $html .= $this->renderVariacionesTable($variaciones);
                $html .= '</div>';

                // Columna derecha: imágenes
                $html .= '<div class="prenda-imagenes">';
                $html .= $this->renderImagenesVariaciones($prenda, $refPrenda);
                $html .= '</div>';

                $html .= '</div>';

                $html .= '</div>';
            }
        } else {
            $html .= '<div style="padding: 20px; text-align: center; color: #999;">No hay prendas de reflectivo en esta cotización</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza la tabla de variaciones
     */
    private function renderVariacionesTable(array $variaciones): string
    {
        if (empty($variaciones)) {
            return '<p style="color: #999; font-size: 9px;">Sin variaciones</p>';
        }

        $html = '<table class="variaciones-table">';
        
        // Headers
        $html .= '<tr>';
        $html .= '<td class="var-header" style="width: 35%;">Variación</td>';
        $html .= '<td class="var-header" style="width: 65%;">Observación</td>';
        $html .= '</tr>';

        // Recolectar todas las variaciones
        foreach ($variaciones as $var) {
            $mangaId = $var['tipo_manga_id'] ?? null;
            $brocheId = $var['tipo_broche_id'] ?? null;
            $tieneBosillos = $var['tiene_bolsillos'] ?? false;
            $obsBolsillos = $var['obs_bolsillos'] ?? '';
            $obsBroche = $var['obs_broche'] ?? '';

            // Fila de manga
            if ($mangaId) {
                $html .= '<tr>';
                $html .= '<td class="var-label">Manga</td>';
                $html .= '<td></td>';
                $html .= '</tr>';
            }

            // Fila de bolsillos
            if ($tieneBosillos && $obsBolsillos) {
                $html .= '<tr>';
                $html .= '<td class="var-label">Bolsillos</td>';
                $html .= '<td>' . htmlspecialchars($obsBolsillos) . '</td>';
                $html .= '</tr>';
            }

            // Fila de broche
            if ($brocheId && $obsBroche) {
                $html .= '<tr>';
                $html .= '<td class="var-label">Broche/Botón</td>';
                $html .= '<td>' . htmlspecialchars($obsBroche) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</table>';

        return $html;
    }

    /**
     * Renderiza las imágenes de variaciones de la prenda y del reflectivo
     */
    private function renderImagenesVariaciones($prenda, $refPrenda): string
    {
        $html = '';

        // Imágenes de la prenda (prendas_cot)
        if ($prenda->fotos && count($prenda->fotos) > 0) {
            foreach ($prenda->fotos as $foto) {
                if ($foto->ruta_webp) {
                    $imagenUrl = public_path('storage/' . $foto->ruta_webp);
                    
                    if (file_exists($imagenUrl)) {
                        $html .= '<div class="prenda-img">';
                        $html .= '<img src="' . $imagenUrl . '" alt="Variación">';
                        $html .= '</div>';
                    } else {
                        $html .= '<div class="prenda-img-placeholder">Imagen no encontrada</div>';
                    }
                }
            }
        }

        // Imágenes del reflectivo paso 4 (reflectivo_fotos_cotizacion)
        // Buscar en reflectivoPrendas los que correspondan a esta prenda
        if ($this->cotizacion->reflectivoPrendas) {
            foreach ($this->cotizacion->reflectivoPrendas as $refPrendaItem) {
                // Filtrar solo los que corresponden a esta prenda
                if ($refPrendaItem->prenda_cot_id === $prenda->id && $refPrendaItem->fotos) {
                    foreach ($refPrendaItem->fotos as $foto) {
                        if ($foto->ruta_webp) {
                            $imagenUrl = public_path('storage/' . $foto->ruta_webp);
                            
                            if (file_exists($imagenUrl)) {
                                $html .= '<div class="prenda-img">';
                                $html .= '<img src="' . $imagenUrl . '" alt="Reflectivo">';
                                $html .= '</div>';
                            } else {
                                $html .= '<div class="prenda-img-placeholder">Imagen no encontrada</div>';
                            }
                        }
                    }
                }
            }
        }

        // Si no hay imágenes, mostrar placeholder
        if (empty($html)) {
            $html .= '<div class="prenda-img-placeholder">Sin imagen</div>';
        }

        return $html;
    }
}
