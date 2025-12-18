<?php

namespace App\Application\Cotizacion\Handlers\Commands;

use App\Application\Cotizacion\Commands\CambiarEstadoCotizacionCommand;
use App\Application\Cotizacion\DTOs\CotizacionDTO;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use App\Domain\Cotizacion\Specifications\EsPropietarioSpecification;
use App\Domain\Cotizacion\ValueObjects\CotizacionId;
use App\Domain\Cotizacion\ValueObjects\EstadoCotizacion;
use App\Domain\Shared\ValueObjects\UserId;
use App\Events\CotizacionEstadoCambiado;
use Illuminate\Support\Facades\Log;

/**
 * CambiarEstadoCotizacionHandler - Handler para cambiar estado
 *
 * Orquesta el cambio de estado de una cotización
 */
final class CambiarEstadoCotizacionHandler
{
    public function __construct(
        private readonly CotizacionRepositoryInterface $repository
    ) {
    }

    /**
     * Ejecutar el comando
     */
    public function handle(CambiarEstadoCotizacionCommand $comando): CotizacionDTO
    {
        Log::info('CambiarEstadoCotizacionHandler: Iniciando cambio de estado', [
            'cotizacion_id' => $comando->cotizacionId,
            'nuevo_estado' => $comando->nuevoEstado,
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

            // Obtener nuevo estado
            $nuevoEstado = EstadoCotizacion::tryFrom($comando->nuevoEstado);
            if (!$nuevoEstado) {
                throw new \DomainException("Estado inválido: {$comando->nuevoEstado}");
            }

            // Guardar estado anterior
            $estadoAnterior = $cotizacion->estado()->value;

            // Cambiar estado
            $cotizacion->cambiarEstado($nuevoEstado);

            // Guardar
            $this->repository->save($cotizacion);

            Log::info('CambiarEstadoCotizacionHandler: Estado cambiado exitosamente', [
                'cotizacion_id' => $comando->cotizacionId,
                'nuevo_estado' => $nuevoEstado->value,
            ]);

            // Obtener datos completos de la cotización para el broadcast
            $cotizacionArray = $cotizacion->toArray();
            
            // Disparar evento de broadcast en tiempo real
            broadcast(new CotizacionEstadoCambiado(
                $comando->cotizacionId,
                $nuevoEstado->value,
                $estadoAnterior,
                $comando->usuarioId,
                $cotizacionArray
            ))->toOthers();

            // Retornar DTO
            return CotizacionDTO::desdeArray($cotizacionArray);
        } catch (\Exception $e) {
            Log::error('CambiarEstadoCotizacionHandler: Error al cambiar estado', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
