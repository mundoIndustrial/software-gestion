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

                // 3. COMENTADO - Telas ahora se procesan en la sección de variantes (línea ~258)
                //    La razón: Necesitaban variante_prenda_cot_id para foreign key constraint
                //    El código antiguo creaba telas SIN variante_id, causando el error:
                //    "Field 'variante_prenda_cot_id' doesn't have a default value"
                //    Ahora se crean telas con telas_multiples en el mismo contexto que variantes
                /* DISABLED CODE - kept for reference
                $telas = $productoData['telas'] ?? [];
                if (!empty($telas)) {
                    foreach ($telas as $telaIndex => $telaData) {
                        $tela = $prenda->telas()->create([
                            'color_id' => $telaData['color_id'] ?? null,
                            'tela_id' => $telaData['tela_id'] ?? null,
                        ]);
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
                        }
                    }
                }
                */

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

                // 4b. ✅ GUARDAR CANTIDADES POR TALLA en prenda_tallas_cot
                // Recibe en formato: ['S' => 10, 'M' => 20, 'L' => 15]
                $cantidades = $productoData['cantidades'] ?? [];
                if (is_string($cantidades)) {
                    $cantidades = json_decode($cantidades, true) ?? [];
                }
                if (!empty($cantidades) && is_array($cantidades)) {
                    // Primero, limpiar tallas previas si existen
                    $prenda->tallas()->delete();
                    
                    // Guardar las tallas con sus cantidades
                    foreach ($cantidades as $talla => $cantidad) {
                        if ($talla && $cantidad > 0) {
                            $prenda->tallas()->create([
                                'talla' => (string)$talla,
                                'cantidad' => (int)$cantidad
                            ]);
                        }
                    }
                    Log::info("✅ Tallas con cantidades guardadas", [
                        'cantidad_tallas' => count($cantidades),
                        'tallas' => array_keys($cantidades)
                    ]);
                }

                // 5. Guardar variantes en prenda_variantes_cot
                // Las variantes vienen dentro de $productoData['variantes']
                $variantes = $productoData['variantes'] ?? [];
                
                // Decodificar si viene como JSON string
                if (is_string($variantes)) {
                    $variantes = json_decode($variantes, true) ?? [];
                }
                
                // Nota sobre genero_id:
                // - null = Aplica a AMBOS géneros (Dama y Caballero)
                // - 1 = Solo Dama
                // - 2 = Solo Caballero
                // - 3 = Unisex
                
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
                    $tipoMangaId = isset($variantes['tipo_manga_id']) ? $variantes['tipo_manga_id'] : null;
                    if (is_string($tipoMangaId) && !empty($tipoMangaId)) {
                        $tipoMangaId = (int)$tipoMangaId;
                    }
                    
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
                    
                    // GUARDAR UN SOLO REGISTRO DE VARIANTE
                    // genero_id = null significa "Aplica a Ambos géneros"
                    // También convertir cadenas vacías a null
                    $generoIdAGuardar = isset($variantes['genero_id']) ? $variantes['genero_id'] : null;
                    if ($generoIdAGuardar === '' || $generoIdAGuardar === '0') {
                        $generoIdAGuardar = null;
                    }
                    
                    try {
                        $variante = $prenda->variantes()->create([
                            'genero_id' => $generoIdAGuardar,
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
                        Log::info("✅ Variante guardada", [
                            'variante_id' => $variante->id,
                            'genero_id' => $generoIdAGuardar,
                            'genero_id_es_null' => $generoIdAGuardar === null,
                            'color' => $color,
                            'referencia' => $referencia,
                            'tipo_manga_id' => $tipoMangaId,
                            'tipo_broche_id' => $variantes['tipo_broche_id'] ?? null,
                            'telas_multiples_count' => count($telasMultiples),
                        ]);

                        // ✅ PROCESAR prenda_telas_cot desde telas_multiples
                        if (!empty($telasMultiples)) {
                            foreach ($telasMultiples as $telaInfo) {
                                // Buscar color por nombre
                                $colorId = null;
                                if (!empty($telaInfo['color'])) {
                                    $colorModel = \App\Models\ColorPrenda::where('nombre', $telaInfo['color'])->first();
                                    $colorId = $colorModel->id ?? null;
                                }
                                
                                // Buscar tela por nombre
                                $telaId = null;
                                if (!empty($telaInfo['tela'])) {
                                    $telaModel = \App\Models\TelaPrenda::where('nombre', $telaInfo['tela'])->first();
                                    $telaId = $telaModel->id ?? null;
                                }
                                
                                // Crear registro en prenda_telas_cot
                                if ($colorId && $telaId) {
                                    $prendaTelaCot = \App\Models\PrendaTelaCot::create([
                                        'prenda_cot_id' => $prenda->id,
                                        'variante_prenda_cot_id' => $variante->id,
                                        'color_id' => $colorId,
                                        'tela_id' => $telaId,
                                    ]);
                                    
                                    Log::info("✅ Registro guardado en prenda_telas_cot (desde telas_multiples)", [
                                        'prenda_telas_cot_id' => $prendaTelaCot->id,
                                        'prenda_id' => $prenda->id,
                                        'variante_id' => $variante->id,
                                        'color_id' => $colorId,
                                        'tela_id' => $telaId,
                                        'color' => $telaInfo['color'] ?? '',
                                        'tela' => $telaInfo['tela'] ?? '',
                                        'referencia' => $telaInfo['referencia'] ?? '',
                                        'indice' => $telaInfo['indice'] ?? '',
                                    ]);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("❌ ERROR al guardar variante", [
                            'error' => $e->getMessage(),
                            'tipo_manga_id' => $tipoMangaId,
                            'genero_id' => $generoIdAGuardar,
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

    /**
     * Guardar prenda con múltiples telas, referencias, colores e imágenes
     * Método diseñado para tests y uso directo
     */
    public function guardarPrendaConTelas(Cotizacion $cotizacion, array $prendaData): \App\Models\PrendaCot
    {
        $nombre = $prendaData['nombre_producto'] ?? 'Sin nombre';
        
        // 1. Guardar prenda principal
        $prenda = $cotizacion->prendas()->create([
            'nombre_producto' => $nombre,
            'descripcion' => $prendaData['descripcion'] ?? '',
            'cantidad' => $prendaData['cantidad'] ?? 1,
        ]);
        
        Log::info("✅ Prenda creada con telas", [
            'prenda_id' => $prenda->id,
            'nombre' => $nombre
        ]);

        // 2. Guardar variante
        $variantes = $prendaData['variantes'] ?? [];
        if (!empty($variantes)) {
            $prenda->variantes()->create([
                'genero_id' => $variantes['genero_id'] ?? null,
                'color' => $variantes['color'] ?? null,
                'tipo_prenda' => $variantes['tipo_prenda'] ?? null,
            ]);
        }

        // 3. Guardar telas y sus fotos
        $telas = $prendaData['telas'] ?? [];
        foreach ($telas as $telaIndex => $telaData) {
            // Guardar foto de tela en prenda_tela_fotos_cot
            // Nota: Guardamos directamente en PrendaTelaFotoCot con referencia y color
            $telaFotos = $telaData['fotos'] ?? [];
            if (!empty($telaFotos)) {
                foreach ($telaFotos as $telaFoto) {
                    \App\Models\PrendaTelaFotoCot::create([
                        'prenda_cot_id' => $prenda->id,
                        'referencia' => $telaData['referencia'] ?? '',
                        'color_id' => $telaData['color_id'] ?? null,
                        'tela_id' => $telaData['tela_id'] ?? null,
                        'ruta_original' => $telaFoto['ruta_original'] ?? '',
                        'ruta_webp' => $telaFoto['ruta_webp'] ?? $telaFoto['ruta_original'] ?? '',
                        'ruta_miniatura' => $telaFoto['ruta_miniatura'] ?? null,
                        'orden' => $telaFoto['orden'] ?? 1,
                        'ancho' => $telaFoto['ancho'] ?? null,
                        'alto' => $telaFoto['alto'] ?? null,
                        'tamaño' => $telaFoto['tamaño'] ?? null,
                    ]);
                    
                    Log::info("✅ Foto de tela guardada", [
                        'prenda_id' => $prenda->id,
                        'referencia' => $telaData['referencia'] ?? '',
                        'ruta' => $telaFoto['ruta_original'] ?? ''
                    ]);
                }
            }
        }

        Log::info("✅ Prenda con múltiples telas guardada completamente", [
            'prenda_id' => $prenda->id,
            'total_telas' => count($telas)
        ]);

        return $prenda;
    }
}
