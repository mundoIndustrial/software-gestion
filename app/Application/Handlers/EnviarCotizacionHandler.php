<?php

namespace App\Application\Handlers;

use App\Application\Commands\EnviarCotizacionCommand;
use App\Domain\Cotizacion\Services\GeneradorNumeroCotizacionService;
use App\Models\Cotizacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * EnviarCotizacionHandler
 *
 * Application Handler que procesa el comando EnviarCotizacionCommand.
 * Coordina la lógica de aplicación para enviar una cotización.
 *
 * Responsabilidad: Orquestar el envío de una cotización usando servicios de dominio
 */
class EnviarCotizacionHandler
{
    /**
     * Constructor
     */
    public function __construct(
        private GeneradorNumeroCotizacionService $generadorNumero
    ) {}

    /**
     * Maneja el comando de envío de cotización
     *
     * Usa transacción para garantizar atomicidad:
     * Si algo falla, TODO se revierte (ROLLBACK)
     * Nada se guarda en la BD si hay error
     *
     * @param EnviarCotizacionCommand $command
     * @return Cotizacion Cotización actualizada
     * @throws \Exception Si hay error al enviar
     */
    public function handle(EnviarCotizacionCommand $command): Cotizacion
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($command) {
            Log::info(' EnviarCotizacionHandler - Iniciando envío de cotización', [
                'cotizacion_id' => $command->cotizacionId,
                'tipo_cotizacion_id' => $command->tipoCotizacionId
            ]);

            // Obtener la cotización
            $cotizacion = Cotizacion::findOrFail($command->cotizacionId);

            // Validar que sea un borrador
            if (!$cotizacion->es_borrador) {
                Log::warning(' Intento de enviar cotización ya enviada', [
                    'cotizacion_id' => $command->cotizacionId,
                    'numero_cotizacion' => $cotizacion->numero_cotizacion
                ]);
                throw new \Exception('Esta cotización ya ha sido enviada');
            }

            // Generar número de cotización de forma segura (con lock)
            $numeroCotizacion = $this->generadorNumero->generarProximoGlobal();

            // Actualizar cotización
            $cotizacion->update([
                'numero_cotizacion' => $numeroCotizacion,
                'es_borrador' => false,
                'estado' => 'ENVIADA',
                'fecha_envio' => Carbon::now('America/Bogota')
            ]);

            Log::info(' Cotización enviada exitosamente', [
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $numeroCotizacion,
                'tipo_cotizacion_id' => $command->tipoCotizacionId
            ]);

            return $cotizacion;
        }, attempts: 3); // Reintentar hasta 3 veces si hay deadlock
    }
}
