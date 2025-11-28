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
        
        // Si el usuario es de insumos, redirigirlo a Control de Materiales
        if ($user && ($user->role === 'insumos' || (is_object($user->role) && $user->role->name === 'insumos'))) {
            return redirect()->route('insumos.materiales.index');
        }
        
        return view('dashboard');
    }

    public function getKPIs()
    {
        $totalOrders = DB::table('tabla_original')->count();
        $ordersByStatus = DB::table('tabla_original')
            ->select('estado', DB::raw('count(*) as count'))
            ->groupBy('estado')
            ->get();
        $ordersByArea = DB::table('tabla_original')
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
        $recentOrders = DB::table('tabla_original')
            ->select('pedido', 'cliente', 'estado', 'area', 'fecha_de_creacion_de_orden')
            ->orderBy('fecha_de_creacion_de_orden', 'desc')
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
