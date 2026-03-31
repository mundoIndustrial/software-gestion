<?php

namespace App\Infrastructure\Http\Controllers\Despacho;

use App\Application\Services\Asesores\ObservacionesDespachoApplicationService;
use App\Events\ObservacionDespachoCreada;
use App\Http\Controllers\Controller;
use App\Models\BodegaNota;
use App\Models\PedidoObservacionesDespacho;
use App\Models\PedidoProduccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DespachoObservacionesController extends Controller
{
    public function __construct(
        private readonly ObservacionesDespachoApplicationService $service,
    ) {
    }

    public function resumenObservaciones(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pedido_ids' => 'required|array',
            'pedido_ids.*' => 'integer',
        ]);

        $pedidoIds = $validated['pedido_ids'];

        $resumenDespacho = PedidoObservacionesDespacho::query()
            ->whereIn('pedido_produccion_id', $pedidoIds)
            ->selectRaw('pedido_produccion_id, COUNT(*) as total')
            ->groupBy('pedido_produccion_id')
            ->pluck('total', 'pedido_produccion_id')
            ->toArray();

        $resumenBodega = BodegaNota::query()
            ->whereIn('pedido_produccion_id', $pedidoIds)
            ->selectRaw('pedido_produccion_id, COUNT(*) as total')
            ->groupBy('pedido_produccion_id')
            ->pluck('total', 'pedido_produccion_id')
            ->toArray();

        $resultado = [];
        foreach ($pedidoIds as $pedidoId) {
            $total = (int) ($resumenDespacho[$pedidoId] ?? 0) + (int) ($resumenBodega[$pedidoId] ?? 0);
            $resultado[$pedidoId] = [
                'unread' => $total,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $resultado,
        ]);
    }

    public function marcarLeidas(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        $usuario = auth()->user();
        $usuarioId = $usuario?->id;

        PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->whereNull('visto_at')
            ->where(function ($q) use ($usuarioId) {
                $q->whereNull('usuario_id')
                    ->orWhere('usuario_id', '!=', $usuarioId);
            })
            ->update(['visto_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Observaciones marcadas como leídas',
        ]);
    }

    public function obtenerObservaciones(PedidoProduccion $pedido): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->obtenerObservacionesUnificadas((int) $pedido->id),
        ]);
    }

    public function guardarObservacion(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        $validated = $request->validate([
            'contenido' => 'required|string|max:5000',
        ]);

        $row = $this->service->guardar(
            (int) $pedido->id,
            (string) $validated['contenido'],
            auth()->user(),
            $request->ip(),
        );

        broadcast(new ObservacionDespachoCreada($row, 'created'))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Observación guardada exitosamente',
            'data' => $this->service->mapPayload($row),
        ]);
    }

    public function actualizarObservacion(Request $request, PedidoProduccion $pedido, string $observacionId): JsonResponse
    {
        $validated = $request->validate([
            'contenido' => 'required|string|max:5000',
        ]);

        try {
            $row = $this->service->actualizar(
                (int) $pedido->id,
                $observacionId,
                (string) $validated['contenido'],
                auth()->user(),
            );
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Observación no encontrada',
            ], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para editar esta observación',
            ], 403);
        }

        broadcast(new ObservacionDespachoCreada($row, 'updated'))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Observación actualizada correctamente',
            'data' => $this->service->mapPayload($row),
        ]);
    }

    public function eliminarObservacion(Request $request, PedidoProduccion $pedido, string $observacionId): JsonResponse
    {
        try {
            $row = $this->service->eliminar((int) $pedido->id, $observacionId, auth()->user());
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Observación no encontrada',
            ], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar esta observación',
            ], 403);
        }

        broadcast(new ObservacionDespachoCreada($row, 'deleted'))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Observación eliminada correctamente',
        ]);
    }

    /**
     * Marcar observaciones de un pedido como vistas (para badges)
     */
    public function marcarObservacionesComoVistas($pedidoId)
    {
        try {
            $updated = DB::table('pedido_observaciones_despacho')
                ->where('pedido_produccion_id', $pedidoId)
                ->whereNull('visto_at')
                ->update(['visto_at' => now()]);

            \Log::info('[DespachoController] Observaciones marcadas como vistas', [
                'pedido_id' => $pedidoId,
                'updated_count' => $updated,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Observaciones marcadas como vistas',
                'updated_count' => $updated,
            ]);
        } catch (\Exception $e) {
            \Log::error('[DespachoController] Error marcando observaciones como vistas', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al marcar observaciones como vistas',
            ], 500);
        }
    }
}
