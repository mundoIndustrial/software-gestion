<?php

namespace App\Application\Cotizacion\Handlers\Queries;

use App\Application\Cotizacion\DTOs\CotizacionDTO;
use App\Application\Cotizacion\Queries\ObtenerCotizacionQuery;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use App\Domain\Cotizacion\Specifications\EsPropietarioSpecification;
use App\Domain\Cotizacion\ValueObjects\CotizacionId;
use App\Domain\Shared\ValueObjects\UserId;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerCotizacionHandler - Handler para obtener cotización
 *
 * Orquesta la obtención de una cotización específica
 */
final class ObtenerCotizacionHandler
{
    public function __construct(
        private readonly CotizacionRepositoryInterface $repository
    ) {
    }

    /**
     * Ejecutar la query
     */
    public function handle(ObtenerCotizacionQuery $query): CotizacionDTO
    {
        Log::info('ObtenerCotizacionHandler: Obteniendo cotización', [
            'cotizacion_id' => $query->cotizacionId,
            'usuario_id' => $query->usuarioId,
        ]);

        try {
            // Obtener cotización
            $cotizacionId = CotizacionId::crear($query->cotizacionId);
            $cotizacion = $this->repository->findById($cotizacionId);

            if (!$cotizacion) {
                Log::warning('ObtenerCotizacionHandler: Cotización no encontrada', [
                    'cotizacion_id' => $query->cotizacionId,
                ]);

                throw new \DomainException('Cotización no encontrada');
            }

            // Verificar propiedad
            $usuarioId = UserId::crear($query->usuarioId);
            $esPropietario = new EsPropietarioSpecification($usuarioId);
            $esPropietario->throwIfNotSatisfied($cotizacion);

            Log::info('ObtenerCotizacionHandler: Cotización obtenida exitosamente', [
                'cotizacion_id' => $cotizacion->id()->valor(),
            ]);

            // Retornar DTO
            return CotizacionDTO::desdeArray($cotizacion->toArray());
        } catch (\Exception $e) {
            Log::error('ObtenerCotizacionHandler: Error al obtener cotización', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
