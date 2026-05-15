<?php

namespace App\Application\Talleres\UseCases;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ObtenerDashboardTallerUseCase
{
    public function execute(int $userId)
    {
        $taller = User::findOrFail($userId);
        $nombreTaller = $taller->name;

        // 1. Recibos normales asignados al taller (por encargado en Costura)
        $recibosNormales = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
            ->join('pedidos_produccion as ppro', 'crp.pedido_produccion_id', '=', 'ppro.id')
            ->join('clientes', 'ppro.cliente_id', '=', 'clientes.id')
            ->join('procesos_prenda as ppren', function($join) {
                $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
                     ->on('crp.consecutivo_actual', '=', 'ppren.numero_recibo')
                     ->where('ppren.proceso', '=', 'Costura');
            })
            ->whereIn('crp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->where('crp.area', '=', 'Costura')
            ->where('ppren.encargado', '=', $nombreTaller)
            ->select(
                'crp.id', 
                'crp.consecutivo_actual as numero_recibo', 
                'pp.id as prenda_id',
                'pp.nombre_prenda',
                'pp.descripcion as descripcion_prenda',
                'clientes.nombre as cliente', 
                'crp.tipo_recibo',
                DB::raw('0 as es_parcial')
            )
            ->get();

        // 2. Recibos parciales asignados al taller
        $recibosParciales = DB::table('recibo_por_partes as rpp')
            ->join('prendas_pedido as pp', 'rpp.prenda_pedido_id', '=', 'pp.id')
            ->join('pedidos_produccion as ppro', 'rpp.pedido_produccion_id', '=', 'ppro.id')
            ->join('clientes', 'ppro.cliente_id', '=', 'clientes.id')
            ->join('procesos_prenda as ppren', function($join) {
                $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
                     ->on('rpp.prenda_pedido_id', '=', 'ppren.prenda_pedido_id')
                     ->on('rpp.consecutivo_parcial', '=', 'ppren.numero_recibo_parcial')
                     ->where('ppren.proceso', '=', 'Costura');
            })
            ->whereIn('rpp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->where('ppren.encargado', '=', $nombreTaller)
            ->select(
                'rpp.id', 
                'rpp.consecutivo_parcial as numero_recibo', 
                'pp.nombre_prenda',
                'pp.descripcion as descripcion_prenda',
                'clientes.nombre as cliente', 
                'rpp.tipo_recibo',
                DB::raw('1 as es_parcial')
            )
            ->get();

        $recibos = $recibosNormales->concat($recibosParciales);

        // Optimización: Cargar totales y entregas en bloque
        $idsNormales = $recibosNormales->pluck('id')->toArray();
        $prendaIdsNormales = $recibosNormales->pluck('prenda_id')->unique()->toArray();
        $idsParciales = $recibosParciales->pluck('id')->toArray();

        // Totales Asignados (Normales)
        $totalesAsignadosNormales = DB::table('prenda_pedido_tallas')
            ->whereIn('prenda_pedido_id', $prendaIdsNormales)
            ->groupBy('prenda_pedido_id')
            ->select('prenda_pedido_id', DB::raw('SUM(cantidad) as total'))
            ->pluck('total', 'prenda_pedido_id');

        // Totales Asignados (Parciales)
        $totalesAsignadosParciales = DB::table('recibos_por_partes_tallas')
            ->whereIn('recibo_por_partes_id', $idsParciales)
            ->groupBy('recibo_por_partes_id')
            ->select('recibo_por_partes_id', DB::raw('SUM(cantidad) as total'))
            ->pluck('total', 'recibo_por_partes_id');

        // Entregas (Normales)
        $entregasNormales = DB::table('entrega_recibo_costura')
            ->whereIn('consecutivo_recibo_id', $idsNormales)
            ->groupBy('consecutivo_recibo_id')
            ->select('consecutivo_recibo_id', DB::raw('SUM(cantidad_entregada) as total'))
            ->pluck('total', 'consecutivo_recibo_id');

        // Entregas (Parciales)
        $entregasParciales = DB::table('entrega_recibo_costura')
            ->whereIn('recibo_parcial_id', $idsParciales)
            ->groupBy('recibo_parcial_id')
            ->select('recibo_parcial_id', DB::raw('SUM(cantidad_entregada) as total'))
            ->pluck('total', 'recibo_parcial_id');

        $recibosProcesados = $recibos->map(function($r) use ($totalesAsignadosNormales, $totalesAsignadosParciales, $entregasNormales, $entregasParciales) {
            $r->numero_recibo = (int)$r->numero_recibo;
            
            if ($r->es_parcial) {
                $r->cantidad_total = $totalesAsignadosParciales[$r->id] ?? 0;
                $r->cantidad_entregada = $entregasParciales[$r->id] ?? 0;
            } else {
                $r->cantidad_total = $totalesAsignadosNormales[$r->prenda_id] ?? 0;
                $r->cantidad_entregada = $entregasNormales[$r->id] ?? 0;
            }
            
            $r->cantidad_pendiente = max(0, $r->cantidad_total - $r->cantidad_entregada);
            $r->porcentaje = $r->cantidad_total > 0 ? round(($r->cantidad_entregada / $r->cantidad_total) * 100) : 0;
            
            return $r;
        });

        // 3. Calcular completados
        $totalCompletados = $this->calcularCompletados($nombreTaller, $recibosNormales, $recibosParciales);
        $totalPendientes = $recibosProcesados->count() - $totalCompletados;

        return [
            'taller' => $taller,
            'recibos' => $recibosProcesados,
            'total' => $recibosProcesados->count(),
            'completados' => $totalCompletados,
            'pendientes' => $totalPendientes
        ];
    }

    private function calcularCompletados($nombreTaller, $normales, $parciales)
    {
        $idsNormales = $normales->pluck('id')->toArray();
        $idsParciales = $parciales->pluck('id')->toArray();

        $countNormales = 0;
        if (!empty($idsNormales)) {
            $countNormales = DB::table('prenda_recibo_completado')
                ->whereIn('id_recibo', $idsNormales)
                ->where('nombre_operario', $nombreTaller)
                ->where('area', 'Costura')
                ->distinct('id_recibo')
                ->count('id_recibo');
        }

        $countParciales = 0;
        if (!empty($idsParciales)) {
            $countParciales = DB::table('prenda_recibo_completado')
                ->whereIn('id_parcial', $idsParciales)
                ->where('nombre_operario', $nombreTaller)
                ->where('area', 'Costura')
                ->distinct('id_parcial')
                ->count('id_parcial');
        }

        return $countNormales + $countParciales;
    }
}
