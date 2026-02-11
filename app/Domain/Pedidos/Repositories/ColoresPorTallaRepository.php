<?php

namespace App\Domain\Pedidos\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Repository para Colores por Talla
 * Maneja el acceso a datos de asignaciones de colores
 */
class ColoresPorTallaRepository
{
    /**
     * Obtener asignaciones con filtros opcionales
     * NOTA: Ahora usa la tabla prenda_pedido_tallas existente
     */
    public function obtenerAsignaciones(array $filters = []): array
    {
        try {
            Log::info('Obteniendo asignaciones de colores', ['filters' => $filters]);
            
            $query = DB::table('prenda_pedido_tallas')
                ->select([
                    'id',
                    'prenda_pedido_id',
                    'genero', 
                    'talla',
                    'tipo_talla',
                    'tela',
                    'colores',
                    'cantidad',
                    'es_sobremedida',
                    'created_at',
                    'updated_at'
                ])
                ->orderBy('created_at', 'desc');
            
            // Aplicar filtros
            if (!empty($filters['genero'])) {
                $query->where('genero', $filters['genero']);
            }
            
            if (!empty($filters['talla'])) {
                $query->where('talla', $filters['talla']);
            }
            
            if (!empty($filters['tela'])) {
                $query->where('tela', $filters['tela']);
            }
            
            if (!empty($filters['prenda_pedido_id'])) {
                $query->where('prenda_pedido_id', $filters['prenda_pedido_id']);
            }
            
            $asignaciones = $query->get()->toArray();
            
            Log::info('Asignaciones obtenidas de BD', ['filters' => $filters, 'total' => count($asignaciones)]);
            
            // Transformar datos para la respuesta
            $resultado = [];
            foreach ($asignaciones as $asignacion) {
                $resultado[] = [
                    'id' => $asignacion->id,
                    'prenda_pedido_id' => $asignacion->prenda_pedido_id,
                    'genero' => $asignacion->genero,
                    'talla' => $asignacion->talla,
                    'tipo_talla' => $asignacion->tipo_talla,
                    'tela' => $asignacion->tela,
                    'colores' => $this->formatearColores($asignacion->colores),
                    'cantidad' => $asignacion->cantidad,
                    'es_sobremedida' => $asignacion->es_sobremedida,
                    'total_unidades' => $this->calcularTotalUnidades($asignacion->colores),
                    'created_at' => $asignacion->created_at,
                    'updated_at' => $asignacion->updated_at
                ];
            }
            
            Log::info('Asignaciones obtenidas exitosamente', ['total' => count($resultado)]);
            
            return $resultado;
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo asignaciones de BD', [
                'filters' => $filters,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Buscar asignación por ID
     */
    public function buscarPorId(int $id)
    {
        try {
            $asignacion = DB::table('prenda_pedido_tallas')
                ->where('id', $id)
                ->first();
            
            if (!$asignacion) {
                Log::warning('Asignación no encontrada', ['id' => $id]);
                return null;
            }
            
            Log::info('Asignación encontrada', ['id' => $id]);
            
            return $asignacion;
            
        } catch (\Exception $e) {
            Log::error('Error buscando asignación por ID', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Buscar asignación por género y talla
     */
    public function buscarPorGeneroYTalla(string $genero, string $talla, string $tipoTalla)
    {
        try {
            $asignacion = DB::table('prenda_pedido_tallas')
                ->where('genero', $genero)
                ->where('talla', $talla)
                ->where('tipo_talla', $tipoTalla)
                ->first();
            
            Log::info('Búsqueda por género y talla', [
                'genero' => $genero,
                'talla' => $talla,
                'tipo_talla' => $tipoTalla,
                'encontrada' => !!$asignacion
            ]);
            
            return $asignacion;
            
        } catch (\Exception $e) {
            Log::error('Error buscando asignación por género y talla', [
                'genero' => $genero,
                'talla' => $talla,
                'tipo_talla' => $tipoTalla,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Crear nueva asignación
     */
    public function crear($asignacion)
    {
        try {
            $id = DB::table('prenda_pedido_tallas')->insertGetId([
                'prenda_pedido_id' => $asignacion->prenda_pedido_id ?? null,
                'genero' => $asignacion->genero,
                'talla' => $asignacion->talla,
                'tipo_talla' => $asignacion->tipo_talla,
                'tela' => $asignacion->tela,
                'colores' => $asignacion->colores,
                'cantidad' => $asignacion->cantidad ?? 0,
                'es_sobremedida' => $asignacion->es_sobremedida ?? 0,
                'created_at' => $asignacion->created_at ?? now(),
                'updated_at' => $asignacion->updated_at ?? now()
            ]);
            
            $asignacion->id = $id;
            
            Log::info('Asignación creada', ['id' => $id]);
            
            return $asignacion;
            
        } catch (\Exception $e) {
            Log::error('Error creando asignación', [
                'asignacion' => $asignacion,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Guardar asignación (crear o actualizar)
     */
    public function guardar($asignacion)
    {
        try {
            if (isset($asignacion->id)) {
                // Actualizar
                $actualizadas = DB::table('prenda_pedido_tallas')
                    ->where('id', $asignacion->id)
                    ->update([
                        'prenda_pedido_id' => $asignacion->prenda_pedido_id,
                        'genero' => $asignacion->genero,
                        'talla' => $asignacion->talla,
                        'tipo_talla' => $asignacion->tipo_talla,
                        'tela' => $asignacion->tela,
                        'colores' => $asignacion->colores,
                        'cantidad' => $asignacion->cantidad,
                        'es_sobremedida' => $asignacion->es_sobremedida,
                        'updated_at' => $asignacion->updated_at
                    ]);
                
                Log::info('Asignación actualizada', ['id' => $asignacion->id, 'actualizadas' => $actualizadas]);
                
            } else {
                // Crear
                $id = DB::table('prenda_pedido_tallas')->insertGetId([
                    'prenda_pedido_id' => $asignacion->prenda_pedido_id ?? null,
                    'genero' => $asignacion->genero,
                    'talla' => $asignacion->talla,
                    'tipo_talla' => $asignacion->tipo_talla,
                    'tela' => $asignacion->tela,
                    'colores' => $asignacion->colores,
                    'cantidad' => $asignacion->cantidad ?? 0,
                    'es_sobremedida' => $asignacion->es_sobremedida ?? 0,
                    'created_at' => $asignacion->created_at ?? now(),
                    'updated_at' => $asignacion->updated_at ?? now()
                ]);
                
                $asignacion->id = $id;
                
                Log::info('Asignación creada', ['id' => $id]);
            }
            
            return $asignacion;
            
        } catch (\Exception $e) {
            Log::error('Error guardando asignación', [
                'asignacion' => $asignacion,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Eliminar asignación
     */
    public function eliminar($asignacion)
    {
        try {
            $eliminadas = DB::table('prenda_pedido_tallas')
                ->where('id', $asignacion->id)
                ->delete();
            
            Log::info('Asignación eliminada', ['id' => $asignacion->id, 'eliminadas' => $eliminadas]);
            
            return $eliminadas > 0;
            
        } catch (\Exception $e) {
            Log::error('Error eliminando asignación', [
                'id' => $asignacion->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Obtener estadísticas de asignaciones
     */
    public function obtenerEstadisticas(): array
    {
        try {
            $stats = DB::table('colores_por_talla')
                ->selectRaw('COUNT(*) as total_asignaciones')
                ->selectRaw('SUM(JSON_LENGTH(colores)) as total_colores')
                ->selectRaw('COUNT(DISTINCT genero) as generos_unicos')
                ->selectRaw('COUNT(DISTINCT talla) as tallas_unicas')
                ->selectRaw('COUNT(DISTINCT tela) as telas_unicas')
                ->first();
            
            // Calcular total de unidades
            $totalUnidades = DB::table('colores_por_talla')
                ->selectRaw('SUM(
                    (
                        SELECT SUM(JSON_EXTRACT(colores, CONCAT("$[", idx, "].cantidad")))
                        FROM JSON_TABLE(colores, "$[*]" AS idx) AS jt
                    )
                ) as total_unidades')
                ->value('total_unidades') ?? 0;
            
            $estadisticas = [
                'total_asignaciones' => $stats->total_asignaciones ?? 0,
                'total_colores' => $stats->total_colores ?? 0,
                'total_unidades' => $totalUnidades,
                'generos_unicos' => $stats->generos_unicos ?? 0,
                'tallas_unicas' => $stats->tallas_unicas ?? 0,
                'telas_unicas' => $stats->telas_unicas ?? 0
            ];
            
            Log::info('Estadísticas obtenidas', $estadisticas);
            
            return $estadisticas;
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Verificar si existe una asignación
     */
    public function existe(string $genero, string $talla, string $tipoTalla): bool
    {
        try {
            $existe = DB::table('colores_por_talla')
                ->where('genero', $genero)
                ->where('talla', $talla)
                ->where('tipo_talla', $tipoTalla)
                ->exists();
            
            Log::info('Verificación de existencia', [
                'genero' => $genero,
                'talla' => $talla,
                'tipo_talla' => $tipoTalla,
                'existe' => $existe
            ]);
            
            return $existe;
            
        } catch (\Exception $e) {
            Log::error('Error verificando existencia de asignación', [
                'genero' => $genero,
                'talla' => $talla,
                'tipo_talla' => $tipoTalla,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Limpiar asignaciones antiguas
     */
    public function limpiarAntiguas(int $dias = 30): int
    {
        try {
            $eliminadas = DB::table('colores_por_talla')
                ->where('created_at', '<', now()->subDays($dias))
                ->delete();
            
            Log::info('Asignaciones antiguas eliminadas', [
                'dias' => $dias,
                'eliminadas' => $eliminadas
            ]);
            
            return $eliminadas;
            
        } catch (\Exception $e) {
            Log::error('Error limpiando asignaciones antiguas', [
                'dias' => $dias,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}
