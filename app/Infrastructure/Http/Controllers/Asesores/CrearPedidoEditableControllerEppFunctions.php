<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\PedidoProduccion;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;

/**
 *  CrearPedidoEditableControllerEppCompleto.php
 * 
 * SOLO las funciones necesarias para procesar EPP e imágenes correctamente
 * 
 * Incluir en: CrearPedidoEditableController
 */
class CrearPedidoEditableControllerEppFunctions
{
    /**
     *  Crear estructura de carpetas para un pedido
     * 
     * Crea:
     * - storage/app/public/pedido/{pedido_id}/prendas/
     * - storage/app/public/pedido/{pedido_id}/telas/
     * - storage/app/public/pedido/{pedido_id}/procesos/
     * - storage/app/public/pedido/{pedido_id}/epp/
     */
    public function crearCarpetasPedido(int $pedidoId): void
    {
        $basePath = "pedido/{$pedidoId}";
        $carpetas = ['prendas', 'telas', 'procesos', 'epp'];

        foreach ($carpetas as $carpeta) {
            $rutaCompleta = "{$basePath}/{$carpeta}";
            
            if (!Storage::disk('public')->exists($rutaCompleta)) {
                try {
                    Storage::disk('public')->makeDirectory($rutaCompleta, 0755, true);
                    Log::info('[CrearPedido] Carpeta creada', [
                        'pedido_id' => $pedidoId,
                        'carpeta' => $rutaCompleta
                    ]);
                } catch (\Exception $e) {
                    Log::warning('[CrearPedido] Error creando carpeta', [
                        'pedido_id' => $pedidoId,
                        'carpeta' => $rutaCompleta,
                        'error' => $e->getMessage()
                    ]);
                    // No fallar por carpetas
                }
            }
        }
    }

    /**
     *  Guardar imagen de EPP con validación
     * 
     * @param $archivo - UploadedFile desde $request->file()
     * @param int $pedidoId - ID del pedido
     * @param int $eppId - ID del EPP
     * @param int $indice - Índice de la imagen (0, 1, 2...)
     * @return string|null Ruta guardada o null si error
     */
    public function guardarImagenEpp($archivo, int $pedidoId, int $eppId, int $indice): ?string
    {
        if (!$archivo) {
            return null;
        }

        if (!$archivo->isValid()) {
            Log::warning('[CrearPedido] Archivo no válido', [
                'pedido_id' => $pedidoId,
                'epp_id' => $eppId,
                'error' => $archivo->getErrorMessage()
            ]);
            return null;
        }

        try {
            // Generar nombre único
            $timestamp = now()->format('YmdHis');
            $random = substr(md5(uniqid()), 0, 8);
            $extension = $archivo->extension() ?? 'jpg';
            $nombreUnico = "epp_{$eppId}_{$timestamp}_{$random}_{$indice}.{$extension}";

            // Ruta de destino: pedido/{id}/epp/
            $ruta = $archivo->storeAs(
                "pedido/{$pedidoId}/epp",
                $nombreUnico,
                'public'
            );

            Log::info('[CrearPedido] Imagen EPP guardada', [
                'pedido_id' => $pedidoId,
                'epp_id' => $eppId,
                'ruta' => $ruta,
                'tamaño' => $archivo->getSize()
            ]);

            return $ruta;

        } catch (\Exception $e) {
            Log::error('[CrearPedido] Error guardando imagen EPP', [
                'pedido_id' => $pedidoId,
                'epp_id' => $eppId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return null;
        }
    }

    /**
     *  Guardar referencia de imagen EPP en BD
     * 
     * Crea registro en:
     * - Tabla: pedido_epp (si no existe)
     * - Tabla: pedido_epp_imagenes (nueva imagen)
     */
    public function guardarImagenEppEnBd(
        int $pedidoId,
        int $eppId,
        string $ruta,
        int $orden = 0
    ): ?PedidoEppImagen {
        try {
            // Obtener o crear PedidoEpp
            $pedidoEpp = PedidoEpp::firstOrCreate(
                [
                    'pedido_produccion_id' => $pedidoId,
                    'epp_id' => $eppId,
                ],
                [
                    'cantidad' => 0,
                    'estado' => 'pendiente'
                ]
            );

            // Guardar imagen
            $imagen = PedidoEppImagen::create([
                'pedido_epp_id' => $pedidoEpp->id,
                'ruta' => $ruta,
                'es_principal' => $orden === 0,
                'orden' => $orden
            ]);

            Log::info('[CrearPedido] Imagen EPP registrada en BD', [
                'pedido_id' => $pedidoId,
                'epp_id' => $eppId,
                'pedido_epp_id' => $pedidoEpp->id,
                'ruta' => $ruta
            ]);

            return $imagen;

        } catch (\Exception $e) {
            Log::error('[CrearPedido] Error registrando imagen en BD', [
                'pedido_id' => $pedidoId,
                'epp_id' => $eppId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     *  Procesar imágenes de EPP desde FormData
     * 
     * Se ejecuta después de crear el pedido
     */
    public function procesarImagenesEpp(
        Request $request,
        int $pedidoId,
        array $eppsData
    ): array {
        $resultado = [
            'exitosas' => 0,
            'fallidas' => 0,
            'imagenes' => []
        ];

        if (!$eppsData || !is_array($eppsData)) {
            Log::debug('[CrearPedido] Sin datos de EPP para procesar');
            return $resultado;
        }

        // ========== PROCESAR CADA EPP ==========
        foreach ($eppsData as $eppIdx => $eppData) {
            $eppId = $eppData['epp_id'] ?? null;
            
            if (!$eppId) {
                Log::debug('[CrearPedido] EPP sin ID, saltando', ['index' => $eppIdx]);
                continue;
            }

            // ========== OBTENER ARCHIVOS DE ESTE EPP ==========
            $archivosEpp = $request->file("epps.{$eppIdx}.imagenes");

            if (!$archivosEpp) {
                Log::debug('[CrearPedido] EPP sin imágenes', [
                    'eppIdx' => $eppIdx,
                    'epp_id' => $eppId
                ]);
                continue;
            }

            // Convertir a array si es un solo archivo
            $archivosArray = is_array($archivosEpp) ? $archivosEpp : [$archivosEpp];

            Log::info('[CrearPedido] Procesando EPP imágenes', [
                'eppIdx' => $eppIdx,
                'epp_id' => $eppId,
                'cantidad_archivos' => count($archivosArray)
            ]);

            // ========== GUARDAR CADA IMAGEN ==========
            foreach ($archivosArray as $imgIdx => $archivo) {
                if (!$archivo) {
                    continue;
                }

                try {
                    // Guardar archivo en storage
                    $ruta = $this->guardarImagenEpp(
                        $archivo,
                        $pedidoId,
                        $eppId,
                        $imgIdx
                    );

                    if ($ruta) {
                        // Guardar referencia en BD
                        $imagenBd = $this->guardarImagenEppEnBd(
                            $pedidoId,
                            $eppId,
                            $ruta,
                            $imgIdx
                        );

                        if ($imagenBd) {
                            $resultado['exitosas']++;
                            $resultado['imagenes'][] = [
                                'epp_id' => $eppId,
                                'ruta' => $ruta,
                                'url' => "/storage/{$ruta}"
                            ];

                            Log::debug('[CrearPedido] Imagen EPP guardada exitosamente', [
                                'epp_id' => $eppId,
                                'indice' => $imgIdx,
                                'ruta' => $ruta
                            ]);
                        }
                    } else {
                        $resultado['fallidas']++;
                        Log::warning('[CrearPedido] Fallo guardando archivo de EPP', [
                            'epp_id' => $eppId,
                            'indice' => $imgIdx
                        ]);
                    }
                } catch (\Exception $e) {
                    $resultado['fallidas']++;
                    Log::error('[CrearPedido] Error procesando imagen de EPP', [
                        'epp_id' => $eppId,
                        'indice' => $imgIdx,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        Log::info('[CrearPedido] Procesamiento de EPP finalizado', [
            'pedido_id' => $pedidoId,
            'exitosas' => $resultado['exitosas'],
            'fallidas' => $resultado['fallidas']
        ]);

        return $resultado;
    }

    /**
     *  Validar que los files existan en FormData
     */
    public function validarImagenesEpp(Request $request): bool
    {
        $tieneImagenes = false;

        // Verificar si hay al menos un archivo en epps
        foreach ($request->all() as $key => $value) {
            if (strpos($key, 'epps') === 0 && $request->hasFile($key)) {
                $tieneImagenes = true;
                break;
            }
        }

        return $tieneImagenes;
    }

    /**
     *  Obtener count total de imágenes EPP en FormData
     */
    public function contarImagenesEpp(Request $request): int
    {
        $total = 0;

        foreach ($request->all() as $key => $value) {
            if (strpos($key, 'epps') === 0 && strpos($key, 'imagenes') !== false) {
                if ($request->hasFile($key)) {
                    $files = $request->file($key);
                    $total += is_array($files) ? count($files) : 1;
                }
            }
        }

        return $total;
    }
}
