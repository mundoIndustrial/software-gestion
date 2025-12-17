<?php

namespace App\Http\Controllers;

use App\Models\PedidoProduccion;
use App\Models\TablaOriginal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class SupervisorPedidosController extends Controller
{
    /**
     * Mostrar el perfil del supervisor
     */
    public function profile()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'Por favor inicia sesión para ver tu perfil.');
            }
            
            return view('supervisor-pedidos.profile', compact('user'));
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al cargar el perfil: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar el perfil del supervisor
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

            // Manejar avatar - Guardar en storage/supervisores como webp
            if ($request->hasFile('avatar')) {
                // Eliminar avatar anterior si existe
                if ($user->avatar && Storage::disk('public')->exists('supervisores/' . $user->avatar)) {
                    try {
                        Storage::disk('public')->delete('supervisores/' . $user->avatar);
                        \Log::info('Avatar anterior eliminado: ' . $user->avatar);
                    } catch (\Exception $e) {
                        \Log::warning('Error al eliminar avatar anterior: ' . $e->getMessage());
                    }
                }

                // Crear directorio si no existe
                if (!Storage::disk('public')->exists('supervisores')) {
                    Storage::disk('public')->makeDirectory('supervisores');
                    \Log::info('Directorio de supervisores creado');
                }

                // Convertir a webp y guardar
                $file = $request->file('avatar');
                $filename = time() . '_' . uniqid() . '.webp';
                
                try {
                    // Usar Intervention Image para convertir a webp
                    $image = \Intervention\Image\ImageManager::gd()
                        ->read($file)
                        ->scaleDown(height: 500)
                        ->toWebp();
                    
                    Storage::disk('public')->put('supervisores/' . $filename, $image);
                    $user->avatar = $filename;
                    \Log::info('Avatar guardado como webp: ' . $filename);
                } catch (\Exception $e) {
                    \Log::error('Error al convertir avatar a webp: ' . $e->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al procesar la imagen: ' . $e->getMessage()
                    ], 500);
                }
            }

            // Guardar cambios
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar perfil: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar lista de órdenes para supervisar
     */
    public function index(Request $request)
    {
        // Obtener órdenes con filtros
        $query = PedidoProduccion::with(['asesora', 'prendas']);

        // FILTRO DE APROBACIÓN: Mostrar solo órdenes según su estado de aprobación
        if ($request->filled('aprobacion')) {
            if ($request->aprobacion === 'pendiente') {
                // Órdenes PENDIENTES: Estado "PENDIENTE_SUPERVISOR" y sin aprobado_por_supervisor_en
                $query->where('estado', 'PENDIENTE_SUPERVISOR')
                      ->whereNull('aprobado_por_supervisor_en');
            } elseif ($request->aprobacion === 'aprobadas') {
                // Órdenes ya aprobadas (con aprobado_por_supervisor_en)
                $query->whereNotNull('aprobado_por_supervisor_en');
            }
        } else {
            // Por defecto, mostrar TODAS las órdenes (sin filtro de aprobación)
            // Esto incluye órdenes con y sin cotización
        }

        // Búsqueda general por pedido o cliente
        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function($q) use ($busqueda) {
                $q->where('numero_pedido', 'like', '%' . $busqueda . '%')
                  ->orWhere('cliente', 'like', '%' . $busqueda . '%');
            });
        }

        // Filtro por estado (mantener para filtros avanzados por columnas)
        if ($request->filled('estado')) {
            $estado = $request->estado;
            // Para "En Producción", filtrar por múltiples estados
            if ($estado === 'En Producción') {
                $query->whereIn('estado', ['No iniciado', 'En Ejecución']);
            } else {
                $query->where('estado', $estado);
            }
        }

        // Filtro por asesora (por nombre)
        if ($request->filled('asesora')) {
            $query->whereHas('asesora', function($q) {
                $q->where('name', 'like', '%' . request()->asesora . '%');
            });
        }

        // Filtro por rango de fechas
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_de_creacion_de_orden', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_de_creacion_de_orden', '<=', $request->fecha_hasta);
        }

        // Ordenar por fecha descendente
        $ordenes = $query->orderBy('fecha_de_creacion_de_orden', 'desc')
                        ->paginate(15)
                        ->appends($request->query());

        // Obtener estados únicos para filtro
        $estados = PedidoProduccion::distinct()
                                   ->pluck('estado')
                                   ->filter()
                                   ->values();

        return view('supervisor-pedidos.index', compact('ordenes', 'estados'));
    }

    /**
     * Ver detalle de la orden
     */
    public function show($id)
    {
        $orden = PedidoProduccion::with(['prendas', 'prendas.procesos'])
                                 ->findOrFail($id);

        return view('supervisor-pedidos.show', compact('orden'));
    }

    /**
     * Descargar PDF de la orden
     */
    public function descargarPDF($id)
    {
        $orden = PedidoProduccion::with(['prendas', 'prendas.procesos'])
                                 ->findOrFail($id);

        $pdf = Pdf::loadView('supervisor-pedidos.pdf', compact('orden'));
        
        return $pdf->download('Orden_' . $orden->numero_pedido . '.pdf');
    }

    /**
     * Anular orden con observación
     */
    public function anular(Request $request, $id)
    {
        $request->validate([
            'motivo_anulacion' => 'required|string|min:10|max:500',
        ], [
            'motivo_anulacion.required' => 'El motivo de anulación es obligatorio',
            'motivo_anulacion.min' => 'El motivo debe tener al menos 10 caracteres',
            'motivo_anulacion.max' => 'El motivo no puede exceder 500 caracteres',
        ]);

        $orden = PedidoProduccion::findOrFail($id);

        // Actualizar estado
        // IMPORTANTE: Se registra aprobado_por_supervisor_en para marcar que el supervisor ha actuado sobre la orden
        // Esto hace que la orden aparezca en el registro (tanto si es aprobada como si es anulada)
        $orden->update([
            'estado' => 'Anulada',
            'motivo_anulacion' => $request->motivo_anulacion,
            'fecha_anulacion' => now(),
            'usuario_anulacion' => auth()->user()->name,
            'aprobado_por_supervisor_en' => now(), // Registrar acción del supervisor
        ]);

        // Log de auditoría
        \Log::info("Orden #{$orden->numero_pedido} anulada por " . auth()->user()->name, [
            'motivo' => $request->motivo_anulacion,
            'fecha' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Orden anulada correctamente',
            'orden' => $orden,
        ]);
    }

    /**
     * Aprobar orden y enviarla a producción
     */
    public function aprobarOrden($id)
    {
        try {
            $orden = PedidoProduccion::findOrFail($id);

            // Verificar que la orden esté en estado "PENDIENTE_SUPERVISOR"
            if ($orden->estado !== 'PENDIENTE_SUPERVISOR') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden aprobar órdenes en estado "Pendiente de Supervisor"'
                ], 422);
            }

            // Marcar orden como aprobada por el supervisor
            // IMPORTANTE: Solo se registra aprobado_por_supervisor_en, SIN cambiar el estado
            // El cambio de estado a "En Ejecución" lo hace otro rol (ej: supervisor de producción)
            $orden->update([
                'aprobado_por_supervisor_en' => now(),
            ]);

            // Log de auditoría
            \Log::info("Orden #{$orden->numero_pedido} aprobada por " . auth()->user()->name, [
                'fecha_aprobacion' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Orden aprobada correctamente. Pendiente de envío a producción.',
                'orden' => $orden,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al aprobar orden: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la orden: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado de la orden
     */
    public function cambiarEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:No iniciado,En Ejecución,Entregado,Anulada',
        ]);

        $orden = PedidoProduccion::findOrFail($id);
        $estadoAnterior = $orden->estado;

        $orden->update(['estado' => $request->estado]);

        \Log::info("Estado de orden #{$orden->numero_pedido} cambiado", [
            'de' => $estadoAnterior,
            'a' => $request->estado,
            'usuario' => auth()->user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'orden' => $orden,
        ]);
    }

    /**
     * Obtener datos de la orden en JSON
     */
    public function obtenerDatos($id)
    {
        $orden = PedidoProduccion::with([
            'prendas' => function ($query) {
                $query->with([
                    'color',
                    'tela',
                    'tipoManga',
                    'tipoBroche',
                    'procesos'
                ]);
            },
            'cotizacion' => function ($query) {
                $query->with([
                    'prendasCotizaciones' => function ($q) {
                        $q->with([
                            'variantes' => function ($v) {
                                $v->with(['color', 'tela', 'tipoManga']);
                            }
                        ]);
                    }
                ]);
            },
            'asesora' // Cargar relación con el usuario (asesora)
        ])->findOrFail($id);

        // Forzar la evaluación de atributos calculados ANTES de convertir a array
        $descripcionPrendas = $orden->descripcion_prendas;
        $cantidadTotal = $orden->cantidad_total;
        
        // Convertir a array para incluir atributos calculados
        $ordenArray = $orden->toArray();
        
        // Asegurar que los atributos calculados estén en el array
        $ordenArray['descripcion_prendas'] = $descripcionPrendas;
        $ordenArray['cantidad_total'] = $cantidadTotal;
        
        // Calcular total entregado (similar a RegistroOrdenController)
        $totalCantidad = \DB::table('prendas_pedido')
            ->where('numero_pedido', $orden->numero_pedido)
            ->sum('cantidad');

        $totalEntregado = 0;
        try {
            $totalEntregado = \DB::table('procesos_prenda')
                ->where('numero_pedido', $orden->numero_pedido)
                ->sum('cantidad_completada');
        } catch (\Exception $e) {
            \Log::warning('Error al calcular totalEntregado', ['error' => $e->getMessage()]);
            $totalEntregado = 0;
        }

        $ordenArray['total_cantidad'] = $totalCantidad;
        $ordenArray['total_entregado'] = $totalEntregado;
        
        // Agregar nombre de cliente
        if (!empty($ordenArray['cliente'])) {
            $ordenArray['cliente_nombre'] = $ordenArray['cliente'];
        }
        
        // Agregar nombre de asesora
        if ($orden->asesora) {
            $ordenArray['asesora_nombre'] = $orden->asesora->name;
        }

        return response()->json($ordenArray);
    }

    /**
     * Obtener opciones de filtro para una columna
     */
    public function obtenerOpcionesFiltro($campo)
    {
        $opciones = [];

        switch($campo) {
            case 'numero':
                $opciones = PedidoProduccion::distinct()
                                           ->pluck('numero_pedido')
                                           ->filter()
                                           ->sort()
                                           ->values()
                                           ->toArray();
                break;
            case 'cliente':
                $opciones = PedidoProduccion::distinct()
                                           ->pluck('cliente')
                                           ->filter()
                                           ->sort()
                                           ->values()
                                           ->toArray();
                break;
            case 'estado':
                $opciones = PedidoProduccion::distinct()
                                           ->pluck('estado')
                                           ->filter()
                                           ->sort()
                                           ->values()
                                           ->toArray();
                break;
            case 'asesora':
                $opciones = PedidoProduccion::with('asesora')
                                           ->get()
                                           ->pluck('asesora.name')
                                           ->filter()
                                           ->unique()
                                           ->sort()
                                           ->values()
                                           ->toArray();
                break;
            case 'forma_pago':
                $opciones = PedidoProduccion::distinct()
                                           ->pluck('forma_de_pago')
                                           ->filter()
                                           ->sort()
                                           ->values()
                                           ->toArray();
                break;
        }

        return response()->json([
            'opciones' => $opciones,
        ]);
    }

    /**
     * Obtener notificaciones del supervisor
     */
    /**
     * Obtener notificaciones (órdenes pendientes de aprobación)
     */
    public function getNotifications()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Obtener IDs de órdenes ya vistas por el usuario
            $viewedOrdenIds = session('viewed_ordenes_' . $user->id, []);

            // Obtener todas las órdenes PENDIENTES DE APROBACIÓN
            // (sin aprobado_por_supervisor_en) QUE TENGAN COTIZACIÓN ASOCIADA Y NO ANULADAS
            $ordenesPendientes = PedidoProduccion::whereNull('aprobado_por_supervisor_en')
                ->whereNotNull('cotizacion_id')
                ->where('estado', '!=', 'Anulada')
                ->whereNotIn('id', $viewedOrdenIds)
                ->with(['asesora:id,name'])
                ->select([
                    'id', 'numero_pedido', 'cliente', 'asesor_id', 
                    'fecha_de_creacion_de_orden', 'estado', 'forma_de_pago'
                ])
                ->orderBy('fecha_de_creacion_de_orden', 'desc')
                ->get();

            // Convertir a formato de notificación
            $notificaciones = $ordenesPendientes->map(function($orden) {
                return [
                    'id' => $orden->id,
                    'numero_pedido' => $orden->numero_pedido,
                    'cliente' => $orden->cliente,
                    'asesor' => ($orden->asesora?->name) ?? 'N/A',
                    'fecha' => ($orden->fecha_de_creacion_de_orden?->format('d/m/Y H:i')) ?? '',
                    'estado' => $orden->estado,
                    'titulo' => "Orden #" . $orden->numero_pedido . " - " . $orden->cliente,
                    'mensaje' => "Cliente: {$orden->cliente} | Asesor: " . (($orden->asesora?->name) ?? 'N/A'),
                    'tipo' => 'orden_pendiente_aprobacion',
                    'timestamp' => ($orden->fecha_de_creacion_de_orden?->toIso8601String()) ?? null
                ];
            });

            // Obtener notificaciones NO LEÍDAS del usuario
            $unreadCount = $user->unreadNotifications()->count();

            return response()->json([
                'success' => true,
                'notificaciones' => $notificaciones,
                'totalPendientes' => $ordenesPendientes->count(),
                'sin_leer' => $unreadCount  // Cambiar a contar notificaciones no leídas del usuario
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener notificaciones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener notificaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllNotificationsAsRead()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Marcar notificaciones del modelo de Laravel como leídas
            $user->unreadNotifications()->update(['read_at' => now()]);

            // También guardar en sesión los IDs de órdenes pendientes
            $viewedOrdenIds = PedidoProduccion::whereNull('aprobado_por_supervisor_en')
                ->whereNotNull('cotizacion_id')
                ->where('estado', '!=', 'Anulada')
                ->pluck('id')
                ->toArray();
            
            session(['viewed_ordenes_' . $user->id => $viewedOrdenIds]);

            return response()->json([
                'success' => true,
                'message' => 'Todas las notificaciones han sido marcadas como leídas'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar notificaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar una notificación como leída
     */
    public function markNotificationAsRead($notificationId)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $notification = $user->notifications()->find($notificationId);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ], 404);
            }

            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar notificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener contador de órdenes pendientes de aprobación
     * Endpoint: GET /supervisor-pedidos/ordenes-pendientes-count
     */
    public function ordenesPendientesCount()
    {
        try {
            // Contar órdenes pendientes de aprobación
            // (sin aprobado_por_supervisor_en, no anuladas y con cotización)
            $count = PedidoProduccion::whereNull('aprobado_por_supervisor_en')
                ->whereNotNull('cotizacion_id')
                ->where('estado', '!=', 'Anulada')
                ->count();

            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => $count > 0 ? "Hay $count orden(es) pendiente(s) de aprobación" : 'No hay órdenes pendientes'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener contador de órdenes pendientes', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'count' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos del pedido y su cotización para comparación
     * GET /supervisor-pedidos/{id}/comparar
     */
    public function obtenerDatosComparacion($id)
    {
        try {
            $orden = PedidoProduccion::with([
                'prendas',
                'asesora',
                'cotizacion' => function($query) {
                    $query->with([
                        'prendas' => function($q) {
                            $q->with('tallas');
                        },
                        'asesor'
                    ]);
                }
            ])->findOrFail($id);

            $datosComparacion = [
                'pedido' => [
                    'numero' => $orden->numero_pedido,
                    'cliente' => $orden->cliente,
                    'asesora' => $orden->asesora?->name ?? 'N/A',
                    'estado' => $orden->estado,
                    'fecha' => $orden->fecha_de_creacion_de_orden,
                    'prendas' => $orden->prendas->map(function($prenda, $index) {
                        return [
                            'nombre' => $prenda->nombre_prenda,
                            'descripcion' => $prenda->generarDescripcionDetallada($index + 1),
                            'tallas' => $prenda->cantidad_talla ?? []
                        ];
                    })->toArray()
                ],
                'cotizacion' => null
            ];

            if ($orden->cotizacion) {
                $datosComparacion['cotizacion'] = [
                    'numero' => 'COT-' . str_pad($orden->cotizacion->id, 5, '0', STR_PAD_LEFT),
                    'cliente' => $orden->cotizacion->cliente?->nombre ?? $orden->cliente ?? 'N/A',
                    'asesora' => $orden->cotizacion->asesor?->name ?? 'N/A',
                    'estado' => $orden->cotizacion->estado,
                    'fecha' => $orden->cotizacion->created_at,
                    'prendas' => $orden->cotizacion->prendas->map(function($prenda, $index) {
                        $tallas = $prenda->tallas ? $prenda->tallas->pluck('talla')->toArray() : [];
                        return [
                            'nombre' => $prenda->nombre_producto,
                            'descripcion' => $prenda->generarDescripcionDetallada($index + 1),
                            'tallas' => $tallas
                        ];
                    })->toArray()
                ];
            }

            return response()->json($datosComparacion);
        } catch (\Exception $e) {
            \Log::error('Error al obtener datos de comparación', [
                'error' => $e->getMessage(),
                'orden_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de comparación'
            ], 500);
        }
    }
}