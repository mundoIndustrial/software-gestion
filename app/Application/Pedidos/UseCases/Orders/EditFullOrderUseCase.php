<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Models\PedidoProduccion;
use App\Services\RegistroOrdenValidationService;
use App\Services\RegistroOrdenPrendaService;
use App\Services\RegistroOrdenCacheService;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * UseCase: Actualizar orden completa con sus prendas
 * 
 * Responsabilidades:
 * - Validar datos completos
 * - Reemplazar prendas
 * - Invalidar cache
 * - Registrar evento
 */
class EditFullOrderUseCase
{
    public function __construct(
        private RegistroOrdenValidationService $validationService,
        private RegistroOrdenPrendaService $prendaService,
        private RegistroOrdenCacheService $cacheService,
    ) {}

    /**
     * Ejecutar el caso de uso
     */
    public function execute(Request $request, int $pedido): array
    {
        // Validar datos
        $validatedData = $this->validationService->validateEditFullOrderRequest($request);

        // Obtener la orden
        $orden = PedidoProduccion::where('numero_pedido', $pedido)
            ->firstOrFail();

        DB::beginTransaction();

        try {
            // Actualizar campos básicos
            $orden->update([
                'estado' => $validatedData['estado'] ?? 'No iniciado',
                'cliente' => $validatedData['cliente'],
                'created_at' => $validatedData['fecha_creacion'],
                'forma_de_pago' => $validatedData['forma_pago'] ?? null,
            ]);

            // Reemplazar prendas
            $this->prendaService->replacePrendas($pedido, $validatedData['prendas']);

            // Invalidar cache
            $this->cacheService->invalidateDaysCache($pedido);

            // Registrar evento
            News::create([
                'event_type' => 'order_updated',
                'description' => "Orden editada: Pedido {$pedido} para cliente {$validatedData['cliente']}",
                'user_id' => auth()->id(),
                'pedido' => $pedido,
                'metadata' => ['cliente' => $validatedData['cliente'], 'total_prendas' => count($validatedData['prendas'])]
            ]);

            DB::commit();

            // Recargar relaciones
            $orden->load('prendas');

            // Broadcast evento
            broadcast(new \App\Events\OrdenUpdated($orden, 'updated'));

            return [
                'success' => true,
                'message' => 'Orden actualizada correctamente',
                'pedido' => $pedido,
                'orden' => $orden
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

