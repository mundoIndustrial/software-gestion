<?php

namespace App\Application\Cotizacion\Handlers\Commands;

use App\Application\Cotizacion\Commands\CrearCotizacionCommand;
use App\Application\Cotizacion\DTOs\CotizacionDTO;
use App\Application\Services\CotizacionPrendaService;
use App\Domain\Cotizacion\Entities\Cotizacion;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use App\Domain\Cotizacion\ValueObjects\TipoCotizacion;
use App\Domain\Shared\ValueObjects\UserId;
use App\Models\Cotizacion as CotizacionModel;
use Illuminate\Support\Facades\Log;

/**
 * CrearCotizacionHandler - Handler para crear cotización
 *
 * Orquesta la creación de una nueva cotización y guarda prendas en tablas normalizadas
 */
final class CrearCotizacionHandler
{
    public function __construct(
        private readonly CotizacionRepositoryInterface $repository,
        private readonly CotizacionPrendaService $prendaService
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

            // Guardar cotización
            $this->repository->save($cotizacion);

            // Obtener la cotización guardada desde BD para tener el ID correcto
            $usuarioIdValue = $usuarioId->valor();
            $cotizacionModel = CotizacionModel::where('asesor_id', $usuarioIdValue)
                ->orderBy('id', 'desc')
                ->first();

            if (!$cotizacionModel) {
                throw new \Exception('Error al recuperar la cotización guardada');
            }

            Log::info('CrearCotizacionHandler: Cotización creada exitosamente', [
                'cotizacion_id' => $cotizacionModel->id,
                'numero' => $cotizacionModel->numero_cotizacion,
            ]);

            // Guardar prendas en tablas normalizadas
            $prendas = $datos->prendas ?? [];

            if (!empty($prendas)) {
                Log::info('CrearCotizacionHandler: Guardando prendas en tablas normalizadas', [
                    'cotizacion_id' => $cotizacionModel->id,
                    'prendas_count' => count($prendas),
                ]);

                $this->prendaService->guardarProductosEnCotizacion($cotizacionModel, $prendas);
            }

            // Retornar DTO con datos de la cotización guardada
            $cotizacionArray = $cotizacionModel->toArray();
            $cotizacionArray['cliente_id'] = $datos->clienteId;
            $cotizacionArray['tipo_venta'] = $datos->tipoVenta;
            $cotizacionArray['especificaciones'] = $datos->especificaciones;

            return CotizacionDTO::desdeArray($cotizacionArray);
        } catch (\Exception $e) {
            Log::error('CrearCotizacionHandler: Error al crear cotización', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
