<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\DTOs\ItemPedidoDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Services\GestionItemsPedidoService;

/**
 * Use Case: Agregar Item a Pedido
 * 
 * Responsabilidad:
 * - Validar datos del item
 * - Agregar item a la sesiÃ³n de construcciÃ³n
 * - Retornar estado actualizado
 */
class AgregarItemPedidoUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private GestionItemsPedidoService $gestionItems
    ) {}

    /**
     * Ejecutar caso de uso
     */
    public function ejecutar(array $itemData): array
    {
        $this->validarNoVacio($itemData, 'Datos del item');
        
        $itemDTO = ItemPedidoDTO::fromArray($itemData);
        $this->gestionItems->agregarItem($itemDTO);

        return [
            'success' => true,
            'message' => 'Ãtem agregado correctamente',
            'items' => $this->gestionItems->obtenerItemsArray(),
            'count' => $this->gestionItems->contar(),
        ];
    }
}

