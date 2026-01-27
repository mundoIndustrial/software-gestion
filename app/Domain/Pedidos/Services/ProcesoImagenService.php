<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcesoImagenService
 * 
 * Responsabilidad: Guardar imÃ¡genes de procesos en la BD
 * Ahora recibe File objects o arrays con información de archivo, NO base64
 */
class ProcesoImagenService
{
    private ImagenTransformadorService $transformador;

    public function __construct(ImagenTransformadorService $transformador = null)
    {
        $this->transformador = $transformador ?? app(ImagenTransformadorService::class);
    }

    /**
     * Guardar imÃ¡genes de procesos como WebP
     */
    public function guardarImagenesProcesos(int $procesoDetalleId, int $pedidoId, array $imagenes): void
    {
        Log::info(' [ProcesoImagenService::guardarImagenesProcesos] Guardando imÃ¡genes de procesos', [
            'proceso_detalle_id' => $procesoDetalleId,
            'pedido_id' => $pedidoId,
            'cantidad_imagenes' => count($imagenes),
        ]);

        // Obtener tipo de proceso
        $procesoDetalle = DB::table('pedidos_procesos_prenda_detalles')
            ->where('id', $procesoDetalleId)
            ->first();

        if (!$procesoDetalle) {
            Log::error(' Proceso detalle no encontrado', [
                'proceso_detalle_id' => $procesoDetalleId,
            ]);
            return;
        }

        $tipoProcesoNombre = DB::table('tipos_procesos')
            ->where('id', $procesoDetalle->tipo_proceso_id)
            ->value('slug') ?? 'proceso';

        foreach ($imagenes as $index => $imagenData) {
            try {
                $archivo = null;
                $esPrincipal = $index === 0;

                // CASO 1: UploadedFile directo
                if ($imagenData instanceof UploadedFile) {
                    $archivo = $imagenData;
                }
                // CASO 2: Array con UploadedFile
                elseif (is_array($imagenData) && isset($imagenData['archivo']) && $imagenData['archivo'] instanceof UploadedFile) {
                    $archivo = $imagenData['archivo'];
                    $esPrincipal = $imagenData['principal'] ?? ($index === 0);
                }
                // CASO 3: String (ruta existente)
                elseif (is_string($imagenData)) {
                    $rutaAbsoluta = $imagenData && !str_starts_with($imagenData, '/') ? '/' . $imagenData : $imagenData;
                    
                    DB::table('pedidos_procesos_imagenes')->insert([
                        'proceso_prenda_detalle_id' => $procesoDetalleId,
                        'ruta_original' => basename($imagenData),
                        'ruta_webp' => $rutaAbsoluta,
                        'orden' => $index,
                        'es_principal' => $index === 0 ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info(' Imagen de proceso guardada (String)', [
                        'proceso_detalle_id' => $procesoDetalleId,
                        'index' => $index,
                        'ruta_absoluta' => $rutaAbsoluta,
                    ]);
                    continue;
                }
                // CASO 4: Array con ruta string
                elseif (is_array($imagenData) && isset($imagenData['ruta'])) {
                    $rutaAbsoluta = $imagenData['ruta'] && !str_starts_with($imagenData['ruta'], '/') ? '/' . $imagenData['ruta'] : $imagenData['ruta'];
                    $esPrincipal = $imagenData['principal'] ?? ($index === 0);
                    
                    DB::table('pedidos_procesos_imagenes')->insert([
                        'proceso_prenda_detalle_id' => $procesoDetalleId,
                        'ruta_original' => basename($imagenData['ruta']),
                        'ruta_webp' => $rutaAbsoluta,
                        'orden' => $index,
                        'es_principal' => $esPrincipal ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info(' Imagen de proceso guardada (Array con ruta)', [
                        'proceso_detalle_id' => $procesoDetalleId,
                        'index' => $index,
                        'ruta_absoluta' => $rutaAbsoluta,
                        'es_principal' => $esPrincipal,
                    ]);
                    continue;
                }
                // CASO 5: Array del frontend sin ruta (uid, nombre_archivo, formdata_key) → IGNORAR
                // Estas son referencias a archivos pendientes de carga que no tienen ruta real
                elseif (is_array($imagenData) && isset($imagenData['uid'])) {
                    Log::info(' Imagen sin ruta real ignorada (pendiente de procesar)', [
                        'proceso_detalle_id' => $procesoDetalleId,
                        'index' => $index,
                        'uid' => $imagenData['uid'] ?? null,
                        'nombre' => $imagenData['nombre_archivo'] ?? null,
                    ]);
                    continue;
                }

                if ($archivo) {
                    $directorio = storage_path("app/public/pedidos/{$pedidoId}/procesos/{$tipoProcesoNombre}");
                    $resultado = $this->transformador->transformarAWebp($archivo, $directorio, $index, 'proceso');
                    $rutaAbsoluta = '/storage/pedidos/' . $pedidoId . '/procesos/' . $tipoProcesoNombre . '/' . $resultado['nombreArchivo'];
                    
                    DB::table('pedidos_procesos_imagenes')->insert([
                        'proceso_prenda_detalle_id' => $procesoDetalleId,
                        'ruta_original' => $archivo->getClientOriginalName(),
                        'ruta_webp' => $rutaAbsoluta,
                        'orden' => $index,
                        'es_principal' => $esPrincipal ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info(' Imagen de proceso guardada', [
                        'proceso_detalle_id' => $procesoDetalleId,
                        'index' => $index,
                        'ruta_absoluta' => $rutaAbsoluta,
                        'es_principal' => $esPrincipal,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error(' Error guardando imagen de proceso', [
                    'proceso_detalle_id' => $procesoDetalleId,
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

