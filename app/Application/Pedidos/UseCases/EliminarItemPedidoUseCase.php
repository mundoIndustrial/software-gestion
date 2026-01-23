<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\PedidoProduccion\Services\GestionItemsPedidoService;

/**
 * Use Case: Eliminar Item de Pedido
 * 
 * Responsabilidad:
 * - Validar índice del item
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
        // Validar índice
        if ($index < 0 || $index >= $this->gestionItems->contar()) {
            throw new \InvalidArgumentException('Índice de ítem inválido');
        }

        // Eliminar item
        $this->gestionItems->eliminarItem($index);

        // Retornar estado actualizado
        return [
            'success' => true,
            'message' => 'Ítem eliminado correctamente',
            'items' => $this->gestionItems->obtenerItemsArray(),
            'count' => $this->gestionItems->contar(),
        ];
    }
}
