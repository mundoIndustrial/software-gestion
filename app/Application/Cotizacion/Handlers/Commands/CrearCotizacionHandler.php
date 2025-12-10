<?php

namespace App\Application\Cotizacion\Handlers\Commands;

use App\Application\Cotizacion\Commands\CrearCotizacionCommand;
use App\Application\Cotizacion\DTOs\CotizacionDTO;
use App\Domain\Cotizacion\Entities\Cotizacion;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use App\Domain\Cotizacion\ValueObjects\TipoCotizacion;
use App\Domain\Shared\ValueObjects\UserId;
use Illuminate\Support\Facades\Log;

/**
 * CrearCotizacionHandler - Handler para crear cotización
 *
 * Orquesta la creación de una nueva cotización
 */
final class CrearCotizacionHandler
{
    public function __construct(
        private readonly CotizacionRepositoryInterface $repository
    ) {
    }

    /**
     * Ejecutar el comando
     */
    public function handle(CrearCotizacionCommand $comando): CotizacionDTO
    {
        $datos = $comando->datos;

        Log::info('CrearCotizacionHandler: Iniciando creación', [
            'usuario_id' => $datos->usuarioId,
            'tipo' => $datos->tipo,
            'cliente_id' => $datos->clienteId,
            'tipo_venta' => $datos->tipoVenta,
            'es_borrador' => $datos->esBorrador,
        ]);

        try {
            // Crear Value Objects
            $usuarioId = UserId::crear($datos->usuarioId);
            $tipo = TipoCotizacion::tryFrom($datos->tipo) ?? TipoCotizacion::PRENDA;

            // Crear Aggregate Root
            if ($datos->esBorrador) {
                $cotizacion = Cotizacion::crearBorrador(
                    $usuarioId,
                    $tipo,
                    $datos->clienteId,
                    $datos->tipoVenta,
                    $datos->especificaciones ?? []
                );
            } else {
                // Para enviadas, necesitamos un secuencial
                $secuencial = $this->repository->countByUserId($usuarioId) + 1;
                $cotizacion = Cotizacion::crearEnviada(
                    $usuarioId,
                    $tipo,
                    $secuencial,
                    $datos->clienteId,
                    $datos->tipoVenta,
                    $datos->especificaciones ?? []
                );
            }

            // Guardar
            $this->repository->save($cotizacion);

            Log::info('CrearCotizacionHandler: Cotización creada exitosamente', [
                'cotizacion_id' => $cotizacion->id()->valor(),
                'numero' => $cotizacion->numero()->valor(),
            ]);

            // Retornar DTO
            return CotizacionDTO::desdeArray($cotizacion->toArray());
        } catch (\Exception $e) {
            Log::error('CrearCotizacionHandler: Error al crear cotización', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
