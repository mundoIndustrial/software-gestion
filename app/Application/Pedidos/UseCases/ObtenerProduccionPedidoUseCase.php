<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractObtenerUseCase;
use App\Application\Pedidos\DTOs\ObtenerProduccionPedidoDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Obtener Producción Pedido
 * 
 * REFACTORIZADO: Utiliza AbstractObtenerUseCase para eliminar duplicación
 * 
 * Antes: 22 lÃ­neas (7 lÃ­neas de lógica actual + 15 lÃ­neas duplicadas)
 * DespuÃ©s: 12 lÃ­neas (solo implementa personalización)
 * Reducción: 45%
 */
class ObtenerProduccionPedidoUseCase extends AbstractObtenerUseCase
{
    public function ejecutar(ObtenerProduccionPedidoDTO $dto)
    {
        Log::info('[ObtenerProduccionPedidoUseCase] Iniciando obtención de pedido', ['pedidoId' => $dto->pedidoId]);
        
        $resultado = $this->obtenerYEnriquecer($dto->pedidoId);
        
        Log::info('[ObtenerProduccionPedidoUseCase] Pedido obtenido', [
            'id' => $resultado->id ?? 'N/A',
            'tiene_procesos' => isset($resultado->procesos) ? count($resultado->procesos) : 0
        ]);
        
        return $resultado;
    }

    /**
     * Personalización: Obtener solo el modelo sin enriquecimiento
     */
    protected function obtenerOpciones(): array
    {
        Log::debug('[ObtenerProduccionPedidoUseCase] Opciones: incluirProcesos = true');
        
        return [
            'incluirPrendas' => false,
            'incluirEpps' => false,
            'incluirProcesos' => true,
            'incluirImagenes' => false,
        ];
    }

    /**
     * Personalización: Retornar modelo directamente
     */
    protected function construirRespuesta(array $datosEnriquecidos, $pedidoId): mixed
    {
        return $datosEnriquecidos;
    }
}

