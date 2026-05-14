<?php

namespace App\Application\EntregasTalleres\UseCases;

use App\Models\ConsecutivoReciboPedido;
use App\Models\ReciboPorPartes;
use App\Models\EntregaReciboCostura;
use Illuminate\Support\Facades\DB;

class RegistrarEntregaTallerUseCase
{
    public function execute(array $data)
    {
        $reciboId = $data['recibo_id'];
        $esParcial = $data['es_parcial'] == '1';
        $talla = $data['talla'];
        $genero = $data['genero'] ?? 'UNISEX';
        $color = $data['color'] ?? 'SIN COLOR';
        $cantidad = (int) $data['cantidad'];

        $colorParaGuardar = ($color === 'SIN COLOR') ? null : $color;

        if ($esParcial) {
            $recibo = ReciboPorPartes::with(['pedido', 'prenda', 'tallas'])->findOrFail($reciboId);
            $consecutivoReciboId = null;
            $reciboParcialId = $reciboId;
            $prendaId = $recibo->prenda_pedido_id;

            // Buscar la talla exacta con género y color
            $tallaInfo = $recibo->tallas->where('talla', $talla)
                ->where('genero', $genero)
                ->where('color_nombre', $colorParaGuardar)
                ->first();

            if (!$tallaInfo) {
                $msgColor = $colorParaGuardar ?? 'sin color';
                return ['success' => false, 'message' => "Talla {$talla} ({$genero}) - {$msgColor} no encontrada en este recibo parcial."];
            }
            $maxCantidad = $tallaInfo->cantidad;

            // Sumar entregas previas usando las nuevas columnas
            $yaEntregado = EntregaReciboCostura::where('recibo_parcial_id', $reciboId)
                ->where('talla', $talla)
                ->where('genero', $genero)
                ->where('color_nombre', $colorParaGuardar)
                ->sum('cantidad_entregada');

            $encargadoActual = DB::table('procesos_prenda')
                ->where('numero_pedido', $recibo->pedido->numero_pedido)
                ->where('prenda_pedido_id', $recibo->prenda_pedido_id)
                ->where('numero_recibo_parcial', $recibo->consecutivo_parcial)
                ->where('proceso', 'Costura')
                ->value('encargado') ?? 'Sin asignar';

            $numeroReciboDisplay = $recibo->consecutivo_parcial;
        } else {
            $recibo = ConsecutivoReciboPedido::with(['pedido', 'prenda.tallas.coloresAsignados'])->findOrFail($reciboId);
            $consecutivoReciboId = $reciboId;
            $reciboParcialId = null;
            $prendaId = $recibo->prenda_id;

            // Buscar la talla exacta
            $tallaRow = $recibo->prenda->tallas->where('talla', $talla)
                ->where('genero', $genero)
                ->first();

            if (!$tallaRow) {
                return ['success' => false, 'message' => "Talla {$talla} ({$genero}) no encontrada."];
            }

            if ($colorParaGuardar) {
                $colorInfo = $tallaRow->coloresAsignados->where('color_nombre', $colorParaGuardar)->first();
                if (!$colorInfo) {
                    return ['success' => false, 'message' => "Color {$colorParaGuardar} no encontrado para la talla {$talla}."];
                }
                $maxCantidad = $colorInfo->cantidad;
            } else {
                $maxCantidad = $tallaRow->cantidad;
            }

            // Sumar entregas previas
            $yaEntregado = EntregaReciboCostura::where('consecutivo_recibo_id', $reciboId)
                ->where('talla', $talla)
                ->where('genero', $genero)
                ->where('color_nombre', $colorParaGuardar)
                ->sum('cantidad_entregada');

            $encargadoActual = DB::table('procesos_prenda')
                ->where('numero_pedido', $recibo->pedido->numero_pedido)
                ->where('numero_recibo', $recibo->consecutivo_actual)
                ->where('proceso', 'Costura')
                ->value('encargado') ?? 'Sin asignar';

            $numeroReciboDisplay = $recibo->consecutivo_actual;
        }

        if (($yaEntregado + $cantidad) > $maxCantidad) {
            return [
                'success' => false,
                'message' => "La cantidad excede el máximo permitido ({$maxCantidad}). Ya se han entregado {$yaEntregado}."
            ];
        }

        // Crear la entrega con las nuevas columnas
        EntregaReciboCostura::create([
            'prenda_pedido_id' => $prendaId,
            'consecutivo_recibo_id' => $consecutivoReciboId,
            'recibo_parcial_id' => $reciboParcialId,
            'encargado' => $encargadoActual,
            'area' => 'Costura',
            'cantidad_entregada' => $cantidad,
            'talla' => $talla,
            'genero' => $genero,
            'color_nombre' => $colorParaGuardar,
            'usuario_id' => auth()->id(),
        ]);

        // VERIFICAR COMPLETADO TOTAL DEL RECIBO
        $todoCompletado = $this->verificarCompletado($recibo, $esParcial, $reciboId);

        if ($todoCompletado) {
            $idReciboParaCompletado = $esParcial ? 0 : $reciboId;

            DB::table('prenda_recibo_completado')->updateOrInsert(
                [
                    'id_recibo' => $idReciboParaCompletado,
                    'area' => 'Costura',
                    'id_parcial' => $reciboParcialId
                ],
                [
                    'numero_recibo' => $numeroReciboDisplay,
                    'nombre_operario' => $encargadoActual,
                    'fecha_completado' => now()
                ]
            );
        }

        return [
            'success' => true,
            'completado' => $todoCompletado
        ];
    }

    private function verificarCompletado($recibo, $esParcial, $reciboId)
    {
        $requerimientos = [];
        if ($esParcial) {
            foreach ($recibo->tallas as $t) {
                // Si el color es null en DB, lo tratamos como null aquí también para la llave
                $colorKey = $t->color_nombre ?: 'NULL';
                $key = "{$t->talla}|{$t->genero}|{$colorKey}";
                $requerimientos[$key] = $t->cantidad;
            }
            $entregasTotales = EntregaReciboCostura::where('recibo_parcial_id', $reciboId)->get();
        } else {
            foreach ($recibo->prenda->tallas as $t) {
                if ($t->coloresAsignados->count() > 0) {
                    foreach ($t->coloresAsignados as $c) {
                        $colorKey = $c->color_nombre ?: 'NULL';
                        $key = "{$t->talla}|{$t->genero}|{$colorKey}";
                        $requerimientos[$key] = $c->cantidad;
                    }
                } else {
                    $key = "{$t->talla}|{$t->genero}|NULL";
                    $requerimientos[$key] = $t->cantidad;
                }
            }
            $entregasTotales = EntregaReciboCostura::where('consecutivo_recibo_id', $reciboId)->get();
        }

        $entregadoPorLlave = [];
        foreach ($entregasTotales as $entrega) {
            $colorKey = $entrega->color_nombre ?: 'NULL';
            $key = "{$entrega->talla}|{$entrega->genero}|{$colorKey}";
            $entregadoPorLlave[$key] = ($entregadoPorLlave[$key] ?? 0) + $entrega->cantidad_entregada;
        }

        foreach ($requerimientos as $key => $cantidadRequerida) {
            if (($entregadoPorLlave[$key] ?? 0) < $cantidadRequerida) {
                return false;
            }
        }

        return true;
    }
}
