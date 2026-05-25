<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TalleresController extends Controller
{
    public function index(Request $request, \App\Application\Talleres\UseCases\ObtenerListadoTalleresUseCase $useCase)
    {
        $search = $request->input('search');
        $view = $request->input('view', 'talleres');
        $status = $request->input('status', 'activos');
        
        // Solo cargar talleres si estamos en la vista de talleres
        if ($view === 'talleres') {
            $activoVal = ($status === 'inactivos') ? 0 : 1;
            $talleres = $useCase->execute($search, 9, $activoVal);
        } else {
            $talleres = collect(); // Colección vacía
        }
        
        return view('admin.talleres.index', compact('talleres', 'search', 'view', 'status'));
    }

    public function showRecibos($id, \App\Application\Talleres\UseCases\ObtenerDashboardTallerUseCase $useCase)
    {
        $data = $useCase->execute($id);

        return view('admin.talleres.show', [
            'taller' => $data['taller'],
            'recibos' => $data['recibos'],
            'totalCarga' => $data['total'],
            'completados' => $data['completados']
        ]);
    }

    public function showEntregas($taller_id, $recibo_id, $es_parcial, \App\Application\Talleres\UseCases\ObtenerDetalleEntregasUseCase $useCase)
    {
        $isParcial = $es_parcial == '1';
        $data = $useCase->execute($taller_id, $recibo_id, $isParcial);

        if (!$data) {
            abort(404, 'Recibo no encontrado');
        }

        return view('admin.talleres.entregas', [
            'taller' => $data['taller'],
            'recibo' => $data['recibo'],
            'entregasAgrupadas' => $data['entregasAgrupadas'],
            'totalGeneral' => $data['totalGeneral']
        ]);
    }

    // API endpoints para SPA
    public function apiSearch(Request $request, \App\Application\Talleres\UseCases\ObtenerListadoTalleresUseCase $useCase)
    {
        try {
            $search = $request->input('search', '');
            $perPage = $request->input('per_page', 15);
            $status = $request->input('status', 'activos');
            $activoVal = ($status === 'inactivos') ? 0 : 1;

            $talleres = $useCase->execute($search, $perPage, $activoVal);

            return response()->json([
                'success' => true,
                'data' => $talleres->items(),
                'pagination' => [
                    'current_page' => $talleres->currentPage(),
                    'last_page' => $talleres->lastPage(),
                    'per_page' => $talleres->perPage(),
                    'total' => $talleres->total(),
                    'from' => $talleres->firstItem(),
                    'to' => $talleres->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en apiSearch: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al buscar talleres'
            ], 500);
        }
    }

    public function apiRecibos($id, \App\Application\Talleres\UseCases\ObtenerDashboardTallerUseCase $useCase)
    {
        $data = $useCase->execute($id);

        return response()->json([
            'taller_id' => $id,
            'taller_name' => $data['taller']->name,
            'recibos' => $data['recibos'],
            'total' => $data['total'],
            'completados' => $data['completados'],
            'pendientes' => $data['pendientes']
        ]);
    }

    public function apiEntregas($taller_id, $recibo_id, $es_parcial, \App\Application\Talleres\UseCases\ObtenerDetalleEntregasUseCase $useCase)
    {
        $isParcial = $es_parcial == '1';
        $data = $useCase->execute($taller_id, $recibo_id, $isParcial);

        if (!$data) {
            return response()->json(['error' => 'Recibo no encontrado'], 404);
        }

        // Transformar a array para JSON (quitar objetos Carbon)
        $entregasFormateadas = $data['entregasAgrupadas']->map(function ($grupo) {
            return $grupo->map(function ($item) {
                unset($item['fecha_obj']);
                return $item;
            })->values();
        })->values();

        return response()->json([
            'recibo' => $data['recibo'],
            'entregas' => $entregasFormateadas,
            'total' => $data['totalGeneral']
        ]);
    }

    public function apiOrdenes(
        Request $request,
        \App\Application\Talleres\UseCases\ObtenerOrdenesAsignadasUseCase $useCase
    ) {
        try {
            $search = $request->input('search', '');
            $page = $request->input('page', 1);

            $resultado = $useCase->execute($search, $page);

            return response()->json($resultado);
        } catch (\Exception $e) {
            \Log::error('Error en apiOrdenes: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'error' => 'Error al cargar las órdenes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function apiReciboCompleto(Request $request)
    {
        try {
            $numeroRecibo = trim((string) $request->query('numero_recibo', ''));
            $tipoRecibo = strtoupper(trim((string) $request->query('tipo_recibo', '')));

            if ($numeroRecibo === '' || $tipoRecibo === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Parámetros incompletos'
                ], 422);
            }

            // CORTE-PARA-BODEGA: resolver por consecutivo base
            if ($tipoRecibo === 'CORTE-PARA-BODEGA') {
                $prendaBodegaId = DB::table('consecutivos_recibos_pedidos')
                    ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
                    ->where('consecutivo_actual', $numeroRecibo)
                    ->orderByDesc('id')
                    ->value('prenda_bodega_id');

                if (!$prendaBodegaId) {
                    return response()->json(['success' => false, 'message' => 'Recibo no encontrado'], 404);
                }

                $prenda = DB::table('prenda_bodega')->where('id', $prendaBodegaId)->first();
                if (!$prenda) {
                    return response()->json(['success' => false, 'message' => 'Prenda no encontrada'], 404);
                }

                $tallas = DB::table('prenda_tallas_bodega')
                    ->where('prenda_bodega_id', $prendaBodegaId)
                    ->get(['talla', 'genero', 'color', 'cantidad']);

                $fecha = Carbon::parse($prenda->created_at);
                return response()->json([
                    'success' => true,
                    'tipo_recibo' => 'CORTE-PARA-BODEGA',
                    'numero_recibo' => (float) $numeroRecibo,
                    'descripcion' => $prenda->descripcion ?? '',
                    'dia' => $fecha->format('d'),
                    'mes' => $fecha->format('m'),
                    'ano' => $fecha->format('Y'),
                    'tallas' => $tallas->map(fn($t) => [
                        'talla' => $t->talla,
                        'genero' => $t->genero,
                        'color' => $t->color,
                        'cantidad' => (int) $t->cantidad,
                    ])->toArray(),
                    'total' => (int) $tallas->sum('cantidad'),
                ]);
            }

            // COSTURA: resolver por consecutivo base
            $reciboBase = DB::table('consecutivos_recibos_pedidos')
                ->where('tipo_recibo', 'COSTURA')
                ->where('consecutivo_actual', $numeroRecibo)
                ->orderByDesc('id')
                ->first();

            if (!$reciboBase || !$reciboBase->prenda_id) {
                return response()->json(['success' => false, 'message' => 'Recibo de costura no encontrado'], 404);
            }

            $prenda = DB::table('prendas_pedido')->where('id', $reciboBase->prenda_id)->first();
            $tallasColor = DB::table('prenda_pedido_tallas as ppt')
                ->leftJoin('prenda_pedido_talla_colores as ppc', 'ppc.prenda_pedido_talla_id', '=', 'ppt.id')
                ->where('ppt.prenda_pedido_id', $reciboBase->prenda_id)
                ->get([
                    'ppt.talla',
                    'ppt.genero',
                    DB::raw('COALESCE(ppc.color_nombre, "") as color'),
                    DB::raw('COALESCE(ppc.cantidad, ppt.cantidad) as cantidad')
                ]);

            $fecha = Carbon::parse($reciboBase->created_at);
            return response()->json([
                'success' => true,
                'tipo_recibo' => 'COSTURA',
                'numero_recibo' => (float) $numeroRecibo,
                'descripcion' => $prenda->descripcion ?? ($prenda->nombre_prenda ?? ''),
                'dia' => $fecha->format('d'),
                'mes' => $fecha->format('m'),
                'ano' => $fecha->format('Y'),
                'tallas' => $tallasColor->map(fn($t) => [
                    'talla' => $t->talla,
                    'genero' => $t->genero,
                    'color' => $t->color,
                    'cantidad' => (int) $t->cantidad,
                ])->toArray(),
                'total' => (int) $tallasColor->sum('cantidad'),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error en apiReciboCompleto: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recibo completo'
            ], 500);
        }
    }

    public function toggleStatus($id, \App\Application\Talleres\UseCases\ToggleEstadoTallerUseCase $useCase)
    {
        $result = $useCase->execute($id);
        return response()->json($result);
    }

    public function actualizarPrecio(Request $request, $id)
    {
        $request->validate([
            'precio' => 'required|numeric|min:0'
        ]);

        $entrega = \App\Models\EntregaReciboCostura::findOrFail($id);
        $entrega->precio = $request->precio;
        $entrega->save();

        return response()->json(['success' => true]);
    }

    public function store(Request $request, \App\Application\Talleres\UseCases\CrearTallerUseCase $useCase)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $result = $useCase->execute($request->all());

        return response()->json($result);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $user = \App\Models\User::findOrFail($id);
        $user->name = $request->name;
        $user->save();

        return response()->json(['success' => true, 'message' => 'Taller actualizado correctamente.']);
    }
}
