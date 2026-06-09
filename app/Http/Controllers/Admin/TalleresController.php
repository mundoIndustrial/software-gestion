<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class TalleresController extends Controller
{
    public function index(Request $request, \App\Application\Talleres\UseCases\ObtenerListadoTalleresUseCase $useCase)
    {
        $search = $request->input('search');
        $view = $request->input('view', 'talleres');
        $status = $request->input('status', 'activos');
        if ($view === 'talleres') {
            $activoVal = ($status === 'inactivos') ? 0 : 1;
            $talleres = $useCase->execute($search, 9, $activoVal);
        } else {
            $talleres = collect(); // Colección vacía
        }
        
        return view('admin.talleres.index', compact(
            'talleres',
            'search',
            'view',
            'status'
        ));
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
        $tab = $request->query('tab', $request->query('tipo', 'insumos'));
        $tab = $this->normalizarTabPrestamoGlobal($tab);
        $search = $request->query('search', '');
        $perPage = 15;
        $userId = auth()->id();
        $revisadosIds = $this->obtenerPrestamosGlobalRevisadosIds($userId, $tab);

        if ($tab === 'insumos') {
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
            
            $registros = $query->orderByDesc('created_at')->paginate($perPage)->appends(['tab' => $tab, 'search' => $search]);
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
            
            $registros = $query->orderByDesc('created_at')->paginate($perPage)->appends(['tab' => $tab, 'search' => $search]);
        }

        return view('admin.talleres.prestamos-global', [
            'tab' => $tab,
            'registros' => $registros,
            'revisadosIds' => $revisadosIds,
        ]);
    }

    public function apiPrestamosGlobal(Request $request)
    {
        try {
            $tab = $request->query('tab', $request->query('tipo', 'insumos'));
            $tab = $this->normalizarTabPrestamoGlobal($tab);
            $search = $request->query('search', '');
            $page = $request->query('page', 1);
            $perPage = 15;
            $userId = auth()->id();
            $revisadosIds = $this->obtenerPrestamosGlobalRevisadosIds($userId, $tab);

            if ($tab === 'insumos') {
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
                    $html .= $this->renderPrestamoGlobalRow($r, $tab, $revisadosIds);
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

    public function marcarPrestamoGlobalVisto(Request $request)
    {
        try {
            $validated = $request->validate([
                'tab' => ['required', 'string'],
                'id' => ['required', 'integer'],
                'checked' => ['required', 'boolean'],
            ]);

            $tab = $this->normalizarTabPrestamoGlobal($validated['tab']);
            $userId = auth()->id();
            $reciboId = (int) $validated['id'];
            $checked = (bool) $validated['checked'];

            $query = DB::table('prestamos_global_vistos')
                ->where('user_id', $userId)
                ->where('tab', $tab)
                ->where('recibo_id', $reciboId);

            if ($checked) {
                $exists = $query->exists();

                if (!$exists) {
                    DB::table('prestamos_global_vistos')->insert([
                        'user_id' => $userId,
                        'tab' => $tab,
                        'recibo_id' => $reciboId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $query->update(['updated_at' => now()]);
                }
            } else {
                $query->delete();
            }

            return response()->json([
                'success' => true,
                'checked' => $checked,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en marcarPrestamoGlobalVisto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el estado de revisión',
            ], 500);
        }
    }

    private function normalizarTabPrestamoGlobal(string $tab): string
    {
        $tab = in_array($tab, ['insumos', 'contramuestra', 'contramuestras'], true) ? $tab : 'insumos';

        return $tab === 'contramuestras' ? 'contramuestra' : $tab;
    }

    private function obtenerPrestamosGlobalRevisadosIds(?int $userId, string $tab): array
    {
        if (!$userId) {
            return [];
        }

        return DB::table('prestamos_global_vistos')
            ->where('user_id', $userId)
            ->where('tab', $tab)
            ->pluck('recibo_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function renderPrestamoGlobalRow(object $r, string $tab, array $revisadosIds): string
    {
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

        $isRevisado = in_array((int) $r->id, $revisadosIds, true);
        $fechaSalida = \Carbon\Carbon::parse($r->fecha_salida)->format('d/m/Y h:i A');
        $fechaEntrada = $r->fecha_entrada ? \Carbon\Carbon::parse($r->fecha_entrada)->format('d/m/Y h:i A') : '-';
        $checked = $isRevisado ? 'checked' : '';
        $revisadoLabelStyle = $isRevisado
            ? 'background:#dcfce7;color:#166534;border-color:#86efac;'
            : 'background:#fff;color:#64748b;border-color:#cbd5e1;';

        return '<tr data-prestamo-id="' . $r->id . '">
            <td style="padding:10px;border-bottom:1px solid #f1f5f9;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:nowrap;white-space:nowrap;">
                    <label title="Marcar como revisado" style="display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border:1px solid ' . ($isRevisado ? '#86efac' : '#cbd5e1') . ';border-radius:8px;cursor:pointer;user-select:none;' . $revisadoLabelStyle . '">
                        <input type="checkbox" class="prestamo-visto-toggle" data-tab="' . $tab . '" data-id="' . $r->id . '" ' . $checked . ' style="margin:0;cursor:pointer;">
                        <span class="material-symbols-rounded" style="font-size:18px;line-height:1;">done</span>
                    </label>
                    <button type="button" class="btn-ver-prestamo" data-tipo="' . $tab . '" data-id="' . $r->id . '" style="background:#3b82f6;color:#fff;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;">Ver</button>
                </div>
            </td>
            <td style="padding:10px;border-bottom:1px solid #f1f5f9;">#' . e($r->numero_orden) . '</td>
            <td style="padding:10px;border-bottom:1px solid #f1f5f9;">' . e($r->nombre_costurero) . '</td>
            <td style="padding:10px;border-bottom:1px solid #f1f5f9;">' . e($fechaSalida) . '</td>
            <td style="padding:10px;border-bottom:1px solid #f1f5f9;">' . e($fechaEntrada) . '</td>
            <td style="padding:10px;border-bottom:1px solid #f1f5f9;"><span style="' . $estadoClass . 'padding:4px 8px;border-radius:4px;font-size:12px;font-weight:600;">' . e($estadoTexto) . '</span></td>
            <td style="padding:10px;border-bottom:1px solid #f1f5f9;">' . e($r->novedades ?: '-') . '</td>
        </tr>';
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
            $reciboId = (int) $request->query('recibo_id', 0);
            $numeroRecibo = trim((string) $request->query('numero_recibo', ''));
            $tipoRecibo = strtoupper(trim((string) $request->query('tipo_recibo', '')));
            $pedidoProduccionId = (int) $request->query('pedido_produccion_id', 0);
            $prendaId = (int) $request->query('prenda_id', 0);

            if ($numeroRecibo === '' || $tipoRecibo === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Parámetros incompletos'
                ], 422);
            }

            if (!in_array($tipoRecibo, ['COSTURA', 'CORTE-PARA-BODEGA'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El recibo solicitado no está disponible en esta vista'
                ], 422);
            }

            // CORTE-PARA-BODEGA: resolver por consecutivo base
            if ($tipoRecibo === 'CORTE-PARA-BODEGA') {
                $reciboBodegaQuery = DB::table('consecutivos_recibos_pedidos')
                    ->where('tipo_recibo', 'CORTE-PARA-BODEGA');

                if ($reciboId > 0) {
                    $reciboBodegaQuery->where('id', $reciboId);
                } else {
                    $reciboBodegaQuery->where('consecutivo_actual', $numeroRecibo);
                }

                if ($pedidoProduccionId > 0) {
                    $reciboBodegaQuery->where('pedido_produccion_id', $pedidoProduccionId);
                }

                if ($prendaId > 0) {
                    $reciboBodegaQuery->where('prenda_id', $prendaId);
                }

                $prendaBodegaId = $reciboBodegaQuery->orderByDesc('id')->value('prenda_bodega_id');

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

            // COSTURA / REFLECTIVO / otros: resolver por tipo real y, si existe, por ID exacto
            $reciboBaseQuery = DB::table('consecutivos_recibos_pedidos')
                ->where('tipo_recibo', $tipoRecibo);

            if ($reciboId > 0) {
                $reciboBaseQuery->where('id', $reciboId);
            } else {
                $reciboBaseQuery->where('consecutivo_actual', $numeroRecibo);
            }

            if ($pedidoProduccionId > 0) {
                $reciboBaseQuery->where('pedido_produccion_id', $pedidoProduccionId);
            }

            if ($prendaId > 0) {
                $reciboBaseQuery->where('prenda_id', $prendaId);
            }

            $reciboBase = $reciboBaseQuery->orderByDesc('id')->first();

            if (!$reciboBase || !$reciboBase->prenda_id) {
                return response()->json(['success' => false, 'message' => 'Recibo no encontrado'], 404);
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

}
