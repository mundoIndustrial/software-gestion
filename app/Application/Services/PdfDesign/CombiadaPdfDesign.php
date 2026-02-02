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
                'telaFotos',
                'prendaCotReflectivo:id,prenda_cot_id,variaciones,ubicaciones'
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
        $tallas = $prenda->tallas ? $prenda->tallas->pluck('talla')->implode(', ') : 'Sin tallas';
        return '<div class="prenda-tallas">Tallas: ' . htmlspecialchars($tallas) . '</div>';
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
        
        // Descripción de reflectivo si existe
        // NOTA: prendaCotReflectivo es HasMany, así que devuelve una colección
        // Accedemos al primer elemento si existe
        $prendaCotReflectivo = $prenda->prendaCotReflectivo;
        if ($prendaCotReflectivo) {
            // Si es una colección, obtener el primer elemento
            $reflectivo = is_object($prendaCotReflectivo) && method_exists($prendaCotReflectivo, 'first') 
                ? $prendaCotReflectivo->first() 
                : $prendaCotReflectivo;
            
            if ($reflectivo && $reflectivo->descripcion) {
                $descripciones[] = htmlspecialchars($reflectivo->descripcion);
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
            
            // Broche
            if ($var->aplica_broche) {
                $tipo = $var->broche?->nombre ?? 'Sí';
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
     * Renderiza imágenes lado a lado
     */
    private function renderImagenes($prenda, $tecnicasPrendaArray): string
    {
        $imagenes = [];

        // Logo de cada técnica
        foreach ($tecnicasPrendaArray as $tp) {
            if ($tp->fotos && count($tp->fotos) > 0) {
                foreach ($tp->fotos as $foto) {
                    if ($foto->url) {
                        $imagenes[] = [
                            'grupo' => 'Logo - ' . ($tp->tipoLogo?->nombre ?? 'Logo'),
                            'url' => $foto->url,
                            'titulo' => 'Logo'
                        ];
                    }
                }
            }
        }

        // Tela
        if ($prenda->telaFotos && count($prenda->telaFotos) > 0) {
            foreach ($prenda->telaFotos as $idx => $foto) {
                $imagenes[] = [
                    'grupo' => 'Tela',
                    'url' => $foto->url ?? $foto->ruta_webp,
                    'titulo' => 'Tela'
                ];
            }
        }

        // Prenda
        if ($prenda->fotos && count($prenda->fotos) > 0) {
            foreach ($prenda->fotos as $idx => $foto) {
                $imagenes[] = [
                    'grupo' => 'Prenda',
                    'url' => $foto->url,
                    'titulo' => 'Prenda'
                ];
            }
        }

        if (empty($imagenes)) {
            return '';
        }

        $html = '<div class="imagenes-grupo">';

        foreach ($imagenes as $img) {
            $imagenUrl = $img['url'];
            if (!str_starts_with($imagenUrl, 'http')) {
                $ruta = str_starts_with($imagenUrl, '/storage/') ? $imagenUrl : '/storage/' . ltrim($imagenUrl, '/');
                $imagenUrl = public_path(ltrim($ruta, '/'));
            }

            if (file_exists($imagenUrl)) {
                $html .= '<div class="imagen-container">';
                $html .= '<div class="imagen-box">';
                $html .= '<img src="' . $imagenUrl . '" alt="' . htmlspecialchars($img['titulo']) . '">';
                $html .= '</div>';
                $html .= '<div class="imagen-label">' . htmlspecialchars($img['grupo']) . '</div>';
                $html .= '</div>';
            }
        }

        $html .= '</div>';

        return $html;
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
}
