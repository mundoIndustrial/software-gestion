<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Maneja la busqueda autocomplete de clientes para el formulario de pedidos.
 */
class ObtenerClientesAutocompleteController extends Controller
{
    /**
     * GET /api/asesores/clientes/autocomplete
     */
    public function obtenerClientes(Request $request): JsonResponse
    {
        try {
            $query = trim((string) $request->query('q', ''));
            $limit = max(1, min((int) $request->query('limit', 15), 50));

            $clientesQuery = Cliente::query()
                ->select('id', 'nombre')
                ->whereNotNull('nombre')
                ->where('nombre', '!=', '');

            if ($query !== '') {
                $clientesQuery->where('nombre', 'like', '%' . $query . '%');
            }

            $clientes = $clientesQuery
                ->orderBy('nombre')
                ->limit($limit)
                ->get()
                ->map(fn (Cliente $cliente): array => [
                    'id' => $cliente->id,
                    'nombre' => (string) $cliente->nombre,
                ])
                ->values();

            return response()->json([
                'success' => true,
                'clientes' => $clientes,
                'total' => $clientes->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[ObtenerClientesAutocompleteController] Error', [
                'query' => $request->query('q'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clientes',
                'clientes' => [],
            ], 500);
        }
    }
}
