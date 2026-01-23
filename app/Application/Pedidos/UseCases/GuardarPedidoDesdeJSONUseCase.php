<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\Services\GuardarPedidoDesdeJSONService;

/**
 * Use Case: Guardar Pedido desde JSON
 * 
 * Responsabilidad:
 * - Recibir datos del pedido en JSON
 * - Validar estructura
 * - Delegar al servicio de dominio
 * - Retornar resultado
 */
class GuardarPedidoDesdeJSONUseCase
{
    public function __construct(
        private GuardarPedidoDesdeJSONService $guardarService
    ) {}

    /**
     * Ejecutar caso de uso
     */
    public function ejecutar(int $pedidoId, array $prendas): array
    {
        return $this->guardarService->guardar($pedidoId, $prendas);
    }
}

