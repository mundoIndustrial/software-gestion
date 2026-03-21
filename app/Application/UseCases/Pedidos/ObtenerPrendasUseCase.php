<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\ObtenerPrendasInput;
use App\Application\UseCases\Pedidos\DTOs\ObtenerPrendasOutput;
use App\Models\PedidoProduccion;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * UseCase: Obtener Prendas de una Orden
 * 
 * Responsabilidad: Orquestar obtención y preparación de prendas para una orden
 * Patrón: UseCase (Application Service)
 */
class ObtenerPrendasUseCase
{
    public function __construct(
        private \App\Services\RegistroOrdenPrendaService $prendaService,
    ) {}

    /**
     * Ejecutar: Obtener prendas y metadatos de una orden
     * 
     * @throws ModelNotFoundException Si la orden no existe
     */
    public function execute(ObtenerPrendasInput $input): ObtenerPrendasOutput
    {
        try {
            \Log::info('ObtenerPrendasUseCase iniciado', [
                'numero_pedido' => $input->numero_pedido,
            ]);

            // Validar que la orden existe
            $orden = PedidoProduccion::where('numero_pedido', $input->numero_pedido)
                ->firstOrFail();

            // Obtener prendas desde el servicio
            $prendas = $this->prendaService->getPrendasArray($input->numero_pedido);

            // Calcular metadatos
            $totalPrendas = count($prendas);
            $metadata = [
                'cliente' => $orden->cliente,
                'estado' => $orden->estado,
                'fecha_creacion' => $orden->fecha_creacion?->format('Y-m-d H:i:s'),
            ];

            \Log::info('ObtenerPrendasUseCase completado', [
                'numero_pedido' => $input->numero_pedido,
                'total_prendas' => $totalPrendas,
            ]);

            return new ObtenerPrendasOutput(
                numero_pedido: $input->numero_pedido,
                prendas: $prendas,
                total_prendas: $totalPrendas,
                metadata: $metadata,
            );
        } catch (ModelNotFoundException $e) {
            \Log::error('Orden no encontrada en ObtenerPrendasUseCase', [
                'numero_pedido' => $input->numero_pedido,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error en ObtenerPrendasUseCase', [
                'numero_pedido' => $input->numero_pedido,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
