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
                ->whereRaw('UPPER(TRIM(tipo_recibo)) IN (?, ?)', ['COSTURA', 'COSTURA-BODEGA'])
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
            $filtros = [
                'page' => max(1, (int) $request->query('page', 1)),
                'per_page' => max(1, (int) $request->query('per_page', 15)),
            ];
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

    public function buscarPedidosAsesor(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->query('search', '');
            if (strlen($searchTerm) < 1) {
                return $this->json([
                    'success' => false,
                    'message' => 'Término de búsqueda muy corto',
                ], 400);
            }

            $user = Auth::user();
            $perPage = 15;
            $maxPages = 100;
            $resultados = [];

            for ($page = 1; $page <= $maxPages; $page++) {
                $filtros = [
                    'page' => $page,
                    'per_page' => $perPage,
                    'search' => $searchTerm,
                ];

                $dto = ListarProduccionPedidosDTO::fromRequest(
                    $request->query('tipo', null),
                    $filtros,
                    $user?->id,
                    (bool) ($user?->hasRole('asesor'))
                );

                $pedidos = $this->listarProduccionPedidosUseCase->ejecutar($dto);
                $items = $pedidos->items();

                if (empty($items)) {
                    break;
                }

                foreach ($items as $pedido) {
                    $resultados[] = [
                        'id' => data_get($pedido, 'id'),
                        'numero_pedido' => data_get($pedido, 'numero_pedido'),
                        'cliente' => data_get($pedido, 'cliente'),
                        'estado' => data_get($pedido, 'estado'),
                        'forma_pago' => data_get($pedido, 'forma_pago'),
                        'dias_restantes_entrega' => \App\Application\Pedidos\Presenters\PedidoTableRowPresenter::present($pedido)->dias_restantes_entrega ?? null,
                        'fecha_estimada' => data_get($pedido, 'fecha_estimada'),
                        'page' => $page,
                    ];
                }

                if ($page >= $pedidos->lastPage()) {
                    break;
                }
            }

            if (empty($resultados)) {
                return $this->json([
                    'success' => false,
                    'message' => 'No se encontraron resultados',
                ], 404);
            }

            return $this->json([
                'success' => true,
                'data' => $resultados,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error en buscarPedidosAsesor', ['error' => $e->getMessage()]);
            return $this->failure('Error al buscar pedidos', 500);
        }
    }

    public function contarPendientesLogo(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->failure('No autenticado', 401, ['conteo' => 0]);
            }

            // Contar diseños pendientes de confirmar del asesor
            $conteo = \App\Models\DisenoLogoPedido::query()
                ->where('estado', 'pendiente_por_confirmar')
                ->whereHas('proceso.prenda.pedidoProduccion', function ($query) use ($user) {
                    $query->where('asesor_id', $user->id);
                })
                ->count();

            return $this->json([
                'success' => true,
                'conteo' => $conteo,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al contar pendientes logo', ['error' => $e->getMessage()]);
            return $this->failure('Error al contar pendientes logo', 500, [
                'conteo' => 0,
            ]);
        }
    }

    public function obtenerDiseñosPendientesLogo(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->failure('No autenticado', 401);
            }

            $diseños = \App\Models\DisenoLogoPedido::query()
                ->where('estado', 'pendiente_por_confirmar')
                ->whereHas('proceso.prenda.pedidoProduccion', function ($query) use ($user) {
                    $query->where('asesor_id', $user->id);
                })
                ->with(['proceso.prenda.pedidoProduccion.cliente', 'proceso.tipoProceso'])
                ->orderBy('created_at', 'DESC')
                ->get()
                ->map(function ($diseño) {
                    $proceso = $diseño->proceso;
                    $prenda = $proceso?->prenda;
                    $pedido = $prenda?->pedidoProduccion;
                    $cliente = $pedido?->cliente;
                    $tipoProceso = $proceso?->tipoProceso;

                    return [
                        'id' => $diseño->id,
                        'proceso_id' => $diseño->proceso_prenda_detalle_id,
                        'prenda_id' => $prenda?->id,
                        'pedido_id' => $pedido?->id,
                        'tipo_proceso' => $tipoProceso?->slug ?? 'logo',
                        'numero_recibo' => $proceso?->numero_recibo ?? '-',
                        'cliente' => $cliente?->nombre ?? $pedido?->cliente ?? 'Sin cliente',
                        'prenda' => $prenda?->nombre_prenda ?? 'Sin prenda',
                        'fecha' => $diseño->created_at?->format('d/m/Y H:i') ?? '-',
                        'estado' => $diseño->estado,
                    ];
                })
                // Deduplicar por pedido_id + prenda_id (un recibo debe aparecer una sola vez)
                ->unique(function ($item) {
                    return $item['pedido_id'] . '-' . $item['prenda_id'];
                })
                ->values(); // Reiniciar índices

            return $this->json([
                'success' => true,
                'diseños' => $diseños,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al obtener diseños pendientes logo', ['error' => $e->getMessage()]);
            return $this->failure('Error al obtener diseños pendientes', 500);
        }
    }

    public function obtenerDiseñosProceso(int $procesoId): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->failure('No autenticado', 401);
            }

            $proceso = \App\Models\PedidosProcesosPrendaDetalle::find($procesoId);
            if (!$proceso) {
                return $this->failure('Proceso no encontrado', 404);
            }

            $prenda = $proceso->prenda;
            $pedido = $prenda?->pedidoProduccion;

            // Verificar que el pedido pertenece al asesor
            if (!$pedido || $pedido->asesor_id !== $user->id) {
                return $this->failure('No tienes permiso para ver este proceso', 403);
            }

            $diseños = \App\Models\DisenoLogoPedido::query()
                ->where('proceso_prenda_detalle_id', $procesoId)
                ->orderBy('created_at', 'ASC')
                ->get()
                ->map(function ($diseño) {
                    return [
                        'id' => $diseño->id,
                        'url' => $diseño->url,
                        'observacio_diseño' => $diseño->observacio_diseño,
                        'estado' => $diseño->estado,
                    ];
                });

            return $this->json([
                'success' => true,
                'diseños' => $diseños,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al obtener diseños del proceso', ['error' => $e->getMessage()]);
            return $this->failure('Error al obtener diseños', 500);
        }
    }

    public function obtenerDatosReciboDesdeAsesor(int $pedidoId, int $prendaId): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->failure('No autenticado', 401);
            }

            // Verificar que el pedido pertenece al asesor
            $pedido = \App\Models\PedidoProduccion::find($pedidoId);
            if (!$pedido || $pedido->asesor_id !== $user->id) {
                return $this->failure('No tienes permiso para ver este pedido', 403);
            }

            // Retornar autorización exitosa
            return $this->json([
                'success' => true,
                'message' => 'Autorizado',
                'pedidoId' => $pedidoId,
                'prendaId' => $prendaId,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al validar acceso al recibo', ['error' => $e->getMessage()]);
            return $this->failure('Error al validar acceso', 500);
        }
    }

    public function confirmarDiseñoLogo(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'diseño_id' => 'required|integer|min:1',
                'proceso_id' => 'required|integer|min:1',
            ]);

            $user = Auth::user();
            if (!$user) {
                return $this->failure('No autenticado', 401);
            }

            $diseño = \App\Models\DisenoLogoPedido::find($request->diseño_id);
            if (!$diseño) {
                return $this->failure('Diseño no encontrado', 404);
            }

            // Verificar que el diseño pertenece a un pedido del asesor
            $proceso = $diseño->proceso;
            $pedido = $proceso?->prenda?->pedidoProduccion;
            if (!$pedido || $pedido->asesor_id !== $user->id) {
                return $this->failure('No tienes permiso para confirmar este diseño', 403);
            }

            // Obtener todos los diseños del mismo recibo (pedido + prenda) en estado pendiente_por_confirmar
            $prendaId = $proceso->prenda_pedido_id;
            $todosLosDiseños = \App\Models\DisenoLogoPedido::query()
                ->whereHas('proceso.prenda', function ($query) use ($prendaId) {
                    $query->where('id', $prendaId);
                })
                ->where('estado', 'pendiente_por_confirmar')
                ->get();

            // Confirmar todos los diseños del recibo
            foreach ($todosLosDiseños as $d) {
                $d->update(['estado' => 'logo_confirmado']);
            }

            $cantidadConfirmada = $todosLosDiseños->count();

            return $this->json([
                'success' => true,
                'message' => "Se confirmaron $cantidadConfirmada diseño(s) correctamente",
                'cantidad' => $cantidadConfirmada,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al confirmar diseño logo', ['error' => $e->getMessage()]);
            return $this->failure('Error al confirmar diseño', 500);
        }
    }

    public function obtenerObservacionReciboProceso(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'pedido_id' => 'required|integer|exists:pedidos_produccion,id',
                'prenda_id' => 'nullable|integer',
                'tipo_proceso' => 'required|string|max:100',
            ]);

            $user = Auth::user();
            if (!$user) {
                return $this->failure('No autenticado', 401);
            }

            $pedidoId = (int) $validated['pedido_id'];
            $prendaId = (int) ($validated['prenda_id'] ?? 0);
            $tipoProceso = $validated['tipo_proceso'];

            // Verificar que el pedido pertenece al asesor
            $pedido = \App\Models\PedidoProduccion::find($pedidoId);
            if (!$pedido || $pedido->asesor_id !== $user->id) {
                return $this->failure('No tienes permiso para acceder a este pedido', 403);
            }

            // Obtener observación de la tabla genérica
            $row = \Illuminate\Support\Facades\DB::table('observaciones_recibos_procesos')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('tipo_proceso', strtoupper($tipoProceso))
                ->orderByDesc('updated_at')
                ->first();

            // Si no hay observación para el tipo_proceso exacto, buscar en prenda_pedido_id si viene especificado
            if (!$row && $prendaId > 0) {
                $row = \Illuminate\Support\Facades\DB::table('observaciones_recibos_procesos')
                    ->where('pedido_produccion_id', $pedidoId)
                    ->where('prenda_pedido_id', $prendaId)
                    ->where('tipo_proceso', strtoupper($tipoProceso))
                    ->orderByDesc('updated_at')
                    ->first();
            }

            return $this->json([
                'success' => true,
                'data' => [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => (int) ($row->prenda_pedido_id ?? $prendaId),
                    'tipo_proceso' => strtoupper($tipoProceso),
                    'observacion' => $row?->observacion,
                    'updated_at' => $row?->updated_at,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al obtener observación de recibo/proceso', ['error' => $e->getMessage()]);
            return $this->failure('Error al obtener observación', 500);
        }
    }
}
