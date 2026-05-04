<?php

namespace App\Infrastructure\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ConsecutivoReciboPedido;
use App\Models\EntregaReciboCostura;
use App\Models\PrendaPedido;
use App\Models\PedidoProduccion;
use App\Models\SeguimientoPedidosPorPrenda;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EntregasTalleresController extends Controller
{
    public function index()
    {
        return view('entregas_talleres.index');
    }

    public function buscar(Request $request)
    {
        $busqueda = $request->input('busqueda');
        
        if (!$busqueda) {
            return redirect()->route('entregas-talleres.index');
        }
        
        // 1. Recibos normales
        $recibosNormales = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
            ->join('pedidos_produccion as ppro', 'crp.pedido_produccion_id', '=', 'ppro.id')
            ->leftJoin('procesos_prenda as ppren', function($join) {
                $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
                     ->on('crp.consecutivo_actual', '=', 'ppren.numero_recibo')
                     ->where('ppren.proceso', '=', 'Costura');
            })
            ->whereIn('crp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->where('crp.area', '=', 'Costura') // Solo los que están en Costura
            ->where(function($query) use ($busqueda) {
                $query->where('crp.consecutivo_actual', 'LIKE', "%$busqueda%")
                      ->orWhere('ppren.encargado', 'LIKE', "%$busqueda%");
            })
            ->select(
                'crp.id', 
                'crp.consecutivo_actual as numero_recibo', 
                'pp.nombre_prenda', 
                'ppren.encargado', 
                'crp.tipo_recibo',
                DB::raw('0 as es_parcial')
            )
            ->get();

        // 2. Recibos parciales (ReciboPorPartes)
        // Para parciales, consideramos que están en Costura si tienen un proceso de Costura asignado
        $recibosParciales = DB::table('recibo_por_partes as rpp')
            ->join('prendas_pedido as pp', 'rpp.prenda_pedido_id', '=', 'pp.id')
            ->join('pedidos_produccion as ppro', 'rpp.pedido_produccion_id', '=', 'ppro.id')
            ->join('procesos_prenda as ppren', function($join) {
                $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
                     ->on('rpp.prenda_pedido_id', '=', 'ppren.prenda_pedido_id')
                     ->on('rpp.consecutivo_parcial', '=', 'ppren.numero_recibo_parcial')
                     ->where('ppren.proceso', '=', 'Costura');
            })
            ->whereIn('rpp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->where(function($query) use ($busqueda) {
                $query->where('rpp.consecutivo_parcial', 'LIKE', "%$busqueda%")
                      ->orWhere('ppren.encargado', 'LIKE', "%$busqueda%");
            })
            ->select(
                'rpp.id', 
                'rpp.consecutivo_parcial as numero_recibo', 
                'pp.nombre_prenda', 
                'ppren.encargado', 
                'rpp.tipo_recibo',
                DB::raw('1 as es_parcial')
            )
            ->get();

        $recibos = $recibosNormales->concat($recibosParciales)->map(function($r) {
            $r->numero_recibo = $r->numero_recibo + 0;
            return $r;
        });

        return view('entregas_talleres.resultados', compact('recibos', 'busqueda'));
    }

    public function showRecibo(Request $request, $id)
    {
        $esParcial = $request->query('es_parcial') == '1';

        if ($esParcial) {
            $recibo = \App\Models\ReciboPorPartes::with(['pedido', 'prenda', 'tallas'])->findOrFail($id);
            $numeroRecibo = $recibo->consecutivo_parcial + 0;
            
            // Buscar encargado en procesos_prenda
            $encargado = DB::table('procesos_prenda')
                ->where('numero_pedido', $recibo->pedido->numero_pedido)
                ->where('prenda_pedido_id', $recibo->prenda_pedido_id)
                ->where('numero_recibo_parcial', $recibo->consecutivo_parcial)
                ->where('proceso', 'Costura')
                ->value('encargado');

            $tallas = $recibo->tallas->map(function($t) {
                return (object)[
                    'talla' => $t->talla,
                    'cantidad' => $t->cantidad,
                    'genero' => $t->genero
                ];
            });

            // Entregas realizadas para este parcial
            $entregas = DB::table('entrega_recibo_costura')
                ->where('recibo_parcial_id', $id)
                ->get();
        } else {
            $recibo = \App\Models\ConsecutivoReciboPedido::with(['pedido', 'prenda', 'prenda.tallas'])->findOrFail($id);
            $numeroRecibo = $recibo->consecutivo_actual;

            // Buscar encargado
            $encargado = DB::table('procesos_prenda')
                ->where('numero_pedido', $recibo->pedido->numero_pedido)
                ->where('numero_recibo', $recibo->consecutivo_actual)
                ->where('proceso', 'Costura')
                ->value('encargado');

            $tallas = $recibo->prenda->tallas;

            // Entregas realizadas para este recibo normal
            $entregas = DB::table('entrega_recibo_costura')
                ->where('consecutivo_recibo_id', $id)
                ->get();
        }

        // Mapear entregas por talla
        $entregasPorTalla = [];
        foreach ($entregas as $entrega) {
            $detalle = json_decode($entrega->detalle_tallas, true);
            foreach ($detalle as $talla => $cantidad) {
                $entregasPorTalla[$talla] = ($entregasPorTalla[$talla] ?? 0) + $cantidad;
            }
        }

        return view('entregas_talleres.detalle', compact('recibo', 'tallas', 'entregasPorTalla', 'encargado', 'esParcial', 'numeroRecibo'));
    }

    public function store(Request $request)
    {
        $esParcial = $request->input('es_parcial') == '1';
        $reciboId = $request->input('recibo_id');

        $request->validate([
            'recibo_id' => 'required',
            'talla' => 'required|string',
            'cantidad' => 'required|integer|min:1',
        ]);

        if ($esParcial) {
            $recibo = \App\Models\ReciboPorPartes::with(['pedido', 'prenda', 'tallas'])->findOrFail($reciboId);
            $consecutivoReciboId = null;
            $reciboParcialId = $reciboId;
            $prendaId = $recibo->prenda_pedido_id;
            
            $tallaInfo = $recibo->tallas->where('talla', $request->talla)->first();
            if (!$tallaInfo) {
                return response()->json(['success' => false, 'message' => 'Talla no encontrada'], 400);
            }
            $maxCantidad = $tallaInfo->cantidad;

            $yaEntregado = EntregaReciboCostura::where('recibo_parcial_id', $reciboId)
                ->get()
                ->sum(function($m) {
                    $dt = is_array($m->detalle_tallas) ? $m->detalle_tallas : json_decode($m->detalle_tallas, true);
                    return $dt[request('talla')] ?? 0;
                });

            $encargadoActual = DB::table('procesos_prenda')
                ->where('numero_pedido', $recibo->pedido->numero_pedido)
                ->where('prenda_pedido_id', $recibo->prenda_pedido_id)
                ->where('numero_recibo_parcial', $recibo->consecutivo_parcial)
                ->where('proceso', 'Costura')
                ->value('encargado') ?? 'No asignado';

            $numeroReciboDisplay = $recibo->consecutivo_parcial;
        } else {
            $recibo = ConsecutivoReciboPedido::with(['pedido', 'prenda.tallas'])->findOrFail($reciboId);
            $consecutivoReciboId = $reciboId;
            $reciboParcialId = null;
            $prendaId = $recibo->prenda_id;

            $tallaInfo = $recibo->prenda->tallas->where('talla', $request->talla)->first();
            if (!$tallaInfo) {
                return response()->json(['success' => false, 'message' => 'Talla no encontrada'], 400);
            }
            $maxCantidad = $tallaInfo->cantidad;

            $yaEntregado = EntregaReciboCostura::where('consecutivo_recibo_id', $reciboId)
                ->get()
                ->sum(function($m) {
                    $dt = is_array($m->detalle_tallas) ? $m->detalle_tallas : json_decode($m->detalle_tallas, true);
                    return $dt[request('talla')] ?? 0;
                });

            $encargadoActual = DB::table('procesos_prenda')
                ->where('numero_pedido', $recibo->pedido->numero_pedido)
                ->where('numero_recibo', $recibo->consecutivo_actual)
                ->where('proceso', 'Costura')
                ->value('encargado') ?? 'No asignado';

            $numeroReciboDisplay = $recibo->consecutivo_actual;
        }

        if (($yaEntregado + $request->cantidad) > $maxCantidad) {
            return response()->json([
                'success' => false, 
                'message' => "La cantidad excede el máximo permitido ({$maxCantidad}). Ya se han entregado {$yaEntregado}."
            ], 400);
        }

        EntregaReciboCostura::create([
            'prenda_pedido_id' => $prendaId,
            'consecutivo_recibo_id' => $consecutivoReciboId,
            'recibo_parcial_id' => $reciboParcialId,
            'encargado' => $encargadoActual,
            'area' => 'Costura',
            'cantidad_entregada' => $request->cantidad,
            'detalle_tallas' => [$request->talla => $request->cantidad],
            'usuario_id' => auth()->id(),
        ]);

        // VERIFICAR COMPLETADO
        if ($esParcial) {
            $requerimientos = $recibo->tallas->pluck('cantidad', 'talla')->toArray();
            $entregasTotales = EntregaReciboCostura::where('recibo_parcial_id', $reciboId)->get();
        } else {
            $requerimientos = $recibo->prenda->tallas->pluck('cantidad', 'talla')->toArray();
            $entregasTotales = EntregaReciboCostura::where('consecutivo_recibo_id', $reciboId)->get();
        }

        $entregadoPorTalla = [];
        foreach ($entregasTotales as $entrega) {
            $dt = is_array($entrega->detalle_tallas) ? $entrega->detalle_tallas : json_decode($entrega->detalle_tallas, true);
            foreach ($dt as $t => $c) {
                $entregadoPorTalla[$t] = ($entregadoPorTalla[$t] ?? 0) + $c;
            }
        }

        $todoCompletado = true;
        foreach ($requerimientos as $t => $c) {
            if (($entregadoPorTalla[$t] ?? 0) < $c) {
                $todoCompletado = false;
                break;
            }
        }

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

        return response()->json([
            'success' => true, 
            'completado' => $todoCompletado
        ]);
    }

    public function historial(Request $request, $id)
    {
        $esParcial = $request->query('es_parcial') == '1';

        $entregas = EntregaReciboCostura::where($esParcial ? 'recibo_parcial_id' : 'consecutivo_recibo_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $formateadas = $entregas->map(function($e) {
            return [
                'id' => $e->id,
                'cantidad_total' => $e->cantidad_entregada,
                'fecha' => $e->created_at->format('d/m/Y H:i'),
                'encargado' => $e->encargado,
                'detalle' => $e->detalle_tallas
            ];
        });

        return response()->json($formateadas);
    }

    public function apiSearch(Request $request)
    {
        $term = $request->get('term');
        
        // 1. Recibos normales
        $recibosNormales = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
            ->join('pedidos_produccion as ppro', 'crp.pedido_produccion_id', '=', 'ppro.id')
            ->leftJoin('procesos_prenda as ppren', function($join) {
                $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
                     ->on('crp.consecutivo_actual', '=', 'ppren.numero_recibo')
                     ->where('ppren.proceso', '=', 'Costura');
            })
            ->whereIn('crp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->where('crp.area', '=', 'Costura') // Solo los que están en Costura
            ->where(function($query) use ($term) {
                $query->where('crp.consecutivo_actual', 'LIKE', "%$term%")
                      ->orWhere('ppren.encargado', 'LIKE', "%$term%");
            })
            ->select(
                'crp.id', 
                'crp.consecutivo_actual as numero_recibo', 
                'pp.nombre_prenda', 
                'ppren.encargado',
                DB::raw('0 as es_parcial')
            )
            ->limit(10)
            ->get();

        // 2. Recibos parciales
        $recibosParciales = DB::table('recibo_por_partes as rpp')
            ->join('prendas_pedido as pp', 'rpp.prenda_pedido_id', '=', 'pp.id')
            ->join('pedidos_produccion as ppro', 'rpp.pedido_produccion_id', '=', 'ppro.id')
            ->join('procesos_prenda as ppren', function($join) {
                $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
                     ->on('rpp.prenda_pedido_id', '=', 'ppren.prenda_pedido_id')
                     ->on('rpp.consecutivo_parcial', '=', 'ppren.numero_recibo_parcial')
                     ->where('ppren.proceso', '=', 'Costura');
            })
            ->whereIn('rpp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->where(function($query) use ($term) {
                $query->where('rpp.consecutivo_parcial', 'LIKE', "%$term%")
                      ->orWhere('ppren.encargado', 'LIKE', "%$term%");
            })
            ->select(
                'rpp.id', 
                'rpp.consecutivo_parcial as numero_recibo', 
                'pp.nombre_prenda', 
                'ppren.encargado',
                DB::raw('1 as es_parcial')
            )
            ->limit(10)
            ->get();

        $recibos = $recibosNormales->concat($recibosParciales);

        return response()->json($recibos);
    }
}
