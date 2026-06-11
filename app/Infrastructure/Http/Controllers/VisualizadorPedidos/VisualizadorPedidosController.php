<?php

namespace App\Infrastructure\Http\Controllers\VisualizadorPedidos;

use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class VisualizadorPedidosController extends Controller
{
    public function __construct(
        private PedidoProduccionReadService $pedidoProduccionReadService
    ) {}

    public function dashboard()
    {
        return view('visualizador-pedidos.dashboard');
    }

    public function marcarRevisado(Request $request)
    {
        $request->validate([
            'pedido_id' => 'required|integer|exists:pedidos_produccion,id',
        ]);

        $pedidoId = $request->input('pedido_id');
        $userId = Auth::id();

        try {
            $isReviewed = $this->pedidoProduccionReadService->togglePedidoVisto($pedidoId, $userId);

            $message = $isReviewed ? 'Pedido marcado como revisado.' : 'Revisión de pedido desmarcada.';
            return response()->json(['success' => true, 'is_reviewed' => $isReviewed, 'message' => $message]);
        } catch (\Exception $e) {
            Log::error("Error al alternar estado de revisión del pedido: {$e->getMessage()}");
            return response()->json(['success' => false, 'message' => 'Error al alternar estado de revisión del pedido.'], 500);
        }
    }
}
