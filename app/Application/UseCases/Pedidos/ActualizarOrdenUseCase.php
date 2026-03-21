<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\ActualizarOrdenInput;
use App\Application\UseCases\Pedidos\DTOs\ActualizarOrdenOutput;
use App\Models\PedidoProduccion;
use App\Services\RegistroOrdenValidationService;
use App\Services\RegistroOrdenUpdateService;
use App\Services\RegistroOrdenCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * UseCase: ActualizarOrdenUseCase
 * 
 * Responsabilidad: Orquestar la actualización de una orden existente
 * Patrón: Application Service (UseCase)
 * 
 * Flujo:
 * 1. Obtener orden existente
 * 2. Validar entrada
 * 3. Aplicar cambios
 * 4. Registrar evento
 * 5. Broadcast en tiempo real
 * 6. Retornar resultado con campos modificados
 */
class ActualizarOrdenUseCase
{
    public function __construct(
        private RegistroOrdenValidationService $validationService,
        private RegistroOrdenUpdateService $updateService,
        private RegistroOrdenCacheService $cacheService,
    ) {}

    /**
     * Ejecutar caso de uso
     * 
     * @throws \Exception
     */
    public function execute(ActualizarOrdenInput $input): ActualizarOrdenOutput
    {
        try {
            DB::beginTransaction();

            Log::info('🔄 ActualizarOrdenUseCase iniciado', [
                'numero_pedido' => $input->numero_pedido,
                'campos' => count($input->getChangedFields()),
            ]);

            // 1️⃣ Obtener la orden
            $orden = PedidoProduccion::where('numero_pedido', $input->numero_pedido)
                ->firstOrFail();

            // 2️⃣ Validar datos
            $validatedData = $this->validationService->validateUpdateRequest(
                $this->crearFakeRequest($input)
            );

            // 3️⃣ Ejecutar actualización
            $response = $this->updateService->updateOrder($orden, $validatedData);

            // 4️⃣ Recargar orden actualizada
            $ordenActualizada = $orden->fresh();

            // 5️⃣ Obtener campos que fueron realmente actualizados
            $camposModificados = array_keys($validatedData);

            // 6️⃣ Si se actualiza dia_de_entrega, agregar fecha_estimada_de_entrega
            if (in_array('dia_de_entrega', $camposModificados) && 
                !in_array('fecha_estimada_de_entrega', $camposModificados)) {
                $camposModificados[] = 'fecha_estimada_de_entrega';
            }

            // 7️⃣ Invalidar cache
            $this->cacheService->invalidateDaysCache($input->numero_pedido);

            // 8️⃣ Broadcast evento
            try {
                broadcast(new \App\Events\OrdenUpdated($ordenActualizada, 'updated', $camposModificados));
                Log::info('📡 Broadcast enviado para pedido ' . $ordenActualizada->numero_pedido, [
                    'campos' => $camposModificados
                ]);
            } catch (\Exception $e) {
                Log::warning('⚠️ Error en broadcast: ' . $e->getMessage());
                // No detener el flujo si falla broadcast
            }

            DB::commit();

            Log::info('✅ Orden actualizada exitosamente', [
                'numero_pedido' => $input->numero_pedido,
                'campos_modificados' => count($camposModificados),
            ]);

            return new ActualizarOrdenOutput(
                numero_pedido: $input->numero_pedido,
                mensaje: "Orden actualizada exitosamente",
                campos_modificados: $camposModificados,
                orden_actualizada: $response ?? $ordenActualizada->toArray(),
                metadata: [
                    'updated_at' => $ordenActualizada->updated_at,
                ]
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('❌ Orden no encontrada', ['numero_pedido' => $input->numero_pedido]);
            throw new \Exception("Orden {$input->numero_pedido} no encontrada", 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error en ActualizarOrdenUseCase: ' . $e->getMessage(), [
                'numero_pedido' => $input->numero_pedido,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Helper: Crear Request fake para validación existente
     */
    private function crearFakeRequest(ActualizarOrdenInput $input): \Illuminate\Http\Request
    {
        $request = new \Illuminate\Http\Request();
        // Solo hacer merge de los campos que fueron modificados
        $request->merge($input->getChangedFields());
        return $request;
    }
}
