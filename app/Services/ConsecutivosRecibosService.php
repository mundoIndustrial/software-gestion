<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsecutivosRecibosService
{
    /**
     * Genera consecutivos de recibos para un pedido cuando cambia a PENDIENTE_INSUMOS
     * 
     * @param PedidoProduccion $pedido
     * @param string $estadoAnterior
     * @param string $estadoNuevo
     * @return bool
     */
    public function generarConsecutivosSiAplica(PedidoProduccion $pedido, string $estadoAnterior, string $estadoNuevo): bool
    {
        // Solo ejecutar cuando el estado cambia a PENDIENTE_INSUMOS
        if ($estadoAnterior === 'PENDIENTE_INSUMOS' || $estadoNuevo !== 'PENDIENTE_INSUMOS') {
            Log::info('🔢 Consecutivos: No aplica generación', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $estadoNuevo,
                'motivo' => 'El cambio no es a PENDIENTE_INSUMOS o ya estaba en ese estado'
            ]);
            return false;
        }

        // Verificar que el pedido no tenga ya consecutivos generados
        if ($this->yaTieneConsecutivos($pedido->id)) {
            Log::info('🔢 Consecutivos: Ya existen para el pedido', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido
            ]);
            return false;
        }

        // Determinar qué tipos de recibo aplican por prenda
        $tiposPorPrenda = $this->determinarTiposReciboPorPrenda($pedido);
        
        if (empty($tiposPorPrenda)) {
            Log::info('🔢 Consecutivos: No hay tipos de recibo aplicables', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido
            ]);
            return false;
        }

        // Generar consecutivos en transacción
        return DB::transaction(function () use ($pedido, $tiposPorPrenda) {
            try {
                $consecutivosGenerados = [];

                foreach ($tiposPorPrenda as $prendaId => $tiposRecibo) {
                    foreach ($tiposRecibo as $tipoRecibo) {
                        // Obtener el último consecutivo para este tipo de recibo
                        $ultimoConsecutivo = DB::table('consecutivos_recibos_pedidos')
                            ->where('tipo_recibo', $tipoRecibo)
                            ->where('activo', 1)
                            ->orderBy('consecutivo_actual', 'desc')
                            ->lockForUpdate()
                            ->first();

                        $nuevoConsecutivo = $ultimoConsecutivo ? $ultimoConsecutivo->consecutivo_actual + 1 : 1;

                        // Insertar registro para el pedido y tipo específico
                        DB::table('consecutivos_recibos_pedidos')->insert([
                            'pedido_produccion_id' => $pedido->id,
                            'tipo_recibo' => $tipoRecibo,
                            'consecutivo_inicial' => $nuevoConsecutivo,
                            'consecutivo_actual' => $nuevoConsecutivo,
                            'activo' => 1,
                            'notas' => "Generado automáticamente para pedido #{$pedido->numero_pedido} - prenda #{$prendaId}",
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        $consecutivosGenerados[] = [
                            'prenda_id' => $prendaId,
                            'tipo' => $tipoRecibo,
                            'consecutivo' => $nuevoConsecutivo
                        ];
                    }
                }

                Log::info('🔢 Consecutivos generados exitosamente', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado_anterior' => 'PENDIENTE_SUPERVISOR',
                    'estado_nuevo' => 'PENDIENTE_INSUMOS',
                    'consecutivos' => $consecutivosGenerados,
                    'usuario' => auth()->user()->name ?? 'sistema'
                ]);

                return true;

            } catch (\Exception $e) {
                Log::error('🔢 Error al generar consecutivos', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Verifica si un pedido ya tiene consecutivos generados
     */
    private function yaTieneConsecutivos(int $pedidoId): bool
    {
        return DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedidoId)
            ->exists();
    }

    /**
     * Determina qué tipos de recibo aplican por cada prenda
     * Basado en la lógica: COSTURA por prenda (si no es de bodega) + procesos por prenda
     */
    public function determinarTiposReciboPorPrenda(PedidoProduccion $pedido): array
    {
        $tiposPorPrenda = [];
        
        // Cargar el pedido con sus prendas y procesos
        $pedidoCompleto = PedidoProduccion::with(['prendas.procesos.tipoProceso'])
            ->find($pedido->id);

        if (!$pedidoCompleto) {
            return $tiposPorPrenda;
        }

        // Analizar cada prenda individualmente
        foreach ($pedidoCompleto->prendas as $prenda) {
            $tiposPrenda = [];
            
            // COSTURA: Solo si la prenda NO es de bodega
            if (!$prenda->de_bodega) {
                $tiposPrenda[] = 'COSTURA';
            }

            // Para ESTAMPADO, BORDADO, REFLECTIVO: se generan por proceso 
            // independientemente de si la prenda es de bodega o no
            foreach ($prenda->procesos as $proceso) {
                // Obtener el nombre del tipo de proceso desde la relación
                $nombreTipoProceso = strtoupper(trim($proceso->tipoProceso->nombre ?? ''));
                
                // Si no hay relación, intentar obtener directamente desde la BD
                if (!$proceso->tipoProceso) {
                    $tipoDirecto = DB::table('tipos_procesos')
                        ->where('id', $proceso->tipo_proceso_id)
                        ->first();
                    $nombreTipoProceso = strtoupper(trim($tipoDirecto->nombre ?? ''));
                }
                
                // Mapear tipos de proceso a tipos de recibo
                switch ($nombreTipoProceso) {
                    case 'BORDADO':
                        if (!in_array('BORDADO', $tiposPrenda)) {
                            $tiposPrenda[] = 'BORDADO';
                        }
                        break;
                    case 'ESTAMPADO':
                        if (!in_array('ESTAMPADO', $tiposPrenda)) {
                            $tiposPrenda[] = 'ESTAMPADO';
                        }
                        break;
                    case 'REFLECTIVO':
                        if (!in_array('REFLECTIVO', $tiposPrenda)) {
                            $tiposPrenda[] = 'REFLECTIVO';
                        }
                        break;
                }
            }

            // Si la prenda tiene tipos de recibo aplicables, agregarlos
            if (!empty($tiposPrenda)) {
                $tiposPorPrenda[$prenda->id] = $tiposPrenda;
            }
        }

        Log::info('🔢 Tipos de recibo determinados por prenda', [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'tipos_por_prenda' => $tiposPorPrenda,
            'total_prendas' => $pedidoCompleto->prendas->count(),
            'prendas_con_consecutivo' => count($tiposPorPrenda),
            'total_consecutivos_a_generar' => array_sum(array_map('count', $tiposPorPrenda))
        ]);

        return $tiposPorPrenda;
    }

    /**
     * Obtiene los consecutivos asignados a un pedido
     */
    public function obtenerConsecutivosPedido(int $pedidoId): array
    {
        return DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('activo', 1)
            ->get()
            ->map(function ($registro) {
                return [
                    'tipo_recibo' => $registro->tipo_recibo,
                    'consecutivo' => $registro->consecutivo_actual,
                    'formato' => $this->formatearConsecutivo($registro->tipo_recibo, $registro->consecutivo_actual)
                ];
            })
            ->toArray();
    }

    /**
     * Formatea un consecutivo para mostrar (sin prefijo, solo número)
     */
    private function formatearConsecutivo(string $tipo, int $consecutivo): string
    {
        // Solo retornar el número consecutivo, sin prefijos
        return (string) $consecutivo;
    }
}
