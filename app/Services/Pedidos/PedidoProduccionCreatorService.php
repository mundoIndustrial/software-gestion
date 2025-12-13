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
        // Validar DTO
        if (!$dto->esValido()) {
            throw new \InvalidArgumentException('Datos inválidos para crear pedido');
        }

        // Obtener prendas válidas
        $prendas = $dto->prendasValidas();
        if (empty($prendas)) {
            throw new \InvalidArgumentException('No hay prendas con cantidades válidas');
        }

        // Ejecutar el Job de forma sincrónica para garantizar número secuencial
        // y retornar el pedido creado inmediatamente
        return Bus::dispatchSync(new CrearPedidoProduccionJob($dto, $asesorId, $prendas));
    }

}
