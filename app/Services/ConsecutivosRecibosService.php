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
            Log::info(' Consecutivos: No aplica generación', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $estadoNuevo,
                'motivo' => 'El cambio no es a PENDIENTE_INSUMOS o ya estaba en ese estado'
            ]);
            return false;
        }

        // Verificar que el usuario tenga rol SUPERVISOR_PEDIDOS o supervisor_pedidos
        if (!auth()->user()->hasRole('SUPERVISOR_PEDIDOS') && !auth()->user()->hasRole('supervisor_pedidos')) {
            Log::info(' Consecutivos: Usuario no autorizado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'usuario' => auth()->user()->name ?? 'sistema',
                'motivo' => 'Usuario no tiene rol SUPERVISOR_PEDIDOS ni supervisor_pedidos'
            ]);
            return false;
        }

        // Determinar qué tipos de recibo aplican
        $tiposRecibo = $this->determinarTiposRecibo($pedido);
        
        if (empty($tiposRecibo)) {
            Log::info(' Consecutivos: No hay tipos de recibo aplicables', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido
            ]);
            return false;
        }

        // Generar consecutivos en transacción
        return DB::transaction(function () use ($pedido, $tiposRecibo) {
            try {
                $consecutivosGenerados = [];

                foreach ($tiposRecibo as $tipoRecibo => $config) {
                    // Validar que no existan duplicados
                    $existe = $this->existeConsecutivo($pedido->id, $tipoRecibo, $config['prenda_pedido_id'] ?? null);
                    if ($existe) {
                        Log::warning(' Consecutivo ya existe, omitiendo', [
                            'pedido_id' => $pedido->id,
                            'tipo_recibo' => $tipoRecibo,
                            'prenda_pedido_id' => $config['prenda_pedido_id'] ?? null
                        ]);
                        continue;
                    }

                    // Extraer el tipo de recibo real (sin el sufijo de prenda si existe)
                    // COSTURA_20 -> COSTURA, ESTAMPADO -> ESTAMPADO
                    $tipoReciboReal = $config['tipo_recibo'] ?? explode('_', $tipoRecibo)[0];

                    // Obtener el siguiente consecutivo de la tabla maestra consecutivos_recibos
                    $registroMaestro = DB::table('consecutivos_recibos')
                        ->where('tipo_recibo', $tipoReciboReal)
                        ->where('activo', 1)
                        ->lockForUpdate()
                        ->first();

                    if (!$registroMaestro) {
                        Log::warning(' No existe registro maestro para tipo de recibo', [
                            'tipo_recibo_buscado' => $tipoReciboReal,
                            'tipo_recibo_clave' => $tipoRecibo,
                            'pedido_id' => $pedido->id
                        ]);
                        continue;
                    }

                    // Obtener el siguiente número y actualizar la tabla maestra
                    $nuevoConsecutivo = $registroMaestro->consecutivo_actual + 1;
                    
                    // Actualizar el consecutivo en la tabla maestra
                    DB::table('consecutivos_recibos')
                        ->where('id', $registroMaestro->id)
                        ->update([
                            'consecutivo_actual' => $nuevoConsecutivo,
                            'updated_at' => now()
                        ]);

                    // Insertar registro con la estructura correcta
                    $insertData = [
                        'pedido_produccion_id' => $pedido->id,
                        'tipo_recibo' => $config['tipo_recibo'],
                        'consecutivo_inicial' => $nuevoConsecutivo,
                        'consecutivo_actual' => $nuevoConsecutivo,
                        'activo' => 1,
                        'notas' => "Generado automáticamente para pedido #{$pedido->numero_pedido}" . (
                            $config['prenda_pedido_id'] ? " - prenda #{$config['prenda_pedido_id']}" : ""
                        ),
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                    // Agregar prenda_id solo si aplica
                    if (isset($config['prenda_pedido_id'])) {
                        $insertData['prenda_id'] = $config['prenda_pedido_id'];
                    }

                    DB::table('consecutivos_recibos_pedidos')->insert($insertData);

                    $consecutivosGenerados[] = [
                        'tipo' => $tipoRecibo,
                        'consecutivo' => $nuevoConsecutivo,
                        'prenda_pedido_id' => $config['prenda_pedido_id'] ?? null
                    ];
                }

                Log::info(' Consecutivos generados exitosamente', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado_anterior' => 'PENDIENTE_SUPERVISOR',
                    'estado_nuevo' => 'PENDIENTE_INSUMOS',
                    'consecutivos' => $consecutivosGenerados,
                    'usuario' => auth()->user()->name ?? 'sistema'
                ]);

                return true;

            } catch (\Exception $e) {
                Log::error(' Error al generar consecutivos', [
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
     * Verifica si ya existe un consecutivo para evitar duplicados
     */
    private function existeConsecutivo(int $pedidoId, string $tipoRecibo, ?int $prendaPedidoId): bool
    {
        $query = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('tipo_recibo', $tipoRecibo);
            
        if ($prendaPedidoId) {
            $query->where('prenda_id', $prendaPedidoId);
        } else {
            $query->whereNull('prenda_id');
        }
        
        return $query->exists();
    }

    /**
     * Determina qué tipos de recibo aplican para un pedido
     * Regla: COSTURA por cada prenda (si no es de bodega), otros procesos por pedido
     */
    public function determinarTiposRecibo(PedidoProduccion $pedido): array
    {
        $tiposRecibo = [];
        
        // Cargar el pedido con sus prendas y procesos
        $pedidoCompleto = PedidoProduccion::with(['prendas.procesos.tipoProceso'])
            ->find($pedido->id);

        if (!$pedidoCompleto) {
            return $tiposRecibo;
        }

        // Procesar cada prenda para COSTURA
        foreach ($pedidoCompleto->prendas as $prenda) {
            // COSTURA: Solo si la prenda NO es de bodega
            if (!$prenda->de_bodega) {
                $tiposRecibo['COSTURA_' . $prenda->id] = [
                    'tipo_recibo' => 'COSTURA',
                    'prenda_pedido_id' => $prenda->id
                ];
            }
        }

        // Para ESTAMPADO, BORDADO, REFLECTIVO, DTF, SUBLIMADO: se generan por cada prenda que tenga el proceso
        foreach ($pedidoCompleto->prendas as $prenda) {
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
                
                // Mapear tipos de proceso a tipos de recibo (uno por cada prenda)
                switch ($nombreTipoProceso) {
                    case 'BORDADO':
                        $tiposRecibo["BORDADO_{$prenda->id}"] = [
                            'tipo_recibo' => 'BORDADO',
                            'prenda_pedido_id' => $prenda->id
                        ];
                        break;
                    case 'ESTAMPADO':
                        $tiposRecibo["ESTAMPADO_{$prenda->id}"] = [
                            'tipo_recibo' => 'ESTAMPADO',
                            'prenda_pedido_id' => $prenda->id
                        ];
                        break;
                    case 'DTF':
                        $tiposRecibo["DTF_{$prenda->id}"] = [
                            'tipo_recibo' => 'DTF',
                            'prenda_pedido_id' => $prenda->id
                        ];
                        break;
                    case 'SUBLIMADO':
                        $tiposRecibo["SUBLIMADO_{$prenda->id}"] = [
                            'tipo_recibo' => 'SUBLIMADO',
                            'prenda_pedido_id' => $prenda->id
                        ];
                        break;
                    case 'REFLECTIVO':
                        // REFLECTIVO: un consecutivo por cada prenda con de_bodega = true
                        if ($prenda->de_bodega) {
                            $tiposRecibo["REFLECTIVO_{$prenda->id}"] = [
                                'tipo_recibo' => 'REFLECTIVO',
                                'prenda_pedido_id' => $prenda->id
                            ];
                        }
                        break;
                }
            }
        }

        Log::info(' Tipos de recibo determinados', [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'tipos_recibo' => $tiposRecibo,
            'total_prendas' => $pedidoCompleto->prendas->count(),
            'prendas_no_bodega' => $pedidoCompleto->prendas->where('de_bodega', 0)->count(),
            'total_consecutivos_a_generar' => count($tiposRecibo)
        ]);

        return $tiposRecibo;
    }

    /**
     * Obtiene los consecutivos asignados a un pedido en el formato esperado por el frontend
     */
    public function obtenerConsecutivosPedido(int $pedidoId): array
    {
        $consecutivos = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('activo', 1)
            ->get();

        $resultado = [];
        
        foreach ($consecutivos as $registro) {
            $tipoRecibo = $registro->tipo_recibo;
            $consecutivo = $registro->consecutivo_actual;
            $prendaId = $registro->prenda_id;
            
            if ($prendaId) {
                // Para COSTURA: estructura anidada por prenda
                if (!isset($resultado[$tipoRecibo])) {
                    $resultado[$tipoRecibo] = [];
                }
                $resultado[$tipoRecibo][(string)$prendaId] = $consecutivo;
            } else {
                // Para otros procesos: valor directo
                $resultado[$tipoRecibo] = $consecutivo;
            }
        }
        
        Log::info(' Consecutivos obtenidos para pedido', [
            'pedido_id' => $pedidoId,
            'resultado' => $resultado,
            'total_registros' => $consecutivos->count()
        ]);
        
        return $resultado;
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
