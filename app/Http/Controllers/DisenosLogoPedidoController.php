<?php

namespace App\Http\Controllers;

use App\Application\Services\ImageUploadService;
use App\Models\DisenoLogoPedido;
use App\Models\PedidosProcesosPrendaDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DisenosLogoPedidoController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pedido_id' => 'required|integer|min:1',
            'proceso_prenda_detalle_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $pedidoId = (int) $request->input('pedido_id');
        $procesoId = (int) $request->input('proceso_prenda_detalle_id');

        $proceso = PedidosProcesosPrendaDetalle::with('prenda.pedidoProduccion')->findOrFail($procesoId);
        $pedidoProduccionId = $proceso->prenda?->pedidoProduccion?->id;

        if (!$pedidoProduccionId || $pedidoProduccionId !== $pedidoId) {
            return response()->json([
                'success' => false,
                'message' => 'El recibo no pertenece al pedido indicado.',
            ], 422);
        }

        $items = DisenoLogoPedido::query()
            ->where('proceso_prenda_detalle_id', $procesoId)
            ->orderBy('id', 'asc')
            ->get(['id', 'url']);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    public function store(Request $request, ImageUploadService $imageUploadService)
    {
        $validator = Validator::make($request->all(), [
            'pedido_id' => 'required|integer|min:1',
            'proceso_prenda_detalle_id' => 'required|integer|min:1',
            'images' => 'required|array|min:1|max:3',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $pedidoId = (int) $request->input('pedido_id');
        $procesoId = (int) $request->input('proceso_prenda_detalle_id');

        $proceso = PedidosProcesosPrendaDetalle::with('prenda.pedidoProduccion')->findOrFail($procesoId);
        $pedidoProduccionId = $proceso->prenda?->pedidoProduccion?->id;

        if (!$pedidoProduccionId || $pedidoProduccionId !== $pedidoId) {
            return response()->json([
                'success' => false,
                'message' => 'El recibo no pertenece al pedido indicado.',
            ], 422);
        }

        $existingCount = DisenoLogoPedido::where('proceso_prenda_detalle_id', $procesoId)->count();
        $incomingCount = count($request->file('images') ?? []);

        if ($existingCount + $incomingCount > 3) {
            return response()->json([
                'success' => false,
                'message' => 'Máximo 3 imágenes por recibo.',
            ], 422);
        }

        $records = [];

        DB::beginTransaction();
        try {
            foreach (($request->file('images') ?? []) as $file) {
                $paths = $imageUploadService->guardarImagenDirecta($file, $pedidoId, 'diseños-logo');
                $url = Storage::url($paths['webp']);

                $records[] = DisenoLogoPedido::create([
                    'proceso_prenda_detalle_id' => $procesoId,
                    'url' => $url,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => array_map(fn ($r) => [
                    'id' => $r->id,
                    'url' => $r->url,
                ], $records),
            ],
        ]);
    }

    public function destroy(Request $request, DisenoLogoPedido $diseno)
    {
        $validator = Validator::make($request->all(), [
            'pedido_id' => 'required|integer|min:1',
            'proceso_prenda_detalle_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $pedidoId = (int) $request->input('pedido_id');
        $procesoId = (int) $request->input('proceso_prenda_detalle_id');

        if ((int) $diseno->proceso_prenda_detalle_id !== $procesoId) {
            return response()->json([
                'success' => false,
                'message' => 'El diseño no pertenece al recibo indicado.',
            ], 422);
        }

        $proceso = PedidosProcesosPrendaDetalle::with('prenda.pedidoProduccion')->findOrFail($procesoId);
        $pedidoProduccionId = $proceso->prenda?->pedidoProduccion?->id;

        if (!$pedidoProduccionId || $pedidoProduccionId !== $pedidoId) {
            return response()->json([
                'success' => false,
                'message' => 'El recibo no pertenece al pedido indicado.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $url = (string) $diseno->url;
            $relative = $url;
            if (str_starts_with($relative, '/storage/')) {
                $relative = substr($relative, strlen('/storage/'));
            }
            $diskPath = $relative;

            if ($diskPath) {
                Storage::disk('public')->delete($diskPath);
            }

            $diseno->delete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
