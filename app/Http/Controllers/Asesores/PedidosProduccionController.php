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
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PedidosProduccionController extends Controller
{
    /**
     * Mostrar formulario para crear pedido desde cotización
     */
    public function crearForm()
    {
        // Solo permitir crear pedidos de cotizaciones APROBADAS
        $cotizaciones = Cotizacion::where('asesor_id', Auth::id())
            ->whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])
            ->with([
                'asesor',
                'cliente',
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
     * Mostrar formulario EDITABLE para crear pedido desde cotización
     * 
     * @return \Illuminate\View\View
     */
    public function crearFormEditable()
    {
        // Solo permitir crear pedidos de cotizaciones APROBADAS
        $cotizaciones = Cotizacion::where('asesor_id', Auth::id())
            ->whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])
            ->with([
                'asesor',
                'cliente',
                'prendasCotizaciones.variantes.color',
                'prendasCotizaciones.variantes.tela',
                'prendasCotizaciones.variantes.tipoManga',
                'prendasCotizaciones.variantes.tipoBroche'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('asesores.pedidos.crear-desde-cotizacion-editable', compact('cotizaciones'));
    }

    /**
     * Listar pedidos de producción del asesor
     */
    public function index(Request $request)
    {
        $query = PedidoProduccion::whereHas('cotizacion', function ($query) {
            $query->where('asesor_id', Auth::id());
        });

        // Filtrar por estado si se proporciona
        if ($request->has('estado')) {
            $estado = $request->input('estado');
            
            // Debug: Log el estado recibido
            \Log::info('Filtro estado recibido: "' . $estado . '"');
            
            // Para "En Producción", filtrar por múltiples estados
            if ($estado === 'En Producción') {
                $query->whereIn('estado', ['No iniciado', 'En Ejecución']);
                \Log::info('Filtrando por En Producción (No iniciado + En Ejecución)');
            } else {
                $query->where('estado', $estado);
                \Log::info('Filtrando por estado: ' . $estado);
            }
        }

        $pedidos = $query->orderBy('created_at', 'desc')->paginate(15);
        
        \Log::info('Total de pedidos encontrados: ' . $pedidos->total());

        return view('asesores.pedidos.index', compact('pedidos'));
    }

    /**
     * Ver detalle de pedido de producción
     */
    public function show($id)
    {
        $pedido = PedidoProduccion::findOrFail($id);
        
        // Verificar que el pedido pertenece al asesor autenticado
        if ($pedido->asesor_id !== Auth::id()) {
            abort(403);
        }

        $prendas = $pedido->prendas()->with('procesos')->get();
        $cotizacion = $pedido->cotizacion;
        $prendasCotizacion = $cotizacion ? $cotizacion->prendasCotizaciones : [];

        return view('asesores.pedidos.plantilla-erp', compact('pedido', 'prendas', 'cotizacion', 'prendasCotizacion'));
    }

    /**
     * Ver plantilla ERP/Factura del pedido
     */
    public function plantilla($id)
    {
        $pedido = PedidoProduccion::findOrFail($id);
        
        // Verificar que el pedido pertenece al asesor autenticado
        if ($pedido->asesor_id !== Auth::id()) {
            abort(403);
        }

        $prendas = $pedido->prendas()->with('procesos')->get();
        $cotizacion = $pedido->cotizacion;
        $prendasCotizacion = $cotizacion ? $cotizacion->prendasCotizaciones : [];

        return view('asesores.pedidos.plantilla-erp', compact('pedido', 'prendas', 'cotizacion', 'prendasCotizacion'));
    }

    /**
     * Crear pedido de producción desde cotización (llamado desde CotizacionesController)
     */
    public function crearDesdeCotizacion($cotizacionId)
    {
        $cotizacion = Cotizacion::findOrFail($cotizacionId);
        
        if ($cotizacion->asesor_id !== Auth::id()) {
            abort(403);
        }

        // Validar que la cotización esté aprobada
        $estadosValidos = ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'];
        if (!in_array($cotizacion->estado, $estadosValidos)) {
            return response()->json([
                'success' => false,
                'message' => 'La cotización debe estar aprobada para crear un pedido. Estado actual: ' . $cotizacion->estado
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Crear pedido de producción
            $especificaciones = $cotizacion->especificaciones;
            
            // Si es string JSON, decodificar
            if (is_string($especificaciones)) {
                $especificaciones = json_decode($especificaciones, true) ?? [];
            }
            
            // Sanitizar numero_cotizacion (convertir a string si es array)
            $numeroCotizacion = $cotizacion->numero_cotizacion;
            if (is_array($numeroCotizacion)) {
                $numeroCotizacion = implode(',', $numeroCotizacion);
            }
            
            // Obtener forma_de_pago del request (enviado por frontend)
            $formaPago = request()->input('forma_de_pago');
            
            // Si no viene en el request, intentar obtener de las especificaciones
            if (empty($formaPago)) {
                $formaPago = $especificaciones['forma_pago'] ?? null;
                if (is_array($formaPago)) {
                    $formaPago = implode(',', $formaPago);
                }
            }
            
            \Log::info('💰 Forma de pago recibida:', [
                'forma_de_pago' => $formaPago,
                'from_request' => request()->input('forma_de_pago'),
                'from_spec' => $especificaciones['forma_pago'] ?? 'none'
            ]);
            
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $numeroCotizacion,
                'numero_pedido' => $this->generarNumeroPedido(),
                'cliente' => $cotizacion->cliente,
                'asesor_id' => auth()->id(),
                'forma_de_pago' => $formaPago,
                'estado' => 'No iniciado',
                'fecha_de_creacion_de_orden' => now(),
            ]);

            // Obtener datos del request (JSON o Form Data)
            $prendas = request()->input('prendas', []);
            
            // Si no hay prendas en el request, usar las de la cotización
            if (empty($prendas)) {
                $productos = $cotizacion->productos;
                
                // Si productos es un string JSON, decodificarlo
                if (is_string($productos)) {
                    $productos = json_decode($productos, true) ?? [];
                }
                
                // Obtener cantidades del request
                $cantidades = request()->input('cantidades', []);
                \Log::info('📊 Cantidades recibidas del frontend:', $cantidades);
                
                // Convertir productos al formato esperado
                if ($productos && is_array($productos)) {
                    foreach ($productos as $index => $producto) {
                        // Obtener cantidades para este producto
                        $productosCantidades = $cantidades[$index] ?? [];
                        
                        // Filtrar solo las cantidades > 0
                        $cantidadesFiltradasPorTalla = [];
                        foreach ($productosCantidades as $talla => $cantidad) {
                            $cantidadInt = (int)$cantidad;
                            if ($cantidadInt > 0) {
                                $cantidadesFiltradasPorTalla[$talla] = $cantidadInt;
                            }
                        }
                        
                        $prendas[] = array_merge($producto, [
                            'index' => $index,
                            'cantidades' => $cantidadesFiltradasPorTalla
                        ]);
                    }
                }
            }

            // Crear prendas del pedido
            if (!empty($prendas)) {
                foreach ($prendas as $prenda) {
                    $index = $prenda['index'] ?? 0;
                    $cantidadesPorTalla = $prenda['cantidades'] ?? [];
                    
                    // Calcular cantidad total
                    $cantidadTotal = array_sum($cantidadesPorTalla);

                    // CONSTRUIR DESCRIPCIÓN EN EL FORMATO REQUERIDO
                    $descripcionPrenda = $this->construirDescripcionPrenda(
                        $index + 1,
                        $prenda,
                        $cantidadesPorTalla
                    );

                    \Log::info('✅ Prenda creada', [
                        'index' => $index,
                        'nombre' => $prenda['nombre_producto'] ?? 'Sin nombre',
                        'cantidades_por_talla' => $cantidadesPorTalla,
                        'cantidad_total' => $cantidadTotal,
                        'descripcion_construida' => $descripcionPrenda
                    ]);

                    $prendaPedido = PrendaPedido::create([
                        'numero_pedido' => $pedido->numero_pedido,
                        'nombre_prenda' => $prenda['nombre_producto'] ?? 'Sin nombre',
                        'cantidad' => $cantidadTotal,
                        'descripcion' => $descripcionPrenda,
                        'cantidad_talla' => json_encode($cantidadesPorTalla)
                    ]);

                    // Crear proceso inicial para cada prenda
                    ProcesoPrenda::create([
                        'numero_pedido' => $pedido->numero_pedido,
                        'proceso' => 'Creación Orden',
                        'estado_proceso' => 'Completado',
                        'fecha_inicio' => now(),
                        'fecha_fin' => now(),
                    ]);
                    
                    // HEREDAR VARIANTES DE LA COTIZACIÓN
                    $this->heredarVariantesDePrenda($cotizacion, $prendaPedido, $index);
                }
            }

            // NO cambiar el estado de la cotización para permitir crear múltiples pedidos
            // La cotización mantiene su estado actual (enviada, aceptada, etc.)

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
                // NOTA: NO sobrescribir cantidad_talla, ya que tiene el mapeo correcto talla:cantidad
                $prendaPedido->update([
                    'color_id' => $variante->color_id,
                    'tela_id' => $variante->tela_id,
                    'tipo_manga_id' => $variante->tipo_manga_id,
                    'tipo_broche_id' => $variante->tipo_broche_id,
                    'tiene_bolsillos' => $variante->tiene_bolsillos,
                    'tiene_reflectivo' => $variante->tiene_reflectivo,
                    'descripcion_variaciones' => $variante->descripcion_adicional
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

    /**
     * Construir descripción de prenda en el formato requerido
     * 
     * Formato: Un párrafo único con descripción + todas las variaciones + observaciones
     * Ejemplo: "MANGA CORTA CUELLO Y PUÑOS TELA PIQUE REF:123 AZUL MARINO DAMA MANGA CORTA CON BOLSILLO BOTON OBSERVACION"
     */
    private function construirDescripcionPrenda($numeroPrenda, $producto, $cantidadesPorTalla)
    {
        $lineas = [];
        
        // 1. Prenda número y nombre
        $nombrePrenda = strtoupper($producto['nombre_producto'] ?? 'PRENDA');
        $lineas[] = "Prenda $numeroPrenda: $nombrePrenda";
        
        // 2. Descripción
        if (!empty($producto['descripcion'])) {
            $lineas[] = "Descripción: " . strtoupper($producto['descripcion']);
        }
        
        // 3. Tela con referencia
        if (!empty($producto['tela'])) {
            $tela = strtoupper($producto['tela']);
            if (!empty($producto['tela_referencia'])) {
                $tela .= ' REF:' . strtoupper($producto['tela_referencia']);
            }
            $lineas[] = "Tela: " . $tela;
        }
        
        // 4. Color
        if (!empty($producto['color'])) {
            $lineas[] = "Color: " . strtoupper($producto['color']);
        }
        
        // 5. Género
        if (!empty($producto['genero'])) {
            $lineas[] = "Genero: " . strtoupper($producto['genero']);
        }
        
        // 6. Manga + observación
        if (!empty($producto['manga'])) {
            $manga = "Manga: " . strtoupper($producto['manga']);
            if (!empty($producto['manga_obs'])) {
                $manga .= ' - ' . strtoupper($producto['manga_obs']);
            }
            $lineas[] = $manga;
        }
        
        // 7. Bolsillos + observación
        if (!empty($producto['tiene_bolsillos']) && $producto['tiene_bolsillos']) {
            $bolsillos = "Bolsillos: SI";
            if (!empty($producto['bolsillos_obs'])) {
                $bolsillos .= ' - ' . strtoupper($producto['bolsillos_obs']);
            }
            $lineas[] = $bolsillos;
        }
        
        // 8. Broche + observación
        if (!empty($producto['broche'])) {
            $broche = "Broche: " . strtoupper($producto['broche']);
            if (!empty($producto['broche_obs'])) {
                $broche .= ' - ' . strtoupper($producto['broche_obs']);
            }
            $lineas[] = $broche;
        }
        
        // 9. Reflectivo + observación
        if (!empty($producto['tiene_reflectivo']) && $producto['tiene_reflectivo']) {
            $reflectivo = "Reflectivo: SI";
            if (!empty($producto['reflectivo_obs'])) {
                $reflectivo .= ' - ' . strtoupper($producto['reflectivo_obs']);
            }
            $lineas[] = $reflectivo;
        }
        
        // 10. Talla con cantidades (AL FINAL)
        if (!empty($cantidadesPorTalla) && is_array($cantidadesPorTalla)) {
            $tallas = [];
            foreach ($cantidadesPorTalla as $talla => $cantidad) {
                if ($cantidad > 0) {
                    $tallas[] = "{$talla}:{$cantidad}";
                }
            }
            if (!empty($tallas)) {
                $lineas[] = "Tallas: " . implode(', ', $tallas);
            }
        }
        
        // Retornar con saltos de línea entre cada elemento
        return implode("\n", $lineas);
    }

    /**
     * Obtener datos COMPLETOS de una cotización con todas sus prendas e información (para AJAX)
     * 
     * @param int $cotizacionId
     * @return JsonResponse
     */
    public function obtenerDatosCotizacion(int $cotizacionId): JsonResponse
    {
        try {
            $cotizacion = Cotizacion::with([
                'cliente',
                'asesor',
                // Prendas y sus relaciones completas
                'prendas.variantes.manga',
                'prendas.variantes.broche',
                'prendas.variantes.genero',
                'prendas.tallas',
                'prendas.fotos',
                'prendas.telas',
                'prendas.telaFotos',
                // Logo - solo fotos es relación, el resto son campos JSON
                'logoCotizacion.fotos',
                // Reflectivo
                'reflectivo',
            ])->findOrFail($cotizacionId);

            // Verificar que pertenezca al asesor actual
            if ($cotizacion->asesor_id !== Auth::id()) {
                return response()->json([
                    'error' => 'No tienes permiso para acceder a esta cotización'
                ], 403);
            }

            return response()->json([
                'id' => $cotizacion->id,
                'numero' => $cotizacion->numero_cotizacion,
                'cliente' => $cotizacion->cliente ? $cotizacion->cliente->nombre : '',
                'asesora' => $cotizacion->asesor ? $cotizacion->asesor->name : Auth::user()->name,
                'forma_pago' => $cotizacion->forma_pago ?? '',
                'tipo_venta' => $cotizacion->tipo_venta ?? '',
                'especificaciones' => $cotizacion->especificaciones ?? [],
                'observaciones_generales' => $cotizacion->observaciones_generales ?? [],
                'ubicaciones' => $cotizacion->ubicaciones ?? [],
                
                // Prendas con TODA la información
                'prendas' => $cotizacion->prendas->map(function($prenda) {
                    // Obtener primera variante
                    $primerVariante = $prenda->variantes->first();
                    
                    // Construir variantes con información completa
                    $variantes = [];
                    if ($primerVariante) {
                        $variantes = [
                            'id' => $primerVariante->id,
                            'prenda_cot_id' => $primerVariante->prenda_cot_id,
                            'tipo_prenda' => $primerVariante->tipo_prenda,
                            'es_jean_pantalon' => $primerVariante->es_jean_pantalon,
                            'tipo_jean_pantalon' => $primerVariante->tipo_jean_pantalon,
                            'genero_id' => $primerVariante->genero_id,
                            'genero_nombre' => $primerVariante->genero ? $primerVariante->genero->nombre : null,
                            'color' => $primerVariante->color,
                            'tipo_manga_id' => $primerVariante->tipo_manga_id,
                            'tipo_manga' => $primerVariante->manga ? $primerVariante->manga->nombre : null,
                            'obs_manga' => $primerVariante->obs_manga,
                            'tipo_broche_id' => $primerVariante->tipo_broche_id,
                            'tipo_broche' => $primerVariante->broche ? $primerVariante->broche->nombre : null,
                            'obs_broche' => $primerVariante->obs_broche,
                            'tiene_bolsillos' => $primerVariante->tiene_bolsillos,
                            'obs_bolsillos' => $primerVariante->obs_bolsillos,
                            'aplica_manga' => $primerVariante->aplica_manga,
                            'aplica_broche' => $primerVariante->aplica_broche,
                            'tiene_reflectivo' => $primerVariante->tiene_reflectivo,
                            'obs_reflectivo' => $primerVariante->obs_reflectivo,
                            'descripcion_adicional' => $primerVariante->descripcion_adicional,
                            'telas_multiples' => is_array($primerVariante->telas_multiples) ? $primerVariante->telas_multiples : (is_string($primerVariante->telas_multiples) ? json_decode($primerVariante->telas_multiples, true) : []),
                            'created_at' => $primerVariante->created_at,
                            'updated_at' => $primerVariante->updated_at,
                        ];
                    }
                    
                    // Obtener tallas
                    $tallas = $prenda->tallas->pluck('talla')->toArray();
                    
                    // Obtener fotos de prenda con URLs completas
                    $fotos = $prenda->fotos->map(function($foto) {
                        // El campo 'url' puede contener la ruta relativa o completa
                        return '/storage/' . ltrim($foto->ruta_webp, '/');
                    })->toArray();
                    
                    // Obtener telas
                    $telas = $prenda->telas->map(function($tela) {
                        return [
                            'id' => $tela->id,
                            'color' => $tela->color,
                            'nombre_tela' => $tela->nombre_tela,
                            'referencia' => $tela->referencia,
                            'url_imagen' => $tela->url_imagen,
                        ];
                    })->toArray();
                    
                    // Obtener fotos de telas con URLs correctas
                    $telaFotos = $prenda->telaFotos->map(function($telaFoto) {
                        return [
                            'id' => $telaFoto->id,
                            'tela_id' => $telaFoto->tela_id,
                            'url' => '/storage/' . ltrim($telaFoto->ruta_webp ?? $telaFoto->url, '/'),
                            'ruta_original' => '/storage/' . ltrim($telaFoto->ruta_original, '/'),
                            'ruta_webp' => '/storage/' . ltrim($telaFoto->ruta_webp, '/'),
                        ];
                    })->toArray();
                    
                    return [
                        'id' => $prenda->id,
                        'nombre_producto' => $prenda->nombre_producto,
                        'descripcion' => $prenda->descripcion,
                        'cantidad' => $prenda->cantidad,
                        'tallas' => $tallas,
                        'fotos' => $fotos,
                        'variantes' => $variantes,
                        'telas' => $telas,
                        'telaFotos' => $telaFotos,
                    ];
                })->toArray(),
                
                // Logo información COMPLETA
                'logo' => $cotizacion->logoCotizacion ? [
                    'id' => $cotizacion->logoCotizacion->id,
                    'descripcion' => $cotizacion->logoCotizacion->descripcion,
                    'tipo_venta' => $cotizacion->logoCotizacion->tipo_venta,
                    'imagenes' => is_array($cotizacion->logoCotizacion->imagenes) ? $cotizacion->logoCotizacion->imagenes : [],
                    'tecnicas' => (is_array($cotizacion->logoCotizacion->tecnicas) ? $cotizacion->logoCotizacion->tecnicas : (is_string($cotizacion->logoCotizacion->tecnicas) ? json_decode($cotizacion->logoCotizacion->tecnicas, true) : [])) ?? [],
                    'observaciones_tecnicas' => $cotizacion->logoCotizacion->observaciones_tecnicas,
                    'ubicaciones' => (is_array($cotizacion->logoCotizacion->ubicaciones) ? $cotizacion->logoCotizacion->ubicaciones : (is_string($cotizacion->logoCotizacion->ubicaciones) ? json_decode($cotizacion->logoCotizacion->ubicaciones, true) : [])) ?? [],
                    'observaciones_generales' => (is_array($cotizacion->logoCotizacion->observaciones_generales) ? $cotizacion->logoCotizacion->observaciones_generales : (is_string($cotizacion->logoCotizacion->observaciones_generales) ? json_decode($cotizacion->logoCotizacion->observaciones_generales, true) : [])) ?? [],
                    'fotos' => $cotizacion->logoCotizacion->fotos->map(function($foto) {
                        return [
                            'id' => $foto->id,
                            'url' => '/storage/' . ltrim($foto->ruta_webp, '/'),
                            'ruta_original' => '/storage/' . ltrim($foto->ruta_original, '/'),
                            'ruta_webp' => '/storage/' . ltrim($foto->ruta_webp, '/'),
                        ];
                    })->toArray(),
                ] : null,
                
                // Reflectivo INFORMACIÓN COMPLETA
                'reflectivo' => $cotizacion->reflectivo ? [
                    'id' => $cotizacion->reflectivo->id,
                    'ubicacion' => $cotizacion->reflectivo->ubicacion,
                    'descripcion' => $cotizacion->reflectivo->descripcion,
                    'observaciones' => $cotizacion->reflectivo->observaciones,
                ] : null,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
}

