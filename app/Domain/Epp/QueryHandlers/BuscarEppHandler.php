<?php

namespace App\Domain\Epp\QueryHandlers;

use App\Domain\Shared\CQRS\Query;
use App\Domain\Shared\CQRS\QueryHandler;
use App\Domain\Epp\Queries\BuscarEppQuery;
use App\Domain\Epp\Services\EppDomainService;
use Illuminate\Support\Facades\Log;

/**
 * BuscarEppHandler
 * 
 * Maneja BuscarEppQuery
 * Busca EPP por término (código o nombre)
 */
class BuscarEppHandler implements QueryHandler
{
    public function __construct(
        private EppDomainService $eppService,
    ) {}

    public function handle(Query $query): mixed
    {
        if (!$query instanceof BuscarEppQuery) {
            throw new \InvalidArgumentException('Query debe ser BuscarEppQuery');
        }

        try {
            Log::info('🔍 [BuscarEppHandler] Buscando EPP', [
                'termino' => $query->getTermino(),
            ]);

            $epps = $this->eppService->buscarEppConImagenes($query->getTermino());

            Log::info('✅ [BuscarEppHandler] EPP encontrados', [
                'cantidad' => count($epps),
            ]);

            return $epps;

        } catch (\Exception $e) {
            Log::error('❌ [BuscarEppHandler] Error buscando EPP', [
                'error' => $e->getMessage(),
                'termino' => $query->getTermino(),
            ]);

            throw $e;
        }
    }
}
