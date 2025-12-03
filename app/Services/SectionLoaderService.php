<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\RegistroPisoProduccion;
use App\Models\RegistroPisoPolo;
use App\Models\RegistroPisoCorte;

/**
 * SectionLoaderService
 * 
 * Encapsula la lógica de carga y renderización de secciones dinámicas:
 * - Carga de datos según sección (producción, polos, corte)
 * - Aplicación de filtros dinámicos por sección
 * - Paginación de resultados (50 registros por página)
 * - Renderización de vistas parciales de tabla
 * - Información de debug (tiempo de ejecución)
 * 
 * Trabaja en conjunto con FiltracionService para aplicar filtros validados
 * 
 * @author Refactor Service Layer - Fase 2
 */
class SectionLoaderService extends BaseService
{
    /**
     * Inyección de FiltracionService
     * 
     * @var \App\Services\FiltracionService
     */
    private $filtracion;

    /**
     * Constructor
     * 
     * @param \App\Services\FiltracionService $filtracion
     */
    public function __construct(FiltracionService $filtracion)
    {
        $this->filtracion = $filtracion;
    }

    /**
     * Cargar sección y retornar datos paginados con HTML renderizado
     * 
     * Procesa una sección específica:
     * 1. Obtiene el query builder apropiad según sección
     * 2. Aplica filtros dinámicos via FiltracionService
     * 3. Pagina resultados (50 por página)
     * 4. Obtiene columnas de la tabla
     * 5. Renderiza vista parcial HTML
     * 6. Retorna JSON con tabla, paginación e info de debug
     * 
     * @param string $section Nombre de sección ('produccion', 'polos', 'corte')
     * @param \Illuminate\Http\Request $request Request con filtros
     * @return \Illuminate\Http\JsonResponse
     */
    public function loadSection($section, $request)
    {
        $startTime = microtime(true);
        
        $this->log('Cargando sección', [
            'section' => $section,
            'filters_present' => !empty($request->get('filters'))
        ]);

        try {
            // Procesar según sección
            if ($section === 'produccion') {
                return $this->loadProduccion($startTime, $request);
            } elseif ($section === 'polos') {
                return $this->loadPolos($startTime, $request);
            } elseif ($section === 'corte') {
                return $this->loadCorte($startTime, $request);
            }

            // Sección no válida
            $this->logWarning('Sección no válida solicitada', ['section' => $section]);
            return response()->json(['error' => 'Invalid section'], 400);
        } catch (\Exception $e) {
            $this->logError('Error al cargar sección', [
                'section' => $section,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error loading section'], 500);
        }
    }

    /**
     * Cargar sección de Producción
     * 
     * @param float $startTime Tiempo de inicio para calcular duración
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function loadProduccion($startTime, $request)
    {
        $this->log('Procesando sección producción');

        $query = RegistroPisoProduccion::query();
        
        // Aplicar filtros dinámicos
        $this->filtracion->aplicarFiltrosDinamicos($query, $request, 'produccion');
        
        // Paginar resultados
        $registros = $query->orderBy('id', 'desc')->paginate(50);
        
        // Obtener columnas
        $columns = Schema::getColumnListing('registro_piso_produccion');
        $columns = array_diff($columns, ['id', 'created_at', 'updated_at', 'producida']);

        // Renderizar HTML de la tabla
        $tableHtml = view('partials.table-body-produccion', compact('registros', 'columns'))->render();

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $this->log('Sección producción cargada', [
            'total_records' => $registros->total(),
            'current_page' => $registros->currentPage(),
            'duration_ms' => round($duration, 2)
        ]);

        return response()->json([
            'table_html' => $tableHtml,
            'pagination' => [
                'current_page' => $registros->currentPage(),
                'last_page' => $registros->lastPage(),
                'per_page' => $registros->perPage(),
                'total' => $registros->total(),
                'first_item' => $registros->firstItem(),
                'last_item' => $registros->lastItem(),
                'links_html' => $registros->appends($request->query())->links('vendor.pagination.custom')->render()
            ],
            'debug' => [
                'server_time_ms' => round($duration, 2),
                'section' => 'produccion'
            ]
        ]);
    }

    /**
     * Cargar sección de Polos
     * 
     * @param float $startTime Tiempo de inicio para calcular duración
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function loadPolos($startTime, $request)
    {
        $this->log('Procesando sección polos');

        $query = RegistroPisoPolo::query();
        
        // Aplicar filtros dinámicos
        $this->filtracion->aplicarFiltrosDinamicos($query, $request, 'polos');
        
        // Paginar resultados
        $registros = $query->orderBy('id', 'desc')->paginate(50);
        
        // Obtener columnas
        $columns = Schema::getColumnListing('registro_piso_polo');
        $columns = array_diff($columns, ['id', 'created_at', 'updated_at', 'producida']);

        // Renderizar HTML de la tabla
        $tableHtml = view('partials.table-body-polos', compact('registros', 'columns'))->render();

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $this->log('Sección polos cargada', [
            'total_records' => $registros->total(),
            'current_page' => $registros->currentPage(),
            'duration_ms' => round($duration, 2)
        ]);

        return response()->json([
            'table_html' => $tableHtml,
            'pagination' => [
                'current_page' => $registros->currentPage(),
                'last_page' => $registros->lastPage(),
                'per_page' => $registros->perPage(),
                'total' => $registros->total(),
                'first_item' => $registros->firstItem(),
                'last_item' => $registros->lastItem(),
                'links_html' => $registros->appends($request->query())->links('vendor.pagination.custom')->render()
            ],
            'debug' => [
                'server_time_ms' => round($duration, 2),
                'section' => 'polos'
            ]
        ]);
    }

    /**
     * Cargar sección de Corte
     * 
     * Incluye eager loading de relaciones (hora, operario, máquina, tela)
     * para evitar N+1 queries
     * 
     * @param float $startTime Tiempo de inicio para calcular duración
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function loadCorte($startTime, $request)
    {
        $this->log('Procesando sección corte');

        // Usar with() para eager loading evitar N+1
        $query = RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela']);
        
        // Aplicar filtros dinámicos
        $this->filtracion->aplicarFiltrosDinamicos($query, $request, 'corte');
        
        // Paginar resultados
        $registros = $query->orderBy('id', 'desc')->paginate(50);
        
        // Obtener columnas
        $columns = Schema::getColumnListing('registro_piso_corte');
        $columns = array_diff($columns, ['id', 'created_at', 'updated_at', 'producida']);

        // Renderizar HTML de la tabla
        $tableHtml = view('partials.table-body-corte', compact('registros', 'columns'))->render();

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $this->log('Sección corte cargada', [
            'total_records' => $registros->total(),
            'current_page' => $registros->currentPage(),
            'duration_ms' => round($duration, 2)
        ]);

        return response()->json([
            'table_html' => $tableHtml,
            'pagination' => [
                'current_page' => $registros->currentPage(),
                'last_page' => $registros->lastPage(),
                'per_page' => $registros->perPage(),
                'total' => $registros->total(),
                'first_item' => $registros->firstItem(),
                'last_item' => $registros->lastItem(),
                'links_html' => $registros->appends($request->query())->links('vendor.pagination.custom')->render()
            ],
            'debug' => [
                'server_time_ms' => round($duration, 2),
                'section' => 'corte'
            ]
        ]);
    }
}
