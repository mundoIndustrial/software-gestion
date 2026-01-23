<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractObtenerUseCase;
use App\Application\Pedidos\DTOs\ObtenerProduccionPedidoDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;

/**
 * Use Case: Obtener ProducciÃ³n Pedido
 * 
 * REFACTORIZADO: Utiliza AbstractObtenerUseCase para eliminar duplicaciÃ³n
 * 
 * Antes: 22 lÃ­neas (7 lÃ­neas de lÃ³gica actual + 15 lÃ­neas duplicadas)
 * DespuÃ©s: 12 lÃ­neas (solo implementa personalizaciÃ³n)
 * ReducciÃ³n: 45%
 */
class ObtenerProduccionPedidoUseCase extends AbstractObtenerUseCase
{
    public function ejecutar(ObtenerProduccionPedidoDTO $dto)
    {
        return $this->obtenerYEnriquecer($dto->pedidoId);
    }

    /**
     * PersonalizaciÃ³n: Obtener solo el modelo sin enriquecimiento
     */
    protected function obtenerOpciones(): array
    {
        return [
            'incluirPrendas' => false,
            'incluirEpps' => false,
            'incluirProcesos' => false,
            'incluirImagenes' => false,
        ];
    }

    /**
     * PersonalizaciÃ³n: Retornar modelo directamente
     */
    protected function construirRespuesta(array $datosEnriquecidos, $pedidoId): mixed
    {
        return $datosEnriquecidos;
    }
}

