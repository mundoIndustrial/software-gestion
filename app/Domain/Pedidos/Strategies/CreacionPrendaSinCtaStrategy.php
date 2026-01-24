<?php

namespace App\Domain\Pedidos\Strategies;

use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Domain\Pedidos\Services\DescripcionService;
use App\Domain\Pedidos\Services\ImagenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Estrategia de CreaciÃ³n de Prenda SIN COTIZACIÃ“N
 * 
 * Encapsula la lÃ³gica del mÃ©todo controller::crearPrendaSinCotizacion() (~400 lÃ­neas)
 * 
 * Responsabilidades:
 * - Procesar estructura compleja de cantidades (3 formas diferentes)
 * - Extraer y crear/obtener variantes (colores, telas, mangas, broches)
 * - Guardar prenda con toda la informaciÃ³n
 * - Procesar fotos de prenda y telas
 * - Crear proceso inicial
 * 
 * Patrones utilizados:
 * - Strategy: Diferentes algoritmos para diferentes contextos
 * - Template Method (implÃ­cito): procesar() llama mÃ©todos privados
 * - Factory: CreaciÃ³n de variantes
 */
class CreacionPrendaSinCtaStrategy implements CreacionPrendaStrategy
{
    private DescripcionService $descripcionService;
    private ImagenService $imagenService;

    /**
     * {@inheritDoc}
     */
    public function procesar(
        array $prendaData,
        int $pedidoProduccionId,
        array $servicios
    ): PrendaPedido {
        $this->descripcionService = $servicios['descripcionService'] ?? throw new \RuntimeException('DescripcionService requerido');
        $this->imagenService = $servicios['imagenService'] ?? throw new \RuntimeException('ImagenService requerido');

        // Validar datos
        $this->validar($prendaData);

        try {
            DB::beginTransaction();

            // Obtener nÃºmero de pedido desde el ID para auditorÃ­a
            $pedido = \App\Models\PedidoProduccion::find($pedidoProduccionId);
            if (!$pedido) {
                throw new \Exception("Pedido no encontrado con ID: {$pedidoProduccionId}");
            }
            $numeroPedido = $pedido->numero_pedido;

            Log::info(' [CreacionPrendaSinCtaStrategy] Procesando prenda', [
                'nombre' => $prendaData['nombre_prenda'] ?? $prendaData['nombre_producto'] ?? 'Sin nombre',
                'pedido_produccion_id' => $pedidoProduccionId,
                'numero_pedido' => $numeroPedido,
            ]);

            // ===== PASO 1: PROCESAR CANTIDADES (ANTES LÃNEA 1050-1150) =====
            $cantidadesPorTalla = $this->procesarCantidades($prendaData);
            $cantidadTotal = $this->calcularCantidadTotal($cantidadesPorTalla);

            Log::debug(' [CreacionPrendaSinCtaStrategy] Cantidades procesadas', [
                'cantidad_total' => $cantidadTotal,
                'estructura' => count($cantidadesPorTalla) > 0 ? 'VÃ¡lida' : 'VacÃ­a',
            ]);

            // ===== PASO 2: PROCESAR VARIANTES (ANTES LÃNEA 1200-1350) =====
            $variantes = $this->procesarVariantes($prendaData);

            Log::debug(' [CreacionPrendaSinCtaStrategy] Variantes procesadas', [
                'color_id' => $variantes['color_id'],
                'tela_id' => $variantes['tela_id'],
                'tipo_manga_id' => $variantes['tipo_manga_id'],
                'tipo_broche_id' => $variantes['tipo_broche_id'],
            ]);

            // ===== PASO 3: CONSTRUIR DESCRIPCIÃ“N (ANTES LÃNEA 1165) =====
            $descripcion = $this->descripcionService->construirDescripcionPrendaSinCotizacion(
                $prendaData,
                $cantidadesPorTalla
            );

            // ===== PASO 4: CREAR PRENDA (ANTES LÃNEA 1380-1410) =====
            $prendaPedido = PrendaPedido::create([
                'pedido_produccion_id' => $pedidoProduccionId,
                'nombre_prenda' => $prendaData['nombre_prenda'] ?? $prendaData['nombre_producto'] ?? 'Sin nombre',

                'descripcion' => $prendaData['descripcion'] ?? '',
                'descripcion_variaciones' => $this->armarDescripcionVariaciones($variantes),
                'cantidad_talla' => json_encode($cantidadesPorTalla),
                'genero' => json_encode($this->procesarGeneros($prendaData['genero'] ?? '')),
                'color_id' => $variantes['color_id'],
                'tela_id' => $variantes['tela_id'],
                'tipo_manga_id' => $variantes['tipo_manga_id'],
                'tipo_broche_id' => $variantes['tipo_broche_id'],
                'tiene_bolsillos' => $variantes['tiene_bolsillos'],
                'tiene_reflectivo' => $variantes['tiene_reflectivo'],
                'manga_obs' => $prendaData['obs_manga'] ?? $prendaData['manga_obs'] ?? '',
                'bolsillos_obs' => $prendaData['obs_bolsillos'] ?? $prendaData['bolsillos_obs'] ?? '',
                'broche_obs' => $prendaData['obs_broche'] ?? $prendaData['broche_obs'] ?? '',
                'reflectivo_obs' => $prendaData['obs_reflectivo'] ?? $prendaData['reflectivo_obs'] ?? '',
                'de_bodega' => (int)($prendaData['de_bodega'] ?? 0),
            ]);

            Log::info(' [CreacionPrendaSinCtaStrategy] Prenda creada', [
                'prenda_pedido_id' => $prendaPedido->id,
                'nombre' => $prendaPedido->nombre_prenda,
            ]);

            // ===== PASO 5A: GUARDAR TALLAS EN TABLA RELACIONAL =====
            // CRÃTICO: Guardar tallas desde cantidadesPorTalla que tiene estructura {GENERO: {TALLA: CANTIDAD}}
            if (!empty($cantidadesPorTalla)) {
                // Usar el trait GestionaTallasRelacional para guardar correctamente
                $repository = app(\App\Domain\Pedidos\Repositories\PedidoProduccionRepository::class);
                $repository->guardarTallas($prendaPedido->id, $cantidadesPorTalla);
                
                Log::info(' [CreacionPrendaSinCtaStrategy] Tallas guardadas en tabla relacional', [
                    'prenda_pedido_id' => $prendaPedido->id,
                    'tallas_structure' => $cantidadesPorTalla,
                ]);
            }

            // ===== PASO 5: CREAR VARIANTES =====
            // Crear registro en prenda_pedido_variantes si hay tipo_manga, tipo_broche o bolsillos
            if ($variantes['tipo_manga_id'] || $variantes['tipo_broche_id'] || $variantes['tiene_bolsillos']) {
                \App\Models\PrendaVariante::create([
                    'prenda_pedido_id' => $prendaPedido->id,
                    'tipo_manga_id' => $variantes['tipo_manga_id'],
                    'tipo_broche_boton_id' => $variantes['tipo_broche_id'],
                    'manga_obs' => $prendaData['obs_manga'] ?? $prendaData['manga_obs'] ?? '',
                    'broche_boton_obs' => $prendaData['obs_broche'] ?? $prendaData['broche_obs'] ?? '',
                    'tiene_bolsillos' => $variantes['tiene_bolsillos'],
                    'bolsillos_obs' => $prendaData['obs_bolsillos'] ?? $prendaData['bolsillos_obs'] ?? '',
                ]);

                Log::info(' [CreacionPrendaSinCtaStrategy] Variante de prenda creada', [
                    'prenda_pedido_id' => $prendaPedido->id,
                    'tipo_manga_id' => $variantes['tipo_manga_id'],
                    'tipo_broche_id' => $variantes['tipo_broche_id'],
                    'tiene_bolsillos' => $variantes['tiene_bolsillos'],
                ]);
            }

            // ===== PASO 6: CREAR PROCESO INICIAL (ANTES LÃNEA 1415-1425) =====
            ProcesoPrenda::create([
                'numero_pedido' => $numeroPedido,
                'prenda_pedido_id' => $prendaPedido->id,
                'proceso' => 'CreaciÃ³n Orden',
                'estado_proceso' => 'Completado',
                'fecha_inicio' => now(),
                'fecha_fin' => now(),
            ]);
            // ===== PASO 7: GUARDAR PROCESOS (REFLECTIVO, BORDADO, ETC) =====
            if (!empty($prendaData['procesos'])) {
                $this->guardarProcesos($prendaPedido->id, $numeroPedido, $prendaData['procesos']);
            }

            // ===== PASO 8: GUARDAR IMÁGENES DE PRENDA =====
            if (!empty($prendaData['imagenes'])) {
                $this->guardarImagenesPrenda($prendaPedido->id, $prendaData['imagenes']);
            }

            // ===== PASO 9: GUARDAR IMÁGENES DE TELAS =====
            if (!empty($prendaData['telas'])) {
                $this->guardarImagenesTelas($prendaPedido->id, $prendaData['telas']);
            }
            DB::commit();

            Log::info(' [CreacionPrendaSinCtaStrategy] Prenda completamente procesada', [
                'prenda_id' => $prendaPedido->id,
                'cantidad_total' => $cantidadTotal,
            ]);

            return $prendaPedido;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error(' [CreacionPrendaSinCtaStrategy] Error al procesar prenda', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validar(array $prendaData): bool
    {
        // Aceptar tanto nombre_prenda como nombre_producto (para compatibilidad)
        if (empty($prendaData['nombre_prenda']) && empty($prendaData['nombre_producto'])) {
            throw new \InvalidArgumentException('nombre_prenda es requerido');
        }

        // Validar que haya al menos una forma de cantidad
        $tieneCantidades = !empty($prendaData['cantidad_talla']) 
            || !empty($prendaData['cantidades_por_genero'])
            || !empty($prendaData['cantidades'])
            || !empty($prendaData['cantidadesPorTalla']);

        if (!$tieneCantidades) {
            throw new \InvalidArgumentException('Se requiere al menos una estructura de cantidades vÃ¡lida');
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getNombre(): string
    {
        return 'CreacionPrendaSinCotizacion';
    }

    /**
     * ===== MÃ‰TODOS PRIVADOS: LÃ“GICA COMPLEJA =====
     */

    /**
     * Procesar cantidades desde mÃºltiples estructuras posibles
     * 
     * Soporta 3 formatos diferentes de entrada:
     * 1. cantidad_talla = {genero: {talla: cantidad}} (FormData)
     * 2. cantidades_por_genero = {genero: {talla: cantidad}}
     * 3. cantidades/cantidadesPorTalla = {talla: cantidad} (antigua)
     * 
     * ANTES: LÃ­nea 1050-1150 en controller (100 lÃ­neas)
     */
    private function procesarCantidades(array $prendaData): array
    {
        Log::debug(' [procesarCantidades] Analizando estructuras de cantidad', [
            'tiene_cantidad_talla' => !empty($prendaData['cantidad_talla']),
            'tiene_cantidades_por_genero' => !empty($prendaData['cantidades_por_genero']),
            'tiene_cantidades' => !empty($prendaData['cantidades']),
        ]);

        // NUEVA ESTRUCTURA: cantidad_talla = {genero: {talla: cantidad}} (FormData)
        if (!empty($prendaData['cantidad_talla'])) {
            $cantidad = $prendaData['cantidad_talla'];
            if (is_string($cantidad)) {
                $cantidad = json_decode($cantidad, true);
            }
            
            // Si viene en formato plano {dama-S: 20, dama-M: 20}, transformar a jerÃ¡rquico
            if (is_array($cantidad) && !empty($cantidad)) {
                $cantidad = $this->normalizarCantidades($cantidad);
            }
            
            return is_array($cantidad) ? $cantidad : [];
        }

        // ESTRUCTURA ALTERNATIVA: cantidades_por_genero
        if (!empty($prendaData['cantidades_por_genero'])) {
            $cantidad = $prendaData['cantidades_por_genero'];
            if (is_string($cantidad)) {
                $cantidad = json_decode($cantidad, true);
            }
            
            // Si viene en formato plano, transformar a jerÃ¡rquico
            if (is_array($cantidad) && !empty($cantidad)) {
                $cantidad = $this->normalizarCantidades($cantidad);
            }
            
            return is_array($cantidad) ? $cantidad : [];
        }

        // ESTRUCTURA ANTIGUA: cantidades o cantidadesPorTalla
        $cantidad = $prendaData['cantidades'] ?? $prendaData['cantidadesPorTalla'] ?? [];
        if (is_string($cantidad)) {
            $cantidad = json_decode($cantidad, true) ?? [];
        }

        if (is_array($cantidad) && !empty($cantidad)) {
            // Si viene en formato plano, transformar
            $cantidad = $this->normalizarCantidades($cantidad);
        }

        return $cantidad ?? [];
    }

    /**
     * Normalizar cantidades a formato jerÃ¡rquico
     * Transforma {"dama-S": 20, "dama-M": 20} a {"dama": {"S": 20, "M": 20}}
     */
    private function normalizarCantidades(array $cantidades): array
    {
        $resultado = [];
        
        foreach ($cantidades as $key => $valor) {
            // Si es array, ya estÃ¡ en formato correcto
            if (is_array($valor)) {
                $resultado[$key] = $valor;
            } 
            // Si es string con guiÃ³n, dividir en genero-talla
            elseif (is_string($key) && strpos($key, '-') !== false) {
                [$genero, $talla] = explode('-', $key, 2);
                if (!isset($resultado[$genero])) {
                    $resultado[$genero] = [];
                }
                $resultado[$genero][$talla] = (int)$valor;
            }
            // Si no tiene guiÃ³n, asumir que es talla sin gÃ©nero
            else {
                if (!isset($resultado['sin_genero'])) {
                    $resultado['sin_genero'] = [];
                }
                $resultado['sin_genero'][$key] = (int)$valor;
            }
        }
        
        return $resultado;
    }

    /**
     * Calcular cantidad total sumando todas las cantidades
     * Maneja tanto {talla: cantidad} como {genero: {talla: cantidad}}
     */
    private function calcularCantidadTotal(array $cantidades): int
    {
        $total = 0;

        foreach ($cantidades as $key => $valor) {
            if (is_array($valor)) {
                // Es {genero: {talla: cantidad}}
                $total += array_sum($valor);
            } else {
                // Es {talla: cantidad}
                $total += (int)$valor;
            }
        }

        return $total;
    }

    /**
     * Procesar variantes: Color, Tela, Manga, Broche, Bolsillos, Reflectivo
     * 
     * ANTES: LÃ­nea 1200-1350 en controller (150 lÃ­neas)
     */
    private function procesarVariantes(array $prendaData): array
    {
        $variantes = $prendaData['variantes'] ?? [];
        if (is_string($variantes)) {
            $variantes = json_decode($variantes, true) ?? [];
        }

        $resultado = [
            'color_id' => null,
            'tela_id' => null,
            'tipo_manga_id' => null,
            'tipo_broche_id' => null,
            'tiene_bolsillos' => 0,
            'tiene_reflectivo' => 0,
        ];

        if (!is_array($variantes) || empty($variantes)) {
            return $resultado;
        }

        // COLOR
        $colorId = $variantes['color_id'] ?? null;
        if (!$colorId) {
            $colorNombre = $variantes['color'] ?? null;
            if (!$colorNombre && !empty($variantes['telas_multiples'])) {
                $telas = is_string($variantes['telas_multiples']) 
                    ? json_decode($variantes['telas_multiples'], true) 
                    : $variantes['telas_multiples'];
                
                if (is_array($telas) && !empty($telas) && !empty($telas[0]['color'])) {
                    $colorNombre = $telas[0]['color'];
                }
            }

            if ($colorNombre) {
                $color = DB::table('colores_prenda')
                    ->where('nombre', 'LIKE', '%' . $colorNombre . '%')
                    ->first();

                if (!$color) {
                    $colorId = DB::table('colores_prenda')->insertGetId([
                        'nombre' => $colorNombre,
                        'activo' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $colorId = $color->id;
                }
            }
        }

        // TELA
        $telaId = $variantes['tela_id'] ?? null;
        if (!$telaId && !empty($variantes['telas_multiples'])) {
            $telas = is_string($variantes['telas_multiples']) 
                ? json_decode($variantes['telas_multiples'], true) 
                : $variantes['telas_multiples'];
                
            if (is_array($telas) && !empty($telas) && !empty($telas[0]['nombre_tela'])) {
                $tela = DB::table('telas_prenda')
                    ->where('nombre', 'LIKE', '%' . $telas[0]['nombre_tela'] . '%')
                    ->first();

                if (!$tela) {
                    $telaId = DB::table('telas_prenda')->insertGetId([
                        'nombre' => $telas[0]['nombre_tela'],
                        'activo' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $telaId = $tela->id;
                }
            }
        }

        // TIPO MANGA
        $tipoMangaId = $variantes['tipo_manga_id'] ?? null;
        if (!$tipoMangaId && !empty($variantes['tipo_manga'])) {
            $manga = DB::table('tipos_manga')
                ->where('nombre', 'LIKE', '%' . $variantes['tipo_manga'] . '%')
                ->first();

            if (!$manga) {
                $tipoMangaId = DB::table('tipos_manga')->insertGetId([
                    'nombre' => $variantes['tipo_manga'],
                    'activo' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $tipoMangaId = $manga->id;
            }
        }

        // TIPO BROCHE
        $tipoBrocheId = $variantes['tipo_broche_id'] ?? null;
        if (!$tipoBrocheId && !empty($variantes['tipo_broche'])) {
            $broche = DB::table('tipos_broche_boton')
                ->where('nombre', 'LIKE', '%' . $variantes['tipo_broche'] . '%')
                ->first();

            if (!$broche) {
                $tipoBrocheId = DB::table('tipos_broche_boton')->insertGetId([
                    'nombre' => $variantes['tipo_broche'],
                    'activo' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $tipoBrocheId = $broche->id;
            }
        }

        // BOOLEANOS
        $tieneBolsillos = isset($variantes['tiene_bolsillos']) ? ($variantes['tiene_bolsillos'] ? 1 : 0) : 0;
        $tieneReflectivo = isset($variantes['tiene_reflectivo']) ? ($variantes['tiene_reflectivo'] ? 1 : 0) : 0;

        return [
            'color_id' => $colorId,
            'tela_id' => $telaId,
            'tipo_manga_id' => $tipoMangaId,
            'tipo_broche_id' => $tipoBrocheId,
            'tiene_bolsillos' => $tieneBolsillos,
            'tiene_reflectivo' => $tieneReflectivo,
        ];
    }

    /**
     * Armar descripciÃ³n de variaciones desde variantes
     */
    private function armarDescripcionVariaciones(array $variantes): string
    {
        $partes = [];

        if (!empty($variantes['color_id'])) {
            $color = DB::table('colores_prenda')->find($variantes['color_id']);
            if ($color) {
                $partes[] = 'Color: ' . $color->nombre;
            }
        }

        if (!empty($variantes['tela_id'])) {
            $tela = DB::table('telas_prenda')->find($variantes['tela_id']);
            if ($tela) {
                $partes[] = 'Tela: ' . $tela->nombre;
            }
        }

        if (!empty($variantes['tipo_manga_id'])) {
            $manga = DB::table('tipos_manga')->find($variantes['tipo_manga_id']);
            if ($manga) {
                $partes[] = 'Manga: ' . $manga->nombre;
            }
        }

        if (!empty($variantes['tipo_broche_id'])) {
            $broche = DB::table('tipos_broche')->find($variantes['tipo_broche_id']);
            if ($broche) {
                $partes[] = 'Broche: ' . $broche->nombre;
            }
        }

        if ($variantes['tiene_bolsillos']) {
            $partes[] = 'Con bolsillos';
        }

        if ($variantes['tiene_reflectivo']) {
            $partes[] = 'Con reflectivo';
        }

        return implode(' | ', $partes);
    }

    /**
     * Procesar gÃ©nero (puede ser string, array, JSON)
     */
    private function procesarGeneros($genero): array
    {
        if (is_array($genero)) {
            return $genero;
        }

        if (is_string($genero)) {
            // Intentar decodificar JSON
            $decoded = json_decode($genero, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            // Si no es JSON, es un string simple
            return [trim($genero)];
        }

        return [];
    }

    /**
     * Guardar procesos de la prenda (reflectivo, bordado, estampado, etc)
     * 
     * @param int $prendaPedidoId
     * @param string $numeroPedido
     * @param array $procesos Estructura: {reflectivo: {tipo, datos}, bordado: {...}}
     */
    private function guardarProcesos(int $prendaPedidoId, string $numeroPedido, array $procesos): void
    {
        // Mapeo de nombres de proceso a IDs en tipos_procesos
        $tipoProcesoMap = [
            'reflectivo' => 1,
            'bordado' => 2,
            'estampado' => 3,
            'dtf' => 4,
            'sublimado' => 5,
        ];

        foreach ($procesos as $tipoProceso => $procesoData) {
            // Verificar que el proceso tenga datos válidos
            if (empty($procesoData) || !isset($procesoData['datos'])) {
                continue;
            }

            $datos = $procesoData['datos'];
            $tipoProcesoNormalizado = strtolower($tipoProceso);

            // Obtener ID del tipo de proceso
            $tipoProcesoId = $tipoProcesoMap[$tipoProcesoNormalizado] ?? null;
            
            if (!$tipoProcesoId) {
                Log::warning(' [guardarProcesos] Tipo de proceso desconocido', [
                    'tipo' => $tipoProceso,
                    'prenda_id' => $prendaPedidoId,
                ]);
                continue;
            }

            // Crear registro en pedidos_procesos_prenda_detalle
            $proceso = \App\Models\PedidosProcesosPrendaDetalle::create([
                'numero_pedido' => $numeroPedido,
                'prenda_pedido_id' => $prendaPedidoId,
                'tipo_proceso_id' => $tipoProcesoId,
                'proceso' => ucfirst($tipoProceso),
                'estado_proceso' => 'Pendiente',
                'fecha_inicio' => null,
                'fecha_fin' => null,
                'ubicaciones' => isset($datos['ubicaciones']) ? json_encode($datos['ubicaciones']) : null,
                'observaciones' => $datos['observaciones'] ?? null,
                'cantidad_talla' => isset($datos['tallas']) ? json_encode($datos['tallas']) : null,
            ]);

            Log::info(' [guardarProcesos] Proceso guardado', [
                'proceso_id' => $proceso->id,
                'tipo' => $tipoProceso,
                'prenda_id' => $prendaPedidoId,
            ]);

            // Guardar tallas del proceso si las hay
            if (!empty($datos['tallas'])) {
                foreach ($datos['tallas'] as $genero => $tallas) {
                    if (is_array($tallas)) {
                        foreach ($tallas as $talla => $cantidad) {
                            if ($cantidad > 0) {
                                \App\Models\PedidosProcesosPrendaTalla::create([
                                    'proceso_prenda_detalle_id' => $proceso->id,
                                    'genero' => strtoupper($genero),
                                    'talla' => strtoupper($talla),
                                    'cantidad' => (int)$cantidad,
                                ]);
                            }
                        }
                    }
                }
                Log::info('   [guardarProcesos] Tallas de proceso guardadas', [
                    'proceso_id' => $proceso->id,
                ]);
            }

            // Guardar imágenes del proceso si las hay
            if (!empty($datos['imagenes'])) {
                $this->guardarImagenesProceso($proceso->id, $datos['imagenes']);
            }
        }
    }

    /**
     * Guardar imágenes de la prenda principal
     * 
     * @param int $prendaPedidoId
     * @param array $imagenes Array de rutas o archivos
     */
    private function guardarImagenesPrenda(int $prendaPedidoId, array $imagenes): void
    {
        $orden = 1;
        foreach ($imagenes as $imagen) {
            // Si es un array anidado (ej: [[]], [[], []])
            if (is_array($imagen)) {
                foreach ($imagen as $imgNested) {
                    if (is_string($imgNested) && !empty($imgNested)) {
                        \App\Models\PrendaFotoPedido::create([
                            'prenda_pedido_id' => $prendaPedidoId,
                            'ruta_original' => $imgNested,
                            'ruta_webp' => str_replace(['.jpg', '.png', '.jpeg'], '.webp', $imgNested),
                            'orden' => $orden++,
                        ]);
                    }
                }
                continue;
            }

            // Si es una ruta válida
            if (is_string($imagen) && !empty($imagen)) {
                \App\Models\PrendaFotoPedido::create([
                    'prenda_pedido_id' => $prendaPedidoId,
                    'ruta_original' => $imagen,
                    'ruta_webp' => str_replace(['.jpg', '.png', '.jpeg'], '.webp', $imagen),
                    'orden' => $orden++,
                ]);

                Log::debug(' [guardarImagenesPrenda] Imagen guardada', [
                    'prenda_id' => $prendaPedidoId,
                    'ruta' => $imagen,
                    'orden' => $orden - 1,
                ]);
            }
        }
    }

    /**
     * Guardar imágenes de telas
     * 
     * @param int $prendaPedidoId
     * @param array $telas Array de telas con imágenes
     */
    private function guardarImagenesTelas(int $prendaPedidoId, array $telas): void
    {
        foreach ($telas as $telaData) {
            // Crear registro color-tela si no existe
            $colorTela = \App\Models\PrendaPedidoColorTela::create([
                'prenda_pedido_id' => $prendaPedidoId,
                'color_id' => $telaData['color_id'] ?? null,
                'tela_id' => $telaData['tela_id'] ?? null,
            ]);

            Log::debug(' [guardarImagenesTelas] Color-Tela creado', [
                'id' => $colorTela->id,
                'color_id' => $telaData['color_id'] ?? null,
                'tela_id' => $telaData['tela_id'] ?? null,
            ]);

            if (empty($telaData['imagenes'])) {
                continue;
            }

            $orden = 1;
            foreach ($telaData['imagenes'] as $imagen) {
                // Si es un array anidado (ej: [[]])
                if (is_array($imagen)) {
                    foreach ($imagen as $imgNested) {
                        if (is_string($imgNested) && !empty($imgNested)) {
                            \App\Models\PrendaFotoTelaPedido::create([
                                'prenda_pedido_colores_telas_id' => $colorTela->id,
                                'ruta_original' => $imgNested,
                                'ruta_webp' => str_replace(['.jpg', '.png', '.jpeg'], '.webp', $imgNested),
                                'orden' => $orden++,
                            ]);
                        }
                    }
                    continue;
                }

                // Si es una ruta válida directa
                if (is_string($imagen) && !empty($imagen)) {
                    \App\Models\PrendaFotoTelaPedido::create([
                        'prenda_pedido_colores_telas_id' => $colorTela->id,
                        'ruta_original' => $imagen,
                        'ruta_webp' => str_replace(['.jpg', '.png', '.jpeg'], '.webp', $imagen),
                        'orden' => $orden++,
                    ]);

                    Log::debug('   [guardarImagenesTelas] Imagen de tela guardada', [
                        'color_tela_id' => $colorTela->id,
                        'ruta' => $imagen,
                        'orden' => $orden - 1,
                    ]);
                }
            }
        }
    }

    /**
     * Guardar imágenes de un proceso
     * 
     * @param int $procesoId
     * @param array $imagenes
     */
    private function guardarImagenesProceso(int $procesoId, array $imagenes): void
    {
        $orden = 1;
        foreach ($imagenes as $imagen) {
            // Si es un array anidado
            if (is_array($imagen)) {
                if (!empty($imagen)) {
                    $this->guardarImagenesProceso($procesoId, $imagen);
                }
                continue;
            }

            // Si es una ruta válida
            if (is_string($imagen) && !empty($imagen)) {
                \App\Models\PedidosProcessImagenes::create([
                    'proceso_prenda_detalle_id' => $procesoId,
                    'ruta_original' => $imagen,
                    'ruta_webp' => str_replace(['.jpg', '.png', '.jpeg'], '.webp', $imagen),
                    'orden' => $orden,
                    'es_principal' => $orden === 1,
                ]);
                $orden++;

                Log::debug(' [guardarImagenesProceso] Imagen de proceso guardada', [
                    'proceso_id' => $procesoId,
                    'ruta' => $imagen,
                    'orden' => $orden - 1,
                ]);
                \App\Models\ProcesosPrendaImagen::create([
                    'proceso_prenda_detalle_id' => $procesoId,
                    'ruta' => $imagen,
                ]);

                Log::debug(' [guardarImagenesProceso] Imagen de proceso guardada', [
                    'proceso_id' => $procesoId,
                    'ruta' => $imagen,
                ]);
            }
        }
    }
}


