<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractObtenerUseCase;
use App\Application\Pedidos\DTOs\ObtenerPrendasPedidoDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Obtener Prendas del Pedido
 * 
 * REFACTORIZADO: Utiliza AbstractObtenerUseCase para eliminar duplicación
 * 
 * Antes: 33 líneas (10 líneas de lógica actual + 23 líneas duplicadas)
 * Después: 18 líneas (solo implementa personalización)
 * Reducción: 45%
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
     * Personalización: Incluir solo prendas
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
     * Personalización: Retornar solo array de prendas
     */
    protected function construirRespuesta(array $datosEnriquecidos)
    {
        Log::info('[ObtenerPrendasPedidoUseCase] Prendas obtenidas', [
            'pedido_id' => $datosEnriquecidos['id'],
            'total_prendas' => count($datosEnriquecidos['prendas'] ?? []),
        ]);

        return $datosEnriquecidos['prendas'] ?? [];
    }
}