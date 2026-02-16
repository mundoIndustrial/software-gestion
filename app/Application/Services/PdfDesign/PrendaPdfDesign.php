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
        @page { margin: 0; }
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
        .prenda-tallas { font-size: 10px; color: #0f172a; font-weight: bold; }
        
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

        /* Tabla de especificaciones (mismo diseño que PDF combinada) */
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
                'telaFotos'
            ])
            ->get() ?? [];

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
        
        // Observación de reflectivo desde variantes
        if ($prenda->variantes && $prenda->variantes->isNotEmpty()) {
            foreach ($prenda->variantes as $variante) {
                if ($variante->obs_reflectivo && !empty($variante->obs_reflectivo)) {
                    $descripciones[] = htmlspecialchars($variante->obs_reflectivo);
                }
            }
        }
        
        // Ubicaciones de logo para esta prenda
        if ($this->cotizacion->logoCotizacion && $this->cotizacion->logoCotizacion->tecnicasPrendas) {
            $tecnicasPrendaArray = $this->cotizacion->logoCotizacion->tecnicasPrendas
                ->filter(fn($tp) => $tp->prenda_cot_id === $prenda->id);
            
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
        $html .= $this->renderImagenesVariaciones($prenda);
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
                if (!$talla) continue;

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

    private function renderEspecificaciones(): string
    {
        if (!$this->cotizacion->especificaciones || empty($this->cotizacion->especificaciones)) {
            return '';
        }

        // MISMA ESTRUCTURA/LÓGICA QUE PDF COMBINADA
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
            $obsManga = $var->obs_manga ?? '';

            // Fila de manga
            if ($mangaId) {
                $tipoManga = $var->manga?->nombre ?? ($var->tipo_manga ?? 'Sin especificar');
                $mangaTxt = 'Tipo: ' . $tipoManga;
                if ($obsManga) {
                    $mangaTxt .= ' - ' . $obsManga;
                }
                $html .= '<tr>';
                $html .= '<td class="var-label">Manga</td>';
                $html .= '<td>' . htmlspecialchars($mangaTxt) . '</td>';
                $html .= '</tr>';
            }

            // Fila de bolsillos
            if ($tieneBosillos && $obsBolsillos) {
                $html .= '<tr>';
                $html .= '<td class="var-label">Bolsillos</td>';
                $html .= '<td>' . htmlspecialchars($obsBolsillos) . '</td>';
                $html .= '</tr>';
            }

            // Fila de broche/botón
            if ($brocheId || $obsBroche) {
                $html .= '<tr>';
                $html .= '<td class="var-label">Broche/Botón</td>';
                
                $brocheInfo = [];
                // Tipo de broche
                if ($brocheId && $var->broche && $var->broche->nombre) {
                    $brocheInfo[] = htmlspecialchars($var->broche->nombre);
                }
                // Observación de broche
                if ($obsBroche) {
                    $brocheInfo[] = htmlspecialchars($obsBroche);
                }
                
                $html .= '<td>' . implode(' - ', $brocheInfo) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</table>';

        return $html;
    }

    /**
     * Renderiza las imágenes de variaciones de la prenda
     */
    private function renderImagenesVariaciones($prenda): string
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
