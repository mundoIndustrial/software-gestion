<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\DTOs\ItemPedidoDTO;
use App\Domain\PedidoProduccion\Services\GestionItemsPedidoService;

/**
 * Use Case: Agregar Item a Pedido
 * 
 * Responsabilidad:
 * - Validar datos del item
 * - Agregar item a la sesión de construcción
 * - Retornar estado actualizado
 */
class AgregarItemPedidoUseCase
{
    public function __construct(
        private GestionItemsPedidoService $gestionItems
    ) {}

    /**
     * Ejecutar caso de uso
     */
    public function ejecutar(array $itemData): array
    {
        // Crear DTO desde datos
        $itemDTO = ItemPedidoDTO::fromArray($itemData);
        
        // Agregar item
        $this->gestionItems->agregarItem($itemDTO);

        // Retornar estado actualizado
        return [
            'success' => true,
            'message' => 'Ítem agregado correctamente',
            'items' => $this->gestionItems->obtenerItemsArray(),
            'count' => $this->gestionItems->contar(),
        ];
    }
}
