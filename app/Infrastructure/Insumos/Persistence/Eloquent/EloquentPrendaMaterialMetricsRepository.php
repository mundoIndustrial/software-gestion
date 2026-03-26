<?php

namespace App\Infrastructure\Insumos\Persistence\Eloquent;

use App\Domain\Insumos\Repositories\PrendaMaterialMetricsRepository;
use App\Models\PedidoAnchoGeneral;
use App\Models\PedidoMetrajeColor;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedidoColorTela;
use App\Models\PrendaPedidoTalla;

class EloquentPrendaMaterialMetricsRepository implements PrendaMaterialMetricsRepository
{
    public function obtenerAnchoMetrajePrenda(string $numeroPedido, int $prendaId): array
    {
        try {
            $pedido = $this->resolverPedidoPorNumeroOId($numeroPedido);

            $anchoGeneral = PedidoAnchoGeneral::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId)
                ->latest()
                ->first();

            $metrajesPorColor = PedidoMetrajeColor::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId)
                ->get();

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
            $pedido = $this->resolverPedidoPorNumeroOId($numeroPedido);
            $tipoModoNuevo = $datos['tipo_modo'] ?? 'normal';

            $anchoExistente = PedidoAnchoGeneral::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId)
                ->first();

            $metrajeExistente = PedidoMetrajeColor::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId)
                ->first();

            $tipoModoExistente = null;
            if ($anchoExistente && $anchoExistente->tipo_modo) {
                $tipoModoExistente = $anchoExistente->tipo_modo;
            } elseif ($metrajeExistente && $metrajeExistente->tipo_modo) {
                $tipoModoExistente = $metrajeExistente->tipo_modo;
            }

            if ($tipoModoExistente && $tipoModoNuevo !== $tipoModoExistente) {
                PedidoAnchoGeneral::where('pedido_produccion_id', $pedido->id)
                    ->where('prenda_pedido_id', $prendaId)
                    ->delete();

                PedidoMetrajeColor::where('pedido_produccion_id', $pedido->id)
                    ->where('prenda_pedido_id', $prendaId)
                    ->delete();

                \Log::info("Limpieza automatica de datos anteriores: pedido {$pedido->numero_pedido}, prenda {$prendaId}, cambio de {$tipoModoExistente} a {$tipoModoNuevo}");
            }

            if (empty($datos['color'])) {
                PedidoAnchoGeneral::updateOrCreate(
                    [
                        'pedido_produccion_id' => $pedido->id,
                        'prenda_pedido_id' => $prendaId,
                    ],
                    [
                        'ancho' => $datos['ancho'] ?? null,
                        'metraje' => $datos['metraje'] ?? null,
                        'contenido_mano' => $datos['contenido_mano'] ?? null,
                        'tipo_modo' => $tipoModoNuevo,
                    ]
                );
            }

            if (!empty($datos['color']) && ($datos['metraje'] ?? null) !== null) {
                PedidoMetrajeColor::updateOrCreate(
                    [
                        'pedido_produccion_id' => $pedido->id,
                        'prenda_pedido_id' => $prendaId,
                        'color' => $datos['color'],
                    ],
                    [
                        'metraje' => $datos['metraje'],
                        'ancho' => $datos['ancho'] ?? null,
                        'tipo_modo' => $tipoModoNuevo,
                    ]
                );
            }

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

