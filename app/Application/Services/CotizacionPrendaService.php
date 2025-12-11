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
                Log::info("📦 DEBUG - Datos recibidos del producto", [
                    'index' => $index,
                    'keys' => array_keys($productoData),
                    'data' => json_encode($productoData, JSON_UNESCAPED_SLASHES)
                ]);

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
                
                // 2. Guardar fotos en prenda_fotos_cot (múltiples fotos)
                $fotos = $productoData['fotos_desde_prendaConIndice'] ?? $productoData['fotos'] ?? [];
                if (!empty($fotos)) {
                    $orden = 1;
                    foreach ($fotos as $foto) {
                        // Si es base64, guardar directamente; si es archivo, usar ruta
                        $ruta = is_array($foto) ? ($foto['ruta_webp'] ?? $foto['url'] ?? '') : $foto;
                        if (!empty($ruta)) {
                            $prenda->fotos()->create([
                                'ruta_original' => $ruta,
                                'ruta_webp' => $ruta,
                                'tipo' => 'prenda',
                                'orden' => $orden,
                            ]);
                            $orden++;
                        }
                    }
                    Log::info("✅ Fotos de prenda guardadas", ['cantidad' => count($fotos)]);
                }

                // 3. Guardar telas y sus fotos en prenda_telas_cot y prenda_tela_fotos_cot
                $telas = $productoData['telas'] ?? [];
                if (!empty($telas)) {
                    foreach ($telas as $telaIndex => $telaData) {
                        // Guardar tela en prenda_telas_cot
                        $tela = $prenda->telas()->create([
                            'color_id' => $telaData['color_id'] ?? null,
                            'tela_id' => $telaData['tela_id'] ?? null,
                        ]);
                        
                        // Guardar fotos de tela en prenda_tela_fotos_cot
                        $telaFotos = $telaData['fotos'] ?? [];
                        if (!empty($telaFotos)) {
                            $orden = 1;
                            foreach ($telaFotos as $telaFoto) {
                                $ruta = is_array($telaFoto) ? ($telaFoto['ruta_webp'] ?? $telaFoto['url'] ?? '') : $telaFoto;
                                if (!empty($ruta)) {
                                    \App\Models\PrendaTelaFotoCot::create([
                                        'prenda_cot_id' => $prenda->id,
                                        'ruta_original' => $ruta,
                                        'ruta_webp' => $ruta,
                                        'orden' => $orden,
                                    ]);
                                    $orden++;
                                }
                            }
                            Log::info("✅ Fotos de tela guardadas", ['cantidad' => count($telaFotos)]);
                        }
                    }
                    Log::info("✅ Telas guardadas", ['cantidad' => count($telas)]);
                }

                // 4. Guardar tallas en prenda_tallas_cot
                $tallas = $productoData['tallas'] ?? [];
                // Decodificar si viene como JSON string
                if (is_string($tallas)) {
                    $tallas = json_decode($tallas, true) ?? [];
                }
                if (!empty($tallas)) {
                    foreach ($tallas as $talla) {
                        $prenda->tallas()->create([
                            'talla' => $talla,
                            'cantidad' => 1
                        ]);
                    }
                    Log::info("✅ Tallas guardadas", ['cantidad' => count($tallas)]);
                }

                // 5. Guardar variantes en prenda_variantes_cot
                // Las variantes vienen dentro de $productoData['variantes']
                $variantes = $productoData['variantes'] ?? [];
                
                // Decodificar si viene como JSON string
                if (is_string($variantes)) {
                    $variantes = json_decode($variantes, true) ?? [];
                }
                
                // Verificar si hay al menos un campo de variante
                $tieneVariantes = !empty($variantes) && (
                    isset($variantes['genero_id']) || 
                    isset($variantes['tipo_broche_id']) || 
                    isset($variantes['tipo_manga_id']) ||
                    isset($variantes['tiene_bolsillos']) ||
                    isset($variantes['tiene_reflectivo'])
                );
                
                if ($tieneVariantes) {
                    // Procesar telas_multiples si existe
                    $telasMultiples = $variantes['telas_multiples'] ?? [];
                    if (is_string($telasMultiples)) {
                        $telasMultiples = json_decode($telasMultiples, true) ?? [];
                    }
                    
                    // Extraer color y referencia de la primera tela (si existe)
                    $color = $variantes['color'] ?? null;
                    $referencia = null;
                    if (!$color && !empty($telasMultiples) && is_array($telasMultiples)) {
                        // Si no hay color directo, extraer de telas_multiples
                        $primeraTela = $telasMultiples[0] ?? [];
                        $color = $primeraTela['color'] ?? null;
                        $referencia = $primeraTela['referencia'] ?? null;
                    }
                    
                    // Convertir tipo_manga_id a número si es string
                    $tipoMangaId = $variantes['tipo_manga_id'] ?? null;
                    Log::info("DEBUG tipo_manga_id recibido", [
                        'tipo_manga_id_raw' => $tipoMangaId,
                        'tipo_manga_id_type' => gettype($tipoMangaId),
                        'variantes_keys' => array_keys($variantes)
                    ]);
                    if (is_string($tipoMangaId) && !empty($tipoMangaId)) {
                        $tipoMangaId = (int)$tipoMangaId;
                    }
                    Log::info("DEBUG tipo_manga_id después conversión", [
                        'tipo_manga_id_final' => $tipoMangaId,
                        'tipo_manga_id_final_type' => gettype($tipoMangaId)
                    ]);
                    
                    // Verificar si la manga existe en la BD, si no, crearla
                    if ($tipoMangaId && $tipoMangaId > 0) {
                        $mangaExiste = \App\Models\TipoManga::find($tipoMangaId);
                        if (!$mangaExiste) {
                            // Si no existe, obtener el nombre de obs_manga o crear uno genérico
                            $nombreManga = $variantes['obs_manga'] ?? "Manga ID $tipoMangaId";
                            $mangaCreada = \App\Models\TipoManga::create([
                                'nombre' => $nombreManga,
                                'activo' => true
                            ]);
                            $tipoMangaId = $mangaCreada->id;
                            Log::info("✅ Manga personalizada creada", [
                                'manga_id' => $tipoMangaId,
                                'manga_nombre' => $nombreManga
                            ]);
                        }
                    }
                    
                    Log::info("DEBUG antes de crear variante", [
                        'tipo_manga_id_a_guardar' => $tipoMangaId,
                        'genero_id' => $variantes['genero_id'] ?? null,
                        'color' => $color
                    ]);
                    
                    try {
                        $variante = $prenda->variantes()->create([
                            'genero_id' => $variantes['genero_id'] ?? null,
                            'color' => $color,
                            'tipo_manga_id' => $tipoMangaId,
                            'tipo_broche_id' => $variantes['tipo_broche_id'] ?? null,
                            'obs_broche' => $variantes['obs_broche'] ?? null,
                            'tiene_bolsillos' => $variantes['tiene_bolsillos'] ?? false,
                            'obs_bolsillos' => $variantes['obs_bolsillos'] ?? null,
                            'aplica_manga' => $variantes['aplica_manga'] ?? false,
                            'obs_manga' => $variantes['obs_manga'] ?? null,
                            'tiene_reflectivo' => $variantes['tiene_reflectivo'] ?? false,
                            'obs_reflectivo' => $variantes['obs_reflectivo'] ?? null,
                            'descripcion_adicional' => $variantes['descripcion_adicional'] ?? '',
                            'telas_multiples' => !empty($telasMultiples) ? json_encode($telasMultiples) : null,
                        ]);
                        Log::info("✅ Variantes guardadas", [
                            'variante_id' => $variante->id,
                            'genero_id' => $variantes['genero_id'] ?? null,
                            'color' => $color,
                            'referencia' => $referencia,
                            'tipo_manga_id' => $tipoMangaId,
                            'tipo_broche_id' => $variantes['tipo_broche_id'] ?? null,
                            'telas_multiples_count' => count($telasMultiples),
                        ]);
                    } catch (\Exception $e) {
                        Log::error("❌ ERROR al guardar variantes", [
                            'error' => $e->getMessage(),
                            'tipo_manga_id' => $tipoMangaId,
                            'genero_id' => $variantes['genero_id'] ?? null,
                            'color' => $color
                        ]);
                    }
                } else {
                    Log::warning("⚠️ No hay variantes para guardar");
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
