<?php

namespace App\Domain\Pedidos\Services;

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

/**
 * Service: Gestión de Imágenes de Pedidos
 * 
 * FASE 4 - Consolidación de procesamiento de imágenes
 * Responsabilidades:
 * - Procesar y asignar imágenes de prendas, telas, procesos
 * - Procesar y asignar imágenes de EPPs
 * - Crear estructura de carpetas para pedidos
 * - Validar archivos JSON sin objetos File
 * 
 * Patrón: Domain Service (lógica de negocio, sin HTTP)
 * 
 * @package App\Domain\Pedidos\Services
 */
class PedidoImagenesService
{
    public function __construct(
        private ImageUploadService $imageUploadService,
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
     * Procesar y asignar imágenes directamente a carpetas finales
     * 
     * 1 archivo = 1 webp en su carpeta final
     * NO temp, NO relocalización
     * Carpetas específicas por tipo:
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
        Log::info('[PedidoImagenesService] 📸 Procesando imágenes en carpetas finales', [
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

        Log::info('[PedidoImagenesService] Todas las imágenes procesadas y asignadas');
    }

    /**
     * Procesar imágenes de prendas
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
                'ruta_original' => $resultado['original'],
                'ruta_webp' => $resultado['webp'],
                'orden' => $imgIdx + 1,
            ]);

            Log::debug('[PedidoImagenesService] 📸 Imagen prenda guardada', [
                'prenda_id' => $prenda->id,
                'webp' => $resultado['webp'],
            ]);

            $imgIdx++;
        }
    }

    /**
     * Procesar imágenes de telas
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
        Log::info('[PedidoImagenesService] 🧵 Procesando telas', [
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
                    'ruta_original' => $resultado['original'],
                    'ruta_webp' => $resultado['webp'],
                ]);

                Log::debug('[PedidoImagenesService] 📸 Imagen tela guardada', [
                    'tela_id' => $telaRelacion->id,
                    'webp' => $resultado['webp'],
                ]);

                $imgIdx++;
            }
        }
    }

    /**
     * Procesar imágenes de procesos
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
        Log::info('[PedidoImagenesService] ⚙️ Procesando procesos', [
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
                        'ruta_original' => $resultado['original'],
                        'ruta_webp' => $resultado['webp'],
                    ]);

                    Log::debug('[PedidoImagenesService] 📸 Imagen proceso guardada', [
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
                Log::warning('[PedidoImagenesService] EPP no encontrado en catálogo', [
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
     * Procesar imágenes de un EPP específico
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

                Log::debug('[PedidoImagenesService] 📸 Imagen EPP guardada', [
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
            Log::warning('[PedidoImagenesService] EPP sin imágenes procesadas', [
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
                Log::debug('[PedidoImagenesService] Array de imágenes vacío encontrado', [
                    'ruta' => $rutaActual,
                    'nota' => 'Esto es normal si el usuario no cargó imágenes de este tipo',
                ]);
            }
        }
    }
}
