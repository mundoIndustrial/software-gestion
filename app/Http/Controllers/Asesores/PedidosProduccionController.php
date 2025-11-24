<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\Cotizacion;
use App\Models\VariantePrenda;
use App\Models\PrendaCotizacionFriendly;
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
            ->with([
                'prendasCotizaciones.variantes.color',
                'prendasCotizaciones.variantes.tela',
                'prendasCotizaciones.variantes.tipoManga',
                'prendasCotizaciones.variantes.tipoBroche'
            ])
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

        return view('asesores.pedidos.plantilla-erp', compact('pedido', 'prendas', 'cotizacion', 'prendasCotizacion'));
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
            $especificaciones = is_array($cotizacion->especificaciones) ? $cotizacion->especificaciones : [];
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'numero_pedido' => $this->generarNumeroPedido(),
                'cliente' => $cotizacion->cliente,
                'asesora' => auth()->user()?->name ?? 'Sin nombre',
                'forma_de_pago' => $especificaciones['forma_pago'] ?? null,
                'estado' => 'No iniciado',
                'fecha_de_creacion_de_orden' => now()->toDateString(),
            ]);

            // Crear prendas del pedido
            $productos = $cotizacion->productos;
            
            // Si productos es un string JSON, decodificarlo
            if (is_string($productos)) {
                $productos = json_decode($productos, true) ?? [];
            }
            
            if ($productos && is_array($productos)) {
                foreach ($productos as $index => $producto) {
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
                    
                    // HEREDAR VARIANTES DE LA COTIZACIÓN
                    $this->heredarVariantesDePrenda($cotizacion, $prenda, $index);
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
     * Heredar variantes de una prenda de cotización a pedido
     */
    private function heredarVariantesDePrenda($cotizacion, $prendaPedido, $index)
    {
        try {
            // Obtener la prenda de cotización correspondiente
            $prendasCotizacion = $cotizacion->prendasCotizaciones;
            
            if (!isset($prendasCotizacion[$index])) {
                \Log::warning('⚠️ No se encontró prenda de cotización en índice', [
                    'index' => $index,
                    'total_prendas' => count($prendasCotizacion)
                ]);
                return;
            }
            
            $prendaCotizacion = $prendasCotizacion[$index];
            
            // Obtener variantes de la prenda de cotización
            $variantes = VariantePrenda::where('prenda_cotizacion_id', $prendaCotizacion->id)->get();
            
            if ($variantes->isEmpty()) {
                \Log::info('ℹ️ Sin variantes para heredar', [
                    'prenda_cotizacion_id' => $prendaCotizacion->id
                ]);
                return;
            }
            
            // Copiar cada variante al pedido
            foreach ($variantes as $variante) {
                // Actualizar prenda del pedido con datos de variantes
                $prendaPedido->update([
                    'color_id' => $variante->color_id,
                    'tela_id' => $variante->tela_id,
                    'tipo_manga_id' => $variante->tipo_manga_id,
                    'tipo_broche_id' => $variante->tipo_broche_id,
                    'tiene_bolsillos' => $variante->tiene_bolsillos,
                    'tiene_reflectivo' => $variante->tiene_reflectivo,
                    'descripcion_variaciones' => $variante->descripcion_adicional,
                    'cantidad_talla' => $variante->cantidad_talla
                ]);
                
                \Log::info('✅ Variantes heredadas exitosamente', [
                    'prenda_pedido_id' => $prendaPedido->id,
                    'variante_original_id' => $variante->id,
                    'color_id' => $variante->color_id,
                    'tela_id' => $variante->tela_id,
                    'tipo_manga_id' => $variante->tipo_manga_id,
                    'tipo_broche_id' => $variante->tipo_broche_id,
                    'tiene_bolsillos' => $variante->tiene_bolsillos,
                    'tiene_reflectivo' => $variante->tiene_reflectivo
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('❌ Error heredando variantes', [
                'prenda_pedido_id' => $prendaPedido->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
