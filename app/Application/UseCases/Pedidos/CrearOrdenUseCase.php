<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\CrearOrdenInput;
use App\Application\UseCases\Pedidos\DTOs\CrearOrdenOutput;
use App\Services\RegistroOrdenValidationService;
use App\Services\RegistroOrdenCreationService;
use App\Services\RegistroOrdenNumberService;
use App\Services\RegistroOrdenCacheService;
use App\Domain\Pedidos\Services\ValidadorNumeroPedidoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * UseCase: CrearOrdenUseCase
 * 
 * Responsabilidad: Orquestar la creación de una nueva orden de producción
 * Patrón: Application Service (UseCase)
 * 
 * Flujo:
 * 1. Validar entrada
 * 2. Validar número de pedido (consecutivo)
 * 3. Crear orden con todas sus prendas
 * 4. Registrar evento
 * 5. Broadcast en tiempo real
 * 6. Retornar resultado
 */
class CrearOrdenUseCase
{
    public function __construct(
        private RegistroOrdenValidationService $validationService,
        private RegistroOrdenCreationService $creationService,
        private RegistroOrdenNumberService $numberService,
        private RegistroOrdenCacheService $cacheService,
        private ValidadorNumeroPedidoService $validadorNumero,
    ) {}

    /**
     * Ejecutar caso de uso
     * 
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function execute(CrearOrdenInput $input): CrearOrdenOutput
    {
        try {
            DB::beginTransaction();

            Log::info('🚀 CrearOrdenUseCase iniciado', ['cliente' => $input->cliente]);

            // 1️⃣ Validar datos de entrada
            $validatedData = $this->validationService->validateStoreRequest(
                $this->crearFakeRequest($input)
            );

            // 2️⃣ Validar número consecutivo
            $nextPedido = $this->numberService->getNextNumber();
            
            if (!$input->allow_any_pedido) {
                $validacion = $this->validadorNumero->validarConOpcion($nextPedido, false);
                if (!$validacion['valido']) {
                    throw new \InvalidArgumentException($validacion['mensaje'] ?? 'Número de pedido inválido');
                }
            }

            // 3️⃣ Crear orden con todas sus prendas
            $pedido = $this->creationService->createOrder($validatedData);

            if (!$pedido) {
                throw new \Exception('No se pudo crear la orden');
            }

            // 4️⃣ Registrar evento
            $this->creationService->logOrderCreated(
                $pedido->id,
                auth()->id() ?? 0,
                "Orden {$pedido->numero_pedido} creada para {$input->cliente}"
            );

            // 5️⃣ Broadcast evento
            $this->creationService->broadcastOrderCreated($pedido);

            // 6️⃣ Invalidar cache
            $this->cacheService->invalidateDaysCache($pedido->numero_pedido);

            DB::commit();

            Log::info('✅ Orden creada exitosamente', [
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $input->cliente,
            ]);

            return new CrearOrdenOutput(
                pedido_id: $pedido->id,
                numero_pedido: $pedido->numero_pedido,
                cliente: $pedido->cliente,
                estado: $pedido->estado ?? 'Pendiente',
                mensaje: "Orden {$pedido->numero_pedido} creada exitosamente",
                metadata: [
                    'created_at' => $pedido->created_at,
                    'asesora' => $pedido->asesora_id,
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error en CrearOrdenUseCase: ' . $e->getMessage(), [
                'cliente' => $input->cliente,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Helper: Crear Request fake para validación existente
     */
    private function crearFakeRequest(CrearOrdenInput $input): \Illuminate\Http\Request
    {
        $request = new \Illuminate\Http\Request();
        $request->merge($input->toArray());
        return $request;
    }
}
