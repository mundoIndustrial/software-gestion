<?php

namespace App\Services\Pedidos;

use App\Models\Cotizacion;
use Illuminate\Support\Facades\DB;

/**
 * CotizacionDataExtractorService
 * 
 * Extrae TODA la información normalizada de una cotización:
 * - Prendas con descripción
 * - Variantes de cada prenda
 * - Telas/colores
 * - Tallas
 * - Fotos
 * 
 * Convierte la información en formato listo para insertar en pedidos
 */
class CotizacionDataExtractorService
{
    /**
     * Extrae toda la información de una cotización
     * 
     * @param Cotizacion $cotizacion
     * @return array Array con estructura pronta para usar en creación de pedido
     */
    public function extraerDatos(Cotizacion $cotizacion): array
    {
        return [
            'cotizacion_id' => $cotizacion->id,
            'numero_cotizacion' => $cotizacion->numero_cotizacion,
            'cliente_id' => $cotizacion->cliente_id,
            'cliente' => $cotizacion->cliente?->nombre,
            'asesor_id' => $cotizacion->asesor_id,
            'prendas' => $this->extraerPrendas($cotizacion->id),
        ];
    }

    /**
     * Extrae todas las prendas de una cotización con sus datos relacionados
     * 
     * @param int $cotizacionId
     * @return array
     */
    private function extraerPrendas(int $cotizacionId): array
    {
        // Obtener todas las prendas de la cotización
        $prendas = DB::table('prendas_cot')
            ->where('cotizacion_id', $cotizacionId)
            ->get();

        $prendasProcesadas = [];

        foreach ($prendas as $prenda) {
            // Obtener variantes (primer elemento si existen)
            $variantes = $this->extraerVariantes($prenda->id);
            $primeraVariante = !empty($variantes) ? $variantes : [];
            
            // Obtener telas/colores (primera si existen)
            $telas = $this->extraerTelas($prenda->id);
            $primeraTela = !empty($telas) ? $telas[0] : [];
            
            // Obtener tallas
            $cantidades = $this->extraerTallas($prenda->id);
            
            // Obtener fotos de prenda
            $fotos = $this->extraerFotos($prenda->id);
            
            // ✅ Obtener logos de prenda
            $logos = $this->extraerLogos($prenda->id);

            $prendasProcesadas[] = [
                'index' => count($prendasProcesadas) + 1,
                'nombre_producto' => $prenda->nombre_producto,
                'descripcion' => $prenda->descripcion,
                'tela' => $primeraTela['nombre_tela'] ?? null,
                'tela_referencia' => $primeraTela['referencia'] ?? null,
                'color' => $primeraTela['color'] ?? null,
                'genero' => $primeraVariante['genero'] ?? null,
                'manga' => $primeraVariante['manga'] ?? null,
                'broche' => $primeraVariante['broche'] ?? null,
                'tiene_bolsillos' => $primeraVariante['tiene_bolsillos'] ?? false,
                'tiene_reflectivo' => $primeraVariante['tiene_reflectivo'] ?? false,
                'manga_obs' => $primeraVariante['manga_obs'] ?? null,
                'bolsillos_obs' => $primeraVariante['bolsillos_obs'] ?? null,
                'broche_obs' => $primeraVariante['broche_obs'] ?? null,
                'reflectivo_obs' => $primeraVariante['reflectivo_obs'] ?? null,
                'observaciones' => $primeraVariante['observaciones'] ?? null,
                'cantidades' => $cantidades,
                'tipo_manga_id' => $primeraVariante['tipo_manga_id'] ?? null,
                'tipo_broche_id' => $primeraVariante['tipo_broche_id'] ?? null,
                'color_id' => $primeraTela['color_id'] ?? null,
                'tela_id' => $primeraTela['tela_id'] ?? null,
                
                // Datos extra: fotos, telas y logos
                'fotos' => $fotos,
                'telas' => $telas,
                'logos' => $logos,
            ];
        }

        return $prendasProcesadas;
    }

    /**
     * Extrae las variantes de una prenda
     * 
     * @param int $prendaCotId
     * @return array
     */
    private function extraerVariantes(int $prendaCotId): array
    {
        $variantes = DB::table('prenda_variantes_cot')
            ->where('prenda_cot_id', $prendaCotId)
            ->get();

        $resultado = [];

        foreach ($variantes as $variante) {
            $resultado = [
                'tipo_prenda' => $variante->tipo_prenda,
                'genero' => $variante->genero_id ? $this->obtenerGenero($variante->genero_id) : null,
                'color' => $variante->color,
                'manga' => $variante->tipo_manga,
                'manga_obs' => $variante->obs_manga,
                'tiene_bolsillos' => (bool)$variante->tiene_bolsillos,
                'bolsillos_obs' => $variante->obs_bolsillos,
                'broche' => $variante->tipo_broche_id ? $this->obtenerBroche($variante->tipo_broche_id) : null,
                'broche_obs' => $variante->obs_broche,
                'tiene_reflectivo' => (bool)$variante->tiene_reflectivo,
                'reflectivo_obs' => $variante->obs_reflectivo,
                'observaciones' => $variante->descripcion_adicional,
                'tipo_manga_id' => $variante->tipo_manga_id,
                'tipo_broche_id' => $variante->tipo_broche_id,
            ];
        }

        return $resultado;
    }

    /**
     * Extrae las telas/colores de una prenda
     * Lee desde prenda_variantes_cot.telas_multiples (JSON)
     * 
     * @param int $prendaCotId
     * @return array
     */
    private function extraerTelas(int $prendaCotId): array
    {
        // Obtener la variante de esta prenda (si existe)
        $variante = DB::table('prenda_variantes_cot')
            ->where('prenda_cot_id', $prendaCotId)
            ->first();

        if (!$variante || empty($variante->telas_multiples)) {
            return [];
        }

        // Decodificar JSON de telas
        $telasJson = json_decode($variante->telas_multiples, true);
        if (!is_array($telasJson)) {
            return [];
        }

        $resultado = [];

        foreach ($telasJson as $telaData) {
            // Buscar el tela_id por nombre en telas_prenda
            $nombreTela = $telaData['tela'] ?? '';
            $tela = DB::table('telas_prenda')
                ->where('nombre', $nombreTela)
                ->first();
            
            // Buscar el color_id por nombre en colores_prenda
            $nombreColor = $telaData['color'] ?? '';
            $color = DB::table('colores_prenda')
                ->where('nombre', $nombreColor)
                ->first();

            $resultado[] = [
                'color' => $nombreColor,
                'nombre_tela' => $nombreTela,
                'referencia' => $telaData['referencia'] ?? '',
                'color_id' => $color?->id ?? null,
                'tela_id' => $tela?->id ?? null,  // ✅ BUSCAR POR NOMBRE
                'fotos' => [],  // Las fotos de tela no se usan en pedidos
            ];
        }

        return $resultado;
    }

    /**
     * Extrae las tallas y cantidades de una prenda
     * Retorna en formato array: ['S' => 50, 'M' => 50, ...]
     * 
     * @param int $prendaCotId
     * @return array
     */
    private function extraerTallas(int $prendaCotId): array
    {
        $tallas = DB::table('prenda_tallas_cot')
            ->where('prenda_cot_id', $prendaCotId)
            ->get();

        $resultado = [];

        foreach ($tallas as $talla) {
            $resultado[$talla->talla] = (int)$talla->cantidad;
        }

        return $resultado;
    }

    /**
     * Extrae las fotos de una prenda
     * 
     * @param int $prendaCotId
     * @return array
     */
    private function extraerFotos(int $prendaCotId): array
    {
        $fotos = DB::table('prenda_fotos_cot')
            ->where('prenda_cot_id', $prendaCotId)
            ->get();

        $resultado = [];

        foreach ($fotos as $foto) {
            $resultado[] = [
                'ruta_original' => $foto->ruta_original,
                'ruta_webp' => $foto->ruta_webp,
                'ruta_miniatura' => $foto->ruta_miniatura,
                'ancho' => $foto->ancho,
                'alto' => $foto->alto,
                'tamaño' => $foto->tamaño,
                'orden' => $foto->orden,
            ];
        }

        return $resultado;
    }

    /**
     * Extrae los logos de una prenda
     * 
     * @param int $prendaCotId
     * @return array
     */
    private function extraerLogos(int $prendaCotId): array
    {
        // Obtener la cotización de la prenda para acceder a los logos
        $prendaCot = DB::table('prendas_cot')->find($prendaCotId);
        if (!$prendaCot) {
            return [];
        }

        // Obtener logos de la cotización
        $logoCotizaciones = DB::table('logo_cotizaciones')
            ->where('cotizacion_id', $prendaCot->cotizacion_id)
            ->first();
        
        if (!$logoCotizaciones) {
            return [];
        }

        // Obtener fotos del logo
        $logos = DB::table('logo_fotos_cot')
            ->where('logo_cotizacion_id', $logoCotizaciones->id)
            ->get();

        $resultado = [];

        foreach ($logos as $logo) {
            $resultado[] = [
                'ruta_original' => $logo->ruta_original,
                'ruta_webp' => $logo->ruta_webp,
                'ruta_miniatura' => $logo->ruta_miniatura,
                'ancho' => $logo->ancho,
                'alto' => $logo->alto,
                'tamaño' => $logo->tamaño,
                'orden' => $logo->orden,
                'ubicacion' => $logo->ubicacion ?? null,
            ];
        }

        return $resultado;
    }

    /**
     * Obtiene el nombre de un género por su ID
     */
    private function obtenerGenero(int $generoId): ?string
    {
        return DB::table('generos_prenda')
            ->where('id', $generoId)
            ->value('nombre');
    }

    /**
     * Obtiene el nombre de un broche por su ID
     */
    private function obtenerBroche(int $brocheId): ?string
    {
        return DB::table('tipos_broche')
            ->where('id', $brocheId)
            ->value('nombre') ?? DB::table('tipo_broche')
            ->where('id', $brocheId)
            ->value('nombre');
    }
}
