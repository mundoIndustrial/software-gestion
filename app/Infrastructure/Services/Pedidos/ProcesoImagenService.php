<?php

namespace App\Infrastructure\Services\Pedidos;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcesoImagenService
 * 
 * Responsabilidad: Guardar imagenes de procesos en la BD
 * Ahora recibe File objects o arrays con informacion de archivo, NO base64
 */
class ProcesoImagenService
{
    private ImagenTransformadorService $transformador;

    public function __construct(ImagenTransformadorService $transformador = null)
    {
        $this->transformador = $transformador ?? app(ImagenTransformadorService::class);
    }

    /**
     * Guardar imagenes de procesos como WebP
     */
    public function guardarImagenesProcesos(int $procesoDetalleId, int $pedidoId, array $imagenes): void
    {
        Log::info(' [ProcesoImagenService::guardarImagenesProcesos] Guardando imagenes de procesos', [
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
                // CASO 5: Array del frontend sin ruta (uid, nombre_archivo, formdata_key)  IGNORAR
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

    /**
     * Guardar una imagen individual de proceso, opcionalmente vinculada a una talla
     */
    public function guardarImagenProceso(int $procesoDetalleId, int $pedidoId, mixed $imagenData, int $index = 0, ?int $tallaId = null): void
    {
        $procesoDetalle = DB::table('pedidos_procesos_prenda_detalles')
            ->where('id', $procesoDetalleId)
            ->first();

        if (!$procesoDetalle) return;

        $tipoProcesoNombre = DB::table('tipos_procesos')
            ->where('id', $procesoDetalle->tipo_proceso_id)
            ->value('slug') ?? 'proceso';

        $archivo = null;
        $esPrincipal = $index === 0;

        if ($imagenData instanceof UploadedFile) {
            $archivo = $imagenData;
        } elseif (is_array($imagenData) && isset($imagenData['archivo']) && $imagenData['archivo'] instanceof UploadedFile) {
            $archivo = $imagenData['archivo'];
        } elseif (is_string($imagenData)) {
            $rutaAbsoluta = !str_starts_with($imagenData, '/') ? '/' . $imagenData : $imagenData;
            DB::table('pedidos_procesos_imagenes')->insert([
                'proceso_prenda_detalle_id' => $procesoDetalleId,
                'proceso_prenda_talla_id' => $tallaId,
                'ruta_original' => basename($imagenData),
                'ruta_webp' => $rutaAbsoluta,
                'orden' => $index,
                'es_principal' => $esPrincipal ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return;
        }

        if ($archivo) {
            $directorio = storage_path("app/public/pedidos/{$pedidoId}/procesos/{$tipoProcesoNombre}");
            $resultado = $this->transformador->transformarAWebp($archivo, $directorio, $index, 'proceso');
            $rutaAbsoluta = '/storage/pedidos/' . $pedidoId . '/procesos/' . $tipoProcesoNombre . '/' . $resultado['nombreArchivo'];

            DB::table('pedidos_procesos_imagenes')->insert([
                'proceso_prenda_detalle_id' => $procesoDetalleId,
                'proceso_prenda_talla_id' => $tallaId,
                'ruta_original' => $archivo->getClientOriginalName(),
                'ruta_webp' => $rutaAbsoluta,
                'orden' => $index,
                'es_principal' => $esPrincipal ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Procesar imágenes de procesos por talla (modo específico).
     *
     * Lee archivos desde FormData en:
     * prendas.{prendaIdx}.procesos.{procesoKey}.datosExtendidos.{genero}.{talla}.imagenes.{imgIdx}
     * y los guarda en pedidos_procesos_imagenes con proceso_prenda_talla_id.
     */
    public function procesarImagenesPorTalla(Request $request, int $pedidoId, int $prendaIdx, int $procesoNumerico, $procesoKey, array $datosExtendidos): int
    {
        $procesoDetalleId = $this->resolverProcesoDetalleId($pedidoId, $prendaIdx, $procesoNumerico, $procesoKey);
        if (!$procesoDetalleId) {
            Log::warning('[ProcesoImagenService] No se pudo resolver proceso_detalle_id para modo por talla', [
                'pedido_id' => $pedidoId,
                'prenda_idx' => $prendaIdx,
                'proceso_numerico' => $procesoNumerico,
                'proceso_key' => $procesoKey,
            ]);
            return 0;
        }

        $procesadas = $this->procesarImagenesExtendidasPorTalla(
            $request,
            $pedidoId,
            $prendaIdx,
            (string) $procesoKey,
            $procesoDetalleId,
            $datosExtendidos
        );

        Log::info('[ProcesoImagenService] Imágenes por talla procesadas', [
            'pedido_id' => $pedidoId,
            'prenda_idx' => $prendaIdx,
            'proceso_key' => $procesoKey,
            'proceso_detalle_id' => $procesoDetalleId,
            'total_procesadas' => $procesadas,
        ]);

        return $procesadas;
    }

    private function procesarImagenesExtendidasPorTalla(
        Request $request,
        int $pedidoId,
        int $prendaIdx,
        string $procesoKey,
        int $procesoDetalleId,
        array $datosExtendidos
    ): int {
        $procesadas = 0;

        foreach ($datosExtendidos as $generoKey => $tallasDatos) {
            if (!is_array($tallasDatos)) {
                continue;
            }

            foreach ($tallasDatos as $tallaKey => $tallaData) {
                if (!is_array($tallaData)) {
                    continue;
                }

                $tallaId = $this->resolverProcesoPrendaTallaId($procesoDetalleId, (string) $generoKey, (string) $tallaKey);
                if (!$tallaId) {
                    continue;
                }

                $procesadas += $this->guardarArchivosPorTallaDesdeRequest(
                    $request,
                    $pedidoId,
                    $prendaIdx,
                    $procesoKey,
                    (string) $generoKey,
                    (string) $tallaKey,
                    $procesoDetalleId,
                    $tallaId
                );
            }
        }

        return $procesadas;
    }

    private function guardarArchivosPorTallaDesdeRequest(
        Request $request,
        int $pedidoId,
        int $prendaIdx,
        string $procesoKey,
        string $generoKey,
        string $tallaKey,
        int $procesoDetalleId,
        int $tallaId
    ): int {
        $procesadas = 0;
        $imgIdx = 0;

        while (true) {
            $dotKey = "prendas.{$prendaIdx}.procesos.{$procesoKey}.datosExtendidos.{$generoKey}.{$tallaKey}.imagenes.{$imgIdx}";
            if (!$request->hasFile($dotKey)) {
                break;
            }

            $archivo = $request->file($dotKey);
            $this->guardarImagenProceso($procesoDetalleId, $pedidoId, $archivo, $imgIdx, $tallaId);
            $procesadas++;
            $imgIdx++;
        }

        return $procesadas;
    }

    private function resolverProcesoDetalleId(int $pedidoId, int $prendaIdx, int $_procesoNumerico, mixed $procesoKey): ?int
    {
        $prenda = DB::table('prendas_pedido')
            ->where('pedido_produccion_id', $pedidoId)
            ->orderBy('id')
            ->offset($prendaIdx)
            ->first();

        if (!$prenda) {
            return null;
        }

        // Resolución estricta por nombre/slug del proceso (sin fallback por índice)
        if (!is_string($procesoKey) || $procesoKey === '') {
            return null;
        }

        $detalleByNombre = DB::table('pedidos_procesos_prenda_detalles as ppd')
            ->join('tipos_procesos as tp', 'tp.id', '=', 'ppd.tipo_proceso_id')
            ->where('ppd.prenda_pedido_id', $prenda->id)
            ->where(function ($q) use ($procesoKey) {
                $q->where('tp.slug', 'LIKE', $procesoKey)
                    ->orWhere('tp.nombre', 'LIKE', $procesoKey);
            })
            ->orderBy('ppd.id')
            ->select('ppd.id')
            ->first();

        return $detalleByNombre ? (int) $detalleByNombre->id : null;
    }

    private function resolverProcesoPrendaTallaId(int $procesoDetalleId, string $generoKey, string $tallaKey): ?int
    {
        $genero = strtoupper($generoKey);

        // Caso especial sobremedida: datosExtendidos.sobremedida.{genero}
        if ($genero === 'SOBREMEDIDA') {
            $generoSobremedida = strtoupper($tallaKey);
            $row = DB::table('pedidos_procesos_prenda_tallas')
                ->where('proceso_prenda_detalle_id', $procesoDetalleId)
                ->where('genero', $generoSobremedida)
                ->where('es_sobremedida', 1)
                ->orderBy('id')
                ->first();

            return $row ? (int) $row->id : null;
        }

        // En llaves con color: "M__NEGRO" => talla real "M"
        $tallaReal = explode('__', $tallaKey)[0] ?? $tallaKey;

        $row = DB::table('pedidos_procesos_prenda_tallas')
            ->where('proceso_prenda_detalle_id', $procesoDetalleId)
            ->where('genero', $genero)
            ->where('talla', $tallaReal)
            ->orderBy('id')
            ->first();

        return $row ? (int) $row->id : null;
    }
}


