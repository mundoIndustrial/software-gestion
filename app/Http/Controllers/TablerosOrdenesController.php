<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Role;
use App\Models\User;
use App\Models\ProcesoPrenda;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use App\Models\ReciboFijado;
use App\Events\ReciboFijadoActualizado;
use Illuminate\Support\Facades\DB;

class TablerosOrdenesController extends Controller
{
    public function index()
    {
        return view('tableros-ordenes.index');
    }

    public function costureros(): JsonResponse
    {
        $rolCosturero = Role::where('name', 'costurero')->first();
        if (!$rolCosturero) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $costureros = User::whereJsonContains('roles_ids', $rolCosturero->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $costureros,
        ]);
    }

    public function buscarRecibos(Request $request): JsonResponse
    {
        $encargadoNombre = $request->input('encargado_nombre');
        $q = trim((string) $request->input('q', ''));

        if (empty($encargadoNombre)) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $encargado = strtolower(trim((string) $encargadoNombre));
        $encargado = preg_replace('/\s+/', ' ', $encargado);

        $query = ConsecutivoReciboPedido::query()
            ->select('consecutivos_recibos_pedidos.*')
            ->join('procesos_prenda as pp', function ($join) {
                $join->on('pp.prenda_pedido_id', '=', 'consecutivos_recibos_pedidos.prenda_id')
                    ->whereNull('pp.deleted_at');
            })
            ->where('consecutivos_recibos_pedidos.tipo_recibo', 'COSTURA')
            ->where('consecutivos_recibos_pedidos.activo', 1)
            ->whereIn('consecutivos_recibos_pedidos.area', ['Corte', 'Costura', 'Control de Calidad', 'Control Calidad'])
            ->whereRaw('LOWER(TRIM(pp.proceso)) = ?', ['costura'])
            ->whereRaw("REGEXP_REPLACE(LOWER(TRIM(pp.encargado)), '\\s+', ' ') = ?", [$encargado])
            ->distinct();

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('consecutivo_actual', 'like', '%' . $q . '%')
                  ->orWhereHas('pedido', function ($p) use ($q) {
                      $p->where('cliente', 'like', '%' . $q . '%');
                  });
            });
        }

        $recibos = $query
            ->with(['pedido:id,cliente', 'prenda:id'])
            ->orderBy('consecutivo_actual', 'desc')
            ->limit(15)
            ->get();

        $data = $recibos->map(function ($r) {
            return [
                'recibo_id' => $r->id,
                'numero_recibo' => $r->consecutivo_actual,
                'cliente' => $r->pedido?->cliente,
                'pedido_produccion_id' => $r->pedido_produccion_id,
                'prenda_id' => $r->prenda_id,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function obtenerReciboPorId(Request $request): JsonResponse
    {
        $idRecibo = $request->input('id_recibo');
        if (empty($idRecibo)) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        $recibo = ConsecutivoReciboPedido::query()
            ->where('id', (int) $idRecibo)
            ->where('tipo_recibo', 'COSTURA')
            ->with(['pedido:id,cliente', 'prenda:id'])
            ->first();

        if (!$recibo) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'recibo_id' => $recibo->id,
                'numero_recibo' => $recibo->consecutivo_actual,
                'cliente' => $recibo->pedido?->cliente,
                'pedido_produccion_id' => $recibo->pedido_produccion_id,
                'prenda_id' => $recibo->prenda_id,
            ],
        ]);
    }

    public function obtenerReciboFijado(Request $request): JsonResponse
    {
        $encargadoNombre = $request->input('encargado_nombre');
        if (empty($encargadoNombre)) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        $encargado = strtolower(trim((string) $encargadoNombre));
        $fijado = ReciboFijado::query()
            ->whereRaw('LOWER(TRIM(encargado_actual)) = ?', [$encargado])
            ->first();

        return response()->json([
            'success' => true,
            'data' => $fijado ? [
                'id_recibo' => (int) $fijado->id_recibo,
                'encargado_actual' => (string) $fijado->encargado_actual,
                'created_at' => $fijado->created_at,
            ] : null,
        ]);
    }

    public function fijarRecibo(Request $request): JsonResponse
    {
        $encargadoNombre = $request->input('encargado_nombre');
        $idRecibo = $request->input('id_recibo');

        if (empty($encargadoNombre) || empty($idRecibo)) {
            return response()->json([
                'success' => false,
                'message' => 'encargado_nombre e id_recibo son requeridos',
            ], 422);
        }

        $encargado = strtolower(trim((string) $encargadoNombre));

        $fijado = ReciboFijado::query()->updateOrCreate(
            ['encargado_actual' => $encargado],
            ['id_recibo' => (int) $idRecibo, 'created_at' => now()]
        );

        event(new ReciboFijadoActualizado($encargado, (int) $fijado->id_recibo, 'fijar'));

        return response()->json([
            'success' => true,
            'data' => [
                'id_recibo' => (int) $fijado->id_recibo,
                'encargado_actual' => (string) $fijado->encargado_actual,
                'created_at' => $fijado->created_at,
            ],
        ]);
    }

    public function limpiarReciboFijado(Request $request): JsonResponse
    {
        $encargadoNombre = $request->input('encargado_nombre');
        if (empty($encargadoNombre)) {
            return response()->json([
                'success' => false,
                'message' => 'encargado_nombre es requerido',
            ], 422);
        }

        $encargado = strtolower(trim((string) $encargadoNombre));

        ReciboFijado::query()
            ->whereRaw('LOWER(TRIM(encargado_actual)) = ?', [$encargado])
            ->delete();

        event(new ReciboFijadoActualizado($encargado, null, 'limpiar'));

        return response()->json([
            'success' => true,
        ]);
    }
}
