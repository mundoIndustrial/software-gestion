<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Asesores\UseCases\ContarPendientesAsesorUseCase;
use App\Application\Asesores\UseCases\ObtenerNotasPedidoUseCase;
use App\Application\Asesores\UseCases\ObtenerPendientesAsesorUseCase;
use App\Application\Asesores\UseCases\ResolverPedidoIdAsesorUseCase;
use App\Application\Pedidos\DTOs\ListarProduccionPedidosDTO;
use App\Application\Pedidos\UseCases\ListarProduccionPedidosUseCase;
use App\Application\Pedidos\UseCases\ObtenerProximoNumeroPedidoUseCase;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use Illuminate\Http\JsonResponse;
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

    public function obtenerNotasPedido(int|string $id): JsonResponse
    {
        try {
            $usuarioId = (int) (Auth::id() ?? 0);
            $pedidoId = $this->resolverPedidoIdAsesorUseCase->ejecutar((string) $id, $usuarioId);
            $pedidoRef = $this->pedidoProduccionRepository->obtenerPorIdYAsesor($pedidoId, $usuarioId);

            if ($pedidoRef === null || $pedidoRef->numeroPedido === null) {
                return $this->failure('Pedido no encontrado', 404);
            }

            $notas = $this->obtenerNotasPedidoUseCase->ejecutar((string) $pedidoRef->numeroPedido);

            return $this->json([
                'success' => true,
                'data' => $notas,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al obtener notas del pedido', ['error' => $e->getMessage()]);
            return $this->failure('Error al cargar las notas', 500);
        }
    }

    public function contarPendientesAsesor(): JsonResponse
    {
        try {
            $user = Auth::user();
            $asesorNombre = $user->name ?? '';
            $conteo = $this->contarPendientesAsesorUseCase->ejecutar($asesorNombre);

            return $this->json([
                'success' => true,
                'conteo' => $conteo,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al contar pendientes del asesor', ['error' => $e->getMessage()]);
            return $this->failure('Error al contar pendientes del asesor', 500, [
                'conteo' => 0,
            ]);
        }
    }

    public function contarPedidosDevueltos(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->failure('No autenticado', 401, ['conteo' => 0]);
            }

            $conteo = \App\Models\PedidoProduccion::query()
                ->where('asesor_id', $user->id)
                ->where('estado', 'DEVUELTO_A_ASESORA')
                ->count();

            return $this->json([
                'success' => true,
                'conteo' => $conteo,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al contar pedidos devueltos', ['error' => $e->getMessage()]);
            return $this->failure('Error al contar pedidos devueltos', 500, [
                'conteo' => 0,
            ]);
        }
    }

    public function contarPedidosProduccion(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->failure('No autenticado', 401, ['conteo' => 0]);
            }

            // Contar pedidos devueltos a asesor
            $pedidosDevueltos = \App\Models\PedidoProduccion::query()
                ->where('asesor_id', $user->id)
                ->where('estado', 'DEVUELTO_A_ASESORA')
                ->count();

            // Contar recibos de costura devueltos
            $recibosDevueltos = \App\Models\ConsecutivoReciboPedido::query()
                ->where('activo', 1)
                ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', ['COSTURA'])
                ->whereRaw("UPPER(REPLACE(TRIM(COALESCE(estado, '')), ' ', '_')) IN (?, ?)", [
                    'DEVUELTO_ASESOR',
                    'DEVUELTO_A_ASESOR',
                ])
                ->whereHas('pedido', static function ($pedidoQuery) use ($user) {
                    $pedidoQuery->where('asesor_id', $user->id);
                })
                ->count();

            $conteoTotal = $pedidosDevueltos + $recibosDevueltos;

            return $this->json([
                'success' => true,
                'conteo' => $conteoTotal,
                'pedidos_devueltos' => $pedidosDevueltos,
                'recibos_devueltos' => $recibosDevueltos,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al contar producción', ['error' => $e->getMessage()]);
            return $this->failure('Error al contar producción', 500, [
                'conteo' => 0,
            ]);
        }
    }

    public function obtenerPendientesAsesor(Request $request): JsonResponse
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

            return $this->json([
                'success' => true,
                'data' => $resultado['data'],
                'meta' => $resultado['meta'],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al obtener pendientes del asesor', ['error' => $e->getMessage()]);
            return $this->failure('Error al obtener pendientes.', 500);
        }
    }

    public function getNextPedido(): JsonResponse
    {
        try {
            $siguientePedido = $this->obtenerProximoNumeroPedidoUseCase->ejecutar();

            return $this->json([
                'success' => true,
                'siguiente_pedido' => $siguientePedido,
            ]);
        } catch (\Throwable $e) {
            return $this->failure('Error al obtener proximo numero', 500);
        }
    }

    public function apiListar(Request $request): JsonResponse
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
            $pedidosArray = collect($pedidos->items())->map(function ($pedido) {
                return [
                    'id' => data_get($pedido, 'id'),
                    'numero_pedido' => data_get($pedido, 'numero_pedido'),
                    'cliente' => data_get($pedido, 'cliente'),
                    'estado' => data_get($pedido, 'estado'),
                    'area' => data_get($pedido, 'area'),
                    'novedades' => data_get($pedido, 'novedades'),
                    'forma_pago' => data_get($pedido, 'forma_pago'),
                    'fecha_creacion' => data_get($pedido, 'fecha_creacion'),
                    'fecha_estimada' => data_get($pedido, 'fecha_estimada'),
                ];
            })->toArray();

            return $this->json([
                'success' => true,
                'data' => $pedidosArray,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error en apiListar', ['error' => $e->getMessage()]);
            return $this->failure('Error al listar pedidos', 500);
        }
    }
}
