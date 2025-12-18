<?php

namespace App\Application\Cotizacion\Handlers\Commands;

use App\Application\Cotizacion\Commands\AceptarCotizacionCommand;
use App\Application\Cotizacion\DTOs\CotizacionDTO;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use App\Domain\Cotizacion\Specifications\EsPropietarioSpecification;
use App\Domain\Cotizacion\ValueObjects\CotizacionId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Events\CotizacionAprobada;
use Illuminate\Support\Facades\Log;

/**
 * AceptarCotizacionHandler - Handler para aceptar cotización
 *
 * Orquesta la aceptación de una cotización (dispara Domain Event)
 */
final class AceptarCotizacionHandler
{
    public function __construct(
        private readonly CotizacionRepositoryInterface $repository
    ) {
    }

    /**
     * Ejecutar el comando
     */
    public function handle(AceptarCotizacionCommand $comando): CotizacionDTO
    {
        Log::info('AceptarCotizacionHandler: Iniciando aceptación', [
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

            // Aceptar cotización (dispara evento de dominio)
            $cotizacion->aceptar();

            // Guardar
            $this->repository->save($cotizacion);

            Log::info('AceptarCotizacionHandler: Cotización aceptada exitosamente', [
                'cotizacion_id' => $comando->cotizacionId,
                'eventos' => count($cotizacion->eventos()),
            ]);

            // Obtener datos completos de la cotización para el broadcast
            $cotizacionArray = $cotizacion->toArray();
            
            // Disparar evento de broadcast en tiempo real
            broadcast(new CotizacionAprobada(
                $comando->cotizacionId,
                $cotizacion->asesorId()->valor(),
                $comando->usuarioId,
                $cotizacion->estado()->value,
                $cotizacionArray
            ))->toOthers();

            // Retornar DTO
            return CotizacionDTO::desdeArray($cotizacionArray);
        } catch (\Exception $e) {
            Log::error('AceptarCotizacionHandler: Error al aceptar', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
