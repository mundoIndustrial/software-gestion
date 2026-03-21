<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\EliminarOrdenOutput;
use App\Models\PedidoProduccion;
use App\Services\RegistroOrdenDeletionService;
use App\Services\RegistroOrdenCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * UseCase: EliminarOrdenUseCase
 * 
 * Responsabilidad: Orquestar la eliminación de una orden
 * Patrón: Application Service (UseCase)
 * 
 * Flujo:
 * 1. Obtener orden existente
 * 2. Eliminar orden
 * 3. Invalidar cache
 * 4. Broadcast evento
 * 5. Retornar resultado
 */
class EliminarOrdenUseCase
{
    public function __construct(
        private RegistroOrdenDeletionService $deletionService,
        private RegistroOrdenCacheService $cacheService,
    ) {}

    /**
     * Ejecutar caso de uso
     * 
     * @throws \Exception
     */
    public function execute(int $numeroPedido): EliminarOrdenOutput
    {
        try {
            DB::beginTransaction();

            Log::info('🗑️ EliminarOrdenUseCase iniciado', ['numero_pedido' => $numeroPedido]);

            // 1️⃣ Obtener la orden para verificar que existe
            $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)
                ->firstOrFail();

            // 2️⃣ Ejecutar eliminación
            $this->deletionService->deleteOrder($numeroPedido);

            // 3️⃣ Invalidar cache
            $this->cacheService->invalidateDaysCache($numeroPedido);

            // 4️⃣ Broadcast evento
            try {
                $this->deletionService->broadcastOrderDeleted($numeroPedido);
                Log::info('📡 Broadcast de eliminación enviado para pedido ' . $numeroPedido);
            } catch (\Exception $e) {
                Log::warning('⚠️ Error en broadcast de eliminación: ' . $e->getMessage());
                // No detener el flujo si falla broadcast
            }

            DB::commit();

            Log::info('✅ Orden eliminada exitosamente', ['numero_pedido' => $numeroPedido]);

            return new EliminarOrdenOutput(
                numero_pedido: $numeroPedido,
                mensaje: "Orden {$numeroPedido} eliminada exitosamente",
                eliminada: true,
                metadata: [
                    'deleted_at' => now(),
                ]
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('❌ Orden no encontrada para eliminar', ['numero_pedido' => $numeroPedido]);
            throw new \Exception("Orden {$numeroPedido} no encontrada", 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error en EliminarOrdenUseCase: ' . $e->getMessage(), [
                'numero_pedido' => $numeroPedido,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
