<?php

namespace App\Application\Cotizacion\Handlers;

use App\Application\Cotizacion\Commands\CrearReflectivoCotizacionCommand;
use App\Domain\Cotizacion\Entities\ReflectivoCotizacion;
use App\Domain\Cotizacion\ValueObjects\RutaImagen;
use App\Models\Cotizacion;
use Illuminate\Support\Facades\Log;

/**
 * CrearReflectivoCotizacionHandler - Handler para procesar comando de crear reflectivo
 *
 * Responsabilidades:
 * - Validar datos del comando
 * - Crear entity ReflectivoCotizacion
 * - Persistir en base de datos
 * - Registrar eventos de dominio
 */
final class CrearReflectivoCotizacionHandler
{
    /**
     * Ejecutar el comando
     */
    public function handle(CrearReflectivoCotizacionCommand $command): array
    {
        try {
            Log::info('🔵 CrearReflectivoCotizacionHandler@handle - Iniciando creación de reflectivo');

            $dto = $command->dto;

            // Validar que la cotización exista
            $cotizacion = Cotizacion::findOrFail($dto->cotizacionId);
            Log::info(' Cotización encontrada', ['cotizacion_id' => $dto->cotizacionId]);

            // Crear entity del dominio
            $reflectivo = ReflectivoCotizacion::crear(
                $dto->descripcion,
                $dto->ubicacion
            );

            // Agregar imágenes
            foreach ($dto->imagenes as $imagen) {
                if (!empty($imagen)) {
                    $reflectivo->agregarImagen(new RutaImagen($imagen));
                }
            }
            Log::info('📸 Imágenes agregadas', ['cantidad' => count($dto->imagenes)]);

            // Agregar observaciones generales
            foreach ($dto->observacionesGenerales as $observacion) {
                if (!empty($observacion)) {
                    $reflectivo->agregarObservacion($observacion);
                }
            }
            Log::info(' Observaciones agregadas', ['cantidad' => count($dto->observacionesGenerales)]);

            // Persistir en base de datos
            $reflectivoGuardado = $cotizacion->reflectivo()->create([
                'descripcion' => $reflectivo->descripcion(),
                'ubicacion' => $reflectivo->ubicacion(),
                'imagenes' => json_encode($reflectivo->imagenes()),
                'observaciones_generales' => json_encode($reflectivo->observacionesGenerales()),
            ]);

            Log::info(' Reflectivo guardado en BD', [
                'reflectivo_id' => $reflectivoGuardado->id,
                'cotizacion_id' => $dto->cotizacionId,
            ]);

            return [
                'success' => true,
                'message' => 'Reflectivo creado exitosamente',
                'reflectivo_id' => $reflectivoGuardado->id,
                'data' => $reflectivoGuardado->toArray(),
            ];

        } catch (\Exception $e) {
            Log::error(' Error al crear reflectivo', [
                'error' => $e->getMessage(),
                'cotizacion_id' => $dto->cotizacionId ?? null,
            ]);

            return [
                'success' => false,
                'message' => 'Error al crear reflectivo: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }
}
