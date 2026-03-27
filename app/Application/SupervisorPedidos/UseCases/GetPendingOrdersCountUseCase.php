<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetPendingOrdersCountRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingOrdersCountResponse;
use App\Models\PedidoProduccion;
use App\Models\TipoCotizacion;
use Illuminate\Support\Facades\Log;

class GetPendingOrdersCountUseCase
{
    public function execute(GetPendingOrdersCountRequest $request): GetPendingOrdersCountResponse
    {
        try {
            // Contar solo pedidos pendientes visibles en supervisor-pedidos.
            $baseQuery = PedidoProduccion::query()
                ->where('estado', 'PENDIENTE_SUPERVISOR')
                ->whereNull('ocultado_en')
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '!=', '');

            $totalPendientes = (clone $baseQuery)->count();

            $logoTipoId = TipoCotizacion::getIdPorCodigo('logo');

            // Contar solo las órdenes de logo pendientes
            $pendientesLogo = 0;
            if ($logoTipoId) {
                $pendientesLogo = (clone $baseQuery)
                    ->whereHas('cotizacion', function ($q) use ($logoTipoId) {
                        $q->where('tipo_cotizacion_id', $logoTipoId);
                    })
                    ->count();
            }

            Log::info('[GetPendingOrdersCountUseCase] Total pendientes: ' . $totalPendientes . ', Logo: ' . $pendientesLogo);

            return new GetPendingOrdersCountResponse($totalPendientes, $pendientesLogo);

        } catch (\Exception $e) {
            Log::error('[GetPendingOrdersCountUseCase] Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
