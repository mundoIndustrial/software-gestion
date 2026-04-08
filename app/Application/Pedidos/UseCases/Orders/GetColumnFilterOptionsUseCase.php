<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Infrastructure\QueryServices\OrderQueryService;
use Illuminate\Http\Request;

class GetColumnFilterOptionsUseCase
{
    protected $orderQueryService;

    public function __construct(OrderQueryService $orderQueryService)
    {
        $this->orderQueryService = $orderQueryService;
    }

    /**
     * Obtiene opciones de filtro para una columna específica con paginación y búsqueda
     *
     * @param string $column
     * @param Request $request
     * @return array
     */
    public function execute(string $column, Request $request): array
    {
        $result = $this->orderQueryService->getColumnFilterOptions(
            $column,
            $request->input('search', ''),
            $request->input('page', 1),
            $request->input('limit', 25)
        );

        return array_merge(['success' => true, 'column' => $column], $result);
    }
}

