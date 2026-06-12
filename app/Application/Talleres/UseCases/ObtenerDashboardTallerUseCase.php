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
                     ->whereRaw("LOWER(TRIM(ppren.proceso)) = 'costura'");
            })
            ->whereIn('crp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->whereRaw("LOWER(TRIM(COALESCE(crp.area, ''))) = 'costura'")
            ->where('ppren.encargado', '=', $nombreTaller)
            ->select(
                'crp.id', 
                'crp.consecutivo_actual as numero_recibo', 
                'pp.id as prenda_id',
                'pp.nombre_prenda',
                'pp.descripcion as descripcion_prenda',
                'clientes.nombre as cliente', 
                'crp.tipo_recibo',
                DB::raw('MAX(COALESCE(ppren.fecha_de_asignacion_encargado, ppren.created_at)) as fecha_salida'),
                DB::raw('0 as es_parcial')
            )
            ->groupBy('crp.id')
            ->get();

        $recibosNormalesBodega = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('prenda_bodega as pb', 'crp.prenda_bodega_id', '=', 'pb.id')
            ->join('procesos_prenda as ppren', function ($join) {
                $join->on('crp.consecutivo_actual', '=', 'ppren.numero_recibo')
                    ->whereRaw("LOWER(TRIM(ppren.proceso)) = 'costura'");
            })
            ->where('crp.tipo_recibo', 'CORTE-PARA-BODEGA')
            ->whereRaw("LOWER(TRIM(COALESCE(crp.area, ''))) = 'costura'")
            ->where('ppren.encargado', '=', $nombreTaller)
            ->select(
                'crp.id',
                'crp.consecutivo_actual as numero_recibo',
                DB::raw('NULL as prenda_id'),
                'pb.nombre as nombre_prenda',
                'pb.descripcion as descripcion_prenda',
                DB::raw("'Bodega' as cliente"),
                'crp.tipo_recibo',
                DB::raw('MAX(COALESCE(ppren.fecha_de_asignacion_encargado, ppren.created_at)) as fecha_salida'),
                DB::raw('0 as es_parcial')
            )
            ->groupBy('crp.id')
            ->get();

        // 2. Recibos parciales asignados al taller
        $recibosParciales = DB::table('recibo_por_partes as rpp')
            ->leftJoin('prendas_pedido as pp', 'rpp.prenda_pedido_id', '=', 'pp.id')
            ->leftJoin('pedidos_produccion as ppro', 'rpp.pedido_produccion_id', '=', 'ppro.id')
            ->leftJoin('clientes', 'ppro.cliente_id', '=', 'clientes.id')
            ->leftJoin('consecutivos_recibos_pedidos as crp_base', function ($join) {
                $join->on('rpp.consecutivo_original', '=', 'crp_base.consecutivo_actual')
                    ->where('crp_base.tipo_recibo', '=', 'CORTE-PARA-BODEGA')
                    ->whereColumn('crp_base.prenda_bodega_id', 'rpp.prenda_pedido_id');
            })
            ->leftJoin('prenda_bodega as pb', 'crp_base.prenda_bodega_id', '=', 'pb.id')
            ->join('procesos_prenda as ppren', function($join) {
                $join->on('rpp.consecutivo_parcial', '=', 'ppren.numero_recibo_parcial')
                    ->whereRaw("LOWER(TRIM(ppren.proceso)) = 'costura'");
            })
            ->whereIn('rpp.tipo_recibo', ['REFLECTIVO', 'COSTURA', 'CORTE-PARA-BODEGA'])
            ->whereRaw("LOWER(TRIM(COALESCE(rpp.estado, ''))) NOT IN ('anulada', 'anulado')")
            ->where('ppren.encargado', '=', $nombreTaller)
            ->select(
                'rpp.id',
                DB::raw('ANY_VALUE(rpp.consecutivo_parcial) as numero_recibo'),
                DB::raw("ANY_VALUE(CASE WHEN UPPER(TRIM(rpp.tipo_recibo)) = 'CORTE-PARA-BODEGA' THEN COALESCE(pb.nombre, 'N/A') ELSE COALESCE(pp.nombre_prenda, 'N/A') END) as nombre_prenda"),
                DB::raw("ANY_VALUE(CASE WHEN UPPER(TRIM(rpp.tipo_recibo)) = 'CORTE-PARA-BODEGA' THEN COALESCE(pb.descripcion, 'N/A') ELSE COALESCE(pp.descripcion, 'N/A') END) as descripcion_prenda"),
                DB::raw("ANY_VALUE(CASE WHEN UPPER(TRIM(rpp.tipo_recibo)) = 'CORTE-PARA-BODEGA' THEN 'Bodega' ELSE COALESCE(clientes.nombre, 'Bodega') END) as cliente"),
                DB::raw('ANY_VALUE(rpp.tipo_recibo) as tipo_recibo'),
                DB::raw('MAX(COALESCE(ppren.fecha_de_asignacion_encargado, ppren.created_at)) as fecha_salida'),
                DB::raw('1 as es_parcial')
            )
            ->groupBy('rpp.id')
            ->get();

        $recibosNormales = $recibosNormales->concat($recibosNormalesBodega);
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

        $idsNormalesBodega = $recibosNormales
            ->filter(fn($r) => ($r->tipo_recibo ?? null) === 'CORTE-PARA-BODEGA')
            ->pluck('id')
            ->values()
            ->all();

        $totalesAsignadosNormalesBodega = [];
        if (!empty($idsNormalesBodega)) {
            $totalesAsignadosNormalesBodega = DB::table('consecutivos_recibos_pedidos as crp')
                ->join('prenda_tallas_bodega as ptb', 'crp.prenda_bodega_id', '=', 'ptb.prenda_bodega_id')
                ->whereIn('crp.id', $idsNormalesBodega)
                ->groupBy('crp.id')
                ->select('crp.id', DB::raw('SUM(ptb.cantidad) as total'))
                ->pluck('total', 'crp.id')
                ->toArray();
        }

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
            // No convertir a entero para preservar decimales (175.1, 187.2, etc.)
            
            if ($r->es_parcial) {
                $r->cantidad_total = $totalesAsignadosParciales[$r->id] ?? 0;
                $r->cantidad_entregada = $entregasParciales[$r->id] ?? 0;
            } else {
                if (($r->tipo_recibo ?? null) === 'CORTE-PARA-BODEGA') {
                    $r->cantidad_total = $totalesAsignadosNormalesBodega[$r->id] ?? 0;
                } else {
                    $r->cantidad_total = $totalesAsignadosNormales[$r->prenda_id] ?? 0;
                }
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

    public function executeBatchStats(array $userIds): array
    {
        $users = User::whereIn('id', $userIds)
            ->get(['id', 'name'])
            ->filter(fn ($user) => !empty(trim((string) $user->name)));

        if ($users->isEmpty()) {
            return [];
        }

        $names = $users->pluck('name')->map(fn ($name) => trim((string) $name))->values()->all();

        $recibosNormales = $this->obtenerRecibosNormalesPorNombres($names);
        $recibosNormalesBodega = $this->obtenerRecibosNormalesBodegaPorNombres($names);
        $recibosParciales = $this->obtenerRecibosParcialesPorNombres($names);

        $recibosPorTaller = [];
        foreach ($recibosNormales->concat($recibosNormalesBodega)->concat($recibosParciales) as $recibo) {
            $tallerNombre = trim((string) ($recibo->taller_nombre ?? ''));
            if ($tallerNombre === '') {
                continue;
            }

            $recibosPorTaller[$tallerNombre] ??= [];
            $recibosPorTaller[$tallerNombre][] = $recibo;
        }

        $idsNormales = $recibosNormales->pluck('id')->merge($recibosNormalesBodega->pluck('id'))->values()->all();
        $idsParciales = $recibosParciales->pluck('id')->values()->all();

        $completadosNormales = $this->contarCompletadosPorOperario($names, 'id_recibo', $idsNormales);
        $completadosParciales = $this->contarCompletadosPorOperario($names, 'id_parcial', $idsParciales);

        $resultado = [];

        foreach ($users as $user) {
            $nombreTaller = trim((string) $user->name);
            $total = count($recibosPorTaller[$nombreTaller] ?? []);
            $completados = (int) ($completadosNormales[$nombreTaller] ?? 0) + (int) ($completadosParciales[$nombreTaller] ?? 0);

            $resultado[$user->id] = [
                'taller_id' => $user->id,
                'completados' => $completados,
                'pendientes' => max(0, $total - $completados),
                'total' => $total,
            ];
        }

        return $resultado;
    }

    private function obtenerRecibosNormalesPorNombres(array $nombres)
    {
        return DB::table('consecutivos_recibos_pedidos as crp')
            ->join('pedidos_produccion as ppro', 'crp.pedido_produccion_id', '=', 'ppro.id')
            ->join('procesos_prenda as ppren', function($join) {
                $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
                     ->on('crp.consecutivo_actual', '=', 'ppren.numero_recibo')
                     ->whereRaw("LOWER(TRIM(ppren.proceso)) = 'costura'");
            })
            ->whereIn('crp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->whereRaw("LOWER(TRIM(COALESCE(crp.area, ''))) = 'costura'")
            ->whereIn('ppren.encargado', $nombres)
            ->select(
                'crp.id',
                'ppren.encargado as taller_nombre',
                DB::raw('0 as es_parcial')
            )
            ->groupBy('crp.id', 'ppren.encargado')
            ->get();
    }

    private function obtenerRecibosNormalesBodegaPorNombres(array $nombres)
    {
        return DB::table('consecutivos_recibos_pedidos as crp')
            ->join('procesos_prenda as ppren', function ($join) {
                $join->on('crp.consecutivo_actual', '=', 'ppren.numero_recibo')
                    ->whereRaw("LOWER(TRIM(ppren.proceso)) = 'costura'");
            })
            ->where('crp.tipo_recibo', 'CORTE-PARA-BODEGA')
            ->whereRaw("LOWER(TRIM(COALESCE(crp.area, ''))) = 'costura'")
            ->whereIn('ppren.encargado', $nombres)
            ->select(
                'crp.id',
                'ppren.encargado as taller_nombre',
                DB::raw('0 as es_parcial')
            )
            ->groupBy('crp.id', 'ppren.encargado')
            ->get();
    }

    private function obtenerRecibosParcialesPorNombres(array $nombres)
    {
        return DB::table('recibo_por_partes as rpp')
            ->join('procesos_prenda as ppren', function($join) {
                $join->on('rpp.consecutivo_parcial', '=', 'ppren.numero_recibo_parcial')
                    ->whereRaw("LOWER(TRIM(ppren.proceso)) = 'costura'");
            })
            ->whereIn('rpp.tipo_recibo', ['REFLECTIVO', 'COSTURA', 'CORTE-PARA-BODEGA'])
            ->whereIn('ppren.encargado', $nombres)
            ->select(
                'rpp.id',
                'ppren.encargado as taller_nombre',
                DB::raw('1 as es_parcial')
            )
            ->groupBy('rpp.id', 'ppren.encargado')
            ->get();
    }

    private function contarCompletadosPorOperario(array $nombres, string $columnaId, array $ids): array
    {
        if (empty($nombres) || empty($ids)) {
            return [];
        }

        $rows = DB::table('prenda_recibo_completado')
            ->whereIn('nombre_operario', $nombres)
            ->where('area', 'Costura')
            ->whereIn($columnaId, $ids)
            ->select('nombre_operario', $columnaId)
            ->distinct()
            ->get();

        $conteos = [];

        foreach ($rows as $row) {
            $nombre = trim((string) $row->nombre_operario);
            if ($nombre === '') {
                continue;
            }

            $conteos[$nombre] = ($conteos[$nombre] ?? 0) + 1;
        }

        return $conteos;
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
