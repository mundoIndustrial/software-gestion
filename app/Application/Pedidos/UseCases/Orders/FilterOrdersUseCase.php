<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Infrastructure\QueryServices\OrderQueryService;
use Illuminate\Http\Request;

class FilterOrdersUseCase
{
    protected $orderQueryService;

    public function __construct(OrderQueryService $orderQueryService)
    {
        $this->orderQueryService = $orderQueryService;
    }

    /**
     * Filtra las órdenes por criterios específicos
     *
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
    {
        $result = $this->orderQueryService->filterOrders(
            $request->input('filters', []),
            $request->input('page', 1),
            25
        );

        return array_merge(['success' => true], $result);
    }
}

