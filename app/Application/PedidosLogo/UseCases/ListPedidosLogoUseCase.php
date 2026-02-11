<?php

namespace App\Application\PedidosLogo\UseCases;

use App\Domain\PedidosLogo\Repositories\ProcesoPrendaDetalleReadRepositoryInterface;
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

        $recibos->getCollection()->transform(function ($proceso) use ($isMinimalLogoRole) {
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
            $totalDias = CalculadorDiasService::calcularDiasHabiles($proceso->created_at, $fechaFinDias) ?? 0;

            if ($isMinimalLogoRole) {
                return [
                    'id' => $proceso->id,
                    'numero_recibo' => $proceso->numero_recibo,
                    'cliente' => $clienteNombre,
                    'created_at' => $proceso->created_at,
                    'area' => $proceso->area,
                    'pedido_id' => $pedido?->id,
                    'prenda_id' => $proceso->prenda_pedido_id,
                    'tipo_proceso' => $proceso->tipoProceso?->nombre,
                    'tipo_proceso_id' => $proceso->tipo_proceso_id,
                ];
            }

            return [
                'id' => $proceso->id,
                'numero_recibo' => $proceso->numero_recibo,
                'cliente' => $clienteNombre,
                'created_at' => $proceso->created_at,
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
            ];
        });

        return $recibos;
    }
}
