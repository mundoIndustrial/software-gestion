<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractObtenerUseCase;
use App\Application\Pedidos\DTOs\ObtenerPrendasPedidoDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Obtener Prendas del Pedido
 * 
 * REFACTORIZADO: Utiliza AbstractObtenerUseCase para eliminar duplicaciÃ³n
 * 
 * Antes: 33 lÃ­neas (10 lÃ­neas de lÃ³gica actual + 23 lÃ­neas duplicadas)
 * DespuÃ©s: 18 lÃ­neas (solo implementa personalizaciÃ³n)
 * ReducciÃ³n: 45%
 */
final class ObtenerPrendasPedidoUseCase extends AbstractObtenerUseCase
{
    public function ejecutar(ObtenerPrendasPedidoDTO $dto)
    {
        Log::info('[ObtenerPrendasPedidoUseCase] Obteniendo prendas del pedido', [
            'pedido_id' => $dto->pedidoId,
        ]);

        return $this->obtenerYEnriquecer($dto->pedidoId);
    }

    /**
     * PersonalizaciÃ³n: Incluir solo prendas
     */
    protected function obtenerOpciones(): array
    {
        return [
            'incluirPrendas' => true,
            'incluirEpps' => false,
            'incluirProcesos' => false,
            'incluirImagenes' => false,
        ];
    }

    /**
     * PersonalizaciÃ³n: Retornar solo array de prendas
     */
    protected function construirRespuesta(array $datosEnriquecidos, $pedidoId): mixed
    {
        Log::info('[ObtenerPrendasPedidoUseCase] Prendas obtenidas', [
            'pedido_id' => $datosEnriquecidos['id'],
            'total_prendas' => count($datosEnriquecidos['prendas'] ?? []),
        ]);

        return $datosEnriquecidos['prendas'] ?? [];
    }
}
