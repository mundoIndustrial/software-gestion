<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use App\Application\Services\PedidoPrendaService;
use App\Application\Services\Asesores\ObtenerPedidoDetalleService;
use Illuminate\Support\Facades\Storage;

/**
 * PedidosProduccionViewController
 * 
 * Controlador para servir VISTAS HTML de pedidos
 * El controlador API (PedidosProduccionController) maneja solo JSON/CQRS
 * 
 * Responsabilidad: Renderizar vistas y preparar datos para templates
 */
class PedidosProduccionViewController
{
    /**
     * Mostrar formulario para crear pedido desde cotización
     */
    public function crearFormEditable(): View
    {
        // Obtener cotizaciones aprobadas para pedido
        $cotizacionesQuery = Cotizacion::query()
            ->select('id', 'numero_cotizacion', 'cliente_id', 'asesor_id', 'estado')
            ->with('cliente:id,nombre', 'asesor:id,name')
            ->where('estado', 'APROBADO_PARA_PEDIDO')
            ->orderBy('created_at', 'desc');
        
        $cotizaciones = $cotizacionesQuery->get();
        
        // Transformar para el frontend
        $cotizacionesData = $cotizaciones->map(function($cot) {
            return [
                'id' => $cot->id,
                'numero_cotizacion' => $cot->numero_cotizacion,
                'cliente' => $cot->cliente?->nombre ?? 'N/A',
                'asesora' => $cot->asesor?->name ?? 'N/A',
                'estado' => $cot->estado,
            ];
        })->toArray();

        return view('asesores.pedidos.crear-pedido-desde-cotizacion', [
            'cotizacionesData' => $cotizacionesData
        ]);
    }

    /**
     * Mostrar formulario para crear pedido nuevo (sin cotización)
     * También soporta edición de pedidos existentes via parámetro ?editar=id
     * 
     * FLUJOS:
     * 1. CREAR NUEVO: Inicia con estructura vacía que el frontend rellena (JSON)
     * 2. EDITAR: Carga datos de BD y los convierte a estructura del frontend
     */
    public function crearFormEditableNuevo(Request $request): View
    {
        $editarId = $request->query('editar');
        
        // ✅ ESTRUCTURA POR DEFECTO para "crear nuevo" (frontend JSON)
        $datos = [
            'modoEdicion' => false,
            'pedido' => (object)[
                'id' => null,
                'numero_pedido' => null,
                'cliente' => '',
                'forma_de_pago' => '',
                'observaciones' => '',
                'estado' => 'pendiente',
                'fecha_de_creacion_de_orden' => date('Y-m-d'),
                'asesor_id' => auth()->id(),
            ],
            'prendas' => [],
            'epps' => [],
            'estados' => [
                'No iniciado',
                'En Ejecución',
                'Entregado',
                'Anulada'
            ],
            'areas' => [
                'Creación de Orden',
                'Corte',
                'Costura',
                'Bordado',
                'Estampado',
                'Control-Calidad',
                'Entrega',
                'Polos',
                'Taller',
                'Insumos',
                'Lavandería',
                'Arreglos',
                'Despachos'
            ]
        ];

        // ✅ Si es modo edición: cargar datos de BD y convertir a estructura del frontend
        if ($editarId) {
            try {
                $service = app(ObtenerPedidoDetalleService::class);
                // El servicio ya convierte BD → Estructura Frontend
                $datos = $service->obtenerParaEdicion($editarId);
                $datos['modoEdicion'] = true;
                $datos['pedidoEditarId'] = $editarId;
                
                \Log::info('[EDITAR] Pedido cargado para edición', [
                    'pedido_id' => $editarId,
                    'cliente' => $datos['pedido']->cliente ?? 'N/A',
                    'prendas' => count($datos['prendas'] ?? []),
                    'epps' => count($datos['epps'] ?? [])
                ]);
            } catch (\Exception $e) {
                \Log::warning('[EDITAR] Error cargando pedido, iniciando vacío', [
                    'error' => $e->getMessage(),
                    'pedido_id' => $editarId
                ]);
                // Continuar con estructura vacía si hay error
            }
        }

        return view('asesores.pedidos.crear-pedido-nuevo', $datos);
    }

    /**
     * Obtener datos de cotización (AJAX)
     */
    public function obtenerDatosCotizacion($cotizacionId)
    {
        try {
            // Obtener cotización con sus relaciones
            $cotizacion = Cotizacion::with([
                'tipoCotizacion:id,nombre',
                'prendas:id,cotizacion_id,prenda_id,cantidad',
                'prendas.prenda:id,nombre',
                'reflectivo:id,cotizacion_id,tipo_reflectivo,cantidad',
                'logoCotizacion:id,cotizacion_id,tipo_logo'
            ])->find($cotizacionId);

            if (!$cotizacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cotización no encontrada'
                ], 404);
            }

            // Formatear datos para el frontend
            $prendas = $cotizacion->prendas->map(function($prenda) {
                return [
                    'id' => $prenda->id,
                    'nombre' => $prenda->prenda?->nombre ?? 'Prenda',
                    'cantidad' => $prenda->cantidad,
                    'tipo' => 'prenda'
                ];
            })->toArray();

            $reflectivo = null;
            if ($cotizacion->reflectivo) {
                $reflectivo = [
                    'id' => $cotizacion->reflectivo->id,
                    'tipo' => $cotizacion->reflectivo->tipo_reflectivo,
                    'cantidad' => $cotizacion->reflectivo->cantidad,
                    'tipo' => 'reflectivo'
                ];
            }

            $logo = null;
            if ($cotizacion->logoCotizacion) {
                $logo = [
                    'id' => $cotizacion->logoCotizacion->id,
                    'tipo' => $cotizacion->logoCotizacion->tipo_logo,
                    'tipo' => 'logo'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'tipo_cotizacion' => $cotizacion->tipoCotizacion?->nombre ?? 'Desconocido',
                    'prendas' => $prendas,
                    'reflectivo' => $reflectivo,
                    'logo' => $logo,
                    'tiene_prendas' => count($prendas) > 0,
                    'tiene_reflectivo' => $reflectivo !== null,
                    'tiene_logo' => $logo !== null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar plantilla de pedido
     */
    public function plantilla($id)
    {
        return view('asesores.pedidos.show', [
            'pedido_id' => $id
        ]);
    }

    /**
     * Crear pedido desde cotización (formulario)
     */
    public function crearDesdeCotizacion(Request $request, $cotizacionId)
    {
        // Validar y procesar
        return response()->json([
            'success' => true,
            'message' => 'Use la ruta API POST /api/pedidos'
        ]);
    }

    /**
     * Crear pedido sin cotización (formulario)
     */
    public function crearSinCotizacion(Request $request)
    {
        // Validar y procesar
        return response()->json([
            'success' => true,
            'message' => 'Use la ruta API POST /api/pedidos'
        ]);
    }

    /**
     * Crear prenda sin cotización (AJAX)
     */
    public function crearPrendaSinCotizacion(Request $request)
    {
        try {
            $pedidoPrendaService = new PedidoPrendaService(
                new \App\Application\Services\ColorGeneroService(),
                new \App\Application\Services\TelasColorService()
            );

            // Procesar archivos del FormData
            $prendas = $request->input('prendas', []);
            foreach ($prendas as $prendaIndex => &$prendaData) {
                // Procesar fotos de prenda
                if ($request->hasFile("prendas.{$prendaIndex}.fotos")) {
                    $prendaData['fotos'] = [];
                    foreach ($request->file("prendas.{$prendaIndex}.fotos", []) as $archivo) {
                        if ($archivo && $archivo->isValid()) {
                            $ruta = $this->guardarImagenArchivo($archivo, "prendas/{$prendaIndex}");
                            if ($ruta) {
                                $prendaData['fotos'][] = [
                                    'ruta_original' => asset('storage/' . $ruta),
                                    'ruta_archivo' => $ruta,
                                    'tamaño' => $archivo->getSize()
                                ];
                            }
                        }
                    }
                }

                // Procesar fotos de telas
                if (isset($prendaData['telas']) && is_array($prendaData['telas'])) {
                    foreach ($prendaData['telas'] as $telaIndex => &$telaData) {
                        if ($request->hasFile("prendas.{$prendaIndex}.telas.{$telaIndex}.fotos")) {
                            $telaData['fotos'] = [];
                            foreach ($request->file("prendas.{$prendaIndex}.telas.{$telaIndex}.fotos", []) as $archivo) {
                                if ($archivo && $archivo->isValid()) {
                                    $ruta = $this->guardarImagenArchivo($archivo, "telas/{$prendaIndex}/{$telaIndex}");
                                    if ($ruta) {
                                        $telaData['fotos'][] = [
                                            'ruta_original' => asset('storage/' . $ruta),
                                            'ruta_archivo' => $ruta,
                                            'tamaño' => $archivo->getSize()
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Crear pedido
            $cliente = $request->input('cliente');
            $asesora = Auth::user();

            // Obtener o crear cliente
            $clienteModel = \App\Models\Cliente::firstOrCreate(
                ['nombre' => $cliente],
                ['estado' => 'activo']
            );

            // Generar número de pedido usando tabla de secuencias
            $secuenciaRow = \Illuminate\Support\Facades\DB::table('numero_secuencias')
                ->where('tipo', 'pedido_produccion')
                ->lockForUpdate()
                ->first();
            
            $numeroPedido = $secuenciaRow?->siguiente ?? 45696;
            
            // Incrementar secuencia para el próximo pedido
            \Illuminate\Support\Facades\DB::table('numero_secuencias')
                ->where('tipo', 'pedido_produccion')
                ->increment('siguiente');

            // Crear pedido
            $pedido = \App\Models\PedidoProduccion::create([
                'numero_pedido' => $numeroPedido,
                'cliente' => $cliente,
                'cliente_id' => $clienteModel->id,
                'asesor_id' => $asesora->id,
                'forma_de_pago' => $request->input('forma_de_pago'),
                'estado' => 'pendiente',
                'fecha_de_creacion_de_orden' => now(),
                'cantidad_total' => 0,
            ]);

            // Guardar prendas
            $pedidoPrendaService->guardarPrendasEnPedido($pedido, $prendas);

            // Calcular cantidad total
            $cantidadTotal = $pedido->prendas()->sum(\DB::raw('CAST(cantidad_talla AS CHAR)'));

            $pedido->update(['cantidad_total' => $cantidadTotal]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido PRENDA creado exitosamente',
                'numero_pedido' => $pedido->numero_pedido,
                'pedido_id' => $pedido->id,
                'cantidad_total' => $cantidadTotal,
                'redirect_url' => route('pedidos-produccion.index')
            ]);
        } catch (\Exception $e) {
            \Log::error(' Error en crearPrendaSinCotizacion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Guardar imagen desde archivo uploadado
     */
    private function guardarImagenArchivo($archivo, string $subdirectorioExtra): ?string
    {
        try {
            // Crear nombre único
            $timestamp = now()->format('YmdHis');
            $random = substr(uniqid(), -6);
            $extension = $archivo->getClientOriginalExtension();
            $nombreArchivo = "imagen_{$timestamp}_{$random}.{$extension}";

            // Ruta completa
            $directorio = "prendas-pedidos/{$subdirectorioExtra}";
            
            // Guardar archivo
            $ruta = \Storage::disk('public')->putFileAs(
                $directorio,
                $archivo,
                $nombreArchivo
            );

            \Log::info('✅ Imagen guardada', [
                'ruta' => $ruta,
                'nombre_original' => $archivo->getClientOriginalName()
            ]);

            return $ruta;
        } catch (\Exception $e) {
            \Log::error(' Error guardando imagen', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Obtener datos del pedido para edición modal
     * GET /asesores/pedidos-produccion/{id}/datos-edicion
     */
    public function obtenerDatosEdicion($pedidoId)
    {
        try {
            // Usar el mismo servicio que invoice-from-list usa
            $service = app(\App\Application\Services\Asesores\ObtenerDatosFacturaService::class);
            $datos = $service->obtener($pedidoId);
            
            \Log::info('[DATOS-EDICION] Datos cargados', ['pedido_id' => $pedidoId, 'prendas' => count($datos['prendas'] ?? [])]);

            // Retornar en formato que la modal espera
            return response()->json([
                'success' => true,
                'datos' => $datos
            ]);
        } catch (\Exception $e) {
            \Log::error('[DATOS-EDICION] Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar datos del pedido'
            ], 500);
        }
    }

    /**
     * Crear reflectivo sin cotización (AJAX)
     */
    public function crearReflectivoSinCotizacion(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Use la ruta API POST /api/pedidos/{id}/prendas'
        ]);
    }
}
