<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetFilterOptionsRequest;
use App\Application\SupervisorPedidos\DTOs\GetFilterOptionsResponse;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

class GetFilterOptionsUseCase
{
    public function execute(GetFilterOptionsRequest $request): GetFilterOptionsResponse
    {
        try {
            $field = $request->getField();
            $opciones = $this->getOptionsByField($field);

            Log::info('Opciones de filtro obtenidas', [
                'field' => $field,
                'count' => count($opciones)
            ]);

            return new GetFilterOptionsResponse($opciones);

        } catch (\Exception $e) {
            Log::error('Error en GetFilterOptions: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getOptionsByField(string $field): array
    {
        return match($field) {
            'numero' => $this->getOrderNumbers(),
            'cliente' => $this->getClients(),
            'estado' => $this->getStates(),
            'asesora' => $this->getAdvisors(),
            'forma_pago' => $this->getPaymentMethods(),
            'fecha' => $this->getCreatedDates(),
            default => []
        };
    }

    private function getOrderNumbers(): array
    {
        return PedidoProduccion::distinct()
            ->pluck('numero_pedido')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getClients(): array
    {
        return PedidoProduccion::distinct()
            ->pluck('cliente')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getStates(): array
    {
        return PedidoProduccion::distinct()
            ->pluck('estado')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getAdvisors(): array
    {
        return PedidoProduccion::with('asesora')
            ->get()
            ->pluck('asesora.name')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getPaymentMethods(): array
    {
        return PedidoProduccion::distinct()
            ->pluck('forma_de_pago')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getCreatedDates(): array
    {
        return PedidoProduccion::query()
            ->selectRaw('DATE(created_at) as fecha')
            ->whereNotNull('created_at')
            ->distinct()
            ->orderByDesc('fecha')
            ->pluck('fecha')
            ->filter()
            ->values()
            ->toArray();
    }
}
