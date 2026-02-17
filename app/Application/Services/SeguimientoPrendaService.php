<?php

namespace App\Application\Services;

use App\Models\SeguimientoPedidosPorPrenda;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SeguimientoPrendaService
{
    /**
     * Crear seguimiento para una prenda
     */
    public function crearSeguimiento(array $data): SeguimientoPedidosPorPrenda
    {
        try {
            Log::info('[SeguimientoPrendaService] Creando seguimiento', $data);
            
            $seguimiento = SeguimientoPedidosPorPrenda::create([
                'pedido_produccion_id' => $data['pedido_produccion_id'],
                'prenda_id' => $data['prenda_id'],
                'proceso_prenda_id' => $data['proceso_prenda_id'] ?? null,
                'tipo_recibo' => $data['tipo_recibo'],
                'area' => $data['area'] ?? null,
                'estado' => $data['estado'] ?? 'Pendiente',
                'consecutivo_actual' => $data['consecutivo_actual'] ?? 0,
                'consecutivo_inicial' => $data['consecutivo_inicial'] ?? 0,
                'encargado' => $data['encargado'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'activo' => true,
            ]);

            Log::info('[SeguimientoPrendaService] Seguimiento creado exitosamente', [
                'seguimiento_id' => $seguimiento->id,
                'prenda_id' => $seguimiento->prenda_id,
                'tipo_recibo' => $seguimiento->tipo_recibo
            ]);

            return $seguimiento;

        } catch (\Exception $e) {
            Log::error('[SeguimientoPrendaService] Error al crear seguimiento', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Iniciar seguimiento de proceso para una prenda
     */
    public function iniciarProceso(int $pedidoId, int $prendaId, string $area, string $encargado = null): SeguimientoPedidosPorPrenda
    {
        try {
            // Buscar si ya existe un seguimiento para esta área
            $seguimientoExistente = SeguimientoPedidosPorPrenda::where('pedido_produccion_id', $pedidoId)
                ->where('prenda_id', $prendaId)
                ->where('area', $area)
                ->activos()
                ->first();

            if ($seguimientoExistente) {
                // Si existe, solo actualizar estado
                $seguimientoExistente->iniciarProceso($encargado);
                return $seguimientoExistente;
            }

            // Crear nuevo seguimiento
            return $this->crearSeguimiento([
                'pedido_produccion_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'area' => $area,
                'estado' => 'En Progreso',
                'encargado' => $encargado,
                'tipo_recibo' => $this->determinarTipoReciboDesdeArea($area),
                'consecutivo_actual' => 0,
                'consecutivo_inicial' => 10, // Valor por defecto
            ]);

        } catch (\Exception $e) {
            Log::error('[SeguimientoPrendaService] Error al iniciar proceso', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'area' => $area,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Completar proceso de una prenda
     */
    public function completarProceso(int $pedidoId, int $prendaId, string $area): bool
    {
        try {
            $seguimiento = SeguimientoPedidosPorPrenda::where('pedido_produccion_id', $pedidoId)
                ->where('prenda_id', $prendaId)
                ->where('area', $area)
                ->activos()
                ->first();

            if (!$seguimiento) {
                Log::warning('[SeguimientoPrendaService] No se encontró seguimiento para completar', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prendaId,
                    'area' => $area
                ]);
                return false;
            }

            return $seguimiento->completarProceso();

        } catch (\Exception $e) {
            Log::error('[SeguimientoPrendaService] Error al completar proceso', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'area' => $area,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener seguimientos de un pedido
     */
    public function obtenerSeguimientosPorPedido(int $pedidoId): array
    {
        try {
            $seguimientos = SeguimientoPedidosPorPrenda::where('pedido_produccion_id', $pedidoId)
                ->activos()
                ->with(['prenda', 'procesoPrenda'])
                ->get()
                ->groupBy('prenda_id');

            $resultado = [];
            foreach ($seguimientos as $prendaId => $seguimientosPorPrenda) {
                $prendas = $seguimientosPorPrenda->first()->prenda;
                
                $seguimientosPorTipo = [];
                $seguimientosPorArea = [];

                foreach ($seguimientosPorPrenda as $seguimiento) {
                    // Agrupar por tipo de recibo
                    if ($seguimiento->tipo_recibo) {
                        $seguimientosPorTipo[$seguimiento->tipo_recibo] = [
                            'consecutivo_actual' => $seguimiento->consecutivo_actual,
                            'consecutivo_inicial' => $seguimiento->consecutivo_inicial,
                            'siguiente_consecutivo' => $seguimiento->siguiente_consecutivo,
                            'tiene_disponibles' => $seguimiento->tieneConsecutivosDisponibles(),
                            'notas' => $seguimiento->notas,
                        ];
                    }

                    // Agrupar por área
                    if ($seguimiento->area) {
                        $seguimientosPorArea[$seguimiento->area] = [
                            'id' => $seguimiento->id,
                            'proceso_prenda_id' => $seguimiento->proceso_prenda_id,
                            'area' => $seguimiento->area,
                            'estado' => $seguimiento->estado,
                            'fecha_inicio' => $seguimiento->fecha_inicio,
                            'fecha_fin' => $seguimiento->fecha_fin,
                            'encargado' => $seguimiento->encargado,
                            'observaciones' => $seguimiento->observaciones,
                            'icono' => $seguimiento->icono_area,
                            'duracion_dias' => $seguimiento->duracion_dias,
                            'esta_activo' => $seguimiento->estaActivo(),
                        ];
                    }
                }

                $resultado[] = [
                    'id' => $prendas->id,
                    'nombre_prenda' => $prendas->nombre_prenda,
                    'descripcion' => $prendas->descripcion,
                    'cantidad' => $prendas->cantidad,
                    'cantidad_talla' => $prendas->cantidad_talla,
                    'seguimientos' => $seguimientosPorTipo,
                    'seguimientos_por_area' => $seguimientosPorArea,
                ];
            }

            return $resultado;

        } catch (\Exception $e) {
            Log::error('[SeguimientoPrendaService] Error al obtener seguimientos', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Sincronizar seguimientos con procesos_prenda
     */
    public function sincronizarConProcesosPrenda(int $pedidoId): array
    {
        try {
            Log::info('[SeguimientoPrendaService] Iniciando sincronización con procesos_prenda', [
                'pedido_id' => $pedidoId
            ]);

            // Obtener procesos de la tabla procesos_prenda
            $procesos = DB::table('procesos_prenda')
                ->where('numero_pedido', $pedidoId)
                ->get();

            $sincronizados = [];
            $creados = 0;
            $actualizados = 0;

            foreach ($procesos as $proceso) {
                // Buscar prenda correspondiente
                $prenda = PrendaPedido::where('pedido_produccion_id', $pedidoId)
                    ->where('id', $proceso->prenda_pedido_id)
                    ->first();

                if (!$prenda) {
                    Log::warning('[SeguimientoPrendaService] Prenda no encontrada para proceso', [
                        'proceso_id' => $proceso->id,
                        'prenda_pedido_id' => $proceso->prenda_pedido_id
                    ]);
                    continue;
                }

                // Buscar seguimiento existente
                $seguimientoExistente = SeguimientoPedidosPorPrenda::where('pedido_produccion_id', $pedidoId)
                    ->where('prenda_id', $prenda->id)
                    ->where('area', $proceso->proceso)
                    ->activos()
                    ->first();

                if ($seguimientoExistente) {
                    // Actualizar seguimiento existente
                    $seguimientoExistente->update([
                        'estado' => $proceso->estado_proceso,
                        'fecha_inicio' => $proceso->fecha_inicio,
                        'fecha_fin' => $proceso->fecha_fin,
                        'encargado' => $proceso->encargado,
                        'observaciones' => $proceso->observaciones,
                    ]);
                    $actualizados++;
                } else {
                    // Crear nuevo seguimiento
                    $this->crearSeguimiento([
                        'pedido_produccion_id' => $pedidoId,
                        'prenda_id' => $prenda->id,
                        'proceso_prenda_id' => $proceso->id,
                        'area' => $proceso->proceso,
                        'estado' => $proceso->estado_proceso,
                        'fecha_inicio' => $proceso->fecha_inicio,
                        'fecha_fin' => $proceso->fecha_fin,
                        'encargado' => $proceso->encargado,
                        'observaciones' => $proceso->observaciones,
                        'tipo_recibo' => $this->determinarTipoReciboDesdeArea($proceso->proceso),
                        'consecutivo_actual' => 0,
                        'consecutivo_inicial' => 10,
                    ]);
                    $creados++;
                }

                $sincronizados[] = [
                    'proceso_id' => $proceso->id,
                    'area' => $proceso->proceso,
                    'estado' => $proceso->estado_proceso,
                    'accion' => $seguimientoExistente ? 'actualizado' : 'creado'
                ];
            }

            Log::info('[SeguimientoPrendaService] Sincronización completada', [
                'pedido_id' => $pedidoId,
                'procesos_procesados' => count($procesos),
                'creados' => $creados,
                'actualizados' => $actualizados
            ]);

            return $sincronizados;

        } catch (\Exception $e) {
            Log::error('[SeguimientoPrendaService] Error en sincronización', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Determinar tipo de recibo basado en el área
     */
    private function determinarTipoReciboDesdeArea(string $area): string
    {
        $mapeo = [
            'Corte' => 'COSTURA',
            'Bordado' => 'BORDADO',
            'Estampado' => 'ESTAMPADO',
            'Reflectivo' => 'REFLECTIVO',
            'DTF' => 'DTF',
            'Sublimado' => 'SUBLIMADO',
        ];

        return $mapeo[$area] ?? 'COSTURA';
    }

    /**
     * Obtener estadísticas de seguimiento para un pedido
     */
    public function obtenerEstadisticasPedido(int $pedidoId): array
    {
        try {
            $seguimientos = SeguimientoPedidosPorPrenda::where('pedido_produccion_id', $pedidoId)
                ->activos()
                ->get();

            $estadisticas = [
                'total_seguimientos' => $seguimientos->count(),
                'por_estado' => [
                    'Pendiente' => 0,
                    'En Progreso' => 0,
                    'Completado' => 0,
                    'Pausado' => 0,
                ],
                'por_area' => [],
                'prendas_con_seguimiento' => $seguimientos->pluck('prenda_id')->unique()->count(),
            ];

            foreach ($seguimientos as $seguimiento) {
                // Contar por estado
                $estadisticas['por_estado'][$seguimiento->estado]++;

                // Contar por área
                if ($seguimiento->area) {
                    if (!isset($estadisticas['por_area'][$seguimiento->area])) {
                        $estadisticas['por_area'][$seguimiento->area] = [
                            'total' => 0,
                            'completados' => 0,
                            'en_progreso' => 0,
                        ];
                    }
                    $estadisticas['por_area'][$seguimiento->area]['total']++;
                    
                    if ($seguimiento->estado === 'Completado') {
                        $estadisticas['por_area'][$seguimiento->area]['completados']++;
                    } elseif ($seguimiento->estado === 'En Progreso') {
                        $estadisticas['por_area'][$seguimiento->area]['en_progreso']++;
                    }
                }
            }

            return $estadisticas;

        } catch (\Exception $e) {
            Log::error('[SeguimientoPrendaService] Error al obtener estadísticas', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
