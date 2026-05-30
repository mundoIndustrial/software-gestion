<?php

namespace App\Application\EntregasTalleres\UseCases;

use App\Models\ConsecutivoReciboPedido;
use App\Models\ReciboPorPartes;
use App\Models\PrendaBodega;
use App\Models\EntregaReciboCostura;
use Illuminate\Support\Facades\DB;

class RegistrarEntregaTallerUseCase
{
    public function execute(array $data)
    {
        $reciboId = $data['recibo_id'];
        $esParcial = $data['es_parcial'] == '1';
        $esBodega = ($data['es_bodega'] ?? '0') == '1';
        $prendaBodegaId = (int) ($data['prenda_bodega_id'] ?? 0);
        $talla = $data['talla'];
        $genero = $data['genero'] ?? 'UNISEX';
        $color = $data['color'] ?? 'SIN COLOR';
        $cantidad = (int) $data['cantidad'];
        $observaciones = trim((string) ($data['observaciones'] ?? ''));
        $esNovedadSolo = ($data['es_novedad_solo'] ?? false) === true;

        $colorParaGuardar = ($color === 'SIN COLOR') ? null : $color;

        // Si es solo una novedad sin entrega, guardar directamente
        if ($esNovedadSolo && $cantidad === 0) {
            return $this->guardarNovedadSolo($reciboId, $esParcial, $esBodega, $prendaBodegaId, $observaciones);
        }

        if ($esBodega) {
            if ($esParcial) {
                $recibo = ReciboPorPartes::with(['pedido', 'tallas'])->findOrFail($reciboId);
                $consecutivoReciboId = null;
                $reciboParcialId = $reciboId;
                $bodegaId = $prendaBodegaId > 0 ? $prendaBodegaId : (int) ($recibo->prenda_pedido_id ?? 0);
                $prendaId = $bodegaId;
                $prendaBodega = $bodegaId > 0 ? PrendaBodega::with('tallas')->find($bodegaId) : null;

                $tallaInfo = $recibo->tallas->where('talla', $talla)
                    ->where('genero', $genero)
                    ->where('color_nombre', $colorParaGuardar)
                    ->first();

                if (!$tallaInfo) {
                    $msgColor = $colorParaGuardar ?? 'sin color';
                    return ['success' => false, 'message' => "Talla {$talla} ({$genero}) - {$msgColor} no encontrada en este recibo parcial de bodega."];
                }

                $maxCantidad = $tallaInfo->cantidad;

                $yaEntregado = EntregaReciboCostura::where('recibo_parcial_id', $reciboId)
                    ->where('talla', $talla)
                    ->where('genero', $genero)
                    ->where('color_nombre', $colorParaGuardar)
                    ->sum('cantidad_entregada');

                $encargadoActual = DB::table('procesos_prenda')
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
                    ->orderByDesc('id')
                    ->value('encargado') ?? 'Sin asignar';

                $numeroReciboDisplay = $recibo->consecutivo_parcial;
            } else {
                $recibo = ConsecutivoReciboPedido::with(['pedido', 'prendaBodega.tallas'])->findOrFail($reciboId);
                $consecutivoReciboId = $reciboId;
                $reciboParcialId = null;
                $prendaId = (int) ($recibo->prenda_bodega_id ?? 0);
                $prendaBodega = $recibo->prendaBodega;

                $tallaRow = $prendaBodega?->tallas->where('talla', $talla)
                    ->where('genero', $genero)
                    ->first();

                if (!$tallaRow) {
                    return ['success' => false, 'message' => "Talla {$talla} ({$genero}) no encontrada en bodega."];
                }

                $maxCantidad = $tallaRow->cantidad;

                $yaEntregado = EntregaReciboCostura::where('consecutivo_recibo_id', $reciboId)
                    ->where('talla', $talla)
                    ->where('genero', $genero)
                    ->where('color_nombre', $colorParaGuardar)
                    ->sum('cantidad_entregada');

                $encargadoActual = DB::table('procesos_prenda')
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

                $numeroReciboDisplay = $recibo->consecutivo_actual;
            }
        } elseif ($esParcial) {
            $recibo = ReciboPorPartes::with(['pedido', 'prenda', 'tallas'])->findOrFail($reciboId);
            $consecutivoReciboId = null;
            $reciboParcialId = $reciboId;
            $prendaId = $recibo->prenda_pedido_id;

            $tallaInfo = $recibo->tallas->where('talla', $talla)
                ->where('genero', $genero)
                ->where('color_nombre', $colorParaGuardar)
                ->first();

            if (!$tallaInfo) {
                $msgColor = $colorParaGuardar ?? 'sin color';
                return ['success' => false, 'message' => "Talla {$talla} ({$genero}) - {$msgColor} no encontrada en este recibo parcial."];
            }
            $maxCantidad = $tallaInfo->cantidad;

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
            'observaciones' => $observaciones ?: null,
        ]);

        $todoCompletado = $this->verificarCompletado($recibo, $esParcial, $esBodega, $reciboId);

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

    private function verificarCompletado($recibo, $esParcial, $esBodega, $reciboId)
    {
        $requerimientos = [];

        if ($esBodega) {
            if ($esParcial) {
                foreach (($recibo->tallas ?? collect()) as $t) {
                    $colorKey = $t->color_nombre ?: 'NULL';
                    $key = "{$t->talla}|{$t->genero}|{$colorKey}";
                    $requerimientos[$key] = $t->cantidad;
                }

                $entregasTotales = EntregaReciboCostura::where('recibo_parcial_id', $reciboId)->get();
            } else {
                foreach (($recibo->prendaBodega?->tallas ?? collect()) as $t) {
                    $colorKey = $t->color ?: 'NULL';
                    $generoKey = $t->genero ?: 'UNISEX';
                    $key = "{$t->talla}|{$generoKey}|{$colorKey}";
                    $requerimientos[$key] = $t->cantidad;
                }

                $entregasTotales = EntregaReciboCostura::where('consecutivo_recibo_id', $reciboId)->get();
            }
        } elseif ($esParcial) {
            foreach ($recibo->tallas as $t) {
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

    private function guardarNovedadSolo(int $reciboId, bool $esParcial, bool $esBodega, int $prendaBodegaId, string $observaciones)
    {
        if (!$observaciones) {
            return ['success' => false, 'message' => 'La novedad no puede estar vacía'];
        }

        try {
            if ($esBodega) {
                if ($esParcial) {
                    $recibo = ReciboPorPartes::findOrFail($reciboId);
                    $prendaId = (int) ($recibo->prenda_pedido_id ?? 0);
                    $consecutivoReciboId = null;
                    $reciboParcialId = $reciboId;
                } else {
                    $recibo = ConsecutivoReciboPedido::findOrFail($reciboId);
                    $prendaId = (int) ($recibo->prenda_bodega_id ?? 0);
                    $consecutivoReciboId = $reciboId;
                    $reciboParcialId = null;
                }
            } elseif ($esParcial) {
                $recibo = ReciboPorPartes::findOrFail($reciboId);
                $prendaId = $recibo->prenda_pedido_id;
                $consecutivoReciboId = null;
                $reciboParcialId = $reciboId;
            } else {
                $recibo = ConsecutivoReciboPedido::findOrFail($reciboId);
                $prendaId = $recibo->prenda_id;
                $consecutivoReciboId = $reciboId;
                $reciboParcialId = null;
            }

            $encargadoActual = auth()->user()->name ?? 'Sistema';

            EntregaReciboCostura::create([
                'prenda_pedido_id' => $prendaId,
                'consecutivo_recibo_id' => $consecutivoReciboId,
                'recibo_parcial_id' => $reciboParcialId,
                'encargado' => $encargadoActual,
                'area' => 'Costura',
                'cantidad_entregada' => 0,
                'talla' => 'NOVEDAD',
                'genero' => 'N/A',
                'color_nombre' => null,
                'usuario_id' => auth()->id(),
                'observaciones' => $observaciones,
            ]);

            return [
                'success' => true,
                'completado' => false,
                'es_novedad' => true
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error al guardar la novedad: ' . $e->getMessage()];
        }
    }
}
