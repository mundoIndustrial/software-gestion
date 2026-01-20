<?php

namespace App\Domain\PedidoProduccion\Strategies;

use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Domain\PedidoProduccion\Services\DescripcionService;
use App\Domain\PedidoProduccion\Services\ImagenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Estrategia de Creación de Prenda SIN COTIZACIÓN
 * 
 * Encapsula la lógica del método controller::crearPrendaSinCotizacion() (~400 líneas)
 * 
 * Responsabilidades:
 * - Procesar estructura compleja de cantidades (3 formas diferentes)
 * - Extraer y crear/obtener variantes (colores, telas, mangas, broches)
 * - Guardar prenda con toda la información
 * - Procesar fotos de prenda y telas
 * - Crear proceso inicial
 * 
 * Patrones utilizados:
 * - Strategy: Diferentes algoritmos para diferentes contextos
 * - Template Method (implícito): procesar() llama métodos privados
 * - Factory: Creación de variantes
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
        string $numeroPedido,
        array $servicios
    ): PrendaPedido {
        $this->descripcionService = $servicios['descripcionService'] ?? throw new \RuntimeException('DescripcionService requerido');
        $this->imagenService = $servicios['imagenService'] ?? throw new \RuntimeException('ImagenService requerido');

        // Validar datos
        $this->validar($prendaData);

        try {
            DB::beginTransaction();

            Log::info('📦 [CreacionPrendaSinCtaStrategy] Procesando prenda', [
                'nombre' => $prendaData['nombre_producto'] ?? 'Sin nombre',
                'numero_pedido' => $numeroPedido,
            ]);

            // ===== PASO 1: PROCESAR CANTIDADES (ANTES LÍNEA 1050-1150) =====
            $cantidadesPorTalla = $this->procesarCantidades($prendaData);
            $cantidadTotal = $this->calcularCantidadTotal($cantidadesPorTalla);

            Log::debug('📊 [CreacionPrendaSinCtaStrategy] Cantidades procesadas', [
                'cantidad_total' => $cantidadTotal,
                'estructura' => count($cantidadesPorTalla) > 0 ? 'Válida' : 'Vacía',
            ]);

            // ===== PASO 2: PROCESAR VARIANTES (ANTES LÍNEA 1200-1350) =====
            $variantes = $this->procesarVariantes($prendaData);

            Log::debug('📝 [CreacionPrendaSinCtaStrategy] Variantes procesadas', [
                'color_id' => $variantes['color_id'],
                'tela_id' => $variantes['tela_id'],
                'tipo_manga_id' => $variantes['tipo_manga_id'],
                'tipo_broche_id' => $variantes['tipo_broche_id'],
            ]);

            // ===== PASO 3: CONSTRUIR DESCRIPCIÓN (ANTES LÍNEA 1165) =====
            $descripcion = $this->descripcionService->construirDescripcionPrendaSinCotizacion(
                $prendaData,
                $cantidadesPorTalla
            );

            // ===== PASO 4: CREAR PRENDA (ANTES LÍNEA 1380-1410) =====
            $prendaPedido = PrendaPedido::create([
                'numero_pedido' => $numeroPedido,
                'nombre_prenda' => $prendaData['nombre_producto'] ?? 'Sin nombre',
                'cantidad' => $cantidadTotal,
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

            // ===== PASO 5: CREAR PROCESO INICIAL (ANTES LÍNEA 1415-1425) =====
            ProcesoPrenda::create([
                'numero_pedido' => $numeroPedido,
                'prenda_pedido_id' => $prendaPedido->id,
                'proceso' => 'Creación Orden',
                'estado_proceso' => 'Completado',
                'fecha_inicio' => now(),
                'fecha_fin' => now(),
            ]);

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
        if (empty($prendaData['nombre_producto'])) {
            throw new \InvalidArgumentException('nombre_producto es requerido');
        }

        // Validar que haya al menos una forma de cantidad
        $tieneCantidades = !empty($prendaData['cantidad_talla']) 
            || !empty($prendaData['cantidades_por_genero'])
            || !empty($prendaData['cantidades'])
            || !empty($prendaData['cantidadesPorTalla']);

        if (!$tieneCantidades) {
            throw new \InvalidArgumentException('Se requiere al menos una estructura de cantidades válida');
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
     * ===== MÉTODOS PRIVADOS: LÓGICA COMPLEJA =====
     */

    /**
     * Procesar cantidades desde múltiples estructuras posibles
     * 
     * Soporta 3 formatos diferentes de entrada:
     * 1. cantidad_talla = {genero: {talla: cantidad}} (FormData)
     * 2. cantidades_por_genero = {genero: {talla: cantidad}}
     * 3. cantidades/cantidadesPorTalla = {talla: cantidad} (antigua)
     * 
     * ANTES: Línea 1050-1150 en controller (100 líneas)
     */
    private function procesarCantidades(array $prendaData): array
    {
        Log::debug('🔍 [procesarCantidades] Analizando estructuras de cantidad', [
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
            return is_array($cantidad) ? $cantidad : [];
        }

        // ESTRUCTURA ALTERNATIVA: cantidades_por_genero
        if (!empty($prendaData['cantidades_por_genero'])) {
            $cantidad = $prendaData['cantidades_por_genero'];
            if (is_string($cantidad)) {
                $cantidad = json_decode($cantidad, true);
            }
            return is_array($cantidad) ? $cantidad : [];
        }

        // ESTRUCTURA ANTIGUA: cantidades o cantidadesPorTalla
        $cantidad = $prendaData['cantidades'] ?? $prendaData['cantidadesPorTalla'] ?? [];
        if (is_string($cantidad)) {
            $cantidad = json_decode($cantidad, true) ?? [];
        }

        if (is_array($cantidad) && !empty($cantidad)) {
            $cantidadesTemp = [];
            foreach ($cantidad as $talla => $valor) {
                $cantidadesTemp[$talla] = (int)$valor;
            }
            return $cantidadesTemp;
        }

        return [];
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
     * ANTES: Línea 1200-1350 en controller (150 líneas)
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
            $broche = DB::table('tipos_broche')
                ->where('nombre', 'LIKE', '%' . $variantes['tipo_broche'] . '%')
                ->first();

            if (!$broche) {
                $tipoBrocheId = DB::table('tipos_broche')->insertGetId([
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
     * Armar descripción de variaciones desde variantes
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
     * Procesar género (puede ser string, array, JSON)
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
}
