<?php

namespace App\Domain\Epp\QueryHandlers;

use App\Domain\Shared\CQRS\Query;
use App\Domain\Shared\CQRS\QueryHandler;
use App\Domain\Epp\Queries\ObtenerEppPorIdQuery;
use App\Domain\Epp\Services\EppDomainService;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerEppPorIdHandler
 * 
 * Maneja ObtenerEppPorIdQuery
 * Obtiene un EPP específico por su ID
 */
class ObtenerEppPorIdHandler implements QueryHandler
{
    public function __construct(
        private EppDomainService $eppService,
    ) {}

    public function handle(Query $query): mixed
    {
        if (!$query instanceof ObtenerEppPorIdQuery) {
            throw new \InvalidArgumentException('Query debe ser ObtenerEppPorIdQuery');
        }

        try {
            Log::info(' [ObtenerEppPorIdHandler] Obteniendo EPP', [
                'id' => $query->getId(),
            ]);

            $epp = $this->eppService->obtenerEppPorId($query->getId());

            if (!$epp) {
                Log::warning(' [ObtenerEppPorIdHandler] EPP no encontrado', [
                    'id' => $query->getId(),
                ]);
                return null;
            }

            Log::info(' [ObtenerEppPorIdHandler] EPP obtenido', [
                'id' => $query->getId(),
                'nombre' => $epp['nombre'],
            ]);

            return $epp;

        } catch (\Exception $e) {
            Log::error(' [ObtenerEppPorIdHandler] Error obteniendo EPP', [
                'error' => $e->getMessage(),
                'id' => $query->getId(),
            ]);

            throw $e;
        }
    }
}
