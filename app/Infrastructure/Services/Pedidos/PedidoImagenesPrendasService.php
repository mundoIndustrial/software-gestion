<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Application\Services\ImageUploadService;
use App\Models\PedidoProduccion;
use App\Models\PedidosProcessImagenes;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PedidoImagenesPrendasService
{
    public function __construct(
        private ImageUploadService $imageUploadService,
    ) {}

    public function procesarYAsignarImagenes(Request $request, int $pedidoId, array $items): void
    {
        Log::info('[PedidoImagenesService] ¸ Procesando imagenes en carpetas finales', [
            'pedido_id' => $pedidoId,
            'items_count' => count($items),
        ]);

        $pedido = PedidoProduccion::with('prendas.procesos.tipoProceso', 'prendas.coloresTelas')
            ->findOrFail($pedidoId);
        $prendas = $pedido->prendas;

        foreach ($items as $itemIdx => $item) {
            if (!isset($prendas[$itemIdx])) {
                Log::warning('[PedidoImagenesService] Prenda no encontrada', ['prenda_idx' => $itemIdx]);
                continue;
            }

            $prenda = $prendas[$itemIdx];
            $this->procesarImagenesPrenda($request, $pedidoId, $itemIdx, $prenda);

            if (isset($item['telas']) && is_array($item['telas'])) {
                $this->procesarImagenesTelas($request, $pedidoId, $itemIdx, $item, $prenda);
            }

            if (isset($item['procesos']) && is_array($item['procesos'])) {
                $this->procesarImagenesProcesos($request, $pedidoId, $itemIdx, $item, $prenda);
            }
        }

        Log::info('[PedidoImagenesService] Todas las imagenes procesadas y asignadas');
    }

    public function procesarImagenesPrenda(Request $request, int $pedidoId, int $itemIdx, $prenda): void
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

            Log::debug('[PedidoImagenesService] ¸ Imagen prenda guardada', [
                'prenda_id' => $prenda->id,
                'webp' => $resultado['webp'],
            ]);

            $imgIdx++;
        }
    }

    public function procesarImagenesNuevasPrendas($request, array $nuevasPrendasIds, array $items): void
    {
        foreach ($nuevasPrendasIds as $i => $prendaId) {
            $prenda = \App\Models\PrendaPedido::with(['coloresTelas', 'procesos.tipoProceso'])->find($prendaId);
            if (!$prenda) {
                continue;
            }

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

            $procesos = $items[$i]['procesos'] ?? [];
            if (is_array($procesos)) {
                foreach ($procesos as $procesoKey => $procesoData) {
                    $procesoClave = is_numeric($procesoKey)
                        ? (string) ($procesoData['tipo'] ?? $procesoData['nombre'] ?? '')
                        : (string) $procesoKey;

                    if ($procesoClave === '') {
                        continue;
                    }

                    $procesoDetalleId = $this->obtenerProcesoDetalleIdPorClave($prenda, $procesoClave);
                    if (!$procesoDetalleId) {
                        Log::warning('[PedidoImagenesService] Proceso de nueva prenda no encontrado para guardar imagenes', [
                            'prenda_id' => $prenda->id,
                            'proceso_clave' => $procesoClave,
                        ]);
                        continue;
                    }

                    $imgIdx = 0;
                    while (true) {
                        $formKey = "nuevas_prendas.{$i}.procesos.{$procesoClave}.imagenes.{$imgIdx}";
                        if (!$request->hasFile($formKey)) {
                            break;
                        }

                        $archivo = $request->file($formKey);
                        $resultado = $this->imageUploadService->guardarImagenDirecta(
                            $archivo,
                            $prenda->pedido_produccion_id,
                            'procesos',
                            strtoupper($procesoClave),
                            null
                        );

                        PedidosProcessImagenes::create([
                            'proceso_prenda_detalle_id' => $procesoDetalleId,
                            'ruta_original' => null,
                            'ruta_webp' => $resultado['webp'],
                            'orden' => $imgIdx,
                            'es_principal' => $imgIdx === 0,
                        ]);

                        Log::debug('[PedidoImagenesService] Nueva prenda proceso imagen guardada', [
                            'prenda_id' => $prenda->id,
                            'proceso_detalle_id' => $procesoDetalleId,
                            'proceso_clave' => $procesoClave,
                        ]);

                        $imgIdx++;
                    }
                }
            }
        }
    }

    public function procesarImagenesDeProcesos($request, int $pedidoId, array $procesos, int $prendaIndex, int $prendaId = 0): void
    {
        Log::info('[PedidoImagenesService] Procesando imagenes de procesos', [
            'pedido_id' => $pedidoId,
            'prenda_index' => $prendaIndex,
            'prenda_id' => $prendaId,
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

            // Buscar el proceso_prenda_detalle_id usando prenda_id y tipo_proceso_id
            $procesoDetalle = $prendaId > 0 
                ? \DB::table('pedidos_procesos_prenda_detalles')
                    ->where('prenda_pedido_id', $prendaId)
                    ->where('tipo_proceso_id', $tipoProcesoId)
                    ->first(['id'])
                : null;

            $procesoPrendaDetalleId = $procesoDetalle?->id ?? null;

            if (!$procesoPrendaDetalleId && $prendaId > 0) {
                Log::warning('[PedidoImagenesService] No se encontró proceso_prenda_detalle para prenda', [
                    'prenda_id' => $prendaId,
                    'tipo_proceso_id' => $tipoProcesoId,
                    'tipo_proceso' => $tipoProcesoNombre,
                ]);
            }

            $imgIdx = 0;
            while (true) {
                $formKey = "procesos_{$prendaIndex}_{$procesoIdx}_imagenes_{$imgIdx}";
                if (!$request->hasFile($formKey)) {
                    break;
                }

                try {
                    $archivo = $request->file($formKey);
                    $resultado = $this->imageUploadService->guardarImagenDirecta(
                        $archivo,
                        $pedidoId,
                        'procesos',
                        $tipoProcesoNombre,
                        "proceso_{$tipoProcesoNombre}_{$imgIdx}"
                    );

                    $createData = [
                        'ruta_original' => $resultado['webp'],
                        'ruta_webp' => $resultado['webp'],
                        'orden' => $imgIdx + 1,
                        'es_principal' => ($imgIdx === 0 ? 1 : 0),
                    ];

                    // Incluir proceso_prenda_detalle_id si está disponible
                    if ($procesoPrendaDetalleId) {
                        $createData['proceso_prenda_detalle_id'] = $procesoPrendaDetalleId;
                    }

                    PedidosProcessImagenes::create($createData);

                    $procesosConImagenes++;
                    Log::debug('[PedidoImagenesService] Imagen de proceso guardada', [
                        'pedido_id' => $pedidoId,
                        'prenda_id' => $prendaId,
                        'tipo_proceso' => $tipoProcesoNombre,
                        'webp' => $resultado['webp'],
                        'orden' => $imgIdx + 1,
                        'proceso_prenda_detalle_id' => $procesoPrendaDetalleId,
                    ]);

                    $imgIdx++;
                } catch (\Exception $e) {
                    Log::error('[PedidoImagenesService] Error procesando imagen de proceso', [
                        'pedido_id' => $pedidoId,
                        'prenda_id' => $prendaId,
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

    private function procesarImagenesTelas(Request $request, int $pedidoId, int $itemIdx, array $item, $prenda): void
    {
        Log::info('[PedidoImagenesService]  Procesando telas', [
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

                Log::debug('[PedidoImagenesService] ¸ Imagen tela guardada', [
                    'tela_id' => $telaRelacion->id,
                    'webp' => $resultado['webp'],
                ]);

                $imgIdx++;
            }
        }
    }

    private function procesarImagenesProcesos(Request $request, int $pedidoId, int $itemIdx, array $item, $prenda): void
    {
        Log::info('[PedidoImagenesService]  Procesando procesos', [
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
                }

                $imgIdx++;
            }
        }
    }

    private function obtenerProcesoDetalleId($prenda, string $nombreProceso): ?int
    {
        $proceso = $prenda->procesos()->whereHas('tipoProceso', function ($q) use ($nombreProceso) {
            $q->where('nombre', 'LIKE', $nombreProceso);
        })->first();

        return $proceso ? $proceso->id : null;
    }

    private function obtenerProcesoDetalleIdPorClave($prenda, string $procesoClave): ?int
    {
        $clave = mb_strtolower(trim($procesoClave));
        if ($clave === '') {
            return null;
        }

        $proceso = $prenda->procesos->first(function ($proc) use ($clave) {
            $slug = mb_strtolower((string) ($proc->tipoProceso->slug ?? ''));
            $nombre = mb_strtolower((string) ($proc->tipoProceso->nombre ?? ''));
            return $slug === $clave || $nombre === $clave;
        });

        return $proceso?->id;
    }
}
