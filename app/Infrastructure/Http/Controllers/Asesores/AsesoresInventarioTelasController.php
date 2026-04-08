<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\InventarioTelas\UseCases\AjustarStockInventarioTelaUseCase;
use App\Application\InventarioTelas\UseCases\CrearInventarioTelaUseCase;
use App\Application\InventarioTelas\UseCases\EliminarInventarioTelaUseCase;
use App\Application\InventarioTelas\UseCases\ObtenerHistorialInventarioUseCase;
use App\Application\InventarioTelas\UseCases\ObtenerInventarioTelasUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Asesores\AjustarStockInventarioTelaRequest;
use App\Infrastructure\Http\Requests\Asesores\CrearInventarioTelaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AsesoresInventarioTelasController extends Controller
{
    public function __construct(
        private readonly ObtenerInventarioTelasUseCase $obtenerTelasUseCase,
        private readonly CrearInventarioTelaUseCase $crearTelaUseCase,
        private readonly AjustarStockInventarioTelaUseCase $ajustarStockUseCase,
        private readonly ObtenerHistorialInventarioUseCase $obtenerHistorialUseCase,
        private readonly EliminarInventarioTelaUseCase $eliminarTelaUseCase,
    ) {
    }

    private function json(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    private function failure(string $message, int $status = 500, array $extra = []): JsonResponse
    {
        return $this->json(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra), $status);
    }

    public function index()
    {
        $telas = $this->obtenerTelasUseCase->ejecutar();
        $user = Auth::user();

        if ($user && $user->hasRole('insumos')) {
            return view('inventario-telas.index-insumos', compact('telas'));
        }

        return view('asesores.inventario-telas.index', compact('telas'));
    }

    public function store(CrearInventarioTelaRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $tela = $this->crearTelaUseCase->ejecutar($validated);

            return $this->json([
                'success' => true,
                'message' => 'Tela creada correctamente',
                'tela' => $tela,
            ]);
        } catch (\Exception $e) {
            return $this->failure('Error al crear la tela: ' . $e->getMessage(), 500);
        }
    }

    public function ajustarStock(AjustarStockInventarioTelaRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $resultado = $this->ajustarStockUseCase->ejecutar(
                telaId: (int) $validated['tela_id'],
                tipoAccion: $validated['tipo_accion'],
                cantidad: (float) $validated['cantidad'],
                observaciones: $validated['observaciones'] ?? null
            );

            return $this->json([
                'success' => true,
                'message' => 'Stock ajustado correctamente',
                'stock_anterior' => $resultado['stock_anterior'],
                'stock_nuevo' => $resultado['stock_nuevo'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->failure($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->failure('Error al ajustar el stock: ' . $e->getMessage(), 500);
        }
    }

    public function historial(): JsonResponse
    {
        try {
            $datos = $this->obtenerHistorialUseCase->ejecutar();

            return $this->json([
                'success' => true,
                'historial' => $datos['historial'],
                'estadisticas' => $datos['estadisticas'],
                'telas_mas_movidas' => $datos['telas_mas_movidas'],
                'stock_por_tela' => $datos['stock_por_tela'],
                'telas' => $datos['telas'],
            ]);
        } catch (\Exception $e) {
            return $this->failure('Error al obtener el historial: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(int|string $id): JsonResponse
    {
        try {
            $this->eliminarTelaUseCase->ejecutar((int) $id);

            return $this->json([
                'success' => true,
                'message' => 'Tela eliminada correctamente',
            ]);
        } catch (\Exception $e) {
            return $this->failure('Error al eliminar la tela: ' . $e->getMessage(), 500);
        }
    }
}
