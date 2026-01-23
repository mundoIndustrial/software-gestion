<?php

namespace App\Http\Controllers;

use App\Models\OrdenAsesor;
use App\Models\ProductoPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CotizacionController - Gestión de cotizaciones con borradores
 * 
 * Responsabilidades:
 * - Listar cotizaciones confirmadas (ordenes_asesor)
 * - Gestionar borradores (órdenes sin confirmar)
 * - Crear, actualizar, confirmar y eliminar borradores
 * - Generar número de pedido al confirmar cotización
 * 
 * Nota: Los procesos de producción se gestionan en PedidosProduccionController
 */
class CotizacionController extends Controller
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
}