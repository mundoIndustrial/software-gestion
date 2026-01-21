<?php

namespace App\Http\Controllers;

use App\Constants\AreaOptions;
use App\Models\OrdenAsesor;
use App\Models\ProductoPedido;
use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;
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
            // Solo de procesos_prenda (excluir soft-deleted)
            $procesos = DB::table('procesos_prenda')
                ->where('numero_pedido', $orden->numero_pedido)
                ->whereNull('deleted_at')  // Excluir soft-deleted
                ->orderBy('fecha_inicio', 'asc')
                ->select('id', 'numero_pedido', 'proceso', 'fecha_inicio', 'encargado', 'estado_proceso')
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
        // Contar todos los días hábiles desde la fecha de inicio hasta la fecha final
        $diasCalculados = 0;
        $actual = $inicio->copy();
        
        while ($actual <= $fin) {
            // Verificar si no es sábado (6) ni domingo (0)
            if ($actual->dayOfWeek !== 0 && $actual->dayOfWeek !== 6) {
                // Verificar si no es festivo
                $dateString = $actual->format('Y-m-d');
                $isFestivo = in_array($dateString, array_map(fn($f) => \Carbon\Carbon::parse($f)->format('Y-m-d'), $festivos));
                
                if (!$isFestivo) {
                    $diasCalculados++;
                }
            }
            $actual->addDay();
        }
        
        // Restar 1 porque no se cuenta el día de inicio
        return max(0, $diasCalculados - 1);
    }

    /**
     * API: Editar un proceso (para admin y producción)
     */
    public function editarProceso(Request $request, $id)
    {
        try {
            // Verificar que es admin o producción
            $userRole = auth()->user()->role?->name;
            $isAllowed = in_array($userRole, ['admin', 'produccion']);
            
            if (!$isAllowed) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar procesos'
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

            // Buscar el proceso usando el modelo (para que dispare Observer)
            $proceso = \App\Models\ProcesoPrenda::where('id', $id)
                ->where('numero_pedido', $validated['numero_pedido'])
                ->first();

            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado'
                ], 404);
            }

            // Actualizar usando el modelo (dispara Observer)
            $proceso->update([
                'proceso' => $validated['proceso'],
                'fecha_inicio' => $validated['fecha_inicio'],
                'encargado' => $validated['encargado'],
                'estado_proceso' => $validated['estado_proceso'],
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            \Log::info(' Proceso actualizado correctamente', [
                'proceso_id' => $id,
                'numero_pedido' => $validated['numero_pedido'],
                'nuevo_estado' => $validated['estado_proceso'],
                'nuevo_proceso' => $validated['proceso'],
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
     * API: Eliminar un proceso (para admin y producción)
     */
    public function eliminarProceso(Request $request, $id)
    {
        try {
            // Verificar que es admin o producción
            $userRole = auth()->user()->role?->name;
            $isAllowed = in_array($userRole, ['admin', 'produccion']);
            
            if (!$isAllowed) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar procesos'
                ], 403);
            }

            $validated = $request->validate([
                'numero_pedido' => 'required|integer',
            ]);

            // Buscar el proceso SOLO en procesos_prenda
            // Los procesos en procesos_historial no deben ser eliminables desde el modal
            $proceso = ProcesoPrenda::where('id', $id)
                ->where('numero_pedido', $validated['numero_pedido'])
                ->first();

            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado o ya fue eliminado'
                ], 404);
            }

            // Verificar que no sea el último proceso (debe haber al menos 1)
            $totalProcesos = ProcesoPrenda::where('numero_pedido', $validated['numero_pedido'])
                ->count();

            if ($totalProcesos <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el último proceso de una orden'
                ], 422);
            }

            // Eliminar usando el Model (dispara el Observer deleting)
            $proceso->delete();

            // También eliminar del historial para que no aparezca en el modal
            DB::table('procesos_historial')
                ->where('numero_pedido', $validated['numero_pedido'])
                ->where('proceso', $proceso->proceso)
                ->delete();

            \Log::info("Proceso eliminado correctamente", [
                'id' => $id,
                'numero_pedido' => $validated['numero_pedido'],
                'proceso' => $proceso->proceso
            ]);

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

    /**
     * Obtener datos completos del pedido confirmado para edición
     * GET /ordenes/{id}/editar-pedido
     */
    public function editarPedido($id)
    {
        try {
            $orden = PedidoProduccion::with([
                'prendas' => function($query) {
                    $query->with([
                        'fotos',
                        'coloresTelas' => function($q) {
                            $q->with(['color', 'tela', 'fotos']);
                        },
                        'fotosTelas',
                        'variantes' => function($q) {
                            $q->with(['tipoManga', 'tipoBroche']);
                        },
                        'procesos' => function($q) {
                            $q->with(['imagenes']);
                        }
                    ]);
                },
                'epp' => function($query) {
                    $query->with('imagenes');
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
                    $texto = $prenda->descripcion_variaciones;
                    
                    if (preg_match('/Manga:\s*([^|]+)/', $texto, $matches)) {
                        $variaciones['obs_manga'] = trim($matches[1]);
                    }
                    
                    if (preg_match('/Bolsillos:\s*([^|]+)/', $texto, $matches)) {
                        $variaciones['obs_bolsillos'] = trim($matches[1]);
                    }
                    
                    if (preg_match('/Broche:\s*([^|]+)/', $texto, $matches)) {
                        $variaciones['obs_broche'] = trim($matches[1]);
                    }
                    
                    if (preg_match('/Reflectivo:\s*(.+)$/', $texto, $matches)) {
                        $variaciones['obs_reflectivo'] = trim($matches[1]);
                    }
                }
                
                // Preparar tallas por género desde cantidad_talla JSON
                $tallasGenero = [];
                $cantidadTalla = $prenda->cantidad_talla;
                if (is_string($cantidadTalla)) {
                    $cantidadTalla = json_decode($cantidadTalla, true) ?? [];
                }
                
                if ($cantidadTalla && is_array($cantidadTalla)) {
                    foreach ($cantidadTalla as $genero => $tallas) {
                        if (is_array($tallas)) {
                            $tallasGenero[$genero] = [
                                'tallas' => array_keys($tallas),
                                'tipo' => null
                            ];
                        }
                    }
                }

                // Obtener color y tela desde coloresTelas
                $colorNombre = null;
                $telaNombre = null;
                if ($prenda->coloresTelas && count($prenda->coloresTelas) > 0) {
                    $colorTela = $prenda->coloresTelas->first();
                    if ($colorTela) {
                        $colorNombre = $colorTela->color?->nombre;
                        $telaNombre = $colorTela->tela?->nombre;
                    }
                }
                
                // Preparar variantes
                $variantes = [];
                if ($prenda->variantes && count($prenda->variantes) > 0) {
                    foreach ($prenda->variantes as $variante) {
                        $variantes[] = [
                            'id' => $variante->id,
                            'talla' => '',
                            'cantidad' => '',
                            'genero' => '',
                            'color_id' => null,
                            'color_nombre' => $colorNombre,
                            'tela_id' => null,
                            'tela_nombre' => $telaNombre,
                            'tipo_manga_id' => $variante->tipo_manga_id,
                            'tipo_manga_nombre' => $variante->tipoManga?->nombre,
                            'tipo_broche_id' => $variante->tipo_broche_boton_id,
                            'tipo_broche_nombre' => $variante->tipoBroche?->nombre,
                            'manga_obs' => $variante->manga_obs,
                            'broche_boton_obs' => $variante->broche_boton_obs,
                            'bolsillos_obs' => $variante->bolsillos_obs,
                            'tiene_bolsillos' => $variante->tiene_bolsillos
                        ];
                    }
                }

                // Preparar procesos
                $procesos = [];
                if ($prenda->procesos && count($prenda->procesos) > 0) {
                    foreach ($prenda->procesos as $proceso) {
                        // Procesar ubicaciones
                        $ubicacionesData = [];
                        if ($proceso->ubicaciones) {
                            if (is_string($proceso->ubicaciones)) {
                                $ubicacionesData = json_decode($proceso->ubicaciones, true) ?? [];
                            } else if (is_array($proceso->ubicaciones)) {
                                $ubicacionesData = $proceso->ubicaciones;
                            }
                        }
                        
                        // Obtener nombre del tipo de proceso
                        $tipoProceso = 'Proceso';
                        if ($proceso->tipo_proceso_id) {
                            $tipoProcesoDB = \App\Models\TipoProceso::find($proceso->tipo_proceso_id);
                            if ($tipoProcesoDB) {
                                $tipoProceso = $tipoProcesoDB->nombre;
                            }
                        }
                        
                        $procesos[] = [
                            'id' => $proceso->id,
                            'tipo' => $tipoProceso,
                            'nombre' => $tipoProceso,
                            'observaciones' => $proceso->observaciones,
                            'ubicaciones' => is_array($ubicacionesData) ? $ubicacionesData : [],
                            'imagenes' => $proceso->imagenes ? $proceso->imagenes->map(function($img) {
                                return [
                                    'id' => $img->id,
                                    'url' => $img->url,
                                    'ruta' => $img->ruta_webp ?? $img->ruta_original
                                ];
                            })->toArray() : []
                        ];
                    }
                }

                // Preparar telas agregadas
                $telasAgregadas = [];
                if ($prenda->coloresTelas && count($prenda->coloresTelas) > 0) {
                    $telasUnicas = [];
                    foreach ($prenda->coloresTelas as $colorTela) {
                        $telaKey = $colorTela->tela_id . '_' . $colorTela->color_id;
                        if (!isset($telasUnicas[$telaKey])) {
                            $telasUnicas[$telaKey] = [
                                'tela' => $colorTela->tela?->nombre,
                                'color' => $colorTela->color?->nombre,
                                'referencia' => $colorTela->tela?->referencia ?? '',
                                'imagenes' => []
                            ];
                        }
                    }
                    $telasAgregadas = array_values($telasUnicas);
                }

                return [
                    'id' => $prenda->id,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'nombre_producto' => $prenda->nombre_prenda,
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
                    'tipo_broche_nombre' => $prenda->tipoBrocheBoton?->nombre ?? null,
                    'tiene_bolsillos' => $prenda->tiene_bolsillos,
                    'tiene_reflectivo' => $prenda->tiene_reflectivo,
                    'fotos' => $prenda->fotos->map(function($foto) {
                        $urlFoto = $foto->url;
                        \Log::info('[ORDEN-CONTROLLER] Foto de prenda - Datos en BD y accessor', [
                            'foto_id' => $foto->id,
                            'ruta_webp_bd' => $foto->ruta_webp,
                            'ruta_original_bd' => $foto->ruta_original,
                            'url_accessor' => $urlFoto,
                            'metodo' => 'fotos'
                        ]);
                        return [
                            'id' => $foto->id,
                            'ruta' => $urlFoto,
                            'url' => $urlFoto
                        ];
                    }),
                    'imagenes' => $prenda->fotos->map(function($foto) {
                        $urlFoto = $foto->url;
                        \Log::info('[ORDEN-CONTROLLER] Foto de prenda (imagenes) - Datos en BD y accessor', [
                            'foto_id' => $foto->id,
                            'ruta_webp_bd' => $foto->ruta_webp,
                            'ruta_original_bd' => $foto->ruta_original,
                            'url_accessor' => $urlFoto,
                            'metodo' => 'imagenes'
                        ]);
                        return [
                            'id' => $foto->id,
                            'ruta' => $urlFoto,
                            'url' => $urlFoto
                        ];
                    }),
                    'fotos_logo' => $prenda->fotosLogo->map(function($foto) {
                        $urlFoto = $foto->url;
                        \Log::info('[ORDEN-CONTROLLER] Foto de logo - Datos en BD y accessor', [
                            'foto_id' => $foto->id,
                            'ruta_foto_bd' => $foto->ruta_foto,
                            'url_accessor' => $urlFoto,
                            'metodo' => 'fotos_logo'
                        ]);
                        return [
                            'id' => $foto->id,
                            'ruta' => $foto->ruta_foto,
                            'url' => $urlFoto
                        ];
                    }),
                    'fotos_tela' => $prenda->fotosTelas->map(function($foto) {
                        $urlFoto = $foto->url;
                        \Log::info('[ORDEN-CONTROLLER] Foto de tela - Datos en BD y accessor', [
                            'foto_id' => $foto->id,
                            'ruta_webp_bd' => $foto->ruta_webp,
                            'ruta_original_bd' => $foto->ruta_original,
                            'url_accessor' => $urlFoto,
                            'metodo' => 'fotos_tela'
                        ]);
                        return [
                            'id' => $foto->id,
                            'ruta' => $urlFoto,
                            'url' => $urlFoto
                        ];
                    }),
                    'imagenes_tela' => $prenda->fotosTelas->map(function($foto) {
                        $urlFoto = $foto->url;
                        \Log::info('[ORDEN-CONTROLLER] Foto de tela (imagenes_tela) - Datos en BD y accessor', [
                            'foto_id' => $foto->id,
                            'ruta_webp_bd' => $foto->ruta_webp,
                            'ruta_original_bd' => $foto->ruta_original,
                            'url_accessor' => $urlFoto,
                            'metodo' => 'imagenes_tela'
                        ]);
                        return [
                            'id' => $foto->id,
                            'ruta' => $urlFoto,
                            'url' => $urlFoto
                        ];
                    }),
                    'variantes' => $variantes,
                    'tallas' => $tallasGenero,
                    'generosConTallas' => $tallasGenero,
                    'telasAgregadas' => $telasAgregadas,
                    'procesos' => $procesos,
                    'origen' => $prenda->de_bodega ? 'bodega' : 'cliente'
                ];
            });

            // Preparar datos del EPP
            $eppData = [];
            if ($orden->epp && count($orden->epp) > 0) {
                foreach ($orden->epp as $epp) {
                    $eppData[] = [
                        'id' => $epp->id,
                        'epp_id' => $epp->epp_id,
                        'cantidad' => $epp->cantidad,
                        'tallas_medidas' => $epp->tallas_medidas ? json_decode($epp->tallas_medidas, true) : [],
                        'observaciones' => $epp->observaciones,
                        'imagenes' => $epp->imagenes ? $epp->imagenes->map(function($img) {
                            return [
                                'id' => $img->id,
                                'ruta' => $img->ruta_web ?? $img->ruta_original,
                                'url' => $img->ruta_web ?? $img->ruta_original,
                                'principal' => $img->principal
                            ];
                        })->toArray() : []
                    ];
                }
            }

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
                    'prendas' => $prendasData,
                    'epp' => $eppData
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
}
