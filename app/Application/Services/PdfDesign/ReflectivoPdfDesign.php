<?php

namespace App\Application\Services\PdfDesign;

use App\Models\Cotizacion;

/**
 * ReflectivoPdfDesign - Componente de diseño para PDF de reflectivo
 * 
 * Utiliza el mismo diseño que CombiadaPdfDesign
 * Muestra: nombre de prenda y tallas
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

        $html .= '</body>
</html>';

        return $html;
    }

    /**
     * Retorna todos los estilos CSS - MISMO QUE COMBINADA
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
        .prenda-nombre { font-weight: bold; font-size: 11px; margin-bottom: 3px; color: #1e5ba8; }
        .prenda-detalles { font-size: 9px; margin-bottom: 2px; }
        .prenda-tallas { font-size: 10px; color: #e74c3c; font-weight: bold; }
        
        /* Contenedor principal de la prenda */
        .prenda-contenido { padding: 10px; }
        
        /* Descripción */
        .prenda-descripcion { background: #f5f5f5; border: 1px solid #ddd; padding: 8px; margin-bottom: 10px; font-size: 9px; line-height: 1.4; border-radius: 3px; }
        .prenda-descripcion strong { color: #1e5ba8; }
        
        /* Tabla de variaciones */
        .variaciones-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .variaciones-table thead tr { background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white; }
        .variaciones-table th { padding: 0.75rem; text-align: left; font-weight: 700; border-right: 1px solid rgba(255,255,255,0.2); color: white; font-weight: bold; }
        .variaciones-table td { padding: 0.75rem; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
        .variaciones-table tr:nth-child(even) { background: #f9fafb; }
        .variaciones-table .var-label { background: #f5f5f5; font-weight: 600; color: #0f172a; }
        .variaciones-table .var-valor { color: #0ea5e9; font-weight: 500; }
        
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
                <img src="{$logoPath}" class="header-logo" alt="Logo">
                <div class="header-content">
                    <div class="header-title">Uniformes Mundo Industrial</div>
                    <div class="header-subtitle">Lenis Ruth Mahecha Acosta</div>
                    <div class="header-subtitle">NIT: 1.093.738.433-3 Régimen Común</div>
                    <div style="font-size: 12px; font-weight: bold; margin-top: 4px;">COTIZACIÓN REFLECTIVO</div>
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
     * Renderiza todas las prendas con sus tallas
     */
    private function renderPrendas(): string
    {
        // Cargar prendas de reflectivo
        $prendas = $this->cotizacion->prendaCotReflectivos()
            ->with(['prendaCot.tallas', 'prendaCot.prendaCotReflectivo'])
            ->get() ?? [];

        if ($prendas->isEmpty()) {
            return '<div class="prendas-wrapper"><p style="text-align: center; color: #999;">No hay prendas de reflectivo en esta cotización</p></div>';
        }

        $html = '<div class="prendas-wrapper">';

        foreach ($prendas as $refPrenda) {
            $html .= $this->renderPrendaCard($refPrenda);
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza una prenda en card simplificado (nombre, color/tela/referencia y tallas)
     */
    private function renderPrendaCard($refPrenda): string
    {
        $prenda = $refPrenda->prendaCot;
        
        if (!$prenda) {
            return '';
        }

        $html = '<div class="prenda-card">';

        // Header del card con nombre
        $html .= '<div class="prenda-header">';
        $html .= '<div class="prenda-nombre">' . htmlspecialchars($prenda->nombre_producto) . '</div>';

        // Información de Color, Tela y Referencia (desde prenda_cot_reflectivo.color_tela_ref)
        $html .= $this->renderColorTelaReferencia($prenda);

        // Tallas en rojo
        $tallas = $prenda->tallas ? $prenda->tallas->pluck('talla')->implode(', ') : 'Sin tallas';
        $html .= '<div class="prenda-tallas">Tallas: ' . htmlspecialchars($tallas) . '</div>';

        $html .= '</div>';

        // Contenido de la prenda
        $html .= '<div class="prenda-contenido">';

        // Descripción y Ubicaciones con título
        $html .= $this->renderDescripcionYUbicaciones($prenda);

        // Tabla de variaciones específicas
        $html .= $this->renderVariacionesEspecificas($prenda);

        // Tabla de imágenes
        $html .= $this->renderImagenesEspecificas($prenda);

        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza la información de Color, Tela y Referencia
     */
    private function renderColorTelaReferencia($prenda): string
    {
        // Obtener prenda_cot_reflectivo (puede ser una colección, tomar el primero)
        $prendaCotReflectivo = $prenda->prendaCotReflectivo;
        
        if (!$prendaCotReflectivo) {
            return '';
        }

        // Si es una colección, obtener el primero
        if (is_object($prendaCotReflectivo) && method_exists($prendaCotReflectivo, 'first')) {
            $prendaCotReflectivo = $prendaCotReflectivo->first();
        }

        if (!$prendaCotReflectivo || !$prendaCotReflectivo->color_tela_ref) {
            return '';
        }

        // Decodificar color_tela_ref (puede ser string o array)
        $colorTelaRef = $prendaCotReflectivo->color_tela_ref;
        if (is_string($colorTelaRef)) {
            $colorTelaRef = json_decode($colorTelaRef, true);
        }

        if (!is_array($colorTelaRef) || empty($colorTelaRef)) {
            return '';
        }

        $html = '<div class="prenda-detalles">';
        
        // Procesar cada item de color_tela_ref
        foreach ($colorTelaRef as $item) {
            $color = $item['color'] ?? '';
            $tela = $item['tela'] ?? '';
            $referencia = $item['referencia'] ?? '';
            
            if ($color || $tela || $referencia) {
                if ($color) {
                    $html .= 'Color: ' . htmlspecialchars($color);
                }
                if ($tela) {
                    if ($color) {
                        $html .= ' | ';
                    }
                    $html .= 'Tela: ' . htmlspecialchars($tela);
                }
                if ($referencia) {
                    $html .= ' Ref: ' . htmlspecialchars($referencia);
                }
            }
        }
        
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza la descripción y ubicaciones del reflectivo con título
     */
    private function renderDescripcionYUbicaciones($prenda): string
    {
        // Obtener prenda_cot_reflectivo (puede ser una colección, tomar el primero)
        $prendaCotReflectivo = $prenda->prendaCotReflectivo;
        
        if (!$prendaCotReflectivo) {
            return '';
        }

        // Si es una colección, obtener el primero
        if (is_object($prendaCotReflectivo) && method_exists($prendaCotReflectivo, 'first')) {
            $prendaCotReflectivo = $prendaCotReflectivo->first();
        }

        if (!$prendaCotReflectivo) {
            return '';
        }

        $descripcion = $prendaCotReflectivo->descripcion ?? '';
        $ubicaciones = $prendaCotReflectivo->ubicaciones ?? [];

        // Decodificar ubicaciones si es string
        if (is_string($ubicaciones)) {
            $ubicaciones = json_decode($ubicaciones, true) ?? [];
        }

        if (empty($descripcion) && empty($ubicaciones)) {
            return '';
        }

        $html = '<div class="prenda-descripcion">';
        $html .= '<strong>Descripción:</strong> ';
        
        // Agregar descripción
        if ($descripcion) {
            $html .= htmlspecialchars($descripcion);
        }

        // Agregar ubicaciones
        if (!empty($ubicaciones) && is_array($ubicaciones)) {
            $ubicacionesTexto = [];
            
            foreach ($ubicaciones as $item) {
                $ubicacion = $item['ubicacion'] ?? '';
                $desc = $item['descripcion'] ?? '';
                
                if ($ubicacion) {
                    if ($desc) {
                        $ubicacionesTexto[] = htmlspecialchars($ubicacion) . ': ' . htmlspecialchars($desc);
                    } else {
                        $ubicacionesTexto[] = htmlspecialchars($ubicacion);
                    }
                }
            }

            if (!empty($ubicacionesTexto)) {
                if ($descripcion) {
                    $html .= ', ';
                }
                $html .= implode(', ', $ubicacionesTexto);
            }
        }
        
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza la tabla de variaciones específicas
     */
    private function renderVariacionesEspecificas($prenda): string
    {
        // Obtener prenda_cot_reflectivo (puede ser una colección, tomar el primero)
        $prendaCotReflectivo = $prenda->prendaCotReflectivo;
        
        if (!$prendaCotReflectivo) {
            return '';
        }

        // Si es una colección, obtener el primero
        if (is_object($prendaCotReflectivo) && method_exists($prendaCotReflectivo, 'first')) {
            $prendaCotReflectivo = $prendaCotReflectivo->first();
        }

        if (!$prendaCotReflectivo || !$prendaCotReflectivo->variaciones) {
            return '';
        }

        $variaciones = $prendaCotReflectivo->variaciones;

        // Decodificar si es string
        if (is_string($variaciones)) {
            $variaciones = json_decode($variaciones, true);
        }

        if (!is_array($variaciones) || empty($variaciones)) {
            return '';
        }

        $html = '<div style="margin-top: 15px; border-top: 2px solid #1e5ba8; padding-top: 10px;">';
        $html .= '<div style="font-weight: bold; font-size: 10px; color: #1e5ba8; margin-bottom: 8px; text-transform: uppercase;">Variaciones Específicas</div>';
        
        $html .= '<table class="variaciones-table" style="width: 100%; border-collapse: collapse;">';
        $html .= '<thead>';
        $html .= '<tr style="background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white;">';
        $html .= '<th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2); font-size: 9px;">Tipo</th>';
        $html .= '<th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2); font-size: 9px;">Opción</th>';
        $html .= '<th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 9px;">Observación</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $contador = 0;
        foreach ($variaciones as $item) {
            $tipo = $item['variacion'] ?? '';
            $opcion = $item['opcion'] ?? '';
            $observacion = $item['observacion'] ?? '';
            
            // Solo mostrar si está chequeado o si no hay campo checked
            $checked = isset($item['checked']) ? $item['checked'] : true;
            
            if (!$checked) {
                continue;
            }
            
            $rowBg = ($contador % 2 == 0) ? '#f9fafb' : '#ffffff';
            
            $html .= '<tr style="background: ' . $rowBg . ';">';
            $html .= '<td style="padding: 0.75rem; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; font-size: 9px; background: #f5f5f5; font-weight: 600; color: #0f172a;" class="var-label">' . htmlspecialchars($tipo) . '</td>';
            $html .= '<td style="padding: 0.75rem; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; font-size: 9px; color: #0ea5e9; font-weight: 500;" class="var-valor">' . htmlspecialchars($opcion ?: '-') . '</td>';
            $html .= '<td style="padding: 0.75rem; border-bottom: 1px solid #e2e8f0; font-size: 9px;">' . htmlspecialchars($observacion ?: '-') . '</td>';
            $html .= '</tr>';
            
            $contador++;
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza las imágenes específicas: reflectivo y telas
     */
    private function renderImagenesEspecificas($prenda): string
    {
        $prendaCotReflectivo = $prenda->prendaCotReflectivo;
        
        if (!$prendaCotReflectivo) {
            return '';
        }

        // Si es una colección, obtener el primero
        if (is_object($prendaCotReflectivo) && method_exists($prendaCotReflectivo, 'first')) {
            $prendaCotReflectivo = $prendaCotReflectivo->first();
        }

        if (!$prendaCotReflectivo || !$prendaCotReflectivo->color_tela_ref) {
            return '';
        }

        $colorTelaRef = $prendaCotReflectivo->color_tela_ref;
        if (is_string($colorTelaRef)) {
            $colorTelaRef = json_decode($colorTelaRef, true);
        }

        if (!is_array($colorTelaRef) || empty($colorTelaRef)) {
            return '';
        }

        // Obtener las fotos de reflectivo desde reflectivoPrendas (tabla reflectivo_cotizacion) - solo UNA VEZ
        $fotosReflectivo = [];
        foreach ($this->cotizacion->reflectivoPrendas as $refPrenda) {
            if ($refPrenda->prenda_cot_id === $prenda->id && $refPrenda->fotos) {
                foreach ($refPrenda->fotos as $foto) {
                    if ($foto->ruta_webp) {
                        $fotosReflectivo[] = $foto->ruta_webp;
                    } elseif ($foto->ruta_original) {
                        $fotosReflectivo[] = $foto->ruta_original;
                    }
                }
                break; // Solo obtener fotos de la primera coincidencia
            }
        }

        // Obtener fotos de tela organizadas por tela_index
        $fotasTelaPorIndice = [];
        if ($prenda->telaFotos) {
            foreach ($prenda->telaFotos as $foto) {
                $indice = $foto->tela_index ?? 0;
                if (!isset($fotasTelaPorIndice[$indice])) {
                    $fotasTelaPorIndice[$indice] = [];
                }
                if ($foto->ruta_webp) {
                    $fotasTelaPorIndice[$indice][] = $foto->ruta_webp;
                } elseif ($foto->ruta_original) {
                    $fotasTelaPorIndice[$indice][] = $foto->ruta_original;
                }
            }
        }

        $html = '<div style="margin-top: 15px; border-top: 2px solid #1e5ba8; padding-top: 10px;">';
        $html .= '<div style="font-weight: bold; font-size: 10px; color: #1e5ba8; margin-bottom: 8px; text-transform: uppercase;">Imágenes</div>';

        // Renderizar tablas de imágenes para cada tela/color
        foreach ($colorTelaRef as $item) {
            $indice = $item['indice'] ?? 0;
            $color = $item['color'] ?? '';
            $tela = $item['tela'] ?? '';

            // Encabezado con color y tela
            if ($color && $tela) {
                $html .= '<div style="font-weight: 600; font-size: 9px; margin-bottom: 8px; color: #1e5ba8; padding: 8px; background: #f5f5f5; border: 1px solid #e2e8f0;">' . htmlspecialchars($color . ' - ' . $tela) . '</div>';
            }

            // Tabla horizontal con Reflectivo y Tela
            $html .= '<table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="width: 50%; background: #e8eef7; font-weight: bold; padding: 8px; border: 1px solid #000; text-align: center;">Reflectivo</th>';
            $html .= '<th style="width: 50%; background: #e8eef7; font-weight: bold; padding: 8px; border: 1px solid #000; text-align: center;">Tela</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            $html .= '<tr>';

            // Columna Reflectivo
            $html .= '<td style="width: 50%; padding: 8px; border: 1px solid #000; vertical-align: middle; text-align: center;">';
            if (!empty($fotosReflectivo)) {
                $imagenUrl = $fotosReflectivo[0];
                if (!str_starts_with($imagenUrl, 'http')) {
                    $ruta = str_starts_with($imagenUrl, '/storage/') ? $imagenUrl : '/storage/' . ltrim($imagenUrl, '/');
                    $imagenUrl = public_path(ltrim($ruta, '/'));
                }

                if (file_exists($imagenUrl)) {
                    $html .= '<img src="' . $imagenUrl . '" alt="Reflectivo" style="max-width: 100%; max-height: 120px; display: block; margin: 0 auto;">';
                } else {
                    $html .= '<div style="color: #999; font-size: 9px; padding: 10px;">Imagen no encontrada</div>';
                }
            } else {
                $html .= '<div style="color: #ccc; font-size: 9px; padding: 10px;">Sin reflectivo</div>';
            }
            $html .= '</td>';

            // Columna Tela
            $html .= '<td style="width: 50%; padding: 8px; border: 1px solid #000; vertical-align: middle; text-align: center;">';
            if (!empty($fotasTelaPorIndice[$indice])) {
                $imagenUrl = $fotasTelaPorIndice[$indice][0];
                if (!str_starts_with($imagenUrl, 'http')) {
                    $ruta = str_starts_with($imagenUrl, '/storage/') ? $imagenUrl : '/storage/' . ltrim($imagenUrl, '/');
                    $imagenUrl = public_path(ltrim($ruta, '/'));
                }

                if (file_exists($imagenUrl)) {
                    $html .= '<img src="' . $imagenUrl . '" alt="Tela" style="max-width: 100%; max-height: 120px; display: block; margin: 0 auto;">';
                } else {
                    $html .= '<div style="color: #999; font-size: 9px; padding: 10px;">Imagen no encontrada</div>';
                }
            } else {
                $html .= '<div style="color: #ccc; font-size: 9px; padding: 10px;">Sin tela</div>';
            }
            $html .= '</td>';

            $html .= '</tr>';
            $html .= '</tbody>';
            $html .= '</table>';
        }

        $html .= '</div>';

        return $html;
    }
}
