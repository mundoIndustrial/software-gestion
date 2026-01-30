<?php

namespace App\Domain\Pedidos\Services;

use App\Domain\Pedidos\DTOs\PedidoNormalizadorDTO;
use Illuminate\Support\Facades\Log;

/**
 * MapeoImagenesService
 * 
 * Mapea imágenes a sus entidades después de crear en BD
 * 
 * FLUJO:
 * 1. Se recibe JSON con referencias de imágenes (UID + nombre_archivo)
 * 2. Se crean prendas/telas/procesos en BD (obtienen IDs)
 * 3. Este servicio mapea: UID → ID_BD y crea PrendaFotoPedido, PrendaFotoTelaPedido, etc
 * 
 * FLUJO DETALADO:
 * 
 * Backend recibe:
 * {
 *   "prendas": [
 *     {
 *       "uid": "uuid-1",
 *       "nombre_prenda": "Camisa",
 *       "telas": [
 *         {
 *           "uid": "tela-uuid-1",
 *           "imagenes": [
 *             { "uid": "img-uuid-1", "nombre_archivo": "tela_001.jpg" }
 *           ]
 *         }
 *       ],
 *       "procesos": [
 *         {
 *           "uid": "proceso-uuid-1",
 *           "imagenes": [
 *             { "uid": "img-uuid-2", "nombre_archivo": "bordado_001.jpg" }
 *           ]
 *         }
 *       ],
 *       "imagenes": [
 *         { "uid": "img-uuid-3", "nombre_archivo": "prenda_001.jpg" }
 *       ]
 *     }
 *   ]
 * }
 * 
 * + FormData con archivos:
 * - prendas.0.imagenes.0 = File (archivo 001.jpg)
 * - prendas.0.telas.0.imagenes.0 = File (archivo tela_001.jpg)
 * - prendas.0.procesos.0.imagenes.0 = File (archivo bordado_001.jpg)
 * 
 * PROCESO:
 * 1. CrearPedidoEditableController → ResolutorImagenesService
 *    - Extrae archivos de FormData
 *    - Guarda en storage/pedidos/{id}/{carpeta}/
 *    - Mapea: UID → ruta final
 * 
 * 2. Crea entidades en BD:
 *    - PrendaProduccion (obtiene ID: 3432)
 *    - PrendaPedidoColorTela (obtiene ID: 60)
 *    - TipoProceso → ProcesoPrendaDetalle (obtiene ID: 77)
 * 
 * 3. MapeoImagenesService (este archivo) → crea registros en:
 *    - PrendaFotoPedido (uid_img_3 → ruta final)
 *    - PrendaFotoTelaPedido (uid_img_1 → ruta final)
 *    - ProcesoPrendaFoto (uid_img_2 → ruta final)
 */

class MapeoImagenesService
{
    public function __construct(
        private ResolutorImagenesService $resolutorImagenes
    ) {}

    /**
     * Mapear y crear registros de imágenes después de crear entidades en BD
     * 
     * @param PedidoNormalizadorDTO $dto
     * @param int $pedidoId
     * @param $request
     */
    public function mapearYCrearFotos(
        PedidoNormalizadorDTO $dto,
        int $pedidoId,
        $request
    ): void {
        $inicioTotal = microtime(true);
        Log::info('[MAPEO-IMAGENES] 📸 INICIANDO MAPEO DE IMÁGENES', [
            'pedido_id' => $pedidoId,
            'prendas' => count($dto->prendas),
            'timestamp' => now(),
        ]);

        // Resolver todas las imágenes (extraer, procesar, mapear)
        $inicioResolver = microtime(true);
        $mapeoUidARuta = $this->resolutorImagenes->extraerYProcesarImagenes(
            $request,
            $pedidoId,
            $dto->prendas,
            function ($uid, $ruta) use ($dto) {
                $dto->registrarImagenUID($uid, $ruta);
            }
        );
        $tiempoResolver = round((microtime(true) - $inicioResolver) * 1000, 2);

        Log::info('[MAPEO-IMAGENES] ✅ Mapeo UID→Ruta completado', [
            'imagenes_mapeadas' => count($mapeoUidARuta),
            'tiempo_resolver_ms' => $tiempoResolver,
        ]);

        // Ahora crear registros en BD usando los IDs obtenidos
        $inicioRegistros = microtime(true);
        $this->crearRegistrosPrendas($dto, $pedidoId, $mapeoUidARuta);
        $tiempoRegistros = round((microtime(true) - $inicioRegistros) * 1000, 2);
        
        $tiempoTotal = round((microtime(true) - $inicioTotal) * 1000, 2);
        Log::info('[MAPEO-IMAGENES] ✨ MAPEO COMPLETADO', [
            'tiempo_total_ms' => $tiempoTotal,
            'tiempo_resolver_ms' => $tiempoResolver,
            'tiempo_crear_registros_ms' => $tiempoRegistros,
            'resumen' => "Resolver: {$tiempoResolver}ms | Registros BD: {$tiempoRegistros}ms | TOTAL: {$tiempoTotal}ms",
        ]);
    }

    /**
     * Crear registros PrendaFotoPedido, PrendaFotoTelaPedido, etc
     */
    private function crearRegistrosPrendas(
        PedidoNormalizadorDTO $dto,
        int $pedidoId,
        array $mapeoUidARuta
    ): void {
        // Obtener prendas creadas en BD
        $prendas = \App\Models\PrendaPedido::where('pedido_produccion_id', $pedidoId)
            ->with('coloresTelas', 'procesos.tipoProceso')
            ->get();

        $prendaIdx = 0;
        foreach ($dto->prendas as $prendaDTO) {
            $prendaUID = $prendaDTO['uid'];
            $prenda = $prendas[$prendaIdx] ?? null;

            if (!$prenda) {
                Log::warning('[MapeoImagenesService] Prenda no encontrada en BD', [
                    'prendaIdx' => $prendaIdx,
                    'uid' => $prendaUID,
                ]);
                $prendaIdx++;
                continue;
            }

            // Registrar prenda UID
            $dto->registrarPrendaUID($prendaUID, $prenda->id);

            // ========================================
            // IMÁGENES DE PRENDA
            // ========================================
            foreach ($prendaDTO['imagenes'] as $imgIdx => $imagenDTO) {
                $imagenUID = $imagenDTO['uid'];
                $rutaFinal = $mapeoUidARuta[$imagenUID] ?? null;

                if (!$rutaFinal) {
                    Log::warning('[MapeoImagenesService] Imagen sin ruta', [
                        'imagen_uid' => $imagenUID,
                        'prenda_id' => $prenda->id,
                    ]);
                    continue;
                }

                \App\Models\PrendaFotoPedido::create([
                    'prenda_pedido_id' => $prenda->id,
                    'ruta_webp' => $rutaFinal,
                    'orden' => $imgIdx + 1,
                ]);

                Log::debug('[MapeoImagenesService] PrendaFotoPedido creado', [
                    'prenda_id' => $prenda->id,
                    'ruta' => $rutaFinal,
                ]);
            }

            // ========================================
            // IMÁGENES DE TELAS
            // ========================================
            $telasEnPrenda = $prenda->coloresTelas()->get();
            foreach ($prendaDTO['telas'] as $telaIdx => $telaDTO) {
                $telaUID = $telaDTO['uid'];
                $telaEnBD = $telasEnPrenda[$telaIdx] ?? null;

                if (!$telaEnBD) {
                    Log::warning('[MapeoImagenesService] Tela no encontrada en BD', [
                        'prenda_id' => $prenda->id,
                        'telaIdx' => $telaIdx,
                        'uid' => $telaUID,
                    ]);
                    continue;
                }

                // Registrar tela UID
                $dto->registrarTelaUID($telaUID, $telaEnBD->id);

                foreach ($telaDTO['imagenes'] as $imgIdx => $imagenDTO) {
                    $imagenUID = $imagenDTO['uid'];
                    $rutaFinal = $mapeoUidARuta[$imagenUID] ?? null;

                    if (!$rutaFinal) continue;

                    \App\Models\PrendaFotoTelaPedido::create([
                        'prenda_pedido_colores_telas_id' => $telaEnBD->id,
                        'ruta_webp' => $rutaFinal,
                        'orden' => $imgIdx + 1,
                    ]);

                    Log::debug('[MapeoImagenesService] PrendaFotoTelaPedido creado', [
                        'tela_id' => $telaEnBD->id,
                        'ruta' => $rutaFinal,
                    ]);
                }
            }

            // ========================================
            // IMÁGENES DE PROCESOS
            // ========================================
            $procesosEnPrenda = $prenda->procesos()->get();
            
            // DEBUG: Verificar qué se guardó en datos_adicionales
            Log::debug('[MapeoImagenesService] Procesos cargados', [
                'prenda_id' => $prenda->id,
                'procesos' => $procesosEnPrenda->map(function ($p) {
                    $datos = $p->datos_adicionales;
                    return [
                        'id' => $p->id,
                        'datos_adicionales_raw' => $datos,
                        'es_null' => is_null($datos),
                        'es_string' => is_string($datos),
                        'es_array' => is_array($datos),
                        'uid_value' => $datos['uid'] ?? 'NO_ENCONTRADO',
                    ];
                })->toArray(),
            ]);
            
            foreach ($prendaDTO['procesos'] as $procesoIdx => $procesoDTO) {
                $procesoUID = $procesoDTO['uid'];
                
                // Buscar proceso en BD por UID guardado en datos_adicionales
                // (El UID se guardó en PedidoWebService::crearProcesosCompletos)
                $procesoEnBD = $procesosEnPrenda
                    ->first(function ($p) use ($procesoUID) {
                        $datosAdicionales = $p->datos_adicionales ?? [];
                        return ($datosAdicionales['uid'] ?? null) === $procesoUID;
                    });

                if (!$procesoEnBD) {
                    // Si no encuentra por UID, intentar búsqueda alternativa por tipo de proceso
                    // (Por si el UID no se guardó correctamente)
                    // Usar el índice del DTO como nombre del tipo (ej: "reflectivo", "bordado")
                    $nombreProcesoDTO = strtoupper($procesoIdx);
                    Log::debug('[MapeoImagenesService] Búsqueda alternativa por tipo', [
                        'procesoIdx' => $procesoIdx,
                        'nombreProcesoDTO' => $nombreProcesoDTO,
                        'tipos_disponibles' => $procesosEnPrenda->map(fn($p) => strtoupper($p->tipoProceso->nombre ?? ''))->unique()->toArray(),
                    ]);
                    if ($nombreProcesoDTO) {
                        $procesoEnBD = $procesosEnPrenda
                            ->first(function ($p) use ($nombreProcesoDTO) {
                                return strtoupper($p->tipoProceso->nombre ?? '') === $nombreProcesoDTO;
                            });
                    }
                }

                if (!$procesoEnBD) {
                    Log::warning('[MapeoImagenesService] Proceso no encontrado en BD', [
                        'prenda_id' => $prenda->id,
                        'procesoIdx' => $procesoIdx,
                        'uid' => $procesoUID,
                        'procesos_disponibles' => $procesosEnPrenda->map(function ($p) {
                            return [
                                'id' => $p->id,
                                'tipo' => $p->tipoProceso->nombre ?? 'desconocido',
                                'uid_guardado' => $p->datos_adicionales['uid'] ?? 'no-guardado',
                            ];
                        })->toArray(),
                    ]);
                    continue;
                }

                // Registrar proceso UID
                $dto->registrarProcesoUID($procesoUID, $procesoEnBD->id);

                foreach ($procesoDTO['imagenes'] as $imgIdx => $imagenDTO) {
                    $imagenUID = $imagenDTO['uid'];
                    $rutaFinal = $mapeoUidARuta[$imagenUID] ?? null;

                    if (!$rutaFinal) continue;

                    \App\Models\PedidosProcessImagenes::create([
                        'proceso_prenda_detalle_id' => $procesoEnBD->id,
                        'ruta_original' => $rutaFinal,
                        'ruta_webp' => $rutaFinal,
                        'orden' => $imgIdx + 1,
                        'es_principal' => $imgIdx === 0,
                    ]);

                    Log::debug('[MapeoImagenesService] PedidosProcessImagenes creado', [
                        'proceso_id' => $procesoEnBD->id,
                        'ruta' => $rutaFinal,
                        'es_principal' => $imgIdx === 0,
                    ]);
                }
            }

            $prendaIdx++;
        }

        Log::info('[MapeoImagenesService] Mapeo de imágenes completado', [
            'pedido_id' => $pedidoId,
        ]);
    }
}
