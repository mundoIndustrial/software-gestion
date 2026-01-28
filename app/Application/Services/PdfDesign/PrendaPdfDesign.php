<?php

namespace App\Application\Services\PdfDesign;

use App\Models\Cotizacion;

/**
 * PrendaPdfDesign - Componente de diseño para PDF de prendas
 * 
 * Responsabilidades:
 * - Generar estructura HTML del PDF de prendas
 * - Manejar estilos y diseño visual
 * - Armar las secciones: encabezado, cliente, prendas, especificaciones
 * 
 * No es responsable de:
 * - Lógica de negocio
 * - Control de acceso
 * - Generación del PDF (eso lo hace mPDF)
 * - Manejo de memoria
 */
class PrendaPdfDesign
{
    private Cotizacion $cotizacion;

    public function __construct(Cotizacion $cotizacion)
    {
        $this->cotizacion = $cotizacion;
    }

    /**
     * Genera el HTML completo del PDF de prenda
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
        .prenda-imagenes { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-start; width: 100%; }
        .prenda-img-container { display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .prenda-img { border: 2px solid #999; padding: 4px; width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; background: #f9f9f9; flex-shrink: 0; }
        .prenda-img img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .prenda-img-label { font-size: 8px; font-weight: bold; text-align: center; color: #333; width: 100px; word-wrap: break-word; }
        .prenda-img-placeholder { width: 100px; height: 100px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999; font-size: 8px; text-align: center; border: 2px solid #999; flex-shrink: 0; }
        
        /* Tabla de ubicaciones */
        .ubicaciones-wrapper { width: 100%; border-top: 1px solid #000; padding: 10px; background: #f9f9f9; }
        .ubicaciones-title { font-weight: bold; font-size: 10px; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 4px; }
        .ubicaciones-content { font-size: 9px; line-height: 1.6; }
        .ubicacion-item { margin-bottom: 8px; padding: 6px; border-left: 3px solid #000; }
        .ubicacion-titulo { font-weight: bold; }
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
                    <div style="font-size: 12px; font-weight: bold; margin-top: 4px;">COTIZACIÓN</div>
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
     * Renderiza la sección "Por favor para Cotizar"
     */
    private function renderQuoteSection(): string
    {
        $tipoVenta = htmlspecialchars($this->cotizacion->tipo_venta ?? 'N/A');

        return <<<HTML
        <div style="padding: 0 12mm; margin: 12px 0 15px 0; background: #f5f5f5; border-left: 4px solid #000; padding-left: 15px; padding-right: 15px; padding-top: 10px; padding-bottom: 10px;">
            <div style="font-size: 11px; font-weight: bold; color: #000; margin-bottom: 6px;">Por favor para Cotizar:</div>
            <div style="font-size: 12px; font-weight: bold; color: #333;">
                Tipo: <span style="color: #e74c3c;">{$tipoVenta}</span>
            </div>
        </div>
        HTML;
    }

    /**
     * Renderiza todas las prendas
     */
    /**
     * Renderiza las prendas en cards con variaciones, colores, telas, tallas e imágenes
     */
    private function renderPrendas(): string
    {
        $prendas = $this->cotizacion->prendas()
            ->with([
                'telas.tela',
                'variantes.manga',
                'variantes.broche',
                'tallas',
                'fotos',
                'telaFotos',
                'prendaCotReflectivo:id,prenda_cot_id,variaciones,ubicaciones'
            ])
            ->get() ?? [];

        if ($prendas->isEmpty()) {
            return '';
        }

        $html = '<div class="prendas-wrapper">';

        foreach ($prendas as $index => $prenda) {
            $html .= $this->renderPrendaCard($prenda, $index);
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza una prenda en card con diseño mejorado
     */
    private function renderPrendaCard($prenda, int $index): string
    {
        $html = '<div class="prenda-card">';

        // Header del card con nombre y detalles
        $html .= '<div class="prenda-header">';
        $html .= '<div class="prenda-nombre">' . htmlspecialchars($prenda->nombre_producto) . '</div>';

        // Información de color, tela y referencia
        $html .= $this->renderPrendaHeaderDetails($prenda);

        // Tallas en rojo
        $html .= $this->renderPrendaTallas($prenda);

        $html .= '</div>';

        // Contenido: tabla de variaciones + imágenes
        $html .= '<div class="prenda-contenido">';

        // Columna izquierda: descripción + tabla de variaciones
        $html .= '<div class="prenda-info">';
        
        // Descripción concatenada de prenda y reflectivo
        $descripciones = [];
        
        // Descripción de la prenda base
        if ($prenda->descripcion) {
            $descripciones[] = htmlspecialchars($prenda->descripcion);
        }
        
        // Descripción de reflectivo si existe
        $prendaCotReflectivo = $prenda->prendaCotReflectivo()->first();
        if ($prendaCotReflectivo && $prendaCotReflectivo->descripcion) {
            $descripciones[] = htmlspecialchars($prendaCotReflectivo->descripcion);
        }
        
        // Mostrar descripción concatenada
        if (!empty($descripciones)) {
            $html .= '<div style="background: #f5f5f5; border: 1px solid #ddd; padding: 8px; margin-bottom: 10px; font-size: 9px; line-height: 1.4; border-radius: 3px;">';
            $html .= '<strong>DESCRIPCIÓN:</strong><br>';
            $html .= nl2br(implode(' - ', $descripciones));
            $html .= '</div>';
        }
        
        // Tabla de variaciones
        $html .= $this->renderVariacionesTable($prenda);
        $html .= '</div>';

        // Columna derecha: imágenes
        $html .= '<div class="prenda-imagenes">';
        $html .= $this->renderImagenesVariaciones($prenda, $prendaCotReflectivo);
        $html .= '</div>';

        $html .= '</div>';

        // Ubicaciones de reflectivo si existen
        $prendaCotReflectivo = $prenda->prendaCotReflectivo()->first();
        if ($prendaCotReflectivo && $prendaCotReflectivo->ubicaciones) {
            $html .= $this->renderUbicacionesReflectivo($prendaCotReflectivo->ubicaciones);
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza los detalles del header (color, tela, referencia)
     */
    private function renderPrendaHeaderDetails($prenda): string
    {
        $html = '';
        $variantes = $prenda->variantes ?? [];

        if ($variantes->isEmpty()) {
            return $html;
        }

        $variante = $variantes[0];
        $color = $variante->color ?? 'N/A';
        $tela = '';
        $referencia = '';

        // Obtener tela y referencia
        if (isset($variante->telas_multiples)) {
            $telasMultiples = is_string($variante->telas_multiples)
                ? json_decode($variante->telas_multiples, true)
                : $variante->telas_multiples;

            if (is_array($telasMultiples) && !empty($telasMultiples)) {
                $primeraTela = $telasMultiples[0];
                $tela = $primeraTela['tela'] ?? '';
                $referencia = $primeraTela['referencia'] ?? '';
            }
        }

        if (!$tela && $prenda->telas && !$prenda->telas->isEmpty()) {
            $primeraTela = $prenda->telas->first();
            $tela = $primeraTela->tela?->nombre ?? $primeraTela->nombre_tela ?? '';
            $referencia = $primeraTela->tela?->referencia ?? $primeraTela->referencia_tela ?? '';
        }

        $html .= '<div class="prenda-detalles">';
        $html .= 'Color: ' . htmlspecialchars($color) . ' | Tela: ' . htmlspecialchars($tela);
        if ($referencia) {
            $html .= ' Ref: ' . htmlspecialchars($referencia);
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza las tallas en rojo
     */
    private function renderPrendaTallas($prenda): string
    {
        $tallas = $prenda->tallas ? $prenda->tallas->pluck('talla')->implode(', ') : 'Sin tallas';
        return '<div class="prenda-tallas">Tallas: ' . htmlspecialchars($tallas) . '</div>';
    }

    /**
     * Renderiza la tabla de variaciones
     */
    private function renderVariacionesTable($prenda): string
    {
        $variantes = $prenda->variantes ?? [];

        if ($variantes->isEmpty()) {
            return '<p style="color: #999; font-size: 9px;">Sin variaciones</p>';
        }

        $html = '<table class="variaciones-table">';

        // Headers
        $html .= '<tr>';
        $html .= '<td class="var-header" style="width: 35%;">Variación</td>';
        $html .= '<td class="var-header" style="width: 65%;">Observación</td>';
        $html .= '</tr>';

        // Recolectar todas las variaciones
        foreach ($variantes as $var) {
            $mangaId = $var->tipo_manga_id ?? null;
            $brocheId = $var->tipo_broche_id ?? null;
            $tieneBosillos = $var->tiene_bolsillos ?? false;
            $obsBolsillos = $var->obs_bolsillos ?? '';
            $obsBroche = $var->obs_broche ?? '';

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
     * Renderiza las imágenes de variaciones de la prenda y del reflectivo con títulos
     */
    private function renderImagenesVariaciones($prenda, $prendaCotReflectivo = null): string
    {
        $html = '';
        $imagenesPrenda = $prenda->fotos ?? [];

        // Imágenes de la prenda
        if ($imagenesPrenda && count($imagenesPrenda) > 0) {
            foreach ($imagenesPrenda as $foto) {
                if ($foto->ruta_webp) {
                    $imagenUrl = public_path('storage/' . $foto->ruta_webp);

                    if (file_exists($imagenUrl)) {
                        $html .= '<div class="prenda-img-container">';
                        $html .= '<div class="prenda-img">';
                        $html .= '<img src="' . $imagenUrl . '" alt="Prenda">';
                        $html .= '</div>';
                        $html .= '<div class="prenda-img-label">Img Prenda</div>';
                        $html .= '</div>';
                    } else {
                        $html .= '<div class="prenda-img-container">';
                        $html .= '<div class="prenda-img-placeholder">Imagen no encontrada</div>';
                        $html .= '<div class="prenda-img-label">Img Prenda</div>';
                        $html .= '</div>';
                    }
                }
            }
        }

        // Imágenes del reflectivo paso 4
        if ($prendaCotReflectivo && $this->cotizacion->reflectivoPrendas) {
            foreach ($this->cotizacion->reflectivoPrendas as $refPrendaItem) {
                if ($refPrendaItem->prenda_cot_id === $prenda->id && $refPrendaItem->fotos) {
                    foreach ($refPrendaItem->fotos as $foto) {
                        if ($foto->ruta_webp) {
                            $imagenUrl = public_path('storage/' . $foto->ruta_webp);
                            
                            if (file_exists($imagenUrl)) {
                                $html .= '<div class="prenda-img-container">';
                                $html .= '<div class="prenda-img">';
                                $html .= '<img src="' . $imagenUrl . '" alt="Reflectivo">';
                                $html .= '</div>';
                                $html .= '<div class="prenda-img-label">Img Reflectivo</div>';
                                $html .= '</div>';
                            } else {
                                $html .= '<div class="prenda-img-container">';
                                $html .= '<div class="prenda-img-placeholder">Imagen no encontrada</div>';
                                $html .= '<div class="prenda-img-label">Img Reflectivo</div>';
                                $html .= '</div>';
                            }
                        }
                    }
                }
            }
        }

        // Si no hay imágenes, mostrar placeholder
        if (empty($html)) {
            $html .= '<div class="prenda-img-container">';
            $html .= '<div class="prenda-img-placeholder">Sin imagen</div>';
            $html .= '<div class="prenda-img-label">Sin contenido</div>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Renderiza la tabla de ubicaciones de reflectivo
     */
    /**
     * Renderiza la tabla de ubicaciones de reflectivo
     */
    private function renderUbicacionesReflectivo($ubicaciones): string
    {
        if (!$ubicaciones) {
            return '';
        }

        // Decodificar si es string
        if (is_string($ubicaciones)) {
            $ubicaciones = json_decode($ubicaciones, true);
        }

        if (!is_array($ubicaciones) || empty($ubicaciones)) {
            return '';
        }

        $html = '<div class="ubicaciones-wrapper">';
        $html .= '<div class="ubicaciones-title">Ubicaciones de Reflectivo</div>';
        $html .= '<div class="ubicaciones-content">';

        foreach ($ubicaciones as $ubicacion) {
            // Obtener la clave correcta (ubicacion, no titulo)
            $titulo = '';
            $descripcion = '';

            if (is_array($ubicacion)) {
                $titulo = $ubicacion['ubicacion'] ?? $ubicacion['titulo'] ?? '';
                $descripcion = $ubicacion['descripcion'] ?? '';
            } else {
                // Si es un objeto
                $titulo = $ubicacion->ubicacion ?? $ubicacion->titulo ?? '';
                $descripcion = $ubicacion->descripcion ?? '';
            }

            if ($titulo) {
                $html .= '<div class="ubicacion-item">';
                $html .= '<div class="ubicacion-titulo">' . htmlspecialchars($titulo) . '</div>';
                $html .= '<div style="margin-top: 4px;">' . nl2br(htmlspecialchars($descripcion)) . '</div>';
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
