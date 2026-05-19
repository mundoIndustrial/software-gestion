<?php

namespace App\Application\PedidosLogo\UseCases;

use App\Domain\PedidosLogo\Repositories\ProcesoPrendaDetalleReadRepositoryInterface;
use App\Models\PrendaReciboCompletado;
use App\Services\CalculadorDiasService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class ListPedidosLogoUseCase
{
    public function __construct(
        private ProcesoPrendaDetalleReadRepositoryInterface $procesoReadRepository
    ) {}

    public function execute(?string $search, string $filtro, int $perPage = 20, ?array $columnFilters = null, bool $incluirEntregados = false): LengthAwarePaginator
    {
        $user = Auth::user();
        $isDisenadorLogos = $user && $user->hasRole('diseñador-logos');
        $isBordador = $user && $user->hasRole('bordador');
        $isMinimalLogoRole = $isDisenadorLogos || $isBordador;

        // Mapeo de filtros a IDs de tipo de proceso
        $filtroMap = [
            'reflectivo' => [1],
            'bordado' => [2],
            'estampado' => [3],
            'dtf' => [4],
            'sublimado' => [5],
        ];

        $tipoProcesoIds = $filtroMap[$filtro] ?? [2]; // Por defecto: bordado
        $esFiltroEstampado = in_array($filtro, ['reflectivo', 'estampado', 'dtf', 'sublimado']);

        // Para roles mínimos (diseñador/bordador), solo forzar vista reducida en filtro bordado.
        // En filtro estampado respetamos el tipo de proceso para evitar mezclar bordado.
        $soloMinimalRole = $isMinimalLogoRole && !$esFiltroEstampado;

        // No forzar área fija: permite mostrar recibos aunque aún no exista
        // trazabilidad de área o proceso técnico asociado.
        $areaFija = null;

        $recibos = $this->procesoReadRepository->paginarRecibosAprobados(
            $tipoProcesoIds,
            $search,
            $soloMinimalRole,
            $areaFija,
            $perPage,
            $columnFilters,
            $incluirEntregados
        );

        // Obtener IDs de recibos completados para bordador
        $areaCompletado = $isBordador ? 'BORDANDO' : null;
        $recibosCompletadosIds = [];
        if ($areaCompletado) {
            $recibosCompletadosIds = PrendaReciboCompletado::where('area', $areaCompletado)
                ->pluck('id_recibo')
                ->toArray();
        }

        $recibos->getCollection()->transform(function ($proceso) use ($isMinimalLogoRole, $recibosCompletadosIds, $isBordador) {
            $pedido = $proceso->prenda?->pedidoProduccion;
            $clienteNombre = $pedido?->cliente?->nombre
                ?? $pedido?->cliente
                ?? 'Sin cliente';

            $asesoraNombre = $pedido?->asesora?->name
                ?? $pedido?->asesor?->name
                ?? '';

            $numeroPedido = $pedido?->numero_pedido;

            $fechasAreas = null;
            $fechaEntrega = null;
            if (!empty($proceso->fechas_areas)) {
                $decoded = json_decode($proceso->fechas_areas, true);
                if (is_array($decoded)) {
                    $fechasAreas = $decoded;
                    $fechaEntrega = $decoded['ENTREGADO'] ?? null;
                }
            }

            $fechaFinDias = $fechaEntrega ? \Carbon\Carbon::parse($fechaEntrega) : now();
            // Always use fecha_creacion_recibo from consecutivos_recibos_pedidos
            $fechaCreacionRecibo = $proceso->fecha_creacion_recibo ?? now();
            $totalDias = CalculadorDiasService::calcularDiasHabiles($fechaCreacionRecibo, $fechaFinDias) ?? 0;

            // Verificar si está completado (solo para bordador)
            $completado = $isBordador && in_array($proceso->id, $recibosCompletadosIds);

            // Extract pedido_parcial_id safely - could be from attribute or property
            $pedidoParcialId = null;
            if (isset($proceso->pedido_parcial_id)) {
                $pedidoParcialId = $proceso->pedido_parcial_id;
            } elseif (is_array($proceso) && isset($proceso['pedido_parcial_id'])) {
                $pedidoParcialId = $proceso['pedido_parcial_id'];
            }

            if ($isMinimalLogoRole) {
                // Obtener tallas para calcular cantidad total
                $tallas = $this->obtenerTallasPrenda($proceso->prenda_pedido_id, $proceso->pedido_parcial_id);
                $cantidadTotal = $this->calcularCantidadTotalTallas($tallas);
                
                return [
                    'id' => $proceso->id,
                    'numero_recibo' => $proceso->numero_recibo_consecutivo,
                    'cliente' => $clienteNombre,
                    'created_at' => $fechaCreacionRecibo,
                    'area' => $proceso->area,
                    'pedido_id' => $pedido?->id,
                    'prenda_id' => $proceso->prenda_pedido_id,
                    'tipo_proceso' => $proceso->tipoProceso?->nombre,
                    'tipo_proceso_id' => $proceso->tipo_proceso_id,
                    'consecutivo_recibo_id' => $proceso->consecutivo_recibo_id ?? null,
                    'es_parcial' => (bool)($proceso->es_parcial ?? false),
                    'pedido_parcial_id' => $pedidoParcialId,
                    'fecha_activacion' => $proceso->fecha_activacion ?? null,
                    'completado' => $completado,
                    'cantidad_total' => $cantidadTotal,
                ];
            }

            return [
                'id' => $proceso->id,
                'numero_recibo' => $proceso->numero_recibo_consecutivo,
                'cliente' => $clienteNombre,
                'created_at' => $fechaCreacionRecibo,
                'fecha_entrega' => $fechaEntrega,
                'fechas_areas' => $fechasAreas,
                'pedido_id' => $pedido?->id,
                'numero_pedido' => $numeroPedido,
                'prenda_id' => $proceso->prenda_pedido_id,
                'tipo_proceso' => $proceso->tipoProceso?->nombre,
                'tipo_proceso_id' => $proceso->tipo_proceso_id,
                'consecutivo_recibo_id' => $proceso->consecutivo_recibo_id ?? null,
                'area' => $proceso->area,
                'novedades' => $proceso->novedades,
                'total_dias' => (int) $totalDias,
                'asesora' => $asesoraNombre,
                'es_parcial' => (bool)($proceso->es_parcial ?? false),
                'pedido_parcial_id' => $pedidoParcialId,
                'fecha_activacion' => $proceso->fecha_activacion ?? null,
            ];
        });

        // Aplicar filtro de total_días en el use case (después de calcular los días)
        if ($columnFilters && isset($columnFilters['total_dias']) && !empty($columnFilters['total_dias'])) {
            $recibos->getCollection()->transform(function ($proceso) use ($columnFilters) {
                $proceso->total_dias = $proceso->total_dias ?? 0;
                return $proceso;
            });
            $filtered = $recibos->getCollection()->filter(function ($proceso) use ($columnFilters) {
                return in_array(String($proceso->total_dias ?? 0), $columnFilters['total_dias']);
            });
            $recibos->setCollection($filtered);
        }

        return $recibos;
    }

    public function obtenerAreasUnicas(string $filtro): array
    {
        $filtroMap = [
            'reflectivo' => [1],
            'bordado' => [2],
            'estampado' => [3],
            'dtf' => [4],
            'sublimado' => [5],
        ];

        $tipoProcesoIds = $filtroMap[$filtro] ?? [2];

        return $this->procesoReadRepository->obtenerAreasUnicas($tipoProcesoIds);
    }

    public function obtenerAsesorasUnicas(string $filtro): array
    {
        $filtroMap = [
            'reflectivo' => [1],
            'bordado' => [2],
            'estampado' => [3],
            'dtf' => [4],
            'sublimado' => [5],
        ];

        $tipoProcesoIds = $filtroMap[$filtro] ?? [2];

        return $this->procesoReadRepository->obtenerAsesorasUnicas($tipoProcesoIds);
    }

    public function buscarValoresColumna(string $columna, string $busqueda, string $filtro): array
    {
        $filtroMap = [
            'reflectivo' => [1],
            'bordado' => [2],
            'estampado' => [3],
            'dtf' => [4],
            'sublimado' => [5],
        ];

        $tipoProcesoIds = $filtroMap[$filtro] ?? [2];

        return $this->procesoReadRepository->buscarValoresColumna($columna, $busqueda, $tipoProcesoIds);
    }

    /**
     * Obtener tallas de una prenda (normal o parcial)
     */
    private function obtenerTallasPrenda(int $prendaPedidoId, ?int $pedidoParcialId = null): array
    {
        if ($pedidoParcialId) {
            // Es un recibo parcial, obtener tallas del parcial
            return DB::table('pedidos_parciales_tallas as ppt')
                ->join('pedidos_parciales as pp', 'pp.id', '=', 'ppt.pedido_parcial_id')
                ->where('ppt.pedido_parcial_id', $pedidoParcialId)
                ->where('pp.prenda_pedido_id', $prendaPedidoId)
                ->select('ppt.talla', 'ppt.cantidad')
                ->get()
                ->toArray();
        } else {
            // Es un recibo normal, obtener tallas de la prenda
            return DB::table('prenda_pedido_tallas as ppt')
                ->where('ppt.prenda_pedido_id', $prendaPedidoId)
                ->select('ppt.talla', 'ppt.cantidad')
                ->get()
                ->toArray();
        }
    }

    /**
     * Calcular cantidad total sumando todas las tallas
     */
    private function calcularCantidadTotalTallas(array $tallas): int
    {
        return array_sum(array_column($tallas, 'cantidad'));
    }
}
