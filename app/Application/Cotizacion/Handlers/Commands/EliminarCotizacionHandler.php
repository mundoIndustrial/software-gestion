<?php

namespace App\Application\Cotizacion\Handlers\Commands;

use App\Application\Cotizacion\Commands\EliminarCotizacionCommand;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use App\Domain\Cotizacion\Specifications\EsPropietarioSpecification;
use App\Domain\Cotizacion\Specifications\PuedeSerEliminadaSpecification;
use App\Domain\Cotizacion\ValueObjects\CotizacionId;
use App\Domain\Shared\ValueObjects\UserId;
use Illuminate\Support\Facades\Log;

/**
 * EliminarCotizacionHandler - Handler para eliminar cotización
 *
 * Orquesta la eliminación de una cotización (solo borradores)
 */
final class EliminarCotizacionHandler
{
    public function __construct(
        private readonly CotizacionRepositoryInterface $repository
    ) {
    }

    /**
     * Ejecutar el comando
     */
    public function handle(EliminarCotizacionCommand $comando): void
    {
        Log::info('EliminarCotizacionHandler: Iniciando eliminación', [
            'cotizacion_id' => $comando->cotizacionId,
            'usuario_id' => $comando->usuarioId,
        ]);

        try {
            // Obtener cotización
            $cotizacionId = CotizacionId::crear($comando->cotizacionId);
            $cotizacion = $this->repository->findById($cotizacionId);

            if (!$cotizacion) {
                throw new \DomainException('Cotización no encontrada');
            }

            // Verificar propiedad
            $usuarioId = UserId::crear($comando->usuarioId);
            $esPropietario = new EsPropietarioSpecification($usuarioId);
            $esPropietario->throwIfNotSatisfied($cotizacion);

            // Verificar que pueda ser eliminada
            $puedeSerEliminada = new PuedeSerEliminadaSpecification();
            $puedeSerEliminada->throwIfNotSatisfied($cotizacion);

            // Eliminar
            $this->repository->delete($cotizacionId);

            Log::info('EliminarCotizacionHandler: Cotización eliminada exitosamente', [
                'cotizacion_id' => $comando->cotizacionId,
            ]);
        } catch (\Exception $e) {
            Log::error('EliminarCotizacionHandler: Error al eliminar', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
