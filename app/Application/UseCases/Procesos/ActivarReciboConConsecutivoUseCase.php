<?php

namespace App\Application\UseCases\Procesos;

use App\Domain\Procesos\Services\ActivarReciboProcesoService;
use App\Events\OperarioRecibosActualizados;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Application UseCase: ActivarReciboConConsecutivoUseCase
 *
 * Orquesta toda la lógica de activación de recibos:
 * 1. Cambia el estado del proceso (PENDIENTE → APROBADO o viceversa)
 * 2. Genera consecutivos para tipos especiales (BORDADO, ESTAMPADO, DTF, SUBLIMADO, REFLECTIVO)
 * 3. Emite broadcasts si aplica (especialmente para REFLECTIVO)
 * 4. Registra auditoría
 *
 * Entrada: procesoId, activar (boolean), usuarioId
 * Salida: Proceso actualizado + consecutivo generado (si aplica)
 */
class ActivarReciboConConsecutivoUseCase
{
    public function __construct(
        private ActivarReciboProcesoService $activarReciboService
    ) {}

    /**
     * Ejecuta la activación/desactivación de un recibo con toda su orquestación
     *
     * @param int  $procesoId
     * @param bool $activar true=aprobar, false=revertir a pendiente
     * @param int  $usuarioId ID del usuario que realiza la acción
     *
     * @return array ['success' => bool, 'message' => string, 'consecutivo' => ?int]
     *
     * @throws \DomainException
     * @throws \Exception
     */
    public function ejecutar(int $procesoId, bool $activar, int $usuarioId): array
    {
        try {
            // 1. Obtener datos del proceso
            $proceso = \DB::table('pedidos_procesos_prenda_detalles')
                ->where('id', $procesoId)
                ->first();

            if (!$proceso) {
                throw new \DomainException('Proceso no encontrado');
            }

            $consecutivoGenerado = null;
            $nombreTipo = null;

            if ($activar) {
                // 2. Cambiar estado a APROBADO
                \DB::table('pedidos_procesos_prenda_detalles')
                    ->where('id', $procesoId)
                    ->update([
                        'estado' => 'APROBADO',
                        'fecha_aprobacion' => now(),
                        'aprobado_por' => $usuarioId
                    ]);

                // Guardar fecha_activacion en pedidos_parciales si existe
                $prenda = \DB::table('prendas_pedido')
                    ->where('id', $proceso->prenda_pedido_id)
                    ->first();
                
                if ($prenda) {
                    \DB::table('pedidos_parciales')
                        ->where('pedido_produccion_id', $prenda->pedido_produccion_id)
                        ->where('prenda_pedido_id', $proceso->prenda_pedido_id)
                        ->whereNull('fecha_activacion')
                        ->update([
                            'fecha_activacion' => now()
                        ]);
                }

                // 3. Obtener tipo de proceso
                $tipoProceso = \DB::table('tipos_procesos')
                    ->where('id', $proceso->tipo_proceso_id)
                    ->first();

                $nombreTipo = strtoupper(trim($tipoProceso->nombre ?? ''));

                // 4. Generar consecutivo si aplica
                $tiposConActivacion = ['BORDADO', 'ESTAMPADO', 'DTF', 'SUBLIMADO', 'REFLECTIVO'];

                if (in_array($nombreTipo, $tiposConActivacion)) {
                    $consecutivoGenerado = $this->generarConsecutivo(
                        $proceso->prenda_pedido_id,
                        $nombreTipo,
                        $usuarioId
                    );
                }

                // 5. Hacer broadcast si es REFLECTIVO
                if ($nombreTipo === 'REFLECTIVO') {
                    $this->hacerBroadcastReflectivo($procesoId, $proceso->prenda_pedido_id);
                }

                Log::info('[ActivarReciboConConsecutivoUseCase] Recibo activado', [
                    'proceso_id' => $procesoId,
                    'tipo' => $nombreTipo,
                    'consecutivo' => $consecutivoGenerado,
                    'usuario_id' => $usuarioId
                ]);

            } else {
                // Revertir a PENDIENTE
                \DB::table('pedidos_procesos_prenda_detalles')
                    ->where('id', $procesoId)
                    ->update([
                        'estado' => 'PENDIENTE',
                        'fecha_aprobacion' => null,
                        'aprobado_por' => null
                    ]);

                Log::info('[ActivarReciboConConsecutivoUseCase] Recibo desactivado', [
                    'proceso_id' => $procesoId,
                    'usuario_id' => $usuarioId
                ]);
            }

            return [
                'success' => true,
                'message' => $activar 
                    ? 'Recibo activado correctamente' 
                    : 'Recibo desactivado correctamente',
                'consecutivo' => $consecutivoGenerado
            ];

        } catch (\DomainException $e) {
            Log::warning('[ActivarReciboConConsecutivoUseCase] Error de dominio', [
                'proceso_id' => $procesoId,
                'message' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('[ActivarReciboConConsecutivoUseCase] Error crítico', [
                'proceso_id' => $procesoId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Genera un consecutivo para un tipo de recibo específico
     * Solo genera si no existe uno previo para esa prenda+tipo
     */
    private function generarConsecutivo(int $prendaPedidoId, string $nombreTipo, int $usuarioId): ?int
    {
        try {
            $prenda = \DB::table('prendas_pedido')
                ->where('id', $prendaPedidoId)
                ->first();

            if (!$prenda) {
                Log::warning('[generarConsecutivo] Prenda no encontrada', ['prenda_id' => $prendaPedidoId]);
                return null;
            }

            $pedidoProduccionId = $prenda->pedido_produccion_id;

            // Verificar que no exista ya un consecutivo para esta prenda+tipo
            $existe = \DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedidoProduccionId)
                ->where('tipo_recibo', $nombreTipo)
                ->where('prenda_id', $prendaPedidoId)
                ->exists();

            if ($existe) {
                Log::info('[generarConsecutivo] Consecutivo ya existe, omitiendo', [
                    'tipo' => $nombreTipo,
                    'pedido_id' => $pedidoProduccionId,
                    'prenda_id' => $prendaPedidoId
                ]);
                return null;
            }

            // Usar transacción para generar consecutivo de forma segura
            return \DB::transaction(function () use ($pedidoProduccionId, $prendaPedidoId, $nombreTipo, $usuarioId) {
                $registroMaestro = \DB::table('consecutivos_recibos')
                    ->where('tipo_recibo', $nombreTipo)
                    ->where('activo', 1)
                    ->lockForUpdate()
                    ->first();

                if (!$registroMaestro) {
                    Log::warning('[generarConsecutivo] No existe registro maestro', ['tipo' => $nombreTipo]);
                    return null;
                }

                $nuevoConsecutivo = $registroMaestro->consecutivo_actual + 1;

                // Actualizar tabla maestra
                \DB::table('consecutivos_recibos')
                    ->where('id', $registroMaestro->id)
                    ->update([
                        'consecutivo_actual' => $nuevoConsecutivo,
                        'updated_at' => now()
                    ]);

                // Insertar en consecutivos_recibos_pedidos
                \DB::table('consecutivos_recibos_pedidos')->insert([
                    'pedido_produccion_id' => $pedidoProduccionId,
                    'prenda_id' => $prendaPedidoId,
                    'tipo_recibo' => $nombreTipo,
                    'consecutivo_inicial' => $nuevoConsecutivo,
                    'consecutivo_actual' => $nuevoConsecutivo,
                    'activo' => 1,
                    'notas' => "Generado al activar recibo por usuario {$usuarioId}",
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info('[generarConsecutivo] Consecutivo generado exitosamente', [
                    'tipo' => $nombreTipo,
                    'consecutivo' => $nuevoConsecutivo,
                    'pedido_id' => $pedidoProduccionId,
                    'prenda_id' => $prendaPedidoId
                ]);

                return $nuevoConsecutivo;
            });

        } catch (\Exception $e) {
            Log::error('[generarConsecutivo] Error al generar consecutivo', [
                'tipo' => $nombreTipo,
                'prenda_id' => $prendaPedidoId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Hace broadcast a los operarios con roles costura-reflectivo y lider-reflectivo
     */
    private function hacerBroadcastReflectivo(int $procesoId, int $prendaPedidoId): void
    {
        try {
            $rolReflectivoIds = \DB::table('roles')
                ->whereIn('name', ['costura-reflectivo', 'lider-reflectivo'])
                ->pluck('id')
                ->toArray();

            if (count($rolReflectivoIds) === 0) {
                Log::warning('[hacerBroadcastReflectivo] Roles no encontrados');
                return;
            }

            // Buscar usuarios que tengan cualquiera de los IDs del rol en roles_ids (JSON)
            $operarios = User::where(function ($q) use ($rolReflectivoIds) {
                foreach ($rolReflectivoIds as $rolId) {
                    $q->orWhereRaw("JSON_CONTAINS(roles_ids, '\"" . $rolId . "\"') OR JSON_CONTAINS(roles_ids, '" . $rolId . "')");
                }
            })->get(['id']);

            Log::info('[hacerBroadcastReflectivo] Usuarios encontrados', [
                'total' => $operarios->count(),
                'ids' => $operarios->pluck('id')->toArray()
            ]);

            foreach ($operarios as $operario) {
                broadcast(new OperarioRecibosActualizados(
                    userId: (int) $operario->id,
                    payload: [
                        'evento' => 'recibo_activado_reflectivo',
                        'tipo_recibo' => 'REFLECTIVO',
                        'proceso_id' => (int) $procesoId,
                        'prenda_id' => (int) $prendaPedidoId,
                    ]
                ));
            }

        } catch (\Throwable $e) {
            Log::warning('[hacerBroadcastReflectivo] Error en broadcast', [
                'proceso_id' => $procesoId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
