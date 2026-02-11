<?php

namespace App\Application\Services\PdfDesign;

use App\Models\Cotizacion;

/**
 * CombiadaPdfDesign - Componente de diseño para PDF de cotizaciones combinadas
 * 
 * Responsabilidades:
 * - Generar estructura HTML del PDF de cotizaciones combinadas (Prenda + Logo)
 * - Manejar estilos y diseño visual (rescatados de PrendaPdfDesign)
 * - Renderizar datos exactamente como aparecen en el modal de contador
 * - Armar las secciones: encabezado, cliente, prendas con variaciones, especificaciones
 * 
 * No es responsable de:
 * - Lógica de negocio
 * - Control de acceso
 * - Generación del PDF (eso lo hace mPDF)
 * - Manejo de memoria
 */
class CombiadaPdfDesign
{
    private Cotizacion $cotizacion;

    public function __construct(Cotizacion $cotizacion)
    {
        $this->cotizacion = $cotizacion;
    }

    /**
     * Genera el HTML completo del PDF de cotización combinada
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
        .variaciones-table th { padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2); }
        .variaciones-table td { padding: 0.75rem; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
        .variaciones-table tr:nth-child(even) { background: #f9fafb; }
        .variaciones-table .var-label { background: #f5f5f5; font-weight: 600; color: #0f172a; }
        .variaciones-table .var-valor { color: #0ea5e9; font-weight: 500; }
        
        /* Imágenes lado a lado */
        .imagenes-grupo { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 10px; margin-bottom: 10px; }
        .imagen-container { display: flex; flex-direction: column; align-items: center; gap: 4px; width: 80px; }
        .imagen-box { border: 2px solid #1e5ba8; padding: 4px; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; background: #f9f9f9; flex-shrink: 0; border-radius: 4px; overflow: hidden; }
        .imagen-box img { width: 100%; height: 100%; max-width: 100%; max-height: 100%; object-fit: contain; display: block; }
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
                    <div style="font-size: 12px; font-weight: bold; margin-top: 4px;">COTIZACIÓN COMBINADA</div>
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
     * Renderiza todas las prendas con sus variaciones y logos
     */
    private function renderPrendas(): string
    {
        // Cargar prendas con todas sus relaciones
        $prendas = $this->cotizacion->prendas()
            ->with([
                'telas.tela',
                'variantes.manga',
                'variantes.broche',
                'tallas',
                'fotos',
                'telaFotos'
            ])
            ->get() ?? [];

        // Cargar técnicas de prendas del logo
        $tecnicasPrendas = [];
        if ($this->cotizacion->logoCotizacion) {
            $tecnicasPrendas = $this->cotizacion->logoCotizacion->tecnicasPrendas()
                ->with(['prenda', 'tipoLogo', 'fotos'])
                ->get() ?? [];
        }

        if ($prendas->isEmpty()) {
            return '';
        }

        $html = '<div class="prendas-wrapper">';

        // Agregar mensaje de tipo de venta antes del primer card
        $tipoVenta = $this->obtenerTipoVenta();
        if ($tipoVenta) {
            $html .= '
                <div style="margin-bottom: 20px; padding: 10px; background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border: 2px solid #ef4444; border-radius: 8px; text-align: left;">
                    <span style="color: #000000; font-size: 12px; font-weight: 600;">
                        Por favor cotizar al 
                    </span>
                    <span style="color: #dc2626; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-left: 8px;">
                        ' . htmlspecialchars($tipoVenta) . '
                    </span>
                </div>
            ';
        }

        foreach ($prendas as $index => $prenda) {
            $html .= $this->renderPrendaCard($prenda, $index, $tecnicasPrendas);
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza una prenda en card con diseño similar al modal contador
     */
    private function renderPrendaCard($prenda, int $index, $tecnicasPrendas): string
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

        // Contenido
        $html .= '<div class="prenda-contenido">';
        
        // Descripción concatenada (como en el modal)
        $html .= $this->renderDescripcion($prenda, $tecnicasPrendas);
        
        // Tabla de variaciones del logo (si existe)
        // NO usar toArray() aquí para mantener los objetos
        $tecnicasPrendaArray = collect($tecnicasPrendas)->filter(fn($tp) => $tp->prenda_cot_id === $prenda->id);
        if ($tecnicasPrendaArray->isNotEmpty()) {
            $html .= $this->renderVariacionesLogo($tecnicasPrendaArray);
        }
        
        // Tabla de variaciones específicas de la prenda
        $html .= $this->renderVariacionesPrenda($prenda);
        
        // Imágenes lado a lado
        $html .= $this->renderImagenes($prenda, $tecnicasPrendaArray);

        $html .= '</div>';

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
        $tallasCollection = $prenda->tallas ?? null;
        if (!$tallasCollection || (is_object($tallasCollection) && method_exists($tallasCollection, 'isEmpty') && $tallasCollection->isEmpty())) {
            return '<div class="prenda-tallas">Tallas: ' . htmlspecialchars('Sin tallas') . '</div>';
        }

        // Agrupar por color y luego por género; sobremedida se muestra como etiqueta roja
        $gruposPorColor = [];
        foreach ($tallasCollection as $t) {
            if (!$t) {
                continue;
            }

            $talla = is_array($t) ? ($t['talla'] ?? null) : ($t->talla ?? null);
            if (!$talla) {
                continue;
            }

            $color = is_array($t) ? ($t['color'] ?? null) : ($t->color ?? null);
            $colorKey = $color ? trim((string) $color) : 'Sin color';
            if (!isset($gruposPorColor[$colorKey])) {
                $gruposPorColor[$colorKey] = [];
            }
            $gruposPorColor[$colorKey][] = $t;
        }

        ksort($gruposPorColor, SORT_NATURAL | SORT_FLAG_CASE);

        $fmtGrupo = function(array $arr): string {
            $items = [];
            foreach ($arr as $x) {
                $talla = is_array($x) ? ($x['talla'] ?? '') : ($x->talla ?? '');
                $talla = trim((string) $talla);
                if ($talla === '') {
                    continue;
                }

                $cantidad = is_array($x) ? ($x['cantidad'] ?? null) : ($x->cantidad ?? null);
                if ($cantidad !== null && $cantidad !== '' && (int) $cantidad !== 1) {
                    $items[] = $talla . ' (' . (int) $cantidad . ')';
                } else {
                    $items[] = $talla;
                }
            }
            return implode(', ', $items);
        };

        $html = '<div class="prenda-tallas">';
        $html .= '<div><strong>TALLAS:</strong></div>';

        foreach ($gruposPorColor as $colorKey => $group) {
            $tallasCaballero = [];
            $tallasDama = [];
            $tallasSinGenero = [];
            $haySobremedida = false;

            foreach ($group as $t) {
                $generoId = is_array($t) ? ($t['genero_id'] ?? null) : ($t->genero_id ?? null);
                $talla = is_array($t) ? ($t['talla'] ?? null) : ($t->talla ?? null);
                $cantidad = is_array($t) ? ($t['cantidad'] ?? null) : ($t->cantidad ?? null);
                if (!$talla) {
                    continue;
                }

                $tallaLower = strtolower(trim((string) $talla));
                $esSobremedida = ($tallaLower === 'sobremedida' || $tallaLower === 'cantidad')
                    && ($generoId === null || $generoId === '' || $generoId === 0 || $generoId === '0');
                if ($esSobremedida) {
                    $haySobremedida = true;
                    continue;
                }

                $item = [
                    'talla' => (string) $talla,
                    'cantidad' => $cantidad,
                ];

                if ((string) $generoId === '1') {
                    $tallasCaballero[] = $item;
                } elseif ((string) $generoId === '2') {
                    $tallasDama[] = $item;
                } else {
                    $tallasSinGenero[] = $item;
                }
            }

            $cabTxt = $fmtGrupo($tallasCaballero);
            $damaTxt = $fmtGrupo($tallasDama);
            $sinTxt = $fmtGrupo($tallasSinGenero);

            $html .= '<div style="margin-top: 6px; padding-top: 4px; border-top: 1px dashed rgba(0,0,0,0.18);">'
                . '<span style="color:#0f172a; font-weight:900; font-size: 9px; text-transform: uppercase;">' . htmlspecialchars($colorKey) . '</span>'
                . '</div>';

            if ($cabTxt !== '') {
                $html .= '<div><span style="color:#1e5ba8; font-weight:800;">Caballero:</span> <span style="color:#e74c3c;">' . htmlspecialchars($cabTxt) . '</span></div>';
            }
            if ($damaTxt !== '') {
                $html .= '<div><span style="color:#1e5ba8; font-weight:800;">Dama:</span> <span style="color:#e74c3c;">' . htmlspecialchars($damaTxt) . '</span></div>';
            }
            if ($haySobremedida) {
                $html .= '<div style="margin-top: 2px;"><span style="color:#e74c3c; font-weight:900;">Sobremedida</span></div>';
            }
            if ($sinTxt !== '') {
                $html .= '<div><span style="color:#1e5ba8; font-weight:800;">Sin género:</span> <span style="color:#e74c3c;">' . htmlspecialchars($sinTxt) . '</span></div>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Renderiza la descripción consolidada
     */
    private function renderDescripcion($prenda, $tecnicasPrendas): string
    {
        $descripciones = [];
        
        // Descripción de la prenda base
        if ($prenda->descripcion) {
            $descripciones[] = htmlspecialchars($prenda->descripcion);
        }
        
        // Observación de reflectivo desde variantes
        if ($prenda->variantes && $prenda->variantes->isNotEmpty()) {
            foreach ($prenda->variantes as $variante) {
                if ($variante->obs_reflectivo && !empty($variante->obs_reflectivo)) {
                    $descripciones[] = htmlspecialchars($variante->obs_reflectivo);
                }
            }
        }
        
        // Ubicaciones de logo para esta prenda
        $tecnicasPrendaArray = collect($tecnicasPrendas)->filter(fn($tp) => $tp->prenda_cot_id === $prenda->id);
        
        if ($tecnicasPrendaArray->isNotEmpty()) {
            $ubicacionesPorTecnica = [];
            foreach ($tecnicasPrendaArray as $tp) {
                $tecnicaNombre = $tp->tipoLogo ? $tp->tipoLogo->nombre : 'Logo';
                if ($tp->ubicaciones) {
                    $ubicacionesArray = is_array($tp->ubicaciones) ? $tp->ubicaciones : [$tp->ubicaciones];
                    $ubicacionesArray = array_map(fn($u) => is_array($u) ? $u['ubicacion'] ?? $u : $u, $ubicacionesArray);
                    $ubicacionesArray = array_filter($ubicacionesArray, fn($u) => !empty($u));
                    
                    // Limpiar cada ubicación: quitar corchetes y comillas
                    $ubicacionesArray = array_map(function($ubicacion) {
                        // Quitar corchetes al inicio y final
                        $ubicacion = preg_replace('/^\[|\]$/', '', $ubicacion);
                        // Quitar comillas al inicio y final
                        $ubicacion = preg_replace('/^["\']|["\']$/', '', $ubicacion);
                        return trim($ubicacion);
                    }, $ubicacionesArray);
                    
                    if (!empty($ubicacionesArray)) {
                        $ubicacionesPorTecnica[$tecnicaNombre] = $ubicacionesArray;
                    }
                }
            }
            
            if (!empty($ubicacionesPorTecnica)) {
                $ubicacionesTexto = [];
                foreach ($ubicacionesPorTecnica as $tecnica => $ubicaciones) {
                    $ubicacionesTexto[] = implode(', ', $ubicaciones);
                }
                $descripciones[] = implode(', ', $ubicacionesTexto);
            }
        }
        
        if (empty($descripciones)) {
            return '';
        }

        $html = '<div class="prenda-descripcion">';
        $html .= '<strong>DESCRIPCION:</strong> ' . implode(', ', $descripciones);
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza tabla de variaciones del logo
     */
    private function renderVariacionesLogo($tecnicasPrendaArray): string
    {
        // Consolidar todas las variaciones
        $variacionesFormateadas = [];
        
        // Iterar sobre la colección o array
        $items = is_object($tecnicasPrendaArray) && method_exists($tecnicasPrendaArray, 'toArray') 
            ? $tecnicasPrendaArray 
            : (is_array($tecnicasPrendaArray) ? collect($tecnicasPrendaArray) : []);
        
        foreach ($items as $tp) {
            // Acceder a las propiedades del objeto
            $variacionesPrenda = is_array($tp) ? ($tp['variaciones_prenda'] ?? null) : ($tp->variaciones_prenda ?? null);
            
            if ($variacionesPrenda && is_array($variacionesPrenda)) {
                foreach ($variacionesPrenda as $opcionNombre => $detalles) {
                    if (is_array($detalles) && isset($detalles['opcion'])) {
                        $nombreFormato = ucfirst(str_replace('_', ' ', $opcionNombre));
                        if (!isset($variacionesFormateadas[$nombreFormato])) {
                            $variacionesFormateadas[$nombreFormato] = $detalles;
                        }
                    }
                }
            }
        }

        if (empty($variacionesFormateadas)) {
            return '';
        }

        $html = '<table class="variaciones-table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th style="width: 30%;">Tipo</th>';
        $html .= '<th style="width: 35%;">Valor</th>';
        $html .= '<th style="width: 35%;">Observación</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($variacionesFormateadas as $tipo => $datos) {
            $opcion = htmlspecialchars($datos['opcion'] ?? '-');
            $observacion = htmlspecialchars($datos['observacion'] ?? '-');
            $html .= '<tr>';
            $html .= '<td class="var-label">' . htmlspecialchars($tipo) . '</td>';
            $html .= '<td class="var-valor">' . $opcion . '</td>';
            $html .= '<td>' . $observacion . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    /**
     * Renderiza tabla de variaciones específicas de la prenda
     */
    private function renderVariacionesPrenda($prenda): string
    {
        $variantes = $prenda->variantes ?? [];

        if ($variantes->isEmpty()) {
            return '';
        }

        $html = '<table class="variaciones-table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th style="width: 30%;">Variación</th>';
        $html .= '<th style="width: 35%;">Tipo</th>';
        $html .= '<th style="width: 35%;">Observaciones</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $filas = [];
        foreach ($variantes as $var) {
            // Tipo Manga
            if ($var->tipo_manga_id || $var->tipo_manga) {
                $tipo = $var->manga?->nombre ?? $var->tipo_manga ?? 'Sin especificar';
                $filas[] = [
                    'variacion' => 'Tipo Manga',
                    'tipo' => htmlspecialchars($tipo),
                    'obs' => htmlspecialchars($var->obs_manga ?? '-')
                ];
            }
            
            // Bolsillos
            if ($var->tiene_bolsillos) {
                $filas[] = [
                    'variacion' => 'Bolsillos',
                    'tipo' => 'Sí',
                    'obs' => htmlspecialchars($var->obs_bolsillos ?? '-')
                ];
            }
            
            // Broche/Botón
            if ($var->tipo_broche_id || $var->obs_broche) {
                $tipo = $var->broche?->nombre ?? 'Sin especificar';
                $filas[] = [
                    'variacion' => 'Broche/Botón',
                    'tipo' => htmlspecialchars($tipo),
                    'obs' => htmlspecialchars($var->obs_broche ?? '-')
                ];
            }
        }

        foreach ($filas as $idx => $fila) {
            $html .= '<tr>';
            $html .= '<td class="var-label">' . $fila['variacion'] . '</td>';
            $html .= '<td class="var-valor">' . $fila['tipo'] . '</td>';
            $html .= '<td>' . $fila['obs'] . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    /**
     * Renderiza imágenes lado a lado (versión simplificada para depuración)
     */
    private function renderImagenesSimplificado($prenda, $tecnicasPrendaArray): string
    {
        try {
            \Log::info("Iniciando renderImagenesSimplificado");
            
            // Versión ultra simplificada sin imágenes
            $html = '<table style="width: 100%; border-collapse: collapse; margin: 10px 0;">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="background: #e8eef7; padding: 8px; border: 1px solid #000; text-align: center;">Logo</th>';
            $html .= '<th style="background: #e8eef7; padding: 8px; border: 1px solid #000; text-align: center;">Tela</th>';
            $html .= '<th style="background: #e8eef7; padding: 8px; border: 1px solid #000; text-align: center;">Prenda</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border: 1px solid #000; text-align: center;">';
            $html .= '<div style="color: #666; font-size: 9px;">Logo disponible</div>';
            $html .= '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #000; text-align: center;">';
            $html .= '<div style="color: #666; font-size: 9px;">Tela disponible</div>';
            $html .= '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #000; text-align: center;">';
            $html .= '<div style="color: #666; font-size: 9px;">Prenda disponible</div>';
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</tbody>';
            $html .= '</table>';

            \Log::info("renderImagenesSimplificado completado");
            return $html;

        } catch (\Exception $e) {
            \Log::error('Error en renderImagenesSimplificado: ' . $e->getMessage());
            return '<div style="color: #999; font-size: 9px; padding: 10px;">Error al cargar imágenes</div>';
        }
    }

    /**
     * Renderiza imágenes lado a lado
     */
    private function renderImagenes($prenda, $tecnicasPrendaArray): string
    {
        try {
            \Log::info("Iniciando renderImagenes con imágenes base64");
            
            // Agrupar imágenes por tipo
            $imagenesPorTipo = [
                'Logo' => [],
                'Tela' => [],
                'Prenda' => [],
                'Reflectivo' => []
            ];

            // Logo de cada técnica
            foreach ($tecnicasPrendaArray as $tp) {
                if ($tp->fotos && count($tp->fotos) > 0) {
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

            // Fallback: logos adjuntados en logo_cotizacion (cuando no hay técnicas por prenda)
            if (empty($imagenesPorTipo['Logo']) && $this->cotizacion->logoCotizacion && $this->cotizacion->logoCotizacion->fotos) {
                foreach ($this->cotizacion->logoCotizacion->fotos as $foto) {
                    if ($foto && ($foto->url ?? null)) {
                        $imagenesPorTipo['Logo'][] = [
                            'url' => $foto->url,
                            'titulo' => 'Logo'
                        ];
                    }
                }
            }

            // Tela
            if ($prenda->telaFotos && count($prenda->telaFotos) > 0) {
                foreach ($prenda->telaFotos as $idx => $foto) {
                    $url = $foto->url ?? $foto->ruta_webp ?? $foto->ruta_original ?? null;
                    if ($url) {
                        $imagenesPorTipo['Tela'][] = [
                            'url' => $url,
                            'titulo' => 'Tela'
                        ];
                    }
                }
            }

            // Tela (imagenes de cada tela asociada)
            if ($prenda->telas && count($prenda->telas) > 0) {
                foreach ($prenda->telas as $tela) {
                    $url = is_array($tela) ? ($tela['url_imagen'] ?? null) : ($tela->url_imagen ?? null);
                    if ($url) {
                        $imagenesPorTipo['Tela'][] = [
                            'url' => $url,
                            'titulo' => 'Tela'
                        ];
                    }
                }
            }

            // Prenda
            if ($prenda->fotos && count($prenda->fotos) > 0) {
                foreach ($prenda->fotos as $idx => $foto) {
                    $url = $foto->url ?? $foto->ruta_webp ?? $foto->ruta_original ?? null;
                    if ($url) {
                        $imagenesPorTipo['Prenda'][] = [
                            'url' => $url,
                            'titulo' => 'Prenda'
                        ];
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

            // Galería única (como en el modal). IMPORTANTE: mPDF no soporta bien flex,
            // así que usamos una tabla-grid para forzar layout horizontal.
            // Usar un número conservador de columnas para asegurar que nunca se salga del ancho del PDF.
            // Si se agregan más imágenes, automáticamente salta a la siguiente fila.
            $colsPorFila = 5;
            $html = '<table style="width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 6px; margin-top: 10px; margin-bottom: 10px;">';
            $html .= '<tbody><tr>';
            $colIdx = 0;

            // Unificar imágenes en un solo arreglo conservando el tipo/label
            $imagenesFlat = [];
            foreach (['Logo', 'Tela', 'Prenda'] as $tipo) {
                foreach (($imagenesPorTipo[$tipo] ?? []) as $img) {
                    $imagenesFlat[] = [
                        'tipo' => $tipo,
                        'url' => $img['url'] ?? null,
                        'titulo' => $img['titulo'] ?? $tipo,
                    ];
                }
            }

            $seen = [];
            foreach ($imagenesFlat as $img) {
                $rawUrl = $img['url'] ?? null;
                if (!$rawUrl) {
                    continue;
                }

                if (isset($seen[$rawUrl])) {
                    continue;
                }
                $seen[$rawUrl] = true;

                $imagenUrl = $rawUrl;
                if (!str_starts_with($imagenUrl, 'http')) {
                    if (str_starts_with($imagenUrl, '/storage/')) {
                        $imagenUrl = asset($imagenUrl);
                    } else {
                        $imagenUrl = asset('storage/' . ltrim($imagenUrl, '/'));
                    }
                }

                \Log::info("Procesando imagen {$img['tipo']}: {$imagenUrl}");

                $base64Image = $this->convertImageToBase64($imagenUrl);
                if (!$base64Image) {
                    continue;
                }

                $imageType = 'image/jpeg';
                if (is_string($rawUrl) && str_ends_with(strtolower($rawUrl), '.png')) {
                    $imageType = 'image/png';
                } elseif (is_string($rawUrl) && str_ends_with(strtolower($rawUrl), '.webp')) {
                    $imageType = 'image/webp';
                }

                $label = htmlspecialchars((string) ($img['titulo'] ?? $img['tipo']));

                if ($colIdx > 0 && ($colIdx % $colsPorFila) === 0) {
                    $html .= '</tr><tr>';
                }
                $colIdx++;

                $html .= '<td style="width: 90px; vertical-align: top;">'
                    . '<div class="imagen-container">'
                    // Caja fija compatible con mPDF
                    . '<table style="border-collapse: collapse; width: 80px; height: 80px; margin: 0 auto;">'
                    . '<tr>'
                    . '<td style="border: 2px solid #1e5ba8; background: #f9f9f9; width: 80px; height: 80px; text-align: center; vertical-align: middle; padding: 0;">'
                    // Forzar que nunca exceda el cuadro (mPDF respeta max-width/max-height mejor que object-fit)
                    . '<img src="data:' . $imageType . ';base64,' . $base64Image . '" alt="' . $label . '" style="max-width: 72px; max-height: 72px; width: auto; height: auto; display: inline-block;">'
                    . '</td>'
                    . '</tr>'
                    . '</table>'
                    . '<div class="imagen-label">' . $label . '</div>'
                    . '</div>'
                    . '</td>';
            }

            $html .= '</tr></tbody></table>';

            \Log::info("renderImagenes completado con imágenes base64");
            return $html;

        } catch (\Exception $e) {
            \Log::error('Error en renderImagenes: ' . $e->getMessage(), [
                'prenda_id' => $prenda->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return '<div style="color: #999; font-size: 9px; padding: 10px;">Error al cargar imágenes</div>';
        }
    }

    /**
     * Convierte una imagen URL a base64
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
     * Renderiza tabla de especificaciones generales
     */
    private function renderEspecificaciones(): string
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

    /**
     * Obtener el tipo de venta desde la fuente correcta
     */
    private function obtenerTipoVenta(): ?string
    {
        // Verificar si es cotización de logo para obtener tipo_venta de logo_cotizaciones
        if ($this->cotizacion->logoCotizacion && $this->cotizacion->logoCotizacion->tipo_venta) {
            // Es cotización de logo, obtener de logo_cotizaciones
            return $this->cotizacion->logoCotizacion->tipo_venta;
        } elseif ($this->cotizacion->tipo_venta) {
            // Es cotización normal, obtener de cotizaciones
            return $this->cotizacion->tipo_venta;
        }
        
        return null;
    }
}
