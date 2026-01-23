<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\PedidoProduccion\Services\GestionItemsPedidoService;

/**
 * Use Case: Obtener Items de Pedido
 * 
 * Responsabilidad:
 * - Recuperar items de la sesión de construcción
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
