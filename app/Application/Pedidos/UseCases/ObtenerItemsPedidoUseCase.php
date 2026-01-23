<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\Services\GestionItemsPedidoService;

/**
 * Use Case: Obtener Items de Pedido
 * 
 * Responsabilidad:
 * - Recuperar items de la sesiÃ³n de construcciÃ³n
 * - Retornar en formato API
 */
class ObtenerItemsPedidoUseCase
{
    public function __construct(
        private GestionItemsPedidoService $gestionItems
    ) {}

    /**
     * Ejecutar caso de uso
     */
    public function ejecutar(): array
    {
        return [
            'items' => $this->gestionItems->obtenerItemsArray(),
            'count' => $this->gestionItems->contar(),
            'tieneItems' => $this->gestionItems->tieneItems(),
        ];
    }
}

