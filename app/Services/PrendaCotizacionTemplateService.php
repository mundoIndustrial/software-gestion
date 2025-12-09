<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * PrendaCotizacionTemplateService
 * 
 * Responsabilidad: Generar plantilla formateada de prendas para pedidos relacionados a cotizaciones
 * Formato esperado:
 * 
 * PRENDA 1: CAMISA DRILL BORNEO
 * Color: Naranja | Tela: DRILL BORNEO Ref: REF-DB-001 | Manga: Larga
 * 
 * DESCRIPCION: LOGO BORDADO EN ESPALDA
 * 
 *      .Reflectivo: CON REFLECTIVO GRIS 2" DE 25 CICLOS EN H EN LA PARTE DELANTERA Y TRASERA 2 VUELTAS EN CADA BRAZO Y UNA LINEA A LA ALTURA DEL OMBLIGO
 * 
 *      .Bolsillos: LLEVA BOLSILLOS CON TAPA BOTON Y OJAL CON LOGOS BORDADOS DENTRO DEL BOLSILLO DERECHO "TRANSPORTE" BOLSILLO IZQUIERDO "ANI"
 * 
 * Tallas: S:4, M:3, L:2, XL:1, XXL:1
 */
class PrendaCotizacionTemplateService
{
    /**
     * Generar plantilla de prendas para un pedido relacionado a cotización
     * 
     * @param int $numeroPedido
     * @return array Array con prendas formateadas
     */
    public function generarPlantillaPrendas(int $numeroPedido): array
    {
        try {
            // Obtener todas las prendas del pedido con sus relaciones
            $prendas = DB::table('prendas_pedido')
                ->where('numero_pedido', $numeroPedido)
                ->orderBy('id', 'asc')
                ->get();

            $prendasFormato = [];

            foreach ($prendas as $index => $prenda) {
                $prendasFormato[] = $this->formatearPrenda($prenda, $index + 1);
            }

            return $prendasFormato;
        } catch (\Exception $e) {
            \Log::warning('Error generando plantilla de prendas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Formatear una prenda individual con toda su información
     * 
     * @param object $prenda Objeto de prenda de la BD
     * @param int $numero Número de prenda (1, 2, 3...)
     * @return array Prenda formateada
     */
    private function formatearPrenda($prenda, int $numero): array
    {
        // Primero intentar extraer atributos de la descripción
        $atributosLinea = $this->extraerAtributosDeDescripcion($prenda->descripcion);

        // Si no encuentra en descripción, obtener de BD
        if ($atributosLinea === '-') {
            $color = $this->obtenerColor($prenda->color_id);
            $tela = $this->obtenerTela($prenda->tela_id);
            $manga = $this->obtenerManga($prenda->tipo_manga_id);
            $atributosLinea = $this->formatearAtributos($color, $tela, $manga);
        }

        // Parsear descripción para extraer detalles (reflectivo, bolsillos, etc.)
        // Primero intenta extraer de descripcion_variaciones, si no, de descripcion
        $detalles = $this->extraerDetalles($prenda->descripcion_variaciones);
        
        if (empty($detalles) && $prenda->descripcion) {
            $detalles = $this->extraerDetallesDeDescripcion($prenda->descripcion);
        }

        // Extraer solo la descripción simple (sin "Prenda X:", "Descripción:", etc.)
        $descripcionSimple = $this->extraerDescripcionSimple($prenda->descripcion);

        // Formatear tallas
        $tallasFormato = $this->formatearTallas($prenda->cantidad_talla);

        return [
            'numero' => $numero,
            'nombre' => $prenda->nombre_prenda ?? '-',
            'atributos' => $atributosLinea,
            'descripcion' => $descripcionSimple,
            'detalles' => $detalles,
            'tallas' => $tallasFormato,
        ];
    }

    /**
     * Obtener información del color
     * 
     * @param int|null $colorId
     * @return array|null
     */
    private function obtenerColor($colorId): ?array
    {
        if (!$colorId) {
            return null;
        }

        try {
            $color = DB::table('colores')->find($colorId);
            return $color ? [
                'nombre' => $color->nombre ?? '-',
            ] : null;
        } catch (\Exception $e) {
            \Log::warning('Error obteniendo color: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener información de la tela
     * 
     * @param int|null $telaId
     * @return array|null
     */
    private function obtenerTela($telaId): ?array
    {
        if (!$telaId) {
            return null;
        }

        try {
            $tela = DB::table('telas')->find($telaId);
            return $tela ? [
                'nombre' => $tela->nombre ?? '-',
                'referencia' => $tela->referencia ?? '-',
            ] : null;
        } catch (\Exception $e) {
            \Log::warning('Error obteniendo tela: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener información del tipo de manga
     * 
     * @param int|null $mangaId
     * @return array|null
     */
    private function obtenerManga($mangaId): ?array
    {
        if (!$mangaId) {
            return null;
        }

        try {
            $manga = DB::table('tipo_mangas')->find($mangaId);
            return $manga ? [
                'nombre' => $manga->nombre ?? '-',
            ] : null;
        } catch (\Exception $e) {
            \Log::warning('Error obteniendo manga: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Formatear línea de atributos (Color | Tela | Manga)
     * Ejemplo: Color: Naranja | Tela: DRILL BORNEO Ref: REF-DB-001 | Manga: Larga
     * 
     * @param array|null $color
     * @param array|null $tela
     * @param array|null $manga
     * @return string
     */
    private function formatearAtributos($color, $tela, $manga): string
    {
        $partes = [];

        if ($color) {
            $partes[] = "Color: {$color['nombre']}";
        }

        if ($tela) {
            $telaStr = "Tela: {$tela['nombre']}";
            if ($tela['referencia'] && $tela['referencia'] !== '-') {
                $telaStr .= " Ref: {$tela['referencia']}";
            }
            $partes[] = $telaStr;
        }

        if ($manga) {
            $partes[] = "Manga: {$manga['nombre']}";
        }

        return !empty($partes) ? implode(' | ', $partes) : '-';
    }

    /**
     * Extraer detalles de la descripción de variaciones
     * Busca patrones como "Reflectivo:", "Bolsillos:", etc.
     * 
     * @param string|null $descripcionVariaciones
     * @return array Array de detalles formateados
     */
    private function extraerDetalles($descripcionVariaciones): array
    {
        $detalles = [];

        if (!$descripcionVariaciones) {
            return $detalles;
        }

        // Dividir por líneas
        $lineas = explode("\n", $descripcionVariaciones);

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) {
                continue;
            }

            // Detectar patrones como "Reflectivo:", "Bolsillos:", etc.
            if (preg_match('/^([^:]+):\s*(.+)$/i', $linea, $matches)) {
                $tipo = trim($matches[1]);
                $valor = trim($matches[2]);

                $detalles[] = [
                    'tipo' => $tipo,
                    'valor' => $valor,
                ];
            }
        }

        return $detalles;
    }

    /**
     * Extraer detalles de la descripción (campo de BD)
     * Busca patrones como "Reflectivo: TEXTO", "Bolsillos: TEXTO", etc.
     * 
     * @param string|null $descripcion
     * @return array Array de detalles formateados
     */
    private function extraerDetallesDeDescripcion($descripcion): array
    {
        $detalles = [];

        if (!$descripcion) {
            return $detalles;
        }

        // Buscar todos los patrones "Tipo: Valor"
        // Patrones conocidos: Reflectivo, Bolsillos, etc.
        $patrones = ['Reflectivo', 'Bolsillos', 'Bordado', 'Estampado', 'Ojal'];

        foreach ($patrones as $patron) {
            // Buscar patrón "Tipo: Valor" hasta el siguiente patrón o fin de string
            if (preg_match("/{$patron}:\s*(.+?)(?=\s+(?:Reflectivo:|Bolsillos:|Bordado:|Estampado:|Ojal:|Tallas:|$))/i", $descripcion, $matches)) {
                $valor = trim($matches[1]);
                if (!empty($valor)) {
                    // Remover "SI - " del inicio si existe
                    $valor = preg_replace('/^SI\s*-\s*/i', '', $valor);
                    $valor = trim($valor);
                    
                    if (!empty($valor)) {
                        $detalles[] = [
                            'tipo' => $patron,
                            'valor' => $valor,
                        ];
                    }
                }
            }
        }

        return $detalles;
    }

    /**
     * Extraer atributos (Color, Tela, Manga) de la descripción
     * Busca patrones como "Color: NARANJA Tela: DRILL BORNEO REF:REF-DB-001 Manga: LARGA"
     * 
     * @param string|null $descripcion
     * @return string
     */
    private function extraerAtributosDeDescripcion($descripcion): string
    {
        if (!$descripcion) {
            return '-';
        }

        $atributos = [];

        // Extraer Color
        if (preg_match('/Color:\s*([^\s]+(?:\s+[^\s]+)*?)(?:\s+(?:Tela:|Manga:|Bolsillos:|Reflectivo:|Tallas:|$))/i', $descripcion, $matches)) {
            $color = trim($matches[1]);
            if (!empty($color)) {
                $atributos[] = "Color: {$color}";
            }
        }

        // Extraer Tela (con referencia si existe)
        if (preg_match('/Tela:\s*([^\s]+(?:\s+[^\s]+)*?)(?:\s+(?:Color:|Manga:|Bolsillos:|Reflectivo:|Tallas:|$))/i', $descripcion, $matches)) {
            $tela = trim($matches[1]);
            if (!empty($tela)) {
                $atributos[] = "Tela: {$tela}";
            }
        }

        // Extraer Manga
        if (preg_match('/Manga:\s*([^\s]+(?:\s+[^\s]+)*?)(?:\s+(?:Color:|Tela:|Bolsillos:|Reflectivo:|Tallas:|$))/i', $descripcion, $matches)) {
            $manga = trim($matches[1]);
            if (!empty($manga)) {
                $atributos[] = "Manga: {$manga}";
            }
        }

        return !empty($atributos) ? implode(' | ', $atributos) : '-';
    }

    /**
     * Extraer solo la descripción simple (el texto después de "Descripción:")
     * 
     * @param string|null $descripcion
     * @return string
     */
    private function extraerDescripcionSimple($descripcion): string
    {
        if (!$descripcion) {
            return '-';
        }

        // Buscar el patrón "Descripción: TEXTO" y extraer solo el TEXTO
        if (preg_match('/Descripción:\s*([^T]*?)(?=\s*(?:Tela:|Bolsillos:|Reflectivo:|Tallas:|$))/i', $descripcion, $matches)) {
            $desc = trim($matches[1]);
            if (!empty($desc)) {
                return $desc;
            }
        }

        // Si no encuentra el patrón, intenta extraer entre "Descripción:" y el siguiente campo
        if (preg_match('/Descripción:\s*(.+?)(?:\s+(?:Tela:|Color:|Manga:|Bolsillos:|Reflectivo:|Tallas:)|$)/i', $descripcion, $matches)) {
            $desc = trim($matches[1]);
            if (!empty($desc)) {
                return $desc;
            }
        }

        return '-';
    }

    /**
     * Formatear tallas desde JSON
     * Ejemplo: {"M": 1, "XL": 2} -> "M: 1, XL: 2"
     * 
     * @param string|array|null $cantidadTalla
     * @return string
     */
    private function formatearTallas($cantidadTalla): string
    {
        if (!$cantidadTalla) {
            return '-';
        }

        try {
            // Si es string JSON, parsear
            if (is_string($cantidadTalla)) {
                $tallasObj = json_decode($cantidadTalla, true);
            } else {
                $tallasObj = $cantidadTalla;
            }

            if (!is_array($tallasObj) || empty($tallasObj)) {
                return '-';
            }

            // Formatear como "M: 1, XL: 2"
            $tallasFormato = [];
            foreach ($tallasObj as $talla => $cantidad) {
                $tallasFormato[] = "{$talla}: {$cantidad}";
            }

            return implode(', ', $tallasFormato);
        } catch (\Exception $e) {
            \Log::warning('Error formateando tallas: ' . $e->getMessage());
            return '-';
        }
    }
}
