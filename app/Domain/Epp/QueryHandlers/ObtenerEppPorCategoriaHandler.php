<?php

namespace App\Domain\Epp\QueryHandlers;

use App\Domain\Shared\CQRS\Query;
use App\Domain\Shared\CQRS\QueryHandler;
use App\Domain\Epp\Queries\ObtenerEppPorCategoriaQuery;
use App\Domain\Epp\Services\EppDomainService;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerEppPorCategoriaHandler
 * 
 * Maneja ObtenerEppPorCategoriaQuery
 * Obtiene EPP filtrados por una categoría
 */
class ObtenerEppPorCategoriaHandler implements QueryHandler
{
    public function __construct(
        private EppDomainService $eppService,
    ) {}

    public function handle(Query $query): mixed
    {
        if (!$query instanceof ObtenerEppPorCategoriaQuery) {
            throw new \InvalidArgumentException('Query debe ser ObtenerEppPorCategoriaQuery');
        }

        try {
            Log::info(' [ObtenerEppPorCategoriaHandler] Obteniendo EPP por categoría', [
                'categoria' => $query->getCategoria(),
            ]);

            $epps = $this->eppService->obtenerEppPorCategoria($query->getCategoria());

            Log::info(' [ObtenerEppPorCategoriaHandler] EPP obtenidos', [
                'categoria' => $query->getCategoria(),
                'cantidad' => count($epps),
            ]);

            return $epps;

        } catch (\DomainException $e) {
            Log::warning(' [ObtenerEppPorCategoriaHandler] Categoría inválida', [
                'error' => $e->getMessage(),
                'categoria' => $query->getCategoria(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error(' [ObtenerEppPorCategoriaHandler] Error obteniendo EPP', [
                'error' => $e->getMessage(),
                'categoria' => $query->getCategoria(),
            ]);

            throw $e;
        }
    }
}
