<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Domain\SupervisorPedidos\Repositories\OrderRepository;
use App\Domain\SupervisorPedidos\ValueObjects\OrderId;
use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsRequest;
use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsResponse;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetOrderDetailsUseCase
{
    private OrderRepository $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function execute(GetOrderDetailsRequest $request): GetOrderDetailsResponse
    {
        try {
            $orderId = new OrderId($request->getOrderId());
            
            // Validar que la orden existe
            $order = $this->orderRepository->findById($orderId);
            if (!$order) {
                throw new \RuntimeException("Pedido #{$request->getOrderId()} no encontrado");
            }

            // Obtener modelo Eloquent con relaciones (para la respuesta)
            $pedido = PedidoProduccion::with([
                'asesora', 
                'prendas',
                'prendas.fotos',
                'prendas.fotosTelas',
                'cotizacion.tipoCotizacion'
            ])->findOrFail($orderId->value());

            // Obtener estadísticas
            $totalCantidad = DB::table('prenda_pedido_tallas')
                ->join('prendas_pedido', 'prenda_pedido_tallas.prenda_pedido_id', '=', 'prendas_pedido.id')
                ->where('prendas_pedido.pedido_produccion_id', $pedido->id)
                ->sum('prenda_pedido_tallas.cantidad');

            $totalEntregado = ($pedido->estado === 'Entregado') ? $totalCantidad : 0;

            // Preparar respuesta
            $ordenArray = $pedido->toArray();
            $ordenArray['total_cantidad'] = $totalCantidad;
            $ordenArray['total_entregado'] = $totalEntregado;
            $ordenArray['es_cotizacion'] = !empty($pedido->cotizacion_id);

            // Agregar nombres
            if ($pedido->asesora) {
                $ordenArray['asesor'] = $pedido->asesora->name ?? '';
                $ordenArray['asesora'] = $pedido->asesora->name ?? '';
                $ordenArray['asesora_nombre'] = $pedido->asesora->name ?? '';
            } else {
                $ordenArray['asesor'] = '';
                $ordenArray['asesora'] = '';
                $ordenArray['asesora_nombre'] = '';
            }

            if (!empty($ordenArray['cliente'])) {
                $ordenArray['cliente_nombre'] = $ordenArray['cliente'];
            }

            // Construir descripción con tallas
            $ordenArray['descripcion_prendas'] = $this->buildDescripcionConTallas($pedido);

            // Obtener prendas formateadas para el modal
            $prendasFormato = $this->formatPrendas($pedido);
            if (!empty($prendasFormato)) {
                $ordenArray['prendas'] = $prendasFormato;
            }

            Log::info('Detalles de orden obtenidos', [
                'order_id' => $orderId->value(),
                'order_number' => $pedido->numero_pedido,
            ]);

            return new GetOrderDetailsResponse($ordenArray);

        } catch (\Exception $e) {
            Log::error('Error en GetOrderDetails: ' . $e->getMessage());
            throw $e;
        }
    }

    private function formatPrendas($pedido): array
    {
        $prendasFormato = [];

        if (!$pedido->prendas || $pedido->prendas->count() === 0) {
            return $prendasFormato;
        }

        foreach ($pedido->prendas as $index => $prenda) {
            $colorNombre = null;
            $telaNombre = null;
            $telaReferencia = null;
            $tipoMangaNombre = null;
            $tipoBrocheNombre = null;

            try {
                if ($prenda->color_id) {
                    $color = \App\Models\ColorPrenda::find($prenda->color_id);
                    $colorNombre = $color ? $color->nombre : null;
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo color', ['error' => $e->getMessage()]);
            }

            try {
                if ($prenda->tela_id) {
                    $tela = \App\Models\TelaPrenda::find($prenda->tela_id);
                    if ($tela) {
                        $telaNombre = $tela->nombre;
                        $telaReferencia = $tela->referencia;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo tela', ['error' => $e->getMessage()]);
            }

            try {
                if ($prenda->tipo_manga_id) {
                    $tipoManga = \App\Models\TipoManga::find($prenda->tipo_manga_id);
                    $tipoMangaNombre = $tipoManga ? $tipoManga->nombre : null;
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo manga', ['error' => $e->getMessage()]);
            }

            try {
                if ($prenda->tipo_broche_id) {
                    $tipoBroche = \App\Models\TipoBrocheBoton::find($prenda->tipo_broche_id);
                    $tipoBrocheNombre = $tipoBroche ? $tipoBroche->nombre : null;
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo broche', ['error' => $e->getMessage()]);
            }

            $prendasFormato[] = [
                'numero' => $index + 1,
                'nombre' => $prenda->nombre_prenda ?? '-',
                'descripcion' => $prenda->descripcion ?? '-',
                'descripcion_variaciones' => $prenda->descripcion_variaciones ?? '',
                'cantidad_talla' => $prenda->cantidad_talla ?? '-',
                'color' => $colorNombre,
                'tela' => $telaNombre,
                'tela_referencia' => $telaReferencia,
                'tipo_manga' => $tipoMangaNombre,
                'tipo_broche' => $tipoBrocheNombre,
                'tiene_bolsillos' => $prenda->tiene_bolsillos ?? 0,
                'tiene_reflectivo' => $prenda->tiene_reflectivo ?? 0,
                'id' => $prenda->id,
                'prenda_pedido_id' => $prenda->id,
                'fotos' => $this->formatPrendaFotos($prenda),
                'tela_fotos' => $this->formatTelaFotos($prenda),
            ];
        }

        return $prendasFormato;
    }

    private function formatPrendaFotos($prenda): array
    {
        $fotos = [];

        if (!$prenda->fotos || $prenda->fotos->count() === 0) {
            return $fotos;
        }

        foreach ($prenda->fotos as $foto) {
            $ruta = $foto->ruta_webp ?? $foto->ruta_original;
            if (!$ruta) continue;

            $ruta = str_replace('\\', '/', $ruta);
            if (str_starts_with($ruta, 'http')) {
                $fotos[] = $ruta;
                continue;
            }
            if (str_starts_with($ruta, '/storage/')) {
                $fotos[] = $ruta;
                continue;
            }
            if (str_starts_with($ruta, 'storage/')) {
                $fotos[] = '/' . $ruta;
                continue;
            }
            $fotos[] = '/storage/' . ltrim($ruta, '/');
        }

        return array_values(array_filter(array_unique($fotos)));
    }

    private function formatTelaFotos($prenda): array
    {
        $imagenes = [];

        // 1) Si hay imágenes guardadas en prenda_pedido_talla_colores (modo talla-color)
        try {
            $tallaColorImgs = DB::table('prenda_pedido_talla_colores as ptc')
                ->join('prenda_pedido_tallas as pt', 'ptc.prenda_pedido_talla_id', '=', 'pt.id')
                ->where('pt.prenda_pedido_id', $prenda->id)
                ->whereNotNull('ptc.imagen_ruta')
                ->pluck('ptc.imagen_ruta')
                ->toArray();

            foreach ($tallaColorImgs as $ruta) {
                if (!$ruta) continue;
                $ruta = str_replace('\\', '/', $ruta);
                if (!str_starts_with($ruta, '/storage/')) {
                    if (str_starts_with($ruta, 'storage/')) {
                        $ruta = '/' . $ruta;
                    } elseif (!str_starts_with($ruta, '/')) {
                        $ruta = '/storage/' . $ruta;
                    }
                }
                $imagenes[] = $ruta;
            }
        } catch (\Exception $e) {
            Log::warning('Error obteniendo fotos de talla-color', ['error' => $e->getMessage()]);
        }

        // 2) Fallback: fotos de tela por relación
        if (count($imagenes) === 0) {
            try {
                $fotosTelaDB = DB::table('prenda_fotos_tela_pedido')
                    ->join('prenda_pedido_colores_telas', 'prenda_fotos_tela_pedido.prenda_pedido_colores_telas_id', '=', 'prenda_pedido_colores_telas.id')
                    ->where('prenda_pedido_colores_telas.prenda_pedido_id', $prenda->id)
                    ->orderBy('prenda_fotos_tela_pedido.orden', 'asc')
                    ->get(['prenda_fotos_tela_pedido.ruta_webp', 'prenda_fotos_tela_pedido.ruta_original']);

                foreach ($fotosTelaDB as $fotoTela) {
                    $ruta = $fotoTela->ruta_webp ?? $fotoTela->ruta_original;
                    if (!$ruta) continue;
                    $ruta = str_replace('\\', '/', $ruta);
                    if (str_starts_with($ruta, 'http')) {
                        $imagenes[] = $ruta;
                        continue;
                    }
                    if (!str_starts_with($ruta, '/storage/')) {
                        if (str_starts_with($ruta, 'storage/')) {
                            $ruta = '/' . $ruta;
                        } elseif (!str_starts_with($ruta, '/')) {
                            $ruta = '/storage/' . $ruta;
                        }
                    }
                    $imagenes[] = $ruta;
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo fotos de tela', ['error' => $e->getMessage()]);
            }
        }

        return array_values(array_filter(array_unique($imagenes)));
    }

    private function buildDescripcionConTallas($order): string
    {
        if (!$order->prendas || $order->prendas->isEmpty()) {
            return '';
        }

        $totalPrendas = $order->prendas->count();
        $descripciones = $order->prendas->map(function($prenda, $index) use ($totalPrendas) {
            $base = $prenda->generarDescripcionDetallada($index + 1, $totalPrendas);

            // Adjuntar observaciones por tallas de PROCESOS cuando aplique
            try {
                $procesos = DB::table('pedidos_procesos_prenda_detalles as ppd')
                    ->join('tipos_procesos as tp', 'ppd.tipo_proceso_id', '=', 'tp.id')
                    ->whereNull('ppd.deleted_at')
                    ->where('ppd.prenda_pedido_id', $prenda->id)
                    ->orderBy('ppd.id', 'asc')
                    ->get([
                        'ppd.id',
                        'ppd.modo_tallas',
                        'ppd.ubicaciones',
                        'ppd.observaciones as observaciones_generales',
                        'tp.nombre as tipo_proceso_nombre',
                    ]);

                $lineasProc = [];
                foreach ($procesos as $proc) {
                    $modo = $proc->modo_tallas ?? 'generico';
                    $tipoProcesoNombre = $proc->tipo_proceso_nombre ?? 'PROCESO';

                    if ($modo === 'general') {
                        $tallasObs = DB::table('pedidos_procesos_prenda_tallas')
                            ->where('proceso_prenda_detalle_id', $proc->id)
                            ->whereNotNull('observaciones')
                            ->where('observaciones', '!=', '')
                            ->orderBy('genero', 'asc')
                            ->orderBy('talla', 'asc')
                            ->get(['genero', 'talla', 'observaciones']);

                        if ($tallasObs->count() > 0) {
                            $lineasProc[] = "\nOBSERVACIONES POR TALLA - " . strtoupper($tipoProcesoNombre) . ":";
                            foreach ($tallasObs as $row) {
                                $genero = strtoupper((string) $row->genero);
                                $talla = $row->talla !== null ? (string) $row->talla : 'SOBREMEDIDA';
                                $obs = trim((string) $row->observaciones);
                                if ($obs === '') {
                                    continue;
                                }
                                $lineasProc[] = "  [{$genero} {$talla}] {$obs}";
                            }
                        }
                    } elseif ($modo === 'especifico') {
                        // Para modo específico, acceder a archivo .ubicaciones
                        if (!empty($proc->ubicaciones)) {
                            $lineasProc[] = "\n" . strtoupper($tipoProcesoNombre) . ": " . $proc->ubicaciones;
                        }
                    }
                }

                if (!empty($lineasProc)) {
                    $base = $base . "\n" . implode("\n", $lineasProc);
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo procesos', ['error' => $e->getMessage()]);
            }

            return $base;
        });

        return implode("\n" . str_repeat("─", 60) . "\n", $descripciones->toArray());
    }
}

