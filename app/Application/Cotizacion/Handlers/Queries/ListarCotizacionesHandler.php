<?php

namespace App\Application\Cotizacion\Handlers\Queries;

use App\Application\Cotizacion\DTOs\CotizacionDTO;
use App\Application\Cotizacion\Queries\ListarCotizacionesQuery;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use App\Domain\Shared\ValueObjects\UserId;
use Illuminate\Support\Facades\Log;

/**
 * ListarCotizacionesHandler - Handler para listar cotizaciones
 *
 * Orquesta la obtención de listado de cotizaciones
 */
final class ListarCotizacionesHandler
{
    public function __construct(
        private readonly CotizacionRepositoryInterface $repository
    ) {
    }

    /**
     * Ejecutar la query
     */
    public function handle(ListarCotizacionesQuery $query): array
    {
        Log::info('ListarCotizacionesHandler: Listando cotizaciones', [
            'usuario_id' => $query->usuarioId,
            'solo_enviadas' => $query->soloEnviadas,
            'solo_borradores' => $query->soloBorradores,
            'pagina' => $query->pagina,
            'por_pagina' => $query->porPagina,
        ]);

        try {
            $usuarioId = UserId::crear($query->usuarioId);

            // Obtener cotizaciones según filtros
            if ($query->soloBorradores) {
                $cotizaciones = $this->repository->findBorradoresByUserId($usuarioId);
            } elseif ($query->soloEnviadas) {
                $cotizaciones = $this->repository->findEnviadasByUserId($usuarioId);
            } else {
                $cotizaciones = $this->repository->findByUserId($usuarioId);
            }

            // Convertir a DTOs
            $dtos = array_map(
                fn($cot) => CotizacionDTO::desdeArray(is_array($cot) ? $cot : $cot->toArray()),
                $cotizaciones
            );

            Log::info('ListarCotizacionesHandler: Cotizaciones listadas exitosamente', [
                'total' => count($dtos),
                'usuario_id' => $query->usuarioId,
            ]);

            return $dtos;
        } catch (\Exception $e) {
            Log::error('ListarCotizacionesHandler: Error al listar cotizaciones', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
