<?php

namespace App\Infrastructure\Http\Controllers\Operario;

use App\Application\Operario\Services\ObtenerPedidosOperarioService;
use App\Application\Operario\UseCases\ReportarPendienteOperarioUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperarioPedidosController extends Controller
{
    public function __construct(
        private ObtenerPedidosOperarioService $obtenerPedidosService,
        private ReportarPendienteOperarioUseCase $reportarPendienteOperarioUseCase,
    ) {}

    public function buscarPedido(Request $request)
    {
        $request->validate([
            'busqueda' => 'required|string|min:2',
        ]);

        $usuario = Auth::user();
        $datosOperario = $this->obtenerPedidosService->obtenerPedidosDelOperario($usuario);
        $busqueda = strtolower($request->input('busqueda'));

        $resultados = collect($datosOperario->pedidos)
            ->filter(function ($pedido) use ($busqueda) {
                return str_contains(strtolower($pedido['numero_pedido']), $busqueda)
                    || str_contains(strtolower($pedido['cliente']), $busqueda)
                    || str_contains(strtolower($pedido['descripcion']), $busqueda);
            })
            ->values()
            ->toArray();

        return response()->json([
            'success' => true,
            'resultados' => $resultados,
            'total' => count($resultados),
        ]);
    }

    public function reportarPendiente(Request $request)
    {
        $request->validate([
            'numero_pedido' => 'required|numeric',
            'novedad' => 'required|string',
        ]);

        try {
            $result = $this->reportarPendienteOperarioUseCase->execute($request);
            return response()->json($result['payload'] ?? [], (int) ($result['status'] ?? 200));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reportar la novedad: ' . $e->getMessage()
            ], 500);
        }
    }
}
