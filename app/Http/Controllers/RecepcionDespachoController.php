<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class RecepcionDespachoController extends Controller
{
    /**
     * Mostrar la vista principal de recepción de prendas
     */
    public function index(): View
    {
        return view('recepcion-despacho.index');
    }

    /**
     * Obtener lista de prendas pendientes de recepción
     * Desde: consecutivos_recibos_pedidos donde area='Despacho' y estado='En Ejecución'
     */
    public function getItems(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 20);
            $page = (int) $request->input('page', 1);
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $statusFilter = strtolower((string) $request->input('status', 'todos'));

            // Obtener consecutivos únicos de COSTURA (que van a despacho)
            $consecutivos = \DB::table('consecutivos_recibos_pedidos as crp')
                ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
                ->join('pedidos_produccion as pedp', 'crp.pedido_produccion_id', '=', 'pedp.id')
                ->join('clientes as c', 'pedp.cliente_id', '=', 'c.id')
                ->select(
                    'crp.id',
                    'c.nombre as cliente',
                    'pp.nombre_prenda as prenda',
                    'pp.descripcion',
                    'crp.consecutivo_actual as recibo',
                    'pedp.numero_pedido as pedido',
                    'crp.fecha_llegada as fechaLlegada',
                    'crp.estado',
                    'pp.id as prenda_id'
                )
                ->where('crp.tipo_recibo', 'COSTURA')
                ->where('crp.area', 'DESPACHO');

            // Filtro por fecha de entrega (fuente principal: prenda_entregas; fallback: movimientos)
            if ($dateFrom) {
                $consecutivos = $consecutivos->whereRaw(
                    "COALESCE(
                        (SELECT pe.fecha_entrega
                         FROM prenda_entregas pe
                         WHERE pe.prenda_pedido_id = pp.id
                         ORDER BY pe.id DESC
                         LIMIT 1),
                        (SELECT pem.fecha_entrega
                         FROM prenda_entrega_movimientos pem
                         WHERE pem.consecutivo_recibo_id = crp.id
                         ORDER BY pem.id DESC
                         LIMIT 1)
                    ) >= ?",
                    [$dateFrom . ' 00:00:00']
                );
            }

            if ($dateTo) {
                $consecutivos = $consecutivos->whereRaw(
                    "COALESCE(
                        (SELECT pe.fecha_entrega
                         FROM prenda_entregas pe
                         WHERE pe.prenda_pedido_id = pp.id
                         ORDER BY pe.id DESC
                         LIMIT 1),
                        (SELECT pem.fecha_entrega
                         FROM prenda_entrega_movimientos pem
                         WHERE pem.consecutivo_recibo_id = crp.id
                         ORDER BY pem.id DESC
                         LIMIT 1)
                    ) <= ?",
                    [$dateTo . ' 23:59:59']
                );
            }

            if ($statusFilter === 'recibidos') {
                $consecutivos = $consecutivos->whereExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('prenda_entrega_movimientos as pem')
                        ->whereColumn('pem.consecutivo_recibo_id', 'crp.id')
                        ->whereRaw("LOWER(COALESCE(pem.estado, '')) = 'recibido'");
                });
            } elseif ($statusFilter === 'pendientes') {
                $consecutivos = $consecutivos->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('prenda_entrega_movimientos as pem')
                        ->whereColumn('pem.consecutivo_recibo_id', 'crp.id')
                        ->whereRaw("LOWER(COALESCE(pem.estado, '')) = 'recibido'");
                });
            }

            $consecutivos = $consecutivos
                ->distinct()
                ->orderBy('crp.fecha_llegada', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Construir estructura con tallas
            $items = [];
            foreach ($consecutivos->items() as $record) {
                // Obtener movimiento de entrega para este recibo (flujo parcial/recepcion)
                $movimiento = \DB::table('prenda_entrega_movimientos')
                    ->where('consecutivo_recibo_id', $record->id)
                    ->first();

                // Tallas a mostrar:
                // - Si hay detalle_tallas en movimiento, mostrar exactamente lo entregado (parcial o completo)
                // - Si no hay movimiento, mostrar tallas totales de la prenda
                $tallas = [];
                $detalleTallasMovimiento = [];
                if ($movimiento && !empty($movimiento->detalle_tallas)) {
                    $decoded = json_decode($movimiento->detalle_tallas, true);
                    if (is_array($decoded)) {
                        $detalleTallasMovimiento = collect($decoded)
                            ->map(function ($item) {
                                return [
                                    'talla' => (string) ($item['talla'] ?? ''),
                                    'cantidad' => (int) ($item['cantidad'] ?? 0),
                                ];
                            })
                            ->filter(fn($item) => $item['talla'] !== '' && $item['cantidad'] > 0)
                            ->values()
                            ->toArray();
                    }
                }

                if (!empty($detalleTallasMovimiento)) {
                    $tallas = $detalleTallasMovimiento;
                } else {
                    $tallas = \DB::table('prenda_pedido_tallas')
                        ->where('prenda_pedido_id', $record->prenda_id)
                        ->select('talla', 'cantidad')
                        ->get()
                        ->groupBy('talla')
                        ->map(function ($group) {
                            return [
                                'talla' => $group->first()->talla,
                                'cantidad' => (int) $group->sum('cantidad'),
                            ];
                        })
                        ->values()
                        ->toArray();
                }

                // Fallback: entrega completa puede haberse guardado solo en prenda_entregas
                $entregaResumen = \DB::table('prenda_entregas')
                    ->where('prenda_pedido_id', $record->prenda_id)
                    ->first();

                $fechaEntrega = null;
                if ($entregaResumen && $entregaResumen->fecha_entrega) {
                    $fechaEntrega = \Carbon\Carbon::parse($entregaResumen->fecha_entrega)->toIso8601String();
                } elseif ($movimiento && $movimiento->fecha_entrega) {
                    $fechaEntrega = \Carbon\Carbon::parse($movimiento->fecha_entrega)->toIso8601String();
                }

                $estado = 'pendiente';
                if ($movimiento && !empty($movimiento->estado)) {
                    $estado = strtolower((string) $movimiento->estado);
                }
                $fechaRecibido = $movimiento && $movimiento->fecha_recibido ? \Carbon\Carbon::parse($movimiento->fecha_recibido)->toIso8601String() : null;
                $cantidadEntregadaMovimiento = $movimiento ? (int) ($movimiento->cantidad_entregada ?? 0) : 0;
                $cantidadTotalPrenda = \DB::table('prenda_pedido_tallas')
                    ->where('prenda_pedido_id', $record->prenda_id)
                    ->sum('cantidad');
                $tipoEntrega = 'completo';
                if ($movimiento && $cantidadTotalPrenda > 0 && $cantidadEntregadaMovimiento > 0 && $cantidadEntregadaMovimiento < (int) $cantidadTotalPrenda) {
                    $tipoEntrega = 'parcial';
                }

                $items[] = [
                    'id' => (int) $record->id,
                    'cliente' => strtoupper($record->cliente),
                    'prenda' => $record->prenda,
                    'descripcion' => $record->descripcion ?? '',
                    'tallas' => $tallas,
                    'status' => $estado,
                    'pedido' => (string) $record->pedido,
                    'recibo' => (string) $record->recibo,
                    'fechaLlegada' => $record->fechaLlegada ? \Carbon\Carbon::parse($record->fechaLlegada)->toIso8601String() : null,
                    'fechaEntrega' => $fechaEntrega,
                    'fechaHora' => $fechaRecibido,
                    'tipoEntrega' => $tipoEntrega,
                ];
            }

            // Counts reales de recepcion, independientes del tab activo
            $countsBaseQuery = \DB::table('consecutivos_recibos_pedidos as crp')
                ->where('crp.tipo_recibo', 'COSTURA')
                ->where('crp.area', 'DESPACHO');

            if ($dateFrom) {
                $countsBaseQuery = $countsBaseQuery->whereRaw(
                    "COALESCE(
                        (SELECT pe.fecha_entrega
                         FROM prenda_entregas pe
                         WHERE pe.prenda_pedido_id = crp.prenda_id
                         ORDER BY pe.id DESC
                         LIMIT 1),
                        (SELECT pem.fecha_entrega
                         FROM prenda_entrega_movimientos pem
                         WHERE pem.consecutivo_recibo_id = crp.id
                         ORDER BY pem.id DESC
                         LIMIT 1)
                    ) >= ?",
                    [$dateFrom . ' 00:00:00']
                );
            }

            if ($dateTo) {
                $countsBaseQuery = $countsBaseQuery->whereRaw(
                    "COALESCE(
                        (SELECT pe.fecha_entrega
                         FROM prenda_entregas pe
                         WHERE pe.prenda_pedido_id = crp.prenda_id
                         ORDER BY pe.id DESC
                         LIMIT 1),
                        (SELECT pem.fecha_entrega
                         FROM prenda_entrega_movimientos pem
                         WHERE pem.consecutivo_recibo_id = crp.id
                         ORDER BY pem.id DESC
                         LIMIT 1)
                    ) <= ?",
                    [$dateTo . ' 23:59:59']
                );
            }

            $total = (int) (clone $countsBaseQuery)->count('crp.id');

            $totalRecibidos = (int) (clone $countsBaseQuery)
                ->whereExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('prenda_entrega_movimientos as pem')
                        ->whereColumn('pem.consecutivo_recibo_id', 'crp.id')
                        ->whereRaw("LOWER(COALESCE(pem.estado, '')) = 'recibido'");
                })
                ->count('crp.id');
            $totalPendientes = max(0, $total - $totalRecibidos);

            return response()->json([
                'data' => $items,
                'pagination' => [
                    'total' => $consecutivos->total(),
                    'per_page' => $consecutivos->perPage(),
                    'current_page' => $consecutivos->currentPage(),
                    'last_page' => $consecutivos->lastPage(),
                    'from' => $consecutivos->firstItem(),
                    'to' => $consecutivos->lastItem(),
                ],
                'counts' => [
                    'total' => $total,
                    'pendientes' => $totalPendientes,
                    'recibidos' => $totalRecibidos,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('[RecepcionDespacho] Error en getItems:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error al obtener prendas',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirmar recepción de una prenda
     * POST /api/recepcion-despacho/{id}/confirmar
     */
    public function confirmarRecepcion(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:recibido,pendiente',
                'fechaHora' => 'required|date',
                'tallas' => 'required|array',
            ]);

            // Obtener el consecutivo con sus detalles
            $consecutivo = \DB::table('consecutivos_recibos_pedidos as crp')
                ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
                ->select('crp.id', 'crp.prenda_id', 'pp.id as prenda_pedido_id')
                ->where('crp.id', $id)
                ->first();

            if (!$consecutivo) {
                return response()->json(['error' => 'Consecutivo no encontrado'], 404);
            }

            // Calcular cantidad total de prendas entregadas
            $cantidadTotal = collect($validated['tallas'])->sum(fn($talla) => $talla['cantidad'] ?? 0);

            // Actualizar o crear movimiento de entrega con confirmación de recepción
            $movimiento = \DB::table('prenda_entrega_movimientos')
                ->where('consecutivo_recibo_id', $consecutivo->id)
                ->first();

            if ($movimiento) {
                // Actualizar con datos de recepción
                \DB::table('prenda_entrega_movimientos')
                    ->where('id', $movimiento->id)
                    ->update([
                        'fecha_recibido' => \Carbon\Carbon::parse($validated['fechaHora']),
                        'usuario_recibido_id' => \Auth::id(),
                        'estado' => 'recibido',
                        'updated_at' => now(),
                    ]);
                $movimientoId = $movimiento->id;
            } else {
                $entregaResumen = \DB::table('prenda_entregas')
                    ->where('prenda_pedido_id', $consecutivo->prenda_pedido_id)
                    ->first();

                $fechaEntregaMovimiento = $entregaResumen && $entregaResumen->fecha_entrega
                    ? \Carbon\Carbon::parse($entregaResumen->fecha_entrega)
                    : \Carbon\Carbon::now();

                // Crear nuevo movimiento si no existe
                $movimientoId = \DB::table('prenda_entrega_movimientos')->insertGetId([
                    'prenda_pedido_id' => $consecutivo->prenda_pedido_id,
                    'consecutivo_recibo_id' => $consecutivo->id,
                    'cantidad_entregada' => $cantidadTotal,
                    'detalle_tallas' => json_encode($validated['tallas']),
                    'fecha_entrega' => $fechaEntregaMovimiento,
                    'usuario_id' => null,
                    'fecha_recibido' => \Carbon\Carbon::parse($validated['fechaHora']),
                    'usuario_recibido_id' => \Auth::id(),
                    'estado' => 'recibido',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Prenda recibida correctamente',
                'data' => [
                    'id' => $id,
                    'status' => $validated['status'],
                    'fechaHora' => $validated['fechaHora'],
                    'movimiento_id' => $movimientoId,
                ],
            ]);

        } catch (ValidationException $e) {
            \Log::warning('[RecepcionDespacho] Validacion en confirmarRecepcion:', [
                'errores' => $e->errors(),
            ]);

            return response()->json([
                'error' => 'Datos invalidos para confirmar recepcion',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('[RecepcionDespacho] Error en confirmarRecepcion:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error al guardar la confirmación',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
