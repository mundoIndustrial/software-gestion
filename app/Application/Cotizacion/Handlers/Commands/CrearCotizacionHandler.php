<?php

namespace App\Application\Cotizacion\Handlers\Commands;

use App\Application\Cotizacion\Commands\CrearCotizacionCommand;
use App\Application\Cotizacion\DTOs\CotizacionDTO;
use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
use App\Application\Services\CotizacionPrendaService;
use App\Domain\Cotizacion\Entities\Cotizacion;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use App\Domain\Cotizacion\ValueObjects\TipoCotizacion;
use App\Domain\Shared\ValueObjects\UserId;
use App\Events\CotizacionCreada;
use App\Models\Cotizacion as CotizacionModel;
use Illuminate\Support\Facades\Log;

/**
 * CrearCotizacionHandler - Handler para crear cotización
 *
 * Orquesta la creación de una nueva cotización y guarda prendas en tablas normalizadas
 * Maneja la generación de números únicos y consecutivos con protección ante concurrencia
 */
final class CrearCotizacionHandler
{
    public function __construct(
        private readonly CotizacionRepositoryInterface $repository,
        private readonly CotizacionPrendaService $prendaService,
        private readonly GenerarNumeroCotizacionService $generarNumeroCotizacionService
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
            // El tipo debe venir correctamente mapeado desde el controller ('P', 'L', 'PL', 'PB', 'RF')
            $tipo = TipoCotizacion::tryFrom($datos->tipo);
            if (!$tipo) {
                Log::error('CrearCotizacionHandler: Tipo de cotización inválido', ['tipo' => $datos->tipo]);
                // Fallback a COMBINADO si hay un tipo inválido
                $tipo = TipoCotizacion::COMBINADO;
            }

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
                // Para enviadas, generar número único con lock
                // Esto evita race conditions cuando dos asesores crean simultáneamente
                if (!$datos->numeroCotizacion) {
                    // generarProxNumeroCotizacion devuelve int (1, 2, 3...)
                    // que luego se formatea en la entidad
                    $numeroCotizacion = $this->generarNumeroCotizacionService->generarProxNumeroCotizacion($usuarioId);
                } else {
                    // Si viene un número del DTO, extraer el número entero si está formateado
                    if (is_string($datos->numeroCotizacion) && preg_match('/\d+/', $datos->numeroCotizacion, $matches)) {
                        $numeroCotizacion = (int)$matches[0];
                    } else {
                        $numeroCotizacion = (int)$datos->numeroCotizacion;
                    }
                }

                $cotizacion = Cotizacion::crearEnviada(
                    $usuarioId,
                    $tipo,
                    $numeroCotizacion,
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

            // Disparar evento de broadcast en tiempo real
            broadcast(new CotizacionCreada(
                $cotizacionModel->id,
                $datos->usuarioId,
                $cotizacionModel->estado,
                $cotizacionArray
            ))->toOthers();

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
