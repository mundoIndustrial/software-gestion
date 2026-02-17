<?php

namespace App\Http\Controllers;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\TablaOriginal;
use App\Events\OrdenUpdated;
use App\Application\Pedidos\DTOs\ObtenerFacturaDTO;
use App\Application\Pedidos\UseCases\ObtenerFacturaUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
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
        // Obtener órdenes con filtros (incluyendo borradas suavemente)
        $query = PedidoProduccion::withTrashed()->with(['asesora', 'prendas', 'cotizacion']);

        // EXCLUIR pedidos en estado pendiente_cartera o RECHAZADO_CARTERA
        $query->whereNotIn('estado', ['pendiente_cartera', 'RECHAZADO_CARTERA']);

        // FILTRO DE APROBACIÓN: Mostrar solo órdenes según su estado de aprobación
        // Si no hay parámetro aprobacion, mostrar todos los pedidos
        if ($request->filled('aprobacion')) {
            if ($request->aprobacion === 'pendiente') {
                // Órdenes PENDIENTES DE SUPERVISOR: aquellas que aún no han sido aprobadas
                // Incluye PENDIENTE_SUPERVISOR y también "No iniciado" (ya aprobadas pero sin iniciar)
                $query->whereIn('estado', ['PENDIENTE_SUPERVISOR', 'No iniciado']);
                
                // Filtrar solo órdenes con cotización de logo si el parámetro tipo=logo está presente
                if ($request->filled('tipo') && $request->tipo === 'logo') {
                    $query->whereHas('cotizacion', function($q) {
                        $q->where('tipo', 'logo');
                    });
                }
            } elseif ($request->aprobacion === 'aprobadas') {
                // Órdenes aprobadas: las que ya fueron aprobadas (estado Pendiente o posteriores)
                $query->whereIn('estado', ['Pendiente', 'En Ejecución', 'Finalizada', 'Anulada']);
            }
        }
        // Si no hay parámetro, mostrar TODOS los pedidos (sin filtro de aprobación)

        // Búsqueda general por pedido o cliente
        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function($q) use ($busqueda) {
                $q->where('numero_pedido', 'like', '%' . $busqueda . '%')
                  ->orWhere('cliente', 'like', '%' . $busqueda . '%');
            });
        }

        // Filtro por número de pedido (para filtros de columna)
        if ($request->filled('numero')) {
            $numeros = explode(',', $request->numero);
            $query->whereIn('numero_pedido', $numeros);
        }

        // Filtro por cliente (para filtros de columna)
        if ($request->filled('cliente')) {
            $clientes = explode(',', $request->cliente);
            $query->whereIn('cliente', $clientes);
        }

        // Filtro por forma de pago (para filtros de columna)
        if ($request->filled('forma_pago')) {
            $formasPago = explode(',', $request->forma_pago);
            $query->whereIn('forma_de_pago', $formasPago);
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
        } else {
            // Si NO hay filtro de estado específico, EXCLUIR pedidos anulados de la vista principal
            $query->where('estado', '!=', 'Anulada');
        }

        // Filtro por asesora (por nombre)
        if ($request->filled('asesora')) {
            $asesoras = explode(',', $request->asesora);
            $query->whereHas('asesora', function($q) use ($asesoras) {
                $q->whereIn('name', $asesoras);
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
        $orden = PedidoProduccion::with([
            'prendas' => function($q) {
                $q->with(['color', 'tela', 'tipoManga', 'tipoBrocheBoton']);
            },
            'prendas.procesos'
        ])->findOrFail($id);

        $pdf = Pdf::loadView('supervisor-pedidos.pdf', compact('orden'));
        
        return $pdf->download('Orden_' . $orden->numero_pedido . '.pdf');
    }

    /**
     * Aprobar orden (cambiar estado de PENDIENTE_SUPERVISOR a Pendiente)
     */
    public function aprobar($id)
    {
        try {
            $orden = PedidoProduccion::findOrFail($id);
            
            // Verificar que está en estado PENDIENTE_SUPERVISOR
            if ($orden->estado !== 'PENDIENTE_SUPERVISOR') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta orden no está pendiente de aprobación'
                ], 400);
            }

            // Obtener usuario actual
            $usuario = auth()->user();
            $nombreUsuario = strtoupper($usuario->name ?? $usuario->email ?? 'Sistema');
            $rol = 'Usuario';
            
            if (method_exists($usuario, 'getRoleNames')) {
                $roles = $usuario->getRoleNames();
                if ($roles && count($roles) > 0) {
                    $rol = strtoupper($roles[0]);
                }
            }

            // Crear novedad de aprobación
            $fechaActual = now();
            $fechaFormato = $fechaActual->format('d/m/Y') . '-' . $fechaActual->format('g:iA');
            $linea_novedad = "{$nombreUsuario}-{$rol}-{$fechaFormato}\nAPROBACIÓN: Pedido aprobado por supervisor";

            // Actualizar estado
            $orden->update([
                'estado' => 'PENDIENTE_INSUMOS',
                'area' => 'Insumos',
                'aprobado_por_supervisor_en' => now()
            ]);

            // Agregar novedad
            if (!empty($orden->novedades)) {
                $orden->novedades .= "\n\n" . str_repeat("-", 60) . "\n" . $linea_novedad;
            } else {
                $orden->novedades = $linea_novedad;
            }
            $orden->save();

            // Registrar en log
            \Log::info('Pedido aprobado por supervisor', [
                'pedido_id' => $id,
                'numero_pedido' => $orden->numero_pedido,
                'usuario' => $usuario->name,
                'timestamp' => now()
            ]);

            //  Broadcast evento en tiempo real (temporalmente deshabilitado para diagnóstico)
            // broadcast(new \App\Events\OrdenUpdated($orden->fresh(), 'updated', ['estado', 'novedades']));
            \Log::info("Broadcast OMITIDO para pedido {$orden->numero_pedido} - Aprobación (diagnóstico)");

            return response()->json([
                'success' => true,
                'message' => 'Pedido aprobado correctamente. Estado cambiado a Pendiente Insumos y consecutivos generados.',
                'estado' => 'Pendiente Insumos'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al aprobar pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar el pedido: ' . $e->getMessage()
            ], 500);
        }
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
        $usuario = auth()->user();

        // Obtener información del usuario para la novedad
        $nombreUsuario = 'Sistema';
        $rol = 'Usuario';
        
        if ($usuario) {
            $nombreUsuario = strtoupper($usuario->name ?? $usuario->email ?? 'Sistema');
            
            // Obtener rol
            if (method_exists($usuario, 'getRoleNames')) {
                $roles = $usuario->getRoleNames();
                if ($roles && count($roles) > 0) {
                    $rol = strtoupper($roles[0]);
                }
            } elseif (method_exists($usuario, 'roles')) {
                $usuarioRoles = $usuario->roles();
                if ($usuarioRoles) {
                    $primerRol = $usuarioRoles->first();
                    if ($primerRol) {
                        $rol = strtoupper($primerRol->name ?? 'Usuario');
                    }
                }
            }
        }
        
        // Construir novedad con formato: NOMBRE-ROL-dd/mm/yyyy-h:mmAM/PM
        // seguido de salto de línea y el motivo
        $fechaActual = now();
        $fechaFormato = $fechaActual->format('d/m/Y') . '-' . $fechaActual->format('g:iA');
        $linea_novedad = "{$nombreUsuario}-{$rol}-{$fechaFormato}\nPASAR A REVISIÓN: {$request->motivo_anulacion}";

        // Actualizar estado
        // IMPORTANTE: Se registra aprobado_por_supervisor_en para marcar que el supervisor ha actuado sobre la orden
        // Esto hace que la orden aparezca en el registro (tanto si es aprobada como si es revisada)
        $orden->update([
            'estado' => 'DEVUELTO_A_ASESORA',
            'motivo_revision' => $request->motivo_anulacion,
            'fecha_revision' => now(),
            'usuario_revision' => auth()->user()->name,
            'aprobado_por_supervisor_en' => now(), // Registrar acción del supervisor
        ]);

        // Agregar novedad al campo novedades del pedido de forma segura
        // Se recarga el modelo para evitar conflictos de concurrencia
        $orden->refresh();
        
        $novedadCompleta = "\n" . str_repeat("=", 70) . "\n" . $linea_novedad . "\n" . str_repeat("=", 70);
        
        if (!empty($orden->novedades)) {
            $orden->novedades .= $novedadCompleta;
        } else {
            $orden->novedades = $linea_novedad;
        }
        $orden->save();

        // Log de auditoría
        \Log::info("Orden #{$orden->numero_pedido} enviada a revisión por " . auth()->user()->name, [
            'motivo' => $request->motivo_anulacion,
            'fecha' => now(),
            'usuario' => $nombreUsuario,
            'rol' => $rol,
        ]);

        //  Broadcast evento en tiempo real (temporalmente deshabilitado para diagnóstico)
        // broadcast(new \App\Events\OrdenUpdated($orden->fresh(), 'updated', ['estado', 'novedades', 'motivo_revision']));
        \Log::info("Broadcast OMITIDO para pedido {$orden->numero_pedido} - Revisión (diagnóstico)");

        return response()->json([
            'success' => true,
            'message' => 'Orden enviada a revisión correctamente',
            'orden' => $orden,
        ]);
    }

    /**
     * Aprobar orden y enviarla a producción
     */
    public function aprobarOrden($id)
    {
        try {
            $orden = PedidoProduccion::with('cotizacion.tipoCotizacion')->findOrFail($id);

            // Verificar que la orden esté en estado "PENDIENTE_SUPERVISOR"
            if ($orden->estado !== 'PENDIENTE_SUPERVISOR') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden aprobar órdenes en estado "PENDIENTE_SUPERVISOR"'
                ], 422);
            }

            // Determinar si es una cotización de tipo reflectivo
            $esReflectivo = false;
            if ($orden->cotizacion && $orden->cotizacion->tipoCotizacion) {
                $tipoCotizacion = strtolower(trim($orden->cotizacion->tipoCotizacion->nombre ?? ''));
                $esReflectivo = ($tipoCotizacion === 'reflectivo');
            }

            // Actualizar estado según el tipo de cotización
            if ($esReflectivo) {
                // Para pedidos reflectivos: estado "En Ejecución" y área "Costura"
                $orden->update([
                    'aprobado_por_supervisor_en' => now(),
                    'estado' => 'En Ejecución',
                    'area' => 'Costura',
                ]);
                
                \Log::info("Orden REFLECTIVA #{$orden->numero_pedido} aprobada por supervisor " . auth()->user()->name, [
                    'fecha_aprobacion' => now(),
                    'estado_anterior' => 'PENDIENTE_SUPERVISOR',
                    'estado_nuevo' => 'En Ejecución',
                    'area' => 'Costura',
                    'tipo_cotizacion' => $orden->cotizacion->tipoCotizacion->nombre ?? 'N/A',
                ]);

                //  Broadcast evento en tiempo real (temporalmente deshabilitado para diagnóstico)
                // broadcast(new \App\Events\OrdenUpdated($orden->fresh(), 'updated', ['estado', 'area']));
                \Log::info("Broadcast OMITIDO para pedido {$orden->numero_pedido} - Aprobación Reflectiva (diagnóstico)");

                return response()->json([
                    'success' => true,
                    'message' => 'Pedido reflectivo aprobado correctamente. Enviado directamente a Costura en estado "En Ejecución".',
                    'orden' => $orden->fresh(),
                ]);
            } else {
                // Para pedidos normales: estado "PENDIENTE_INSUMOS" y área "Insumos"
                $orden->update([
                    'aprobado_por_supervisor_en' => now(),
                    'estado' => 'PENDIENTE_INSUMOS',
                    'area' => 'Insumos',
                ]);
                
                \Log::info("Orden #{$orden->numero_pedido} aprobada por supervisor " . auth()->user()->name, [
                    'fecha_aprobacion' => now(),
                    'estado_anterior' => 'PENDIENTE_SUPERVISOR',
                    'estado_nuevo' => 'PENDIENTE_INSUMOS',
                    'area' => 'Insumos',
                ]);

                //  Broadcast evento en tiempo real (temporalmente deshabilitado para diagnóstico)
                // broadcast(new \App\Events\OrdenUpdated($orden->fresh(), 'updated', ['estado', 'area']));
                \Log::info("Broadcast OMITIDO para pedido {$orden->numero_pedido} - Aprobación Normal (diagnóstico)");

                return response()->json([
                    'success' => true,
                    'message' => 'Pedido aprobado correctamente. Ahora está disponible para el módulo de insumos.',
                    'orden' => $orden->fresh(),
                ]);
            }

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
     * EXACTAMENTE IGUAL que RegistroOrdenQueryController::show()
     */
    public function obtenerDatos($id)
    {
        // Buscar por ID (supervisor usa ID, no numero_pedido)
        $orden = PedidoProduccion::with([
            'asesora', 
            'prendas',
            'prendas.fotos',
            'prendas.fotosTelas',
            'cotizacion.tipoCotizacion'
        ])->findOrFail($id);

        // Obtener estadísticas - sumar cantidad desde prenda_pedido_tallas
        $totalCantidad = \DB::table('prenda_pedido_tallas')
            ->join('prendas_pedido', 'prenda_pedido_tallas.prenda_pedido_id', '=', 'prendas_pedido.id')
            ->where('prendas_pedido.pedido_produccion_id', $orden->id)
            ->sum('prenda_pedido_tallas.cantidad');

        $totalEntregado = ($orden->estado === 'Entregado') ? $totalCantidad : 0;

        $orden->total_cantidad = $totalCantidad;
        $orden->total_entregado = $totalEntregado;

        // Convertir a array
        $ordenArray = $orden->toArray();
        
        // Verificar si es una cotización
        $esCotizacion = !empty($orden->cotizacion_id);
        $ordenArray['es_cotizacion'] = $esCotizacion;
        
        // Agregar nombres en lugar de IDs
        if ($orden->asesora) {
            $ordenArray['asesor'] = $orden->asesora->name ?? '';
            $ordenArray['asesora'] = $orden->asesora->name ?? '';
            $ordenArray['asesora_nombre'] = $orden->asesora->name ?? '';
        } else {
            $ordenArray['asesor'] = '';
            $ordenArray['asesora'] = '';
            $ordenArray['asesora_nombre'] = '';
        }
        
        // Cliente
        if (!empty($ordenArray['cliente'])) {
            $ordenArray['cliente_nombre'] = $ordenArray['cliente'];
        }
        
        // Construir descripción con tallas POR PRENDA (como en RegistroOrdenQueryController)
        $ordenArray['descripcion_prendas'] = $this->buildDescripcionConTallas($orden);
        
        // Obtener prendas formateadas para el modal
        $prendasFormato = [];
        
        if ($orden->prendas && $orden->prendas->count() > 0) {
            foreach ($orden->prendas as $index => $prenda) {
                $colorNombre = null;
                $telaNombre = null;
                $telaReferencia = null;
                $tipoMangaNombre = null;
                $tipoBrocheNombre = null;
                
                try {
                    if ($prenda->color_id) {
                        $color = \App\Models\ColorPrenda::find($prenda->color_id);
                        $colorNombre = $color ? $color->nombre : null;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error obteniendo color', ['error' => $e->getMessage()]);
                }
                
                try {
                    if ($prenda->tela_id) {
                        $tela = \App\Models\TelaPrenda::find($prenda->tela_id);
                        if ($tela) {
                            $telaNombre = $tela->nombre;
                            $telaReferencia = $tela->referencia;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error obteniendo tela', ['error' => $e->getMessage()]);
                }
                
                try {
                    if ($prenda->tipo_manga_id) {
                        $tipoManga = \App\Models\TipoManga::find($prenda->tipo_manga_id);
                        $tipoMangaNombre = $tipoManga ? $tipoManga->nombre : null;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error obteniendo manga', ['error' => $e->getMessage()]);
                }
                
                try {
                    if ($prenda->tipo_broche_id) {
                        $tipoBroche = \App\Models\TipoBrocheBoton::find($prenda->tipo_broche_id);
                        $tipoBrocheNombre = $tipoBroche ? $tipoBroche->nombre : null;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error obteniendo broche', ['error' => $e->getMessage()]);
                }
                
                $prendasFormato[] = [
                    'numero' => $index + 1,
                    'nombre' => $prenda->nombre_prenda ?? '-',
                    'descripcion' => $prenda->descripcion ?? '-',
                    'descripcion_variaciones' => $prenda->descripcion_variaciones ?? '',
                    'cantidad_talla' => $prenda->cantidad_talla ?? '-',
                    'color' => $colorNombre,
                    'tela' => $telaNombre,
                    'tela_referencia' => $telaReferencia,
                    'tipo_manga' => $tipoMangaNombre,
                    'tipo_broche' => $tipoBrocheNombre,
                    'tiene_bolsillos' => $prenda->tiene_bolsillos ?? 0,
                    'tiene_reflectivo' => $prenda->tiene_reflectivo ?? 0,
                ];
            }
            
            $ordenArray['prendas'] = $prendasFormato;
        }

        return response()->json($ordenArray);
    }

    /**
     * Obtener datos de factura para mostrar en modal - DELEGADO A USE CASE
     * Copia del método en AsesoresController para mantener consistencia
     */
    public function obtenerDatosFactura($id)
    {
        Log::warning(' [CONTROLLER-FACTURA-SUPERVISOR] ENDPOINT LLAMADO ', ['pedido_id' => $id]);
        
        try {
            //  LOGS DE DIAGNÓSTICO - AUTENTICACIÓN Y AUTORIZACIÓN
            $usuarioAutenticado = Auth::user();
            Log::info('[DIAGNÓSTICO-SUPERVISOR] Verificando autenticación y autorización', [
                'usuario_id' => $usuarioAutenticado ? $usuarioAutenticado->id : 'NO_AUTENTICADO',
                'usuario_nombre' => $usuarioAutenticado ? $usuarioAutenticado->name : 'ANÓNIMO',
                'usuario_email' => $usuarioAutenticado ? $usuarioAutenticado->email : 'N/A',
                'pedido_id' => $id,
                'ruta_accedida' => Route::getCurrentRoute()->uri ?? 'desconocida',
                'método_http' => request()->getMethod(),
            ]);
            
            //  OBTENER ROLES DEL USUARIO
            if ($usuarioAutenticado) {
                $rolesUsuario = $usuarioAutenticado->roles()->pluck('name')->toArray();
                
                //  EXTENSIÓN: APLICAR JERARQUÍA DE ROLES (herencia)
                $rolesConHerencia = \App\Services\RoleHierarchyService::getEffectiveRoles($rolesUsuario);
                
                Log::info('[DIAGNÓSTICO-SUPERVISOR] Roles y permisos del usuario', [
                    'usuario_id' => $usuarioAutenticado->id,
                    'roles' => $rolesUsuario,
                    'roles_con_herencia' => $rolesConHerencia,
                    'tiene_supervisor_pedidos' => in_array('supervisor_pedidos', $rolesConHerencia),
                    'tiene_asesor' => in_array('asesor', $rolesConHerencia),
                    'tiene_admin' => in_array('admin', $rolesConHerencia),
                ]);
            }
            
            Log::info('[CONTROLLER-FACTURA-SUPERVISOR] Obteniendo datos de factura para pedido: ' . $id);
            
            // Crear DTO para el Use Case
            $dto = ObtenerFacturaDTO::fromRequest((string)$id);

            // Obtener Use Case desde container (inyectado por Laravel)
            $obtenerFacturaUseCase = app(ObtenerFacturaUseCase::class);
            
            // Usar el Use Case DDD
            $datos = $obtenerFacturaUseCase->ejecutar($dto);
            
            Log::info('[CONTROLLER-FACTURA-SUPERVISOR] Datos obtenidos correctamente', [
                'pedido_id' => $id,
                'prendas_count' => count($datos['prendas'] ?? []),
                'procesos_total' => collect($datos['prendas'] ?? [])->sum(fn($p) => count($p['procesos'] ?? []))
            ]);
            
            // LOG CRÍTICO ANTES DE ENVIAR JSON
            if (!empty($datos['prendas'])) {
                foreach ($datos['prendas'] as $idx => $prenda) {
                    Log::warning('[CONTROLLER-FACTURA-SUPERVISOR-TELAS] Verificación ANTES de JSON', [
                        'prenda_idx' => $idx,
                        'prenda_nombre' => $prenda['nombre'] ?? 'N/A',
                        'tiene_telas_array' => isset($prenda['telas_array']),
                        'telas_array_count' => count($prenda['telas_array'] ?? []),
                        'telas_array_full' => json_encode($prenda['telas_array'] ?? []),
                    ]);
                }
            }
            
            Log::info(' [CONTROLLER-FACTURA-SUPERVISOR] Datos de factura obtenidos exitosamente');
            
            //  LOG FINAL: Verificar estructura exacta antes de retornar
            Log::info('[CONTROLLER-FACTURA-SUPERVISOR-JSON-RESPONSE] Estructura JSON final que se envía', [
                'estructura_keys' => array_keys($datos),
                'tiene_prendas' => isset($datos['prendas']),
                'prendas_count' => count($datos['prendas'] ?? []),
                'prendas_vacio' => empty($datos['prendas']),
                'prendas_tipo' => gettype($datos['prendas'] ?? null),
                'prendas_es_array' => is_array($datos['prendas'] ?? false),
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $datos
            ]);
        } catch (\Exception $e) {
            $usuarioAutenticado = Auth::user();
            Log::error(' [CONTROLLER-FACTURA-SUPERVISOR] ERROR obteniendo datos de factura', [
                'pedido_id' => $id,
                'usuario_id' => $usuarioAutenticado ? $usuarioAutenticado->id : 'N/A',
                'usuario_nombre' => $usuarioAutenticado ? $usuarioAutenticado->name : 'N/A',
                'error_mensaje' => $e->getMessage(),
                'error_código' => $e->getCode(),
                'error_clase' => get_class($e),
                'archivo' => $e->getFile(),
                'línea' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error obteniendo datos de la factura: ' . $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
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
            // Contar órdenes con estado 'PENDIENTE_SUPERVISOR' (todas)
            $totalPendientes = PedidoProduccion::where('estado', 'PENDIENTE_SUPERVISOR')
                ->count();
                
            // Contar solo las órdenes de logo pendientes
            $pendientesLogo = PedidoProduccion::where('estado', 'PENDIENTE_SUPERVISOR')
                ->whereHas('cotizacion', function($q) {
                    $q->where('tipo', 'logo');
                })
                ->count();

            return response()->json([
                'success' => true,
                'count' => $totalPendientes,
                'pendientesLogo' => $pendientesLogo,
                'message' => $totalPendientes > 0 ? "Hay $totalPendientes orden(es) pendiente(s)" : 'No hay órdenes pendientes'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener contador de órdenes pendientes', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'count' => 0,
                'pendientesLogo' => 0,
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
                // ACTUALIZACIÓN [16/01/2026]: Cambio de tipo_broche_id a tipo_broche_boton_id
                'prendas.*.tela_id' => 'nullable|exists:telas_prenda,id',
                'prendas.*.tipo_manga_id' => 'nullable|exists:tipos_manga,id',
                'prendas.*.tipo_broche_boton_id' => 'nullable|exists:tipos_broche_boton,id',
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
                
                $descripcionVariaciones = implode(' | ', $variacionesTexto);
                
                $prenda->update([
                    'nombre_prenda' => $prendaData['nombre_prenda'] ?? $prenda->nombre_prenda,
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

                // Guardar nuevas fotos de prenda (convertir a webp)
                if ($request->hasFile("prendas.{$index}.nuevas_fotos")) {
                    foreach ($request->file("prendas.{$index}.nuevas_fotos") as $foto) {
                        $pathWebp = $this->guardarImagenComoWebp($foto, $orden->numero_pedido, 'prendas');
                        $prenda->fotos()->create([
                            'ruta_original' => $pathWebp,
                            'ruta_webp' => $pathWebp
                        ]);
                    }
                }

                // Guardar nuevas fotos de logo (convertir a webp)
                if ($request->hasFile("prendas.{$index}.nuevas_fotos_logo")) {
                    foreach ($request->file("prendas.{$index}.nuevas_fotos_logo") as $foto) {
                        $pathWebp = $this->guardarImagenComoWebp($foto, $orden->numero_pedido, 'logos');
                        $prenda->fotosLogo()->create([
                            'ruta_original' => $pathWebp,
                            'ruta_webp' => $pathWebp
                        ]);
                    }
                }

                // Guardar nuevas fotos de tela (convertir a webp)
                if ($request->hasFile("prendas.{$index}.nuevas_fotos_tela")) {
                    foreach ($request->file("prendas.{$index}.nuevas_fotos_tela") as $foto) {
                        $pathWebp = $this->guardarImagenComoWebp($foto, $orden->numero_pedido, 'telas');
                        $prenda->fotosTela()->create([
                            'ruta_original' => $pathWebp,
                            'ruta_webp' => $pathWebp
                        ]);
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
                // broadcast(new \App\Events\OrdenUpdated($orden->fresh(), 'updated', $changedFields));
                \Log::info("Broadcast OMITIDO para pedido {$orden->numero_pedido} con campos:", $changedFields);
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
            
            \Log::info("🗑️ Iniciando eliminación de imagen {$tipo}", [
                'id' => $id,
                'ruta_original' => $foto->ruta_original ?? 'N/A',
                'ruta_webp' => $foto->ruta_webp ?? 'N/A'
            ]);
            
            // Eliminar archivos físicos (tanto original como webp si existen)
            $archivosEliminados = [];
            
            if (isset($foto->ruta_original) && Storage::disk('public')->exists($foto->ruta_original)) {
                Storage::disk('public')->delete($foto->ruta_original);
                $archivosEliminados[] = $foto->ruta_original;
                \Log::info(" Archivo original eliminado: {$foto->ruta_original}");
            }
            
            if (isset($foto->ruta_webp) && $foto->ruta_webp !== $foto->ruta_original && Storage::disk('public')->exists($foto->ruta_webp)) {
                Storage::disk('public')->delete($foto->ruta_webp);
                $archivosEliminados[] = $foto->ruta_webp;
                \Log::info(" Archivo webp eliminado: {$foto->ruta_webp}");
            }

            // Eliminar registro de la base de datos permanentemente (forceDelete porque usa SoftDeletes)
            $foto->forceDelete();
            \Log::info(" Registro de BD eliminado permanentemente para imagen {$tipo}", [
                'id' => $id,
                'archivos_eliminados' => $archivosEliminados
            ]);

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

    /**
     * Construir descripción con tallas por prenda (igual que en módulo asesores)
     * Usa el método generarDescripcionDetallada de cada prenda para obtener la descripción completa
     * 
     * @param PedidoProduccion $order
     * @return string
     */
    private function buildDescripcionConTallas($order)
    {
        if (!$order->prendas || $order->prendas->isEmpty()) {
            return '';
        }

        // Generar descripción detallada para TODAS las prendas usando el método del modelo
        // Esto incluye automáticamente: Color, Tela, Manga, Reflectivo, Bolsillos, Broche y Tallas
        $totalPrendas = $order->prendas->count();
        $descripciones = $order->prendas->map(function($prenda, $index) use ($totalPrendas) {
            return $prenda->generarDescripcionDetallada($index + 1, $totalPrendas);
        })->toArray();

        return implode("\n\n", $descripciones);
    }

    /**
     * Guardar imagen como WebP en carpeta del pedido
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $numeroPedido ID del pedido
     * @param string $tipo Tipo de imagen: 'prendas', 'logos', 'telas'
     * @return string Ruta relativa del archivo guardado
     */
    private function guardarImagenComoWebp($file, $numeroPedido, $tipo)
    {
        try {
            // Generar nombre único
            $nombreUnico = time() . '_' . uniqid() . '.webp';
            
            // Construir ruta: storage/app/public/pedidos/{numeroPedido}/{tipo}
            $carpetaRelativa = "pedidos/{$numeroPedido}/{$tipo}";
            $rutaCompleta = storage_path("app/public/{$carpetaRelativa}");
            
            // Crear directorio si no existe
            if (!\File::exists($rutaCompleta)) {
                \File::makeDirectory($rutaCompleta, 0755, true);
                \Log::info('📁 Carpeta creada', ['ruta' => $rutaCompleta]);
            }
            
            // Usar Intervention Image para convertir a webp
            $manager = \Intervention\Image\ImageManager::gd();
            $imagen = $manager->read($file->getRealPath());
            
            // Guardar como webp con calidad 85
            $rutaArchivo = $rutaCompleta . '/' . $nombreUnico;
            $imagen->toWebp(85)->save($rutaArchivo);
            
            \Log::info(' Imagen guardada como webp', [
                'nombre' => $nombreUnico,
                'numero_pedido' => $numeroPedido,
                'tipo' => $tipo,
                'ruta_completa' => $rutaArchivo,
                'ruta_relativa' => $carpetaRelativa . '/' . $nombreUnico
            ]);
            
            // Retornar ruta relativa para la base de datos
            return $carpetaRelativa . '/' . $nombreUnico;
            
        } catch (\Exception $e) {
            \Log::error(' Error al convertir imagen a webp: ' . $e->getMessage());
            // Fallback: guardar sin conversión en carpeta del pedido
            return $file->store("pedidos/{$numeroPedido}/{$tipo}", 'public');
        }
    }
}