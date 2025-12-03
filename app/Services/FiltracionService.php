<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\Hora;
use App\Models\User;
use App\Models\Maquina;
use App\Models\Tela;

/**
 * FiltracionService
 * 
 * Encapsula toda la lógica de filtración de datos:
 * - Filtración por fecha (rango, día específico, mes, fechas múltiples)
 * - Obtención de columnas válidas por sección
 * - Aplicación de filtros dinámicos (JSON)
 * - Manejo de relaciones para secciones específicas
 * 
 * @author Refactor Service Layer - Fase 2
 */
class FiltracionService extends BaseService
{
    /**
     * Aplicar filtro de fecha según el tipo especificado
     * 
     * Soporta filtración por:
     * - range: Rango de fechas (start_date a end_date)
     * - day: Día específico
     * - month: Mes completo (formato YYYY-MM)
     * - specific: Múltiples fechas específicas separadas por coma
     * 
     * @param \Illuminate\Database\Query\Builder $query
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function aplicarFiltroFecha($query, $request)
    {
        $this->log('Aplicando filtro de fecha', [
            'filter_type' => $request->get('filter_type'),
            'has_dates' => !empty($request->get('start_date') || $request->get('specific_date') || $request->get('month'))
        ]);

        $filterType = $request->get('filter_type');

        if (!$filterType || $filterType === 'range') {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            if ($startDate && $endDate) {
                $query->whereDate('fecha', '>=', $startDate)
                      ->whereDate('fecha', '<=', $endDate);
                      
                $this->log('Filtro de rango aplicado', ['start' => $startDate, 'end' => $endDate]);
            }
        } elseif ($filterType === 'day') {
            $specificDate = $request->get('specific_date');
            if ($specificDate) {
                $query->whereDate('fecha', $specificDate);
                $this->log('Filtro de día aplicado', ['date' => $specificDate]);
            }
        } elseif ($filterType === 'month') {
            $month = $request->get('month');
            if ($month) {
                // Formato esperado: YYYY-MM
                $year = substr($month, 0, 4);
                $monthNum = substr($month, 5, 2);
                $startOfMonth = "{$year}-{$monthNum}-01";
                $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
                $query->whereDate('fecha', '>=', $startOfMonth)
                      ->whereDate('fecha', '<=', $endOfMonth);
                      
                $this->log('Filtro de mes aplicado', ['month' => $month, 'start' => $startOfMonth, 'end' => $endOfMonth]);
            }
        } elseif ($filterType === 'specific') {
            $specificDates = $request->get('specific_dates');
            if ($specificDates) {
                $dates = explode(',', $specificDates);
                $query->whereIn('fecha', $dates);
                $this->log('Filtro de fechas específicas aplicado', ['count' => count($dates)]);
            }
        }
    }

    /**
     * Obtener columnas válidas para cada sección
     * 
     * Define el conjunto de columnas permitidas para cada sección:
     * - produccion: Sección de registros de producción
     * - polos: Sección de registros de polos
     * - corte: Sección de registros de corte (incluye relaciones)
     * 
     * @param string $section Nombre de la sección ('produccion', 'polos', 'corte')
     * @return array Columnas válidas para la sección
     */
    public function getValidColumnsForSection($section)
    {
        $this->log('Obteniendo columnas válidas para sección', ['section' => $section]);

        $validColumns = [
            'produccion' => [
                'fecha', 'modulo', 'orden_produccion', 'hora', 'tiempo_ciclo',
                'porcion_tiempo', 'cantidad', 'paradas_programadas', 'paradas_no_programadas',
                'tiempo_parada_no_programada', 'numero_operarios', 'tiempo_para_programada',
                'meta', 'eficiencia'
            ],
            'polos' => [
                'fecha', 'modulo', 'orden_produccion', 'hora', 'tiempo_ciclo',
                'porcion_tiempo', 'cantidad', 'paradas_programadas', 'paradas_no_programadas',
                'tiempo_parada_no_programada', 'numero_operarios', 'tiempo_para_programada',
                'meta', 'eficiencia'
            ],
            'corte' => [
                'fecha', 'modulo', 'orden_produccion', 'hora_id', 'operario_id', 'actividad',
                'maquina_id', 'tela_id', 'tiempo_ciclo', 'porcion_tiempo', 'cantidad',
                'paradas_programadas', 'paradas_no_programadas', 'tiempo_parada_no_programada',
                'numero_operarios', 'tiempo_para_programada', 'meta', 'eficiencia',
                'tipo_extendido', 'numero_capas', 'tiempo_extendido', 'trazado', 'tiempo_trazado'
            ]
        ];

        return $validColumns[$section] ?? [];
    }

    /**
     * Aplicar filtros dinámicos en formato JSON
     * 
     * Aplica filtros recibidos como JSON validando que pertenezcan a la sección
     * actual. Maneja relaciones especiales para la sección 'corte':
     * - hora_id → busca en tabla Hora
     * - operario_id → busca en tabla User
     * - maquina_id → busca en tabla Maquina
     * - tela_id → busca en tabla Tela
     * 
     * @param \Illuminate\Database\Query\Builder $query
     * @param \Illuminate\Http\Request $request
     * @param string $section Sección actual (produccion, polos, corte)
     * @return void
     */
    public function aplicarFiltrosDinamicos($query, $request, $section)
    {
        try {
            $this->log('Iniciando aplicación de filtros dinámicos', [
                'section' => $section,
                'has_filters' => !empty($request->get('filters'))
            ]);

            // Obtener filtros del request (formato JSON)
            $filters = $request->get('filters');
            
            if (!$filters) {
                $this->log('No hay filtros para aplicar');
                return;
            }

            // Si es string JSON, decodificar
            if (is_string($filters)) {
                $filters = json_decode($filters, true);
                
                // Si la decodificación falla, retornar sin aplicar filtros
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->logWarning('Error al decodificar filtros JSON', [
                        'error' => json_last_error_msg(),
                        'filters_raw' => $filters
                    ]);
                    return;
                }
            }

            if (!is_array($filters) || empty($filters)) {
                $this->log('Filtros vacíos después de decodificación');
                return;
            }

            // VALIDAR QUE LOS FILTROS CORRESPONDAN A LA SECCIÓN ACTUAL
            // Esto previene que filtros de una sección se apliquen a otra
            $validColumns = $this->getValidColumnsForSection($section);
            $filters = array_intersect_key($filters, array_flip($validColumns));
            
            if (empty($filters)) {
                $this->log('Ningún filtro válido después de validación');
                return;
            }

            $this->log('Aplicando filtros validados', ['count' => count($filters), 'columns' => array_keys($filters)]);

            // Aplicar cada filtro
            foreach ($filters as $column => $values) {
                // Validar que $values sea un array
                if (!is_array($values)) {
                    // Si es un valor único, convertirlo a array
                    $values = [$values];
                }
                
                if (empty($values)) {
                    continue;
                }

                // Manejar columnas especiales según la sección
                if ($section === 'corte') {
                    $this->aplicarFiltroCorte($query, $column, $values);
                } else {
                    // Para producción y polos, todas son columnas directas
                    $this->aplicarFiltroDirecto($query, $column, $values);
                }
            }
            
            $this->log('Filtros dinámicos aplicados exitosamente');
        } catch (\Exception $e) {
            $this->logError('Error al aplicar filtros dinámicos', [
                'error' => $e->getMessage(),
                'section' => $section,
                'trace' => $e->getTraceAsString()
            ]);
            // No lanzar excepción, simplemente continuar sin filtros
        }
    }

    /**
     * Aplicar filtro directo a una columna
     * 
     * Usado para secciones de produccion y polos
     * 
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $column Nombre de la columna
     * @param array $values Valores a filtrar
     * @return void
     */
    private function aplicarFiltroDirecto($query, $column, $values)
    {
        // Manejar fecha con formato especial
        if ($column === 'fecha') {
            // Convertir fechas del formato dd-mm-yyyy a yyyy-mm-dd
            $formattedDates = array_map(function($date) {
                if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date, $matches)) {
                    return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
                }
                return $date;
            }, $values);
            $query->whereIn($column, $formattedDates);
            $this->log('Filtro de fecha aplicado (directo)', ['count' => count($formattedDates)]);
        } else {
            $query->whereIn($column, $values);
            $this->log('Filtro directo aplicado', ['column' => $column, 'count' => count($values)]);
        }
    }

    /**
     * Aplicar filtro para sección de corte (maneja relaciones)
     * 
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $column Nombre de la columna/relación
     * @param array $values Valores a filtrar
     * @return void
     */
    private function aplicarFiltroCorte($query, $column, $values)
    {
        switch ($column) {
            case 'hora_id':
                // Buscar IDs de horas por sus valores
                $horaIds = Hora::whereIn('hora', $values)->pluck('id')->toArray();
                if (!empty($horaIds)) {
                    $query->whereIn('hora_id', $horaIds);
                    $this->log('Filtro hora_id aplicado', ['count' => count($horaIds)]);
                }
                break;

            case 'operario_id':
                // Buscar IDs de operarios por sus nombres
                $operarioIds = User::whereIn('name', $values)->pluck('id')->toArray();
                if (!empty($operarioIds)) {
                    $query->whereIn('operario_id', $operarioIds);
                    $this->log('Filtro operario_id aplicado', ['count' => count($operarioIds)]);
                }
                break;

            case 'maquina_id':
                // Buscar IDs de máquinas por sus nombres
                $maquinaIds = Maquina::whereIn('nombre_maquina', $values)->pluck('id')->toArray();
                if (!empty($maquinaIds)) {
                    $query->whereIn('maquina_id', $maquinaIds);
                    $this->log('Filtro maquina_id aplicado', ['count' => count($maquinaIds)]);
                }
                break;

            case 'tela_id':
                // Buscar IDs de telas por sus nombres
                $telaIds = Tela::whereIn('nombre_tela', $values)->pluck('id')->toArray();
                if (!empty($telaIds)) {
                    $query->whereIn('tela_id', $telaIds);
                    $this->log('Filtro tela_id aplicado', ['count' => count($telaIds)]);
                }
                break;

            case 'fecha':
                // Convertir fechas del formato dd-mm-yyyy a yyyy-mm-dd
                $formattedDates = array_map(function($date) {
                    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date, $matches)) {
                        return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
                    }
                    return $date;
                }, $values);
                $query->whereIn($column, $formattedDates);
                $this->log('Filtro fecha aplicado (corte)', ['count' => count($formattedDates)]);
                break;

            default:
                // Columnas normales
                $query->whereIn($column, $values);
                $this->log('Filtro estándar aplicado (corte)', ['column' => $column, 'count' => count($values)]);
                break;
        }
    }
}
