<?php

namespace App\Infrastructure\Http\Controllers\VisualizadorLogo;

use App\Application\PedidosLogo\UseCases\GuardarAreaNovedadPedidoLogoUseCase;
use App\Application\PedidosLogo\UseCases\ListPedidosLogoUseCase;
use App\Http\Controllers\Controller;
use App\Models\PrendaReciboCompletado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    /**
     * Marcar un recibo como completado por el bordador
     */
    public function marcarCompletado(Request $request): JsonResponse
    {
        $request->validate([
            'id_recibo' => 'required|integer',
            'numero_recibo' => 'required|integer',
        ]);

        $user = Auth::user();
        $area = 'BORDANDO'; // Para bordador, el área es siempre BORDANDO

        // Verificar si ya existe
        $existente = PrendaReciboCompletado::where('id_recibo', $request->id_recibo)
            ->where('area', $area)
            ->first();

        if ($existente) {
            // Si ya existe, eliminarlo (deshacer completado)
            $existente->delete();
            return response()->json([
                'success' => true,
                'message' => 'Completado deshecho.',
                'completado' => false,
            ]);
        }

        // Crear nuevo registro
        $completado = PrendaReciboCompletado::create([
            'id_recibo' => $request->id_recibo,
            'numero_recibo' => $request->numero_recibo,
            'area' => $area,
            'nombre_operario' => $user->name ?? 'Bordador',
            'fecha_completado' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recibo marcado como completado.',
            'completado' => true,
            'data' => $completado,
        ]);
    }
}
