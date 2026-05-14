<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;

class TalleresController extends Controller
{
    public function index()
    {
        $roleTaller = Role::where('name', 'taller')->first();
        if (!$roleTaller) {
            $talleres = collect([]);
        } else {
            // Obtener talleres con su estado de activación
            $talleres = \App\Models\User::whereJsonContains('roles_ids', $roleTaller->id)
                ->leftJoin('taller_config', 'users.id', '=', 'taller_config.user_id')
                ->select('users.*', \Illuminate\Support\Facades\DB::raw('IFNULL(taller_config.activo, 1) as activo'))
                ->get();
        }
        
        return view('admin.talleres.index', compact('talleres'));
    }

    public function showRecibos($id)
    {
        $taller = \App\Models\User::findOrFail($id);
        $nombreTaller = $taller->name;

        // 1. Recibos normales
        $recibosNormales = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos as crp')
            ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
            ->join('pedidos_produccion as ppro', 'crp.pedido_produccion_id', '=', 'ppro.id')
            ->join('clientes', 'ppro.cliente_id', '=', 'clientes.id')
            ->leftJoin('procesos_prenda as ppren', function($join) {
                $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
                     ->on('crp.consecutivo_actual', '=', 'ppren.numero_recibo')
                     ->where('ppren.proceso', '=', 'Costura');
            })
            ->whereIn('crp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->where('crp.area', '=', 'Costura')
            ->where('ppren.encargado', '=', $nombreTaller) // Assuming 'encargado' stores the name
            ->select(
                'crp.id', 
                'crp.consecutivo_actual as numero_recibo', 
                'pp.nombre_prenda',
                'pp.descripcion as descripcion_prenda',
                'clientes.nombre as cliente', 
                'crp.tipo_recibo',
                \Illuminate\Support\Facades\DB::raw('0 as es_parcial')
            )
            ->get();

        // 2. Recibos parciales
        $recibosParciales = \Illuminate\Support\Facades\DB::table('recibo_por_partes as rpp')
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
                \Illuminate\Support\Facades\DB::raw('1 as es_parcial')
            )
            ->get();

        $recibos = $recibosNormales->concat($recibosParciales)->map(function($r) {
            $r->numero_recibo = $r->numero_recibo + 0;
            return $r;
        });

        // Completados logic mock for now
        $totalCarga = $recibos->count();
        $completados = 0; // we can refine this later

        return view('admin.talleres.show', compact('taller', 'recibos', 'totalCarga', 'completados'));
    }

    public function showEntregas($taller_id, $recibo_id, $es_parcial)
    {
        $taller = \App\Models\User::findOrFail($taller_id);
        
        // Determinar si es parcial
        $isParcial = $es_parcial == '1';
        
        if ($isParcial) {
            $recibo = \Illuminate\Support\Facades\DB::table('recibo_por_partes as rpp')
                ->join('prendas_pedido as pp', 'rpp.prenda_pedido_id', '=', 'pp.id')
                ->join('pedidos_produccion as ppro', 'rpp.pedido_produccion_id', '=', 'ppro.id')
                ->join('clientes', 'ppro.cliente_id', '=', 'clientes.id')
                ->where('rpp.id', $recibo_id)
                ->select(
                    'rpp.id',
                    'rpp.consecutivo_parcial as numero_recibo',
                    'pp.nombre_prenda',
                    'pp.descripcion as descripcion_prenda',
                    'clientes.nombre as cliente'
                )
                ->first();
                
            $entregasRaw = \App\Models\EntregaReciboCostura::where('recibo_parcial_id', $recibo_id)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $recibo = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos as crp')
                ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
                ->join('pedidos_produccion as ppro', 'crp.pedido_produccion_id', '=', 'ppro.id')
                ->join('clientes', 'ppro.cliente_id', '=', 'clientes.id')
                ->where('crp.id', $recibo_id)
                ->select(
                    'crp.id',
                    'crp.consecutivo_actual as numero_recibo',
                    'pp.nombre_prenda',
                    'pp.descripcion as descripcion_prenda',
                    'clientes.nombre as cliente'
                )
                ->first();
                
            $entregasRaw = \App\Models\EntregaReciboCostura::where('consecutivo_recibo_id', $recibo_id)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        if (!$recibo) {
            abort(404, 'Recibo no encontrado');
        }

        // Formatear el número (quitar .00)
        $recibo->numero_recibo = $recibo->numero_recibo + 0;

        // Procesar las entregas para expandir las tallas
        $entregasProcesadas = collect();
        $totalGeneral = 0;
        
        \Carbon\Carbon::setLocale('es');

        foreach ($entregasRaw as $entrega) {
            $detalleTallas = is_string($entrega->detalle_tallas) ? json_decode($entrega->detalle_tallas, true) : $entrega->detalle_tallas;
            
            if (!$detalleTallas || !is_array($detalleTallas)) continue;
            
            foreach ($detalleTallas as $talla => $cantidad) {
                if ($cantidad <= 0) continue;
                
                $fecha = \Carbon\Carbon::parse($entrega->created_at);
                
                // Los domingos no se cuentan
                if ($fecha->isSunday()) {
                    continue;
                }
                
                $anio = $fecha->year;
                
                // Agrupar de Sábado a Viernes
                $startOfWeek = $fecha->copy()->startOfWeek(\Carbon\Carbon::SATURDAY);
                $endOfWeek = $fecha->copy()->endOfWeek(\Carbon\Carbon::FRIDAY);
                
                $diaInicio = $startOfWeek->format('d');
                $mesInicio = mb_strtoupper($startOfWeek->translatedFormat('F'));
                $diaFin = $endOfWeek->format('d');
                $mesFin = mb_strtoupper($endOfWeek->translatedFormat('F'));
                
                if ($mesInicio == $mesFin) {
                    $grupoSemana = "SEMANA DEL {$diaInicio} AL {$diaFin} DE {$mesInicio}";
                } else {
                    $grupoSemana = "SEMANA DEL {$diaInicio} DE {$mesInicio} AL {$diaFin} DE {$mesFin}";
                }
                
                $entregasProcesadas->push([
                    'id' => $entrega->id,
                    'fecha_obj' => $fecha,
                    'fecha_formateada' => $fecha->format('d/m/Y'),
                    'recibo_info' => $recibo->numero_recibo . " \n" . $recibo->cliente,
                    'descripcion' => mb_strtoupper($recibo->descripcion_prenda),
                    'talla' => $talla,
                    'cantidad' => $cantidad,
                    'grupo' => $grupoSemana,
                    'orden_semana' => $startOfWeek->format('Ymd') // usar inicio de semana para ordenar
                ]);
                
                $totalGeneral += $cantidad;
            }
        }
        
        // Agrupar por semana
        $entregasAgrupadas = $entregasProcesadas->sortByDesc('fecha_obj')->groupBy('grupo');

        return view('admin.talleres.entregas', compact('taller', 'recibo', 'entregasAgrupadas', 'totalGeneral'));
    }

    // API endpoints para SPA
    public function apiRecibos($id)
    {
        $taller = \App\Models\User::findOrFail($id);
        $nombreTaller = $taller->name;

        // 1. Recibos normales
        $recibosNormales = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos as crp')
            ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
            ->join('pedidos_produccion as ppro', 'crp.pedido_produccion_id', '=', 'ppro.id')
            ->join('clientes', 'ppro.cliente_id', '=', 'clientes.id')
            ->leftJoin('procesos_prenda as ppren', function($join) {
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
                'pp.nombre_prenda',
                'pp.descripcion as descripcion_prenda',
                'clientes.nombre as cliente', 
                'crp.tipo_recibo',
                \Illuminate\Support\Facades\DB::raw('0 as es_parcial')
            )
            ->get();

        // 2. Recibos parciales
        $recibosParciales = \Illuminate\Support\Facades\DB::table('recibo_por_partes as rpp')
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
                \Illuminate\Support\Facades\DB::raw('1 as es_parcial')
            )
            ->get();

        $recibos = $recibosNormales->concat($recibosParciales)->map(function($r) {
            $r->numero_recibo = (int)$r->numero_recibo;
            return $r;
        });

        // Calcular completados filtrando por nombre de operario y área
        $idsRecibosNormales = $recibosNormales->pluck('id')->toArray();
        $idsRecibosParciales = $recibosParciales->pluck('id')->toArray();

        $completadosNormales = 0;
        if (!empty($idsRecibosNormales)) {
            $completadosNormales = \Illuminate\Support\Facades\DB::table('prenda_recibo_completado')
                ->whereIn('id_recibo', $idsRecibosNormales)
                ->where('nombre_operario', $nombreTaller)
                ->where('area', 'Costura')
                ->distinct('id_recibo')
                ->count('id_recibo');
        }

        $completadosParciales = 0;
        if (!empty($idsRecibosParciales)) {
            $completadosParciales = \Illuminate\Support\Facades\DB::table('prenda_recibo_completado')
                ->whereIn('id_parcial', $idsRecibosParciales)
                ->where('nombre_operario', $nombreTaller)
                ->where('area', 'Costura')
                ->distinct('id_parcial')
                ->count('id_parcial');
        }

        $totalCompletados = $completadosNormales + $completadosParciales;
        $totalPendientes = $recibos->count() - $totalCompletados;

        return response()->json([
            'taller_id' => $id,
            'taller_name' => $nombreTaller,
            'recibos' => $recibos,
            'total' => $recibos->count(),
            'completados' => $totalCompletados,
            'pendientes' => $totalPendientes
        ]);
    }

    public function apiEntregas($taller_id, $recibo_id, $es_parcial)
    {
        $isParcial = $es_parcial == '1';
        
        if ($isParcial) {
            $recibo = \Illuminate\Support\Facades\DB::table('recibo_por_partes as rpp')
                ->join('prendas_pedido as pp', 'rpp.prenda_pedido_id', '=', 'pp.id')
                ->join('pedidos_produccion as ppro', 'rpp.pedido_produccion_id', '=', 'ppro.id')
                ->join('clientes', 'ppro.cliente_id', '=', 'clientes.id')
                ->where('rpp.id', $recibo_id)
                ->select(
                    'rpp.id',
                    'rpp.consecutivo_parcial as numero_recibo',
                    'pp.nombre_prenda',
                    'pp.descripcion as descripcion_prenda',
                    'clientes.nombre as cliente'
                )
                ->first();
                
            $entregasRaw = \App\Models\EntregaReciboCostura::where('recibo_parcial_id', $recibo_id)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $recibo = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos as crp')
                ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
                ->join('pedidos_produccion as ppro', 'crp.pedido_produccion_id', '=', 'ppro.id')
                ->join('clientes', 'ppro.cliente_id', '=', 'clientes.id')
                ->where('crp.id', $recibo_id)
                ->select(
                    'crp.id',
                    'crp.consecutivo_actual as numero_recibo',
                    'pp.nombre_prenda',
                    'pp.descripcion as descripcion_prenda',
                    'clientes.nombre as cliente'
                )
                ->first();
                
            $entregasRaw = \App\Models\EntregaReciboCostura::where('consecutivo_recibo_id', $recibo_id)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        if (!$recibo) {
            return response()->json(['error' => 'Recibo no encontrado'], 404);
        }

        $recibo->numero_recibo = (int)$recibo->numero_recibo;

        $entregasProcesadas = collect();
        $totalGeneral = 0;
        
        \Carbon\Carbon::setLocale('es');

        foreach ($entregasRaw as $entrega) {
            $detalleTallas = is_string($entrega->detalle_tallas) ? json_decode($entrega->detalle_tallas, true) : $entrega->detalle_tallas;
            
            if (!$detalleTallas || !is_array($detalleTallas)) continue;
            
            foreach ($detalleTallas as $talla => $cantidad) {
                if ($cantidad <= 0) continue;
                
                $fecha = \Carbon\Carbon::parse($entrega->created_at);
                
                if ($fecha->isSunday()) {
                    continue;
                }
                
                $startOfWeek = $fecha->copy()->startOfWeek(\Carbon\Carbon::SATURDAY);
                $endOfWeek = $fecha->copy()->endOfWeek(\Carbon\Carbon::FRIDAY);
                
                $diaInicio = $startOfWeek->format('d');
                $mesInicio = mb_strtoupper($startOfWeek->translatedFormat('F'));
                $diaFin = $endOfWeek->format('d');
                $mesFin = mb_strtoupper($endOfWeek->translatedFormat('F'));
                
                if ($mesInicio == $mesFin) {
                    $grupoSemana = "SEMANA DEL {$diaInicio} AL {$diaFin} DE {$mesInicio}";
                } else {
                    $grupoSemana = "SEMANA DEL {$diaInicio} DE {$mesInicio} AL {$diaFin} DE {$mesFin}";
                }
                
                $entregasProcesadas->push([
                    'id' => $entrega->id,
                    'fecha_obj' => $fecha,
                    'fecha_formateada' => $fecha->format('d/m/Y'),
                    'descripcion' => mb_strtoupper($recibo->descripcion_prenda),
                    'talla' => $talla,
                    'cantidad' => $cantidad,
                    'grupo' => $grupoSemana,
                    'orden_semana' => $startOfWeek->format('Ymd')
                ]);
                
                $totalGeneral += $cantidad;
            }
        }
        
        $entregasAgrupadas = $entregasProcesadas->sortByDesc('fecha_obj')->groupBy('grupo');

        // Transformar a array para JSON
        $entregasFormateadas = $entregasAgrupadas->map(function($grupo) {
            return $grupo->map(function($item) {
                unset($item['fecha_obj']);
                return $item;
            })->values();
        })->values();

        return response()->json([
            'recibo' => $recibo,
            'entregas' => $entregasFormateadas,
            'total' => $totalGeneral
        ]);
    }

    public function toggleStatus($id)
    {
        try {
            $user = \App\Models\User::findOrFail($id);
            
            // Buscar si ya existe configuración
            $config = \Illuminate\Support\Facades\DB::table('taller_config')
                ->where('user_id', $id)
                ->first();
            
            if ($config) {
                $nuevoEstado = $config->activo ? 0 : 1;
                \Illuminate\Support\Facades\DB::table('taller_config')
                    ->where('user_id', $id)
                    ->update([
                        'activo' => $nuevoEstado,
                        'updated_at' => now()
                    ]);
            } else {
                // Si no existe, lo creamos como desactivado (0) 
                // ya que si entró aquí es porque el default IFNULL(activo, 1) lo mostraba activo
                $nuevoEstado = 0;
                \Illuminate\Support\Facades\DB::table('taller_config')->insert([
                    'user_id' => $id,
                    'activo' => $nuevoEstado,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'activo' => (bool)$nuevoEstado,
                'message' => $nuevoEstado ? 'Taller activado correctamente' : 'Taller desactivado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $e->getMessage()
            ], 500);
        }
    }
}
