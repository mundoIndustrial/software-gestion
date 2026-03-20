<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetSewingReceiptFilterOptionsRequest;
use App\Application\SupervisorPedidos\DTOs\GetSewingReceiptFilterOptionsResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetSewingReceiptFilterOptionsUseCase
{
    public function execute(GetSewingReceiptFilterOptionsRequest $request): GetSewingReceiptFilterOptionsResponse
    {
        try {
            $field = $request->getField();
            $options = $this->getOptionsByField($field);

            Log::info('[GetSewingReceiptFilterOptionsUseCase] Retrieved ' . count($options) . ' options for field: ' . $field);

            return new GetSewingReceiptFilterOptionsResponse($options);

        } catch (\Exception $e) {
            Log::error('[GetSewingReceiptFilterOptionsUseCase] Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getOptionsByField(string $field): array
    {
        return match($field) {
            'numero_recibo' => $this->getReceiptNumbers(),
            'cliente' => $this->getClients(),
            'area' => $this->getAreas(),
            'asesor' => $this->getAdvisors(),
            'prendas' => $this->getGarments(),
            default => []
        };
    }

    private function buildBaseQuery()
    {
        return DB::table('consecutivos_recibos_pedidos as crp')
            ->join('pedidos_produccion as p', 'crp.pedido_produccion_id', '=', 'p.id')
            ->leftJoin('users as u', 'p.asesor_id', '=', 'u.id')
            ->where('crp.tipo_recibo', 'COSTURA')
            ->where('crp.activo', 1);
    }

    private function getReceiptNumbers(): array
    {
        return $this->buildBaseQuery()
            ->distinct()
            ->pluck('crp.consecutivo_actual')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getClients(): array
    {
        return $this->buildBaseQuery()
            ->distinct()
            ->pluck('p.cliente')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getAreas(): array
    {
        return $this->buildBaseQuery()
            ->distinct()
            ->pluck('p.area')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getAdvisors(): array
    {
        return $this->buildBaseQuery()
            ->distinct()
            ->pluck('u.name')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getGarments(): array
    {
        return $this->buildBaseQuery()
            ->leftJoin('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
            ->distinct()
            ->pluck('pp.nombre_prenda')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }
}
