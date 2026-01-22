<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VisualizadorLogoController extends Controller
{
    /**
     * Mostrar dashboard del visualizador de cotizaciones logo
     */
    public function dashboard()
    {
        return view('visualizador-logo.dashboard');
    }

    /**
     * Obtener cotizaciones tipo Logo (L) y Combinadas (PL)
     * Solo muestra las que tienen información de logo
     */
    public function getCotizaciones(Request $request)
    {
        \Log::info(' ===== INICIO getCotizaciones =====');
        
        $query = Cotizacion::with([
            'asesor',
            'logoCotizacion',
            'logoCotizacion.fotos'
        ])
        ->whereNotNull('numero_cotizacion') // Solo cotizaciones enviadas (no borradores)
        ->where('es_borrador', false);

        // Filtrar por tipo de cotización
        // Obtener IDs de tipos L (Logo) y PL (Combinada)
        $tipoLogoId = \App\Models\TipoCotizacion::where('codigo', 'L')->value('id');
        $tipoCombinada1Id = \App\Models\TipoCotizacion::where('codigo', 'PL')->value('id');
        $tipoCombinada2Id = \App\Models\TipoCotizacion::where('codigo', 'C')->value('id'); // Por si aún existe el código antiguo

        $tiposPermitidos = array_filter([$tipoLogoId, $tipoCombinada1Id, $tipoCombinada2Id]);
        
        \Log::info(' Tipos de cotización permitidos:', [
            'tipoLogoId' => $tipoLogoId,
            'tipoCombinada1Id' => $tipoCombinada1Id,
            'tipoCombinada2Id' => $tipoCombinada2Id,
            'tiposPermitidos' => $tiposPermitidos
        ]);

        if (!empty($tiposPermitidos)) {
            $query->whereIn('tipo_cotizacion_id', $tiposPermitidos);
        }

        // Filtros adicionales
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_cotizacion', 'like', "%{$search}%")
                  ->orWhere('cliente', 'like', "%{$search}%");
            });
            \Log::info('🔎 Filtro de búsqueda aplicado:', ['search' => $search]);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
            \Log::info(' Filtro de estado aplicado:', ['estado' => $request->estado]);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_envio', '>=', $request->fecha_desde);
            \Log::info('📅 Filtro fecha desde aplicado:', ['fecha_desde' => $request->fecha_desde]);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_envio', '<=', $request->fecha_hasta);
            \Log::info('📅 Filtro fecha hasta aplicado:', ['fecha_hasta' => $request->fecha_hasta]);
        }

        // Ordenar por más reciente
        $query->orderBy('created_at', 'desc');

        // Paginación
        $cotizaciones = $query->paginate(20);
        
        \Log::info(' Total de cotizaciones encontradas:', ['total' => $cotizaciones->total()]);
        
        // Log detallado de cada cotización
        foreach ($cotizaciones->items() as $index => $cot) {
            \Log::info(" Cotización #{$index}:", [
                'id' => $cot->id,
                'numero_cotizacion' => $cot->numero_cotizacion,
                'cliente_campo_texto' => $cot->cliente,
                'cliente_id' => $cot->cliente_id,
                'asesor_id' => $cot->asesor_id,
                'asesor_name' => $cot->asesor?->name ?? null,
                'tipo_cotizacion_id' => $cot->tipo_cotizacion_id,
                'fecha_envio' => $cot->fecha_envio,
                'created_at' => $cot->created_at,
            ]);
        }
        
        \Log::info(' ===== FIN getCotizaciones =====');

        return response()->json([
            'success' => true,
            'cotizaciones' => $cotizaciones
        ]);
    }

    /**
     * Ver detalle de una cotización
     * Solo permite ver información de logo
     */
    public function verCotizacion($id)
    {
        $cotizacion = Cotizacion::with([
            'cliente',
            'asesor',
            'logoCotizacion',
            'logoCotizacion.fotos',
            'tipoCotizacion'
        ])->findOrFail($id);

        // Verificar que sea tipo Logo o Combinada
        $tiposCodigos = ['L', 'PL', 'C'];
        if (!in_array($cotizacion->tipoCotizacion->codigo ?? '', $tiposCodigos)) {
            abort(403, 'No tienes permiso para ver esta cotización.');
        }

        // Verificar que tenga información de logo
        if (!$cotizacion->logoCotizacion) {
            abort(404, 'Esta cotización no tiene información de logo.');
        }

        return view('visualizador-logo.detalle', compact('cotizacion'));
    }

    /**
     * Obtener estadísticas del dashboard
     */
    public function getEstadisticas()
    {
        // Obtener IDs de tipos permitidos
        $tipoLogoId = \App\Models\TipoCotizacion::where('codigo', 'L')->value('id');
        $tipoCombinada1Id = \App\Models\TipoCotizacion::where('codigo', 'PL')->value('id');
        $tipoCombinada2Id = \App\Models\TipoCotizacion::where('codigo', 'C')->value('id');

        $tiposPermitidos = array_filter([$tipoLogoId, $tipoCombinada1Id, $tipoCombinada2Id]);

        $baseQuery = Cotizacion::whereNotNull('numero_cotizacion')
            ->where('es_borrador', false);

        if (!empty($tiposPermitidos)) {
            $baseQuery->whereIn('tipo_cotizacion_id', $tiposPermitidos);
        }

        $estadisticas = [
            'total' => (clone $baseQuery)->count(),
            'pendientes' => (clone $baseQuery)->where('estado', 'pendiente')->count(),
            'aprobadas' => (clone $baseQuery)->where('estado', 'aprobado')->count(),
            'rechazadas' => (clone $baseQuery)->where('estado', 'rechazado')->count(),
            'este_mes' => (clone $baseQuery)->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'estadisticas' => $estadisticas
        ]);
    }
}
