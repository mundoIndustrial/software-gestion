<?php

namespace App\Infrastructure\Http\Controllers\PrendasEntregas;

use App\Http\Controllers\Controller;
use App\Models\PrendaEntrega;
use App\Models\PrendaPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PrendaEntregaController extends Controller
{
    public function toggleEntrega(Request $request, int $prendaPedidoId)
    {
        try {
            $request->validate([
                'entregado' => 'required|boolean',
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
                'entregado' => $request->entregado,
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
                'message' => $request->entregado
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
                    'data' => [
                        'entregado' => false,
                        'fecha_entrega' => null,
                        'usuario' => null,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'entregado' => $entrega->entregado,
                    'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d H:i:s'),
                    'usuario' => $entrega->usuario?->name,
                ],
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
}
