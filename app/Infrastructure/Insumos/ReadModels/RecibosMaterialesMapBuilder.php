<?php

namespace App\Infrastructure\Insumos\ReadModels;

use Illuminate\Support\Facades\DB;

class RecibosMaterialesMapBuilder
{
    public function build($recibos): array
    {
        $materiales = $recibos
            ->map(function ($recibo) {
                return [
                    'numero_pedido' => $recibo->numero_pedido,
                    'prenda_id' => $recibo->prenda_id,
                ];
            })
            ->filter(fn($item) => !empty($item['numero_pedido']) && !empty($item['prenda_id']))
            ->unique(fn($item) => $item['numero_pedido'] . '_' . $item['prenda_id'])
            ->values()
            ->all();

        if (empty($materiales)) {
            return [];
        }

        $pedidoIds = array_values(array_unique(array_column($materiales, 'numero_pedido')));
        $prendaIds = array_values(array_unique(array_column($materiales, 'prenda_id')));

        $requestedKeys = [];
        foreach ($materiales as $material) {
            $requestedKeys[$material['numero_pedido'] . '_' . $material['prenda_id']] = true;
        }

        // Inicializar todas las combinaciones solicitadas en 0.
        $result = [];
        foreach (array_keys($requestedKeys) as $key) {
            $result[$key] = 0;
        }

        // Evita N+1: una sola consulta agregada para todos los pares pedido+prenda.
        $rows = DB::table('materiales_orden_insumos')
            ->select('numero_pedido', 'prenda_id', DB::raw('COUNT(*) as total'))
            ->whereIn('numero_pedido', $pedidoIds)
            ->whereIn('prenda_id', $prendaIds)
            ->groupBy('numero_pedido', 'prenda_id')
            ->get();

        foreach ($rows as $row) {
            $key = $row->numero_pedido . '_' . $row->prenda_id;
            if (isset($requestedKeys[$key])) {
                $result[$key] = (int) $row->total;
            }
        }

        return $result;
    }
}
