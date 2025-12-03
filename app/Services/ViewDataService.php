<?php

namespace App\Services;

use App\Models\RegistroPisoProduccion;
use App\Models\RegistroPisoPolo;
use App\Models\RegistroPisoCorte;
use App\Models\User;
use App\Models\Hora;
use App\Models\Maquina;
use App\Models\Tela;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ViewDataService extends BaseService
{
    private ProduccionCalculadoraService $produccionCalc;
    private FiltracionService $filtracion;
    private FiltrosService $filtros;

    public function __construct(
        ProduccionCalculadoraService $produccionCalc,
        FiltracionService $filtracion,
        FiltrosService $filtros
    ) {
        $this->produccionCalc = $produccionCalc;
        $this->filtracion = $filtracion;
        $this->filtros = $filtros;
    }

    /**
     * Preparar datos para la vista principal de tableros
     */
    public function prepareIndexViewData(Request $request)
    {
        $startTime = microtime(true);

        $this->log('ViewDataService::prepareIndexViewData INICIADO');

        try {
            // Determinar rango de fechas
            $dateRange = $this->calculateDateRange($request);

            // Cargar tablas principales con paginación
            $tableData = $this->loadMainTables($request, $dateRange['start_date']);

            // Cargar datos para seguimiento
            $followupData = $this->loadFollowupData($request, $dateRange);

            // Cargar datos para selects
            $selectData = $this->loadSelectData();

            $duration = (microtime(true) - $startTime) * 1000;

            $this->log('ViewDataService::prepareIndexViewData completado', [
                'duration_ms' => round($duration, 2)
            ]);

            return [
                'success' => true,
                'tables' => $tableData,
                'followup' => $followupData,
                'selects' => $selectData,
                'duration_ms' => round($duration, 2)
            ];
        } catch (\Exception $e) {
            $this->logError('Error en prepareIndexViewData', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error al preparar datos de vista: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calcular rango de fechas basado en filtros
     */
    private function calculateDateRange(Request $request)
    {
        $limit_days = 30;
        $hasFilters = $request->has('filters') || $request->has('filter_type') ||
                      $request->has('start_date') || $request->has('end_date');

        if (!$hasFilters) {
            $start_date = now()->subDays($limit_days);
        } else {
            $start_date = now()->subMonths(1);
        }

        // Para seguimiento: calcular rango completo basado en filtro_type
        $endDate = now();
        $startDate = now()->subDays(7); // Por defecto: últimos 7 días

        $filterType = $request->get('filter_type');
        if ($filterType) {
            $dateInfo = $this->parseDateFilter($request, $filterType);
            $startDate = $dateInfo['start'];
            $endDate = $dateInfo['end'];
        }

        return [
            'start_date' => $start_date,
            'start_date_followup' => $startDate,
            'end_date_followup' => $endDate,
            'filter_type' => $filterType
        ];
    }

    /**
     * Parsear filtros de fecha
     */
    private function parseDateFilter(Request $request, $filterType)
    {
        $endDate = now();
        $startDate = now()->subDays(7);

        if ($filterType === 'day') {
            $specificDate = $request->get('specific_date');
            if ($specificDate) {
                $startDate = Carbon::createFromFormat('Y-m-d', $specificDate)->startOfDay();
                $endDate = Carbon::createFromFormat('Y-m-d', $specificDate)->endOfDay();
            }
        } elseif ($filterType === 'range') {
            $startDateStr = $request->get('start_date');
            $endDateStr = $request->get('end_date');
            if ($startDateStr && $endDateStr) {
                $startDate = Carbon::createFromFormat('Y-m-d', $startDateStr)->startOfDay();
                $endDate = Carbon::createFromFormat('Y-m-d', $endDateStr)->endOfDay();
            }
        } elseif ($filterType === 'month') {
            $month = $request->get('month');
            if ($month) {
                $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->startOfDay();
                $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->endOfDay();
            }
        } elseif ($filterType === 'specific') {
            $specificDates = $request->get('specific_dates');
            if ($specificDates) {
                $datesArray = array_map(function ($date) {
                    return Carbon::createFromFormat('Y-m-d', trim($date));
                }, explode(',', $specificDates));
                $startDate = collect($datesArray)->min();
                $endDate = collect($datesArray)->max()->endOfDay();
            }
        }

        return ['start' => $startDate, 'end' => $endDate];
    }

    /**
     * Cargar tablas principales con paginación
     */
    private function loadMainTables(Request $request, $start_date)
    {
        $this->log('Cargando tablas principales');

        // Producción
        $queryProduccion = RegistroPisoProduccion::whereDate('fecha', '>=', $start_date);
        $this->filtracion->aplicarFiltrosDinamicos($queryProduccion, $request, 'produccion');
        $registros = $queryProduccion->orderBy('id', 'desc')->paginate(50);
        $columns = Schema::getColumnListing('registro_piso_produccion');
        $columns = array_diff($columns, ['id', 'created_at', 'updated_at', 'producida']);

        // Polos
        $queryPolos = RegistroPisoPolo::whereDate('fecha', '>=', $start_date);
        $this->filtracion->aplicarFiltrosDinamicos($queryPolos, $request, 'polos');
        $registrosPolos = $queryPolos->orderBy('id', 'desc')->paginate(50);
        $columnsPolos = Schema::getColumnListing('registro_piso_polo');
        $columnsPolos = array_diff($columnsPolos, ['id', 'created_at', 'updated_at', 'producida']);

        // Corte
        $queryCorte = RegistroPisoCorte::whereDate('fecha', '>=', $start_date);
        $this->filtracion->aplicarFiltrosDinamicos($queryCorte, $request, 'corte');
        $registrosCorte = $queryCorte->with(['hora', 'operario', 'maquina', 'tela'])->orderBy('id', 'desc')->paginate(50);
        $columnsCorte = Schema::getColumnListing('registro_piso_corte');
        $columnsCorte = array_diff($columnsCorte, ['id', 'created_at', 'updated_at', 'producida']);

        return [
            'registros' => $registros,
            'columns' => array_values($columns),
            'registrosPolos' => $registrosPolos,
            'columnsPolos' => array_values($columnsPolos),
            'registrosCorte' => $registrosCorte,
            'columnsCorte' => array_values($columnsCorte)
        ];
    }

    /**
     * Cargar datos para seguimiento
     */
    private function loadFollowupData(Request $request, $dateRange)
    {
        $this->log('Cargando datos de seguimiento');

        $startDate = $dateRange['start_date_followup'];
        $endDate = $dateRange['end_date_followup'];

        $todosRegistrosProduccion = RegistroPisoProduccion::whereDate('fecha', '>=', $startDate)
            ->whereDate('fecha', '<=', $endDate)
            ->get();

        $todosRegistrosPolos = RegistroPisoPolo::whereDate('fecha', '>=', $startDate)
            ->whereDate('fecha', '<=', $endDate)
            ->get();

        $todosRegistrosCorte = RegistroPisoCorte::whereDate('fecha', '>=', $startDate)
            ->whereDate('fecha', '<=', $endDate)
            ->get();

        // Aplicar filtros adicionales
        $activeSection = $request->get('active_section', 'produccion');

        $registrosProduccionFiltrados = $todosRegistrosProduccion;
        $registrosPolosFiltrados = $todosRegistrosPolos;
        $registrosCorteFiltrados = $todosRegistrosCorte;

        if ($activeSection === 'produccion') {
            $registrosProduccionFiltrados = $this->filtros->filtrarRegistrosPorFecha($todosRegistrosProduccion, $request);
        } elseif ($activeSection === 'polos') {
            $registrosPolosFiltrados = $this->filtros->filtrarRegistrosPorFecha($todosRegistrosPolos, $request);
        } elseif ($activeSection === 'corte') {
            $registrosCorteFiltrados = $this->filtros->filtrarRegistrosPorFecha($todosRegistrosCorte, $request);
        }

        // Calcular seguimiento
        $seguimientoProduccion = $this->produccionCalc->calcularSeguimientoModulos($registrosProduccionFiltrados);
        $seguimientoPolos = $this->produccionCalc->calcularSeguimientoModulos($registrosPolosFiltrados);
        $seguimientoCorte = $this->produccionCalc->calcularSeguimientoModulos($registrosCorteFiltrados);

        // Calcular datos por horas y operarios
        $horasData = $this->produccionCalc->calcularProduccionPorHoras($registrosCorteFiltrados);
        $operariosData = $this->produccionCalc->calcularProduccionPorOperarios($registrosCorteFiltrados);

        return [
            'seguimientoProduccion' => $seguimientoProduccion,
            'seguimientoPolos' => $seguimientoPolos,
            'seguimientoCorte' => $seguimientoCorte,
            'horasData' => $horasData,
            'operariosData' => $operariosData
        ];
    }

    /**
     * Cargar datos para selects en formularios
     */
    private function loadSelectData()
    {
        $this->log('Cargando datos para selects');

        return [
            'horas' => Hora::all(),
            'operarios' => User::whereHas('role', function ($query) {
                $query->where('name', 'cortador');
            })->get(),
            'maquinas' => Maquina::all(),
            'telas' => Tela::all()
        ];
    }

    /**
     * Formatear datos para respuesta JSON en AJAX
     */
    public function formatAjaxResponse($tableData)
    {
        $this->log('Formateando respuesta AJAX');

        try {
            $registrosCorte = $tableData['registrosCorte'];

            return [
                'success' => true,
                'registros' => $tableData['registros']->items(),
                'columns' => $tableData['columns'],
                'registrosPolos' => $tableData['registrosPolos']->items(),
                'columnsPolos' => $tableData['columnsPolos'],
                'registrosCorte' => $this->formatCorteRecords($registrosCorte),
                'columnsCorte' => $tableData['columnsCorte'],
                'pagination' => $this->formatPagination($tableData['registros']),
                'paginationPolos' => $this->formatPagination($tableData['registrosPolos']),
                'paginationCorte' => $this->formatPagination($registrosCorte)
            ];
        } catch (\Exception $e) {
            $this->logError('Error al formatear respuesta AJAX', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error al formatear datos'
            ];
        }
    }

    /**
     * Formatear registros de corte con displays
     */
    private function formatCorteRecords($registrosCorte)
    {
        return $registrosCorte->map(function ($registro) {
            $registroArray = $registro->toArray();
            if ($registro->hora) {
                $registroArray['hora_display'] = $registro->hora->hora;
            }
            if ($registro->operario) {
                $registroArray['operario_display'] = $registro->operario->name;
            }
            if ($registro->maquina) {
                $registroArray['maquina_display'] = $registro->maquina->nombre_maquina;
            }
            if ($registro->tela) {
                $registroArray['tela_display'] = $registro->tela->nombre_tela;
            }
            return $registroArray;
        })->toArray();
    }

    /**
     * Formatear información de paginación
     */
    private function formatPagination($paginated)
    {
        return [
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'first_item' => $paginated->firstItem(),
            'last_item' => $paginated->lastItem(),
            'links_html' => $paginated->appends(request()->query())->links('vendor.pagination.custom')->render()
        ];
    }
}
