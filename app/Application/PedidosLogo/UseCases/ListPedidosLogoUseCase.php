<?php

namespace App\Application\PedidosLogo\UseCases;

use App\Domain\PedidosLogo\Repositories\ProcesoPrendaDetalleReadRepositoryInterface;
use App\Models\PrendaReciboCompletado;
use App\Services\CalculadorDiasService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

final class ListPedidosLogoUseCase
{
    public function __construct(
        private ProcesoPrendaDetalleReadRepositoryInterface $procesoReadRepository
    ) {}

    public function execute(?string $search, string $filtro, int $perPage = 20): LengthAwarePaginator
    {
        $user = Auth::user();
        $isDisenadorLogos = $user && $user->hasRole('diseñador-logos');
        $isBordador = $user && $user->hasRole('bordador');
        $isMinimalLogoRole = $isDisenadorLogos || $isBordador;

        $filtro = $filtro === 'estampado' ? 'estampado' : 'bordado';

        $tipoProcesoIds = $isMinimalLogoRole
            ? [2]
            : ($filtro === 'estampado' ? [3, 4, 5] : [2]);

        $areaFija = null;
        if ($isMinimalLogoRole) {
            $areaFija = $isBordador ? 'BORDANDO' : 'DISENO';
        }

        $recibos = $this->procesoReadRepository->paginarRecibosAprobados(
            $tipoProcesoIds,
            $search,
            $isMinimalLogoRole,
            $areaFija,
            $perPage
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
            $fechaCreacionRecibo = $proceso->fecha_creacion_recibo ?? null;
            $fechaInicioDias = $fechaCreacionRecibo ?: $proceso->created_at;
            $totalDias = CalculadorDiasService::calcularDiasHabiles($fechaInicioDias, $fechaFinDias) ?? 0;

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
                return [
                    'id' => $proceso->id,
                    'numero_recibo' => $proceso->numero_recibo_consecutivo,
                    'cliente' => $clienteNombre,
                    'created_at' => $fechaCreacionRecibo ?: $proceso->created_at,
                    'area' => $proceso->area,
                    'pedido_id' => $pedido?->id,
                    'prenda_id' => $proceso->prenda_pedido_id,
                    'tipo_proceso' => $proceso->tipoProceso?->nombre,
                    'tipo_proceso_id' => $proceso->tipo_proceso_id,
                    'es_parcial' => (bool)($proceso->es_parcial ?? false),
                    'pedido_parcial_id' => $pedidoParcialId,
                    'completado' => $completado,
                ];
            }

            return [
                'id' => $proceso->id,
                'numero_recibo' => $proceso->numero_recibo_consecutivo,
                'cliente' => $clienteNombre,
                'created_at' => $fechaCreacionRecibo ?: $proceso->created_at,
                'fecha_entrega' => $fechaEntrega,
                'fechas_areas' => $fechasAreas,
                'pedido_id' => $pedido?->id,
                'numero_pedido' => $numeroPedido,
                'prenda_id' => $proceso->prenda_pedido_id,
                'tipo_proceso' => $proceso->tipoProceso?->nombre,
                'tipo_proceso_id' => $proceso->tipo_proceso_id,
                'area' => $proceso->area,
                'novedades' => $proceso->novedades,
                'total_dias' => (int) $totalDias,
                'asesora' => $asesoraNombre,
                'es_parcial' => (bool)($proceso->es_parcial ?? false),
                'pedido_parcial_id' => $pedidoParcialId,
            ];
        });

        return $recibos;
    }
}
