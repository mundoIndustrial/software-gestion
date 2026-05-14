<?php

namespace App\Application\EntregasTalleres\UseCases;

use App\Models\ConsecutivoReciboPedido;
use App\Models\ReciboPorPartes;
use App\Models\EntregaReciboCostura;
use Illuminate\Support\Facades\DB;

class ObtenerDetalleReciboTallerUseCase
{
    public function execute(int $id, bool $esParcial)
    {
        $tallasFinales = collect();
        $encargado = 'Sin asignar';
        $numeroReciboDisplay = 0;
        $prendaNombre = '';
        $reciboInstance = null;

        if ($esParcial) {
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
            'esParcial' => $esParcial
        ];
    }
}
