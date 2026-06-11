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
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PedidoVistoSupervisor;
use App\Models\ProcesoPrenda;
use App\Models\SeleccionPedido;
use App\Models\TipoCotizacion;
use App\Services\CalculadorDiasService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $perfEnabled = app()->environment(['local', 'development'])
            && request()?->attributes?->get('sp_ordenes_perf_enabled') === true;
        $perfStartedAt = microtime(true);
        $filtersStartedAt = microtime(true);

        $query = PedidoProduccion::withTrashed()
            ->select([
                'pedidos_produccion.id',
                'pedidos_produccion.numero_pedido',
                'pedidos_produccion.cliente',
                'pedidos_produccion.novedades',
                'pedidos_produccion.asesor_id',
                'pedidos_produccion.forma_de_pago',
                'pedidos_produccion.dia_de_entrega',
                'pedidos_produccion.aprobado_por_cartera_en',
                'pedidos_produccion.aprobado_por_supervisor_en',
                'pedidos_produccion.estado',
                'pedidos_produccion.created_at',
                'pedidos_produccion.updated_at',
                'pedidos_produccion.ocultado_en',
                DB::raw('pedidos_vistos_supervisor.id IS NOT NULL as is_reviewed_by_user'),
            ])
            ->leftJoin('pedidos_vistos_supervisor', function ($join) use ($request) {
                $join->on('pedidos_vistos_supervisor.pedido_id', '=', 'pedidos_produccion.id')
                    ->where('pedidos_vistos_supervisor.user_id', '=', $request->getUserId());
            })
            ->with(['asesora:id,name']);

        $this->applyStatusFilters($query, $request);
        $this->applyHiddenFilter($query, $request);
        if (!$request->isVisualizador()) {
            $this->applyDespachoVisibilityFilter($query, $request);
        }
        $this->applyPendingNumberFilter($query);
        $this->applyEppOnlyFilter($query);
        if (!$request->isVisualizador()) {
            $this->applyApprovalFilter($query, $request);
        }
        $this->applySearchFilter($query, $request);
        $this->applyColumnFilters($query, $request);
        $this->applyDateFilters($query, $request);
        $filtersMs = (microtime(true) - $filtersStartedAt) * 1000;

        $queryExecutionStartedAt = microtime(true);

        Log::debug('[PedidoProduccionReadService] SQL Query:', ['sql' => $query->toSql()]);
        Log::debug('[PedidoProduccionReadService] SQL Bindings:', ['bindings' => $query->getBindings()]);

        $result = $this->orderAndPaginate($query, $request);

        // Asegurarse de que el atributo is_reviewed_by_user se establezca en el modelo
        $result->getCollection()->transform(function ($pedido) {
            $pedido->is_reviewed_by_user = (bool) $pedido->is_reviewed_by_user;
            return $pedido;
        });

        $queryExecutionMs = (microtime(true) - $queryExecutionStartedAt) * 1000;

        if ($perfEnabled) {
            Log::info('[SP_ORDENES_PERF] READ_SERVICE_LIST_ORDERS', [
                'filters_build_ms' => round($filtersMs, 2),
                'query_main_and_eager_ms' => round($queryExecutionMs, 2),
                'service_total_ms' => round((microtime(true) - $perfStartedAt) * 1000, 2),
                'current_page' => method_exists($result, 'currentPage') ? $result->currentPage() : null,
                'per_page' => method_exists($result, 'perPage') ? $result->perPage() : null,
                'items_count' => method_exists($result, 'count') ? $result->count() : null,
            ]);
        }

        return $result;
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
     * @return array{total:int,logo:int,cartera_no_aprobado:int,devuelto_a_asesora:int}
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

        $carteraNoAprobadoQuery = PedidoProduccion::query()
            ->whereNull('ocultado_en')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->where('estado', 'pendiente_cartera')
            ->whereNull('aprobado_por_cartera_en');

        $this->applyCarteraProductionVisibilityFilter($carteraNoAprobadoQuery);
        $pendientesCarteraNoAprobado = (clone $carteraNoAprobadoQuery)->count();
        $devueltoAsesoraCount = PedidoProduccion::query()
            ->where('estado', 'DEVUELTO_A_ASESORA')
            ->whereNull('ocultado_en')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->count();

        return [
            'total' => (int) $totalPendientes,
            'logo' => (int) $pendientesLogo,
            'cartera_no_aprobado' => (int) $pendientesCarteraNoAprobado,
            'devuelto_a_asesora' => (int) $devueltoAsesoraCount,
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
        $raw = trim((string) ($pedido->novedades ?? ''));
        if ($raw === '') {
            return 0;
        }

        // Soporta novedades guardadas como JSON (array) o texto plano.
        if (str_starts_with($raw, '[') || str_starts_with($raw, '{')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return count(array_filter(array_map(
                    static fn ($item) => trim((string) $item),
                    $decoded
                )));
            }
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $raw);
        $items = preg_split('/\n{2,}/', $normalized) ?: [];
        $count = count(array_filter(array_map('trim', $items)));

        // Si existe texto pero no separadores dobles, cuenta al menos 1 novedad.
        return $count > 0 ? $count : 1;
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

    public function getDiasEntregaHtml(PedidoProduccion $pedido): ?string
    {
        try {
            $hoy = now()->startOfDay();
            $fechaEstimada = $this->resolveFechaEstimadaDeEntrega($pedido);

            if ($fechaEstimada) {
                $fechaEstimada = $fechaEstimada->copy()->startOfDay();

                if ($fechaEstimada->equalTo($hoy)) {
                    return $this->buildDiasEntregaHtml('Vence hoy', null, $fechaEstimada->format('d/m/Y'));
                }

                if ($fechaEstimada->greaterThan($hoy)) {
                    $diasRestantes = $this->contarDiasHabilesEntre($hoy, $fechaEstimada);
                    return $this->buildDiasEntregaHtml(
                        $diasRestantes . ' días',
                        'restantes',
                        $fechaEstimada->format('d/m/Y')
                    );
                }

                $diasVencidos = $this->contarDiasHabilesEntre($fechaEstimada, $hoy);
                return $this->buildDiasEntregaHtml(
                    'Vencido hace ' . $diasVencidos . ' días',
                    null,
                    $fechaEstimada->format('d/m/Y'),
                    '#b91c1c'
                );
            }

            if (!$pedido->created_at) {
                return null;
            }

            $diasTranscurridos = $this->contarDiasHabilesEntre(
                Carbon::parse($pedido->created_at)->startOfDay(),
                $hoy
            );

            return $this->buildDiasEntregaHtml(
                $diasTranscurridos . ' días',
                'transcurridos',
                null
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveFechaEstimadaDeEntrega(PedidoProduccion $pedido): ?Carbon
    {
        $valor = $pedido->fecha_estimada_de_entrega
            ?? $pedido->fecha_estimada_entrega
            ?? $pedido->fecha_estimada
            ?? null;

        if (empty($valor)) {
            return null;
        }

        try {
            return Carbon::parse($valor);
        } catch (\Throwable) {
            return null;
        }
    }

    private function contarDiasHabilesEntre(Carbon $inicio, Carbon $fin): int
    {
        $inicio = $inicio->copy()->startOfDay();
        $fin = $fin->copy()->startOfDay();

        if ($inicio->equalTo($fin)) {
            return 0;
        }

        if ($inicio->greaterThan($fin)) {
            [$inicio, $fin] = [$fin, $inicio];
        }

        $contados = 0;
        $cursor = $inicio->copy()->addDay();

        while ($cursor->lte($fin)) {
            if ($cursor->isBusinessDay()) {
                $contados++;
            }
            $cursor->addDay();
        }

        return max(0, $contados);
    }

    private function buildDiasEntregaHtml(
        string $lineaPrincipal,
        ?string $lineaSecundaria = null,
        ?string $fechaEstimada = null,
        string $colorPrincipal = '#dc2626'
    ): string {
        $html = '<span style="display: inline-flex; flex-direction: column; line-height: 1.1; color: ' . $colorPrincipal . '; font-weight: 700; font-size: 0.78rem;">';
        $html .= '<span>' . e($lineaPrincipal) . '</span>';

        if ($lineaSecundaria !== null && $lineaSecundaria !== '') {
            $html .= '<span>' . e($lineaSecundaria) . '</span>';
        }

        if ($fechaEstimada !== null && $fechaEstimada !== '') {
            $html .= '<span style="margin-top: 0.2rem; color: #6b7280; font-weight: 600; font-size: 0.72rem;">Est.: ' . e($fechaEstimada) . '</span>';
        }

        $html .= '</span>';

        return $html;
    }

    private function applyStatusFilters($query, ListOrdersRequest $request): void
    {
        // Para el visualizador, no aplicar filtros restrictivos de estado
        if ($request->isVisualizador()) {
            $query->whereNotNull('numero_pedido')
                ->where('numero_pedido', '!=', '');
            return;
        }

        $filtrosCartera = array_values(array_filter(array_map('trim', explode(',', (string) ($request->getAprobacionCartera() ?? '')))));
        $incluyeNoAprobadoCartera = in_array('no_aprobado', $filtrosCartera, true);
        $estadoSolicitado = trim((string) ($request->getEstado() ?? ''));

        $estadosExcluidos = [];
        if (!$request->shouldIncludeDespacho()) {
            $estadosExcluidos[] = 'Entregado';
        }
        if ($estadoSolicitado !== 'Anulada') {
            $estadosExcluidos[] = 'RECHAZADO_CARTERA';
        }
        if (!$incluyeNoAprobadoCartera) {
            $estadosExcluidos[] = 'pendiente_cartera';
        }

        $query->whereNotIn('estado', $estadosExcluidos)
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '');

        if ($incluyeNoAprobadoCartera) {
            $this->applyCarteraProductionVisibilityFilter($query);
        }
    }

    /**
     * Replica la lógica de Cartera para ocultar pedidos no productivos:
     * - Excluye pedidos solo EPP (sin prendas)
     * - Excluye pedidos con solo prendas de bodega sin procesos
     */
    private function applyCarteraProductionVisibilityFilter($query): void
    {
        $procesosDetalleTable = (new PedidosProcesosPrendaDetalle())->getTable();
        $procesosLegacyTable = (new ProcesoPrenda())->getTable();

        $query->where(function ($q) use ($procesosDetalleTable, $procesosLegacyTable) {
            // Tiene al menos una prenda normal (no bodega)
            $q->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('prendas_pedido as pp')
                    ->whereColumn('pp.pedido_produccion_id', 'pedidos_produccion.id')
                    ->whereNull('pp.deleted_at')
                    ->where(function ($normal) {
                        $normal->whereNull('pp.de_bodega')
                            ->orWhere('pp.de_bodega', 0)
                            ->orWhere('pp.de_bodega', '0')
                            ->orWhere('pp.de_bodega', false);
                    });
            })
            // O tiene al menos una prenda de bodega CON procesos productivos
            ->orWhereExists(function ($sub) use ($procesosDetalleTable, $procesosLegacyTable) {
                $sub->selectRaw('1')
                    ->from('prendas_pedido as ppb')
                    ->whereColumn('ppb.pedido_produccion_id', 'pedidos_produccion.id')
                    ->whereNull('ppb.deleted_at')
                    ->where(function ($bodega) {
                        $bodega->where('ppb.de_bodega', 1)
                            ->orWhere('ppb.de_bodega', '1')
                            ->orWhere('ppb.de_bodega', true);
                    })
                    ->where(function ($procs) use ($procesosDetalleTable, $procesosLegacyTable) {
                        $procs->whereExists(function ($existsDetalle) use ($procesosDetalleTable) {
                            $existsDetalle->selectRaw('1')
                                ->from($procesosDetalleTable . ' as pd')
                                ->whereColumn('pd.prenda_pedido_id', 'ppb.id')
                                ->whereNull('pd.deleted_at');
                        })->orWhereExists(function ($existsLegacy) use ($procesosLegacyTable) {
                            $existsLegacy->selectRaw('1')
                                ->from($procesosLegacyTable . ' as pl')
                                ->whereColumn('pl.prenda_pedido_id', 'ppb.id')
                                ->whereNull('pl.deleted_at');
                        });
                    });
            });
        });
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

    private function applyDespachoVisibilityFilter($query, ListOrdersRequest $request): void
    {
        if ($request->shouldIncludeDespacho()) {
            return;
        }

        $query->where(function ($q) {
            $q->whereNull('area')
                ->orWhereRaw("LOWER(TRIM(area)) <> 'despacho'")
                ->orWhereRaw("UPPER(TRIM(COALESCE(estado, ''))) NOT IN ('ENTREGADO', 'FINALIZADA', 'FINALIZADO')");
        });

        // Cuando NO se activa "ver todos despacho", ocultar tambien pedidos
        // que ya no tienen prendas pendientes de entrega, aunque su estado
        // todavia no haya sido normalizado a ENTREGADO/FINALIZADO.
        $query->whereRaw("(
            SELECT COUNT(*)
            FROM prendas_pedido pp
            LEFT JOIN prenda_entregas pe ON pe.prenda_pedido_id = pp.id
            WHERE pp.pedido_produccion_id = pedidos_produccion.id
              AND pp.deleted_at IS NULL
              AND NOT (
                COALESCE(pe.entregado, 0) = 1
                OR (
                    COALESCE((
                        SELECT SUM(ppt.cantidad)
                        FROM prenda_pedido_tallas ppt
                        WHERE ppt.prenda_pedido_id = pp.id
                    ), 0) > 0
                    AND COALESCE((
                        SELECT SUM(pem.cantidad_entregada)
                        FROM prenda_entrega_movimientos pem
                        WHERE pem.prenda_pedido_id = pp.id
                    ), 0) >= COALESCE((
                        SELECT SUM(ppt.cantidad)
                        FROM prenda_pedido_tallas ppt
                        WHERE ppt.prenda_pedido_id = pp.id
                    ), 0)
                )
              )
        ) > 0");
    }

    private function applyEppOnlyFilter($query): void
    {
        // Mantener 1 fila por pedido (sin JOIN directo) para que paginate/count
        // no duplique registros cuando un pedido tiene varias prendas.
        $query->whereExists(function ($subquery) {
            $subquery->selectRaw('1')
                ->from('prendas_pedido')
                ->whereColumn('prendas_pedido.pedido_produccion_id', 'pedidos_produccion.id')
                ->whereNull('prendas_pedido.deleted_at');
        })->select([
            'pedidos_produccion.*',
            DB::raw('pedidos_vistos_supervisor.id IS NOT NULL as is_reviewed_by_user'),
            DB::raw("(
                SELECT COUNT(*)
                FROM prendas_pedido pp
                LEFT JOIN prenda_entregas pe ON pe.prenda_pedido_id = pp.id
                WHERE pp.pedido_produccion_id = pedidos_produccion.id
                  AND pp.deleted_at IS NULL
                  AND NOT (
                    COALESCE(pe.entregado, 0) = 1
                    OR (
                        COALESCE((
                            SELECT SUM(ppt.cantidad)
                            FROM prenda_pedido_tallas ppt
                            WHERE ppt.prenda_pedido_id = pp.id
                        ), 0) > 0
                        AND COALESCE((
                            SELECT SUM(pem.cantidad_entregada)
                            FROM prenda_entrega_movimientos pem
                            WHERE pem.prenda_pedido_id = pp.id
                        ), 0) >= COALESCE((
                            SELECT SUM(ppt.cantidad)
                            FROM prenda_pedido_tallas ppt
                            WHERE ppt.prenda_pedido_id = pp.id
                        ), 0)
                    )
                  )
            ) as prendas_pendientes_entrega_count"),
        ]);
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

        $busqueda = trim((string) $request->getBusqueda());
        $esNumerica = ctype_digit($busqueda);

        $query->where(function ($q) use ($busqueda, $esNumerica) {
            if ($esNumerica) {
                // Prioriza igualdad/prefijo en número para permitir mejor uso de índices.
                $q->where('numero_pedido', '=', $busqueda)
                    ->orWhere('numero_pedido', 'like', $busqueda . '%');
            } else {
                $q->where('numero_pedido', 'like', '%' . $busqueda . '%');
            }

            $q->orWhere('cliente', 'like', '%' . $busqueda . '%');
        });
    }

    private function applyColumnFilters($query, ListOrdersRequest $request): void
    {
        if ($request->getNumero()) {
            $numeros = array_values(array_filter(array_map('trim', explode(',', (string) $request->getNumero()))));
            if (!empty($numeros)) {
                $query->where(function ($q) use ($numeros) {
                    foreach ($numeros as $numero) {
                        $q->orWhere('numero_pedido', 'like', '%' . $numero . '%');
                    }
                });
            }
        }

        if ($request->getCliente()) {
            $clientes = array_values(array_filter(array_map('trim', explode(',', (string) $request->getCliente()))));
            if (!empty($clientes)) {
                $query->where(function ($q) use ($clientes) {
                    foreach ($clientes as $cliente) {
                        $q->orWhere('cliente', 'like', '%' . $cliente . '%');
                    }
                });
            }
        }

        if ($request->getFormaPago()) {
            $query->whereIn('forma_de_pago', explode(',', $request->getFormaPago()));
        }

        if ($request->getEstado()) {
            $estado = $request->getEstado();
            if ($estado === 'En Producción') {
                $query->whereIn('estado', ['No iniciado', 'En Ejecución']);
            } elseif ($estado === 'Anulada') {
                $query->whereIn('estado', ['Anulada', 'RECHAZADO_CARTERA']);
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

        if ($request->getAprobacionCartera()) {
            $filtrosCartera = array_values(array_filter(array_map('trim', explode(',', (string) $request->getAprobacionCartera()))));
            if (!empty($filtrosCartera)) {
                if (in_array('no_aprobado', $filtrosCartera, true)) {
                    // Regla de negocio solicitada:
                    // "No aprobado por cartera" solo debe listar estado pendiente_cartera.
                    $query->where('estado', 'pendiente_cartera');
                }

                $query->where(function ($q) use ($filtrosCartera) {
                    foreach ($filtrosCartera as $filtro) {
                        if ($filtro === 'no_aprobado') {
                            $q->orWhereNull('aprobado_por_cartera_en');
                        } elseif ($filtro === 'aprobado') {
                            $q->orWhereNotNull('aprobado_por_cartera_en');
                        }
                    }
                });
            }
        }
    }

    private function applyDateFilters($query, ListOrdersRequest $request): void
    {
        if ($request->getFecha()) {
            $fechas = array_values(array_filter(array_map('trim', explode(',', $request->getFecha()))));
            if (!empty($fechas)) {
                $query->where(function ($q) use ($fechas) {
                    foreach ($fechas as $fecha) {
                        $q->orWhereDate('pedidos_produccion.created_at', $fecha);
                    }
                });
            }
            return;
        }

        if ($request->getFechaDesde()) {
            $query->whereDate('pedidos_produccion.created_at', '>=', $request->getFechaDesde());
        }

        if ($request->getFechaHasta()) {
            $query->whereDate('pedidos_produccion.created_at', '<=', $request->getFechaHasta());
        }
    }

    private function orderAndPaginate($query, ListOrdersRequest $request)
    {
        $filtrosCartera = array_values(array_filter(array_map('trim', explode(',', (string) ($request->getAprobacionCartera() ?? '')))));
        $incluyeNoAprobadoCartera = in_array('no_aprobado', $filtrosCartera, true);

        if ($incluyeNoAprobadoCartera) {
            return $query
                ->orderBy('pedidos_produccion.updated_at', 'desc')
                ->orderBy('pedidos_produccion.created_at', 'desc')
                ->paginate($request->getPerPage(), ['pedidos_produccion.*'], 'page', $request->getPage())
                ->appends($request->getAppends());
        }
        // Para búsquedas puntuales evitar ORDER BY correlacionado costoso.
        if ($request->getBusqueda()) {
            return $query
                ->orderBy('pedidos_produccion.created_at', 'desc')
                ->paginate($request->getPerPage(), ['pedidos_produccion.*'], 'page', $request->getPage())
                ->appends($request->getAppends());
        }

        return $query
            ->orderByRaw('GREATEST(
                COALESCE((
                    SELECT MAX(ultima) FROM (
                        SELECT MAX(prendas_pedido.updated_at) as ultima FROM prendas_pedido
                        WHERE prendas_pedido.pedido_produccion_id = pedidos_produccion.id
                        UNION ALL
                        SELECT MAX(prenda_pedido_tallas.updated_at) FROM prenda_pedido_tallas
                        JOIN prendas_pedido ON prenda_pedido_tallas.prenda_pedido_id = prendas_pedido.id
                        WHERE prendas_pedido.pedido_produccion_id = pedidos_produccion.id
                        UNION ALL
                        SELECT MAX(prenda_pedido_talla_colores.updated_at) FROM prenda_pedido_talla_colores
                        JOIN prenda_pedido_tallas ON prenda_pedido_talla_colores.prenda_pedido_talla_id = prenda_pedido_tallas.id
                        JOIN prendas_pedido ON prenda_pedido_tallas.prenda_pedido_id = prendas_pedido.id
                        WHERE prendas_pedido.pedido_produccion_id = pedidos_produccion.id
                        UNION ALL
                        SELECT MAX(prenda_pedido_colores_telas.updated_at) FROM prenda_pedido_colores_telas
                        JOIN prendas_pedido ON prenda_pedido_colores_telas.prenda_pedido_id = prendas_pedido.id
                        WHERE prendas_pedido.pedido_produccion_id = pedidos_produccion.id
                        UNION ALL
                        SELECT MAX(prenda_fotos_pedido.updated_at) FROM prenda_fotos_pedido
                        JOIN prendas_pedido ON prenda_fotos_pedido.prenda_pedido_id = prendas_pedido.id
                        WHERE prendas_pedido.pedido_produccion_id = pedidos_produccion.id
                        UNION ALL
                        SELECT MAX(prenda_fotos_tela_pedido.updated_at) FROM prenda_fotos_tela_pedido
                        JOIN prenda_pedido_colores_telas ON prenda_fotos_tela_pedido.prenda_pedido_colores_telas_id = prenda_pedido_colores_telas.id
                        JOIN prendas_pedido ON prenda_pedido_colores_telas.prenda_pedido_id = prendas_pedido.id
                        WHERE prendas_pedido.pedido_produccion_id = pedidos_produccion.id
                        UNION ALL
                        SELECT MAX(pedidos_procesos_prenda_detalles.updated_at) FROM pedidos_procesos_prenda_detalles
                        JOIN prendas_pedido ON pedidos_procesos_prenda_detalles.prenda_pedido_id = prendas_pedido.id
                        WHERE prendas_pedido.pedido_produccion_id = pedidos_produccion.id
                        UNION ALL
                        SELECT MAX(pedidos_procesos_prenda_tallas.updated_at) FROM pedidos_procesos_prenda_tallas
                        JOIN pedidos_procesos_prenda_detalles ON pedidos_procesos_prenda_tallas.proceso_prenda_detalle_id = pedidos_procesos_prenda_detalles.id
                        JOIN prendas_pedido ON pedidos_procesos_prenda_detalles.prenda_pedido_id = prendas_pedido.id
                        WHERE prendas_pedido.pedido_produccion_id = pedidos_produccion.id
                        UNION ALL
                        SELECT MAX(pedidos_procesos_prenda_talla_colores.updated_at) FROM pedidos_procesos_prenda_talla_colores
                        JOIN pedidos_procesos_prenda_tallas ON pedidos_procesos_prenda_talla_colores.pedidos_procesos_prenda_talla_id = pedidos_procesos_prenda_tallas.id
                        JOIN pedidos_procesos_prenda_detalles ON pedidos_procesos_prenda_tallas.proceso_prenda_detalle_id = pedidos_procesos_prenda_detalles.id
                        JOIN prendas_pedido ON pedidos_procesos_prenda_detalles.prenda_pedido_id = prendas_pedido.id
                        WHERE prendas_pedido.pedido_produccion_id = pedidos_produccion.id
                    ) as todas_actualizaciones
                ), pedidos_produccion.created_at),
                pedidos_produccion.created_at
            ) DESC')
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

        $novedades = $this->filterRecentDuplicateNews($novedades);

        return $novedades->map(function ($news) use ($newsVistosIds) {
            $icono = match ($news->event_type) {
                'pedido_creado', 'order_created' => 'add_shopping_cart',
                'prenda_agregada' => 'checkroom',
                'prenda_modificada' => 'edit',
                'epp_agregado' => 'health_and_safety',
                'epp_modificado' => 'edit',
                'epp_eliminado' => 'delete',
                'order_status_changed' => 'sync_alt',
                'pedido_rechazado_cartera' => 'gpp_bad',
                default => 'notifications',
            };

            $color = match ($news->event_type) {
                'pedido_creado', 'order_created' => '#10b981',
                'prenda_agregada' => '#3b82f6',
                'prenda_modificada' => '#f59e0b',
                'epp_agregado' => '#8b5cf6',
                'epp_modificado' => '#f59e0b',
                'epp_eliminado' => '#ef4444',
                'order_status_changed' => '#6366f1',
                'pedido_rechazado_cartera' => '#dc2626',
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

    private function filterRecentDuplicateNews(\Illuminate\Support\Collection $novedades): \Illuminate\Support\Collection
    {
        $seenBySignature = [];
        $windowSeconds = 90;

        return $novedades->filter(function ($news) use (&$seenBySignature, $windowSeconds) {
            $signature = implode('|', [
                (string) ($news->event_type ?? ''),
                (string) ($news->pedido ?? ''),
                trim((string) ($news->description ?? '')),
            ]);

            $currentTs = \Carbon\Carbon::parse($news->created_at)->getTimestamp();

            if (!isset($seenBySignature[$signature])) {
                $seenBySignature[$signature] = $currentTs;
                return true;
            }

            $previousTs = $seenBySignature[$signature];
            if (($previousTs - $currentTs) <= $windowSeconds) {
                return false;
            }

            $seenBySignature[$signature] = $currentTs;
            return true;
        })->values();
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
            'epp_eliminado',
            'order_status_changed',
            'pedido_rechazado_cartera',
        ];
    }
}
