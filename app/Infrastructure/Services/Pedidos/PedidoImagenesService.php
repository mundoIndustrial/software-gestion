<?php

namespace App\Infrastructure\Services\Pedidos;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\PedidoProduccion;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use App\Models\PedidosProcessImagenes;
use App\Application\Services\ImageUploadService;
use App\Infrastructure\Services\Pedidos\ProcesoImagenService;

/**
 * Service: Gestion de Imagenes de Pedidos
 * 
 * FASE 4 - Consolidacion de procesamiento de imagenes
 * Responsabilidades:
 * - Procesar y asignar imagenes de prendas, telas, procesos
 * - Procesar y asignar imagenes de EPPs
 * - Crear estructura de carpetas para pedidos
 * - Validar archivos JSON sin objetos File
 * 
 * Patron: Domain Service (logica de negocio, sin HTTP)
 * 
 * @package App\Domain\Pedidos\Services
 */
class PedidoImagenesService
{
    public function __construct(
        private ImageUploadService $imageUploadService,
        private ProcesoImagenService $procesoImagenService,
    ) {}

    /**
     * Crear estructura de carpetas para un pedido
     * 
     * Crea:
     * - storage/app/public/pedidos/{pedido_id}/prenda/
     * - storage/app/public/pedidos/{pedido_id}/tela/
     * - storage/app/public/pedidos/{pedido_id}/proceso/
     * - storage/app/public/pedidos/{pedido_id}/epp/
     * 
     * @param int $pedidoId
     * @return void
     */
    public function crearCarpetasPedido(int $pedidoId): void
    {
        $basePath = "pedidos/{$pedidoId}";
        // IMPORTANTE: Usar singular para coincidir con ImageUploadService::guardarImagenDirecta()
        // que convierte plural a singular con rtrim($tipo, 's')
        $carpetas = ['prenda', 'tela', 'proceso', 'epp'];

        foreach ($carpetas as $carpeta) {
            $rutaCompleta = "{$basePath}/{$carpeta}";
            
            if (!Storage::disk('public')->exists($rutaCompleta)) {
                try {
                    Storage::disk('public')->makeDirectory($rutaCompleta);
                    Log::info('[PedidoImagenesService] Carpeta creada', [
                        'pedido_id' => $pedidoId,
                        'carpeta' => $rutaCompleta
                    ]);
                } catch (\Exception $e) {
                    Log::warning('[PedidoImagenesService] Error creando carpeta', [
                        'pedido_id' => $pedidoId,
                        'carpeta' => $rutaCompleta,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Procesar y asignar imagenes directamente a carpetas finales
     * 
     * 1 archivo = 1 webp en su carpeta final
     * NO temp, NO relocalizacion
     * Carpetas especificas por tipo:
     *    - pedidos/{id}/prenda/
     *    - pedidos/{id}/tela/
     *    - pedidos/{id}/proceso/{TIPO}/
     * 
     * @param Request $request
     * @param int $pedidoId
     * @param array $items
     * @return void
     */
    public function procesarYAsignarImagenes(Request $request, int $pedidoId, array $items): void
    {
        Log::info('[PedidoImagenesService] ðŸ“¸ Procesando imagenes en carpetas finales', [
            'pedido_id' => $pedidoId,
            'items_count' => count($items),
        ]);

        // Obtener pedido con relaciones
        $pedido = PedidoProduccion::with('prendas.procesos.tipoProceso', 'prendas.coloresTelas')
            ->findOrFail($pedidoId);
        $prendas = $pedido->prendas;

        foreach ($items as $itemIdx => $item) {
            if (!isset($prendas[$itemIdx])) {
                Log::warning('[PedidoImagenesService] Prenda no encontrada', ['prenda_idx' => $itemIdx]);
                continue;
            }

            $prenda = $prendas[$itemIdx];

            // ==================== PRENDAS ====================
            $this->procesarImagenesPrenda($request, $pedidoId, $itemIdx, $prenda);

            // ==================== TELAS ====================
            if (isset($item['telas']) && is_array($item['telas'])) {
                $this->procesarImagenesTelas($request, $pedidoId, $itemIdx, $item, $prenda);
            }

            // ==================== PROCESOS ====================
            if (isset($item['procesos']) && is_array($item['procesos'])) {
                $this->procesarImagenesProcesos($request, $pedidoId, $itemIdx, $item, $prenda);
            }
        }

        Log::info('[PedidoImagenesService] Todas las imagenes procesadas y asignadas');
    }

    /**
     * Procesar imagenes de prendas
     * 
     * @param Request $request
     * @param int $pedidoId
     * @param int $itemIdx
     * @param $prenda
     * @return void
     */
    private function procesarImagenesPrenda(Request $request, int $pedidoId, int $itemIdx, $prenda): void
    {
        $imgIdx = 0;
        while (true) {
            $formKey = "prendas.{$itemIdx}.imagenes.{$imgIdx}";
            if (!$request->hasFile($formKey)) {
                break;
            }

            $archivo = $request->file($formKey);
            $resultado = $this->imageUploadService->guardarImagenDirecta(
                $archivo,
                $pedidoId,
                'prendas',
                null,
                null
            );

            PrendaFotoPedido::create([
                'prenda_pedido_id' => $prenda->id,
                'ruta_original' => null,
                'ruta_webp' => $resultado['webp'],
                'orden' => $imgIdx + 1,
            ]);

            Log::debug('[PedidoImagenesService] ðŸ“¸ Imagen prenda guardada', [
                'prenda_id' => $prenda->id,
                'webp' => $resultado['webp'],
            ]);

            $imgIdx++;
        }
    }

    /**
     * Procesar imagenes de telas
     * 
     * @param Request $request
     * @param int $pedidoId
     * @param int $itemIdx
     * @param array $item
     * @param $prenda
     * @return void
     */
    private function procesarImagenesTelas(Request $request, int $pedidoId, int $itemIdx, array $item, $prenda): void
    {
        Log::info('[PedidoImagenesService] ðŸ§µ Procesando telas', [
            'prenda_id' => $prenda->id,
            'cantidad_telas' => count($item['telas']),
        ]);

        $telasRelacion = $prenda->coloresTelas()->get();

        foreach ($item['telas'] as $telaIdx => $tela) {
            if (!isset($telasRelacion[$telaIdx])) {
                Log::warning('[PedidoImagenesService] Tela no encontrada', ['tela_idx' => $telaIdx]);
                continue;
            }

            $telaRelacion = $telasRelacion[$telaIdx];
            $imgIdx = 0;

            while (true) {
                $formKey = "prendas.{$itemIdx}.telas.{$telaIdx}.imagenes.{$imgIdx}";
                if (!$request->hasFile($formKey)) {
                    break;
                }

                $archivo = $request->file($formKey);
                $resultado = $this->imageUploadService->guardarImagenDirecta(
                    $archivo,
                    $pedidoId,
                    'telas',
                    null,
                    null
                );

                PrendaFotoTelaPedido::create([
                    'prenda_pedido_colores_telas_id' => $telaRelacion->id,
                    'ruta_original' => null,
                    'ruta_webp' => $resultado['webp'],
                ]);

                Log::debug('[PedidoImagenesService] ðŸ“¸ Imagen tela guardada', [
                    'tela_id' => $telaRelacion->id,
                    'webp' => $resultado['webp'],
                ]);

                $imgIdx++;
            }
        }
    }

    /**
     * Procesar imagenes de procesos
     * 
     * @param Request $request
     * @param int $pedidoId
     * @param int $itemIdx
     * @param array $item
     * @param $prenda
     * @return void
     */
    private function procesarImagenesProcesos(Request $request, int $pedidoId, int $itemIdx, array $item, $prenda): void
    {
        Log::info('[PedidoImagenesService] âš™ï¸ Procesando procesos', [
            'prenda_id' => $prenda->id,
            'procesos_count' => count($item['procesos']),
        ]);

        foreach ($item['procesos'] as $procesoKey => $proceso) {
            $nombreProceso = $proceso['nombre'] ?? $procesoKey;
            $imgIdx = 0;

            while (true) {
                $formKey = "prendas.{$itemIdx}.procesos.{$procesoKey}.imagenes.{$imgIdx}";
                if (!$request->hasFile($formKey)) {
                    break;
                }

                $archivo = $request->file($formKey);
                $resultado = $this->imageUploadService->guardarImagenDirecta(
                    $archivo,
                    $pedidoId,
                    'procesos',
                    strtoupper($nombreProceso),
                    null
                );

                $procesoDetalleId = $this->obtenerProcesoDetalleId($prenda, $nombreProceso);

                if ($procesoDetalleId) {
                    PedidosProcessImagenes::create([
                        'proceso_prenda_detalle_id' => $procesoDetalleId,
                        'ruta_original' => null,
                        'ruta_webp' => $resultado['webp'],
                    ]);

                    Log::debug('[PedidoImagenesService] ðŸ“¸ Imagen proceso guardada', [
                        'proceso_nombre' => $nombreProceso,
                        'webp' => $resultado['webp'],
                    ]);
                }

                $imgIdx++;
            }
        }
    }

    /**
     * Obtener ID del detalle del proceso
     * 
     * @param $prenda
     * @param string $nombreProceso
     * @return int|null
     */
    private function obtenerProcesoDetalleId($prenda, string $nombreProceso): ?int
    {
        $proceso = $prenda->procesos()->whereHas('tipoProceso', function ($q) use ($nombreProceso) {
            $q->where('nombre', 'LIKE', $nombreProceso);
        })->first();

        return $proceso ? $proceso->id : null;
    }

    /**
     * Procesar y asignar EPPs al pedido
     * 
     * 1 archivo EPP = 1 webp en su carpeta final
     * Carpeta: pedidos/{id}/epp/
     * Crea registros en pedido_epp e pedido_epp_imagenes
     * 
     * @param Request $request
     * @param int $pedidoId
     * @param array $epps
     * @return void
     */
    public function procesarYAsignarEpps(Request $request, int $pedidoId, array $epps): void
    {
        Log::info('[PedidoImagenesService] Procesando EPPs', [
            'pedido_id' => $pedidoId,
            'epps_count' => count($epps),
        ]);

        foreach ($epps as $eppIdx => $eppData) {
            if (empty($eppData['epp_id'])) {
                Log::warning('[PedidoImagenesService] EPP sin epp_id', ['epp_idx' => $eppIdx]);
                continue;
            }

            $eppCatalogo = DB::table('epps')->where('id', $eppData['epp_id'])->first();

            if (!$eppCatalogo) {
                Log::warning('[PedidoImagenesService] EPP no encontrado en catalogo', [
                    'epp_id' => $eppData['epp_id'],
                ]);
                continue;
            }

            $pedidoEpp = PedidoEpp::create([
                'pedido_produccion_id' => $pedidoId,
                'epp_id' => $eppData['epp_id'],
                'cantidad' => $eppData['cantidad'] ?? 1,
                'observaciones' => $eppData['observaciones'] ?? null,
            ]);

            Log::info('[PedidoImagenesService] EPP creado', [
                'pedido_epp_id' => $pedidoEpp->id,
                'epp_id' => $eppData['epp_id'],
            ]);

            $this->procesarImagenesEpp($request, $pedidoId, $eppIdx, $eppData, $pedidoEpp);
        }

        Log::info('[PedidoImagenesService] Todos los EPPs procesados exitosamente');
    }

    /**
     * Procesar imagenes de un EPP especifico
     * 
     * @param Request $request
     * @param int $pedidoId
     * @param int $eppIdx
     * @param array $eppData
     * @param PedidoEpp $pedidoEpp
     * @return void
     */
    private function procesarImagenesEpp(Request $request, int $pedidoId, int $eppIdx, array $eppData, PedidoEpp $pedidoEpp): void
    {
        $imagenesGuardadas = 0;
        $imgIdx = 0;

        while (true) {
            // Laravel convierte puntos a underscores
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

                Log::debug('[PedidoImagenesService] ðŸ“¸ Imagen EPP guardada', [
                    'pedido_epp_id' => $pedidoEpp->id,
                    'webp' => $resultado['webp'],
                ]);

                $imgIdx++;
            } catch (\Exception $e) {
                Log::error('[PedidoImagenesService] Error procesando imagen EPP', [
                    'pedido_epp_id' => $pedidoEpp->id,
                    'form_key' => $formKey,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        if ($imagenesGuardadas === 0) {
            Log::warning('[PedidoImagenesService] EPP sin imagenes procesadas', [
                'pedido_epp_id' => $pedidoEpp->id,
                'epp_id' => $eppData['epp_id'],
            ]);
        }
    }

    /**
     * Validar que el JSON del frontend NO contiene objetos File
     * 
     * @param array $datos
     * @param string $ruta
     * @return void
     * @throws \Exception
     */
    public function validarJsonSinFiles(array $datos, string $ruta = ''): void
    {
        foreach ($datos as $key => $valor) {
            $rutaActual = $ruta ? "{$ruta}.{$key}" : $key;

            if (is_array($valor)) {
                $this->validarJsonSinFiles($valor, $rutaActual);
            }

            if (is_object($valor)) {
                throw new \Exception(
                    "JSON contiene objeto (posiblemente File) en ruta: {$rutaActual} - "
                    . "El frontend NO debe serializar objetos FileList, solo string URLs"
                );
            }

            if (str_contains($rutaActual, 'imagenes') && is_array($datos[$key] ?? null) && empty($datos[$key])) {
                Log::debug('[PedidoImagenesService] Array de imagenes vacio encontrado', [
                    'ruta' => $rutaActual,
                    'nota' => 'Esto es normal si el usuario no carga imagenes de este tipo',
                ]);
            }
        }
    }

    /**
     * Procesar imagenes de EPPs (metodo publico llamado desde UseCase)
     * 
     * @param Request $request
     * @param int $pedidoId
     * @param array $epps
     * @return void
     */
    public function procesarImagenesDeEpps($request, int $pedidoId, array $epps): void
    {
        Log::info('[PedidoImagenesService] Procesando imagenes de EPPs', [
            'pedido_id' => $pedidoId,
            'epps_count' => count($epps),
        ]);

        // OPCION A OPTIMIZATION: Batch query en lugar de N+1
        // 1 query: Traer TODOS los EPPs para este pedido, mapear por epp_id
        $eppIds = array_map(fn($e) => $e['epp_id'], $epps);
        $pedidosEppMapeados = PedidoEpp::where('pedido_produccion_id', $pedidoId)
            ->whereIn('epp_id', $eppIds)
            ->get()
            ->keyBy('epp_id'); // Map por epp_id para acceso O(1)

        foreach ($epps as $eppIdx => $eppData) {
            // Acceso en memoria O(1) - no DB query
            $pedidoEpp = $pedidosEppMapeados->get($eppData['epp_id']);

            if (!$pedidoEpp) {
                Log::warning('[PedidoImagenesService] EPP no encontrado para procesar imagenes', [
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
                    Log::debug('[PedidoImagenesService] Imagen EPP guardada', [
                        'pedido_epp_id' => $pedidoEpp->id,
                        'webp' => $resultado['webp'],
                        'orden' => $imgIdx + 1,
                    ]);

                    $imgIdx++;
                } catch (\Exception $e) {
                    Log::error('[PedidoImagenesService] Error procesando imagen EPP', [
                        'pedido_epp_id' => $pedidoEpp->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }

            // Copiar desde URLs existentes (modo edicion)
            if ($imagenesGuardadas === 0) {
                $imagenesJson = $eppData['imagenes'] ?? [];
                if (is_array($imagenesJson) && count($imagenesJson) > 0) {
                    $this->copiarImagenesEppDesdeUrls($pedidoEpp, $eppData, $imagenesJson, $pedidoId);
                }
            }
        }

        Log::info('[PedidoImagenesService] imagenes de EPPs procesadas', [
            'pedido_id' => $pedidoId,
        ]);
    }

    /**
     * Copiar imagenes de EPP desde URLs existentes (modo edicion)
     * 
     * @param PedidoEpp $pedidoEpp
     * @param array $eppData
     * @param array $imagenesJson
     * @param int $pedidoId
     * @return void
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
                Log::warning('[PedidoImagenesService] Imagen EPP no existe para copiar', [
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
                Log::error('[PedidoImagenesService] Error copiando imagen EPP', [
                    'pedido_epp_id' => $pedidoEpp->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $orden++;
        }
    }

    /**
     * Procesar imagenes por talla (delegacion a ProcesoImagenService)
     * 
     * @param Request $request
     * @param int $pedidoId
     * @param array $prendas
     * @return int
     */
    public function procesarImagenesPorTalla($request, int $pedidoId, array $prendas): int
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
     * Procesar imagenes de colores y actualizarlas en la BD
     * 
     * @param Request $request
     * @param int $pedidoId
     * @param array $prendas
     * @return void
     */
    public function procesarImagenesDeColores($request, int $pedidoId, array $prendas): void
    {
        $fotosColorFiles = $request->file('fotos_color') ?? [];
        $fotosColorMetaAll = $request->input('fotos_color_meta') ?? [];

        if (empty($fotosColorFiles)) {
            return;
        }

        $colorFotoServiceCrear = new \App\Infrastructure\Services\Pedidos\TelaFotoService();
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
                    Log::warning('[PedidoImagenesService] Error procesando imagen de color', [
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
                                // OPCION A OPTIMIZATION: Reemplazar triple whereHas() con JOINs directos
                                // whereHas() crea 3 JOINs implicitos - aca lo hacemos explicito y optimizado
                                DB::table('prenda_pedido_talla_color as pptc')
                                    ->join('prendas_pedido_talla as ppt', 'pptc.prenda_pedido_talla_id', '=', 'ppt.id')
                                    ->join('prendas_pedido as pp', 'ppt.prenda_pedido_id', '=', 'pp.id')
                                    ->where('pptc.color_nombre', $colorNombre)
                                    ->where('pp.pedido_produccion_id', $pedidoId)
                                    ->update(['pptc.imagen_ruta' => $fotoMeta['ruta_webp']]);

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
     * Procesar imagenes de procesos productivos
     * 
     * FASE 7 (Marzo 2026): Implementado para completar flujo de de gustos
     * 
     * Procesa imagenes asociadas a procesos (bordado, estampado, DTF, sublimado, etc)
     * Guarda referencias en tabla `pedidos_procesos_imagenes`
     * 
     * Estructura esperada en $procesos:
     * [
     *   {
     *     'tipo_proceso_id': 1,
     *     'tipo_proceso': 'bordado',
     *     'imagenes': [...formdata_keys...],
     *     'ubicaciones': [...]
     *   },
     *   ...
     * ]     * Procesar imagenes de nuevas prendas añadidas en modo actualizacion de borrador.
     * Usa el prefijo 'nuevas_prendas' en las claves FormData:
     *   - Prenda: nuevas_prendas.{i}.imagenes.{j}
     *   - Tela:   nuevas_prendas.{i}.telas.{k}.imagenes.{m}
     *
     * @param \Illuminate\Http\Request $request
     * @param array $nuevasPrendasIds Array of PrendaPedido IDs created
     * @param array $items Array of prenda item data (for telas structure)
     * @return void
     */
    public function procesarImagenesNuevasPrendas($request, array $nuevasPrendasIds, array $items): void
    {
        foreach ($nuevasPrendasIds as $i => $prendaId) {
            $prenda = \App\Models\PrendaPedido::with(['coloresTelas'])->find($prendaId);
            if (!$prenda) {
                continue;
            }

            // Prenda images
            $imgIdx = 0;
            while (true) {
                $formKey = "nuevas_prendas.{$i}.imagenes.{$imgIdx}";
                if (!$request->hasFile($formKey)) {
                    break;
                }
                $archivo = $request->file($formKey);
                $resultado = $this->imageUploadService->guardarImagenDirecta(
                    $archivo, $prenda->pedido_produccion_id, 'prendas', null, null
                );
                PrendaFotoPedido::create([
                    'prenda_pedido_id' => $prenda->id,
                    'ruta_original' => null,
                    'ruta_webp' => $resultado['webp'],
                    'orden' => $imgIdx + 1,
                ]);
                Log::debug('[PedidoImagenesService] Nueva prenda imagen guardada', ['prenda_id' => $prenda->id]);
                $imgIdx++;
            }

            // Tela images
            $telaRelaciones = $prenda->coloresTelas()->get();
            $telas = $items[$i]['telas'] ?? [];
            foreach ($telas as $telaIdx => $tela) {
                if (!isset($telaRelaciones[$telaIdx])) {
                    continue;
                }
                $telaRelacion = $telaRelaciones[$telaIdx];
                $imgIdx = 0;
                while (true) {
                    $formKey = "nuevas_prendas.{$i}.telas.{$telaIdx}.imagenes.{$imgIdx}";
                    if (!$request->hasFile($formKey)) {
                        break;
                    }
                    $archivo = $request->file($formKey);
                    $resultado = $this->imageUploadService->guardarImagenDirecta(
                        $archivo, $prenda->pedido_produccion_id, 'telas', null, null
                    );
                    PrendaFotoTelaPedido::create([
                        'prenda_pedido_colores_telas_id' => $telaRelacion->id,
                        'ruta_original' => null,
                        'ruta_webp' => $resultado['webp'],
                    ]);
                    Log::debug('[PedidoImagenesService] Nueva prenda tela imagen guardada', ['tela_id' => $telaRelacion->id]);
                    $imgIdx++;
                }
            }
        }
    }

    /**
     * Procesar imagenes de procesos productivos (fuera del flow principal de prendas)     * 
     * @param \Illuminate\Http\Request $request
     * @param int $pedidoId
     * @param array $procesos Array de procesos con imagenes
     * @param int $prendaIndex indice de la prenda en el array de prendas
     * @return void
     */
    public function procesarImagenesDeProcesos($request, int $pedidoId, array $procesos, int $prendaIndex): void
    {
        Log::info('[PedidoImagenesService] Procesando imagenes de procesos', [
            'pedido_id' => $pedidoId,
            'prenda_index' => $prendaIndex,
            'procesos_count' => count($procesos),
        ]);

        $procesosConImagenes = 0;

        foreach ($procesos as $procesoIdx => $procesoData) {
            $tipoProcesoId = $procesoData['tipo_proceso_id'] ?? null;
            $tipoProcesoNombre = $procesoData['tipo_proceso'] ?? 'desconocido';
            $imagenes = $procesoData['imagenes'] ?? [];
            $ubicaciones = $procesoData['ubicaciones'] ?? [];

            if (!$tipoProcesoId || empty($imagenes)) {
                Log::debug('[PedidoImagenesService] Proceso sin tipo o sin imagenes, saltando', [
                    'prenda_index' => $prendaIndex,
                    'proceso_index' => $procesoIdx,
                    'tipo_proceso' => $tipoProcesoNombre,
                ]);
                continue;
            }

            $imgIdx = 0;

            // Procesar archivos FormData
            while (true) {
                $formKey = "procesos_{$prendaIndex}_{$procesoIdx}_imagenes_{$imgIdx}";

                if (!$request->hasFile($formKey)) {
                    break;
                }

                try {
                    $archivo = $request->file($formKey);

                    // Guardar imagen con estructura: pedidos/{id}/procesos/{tipo_proceso}/
                    $resultado = $this->imageUploadService->guardarImagenDirecta(
                        $archivo,
                        $pedidoId,
                        'procesos',
                        $tipoProcesoNombre,
                        "proceso_{$tipoProcesoNombre}_{$imgIdx}"
                    );

                    // Crear registro en pedidos_procesos_imagenes
                    $imagenGuardada = PedidosProcessImagenes::create([
                        'pedido_id' => $pedidoId,
                        'prenda_index' => $prendaIndex,
                        'tipo_proceso_id' => $tipoProcesoId,
                        'tipo_proceso' => $tipoProcesoNombre,
                        'ruta_original' => $resultado['webp'],
                        'ruta_web' => $resultado['webp'],
                        'orden' => $imgIdx + 1,
                        'principal' => $imgIdx === 0 ? 1 : 0,
                        'ubicaciones' => $ubicaciones, // Si es JSON, Eloquent lo serializa
                    ]);

                    $procesosConImagenes++;
                    Log::debug('[PedidoImagenesService] Imagen de proceso guardada', [
                        'pedido_id' => $pedidoId,
                        'tipo_proceso' => $tipoProcesoNombre,
                        'webp' => $resultado['webp'],
                        'orden' => $imgIdx + 1,
                    ]);

                    $imgIdx++;
                } catch (\Exception $e) {
                    Log::error('[PedidoImagenesService] Error procesando imagen de proceso', [
                        'pedido_id' => $pedidoId,
                        'tipo_proceso' => $tipoProcesoNombre,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }
        }

        Log::info('[PedidoImagenesService] imagenes de procesos completadas', [
            'pedido_id' => $pedidoId,
            'procesos_con_imagenes' => $procesosConImagenes,
        ]);
    }
}


