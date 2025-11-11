<?php

namespace App\Http\Controllers;

use App\Models\TablaOriginal;
use App\Models\OrdenAsesor;
use App\Models\ProductoPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AsesoresController extends Controller
{
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
     * Listar pedidos del asesor (usando OrdenAsesor con sistema de borradores)
     */
    public function index(Request $request)
    {
        $asesorId = Auth::id();
        
        // Determinar qué pestaña mostrar (borradores o confirmados)
        $tipo = $request->get('tipo', 'confirmados');
        
        $query = OrdenAsesor::where('asesor_id', $asesorId);

        // Filtrar por tipo (borrador o confirmado)
        if ($tipo === 'borradores') {
            $query->where('es_borrador', true);
        } else {
            $query->where('es_borrador', false);
        }

        // Filtros adicionales
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('pedido', 'LIKE', "%{$search}%")
                  ->orWhere('numero_orden', 'LIKE', "%{$search}%")
                  ->orWhere('cliente', 'LIKE', "%{$search}%");
            });
        }

        $pedidos = $query->orderBy('created_at', 'desc')->paginate(20);

        // Contar borradores y confirmados
        $cantidadBorradores = OrdenAsesor::where('asesor_id', $asesorId)
            ->where('es_borrador', true)
            ->count();
            
        $cantidadConfirmados = OrdenAsesor::where('asesor_id', $asesorId)
            ->where('es_borrador', false)
            ->count();

        // Obtener valores únicos para filtros
        $estados = ['pendiente', 'en_proceso', 'completada', 'cancelada'];
        
        // Áreas disponibles
        $areas = [
            'Corte',
            'Control-Calidad',
            'Costura',
            'Bordado',
            'Creación Orden',
            'Estampado',
            'Entrega',
            'Polos',
            'Taller',
            'Insumos',
            'Lavandería',
            'Arreglos',
            'Despachos'
        ];

        return view('asesores.pedidos.index', compact(
            'pedidos', 
            'estados',
            'areas',
            'tipo',
            'cantidadBorradores',
            'cantidadConfirmados'
        ));
    }

    /**
     * Mostrar formulario para crear pedido (como borrador)
     */
    public function create()
    {
        // NO asignamos número de pedido aquí, se asignará al confirmar
        
        // Obtener opciones de enums
        $estados = ['pendiente', 'en_proceso', 'completada', 'cancelada'];
        $areas = [
            'Creación Orden', 'Corte', 'Costura', 'Bordado', 'Estampado',
            'Control-Calidad', 'Entrega', 'Polos', 'Taller', 'Insumos',
            'Lavandería', 'Arreglos', 'Despachos'
        ];

        return view('asesores.pedidos.create', compact('estados', 'areas'));
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
            'productos.*.tela' => 'nullable|string',
            'productos.*.tipo_manga' => 'nullable|string',
            'productos.*.color' => 'nullable|string',
            'productos.*.descripcion' => 'nullable|string',
            'productos.*.bordados' => 'nullable|string',
            'productos.*.estampados' => 'nullable|string',
            'productos.*.personalizacion_combinada' => 'nullable|string',
            'productos.*.modelo_foto' => 'nullable|string',
            'productos.*.talla' => 'nullable|string',
            'productos.*.genero' => 'nullable|string',
            'productos.*.ref_hilo' => 'nullable|string',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'nullable|numeric|min:0',
            'productos.*.notas' => 'nullable|string',
            'productos.*.imagen' => 'nullable|image|max:5120', // 5MB max
            'productos.*.imagenes_personalizacion' => 'nullable|array',
            'productos.*.imagenes_personalizacion.*' => 'nullable|image|max:5120', // 5MB max cada una
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
            foreach ($request->productos as $index => $productoData) {
                $subtotal = null;
                if (isset($productoData['precio_unitario']) && isset($productoData['cantidad'])) {
                    $subtotal = $productoData['precio_unitario'] * $productoData['cantidad'];
                }

                // Manejar imagen del producto
                $imagenPath = null;
                if ($request->hasFile("productos.{$index}.imagen")) {
                    $imagen = $request->file("productos.{$index}.imagen");
                    $imagenPath = $imagen->store('productos', 'public');
                }

                $producto = ProductoPedido::create([
                    'pedido' => $pedido->pedido,
                    'nombre_producto' => $productoData['nombre_producto'],
                    'tela' => $productoData['tela'] ?? null,
                    'tipo_manga' => $productoData['tipo_manga'] ?? null,
                    'color' => $productoData['color'] ?? null,
                    'descripcion' => $productoData['descripcion'] ?? null,
                    'bordados' => $productoData['bordados'] ?? null,
                    'estampados' => $productoData['estampados'] ?? null,
                    'personalizacion_combinada' => $productoData['personalizacion_combinada'] ?? null,
                    'modelo_foto' => $productoData['modelo_foto'] ?? null,
                    'talla' => $productoData['talla'] ?? null,
                    'genero' => $productoData['genero'] ?? null,
                    'ref_hilo' => $productoData['ref_hilo'] ?? null,
                    'cantidad' => $productoData['cantidad'],
                    'precio_unitario' => $productoData['precio_unitario'] ?? null,
                    'subtotal' => $subtotal,
                    'imagen' => $imagenPath,
                    'notas' => $productoData['notas'] ?? null,
                ]);

                // Manejar múltiples imágenes adicionales si existen
                if ($request->hasFile("productos.{$index}.imagenes_adicionales")) {
                    foreach ($request->file("productos.{$index}.imagenes_adicionales") as $imgIndex => $imagen) {
                        $path = $imagen->store('productos/adicionales', 'public');
                        
                        \App\Models\ProductoImagen::create([
                            'producto_pedido_id' => $producto->id,
                            'tipo' => $request->input("productos.{$index}.tipo_imagen.{$imgIndex}", 'referencia'),
                            'imagen' => $path,
                            'titulo' => $request->input("productos.{$index}.titulo_imagen.{$imgIndex}"),
                            'descripcion' => $request->input("productos.{$index}.descripcion_imagen.{$imgIndex}"),
                            'orden' => $imgIndex,
                        ]);
                    }
                }

                // Manejar imágenes de personalización (bordados/estampados)
                if ($request->hasFile("productos.{$index}.imagenes_personalizacion")) {
                    foreach ($request->file("productos.{$index}.imagenes_personalizacion") as $imgIndex => $imagen) {
                        $path = $imagen->store('productos/personalizacion', 'public');
                        
                        \App\Models\ProductoImagen::create([
                            'producto_pedido_id' => $producto->id,
                            'tipo' => 'bordado', // Tipo específico para personalización
                            'imagen' => $path,
                            'titulo' => 'Referencia de Bordado/Estampado',
                            'descripcion' => 'Imagen de referencia para personalización',
                            'orden' => $imgIndex + 100, // Offset para diferenciar de otras imágenes
                        ]);
                    }
                }
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
        
        $pedidoData = TablaOriginal::with(['productos.imagenes'])
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
     * Mostrar el perfil del asesor
     */
    public function profile()
    {
        $user = Auth::user();
        return view('asesores.profile', compact('user'));
    }

    /**
     * Actualizar el perfil del asesor
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'telefono' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
            'ciudad' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'password' => 'nullable|min:8|confirmed',
        ]);

        try {
            // Actualizar campos básicos
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->telefono = $validated['telefono'] ?? null;
            $user->bio = $validated['bio'] ?? null;
            $user->ciudad = $validated['ciudad'] ?? null;
            $user->departamento = $validated['departamento'] ?? null;

            // Manejar avatar
            if ($request->hasFile('avatar')) {
                // Eliminar avatar anterior si existe
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }

                // Guardar nuevo avatar
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $user->avatar = $avatarPath;
            }

            // Actualizar contraseña si se proporcionó
            if ($request->filled('password')) {
                $user->password = bcrypt($validated['password']);
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado exitosamente',
                'avatar_url' => $user->avatar ? Storage::url($user->avatar) : null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar avatar del perfil
     */
    public function deleteAvatar()
    {
        $user = Auth::user();

        try {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
                $user->avatar = null;
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Avatar eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el avatar: ' . $e->getMessage()
            ], 500);
        }
    }
}
