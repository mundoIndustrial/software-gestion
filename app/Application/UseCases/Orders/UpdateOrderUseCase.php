<?php

namespace App\Application\UseCases\Orders;

use App\Models\PedidoProduccion;
use App\Services\RegistroOrdenValidationService;
use App\Services\RegistroOrdenUpdateService;
use App\Events\OrdenUpdated;
use Illuminate\Http\Request;

/**
 * UseCase: Actualizar una orden existente
 * 
 * Responsabilidades:
 * - Validar datos actualizados
 * - Delegar actualización al servicio
 * - Disparar eventos broadcast
 */
class UpdateOrderUseCase
{
    public function __construct(
        private RegistroOrdenValidationService $validationService,
        private RegistroOrdenUpdateService $updateService,
    ) {}

    /**
     * Ejecutar el caso de uso
     */
    public function execute(Request $request, int $pedido): array
    {
        // Obtener la orden
        $orden = PedidoProduccion::where('numero_pedido', $pedido)
            ->firstOrFail();

        // Validar datos
        $validatedData = $this->validationService->validateUpdateRequest($request);

        // Ejecutar actualización
        $response = $this->updateService->updateOrder($orden, $validatedData);

        // Recargar y preparar broadcast
        $ordenActualizada = $orden->fresh();
        $changedFields = array_keys($validatedData);

        // Si se actualizó día de entrega, agregar fecha estimada
        if (in_array('dia_de_entrega', $changedFields) && 
            !in_array('fecha_estimada_de_entrega', $changedFields)) {
            $changedFields[] = 'fecha_estimada_de_entrega';
        }

        // Broadcast con manejo de errores
        try {
            broadcast(new OrdenUpdated($ordenActualizada, 'updated', $changedFields));
        } catch (\Exception $e) {
            \Log::warning("Fallo en broadcast para pedido {$pedido}", [
                'error' => $e->getMessage()
            ]);
        }

        return $response;
    }
}
