<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\Services\GestionItemsPedidoService;

/**
 * Use Case: Eliminar Item de Pedido
 * 
 * Responsabilidad:
 * - Validar indice del item
 * - Eliminar item de la sesión
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
        // Validar indice
        if ($index < 0 || $index >= $this->gestionItems->contar()) {
            throw new \InvalidArgumentException('indice de item invalido');
        }

        // Eliminar item
        $this->gestionItems->eliminarItem($index);

        // Retornar estado actualizado
        return [
            'success' => true,
            'message' => 'item eliminado correctamente',
            'items' => $this->gestionItems->obtenerItemsArray(),
            'count' => $this->gestionItems->contar(),
        ];
    }
}
