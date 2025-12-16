<?php

namespace App\Services\Pedidos;

use App\Models\PedidoProduccion;
use App\DTOs\CrearPedidoProduccionDTO;
use App\DTOs\PrendaCreacionDTO;
use App\Jobs\CrearPedidoProduccionJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;

/**
 * Service para creación de pedidos de producción
 * LSP: Liskov Substitution - extensible para diferentes tipos de pedidos
 */
class PedidoProduccionCreatorService
{
    public function __construct(
        private PrendaProcessorService $prendaProcessor,
    ) {}

    /**
     * Crea un nuevo pedido de producción
     * Ejecuta sincronamente pero con protección de transacción y lock para números secuenciales
     */
    public function crear(CrearPedidoProduccionDTO $dto, int $asesorId): ?PedidoProduccion
    {
        \Log::info('🔍 [PedidoProduccionCreatorService] Iniciando creación de pedido', [
            'dto_forma_de_pago' => $dto->formaDePago,
            'dto_cliente' => $dto->cliente,
            'dto_cotizacion_id' => $dto->cotizacionId,
        ]);

        // Validar DTO
        if (!$dto->esValido()) {
            throw new \InvalidArgumentException('Datos inválidos para crear pedido');
        }

        // Obtener prendas válidas
        $prendas = $dto->prendasValidas();
        if (empty($prendas)) {
            throw new \InvalidArgumentException('No hay prendas con cantidades válidas');
        }

        \Log::info('🔍 [PedidoProduccionCreatorService] Despachando Job', [
            'forma_de_pago_antes_job' => $dto->formaDePago,
            'prendas_validas' => count($prendas),
        ]);

        // Ejecutar el Job de forma sincrónica para garantizar número secuencial
        // y retornar el pedido creado inmediatamente
        $pedido = Bus::dispatchSync(new CrearPedidoProduccionJob($dto, $asesorId, $prendas));

        \Log::info('✅ [PedidoProduccionCreatorService] Pedido creado desde servicio', [
            'pedido_id' => $pedido?->id,
            'forma_de_pago_guardada' => $pedido?->forma_de_pago,
        ]);

        return $pedido;
    }

}
