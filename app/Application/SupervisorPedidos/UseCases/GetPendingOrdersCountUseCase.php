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
            // Contar órdenes con estado 'PENDIENTE_SUPERVISOR'
            $totalPendientes = PedidoProduccion::where('estado', 'PENDIENTE_SUPERVISOR')->count();

            $logoTipoId = TipoCotizacion::getIdPorCodigo('logo');

            // Contar solo las órdenes de logo pendientes
            $pendientesLogo = 0;
            if ($logoTipoId) {
                $pendientesLogo = PedidoProduccion::where('estado', 'PENDIENTE_SUPERVISOR')
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
