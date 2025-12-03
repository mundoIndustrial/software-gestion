<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroPisoProduccion;
use App\Models\RegistroPisoPolo;
use App\Models\RegistroPisoCorte;
use App\Services\ProduccionCalculadoraService;
use App\Services\FiltrosService;
use App\Services\FiltracionService;
use App\Services\SectionLoaderService;
use App\Services\OperarioService;
use App\Services\MaquinaService;
use App\Services\TelaService;
use App\Services\HoraService;
use App\Services\CorteService;
use App\Services\RegistroService;
use App\Services\DashboardService;
use App\Services\UpdateService;
use App\Services\ViewDataService;

class TablerosController extends Controller
{
    public function __construct(
        private ProduccionCalculadoraService $produccionCalc,
        private FiltrosService $filtros,
        private FiltracionService $filtracion,
        private SectionLoaderService $sectionLoader,
        private OperarioService $operario,
        private MaquinaService $maquina,
        private TelaService $tela,
        private HoraService $hora,
        private CorteService $corteService,
        private RegistroService $registroService,
        private DashboardService $dashboardService,
        private UpdateService $updateService,
        private ViewDataService $viewDataService,
    ) {}

    public function fullscreen(Request $request)
    {
        $section = $request->get('section', 'produccion');
        
        // Obtener todos los registros según la sección
        $registros = match($section) {
            'produccion' => RegistroPisoProduccion::all(),
            'polos' => RegistroPisoPolo::all(),
            'corte' => RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela'])->get(),
            default => RegistroPisoProduccion::all(),
        };
        
        // Filtrar registros por fecha si hay filtros
        $registrosFiltrados = $this->filtros->filtrarRegistrosPorFecha($registros, $request);
        
        // Calcular seguimiento de módulos
        $resultado = $this->produccionCalc->calcularSeguimientoModulos($registrosFiltrados);
        $seguimiento = $resultado;
        
        return view('tableros-fullscreen', compact('seguimiento', 'section'));
    }

    public function corteFullscreen(Request $request)
    {
        // Obtener todos los registros de corte
        $registrosCorte = RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela'])->get();
        
        // Filtrar registros por fecha si hay filtros
        $registrosCorteFiltrados = $this->filtros->filtrarRegistrosPorFecha($registrosCorte, $request);
        
        // Calcular datos dinámicos para las tablas
        $horasData = $this->produccionCalc->calcularProduccionPorHoras($registrosCorteFiltrados);
        $operariosData = $this->produccionCalc->calcularProduccionPorOperarios($registrosCorteFiltrados);
        
        return view('tableros-corte-fullscreen', compact('horasData', 'operariosData'));
    }

    public function index()
    {
        // Si es AJAX con sección específica, delegar a SectionLoader
        $section = request()->get('section');
        $isAjax = request()->ajax() || request()->wantsJson();
        
        if ($isAjax && $section) {
            return $this->sectionLoader->loadSection($section, request());
        }

        // Preparar datos para la vista
        $viewData = $this->viewDataService->prepareIndexViewData(request());
        
        return view('tableros', $viewData);
    }

    public function store(Request $request)
    {
        $result = $this->registroService->store($request);
        $statusCode = $result['success'] ? 201 : 422;
        return response()->json($result, $statusCode);
    }

    public function update(Request $request, $id)
    {
        $result = $this->updateService->update($request, $id);
        
        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function destroy($id)
    {
        $section = request()->query('section');
        $result = $this->registroService->destroy($id, $section);
        $statusCode = $result['success'] ? 200 : 500;
        return response()->json($result, $statusCode);
    }

    public function duplicate($id)
    {
        $section = request()->query('section');
        $result = $this->registroService->duplicate($id, $section);
        $statusCode = $result['success'] ? 201 : 500;
        return response()->json($result, $statusCode);
    }

    public function storeCorte(Request $request)
    {
        $result = $this->corteService->store($request);
        $statusCode = $result['success'] ? 201 : 422;
        return response()->json($result, $statusCode);
    }

    public function getTiempoCiclo(Request $request)
    {
        $request->validate([
            'tela_id' => 'required|exists:telas,id',
            'maquina_id' => 'required|exists:maquinas,id',
        ]);

        $result = $this->hora->getTiempoCiclo($request->tela_id, $request->maquina_id);
        return response()->json($result);
    }

    public function storeTela(Request $request)
    {
        $result = $this->tela->store($request);
        $statusCode = $result['success'] ? 201 : 422;
        return response()->json($result, $statusCode);
    }

    public function searchTelas(Request $request)
    {
        $query = $request->get('q', '');
        $telas = $this->tela->search($query);
        return response()->json(['telas' => $telas]);
    }

    public function storeMaquina(Request $request)
    {
        $result = $this->maquina->store($request);
        $statusCode = $result['success'] ? 201 : 422;
        return response()->json($result, $statusCode);
    }

    public function searchMaquinas(Request $request)
    {
        $query = $request->get('q', '');
        $maquinas = $this->maquina->search($query);
        return response()->json(['maquinas' => $maquinas]);
    }

    public function searchOperarios(Request $request)
    {
        $query = $request->get('q', '');
        $operarios = $this->operario->search($query);
        return response()->json(['operarios' => $operarios]);
    }

    public function storeOperario(Request $request)
    {
        $result = $this->operario->store($request);
        $statusCode = $result['success'] ? 201 : 422;
        return response()->json($result, $statusCode);
    }

    public function getDashboardCorteData(Request $request)
    {
        $result = $this->dashboardService->getDashboardCorteData($request);
        return response()->json($result);
    }

    public function getDashboardTablesData(Request $request)
    {
        $result = $this->dashboardService->getDashboardTablesData($request);
        return response()->json($result);
    }

    public function getSeguimientoData(Request $request)
    {
        $result = $this->dashboardService->getSeguimientoData($request);
        $statusCode = $result['success'] ? 200 : 500;
        return response()->json($result['data'] ?? $result, $statusCode);
    }

    /**
     * Crear o buscar operario por nombre
     */
    public function findOrCreateOperario(Request $request)
    {
        $result = $this->operario->findOrCreate($request->input('name'));
        return response()->json($result);
    }

    /**
     * Crear o buscar máquina por nombre
     */
    public function findOrCreateMaquina(Request $request)
    {
        $result = $this->maquina->findOrCreate($request->input('nombre'));
        return response()->json($result);
    }

    /**
     * Cargar solo una sección específica (OPTIMIZACIÓN AJAX)
     */

    /**
     * Crear o buscar tela por nombre
     */
    public function findOrCreateTela(Request $request)
    {
        $result = $this->tela->findOrCreate($request->input('nombre'));
        return response()->json($result);
    }

    /**
     * Obtener valores únicos de una columna para los filtros
     */
    public function getUniqueValues(Request $request)
    {
        $result = $this->dashboardService->getUniqueValues($request);
        $statusCode = $result['success'] ? 200 : 400;
        return response()->json(['values' => $result['values']], $statusCode);
    }

    public function findHoraId(Request $request)
    {
        $request->validate([
            'hora' => 'required|string',
        ]);

        $result = $this->hora->findOrCreate($request->hora);
        return response()->json($result);
    }
}
