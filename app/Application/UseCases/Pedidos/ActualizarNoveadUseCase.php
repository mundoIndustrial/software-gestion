<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\ActualizarNoveadInput;
use App\Application\UseCases\Pedidos\DTOs\ActualizarNoveadOutput;
use App\Events\OrdenUpdated;
use App\Models\PedidoProduccion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * UseCase: Actualizar Novedades de una Orden
 * 
 * Responsabilidad: Orquestar actualización de novedades (reemplazo total)
 * Patrón: UseCase (Application Service)
 */
class ActualizarNoveadUseCase
{
    public function __construct()
    {}

    /**
     * Ejecutar: Actualizar novedades de forma atómicamente
     * 
     * @throws ModelNotFoundException Si la orden no existe
     */
    public function execute(ActualizarNoveadInput $input): ActualizarNoveadOutput
    {
        try {
            \Log::info('ActualizarNoveadUseCase iniciado', [
                'numero_pedido' => $input->numero_pedido,
            ]);

            // Iniciar transacción
            DB::beginTransaction();

            try {
                // Obtener orden existente
                $orden = PedidoProduccion::where('numero_pedido', $input->numero_pedido)
                    ->firstOrFail();

                // Guardar valor anterior para auditoria
                $noveadadesAnterior = $orden->novedades;

                // Actualizar novedades (reemplazo total)
                $orden->update([
                    'novedades' => $input->novedades ?? '',
                ]);

                // Registrar en auditoria si existe
                if (class_exists('App\Models\AuditLog')) {
                    try {
                        \App\Models\AuditLog::create([
                            'user_id' => auth()?->id(),
                            'action' => 'update_novedades',
                            'auditable_type' => PedidoProduccion::class,
                            'auditable_id' => $orden->id,
                            'changes' => [
                                'novedades' => $input->novedades ?? '',
                            ]
                        ]);
                    } catch (\Exception $auditError) {
                        \Log::warning('Error creando AuditLog en ActualizarNoveadUseCase', [
                            'numero_pedido' => $input->numero_pedido,
                            'error' => $auditError->getMessage(),
                        ]);
                    }
                }

                DB::commit();

                // Recargar orden
                $orden->fresh();

                // Broadcast del evento (con fallback)
                try {
                    broadcast(new OrdenUpdated($orden, 'updated', ['novedades']));
                } catch (\Exception $broadcastError) {
                    \Log::warning('Error broadcasting OrdenUpdated en ActualizarNoveadUseCase', [
                        'numero_pedido' => $input->numero_pedido,
                        'error' => $broadcastError->getMessage(),
                    ]);
                }

                \Log::info('ActualizarNoveadUseCase completado', [
                    'numero_pedido' => $input->numero_pedido,
                ]);

                return new ActualizarNoveadOutput(
                    numero_pedido: $input->numero_pedido,
                    mensaje: 'Novedades actualizadas correctamente',
                    novedades_actuales: $orden->novedades,
                    metadata: [
                        'usuario' => auth()?->user()?->name ?? auth()?->user()?->email,
                        'timestamp' => now()->format('Y-m-d H:i:s'),
                    ],
                );
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ModelNotFoundException $e) {
            \Log::error('Orden no encontrada en ActualizarNoveadUseCase', [
                'numero_pedido' => $input->numero_pedido,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error en ActualizarNoveadUseCase', [
                'numero_pedido' => $input->numero_pedido,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
