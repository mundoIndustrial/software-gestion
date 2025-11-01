<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\News;

class DashboardController extends Controller
{
    /**
     * Show the dashboard view.
     */
    public function index()
    {
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
        $news = News::with('user')
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'event_type' => $item->event_type,
                    'description' => $item->description,
                    'created_at' => $item->created_at->format('d/m/Y H:i'),
                    'user' => $item->user ? $item->user->name : 'Sistema',
                    'pedido' => $item->pedido
                ];
            });

        return response()->json($news);
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
