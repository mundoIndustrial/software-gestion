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
        $html .= $this->renderQuoteSection();
        $html .= $this->renderPrendas();
        $html .= $this->renderSpecifications();

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
    private function renderPrendas(): string
    {
        $prendas = $this->cotizacion->prendas()
            ->with(['telas.tela', 'variantes.manga', 'variantes.broche', 'tallas', 'fotos', 'telaFotos'])
            ->get() ?? [];

        if ($prendas->isEmpty()) {
            return '';
        }

        $html = '<div class="content-wrapper">';

        foreach ($prendas as $index => $prenda) {
            $html .= $this->renderPrenda($prenda, $index);
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza una prenda individual
     */
    private function renderPrenda($prenda, int $index): string
    {
        $html = '<div class="prenda" style="page-break-inside: avoid; margin-bottom: 25px; border: 1px solid #ddd; padding: 12px; background: #fafafa;">';

        // Nombre de la prenda
        $nombrePrenda = htmlspecialchars(strtoupper($prenda->nombre_producto ?? 'N/A'));
        $html .= "<div style=\"font-size: 11px; font-weight: bold; margin-bottom: 6px;\">PRENDA " . ($index + 1) . ": {$nombrePrenda}</div>";

        // Color, Tela y Manga
        $html .= $this->renderPrendaDetails($prenda);

        // Descripción
        if ($prenda->descripcion) {
            $descripcion = htmlspecialchars($prenda->descripcion);
            $html .= "<div style=\"font-size: 10px; margin-bottom: 6px; color: #333;\"><strong>DESCRIPCION:</strong> {$descripcion}</div>";
        }

        // Detalles especiales (reflectivo, bolsillos, etc)
        $html .= $this->renderPrendaSpecialDetails($prenda);

        // Tallas
        $html .= $this->renderTallas($prenda);

        // Imágenes
        $html .= $this->renderPrendaImages($prenda);

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza detalles de color, tela y manga
     */
    private function renderPrendaDetails($prenda): string
    {
        $html = '';
        $lineasColorTela = [];

        $variantes = $prenda->variantes ?? [];
        if ($variantes->isEmpty()) {
            return $html;
        }

        $variante = $variantes[0];

        // Telas múltiples
        $telasMultiples = $variante->telas_multiples ?? [];
        if (is_string($telasMultiples)) {
            $telasMultiples = json_decode($telasMultiples, true) ?? [];
        }

        if (is_array($telasMultiples) && count($telasMultiples) > 0) {
            foreach ($telasMultiples as $tm) {
                $partesLinea = [];
                
                if (!empty($tm['color'])) {
                    $partesLinea[] = 'Color: ' . htmlspecialchars($tm['color']);
                } elseif (!empty($variante->color)) {
                    $partesLinea[] = 'Color: ' . htmlspecialchars($variante->color);
                }

                if (!empty($tm['tela'])) {
                    $telaTexto = 'Tela: ' . htmlspecialchars($tm['tela']);
                    if (!empty($tm['referencia'])) {
                        $telaTexto .= ' Ref: ' . htmlspecialchars($tm['referencia']);
                    }
                    $partesLinea[] = $telaTexto;
                }

                if (!empty($tm['manga'])) {
                    $partesLinea[] = 'Manga: ' . htmlspecialchars($tm['manga']);
                }

                if (!empty($partesLinea)) {
                    $lineasColorTela[] = implode(' | ', $partesLinea);
                }
            }
        } else {
            // Fallback: color + telas
            $partes = [];

            if ($variante->color) {
                $partes[] = 'Color: ' . htmlspecialchars($variante->color);
            }

            // Telas
            $telas = $prenda->telas ?? [];
            if (!$telas->isEmpty()) {
                $telasTexto = [];
                foreach ($telas as $telaPrenda) {
                    $textoTela = '';
                    
                    if ($telaPrenda->tela) {
                        $textoTela = htmlspecialchars($telaPrenda->tela->nombre ?? '');
                        if ($telaPrenda->tela->referencia) {
                            $textoTela .= ' REF:' . htmlspecialchars($telaPrenda->tela->referencia);
                        }
                    } elseif ($telaPrenda->nombre_tela) {
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

            if (!empty($partes)) {
                $lineasColorTela[] = implode(' | ', $partes);
            }
        }

        // Manga aparte
        if ($variante->tipo_manga_id) {
            $tipomanga = $variante->manga?->nombre ?? 'Manga desconocida';
            $mangaLinea = 'Manga: ' . htmlspecialchars($tipomanga);
            if (!empty($variante->obs_manga)) {
                $mangaLinea .= ' (' . htmlspecialchars($variante->obs_manga) . ')';
            }
            $lineasColorTela[] = $mangaLinea;
        }

        if (count($lineasColorTela) > 0) {
            $html .= '<div style="font-size: 10px; margin-bottom: 6px; color: #333;">' . implode('<br>', $lineasColorTela) . '</div>';
        }

        return $html;
    }

    /**
     * Renderiza detalles especiales: reflectivo, bolsillos, etc
     */
    private function renderPrendaSpecialDetails($prenda): string
    {
        $html = '';
        $variantes = $prenda->variantes ?? [];

        if ($variantes->isEmpty()) {
            return $html;
        }

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

        // Manga observaciones
        if ($variante->obs_manga && !$variante->tipo_manga_id) {
            $html .= '<div style="font-size: 10px; margin: 4px 0 4px 20px; color: #333;">
                <strong>.</strong> <strong>Manga:</strong> ' . htmlspecialchars($variante->obs_manga) . '</div>';
        }

        // Broche/Botón
        if ($variante->tipo_broche_id && $variante->obs_broche) {
            $nombreBroche = $variante->broche?->nombre ?? 'Botón';
            $html .= '<div style="font-size: 10px; margin: 4px 0 4px 20px; color: #333;">
                <strong>.</strong> <strong>' . htmlspecialchars($nombreBroche) . ':</strong> ' . htmlspecialchars($variante->obs_broche) . '</div>';
        }

        // Tipo de Jean/Pantalón
        if ($variante->es_jean_pantalon && $variante->tipo_jean_pantalon) {
            $nombrePrenda = strtoupper($prenda->nombre_producto ?? '');
            $esJean = str_contains($nombrePrenda, 'JEAN');
            $tipoLabel = $esJean ? 'Jean' : 'Pantalón';
            $html .= '<div style="font-size: 10px; margin: 4px 0 4px 20px; color: #333;">
                <strong>.</strong> <strong>Tipo de ' . htmlspecialchars($tipoLabel) . ':</strong> ' . htmlspecialchars($variante->tipo_jean_pantalon) . '</div>';
        }

        return $html;
    }

    /**
     * Renderiza la sección de tallas
     */
    private function renderTallas($prenda): string
    {
        $tallas = $prenda->tallas ?? [];
        $tallasInfo = [];

        if (!$tallas->isEmpty()) {
            foreach ($tallas as $talla) {
                $cantidad = $talla->cantidad ?? 0;
                if ($cantidad > 0 || $cantidad === null) {
                    $tallasInfo[] = $talla->talla;
                }
            }
        }

        if (empty($tallasInfo)) {
            return '';
        }

        $tallasTexto = implode(', ', $tallasInfo);

        if ($prenda->texto_personalizado_tallas) {
            $tallasTexto .= ' ' . htmlspecialchars($prenda->texto_personalizado_tallas);
        }

        return '<div style="font-size: 10px; font-weight: bold; margin-top: 8px; margin-bottom: 12px; color: #e74c3c;">Tallas: ' . $tallasTexto . '</div>';
    }

    /**
     * Renderiza las imágenes de la prenda
     */
    private function renderPrendaImages($prenda): string
    {
        $imagenesPrenda = $prenda->fotos ?? [];
        $imagenesTela = $prenda->telaFotos ?? [];

        if ($imagenesPrenda->isEmpty() && $imagenesTela->isEmpty()) {
            return '';
        }

        $html = '<div style="display: flex; gap: 8px; margin-bottom: 10px; flex-wrap: wrap; justify-content: center;">';

        // Imágenes de prenda
        foreach ($imagenesPrenda as $imagen) {
            $html .= $this->renderImage($imagen->url ?? null, 'Prenda');
        }

        // Imágenes de tela
        foreach ($imagenesTela as $imagen) {
            $html .= $this->renderImage($imagen->url ?? null, 'Tela');
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza una imagen individual
     */
    private function renderImage(?string $rutaImagen, string $alt): string
    {
        if (!$rutaImagen) {
            return '';
        }

        // URL completa
        if (strpos($rutaImagen, 'http') === 0) {
            return '<img src="' . htmlspecialchars($rutaImagen) . '" alt="' . $alt . '" style="width: 100px; height: 100px; border: 1px solid #ccc; object-fit: cover; flex-shrink: 0;">';
        }

        // Ruta local
        if (!str_starts_with($rutaImagen, '/')) {
            $rutaImagen = '/' . $rutaImagen;
        }
        if (!str_starts_with($rutaImagen, '/storage/')) {
            if (str_starts_with($rutaImagen, '/cotizaciones/')) {
                $rutaImagen = '/storage' . $rutaImagen;
            }
        }

        $rutaAbsoluta = str_starts_with($rutaImagen, '/') 
            ? public_path($rutaImagen) 
            : $rutaImagen;

        if (file_exists($rutaAbsoluta)) {
            return '<img src="' . $rutaAbsoluta . '" alt="' . $alt . '" style="width: 100px; height: 100px; border: 1px solid #ccc; object-fit: cover; flex-shrink: 0;">';
        }

        return '';
    }

    /**
     * Renderiza la tabla de especificaciones
     */
    private function renderSpecifications(): string
    {
        $especificacionesData = $this->cotizacion->especificaciones ?? [];

        if (is_string($especificacionesData)) {
            $especificacionesData = json_decode($especificacionesData, true) ?? [];
        }

        if (!is_array($especificacionesData) || empty($especificacionesData)) {
            return '';
        }

        $categoriasInfo = [
            'disponibilidad' => 'DISPONIBILIDAD',
            'forma_pago' => 'FORMA DE PAGO',
            'regimen' => 'RÉGIMEN',
            'se_ha_vendido' => 'SE HA VENDIDO',
            'ultima_venta' => 'ÚLTIMA VENTA',
            'flete' => 'FLETE DE ENVÍO'
        ];

        $html = '<div class="spec-wrapper" style="page-break-inside: avoid; margin: 8px 0 0 0; padding: 8px 12mm; border-top: 2px solid #FFE082;">
            <div style="font-size: 11px; font-weight: bold; margin-bottom: 6px; color: #1e293b; padding-bottom: 3px;">Especificaciones Generales</div>
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; font-size: 9px;">
                <thead>
                    <tr style="background: #FFE082; border: 1px solid #FFE082;">
                        <th style="padding: 4px 4px; text-align: left; font-weight: bold; font-size: 9px; border: 1px solid #FFE082; width: 35%; color: #000;">CATEGORÍA</th>
                        <th style="padding: 4px 4px; text-align: center; font-weight: bold; font-size: 9px; border: 1px solid #FFE082; width: 10%; color: #000;">ESTADO</th>
                        <th style="padding: 4px 4px; text-align: left; font-weight: bold; font-size: 9px; border: 1px solid #FFE082; width: 55%; color: #000;">OBSERVACIONES</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($categoriasInfo as $categoriaKey => $categoriaNombre) {
            if (isset($especificacionesData[$categoriaKey]) && !empty($especificacionesData[$categoriaKey])) {
                $valores = $especificacionesData[$categoriaKey];

                if (!is_array($valores)) {
                    $valores = [$valores];
                }

                $html .= '<tr style="border: 1px solid #ddd; background: #FFE082;">
                        <td colspan="3" style="padding: 4px 4px; font-size: 9px; font-weight: 600; color: #000; border: 1px solid #FFE082;">' . htmlspecialchars($categoriaNombre) . '</td>
                    </tr>';

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
                            <td style="padding: 3px 4px; font-size: 8.5px; border: 1px solid #ddd; color: #333; font-weight: 500; line-height: 1.2;">' . htmlspecialchars($valor) . '</td>
                            <td style="padding: 3px 4px; text-align: center; font-weight: 700; color: #28a745; font-size: 10px; border: 1px solid #ddd;">✓</td>
                            <td style="padding: 3px 4px; font-size: 8.5px; border: 1px solid #ddd; color: #555; line-height: 1.2;">' . htmlspecialchars($observacion) . '</td>
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
