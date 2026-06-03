<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\User;
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

    public function showPrestamos(Request $request, $id)
    {
        $taller = User::findOrFail((int) $id);
        $tab = $request->query('tab', 'insumos');
        $tab = in_array($tab, ['insumos', 'contramuestra'], true) ? $tab : 'insumos';
        $perPage = 15;

        if ($tab === 'insumos') {
            $registros = DB::table('recibos_prestamo_insumos')
                ->select(
                    'id',
                    'numero_orden',
                    'nombre_costurero',
                    'created_at as fecha_salida',
                    'confirmado_entrada_en as fecha_entrada',
                    'confirmado_entrada',
                    'anulado',
                    'novedades'
                )
                ->where('nombre_costurero', $taller->name)
                ->orderByDesc('id')
                ->paginate($perPage)
                ->appends(['tab' => $tab]);
        } else {
            $registros = DB::table('recibos_prestamo_contramuestra')
                ->select(
                    'id',
                    'numero_orden',
                    'nombre_costurero',
                    'created_at as fecha_salida',
                    'confirmado_entrada_en as fecha_entrada',
                    'confirmado_entrada',
                    'anulado',
                    'novedades'
                )
                ->where('nombre_costurero', $taller->name)
                ->orderByDesc('id')
                ->paginate($perPage)
                ->appends(['tab' => $tab]);
        }

        return view('admin.talleres.prestamos', [
            'taller' => $taller,
            'tab' => $tab,
            'registros' => $registros,
        ]);
    }

    public function showPrestamosGlobal(Request $request)
    {
        $tipo = $request->query('tipo', 'insumos');
        $tipo = in_array($tipo, ['insumos', 'contramuestras'], true) ? $tipo : 'insumos';
        $search = $request->query('search', '');
        $perPage = 15;

        if ($tipo === 'insumos') {
            $query = DB::table('recibos_prestamo_insumos')
                ->select(
                    'id',
                    'numero_orden',
                    'nombre_costurero',
                    'created_at as fecha_salida',
                    'confirmado_entrada_en as fecha_entrada',
                    'confirmado_entrada',
                    'anulado',
                    'novedades'
                )
                ->where('confirmado_entrada', 1);
            
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('numero_orden', 'LIKE', "%{$search}%")
                      ->orWhere('nombre_costurero', 'LIKE', "%{$search}%");
                });
            }
            
            $registros = $query->orderByDesc('fecha')->paginate($perPage)->appends(['tipo' => $tipo, 'search' => $search]);
        } else {
            $query = DB::table('recibos_prestamo_contramuestra')
                ->select(
                    'id',
                    'numero_orden',
                    'nombre_costurero',
                    'created_at as fecha_salida',
                    'confirmado_entrada_en as fecha_entrada',
                    'confirmado_entrada',
                    'anulado',
                    'novedades'
                )
                ->where('confirmado_entrada', 1);
            
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('numero_orden', 'LIKE', "%{$search}%")
                      ->orWhere('nombre_costurero', 'LIKE', "%{$search}%");
                });
            }
            
            $registros = $query->orderByDesc('created_at')->paginate($perPage)->appends(['tipo' => $tipo, 'search' => $search]);
        }

        return view('admin.talleres.prestamos-global', [
            'tipo' => $tipo,
            'registros' => $registros,
        ]);
    }

    public function apiPrestamosGlobal(Request $request)
    {
        try {
            $tipo = $request->query('tipo', 'insumos');
            $tipo = in_array($tipo, ['insumos', 'contramuestras'], true) ? $tipo : 'insumos';
            $search = $request->query('search', '');
            $page = $request->query('page', 1);
            $perPage = 15;

            if ($tipo === 'insumos') {
                $query = DB::table('recibos_prestamo_insumos')
                    ->select(
                        'id',
                        'numero_orden',
                        'nombre_costurero',
                        'created_at as fecha_salida',
                        'confirmado_entrada_en as fecha_entrada',
                        'confirmado_entrada',
                        'anulado',
                        'novedades'
                    )
                    ->where('confirmado_entrada', 1);
                
                if (!empty($search)) {
                    $query->where(function($q) use ($search) {
                        $q->where('numero_orden', 'LIKE', "%{$search}%")
                          ->orWhere('nombre_costurero', 'LIKE', "%{$search}%");
                    });
                }
                
                $registros = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
            } else {
                $query = DB::table('recibos_prestamo_contramuestra')
                    ->select(
                        'id',
                        'numero_orden',
                        'nombre_costurero',
                        'created_at as fecha_salida',
                        'confirmado_entrada_en as fecha_entrada',
                        'confirmado_entrada',
                        'anulado',
                        'novedades'
                    )
                    ->where('confirmado_entrada', 1);
                
                if (!empty($search)) {
                    $query->where(function($q) use ($search) {
                        $q->where('numero_orden', 'LIKE', "%{$search}%")
                          ->orWhere('nombre_costurero', 'LIKE', "%{$search}%");
                    });
                }
                
                $registros = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
            }

            $html = '';
            if ($registros->isEmpty()) {
                $html = '<tr><td colspan="7" style="padding:16px;text-align:center;color:#64748b;">Sin registros de préstamos confirmados.</td></tr>';
            } else {
                foreach ($registros as $r) {
                    $estadoClass = '';
                    $estadoTexto = '';
                    if ($r->anulado) {
                        $estadoClass = 'background:#fee2e2;color:#dc2626;';
                        $estadoTexto = 'ANULADO';
                    } elseif ($r->confirmado_entrada) {
                        $estadoClass = 'background:#dcfce7;color:#16a34a;';
                        $estadoTexto = 'CONFIRMADO';
                    } else {
                        $estadoClass = 'background:#fef3c7;color:#ea8c55;';
                        $estadoTexto = 'PENDIENTE';
                    }
                    
                    $html .= '<tr>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">
                            <button type="button" class="btn-ver-prestamo" data-tipo="' . $tipo . '" data-id="' . $r->id . '" style="background:#3b82f6;color:#fff;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;">Ver</button>
                        </td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">#' . $r->numero_orden . '</td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">' . $r->nombre_costurero . '</td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">' . \Carbon\Carbon::parse($r->fecha_salida)->format('d/m/Y h:i A') . '</td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">' . ($r->fecha_entrada ? \Carbon\Carbon::parse($r->fecha_entrada)->format('d/m/Y h:i A') : '-') . '</td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;"><span style="' . $estadoClass . 'padding:4px 8px;border-radius:4px;font-size:12px;font-weight:600;">' . $estadoTexto . '</span></td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">' . ($r->novedades ?: '-') . '</td>
                    </tr>';
                }
            }

            return response()->json([
                'success' => true,
                'html' => $html,
                'total' => $registros->total(),
                'current_page' => $registros->currentPage(),
                'last_page' => $registros->lastPage(),
                'pagination_html' => (string) $registros->render('vendor.pagination.simple-clean')
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en apiPrestamosGlobal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al buscar préstamos'
            ], 500);
        }
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

    public function apiDetallePrestamo(string $tipo, int $id)
    {
        if (!in_array($tipo, ['insumos', 'contramuestra'], true)) {
            return response()->json(['success' => false, 'message' => 'Tipo inválido'], 422);
        }

        if ($tipo === 'insumos') {
            $recibo = DB::table('recibos_prestamo_insumos')
                ->leftJoin('users as u', 'u.id', '=', 'recibos_prestamo_insumos.creado_por')
                ->select(
                    'recibos_prestamo_insumos.id',
                    'recibos_prestamo_insumos.numero_orden',
                    'recibos_prestamo_insumos.fecha',
                    'recibos_prestamo_insumos.nombre_costurero',
                    'recibos_prestamo_insumos.novedades',
                    'recibos_prestamo_insumos.confirmado_entrada_en',
                    'recibos_prestamo_insumos.firma_mensajero',
                    'recibos_prestamo_insumos.firma_costurero',
                    DB::raw('COALESCE(u.name, "-") as encargado')
                )
                ->where('recibos_prestamo_insumos.id', $id)
                ->first();

            if (!$recibo) {
                return response()->json(['success' => false, 'message' => 'Recibo no encontrado'], 404);
            }

            $items = DB::table('recibos_prestamo_insumos_items')
                ->select('cantidad', 'descripcion', 'orden_fila')
                ->where('recibo_prestamo_insumo_id', $id)
                ->orderBy('orden_fila')
                ->get();

            return response()->json([
                'success' => true,
                'tipo' => 'insumos',
                'recibo' => $recibo,
                'items' => $items,
            ]);
        }

        $recibo = DB::table('recibos_prestamo_contramuestra')
            ->leftJoin('users as u', 'u.id', '=', 'recibos_prestamo_contramuestra.creado_por')
            ->select(
                'recibos_prestamo_contramuestra.id',
                'recibos_prestamo_contramuestra.numero_orden',
                'recibos_prestamo_contramuestra.fecha',
                'recibos_prestamo_contramuestra.nombre_costurero',
                'recibos_prestamo_contramuestra.descripcion',
                'recibos_prestamo_contramuestra.novedades',
                'recibos_prestamo_contramuestra.confirmado_entrada_en',
                'recibos_prestamo_contramuestra.firma_mensajero',
                'recibos_prestamo_contramuestra.firma_costurero',
                DB::raw('COALESCE(u.name, "-") as encargado')
            )
            ->where('recibos_prestamo_contramuestra.id', $id)
            ->first();

        if (!$recibo) {
            return response()->json(['success' => false, 'message' => 'Recibo no encontrado'], 404);
        }

        return response()->json([
            'success' => true,
            'tipo' => 'contramuestra',
            'recibo' => $recibo,
            'items' => [],
        ]);
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
