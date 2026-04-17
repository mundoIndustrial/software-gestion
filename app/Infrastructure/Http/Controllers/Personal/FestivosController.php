<?php

namespace App\Infrastructure\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Festivo;
use Carbon\Carbon;

class FestivosController extends Controller
{
    /**
     * Obtener festivos para un aÃ±o especÃ­fico o todos
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
                // Filtrar por aÃ±o especÃ­fico
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

    /**
     * Sincronizar festivos de Colombia desde la API de Nager.Date
     * 
     * POST /api/festivos/sincronizar/{year}
     * 
     * @param int $year AÃ±o a sincronizar
     * @return \Illuminate\Http\JsonResponse
     */
    public function sincronizarFestivos($year)
    {
        try {
            if (!is_numeric($year) || $year < 2020 || $year > 2100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Año inválido. Ingresa un año entre 2020 y 2100'
                ], 422);
            }

            Log::info('[FestivosController] Iniciando sincronizacion local de festivos', ['year' => (int) $year]);

            [$created, $updated] = $this->syncYearFestivos((int) $year);

            return response()->json([
                'success' => true,
                'message' => "Festivos de {$year} sincronizados correctamente",
                'data' => [
                    'year' => (int) $year,
                    'created' => $created,
                    'updated' => $updated,
                    'total' => $created + $updated,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[FestivosController] Error general en sincronizacion', [
                'year' => $year,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al sincronizar festivos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizar múltiples años de una vez.
     */public function sincronizarRango(Request $request)
    {
        try {
            $years = $request->input('years', []);

            if (!is_array($years) || empty($years)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes proporcionar un array de años'
                ], 422);
            }

            $results = [];

            foreach ($years as $year) {
                if (!is_numeric($year) || $year < 2020 || $year > 2100) {
                    $results[$year] = [
                        'success' => false,
                        'message' => 'Año inválido'
                    ];
                    continue;
                }

                try {
                    [$created, $updated] = $this->syncYearFestivos((int) $year);

                    $results[$year] = [
                        'success' => true,
                        'created' => $created,
                        'updated' => $updated,
                        'total' => $created + $updated,
                    ];
                } catch (\Exception $e) {
                    $results[$year] = [
                        'success' => false,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Sincronizacion de rango completada',
                'results' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('[FestivosController] Error en sincronizacion de rango', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar rango de años'
            ], 500);
        }
    }

    /**
     * Sincroniza un año de festivos colombianos usando cmixin/business-day.
     *
     * @return array{0:int,1:int} [created, updated]
     */
    private function syncYearFestivos(int $year): array
    {
        $created = 0;
        $updated = 0;

        foreach ($this->buildYearHolidays($year) as $holiday) {
            $model = Festivo::updateOrCreate(
                ['fecha' => $holiday['fecha']],
                [
                    'nombre' => $holiday['nombre'],
                    'descripcion' => $holiday['descripcion'],
                    'es_trasladado' => $holiday['es_trasladado'],
                ]
            );

            if ($model->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        return [$created, $updated];
    }

    /**
     * Construye los festivos del año con identificación y nombre de la librería.
     *
     * @return array<int, array{fecha:string,nombre:string,descripcion:string,es_trasladado:int}>
     */
    private function buildYearHolidays(int $year): array
    {
        $items = [];
        $date = Carbon::create($year, 1, 1)->startOfDay();
        $end = $date->copy()->endOfYear()->startOfDay();

        while ($date->lte($end)) {
            if ($date->isHoliday()) {
                $holidayId = $date->getHolidayId();
                $holidayName = $date->getHolidayName() ?: 'Festivo Colombia';
                $isTrasladado = is_string($holidayId) && str_contains($holidayId, 'monday-after-') ? 1 : 0;

                $items[] = [
                    'fecha' => $date->toDateString(),
                    'nombre' => $holidayName,
                    'descripcion' => is_string($holidayId) ? $holidayId : $holidayName,
                    'es_trasladado' => $isTrasladado,
                ];
            }

            $date->addDay();
        }

        return $items;
    }
}