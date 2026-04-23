<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemError;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ErrorLogController extends Controller
{
    /**
     * Registra un error desde JavaScript
     * POST /api/errores/registrar
     */
    public function registrar(Request $request): JsonResponse
    {
        // Validar que sea una solicitud JSON válida
        $data = $request->all();

        if (empty($data['tipo']) || empty($data['mensaje'])) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo y mensaje son requeridos'
            ], 422);
        }

        // Registrar el error
        $error = SystemError::registrarDesdeJavaScript($data);

        if ($error) {
            return response()->json([
                'success' => true,
                'message' => 'Error registrado correctamente',
                'id' => $error->id
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error al registrar el error'
        ], 500);
    }

    /**
     * Obtiene estadísticas de errores
     * GET /api/errores/estadisticas
     */
    public function estadisticas(Request $request): JsonResponse
    {
        $horas = $request->query('horas', 24);

        $stats = [
            'total' => SystemError::recientes($horas)->count(),
            'por_tipo' => SystemError::recientes($horas)
                ->groupBy('tipo')
                ->selectRaw('tipo, count(*) as total')
                ->get()
                ->pluck('total', 'tipo')
                ->toArray(),
            'por_origen' => SystemError::recientes($horas)
                ->groupBy('origen')
                ->selectRaw('origen, count(*) as total')
                ->get()
                ->pluck('total', 'origen')
                ->toArray(),
            'periodo_horas' => $horas
        ];

        return response()->json($stats);
    }
}
