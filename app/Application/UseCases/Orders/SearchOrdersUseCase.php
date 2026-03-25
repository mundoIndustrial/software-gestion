<?php

namespace App\Application\UseCases\Orders;

use App\Infrastructure\QueryServices\OrderQueryService;
use Illuminate\Http\Request;

class SearchOrdersUseCase
{
    protected $orderQueryService;

    public function __construct(OrderQueryService $orderQueryService)
    {
        $this->orderQueryService = $orderQueryService;
    }

    /**
     * Busca órdenes por término de búsqueda
     *
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
    {
        $result = $this->orderQueryService->searchOrders(
            $request->input('search', ''),
            $request->input('page', 1),
            $request->input('limit', 25)
        );

        return array_merge(['success' => true], $result);
    }
}
