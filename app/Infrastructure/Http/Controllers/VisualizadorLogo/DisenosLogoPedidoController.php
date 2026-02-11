<?php

namespace App\Infrastructure\Http\Controllers\VisualizadorLogo;

use App\Application\PedidosLogo\UseCases\DeleteDisenoLogoPedidoUseCase;
use App\Application\PedidosLogo\UseCases\ListDisenosLogoPedidoUseCase;
use App\Application\PedidosLogo\UseCases\UploadDisenosLogoPedidoUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DisenosLogoPedidoController extends Controller
{
    public function __construct(
        private ListDisenosLogoPedidoUseCase $listDisenosLogoPedidoUseCase,
        private UploadDisenosLogoPedidoUseCase $uploadDisenosLogoPedidoUseCase,
        private DeleteDisenoLogoPedidoUseCase $deleteDisenoLogoPedidoUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->listDisenosLogoPedidoUseCase->execute($request->all());

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
                'message' => $result['message'] ?? 'Error.',
            ], $status);
        }

        return response()->json($result['data'], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $result = $this->uploadDisenosLogoPedidoUseCase->execute($request);

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
                'message' => $result['message'] ?? 'Error.',
            ], $status);
        }

        return response()->json($result['data'], 200);
    }

    public function destroy(Request $request, int $diseno): JsonResponse
    {
        $result = $this->deleteDisenoLogoPedidoUseCase->execute($diseno, $request->all());

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
                'message' => $result['message'] ?? 'Error.',
            ], $status);
        }

        return response()->json($result['data'], 200);
    }
}
