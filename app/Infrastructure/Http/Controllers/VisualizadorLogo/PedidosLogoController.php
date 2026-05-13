<?php

namespace App\Infrastructure\Http\Controllers\VisualizadorLogo;

use App\Application\PedidosLogo\UseCases\GuardarAreaNovedadPedidoLogoUseCase;
use App\Application\PedidosLogo\UseCases\ListPedidosLogoUseCase;
use App\Http\Controllers\Controller;
use App\Models\PrendaReciboCompletado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class PedidosLogoController extends Controller
{
    public function __construct(
        private ListPedidosLogoUseCase $listPedidosLogoUseCase,
        private GuardarAreaNovedadPedidoLogoUseCase $guardarAreaNovedadPedidoLogoUseCase
    ) {}

    public function data(Request $request): JsonResponse
    {
        $search = $request->filled('search') ? (string) $request->get('search') : null;
        $filtro = (string) $request->get('filtro', 'bordado');
        $page = (int) $request->get('page', 1);
        
        // Obtener filtros de columnas del request
        $columnFilters = null;
        if ($request->filled('filters')) {
            $filters = $request->get('filters');
            if (is_string($filters)) {
                $columnFilters = json_decode($filters, true);
            } elseif (is_array($filters)) {
                $columnFilters = $filters;
            }
        }

        $incluirEntregados = $request->boolean('incluir_entregados', false);
        $recibos = $this->listPedidosLogoUseCase->execute($search, $filtro, 20, $columnFilters, $incluirEntregados);

        return response()->json([
            'success' => true,
            'recibos' => $recibos,
        ]);
    }

    public function guardarAreaNovedad(Request $request): JsonResponse
    {
        $result = $this->guardarAreaNovedadPedidoLogoUseCase->execute($request->all());

        if (!($result['ok'] ?? false)) {
            $status = (int) ($result['status'] ?? 422);
            if (isset($result['errors'])) {
                return response()->json([
                    'success' => false,
                    'errors' => $result['errors'],
                ], $status);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Error de validación.',
            ], $status);
        }

        return response()->json($result['data'], 200);
    }

    /**
     * Obtener todas las áreas únicas disponibles para filtrar
     */
    public function obtenerAreasUnicas(Request $request): JsonResponse
    {
        $filtro = (string) $request->get('filtro', 'bordado');
        
        $areas = $this->listPedidosLogoUseCase->obtenerAreasUnicas($filtro);

        return response()->json([
            'success' => true,
            'areas' => $areas,
        ]);
    }

    /**
     * Obtener todas las asesoras únicas disponibles para filtrar
     */
    public function obtenerAsesorasUnicas(Request $request): JsonResponse
    {
        $filtro = (string) $request->get('filtro', 'bordado');
        
        $asesoras = $this->listPedidosLogoUseCase->obtenerAsesorasUnicas($filtro);

        return response()->json([
            'success' => true,
            'asesoras' => $asesoras,
        ]);
    }

    /**
     * Buscar valores en una columna (para la búsqueda del modal)
     */
    public function buscarValoresColumna(Request $request): JsonResponse
    {
        $columna = (string) $request->get('columna', '');
        $busqueda = (string) $request->get('busqueda', '');
        $filtro = (string) $request->get('filtro', 'bordado');

        if (empty($columna)) {
            return response()->json([
                'success' => false,
                'message' => 'Columna es requerida',
            ], 400);
        }

        $valores = $this->listPedidosLogoUseCase->buscarValoresColumna($columna, $busqueda, $filtro);

        return response()->json([
            'success' => true,
            'valores' => $valores,
        ]);
    }

    /**
     * Obtener conteos de pedidos pendientes por tipo de proceso
     */
    public function obtenerConteosPendientes(Request $request): JsonResponse
    {
        $conteos = [];

        $tiposProcesoMap = [
            'bordado' => 2,
            'estampado' => 3,
            'dtf' => 4,
            'sublimado' => 5,
        ];

        foreach ($tiposProcesoMap as $nombre => $tipoProcesoId) {
            $ultimasAreas = DB::table('prenda_areas_logo_pedido as p1')
                ->select('p1.proceso_prenda_detalle_id', DB::raw('MAX(p1.id) as max_id'))
                ->whereNull('p1.pedido_parcial_id')
                ->groupBy('p1.proceso_prenda_detalle_id');

            $cantidad = DB::table('pedidos_procesos_prenda_detalles as ppd')
                ->leftJoinSub($ultimasAreas, 'ultima_area', function ($join) {
                    $join->on('ultima_area.proceso_prenda_detalle_id', '=', 'ppd.id');
                })
                ->leftJoin('prenda_areas_logo_pedido as palp', 'palp.id', '=', 'ultima_area.max_id')
                ->where('ppd.tipo_proceso_id', $tipoProcesoId)
                ->where('ppd.estado', 'APROBADO')
                ->where(function ($query) {
                    $query->where('palp.area', 'PENDIENTE')
                        ->orWhereNull('palp.id');
                })
                ->distinct('ppd.id')
                ->count('ppd.id');

            $conteos[$nombre] = $cantidad;
        }

        return response()->json([
            'success' => true,
            'conteos' => $conteos,
        ]);
    }

    /**
     * Marcar un recibo como completado por el bordador
     */
    public function marcarCompletado(Request $request): JsonResponse
    {
        $request->validate([
            'id_recibo' => 'required|integer',
            'numero_recibo' => 'required|integer',
            'consecutivo_recibo_id' => 'nullable|integer|min:1',
        ]);

        $user = Auth::user();
        $area = 'BORDANDO'; // Para bordador, el área es siempre BORDANDO

        // Verificar si ya existe
        $existente = PrendaReciboCompletado::where('id_recibo', $request->id_recibo)
            ->where('area', $area)
            ->first();

        if ($existente) {
            // Si ya existe, eliminarlo (deshacer completado)
            $existente->delete();
            
            // También revertir el área a BORDANDO
            $this->guardarAreaNovedadPedidoLogoUseCase->execute([
                'proceso_prenda_detalle_id' => $request->id_recibo,
                'area' => 'BORDANDO',
                'novedades' => null,
                'pedido_parcial_id' => null,
                'consecutivo_recibo_id' => $request->input('consecutivo_recibo_id'),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Completado deshecho.',
                'completado' => false,
            ]);
        }

        // Crear nuevo registro de completado
        $completado = PrendaReciboCompletado::create([
            'id_recibo' => $request->id_recibo,
            'numero_recibo' => $request->numero_recibo,
            'area' => $area,
            'nombre_operario' => $user->name ?? 'Bordador',
            'fecha_completado' => now(),
        ]);

        // Actualizar el área a BORDADO
        $areaResult = $this->guardarAreaNovedadPedidoLogoUseCase->execute([
            'proceso_prenda_detalle_id' => $request->id_recibo,
            'area' => 'BORDADO',
            'novedades' => 'Completado por bordador: ' . ($user->name ?? 'Bordador'),
            'pedido_parcial_id' => null,
            'consecutivo_recibo_id' => $request->input('consecutivo_recibo_id'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recibo marcado como completado y área actualizada a BORDADO.',
            'completado' => true,
            'data' => $completado,
            'area_actualizada' => $areaResult['ok'] ?? false,
        ]);
    }
}
