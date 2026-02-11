<?php

namespace App\Infrastructure\Http\Controllers\VisualizadorLogo;

use App\Application\PedidosLogo\UseCases\GuardarAreaNovedadPedidoLogoUseCase;
use App\Application\PedidosLogo\UseCases\ListPedidosLogoUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PedidosLogoController extends Controller
{
    public function __construct(
        private ListPedidosLogoUseCase $listPedidosLogoUseCase,
        private GuardarAreaNovedadPedidoLogoUseCase $guardarAreaNovedadPedidoLogoUseCase
    ) {}

    public function data(Request $request): JsonResponse
    {
        $search = $request->filled('search') ? (string) $request->get('search') : null;
        $filtro = (string) $request->get('filtro', 'bordado');

        $recibos = $this->listPedidosLogoUseCase->execute($search, $filtro, 20);

        return response()->json([
            'success' => true,
            'recibos' => $recibos,
        ]);
    }

    public function guardarAreaNovedad(Request $request): JsonResponse
    {
        $result = $this->guardarAreaNovedadPedidoLogoUseCase->execute($request->all());

        if (!($result['ok'] ?? false)) {
            $status = (int) ($result['status'] ?? 422);
            if (isset($result['errors'])) {
                return response()->json([
                    'success' => false,
                    'errors' => $result['errors'],
                ], $status);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Error de validación.',
            ], $status);
        }

        return response()->json($result['data'], 200);
    }
}
