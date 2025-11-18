<?php

namespace App\Http\Controllers;

use App\Models\TablaOriginal;
use App\Models\ProductoPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        $asesoraNombre = Auth::user()->name;
        
        // Estadísticas generales
        $stats = [
            'pedidos_dia' => TablaOriginal::delAsesor($asesoraNombre)->delDia()->count(),
            'pedidos_mes' => TablaOriginal::delAsesor($asesoraNombre)->delMes()->count(),
            'pedidos_anio' => TablaOriginal::delAsesor($asesoraNombre)->delAnio()->count(),
            'pedidos_pendientes' => TablaOriginal::delAsesor($asesoraNombre)
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
        $asesoraNombre = Auth::user()->name;
        $dias = $request->get('tipo', 30);

        // Datos para gráfica de pedidos por día
        $pedidosUltimos30Dias = TablaOriginal::delAsesor($asesoraNombre)
            ->select(DB::raw('DATE(fecha_de_creacion_de_orden) as fecha'), DB::raw('COUNT(*) as total'))
            ->where('fecha_de_creacion_de_orden', '>=', now()->subDays($dias))
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        // Datos para gráfica de pedidos por asesor (comparativa - todos los asesores)
        $pedidosPorAsesor = TablaOriginal::select('asesora', DB::raw('COUNT(*) as total'))
            ->whereNotNull('asesora')
            ->where('fecha_de_creacion_de_orden', '>=', now()->subDays(30))
            ->groupBy('asesora')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->asesora,
                    'total' => $item->total
                ];
            });

        // Datos para gráfica de estados
        $pedidosPorEstado = TablaOriginal::delAsesor($asesoraNombre)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->whereNotNull('estado')
            ->groupBy('estado')
            ->get();

        // Tendencia semanal
        $semanaActual = TablaOriginal::delAsesor($asesoraNombre)
            ->whereBetween('fecha_de_creacion_de_orden', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        
        $semanaAnterior = TablaOriginal::delAsesor($asesoraNombre)
            ->whereBetween('fecha_de_creacion_de_orden', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
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
        $asesoraNombre = Auth::user()->name;
        
        $query = TablaOriginal::delAsesor($asesoraNombre)->with('productos');

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('pedido', 'LIKE', "%{$search}%")
                  ->orWhere('cliente', 'LIKE', "%{$search}%");
            });
        }

        $pedidos = $query->orderBy('fecha_de_creacion_de_orden', 'desc')->paginate(20);

        // Obtener valores únicos para filtros
        $estados = TablaOriginal::select('estado')
            ->whereNotNull('estado')
            ->distinct()
            ->pluck('estado');

        return view('asesores.pedidos.index', compact('pedidos', 'estados'));
    }

    /**
     * Mostrar formulario para crear pedido
     */
    public function create()
    {
        // Obtener el siguiente número de pedido
        $ultimoPedido = TablaOriginal::max('pedido');
        $siguientePedido = $ultimoPedido ? $ultimoPedido + 1 : 1;

        // Obtener opciones de enums
        $estados = ['No iniciado', 'En Ejecución', 'Entregado', 'Anulada'];
        $areas = [
            'Creación Orden', 'Corte', 'Costura', 'Bordado', 'Estampado',
            'Control-Calidad', 'Entrega', 'Polos', 'Taller', 'Insumos',
            'Lavandería', 'Arreglos', 'Despachos'
        ];

        return view('asesores.pedidos.create', compact('siguientePedido', 'estados', 'areas'));
    }

    /**
     * Guardar nuevo pedido
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pedido' => 'required|integer|unique:tabla_original,pedido',
            'cliente' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'novedades' => 'nullable|string',
            'forma_de_pago' => 'nullable|string|max:69',
            'estado' => 'nullable|string',
            'area' => 'nullable|string',
            'productos' => 'required|array|min:1',
            'productos.*.nombre_producto' => 'required|string',
            'productos.*.descripcion' => 'nullable|string',
            'productos.*.tella' => 'nullable|string',
            'productos.*.tipo_manga' => 'nullable|string',
            'productos.*.color' => 'nullable|string',
            'productos.*.talla' => 'nullable|string',
            'productos.*.genero' => 'nullable|string',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.ref_hilo' => 'nullable|string',
            'productos.*.precio_unitario' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Calcular cantidad total de productos
            $cantidadTotal = collect($validated['productos'])->sum('cantidad');

            // Crear el pedido en tabla_original
            $pedido = TablaOriginal::create([
                'pedido' => $validated['pedido'],
                'cliente' => $validated['cliente'],
                'asesora' => Auth::user()->name,
                'descripcion' => $validated['descripcion'] ?? null,
                'novedades' => $validated['novedades'] ?? null,
                'forma_de_pago' => $validated['forma_de_pago'] ?? null,
                'cantidad' => $cantidadTotal,
                'estado' => $validated['estado'] ?? 'No iniciado',
                'area' => $validated['area'] ?? 'Creación Orden',
                'fecha_de_creacion_de_orden' => now()->toDateString(),
            ]);

            // Crear los productos del pedido
            foreach ($validated['productos'] as $productoData) {
                $subtotal = null;
                if (isset($productoData['precio_unitario']) && isset($productoData['cantidad'])) {
                    $subtotal = $productoData['precio_unitario'] * $productoData['cantidad'];
                }

                ProductoPedido::create([
                    'pedido' => $pedido->pedido,
                    'nombre_producto' => $productoData['nombre_producto'],
                    'descripcion' => $productoData['descripcion'] ?? null,
                    'talla' => $productoData['talla'] ?? null,
                    'cantidad' => $productoData['cantidad'],
                    'precio_unitario' => $productoData['precio_unitario'] ?? null,
                    'subtotal' => $subtotal,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'pedido' => $pedido->pedido
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
        $asesoraNombre = Auth::user()->name;
        
        $pedidoData = TablaOriginal::with('productos')
            ->where('pedido', $pedido)
            ->where('asesora', $asesoraNombre)
            ->firstOrFail();

        return view('asesores.pedidos.show', compact('pedidoData'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit($pedido)
    {
        $asesoraNombre = Auth::user()->name;
        
        $pedidoData = TablaOriginal::with('productos')
            ->where('pedido', $pedido)
            ->where('asesora', $asesoraNombre)
            ->firstOrFail();

        $estados = ['No iniciado', 'En Ejecución', 'Entregado', 'Anulada'];
        $areas = [
            'Creación Orden', 'Corte', 'Costura', 'Bordado', 'Estampado',
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
        $asesoraNombre = Auth::user()->name;
        
        $pedidoData = TablaOriginal::where('pedido', $pedido)
            ->where('asesora', $asesoraNombre)
            ->firstOrFail();

        $validated = $request->validate([
            'cliente' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'novedades' => 'nullable|string',
            'forma_de_pago' => 'nullable|string|max:69',
            'estado' => 'nullable|string',
            'area' => 'nullable|string',
            'productos' => 'sometimes|array',
            'productos.*.id' => 'nullable|exists:productos_pedido,id',
            'productos.*.nombre_producto' => 'required_with:productos|string',
            'productos.*.descripcion' => 'nullable|string',
            'productos.*.talla' => 'nullable|string',
            'productos.*.cantidad' => 'required_with:productos|integer|min:1',
            'productos.*.precio_unitario' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Actualizar datos del pedido
            $updateData = collect($validated)->except('productos')->toArray();
            
            if (isset($validated['productos'])) {
                $cantidadTotal = collect($validated['productos'])->sum('cantidad');
                $updateData['cantidad'] = $cantidadTotal;
            }

            $pedidoData->update($updateData);

            // Actualizar productos si se enviaron
            if (isset($validated['productos'])) {
                // Eliminar productos antiguos
                ProductoPedido::where('pedido', $pedido)->delete();

                // Crear nuevos productos
                foreach ($validated['productos'] as $productoData) {
                    $subtotal = null;
                    if (isset($productoData['precio_unitario']) && isset($productoData['cantidad'])) {
                        $subtotal = $productoData['precio_unitario'] * $productoData['cantidad'];
                    }

                    ProductoPedido::create([
                        'pedido' => $pedido,
                        'nombre_producto' => $productoData['nombre_producto'],
                        'descripcion' => $productoData['descripcion'] ?? null,
                        'talla' => $productoData['talla'] ?? null,
                        'cantidad' => $productoData['cantidad'],
                        'precio_unitario' => $productoData['precio_unitario'] ?? null,
                        'subtotal' => $subtotal,
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
        $asesoraNombre = Auth::user()->name;
        
        $pedidoData = TablaOriginal::where('pedido', $pedido)
            ->where('asesora', $asesoraNombre)
            ->firstOrFail();

        DB::beginTransaction();
        try {
            // Los productos se eliminan automáticamente por la foreign key cascade
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
        $ultimoPedido = TablaOriginal::max('pedido');
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
        $asesoraNombre = Auth::user()->name;
        
        // Pedidos próximos a entregar (próximos 7 días)
        $pedidosProximosEntregar = TablaOriginal::delAsesor($asesoraNombre)
            ->whereIn('estado', ['No iniciado', 'En Ejecución'])
            ->whereNotNull('entrega')
            ->whereBetween('entrega', [now(), now()->addDays(7)])
            ->orderBy('entrega')
            ->get();

        // Pedidos en ejecución
        $pedidosEnEjecucion = TablaOriginal::delAsesor($asesoraNombre)
            ->where('estado', 'En Ejecución')
            ->count();

        return response()->json([
            'pedidos_proximos_entregar' => $pedidosProximosEntregar,
            'pedidos_en_ejecucion' => $pedidosEnEjecucion,
            'total_notificaciones' => $pedidosProximosEntregar->count() + $pedidosEnEjecucion
        ]);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead()
    {
        return response()->json([
            'success' => true,
            'message' => 'Notificaciones marcadas como leídas'
        ]);
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
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar
                ]
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
}
