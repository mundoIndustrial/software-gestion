<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FestivosController extends Controller
{
    /**
     * Obtener festivos para un año específico o todos
     * 
     * GET /api/festivos
     * GET /api/festivos?year=2025
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $year = $request->query('year');
            
            $query = DB::table('festivos');
            
            if ($year) {
                // Filtrar por año específico
                $query->whereYear('fecha', $year);
            }
            
            $festivos = $query
                ->select('fecha', 'nombre as name')
                ->orderBy('fecha')
                ->get();
            
            // Convertir a array de fechas (string YYYY-MM-DD)
            $fechas = $festivos->map(function($f) {
                return is_string($f->fecha) ? $f->fecha : $f->fecha->format('Y-m-d');
            })->toArray();
            
            return response()->json([
                'success' => true,
                'data' => $fechas,
                'count' => count($fechas),
                'message' => "Festivos cargados correctamente"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
    
    /**
     * Obtener festivos con detalles completos
     * 
     * GET /api/festivos/detailed
     * GET /api/festivos/detailed?year=2025
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailed(Request $request)
    {
        try {
            $year = $request->query('year');
            
            $query = DB::table('festivos');
            
            if ($year) {
                $query->whereYear('fecha', $year);
            }
            
            $festivos = $query
                ->select('id', 'fecha', 'nombre', 'descripcion', 'es_trasladado')
                ->orderBy('fecha')
                ->get();
            
            // Formatear fechas
            $festivos = $festivos->map(function($f) {
                return [
                    'id' => $f->id,
                    'fecha' => is_string($f->fecha) ? $f->fecha : $f->fecha->format('Y-m-d'),
                    'nombre' => $f->nombre,
                    'descripcion' => $f->descripcion ?? null,
                    'es_trasladado' => (bool)($f->es_trasladado ?? false)
                ];
            })->toArray();
            
            return response()->json([
                'success' => true,
                'data' => $festivos,
                'count' => count($festivos)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
    
    /**
     * Verificar si una fecha es festivo
     * 
     * GET /api/festivos/check?fecha=2025-05-01
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        try {
            $fecha = $request->query('fecha');
            
            if (!$fecha) {
                return response()->json([
                    'success' => false,
                    'error' => 'Parameter "fecha" is required (YYYY-MM-DD format)'
                ], 400);
            }
            
            // Validar formato de fecha
            try {
                $fechaParsed = Carbon::parseDate($fecha)->format('Y-m-d');
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid date format. Use YYYY-MM-DD'
                ], 400);
            }
            
            $festivo = DB::table('festivos')
                ->whereDate('fecha', $fechaParsed)
                ->first();
            
            $isFestivo = $festivo ? true : false;
            
            return response()->json([
                'success' => true,
                'fecha' => $fechaParsed,
                'es_festivo' => $isFestivo,
                'nombre' => $festivo->nombre ?? null
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener festivos en rango de fechas
     * 
     * GET /api/festivos/range?start=2025-05-01&end=2025-06-30
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function range(Request $request)
    {
        try {
            $start = $request->query('start');
            $end = $request->query('end');
            
            if (!$start || !$end) {
                return response()->json([
                    'success' => false,
                    'error' => 'Parameters "start" and "end" are required (YYYY-MM-DD format)'
                ], 400);
            }
            
            // Validar formato de fechas
            try {
                $startParsed = Carbon::parseDate($start)->format('Y-m-d');
                $endParsed = Carbon::parseDate($end)->format('Y-m-d');
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid date format. Use YYYY-MM-DD'
                ], 400);
            }
            
            $festivos = DB::table('festivos')
                ->whereBetween('fecha', [$startParsed, $endParsed])
                ->select('fecha', 'nombre')
                ->orderBy('fecha')
                ->get();
            
            $fechas = $festivos->map(function($f) {
                return is_string($f->fecha) ? $f->fecha : $f->fecha->format('Y-m-d');
            })->toArray();
            
            return response()->json([
                'success' => true,
                'data' => $fechas,
                'count' => count($fechas),
                'range' => [
                    'start' => $startParsed,
                    'end' => $endParsed
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}
