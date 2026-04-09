<?php

namespace App\Application\SupervisorPedidos\Services;

use App\Application\SupervisorPedidos\DTOs\ListOrdersRequest;
use App\Application\Pedidos\Services\PrendaPedidoDescriptionFormatter;
use App\Application\Pedidos\Services\PrendaPedidoQuantityCalculator;
use App\Application\Pedidos\Services\PedidoProduccionCalculatorService;
use App\Infrastructure\Repositories\PedidoProduccionTrackingRepository;
use App\Models\News;
use App\Models\NewsVisto;
use App\Models\PedidoProduccion;
use App\Models\PedidoVistoSupervisor;
use App\Models\SeleccionPedido;
use App\Models\TipoCotizacion;
use App\Services\CalculadorDiasService;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de lectura para supervisor sobre pedidos de produccion.
 *
 * Encapsula calculos y datos derivados usados por la vista/listado
 * sin asumir que esto pertenece al dominio puro.
 */
class PedidoProduccionReadService
{
    public function __construct(
        private PedidoProduccionTrackingRepository $repository,
        private PedidoProduccionCalculatorService $calculatorService,
        private PrendaPedidoDescriptionFormatter $prendaDescriptionFormatter,
        private PrendaPedidoQuantityCalculator $prendaQuantityCalculator
    ) {}

    public function listOrders(ListOrdersRequest $request)
    {
        $query = PedidoProduccion::withTrashed()
            ->select([
                'id',
                'numero_pedido',
                'cliente',
                'novedades',
                'asesor_id',
                'forma_de_pago',
                'estado',
                'created_at',
                'updated_at',
                'ocultado_en',
            ])
            ->with(['asesora:id,name'])
            ->withCount(['prendas', 'epps']);

        $this->applyStatusFilters($query);
        $this->applyHiddenFilter($query, $request);
        $this->applyPendingNumberFilter($query);
        $this->applyEppOnlyFilter($query);
        $this->applyApprovalFilter($query, $request);
        $this->applySearchFilter($query, $request);
        $this->applyColumnFilters($query, $request);
        $this->applyDateFilters($query, $request);

        return $this->orderAndPaginate($query, $request);
    }

    public function findOrderForComparison(int $orderId): ?PedidoProduccion
    {
        return PedidoProduccion::with([
            'prendas',
            'asesora',
            'cotizacion' => function ($query) {
                $query->with([
                    'prendas' => function ($q) {
                        $q->with('tallas');
                    },
                    'asesor',
                ]);
            },
        ])->find($orderId);
    }

    public function findOrderForPdf(int $orderId): ?PedidoProduccion
    {
        return PedidoProduccion::with([
            'prendas' => function ($q) {
                $q->with(['color', 'tela', 'tipoManga', 'tipoBrocheBoton']);
            },
            'prendas.procesos',
        ])->find($orderId);
    }

    public function findOrderForDetailsView(int $orderId): ?PedidoProduccion
    {
        return PedidoProduccion::with(['prendas', 'prendas.procesos'])->find($orderId);
    }

    public function findOrderWithPrendas(int $orderId): ?PedidoProduccion
    {
        return PedidoProduccion::with('prendas')->find($orderId);
    }

    public function listDistinctStates(): array
    {
        return PedidoProduccion::distinct()
            ->pluck('estado')
            ->filter()
            ->values()
            ->toArray();
    }

    public function getSelectedOrders(?int $userId): array
    {
        if (!$userId) {
            return [];
        }

        return SeleccionPedido::where('user_id', $userId)
            ->where('seleccionado', true)
            ->pluck('pedido_id')
            ->toArray();
    }

    public function selectOrderForUser(int $orderId, int $userId): array
    {
        $exists = PedidoProduccion::query()->whereKey($orderId)->exists();
        if (!$exists) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        $selection = SeleccionPedido::updateOrCreate([
            'pedido_id' => $orderId,
            'user_id' => $userId,
        ], [
            'seleccionado' => true,
        ]);

        return [
            'id' => $selection->id,
            'pedido_id' => $selection->pedido_id,
            'user_id' => $selection->user_id,
            'seleccionado' => $selection->seleccionado,
        ];
    }

    public function deselectOrderForUser(int $orderId, int $userId): void
    {
        $selection = SeleccionPedido::where('pedido_id', $orderId)
            ->where('user_id', $userId)
            ->first();

        if ($selection) {
            $selection->delete();
        }
    }

    /**
     * Deselecciona un pedido para TODOS los supervisores
     * Se ejecuta cuando se actualiza un pedido (desde la asesora)
     */
    public function deselectOrderForAllUsers(int $orderId): void
    {
        SeleccionPedido::where('pedido_id', $orderId)->delete();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getOrderSelectionsForUser(int $userId): array
    {
        return SeleccionPedido::paraUsuario($userId)
            ->get(['id', 'pedido_id', 'user_id', 'seleccionado'])
            ->toArray();
    }

    /**
     * @return array{total:int,logo:int}
     */
    public function getPendingOrdersCount(): array
    {
        $baseQuery = PedidoProduccion::query()
            ->where('estado', 'PENDIENTE_SUPERVISOR')
            ->whereNull('ocultado_en')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '');

        $totalPendientes = (clone $baseQuery)->count();

        $logoTipoId = TipoCotizacion::getIdPorCodigo('logo');
        $pendientesLogo = 0;

        if ($logoTipoId) {
            $pendientesLogo = (clone $baseQuery)
                ->whereHas('cotizacion', function ($q) use ($logoTipoId) {
                    $q->where('tipo_cotizacion_id', $logoTipoId);
                })
                ->count();
        }

        return [
            'total' => (int) $totalPendientes,
            'logo' => (int) $pendientesLogo,
        ];
    }

    public function getOrderFilterOptions(string $field): array
    {
        return match ($field) {
            'numero' => PedidoProduccion::distinct()
                ->pluck('numero_pedido')
                ->filter()
                ->sort()
                ->values()
                ->toArray(),
            'cliente' => PedidoProduccion::distinct()
                ->pluck('cliente')
                ->filter()
                ->sort()
                ->values()
                ->toArray(),
            'estado' => PedidoProduccion::distinct()
                ->pluck('estado')
                ->filter()
                ->sort()
                ->values()
                ->toArray(),
            'asesora' => PedidoProduccion::with('asesora')
                ->get()
                ->pluck('asesora.name')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->toArray(),
            'forma_pago' => PedidoProduccion::distinct()
                ->pluck('forma_de_pago')
                ->filter()
                ->sort()
                ->values()
                ->toArray(),
            'fecha' => PedidoProduccion::query()
                ->selectRaw('DATE(created_at) as fecha')
                ->whereNotNull('created_at')
                ->distinct()
                ->orderByDesc('fecha')
                ->pluck('fecha')
                ->filter()
                ->values()
                ->toArray(),
            default => [],
        };
    }

    /**
     * @return array{
     *   notifications:\Illuminate\Support\Collection,
     *   news:\Illuminate\Support\Collection,
     *   totalPending:int,
     *   totalOrdersNotViewed:int,
     *   totalNews:int,
     *   totalNewsNotViewed:int,
     *   totalGeneral:int
     * }
     */
    public function getSupervisorNotificationsData(int $userId): array
    {
        $ordersPending = $this->getOrdersPendingApproval($userId);
        $news = $this->getNews($userId);
        $cancelledOrders = $this->getCancelledOrders($userId);
        $allNews = $news->concat($cancelledOrders)->sortByDesc('timestamp')->values();

        $totalOrdersNotViewed = $ordersPending->where('visto', false)->count();
        $totalNewsNotViewed = $allNews->where('visto', false)->count();

        return [
            'notifications' => $ordersPending->values(),
            'news' => $allNews,
            'totalPending' => $ordersPending->count(),
            'totalOrdersNotViewed' => $totalOrdersNotViewed,
            'totalNews' => $allNews->count(),
            'totalNewsNotViewed' => $totalNewsNotViewed,
            'totalGeneral' => $totalOrdersNotViewed + $totalNewsNotViewed,
        ];
    }

    public function markAllNotificationsAsRead(int $userId): int
    {
        $totalMarked = 0;

        $newsIds = News::whereIn('event_type', $this->getNotificationNewsTypes())
            ->where('created_at', '>=', now()->subMonths(3))
            ->pluck('id')
            ->toArray();

        foreach ($newsIds as $newsId) {
            NewsVisto::firstOrCreate([
                'news_id' => $newsId,
                'user_id' => $userId,
            ]);
            $totalMarked++;
        }

        $pendingOrderIds = PedidoProduccion::whereNull('aprobado_por_supervisor_en')
            ->where('estado', '!=', 'pendiente_cartera')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '>', 0)
            ->pluck('id')
            ->toArray();

        foreach ($pendingOrderIds as $pedidoId) {
            PedidoVistoSupervisor::firstOrCreate([
                'pedido_id' => $pedidoId,
                'user_id' => $userId,
            ]);
            $totalMarked++;
        }

        $cancelledIds = PedidoProduccion::where('estado', 'Anulada')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '>', 0)
            ->where('updated_at', '>=', now()->subMonths(3))
            ->pluck('id')
            ->toArray();

        foreach ($cancelledIds as $anuladaId) {
            PedidoVistoSupervisor::firstOrCreate([
                'pedido_id' => $anuladaId,
                'user_id' => $userId,
            ]);
            $totalMarked++;
        }

        return $totalMarked;
    }

    public function toggleNewsVisto(int $newsId, int $userId): bool
    {
        $existing = NewsVisto::where('news_id', $newsId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
            return false;
        }

        NewsVisto::create([
            'news_id' => $newsId,
            'user_id' => $userId,
        ]);

        return true;
    }

    public function togglePedidoVisto(int $pedidoId, int $userId): bool
    {
        $existing = PedidoVistoSupervisor::where('pedido_id', $pedidoId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
            return false;
        }

        PedidoVistoSupervisor::create([
            'pedido_id' => $pedidoId,
            'user_id' => $userId,
        ]);

        return true;
    }

    public function setOrderVisibility(int $orderId, bool $isHidden, ?int $actorUserId = null): void
    {
        $orden = PedidoProduccion::findOrFail($orderId);

        if ($isHidden) {
            $orden->update([
                'ocultado_en' => now(),
                'usuario_ocultado_por' => $actorUserId,
            ]);
            return;
        }

        $orden->update([
            'ocultado_en' => null,
            'usuario_ocultado_por' => null,
        ]);
    }

    public function calcularFechaEstimada(PedidoProduccion $pedido)
    {
        if (!$pedido->created_at || !$pedido->dia_de_entrega) {
            return null;
        }

        return $this->calculatorService->calcularFechaEstimada($pedido->created_at, $pedido->dia_de_entrega);
    }

    public function getAreaActual(PedidoProduccion $pedido): string
    {
        return $this->repository->getAreaActual($pedido);
    }

    public function procesoActualOptimizado(PedidoProduccion $pedido): string
    {
        if (!$pedido->relationLoaded('prendas')) {
            return $this->getAreaActual($pedido);
        }

        $procesos = $pedido->prendas
            ->flatMap(fn($prenda) => $prenda->procesos ?? collect())
            ->unique('proceso');

        return $this->calculatorService->determinarProcesoActualOptimizado($procesos);
    }

    public function getTotalDias(PedidoProduccion $pedido): ?string
    {
        if (!$pedido->created_at) {
            return null;
        }

        $ultimaFecha = $this->repository->getUltimaFechaProcesoFin($pedido);

        if (!$ultimaFecha) {
            $ultimaFecha = now()->toDateString();
        }

        $dias = CalculadorDiasService::calcularDiasHabiles(
            $pedido->created_at,
            $ultimaFecha
        );

        return CalculadorDiasService::formatearDias($dias);
    }

    public function getTotalDiasNumero(PedidoProduccion $pedido): int
    {
        $totalDias = $this->getTotalDias($pedido);

        if (!$totalDias) {
            return 0;
        }

        preg_match('/(\d+)/', $totalDias, $matches);
        return isset($matches[1]) ? (int) $matches[1] : 0;
    }

    public function getDesgloseDiasPorProceso(PedidoProduccion $pedido): array
    {
        return $this->repository->getDesgloseDiasPorProceso($pedido);
    }

    public function estaEnRetraso(PedidoProduccion $pedido): bool
    {
        $areaActual = $this->getAreaActual($pedido);
        return CalculadorDiasService::estaEnRetraso($areaActual, $pedido->fecha_estimada_de_entrega);
    }

    public function getDiasDeRetraso(PedidoProduccion $pedido): int
    {
        if (!$this->estaEnRetraso($pedido)) {
            return 0;
        }

        return CalculadorDiasService::calcularDiasDeRetraso($pedido->fecha_estimada_de_entrega);
    }

    public function calcularDiasHabilesDesdeCreacion(PedidoProduccion $pedido): string
    {
        if (!$pedido->created_at) {
            return '-';
        }

        $diasCalculados = CalculadorDiasService::calcularDiasHabilesSinIncluirInicio($pedido->created_at);

        $pluralSuffix = $diasCalculados > 1 ? 's' : '';
        $diasFormateado = $diasCalculados . ' día' . $pluralSuffix;

        return $diasCalculados > 0 ? $diasFormateado : '-';
    }

    public function getDescripcionPrendas(PedidoProduccion $pedido): string
    {
        if (!$pedido->relationLoaded('prendas') || $pedido->prendas->isEmpty()) {
            return '';
        }

        $descripciones = $pedido->prendas->map(function ($prenda, $index) {
            return $this->prendaDescriptionFormatter->formatDetailed($prenda, $index + 1);
        })->toArray();

        return implode("\n\n", $descripciones);
    }

    public function getCantidadTotal(PedidoProduccion $pedido): int
    {
        if (!$pedido->relationLoaded('prendas') || $pedido->prendas->isEmpty()) {
            return 0;
        }

        $total = 0;
        foreach ($pedido->prendas as $prenda) {
            $total += $this->prendaQuantityCalculator->calculate($prenda);
        }

        return $total;
    }

    public function esSoloEpp(PedidoProduccion $pedido): bool
    {
        if (isset($pedido->prendas_count) || isset($pedido->epps_count)) {
            return ((int) ($pedido->prendas_count ?? 0) === 0)
                && ((int) ($pedido->epps_count ?? 0) > 0);
        }

        $tienePrendas = $pedido->relationLoaded('prendas')
            ? $pedido->prendas->isNotEmpty()
            : $pedido->prendas()->exists();

        if ($tienePrendas) {
            return false;
        }

        return $pedido->relationLoaded('epps')
            ? $pedido->epps->isNotEmpty()
            : $pedido->epps()->exists();
    }

    public function getNovedadesCount(PedidoProduccion $pedido): int
    {
        if (empty($pedido->novedades)) {
            return 0;
        }

        return count(array_filter(explode("\n\n", $pedido->novedades)));
    }

    public function getNombresPrendas(PedidoProduccion $pedido): string
    {
        if (!$pedido->relationLoaded('prendas') || $pedido->prendas->isEmpty()) {
            return '-';
        }

        return $pedido->prendas
            ->pluck('nombre_prenda')
            ->unique()
            ->implode(', ') ?: '-';
    }

    private function applyStatusFilters($query): void
    {
        $query->whereNotIn('estado', ['pendiente_cartera', 'RECHAZADO_CARTERA']);
    }

    private function applyHiddenFilter($query, ListOrdersRequest $request): void
    {
        if ($request->getMostrar() === 'ocultos') {
            $query->whereNotNull('ocultado_en');
            return;
        }

        $query->whereNull('ocultado_en');
    }

    private function applyPendingNumberFilter($query): void
    {
        $query->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '');
    }

    private function applyEppOnlyFilter($query): void
    {
        $query->where(function ($q) {
            $q->whereHas('prendas')
                ->orWhere(function ($subQuery) {
                    $subQuery->whereDoesntHave('prendas')
                        ->whereDoesntHave('epps');
                });
        });
    }

    private function applyApprovalFilter($query, ListOrdersRequest $request): void
    {
        if (!$request->getAprobacion()) {
            return;
        }

        if ($request->getAprobacion() === 'pendiente') {
            $query->whereIn('estado', ['PENDIENTE_SUPERVISOR', 'No iniciado']);

            if ($request->getTipo() === 'logo') {
                $query->whereHas('cotizacion', function ($q) {
                    $q->where('tipo', 'logo');
                });
            }
            return;
        }

        if ($request->getAprobacion() === 'aprobadas') {
            $query->whereIn('estado', ['Pendiente', 'En Ejecución', 'Finalizada', 'Anulada']);
        }
    }

    private function applySearchFilter($query, ListOrdersRequest $request): void
    {
        if (!$request->getBusqueda()) {
            return;
        }

        $busqueda = $request->getBusqueda();
        $query->where(function ($q) use ($busqueda) {
            $q->where('numero_pedido', 'like', '%' . $busqueda . '%')
                ->orWhere('cliente', 'like', '%' . $busqueda . '%');
        });
    }

    private function applyColumnFilters($query, ListOrdersRequest $request): void
    {
        if ($request->getNumero()) {
            $query->whereIn('numero_pedido', explode(',', $request->getNumero()));
        }

        if ($request->getCliente()) {
            $query->whereIn('cliente', explode(',', $request->getCliente()));
        }

        if ($request->getFormaPago()) {
            $query->whereIn('forma_de_pago', explode(',', $request->getFormaPago()));
        }

        if ($request->getEstado()) {
            $estado = $request->getEstado();
            if ($estado === 'En Producción') {
                $query->whereIn('estado', ['No iniciado', 'En Ejecución']);
            } else {
                $query->where('estado', $estado);
            }
        } else {
            $query->where('estado', '!=', 'Anulada');
        }

        if ($request->getAsesora()) {
            $asesoras = explode(',', $request->getAsesora());
            $query->whereHas('asesora', function ($q) use ($asesoras) {
                $q->whereIn('name', $asesoras);
            });
        }
    }

    private function applyDateFilters($query, ListOrdersRequest $request): void
    {
        if ($request->getFecha()) {
            $fechas = array_values(array_filter(array_map('trim', explode(',', $request->getFecha()))));
            if (!empty($fechas)) {
                $query->where(function ($q) use ($fechas) {
                    foreach ($fechas as $fecha) {
                        $q->orWhereDate('created_at', $fecha);
                    }
                });
            }
            return;
        }

        if ($request->getFechaDesde()) {
            $query->whereDate('created_at', '>=', $request->getFechaDesde());
        }

        if ($request->getFechaHasta()) {
            $query->whereDate('created_at', '<=', $request->getFechaHasta());
        }
    }

    private function orderAndPaginate($query, ListOrdersRequest $request)
    {
        $latestAnexoSubquery = DB::table('pedido_anexos_historial')
            ->select('pedido_produccion_id', DB::raw('MAX(created_at) as latest_anexo_at'))
            ->groupBy('pedido_produccion_id');

        return $query->leftJoinSub($latestAnexoSubquery, 'pah_latest', function ($join) {
            $join->on('pah_latest.pedido_produccion_id', '=', 'pedidos_produccion.id');
        })
            // Orden por "última actividad":
            // 1) última modificación registrada en la tabla de historial de anexos
            // 2) si no hay modificaciones, fecha de creación del pedido
            ->orderByRaw('COALESCE(pah_latest.latest_anexo_at, pedidos_produccion.created_at) DESC')
            ->orderBy('pedidos_produccion.created_at', 'desc')
            ->orderBy('pedidos_produccion.numero_pedido', 'desc')
            ->paginate($request->getPerPage(), ['pedidos_produccion.*'], 'page', $request->getPage())
            ->appends($request->getAppends());
    }

    private function getOrdersPendingApproval(int $userId): \Illuminate\Support\Collection
    {
        $pedidosVistosIds = DB::table('pedidos_vistos_supervisor')
            ->where('user_id', $userId)
            ->pluck('pedido_id')
            ->toArray();

        $ordenesPendientes = DB::table('pedidos_produccion')
            ->whereNull('aprobado_por_supervisor_en')
            ->where('estado', '!=', 'Anulada')
            ->where('estado', '!=', 'pendiente_cartera')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '>', 0)
            ->leftJoin('users as u', 'pedidos_produccion.asesor_id', '=', 'u.id')
            ->select([
                'pedidos_produccion.id',
                'numero_pedido',
                'cliente',
                'asesor_id',
                'pedidos_produccion.created_at',
                'estado',
                'forma_de_pago',
                'u.name as asesor',
            ])
            ->orderBy('pedidos_produccion.created_at', 'desc')
            ->get();

        return $ordenesPendientes->map(function ($orden) use ($pedidosVistosIds) {
            return [
                'id' => $orden->id,
                'numero_pedido' => $orden->numero_pedido,
                'cliente' => $orden->cliente,
                'asesor' => $orden->asesor ?? 'N/A',
                'fecha' => $orden->created_at ? \Carbon\Carbon::parse($orden->created_at)->format('d/m/Y H:i') : '',
                'estado' => $orden->estado,
                'titulo' => 'Orden #' . $orden->numero_pedido . ' - ' . $orden->cliente,
                'mensaje' => 'Cliente: ' . $orden->cliente . ' | Asesor: ' . ($orden->asesor ?? 'N/A'),
                'tipo' => 'orden_pendiente_aprobacion',
                'timestamp' => $orden->created_at ? \Carbon\Carbon::parse($orden->created_at)->toIso8601String() : null,
                'visto' => in_array($orden->id, $pedidosVistosIds),
            ];
        });
    }

    private function getNews(int $userId): \Illuminate\Support\Collection
    {
        $newsVistosIds = DB::table('news_vistos')
            ->where('user_id', $userId)
            ->pluck('news_id')
            ->toArray();

        $novedades = DB::table('news')
            ->whereIn('event_type', $this->getNotificationNewsTypes())
            ->where('created_at', '>=', now()->subMonths(3))
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

        return $novedades->map(function ($news) use ($newsVistosIds) {
            $icono = match ($news->event_type) {
                'pedido_creado', 'order_created' => 'add_shopping_cart',
                'prenda_agregada' => 'checkroom',
                'prenda_modificada' => 'edit',
                'epp_agregado' => 'health_and_safety',
                'epp_modificado' => 'edit',
                'order_status_changed' => 'sync_alt',
                default => 'notifications',
            };

            $color = match ($news->event_type) {
                'pedido_creado', 'order_created' => '#10b981',
                'prenda_agregada' => '#3b82f6',
                'prenda_modificada' => '#f59e0b',
                'epp_agregado' => '#8b5cf6',
                'epp_modificado' => '#f59e0b',
                'order_status_changed' => '#6366f1',
                default => '#6b7280',
            };

            return [
                'id' => $news->id,
                'tipo' => $news->event_type,
                'descripcion' => $news->description,
                'pedido' => $news->pedido,
                'fecha' => \Carbon\Carbon::parse($news->created_at)->format('d/m/Y h:i A'),
                'icono' => $icono,
                'color' => $color,
                'timestamp' => \Carbon\Carbon::parse($news->created_at)->toIso8601String(),
                'metadata' => $news->metadata,
                'visto' => in_array($news->id, $newsVistosIds),
                'source' => 'news',
            ];
        });
    }

    private function getCancelledOrders(int $userId): \Illuminate\Support\Collection
    {
        $pedidosVistosIds = DB::table('pedidos_vistos_supervisor')
            ->where('user_id', $userId)
            ->pluck('pedido_id')
            ->toArray();

        $ordenesAnuladas = DB::table('pedidos_produccion')
            ->where('estado', 'Anulada')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '>', 0)
            ->where('pedidos_produccion.updated_at', '>=', now()->subMonths(3))
            ->leftJoin('users as u', 'pedidos_produccion.asesor_id', '=', 'u.id')
            ->select([
                'pedidos_produccion.id',
                'numero_pedido',
                'cliente',
                'asesor_id',
                'pedidos_produccion.updated_at',
                'u.name as asesor',
            ])
            ->orderBy('pedidos_produccion.updated_at', 'desc')
            ->limit(50)
            ->get();

        return $ordenesAnuladas->map(function ($orden) use ($pedidosVistosIds) {
            return [
                'id' => 'anulada_' . $orden->id,
                'tipo' => 'pedido_anulado',
                'descripcion' => 'Orden #' . $orden->numero_pedido . ' - ' . $orden->cliente . ' fue ANULADA',
                'pedido' => $orden->numero_pedido,
                'fecha' => \Carbon\Carbon::parse($orden->updated_at)->format('d/m/Y h:i A'),
                'icono' => 'cancel',
                'color' => '#ef4444',
                'timestamp' => \Carbon\Carbon::parse($orden->updated_at)->toIso8601String(),
                'metadata' => null,
                'visto' => in_array($orden->id, $pedidosVistosIds),
                'source' => 'anulada',
            ];
        });
    }

    /**
     * @return array<int, string>
     */
    private function getNotificationNewsTypes(): array
    {
        return [
            'pedido_creado',
            'order_created',
            'prenda_agregada',
            'prenda_modificada',
            'epp_agregado',
            'epp_modificado',
            'order_status_changed',
        ];
    }
}
