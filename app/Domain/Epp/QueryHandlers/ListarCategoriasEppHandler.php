<?php

namespace App\Domain\Epp\QueryHandlers;

use App\Domain\Shared\CQRS\Query;
use App\Domain\Shared\CQRS\QueryHandler;
use App\Domain\Epp\Queries\ListarCategoriasEppQuery;
use App\Domain\Epp\Services\EppDomainService;
use Illuminate\Support\Facades\Log;

/**
 * ListarCategoriasEppHandler
 * 
 * Maneja ListarCategoriasEppQuery
 * Obtiene todas las categorías disponibles
 */
class ListarCategoriasEppHandler implements QueryHandler
{
    public function __construct(
        private EppDomainService $eppService,
    ) {}

    public function handle(Query $query): mixed
    {
        if (!$query instanceof ListarCategoriasEppQuery) {
            throw new \InvalidArgumentException('Query debe ser ListarCategoriasEppQuery');
        }

        try {
            Log::info(' [ListarCategoriasEppHandler] Listando categorías');

            $categorias = $this->eppService->obtenerCategorias();

            Log::info(' [ListarCategoriasEppHandler] Categorías listadas', [
                'cantidad' => count($categorias),
            ]);

            return $categorias;

        } catch (\Exception $e) {
            Log::error(' [ListarCategoriasEppHandler] Error listando categorías', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
