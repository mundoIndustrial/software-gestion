<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\{ObtenerEntregasInput, ObtenerEntregasOutput};
use App\Models\PedidoProduccion;
use App\Services\RegistroOrdenEntregasService;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: ObtenerEntregasUseCase
 * 
 * Responsabilidad: Obtener entregas de una orden
 * Patrón: Use Case (Application Service)
 * 
 * Flujo:
 * 1. Validar que la orden existe
 * 2. Obtener entregas usando servicio
 * 3. Enriquecer con metadata
 * 4. Retornar resultado
 */
class ObtenerEntregasUseCase
{
    public function __construct(
        private RegistroOrdenEntregasService $entregasService,
    ) {}

    /**
     * Ejecutar caso de uso
     */
    public function execute(ObtenerEntregasInput $input): ObtenerEntregasOutput
    {
        Log::info('[ObtenerEntregasUseCase] Obteniendo entregas de orden', [
            'numero_pedido' => $input->numero_pedido,
        ]);

        try {
            // Buscar orden
            $orden = PedidoProduccion::where('numero_pedido', $input->numero_pedido)
                ->orWhere('id', $input->numero_pedido)
                ->first();

            if (!$orden) {
                Log::warning('[ObtenerEntregasUseCase] Orden no encontrada', [
                    'numero_pedido' => $input->numero_pedido,
                ]);

                return new ObtenerEntregasOutput(
                    numero_pedido: $input->numero_pedido,
                    entregas: [],
                    metadata: ['mensaje' => 'Orden no encontrada'],
                );
            }

            // Obtener entregas
            $entregas = $this->entregasService->getEntregas($orden->numero_pedido);

            Log::info('[ObtenerEntregasUseCase] Entregas obtenidas correctamente', [
                'numero_pedido' => $orden->numero_pedido,
                'cantidad_entregas' => is_array($entregas) ? count($entregas) : 0,
            ]);

            // Retornar resultado con metadata
            return new ObtenerEntregasOutput(
                numero_pedido: $orden->numero_pedido,
                entregas: is_array($entregas) ? $entregas : [],
                metadata: [
                    'obtenido_en' => now()->toDateTimeString(),
                    'cliente' => $orden->cliente ?? null,
                    'dias_entrega' => $orden->dia_de_entrega ?? null,
                    'fecha_estimada' => $orden->fecha_estimada_de_entrega ?? null,
                ],
            );
        } catch (\Exception $e) {
            Log::error('[ObtenerEntregasUseCase] Error al obtener entregas', [
                'numero_pedido' => $input->numero_pedido,
                'error' => $e->getMessage(),
            ]);

            throw new \InvalidArgumentException('Error al obtener entregas: ' . $e->getMessage());
        }
    }
}
