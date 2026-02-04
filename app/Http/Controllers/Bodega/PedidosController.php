<?php

namespace App\Http\Controllers\Bodega;

use App\Http\Controllers\Controller;
use App\Models\ReciboPrenda; // Ajusta según tu modelo
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
        // Obtener los datos agrupados por número de pedido
        $pedidos = ReciboPrenda::with(['asesor', 'empresa'])
            ->where('estado', '!=', 'cancelado')
            ->orderBy('numero_pedido', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Agrupar por número de pedido
        $pedidosAgrupados = $pedidos->groupBy('numero_pedido')->map(function ($items) {
            return $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'numero_pedido' => $item->numero_pedido,
                    'asesor' => $item->asesor->nombre ?? 'N/A',
                    'empresa' => $item->empresa->nombre ?? 'N/A',
                    'articulo' => $item->articulo->nombre ?? 'N/A',
                    'cantidad' => $item->cantidad,
                    'observaciones' => $item->observaciones,
                    'fecha_entrega' => $item->fecha_entrega ? $item->fecha_entrega->format('Y-m-d') : null,
                    'fecha_pedido' => $item->created_at->format('Y-m-d'),
                    'estado' => $this->determinarEstado($item),
                ];
            })->toArray();
        })->toArray();

        // Obtener lista única de asesores para filtro
        $asesores = $pedidos->pluck('asesor.nombre')
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

            // Registrar en auditoría si tienes
            activity()
                ->performedOn($reciboPrenda)
                ->causedBy(auth()->user())
                ->event('entregado')
                ->log('Pedido marcado como entregado en bodega');

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
            activity()
                ->performedOn($reciboPrenda)
                ->causedBy(auth()->user())
                ->event('observaciones_actualizadas')
                ->withProperties(['observaciones' => $validated['observaciones']])
                ->log('Observaciones actualizadas en bodega');

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

            // Registrar en auditoría
            activity()
                ->performedOn($reciboPrenda)
                ->causedBy(auth()->user())
                ->event('fecha_actualizada')
                ->withProperties(['fecha_entrega' => $validated['fecha_entrega']])
                ->log('Fecha de entrega actualizada en bodega');

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
        // Si está marcado como entregado
        if ($item->estado === 'entregado') {
            return 'entregado';
        }

        // Si la fecha de entrega ya pasó
        if ($item->fecha_entrega && Carbon::createFromFormat('Y-m-d H:i:s', $item->fecha_entrega) < Carbon::now()) {
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
