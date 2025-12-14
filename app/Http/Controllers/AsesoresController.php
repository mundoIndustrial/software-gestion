<?php

namespace App\Http\Controllers;

use App\Models\ProductoPedido;
use App\Models\PedidoProduccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\AsesoresInventarioTelasController;

class AsesoresController extends Controller
{
    /**
     * Mostrar el perfil del asesor
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'Por favor inicia sesión para ver tu perfil.');
            }
            
            return view('asesores.profile', compact('user'));
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al cargar el perfil: ' . $e->getMessage());
        }
    }
    /**
     * Mostrar el dashboard de asesores
     */
    public function dashboard()
    {
        $userId = Auth::id();
        
        // Estadísticas generales usando PedidoProduccion con asesor_id
        $stats = [
            'pedidos_dia' => PedidoProduccion::where('asesor_id', $userId)
                ->whereDate('created_at', today())->count(),
            'pedidos_mes' => PedidoProduccion::where('asesor_id', $userId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
            'pedidos_anio' => PedidoProduccion::where('asesor_id', $userId)
                ->whereYear('created_at', now()->year)->count(),
            'pedidos_pendientes' => PedidoProduccion::where('asesor_id', $userId)
                ->whereIn('estado', ['No iniciado', 'En Ejecución'])
                ->count(),
        ];

        return view('asesores.dashboard', compact('stats'));
    }

    /**
     * Obtener datos para gráficas del dashboard
     */
    public function getDashboardData(Request $request)
    {
        $userId = Auth::id();
        $dias = $request->get('tipo', 30);

        // Datos para gráfica de pedidos por día
        $pedidosUltimos30Dias = PedidoProduccion::where('asesor_id', $userId)
            ->select(DB::raw('DATE(created_at) as fecha'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', now()->subDays($dias))
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        // Datos para gráfica de pedidos por asesor (comparativa - todos los asesores)
        $pedidosPorAsesor = PedidoProduccion::select('asesor_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('asesor_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('asesor_id')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->with('asesora')
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->asesora ? $item->asesora->name : 'Desconocido',
                    'total' => $item->total
                ];
            });

        // Datos para gráfica de estados
        $pedidosPorEstado = PedidoProduccion::where('asesor_id', $userId)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->whereNotNull('estado')
            ->groupBy('estado')
            ->get();

        // Tendencia semanal
        $semanaActual = PedidoProduccion::where('asesor_id', $userId)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        
        $semanaAnterior = PedidoProduccion::where('asesor_id', $userId)
            ->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->count();

        $tendencia = $semanaAnterior > 0 
            ? (($semanaActual - $semanaAnterior) / $semanaAnterior) * 100 
            : 0;

        return response()->json([
            'ordenes_ultimos_30_dias' => $pedidosUltimos30Dias,
            'ordenes_por_asesor' => $pedidosPorAsesor,
            'ordenes_por_estado' => $pedidosPorEstado,
            'tendencia' => round($tendencia, 2),
            'semana_actual' => $semanaActual,
            'semana_anterior' => $semanaAnterior,
        ]);
    }

    /**
     * Listar pedidos del asesor
     */
    public function index(Request $request)
    {
        // DEBUG: Ver usuario autenticado
        $userId = Auth::id();
        $userName = Auth::user()->name ?? 'Sin nombre';
        \Log::info('AsesoresController@index - Usuario ID: ' . $userId . ', Nombre: ' . $userName);

        // Usar pedidos_produccion en lugar de tabla_original
        // Filtrar por asesor_id (Foreign Key del usuario autenticado)
        $query = PedidoProduccion::where('asesor_id', $userId)
            ->with([
                'prendas' => function ($q) {
                    $q->with(['procesos' => function ($q2) {
                        $q2->orderBy('created_at', 'desc'); // Últimos primero
                    }]);
                },
                'asesora' // Eager load la relación de usuario
            ]);

        // Filtros
        if ($request->filled('estado')) {
            // Si el estado es "No iniciado", filtrar por pendientes de aprobación del supervisor
            if ($request->estado === 'No iniciado') {
                $query->where('estado', 'No iniciado')
                      ->whereNull('aprobado_por_supervisor_en');
            } 
            // Si el estado es "En Ejecución", mostrar "No iniciado" y "En Ejecución"
            elseif ($request->estado === 'En Ejecución') {
                $query->whereIn('estado', ['No iniciado', 'En Ejecución']);
            } 
            else {
                $query->where('estado', $request->estado);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_pedido', 'LIKE', "%{$search}%")
                  ->orWhere('cliente', 'LIKE', "%{$search}%");
            });
        }

        $pedidos = $query->orderBy('created_at', 'desc')->paginate(20);

        // DEBUG LOG
        \Log::info('Pedidos encontrados: ' . $pedidos->count());
        \Log::info('Total de pedidos: ' . $pedidos->total());
        foreach ($pedidos as $pedido) {
            \Log::info('Pedido: #' . $pedido->numero_pedido . ' - Cliente: ' . $pedido->cliente . ' - Asesora: ' . $pedido->asesora . ' - Prendas: ' . $pedido->prendas->count());
        }

        // Obtener valores únicos para filtros
        $estados = PedidoProduccion::select('estado')
            ->whereNotNull('estado')
            ->distinct()
            ->pluck('estado');

        return view('asesores.pedidos.index', compact('pedidos', 'estados'));
    }

    /**
     * Mostrar formulario para crear pedido (versión amigable)
     */
    public function create(Request $request)
    {
        $tipo = $request->query('tipo', 'PB'); // Por defecto Prenda/Logo
        
        // Si es tipo Logo (B), redirigir a cotización de bordado
        if ($tipo === 'B') {
            return redirect()->route('asesores.cotizaciones-bordado.create');
        }
        
        // Si es tipo Prenda (P), redirigir a cotización de prenda
        if ($tipo === 'P') {
            return redirect()->route('asesores.cotizaciones-prenda.create');
        }
        
        // Si es tipo Reflectivo (RF), mostrar vista de reflectivo
        if ($tipo === 'RF') {
            return view('asesores.pedidos.create-reflectivo', compact('tipo'));
        }
        
        return view('asesores.pedidos.create-friendly', compact('tipo'));
    }

    /**
     * Guardar nuevo pedido
     */
    public function store(Request $request)
    {
        // Soportar ambos formatos: productos y productos_friendly
        $productosKey = $request->has('productos') ? 'productos' : 'productos_friendly';
        
        $validated = $request->validate([
            'cliente' => 'required|string|max:255',
            'forma_de_pago' => 'nullable|string|max:69',
            'area' => 'nullable|string',
            $productosKey => 'required|array|min:1',
            $productosKey.'.*.nombre_producto' => 'required|string',
            $productosKey.'.*.descripcion' => 'nullable|string',
            $productosKey.'.*.tella' => 'nullable|string',
            $productosKey.'.*.tipo_manga' => 'nullable|string',
            $productosKey.'.*.color' => 'nullable|string',
            $productosKey.'.*.talla' => 'nullable|string',
            $productosKey.'.*.genero' => 'nullable|string',
            $productosKey.'.*.cantidad' => 'required|integer|min:1',
            $productosKey.'.*.ref_hilo' => 'nullable|string',
            $productosKey.'.*.precio_unitario' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Calcular cantidad total de productos
            $cantidadTotal = collect($validated[$productosKey])->sum('cantidad');

            // Crear pedido en PedidoProduccion
            $pedidoBorrador = PedidoProduccion::create([
                'numero_pedido' => null, // Se asignará luego
                'cliente' => $validated['cliente'],
                'asesor_id' => Auth::id(),
                'forma_de_pago' => $validated['forma_de_pago'] ?? null,
                'estado' => 'No iniciado',
            ]);

            // Crear los productos del pedido usando PrendaPedido
            foreach ($validated[$productosKey] as $productoData) {
                $pedidoBorrador->prendas()->create([
                    'nombre_prenda' => $productoData['nombre_producto'],
                    'talla' => $productoData['talla'] ?? null,
                    'cantidad' => $productoData['cantidad'],
                    'precio_unitario' => $productoData['precio_unitario'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido guardado como borrador',
                'borrador_id' => $pedidoBorrador->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmar pedido y asignar ID
     */
    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'borrador_id' => 'required|integer|exists:pedidos_produccion,id',
            'numero_pedido' => 'required|integer|unique:pedidos_produccion,numero_pedido',
        ]);

        DB::beginTransaction();
        try {
            // Obtener el pedido
            $pedido = PedidoProduccion::findOrFail($validated['borrador_id']);
            
            // Actualizar con el número de pedido real
            $pedido->update([
                'numero_pedido' => $validated['numero_pedido']
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente con ID: ' . $validated['numero_pedido'],
                'pedido' => $validated['numero_pedido']
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un pedido específico
     */
    public function show($pedido)
    {
        $userId = Auth::id();
        
        $pedidoData = PedidoProduccion::where('numero_pedido', $pedido)
            ->where('asesor_id', $userId)
            ->with('prendas')
            ->firstOrFail();

        // Usar plantilla-erp para mostrar en formato de recibo
        return view('asesores.pedidos.plantilla-erp-antigua', compact('pedidoData'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit($pedido)
    {
        $userId = Auth::id();
        
        $pedidoData = PedidoProduccion::where('numero_pedido', $pedido)
            ->where('asesor_id', $userId)
            ->with('prendas')
            ->firstOrFail();

        $estados = ['No iniciado', 'En Ejecución', 'Entregado', 'Anulada'];
        $areas = [
            'Creación de Orden', 'Corte', 'Costura', 'Bordado', 'Estampado',
            'Control-Calidad', 'Entrega', 'Polos', 'Taller', 'Insumos',
            'Lavandería', 'Arreglos', 'Despachos'
        ];

        return view('asesores.pedidos.edit', compact('pedidoData', 'estados', 'areas'));
    }

    /**
     * Actualizar pedido
     */
    public function update(Request $request, $pedido)
    {
        $userId = Auth::id();
        
        $pedidoData = PedidoProduccion::where('numero_pedido', $pedido)
            ->where('asesor_id', $userId)
            ->firstOrFail();

        $validated = $request->validate([
            'cliente' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'novedades' => 'nullable|string',
            'forma_de_pago' => 'nullable|string|max:69',
            'estado' => 'nullable|string',
            'area' => 'nullable|string',
            'prendas' => 'sometimes|array',
            'prendas.*.id' => 'nullable|exists:prendas_pedido,id',
            'prendas.*.nombre_prenda' => 'required_with:prendas|string',
            'prendas.*.talla' => 'nullable|string',
            'prendas.*.cantidad' => 'required_with:prendas|integer|min:1',
            'prendas.*.precio_unitario' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Actualizar datos del pedido
            $updateData = collect($validated)->except('prendas')->toArray();
            $pedidoData->update($updateData);

            // Actualizar prendas si se enviaron
            if (isset($validated['prendas'])) {
                // Eliminar prendas antiguas
                $pedidoData->prendas()->delete();

                // Crear nuevas prendas
                foreach ($validated['prendas'] as $prendaData) {
                    $pedidoData->prendas()->create([
                        'nombre_prenda' => $prendaData['nombre_prenda'],
                        'talla' => $prendaData['talla'] ?? null,
                        'cantidad' => $prendaData['cantidad'],
                        'precio_unitario' => $prendaData['precio_unitario'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar pedido
     */
    public function destroy($pedido)
    {
        $userId = Auth::id();
        
        $pedidoData = PedidoProduccion::where('numero_pedido', $pedido)
            ->where('asesor_id', $userId)
            ->firstOrFail();

        DB::beginTransaction();
        try {
            // Las prendas se eliminan automáticamente por la foreign key cascade
            $pedidoData->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener siguiente número de pedido
     */
    public function getNextPedido()
    {
        $ultimoPedido = PedidoProduccion::max('numero_pedido');
        $siguientePedido = $ultimoPedido ? $ultimoPedido + 1 : 1;

        return response()->json([
            'siguiente_pedido' => $siguientePedido
        ]);
    }

    /**
     * Obtener notificaciones del asesor
     */
    public function getNotifications()
    {
        $userId = Auth::id();
        
        // Obtener IDs de pedidos ya vistos por el usuario
        $viewedPedidoIds = session('viewed_pedidos_' . $userId, []);
        
        // ============================================
        // NUEVO: Pedidos/Cotizaciones de OTROS asesores
        // ============================================
        // Pedidos recientes de otros asesores (últimas 24 horas)
        $pedidosOtrosAsesores = PedidoProduccion::where('asesor_id', '!=', $userId)
            ->whereNotNull('asesor_id')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->with('asesora')
            ->get()
            ->map(function($pedido) {
                return [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'numero_cotizacion' => $pedido->numero_cotizacion,
                    'cliente' => $pedido->cliente,
                    'asesor_nombre' => $pedido->asesora?->name ?? 'Desconocido',
                    'estado' => $pedido->estado,
                    'created_at' => $pedido->created_at,
                ];
            });

        // ============================================
        // ANTERIOR: Pedidos propios próximos a vencer
        // ============================================
        // Pedidos propios próximos a entregar (próximos 7 días)
        $pedidosProximosEntregar = PedidoProduccion::where('asesor_id', $userId)
            ->whereIn('estado', ['No iniciado', 'En Ejecución'])
            ->where('created_at', '<=', now()->addDays(7))
            ->whereNotIn('id', $viewedPedidoIds)
            ->orderBy('created_at')
            ->get();

        // Pedidos propios en ejecución
        $pedidosEnEjecucion = PedidoProduccion::where('asesor_id', $userId)
            ->where('estado', 'En Ejecución')
            ->whereNotIn('id', $viewedPedidoIds)
            ->count();

        return response()->json([
            'pedidos_otros_asesores' => $pedidosOtrosAsesores,
            'pedidos_proximos_entregar' => $pedidosProximosEntregar,
            'pedidos_en_ejecucion' => $pedidosEnEjecucion,
            'total_notificaciones' => $pedidosOtrosAsesores->count() + $pedidosProximosEntregar->count() + $pedidosEnEjecucion
        ]);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead()
    {
        try {
            $userId = Auth::id();
            
            // Obtener todos los pedidos que generan notificaciones
            $pedidosProximos = PedidoProduccion::where('asesor_id', $userId)
                ->whereIn('estado', ['No iniciado', 'En Ejecución'])
                ->where('created_at', '<=', now()->addDays(7))
                ->pluck('id')
                ->toArray();
            
            $pedidosEnEjecucion = PedidoProduccion::where('asesor_id', $userId)
                ->where('estado', 'En Ejecución')
                ->pluck('id')
                ->toArray();
            
            // Combinar todos los IDs de pedidos a marcar como vistos
            $allPedidoIds = array_merge($pedidosProximos, $pedidosEnEjecucion);
            
            // Guardar en sesión del usuario
            session(['viewed_pedidos_' . $userId => $allPedidoIds]);
            
            return response()->json([
                'success' => true,
                'message' => 'Notificaciones marcadas como leídas'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al marcar notificaciones',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar el perfil del asesor
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Validar datos
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
                'telefono' => 'nullable|string|max:20',
                'ciudad' => 'nullable|string|max:255',
                'departamento' => 'nullable|string|max:255',
                'bio' => 'nullable|string|max:500',
                'password' => 'nullable|string|min:8|confirmed',
                'avatar' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048'
            ]);

            // Actualizar datos del usuario
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->telefono = $validated['telefono'] ?? $user->telefono;
            $user->ciudad = $validated['ciudad'] ?? $user->ciudad;
            $user->departamento = $validated['departamento'] ?? $user->departamento;
            $user->bio = $validated['bio'] ?? $user->bio;

            // Actualizar contraseña si se proporciona
            if (!empty($validated['password'])) {
                $user->password = bcrypt($validated['password']);
            }

            // Manejar avatar
            if ($request->hasFile('avatar')) {
                // Eliminar avatar anterior si existe
                if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
                    try {
                        Storage::disk('public')->delete('avatars/' . $user->avatar);
                        \Log::info('Avatar anterior eliminado: ' . $user->avatar);
                    } catch (\Exception $e) {
                        \Log::warning('Error al eliminar avatar anterior: ' . $e->getMessage());
                    }
                }

                // Crear directorio si no existe
                if (!Storage::disk('public')->exists('avatars')) {
                    Storage::disk('public')->makeDirectory('avatars');
                    \Log::info('Directorio de avatars creado');
                }

                // Guardar nuevo avatar
                $file = $request->file('avatar');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                // Guardar archivo
                try {
                    $path = $file->storeAs('avatars', $filename, 'public');
                    
                    if ($path) {
                        // Guardar solo el nombre del archivo
                        $user->avatar = $filename;
                        \Log::info('Avatar guardado exitosamente: ' . $filename);
                    } else {
                        throw new \Exception('No se pudo guardar el archivo de avatar');
                    }
                } catch (\Exception $e) {
                    \Log::error('Error al guardar avatar: ' . $e->getMessage());
                    throw new \Exception('Error al guardar la imagen: ' . $e->getMessage());
                }
            }

            $user->save();

            // Preparar respuesta con URL del avatar correcta
            $avatarUrl = null;
            if ($user->avatar) {
                // Generar URL completa: /storage/avatars/{filename}
                $avatarUrl = asset('storage/avatars/' . $user->avatar);
                
                // Log para debugging
                \Log::info('Avatar URL generada: ' . $avatarUrl);
                \Log::info('Archivo en storage: storage/app/public/avatars/' . $user->avatar);
                \Log::info('Existe en storage: ' . (Storage::disk('public')->exists('avatars/' . $user->avatar) ? 'Sí' : 'No'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado correctamente',
                'avatar_url' => $avatarUrl,
                'asesor' => $user->getNombreAsesor()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Errores de validación: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar perfil: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar inventario de telas
     */
    public function inventarioTelas()
    {
        return app(AsesoresInventarioTelasController::class)->index();
    }
}