<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Asesores\UseCases\ContarPendientesAsesorUseCase;
use App\Application\Asesores\UseCases\ObtenerNotasPedidoUseCase;
use App\Application\Asesores\UseCases\ObtenerPendientesAsesorUseCase;
use App\Application\Asesores\UseCases\ResolverPedidoIdAsesorUseCase;
use App\Application\Pedidos\DTOs\ListarProduccionPedidosDTO;
use App\Application\Pedidos\DTOs\ObtenerProximoNumeroPedidoDTO;
use App\Application\Pedidos\UseCases\ListarProduccionPedidosUseCase;
use App\Application\Pedidos\UseCases\ObtenerProximoNumeroPedidoUseCase;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

final class AsesoresPedidosQueryController extends Controller
{
    public function __construct(
        private readonly PedidoProduccionReadRepository $pedidoProduccionRepository,
        private readonly ResolverPedidoIdAsesorUseCase $resolverPedidoIdAsesorUseCase,
        private readonly ObtenerNotasPedidoUseCase $obtenerNotasPedidoUseCase,
        private readonly ContarPendientesAsesorUseCase $contarPendientesAsesorUseCase,
        private readonly ObtenerPendientesAsesorUseCase $obtenerPendientesAsesorUseCase,
        private readonly ObtenerProximoNumeroPedidoUseCase $obtenerProximoNumeroPedidoUseCase,
        private readonly ListarProduccionPedidosUseCase $listarProduccionPedidosUseCase
    ) {
    }

    public function obtenerNotasPedido($id)
    {
        try {
            $usuarioId = (int) (Auth::id() ?? 0);
            $pedidoId = $this->resolverPedidoIdAsesorUseCase->ejecutar((string) $id, $usuarioId);
            $pedidoRef = $this->pedidoProduccionRepository->obtenerPorIdYAsesor($pedidoId, $usuarioId);

            if ($pedidoRef === null || $pedidoRef->numeroPedido === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado',
                ], 404);
            }

            $notas = $this->obtenerNotasPedidoUseCase->ejecutar((string) $pedidoRef->numeroPedido);

            return response()->json([
                'success' => true,
                'data' => $notas,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al obtener notas del pedido', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las notas',
            ], 500);
        }
    }

    public function contarPendientesAsesor()
    {
        try {
            $user = Auth::user();
            $asesorNombre = $user->name ?? '';
            $conteo = $this->contarPendientesAsesorUseCase->ejecutar($asesorNombre);

            return response()->json([
                'success' => true,
                'conteo' => $conteo,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al contar pendientes del asesor', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'conteo' => 0,
            ], 500);
        }
    }

    public function obtenerPendientesAsesor(Request $request)
    {
        try {
            $user = Auth::user();
            $asesorNombre = $user->name ?? '';

            $resultado = $this->obtenerPendientesAsesorUseCase->ejecutar(
                $asesorNombre,
                $request->query('search', ''),
                $request->query('tipo', 'todos'),
                (int) $request->query('page', 1),
                (int) $request->query('per_page', 20)
            );

            return response()->json([
                'success' => true,
                'data' => $resultado['data'],
                'meta' => $resultado['meta'],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al obtener pendientes del asesor', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pendientes.',
            ], 500);
        }
    }

    public function getNextPedido()
    {
        try {
            $dto = ObtenerProximoNumeroPedidoDTO::crear();
            $siguientePedido = $this->obtenerProximoNumeroPedidoUseCase->ejecutar($dto);

            return response()->json([
                'siguiente_pedido' => $siguientePedido,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error al obtener próximo número',
            ], 500);
        }
    }

    public function apiListar(Request $request)
    {
        try {
            $filtros = [];
            if ($request->filled('estado')) {
                $filtros['estado'] = $request->estado;
            }
            if ($request->filled('search')) {
                $filtros['search'] = $request->search;
            }

            $user = Auth::user();
            $dto = ListarProduccionPedidosDTO::fromRequest(
                $request->query('tipo'),
                $filtros,
                $user?->id,
                (bool) ($user?->hasRole('asesor'))
            );

            $pedidos = $this->listarProduccionPedidosUseCase->ejecutar($dto);
            $pedidosArray = $pedidos->getCollection()->map(function ($pedido) {
                return [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'estado' => $pedido->estado,
                    'area' => $pedido->area,
                    'novedades' => $pedido->novedades,
                    'forma_pago' => $pedido->forma_pago,
                    'fecha_creacion' => $pedido->fecha_creacion,
                    'fecha_estimada' => $pedido->fecha_estimada,
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'data' => $pedidosArray,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error en apiListar', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Error al listar pedidos',
            ], 500);
        }
    }
}

