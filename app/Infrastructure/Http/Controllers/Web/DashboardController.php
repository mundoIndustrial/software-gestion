<?php

namespace App\Infrastructure\Http\Controllers\Web;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\News;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    /**
     * Show the dashboard view.
     */
    public function index()
    {
        \Log::info(' DashboardController.index() INICIADO');
        
        $user = Auth::user();

        if (!$user) {
            \Log::warning(' DashboardController: Usuario no autenticado');
            return redirect()->route('login');
        }

        if ($user->hasRole('visualizador_recibos_logo')) {
            \Log::info(' Redirigiendo a registros.recibos-bordado-estampado');
            return redirect()->route('registros.recibos-bordado-estampado');
        }

        \Log::info(' Usuario autenticado', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'roles_ids' => json_encode($user->roles_ids ?? [])
        ]);

        // Obtener el nombre del rol del usuario (primer rol en roles_ids)
        $userRoles = $user->roles();
        
        \Log::info(' Obteniendo roles del usuario', [
            'total_roles' => $userRoles ? $userRoles->count() : 0
        ]);

        if ($userRoles && $userRoles->count() > 0) {
            $roleName = $userRoles->first()->name;
            
            \Log::info(' Rol primario del usuario', [
                'role' => $roleName,
                'all_roles' => $userRoles->pluck('name')->toArray()
            ]);

            if ($roleName === 'asesor') {
                \Log::info(' Redirigiendo a asesores.dashboard');
                return redirect()->route('asesores.dashboard');
            }

            if ($roleName === 'bodeguero') {
                \Log::info(' Redirigiendo a gestion-bodega.pedidos');
                return redirect()->route('gestion-bodega.pedidos');
            }

            if ($roleName === 'insumos') {
                \Log::info(' ðŸŽ¯ PREPARANDO redirect a insumos.materiales.index');
                try {
                    $redirectUrl = route('insumos.materiales.index');
                    \Log::info(' URL generada correctamente', ['url' => $redirectUrl]);
                } catch (\Exception $e) {
                    \Log::error('ERROR generando URL para insumos.materiales.index', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
                return redirect()->route('insumos.materiales.index');
            }

            if ($roleName === 'supervisor_pedidos') {
                \Log::info(' Redirigiendo a supervisor-pedidos.index');
                return redirect()->route('supervisor-pedidos.index');
            }

            if ($roleName === 'contador') {
                \Log::info(' Redirigiendo a contador.index');
                return redirect()->route('contador.index');
            }

            if ($roleName === 'supervisor' || $roleName === 'supervisor_planta') {
                \Log::info(' Redirigiendo a registros.index');
                return redirect()->route('registros.index');
            }

            if ($roleName === 'aprobador_cotizaciones') {
                \Log::info(' Redirigiendo a cotizaciones.pendientes');
                return redirect()->route('cotizaciones.pendientes');
            }

            if ($roleName === 'cortador' || $roleName === 'costurero' || $roleName === 'vista-costura') {
                \Log::info(' Redirigiendo a operario.dashboard');
                return redirect()->route('operario.dashboard');
            }

            if ($roleName === 'lider-control-calidad' || $roleName === 'control de calidad') {
                \Log::info(' Redirigiendo a control-calidad.dashboard');
                return redirect()->route('control-calidad.dashboard');
            }

            if ($roleName === 'visualizador_cotizaciones_logo') {
                \Log::info(' Redirigiendo a visualizador-logo.dashboard');
                return redirect()->route('visualizador-logo.dashboard');
            }

            if ($roleName === 'supervisor_gerencia') {
                \Log::info(' Redirigiendo a registros.recibos-costura');
                return redirect()->route('registros.recibos-costura');
            }

            if ($roleName === 'administrador-costura') {
                \Log::info(' Redirigiendo a operario.dashboard');
                return redirect()->route('operario.dashboard', ['todas' => 1, 'tab' => 'costura']);
            }

            if ($roleName === 'cartera') {
                \Log::info(' Redirigiendo a cartera.pedidos');
                return redirect()->route('cartera.pedidos');
            }

            if ($roleName === 'Despacho' || $roleName === 'despacho') {
                \Log::info(' Redirigiendo a despacho.index');
                return redirect()->route('despacho.index');
            }

            if ($roleName === 'visualizador_plooter') {
                \Log::info(' Redirigiendo a insumos.plooter.index');
                return redirect()->route('insumos.plooter.index');
            }

            if ($roleName === 'recepcion_despacho') {
                \Log::info(' Redirigiendo a recepcion-despacho.index');
                return redirect()->route('recepcion-despacho.index');
            }

            \Log::warning(' Rol no mapeado a ninguna ruta', ['role' => $roleName]);
        } else {
            \Log::warning(' Usuario sin roles asignados');
        }

        \Log::info(' Mostrando vista de dashboard generica');
        return view('dashboard');
    }

    public function getKPIs()
    {
        try {
            $totalOrders = DB::table('pedidos_produccion')->count();
            $ordersByStatus = DB::table('pedidos_produccion')
                ->select('estado', DB::raw('count(*) as count'))
                ->groupBy('estado')
                ->get();
            $ordersByArea = DB::table('pedidos_produccion')
                ->select('area', DB::raw('count(*) as count'))
                ->groupBy('area')
                ->get();

            $recentDeliveries = collect();
            try {
                $recentDeliveries = DB::table('entregas_pedido_costura')
                    ->select('pedido', 'cantidad_entregada', 'fecha_entrega', 'costurero')
                    ->orderBy('fecha_entrega', 'desc')
                    ->limit(5)
                    ->get();
            } catch (\Throwable $e) {
                Log::warning('[DashboardController] entregas_pedido_costura table not found', [
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'total_orders' => $totalOrders,
                'orders_by_status' => $ordersByStatus,
                'orders_by_area' => $ordersByArea,
                'recent_deliveries' => $recentDeliveries
            ]);
        } catch (\Throwable $e) {
            Log::error('[DashboardController] getKPIs error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error retrieving KPIs',
                'total_orders' => 0,
                'orders_by_status' => [],
                'orders_by_area' => [],
                'recent_deliveries' => []
            ], 500);
        }
    }

    public function reporteSeguimiento(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'html'));
        if (!in_array($format, ['csv', 'pdf', 'html'], true)) {
            $format = 'html';
        }

        $rows = DB::table('pedidos_produccion as p')
            ->leftJoin('consecutivos_recibos_pedidos as crp', function ($join) {
                $join->on('crp.pedido_produccion_id', '=', 'p.id')
                    ->where('crp.activo', 1)
                    ->whereIn('crp.tipo_recibo', ['COSTURA', 'REFLECTIVO']);
            })
            ->leftJoin('users as asesor', 'asesor.id', '=', 'p.asesor_id')
            ->select([
                'p.id as pedido_id',
                'p.numero_pedido',
                'p.cliente',
                'asesor.name as asesor_nombre',
                'p.created_at as pedido_creado_en',
                'p.dia_de_entrega',
                'p.fecha_estimada_de_entrega',
                'p.aprobado_por_cartera_en',
                'p.aprobado_por_supervisor_en',
                'crp.id as recibo_id',
                'crp.prenda_id as recibo_prenda_id',
                'crp.consecutivo_actual as numero_recibo',
                'crp.tipo_recibo',
                'crp.aprobado_insumos_en as recibo_aprobado_insumos_en',
                'crp.created_at as recibo_creado_en',
            ])
            ->orderByDesc('p.created_at')
            ->get();

        $pedidoNumbers = $rows->pluck('numero_pedido')->filter()->unique()->values();
        $procesos = collect();
        if ($pedidoNumbers->isNotEmpty()) {
            $procesos = DB::table('procesos_prenda')
                ->whereIn('numero_pedido', $pedidoNumbers->all())
                ->whereNull('deleted_at')
                ->orderBy('numero_pedido')
                ->orderBy('prenda_pedido_id')
                ->orderBy('created_at')
                ->select([
                    'numero_pedido',
                    'prenda_pedido_id',
                    'numero_recibo',
                    'numero_recibo_parcial',
                    'proceso',
                    'fecha_inicio',
                    'fecha_fin',
                    'dias_duracion',
                    'estado_proceso',
                    'created_at',
                ])
                ->get();
        }

        $procesosPorLlave = [];
        foreach ($procesos as $proceso) {
            $key = $this->buildProcesoKey($proceso->numero_pedido, $proceso->prenda_pedido_id, $proceso->numero_recibo);
            $duracion = $proceso->dias_duracion ?: $this->formatDuration($proceso->fecha_inicio, $proceso->fecha_fin);
            $segmento = sprintf(
                '%s: %s -> %s (%s)',
                (string) ($proceso->proceso ?? 'Proceso'),
                $this->formatDateTime($proceso->fecha_inicio),
                $this->formatDateTime($proceso->fecha_fin),
                $duracion
            );
            $procesosPorLlave[$key][] = $segmento;
        }

        $reportRows = [];
        foreach ($rows as $row) {
            $procesoKey = $this->buildProcesoKey($row->numero_pedido, $row->recibo_prenda_id, $row->numero_recibo);
            $procesosResumen = isset($procesosPorLlave[$procesoKey])
                ? implode(' | ', $procesosPorLlave[$procesoKey])
                : '-';

            $reportRows[] = [
                'pedido_id' => $row->pedido_id,
                'numero_pedido' => $row->numero_pedido,
                'cliente' => $row->cliente,
                'asesor_nombre' => $row->asesor_nombre,
                'numero_recibo' => $row->numero_recibo,
                'tipo_recibo' => $row->tipo_recibo,
                'dia_de_entrega' => $row->dia_de_entrega,
                'fecha_estimada_de_entrega' => $this->formatDateTime($row->fecha_estimada_de_entrega),
                'pedido_creado_en' => $this->formatDateTime($row->pedido_creado_en),
                'aprobado_por_cartera_en' => $this->formatDateTime($row->aprobado_por_cartera_en),
                'demora_creacion_cartera' => $this->formatDuration($row->pedido_creado_en, $row->aprobado_por_cartera_en),
                'aprobado_por_supervisor_en' => $this->formatDateTime($row->aprobado_por_supervisor_en),
                'demora_cartera_supervisor' => $this->formatDuration($row->aprobado_por_cartera_en, $row->aprobado_por_supervisor_en),
                'recibo_aprobado_insumos_en' => $this->formatDateTime($row->recibo_aprobado_insumos_en),
                'demora_supervisor_insumos' => $this->formatDuration($row->aprobado_por_supervisor_en, $row->recibo_aprobado_insumos_en),
                'recibo_creado_en' => $this->formatDateTime($row->recibo_creado_en),
                'demora_creacion_recibo' => $this->formatDuration($row->pedido_creado_en, $row->recibo_creado_en),
                'procesos_resumen' => $procesosResumen,
            ];
        }

        if ($format === 'pdf') {
            try {
                @ini_set('memory_limit', '1024M');
                @set_time_limit(120);

                $filename = 'reporte_seguimiento_dashboard_' . now()->format('Ymd_His') . '.pdf';

                // El PDF puede fallar con datasets grandes; limitamos y compactamos contenido pesado.
                $pdfRows = collect($reportRows)
                    ->take(180)
                    ->map(function (array $row) {
                        $row['procesos_resumen'] = mb_strimwidth((string) ($row['procesos_resumen'] ?? '-'), 0, 220, '...');
                        return $row;
                    })
                    ->values()
                    ->all();

                Log::info('[DashboardReporteSeguimiento] Generando PDF', [
                    'total_rows' => count($reportRows),
                    'rows_en_pdf' => count($pdfRows),
                ]);

                $pdf = Pdf::loadView('dashboard.reporte-seguimiento-pdf', [
                    'rows' => $pdfRows,
                    'fechaGeneracion' => now(),
                ])->setPaper('a4', 'landscape');

                return $pdf->download($filename);
            } catch (\Throwable $e) {
                Log::error('[DashboardReporteSeguimiento] Error generando PDF', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response('No se pudo generar el PDF del reporte.', 500)
                    ->header('Content-Type', 'text/plain; charset=UTF-8');
            }
        }

        if ($format === 'html') {
            return view('dashboard.reporte-seguimiento-view', [
                'rows' => $reportRows,
                'fechaGeneracion' => now()
            ]);
        }

        $filename = 'reporte_seguimiento_dashboard_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($reportRows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Pedido ID',
                'Numero Pedido',
                'Cliente',
                'Asesor',
                'Recibo',
                'Tipo Recibo',
                'Dia Entrega',
                'Fecha Estimada Entrega',
                'Fecha Creacion Pedido',
                'Fecha Aprobacion Cartera',
                'Demora Creacion -> Cartera',
                'Fecha Aprobacion Supervisor',
                'Demora Cartera -> Supervisor',
                'Fecha Aprobacion Insumos/Corte (Recibo)',
                'Demora Supervisor -> Insumos/Corte',
                'Fecha Creacion Recibo',
                'Demora Creacion Pedido -> Creacion Recibo',
                'Tiempos por Proceso (Seguimiento)',
            ]);

            foreach ($reportRows as $row) {
                fputcsv($out, [
                    $row['pedido_id'],
                    $row['numero_pedido'],
                    $row['cliente'],
                    $row['asesor_nombre'],
                    $row['numero_recibo'],
                    $row['tipo_recibo'],
                    $row['dia_de_entrega'],
                    $row['fecha_estimada_de_entrega'],
                    $row['pedido_creado_en'],
                    $row['aprobado_por_cartera_en'],
                    $row['demora_creacion_cartera'],
                    $row['aprobado_por_supervisor_en'],
                    $row['demora_cartera_supervisor'],
                    $row['recibo_aprobado_insumos_en'],
                    $row['demora_supervisor_insumos'],
                    $row['recibo_creado_en'],
                    $row['demora_creacion_recibo'],
                    $row['procesos_resumen'],
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function formatDateTime($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return '-';
        }
    }

    private function formatDuration($start, $end): string
    {
        if (empty($start) || empty($end)) {
            return '-';
        }

        try {
            $s = Carbon::parse($start);
            $e = Carbon::parse($end);
            if ($e->lt($s)) {
                return '-';
            }
            $minutes = $s->diffInMinutes($e);
            $days = intdiv($minutes, 1440);
            $hours = intdiv($minutes % 1440, 60);
            $mins = $minutes % 60;
            return trim(($days > 0 ? "{$days}d " : '') . ($hours > 0 ? "{$hours}h " : '') . "{$mins}m");
        } catch (\Throwable $e) {
            return '-';
        }
    }

    private function buildProcesoKey($numeroPedido, $prendaId, $numeroRecibo): string
    {
        return implode('|', [
            (string) $numeroPedido,
            (string) ($prendaId ?? ''),
            (string) ($numeroRecibo ?? ''),
        ]);
    }

    public function getRecentOrders()
    {
        $recentOrders = DB::table('pedidos_produccion')
            ->select('numero_pedido', 'cliente', 'estado', 'area', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json($recentOrders);
    }

    public function getNews(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $table = $request->input('table'); // Filtro opcional por tabla
        $eventType = $request->input('event_type'); // Filtro opcional por tipo de evento
        $limit = $request->input('limit', 50); // limite configurable
        
        $query = News::with('user')
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc');

        // Aplicar filtros opcionales
        if ($table) {
            $query->where('table_name', $table);
        }

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        $news = $query->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'event_type' => $item->event_type,
                    'table_name' => $item->table_name,
                    'record_id' => $item->record_id,
                    'description' => $item->description,
                    'created_at' => $item->created_at->format('d/m/Y H:i:s'),
                    'user' => $item->user ? $item->user->name : 'Sistema',
                    'pedido' => $item->pedido,
                    'metadata' => $item->metadata,
                    'is_read' => $item->read_at !== null,
                    'read_at' => $item->read_at ? $item->read_at->format('d/m/Y H:i:s') : null
                ];
            });

        // Obtener contadores
        $counts = [
            'total' => News::whereDate('created_at', $date)->count(),
            'unread' => News::whereDate('created_at', $date)->whereNull('read_at')->count(),
            'read' => News::whereDate('created_at', $date)->whereNotNull('read_at')->count(),
        ];

        return response()->json([
            'news' => $news,
            'counts' => $counts
        ]);
    }

    /**
     * Obtener estadisticas de auditoria
     */
    public function getAuditStats(Request $request)
    {
        $date = $request->input('date', now()->toDateString());

        $stats = [
            'total_events' => News::whereDate('created_at', $date)->count(),
            'by_type' => News::whereDate('created_at', $date)
                ->select('event_type', \DB::raw('count(*) as count'))
                ->groupBy('event_type')
                ->get(),
            'by_table' => News::whereDate('created_at', $date)
                ->select('table_name', \DB::raw('count(*) as count'))
                ->groupBy('table_name')
                ->orderBy('count', 'desc')
                ->get(),
            'by_user' => News::whereDate('created_at', $date)
                ->join('users', 'news.user_id', '=', 'users.id')
                ->select('users.name', \DB::raw('count(*) as count'))
                ->groupBy('users.name')
                ->orderBy('count', 'desc')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Marcar todas las notificaciones como leidas
     */
    public function markAllAsRead(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        
        News::whereDate('created_at', $date)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones han sido marcadas como leidas'
        ]);
    }

    /**
     * Obtener notificaciones de entregas y registros en tableros para el admin
     */
    public function getAdminNotifications(Request $request)
    {
        $limit = $request->input('limit', 20);
        $days = $request->input('days', 1); // ultimas N dias
        
        $startDate = now()->subDays($days)->startOfDay();
        
        // Notificaciones de entregas y registros en tableros
        $notificationTypes = [
            'entrega_pedido_costura',
            'entrega_pedido_corte',
            'entrega_bodega_costura',
            'entrega_bodega_corte',
            'registro_piso_produccion',
            'registro_piso_corte',
            'registro_piso_polo',
        ];
        
        $notifications = News::whereIn('table_name', $notificationTypes)
            ->where('created_at', '>=', $startDate)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $icon = $this->getNotificationIcon($item->table_name);
                $color = $this->getNotificationColor($item->table_name);
                
                return [
                    'id' => $item->id,
                    'title' => $this->getNotificationTitle($item->table_name),
                    'description' => $item->description,
                    'icon' => $icon,
                    'color' => $color,
                    'created_at' => $item->created_at->format('d/m/Y H:i:s'),
                    'time_ago' => $this->getTimeAgo($item->created_at),
                    'user' => $item->user ? $item->user->name : 'Sistema',
                    'pedido' => $item->pedido,
                    'is_read' => $item->read_at !== null,
                    'table_name' => $item->table_name
                ];
            });
        
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => News::whereIn('table_name', $notificationTypes)
                ->where('created_at', '>=', $startDate)
                ->whereNull('read_at')
                ->count(),
            'total_count' => $notifications->count()
        ]);
    }
    
    /**
     * Obtener icono segun el tipo de notificacion
     */
    private function getNotificationIcon($tableName)
    {
        $icons = [
            'entrega_pedido_costura' => 'local_shipping',
            'entrega_pedido_corte' => 'local_shipping',
            'entrega_bodega_costura' => 'warehouse',
            'entrega_bodega_corte' => 'warehouse',
            'registro_piso_produccion' => 'manufacturing',
            'registro_piso_corte' => 'content_cut',
            'registro_piso_polo' => 'checkroom',
        ];
        
        return $icons[$tableName] ?? 'notifications';
    }
    
    /**
     * Obtener color segun el tipo de notificacion
     */
    private function getNotificationColor($tableName)
    {
        $colors = [
            'entrega_pedido_costura' => '#10b981',
            'entrega_pedido_corte' => '#06b6d4',
            'entrega_bodega_costura' => '#f59e0b',
            'entrega_bodega_corte' => '#f59e0b',
            'registro_piso_produccion' => '#667eea',
            'registro_piso_corte' => '#1e40af',
            'registro_piso_polo' => '#ec4899',
        ];
        
        return $colors[$tableName] ?? '#6b7280';
    }
    
    /**
     * Obtener titulo legible segun el tipo de notificacion
     */
    private function getNotificationTitle($tableName)
    {
        $titles = [
            'entrega_pedido_costura' => ' Entrega Costura - Pedido',
            'entrega_pedido_corte' => ' Entrega Corte - Pedido',
            'entrega_bodega_costura' => ' Entrega Costura - Bodega',
            'entrega_bodega_corte' => ' Entrega Corte - Bodega',
            'registro_piso_produccion' => ' Registro - Produccion',
            'registro_piso_corte' => ' Registro - Corte',
            'registro_piso_polo' => ' Registro - Polos',
        ];
        
        return $titles[$tableName] ?? 'notificacion del Sistema';
    }
    
    /**
     * Calcular tiempo transcurrido
     */
    private function getTimeAgo($date)
    {
        $now = now();
        $diff = $now->diffInSeconds($date);
        
        if ($diff < 60) {
            return 'hace unos segundos';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return "hace $minutes " . ($minutes === 1 ? 'minuto' : 'minutos');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "hace $hours " . ($hours === 1 ? 'hora' : 'horas');
        } else {
            $days = floor($diff / 86400);
            return "hace $days " . ($days === 1 ? 'día' : 'días');
        }
    }

    /**
     * Get aggregated delivery data for entregas_pedido_costura or entregas_bodega_costura grouped by costurero.
     * Supports filtering by year, month, and week.
     */
        public function getEntregasCosturaData(Request $request)
    {
        try {
            $tipo = $request->input('tipo', 'pedido');
            $year = $request->input('year');
            $month = $request->input('month');
            $week = $request->input('week');
            $day = $request->input('day');

            $table = $tipo === 'bodega' ? 'entregas_bodega_costura' : 'entregas_pedido_costura';
            $fechaColumn = 'fecha_entrega';

            $query = DB::table($table)
                ->select('costurero', DB::raw('SUM(cantidad_entregada) as total_entregas'));

            if ($day) {
                $query->whereDate($fechaColumn, $day);
            } else {
                if ($year) {
                    $query->whereYear($fechaColumn, $year);
                }
                if ($month) {
                    $query->whereMonth($fechaColumn, $month);
                }
                if ($week) {
                    $query->whereRaw("WEEK({$fechaColumn}, 1) = ?", [$week]);
                }
            }

            $data = $query->groupBy('costurero')->get();
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::warning('[DashboardController] getEntregasCosturaData error', [
                'error' => $e->getMessage(),
                'tipo' => $request->input('tipo')
            ]);
            return response()->json([], 200);
        }
    }

    /**
     * Get aggregated delivery data for entrega_pedido_corte or entrega_bodega_corte grouped by cortador and etiquetador.
     * Calculates etiquetadas as piezas * pasadas.
     * Supports filtering by year, month, week, and day.
     */
    public function getEntregasCorteData(Request $request)
    {
        try {
            $tipo = $request->input('tipo', 'pedido');
            $year = $request->input('year');
            $month = $request->input('month');
            $week = $request->input('week');
            $day = $request->input('day');

            $table = $tipo === 'bodega' ? 'entrega_bodega_corte' : 'entrega_pedido_corte';

            $query = DB::table($table)
                ->select('cortador', 'etiquetador',
                    DB::raw('SUM(piezas) as total_piezas'),
                    DB::raw('SUM(pasadas) as total_pasadas'),
                    DB::raw('SUM(piezas * pasadas) as total_etiquetadas'));

            if ($day) {
                $query->whereDate('fecha_entrega', $day);
            } else {
                if ($year) {
                    $query->whereYear('fecha_entrega', $year);
                }
                if ($month) {
                    $query->whereMonth('fecha_entrega', $month);
                }
                if ($week) {
                    $query->whereRaw('WEEK(fecha_entrega, 1) = ?', [$week]);
                }
            }

            $data = $query->groupBy('cortador', 'etiquetador')->get();
            return response()->json($data);
        } catch (\Throwable $e) {
            Log::warning('[DashboardController] getEntregasCorteData error', [
                'error' => $e->getMessage(),
                'tipo' => $request->input('tipo')
            ]);
            return response()->json([], 200);
        }
    }

    /**
     * Show a timeline of active orders (estado != Entregado and != Anulada)
     */
    public function timelinePedidos(Request $request)
    {
        $searchCliente = trim((string) $request->input('search_cliente', ''));
        $searchPedido  = trim((string) $request->input('search_pedido', ''));

        $query = DB::table('pedidos_produccion as p')
            ->leftJoin('users as asesor', 'asesor.id', '=', 'p.asesor_id')
            ->leftJoin('users as cartera', 'cartera.id', '=', 'p.aprobado_por_usuario_cartera')
            ->whereNotIn('p.estado', ['Entregado', 'Anulada', 'Borrador'])
            ->whereRaw('LOWER(COALESCE(p.area, \'\')) != ?', ['despacho'])
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('prendas_pedido as pp')
                    ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.prenda_pedido_id', '=', 'pp.id')
                    ->whereColumn('pp.pedido_produccion_id', 'p.id')
                    ->whereNull('pp.deleted_at')
                    ->whereNull('ppd.deleted_at');
            })
            ->select([
                'p.id',
                'p.numero_pedido',
                'p.cliente',
                'p.estado',
                'p.area',
                'asesor.name as asesor_nombre',
                'cartera.name as cartera_nombre',
                'p.created_at',
                'p.aprobado_por_cartera_en',
                'p.aprobado_por_supervisor_en',
                'p.fecha_ultimo_proceso',
                'p.fecha_estimada_de_entrega',
            ])
            ->orderBy('p.created_at', 'desc');

        if ($searchCliente !== '') {
            $query->where('p.cliente', 'like', '%' . $searchCliente . '%');
        }

        if ($searchPedido !== '') {
            $query->where('p.numero_pedido', 'like', '%' . $searchPedido . '%');
        }

        $pedidos = $query->paginate(15)->withQueryString();

        $pedidoIds = $pedidos->pluck('id')->toArray();
        $numeroPedidos = $pedidos->pluck('numero_pedido')->toArray();
        $pedidosAssoc = $pedidos->keyBy('id');

        $recibos = DB::table('consecutivos_recibos_pedidos')
            ->whereIn('pedido_produccion_id', $pedidoIds)
            ->where('tipo_recibo', '!=', 'COSTURA-BODEGA')
            ->select([
                'id',
                'pedido_produccion_id',
                'prenda_id',
                'tipo_recibo',
                'consecutivo_actual',
                'estado',
                'area',
                'created_at',
                'aprobado_insumos_en',
                'fecha_envio',
                'fecha_llegada',
                'fecha_estimada_de_entrega',
                DB::raw('0 as is_parcial')
            ])
            ->get();

        $parciales = DB::table('pedidos_parciales')
            ->whereIn('pedido_produccion_id', $pedidoIds)
            ->where('tipo_recibo', '!=', 'COSTURA-BODEGA')
            ->whereNull('deleted_at')
            ->select([
                'id',
                'pedido_produccion_id',
                'prenda_pedido_id as prenda_id',
                'tipo_recibo',
                'consecutivo_actual',
                'estado',
                DB::raw('NULL as area'),
                'created_at',
                DB::raw('NULL as aprobado_insumos_en'),
                DB::raw('NULL as fecha_envio'),
                DB::raw('NULL as fecha_llegada'),
                DB::raw('NULL as fecha_estimada_de_entrega'),
                DB::raw('1 as is_parcial')
            ])
            ->get();

        $todosLosRecibos = $recibos->merge($parciales)->sortBy('created_at')->values();
        $prendaIds = $todosLosRecibos->pluck('prenda_id')->filter()->unique()->toArray();

        $procesosCostura = collect();
        if (!empty($numeroPedidos)) {
            $procesosCostura = DB::table('procesos_prenda')
                ->whereIn('numero_pedido', $numeroPedidos)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'asc')
                ->get()
                ->groupBy('numero_pedido');
        }

        $procesosLogo = collect();
        if (!empty($prendaIds)) {
            $procesosLogo = DB::table('prenda_areas_logo_pedido as palp')
                ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.id', '=', 'palp.proceso_prenda_detalle_id')
                ->join('tipos_procesos as tp', 'tp.id', '=', 'ppd.tipo_proceso_id')
                ->whereIn('palp.prenda_pedido_id', $prendaIds)
                ->orderBy('palp.created_at', 'asc')
                ->select('palp.*', 'tp.nombre as tipo_proceso_nombre')
                ->get()
                ->groupBy(function($item) {
                    return $item->prenda_pedido_id . '_' . strtoupper($item->tipo_proceso_nombre);
                });
        }

        foreach ($todosLosRecibos as $r) {
            $r->subprocesos = collect();
            $pedidoNumero = $pedidosAssoc[$r->pedido_produccion_id]->numero_pedido ?? null;

            if (in_array($r->tipo_recibo, ['COSTURA', 'REFLECTIVO'])) {
                $procesos = $procesosCostura->get($pedidoNumero, collect());
                
                $r->subprocesos = $procesos->filter(function($p) use ($r) {
                    if ($r->is_parcial) {
                        return !empty($p->numero_recibo_parcial) && (int)$p->numero_recibo_parcial == $r->consecutivo_actual;
                    } else {
                        // Si el recibo tiene prenda_id asignada, buscar los procesos específicos de esa prenda
                        if ($r->prenda_id && $p->prenda_pedido_id) {
                            return $p->prenda_pedido_id == $r->prenda_id;
                        }
                        // De lo contrario, buscar por número de recibo general del pedido
                        return empty($p->numero_recibo_parcial) && $p->numero_recibo == $r->consecutivo_actual;
                    }
                })->values();
                
            } else {
                if (!$r->prenda_id) continue;
                $key = $r->prenda_id . '_' . $r->tipo_recibo;
                $r->subprocesos = $procesosLogo->get($key, collect());
            }
        }

        $recibosAgrupados = $todosLosRecibos->groupBy('pedido_produccion_id');

        return view('dashboard.timeline-pedidos', [
            'pedidos'        => $pedidos,
            'recibos'        => $recibosAgrupados,
            'searchCliente'  => $searchCliente,
            'searchPedido'   => $searchPedido,
        ]);
    }
}
