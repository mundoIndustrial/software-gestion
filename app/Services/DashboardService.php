<?php

namespace App\Services;

use App\Models\RegistroPisoProduccion;
use App\Models\RegistroPisoPolo;
use App\Models\RegistroPisoCorte;
use Illuminate\Http\Request;

class DashboardService extends BaseService
{
    private ProduccionCalculadoraService $produccionCalc;
    private FiltracionService $filtracion;

    public function __construct(
        ProduccionCalculadoraService $produccionCalc,
        FiltracionService $filtracion
    ) {
        $this->produccionCalc = $produccionCalc;
        $this->filtracion = $filtracion;
    }

    /**
     * Obtener datos agregados de dashboard para corte
     */
    public function getDashboardCorteData(Request $request)
    {
        $startTime = microtime(true);

        $paramsRecibidos = array_filter($request->all(), function ($value) {
            return $value !== null && $value !== '';
        });

        $hayFiltro = !empty($paramsRecibidos) && isset($paramsRecibidos['filter_type']);
        
        $this->log('DashboardService::getDashboardCorteData INICIADO', [
            'hay_filtro' => $hayFiltro,
            'parametros' => array_keys($paramsRecibidos)
        ]);

        try {
            // Obtener todos los registros de corte con relaciones
            $query = RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela']);
            $registrosCorte = $query->get();

            $this->log('Total registros antes de filtrar', ['total' => $registrosCorte->count()]);

            // Aplicar filtros solo si hay filter_type
            if ($hayFiltro) {
                // Usar servicio de filtración para aplicar filtros
                $registrosCorteFiltrados = $this->filtracion->aplicarFiltrosDinamicos(
                    RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela']),
                    $request,
                    'corte'
                )->get();

                $this->log('Registros FILTRADOS', [
                    'total' => $registrosCorteFiltrados->count(),
                    'filter_type' => $request->get('filter_type')
                ]);
            } else {
                $registrosCorteFiltrados = $registrosCorte;
                $this->log('SIN FILTRO - Mostrando TODOS los registros', [
                    'total' => $registrosCorteFiltrados->count()
                ]);
            }

            // Calcular datos dinámicos para las tablas
            $horasData = $this->produccionCalc->calcularProduccionPorHoras($registrosCorteFiltrados);
            $operariosData = $this->produccionCalc->calcularProduccionPorOperarios($registrosCorteFiltrados);

            $duration = (microtime(true) - $startTime) * 1000;

            $this->log('DashboardService::getDashboardCorteData completado', [
                'horas_count' => count($horasData),
                'operarios_count' => count($operariosData),
                'duration_ms' => round($duration, 2)
            ]);

            return [
                'success' => true,
                'horas' => $horasData,
                'operarios' => $operariosData,
                'duration_ms' => round($duration, 2)
            ];
        } catch (\Exception $e) {
            $this->logError('Error en getDashboardCorteData', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error al obtener datos del dashboard: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener datos de tablas del dashboard
     */
    public function getDashboardTablesData(Request $request)
    {
        $startTime = microtime(true);

        $this->log('DashboardService::getDashboardTablesData INICIADO');

        try {
            $queryCorte = RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela']);
            $this->filtracion->aplicarFiltroFecha($queryCorte, $request);
            $registrosCorte = $queryCorte->get();

            // Calcular datos dinámicos para las tablas de horas y operarios
            $horasData = $this->produccionCalc->calcularProduccionPorHoras($registrosCorte);
            $operariosData = $this->produccionCalc->calcularProduccionPorOperarios($registrosCorte);

            $duration = (microtime(true) - $startTime) * 1000;

            $this->log('DashboardService::getDashboardTablesData completado', [
                'horas_count' => count($horasData),
                'operarios_count' => count($operariosData),
                'duration_ms' => round($duration, 2)
            ]);

            return [
                'success' => true,
                'horasData' => $horasData,
                'operariosData' => $operariosData,
                'duration_ms' => round($duration, 2)
            ];
        } catch (\Exception $e) {
            $this->logError('Error en getDashboardTablesData', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error al obtener datos de tablas: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener datos de seguimiento con filtros
     */
    public function getSeguimientoData(Request $request)
    {
        $startTime = microtime(true);

        $section = $request->get('section', 'produccion');
        $filterType = $request->get('filter_type');

        $this->log('DashboardService::getSeguimientoData INICIADO', [
            'section' => $section,
            'filter_type' => $filterType
        ]);

        try {
            $model = match ($section) {
                'produccion' => RegistroPisoProduccion::class,
                'polos' => RegistroPisoPolo::class,
                'corte' => RegistroPisoCorte::class,
            };

            $query = $model::query();
            $this->filtracion->aplicarFiltroFecha($query, $request);

            // ⚡ OPTIMIZACIÓN: Si no hay filtro específico, limitar a último día o últimos 500 registros
            // para evitar procesar 7000+ registros que bloquean el servidor
            if (!$filterType) {
                $query = $query->latest()->limit(500);
                $this->log('Aplicando LIMIT 500 porque no hay filtro');
            }

            $registrosFiltrados = $query->get();

            $this->log('Registros filtrados obtenidos', [
                'section' => $section,
                'cantidad' => count($registrosFiltrados),
                'limited' => !$filterType ? true : false
            ]);

            $seguimiento = $this->produccionCalc->calcularSeguimientoModulos($registrosFiltrados);

            $duration = (microtime(true) - $startTime) * 1000;

            $this->log('DashboardService::getSeguimientoData completado', [
                'duration_ms' => round($duration, 2)
            ]);

            return [
                'success' => true,
                'data' => $seguimiento,
                'duration_ms' => round($duration, 2)
            ];
        } catch (\Exception $e) {
            $this->logError('Error en getSeguimientoData', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error al obtener datos de seguimiento: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener valores únicos de una columna para filtros
     */
    public function getUniqueValues(Request $request)
    {
        $startTime = microtime(true);

        $section = $request->get('section');
        $column = $request->get('column');

        $this->log('DashboardService::getUniqueValues INICIADO', [
            'section' => $section,
            'column' => $column
        ]);

        try {
            $model = match ($section) {
                'produccion' => RegistroPisoProduccion::class,
                'polos' => RegistroPisoPolo::class,
                'corte' => RegistroPisoCorte::class,
                default => null
            };

            if (!$model) {
                $this->logError('Sección inválida', ['section' => $section]);
                return [
                    'success' => false,
                    'message' => 'Sección inválida',
                    'values' => []
                ];
            }

            $values = $this->extractUniqueValues($model, $section, $column);

            $duration = (microtime(true) - $startTime) * 1000;

            $this->log('Valores únicos obtenidos', [
                'total_values' => count($values),
                'duration_ms' => round($duration, 2)
            ]);

            return [
                'success' => true,
                'values' => $values,
                'duration_ms' => round($duration, 2)
            ];
        } catch (\Exception $e) {
            $this->logError('Error en getUniqueValues', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error al obtener valores: ' . $e->getMessage(),
                'values' => []
            ];
        }
    }

    /**
     * Extrae valores únicos según el tipo de columna
     */
    private function extractUniqueValues($model, $section, $column)
    {
        // Si es corte, manejar columnas de relaciones especiales
        if ($section === 'corte') {
            return $this->extractCorteValues($model, $column);
        }

        // Para producción y polos
        if ($column === 'fecha') {
            return $model::distinct()
                ->pluck($column)
                ->filter()
                ->map(function ($date) {
                    return \Carbon\Carbon::parse($date)->format('d-m-Y');
                })
                ->sort()
                ->values()
                ->toArray();
        }

        return $model::distinct()
            ->pluck($column)
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Extrae valores únicos específicos para corte (con relaciones)
     */
    private function extractCorteValues($model, $column)
    {
        $values = [];

        if ($column === 'hora_id') {
            $values = \App\Models\Hora::distinct()->pluck('hora')->sort()->values()->toArray();
        } elseif ($column === 'operario_id') {
            $values = \App\Models\User::whereHas('registrosPisoCorte')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values()
                ->toArray();
        } elseif ($column === 'maquina_id') {
            $values = \App\Models\Maquina::whereHas('registrosPisoCorte')
                ->distinct()
                ->pluck('nombre_maquina')
                ->sort()
                ->values()
                ->toArray();
        } elseif ($column === 'tela_id') {
            $values = \App\Models\Tela::whereHas('registrosPisoCorte')
                ->distinct()
                ->pluck('nombre_tela')
                ->sort()
                ->values()
                ->toArray();
        } elseif ($column === 'fecha') {
            $values = $model::distinct()
                ->pluck($column)
                ->filter()
                ->map(function ($date) {
                    return \Carbon\Carbon::parse($date)->format('d-m-Y');
                })
                ->sort()
                ->values()
                ->toArray();
        } else {
            $values = $model::distinct()
                ->pluck($column)
                ->filter()
                ->sort()
                ->values()
                ->toArray();
        }

        return $values;
    }
}
