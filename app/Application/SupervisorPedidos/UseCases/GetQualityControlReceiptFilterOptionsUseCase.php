<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetSewingReceiptFilterOptionsRequest;
use App\Application\SupervisorPedidos\DTOs\GetSewingReceiptFilterOptionsResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetQualityControlReceiptFilterOptionsUseCase
{
    public function execute(GetSewingReceiptFilterOptionsRequest $request): GetSewingReceiptFilterOptionsResponse
    {
        try {
            $field = $request->getField();
            $options = $this->getOptionsByField($field);

            Log::info('[GetQualityControlReceiptFilterOptionsUseCase] Retrieved ' . count($options) . ' options for field: ' . $field);

            return new GetSewingReceiptFilterOptionsResponse($options);

        } catch (\Exception $e) {
            Log::error('[GetQualityControlReceiptFilterOptionsUseCase] Error: ' . $e->getMessage());
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
        return DB::table('procesos_prenda as pp')
            ->join('prendas_pedido as prenda', 'pp.prenda_pedido_id', '=', 'prenda.id')
            ->join('pedidos_produccion as p', 'pp.numero_pedido', '=', 'p.numero_pedido')
            ->leftJoin('users as u', 'p.asesor_id', '=', 'u.id')
            ->join('consecutivos_recibos_pedidos as crp', function($join) {
                $join->on('crp.pedido_produccion_id', '=', 'p.id')
                    ->on('crp.consecutivo_actual', '=', 'pp.numero_recibo');
            })
            ->where('pp.proceso', 'Control de Calidad')
            ->where('pp.estado_proceso', 'Pendiente')
            ->where('crp.tipo_recibo', 'COSTURA')
            ->where('crp.activo', 1);
    }

    private function getReceiptNumbers(): array
    {
        return $this->buildBaseQuery()
            ->distinct()
            ->pluck('pp.numero_recibo')
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
        return ['Control de Calidad'];
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
            ->distinct()
            ->pluck('prenda.nombre_prenda')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }
}
