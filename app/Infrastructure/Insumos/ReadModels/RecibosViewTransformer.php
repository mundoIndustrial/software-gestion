<?php

namespace App\Infrastructure\Insumos\ReadModels;

use Carbon\Carbon;

class RecibosViewTransformer
{
    public function transform($recibos, array $parcialCreatedAtMap, callable $calcularDiasCallback, array $materialesMap = [])
    {
        return $recibos->map(function ($recibo) use ($parcialCreatedAtMap, $calcularDiasCallback, $materialesMap) {
            $diasCalculados = 0;
            if ($recibo->created_at) {
                $fechaInicio = Carbon::parse($recibo->created_at);
                $diasCalculados = $calcularDiasCallback($fechaInicio);
            }

            $parcialId = null;
            $notas = isset($recibo->notas) ? (string) $recibo->notas : '';
            if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                $parcialId = (int) $matches[1];
            }

            $esParcial = $parcialId !== null;
            $fechaInicioOrden = $recibo->created_at;
            if ($esParcial && isset($parcialCreatedAtMap[$parcialId]) && $parcialCreatedAtMap[$parcialId]) {
                $fechaInicioOrden = $parcialCreatedAtMap[$parcialId];
            }

            $materialesKey = $recibo->numero_pedido . '_' . $recibo->prenda_id;
            $cantidadMateriales = $materialesMap[$materialesKey] ?? 0;

            return (object) [
                'id' => $recibo->id,
                'numero_pedido' => $recibo->consecutivo_actual,
                'numero_pedido_original' => $recibo->numero_pedido_original,
                'cliente' => $recibo->cliente,
                'estado' => $recibo->recibo_estado ?? $recibo->pedido_estado,
                'area' => $recibo->recibo_area ?? $recibo->pedido_area,
                'recibo_estado' => $recibo->recibo_estado,
                'recibo_area' => $recibo->recibo_area,
                'pedido_estado' => $recibo->pedido_estado,
                'pedido_area' => $recibo->pedido_area,
                'created_at' => $fechaInicioOrden,
                'dia_de_entrega' => $recibo->dia_de_entrega,
                'fecha_estimada_de_entrega' => !empty($recibo->fecha_estimada_de_entrega)
                    ? Carbon::parse($recibo->fecha_estimada_de_entrega)->format('d/m/Y')
                    : null,
                'dias_calculados' => $diasCalculados,
                'pedido_produccion_id' => $recibo->pedido_produccion_id,
                'prenda_id' => $recibo->prenda_id,
                'consecutivo_actual' => $recibo->consecutivo_actual,
                'tipo_recibo' => $recibo->tipo_recibo,
                'marcar_plooter' => $recibo->marcar_plooter ?? false,
                'es_parcial' => $esParcial,
                'pedido_parcial_id' => $parcialId,
                'updated_at' => $recibo->updated_at,
                'tiene_materiales' => $cantidadMateriales > 0,
                'cantidad_materiales' => $cantidadMateriales,
            ];
        });
    }
}

