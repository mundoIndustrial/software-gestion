<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\Services\GestionItemsPedidoService;

/**
 * Use Case: Eliminar Item de Pedido
 * 
 * Responsabilidad:
 * - Validar Ã­ndice del item
 * - Eliminar item de la sesiÃ³n
 * - Retornar estado actualizado
 */
class EliminarItemPedidoUseCase
{
    public function __construct(
        private GestionItemsPedidoService $gestionItems
    ) {}

    /**
     * Ejecutar caso de uso
     */
    public function ejecutar(int $index): array
    {
        // Validar Ã­ndice
        if ($index < 0 || $index >= $this->gestionItems->contar()) {
            throw new \InvalidArgumentException('Ãndice de Ã­tem invÃ¡lido');
        }

        // Eliminar item
        $this->gestionItems->eliminarItem($index);

        // Retornar estado actualizado
        return [
            'success' => true,
            'message' => 'Ãtem eliminado correctamente',
            'items' => $this->gestionItems->obtenerItemsArray(),
            'count' => $this->gestionItems->contar(),
        ];
    }
}

