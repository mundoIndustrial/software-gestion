<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PedidosProduccionController extends Controller
{
    /**
     * Mostrar formulario para crear pedido desde cotización
     */
    public function crearForm()
    {
        $cotizaciones = Cotizacion::where('user_id', Auth::id())
            ->where('es_borrador', false)
            ->where('estado', 'enviada')
            ->with('prendasCotizaciones')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('asesores.pedidos.crear-desde-cotizacion', compact('cotizaciones'));
    }

    /**
     * Listar pedidos de producción del asesor
     */
    public function index()
    {
        $pedidos = PedidoProduccion::whereHas('cotizacion', function ($query) {
            $query->where('user_id', Auth::id());
        })
        ->orderBy('created_at', 'desc')
        ->paginate(15);

        return view('asesores.pedidos.index', compact('pedidos'));
    }

    /**
     * Ver detalle de pedido de producción
     */
    public function show($id)
    {
        $pedido = PedidoProduccion::findOrFail($id);
        
        // Verificar que el pedido pertenece al asesor
        if ($pedido->cotizacion->user_id !== Auth::id()) {
            abort(403);
        }

        $prendas = $pedido->prendas()->with('procesos')->get();
        $cotizacion = $pedido->cotizacion;
        $prendasCotizacion = $cotizacion->prendasCotizaciones;

        return view('asesores.pedidos.show', compact('pedido', 'prendas', 'cotizacion', 'prendasCotizacion'));
    }

    /**
     * Ver plantilla ERP/Factura del pedido
     */
    public function plantilla($id)
    {
        $pedido = PedidoProduccion::findOrFail($id);
        
        // Verificar que el pedido pertenece al asesor
        if ($pedido->cotizacion->user_id !== Auth::id()) {
            abort(403);
        }

        $prendas = $pedido->prendas()->with('procesos')->get();
        $cotizacion = $pedido->cotizacion;
        $prendasCotizacion = $cotizacion->prendasCotizaciones;

        return view('asesores.pedidos.plantilla-erp', compact('pedido', 'prendas', 'cotizacion', 'prendasCotizacion'));
    }

    /**
     * Crear pedido de producción desde cotización (llamado desde CotizacionesController)
     */
    public function crearDesdeCotizacion($cotizacionId)
    {
        $cotizacion = Cotizacion::findOrFail($cotizacionId);
        
        if ($cotizacion->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            DB::beginTransaction();

            // Crear pedido de producción
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'numero_pedido' => $this->generarNumeroPedido(),
                'cliente' => $cotizacion->cliente,
                'asesora' => auth()->user()?->name ?? 'Sin nombre',
                'forma_de_pago' => $cotizacion->especificaciones['forma_pago'] ?? null,
                'estado' => 'No iniciado',
                'fecha_de_creacion_de_orden' => now()->toDateString(),
            ]);

            // Crear prendas del pedido
            if ($cotizacion->productos) {
                foreach ($cotizacion->productos as $producto) {
                    $cantidadTotal = 0;
                    if (isset($producto['cantidades']) && is_array($producto['cantidades'])) {
                        $cantidadTotal = array_sum($producto['cantidades']);
                    } else {
                        $cantidadTotal = $producto['cantidad'] ?? 1;
                    }

                    $prenda = PrendaPedido::create([
                        'pedido_produccion_id' => $pedido->id,
                        'nombre_prenda' => $producto['nombre_producto'] ?? 'Sin nombre',
                        'cantidad' => $cantidadTotal,
                        'descripcion' => $producto['descripcion'] ?? null,
                    ]);

                    // Crear proceso inicial para cada prenda
                    ProcesoPrenda::create([
                        'prenda_pedido_id' => $prenda->id,
                        'proceso' => 'Creación Orden',
                        'estado_proceso' => 'Completado',
                        'fecha_inicio' => now()->toDateString(),
                        'fecha_fin' => now()->toDateString(),
                    ]);
                }
            }

            // Actualizar cotización
            $cotizacion->update([
                'estado' => 'aceptada',
                'es_borrador' => false
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cotización aceptada y pedido creado',
                'pedido_id' => $pedido->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al crear pedido desde cotización', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar número de pedido único
     */
    private function generarNumeroPedido()
    {
        $ultimoPedido = PedidoProduccion::max('numero_pedido') ?? 0;
        return $ultimoPedido + 1;
    }
}
