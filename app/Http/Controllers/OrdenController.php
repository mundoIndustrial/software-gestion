<?php

namespace App\Http\Controllers;

use App\Models\OrdenAsesor;
use App\Models\ProductoPedido;
use App\Models\PedidoProduccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrdenController extends Controller
{

    /**
     * Listar órdenes confirmadas
     */
    public function index(Request $request)
    {
        $asesorId = Auth::id();
        
        $query = OrdenAsesor::delAsesor($asesorId)->confirmados()->with('productos');

        if ($request->filled('cliente')) {
            $query->where('cliente', 'LIKE', '%' . $request->cliente . '%');
        }

        $ordenes = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('asesores.ordenes.index', compact('ordenes'));
    }

    /**
     * Listar borradores (órdenes sin confirmar)
     */
    public function borradores(Request $request)
    {
        $asesorId = Auth::id();
        
        $query = OrdenAsesor::delAsesor($asesorId)->borradores()->with('productos');

        if ($request->filled('cliente')) {
            $query->where('cliente', 'LIKE', '%' . $request->cliente . '%');
        }

        $borradores = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('asesores.borradores.index', compact('borradores'));
    }

    /**
     * Mostrar formulario para crear orden
     */
    public function create()
    {
        $prioridades = ['baja', 'media', 'alta', 'urgente'];
        
        return view('asesores.ordenes.create', compact('prioridades'));
    }

    /**
     * Guardar borrador
     */
    public function guardarBorrador(Request $request)
    {
        $validated = $request->validate([
            'cliente' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'descripcion' => 'nullable|string',
            'novedades' => 'nullable|string',
            'monto_total' => 'nullable|numeric|min:0',
            'cantidad_prendas' => 'nullable|integer|min:0',
            'prioridad' => 'nullable|in:baja,media,alta,urgente',
            'forma_de_pago' => 'nullable|string|max:69',
            'estado' => 'nullable|string',
            'area' => 'nullable|string',
            'fecha_entrega' => 'nullable|date|after_or_equal:today',
            'productos' => 'nullable|array',
            'productos.*.nombre_producto' => 'required_with:productos|string',
            'productos.*.descripcion' => 'nullable|string',
            'productos.*.tela' => 'nullable|string',
            'productos.*.tipo_manga' => 'nullable|string',
            'productos.*.color' => 'nullable|string',
            'productos.*.talla' => 'nullable|string',
            'productos.*.genero' => 'nullable|string',
            'productos.*.cantidad' => 'required_with:productos|integer|min:1',
            'productos.*.ref_hilo' => 'nullable|string',
            'productos.*.notas' => 'nullable|string',
            'productos.*.precio_unitario' => 'nullable|numeric|min:0',
        ]);

        try {
            $resultado = DB::transaction(function () use ($validated) {
                // Crear borrador
                $orden = OrdenAsesor::create([
                    'numero_orden' => 'TEMP-' . uniqid(),
                    'asesor_id' => Auth::id(),
                    'cliente' => $validated['cliente'],
                    'telefono' => $validated['telefono'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'descripcion' => $validated['descripcion'] ?? null,
                    'monto_total' => $validated['monto_total'] ?? 0,
                    'cantidad_prendas' => $validated['cantidad_prendas'] ?? 0,
                    'estado' => 'pendiente',
                    'estado_pedido' => 'borrador',
                    'es_borrador' => true,
                    'pedido' => null,
                    'prioridad' => $validated['prioridad'] ?? 'media',
                    'fecha_entrega' => $validated['fecha_entrega'] ?? null,
                ]);

                // Guardar productos si existen
                if (!empty($validated['productos'])) {
                    foreach ($validated['productos'] as $productoData) {
                        $subtotal = null;
                        if (isset($productoData['precio_unitario']) && isset($productoData['cantidad'])) {
                            $subtotal = $productoData['precio_unitario'] * $productoData['cantidad'];
                        }

                        ProductoPedido::create([
                            'orden_asesor_id' => $orden->id,
                            'nombre_producto' => $productoData['nombre_producto'],
                            'descripcion' => $productoData['descripcion'] ?? null,
                            'talla' => $productoData['talla'] ?? null,
                            'cantidad' => $productoData['cantidad'],
                            'precio_unitario' => $productoData['precio_unitario'] ?? null,
                            'subtotal' => $subtotal,
                        ]);
                    }
                    
                    // Actualizar cantidad de prendas
                    $cantidadTotal = collect($validated['productos'])->sum('cantidad');
                    $orden->update(['cantidad_prendas' => $cantidadTotal]);
                }

                return [
                    'success' => true,
                    'message' => 'Borrador guardado exitosamente',
                    'identificador' => $orden->identificador,
                    'orden_id' => $orden->id,
                    'redirect' => route('asesores.borradores.index')
                ];
            });

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el borrador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar y editar borrador
     */
    public function edit($id)
    {
        $asesorId = Auth::id();
        $orden = OrdenAsesor::where('id', $id)
            ->where('asesor_id', $asesorId)
            ->with('productos')
            ->firstOrFail();

        // Verificar que sea borrador
        if (!$orden->esBorrador()) {
            return redirect()->route('asesores.ordenes.show', $id)
                ->with('info', 'Esta orden ya está confirmada');
        }

        $prioridades = ['baja', 'media', 'alta', 'urgente'];

        return view('asesores.ordenes.edit', compact('orden', 'prioridades'));
    }

    /**
     * Actualizar borrador
     */
    public function update(Request $request, $id)
    {
        $asesorId = Auth::id();
        $orden = OrdenAsesor::where('id', $id)
            ->where('asesor_id', $asesorId)
            ->firstOrFail();

        // Verificar que sea borrador
        if (!$orden->esBorrador()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede editar una orden confirmada'
            ], 403);
        }

        $validated = $request->validate([
            'cliente' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'descripcion' => 'nullable|string',
            'monto_total' => 'nullable|numeric|min:0',
            'cantidad_prendas' => 'nullable|integer|min:0',
            'prioridad' => 'nullable|in:baja,media,alta,urgente',
            'fecha_entrega' => 'nullable|date|after_or_equal:today',
            'productos' => 'nullable|array',
            'productos.*.nombre_producto' => 'required_with:productos|string',
            'productos.*.descripcion' => 'nullable|string',
            'productos.*.talla' => 'nullable|string',
            'productos.*.cantidad' => 'required_with:productos|integer|min:1',
            'productos.*.precio_unitario' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($orden, $validated) {
            // Actualizar orden
            $orden->update([
                'cliente' => $validated['cliente'],
                'telefono' => $validated['telefono'] ?? $orden->telefono,
                'email' => $validated['email'] ?? $orden->email,
                'descripcion' => $validated['descripcion'] ?? $orden->descripcion,
                'monto_total' => $validated['monto_total'] ?? $orden->monto_total,
                'cantidad_prendas' => $validated['cantidad_prendas'] ?? $orden->cantidad_prendas,
                'prioridad' => $validated['prioridad'] ?? $orden->prioridad,
                'fecha_entrega' => $validated['fecha_entrega'] ?? $orden->fecha_entrega,
            ]);

            // Actualizar productos
            if (!empty($validated['productos'])) {
                // Eliminar productos antiguos
                ProductoPedido::where('pedido', $orden->id)->delete();

                $cantidadTotal = 0;
                foreach ($validated['productos'] as $productoData) {
                    $subtotal = null;
                    if (isset($productoData['precio_unitario']) && isset($productoData['cantidad'])) {
                        $subtotal = $productoData['precio_unitario'] * $productoData['cantidad'];
                    }

                    ProductoPedido::create([
                        'pedido' => $orden->id,
                        'nombre_producto' => $productoData['nombre_producto'],
                        'descripcion' => $productoData['descripcion'] ?? null,
                        'talla' => $productoData['talla'] ?? null,
                        'cantidad' => $productoData['cantidad'],
                        'precio_unitario' => $productoData['precio_unitario'] ?? null,
                        'subtotal' => $subtotal,
                    ]);

                    $cantidadTotal += $productoData['cantidad'];
                }

                // Actualizar cantidad de prendas
                $orden->update(['cantidad_prendas' => $cantidadTotal]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Borrador actualizado exitosamente',
                'identificador' => $orden->fresh()->identificador
            ]);
        });
    }

    /**
     * Confirmar orden (crear pedido con número)
     */
    public function confirmar(Request $request, $id)
    {
        $asesorId = Auth::id();
        $orden = OrdenAsesor::where('id', $id)
            ->where('asesor_id', $asesorId)
            ->with('productos')
            ->firstOrFail();

        // Verificar que sea borrador
        if (!$orden->esBorrador()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden ya está confirmada'
            ], 400);
        }

        // Validaciones
        if ($orden->productos->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'La orden debe tener al menos un producto'
            ], 400);
        }

        if (empty($orden->cliente)) {
            return response()->json([
                'success' => false,
                'message' => 'La orden debe tener un cliente'
            ], 400);
        }

        // Confirmar la orden usando transacción
        try {
            $ordenConfirmada = $orden->confirmar();

            return response()->json([
                'success' => true,
                'message' => 'Orden confirmada exitosamente',
                'numero_pedido' => $ordenConfirmada->pedido,
                'identificador' => $ordenConfirmada->identificador,
                'redirect' => route('asesores.ordenes.show', $ordenConfirmada->id)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar la orden: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver orden confirmada
     */
    public function show($id)
    {
        $asesorId = Auth::id();
        $orden = OrdenAsesor::where('id', $id)
            ->where('asesor_id', $asesorId)
            ->with('productos')
            ->firstOrFail();

        return view('asesores.ordenes.show', compact('orden'));
    }

    /**
     * Eliminar borrador
     */
    public function destroy($id)
    {
        $asesorId = Auth::id();
        $orden = OrdenAsesor::where('id', $id)
            ->where('asesor_id', $asesorId)
            ->firstOrFail();

        // Solo permitir eliminar borradores
        if (!$orden->esBorrador()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una orden confirmada'
            ], 403);
        }

        try {
            $orden->cancelar();

            return response()->json([
                'success' => true,
                'message' => 'Borrador eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el borrador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas del asesor
     */
    public function stats()
    {
        $asesorId = Auth::id();

        $stats = [
            'borradores' => OrdenAsesor::delAsesor($asesorId)->borradores()->count(),
            'confirmados_hoy' => OrdenAsesor::delAsesor($asesorId)->confirmados()->delDia()->count(),
            'confirmados_mes' => OrdenAsesor::delAsesor($asesorId)->confirmados()->delMes()->count(),
            'total_mes' => OrdenAsesor::delAsesor($asesorId)->confirmados()->delMes()->sum('monto_total'),
        ];

        return response()->json($stats);
    }

    /**
     * API: Obtener procesos de una orden (para tracking)
     */
    public function getProcesos($id)
    {
        try {
            // Buscar por numero_pedido (que es lo que envía el frontend)
            $orden = PedidoProduccion::where('numero_pedido', $id)->orWhere('id', $id)->firstOrFail();

            // Obtener festivos (sin filtrar por año, la tabla ya tiene las fechas)
            $festivos = \App\Models\Festivo::pluck('fecha')->toArray();

            // Obtener los procesos ordenados por fecha_inicio
            // Ahora usa numero_pedido como relación
            $procesos = DB::table('procesos_prenda')
                ->where('numero_pedido', $orden->numero_pedido)
                ->orderBy('fecha_inicio', 'asc')
                ->select('proceso', 'fecha_inicio', 'encargado', 'estado_proceso')
                ->get()
                ->groupBy('proceso')
                ->map(function($grupo) {
                    // Tomar el primer registro de cada grupo de procesos
                    return $grupo->first();
                })
                ->values();

            // Calcular días hábiles totales
            $totalDiasHabiles = 0;
            if ($procesos->count() > 0) {
                $fechaInicio = \Carbon\Carbon::parse($procesos->first()->fecha_inicio);
                
                // Determinar la fecha final:
                // 1. Si hay proceso "Despachos" o "Entrega", usar esa fecha
                // 2. Si la orden está entregada, usar la última fecha de proceso
                // 3. Si hay solo un proceso, contar hasta hoy
                // 4. Si hay múltiples procesos, contar hasta el último
                
                $procesoDespachos = $procesos->firstWhere('proceso', 'Despachos') 
                    ?? $procesos->firstWhere('proceso', 'Entrega')
                    ?? $procesos->firstWhere('proceso', 'Despacho');
                
                if ($procesoDespachos) {
                    // Hay proceso de despacho/entrega, usar esa fecha
                    $fechaFin = \Carbon\Carbon::parse($procesoDespachos->fecha_inicio);
                } elseif ($procesos->count() > 1) {
                    // Hay múltiples procesos pero sin despacho, usar el último
                    $fechaFin = \Carbon\Carbon::parse($procesos->last()->fecha_inicio);
                } else {
                    // Solo hay un proceso, contar hasta hoy
                    $fechaFin = \Carbon\Carbon::now();
                }
                
                $totalDiasHabiles = $this->calcularDiasHabilesBatch($fechaInicio, $fechaFin, $festivos);
            }

            return response()->json([
                'numero_pedido' => $orden->numero_pedido,
                'cliente' => $orden->cliente,
                'fecha_inicio' => $orden->fecha_de_creacion_de_orden,
                'fecha_estimada_de_entrega' => $orden->fecha_estimada_de_entrega,
                'procesos' => $procesos,
                'total_dias_habiles' => $totalDiasHabiles,
                'festivos' => $festivos
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getProcesos: ' . $e->getMessage());
            return response()->json([
                'error' => 'No se encontró la orden o no tiene permiso para verla'
            ], 404);
        }
    }

    /**
     * Calcula días hábiles entre dos fechas (mismo método que en RegistroOrdenController)
     */
    private function calcularDiasHabilesBatch(\Carbon\Carbon $inicio, \Carbon\Carbon $fin, array $festivos): int
    {
        // El contador inicia desde el PRIMER DÍA HÁBIL DESPUÉS de la fecha de inicio
        $current = $inicio->copy()->addDay();
        
        $totalDays = 0;
        $weekends = 0;
        $holidaysCount = 0;
        
        while ($current <= $fin) {
            $dateString = $current->format('Y-m-d');
            $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
            $isFestivo = in_array($dateString, array_map(fn($f) => \Carbon\Carbon::parse($f)->format('Y-m-d'), $festivos));
            
            $totalDays++;
            if ($isWeekend) $weekends++;
            if ($isFestivo) $holidaysCount++;
            
            $current->addDay();
        }

        $businessDays = $totalDays - $weekends - $holidaysCount;

        return max(0, $businessDays);
    }

    /**
     * API: Editar un proceso (solo para admin)
     */
    public function editarProceso(Request $request, $id)
    {
        try {
            // Verificar que es admin
            if (!auth()->user()->role || auth()->user()->role->name !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo administradores pueden editar procesos'
                ], 403);
            }

            $validated = $request->validate([
                'numero_pedido' => 'required|integer',
                'proceso' => 'required|string|max:255',
                'fecha_inicio' => 'required|date',
                'encargado' => 'nullable|string|max:255',
                'estado_proceso' => 'required|in:Pendiente,En Progreso,Completado,Pausado',
                'observaciones' => 'nullable|string',
            ]);

            // Buscar el proceso
            $proceso = DB::table('procesos_prenda')
                ->where('id', $id)
                ->where('numero_pedido', $validated['numero_pedido'])
                ->first();

            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado'
                ], 404);
            }

            // Actualizar
            DB::table('procesos_prenda')
                ->where('id', $id)
                ->update([
                    'proceso' => $validated['proceso'],
                    'fecha_inicio' => $validated['fecha_inicio'],
                    'encargado' => $validated['encargado'],
                    'estado_proceso' => $validated['estado_proceso'],
                    'observaciones' => $validated['observaciones'] ?? null,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Proceso actualizado correctamente'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al editar proceso: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al editar proceso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Eliminar un proceso (solo para admin)
     */
    public function eliminarProceso(Request $request, $id)
    {
        try {
            // Verificar que es admin
            if (!auth()->user()->role || auth()->user()->role->name !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo administradores pueden eliminar procesos'
                ], 403);
            }

            $validated = $request->validate([
                'numero_pedido' => 'required|integer',
            ]);

            // Buscar el proceso
            $proceso = DB::table('procesos_prenda')
                ->where('id', $id)
                ->where('numero_pedido', $validated['numero_pedido'])
                ->first();

            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado'
                ], 404);
            }

            // Verificar que no sea el último proceso (debe haber al menos 1)
            $totalProcesos = DB::table('procesos_prenda')
                ->where('numero_pedido', $validated['numero_pedido'])
                ->count();

            if ($totalProcesos <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el último proceso de una orden'
                ], 422);
            }

            // Eliminar
            DB::table('procesos_prenda')
                ->where('id', $id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Proceso eliminado correctamente'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar proceso: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar proceso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Buscar un proceso por numero_pedido y nombre
     */
    public function buscarProceso(Request $request)
    {
        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|integer',
                'proceso' => 'required|string',
            ]);

            $proceso = DB::table('procesos_prenda')
                ->where('numero_pedido', $validated['numero_pedido'])
                ->where('proceso', $validated['proceso'])
                ->first();

            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado'
                ], 404);
            }

            return response()->json([
                'id' => $proceso->id,
                'numero_pedido' => $proceso->numero_pedido,
                'proceso' => $proceso->proceso,
                'fecha_inicio' => $proceso->fecha_inicio,
                'encargado' => $proceso->encargado,
                'estado_proceso' => $proceso->estado_proceso,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al buscar proceso: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar proceso'
            ], 500);
        }
    }

    /**
     * API: Crear un nuevo proceso en procesos_prenda
     */
    public function crearProceso(Request $request)
    {
        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|integer',
                'proceso' => 'required|string',
                'fecha_inicio' => 'required|date',
                'encargado' => 'nullable|string',
                'estado_proceso' => 'required|in:Pendiente,En Progreso,Completado,Pausado',
            ]);

            $numeroPedido = $validated['numero_pedido'];
            $nombreProceso = $validated['proceso'];

            // Verificar si ya existe este MISMO proceso (del mismo tipo) para este pedido
            $procesoDuplicado = DB::table('procesos_prenda')
                ->where('numero_pedido', $numeroPedido)
                ->where('proceso', $nombreProceso)
                ->first();

            if ($procesoDuplicado) {
                // Guardar cambio ANTERIOR en historial antes de actualizar
                DB::table('procesos_historial')->insert([
                    'numero_pedido' => $procesoDuplicado->numero_pedido,
                    'proceso' => $procesoDuplicado->proceso,
                    'fecha_inicio' => $procesoDuplicado->fecha_inicio,
                    'encargado' => $procesoDuplicado->encargado,
                    'estado_proceso' => $procesoDuplicado->estado_proceso,
                    'created_at' => $procesoDuplicado->created_at,
                    'updated_at' => $procesoDuplicado->updated_at
                ]);

                // Ahora actualizar el proceso
                DB::table('procesos_prenda')
                    ->where('id', $procesoDuplicado->id)
                    ->update([
                        'fecha_inicio' => $validated['fecha_inicio'],
                        'encargado' => $validated['encargado'],
                        'estado_proceso' => $validated['estado_proceso'],
                        'updated_at' => now()
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Proceso actualizado correctamente',
                    'id' => $procesoDuplicado->id,
                    'proceso' => $nombreProceso,
                    'duplicado' => true
                ]);
            }

            // Si no existe este proceso, crear uno nuevo
            $id = DB::table('procesos_prenda')->insertGetId([
                'numero_pedido' => $numeroPedido,
                'proceso' => $nombreProceso,
                'fecha_inicio' => $validated['fecha_inicio'],
                'encargado' => $validated['encargado'],
                'estado_proceso' => $validated['estado_proceso'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Guardar en historial
            DB::table('procesos_historial')->insert([
                'numero_pedido' => $numeroPedido,
                'proceso' => $nombreProceso,
                'fecha_inicio' => $validated['fecha_inicio'],
                'encargado' => $validated['encargado'],
                'estado_proceso' => $validated['estado_proceso'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Proceso creado correctamente',
                'id' => $id,
                'proceso' => $nombreProceso
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al crear proceso: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el proceso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los procesos actuales de una orden
     */
    public function obtenerProcesosPorPedido($numero_pedido)
    {
        try {
            // Obtener todos los procesos actuales (en procesos_prenda) de este pedido
            $procesos = DB::table('procesos_prenda')
                ->where('numero_pedido', $numero_pedido)
                ->orderBy('updated_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'numero_pedido' => $numero_pedido,
                'procesos' => $procesos,
                'total' => $procesos->count()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener procesos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los procesos'
            ], 500);
        }
    }

    /**
     * Obtener historial de procesos de una orden
     */
    public function obtenerHistorial($numero_pedido)
    {
        try {
            // Obtener todos los procesos actuales
            $procesosActuales = DB::table('procesos_prenda')
                ->where('numero_pedido', $numero_pedido)
                ->get();

            // Obtener el historial de procesos
            $historial = DB::table('procesos_historial')
                ->where('numero_pedido', $numero_pedido)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'procesos_actuales' => $procesosActuales,
                'historial' => $historial
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener historial: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial'
            ], 500);
        }
    }
}
