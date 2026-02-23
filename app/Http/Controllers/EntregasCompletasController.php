<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EntregasCompletasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Consulta SQL para obtener datos de entregas con paginación
            $sql = "
                SELECT 
                    p.numero_pedido,
                    p.cliente,
                    p.estado as estado_pedido,
                    MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END) as fecha_entrega_supervisor,
                    MAX(CASE WHEN pe.entregado = 1 THEN u_supervisor.name END) as nombre_supervisor_entrega,
                    MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END) as fecha_entrega_despacho,
                    MAX(CASE WHEN dp.entregado = 1 THEN u_despacho.name END) as nombre_despacho_entrega,
                    CASE 
                        WHEN MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END) IS NOT NULL AND 
                             MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END) IS NOT NULL
                        THEN TIMESTAMPDIFF(
                            DAY, 
                            MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END),
                            MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END)
                        )
                        ELSE NULL
                    END as dias_entre_entregas
                FROM pedidos_produccion p
                LEFT JOIN prenda_entregas pe ON p.id = pe.prenda_pedido_id
                LEFT JOIN despacho_parciales dp ON p.id = dp.pedido_id
                LEFT JOIN users u_supervisor ON pe.usuario_id = u_supervisor.id
                LEFT JOIN users u_despacho ON dp.usuario_id = u_despacho.id
                WHERE p.numero_pedido IS NOT NULL AND p.numero_pedido > 0
                GROUP BY p.id, p.numero_pedido, p.cliente, p.estado
                ORDER BY p.numero_pedido DESC
            ";
            
            // Paginación
            $perPage = 15;
            $page = request('page', 1);
            $offset = ($page - 1) * $perPage;
            
            // Consulta para obtener total
            $countSql = "
                SELECT COUNT(*) as total
                FROM pedidos_produccion p
                WHERE p.numero_pedido IS NOT NULL AND p.numero_pedido > 0
            ";
            
            $total = DB::select($countSql)[0]->total;
            
            // Agregar LIMIT y OFFSET
            $sql .= " LIMIT $perPage OFFSET $offset";
            
            $entregasData = DB::select($sql);
            
            // Convertir a paginador
            $entregas = new \Illuminate\Pagination\LengthAwarePaginator(
                collect($entregasData),
                $total,
                $perPage,
                $page,
                [
                    'path' => request()->url(),
                    'pageName' => 'page',
                    'query' => request()->query()
                ]
            );
            
            return view('entregas-completas.index', compact('entregas'));
            
        } catch (\Exception $e) {
            Log::error('Error en EntregasCompletasController@index: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar los datos de entregas: ' . $e->getMessage());
        }
    }
    
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }
    
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            // Consulta SQL para obtener datos del pedido
            $sql = "
                SELECT 
                    p.id as pedido_id,
                    p.numero_pedido,
                    p.numero_cotizacion,
                    p.cliente,
                    p.estado as estado_pedido,
                    p.fecha_de_creacion_de_orden,
                    p.fecha_estimada_de_entrega,
                    
                    -- Datos de entrega de supervisor a despacho (desde prenda_entregas)
                    MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END) as fecha_entrega_supervisor,
                    MAX(CASE WHEN pe.entregado = 1 THEN u_supervisor.name END) as nombre_supervisor_entrega,
                    MAX(CASE WHEN pe.entregado = 1 THEN u_supervisor.email END) as email_supervisor_entrega,
                    
                    -- Datos de entrega de despacho a asesor (desde despacho_parciales)
                    MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END) as fecha_entrega_despacho,
                    MAX(CASE WHEN dp.entregado = 1 THEN u_despacho.name END) as nombre_despacho_entrega,
                    MAX(CASE WHEN dp.entregado = 1 THEN u_despacho.email END) as email_despacho_entrega,
                    
                    -- Estado de entregas
                    CASE 
                        WHEN MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 1 AND 
                             MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 1 
                        THEN 'Completado'
                        WHEN MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 1 
                        THEN 'Pendiente Despacho'
                        WHEN MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 1 
                        THEN 'Pendiente Supervisor'
                        ELSE 'Pendiente Ambos'
                    END as estado_entrega_general,
                    
                    -- Tiempos
                    CASE 
                        WHEN MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END) IS NOT NULL AND 
                             MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END) IS NOT NULL
                        THEN TIMESTAMPDIFF(
                            HOUR, 
                            MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END),
                            MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END)
                        )
                        ELSE NULL
                    END as horas_entre_entregas

                FROM pedidos_produccion p
                LEFT JOIN prenda_entregas pe ON p.id = pe.prenda_pedido_id
                LEFT JOIN despacho_parciales dp ON p.id = dp.pedido_id
                LEFT JOIN users u_supervisor ON pe.usuario_id = u_supervisor.id
                LEFT JOIN users u_despacho ON dp.usuario_id = u_despacho.id
                WHERE p.id = ?
                GROUP BY p.id, p.numero_pedido, p.numero_cotizacion, p.cliente, p.estado, 
                         p.fecha_de_creacion_de_orden, p.fecha_estimada_de_entrega
            ";
            
            $entrega = DB::select($sql, [$id]);
            
            if (empty($entrega)) {
                return back()->with('error', 'Entrega no encontrada');
            }
            
            $entrega = (object) $entrega[0];
                
            // Obtener detalles adicionales de supervisor
            $detallesSupervisorSql = "
                SELECT 
                    pe.fecha_entrega,
                    pe.created_at,
                    u.name as nombre_usuario,
                    pp.nombre_prenda,
                    pp.cantidad
                FROM prenda_entregas pe
                JOIN prendas_pedido pp ON pe.prenda_pedido_id = pp.id
                JOIN users u ON pe.usuario_id = u.id
                WHERE pe.prenda_pedido_id = ? AND pe.entregado = 1
            ";
            
            $detallesSupervisor = DB::select($detallesSupervisorSql, [$id]);
                
            // Obtener detalles adicionales de despacho
            $detallesDespachoSql = "
                SELECT 
                    dp.fecha_entrega,
                    dp.fecha_despacho,
                    dp.tipo_item,
                    dp.item_id,
                    dp.talla_id,
                    dp.genero,
                    dp.pendiente_inicial,
                    dp.parcial_1,
                    dp.pendiente_1,
                    dp.parcial_2,
                    dp.pendiente_2,
                    dp.parcial_3,
                    dp.pendiente_3,
                    dp.observaciones,
                    u.name as nombre_usuario
                FROM despacho_parciales dp
                JOIN users u ON dp.usuario_id = u.id
                WHERE dp.pedido_id = ? AND dp.entregado = 1
            ";
            
            $detallesDespacho = DB::select($detallesDespachoSql, [$id]);
            
            return view('entregas-completas.show', compact(
                'entrega', 
                'detallesSupervisor', 
                'detallesDespacho'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error en EntregasCompletasController@show: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar los detalles de la entrega: ' . $e->getMessage());
        }
    }
    
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }
    
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    
    /**
     * Obtener estadísticas generales de entregas
     */
    private function getEstadisticas()
    {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_pedidos,
                    SUM(CASE 
                        WHEN (MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 1 AND 
                              MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 1) 
                        THEN 1 ELSE 0 END) as completados,
                    SUM(CASE 
                        WHEN (MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 1 AND 
                              MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 0) 
                        THEN 1 ELSE 0 END) as pendientes_despacho,
                    SUM(CASE 
                        WHEN (MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 0 AND 
                              MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 1) 
                        THEN 1 ELSE 0 END) as pendientes_supervisor,
                    SUM(CASE 
                        WHEN (MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 0 AND 
                              MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 0) 
                        THEN 1 ELSE 0 END) as pendientes_ambos,
                    SUM(CASE WHEN MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END) IS NOT NULL THEN 1 ELSE 0 END) as con_entrega_supervisor,
                    SUM(CASE WHEN MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END) IS NOT NULL THEN 1 ELSE 0 END) as con_entrega_despacho,
                    AVG(CASE 
                        WHEN MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END) IS NOT NULL AND 
                             MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END) IS NOT NULL
                        THEN TIMESTAMPDIFF(
                            HOUR, 
                            MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END),
                            MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END)
                        )
                        ELSE NULL
                    END) as promedio_horas_entre_entregas
                FROM pedidos_produccion p
                LEFT JOIN prenda_entregas pe ON p.id = pe.prenda_pedido_id
                LEFT JOIN despacho_parciales dp ON p.id = dp.pedido_id
                GROUP BY p.id
            ";
            
            $result = DB::select($sql);
            
            // Sumar todos los resultados
            $stats = (object) [
                'total_pedidos' => array_sum(array_column($result, 'total_pedidos')),
                'completados' => array_sum(array_column($result, 'completados')),
                'pendientes_despacho' => array_sum(array_column($result, 'pendientes_despacho')),
                'pendientes_supervisor' => array_sum(array_column($result, 'pendientes_supervisor')),
                'pendientes_ambos' => array_sum(array_column($result, 'pendientes_ambos')),
                'con_entrega_supervisor' => array_sum(array_column($result, 'con_entrega_supervisor')),
                'con_entrega_despacho' => array_sum(array_column($result, 'con_entrega_despacho')),
                'promedio_horas_entre_entregas' => 0
            ];
            
            // Calcular promedio de horas correctamente
            $horasValidas = array_filter(array_column($result, 'promedio_horas_entre_entregas'), function($value) {
                return $value !== null && $value > 0;
            });
            
            if (!empty($horasValidas)) {
                $stats->promedio_horas_entre_entregas = array_sum($horasValidas) / count($horasValidas);
            }
                
            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Error en getEstadisticas: ' . $e->getMessage());
            return (object) [
                'total_pedidos' => 0,
                'completados' => 0,
                'pendientes_despacho' => 0,
                'pendientes_supervisor' => 0,
                'pendientes_ambos' => 0,
                'con_entrega_supervisor' => 0,
                'con_entrega_despacho' => 0,
                'promedio_horas_entre_entregas' => 0
            ];
        }
    }
    
    /**
     * API endpoint para obtener datos en formato JSON
     */
    public function apiIndex(Request $request)
    {
        try {
            // Reutilizar la misma lógica que el método index pero para API
            $sql = "
                SELECT 
                    p.id as pedido_id,
                    p.numero_pedido,
                    p.numero_cotizacion,
                    p.cliente,
                    p.estado as estado_pedido,
                    p.fecha_de_creacion_de_orden,
                    p.fecha_estimada_de_entrega,
                    
                    -- Datos de entrega de supervisor a despacho (desde prenda_entregas)
                    MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END) as fecha_entrega_supervisor,
                    MAX(CASE WHEN pe.entregado = 1 THEN u_supervisor.name END) as nombre_supervisor_entrega,
                    MAX(CASE WHEN pe.entregado = 1 THEN u_supervisor.email END) as email_supervisor_entrega,
                    
                    -- Datos de entrega de despacho a asesor (desde despacho_parciales)
                    MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END) as fecha_entrega_despacho,
                    MAX(CASE WHEN dp.entregado = 1 THEN u_despacho.name END) as nombre_despacho_entrega,
                    MAX(CASE WHEN dp.entregado = 1 THEN u_despacho.email END) as email_despacho_entrega,
                    
                    -- Estado de entregas
                    CASE 
                        WHEN MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 1 AND 
                             MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 1 
                        THEN 'Completado'
                        WHEN MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 1 
                        THEN 'Pendiente Despacho'
                        WHEN MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 1 
                        THEN 'Pendiente Supervisor'
                        ELSE 'Pendiente Ambos'
                    END as estado_entrega_general,
                    
                    -- Tiempos
                    CASE 
                        WHEN MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END) IS NOT NULL AND 
                             MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END) IS NOT NULL
                        THEN TIMESTAMPDIFF(
                            HOUR, 
                            MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END),
                            MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END)
                        )
                        ELSE NULL
                    END as horas_entre_entregas

                FROM pedidos_produccion p
                LEFT JOIN prenda_entregas pe ON p.id = pe.prenda_pedido_id
                LEFT JOIN despacho_parciales dp ON p.id = dp.pedido_id
                LEFT JOIN users u_supervisor ON pe.usuario_id = u_supervisor.id
                LEFT JOIN users u_despacho ON dp.usuario_id = u_despacho.id
            ";
            
            // Construir cláusulas WHERE para filtros
            $whereConditions = [];
            $bindings = [];
            
            if ($request->filled('estado_entrega')) {
                $havingConditions = [];
                if ($request->estado_entrega == 'Completado') {
                    $havingConditions[] = "MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 1 AND MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 1";
                } elseif ($request->estado_entrega == 'Pendiente Despacho') {
                    $havingConditions[] = "MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 1 AND MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 0";
                } elseif ($request->estado_entrega == 'Pendiente Supervisor') {
                    $havingConditions[] = "MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 0 AND MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 1";
                } elseif ($request->estado_entrega == 'Pendiente Ambos') {
                    $havingConditions[] = "MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 0 AND MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 0";
                }
            }
            
            if ($request->filled('estado_pedido')) {
                $whereConditions[] = "p.estado = ?";
                $bindings[] = $request->estado_pedido;
            }
            
            if ($request->filled('cliente')) {
                $whereConditions[] = "p.cliente LIKE ?";
                $bindings[] = '%' . $request->cliente . '%';
            }
            
            if ($request->filled('numero_pedido')) {
                $whereConditions[] = "p.numero_pedido = ?";
                $bindings[] = $request->numero_pedido;
            }
            
            // Agregar cláusulas WHERE
            if (!empty($whereConditions)) {
                $sql .= " WHERE " . implode(' AND ', $whereConditions);
            }
            
            // Agregar GROUP BY
            $sql .= "
                GROUP BY p.id, p.numero_pedido, p.numero_cotizacion, p.cliente, p.estado, 
                         p.fecha_de_creacion_de_orden, p.fecha_estimada_de_entrega
            ";
            
            // Agregar cláusulas HAVING si es necesario
            if (!empty($havingConditions)) {
                $sql .= " HAVING " . implode(' AND ', $havingConditions);
            }
            
            // Ordenamiento
            $sortBy = $request->get('sort_by', 'fecha_de_creacion_de_orden');
            $sortOrder = $request->get('sort_order', 'desc');
            $sql .= " ORDER BY $sortBy $sortOrder";
            
            // Paginación
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;
            
            // Consulta para obtener total
            $countSql = str_replace(
                "SELECT 
                    p.id as pedido_id,
                    p.numero_pedido,
                    p.numero_cotizacion,
                    p.cliente,
                    p.estado as estado_pedido,
                    p.fecha_de_creacion_de_orden,
                    p.fecha_estimada_de_entrega,
                    
                    -- Datos de entrega de supervisor a despacho (desde prenda_entregas)
                    MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END) as fecha_entrega_supervisor,
                    MAX(CASE WHEN pe.entregado = 1 THEN u_supervisor.name END) as nombre_supervisor_entrega,
                    MAX(CASE WHEN pe.entregado = 1 THEN u_supervisor.email END) as email_supervisor_entrega,
                    
                    -- Datos de entrega de despacho a asesor (desde despacho_parciales)
                    MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END) as fecha_entrega_despacho,
                    MAX(CASE WHEN dp.entregado = 1 THEN u_despacho.name END) as nombre_despacho_entrega,
                    MAX(CASE WHEN dp.entregado = 1 THEN u_despacho.email END) as email_despacho_entrega,
                    
                    -- Estado de entregas
                    CASE 
                        WHEN MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 1 AND 
                             MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 1 
                        THEN 'Completado'
                        WHEN MAX(CASE WHEN pe.entregado = 1 THEN 1 ELSE 0 END) = 1 
                        THEN 'Pendiente Despacho'
                        WHEN MAX(CASE WHEN dp.entregado = 1 THEN 1 ELSE 0 END) = 1 
                        THEN 'Pendiente Supervisor'
                        ELSE 'Pendiente Ambos'
                    END as estado_entrega_general,
                    
                    -- Tiempos
                    CASE 
                        WHEN MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END) IS NOT NULL AND 
                             MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END) IS NOT NULL
                        THEN TIMESTAMPDIFF(
                            HOUR, 
                            MAX(CASE WHEN pe.entregado = 1 THEN pe.fecha_entrega END),
                            MAX(CASE WHEN dp.entregado = 1 THEN dp.fecha_entrega END)
                        )
                        ELSE NULL
                    END as horas_entre_entregas",
                "SELECT COUNT(*) as total",
                $sql
            );
            
            $countSql = str_replace("GROUP BY p.id, p.numero_pedido, p.numero_cotizacion, p.cliente, p.estado, p.fecha_de_creacion_de_orden, p.fecha_estimada_de_entrega", "", $countSql);
            $countSql = str_replace("ORDER BY $sortBy $sortOrder", "", $countSql);
            
            $total = DB::select($countSql, $bindings)[0]->total;
            
            // Agregar LIMIT y OFFSET
            $sql .= " LIMIT $perPage OFFSET $offset";
            
            // Ejecutar consulta
            $entregasData = DB::select($sql, $bindings);
            
            return response()->json([
                'success' => true,
                'data' => $entregasData,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en EntregasCompletasController@apiIndex: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los datos: ' . $e->getMessage()
            ], 500);
        }
    }
}
