<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Models\PedidoObservacionesDespacho;
use App\Models\PedidoProduccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Events\ObservacionDespachoCreada;
use Illuminate\Support\Str;


class ObservacionesDespachoController extends Controller
{
    private function assertPuedeAccederPedido(PedidoProduccion $pedido): ?JsonResponse
    {
        $usuario = auth()->user();
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        if ($usuario->hasRole('asesor')) {
            if ((string) $pedido->asesor_id !== (string) $usuario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver este pedido',
                ], 403);
            }
        }

        return null;
    }

    public function obtener(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        if ($resp = $this->assertPuedeAccederPedido($pedido)) {
            return $resp;
        }

        $rows = PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->orderByDesc('created_at')
            ->get();

        $observaciones = $rows->map(function ($row) {
            return [
                'id' => (string) $row->uuid,
                'contenido' => $row->contenido,
                'usuario_id' => $row->usuario_id,
                'usuario_nombre' => $row->usuario_nombre,
                'usuario_rol' => $row->usuario_rol,
                'ip_address' => $row->ip_address,
                'estado' => (int) $row->estado,
                'created_at' => optional($row->created_at)->toISOString(),
                'updated_at' => optional($row->updated_at)->toISOString(),
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => $observaciones,
        ]);
    }

    public function resumen(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pedido_ids' => 'required|array',
            'pedido_ids.*' => 'integer',
        ]);

        $ids = array_values(array_unique($validated['pedido_ids']));

        $usuario = auth()->user();
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        $permitidos = $ids;
        if ($usuario->hasRole('asesor')) {
            $permitidos = PedidoProduccion::query()
                ->whereIn('id', $ids)
                ->where('asesor_id', $usuario->id)
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        $conteos = PedidoObservacionesDespacho::query()
            ->selectRaw('pedido_produccion_id, COUNT(*) as unread')
            ->whereIn('pedido_produccion_id', $permitidos)
            ->where('estado', 0)
            ->where('usuario_rol', 'Despacho')
            ->groupBy('pedido_produccion_id')
            ->pluck('unread', 'pedido_produccion_id');

        $map = [];
        foreach ($permitidos as $pedidoId) {
            $map[(int) $pedidoId] = ['unread' => (int) ($conteos[(int) $pedidoId] ?? 0)];
        }

        return response()->json([
            'success' => true,
            'data' => $map,
        ]);
    }

    public function marcarLeidas(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        if ($resp = $this->assertPuedeAccederPedido($pedido)) {
            return $resp;
        }

        PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->where('estado', 0)
            ->where('usuario_rol', 'Despacho')
            ->update(['estado' => 1]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function guardar(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        if ($resp = $this->assertPuedeAccederPedido($pedido)) {
            return $resp;
        }

        $validated = $request->validate([
            'contenido' => 'required|string|max:5000',
        ]);

        $usuario = auth()->user();

        $uuid = (string) Str::uuid();
        $row = PedidoObservacionesDespacho::create([
            'pedido_produccion_id' => $pedido->id,
            'uuid' => $uuid,
            'contenido' => $validated['contenido'],
            'usuario_id' => $usuario?->id,
            'usuario_nombre' => $usuario?->name,
            'usuario_rol' => $usuario?->getCurrentRole()?->name ?? null,
            'ip_address' => $request->ip(),
            'estado' => 0,
        ]);

        // Broadcast event
        broadcast(new ObservacionDespachoCreada($row, 'created'))->toOthers();

        $observacion = [
            'id' => (string) $row->uuid,
            'contenido' => $row->contenido,
            'usuario_id' => $row->usuario_id,
            'usuario_nombre' => $row->usuario_nombre,
            'usuario_rol' => $row->usuario_rol,
            'ip_address' => $row->ip_address,
            'estado' => (int) $row->estado,
            'created_at' => optional($row->created_at)->toISOString(),
            'updated_at' => optional($row->updated_at)->toISOString(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Observación guardada exitosamente',
            'data' => $observacion,
        ]);
    }

    public function actualizar(Request $request, PedidoProduccion $pedido, string $observacionId): JsonResponse
    {
        if ($resp = $this->assertPuedeAccederPedido($pedido)) {
            return $resp;
        }

        $validated = $request->validate([
            'contenido' => 'required|string|max:5000',
        ]);

        $row = PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->where('uuid', $observacionId)
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Observación no encontrada',
            ], 404);
        }

        $usuario = auth()->user();
        $ownerId = $row->usuario_id;
        if ((string) $ownerId !== (string) ($usuario?->id)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para editar esta observación',
            ], 403);
        }

        $row->contenido = $validated['contenido'];
        $row->save();

        // Broadcast event
        broadcast(new ObservacionDespachoCreada($row, 'updated'))->toOthers();

        $payload = [
            'id' => (string) $row->uuid,
            'contenido' => $row->contenido,
            'usuario_id' => $row->usuario_id,
            'usuario_nombre' => $row->usuario_nombre,
            'usuario_rol' => $row->usuario_rol,
            'ip_address' => $row->ip_address,
            'estado' => (int) $row->estado,
            'created_at' => optional($row->created_at)->toISOString(),
            'updated_at' => optional($row->updated_at)->toISOString(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Observación actualizada correctamente',
            'data' => $payload,
        ]);
    }

    public function eliminar(Request $request, PedidoProduccion $pedido, string $observacionId): JsonResponse
    {
        if ($resp = $this->assertPuedeAccederPedido($pedido)) {
            return $resp;
        }

        $row = PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->where('uuid', $observacionId)
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Observación no encontrada',
            ], 404);
        }

        $usuario = auth()->user();
        $ownerId = $row->usuario_id;
        if ((string) $ownerId !== (string) ($usuario?->id)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar esta observación',
            ], 403);
        }

        $row->delete();

        // Broadcast event
        broadcast(new ObservacionDespachoCreada($row, 'deleted'))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Observación eliminada correctamente',
        ]);
    }
}
