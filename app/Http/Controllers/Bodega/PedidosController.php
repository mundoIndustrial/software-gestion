<?php

namespace App\Http\Controllers\Bodega;

use App\Http\Controllers\Controller;
use App\Models\ReciboPrenda;
use App\Models\PrendaPedido;
use App\Models\PedidoEpp;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PedidosController extends Controller
{
    /**
     * Mostrar lista de pedidos para bodeguero
     */
    public function index()
    {
        // Obtener los pedidos de producción
        $pedidos = ReciboPrenda::with(['asesor'])
            ->where('estado', '!=', 'Anulada')
            ->orderBy('numero_pedido', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Agrupar por número de pedido
        $pedidosAgrupados = $pedidos->groupBy('numero_pedido')->map(function ($items) {
            return $items->map(function ($item) {
                // Obtener prendas con sus tallas (como en despacho)
                $prendas = PrendaPedido::where('pedido_produccion_id', $item->id)
                    ->with('prendaPedidoTallas')
                    ->get();

                // Construir descripción de prendas con tallas
                $prendasDesc = $prendas->map(function($prenda) {
                    $tallas = $prenda->prendaPedidoTallas
                        ->map(fn($t) => "{$t->talla} ({$t->cantidad})")
                        ->implode(', ');
                    return "{$prenda->nombre_prenda}" . ($tallas ? " - Tallas: $tallas" : "");
                })->toArray();

                // Obtener EPPs con su información completa
                $epps = PedidoEpp::where('pedido_produccion_id', $item->id)
                    ->with('epp')
                    ->get();

                // Construir descripción de EPPs
                $eppsDesc = $epps->map(function($epp) {
                    $nombre = $epp->epp->nombre_completo ?? $epp->epp->nombre ?? 'EPP sin nombre';
                    $codigo = $epp->epp->codigo ? " ({$epp->epp->codigo})" : '';
                    return "{$nombre}{$codigo}" . ($epp->cantidad ? " x{$epp->cantidad}" : "");
                })->toArray();

                // Combinar todas las descripciones
                $todasLasArticulos = array_merge($prendasDesc, $eppsDesc);
                $articuoDescription = !empty($todasLasArticulos) 
                    ? implode(' | ', $todasLasArticulos) 
                    : 'Sin prendas ni EPP';

                return [
                    'id' => $item->id,
                    'numero_pedido' => $item->numero_pedido,
                    'asesor' => $item->asesor->nombre ?? $item->asesor->name ?? 'N/A',
                    'empresa' => $item->cliente ?? 'N/A',
                    'articulo' => $articuoDescription,
                    'cantidad_total' => $item->cantidad_total ?? 0,
                    'observaciones' => $item->novedades ?? '',
                    'fecha_entrega' => $item->fecha_estimada_de_entrega ? Carbon::parse($item->fecha_estimada_de_entrega)->format('Y-m-d') : null,
                    'fecha_pedido' => $item->created_at->format('Y-m-d'),
                    'estado' => $this->determinarEstado($item),
                ];
            })->toArray();
        })->toArray();

        // Obtener lista única de asesores para filtro
        $asesores = $pedidos->pluck('asesor.nombre')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return view('bodega.pedidos', [
            'pedidosAgrupados' => $pedidosAgrupados,
            'asesores' => $asesores,
        ]);
    }

    /**
     * Marcar pedido como entregado
     */
    public function entregar(Request $request, $id): JsonResponse
    {
        try {
            $reciboPrenda = ReciboPrenda::findOrFail($id);

            // Validar que el usuario sea bodeguero
            $this->authorize('bodegueroDashboard');

            // Actualizar estado
            $reciboPrenda->update([
                'estado' => 'entregado',
                'fecha_entrega_real' => Carbon::now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido marcado como entregado correctamente',
                'data' => [
                    'id' => $reciboPrenda->id,
                    'estado' => 'entregado',
                    'fecha_entrega_real' => $reciboPrenda->fecha_entrega_real,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como entregado: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Actualizar observaciones
     */
    public function actualizarObservaciones(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:recibo_prendas,id',
                'observaciones' => 'nullable|string|max:500',
            ]);

            $reciboPrenda = ReciboPrenda::findOrFail($validated['id']);

            // Validar permiso
            $this->authorize('bodegueroDashboard');

            // Actualizar observaciones
            $reciboPrenda->update([
                'observaciones' => $validated['observaciones'],
            ]);

            // Registrar en auditoría

            return response()->json([
                'success' => true,
                'message' => 'Observaciones actualizadas correctamente',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar observaciones: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Actualizar fecha de entrega
     */
    public function actualizarFecha(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:recibo_prendas,id',
                'fecha_entrega' => 'required|date',
            ]);

            $reciboPrenda = ReciboPrenda::findOrFail($validated['id']);

            // Validar permiso
            $this->authorize('bodegueroDashboard');

            // Actualizar fecha
            $reciboPrenda->update([
                'fecha_entrega' => Carbon::createFromFormat('Y-m-d', $validated['fecha_entrega']),
            ]);

            // Actualizar fecha
            $reciboPrenda->update(['fecha_entrega' => $validated['fecha_entrega']]);

            return response()->json([
                'success' => true,
                'message' => 'Fecha de entrega actualizada correctamente',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar fecha: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Determinar estado del pedido
     */
    private function determinarEstado($item): string
    {
        // Si está marcado como Entregado
        if ($item->estado === 'Entregado') {
            return 'entregado';
        }

        // Si la fecha de entrega estimada ya pasó
        if ($item->fecha_estimada_de_entrega && Carbon::parse($item->fecha_estimada_de_entrega) < Carbon::now()) {
            return 'retrasado';
        }

        return 'pendiente';
    }

    /**
     * Exportar datos (opcional)
     */
    public function export(Request $request)
    {
        // Implementar exportación a Excel/PDF si es necesario
    }

    /**
     * Dashboard con estadísticas (opcional)
     */
    public function dashboard()
    {
        $totalPedidos = ReciboPrenda::whereDate('created_at', Carbon::today())->count();
        $entregadosHoy = ReciboPrenda::where('estado', 'entregado')
            ->whereDate('fecha_entrega_real', Carbon::today())
            ->count();
        $retrasados = ReciboPrenda::where('estado', '!=', 'entregado')
            ->where('fecha_entrega', '<', Carbon::now())
            ->count();

        return view('bodega.dashboard', [
            'totalPedidos' => $totalPedidos,
            'entregadosHoy' => $entregadosHoy,
            'retrasados' => $retrasados,
        ]);
    }
}
