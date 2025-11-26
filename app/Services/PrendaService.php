<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\PrendaCotizacionFriendly;
use App\Models\VariantePrenda;
use App\Models\TipoPrenda;

/**
 * Servicio para gestionar prendas de cotizaciones
 * 
 * Responsabilidades:
 * - Crear prendas
 * - Gestionar variantes
 * - Detectar tipos de prenda
 */
class PrendaService
{
    /**
     * Crear prendas para una cotización
     * 
     * @param \App\Models\Cotizacion $cotizacion
     * @param array $productos
     * @return void
     */
    public function crearPrendasCotizacion(Cotizacion $cotizacion, array $productos): void
    {
        foreach ($productos as $index => $producto) {
            $this->crearPrenda($cotizacion, $producto);
        }
    }

    /**
     * Crear una prenda individual
     * 
     * @param \App\Models\Cotizacion $cotizacion
     * @param array $producto
     * @return \App\Models\PrendaCotizacionFriendly
     */
    public function crearPrenda(Cotizacion $cotizacion, array $producto): PrendaCotizacionFriendly
    {
        $tallas = is_array($producto['tallas'] ?? []) ? $producto['tallas'] : [];
        $nombrePrenda = $producto['nombre_producto'] ?? '';
        
        // Detectar tipo de prenda
        $tipoPrenda = $this->detectarTipoPrenda($nombrePrenda);
        
        // Obtener género de las variantes
        $genero = null;
        if (is_array($producto['variantes'] ?? null) && isset($producto['variantes']['genero'])) {
            $genero = $producto['variantes']['genero'];
        }
        
        // Crear prenda
        $prenda = PrendaCotizacionFriendly::create([
            'cotizacion_id' => $cotizacion->id,
            'nombre_producto' => $nombrePrenda,
            'genero' => $genero,
            'es_jean_pantalon' => $tipoPrenda['esJeanPantalon'],
            'tipo_jean_pantalon' => $tipoPrenda['esJeanPantalon'] 
                ? ($producto['variantes']['tipo'] ?? null) 
                : null,
            'descripcion' => $producto['descripcion'] ?? null,
            'tallas' => $tallas,
            'fotos' => [],
            'telas' => [],
            'estado' => 'Pendiente'
        ]);
        
        // Guardar variantes
        $this->guardarVariantes($prenda, $producto);
        
        return $prenda;
    }

    /**
     * Guardar variantes de una prenda
     * 
     * @param \App\Models\PrendaCotizacionFriendly $prenda
     * @param array $productoData
     * @return void
     */
    public function guardarVariantes(PrendaCotizacionFriendly $prenda, array $productoData): void
    {
        try {
            \Log::warning('📥 Datos recibidos para guardarVariantes', [
                'prenda_id' => $prenda->id,
                'variantes_keys' => array_keys($productoData['variantes'] ?? []),
                'variantes' => $productoData['variantes'] ?? null
            ]);
            
            $nombrePrenda = $productoData['nombre_producto'] ?? '';
            
            // Reconocer tipo de prenda por nombre
            $tipoPrenda = TipoPrenda::reconocerPorNombre($nombrePrenda);
            
            if (!$tipoPrenda) {
                \Log::warning('No se pudo reconocer tipo de prenda, usando tipo genérico', [
                    'nombre' => $nombrePrenda
                ]);
                // Intentar obtener un tipo genérico como fallback
                $tipoPrenda = TipoPrenda::where('nombre', 'LIKE', '%OTRA%')
                    ->orWhere('nombre', 'LIKE', '%GENERICO%')
                    ->orWhere('nombre', 'LIKE', '%GENERAL%')
                    ->first();
                
                // Si tampoco existe tipo genérico, obtener el primer tipo disponible
                if (!$tipoPrenda) {
                    $tipoPrenda = TipoPrenda::first();
                }
                
                // Si aún no hay tipo, crear uno automáticamente
                if (!$tipoPrenda) {
                    $tipoPrenda = TipoPrenda::create([
                        'nombre' => 'OTRO',
                        'codigo' => 'OTRO',
                        'palabras_clave' => json_encode(['OTRO', 'GENERICO']),
                        'activo' => true
                    ]);
                    
                    \Log::info('✅ Tipo prenda creado automáticamente', [
                        'id' => $tipoPrenda->id,
                        'nombre' => $tipoPrenda->nombre
                    ]);
                }
            }
            
            $variantes = $productoData['variantes'] ?? [];
            
            $datosVariante = [
                'prenda_cotizacion_id' => $prenda->id,
                'tipo_prenda_id' => $tipoPrenda ? $tipoPrenda->id : null,
                'cantidad_talla' => $prenda->tallas ? json_encode($prenda->tallas) : null
            ];
            
            // Procesar color
            if (isset($variantes['color']) && !empty($variantes['color'])) {
                $color = \App\Models\ColorPrenda::firstOrCreate(
                    ['nombre' => $variantes['color']],
                    ['nombre' => $variantes['color']]
                );
                $datosVariante['color_id'] = $color->id;
            }
            
            // Procesar género
            if (isset($variantes['genero']) && !empty($variantes['genero'])) {
                $genero = \App\Models\GeneroPrenda::where('nombre', $variantes['genero'])
                    ->orWhere('id', $variantes['genero'])
                    ->first();
                
                // Si no existe, crear género automáticamente
                if (!$genero) {
                    $nombreGenero = is_numeric($variantes['genero']) 
                        ? "GENERO_{$variantes['genero']}" 
                        : $variantes['genero'];
                    
                    $genero = \App\Models\GeneroPrenda::create([
                        'nombre' => $nombreGenero
                    ]);
                    
                    \Log::info('✅ Género creado automáticamente', [
                        'id' => $genero->id,
                        'nombre' => $genero->nombre
                    ]);
                }
                
                if ($genero) {
                    $datosVariante['genero_id'] = $genero->id;
                }
            }
            
            // Procesar tela
            if (isset($variantes['tela']) && !empty($variantes['tela'])) {
                $tela = \App\Models\TelaPrenda::firstOrCreate(
                    ['nombre' => $variantes['tela']],
                    ['nombre' => $variantes['tela']]
                );
                
                // Actualizar referencia si fue proporcionada (incluso si ya existía)
                if (isset($variantes['tela_referencia']) && !empty($variantes['tela_referencia'])) {
                    if (!$tela->referencia || $tela->referencia !== $variantes['tela_referencia']) {
                        $tela->update(['referencia' => $variantes['tela_referencia']]);
                        \Log::info('✅ Referencia de tela actualizada', [
                            'tela_id' => $tela->id,
                            'nombre' => $tela->nombre,
                            'referencia' => $variantes['tela_referencia']
                        ]);
                    }
                }
                
                $datosVariante['tela_id'] = $tela->id;
            }
            
            // Procesar manga
            if (isset($variantes['tipo_manga_id']) && !empty($variantes['tipo_manga_id'])) {
                \Log::warning('🔍 Procesando manga', [
                    'tipo_manga_id_value' => $variantes['tipo_manga_id'],
                    'manga_nombre_value' => $variantes['manga_nombre'] ?? 'NO EXISTE',
                    'variantes_keys' => array_keys($variantes)
                ]);
                
                $manga = \App\Models\TipoManga::where('nombre', $variantes['tipo_manga_id'])
                    ->orWhere('id', $variantes['tipo_manga_id'])
                    ->first();
                
                // Si no existe, crear manga automáticamente usando el nombre si está disponible
                if (!$manga) {
                    // Preferir el nombre legible si está disponible
                    $nombreManga = $variantes['manga_nombre'] ?? 
                        (is_numeric($variantes['tipo_manga_id']) 
                            ? "MANGA_{$variantes['tipo_manga_id']}" 
                            : $variantes['tipo_manga_id']);
                    
                    $manga = \App\Models\TipoManga::create([
                        'nombre' => $nombreManga
                    ]);
                    
                    \Log::warning('✅ Tipo manga creado automáticamente', [
                        'id' => $manga->id,
                        'nombre' => $manga->nombre,
                        'from_id' => $variantes['tipo_manga_id'] ?? null,
                        'from_nombre' => $variantes['manga_nombre'] ?? null
                    ]);
                } else if (isset($variantes['manga_nombre']) && !empty($variantes['manga_nombre'])) {
                    // Si manga existe y se proporcionó nombre, actualizar si es diferente
                    if ($manga->nombre !== $variantes['manga_nombre']) {
                        $manga->update(['nombre' => $variantes['manga_nombre']]);
                        \Log::warning('✅ Nombre de manga actualizado', [
                            'manga_id' => $manga->id,
                            'nombre_anterior' => $manga->getOriginal('nombre'),
                            'nombre_nuevo' => $variantes['manga_nombre']
                        ]);
                    }
                }
                
                if ($manga) {
                    $datosVariante['tipo_manga_id'] = $manga->id;
                }
            }
            
            // Procesar broche
            if (isset($variantes['tipo_broche_id']) && !empty($variantes['tipo_broche_id'])) {
                $broche = \App\Models\TipoBroche::where('nombre', $variantes['tipo_broche_id'])
                    ->orWhere('id', $variantes['tipo_broche_id'])
                    ->first();
                
                // Si no existe, crear broche automáticamente
                if (!$broche) {
                    $nombreBroche = is_numeric($variantes['tipo_broche_id']) 
                        ? "BROCHE_{$variantes['tipo_broche_id']}" 
                        : $variantes['tipo_broche_id'];
                    
                    $broche = \App\Models\TipoBroche::create([
                        'nombre' => $nombreBroche
                    ]);
                    
                    \Log::info('✅ Tipo broche creado automáticamente', [
                        'id' => $broche->id,
                        'nombre' => $broche->nombre
                    ]);
                }
                
                if ($broche) {
                    $datosVariante['tipo_broche_id'] = $broche->id;
                }
            }
            
            // Procesar bolsillos y reflectivo
            if (isset($variantes['tiene_bolsillos'])) {
                $datosVariante['tiene_bolsillos'] = (bool)$variantes['tiene_bolsillos'];
            }
            
            if (isset($variantes['tiene_reflectivo'])) {
                $datosVariante['tiene_reflectivo'] = (bool)$variantes['tiene_reflectivo'];
            }
            
            // Procesar observaciones
            $observacionesArray = [];
            
            if (isset($variantes['obs_bolsillos']) && !empty($variantes['obs_bolsillos'])) {
                $observacionesArray[] = "Bolsillos: {$variantes['obs_bolsillos']}";
            }
            if (isset($variantes['obs_broche']) && !empty($variantes['obs_broche'])) {
                $observacionesArray[] = "Broche: {$variantes['obs_broche']}";
            }
            if (isset($variantes['obs_reflectivo']) && !empty($variantes['obs_reflectivo'])) {
                $observacionesArray[] = "Reflectivo: {$variantes['obs_reflectivo']}";
            }
            
            if (isset($variantes['descripcion_adicional']) && !empty($variantes['descripcion_adicional'])) {
                $datosVariante['descripcion_adicional'] = $variantes['descripcion_adicional'];
            } elseif (!empty($observacionesArray)) {
                $datosVariante['descripcion_adicional'] = implode(' | ', $observacionesArray);
            }
            
            // Crear variante
            $varianteCreada = VariantePrenda::create($datosVariante);
            
            \Log::info('✅ Variante guardada exitosamente', [
                'variante_id' => $varianteCreada->id,
                'prenda_id' => $prenda->id,
                'tipo_manga_id' => $datosVariante['tipo_manga_id'] ?? null,
                'tiene_bolsillos' => $datosVariante['tiene_bolsillos'] ?? null,
                'descripcion_adicional' => $datosVariante['descripcion_adicional'] ?? null
            ]);
            
        } catch (\Exception $e) {
            \Log::error('❌ Error guardando variantes', [
                'prenda_id' => $prenda->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Detectar tipo de prenda (JEAN, PANTALÓN, etc.)
     * 
     * @param string $nombrePrenda
     * @return array
     */
    public function detectarTipoPrenda(string $nombrePrenda): array
    {
        if (empty($nombrePrenda)) {
            return [
                'esJeanPantalon' => false,
                'palabraPrincipal' => ''
            ];
        }
        
        $nombreUpper = strtoupper(trim($nombrePrenda));
        $palabraPrincipal = explode(' ', $nombreUpper)[0] ?? '';
        $esJeanPantalon = (bool)preg_match('/^JEAN|^PANTALÓ?N/', $palabraPrincipal);
        
        return [
            'esJeanPantalon' => $esJeanPantalon,
            'palabraPrincipal' => $palabraPrincipal
        ];
    }

    /**
     * Heredar variantes de una prenda de cotización a una prenda de pedido
     * 
     * @param \App\Models\Cotizacion $cotizacion
     * @param \App\Models\PrendaPedido $prendaPedido
     * @param int $index
     * @return void
     */
    public function heredarVariantesDePrendaPedido(
        Cotizacion $cotizacion,
        \App\Models\PrendaPedido $prendaPedido,
        int $index
    ): void {
        try {
            // Null-safe prendasCotizaciones access
            $prendasCotizacion = $cotizacion->prendasCotizaciones;
            
            if (!$prendasCotizacion) {
                \Log::warning('prendasCotizaciones es null', [
                    'cotizacion_id' => $cotizacion->id
                ]);
                return;
            }
            
            $prendaCotizacion = $prendasCotizacion->get($index);
            
            if (!$prendaCotizacion) {
                \Log::warning('Prenda de cotización no encontrada en índice', [
                    'cotizacion_id' => $cotizacion->id,
                    'index' => $index
                ]);
                return;
            }
            
            // Null-safe variantes access
            $variantes = $prendaCotizacion->variantes;
            if (!$variantes) {
                \Log::info('Prenda sin variantes', [
                    'prenda_cotizacion_id' => $prendaCotizacion->id
                ]);
                return;
            }
            
            foreach ($variantes as $variante) {
                VariantePrenda::create([
                    'prenda_pedido_id' => $prendaPedido->id,
                    'tipo_prenda_id' => $variante->tipo_prenda_id,
                    'color_id' => $variante->color_id,
                    'tela_id' => $variante->tela_id,
                    'tipo_manga_id' => $variante->tipo_manga_id,
                    'tipo_broche_id' => $variante->tipo_broche_id,
                    'tiene_bolsillos' => $variante->tiene_bolsillos,
                    'tiene_reflectivo' => $variante->tiene_reflectivo,
                    'descripcion_adicional' => $variante->descripcion_adicional,
                    'cantidad_talla' => $variante->cantidad_talla
                ]);
            }
            
            \Log::info('Variantes heredadas', [
                'prenda_pedido_id' => $prendaPedido->id,
                'cantidad_variantes' => count($variantes)
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error al heredar variantes', [
                'error' => $e->getMessage(),
                'cotizacion_id' => $cotizacion->id,
                'prenda_pedido_id' => $prendaPedido->id
            ]);
        }
    }
}
