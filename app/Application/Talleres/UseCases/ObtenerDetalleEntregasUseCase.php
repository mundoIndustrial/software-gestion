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
                ->join('prendas_pedido as pp', 'rpp.prenda_pedido_id', '=', 'pp.id')
                ->join('pedidos_produccion as ppro', 'rpp.pedido_produccion_id', '=', 'ppro.id')
                ->join('clientes', 'ppro.cliente_id', '=', 'clientes.id')
                ->where('rpp.id', $reciboId)
                ->select('rpp.id', 'rpp.consecutivo_parcial as numero_recibo', 'pp.nombre_prenda', 'pp.descripcion as descripcion_prenda', 'clientes.nombre as cliente')
                ->first();
                
            $entregasRaw = EntregaReciboCostura::where('recibo_parcial_id', $reciboId)
                ->orderBy('created_at', 'desc')
                ->get();

            $totalesAsignados = DB::table('recibos_por_partes_tallas')
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

        $mapaTotales = [];
        foreach ($totalesAsignados as $t) {
            $key = $this->generarKey($t->talla, $t->genero ?? 'UNISEX', $t->color_nombre ?? '');
            $mapaTotales[$key] = ($mapaTotales[$key] ?? 0) + $t->cantidad;
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
                    'descripcion' => mb_strtoupper($recibo->descripcion_prenda),
                    'talla_nombre' => $item['talla'],
                    'genero' => $item['genero'],
                    'color' => $item['color'],
                    'cantidad' => $item['cantidad'],
                    'total_entregado' => $acumulados[$key] ?? 0,
                    'total_asignado' => $mapaTotales[$key] ?? 0,
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
