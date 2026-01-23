<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * DashboardService
 * 
 * Servicio para obtener datos del dashboard del asesor.
 * Encapsula la lÃ³gica de estadÃ­sticas y grÃ¡ficas.
 */
class DashboardService
{
    /**
     * Obtener estadÃ­sticas generales del dashboard
     */
    public function obtenerEstadisticas(): array
    {
        $userId = Auth::id();

        return [
            'pedidos_dia' => PedidoProduccion::where('asesor_id', $userId)
                ->whereDate('created_at', today())->count(),
            'pedidos_mes' => PedidoProduccion::where('asesor_id', $userId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
            'pedidos_anio' => PedidoProduccion::where('asesor_id', $userId)
                ->whereYear('created_at', now()->year)->count(),
            'pedidos_pendientes' => PedidoProduccion::where('asesor_id', $userId)
                ->whereIn('estado', ['No iniciado', 'En EjecuciÃ³n'])
                ->count(),
        ];
    }

    /**
     * Obtener datos para grÃ¡ficas del dashboard
     */
    public function obtenerDatosGraficas(int $dias = 30): array
    {
        $userId = Auth::id();

        // Datos para grÃ¡fica de pedidos por dÃ­a
        $pedidosUltimos30Dias = PedidoProduccion::where('asesor_id', $userId)
            ->select(DB::raw('DATE(created_at) as fecha'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', now()->subDays($dias))
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        // Datos para grÃ¡fica de pedidos por asesor (comparativa - todos los asesores)
        $pedidosPorAsesor = PedidoProduccion::select('asesor_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('asesor_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('asesor_id')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->with('asesora')
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->asesora ? $item->asesora->name : 'Desconocido',
                    'total' => $item->total
                ];
            });

        // Datos para grÃ¡fica de estados
        $pedidosPorEstado = PedidoProduccion::where('asesor_id', $userId)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->whereNotNull('estado')
            ->groupBy('estado')
            ->get();

        // Tendencia semanal
        $semanaActual = PedidoProduccion::where('asesor_id', $userId)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        
        $semanaAnterior = PedidoProduccion::where('asesor_id', $userId)
            ->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->count();

        $tendencia = $semanaAnterior > 0 
            ? (($semanaActual - $semanaAnterior) / $semanaAnterior) * 100 
            : 0;

        return [
            'ordenes_ultimos_30_dias' => $pedidosUltimos30Dias,
            'ordenes_por_asesor' => $pedidosPorAsesor,
            'ordenes_por_estado' => $pedidosPorEstado,
            'tendencia' => round($tendencia, 2),
            'semana_actual' => $semanaActual,
            'semana_anterior' => $semanaAnterior,
        ];
    }
}

