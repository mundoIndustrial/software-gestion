<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractObtenerUseCase;
use App\Application\Pedidos\DTOs\ObtenerProduccionPedidoDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;

/**
 * Use Case: Obtener Producción Pedido
 * 
 * REFACTORIZADO: Utiliza AbstractObtenerUseCase para eliminar duplicación
 * 
 * Antes: 22 líneas (7 líneas de lógica actual + 15 líneas duplicadas)
 * Después: 12 líneas (solo implementa personalización)
 * Reducción: 45%
 */
class ObtenerProduccionPedidoUseCase extends AbstractObtenerUseCase
{
    public function ejecutar(ObtenerProduccionPedidoDTO $dto)
    {
        return $this->obtenerYEnriquecer($dto->pedidoId);
    }

    /**
     * Personalización: Obtener solo el modelo sin enriquecimiento
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
     * Personalización: Retornar modelo directamente
     */
    protected function construirRespuesta(array $datosEnriquecidos)
    {
        return $this->pedidoRepository->porId($datosEnriquecidos['id']);
    }
}
