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
            'reflectivo' => 1,
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
        \Log::info('[marcarCompletado] Iniciando con request:', $request->all());

        $request->validate([
            'id_recibo' => 'required|integer',
            'numero_recibo' => 'required|integer',
            'consecutivo_recibo_id' => 'nullable|integer|min:1',
        ]);

        $user = Auth::user();
        $area = 'BORDANDO'; // Para bordador, el área es siempre BORDANDO

        $idRecibo = (int) $request->id_recibo;
        $numeroRecibo = (int) $request->numero_recibo;
        $consecutivoReciboId = $request->input('consecutivo_recibo_id') ? (int) $request->input('consecutivo_recibo_id') : null;

        \Log::info('[marcarCompletado] Parámetros procesados:', compact('idRecibo', 'numeroRecibo', 'consecutivoReciboId', 'area'));

        // Verificar si ya existe registro de completado
        $existente = PrendaReciboCompletado::where('id_recibo', $idRecibo)
            ->where('area', $area)
            ->first();

        \Log::info('[marcarCompletado] Registro de completado existente:', ['existe' => !!$existente]);

        if ($existente) {
            \Log::info('[marcarCompletado] Ya estaba completado, deshaciendo...');
            // Si ya existe, eliminarlo (deshacer completado)
            $existente->delete();
            
            // Revertir el área a BORDANDO en prenda_areas_logo_pedido
            DB::table('prenda_areas_logo_pedido')
                ->where('proceso_prenda_detalle_id', $idRecibo)
                ->whereNull('pedido_parcial_id')
                ->update([
                    'area' => 'BORDANDO',
                    'updated_at' => now(),
                ]);

            \Log::info('[marcarCompletado] Área revertida a BORDANDO en BD');
            
            return response()->json([
                'success' => true,
                'message' => 'Completado deshecho.',
                'completado' => false,
            ]);
        }

        // Crear nuevo registro de completado
        $completado = PrendaReciboCompletado::create([
            'id_recibo' => $idRecibo,
            'numero_recibo' => $numeroRecibo,
            'area' => $area,
            'nombre_operario' => $user->name ?? 'Bordador',
            'fecha_completado' => now(),
        ]);

        \Log::info('[marcarCompletado] Registro completado creado:', $completado->toArray());

        // Actualizar DIRECTAMENTE el área a BORDADO en prenda_areas_logo_pedido (sin upsert)
        $updated = DB::table('prenda_areas_logo_pedido')
            ->where('proceso_prenda_detalle_id', $idRecibo)
            ->whereNull('pedido_parcial_id')
            ->update([
                'area' => 'BORDADO',
                'novedades' => 'Completado por bordador: ' . ($user->name ?? 'Bordador'),
                'updated_at' => now(),
            ]);

        \Log::info('[marcarCompletado] Filas actualizadas en prenda_areas_logo_pedido:', ['updated_count' => $updated]);

        return response()->json([
            'success' => true,
            'message' => 'Recibo marcado como completado y área actualizada a BORDADO.',
            'completado' => true,
            'data' => $completado,
            'rows_updated' => $updated,
        ]);
    }
}
