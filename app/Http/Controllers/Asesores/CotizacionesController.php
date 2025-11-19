<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Services\ImagenCotizacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CotizacionesController extends Controller
{
    /**
     * Mostrar lista de cotizaciones y borradores
     */
    public function index()
    {
        $cotizaciones = Cotizacion::where('user_id', Auth::id())
            ->where('es_borrador', false)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        $borradores = Cotizacion::where('user_id', Auth::id())
            ->where('es_borrador', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('asesores.cotizaciones.index', compact('cotizaciones', 'borradores'));
    }

    /**
     * Guardar cotización o borrador
     */
    public function guardar(Request $request)
    {
        try {
            $tipo = $request->input('tipo', 'borrador'); // 'borrador' o 'enviada'
            $cliente = $request->input('cliente');

            // Log para debugging
            \Log::info('Guardando cotización', [
                'tipo' => $tipo,
                'cliente' => $cliente,
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            // Recopilar datos del formulario
            $datos = [
                'user_id' => Auth::id(),
                'cliente' => $cliente,
                'productos' => $request->input('productos', []),
                'especificaciones' => $request->input('especificaciones', []),
                'imagenes' => $request->input('imagenes', []),
                'tecnicas' => $request->input('tecnicas', []),
                'observaciones_tecnicas' => $request->input('observaciones_tecnicas'),
                'ubicaciones' => $request->input('ubicaciones', []),
                'observaciones_generales' => $request->input('observaciones_generales', []),
                'es_borrador' => ($tipo === 'borrador'),
                'estado' => 'enviada',
            ];

            \Log::info('Datos a guardar', $datos);

            $cotizacion = Cotizacion::create($datos);

            \Log::info('Cotización guardada exitosamente', ['id' => $cotizacion->id]);

            return response()->json([
                'success' => true,
                'message' => ($tipo === 'borrador') ? 'Cotización guardada en borradores' : 'Cotización enviada correctamente',
                'cotizacion_id' => $cotizacion->id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al guardar cotización', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Ver detalle de cotización
     */
    public function show($id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        
        if ($cotizacion->user_id !== Auth::id()) {
            abort(403);
        }

        return view('asesores.cotizaciones.show', compact('cotizacion'));
    }

    /**
     * Editar borrador
     */
    public function editarBorrador($id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        
        if ($cotizacion->user_id !== Auth::id() || !$cotizacion->es_borrador) {
            abort(403);
        }

        return view('asesores.pedidos.create-friendly', ['cotizacion' => $cotizacion]);
    }

    /**
     * Subir imágenes a una cotización
     */
    public function subirImagenes(Request $request, $id)
    {
        \Log::info('=== INICIO SUBIR IMAGENES ===', ['cotizacion_id' => $id]);
        
        $cotizacion = Cotizacion::findOrFail($id);
        \Log::info('Cotización encontrada', ['id' => $cotizacion->id, 'user_id' => $cotizacion->user_id]);
        
        if ($cotizacion->user_id !== Auth::id()) {
            \Log::warning('Acceso denegado', ['user_id' => Auth::id(), 'cotizacion_user_id' => $cotizacion->user_id]);
            abort(403);
        }

        \Log::info('Validando archivos', [
            'archivos_recibidos' => count($request->file('imagenes', [])),
            'tipo' => $request->input('tipo')
        ]);

        $request->validate([
            'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'tipo' => 'required|in:bordado,estampado,tela,prenda,general'
        ]);

        try {
            $imagenService = new ImagenCotizacionService();
            
            // Validar archivos
            $archivosValidos = 0;
            foreach ($request->file('imagenes', []) as $archivo) {
                \Log::info('Validando archivo', [
                    'nombre' => $archivo->getClientOriginalName(),
                    'tamaño' => $archivo->getSize(),
                    'mime' => $archivo->getMimeType()
                ]);
                
                if (!$imagenService->validarArchivo($archivo)) {
                    \Log::error('Archivo inválido', ['nombre' => $archivo->getClientOriginalName()]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Archivo inválido: ' . $archivo->getClientOriginalName()
                    ], 422);
                }
                $archivosValidos++;
            }
            
            \Log::info('Archivos validados', ['cantidad' => $archivosValidos]);

            // Guardar imágenes
            \Log::info('Guardando imágenes en storage', [
                'cotizacion_id' => $cotizacion->id,
                'tipo' => $request->input('tipo')
            ]);
            
            $rutas = $imagenService->guardarMultiples(
                $cotizacion->id,
                $request->file('imagenes', []),
                $request->input('tipo', 'general')
            );

            \Log::info('Imágenes guardadas en storage', [
                'cantidad' => count($rutas),
                'rutas' => $rutas
            ]);

            // Obtener imágenes actuales
            $imagenesActuales = [];
            if ($cotizacion->imagenes) {
                \Log::info('Imágenes actuales encontradas', [
                    'tipo' => gettype($cotizacion->imagenes),
                    'contenido' => $cotizacion->imagenes
                ]);
                $imagenesActuales = is_array($cotizacion->imagenes) ? $cotizacion->imagenes : json_decode($cotizacion->imagenes, true) ?? [];
            }
            
            \Log::info('Imágenes actuales decodificadas', [
                'cantidad' => count($imagenesActuales),
                'imagenes' => $imagenesActuales
            ]);

            // Combinar imágenes
            $todasLasImagenes = array_merge($imagenesActuales, $rutas);
            
            \Log::info('Todas las imágenes combinadas', [
                'cantidad_total' => count($todasLasImagenes),
                'imagenes' => $todasLasImagenes
            ]);

            // Guardar en BD
            $jsonEncodado = json_encode($todasLasImagenes);
            \Log::info('JSON a guardar', ['json' => $jsonEncodado]);
            
            $resultado = $cotizacion->update(['imagenes' => $jsonEncodado]);
            
            \Log::info('Actualización en BD', [
                'resultado' => $resultado,
                'imagenes_guardadas' => $cotizacion->fresh()->imagenes
            ]);

            \Log::info('Imágenes subidas exitosamente', [
                'cotizacion_id' => $cotizacion->id,
                'cantidad_nuevas' => count($rutas),
                'cantidad_total' => count($todasLasImagenes),
                'tipo' => $request->input('tipo')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Imágenes subidas correctamente',
                'imagenes' => $rutas,
                'total' => count($todasLasImagenes)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al subir imágenes', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al subir imágenes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar cotización
     */
    public function destroy($id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        
        if ($cotizacion->user_id !== Auth::id()) {
            abort(403);
        }

        // Eliminar imágenes de almacenamiento
        $imagenService = new ImagenCotizacionService();
        $imagenService->eliminarTodasLasImagenes($cotizacion->id);

        $cotizacion->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Cotización eliminada'
        ]);
    }

    /**
     * Cambiar estado de cotización (borrador → enviada, enviada → aceptada, etc.)
     */
    public function cambiarEstado($id, $estado)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        
        if ($cotizacion->user_id !== Auth::id()) {
            abort(403);
        }

        $cotizacion->update([
            'estado' => $estado,
            'es_borrador' => false // Cuando cambia estado, ya no es borrador
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado'
        ]);
    }

    /**
     * Aceptar cotización y crear pedido de producción
     */
    public function aceptarCotizacion($id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        
        if ($cotizacion->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            DB::beginTransaction();

            // Crear pedido de producción
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => $cotizacion->id,
                'numero_pedido' => $this->generarNumeroPedido(),
                'cliente' => $cotizacion->cliente,
                'asesora' => Auth::user()->name,
                'forma_de_pago' => $cotizacion->especificaciones['forma_pago'] ?? null,
                'estado' => 'No iniciado',
                'fecha_de_creacion_de_orden' => now()->toDateString(),
            ]);

            // Crear prendas del pedido
            if ($cotizacion->productos) {
                foreach ($cotizacion->productos as $producto) {
                    $prenda = PrendaPedido::create([
                        'pedido_produccion_id' => $pedido->id,
                        'nombre_prenda' => $producto['nombre_producto'] ?? 'Sin nombre',
                        'cantidad' => $producto['cantidad'] ?? 1,
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
