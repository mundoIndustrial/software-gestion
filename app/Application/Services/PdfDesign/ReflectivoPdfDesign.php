<?php

namespace App\Application\Services\PdfDesign;

use App\Models\Cotizacion;
use App\Models\TipoManga;
use App\Models\TipoBrocheBoton;

class ReflectivoPdfDesign
{
    protected Cotizacion $cotizacion;

    public function __construct(Cotizacion $cotizacion)
    {
        $this->cotizacion = $cotizacion;
    }

    /**
     * Construir el documento HTML completo del PDF de reflectivo
     */
    public function build(): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Cotización Reflectivo</title>
            {$this->getStyles()}
        </head>
        <body>
            {$this->getDocumentStructure()}
        </body>
        </html>
        ";
    }

    /**
     * Obtener la estructura completa del documento
     */
    protected function getDocumentStructure(): string
    {
        $html = '';
        
        // Header
        $html .= $this->renderHeader();
        
        // Información del cliente
        $html .= $this->renderClientInfo();
        
        // Sección de cotización
        $html .= $this->renderQuoteSection();
        
        // Prendas reflectivo
        $html .= $this->renderReflectivos();
        
        // Especificaciones finales
        $html .= $this->renderSpecifications();
        
        return $html;
    }

    /**
     * Obtener todos los estilos CSS
     */
    protected function getStyles(): string
    {
        return '
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: Arial, Helvetica, sans-serif;
                color: #333;
                line-height: 1.5;
            }
            
            .header {
                border-bottom: 3px solid #1e40af;
                padding-bottom: 15px;
                margin-bottom: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .logo-section {
                display: flex;
                align-items: center;
                gap: 15px;
                flex: 1;
            }
            
            .logo-section img {
                max-width: 80px;
                height: auto;
            }
            
            .header-title {
                font-size: 20px;
                font-weight: bold;
                color: #1e40af;
            }
            
            .header-subtitle {
                font-size: 12px;
                color: #666;
                margin-top: 5px;
            }
            
            .info-table {
                width: 100%;
                margin-bottom: 20px;
                border-collapse: collapse;
            }
            
            .info-table td {
                padding: 8px 12px;
                border: 1px solid #ddd;
                font-size: 11px;
            }
            
            .info-table .label {
                font-weight: bold;
                background-color: #f0f7ff;
                width: 30%;
                color: #1e40af;
            }
            
            .info-table .value {
                background-color: #ffffff;
            }
            
            .section-title {
                background-color: #1e40af;
                color: white;
                padding: 10px 15px;
                font-size: 14px;
                font-weight: bold;
                margin: 20px 0 15px 0;
                border-radius: 4px;
            }
            
            .prenda-card {
                border: 2px solid #3b82f6;
                border-radius: 8px;
                margin-bottom: 20px;
                background-color: white;
                overflow: hidden;
            }
            
            .prenda-card-header {
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                color: white;
                padding: 12px 15px;
                font-size: 12px;
                font-weight: bold;
            }
            
            .prenda-card-content {
                padding: 15px;
            }
            
            .card-section {
                margin-bottom: 15px;
            }
            
            .card-section-title {
                color: #1e40af;
                font-size: 12px;
                font-weight: bold;
                margin-bottom: 8px;
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            .variations-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                font-size: 10px;
            }
            
            .variations-table thead {
                background-color: #1e40af;
                color: white;
            }
            
            .variations-table th,
            .variations-table td {
                padding: 8px;
                text-align: left;
                border: 1px solid #ddd;
            }
            
            .variations-table tbody tr:nth-child(even) {
                background-color: #f9fafb;
            }
            
            .ubicaciones-container {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .ubicacion-item {
                background-color: white;
                border-left: 4px solid #10b981;
                border-radius: 4px;
                padding: 10px;
                font-size: 10px;
            }
            
            .ubicacion-titulo {
                color: #059669;
                font-weight: bold;
                margin-bottom: 5px;
            }
            
            .ubicacion-desc {
                color: #666;
                font-size: 9px;
            }
            
            .description-box {
                background-color: #fef3c7;
                border: 1px solid #fcd34d;
                border-radius: 4px;
                padding: 12px;
                font-size: 11px;
                color: #92400e;
                line-height: 1.5;
                margin-top: 8px;
            }
            
            .observations-box {
                background-color: #f3e8ff;
                border: 1px solid #e9d5ff;
                border-radius: 4px;
                padding: 12px;
                font-size: 10px;
            }
            
            .observation-item {
                background-color: white;
                border-left: 4px solid #1e40af;
                padding: 8px;
                margin-bottom: 8px;
                border-radius: 3px;
                color: #333;
            }
            
            .images-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
                margin-top: 10px;
            }
            
            .image-item {
                border: 1px solid #ddd;
                border-radius: 6px;
                overflow: hidden;
                background-color: #f3f4f6;
                text-align: center;
            }
            
            .image-item img {
                width: 100%;
                height: 120px;
                object-fit: cover;
                display: block;
            }
            
            .image-info {
                padding: 8px;
                background-color: white;
                font-size: 9px;
                color: #666;
            }
            
            .spec-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 10px;
                margin-top: 10px;
            }
            
            .spec-table th {
                background-color: #1e40af;
                color: white;
                padding: 8px;
                text-align: left;
                font-weight: bold;
            }
            
            .spec-table td {
                padding: 8px;
                border-bottom: 1px solid #e5e7eb;
            }
            
            .spec-table tr:nth-child(even) {
                background-color: #f9fafb;
            }
            
            .page-break {
                page-break-after: always;
                margin-bottom: 20px;
            }
        </style>
        ';
    }

    /**
     * Renderizar header del documento
     */
    protected function renderHeader(): string
    {
        $company_logo = asset('logo.png');
        
        return "
        <div class='header'>
            <div class='logo-section'>
                <img src='{$company_logo}' alt='Logo'>
                <div>
                    <div class='header-title'>Cotización Reflectivo</div>
                    <div class='header-subtitle'>Mundo Industrial</div>
                </div>
            </div>
            <div style='text-align: right; font-size: 11px;'>
                <strong>Código:</strong> {$this->cotizacion->codigo}<br>
                <strong>Fecha:</strong> {$this->cotizacion->created_at->format('d/m/Y')}
            </div>
        </div>
        ";
    }

    /**
     * Renderizar información del cliente
     */
    protected function renderClientInfo(): string
    {
        $cliente = $this->cotizacion->cliente->nombre ?? 'N/A';
        $asesor = $this->cotizacion->asesor->nombre_usuario ?? 'N/A';
        $fecha = $this->cotizacion->created_at->format('d/m/Y H:i');
        
        return "
        <table class='info-table'>
            <tr>
                <td class='label'>Cliente:</td>
                <td class='value'>{$cliente}</td>
            </tr>
            <tr>
                <td class='label'>Asesor:</td>
                <td class='value'>{$asesor}</td>
            </tr>
            <tr>
                <td class='label'>Fecha de Cotización:</td>
                <td class='value'>{$fecha}</td>
            </tr>
        </table>
        ";
    }

    /**
     * Renderizar sección de cotización
     */
    protected function renderQuoteSection(): string
    {
        return "
        <div class='section-title'>
            Detalles de Cotización Reflectivo
        </div>
        <div style='font-size: 11px; color: #666; margin-bottom: 15px; line-height: 1.6;'>
            <p><strong>Referencia:</strong> Reflectivo aplicable en prendas</p>
            <p><strong>Tipo de Cotización:</strong> Reflectivo (PASO 4)</p>
        </div>
        ";
    }

    /**
     * Renderizar todas las prendas reflectivo
     */
    protected function renderReflectivos(): string
    {
        $html = '<div class="section-title">Prendas con Reflectivo</div>';
        
        $reflectivoPrendas = $this->cotizacion->reflectivoPrendas()->with([
            'prenda',
            'prenda.tallas',
            'fotos'
        ])->get();
        
        if ($reflectivoPrendas->isEmpty()) {
            $html .= '<p style="color: #666; font-size: 11px;">No hay prendas con reflectivo registradas.</p>';
            return $html;
        }
        
        foreach ($reflectivoPrendas as $reflectivo) {
            $html .= $this->renderReflectivoPrenda($reflectivo);
        }
        
        return $html;
    }

    /**
     * Renderizar una prenda reflectivo individual
     */
    protected function renderReflectivoPrenda($reflectivo): string
    {
        $prenda = $reflectivo->prenda;
        
        // Construir nombre con color (obtener de variantes)
        $nombrePrenda = $prenda->nombre_producto ?? 'Prenda sin nombre';
        $color = '';
        
        // Obtener color de la primera variante si existe
        if ($prenda->variantes && count($prenda->variantes) > 0) {
            $primerVariante = $prenda->variantes->first();
            $color = $primerVariante->color ?? '';
        }
        
        $nombreCompleto = $nombrePrenda;
        if ($color) $nombreCompleto .= " - $color";
        
        $html = '<div class="prenda-card">';
        $html .= '<div class="prenda-card-header">' . htmlspecialchars($nombreCompleto) . '</div>';
        $html .= '<div class="prenda-card-content">';
        
        // Tallas
        $html .= $this->renderTallasReflectivo($prenda);
        
        // Variaciones
        $html .= $this->renderVariacionesReflectivo($prenda);
        
        // Ubicaciones del reflectivo
        $html .= $this->renderUbicacionesReflectivo($prenda);
        
        // Descripción
        if ($reflectivo->descripcion) {
            $html .= '<div class="card-section">';
            $html .= '<div class="card-section-title">Descripción</div>';
            $html .= '<div class="description-box">' . htmlspecialchars($reflectivo->descripcion) . '</div>';
            $html .= '</div>';
        }
        
        // Observaciones
        $html .= $this->renderObservacionesReflectivo($reflectivo);
        
        // Imágenes
        $html .= $this->renderImagenesReflectivo($prenda, $reflectivo);
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Renderizar tallas de la prenda
     */
    protected function renderTallasReflectivo($prenda): string
    {
        if (!$prenda->tallas || $prenda->tallas->isEmpty()) {
            return '';
        }
        
        $html = '<div class="card-section">';
        $html .= '<div class="card-section-title">Tallas</div>';
        $html .= '<div style="display: flex; flex-wrap: wrap; gap: 5px; font-size: 10px;">';
        
        foreach ($prenda->tallas as $talla) {
            $html .= '<span style="background-color: #0284c7; color: white; padding: 4px 8px; border-radius: 3px;">';
            $html .= htmlspecialchars($talla->talla) . ' (' . $talla->cantidad . ')';
            $html .= '</span>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Renderizar variaciones de la prenda
     */
    protected function renderVariacionesReflectivo($prenda): string
    {
        $prendaReflectivo = $this->getPrendaCotReflectivo($prenda->id);
        if (!$prendaReflectivo) {
            return '';
        }
        
        $variaciones = $prendaReflectivo->variaciones;
        if (is_string($variaciones)) {
            $variaciones = json_decode($variaciones, true) ?? [];
        }
        
        if (empty($variaciones) || !is_array($variaciones)) {
            return '';
        }
        
        $variacionesFormateadas = $this->formatearVariaciones($variaciones);
        
        if (empty($variacionesFormateadas)) {
            return '';
        }
        
        $html = '<div class="card-section">';
        $html .= '<div class="card-section-title">Variaciones</div>';
        $html .= '<table class="variations-table">';
        $html .= '<thead><tr>';
        $html .= '<th>Tipo</th>';
        $html .= '<th>Valor</th>';
        $html .= '<th>Observación</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        
        foreach ($variacionesFormateadas as $tipo => $datos) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($tipo) . '</td>';
            $html .= '<td>' . htmlspecialchars($datos['valor']) . '</td>';
            $html .= '<td>' . htmlspecialchars($datos['observacion'] ?? '-') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Renderizar ubicaciones del reflectivo
     */
    protected function renderUbicacionesReflectivo($prenda): string
    {
        $prendaReflectivo = $this->getPrendaCotReflectivo($prenda->id);
        if (!$prendaReflectivo) {
            return '';
        }
        
        $ubicaciones = $prendaReflectivo->ubicaciones;
        if (is_string($ubicaciones)) {
            $ubicaciones = json_decode($ubicaciones, true) ?? [];
        }
        
        if (empty($ubicaciones) || !is_array($ubicaciones)) {
            return '';
        }
        
        $html = '<div class="card-section">';
        $html .= '<div class="card-section-title">Ubicaciones del Reflectivo</div>';
        $html .= '<div class="ubicaciones-container">';
        
        foreach ($ubicaciones as $ubi) {
            $html .= '<div class="ubicacion-item">';
            
            if (isset($ubi['ubicacion']) && !empty($ubi['ubicacion'])) {
                $html .= '<div class="ubicacion-titulo">' . htmlspecialchars($ubi['ubicacion']) . '</div>';
            }
            
            if (isset($ubi['descripcion']) && !empty($ubi['descripcion'])) {
                $html .= '<div class="ubicacion-desc">' . htmlspecialchars($ubi['descripcion']) . '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Renderizar observaciones del reflectivo
     */
    protected function renderObservacionesReflectivo($reflectivo): string
    {
        $obsGenerales = $reflectivo->observaciones_generales ?? [];
        if (is_string($obsGenerales)) {
            $obsGenerales = json_decode($obsGenerales, true) ?? [];
        }
        
        if (empty($obsGenerales) || !is_array($obsGenerales)) {
            return '';
        }
        
        $html = '<div class="card-section">';
        $html .= '<div class="card-section-title">Observaciones</div>';
        $html .= '<div class="observations-box">';
        
        foreach ($obsGenerales as $obs) {
            $html .= '<div class="observation-item">';
            
            if (is_array($obs)) {
                if ($obs['tipo'] === 'checkbox' && $obs['valor'] === true) {
                    $html .= '<strong>' . htmlspecialchars($obs['texto']) . '</strong> ✓';
                } elseif (isset($obs['valor'])) {
                    $html .= '<strong>' . htmlspecialchars($obs['texto']) . ':</strong> ' . htmlspecialchars($obs['valor']);
                } else {
                    $html .= '<strong>' . htmlspecialchars($obs['texto'] ?? $obs) . '</strong>';
                }
            } else {
                $html .= htmlspecialchars($obs);
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Renderizar imágenes del reflectivo y la prenda
     */
    protected function renderImagenesReflectivo($prenda, $reflectivo): string
    {
        $todasLasImagenes = [];
        
        // Imágenes de la prenda (PASO 2)
        if ($prenda->fotos && count($prenda->fotos) > 0) {
            foreach ($prenda->fotos as $foto) {
                $url = $foto->ruta_webp ? '/storage/' . $foto->ruta_webp : null;
                if ($url) {
                    $todasLasImagenes[] = [
                        'url' => asset($url),
                        'tipo' => 'Prenda (PASO 2)',
                        'fecha' => $foto->created_at->format('d/m/Y H:i') ?? ''
                    ];
                }
            }
        }
        
        // Imágenes del reflectivo (PASO 4)
        if ($reflectivo->fotos && count($reflectivo->fotos) > 0) {
            foreach ($reflectivo->fotos as $foto) {
                if ($foto->ruta_original) {
                    $todasLasImagenes[] = [
                        'url' => $foto->url,
                        'tipo' => 'Reflectivo (PASO 4)',
                        'fecha' => $foto->created_at->format('d/m/Y H:i') ?? ''
                    ];
                }
            }
        }
        
        if (empty($todasLasImagenes)) {
            return '';
        }
        
        $html = '<div class="card-section">';
        $html .= '<div class="card-section-title">Imágenes Adjuntas (' . count($todasLasImagenes) . ')</div>';
        $html .= '<div class="images-grid">';
        
        foreach ($todasLasImagenes as $imagen) {
            if ($imagen['url']) {
                $html .= '<div class="image-item">';
                $html .= '<img src="' . htmlspecialchars($imagen['url']) . '" alt="' . htmlspecialchars($imagen['tipo']) . '">';
                $html .= '<div class="image-info">';
                $html .= '<div><strong>' . htmlspecialchars($imagen['tipo']) . '</strong></div>';
                if ($imagen['fecha']) {
                    $html .= '<div style="font-size: 8px; color: #999;">' . htmlspecialchars($imagen['fecha']) . '</div>';
                }
                $html .= '</div>';
                $html .= '</div>';
            }
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Renderizar especificaciones finales
     */
    protected function renderSpecifications(): string
    {
        $especificaciones = $this->cotizacion->especificaciones;
        if (is_string($especificaciones)) {
            $especificaciones = json_decode($especificaciones, true) ?? [];
        }
        
        if (empty($especificaciones) || !is_array($especificaciones)) {
            return '';
        }
        
        $html = '<div style="page-break-before: avoid;" class="section-title">Especificaciones Generales</div>';
        $html .= '<table class="spec-table">';
        $html .= '<thead><tr>';
        $html .= '<th>Categoría</th>';
        $html .= '<th>Valor</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        
        foreach ($especificaciones as $key => $valor) {
            $html .= '<tr>';
            $html .= '<td><strong>' . htmlspecialchars($key) . '</strong></td>';
            
            if (is_array($valor)) {
                $html .= '<td>' . htmlspecialchars($valor['valor'] ?? json_encode($valor)) . '</td>';
            } else {
                $html .= '<td>' . htmlspecialchars($valor) . '</td>';
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        
        return $html;
    }

    /**
     * Obtener el registro PrendaCotReflectivo para una prenda
     */
    protected function getPrendaCotReflectivo($prendaId)
    {
        return \App\Models\PrendaCotReflectivo::where([
            'cotizacion_id' => $this->cotizacion->id,
            'prenda_cot_id' => $prendaId
        ])->first();
    }

    /**
     * Formatear variaciones para mostrar en tabla
     */
    protected function formatearVariaciones(array $variaciones): array
    {
        $formateadas = [];
        
        if (empty($variaciones)) {
            return $formateadas;
        }
        
        $variacion = is_array($variaciones[0]) ? $variaciones[0] : $variaciones;
        
        // Color
        if (isset($variacion['color']) && !empty($variacion['color'])) {
            $formateadas['Color'] = [
                'valor' => $variacion['color'],
                'observacion' => ''
            ];
        }
        
        // Tela
        if (isset($variacion['telas_multiples']) && is_array($variacion['telas_multiples']) && count($variacion['telas_multiples']) > 0) {
            $telaObj = $variacion['telas_multiples'][0];
            if (is_array($telaObj) && isset($telaObj['tela'])) {
                $formateadas['Tela'] = [
                    'valor' => $telaObj['tela'],
                    'observacion' => $telaObj['referencia'] ?? ''
                ];
            }
        }
        
        // Manga
        if (isset($variacion['tipo_manga_id'])) {
            $tipoManga = TipoManga::find($variacion['tipo_manga_id']);
            $nombreManga = $tipoManga ? $tipoManga->nombre : 'Tipo ' . $variacion['tipo_manga_id'];
            $formateadas['Manga'] = [
                'valor' => $nombreManga,
                'observacion' => ''
            ];
        }
        
        // Bolsillo
        if (isset($variacion['tiene_bolsillos'])) {
            $bolsilloValor = $variacion['tiene_bolsillos'] ? 'Sí' : 'No';
            $bolsilloObs = $variacion['obs_bolsillos'] ?? '';
            $formateadas['Bolsillo'] = [
                'valor' => $bolsilloValor,
                'observacion' => $bolsilloObs
            ];
        }
        
        // Broche/Botón
        if (isset($variacion['tipo_broche_id'])) {
            $tipoBroche = TipoBrocheBoton::find($variacion['tipo_broche_id']);
            $nombreBroche = $tipoBroche ? $tipoBroche->nombre : 'Tipo ' . $variacion['tipo_broche_id'];
            $brocheObs = $variacion['obs_broche'] ?? '';
            $formateadas['Broche/Botón'] = [
                'valor' => $nombreBroche,
                'observacion' => $brocheObs
            ];
        }
        
        return $formateadas;
    }
}
