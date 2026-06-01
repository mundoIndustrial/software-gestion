<?php

namespace App\Infrastructure\Insumos\Persistence\Eloquent;

use App\Domain\Insumos\Repositories\PrendaMaterialMetricsRepository;
use App\Models\PedidoAnchoGeneral;
use App\Models\PedidoMetrajeColor;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedidoColorTela;
use App\Models\PrendaPedidoTalla;
use Illuminate\Support\Facades\Schema;

class EloquentPrendaMaterialMetricsRepository implements PrendaMaterialMetricsRepository
{
    public function obtenerAnchoMetrajePrenda(string $numeroPedido, int $prendaId, ?int $numeroRecibo = null): array
    {
        try {
            $pedido = $this->resolverPedidoPorNumeroOId($numeroPedido);

            $anchoQuery = PedidoAnchoGeneral::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId);
            $metrajeQuery = PedidoMetrajeColor::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId);

            if (($numeroRecibo ?? 0) > 0) {
                $anchoQuery->where('numero_recibo', $numeroRecibo);
                $metrajeQuery->where('numero_recibo', $numeroRecibo);
            }

            $anchoGeneral = $anchoQuery->latest()->first();
            $metrajesPorColor = $metrajeQuery->get();

            $tipoModoGuardado = null;
            if ($anchoGeneral && $anchoGeneral->tipo_modo) {
                $tipoModoGuardado = $anchoGeneral->tipo_modo;
            } elseif ($metrajesPorColor->isNotEmpty()) {
                $tipoModoGuardado = $metrajesPorColor->first()->tipo_modo;
            }

            $data = [];
            foreach ($metrajesPorColor as $metraje) {
                $data[] = [
                    'color' => $metraje->color,
                    'metraje' => $metraje->metraje,
                    'talla' => null,
                ];
            }

            return [
                'success' => true,
                'ancho' => $anchoGeneral ? $anchoGeneral->ancho : null,
                'ancho_general' => $anchoGeneral ? $anchoGeneral->ancho : null,
                'metraje' => $anchoGeneral ? $anchoGeneral->metraje : null,
                'contenido_mano' => $anchoGeneral ? $anchoGeneral->contenido_mano : null,
                'data' => $data,
                'tipo_modo' => $tipoModoGuardado,
            ];
        } catch (\Exception $e) {
            \Log::error('Error al obtener ancho/metraje: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al obtener ancho/metraje',
            ];
        }
    }

    public function guardarAnchoMetrajePrenda(string $numeroPedido, int $prendaId, array $datos): array
    {
        try {
            // Temporalmente desactivado: este flujo debe resolver por numero_recibo.
            $usarConsecutivoReciboId = false;
            $anchoTieneConsecutivoReciboId = $usarConsecutivoReciboId && Schema::hasColumn('pedido_ancho_general', 'consecutivo_recibo_id');
            $metrajeTieneConsecutivoReciboId = $usarConsecutivoReciboId && Schema::hasColumn('pedido_metraje_color', 'consecutivo_recibo_id');
            $tipoModoNuevo = $datos['tipo_modo'] ?? 'normal';
            $prendaBodegaId = isset($datos['prenda_bodega_id']) ? (int) $datos['prenda_bodega_id'] : null;
            $numeroRecibo = isset($datos['numero_recibo']) ? (int) $datos['numero_recibo'] : null;
            $consecutivoReciboId = isset($datos['consecutivo_recibo_id']) ? (int) $datos['consecutivo_recibo_id'] : null;
            $esBodega = strtoupper((string) ($datos['tipo_recibo'] ?? '')) === 'CORTE-PARA-BODEGA' || ($prendaBodegaId ?? 0) > 0;
            $pedidoId = null;
            \Log::info('[PrendaMaterialMetricsRepository] guardar inicio', [
                'numero_pedido' => $numeroPedido,
                'prenda_id' => $prendaId,
                'prenda_referencia' => $esBodega ? 'prenda_bodega_id' : 'prenda_pedido_id',
                'pedido_id' => $pedidoId,
                'prenda_bodega_id' => $prendaBodegaId,
                'numero_recibo' => $numeroRecibo,
                'consecutivo_recibo_id' => $consecutivoReciboId,
                'tipo_recibo' => $datos['tipo_recibo'] ?? null,
                'tipo_modo' => $tipoModoNuevo,
                'color' => $datos['color'] ?? null,
            ]);

            if (!$esBodega) {
                $pedido = $this->resolverPedidoPorNumeroOId($numeroPedido);
                $pedidoId = (int) $pedido->id;
            } else {
                // En CORTE-PARA-BODEGA NO dependemos de pedido_produccion_id para evitar colisiones
                // con el índice único legacy (pedido_produccion_id, prenda_pedido_id).
                $pedidoId = null;
            }

            $anchoExistenteQuery = PedidoAnchoGeneral::query();
            $metrajeExistenteQuery = PedidoMetrajeColor::query();
            if ($pedidoId === null) {
                $anchoExistenteQuery->whereNull('pedido_produccion_id');
                $metrajeExistenteQuery->whereNull('pedido_produccion_id');
            } else {
                $anchoExistenteQuery->where('pedido_produccion_id', $pedidoId);
                $metrajeExistenteQuery->where('pedido_produccion_id', $pedidoId);
            }
            if ($esBodega && ($prendaBodegaId ?? 0) > 0) {
                $anchoExistenteQuery->where('prenda_bodega_id', $prendaBodegaId);
                $metrajeExistenteQuery->where('prenda_bodega_id', $prendaBodegaId);
            } else {
                $anchoExistenteQuery->where('prenda_pedido_id', $prendaId);
                $metrajeExistenteQuery->where('prenda_pedido_id', $prendaId);
            }
            if (($numeroRecibo ?? 0) > 0) {
                $anchoExistenteQuery->where('numero_recibo', $numeroRecibo);
                $metrajeExistenteQuery->where('numero_recibo', $numeroRecibo);
            }
            $anchoExistente = $anchoExistenteQuery->first();

            $metrajeExistente = $metrajeExistenteQuery->first();

            $tipoModoExistente = null;
            if ($anchoExistente && $anchoExistente->tipo_modo) {
                $tipoModoExistente = $anchoExistente->tipo_modo;
            } elseif ($metrajeExistente && $metrajeExistente->tipo_modo) {
                $tipoModoExistente = $metrajeExistente->tipo_modo;
            }

            if ($tipoModoExistente && $tipoModoNuevo !== $tipoModoExistente) {
                $deleteAnchoQuery = PedidoAnchoGeneral::query();
                $deleteMetrajeQuery = PedidoMetrajeColor::query();
                if ($pedidoId === null) {
                    $deleteAnchoQuery->whereNull('pedido_produccion_id');
                    $deleteMetrajeQuery->whereNull('pedido_produccion_id');
                } else {
                    $deleteAnchoQuery->where('pedido_produccion_id', $pedidoId);
                    $deleteMetrajeQuery->where('pedido_produccion_id', $pedidoId);
                }
                if ($esBodega && ($prendaBodegaId ?? 0) > 0) {
                    $deleteAnchoQuery->where('prenda_bodega_id', $prendaBodegaId);
                    $deleteMetrajeQuery->where('prenda_bodega_id', $prendaBodegaId);
                } else {
                    $deleteAnchoQuery->where('prenda_pedido_id', $prendaId);
                    $deleteMetrajeQuery->where('prenda_pedido_id', $prendaId);
                }
                if (($numeroRecibo ?? 0) > 0) {
                    $deleteAnchoQuery->where('numero_recibo', $numeroRecibo);
                    $deleteMetrajeQuery->where('numero_recibo', $numeroRecibo);
                }
                $deleteAnchoQuery->delete();

                $deleteMetrajeQuery->delete();

                \Log::info("Limpieza automatica de datos anteriores: pedido {$numeroPedido}, prenda {$prendaId}, cambio de {$tipoModoExistente} a {$tipoModoNuevo}");
            }

            if (empty($datos['color'])) {
                $match = [
                    'pedido_produccion_id' => $pedidoId,
                ];
                if ($esBodega && ($prendaBodegaId ?? 0) > 0) {
                    $match['prenda_bodega_id'] = $prendaBodegaId;
                } else {
                    $match['prenda_pedido_id'] = $prendaId;
                }
                if (($numeroRecibo ?? 0) > 0) {
                    $match['numero_recibo'] = $numeroRecibo;
                }
                if ($anchoTieneConsecutivoReciboId && $esBodega && ($consecutivoReciboId ?? 0) > 0) {
                    $match['consecutivo_recibo_id'] = $consecutivoReciboId;
                }
                \Log::info('[PrendaMaterialMetricsRepository] upsert ancho_general', [
                    'match' => $match,
                    'numero_recibo' => $numeroRecibo,
                    'consecutivo_recibo_id' => $consecutivoReciboId,
                ]);
                PedidoAnchoGeneral::updateOrCreate(
                    $match,
                    [
                        'prenda_pedido_id' => ($esBodega && ($prendaBodegaId ?? 0) > 0) ? null : ($prendaId ?: null),
                        'prenda_bodega_id' => ($esBodega && ($prendaBodegaId ?? 0) > 0) ? $prendaBodegaId : null,
                        'numero_recibo' => $numeroRecibo,
                        'ancho' => $datos['ancho'] ?? null,
                        'metraje' => $datos['metraje'] ?? null,
                        'contenido_mano' => $datos['contenido_mano'] ?? null,
                        'tipo_modo' => $tipoModoNuevo,
                    ]
                );
            }

            if (!empty($datos['color']) && ($datos['metraje'] ?? null) !== null) {
                $match = [
                    'pedido_produccion_id' => $pedidoId,
                    'color' => $datos['color'],
                ];
                if ($esBodega && ($prendaBodegaId ?? 0) > 0) {
                    $match['prenda_bodega_id'] = $prendaBodegaId;
                } else {
                    $match['prenda_pedido_id'] = $prendaId;
                }
                if (($numeroRecibo ?? 0) > 0) {
                    $match['numero_recibo'] = $numeroRecibo;
                }
                if ($metrajeTieneConsecutivoReciboId && $esBodega && ($consecutivoReciboId ?? 0) > 0) {
                    $match['consecutivo_recibo_id'] = $consecutivoReciboId;
                }
                \Log::info('[PrendaMaterialMetricsRepository] upsert metraje_color', [
                    'match' => $match,
                    'numero_recibo' => $numeroRecibo,
                    'consecutivo_recibo_id' => $consecutivoReciboId,
                ]);
                PedidoMetrajeColor::updateOrCreate(
                    $match,
                    [
                        'prenda_pedido_id' => ($esBodega && ($prendaBodegaId ?? 0) > 0) ? null : ($prendaId ?: null),
                        'prenda_bodega_id' => ($esBodega && ($prendaBodegaId ?? 0) > 0) ? $prendaBodegaId : null,
                        'numero_recibo' => $numeroRecibo,
                        'metraje' => $datos['metraje'],
                        'tipo_modo' => $tipoModoNuevo,
                    ]
                );
            }

            $debugAncho = PedidoAnchoGeneral::query();
            $debugMetraje = PedidoMetrajeColor::query();
            if ($pedidoId === null) {
                $debugAncho->whereNull('pedido_produccion_id');
                $debugMetraje->whereNull('pedido_produccion_id');
            } else {
                $debugAncho->where('pedido_produccion_id', $pedidoId);
                $debugMetraje->where('pedido_produccion_id', $pedidoId);
            }
            if ($esBodega && ($prendaBodegaId ?? 0) > 0) {
                $debugAncho->where('prenda_bodega_id', $prendaBodegaId);
                $debugMetraje->where('prenda_bodega_id', $prendaBodegaId);
            } else {
                $debugAncho->where('prenda_pedido_id', $prendaId);
                $debugMetraje->where('prenda_pedido_id', $prendaId);
            }
            if (($numeroRecibo ?? 0) > 0) {
                $debugAncho->where('numero_recibo', $numeroRecibo);
                $debugMetraje->where('numero_recibo', $numeroRecibo);
            }
            if ($anchoTieneConsecutivoReciboId && $esBodega && ($consecutivoReciboId ?? 0) > 0) {
                $debugAncho->where('consecutivo_recibo_id', $consecutivoReciboId);
            }
            if ($metrajeTieneConsecutivoReciboId && $esBodega && ($consecutivoReciboId ?? 0) > 0) {
                $debugMetraje->where('consecutivo_recibo_id', $consecutivoReciboId);
            }
            $debugAncho = $debugAncho->orderByDesc('id')->first();
            $debugMetraje = $debugMetraje->orderByDesc('id')->first();
            \Log::info('[PrendaMaterialMetricsRepository] guardado resultado', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'prenda_referencia' => $esBodega ? 'prenda_bodega_id' : 'prenda_pedido_id',
                'prenda_bodega_id' => $prendaBodegaId,
                'numero_recibo_esperado' => $numeroRecibo,
                'ancho_general_id' => $debugAncho?->id,
                'ancho_general_numero_recibo' => $debugAncho?->numero_recibo,
                'ancho_general_consecutivo_recibo_id' => $debugAncho?->consecutivo_recibo_id,
                'metraje_color_id' => $debugMetraje?->id,
                'metraje_color_numero_recibo' => $debugMetraje?->numero_recibo,
                'metraje_color_consecutivo_recibo_id' => $debugMetraje?->consecutivo_recibo_id,
            ]);

            return [
                'success' => true,
                'message' => 'Ancho y metraje guardados correctamente',
            ];
        } catch (\Exception $e) {
            \Log::error('Error al guardar ancho/metraje: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al guardar ancho/metraje',
            ];
        }
    }

    public function eliminarAnchoMetrajePrenda(string $numeroPedido, int $prendaId): array
    {
        try {
            $pedido = $this->resolverPedidoPorNumeroOId($numeroPedido);

            PedidoAnchoGeneral::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId)
                ->delete();

            PedidoMetrajeColor::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId)
                ->delete();

            return [
                'success' => true,
                'message' => 'Ancho y metraje eliminados correctamente',
            ];
        } catch (\Exception $e) {
            \Log::error('Error al eliminar ancho/metraje: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al eliminar ancho/metraje',
            ];
        }
    }

    public function obtenerColoresPrenda(string $numeroPedido, int $prendaId): array
    {
        try {
            $this->resolverPedidoPorNumeroOId($numeroPedido);

            $tallasConColores = PrendaPedidoTalla::where('prenda_pedido_id', $prendaId)
                ->with('coloresAsignados')
                ->get();

            $coloresUnicos = [];
            $tallasProcesadas = 0;

            foreach ($tallasConColores as $talla) {
                if ($talla->coloresAsignados && count($talla->coloresAsignados) > 0) {
                    $tallasProcesadas++;

                    foreach ($talla->coloresAsignados as $colorAsignado) {
                        $colorNombre = $colorAsignado->color_nombre;
                        $colorKey = strtolower($colorNombre);
                        if (!isset($coloresUnicos[$colorKey])) {
                            $coloresUnicos[$colorKey] = [
                                'nombre' => $colorNombre,
                                'color' => ['nombre' => $colorNombre],
                            ];
                        }
                    }
                }
            }

            if ($tallasProcesadas > 0 && count($coloresUnicos) > 0) {
                return [
                    'success' => true,
                    'tipo' => 'talla_color',
                    'data' => array_values($coloresUnicos),
                    'esMatriz' => true,
                ];
            }

            $coloresTelas = PrendaPedidoColorTela::with(['color', 'tela'])
                ->where('prenda_pedido_id', $prendaId)
                ->get();

            if ($coloresTelas->count() > 0) {
                $coloresFormatted = $coloresTelas->map(function ($ct) {
                    return [
                        'nombre' => $ct->color ? $ct->color->nombre : $ct->color_id,
                        'color' => [
                            'nombre' => $ct->color ? $ct->color->nombre : $ct->color_id,
                        ],
                    ];
                })->unique(fn($item) => $item['nombre'])->values();

                return [
                    'success' => true,
                    'tipo' => 'piezas',
                    'data' => $coloresFormatted,
                    'esMatriz' => false,
                ];
            }

            return [
                'success' => true,
                'tipo' => 'normal',
                'data' => [],
                'esMatriz' => false,
            ];
        } catch (\Exception $e) {
            \Log::error('Error al obtener colores: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al obtener colores',
            ];
        }
    }

    private function resolverPedidoPorNumeroOId(string $numeroPedido): PedidoProduccion
    {
        if (ctype_digit($numeroPedido)) {
            $pedidoPorId = PedidoProduccion::find((int) $numeroPedido);
            if ($pedidoPorId) {
                return $pedidoPorId;
            }
        }

        return PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
    }
}
