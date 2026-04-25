<?php

namespace App\Application\Bodega\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoFiltroService
{
    private const ITEMS_PER_PAGE = 20;

    public function __construct(
        private BodegaFiltroService $filtroService
    ) {}

    public function obtenerDatosFiltro(Request $request, string $tipo): JsonResponse
    {
        try {
            $page = (int) $request->get('page', 1);
            $search = $request->get('search', '');
            $area = $this->detectarArea($request);

            \Log::info('obtenerDatosFiltro iniciado', [
                'tipo' => $tipo,
                'area' => $area,
                'page' => $page,
                'search' => $search,
            ]);

            $datos = $this->obtenerDatos($area, $tipo, $search, $page);
            $paginacion = $this->generarPaginacion($datos->count(), $page);

            return response()->json([
                'success' => true,
                'datos' => $datos->forPage($page, self::ITEMS_PER_PAGE)->values()->all(),
                'paginacion' => $paginacion
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosFiltro: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al cargar datos del filtro: ' . $e->getMessage()
            ], 500);
        }
    }

    private function detectarArea(Request $request): string
    {
        $path = request()->path();
        $referer = request()->header('referer', '');

        if (str_contains($path, 'pendiente-costura') || str_contains($referer, 'pendiente-costura')) {
            return 'Costura';
        }

        if (str_contains($path, 'pendiente-epp') || str_contains($referer, 'pendiente-epp')) {
            return 'EPP';
        }

        return 'Costura'; // Default
    }

    private function obtenerDatos(string $area, string $tipo, string $search, int $page)
    {
        // Mapeo de tipo a nombre de método
        $tipoMap = [
            'numero_pedido' => 'NumerosPedido',
            'cliente' => 'Clientes',
            'asesor' => 'Asesores',
            'estado' => 'Estados',
            'fecha_creacion' => 'FechasCreacion',
            'fecha' => 'Fechas',
            'fecha_entrega' => 'Fechas',
        ];

        if (!isset($tipoMap[$tipo])) {
            \Log::warning('Tipo de filtro no reconocido: ' . $tipo);
            return collect();
        }

        $metodo = 'obtener' . $tipoMap[$tipo] . $area;

        if (!method_exists($this->filtroService, $metodo)) {
            \Log::warning("Método {$metodo} no existe en BodegaFiltroService");
            return collect();
        }

        return $this->filtroService->$metodo($search, $page, self::ITEMS_PER_PAGE);
    }

    private function generarPaginacion(int $total, int $page): array
    {
        return [
            'current_page' => $page,
            'total_pages' => ceil($total / self::ITEMS_PER_PAGE),
            'total' => $total,
            'per_page' => self::ITEMS_PER_PAGE,
            'from' => ($page - 1) * self::ITEMS_PER_PAGE + 1,
            'to' => min($page * self::ITEMS_PER_PAGE, $total),
        ];
    }
}
