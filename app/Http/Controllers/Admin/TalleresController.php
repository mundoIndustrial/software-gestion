<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class TalleresController extends Controller
{
    public function index(Request $request, \App\Application\Talleres\UseCases\ObtenerListadoTalleresUseCase $useCase)
    {
        $search = $request->input('search');
        $view = $request->input('view', 'talleres');
        $status = $request->input('status', 'activos');
        $selectedTallerName = null;
        if ($view === 'talleres') {
            $activoVal = ($status === 'inactivos') ? 0 : 1;
            $talleres = $useCase->execute($search, 9, $activoVal);
        } else {
            $talleres = collect(); // Coleccion vaci­a
        }

        if ($view === 'recibos' && $request->filled('taller_id')) {
            $selectedTallerName = User::where('id', $request->integer('taller_id'))->value('name');
        }
        
        return view('admin.talleres.index', compact(
            'talleres',
            'search',
            'view',
            'status',
            'selectedTallerName'
        ));
    }

    public function toggleStatus(Request $request, $id, \App\Application\Talleres\UseCases\ToggleEstadoTallerUseCase $useCase)
    {
        try {
            $result = $useCase->execute((int) $id);

            return response()->json($result);
        } catch (\Throwable $e) {
            \Log::error('Error en toggleStatus de talleres: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cambiar el estado del taller',
            ], 500);
        }
    }

    public function store(Request $request, \App\Application\Talleres\UseCases\CrearTallerUseCase $useCase)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            $result = $useCase->execute($validated);

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos invalidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Error en store de talleres: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el taller',
            ], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            $taller = User::findOrFail($id);
            $taller->update([
                'name' => $validated['name'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Taller actualizado correctamente.',
                'taller' => $taller->fresh(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos invalidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Error en update de talleres: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el taller',
            ], 500);
        }
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
        $mostrarPendientes = auth()->user()?->hasRole('visualizador_talleres') === true;
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
                ;
                
                if (!$mostrarPendientes) {
                    $query->where('confirmado_entrada', 1);
                }
            
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
                ;
                
                if (!$mostrarPendientes) {
                    $query->where('confirmado_entrada', 1);
                }
            
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
            'mostrarPendientes' => $mostrarPendientes,
            'currentUserId' => $userId,
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
            $mostrarPendientes = auth()->user()?->hasRole('visualizador_talleres') === true;
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
                    ;
                
                if (!$mostrarPendientes) {
                    $query->where('confirmado_entrada', 1);
                }
                
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
                    ;
                
                if (!$mostrarPendientes) {
                    $query->where('confirmado_entrada', 1);
                }
                
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
                $mensajeVacio = $mostrarPendientes
                    ? 'Sin registros de prestamos.'
                    : 'Sin registros de prestamos confirmados.';
                $html = '<tr><td colspan="7" style="padding:16px;text-align:center;color:#64748b;">' . e($mensajeVacio) . '</td></tr>';
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
                'error' => 'Error al buscar prestamos'
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
                'message' => 'Datos invalidos',
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en marcarPrestamoGlobalVisto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el estado de revision',
            ], 500);
        }
    }

    public function confirmarEntradaPrestamoGlobal(Request $request, string $tipo, int $id)
    {
        try {
            $validated = $request->validate([
                'corresponde' => ['required', 'boolean'],
                'novedades' => ['nullable', 'string'],
                'accion' => ['nullable', 'string', 'in:confirmar,deshacer'],
            ]);

            $accion = $validated['accion'] ?? 'confirmar';

            if ($accion === 'confirmar' && $validated['corresponde'] === false && trim((string) ($validated['novedades'] ?? '')) === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes registrar una novedad cuando no corresponde.',
                ], 422);
            }

            $tab = $this->normalizarTabPrestamoGlobal($tipo);
            $table = $tab === 'insumos'
                ? 'recibos_prestamo_insumos'
                : 'recibos_prestamo_contramuestra';
            $recibo = DB::table($table)
                ->select('id', 'numero_orden')
                ->where('id', $id)
                ->first();

            if (!$recibo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado.',
                ], 404);
            }

            $updateData = [
                'confirmado_entrada' => $accion === 'confirmar',
                'confirmado_entrada_en' => $accion === 'confirmar' ? now() : null,
                'updated_at' => now(),
            ];

            DB::table($table)
                ->where('id', $id)
                ->update($updateData);

            if ($accion === 'confirmar') {
                $usuarioId = $request->user()?->id;
                $usuarioNombre = (string) ($request->user()?->name ?? 'Usuario');
                $textoConfirmacion = $usuarioNombre . ' confirmo la entrada del recibo #' . $recibo->numero_orden;

                DB::table('prestamos_global_novedades')->insert([
                    'tab' => $tab,
                    'recibo_id' => $recibo->id,
                    'usuario_id' => $usuarioId,
                    'novedad' => $textoConfirmacion,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($accion === 'deshacer') {
                $usuarioId = $request->user()?->id;
                $usuarioNombre = (string) ($request->user()?->name ?? 'Usuario');
                $textoDeshacer = $usuarioNombre . ' deshizo la confirmacion de la entrada del recibo #' . $recibo->numero_orden;

                DB::table('prestamos_global_novedades')->insert([
                    'tab' => $tab,
                    'recibo_id' => $recibo->id,
                    'usuario_id' => $usuarioId,
                    'novedad' => $textoDeshacer,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->sincronizarPrestamoGlobalNovedadResumen($tab, $recibo->id);

            return response()->json([
                'success' => true,
                'message' => $accion === 'confirmar'
                    ? 'Entrada confirmada correctamente.'
                    : 'Entrada deshecha correctamente.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos invalidos',
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Error en confirmarEntradaPrestamoGlobal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo confirmar la entrada.',
            ], 500);
        }
    }

    public function obtenerPrestamoGlobalNovedades(Request $request, string $tipo, int $id)
    {
        try {
            $tab = $this->normalizarTabPrestamoGlobal($tipo);
            $table = $tab === 'insumos'
                ? 'recibos_prestamo_insumos'
                : 'recibos_prestamo_contramuestra';

            $exists = DB::table($table)->where('id', $id)->exists();
            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado.',
                ], 404);
            }

            $novedades = DB::table('prestamos_global_novedades as pgn')
                ->leftJoin('users as u', 'u.id', '=', 'pgn.usuario_id')
                ->select([
                    'pgn.id',
                    'pgn.usuario_id',
                    'pgn.novedad',
                    'pgn.created_at',
                    'pgn.updated_at',
                    'u.name as usuario_nombre',
                ])
                ->where('pgn.tab', $tab)
                ->where('pgn.recibo_id', $id)
                ->orderByDesc('pgn.created_at')
                ->get();

            return response()->json([
                'success' => true,
                'novedades' => $novedades,
                'current_user_id' => $request->user()?->id,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error en obtenerPrestamoGlobalNovedades: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudieron cargar las novedades.',
            ], 500);
        }
    }

    public function guardarPrestamoGlobalNovedad(Request $request, string $tipo, int $id)
    {
        try {
            $validated = $request->validate([
                'novedad' => ['required', 'string', 'max:4000'],
            ]);

            $tab = $this->normalizarTabPrestamoGlobal($tipo);
            $table = $tab === 'insumos'
                ? 'recibos_prestamo_insumos'
                : 'recibos_prestamo_contramuestra';

            $exists = DB::table($table)->where('id', $id)->exists();
            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado.',
                ], 404);
            }

            $novedad = trim((string) $validated['novedad']);
            $user = $request->user();
            $now = now();

            DB::table('prestamos_global_novedades')->insert([
                'tab' => $tab,
                'recibo_id' => $id,
                'usuario_id' => $user?->id,
                'novedad' => $novedad,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->sincronizarPrestamoGlobalNovedadResumen($tab, $id);

            return response()->json([
                'success' => true,
                'message' => 'Novedad registrada correctamente.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La novedad es obligatoria.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Error en guardarPrestamoGlobalNovedad: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar la novedad.',
            ], 500);
        }
    }

    public function actualizarPrestamoGlobalNovedad(Request $request, string $tipo, int $id, int $novedadId)
    {
        try {
            $validated = $request->validate([
                'novedad' => ['required', 'string', 'max:4000'],
            ]);

            $tab = $this->normalizarTabPrestamoGlobal($tipo);
            $novedad = DB::table('prestamos_global_novedades')
                ->where('id', $novedadId)
                ->where('tab', $tab)
                ->where('recibo_id', $id)
                ->first();

            if (!$novedad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Novedad no encontrada.',
                ], 404);
            }

            if ((int) $novedad->usuario_id !== (int) ($request->user()?->id ?? 0)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo puedes editar tus propias novedades.',
                ], 403);
            }

            DB::table('prestamos_global_novedades')
                ->where('id', $novedadId)
                ->update([
                    'novedad' => trim((string) $validated['novedad']),
                    'updated_at' => now(),
                ]);

            $this->sincronizarPrestamoGlobalNovedadResumen($tab, $id);

            return response()->json([
                'success' => true,
                'message' => 'Novedad actualizada correctamente.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La novedad es obligatoria.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Error en actualizarPrestamoGlobalNovedad: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar la novedad.',
            ], 500);
        }
    }

    public function eliminarPrestamoGlobalNovedad(Request $request, string $tipo, int $id, int $novedadId)
    {
        try {
            $tab = $this->normalizarTabPrestamoGlobal($tipo);
            $novedad = DB::table('prestamos_global_novedades')
                ->where('id', $novedadId)
                ->where('tab', $tab)
                ->where('recibo_id', $id)
                ->first();

            if (!$novedad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Novedad no encontrada.',
                ], 404);
            }

            if ((int) $novedad->usuario_id !== (int) ($request->user()?->id ?? 0)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo puedes eliminar tus propias novedades.',
                ], 403);
            }

            DB::table('prestamos_global_novedades')
                ->where('id', $novedadId)
                ->delete();

            $this->sincronizarPrestamoGlobalNovedadResumen($tab, $id);

            return response()->json([
                'success' => true,
                'message' => 'Novedad eliminada correctamente.',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error en eliminarPrestamoGlobalNovedad: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar la novedad.',
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

    private function sincronizarPrestamoGlobalNovedadResumen(string $tab, int $reciboId): void
    {
        $table = $tab === 'insumos'
            ? 'recibos_prestamo_insumos'
            : 'recibos_prestamo_contramuestra';

        $ultimaNovedad = DB::table('prestamos_global_novedades')
            ->where('tab', $tab)
            ->where('recibo_id', $reciboId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('novedad');

        DB::table($table)
            ->where('id', $reciboId)
            ->update([
                'novedades' => $ultimaNovedad,
                'updated_at' => now(),
            ]);
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

        $fechaSalida = \Carbon\Carbon::parse($r->fecha_salida)->format('d/m/Y h:i A');
        $fechaEntrada = $r->fecha_entrada ? \Carbon\Carbon::parse($r->fecha_entrada)->format('d/m/Y h:i A') : '-';
        $confirmadoClass = $r->confirmado_entrada ? ' is-confirmed' : '';
        $confirmadoRowStyle = $r->confirmado_entrada ? 'background:#dcfce7;' : '';
        $confirmarTitle = $r->confirmado_entrada ? 'Deshacer entrada' : 'Confirmar entrada';
        $confirmadoAttr = $r->confirmado_entrada ? '1' : '0';

        return '<tr data-prestamo-id="' . $r->id . '" style="' . e($confirmadoRowStyle) . '">
            <td style="padding:10px;border-bottom:1px solid #f1f5f9;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:nowrap;white-space:nowrap;">
                    <button type="button" class="prestamo-confirmar-toggle' . $confirmadoClass . '" data-action="confirmar-entrada" data-tipo="' . $tab . '" data-id="' . $r->id . '" data-confirmed="' . $confirmadoAttr . '" data-url="' . route('talleres.api.prestamos-global.confirmar-entrada', ['tipo' => $tab, 'id' => $r->id]) . '" title="' . e($confirmarTitle) . '">
                        <span class="material-symbols-rounded" style="font-size:18px;line-height:1;">done</span>
                    </button>
                    <button type="button" class="btn-ver-prestamo" data-tipo="' . $tab . '" data-id="' . $r->id . '" style="background:#3b82f6;color:#fff;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;">Ver</button>
                </div>
            </td>
            <td style="padding:10px;border-bottom:1px solid #f1f5f9;">#' . e($r->numero_orden) . '</td>
            <td style="padding:10px;border-bottom:1px solid #f1f5f9;">' . e($r->nombre_costurero) . '</td>
            <td style="padding:10px;border-bottom:1px solid #f1f5f9;">' . e($fechaSalida) . '</td>
            <td style="padding:10px;border-bottom:1px solid #f1f5f9;">' . e($fechaEntrada) . '</td>
            <td style="padding:10px;border-bottom:1px solid #f1f5f9;"><span style="' . $estadoClass . 'padding:4px 8px;border-radius:4px;font-size:12px;font-weight:600;">' . e($estadoTexto) . '</span></td>
            <td style="padding:10px;border-bottom:1px solid #f1f5f9;">
                <button type="button" class="prestamo-novedad-button" data-action="abrir-novedades" data-tipo="' . $tab . '" data-id="' . $r->id . '" data-url="' . route('talleres.api.prestamos-global.novedades', ['tipo' => $tab, 'id' => $r->id]) . '" data-save-url="' . route('talleres.api.prestamos-global.novedades.guardar', ['tipo' => $tab, 'id' => $r->id]) . '"><span class="material-symbols-rounded" aria-hidden="true" style="font-size:18px;">ads_click</span><span>Ver novedades</span></button>
            </td>
        </tr>';
    }

    public function apiDetallePrestamo(string $tipo, int $id)
    {
        try {
            $tipoNormalizado = strtolower(trim($tipo));

            if (!in_array($tipoNormalizado, ['insumos', 'contramuestra'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de prestamo invalido.',
                ], 422);
            }

            if ($tipoNormalizado === 'insumos') {
                $recibo = DB::table('recibos_prestamo_insumos')
                    ->leftJoin('users', 'users.id', '=', 'recibos_prestamo_insumos.creado_por')
                    ->select(
                        'recibos_prestamo_insumos.id',
                        'recibos_prestamo_insumos.numero_orden',
                        'recibos_prestamo_insumos.fecha',
                        'recibos_prestamo_insumos.nombre_costurero',
                        'recibos_prestamo_insumos.firma_costurero',
                        'recibos_prestamo_insumos.firma_costurero_fecha',
                        'recibos_prestamo_insumos.firma_mensajero',
                        'recibos_prestamo_insumos.firma_mensajero_fecha',
                        'recibos_prestamo_insumos.anulado',
                        'recibos_prestamo_insumos.anulado_en',
                        'recibos_prestamo_insumos.confirmado_entrada',
                        'recibos_prestamo_insumos.confirmado_entrada_en',
                        'recibos_prestamo_insumos.novedades',
                        'recibos_prestamo_insumos.created_at',
                        'users.name as encargado'
                    )
                    ->where('recibos_prestamo_insumos.id', $id)
                    ->first();

                if (!$recibo) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Recibo no encontrado.',
                    ], 404);
                }

                $items = DB::table('recibos_prestamo_insumos_items')
                    ->select('cantidad', 'descripcion', 'orden_fila')
                    ->where('recibo_prestamo_insumo_id', $id)
                    ->orderBy('orden_fila')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'cantidad' => $item->cantidad,
                            'descripcion' => $item->descripcion,
                            'orden_fila' => $item->orden_fila,
                        ];
                    })
                    ->values()
                    ->all();

                return response()->json([
                    'success' => true,
                    'tipo' => 'insumos',
                    'recibo' => [
                        'id' => $recibo->id,
                        'numero_orden' => $recibo->numero_orden,
                        'fecha' => $recibo->fecha ? Carbon::parse($recibo->fecha)->toDateString() : null,
                        'nombre_costurero' => $recibo->nombre_costurero,
                        'encargado' => $recibo->encargado,
                        'firma_costurero' => $recibo->firma_costurero,
                        'firma_costurero_fecha' => $recibo->firma_costurero_fecha,
                        'firma_mensajero' => $recibo->firma_mensajero,
                        'firma_mensajero_fecha' => $recibo->firma_mensajero_fecha,
                        'anulado' => (bool) $recibo->anulado,
                        'anulado_en' => $recibo->anulado_en,
                        'confirmado_entrada' => (bool) $recibo->confirmado_entrada,
                        'confirmado_entrada_en' => $recibo->confirmado_entrada_en,
                        'novedades' => $recibo->novedades,
                        'created_at' => $recibo->created_at,
                    ],
                    'items' => $items,
                ]);
            }

            $recibo = DB::table('recibos_prestamo_contramuestra')
                ->leftJoin('users', 'users.id', '=', 'recibos_prestamo_contramuestra.creado_por')
                ->select(
                    'recibos_prestamo_contramuestra.id',
                    'recibos_prestamo_contramuestra.numero_orden',
                    'recibos_prestamo_contramuestra.fecha',
                    'recibos_prestamo_contramuestra.nombre_costurero',
                    'recibos_prestamo_contramuestra.descripcion',
                    'recibos_prestamo_contramuestra.firma_costurero',
                    'recibos_prestamo_contramuestra.firma_costurero_fecha',
                    'recibos_prestamo_contramuestra.firma_mensajero',
                    'recibos_prestamo_contramuestra.firma_mensajero_fecha',
                    'recibos_prestamo_contramuestra.anulado',
                    'recibos_prestamo_contramuestra.anulado_en',
                    'recibos_prestamo_contramuestra.confirmado_entrada',
                    'recibos_prestamo_contramuestra.confirmado_entrada_en',
                    'recibos_prestamo_contramuestra.novedades',
                    'recibos_prestamo_contramuestra.created_at',
                    'users.name as encargado'
                )
                ->where('recibos_prestamo_contramuestra.id', $id)
                ->first();

            if (!$recibo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'tipo' => 'contramuestra',
                'recibo' => [
                    'id' => $recibo->id,
                    'numero_orden' => $recibo->numero_orden,
                    'fecha' => $recibo->fecha ? Carbon::parse($recibo->fecha)->toDateString() : null,
                    'nombre_costurero' => $recibo->nombre_costurero,
                    'descripcion' => $recibo->descripcion,
                    'encargado' => $recibo->encargado,
                    'firma_costurero' => $recibo->firma_costurero,
                    'firma_costurero_fecha' => $recibo->firma_costurero_fecha,
                    'firma_mensajero' => $recibo->firma_mensajero,
                    'firma_mensajero_fecha' => $recibo->firma_mensajero_fecha,
                    'anulado' => (bool) $recibo->anulado,
                    'anulado_en' => $recibo->anulado_en,
                    'confirmado_entrada' => (bool) $recibo->confirmado_entrada,
                    'confirmado_entrada_en' => $recibo->confirmado_entrada_en,
                    'novedades' => $recibo->novedades,
                    'created_at' => $recibo->created_at,
                ],
                'items' => [],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error en apiDetallePrestamo: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el detalle del prestamo',
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

    public function apiStats(Request $request, \App\Application\Talleres\UseCases\ObtenerDashboardTallerUseCase $useCase)
    {
        try {
            $ids = $request->input('ids', []);

            if (is_string($ids)) {
                $ids = array_filter(array_map('trim', explode(',', $ids)));
            }

            $ids = array_values(array_unique(array_map('intval', is_array($ids) ? $ids : [])));

            $stats = $useCase->executeBatchStats($ids);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error en apiStats de talleres: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudieron cargar las estadísticas de los talleres',
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
            $tab = $request->input('tab', 'pedidos');

            $resultado = $useCase->execute($search, $page, $tab);

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
            $esParcial = $request->boolean('es_parcial');
            $pedidoParcialId = (int) $request->query('pedido_parcial_id', 0);
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

            if ($esParcial) {
                $reciboParcialQuery = DB::table('recibo_por_partes');

                if ($reciboId > 0) {
                    $reciboParcialQuery->where('id', $reciboId);
                } elseif ($numeroRecibo !== '') {
                    $reciboParcialQuery->where('consecutivo_parcial', $numeroRecibo);
                }

                if ($pedidoProduccionId > 0 && $tipoRecibo !== 'CORTE-PARA-BODEGA') {
                    $reciboParcialQuery->where('pedido_produccion_id', $pedidoProduccionId);
                }

                $reciboParcial = $reciboParcialQuery->orderByDesc('id')->first();

                if (!$reciboParcial) {
                    return response()->json(['success' => false, 'message' => 'Recibo no encontrado'], 404);
                }

                $tipoReciboParcial = strtoupper(trim((string) ($reciboParcial->tipo_recibo ?? $tipoRecibo)));
                $fechaParcial = Carbon::parse($reciboParcial->created_at);
                if ($tipoReciboParcial === 'CORTE-PARA-BODEGA') {
                    $prendaBodegaId = (int) ($reciboParcial->prenda_pedido_id ?? 0);

                    $reciboBase = DB::table('consecutivos_recibos_pedidos as crp_base')
                        ->where('crp_base.tipo_recibo', 'CORTE-PARA-BODEGA')
                        ->where('crp_base.consecutivo_actual', $reciboParcial->consecutivo_original)
                        ->where('crp_base.prenda_bodega_id', $prendaBodegaId)
                        ->orderByDesc('crp_base.id')
                        ->first();

                    if ($reciboBase && $reciboBase->prenda_bodega_id) {
                        $prendaBodegaId = (int) $reciboBase->prenda_bodega_id;
                    }

                    $prenda = DB::table('prenda_bodega')->where('id', $prendaBodegaId)->first();

                    if (!$prenda) {
                        return response()->json(['success' => false, 'message' => 'Prenda no encontrada'], 404);
                    }

                    $tallas = DB::table('recibos_por_partes_tallas')
                        ->where('recibo_por_partes_id', $reciboParcial->id)
                        ->get(['talla', 'genero', 'color_nombre', 'cantidad']);

                    $procesoSalida = DB::table('procesos_prenda')
                        ->where('prenda_bodega_id', $prendaBodegaId)
                        ->whereRaw("LOWER(TRIM(proceso)) = 'costura'")
                        ->orderByDesc('fecha_de_asignacion_encargado')
                        ->orderByDesc('id')
                        ->selectRaw('COALESCE(fecha_de_asignacion_encargado, created_at) as fecha_salida')
                        ->value('fecha_salida');

                    $entregasRecibo = DB::table('entrega_recibo_costura')
                        ->where('recibo_parcial_id', $reciboParcial->id);
                    $totalEntregado = (int) (clone $entregasRecibo)->sum('cantidad_entregada');
                    $fechaEntrada = (clone $entregasRecibo)->max('created_at');

                    return response()->json([
                        'success' => true,
                        'tipo_recibo' => $tipoReciboParcial,
                        'numero_recibo' => (float) $reciboParcial->consecutivo_parcial,
                        'descripcion' => $prenda->descripcion ?? ($prenda->nombre ?? 'Recibo parcial'),
                        'dia' => $fechaParcial->format('d'),
                        'mes' => $fechaParcial->format('m'),
                        'ano' => $fechaParcial->format('Y'),
                        'fecha_salida' => $procesoSalida ? \Carbon\Carbon::parse($procesoSalida)->format('d/m/Y h:i A') : '-',
                        'fecha_entrada' => $fechaEntrada ? \Carbon\Carbon::parse($fechaEntrada)->format('d/m/Y h:i A') : null,
                        'tallas' => $tallas->map(fn($t) => [
                            'talla' => $t->talla,
                            'genero' => $t->genero,
                            'color_nombre' => $t->color_nombre,
                            'cantidad' => (int) $t->cantidad,
                        ])->toArray(),
                        'total' => (int) $tallas->sum('cantidad'),
                        'total_entregado' => $totalEntregado,
                    ]);
                }

                $prenda = DB::table('prendas_pedido')->where('id', $reciboParcial->prenda_pedido_id)->first();
                $tallas = DB::table('recibos_por_partes_tallas')
                    ->where('recibo_por_partes_id', $reciboParcial->id)
                    ->get(['talla', 'genero', 'color_nombre', 'cantidad']);

                $procesoSalida = DB::table('procesos_prenda')
                    ->where('numero_recibo_parcial', $reciboParcial->consecutivo_parcial)
                    ->whereRaw("LOWER(TRIM(proceso)) = 'costura'")
                    ->orderByDesc('fecha_de_asignacion_encargado')
                    ->orderByDesc('id')
                    ->selectRaw('COALESCE(fecha_de_asignacion_encargado, created_at) as fecha_salida')
                    ->value('fecha_salida');

                $entregasRecibo = DB::table('entrega_recibo_costura')
                    ->where('recibo_parcial_id', $reciboParcial->id);
                $totalEntregado = (int) (clone $entregasRecibo)->sum('cantidad_entregada');
                $fechaEntrada = (clone $entregasRecibo)->max('created_at');

                return response()->json([
                    'success' => true,
                    'tipo_recibo' => $tipoReciboParcial,
                    'numero_recibo' => (float) $reciboParcial->consecutivo_parcial,
                    'descripcion' => $prenda->descripcion ?? ($prenda->nombre_prenda ?? 'Recibo parcial'),
                    'dia' => $fechaParcial->format('d'),
                    'mes' => $fechaParcial->format('m'),
                    'ano' => $fechaParcial->format('Y'),
                    'fecha_salida' => $procesoSalida ? \Carbon\Carbon::parse($procesoSalida)->format('d/m/Y h:i A') : '-',
                    'fecha_entrada' => $fechaEntrada ? \Carbon\Carbon::parse($fechaEntrada)->format('d/m/Y h:i A') : null,
                    'tallas' => $tallas->map(fn($t) => [
                        'talla' => $t->talla,
                        'genero' => $t->genero,
                        'color' => $t->color,
                        'cantidad' => (int) $t->cantidad,
                    ])->toArray(),
                    'total' => (int) $tallas->sum('cantidad'),
                    'total_entregado' => $totalEntregado,
                ]);
            }

            // CORTE-PARA-BODEGA: resolver por consecutivo base
            if ($tipoRecibo === 'CORTE-PARA-BODEGA') {
                $reciboBodegaQuery = DB::table('consecutivos_recibos_pedidos')
                    ->where('tipo_recibo', 'CORTE-PARA-BODEGA');

                if ($reciboId > 0) {
                    $reciboBodegaQuery->where('id', $reciboId);
                } elseif ($pedidoParcialId > 0) {
                    $reciboBodegaQuery->where('pedido_parcial_id', $pedidoParcialId);
                } else {
                    $reciboBodegaQuery->where('consecutivo_actual', $numeroRecibo);
                }

                $reciboBodega = $reciboBodegaQuery->orderByDesc('id')->first();
                $prendaBodegaId = $reciboBodega->prenda_bodega_id ?? null;

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

                $procesoSalida = DB::table('procesos_prenda')
                    ->where('prenda_bodega_id', $prendaBodegaId)
                    ->whereRaw("LOWER(TRIM(proceso)) = 'costura'")
                    ->orderByDesc('fecha_de_asignacion_encargado')
                    ->orderByDesc('id')
                    ->selectRaw('COALESCE(fecha_de_asignacion_encargado, created_at) as fecha_salida')
                    ->value('fecha_salida');

                $entregasRecibo = DB::table('entrega_recibo_costura')
                    ->where('consecutivo_recibo_id', $reciboBodega->id ?? 0);
                $totalEntregado = (int) (clone $entregasRecibo)->sum('cantidad_entregada');
                $fechaEntrada = (clone $entregasRecibo)->max('created_at');

                $fecha = Carbon::parse($prenda->created_at);
                return response()->json([
                    'success' => true,
                    'tipo_recibo' => 'CORTE-PARA-BODEGA',
                    'numero_recibo' => (float) $numeroRecibo,
                    'descripcion' => $prenda->descripcion ?? '',
                    'dia' => $fecha->format('d'),
                    'mes' => $fecha->format('m'),
                    'ano' => $fecha->format('Y'),
                    'fecha_salida' => $procesoSalida ? \Carbon\Carbon::parse($procesoSalida)->format('d/m/Y h:i A') : '-',
                    'fecha_entrada' => $fechaEntrada ? \Carbon\Carbon::parse($fechaEntrada)->format('d/m/Y h:i A') : null,
                    'tallas' => $tallas->map(fn($t) => [
                        'talla' => $t->talla,
                        'genero' => $t->genero,
                        'color_nombre' => $t->color_nombre,
                        'cantidad' => (int) $t->cantidad,
                    ])->toArray(),
                    'total' => (int) $tallas->sum('cantidad'),
                    'total_entregado' => $totalEntregado,
                ]);
            }

            // COSTURA / REFLECTIVO / otros: resolver por tipo real y, si existe, por ID exacto
            $reciboBaseQuery = DB::table('consecutivos_recibos_pedidos')
                ->where('tipo_recibo', $tipoRecibo);

            if ($reciboId > 0) {
                $reciboBaseQuery->where('id', $reciboId);
            } elseif ($pedidoParcialId > 0) {
                $reciboBaseQuery->where('pedido_parcial_id', $pedidoParcialId);
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
                    DB::raw('COALESCE(ppc.color_nombre, "") as color_nombre'),
                    DB::raw('COALESCE(ppc.cantidad, ppt.cantidad) as cantidad')
                ]);

            $procesoSalida = DB::table('procesos_prenda')
                ->where('numero_recibo', $reciboBase->consecutivo_actual)
                ->whereRaw("LOWER(TRIM(proceso)) = 'costura'")
                ->orderByDesc('fecha_de_asignacion_encargado')
                ->orderByDesc('id')
                ->selectRaw('COALESCE(fecha_de_asignacion_encargado, created_at) as fecha_salida')
                ->value('fecha_salida');

            $entregasRecibo = DB::table('entrega_recibo_costura')
                ->where('consecutivo_recibo_id', $reciboBase->id);
            $totalEntregado = (int) (clone $entregasRecibo)->sum('cantidad_entregada');
            $fechaEntrada = (clone $entregasRecibo)->max('created_at');

            $fecha = Carbon::parse($reciboBase->created_at);
            return response()->json([
                'success' => true,
                'tipo_recibo' => 'COSTURA',
                'numero_recibo' => (float) $numeroRecibo,
                'descripcion' => $prenda->descripcion ?? ($prenda->nombre_prenda ?? ''),
                'dia' => $fecha->format('d'),
                'mes' => $fecha->format('m'),
                'ano' => $fecha->format('Y'),
                'fecha_salida' => $procesoSalida ? \Carbon\Carbon::parse($procesoSalida)->format('d/m/Y h:i A') : '-',
                'fecha_entrada' => $fechaEntrada ? \Carbon\Carbon::parse($fechaEntrada)->format('d/m/Y h:i A') : null,
                    'tallas' => $tallasColor->map(fn($t) => [
                        'talla' => $t->talla,
                        'genero' => $t->genero,
                        'color_nombre' => $t->color_nombre,
                        'cantidad' => (int) $t->cantidad,
                    ])->toArray(),
                'total' => (int) $tallasColor->sum('cantidad'),
                'total_entregado' => $totalEntregado,
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
