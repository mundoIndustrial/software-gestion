<?php

namespace App\Http\Controllers;

use App\Models\PedidoProduccion;
use App\Models\TablaOriginal;
use App\Events\OrdenUpdated;
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

    /**
     * Obtener datos completos del pedido para edición
     * GET /supervisor-pedidos/{id}/editar
     */
    public function edit($id)
    {
        try {
            $orden = PedidoProduccion::with([
                'prendas' => function($query) {
                    $query->with([
                        'color',
                        'tela',
                        'tipoManga',
                        'tipoBroche',
                        'fotos',
                        'fotosLogo',
                        'fotosTela'
                    ]);
                },
                'asesora'
            ])->findOrFail($id);

            // Preparar datos de prendas con todas las relaciones
            $prendasData = $orden->prendas->map(function($prenda) {
                // Convertir IDs de tallas a nombres de tallas
                $cantidadTallaConNombres = [];
                
                // Asegurar que cantidad_talla sea un array
                $cantidadTalla = $prenda->cantidad_talla;
                if (is_string($cantidadTalla)) {
                    $cantidadTalla = json_decode($cantidadTalla, true) ?? [];
                }
                
                if ($cantidadTalla && is_array($cantidadTalla)) {
                    foreach ($cantidadTalla as $tallaId => $cantidad) {
                        if ($cantidad > 0) {
                            // Buscar el nombre de la talla por ID
                            $talla = \App\Models\Talla::find($tallaId);
                            $nombreTalla = $talla ? $talla->nombre : $tallaId;
                            $cantidadTallaConNombres[$nombreTalla] = $cantidad;
                        }
                    }
                }
                
                // Parsear descripcion_variaciones en campos individuales
                $variaciones = [
                    'obs_manga' => '',
                    'obs_bolsillos' => '',
                    'obs_broche' => '',
                    'obs_reflectivo' => ''
                ];
                
                if ($prenda->descripcion_variaciones) {
                    // Parsear el texto de variaciones
                    $texto = $prenda->descripcion_variaciones;
                    
                    // Extraer Manga
                    if (preg_match('/Manga:\s*([^|]+)/', $texto, $matches)) {
                        $variaciones['obs_manga'] = trim($matches[1]);
                    }
                    
                    // Extraer Bolsillos
                    if (preg_match('/Bolsillos:\s*([^|]+)/', $texto, $matches)) {
                        $variaciones['obs_bolsillos'] = trim($matches[1]);
                    }
                    
                    // Extraer Broche
                    if (preg_match('/Broche:\s*([^|]+)/', $texto, $matches)) {
                        $variaciones['obs_broche'] = trim($matches[1]);
                    }
                    
                    // Extraer Reflectivo
                    if (preg_match('/Reflectivo:\s*(.+)$/', $texto, $matches)) {
                        $variaciones['obs_reflectivo'] = trim($matches[1]);
                    }
                }
                
                return [
                    'id' => $prenda->id,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'cantidad' => $prenda->cantidad,
                    'descripcion' => $prenda->descripcion,
                    'obs_manga' => $variaciones['obs_manga'],
                    'obs_bolsillos' => $variaciones['obs_bolsillos'],
                    'obs_broche' => $variaciones['obs_broche'],
                    'obs_reflectivo' => $variaciones['obs_reflectivo'],
                    'cantidad_talla' => $cantidadTallaConNombres,
                    'color_id' => $prenda->color_id,
                    'color_nombre' => $prenda->color?->nombre ?? null,
                    'tela_id' => $prenda->tela_id,
                    'tela_nombre' => $prenda->tela?->nombre ?? null,
                    'tipo_manga_id' => $prenda->tipo_manga_id,
                    'tipo_manga_nombre' => $prenda->tipoManga?->nombre ?? null,
                    'tipo_broche_id' => $prenda->tipo_broche_id,
                    'tipo_broche_nombre' => $prenda->tipoBroche?->nombre ?? null,
                    'tiene_bolsillos' => $prenda->tiene_bolsillos,
                    'tiene_reflectivo' => $prenda->tiene_reflectivo,
                    'fotos' => $prenda->fotos->map(function($foto) {
                        return [
                            'id' => $foto->id,
                            'ruta' => $foto->ruta_foto,
                            'url' => Storage::url($foto->ruta_foto)
                        ];
                    }),
                    'fotos_logo' => $prenda->fotosLogo->map(function($foto) {
                        return [
                            'id' => $foto->id,
                            'ruta' => $foto->ruta_foto,
                            'url' => Storage::url($foto->ruta_foto)
                        ];
                    }),
                    'fotos_tela' => $prenda->fotosTela->map(function($foto) {
                        return [
                            'id' => $foto->id,
                            'ruta' => $foto->ruta_foto,
                            'url' => Storage::url($foto->ruta_foto)
                        ];
                    })
                ];
            });

            // Obtener listas de colores y telas disponibles
            $colores = \App\Models\ColorPrenda::orderBy('nombre')->get(['id', 'nombre']);
            $telas = \App\Models\TelaPrenda::orderBy('nombre')->get(['id', 'nombre']);

            return response()->json([
                'success' => true,
                'orden' => [
                    'id' => $orden->id,
                    'numero_pedido' => $orden->numero_pedido,
                    'cliente' => $orden->cliente,
                    'cliente_id' => $orden->cliente_id,
                    'asesor_id' => $orden->asesor_id,
                    'asesora_nombre' => $orden->asesora?->name ?? 'N/A',
                    'forma_de_pago' => $orden->forma_de_pago,
                    'estado' => $orden->estado,
                    'novedades' => $orden->novedades,
                    'dia_de_entrega' => $orden->dia_de_entrega,
                    'fecha_de_creacion_de_orden' => $orden->fecha_de_creacion_de_orden?->format('Y-m-d'),
                    'fecha_estimada_de_entrega' => $orden->fecha_estimada_de_entrega?->format('Y-m-d'),
                    'prendas' => $prendasData
                ],
                'colores' => $colores,
                'telas' => $telas
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener datos para edición', [
                'error' => $e->getMessage(),
                'orden_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar pedido completo
     * PUT /supervisor-pedidos/{id}/actualizar
     */
    public function update(Request $request, $id)
    {
        try {
            $orden = PedidoProduccion::with('prendas')->findOrFail($id);

            // Validar datos básicos del pedido
            $validated = $request->validate([
                'cliente' => 'required|string|max:255',
                'forma_de_pago' => 'nullable|string|max:255',
                'novedades' => 'nullable|string',
                'dia_de_entrega' => 'nullable|integer|min:1',
                'fecha_estimada_de_entrega' => 'nullable|string',
                'prendas' => 'required|array|min:1',
                'prendas.*.id' => 'required|exists:prendas_pedido,id',
                'prendas.*.nombre_prenda' => 'required|string|max:255',
                'prendas.*.descripcion' => 'nullable|string',
                'prendas.*.obs_manga' => 'nullable|string',
                'prendas.*.obs_bolsillos' => 'nullable|string',
                'prendas.*.obs_broche' => 'nullable|string',
                'prendas.*.obs_reflectivo' => 'nullable|string',
                'prendas.*.cantidad_talla' => 'nullable|array',
                'prendas.*.color_id' => 'nullable|exists:colores_prenda,id',
                'prendas.*.tela_id' => 'nullable|exists:telas_prenda,id',
                'prendas.*.tipo_manga_id' => 'nullable|exists:tipos_manga,id',
                'prendas.*.tipo_broche_id' => 'nullable|exists:tipos_broche,id',
                'prendas.*.tiene_bolsillos' => 'nullable|boolean',
                'prendas.*.tiene_reflectivo' => 'nullable|boolean',
                'prendas.*.nuevas_fotos' => 'nullable|array',
                'prendas.*.nuevas_fotos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
                'prendas.*.nuevas_fotos_logo' => 'nullable|array',
                'prendas.*.nuevas_fotos_logo.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
                'prendas.*.nuevas_fotos_tela' => 'nullable|array',
                'prendas.*.nuevas_fotos_tela.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            ]);

            \DB::beginTransaction();

            // Preparar datos a actualizar
            $datosActualizar = [
                'cliente' => $validated['cliente'],
                'forma_de_pago' => $validated['forma_de_pago'] ?? $orden->forma_de_pago,
                'novedades' => $validated['novedades'] ?? $orden->novedades,
                'dia_de_entrega' => $validated['dia_de_entrega'] ?? $orden->dia_de_entrega,
            ];

            // Si se envió fecha_estimada_de_entrega desde el frontend (calculada)
            if (!empty($validated['fecha_estimada_de_entrega'])) {
                $datosActualizar['fecha_estimada_de_entrega'] = $validated['fecha_estimada_de_entrega'];
                \Log::info("Fecha estimada recibida del frontend para pedido {$orden->numero_pedido}: {$validated['fecha_estimada_de_entrega']}");
            }
            // Si se está actualizando dia_de_entrega y no se envió fecha_estimada, calcularla
            elseif (isset($validated['dia_de_entrega']) && $validated['dia_de_entrega'] !== null) {
                $orden->dia_de_entrega = $validated['dia_de_entrega'];
                $fechaEstimada = $orden->calcularFechaEstimada();
                if ($fechaEstimada) {
                    $datosActualizar['fecha_estimada_de_entrega'] = $fechaEstimada->format('Y-m-d H:i:s');
                    \Log::info("Fecha estimada calculada para pedido {$orden->numero_pedido}: {$fechaEstimada->format('Y-m-d H:i:s')}");
                }
            }

            // Actualizar datos del pedido
            $orden->update($datosActualizar);
            \Log::info("Pedido actualizado con datos:", $datosActualizar);

            // Actualizar cada prenda
            foreach ($validated['prendas'] as $index => $prendaData) {
                $prenda = PrendaPedido::findOrFail($prendaData['id']);
                
                // Reconstruir descripcion_variaciones desde los campos individuales
                $variacionesTexto = [];
                
                if (!empty($prendaData['obs_manga'])) {
                    $variacionesTexto[] = "Manga: " . $prendaData['obs_manga'];
                }
                
                if (!empty($prendaData['obs_bolsillos'])) {
                    $variacionesTexto[] = "Bolsillos: " . $prendaData['obs_bolsillos'];
                }
                
                if (!empty($prendaData['obs_broche'])) {
                    $variacionesTexto[] = "Broche: " . $prendaData['obs_broche'];
                }
                
                if (!empty($prendaData['obs_reflectivo'])) {
                    $variacionesTexto[] = "Reflectivo: " . $prendaData['obs_reflectivo'];
                }
                
                $descripcionVariaciones = !empty($variacionesTexto) ? implode(' | ', $variacionesTexto) : null;
                
                $prenda->update([
                    'nombre_prenda' => $prendaData['nombre_prenda'],
                    'descripcion' => $prendaData['descripcion'] ?? $prenda->descripcion,
                    'descripcion_variaciones' => $descripcionVariaciones,
                    'cantidad_talla' => $prendaData['cantidad_talla'] ?? $prenda->cantidad_talla,
                    'color_id' => $prendaData['color_id'] ?? $prenda->color_id,
                    'tela_id' => $prendaData['tela_id'] ?? $prenda->tela_id,
                    'tipo_manga_id' => $prendaData['tipo_manga_id'] ?? $prenda->tipo_manga_id,
                    'tipo_broche_id' => $prendaData['tipo_broche_id'] ?? $prenda->tipo_broche_id,
                    'tiene_bolsillos' => $prendaData['tiene_bolsillos'] ?? false,
                    'tiene_reflectivo' => $prendaData['tiene_reflectivo'] ?? false,
                ]);

                // Guardar nuevas fotos de prenda
                if ($request->hasFile("prendas.{$index}.nuevas_fotos")) {
                    foreach ($request->file("prendas.{$index}.nuevas_fotos") as $foto) {
                        $path = $foto->store('pedidos/prendas', 'public');
                        $prenda->fotos()->create(['ruta_foto' => $path]);
                    }
                }

                // Guardar nuevas fotos de logo
                if ($request->hasFile("prendas.{$index}.nuevas_fotos_logo")) {
                    foreach ($request->file("prendas.{$index}.nuevas_fotos_logo") as $foto) {
                        $path = $foto->store('pedidos/logos', 'public');
                        $prenda->fotosLogo()->create(['ruta_foto' => $path]);
                    }
                }

                // Guardar nuevas fotos de tela
                if ($request->hasFile("prendas.{$index}.nuevas_fotos_tela")) {
                    foreach ($request->file("prendas.{$index}.nuevas_fotos_tela") as $foto) {
                        $path = $foto->store('pedidos/telas', 'public');
                        $prenda->fotosTela()->create(['ruta_foto' => $path]);
                    }
                }
            }

            \DB::commit();

            // 🆕 Broadcast actualización en tiempo real
            $changedFields = [];
            if (!empty($validated['cliente'])) $changedFields[] = 'cliente';
            if (!empty($validated['forma_de_pago'])) $changedFields[] = 'forma_de_pago';
            if (!empty($validated['novedades'])) $changedFields[] = 'novedades';
            if (!empty($validated['dia_de_entrega'])) $changedFields[] = 'dia_de_entrega';
            if (!empty($validated['fecha_estimada_de_entrega'])) $changedFields[] = 'fecha_estimada_de_entrega';
            
            if (!empty($changedFields)) {
                broadcast(new \App\Events\OrdenUpdated($orden->fresh(), 'updated', $changedFields));
                \Log::info("Broadcast enviado para pedido {$orden->numero_pedido} con campos:", $changedFields);
            }

            \Log::info("Pedido #{$orden->numero_pedido} actualizado por " . auth()->user()->name);

            return response()->json([
                'success' => true,
                'message' => 'Pedido actualizado correctamente',
                'orden' => $orden->fresh('prendas')
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error al actualizar pedido', [
                'error' => $e->getMessage(),
                'orden_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar imagen de prenda
     * DELETE /supervisor-pedidos/imagen/{tipo}/{id}
     */
    public function deleteImage($tipo, $id)
    {
        try {
            $modelClass = match($tipo) {
                'prenda' => \App\Models\PrendaFotoPedido::class,
                'logo' => \App\Models\PrendaFotoLogoPedido::class,
                'tela' => \App\Models\PrendaFotoTelaPedido::class,
                default => null
            };

            if (!$modelClass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de imagen no válido'
                ], 400);
            }

            $foto = $modelClass::findOrFail($id);
            
            // Eliminar archivo físico
            if (Storage::disk('public')->exists($foto->ruta_foto)) {
                Storage::disk('public')->delete($foto->ruta_foto);
            }

            // Eliminar registro
            $foto->delete();

            \Log::info("Imagen {$tipo} eliminada", ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al eliminar imagen', [
                'error' => $e->getMessage(),
                'tipo' => $tipo,
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }
}