<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

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

            if ($dateFrom) {
                $consecutivos = $consecutivos->where('crp.fecha_llegada', '>=', $dateFrom . ' 00:00:00');
            }

            if ($dateTo) {
                $consecutivos = $consecutivos->where('crp.fecha_llegada', '<=', $dateTo . ' 23:59:59');
            }

            $consecutivos = $consecutivos
                ->distinct()
                ->orderBy('crp.fecha_llegada', 'asc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Construir estructura con tallas
            $items = [];
            foreach ($consecutivos->items() as $record) {
                // Obtener tallas para esta prenda
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

                $items[] = [
                    'id' => (int) $record->id,
                    'cliente' => strtoupper($record->cliente),
                    'prenda' => $record->prenda,
                    'descripcion' => $record->descripcion ?? '',
                    'tallas' => $tallas,
                    'status' => 'pendiente',
                    'pedido' => (string) $record->pedido,
                    'recibo' => (string) $record->recibo,
                    'fechaLlegada' => $record->fechaLlegada ? \Carbon\Carbon::parse($record->fechaLlegada)->toIso8601String() : null,
                    'fechaHora' => null,
                ];
            }

            // Count totals from all items (not paginated)
            $allConsecutivos = \DB::table('consecutivos_recibos_pedidos as crp')
                ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
                ->where('crp.tipo_recibo', 'COSTURA')
                ->where('crp.area', 'DESPACHO')
                ->get();

            $totalRecibidos = $allConsecutivos->where('estado', 'Recibido')->count();
            $totalPendientes = $allConsecutivos->count() - $totalRecibidos;

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
                    'total' => $allConsecutivos->count(),
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
        $validated = $request->validate([
            'status' => 'required|string|in:recibido,pendiente',
            'fechaHora' => 'required|date_format:Y-m-d\TH:i:s.000\Z',
        ]);

        // TODO: Implementar lógica para actualizar estado de la prenda
        // Guardar en base de datos:
        // - ID de la prenda
        // - Status a "recibido"
        // - Fecha y hora exacta de confirmación
        // - Usuario que confirmó

        return response()->json([
            'success' => true,
            'message' => 'Prenda recibida correctamente',
            'data' => [
                'id' => $id,
                'status' => $validated['status'],
                'fechaHora' => $validated['fechaHora'],
            ],
        ]);
    }
}
