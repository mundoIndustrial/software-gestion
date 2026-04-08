<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Infrastructure\QueryServices\OrderQueryService;

class GetFilterOptionsUseCase
{
    protected $orderQueryService;

    public function __construct(OrderQueryService $orderQueryService)
    {
        $this->orderQueryService = $orderQueryService;
    }

    /**
     * Obtiene todas las opciones de filtro disponibles para las órdenes
     *
     * @return array
     */
    public function execute(): array
    {
        return [
            'success' => true,
            'options' => $this->orderQueryService->getFilterOptions()
        ];
    }
}

