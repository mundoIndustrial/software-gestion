<?php

namespace App\Application\UseCases\Pedidos;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use App\Models\News;
use App\Domain\Pedidos\DTOs\PedidoNormalizadorDTO;
use App\Domain\Pedidos\Services\PedidoWebService;
use App\Domain\Pedidos\Services\PedidoImagenesService;
use App\Domain\Clientes\Services\ClienteService;
use App\Application\Services\ImageUploadService;
use App\Application\Services\ColorTelaService;
use App\Domain\Pedidos\Services\ResolutorImagenesService;
use App\Domain\Pedidos\Services\MapeoImagenesService;
use App\Domain\Pedidos\Services\ProcesoImagenService;

/**
 * UseCase: Crear Pedido Completo
 * 
 * Orquesta todo el proceso de creación de un pedido:
 * 1. Validación
 * 2. Normalización
 * 3. Persistencia
 * 4. Gestión de imágenes
 * 5. Notificaciones
 * 
 * 100% TRANSACCIONAL: TODO o NADA
 */
class CrearPedidoCompleteUseCase
{
    public function __construct(
        private ClienteService $clienteService,
        private PedidoImagenesService $pedidoImagenesService,
        private PedidoWebService $pedidoWebService,
        private ImageUploadService $imageUploadService,
        private ColorTelaService $colorTelaService,
        private ResolutorImagenesService $resolutorImagenes,
        private MapeoImagenesService $mapeoImagenes,
        private ProcesoImagenService $procesoImagenService,
    ) {}

    /**
     * Ejecutar el UseCase
     * 
     * @param CrearPedidoInput $input
     * @return CrearPedidoOutput
     */
    public function ejecutar(CrearPedidoInput $input): CrearPedidoOutput
    {
        $pedidoId = null;
        $inicioTotal = microtime(true);

        try {
            Log::info('[CREAR-PEDIDO-USECASE] INICIANDO CREACIÓN TRANSACCIONAL', [
                'has_pedido_json' => !!$input->datosFrontend,
                'archivos_count' => count($input->request->allFiles()),
                'timestamp' => now(),
            ]);

            // ====== PASO 1: Validar JSON sin Files ======
            $this->pedidoImagenesService->validarJsonSinFiles($input->datosFrontend);
            Log::info('[CREAR-PEDIDO-USECASE] PASO 1: JSON validado');

            // ====== PASO 2: Obtener/crear cliente ======
            $clienteNombre = $input->getClienteNombre();
            $cliente = $this->clienteService->obtenerOCrearCliente($clienteNombre);
            Log::info('[CREAR-PEDIDO-USECASE] PASO 2: Cliente obtenido/creado', [
                'cliente_id' => $cliente->id,
                'nombre' => $cliente->nombre,
            ]);

            // ====== PASO 3: Normalizar usando DTO ======
            $dtoPedido = PedidoNormalizadorDTO::fromFrontendJSON(
                $input->datosFrontend,
                $cliente->id
            );
            Log::info('[CREAR-PEDIDO-USECASE] PASO 3: Pedido normalizado (DTO)', [
                'cliente_id' => $dtoPedido->cliente_id,
                'prendas' => count($dtoPedido->prendas),
                'epps' => count($dtoPedido->epps),
            ]);

            // ====== PASO 4: Iniciar transacción ======
            DB::beginTransaction();
            Log::debug('[CREAR-PEDIDO-USECASE] Transacción DB iniciada');

            // ====== PASO 5: Crear pedido base ======
            $datosParaServicio = [
                'cliente' => $dtoPedido->cliente,
                'asesora' => $dtoPedido->asesora,
                'forma_de_pago' => $dtoPedido->forma_de_pago,
                'observaciones' => $dtoPedido->observaciones,
                'cliente_id' => $dtoPedido->cliente_id,
                'items' => $dtoPedido->prendas,
                'epps' => $dtoPedido->epps,
            ];

            $pedido = $this->pedidoWebService->crearPedidoCompleto(
                $datosParaServicio,
                $input->usuarioId
            );
            $pedidoId = $pedido->id;
            Log::info('[CREAR-PEDIDO-USECASE] PASO 5: Pedido base creado', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            // ====== PASO 6: Crear carpetas ======
            $this->pedidoImagenesService->crearCarpetasPedido($pedidoId);
            Log::info('[CREAR-PEDIDO-USECASE] PASO 6: Carpetas creadas', [
                'pedido_id' => $pedidoId,
            ]);

            // ====== PASO 7: Mapear y procesar imágenes ======
            $this->mapeoImagenes->mapearYCrearFotos(
                $dtoPedido,
                $pedidoId,
                $input->request
            );
            Log::info('[CREAR-PEDIDO-USECASE] PASO 7: Imágenes mapeadas y creadas', [
                'pedido_id' => $pedidoId,
                'imagenes_mapeadas' => count($dtoPedido->imagen_uid_a_ruta),
            ]);

            // ====== PASO 7B: Procesar imágenes de EPPs ======
            $eppsData = $input->getEpps();
            if (!empty($eppsData)) {
                $this->procesarImagenesDeEpps($input->request, $pedidoId, $eppsData);
                Log::info('[CREAR-PEDIDO-USECASE] PASO 7B: Imágenes de EPPs procesadas', [
                    'pedido_id' => $pedidoId,
                    'epps_count' => count($eppsData),
                ]);
            }

            // ====== PASO 7C: Procesar imágenes por talla ======
            $prendas = $input->getPrendas();
            if (!empty($prendas)) {
                $imagenesPorTallaGuardadas = $this->procesarImagenesPorTalla(
                    $input->request,
                    $pedidoId,
                    $prendas
                );
                if ($imagenesPorTallaGuardadas > 0) {
                    Log::info('[CREAR-PEDIDO-USECASE] PASO 7C: Imágenes por talla procesadas', [
                        'pedido_id' => $pedidoId,
                        'imagenes_guardadas' => $imagenesPorTallaGuardadas,
                    ]);
                }
            }

            // ====== PASO 7D: Procesar imágenes de colores ======
            $this->procesarImagenesDeColores($input->request, $pedidoId, $prendas);
            Log::info('[CREAR-PEDIDO-USECASE] PASO 7D: Imágenes de colores procesadas', [
                'pedido_id' => $pedidoId,
            ]);

            // ====== PASO 8: Calcular cantidades y commit ======
            $cantidadTotalPrendas = $this->calcularCantidadTotalPrendas($pedidoId);
            $cantidadTotalEpps = $this->calcularCantidadTotalEpps($pedidoId);
            $cantidadTotal = $cantidadTotalPrendas + $cantidadTotalEpps;
            $pedido->update(['cantidad_total' => $cantidadTotal]);

            DB::commit();
            Log::info('[CREAR-PEDIDO-USECASE] PASO 8: Cantidades calculadas y commit realizado', [
                'pedido_id' => $pedidoId,
                'cantidades' => [
                    'prendas' => $cantidadTotalPrendas,
                    'epps' => $cantidadTotalEpps,
                    'total' => $cantidadTotal,
                ],
            ]);

            // ====== PASO 9: Crear notificación ======
            $this->crearNotificacionPedido($pedido, $cliente, $input->usuarioId, $cantidadTotalPrendas, $cantidadTotalEpps);

            // ====== PASO 10: Crear output ======
            $tiempoTotal = round((microtime(true) - $inicioTotal) * 1000, 2);
            Log::info('[CREAR-PEDIDO-USECASE] ✨ CREACIÓN EXITOSA', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'tiempo_total_ms' => $tiempoTotal,
            ]);

            return CrearPedidoOutput::success(
                $pedidoId,
                $pedido->numero_pedido,
                $cliente->id,
                [
                    'prendas' => $cantidadTotalPrendas,
                    'epps' => $cantidadTotalEpps,
                    'tiempo_ms' => $tiempoTotal,
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[CREAR-PEDIDO-USECASE] ERROR - Rollback iniciado', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Limpiar archivos si se creó carpeta
            if ($pedidoId) {
                try {
                    $carpetaPedido = "pedidos/{$pedidoId}";
                    if (Storage::disk('public')->exists($carpetaPedido)) {
                        Storage::disk('public')->deleteDirectory($carpetaPedido);
                        Log::info('[CREAR-PEDIDO-USECASE] Carpeta eliminada en cleanup', [
                            'carpeta' => $carpetaPedido,
                        ]);
                    }
                } catch (\Exception $cleanupError) {
                    Log::error('[CREAR-PEDIDO-USECASE] Error al limpiar archivos', [
                        'error' => $cleanupError->getMessage(),
                    ]);
                }
            }

            return CrearPedidoOutput::failure('Error: ' . $e->getMessage());
        }
    }

    /**
     * Procesar SOLO las imágenes de EPPs ya existentes
     */
    private function procesarImagenesDeEpps($request, int $pedidoId, array $epps): void
    {
        Log::info('[CREAR-PEDIDO-USECASE] Procesando imágenes de EPPs', [
            'pedido_id' => $pedidoId,
            'epps_count' => count($epps),
        ]);

        foreach ($epps as $eppIdx => $eppData) {
            $pedidoEpp = PedidoEpp::where('pedido_produccion_id', $pedidoId)
                ->where('epp_id', $eppData['epp_id'])
                ->first();

            if (!$pedidoEpp) {
                Log::warning('[CREAR-PEDIDO-USECASE] EPP no encontrado para procesar imágenes', [
                    'pedido_id' => $pedidoId,
                    'epp_id' => $eppData['epp_id'],
                ]);
                continue;
            }

            $imagenesGuardadas = 0;
            $imgIdx = 0;

            // Procesar archivos FormData
            while (true) {
                $formKey = "epps_{$eppIdx}_imagenes_{$imgIdx}";

                if (!$request->hasFile($formKey)) {
                    break;
                }

                try {
                    $archivo = $request->file($formKey);

                    $resultado = $this->imageUploadService->guardarImagenDirecta(
                        $archivo,
                        $pedidoId,
                        'epps',
                        null,
                        "epp_{$eppData['epp_id']}_img_{$imgIdx}"
                    );

                    PedidoEppImagen::create([
                        'pedido_epp_id' => $pedidoEpp->id,
                        'ruta_original' => $resultado['webp'],
                        'ruta_web' => $resultado['webp'],
                        'orden' => $imgIdx + 1,
                        'principal' => $imgIdx === 0 ? 1 : 0,
                    ]);

                    $imagenesGuardadas++;
                    Log::debug('[CREAR-PEDIDO-USECASE] Imagen EPP guardada', [
                        'pedido_epp_id' => $pedidoEpp->id,
                        'webp' => $resultado['webp'],
                        'orden' => $imgIdx + 1,
                    ]);

                    $imgIdx++;
                } catch (\Exception $e) {
                    Log::error('[CREAR-PEDIDO-USECASE] Error procesando imagen EPP', [
                        'pedido_epp_id' => $pedidoEpp->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }

            // Copiar desde URLs existentes (modo edición)
            if ($imagenesGuardadas === 0) {
                $imagenesJson = $eppData['imagenes'] ?? [];
                if (is_array($imagenesJson) && count($imagenesJson) > 0) {
                    $this->copiarImagenesEppDesdeUrls($pedidoEpp, $eppData, $imagenesJson, $pedidoId);
                }
            }
        }

        Log::info('[CREAR-PEDIDO-USECASE] Imágenes de EPPs procesadas', [
            'pedido_id' => $pedidoId,
        ]);
    }

    /**
     * Copiar imágenes de EPP desde URLs existentes
     */
    private function copiarImagenesEppDesdeUrls($pedidoEpp, $eppData, $imagenesJson, int $pedidoId): void
    {
        $destDir = "pedidos/{$pedidoId}/epp";
        if (!Storage::disk('public')->exists($destDir)) {
            Storage::disk('public')->makeDirectory($destDir);
        }

        $orden = 1;
        foreach ($imagenesJson as $img) {
            $url = is_string($img) ? $img : ($img['url'] ?? $img['preview'] ?? null);

            if (!$url) {
                continue;
            }

            $path = parse_url($url, PHP_URL_PATH) ?: '';
            $pos = strpos($path, '/storage/');
            $relative = '';

            if ($pos !== false) {
                $relative = ltrim(substr($path, $pos + strlen('/storage/')), '/');
            } else {
                $relative = ltrim($path !== '' ? $path : $url, '/');
            }

            if (str_starts_with($relative, 'storage/')) {
                $relative = substr($relative, strlen('storage/'));
            }

            if ($relative === '' || !Storage::disk('public')->exists($relative)) {
                Log::warning('[CREAR-PEDIDO-USECASE] Imagen EPP no existe para copiar', [
                    'pedido_epp_id' => $pedidoEpp->id,
                    'url' => $url,
                ]);
                continue;
            }

            $destName = "epp_{$eppData['epp_id']}_img_" . ($orden - 1) . '.webp';
            $destRelative = $destDir . '/' . $destName;

            try {
                $copiado = Storage::disk('public')->copy($relative, $destRelative);
                if ($copiado) {
                    PedidoEppImagen::create([
                        'pedido_epp_id' => $pedidoEpp->id,
                        'ruta_original' => $destRelative,
                        'ruta_web' => $destRelative,
                        'orden' => $orden,
                        'principal' => $orden === 1 ? 1 : 0,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('[CREAR-PEDIDO-USECASE] Error copiando imagen EPP', [
                    'pedido_epp_id' => $pedidoEpp->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $orden++;
        }
    }

    /**
     * Procesar imágenes por talla
     */
    private function procesarImagenesPorTalla($request, int $pedidoId, array $prendas): int
    {
        $contadorTotal = 0;

        foreach ($prendas as $prendaIdx => $prenda) {
            $procesos = $prenda['procesos'] ?? [];
            $procesoNumerico = 0;

            foreach ($procesos as $procesoKey => $proceso) {
                $modeTallas = $proceso['modo_tallas'] ?? 'para_todas';
                $datosExtendidos = $proceso['datosExtendidos'] ?? $proceso['datos_extendidos'] ?? null;

                if (($modeTallas === 'por_tallas' || $modeTallas === 'especifico') && !empty($datosExtendidos)) {
                    $contadorTotal += $this->procesoImagenService->procesarImagenesPorTalla(
                        $request,
                        $pedidoId,
                        $prendaIdx,
                        $procesoNumerico,
                        $procesoKey,
                        $datosExtendidos
                    );
                }
                $procesoNumerico++;
            }
        }

        return $contadorTotal;
    }

    /**
     * Procesar imágenes de colores
     */
    private function procesarImagenesDeColores($request, int $pedidoId, array $prendas): void
    {
        $fotosColorFiles = $request->file('fotos_color') ?? [];
        $fotosColorMetaAll = $request->input('fotos_color_meta') ?? [];

        if (empty($fotosColorFiles)) {
            return;
        }

        $colorFotoServiceCrear = new \App\Domain\Pedidos\Services\TelaFotoService();
        $fotosColorMeta = [];
        $imagenesProcesadas = 0;

        foreach ($fotosColorFiles as $indice => $archivo) {
            if ($archivo && $archivo->isValid()) {
                try {
                    $rutas = $colorFotoServiceCrear->procesarFoto($archivo, (int)$pedidoId, true);
                    $metaRaw = $fotosColorMetaAll[$indice] ?? null;
                    $meta = is_string($metaRaw) ? json_decode($metaRaw, true) : $metaRaw;

                    $fotosColorMeta[] = [
                        'ruta_webp' => $rutas['ruta_webp'] ?? $rutas['ruta_original'],
                        'clave' => $meta['clave'] ?? '',
                        'color_nombre' => $meta['color_nombre'] ?? '',
                    ];

                    $imagenesProcesadas++;
                } catch (\Exception $e) {
                    Log::warning('[CREAR-PEDIDO-USECASE] Error procesando imagen de color', [
                        'pedido_id' => $pedidoId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Inyectar rutas en asignaciones_colores
        if (!empty($fotosColorMeta)) {
            foreach ($prendas as $prendaIdx => $prenda) {
                $asignacionesColores = $prenda['asignacionesColoresPorTalla'] ?? [];

                foreach ($fotosColorMeta as $fotoMeta) {
                    $clave = $fotoMeta['clave'];
                    $colorNombre = strtoupper($fotoMeta['color_nombre']);

                    if (isset($asignacionesColores[$clave]) && !empty($asignacionesColores[$clave]['colores'])) {
                        foreach ($asignacionesColores[$clave]['colores'] as &$colorItem) {
                            if (strtoupper($colorItem['nombre'] ?? '') === $colorNombre) {
                                \App\Models\PrendaPedidoTallaColor::where('color_nombre', $colorNombre)
                                    ->whereHas('prendaPedidoTalla', function ($q) use ($pedidoId) {
                                        $q->whereHas('prendaPedido', function ($q2) use ($pedidoId) {
                                            $q2->where('pedido_produccion_id', $pedidoId);
                                        });
                                    })
                                    ->update(['imagen_ruta' => $fotoMeta['ruta_webp']]);

                                break;
                            }
                        }
                        unset($colorItem);
                    }
                }
            }
        }
    }

    /**
     * Calcular cantidad total de prendas
     */
    private function calcularCantidadTotalPrendas(int $pedidoId): int
    {
        try {
            $cantidad = DB::table('pedidos_procesos_prenda_tallas as pppt')
                ->selectRaw('COALESCE(SUM(pppt.cantidad), 0) as total')
                ->join('pedidos_procesos_prenda_detalles as ppd', 'pppt.proceso_prenda_detalle_id', '=', 'ppd.id')
                ->join('prendas_pedido as pp', 'ppd.prenda_pedido_id', '=', 'pp.id')
                ->where('pp.pedido_produccion_id', $pedidoId)
                ->value('total');

            return (int) $cantidad ?? 0;
        } catch (\Exception $e) {
            Log::warning('[CREAR-PEDIDO-USECASE] Error calculando cantidad de prendas', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Calcular cantidad total de EPPs
     */
    private function calcularCantidadTotalEpps(int $pedidoId): int
    {
        return (int) DB::table('pedido_epp')
            ->where('pedido_produccion_id', $pedidoId)
            ->sum('cantidad');
    }

    /**
     * Crear notificación de pedido creado
     */
    private function crearNotificacionPedido($pedido, $cliente, int $usuarioId, int $cantidadPrendas, int $cantidadEpps): void
    {
        try {
            $user = \Auth::user();
            $nombreAsesor = $user->name ?? 'Sistema';

            News::create([
                'event_type' => 'pedido_creado',
                'table_name' => 'pedidos_produccion',
                'record_id' => $pedido->id,
                'description' => "Asesor {$nombreAsesor} creó el Pedido #{$pedido->numero_pedido} - Cliente: {$cliente->nombre}",
                'user_id' => $usuarioId,
                'pedido' => $pedido->numero_pedido,
                'metadata' => [
                    'tipo' => 'pedido_creado',
                    'pedido_id' => $pedido->id,
                    'cliente' => $cliente->nombre,
                    'prendas' => $cantidadPrendas,
                    'epps' => $cantidadEpps,
                ],
            ]);
        } catch (\Exception $e) {
            Log::warning('[CREAR-PEDIDO-USECASE] Error creando News', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
