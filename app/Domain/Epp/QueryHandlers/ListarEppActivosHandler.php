<?php

namespace App\Domain\Epp\QueryHandlers;

use App\Domain\Shared\CQRS\Query;
use App\Domain\Shared\CQRS\QueryHandler;
use App\Domain\Epp\Queries\ListarEppActivosQuery;
use App\Domain\Epp\Services\EppDomainService;
use Illuminate\Support\Facades\Log;

/**
 * ListarEppActivosHandler
 * 
 * Maneja ListarEppActivosQuery
 * Lista todos los EPP activos disponibles
 */
class ListarEppActivosHandler implements QueryHandler
{
    public function __construct(
        private EppDomainService $eppService,
    ) {}

    public function handle(Query $query): mixed
    {
        if (!$query instanceof ListarEppActivosQuery) {
            throw new \InvalidArgumentException('Query debe ser ListarEppActivosQuery');
        }

        try {
            Log::info('📋 [ListarEppActivosHandler] Listando EPP activos');

            $epps = $this->eppService->obtenerEppActivos();

            Log::info('✅ [ListarEppActivosHandler] EPP listados', [
                'cantidad' => count($epps),
            ]);

            return $epps;

        } catch (\Exception $e) {
            Log::error('❌ [ListarEppActivosHandler] Error listando EPP', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
