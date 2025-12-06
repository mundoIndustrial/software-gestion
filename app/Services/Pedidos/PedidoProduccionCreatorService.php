<?php

namespace App\Services\Pedidos;

use App\Models\PedidoProduccion;
use App\DTOs\CrearPedidoProduccionDTO;
use Illuminate\Database\Eloquent\Model;

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

        // Procesar prendas
        $prendasProcesadas = array_map(
            fn(PrendaCreacionDTO $prenda) => $this->prendaProcessor->procesar($prenda),
            $prendas
        );

        // Crear pedido
        return PedidoProduccion::create([
            'cotizacion_id' => $dto->cotizacionId,
            'asesor_id' => $asesorId,
            'prendas' => $prendasProcesadas,
            'estado' => 'pendiente',
        ]);
    }

    /**
     * Obtiene el próximo número de pedido
     * SRP: responsabilidad única = generar número
     */
    public function obtenerProximoNumero(): int
    {
        $ultimoPedido = PedidoProduccion::max('numero_pedido') ?? 0;
        return $ultimoPedido + 1;
    }
}
