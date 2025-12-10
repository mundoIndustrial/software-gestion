<?php

namespace App\Application\Services;

use App\Models\Cotizacion;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestionar cotizaciones con prendas
 * Integra la nueva arquitectura de prendas
 * 
 * Responsabilidades:
 * - Guardar productos en tablas de cotización
 * - Registrar información de prendas
 * 
 * NOTA: El procesamiento de imágenes se hace en el controlador
 * usando ImagenProcesadorService ANTES de pasar los datos aquí
 */
class CotizacionPrendaService
{
    /**
     * Guardar productos en una cotización
     * 
     * Guarda los productos en tablas normalizadas:
     * - prendas_cot (prenda principal)
     * - prenda_fotos_cot (fotos individuales)
     * - prenda_telas_cot (telas individuales)
     * - prenda_tallas_cot (tallas individuales)
     * - prenda_variantes_cot (variantes)
     */
    public function guardarProductosEnCotizacion(Cotizacion $cotizacion, array $productos): void
    {
        if (empty($productos)) {
            Log::info('No hay productos para guardar en cotización', ['cotizacion_id' => $cotizacion->id]);
            return;
        }

        Log::info('📦 Guardando productos en tablas normalizadas', [
            'cotizacion_id' => $cotizacion->id,
            'productos_count' => count($productos),
        ]);

        foreach ($productos as $index => $productoData) {
            try {
                $nombre = $productoData['nombre'] ?? $productoData['nombre_producto'] ?? 'Sin nombre';
                
                // 1. Guardar prenda principal en prendas_cot
                $prenda = $cotizacion->prendas()->create([
                    'nombre_producto' => $nombre,
                    'descripcion' => $productoData['descripcion'] ?? '',
                    'cantidad' => $productoData['cantidad'] ?? 1,
                ]);
                
                Log::info("✅ Prenda creada en prendas_cot", [
                    'prenda_id' => $prenda->id,
                    'nombre' => $nombre
                ]);
                
                // 2. Guardar fotos en prenda_fotos_cot
                $fotos = $productoData['fotos'] ?? [];
                if (!empty($fotos)) {
                    foreach ($fotos as $foto) {
                        $prenda->fotos()->create([
                            'url' => $foto,
                            'nombre' => basename($foto)
                        ]);
                    }
                    Log::info("✅ Fotos guardadas", ['cantidad' => count($fotos)]);
                }
                
                // 3. Guardar telas en prenda_telas_cot
                $telas = $productoData['telas'] ?? [];
                if (!empty($telas)) {
                    foreach ($telas as $tela) {
                        $prenda->telas()->create([
                            'color' => $tela['color'] ?? '',
                            'nombre_tela' => $tela['tela'] ?? '',
                            'referencia' => $tela['referencia'] ?? '',
                            'url_imagen' => $tela['url'] ?? ''
                        ]);
                    }
                    Log::info("✅ Telas guardadas", ['cantidad' => count($telas)]);
                }
                
                // 4. Guardar tallas en prenda_tallas_cot
                $tallas = $productoData['tallas'] ?? [];
                if (!empty($tallas)) {
                    foreach ($tallas as $talla) {
                        $prenda->tallas()->create([
                            'talla' => $talla,
                            'cantidad' => 0  // Se puede actualizar después
                        ]);
                    }
                    Log::info("✅ Tallas guardadas", ['cantidad' => count($tallas)]);
                }
                
                // 5. Guardar variantes en prenda_variantes_cot
                $variantes = $productoData['variantes'] ?? [];
                if (!empty($variantes)) {
                    $prenda->variantes()->create([
                        'tipo_prenda' => $productoData['tipo_prenda'] ?? '',
                        'es_jean_pantalon' => $variantes['es_jean_pantalon'] ?? false,
                        'tipo_jean_pantalon' => $variantes['tipo_jean_pantalon'] ?? '',
                        'genero' => $variantes['genero'] ?? '',
                        'color' => $variantes['color'] ?? '',
                        'tiene_bolsillos' => $variantes['tiene_bolsillos'] ?? false,
                        'obs_bolsillos' => $variantes['obs_bolsillos'] ?? '',
                        'aplica_manga' => $variantes['aplica_manga'] ?? false,
                        'tipo_manga' => $variantes['tipo_manga'] ?? '',
                        'obs_manga' => $variantes['obs_manga'] ?? '',
                        'aplica_broche' => $variantes['aplica_broche'] ?? false,
                        'tipo_broche_id' => $variantes['tipo_broche_id'] ?? null,
                        'obs_broche' => $variantes['obs_broche'] ?? '',
                        'tiene_reflectivo' => $variantes['tiene_reflectivo'] ?? false,
                        'obs_reflectivo' => $variantes['obs_reflectivo'] ?? '',
                        'descripcion_adicional' => $variantes['descripcion_adicional'] ?? ''
                    ]);
                    Log::info("✅ Variantes guardadas");
                }

            } catch (\Exception $e) {
                Log::error("❌ Error guardando producto", [
                    'cotizacion_id' => $cotizacion->id,
                    'producto_index' => $index,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
        }

        Log::info("✅ Productos guardados en tablas normalizadas", [
            'cotizacion_id' => $cotizacion->id,
            'total' => count($productos)
        ]);
    }

    /**
     * Obtener productos de una cotización
     */
    public function obtenerProductosDeCotizacion(Cotizacion $cotizacion): array
    {
        $productos = $cotizacion->productos ?? [];

        if (is_string($productos)) {
            $productos = json_decode($productos, true) ?? [];
        }

        return $productos;
    }
}
