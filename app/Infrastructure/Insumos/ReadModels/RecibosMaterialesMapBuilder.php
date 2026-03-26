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
            ->unique(fn($item) => $item['numero_pedido'] . '_' . $item['prenda_id'])
            ->values()
            ->all();

        if (empty($materiales)) {
            return [];
        }

        $result = [];
        foreach ($materiales as $material) {
            $count = DB::table('materiales_orden_insumos')
                ->where('numero_pedido', $material['numero_pedido'])
                ->where('prenda_id', $material['prenda_id'])
                ->count();

            $result[$material['numero_pedido'] . '_' . $material['prenda_id']] = $count;
        }

        return $result;
    }
}

