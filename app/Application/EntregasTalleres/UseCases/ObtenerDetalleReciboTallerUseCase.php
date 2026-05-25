<?php

namespace App\Application\EntregasTalleres\UseCases;

use App\Models\ConsecutivoReciboPedido;
use App\Models\ReciboPorPartes;
use App\Models\PrendaBodega;
use App\Models\EntregaReciboCostura;
use Illuminate\Support\Facades\DB;

class ObtenerDetalleReciboTallerUseCase
{
    public function execute(int $id, bool $esParcial, bool $esBodega = false, int $prendaBodegaId = 0)
    {
        $tallasFinales = collect();
        $encargado = 'Sin asignar';
        $numeroReciboDisplay = 0;
        $prendaNombre = '';
        $reciboInstance = null;

        if ($esBodega) {
            if ($esParcial) {
                $recibo = ReciboPorPartes::with(['pedido', 'tallas'])->findOrFail($id);
                $reciboInstance = $recibo;
                $numeroReciboDisplay = $recibo->consecutivo_parcial + 0;

                $bodegaId = $prendaBodegaId > 0 ? $prendaBodegaId : (int) ($recibo->prenda_pedido_id ?? 0);
                $prendaBodega = $bodegaId > 0 ? PrendaBodega::with('tallas')->find($bodegaId) : null;
                $prendaNombre = $prendaBodega?->nombre ?? 'Sin prenda de bodega';

                $encargadoQuery = DB::table('procesos_prenda')
                    ->where(function ($q) use ($bodegaId) {
                        $q->where('prenda_bodega_id', $bodegaId)
                            ->orWhereNull('prenda_bodega_id');
                    })
                    ->where(function ($q) use ($recibo) {
                        $q->where('numero_recibo_parcial', $recibo->consecutivo_parcial)
                            ->orWhereNull('numero_recibo_parcial');
                    })
                    ->where(function ($q) use ($recibo) {
                        $q->where('numero_recibo', $recibo->consecutivo_original)
                            ->orWhereNull('numero_recibo');
                    })
                    ->whereRaw("COALESCE(NULLIF(TRIM(encargado), ''), '') <> ''")
                    ->orderByRaw("CASE WHEN proceso = 'Costura' THEN 0 ELSE 1 END")
                    ->orderByDesc('fecha_de_asignacion_encargado')
                    ->orderByDesc('id');

                $encargado = $encargadoQuery->value('encargado') ?? 'Sin asignar';

                foreach ($recibo->tallas as $t) {
                    $tallasFinales->push((object) [
                        'talla' => $t->talla,
                        'cantidad' => $t->cantidad,
                        'genero' => $t->genero ?? 'UNISEX',
                        'color' => $t->color_nombre ?? 'SIN COLOR'
                    ]);
                }

                $entregas = EntregaReciboCostura::where('recibo_parcial_id', $id)->get();
            } else {
                $recibo = ConsecutivoReciboPedido::with(['pedido', 'prendaBodega.tallas'])->findOrFail($id);
                $reciboInstance = $recibo;
                $numeroReciboDisplay = $recibo->consecutivo_actual;
                $prendaNombre = $recibo->prendaBodega?->nombre ?? 'Sin prenda de bodega';

                $encargado = DB::table('procesos_prenda')
                    ->where(function ($q) use ($recibo) {
                        $q->where('prenda_bodega_id', $recibo->prenda_bodega_id)
                            ->orWhereNull('prenda_bodega_id');
                    })
                    ->where(function ($q) use ($recibo) {
                        $q->where('numero_recibo', $recibo->consecutivo_actual)
                            ->orWhereNull('numero_recibo');
                    })
                    ->whereRaw("COALESCE(NULLIF(TRIM(encargado), ''), '') <> ''")
                    ->orderByRaw("CASE WHEN proceso = 'Costura' THEN 0 ELSE 1 END")
                    ->orderByDesc('fecha_de_asignacion_encargado')
                    ->orderByDesc('id')
                    ->value('encargado') ?? 'Sin asignar';

                foreach ($recibo->prendaBodega?->tallas ?? collect() as $t) {
                    $tallasFinales->push((object) [
                        'talla' => $t->talla,
                        'cantidad' => $t->cantidad,
                        'genero' => $t->genero ?? 'UNISEX',
                        'color' => $t->color ?? 'SIN COLOR'
                    ]);
                }

                $entregas = EntregaReciboCostura::where('consecutivo_recibo_id', $id)->get();
            }
        } elseif ($esParcial) {
            $recibo = ReciboPorPartes::with(['pedido', 'prenda', 'tallas'])->findOrFail($id);
            $reciboInstance = $recibo;
            $numeroReciboDisplay = $recibo->consecutivo_parcial + 0;
            $prendaNombre = $recibo->prenda->nombre_prenda;

            $encargado = DB::table('procesos_prenda')
                ->where('numero_pedido', $recibo->pedido->numero_pedido)
                ->where('prenda_pedido_id', $recibo->prenda_pedido_id)
                ->where('numero_recibo_parcial', $recibo->consecutivo_parcial)
                ->where('proceso', 'Costura')
                ->value('encargado') ?? 'Sin asignar';

            foreach ($recibo->tallas as $t) {
                $tallasFinales->push((object) [
                    'talla' => $t->talla,
                    'cantidad' => $t->cantidad,
                    'genero' => $t->genero ?? 'UNISEX',
                    'color' => $t->color_nombre ?? 'SIN COLOR'
                ]);
            }

            $entregas = EntregaReciboCostura::where('recibo_parcial_id', $id)->get();
        } else {
            $recibo = ConsecutivoReciboPedido::with(['pedido', 'prenda.tallas.coloresAsignados'])->findOrFail($id);
            $reciboInstance = $recibo;
            $numeroReciboDisplay = $recibo->consecutivo_actual;
            $prendaNombre = $recibo->prenda->nombre_prenda;

            $encargado = DB::table('procesos_prenda')
                ->where('numero_pedido', $recibo->pedido->numero_pedido)
                ->where('numero_recibo', $recibo->consecutivo_actual)
                ->where('proceso', 'Costura')
                ->value('encargado') ?? 'Sin asignar';

            foreach ($recibo->prenda->tallas as $t) {
                if ($t->coloresAsignados->count() > 0) {
                    foreach ($t->coloresAsignados as $c) {
                        $tallasFinales->push((object) [
                            'talla' => $t->talla,
                            'cantidad' => $c->cantidad,
                            'genero' => $t->genero ?? 'UNISEX',
                            'color' => $c->color_nombre ?? 'SIN COLOR'
                        ]);
                    }
                } else {
                    $tallasFinales->push((object) [
                        'talla' => $t->talla,
                        'cantidad' => $t->cantidad,
                        'genero' => $t->genero ?? 'UNISEX',
                        'color' => 'SIN COLOR'
                    ]);
                }
            }

            $entregas = EntregaReciboCostura::where('consecutivo_recibo_id', $id)->get();
        }

        // Mapear entregas por llave única: TALLA|GENERO|COLOR
        $entregasPorLlave = [];
        foreach ($entregas as $entrega) {
            $colorKey = $entrega->color_nombre ?: 'SIN COLOR';
            $key = "{$entrega->talla}|{$entrega->genero}|{$colorKey}";
            $entregasPorLlave[$key] = ($entregasPorLlave[$key] ?? 0) + $entrega->cantidad_entregada;
        }

        // Agrupar para la vista: Genero -> Color -> Items
        $tallasAgrupadas = $tallasFinales->groupBy('genero')->map(function ($items) {
            return $items->groupBy('color');
        });

        return [
            'recibo' => $reciboInstance,
            'numeroRecibo' => $numeroReciboDisplay,
            'prendaNombre' => $prendaNombre,
            'encargado' => $encargado,
            'tallasAgrupadas' => $tallasAgrupadas,
            'entregasPorLlave' => $entregasPorLlave,
            'esParcial' => $esParcial,
            'esBodega' => $esBodega,
            'prendaBodegaId' => $prendaBodegaId
        ];
    }
}
