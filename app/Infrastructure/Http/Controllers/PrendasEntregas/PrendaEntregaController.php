<?php

namespace App\Infrastructure\Http\Controllers\PrendasEntregas;

use App\Http\Controllers\Controller;
use App\Models\PrendaEntrega;
use App\Models\PrendaEntregaMovimiento;
use App\Models\PrendaPedido;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

final class PrendaEntregaController extends Controller
{
    public function toggleEntrega(Request $request, int $prendaPedidoId)
    {
        try {
            $validated = $request->validate([
                'entregado' => 'required|boolean',
                'consecutivo_recibo_id' => 'nullable|integer|min:1',
                'cantidad_entregada' => 'nullable|integer|min:1',
                'detalle_tallas' => 'nullable|array',
                'detalle_tallas.*.talla' => 'required_with:detalle_tallas|string',
                'detalle_tallas.*.cantidad' => 'required_with:detalle_tallas|integer|min:1',
                'detalle_tallas.*.genero' => 'nullable|string',
                'detalle_tallas.*.color_nombre' => 'nullable|string',
            ]);

            $prendaPedido = PrendaPedido::findOrFail($prendaPedidoId);
            $pedidoProduccionId = (int) $prendaPedido->pedido_produccion_id;
            $esPrendaDeBodega = (int) ($prendaPedido->de_bodega ?? 0) === 1;

            $recibosMovidosADespacho = 0;
            $recibosMovidosAEntrega = 0;
            $recibosProcesosBodegaMovidosADespacho = 0;
            $recibosProcesosBodegaMovidosAEntrega = 0;
            $recibosBaseBodegaMovidosADespacho = 0;
            $recibosBaseBodegaMovidosAEntrega = 0;
            $pedidoActualizadoADespacho = false;
            $entrega = null;

            // Nuevo flujo: entrega parcial por recibo.
            $consecutivoReciboId = isset($validated['consecutivo_recibo_id'])
                ? (int) $validated['consecutivo_recibo_id']
                : null;
            $cantidadEntregada = isset($validated['cantidad_entregada'])
                ? (int) $validated['cantidad_entregada']
                : 1;
            $detalleTallas = isset($validated['detalle_tallas']) && is_array($validated['detalle_tallas'])
                ? array_values($validated['detalle_tallas'])
                : [];

            if ($detalleTallas !== []) {
                $cantidadEntregada = (int) collect($detalleTallas)->sum(function ($item) {
                    return (int) ($item['cantidad'] ?? 0);
                });
            }

            $modoParcialSolicitado = (string) $request->input('modo', '') === 'parcial';

            if ($modoParcialSolicitado && !$consecutivoReciboId) {
                $recibosCosturaCandidatos = DB::table('consecutivos_recibos_pedidos')
                    ->where('prenda_id', $prendaPedidoId)
                    ->whereRaw("UPPER(TRIM(COALESCE(tipo_recibo, ''))) = 'COSTURA'")
                    ->where('activo', 1)
                    ->whereRaw("UPPER(COALESCE(estado, '')) <> 'ANULADO'")
                    ->orderByDesc('id')
                    ->get(['id', 'tipo_recibo', 'consecutivo_actual', 'activo', 'estado', 'area', 'prenda_id']);

                $consecutivoReciboId = (int) optional($recibosCosturaCandidatos->first())->id;
            }

            if ($consecutivoReciboId) {
                $estadoParcial = null;

                DB::transaction(function () use (
                    $validated,
                    $prendaPedidoId,
                    $pedidoProduccionId,
                    $consecutivoReciboId,
                    $cantidadEntregada,
                    $detalleTallas,
                    &$entrega,
                    &$estadoParcial,
                    &$pedidoActualizadoADespacho
                ) {
                    $recibo = DB::table('consecutivos_recibos_pedidos')
                        ->where('id', $consecutivoReciboId)
                        ->where('prenda_id', $prendaPedidoId)
                        ->where('activo', 1)
                        ->whereRaw("UPPER(COALESCE(estado, '')) <> 'ANULADO'")
                        ->first();

                    if (!$recibo) {
                        throw ValidationException::withMessages([
                            'consecutivo_recibo_id' => 'El recibo no existe para esta prenda o está inactivo.',
                        ]);
                    }

                    if ((bool) $validated['entregado'] === true) {
                        $resumenRecibo = $this->buildResumenEntregaRecibo($consecutivoReciboId, $prendaPedidoId);
                        if ($resumenRecibo['cantidad_restante'] <= 0) {
                            throw ValidationException::withMessages([
                                'consecutivo_recibo_id' => 'Este recibo ya fue entregado completamente.',
                            ]);
                        }

                        if ($cantidadEntregada > $resumenRecibo['cantidad_restante']) {
                            throw ValidationException::withMessages([
                                'cantidad_entregada' => 'La cantidad supera lo pendiente por entregar en el recibo.',
                            ]);
                        }

                        PrendaEntregaMovimiento::create([
                            'prenda_pedido_id' => $prendaPedidoId,
                            'consecutivo_recibo_id' => $consecutivoReciboId,
                            'cantidad_entregada' => $cantidadEntregada,
                            'detalle_tallas' => $detalleTallas !== [] ? $detalleTallas : null,
                            'fecha_entrega' => now(),
                            'usuario_id' => Auth::id(),
                        ]);

                        DB::table('consecutivos_recibos_pedidos')
                            ->where('id', $consecutivoReciboId)
                            ->update([
                                'area' => 'Despacho',
                                'updated_at' => now(),
                            ]);
                    } else {
                        $ultimoMovimiento = PrendaEntregaMovimiento::query()
                            ->where('prenda_pedido_id', $prendaPedidoId)
                            ->where('consecutivo_recibo_id', $consecutivoReciboId)
                            ->latest('id')
                            ->first();

                        if (!$ultimoMovimiento) {
                            throw ValidationException::withMessages([
                                'consecutivo_recibo_id' => 'No hay entregas parciales registradas para deshacer.',
                            ]);
                        }

                        $ultimoMovimiento->delete();

                        DB::table('consecutivos_recibos_pedidos')
                            ->where('id', $consecutivoReciboId)
                            ->update([
                                'area' => 'Entrega',
                                'updated_at' => now(),
                            ]);
                    }

                    $estadoParcial = $this->buildEstadoParcialPrenda($prendaPedidoId);

                    $entrega = PrendaEntrega::updateOrCreate(
                        ['prenda_pedido_id' => $prendaPedidoId],
                        [
                            'entregado' => $estadoParcial['completa'],
                            'fecha_entrega' => $estadoParcial['completa'] ? now() : null,
                            'usuario_id' => Auth::id(),
                        ]
                    );

                    if ($estadoParcial['completa']) {
                        $hayRecibosNoDespacho = DB::table('consecutivos_recibos_pedidos')
                            ->where('pedido_produccion_id', $pedidoProduccionId)
                            ->whereNotNull('prenda_id')
                            ->where('activo', 1)
                            ->whereRaw("UPPER(COALESCE(estado, '')) <> 'ANULADO'")
                            ->whereRaw("UPPER(TRIM(COALESCE(area, ''))) <> 'DESPACHO'")
                            ->exists();

                        $hayRecibosValidos = DB::table('consecutivos_recibos_pedidos')
                            ->where('pedido_produccion_id', $pedidoProduccionId)
                            ->whereNotNull('prenda_id')
                            ->where('activo', 1)
                            ->whereRaw("UPPER(COALESCE(estado, '')) <> 'ANULADO'")
                            ->exists();

                        if ($hayRecibosValidos && !$hayRecibosNoDespacho) {
                            $pedidoActualizadoADespacho = DB::table('pedidos_produccion')
                                ->where('id', $pedidoProduccionId)
                                ->update([
                                    'area' => 'Despacho',
                                    'updated_at' => now(),
                                ]) > 0;
                        }
                    }
                });

                Log::info('Estado de entrega parcial actualizado', [
                    'prenda_pedido_id' => $prendaPedidoId,
                    'entregado' => $validated['entregado'],
                    'consecutivo_recibo_id' => $consecutivoReciboId,
                    'cantidad_entregada' => $cantidadEntregada,
                    'usuario_id' => Auth::id(),
                    'pedido_actualizado_a_despacho' => $pedidoActualizadoADespacho,
                    'pedido_produccion_id' => $pedidoProduccionId,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => (bool) $validated['entregado']
                        ? 'Entrega parcial registrada correctamente'
                        : 'Entrega parcial revertida correctamente',
                    'data' => array_merge([
                        'entregado' => (bool) ($entrega->entregado ?? false),
                        'fecha_entrega' => $entrega?->fecha_entrega?->format('Y-m-d H:i:s'),
                        'usuario' => $entrega?->usuario?->name,
                        'pedido_actualizado_a_despacho' => $pedidoActualizadoADespacho,
                        'pedido_produccion_id' => $pedidoProduccionId,
                        'es_prenda_de_bodega' => $esPrendaDeBodega,
                    ], $this->buildEstadoParcialPrenda($prendaPedidoId)),
                ]);
            }

            DB::transaction(function () use (
                $request,
                $prendaPedidoId,
                $pedidoProduccionId,
                $esPrendaDeBodega,
                &$entrega,
                &$recibosMovidosADespacho,
                &$recibosMovidosAEntrega,
                &$recibosProcesosBodegaMovidosADespacho,
                &$recibosProcesosBodegaMovidosAEntrega,
                &$recibosBaseBodegaMovidosADespacho,
                &$recibosBaseBodegaMovidosAEntrega,
                &$pedidoActualizadoADespacho
            ) {
                $entrega = PrendaEntrega::firstOrNew([
                    'prenda_pedido_id' => $prendaPedidoId,
                ]);

                $entrega->entregado = $request->entregado;
                $entrega->fecha_entrega = $request->entregado ? now() : null;
                $entrega->usuario_id = Auth::id();
                $entrega->save();

                // Cuando se marca como entregada, mover recibos activos al area de despacho.
                if ((bool) $request->entregado === true) {
                    $recibosMovidosADespacho = DB::table('consecutivos_recibos_pedidos')
                        ->where('prenda_id', $prendaPedidoId)
                        ->where('activo', 1)
                        ->whereRaw("UPPER(COALESCE(estado, '')) <> 'ANULADO'")
                        ->whereRaw("UPPER(TRIM(COALESCE(area, ''))) <> 'DESPACHO'")
                        ->update([
                            'area' => 'Despacho',
                            'updated_at' => now(),
                        ]);

                    if ($esPrendaDeBodega) {
                        $tiposProcesoBodega = ['BORDADO', 'ESTAMPADO', 'DTF', 'SUBLIMADO', 'REFLECTIVO'];
                        $recibosProcesosBodegaMovidosADespacho = DB::table('consecutivos_recibos_pedidos')
                            ->where('prenda_id', $prendaPedidoId)
                            ->whereIn('tipo_recibo', $tiposProcesoBodega)
                            ->where('activo', 1)
                            ->whereRaw("UPPER(COALESCE(estado, '')) <> 'ANULADO'")
                            ->whereRaw("UPPER(TRIM(COALESCE(area, ''))) <> 'DESPACHO'")
                            ->update([
                                'area' => 'Despacho',
                                'updated_at' => now(),
                            ]);
                    }

                    // Caso especial bodega: cuando el pedido tiene una sola prenda activa,
                    // también mover el recibo base COSTURA-BODEGA que viene con prenda_id null.
                    $cantidadPrendasActivas = DB::table('prendas_pedido')
                        ->where('pedido_produccion_id', $pedidoProduccionId)
                        ->whereNull('deleted_at')
                        ->count();

                    if ($cantidadPrendasActivas === 1) {
                        $recibosBaseBodegaMovidosADespacho = DB::table('consecutivos_recibos_pedidos')
                            ->where('pedido_produccion_id', $pedidoProduccionId)
                            ->whereNull('prenda_id')
                            ->where('tipo_recibo', 'COSTURA-BODEGA')
                            ->where('activo', 1)
                            ->whereRaw("UPPER(COALESCE(estado, '')) <> 'ANULADO'")
                            ->whereRaw("UPPER(TRIM(COALESCE(area, ''))) <> 'DESPACHO'")
                            ->update([
                                'area' => 'Despacho',
                                'updated_at' => now(),
                            ]);
                    }

                    $hayRecibosNoDespacho = DB::table('consecutivos_recibos_pedidos')
                        ->where('pedido_produccion_id', $pedidoProduccionId)
                        ->whereNotNull('prenda_id')
                        ->where('activo', 1)
                        ->whereRaw("UPPER(COALESCE(estado, '')) <> 'ANULADO'")
                        ->whereRaw("UPPER(TRIM(COALESCE(area, ''))) <> 'DESPACHO'")
                        ->exists();

                    $hayRecibosValidos = DB::table('consecutivos_recibos_pedidos')
                        ->where('pedido_produccion_id', $pedidoProduccionId)
                        ->whereNotNull('prenda_id')
                        ->where('activo', 1)
                        ->whereRaw("UPPER(COALESCE(estado, '')) <> 'ANULADO'")
                        ->exists();

                    if ($hayRecibosValidos && !$hayRecibosNoDespacho) {
                        $pedidoActualizadoADespacho = DB::table('pedidos_produccion')
                            ->where('id', $pedidoProduccionId)
                            ->update([
                                'area' => 'Despacho',
                                'updated_at' => now(),
                            ]) > 0;
                    }
                } else {
                    // Cuando se desmarca, devolver recibos activos al area de entrega.
                    $recibosMovidosAEntrega = DB::table('consecutivos_recibos_pedidos')
                        ->where('prenda_id', $prendaPedidoId)
                        ->where('activo', 1)
                        ->whereRaw("UPPER(COALESCE(estado, '')) <> 'ANULADO'")
                        ->whereRaw("UPPER(TRIM(COALESCE(area, ''))) <> 'ENTREGA'")
                        ->update([
                            'area' => 'Entrega',
                            'updated_at' => now(),
                        ]);

                    if ($esPrendaDeBodega) {
                        $tiposProcesoBodega = ['BORDADO', 'ESTAMPADO', 'DTF', 'SUBLIMADO', 'REFLECTIVO'];
                        $recibosProcesosBodegaMovidosAEntrega = DB::table('consecutivos_recibos_pedidos')
                            ->where('prenda_id', $prendaPedidoId)
                            ->whereIn('tipo_recibo', $tiposProcesoBodega)
                            ->where('activo', 1)
                            ->whereRaw("UPPER(COALESCE(estado, '')) <> 'ANULADO'")
                            ->whereRaw("UPPER(TRIM(COALESCE(area, ''))) <> 'ENTREGA'")
                            ->update([
                                'area' => 'Entrega',
                                'updated_at' => now(),
                            ]);
                    }

                    // Caso especial bodega (pedido de una sola prenda): devolver base COSTURA-BODEGA.
                    $cantidadPrendasActivas = DB::table('prendas_pedido')
                        ->where('pedido_produccion_id', $pedidoProduccionId)
                        ->whereNull('deleted_at')
                        ->count();

                    if ($cantidadPrendasActivas === 1) {
                        $recibosBaseBodegaMovidosAEntrega = DB::table('consecutivos_recibos_pedidos')
                            ->where('pedido_produccion_id', $pedidoProduccionId)
                            ->whereNull('prenda_id')
                            ->where('tipo_recibo', 'COSTURA-BODEGA')
                            ->where('activo', 1)
                            ->whereRaw("UPPER(COALESCE(estado, '')) <> 'ANULADO'")
                            ->whereRaw("UPPER(TRIM(COALESCE(area, ''))) <> 'ENTREGA'")
                            ->update([
                                'area' => 'Entrega',
                                'updated_at' => now(),
                            ]);
                    }
                }
            });

            Log::info('Estado de entrega actualizado', [
                'prenda_pedido_id' => $prendaPedidoId,
                'entregado' => $validated['entregado'],
                'usuario_id' => Auth::id(),
                'fecha_entrega' => $entrega->fecha_entrega,
                'recibos_movidos_a_despacho' => $recibosMovidosADespacho,
                'recibos_movidos_a_entrega' => $recibosMovidosAEntrega,
                'recibos_procesos_bodega_movidos_a_despacho' => $recibosProcesosBodegaMovidosADespacho,
                'recibos_procesos_bodega_movidos_a_entrega' => $recibosProcesosBodegaMovidosAEntrega,
                'recibos_base_bodega_movidos_a_despacho' => $recibosBaseBodegaMovidosADespacho,
                'recibos_base_bodega_movidos_a_entrega' => $recibosBaseBodegaMovidosAEntrega,
                'pedido_actualizado_a_despacho' => $pedidoActualizadoADespacho,
                'pedido_produccion_id' => $pedidoProduccionId,
                'es_prenda_de_bodega' => $esPrendaDeBodega,
            ]);

            return response()->json([
                'success' => true,
                'message' => $validated['entregado']
                    ? 'Prenda marcada como entregada y recibos enviados a despacho'
                    : 'Prenda marcada como no entregada y recibos enviados a entrega',
                'data' => [
                    'entregado' => $entrega->entregado,
                    'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d H:i:s'),
                    'usuario' => $entrega->usuario?->name,
                    'recibos_movidos_a_despacho' => $recibosMovidosADespacho,
                    'recibos_movidos_a_entrega' => $recibosMovidosAEntrega,
                    'recibos_procesos_bodega_movidos_a_despacho' => $recibosProcesosBodegaMovidosADespacho,
                    'recibos_procesos_bodega_movidos_a_entrega' => $recibosProcesosBodegaMovidosAEntrega,
                    'recibos_base_bodega_movidos_a_despacho' => $recibosBaseBodegaMovidosADespacho,
                    'recibos_base_bodega_movidos_a_entrega' => $recibosBaseBodegaMovidosAEntrega,
                    'pedido_actualizado_a_despacho' => $pedidoActualizadoADespacho,
                    'pedido_produccion_id' => $pedidoProduccionId,
                    'es_prenda_de_bodega' => $esPrendaDeBodega,
                    'modo' => 'completo',
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos invalidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prenda no encontrada',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al actualizar estado de entrega', [
                'prenda_pedido_id' => $prendaPedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado de entrega',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function obtenerEstado(int $prendaPedidoId)
    {
        try {
            $entrega = PrendaEntrega::where('prenda_pedido_id', $prendaPedidoId)->first();

            if (!$entrega) {
                return response()->json([
                    'success' => true,
                    'data' => array_merge([
                        'entregado' => false,
                        'fecha_entrega' => null,
                        'usuario' => null,
                    ], $this->buildEstadoParcialPrenda($prendaPedidoId)),
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => array_merge([
                    'entregado' => $entrega->entregado,
                    'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d H:i:s'),
                    'usuario' => $entrega->usuario?->name,
                ], $this->buildEstadoParcialPrenda($prendaPedidoId)),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener estado de entrega', [
                'prenda_pedido_id' => $prendaPedidoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado de entrega',
            ], 500);
        }
    }

    public function obtenerMovimientos(int $prendaPedidoId)
    {
        try {
            $prenda = PrendaPedido::query()
                ->select(['id', 'nombre_prenda', 'pedido_produccion_id'])
                ->findOrFail($prendaPedidoId);

            $movimientosParciales = PrendaEntregaMovimiento::query()
                ->with([
                    'usuario:id,name',
                    'consecutivoRecibo:id,tipo_recibo,consecutivo_actual',
                ])
                ->where('prenda_pedido_id', $prendaPedidoId)
                ->orderByDesc('fecha_entrega')
                ->orderByDesc('id')
                ->get()
                ->map(function (PrendaEntregaMovimiento $movimiento) {
                    return [
                        'id' => $movimiento->id,
                        'cantidad_entregada' => (int) $movimiento->cantidad_entregada,
                        'fecha_entrega' => optional($movimiento->fecha_entrega)->format('Y-m-d H:i:s'),
                        'usuario' => $movimiento->usuario?->name,
                        'usuario_id' => $movimiento->usuario_id,
                        'consecutivo_recibo_id' => $movimiento->consecutivo_recibo_id,
                        'tipo_recibo' => $movimiento->consecutivoRecibo?->tipo_recibo,
                        'numero_recibo' => $movimiento->consecutivoRecibo?->consecutivo_actual,
                        'detalle_tallas' => is_array($movimiento->detalle_tallas)
                            ? array_values($movimiento->detalle_tallas)
                            : [],
                    ];
                })
                ->values()
                ->all();

            $movimientos = collect($movimientosParciales);

            // Incluir entrega completa (tabla prenda_entregas) para que el historial
            // no quede vacio cuando no hubo movimientos parciales por recibo.
            $entregaCompleta = PrendaEntrega::query()
                ->with('usuario:id,name')
                ->where('prenda_pedido_id', $prendaPedidoId)
                ->where('entregado', true)
                ->first();

            if ($entregaCompleta && $entregaCompleta->fecha_entrega) {
                $cantidadTotalPrenda = (int) DB::table('prenda_pedido_tallas')
                    ->where('prenda_pedido_id', $prendaPedidoId)
                    ->sum('cantidad');

                $detalleTallas = DB::table('prenda_pedido_tallas')
                    ->where('prenda_pedido_id', $prendaPedidoId)
                    ->orderBy('id')
                    ->get(['talla', 'cantidad'])
                    ->map(function ($item) {
                        return [
                            'talla' => (string) ($item->talla ?? 'Sin talla'),
                            'cantidad' => (int) ($item->cantidad ?? 0),
                        ];
                    })
                    ->values()
                    ->all();

                $movimientos->push([
                    'id' => 'completa-' . $entregaCompleta->id,
                    'cantidad_entregada' => $cantidadTotalPrenda,
                    'fecha_entrega' => optional($entregaCompleta->fecha_entrega)->format('Y-m-d H:i:s'),
                    'usuario' => $entregaCompleta->usuario?->name,
                    'usuario_id' => $entregaCompleta->usuario_id,
                    'consecutivo_recibo_id' => null,
                    'tipo_recibo' => 'ENTREGA COMPLETA',
                    'numero_recibo' => null,
                    'detalle_tallas' => $detalleTallas,
                    'origen' => 'prenda_entregas',
                ]);
            }

            $movimientos = $movimientos
                ->sortByDesc(function (array $movimiento) {
                    return strtotime((string) ($movimiento['fecha_entrega'] ?? '1970-01-01 00:00:00'));
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'prenda_id' => $prenda->id,
                    'prenda_nombre' => $prenda->nombre_prenda,
                    'pedido_produccion_id' => $prenda->pedido_produccion_id,
                    'total_movimientos' => $movimientos->count(),
                    'movimientos' => $movimientos,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prenda no encontrada',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al obtener movimientos de entrega parcial', [
                'prenda_pedido_id' => $prendaPedidoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial de entregas',
            ], 500);
        }
    }

    private function buildEstadoParcialPrenda(int $prendaPedidoId): array
    {
        static $tienePedidoParcialIdCache = null;
        $tienePedidoParcialId = $tienePedidoParcialIdCache ??= Schema::hasColumn('consecutivos_recibos_pedidos', 'pedido_parcial_id');

        $columns = ['id'];
        if ($tienePedidoParcialId) {
            $columns[] = 'pedido_parcial_id';
        }

        $recibos = DB::table('consecutivos_recibos_pedidos')
            ->where('prenda_id', $prendaPedidoId)
            ->where('activo', 1)
            ->whereRaw("UPPER(COALESCE(estado, '')) <> 'ANULADO'")
            ->get($columns);

        $totalRecibos = $recibos->count();
        if ($totalRecibos === 0) {
            return [
                'total_recibos' => 0,
                'recibos_entregados' => 0,
                'recibos_con_movimiento' => 0,
                'estado_entrega' => 'pendiente',
                'completa' => false,
            ];
        }

        $cantidadBasePrenda = (int) DB::table('prenda_pedido_tallas')
            ->where('prenda_pedido_id', $prendaPedidoId)
            ->sum('cantidad');

        $cantidadesEntregadas = DB::table('prenda_entrega_movimientos')
            ->where('prenda_pedido_id', $prendaPedidoId)
            ->select('consecutivo_recibo_id', DB::raw('SUM(cantidad_entregada) as total_entregado'))
            ->groupBy('consecutivo_recibo_id')
            ->pluck('total_entregado', 'consecutivo_recibo_id');

        $cantidadesParciales = collect();
        if ($tienePedidoParcialId) {
            $pedidoParcialIds = $recibos
                ->pluck('pedido_parcial_id')
                ->filter()
                ->unique()
                ->values();

            if ($pedidoParcialIds->isNotEmpty()) {
                $cantidadesParciales = DB::table('pedidos_parciales_tallas')
                    ->whereIn('pedido_parcial_id', $pedidoParcialIds)
                    ->select('pedido_parcial_id', DB::raw('SUM(cantidad) as total_cantidad'))
                    ->groupBy('pedido_parcial_id')
                    ->pluck('total_cantidad', 'pedido_parcial_id');
            }
        }

        $recibosEntregados = 0;
        $recibosConMovimiento = 0;

        foreach ($recibos as $recibo) {
            $pedidoParcialId = $tienePedidoParcialId ? ($recibo->pedido_parcial_id ?? null) : null;
            $cantidadTotal = $pedidoParcialId
                ? (int) ($cantidadesParciales[$pedidoParcialId] ?? 0)
                : $cantidadBasePrenda;
            $cantidadEntregada = (int) ($cantidadesEntregadas[$recibo->id] ?? 0);

            if ($cantidadEntregada > 0) {
                $recibosConMovimiento++;
            }

            if ($cantidadTotal > 0 && $cantidadEntregada >= $cantidadTotal) {
                $recibosEntregados++;
            }
        }

        $completa = $totalRecibos > 0 && $recibosEntregados >= $totalRecibos;
        $estado = 'pendiente';
        if ($completa) {
            $estado = 'completo';
        } elseif ($recibosConMovimiento > 0) {
            $estado = 'parcial';
        }

        return [
            'total_recibos' => (int) $totalRecibos,
            'recibos_entregados' => (int) $recibosEntregados,
            'recibos_con_movimiento' => (int) $recibosConMovimiento,
            'estado_entrega' => $estado,
            'completa' => $completa,
        ];
    }

    private function buildResumenEntregaRecibo(int $consecutivoReciboId, int $prendaPedidoId): array
    {
        static $tienePedidoParcialIdCache = null;
        $tienePedidoParcialId = $tienePedidoParcialIdCache ??= Schema::hasColumn('consecutivos_recibos_pedidos', 'pedido_parcial_id');

        $columns = ['id'];
        if ($tienePedidoParcialId) {
            $columns[] = 'pedido_parcial_id';
        }

        $recibo = DB::table('consecutivos_recibos_pedidos')
            ->where('id', $consecutivoReciboId)
            ->first($columns);

        if (!$recibo) {
            return [
                'cantidad_total' => 0,
                'cantidad_entregada' => 0,
                'cantidad_restante' => 0,
                'completo' => false,
            ];
        }

        $pedidoParcialId = $tienePedidoParcialId ? ($recibo->pedido_parcial_id ?? null) : null;

        $cantidadTotal = $pedidoParcialId
            ? (int) DB::table('pedidos_parciales_tallas')
                ->where('pedido_parcial_id', $pedidoParcialId)
                ->sum('cantidad')
            : (int) DB::table('prenda_pedido_tallas')
                ->where('prenda_pedido_id', $prendaPedidoId)
                ->sum('cantidad');

        $cantidadEntregada = (int) PrendaEntregaMovimiento::query()
            ->where('prenda_pedido_id', $prendaPedidoId)
            ->where('consecutivo_recibo_id', $consecutivoReciboId)
            ->sum('cantidad_entregada');

        $cantidadRestante = max($cantidadTotal - $cantidadEntregada, 0);

        return [
            'cantidad_total' => $cantidadTotal,
            'cantidad_entregada' => $cantidadEntregada,
            'cantidad_restante' => $cantidadRestante,
            'completo' => $cantidadTotal > 0 && $cantidadEntregada >= $cantidadTotal,
        ];
    }
}
