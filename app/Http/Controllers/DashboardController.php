<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\News;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show the dashboard view.
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Obtener el nombre del rol del usuario (primer rol en roles_ids)
        $userRoles = $user->roles();
        
        if ($userRoles && $userRoles->count() > 0) {
            $roleName = $userRoles->first()->name;

            if ($roleName === 'asesor') {
                return redirect()->route('asesores.dashboard');
            }

            if ($roleName === 'bodeguero') {
                return redirect()->route('bodega.pedidos');
            }

            if ($roleName === 'insumos') {
                return redirect()->route('insumos.materiales.index');
            }

            if ($roleName === 'supervisor_pedidos') {
                return redirect()->route('supervisor-pedidos.index');
            }

            if ($roleName === 'contador') {
                return redirect()->route('contador.index');
            }

            if ($roleName === 'supervisor' || $roleName === 'supervisor_planta') {
                return redirect()->route('registros.index');
            }

            if ($roleName === 'aprobador_cotizaciones') {
                return redirect()->route('cotizaciones.pendientes');
            }

            if ($roleName === 'cortador' || $roleName === 'costurero') {
                return redirect()->route('operario.dashboard');
            }

            if ($roleName === 'visualizador_cotizaciones_logo') {
                return redirect()->route('visualizador-logo.dashboard');
            }

            if ($roleName === 'cartera') {
                return redirect()->route('cartera.pedidos');
            }
        }

        return view('dashboard');
    }

    public function getKPIs()
    {
        $totalOrders = DB::table('pedidos_produccion')->count();
        $ordersByStatus = DB::table('pedidos_produccion')
            ->select('estado', DB::raw('count(*) as count'))
            ->groupBy('estado')
            ->get();
        $ordersByArea = DB::table('pedidos_produccion')
            ->select('area', DB::raw('count(*) as count'))
            ->groupBy('area')
            ->get();
        $recentDeliveries = DB::table('entregas_pedido_costura')
            ->select('pedido', 'cantidad_entregada', 'fecha_entrega', 'costurero')
            ->orderBy('fecha_entrega', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_orders' => $totalOrders,
            'orders_by_status' => $ordersByStatus,
            'orders_by_area' => $ordersByArea,
            'recent_deliveries' => $recentDeliveries
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
        $limit = $request->input('limit', 50); // Límite configurable
        
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
     * Obtener estadísticas de auditoría
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
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        
        News::whereDate('created_at', $date)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones han sido marcadas como leídas'
        ]);
    }

    /**
     * Obtener notificaciones de entregas y registros en tableros para el admin
     */
    public function getAdminNotifications(Request $request)
    {
        $limit = $request->input('limit', 20);
        $days = $request->input('days', 1); // Últimos N días
        
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
     * Obtener icono según el tipo de notificación
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
     * Obtener color según el tipo de notificación
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
     * Obtener título legible según el tipo de notificación
     */
    private function getNotificationTitle($tableName)
    {
        $titles = [
            'entrega_pedido_costura' => ' Entrega Costura - Pedido',
            'entrega_pedido_corte' => ' Entrega Corte - Pedido',
            'entrega_bodega_costura' => ' Entrega Costura - Bodega',
            'entrega_bodega_corte' => ' Entrega Corte - Bodega',
            'registro_piso_produccion' => ' Registro - Producción',
            'registro_piso_corte' => ' Registro - Corte',
            'registro_piso_polo' => ' Registro - Polos',
        ];
        
        return $titles[$tableName] ?? 'Notificación del Sistema';
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
        $tipo = $request->input('tipo', 'pedido'); // Default to 'pedido'
        $year = $request->input('year');
        $month = $request->input('month');
        $week = $request->input('week');
        $day = $request->input('day');

        $table = $tipo === 'bodega' ? 'entregas_bodega_costura' : 'entregas_pedido_costura';
        // Nombre de columna diferente según la tabla
        $fechaColumn = $tipo === 'bodega' ? 'fecha_entrega' : 'fecha_entrega';

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
                // Filter by week number of the year
                $query->whereRaw("WEEK({$fechaColumn}, 1) = ?", [$week]);
            }
        }

        $query->groupBy('costurero');

        $data = $query->get();

        return response()->json($data);
    }

    /**
     * Get aggregated delivery data for entrega_pedido_corte or entrega_bodega_corte grouped by cortador and etiquetador.
     * Calculates etiquetadas as piezas * pasadas.
     * Supports filtering by year, month, week, and day.
     */
    public function getEntregasCorteData(Request $request)
    {
        $tipo = $request->input('tipo', 'pedido'); // Default to 'pedido'
        $year = $request->input('year');
        $month = $request->input('month');
        $week = $request->input('week');
        $day = $request->input('day');

        $table = $tipo === 'bodega' ? 'entrega_bodega_corte' : 'entrega_pedido_corte';

        $query = DB::table($table)
            ->select('cortador', 'etiquetador', DB::raw('SUM(piezas) as total_piezas'), DB::raw('SUM(pasadas) as total_pasadas'), DB::raw('SUM(piezas * pasadas) as total_etiquetadas'));

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
                // Filter by week number of the year
                $query->whereRaw('WEEK(fecha_entrega, 1) = ?', [$week]);
            }
        }

        $query->groupBy('cortador', 'etiquetador');

        $data = $query->get();

        return response()->json($data);
    }
}
