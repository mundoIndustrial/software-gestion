<?php

namespace App\Application\Talleres\UseCases;

use App\Models\User;
use App\Models\EntregaReciboCostura;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ObtenerDetalleEntregasUseCase
{
    public function execute(int $tallerId, int $reciboId, bool $isParcial)
    {
        $taller = User::findOrFail($tallerId);
        
        if ($isParcial) {
            $recibo = DB::table('recibo_por_partes as rpp')
                ->leftJoin('consecutivos_recibos_pedidos as crp_base', function ($join) {
                    $join->on('rpp.consecutivo_original', '=', 'crp_base.consecutivo_actual')
                        ->where('crp_base.tipo_recibo', '=', 'CORTE-PARA-BODEGA')
                        ->whereColumn('crp_base.prenda_bodega_id', 'rpp.prenda_pedido_id');
                })
                ->leftJoin('prenda_bodega as pb', 'crp_base.prenda_bodega_id', '=', 'pb.id')
                ->leftJoin('prendas_pedido as pp', 'rpp.prenda_pedido_id', '=', 'pp.id')
                ->leftJoin('pedidos_produccion as ppro', 'rpp.pedido_produccion_id', '=', 'ppro.id')
                ->leftJoin('clientes', 'ppro.cliente_id', '=', 'clientes.id')
                ->where('rpp.id', $reciboId)
                ->select(
                    'rpp.id',
                    'rpp.consecutivo_parcial as numero_recibo',
                    'rpp.tipo_recibo',
                    'rpp.prenda_pedido_id',
                    'crp_base.prenda_bodega_id',
                    'pp.nombre_prenda as nombre_prenda_pedido',
                    'pp.descripcion as descripcion_prenda_pedido',
                    'pb.nombre as nombre_prenda_bodega',
                    'pb.descripcion as descripcion_prenda_bodega',
                    'clientes.nombre as cliente_pedido'
                )
                ->first();

            $entregasRaw = EntregaReciboCostura::where('recibo_parcial_id', $reciboId)
                ->orderBy('created_at', 'desc')
                ->get();

            $totalesAsignados = $recibo && strtoupper(trim((string) ($recibo->tipo_recibo ?? ''))) === 'CORTE-PARA-BODEGA'
                ? DB::table('prenda_tallas_bodega')
                    ->where('prenda_bodega_id', (int) ($recibo->prenda_bodega_id ?? 0))
                    ->select('talla', 'genero', 'color', 'cantidad')
                    ->get()
                : DB::table('recibos_por_partes_tallas')
                    ->where('recibo_por_partes_id', $reciboId)
                    ->get();
        } else {
            $recibo = DB::table('consecutivos_recibos_pedidos as crp')
                ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
                ->join('pedidos_produccion as ppro', 'crp.pedido_produccion_id', '=', 'ppro.id')
                ->join('clientes', 'ppro.cliente_id', '=', 'clientes.id')
                ->where('crp.id', $reciboId)
                ->select('crp.id', 'crp.consecutivo_actual as numero_recibo', 'pp.nombre_prenda', 'pp.descripcion as descripcion_prenda', 'clientes.nombre as cliente')
                ->first();
                
            $entregasRaw = EntregaReciboCostura::where('consecutivo_recibo_id', $reciboId)
                ->orderBy('created_at', 'desc')
                ->get();

            $prendaId = DB::table('consecutivos_recibos_pedidos')->where('id', $reciboId)->value('prenda_id');
            
            // Para recibos normales, el desglose puede estar en prenda_pedido_tallas + prenda_pedido_talla_colores
            $totalesAsignados = collect();
            if ($prendaId) {
                // Intentamos traer por colores si existen
                $totalesColores = DB::table('prenda_pedido_talla_colores as tc')
                    ->join('prenda_pedido_tallas as pt', 'tc.prenda_pedido_talla_id', '=', 'pt.id')
                    ->where('pt.prenda_pedido_id', $prendaId)
                    ->select('pt.talla', 'pt.genero', 'tc.color_nombre', 'tc.cantidad')
                    ->get();
                
                if ($totalesColores->isNotEmpty()) {
                    $totalesAsignados = $totalesColores;
                } else {
                    // Si no hay desglose de colores, tomamos la talla directa
                    $totalesAsignados = DB::table('prenda_pedido_tallas')
                        ->where('prenda_pedido_id', $prendaId)
                        ->select('talla', 'genero', DB::raw('NULL as color_nombre'), 'cantidad')
                        ->get();
                }
            }
        }
        
        if (!$recibo) return null;

        $esBodega = $isParcial && strtoupper(trim((string) ($recibo->tipo_recibo ?? ''))) === 'CORTE-PARA-BODEGA';
        $prendaBodegaId = (int) ($recibo->prenda_bodega_id ?? 0);
        $fechaSalidaQuery = DB::table('procesos_prenda')
            ->whereRaw("LOWER(TRIM(proceso)) = 'costura'");

        if ($isParcial) {
            $fechaSalidaQuery->where('numero_recibo_parcial', $recibo->numero_recibo);
            if ($esBodega && $prendaBodegaId > 0) {
                $fechaSalidaQuery->where('prenda_bodega_id', $prendaBodegaId);
            } else {
                $fechaSalidaQuery->where('prenda_pedido_id', (int) ($recibo->prenda_pedido_id ?? 0));
            }
        } else {
            $fechaSalidaQuery->where('numero_recibo', $recibo->numero_recibo);
        }

        $fechaSalida = $fechaSalidaQuery
            ->orderByDesc('fecha_de_asignacion_encargado')
            ->orderByDesc('id')
            ->selectRaw('COALESCE(fecha_de_asignacion_encargado, created_at) as fecha_salida')
            ->value('fecha_salida');

        if ($esBodega) {
            $recibo->nombre_prenda = $recibo->nombre_prenda_bodega ?? 'N/A';
            $recibo->descripcion_prenda = $recibo->descripcion_prenda_bodega ?? '';
            $recibo->cliente = 'Bodega';
        } elseif ($isParcial) {
            $recibo->nombre_prenda = $recibo->nombre_prenda_pedido ?? 'N/A';
            $recibo->descripcion_prenda = $recibo->descripcion_prenda_pedido ?? '';
            $recibo->cliente = $recibo->cliente_pedido ?? 'N/A';
        }

        $mapaTotales = [];
        foreach ($totalesAsignados as $t) {
            $color = $t->color_nombre ?? $t->color ?? '';
            $key = $this->generarKey($t->talla, $t->genero ?? 'UNISEX', $color);
            $mapaTotales[$key] = ($mapaTotales[$key] ?? 0) + (int) $t->cantidad;
        }

        $acumulados = [];
        $entregasProcesadas = collect();
        $totalGeneral = 0;
        Carbon::setLocale('es');

        foreach ($entregasRaw as $entrega) {
            $items = $this->extraerItemsDeEntrega($entrega);
            foreach ($items as $item) {
                $key = $this->generarKey($item['talla'], $item['genero'], $item['color']);
                $acumulados[$key] = ($acumulados[$key] ?? 0) + $item['cantidad'];
            }
        }


        foreach ($entregasRaw as $entrega) {
            $items = $this->extraerItemsDeEntrega($entrega);
            $fecha = Carbon::parse($entrega->created_at);
            if ($fecha->isSunday()) continue;

            foreach ($items as $item) {
                $key = $this->generarKey($item['talla'], $item['genero'], $item['color']);
                $startOfWeek = $fecha->copy()->startOfWeek(Carbon::SATURDAY);
                $endOfWeek = $fecha->copy()->endOfWeek(Carbon::FRIDAY);
                $mesI = mb_strtoupper($startOfWeek->translatedFormat('F'));
                $mesF = mb_strtoupper($endOfWeek->translatedFormat('F'));
                $grupoSemana = ($mesI == $mesF) 
                    ? "SEMANA DEL {$startOfWeek->format('d')} AL {$endOfWeek->format('d')} DE {$mesI}"
                    : "SEMANA DEL {$startOfWeek->format('d')} DE {$mesI} AL {$endOfWeek->format('d')} DE {$mesF}";

                $entregasProcesadas->push([
                    'id' => $entrega->id,
                    'fecha_formateada' => $fecha->format('d/m/Y'),
                    'fecha_salida' => $fechaSalida ? Carbon::parse($fechaSalida)->format('d/m/Y h:i A') : '-',
                    'fecha_entrada' => $fecha->format('d/m/Y h:i A'),
                    'descripcion' => mb_strtoupper($recibo->descripcion_prenda),
                    'talla_nombre' => $item['talla'],
                    'genero' => $item['genero'],
                    'color' => $item['color'],
                    'cantidad' => $item['cantidad'],
                    'total_entregado' => $acumulados[$key] ?? 0,
                    'total_asignado' => $mapaTotales[$key] ?? 0,
                    'precio' => $entrega->precio,
                    'grupo' => $grupoSemana,
                    'orden_semana' => $startOfWeek->format('Ymd'),
                    'fecha_obj' => $fecha
                ]);
                $totalGeneral += $item['cantidad'];
            }
        }
        
        return [
            'taller' => $taller,
            'recibo' => $recibo,
            'entregasAgrupadas' => $entregasProcesadas->sortByDesc('fecha_obj')->groupBy('grupo'),
            'totalGeneral' => $totalGeneral
        ];
    }

    private function generarKey($talla, $genero, $color) {
        return strtoupper(trim((string)$talla)) . '_' . strtoupper(trim((string)($genero ?: 'UNISEX'))) . '_' . strtoupper(trim((string)($color ?: '')));
    }

    private function extraerItemsDeEntrega($entrega) {
        if ($entrega->talla) {
            return [[
                'talla' => $entrega->talla,
                'cantidad' => (int)$entrega->cantidad_entregada,
                'genero' => $entrega->genero ?? 'UNISEX',
                'color' => $entrega->color_nombre
            ]];
        }
        
        $dt = is_string($entrega->detalle_tallas) ? json_decode($entrega->detalle_tallas, true) : $entrega->detalle_tallas;
        $items = [];
        if ($dt && is_array($dt)) {
            foreach ($dt as $talla => $cantidad) {
                if ($cantidad > 0) {
                    $items[] = [
                        'talla' => $talla,
                        'cantidad' => (int)$cantidad,
                        'genero' => 'UNISEX',
                        'color' => null
                    ];
                }
            }
        }
        return $items;
    }
}
