<?php

namespace App\Infrastructure\Http\Controllers\VisualizadorPedidos;

use App\Application\SupervisorPedidos\DTOs\ListOrdersRequest;
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

    public function getVisualizadorPedidosData(Request $request)
    {
        try {
            $userId = Auth::id();
            $listOrdersRequest = new ListOrdersRequest([
                'page' => $request->get('page', 1),
                'perPage' => $request->get('perPage', 20),
                'busqueda' => $request->get('busqueda'),
                'is_visualizador' => true, // Indicar que es para el visualizador
                'user_id' => $userId,
            ]);

            $ordenes = $this->pedidoProduccionReadService->listOrders($listOrdersRequest);

            // Formatear la respuesta de manera similar a como lo hace VisualizadorLogoController
            $ordenesArray = [
                'data' => $ordenes->items(),
                'current_page' => $ordenes->currentPage(),
                'last_page' => $ordenes->lastPage(),
                'per_page' => $ordenes->perPage(),
                'total' => $ordenes->total(),
                'from' => $ordenes->firstItem(),
                'to' => $ordenes->lastItem(),
            ];

            return response()->json([
                'success' => true,
                'ordenes' => $ordenesArray,
                'estados' => [], // No necesitamos estados específicos para este visualizador
                'pedidosSeleccionados' => [], // No necesitamos pedidos seleccionados aquí
            ]);
        } catch (\Exception $e) {
            Log::error("Error al obtener datos para visualizador de pedidos: {$e->getMessage()}");
            return response()->json(['success' => false, 'message' => 'Error al cargar los pedidos.'], 500);
        }
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
