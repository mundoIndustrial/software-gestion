<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\Cotizacion;
use App\Models\VariantePrenda;
use App\Models\PrendaCotizacionFriendly;
use App\Enums\EstadoPedido;
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
        })
        ->with([
            'prendas' => function ($q) {
                $q->with(['color', 'tela', 'tipoManga', 'procesos']);
            }
        ]);

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
        $cotizacion = Cotizacion::with('tipoCotizacion')
            ->findOrFail($cotizacionId);
        
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
            
            // Determinar el área basado en el tipo de cotización
            $tipoCotizacion = strtolower(trim($cotizacion->tipoCotizacion?->nombre ?? ''));
            $area = ($tipoCotizacion === 'reflectivo') ? 'Costura' : null;
            
            \Log::info('🎯 Determinando área del pedido', [
                'tipo_cotizacion' => $tipoCotizacion,
                'area_asignada' => $area,
            ]);
            
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $numeroCotizacion,
                'numero_pedido' => $this->generarNumeroPedido(),
                'cliente' => $cotizacion->cliente->nombre ?? 'Sin nombre',
                'asesor_id' => auth()->id(),
                'forma_de_pago' => $formaPago,
                'area' => $area,
                'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
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
                        'cantidad_talla' => json_encode($cantidadesPorTalla),
                        'color_id' => $prenda['color_id'] ?? null,
                        'tela_id' => $prenda['tela_id'] ?? null,
                        'tipo_manga_id' => $prenda['tipo_manga_id'] ?? null,
                        'tipo_broche_id' => $prenda['tipo_broche_id'] ?? null,
                        'tiene_bolsillos' => ($prenda['tiene_bolsillos'] ?? false) ? 1 : 0,
                        'tiene_reflectivo' => ($prenda['tiene_reflectivo'] ?? false) ? 1 : 0,
                    ]);

                    // Crear proceso inicial para cada prenda (SOLO si NO es reflectivo)
                    // Para reflectivo, se crea en crearProcesosParaReflectivo()
                    $tipoCotizacion = strtolower(trim($cotizacion->tipoCotizacion?->nombre ?? ''));
                    if ($tipoCotizacion !== 'reflectivo') {
                        ProcesoPrenda::create([
                            'numero_pedido' => $pedido->numero_pedido,
                            'prenda_pedido_id' => $prendaPedido->id,
                            'proceso' => 'Creación Orden',
                            'estado_proceso' => 'Completado',
                            'fecha_inicio' => now(),
                            'fecha_fin' => now(),
                        ]);
                    }
                    
                    // HEREDAR VARIANTES DE LA COTIZACIÓN
                    $this->heredarVariantesDePrenda($cotizacion, $prendaPedido, $index);
                }
            }

            // Calcular cantidad_total: suma de todas las cantidades de todas las prendas
            $cantidadTotalPedido = PrendaPedido::where('numero_pedido', $pedido->numero_pedido)
                ->sum('cantidad');
            
            $pedido->update([
                'cantidad_total' => $cantidadTotalPedido
            ]);

            // ✅ CREAR PROCESOS AUTOMÁTICAMENTE PARA COTIZACIONES REFLECTIVO
            \Log::info('📞 Llamando a crearProcesosParaReflectivo', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cotizacion_id' => $cotizacion->id,
                'tipo_cotizacion' => $cotizacion->tipoCotizacion?->nombre,
            ]);
            $this->crearProcesosParaReflectivo($pedido, $cotizacion);

            // ✅ PROCESAR FOTOS DEL REFLECTIVO SI EXISTEN
            $reflectivoFotosIds = request()->input('reflectivo_fotos_ids', []);
            if (!empty($reflectivoFotosIds)) {
                \Log::info('📸 [PedidosProduccionController] Procesando fotos de reflectivo', [
                    'fotos_ids' => $reflectivoFotosIds
                ]);
                
                // Obtener el reflectivo de la cotización
                $reflectivo = \App\Models\ReflectivoCotizacion::where('cotizacion_id', $cotizacion->id)->first();
                
                if ($reflectivo) {
                    $fotosReflectivo = \App\Models\ReflectivoCotizacionFoto::whereIn('id', $reflectivoFotosIds)
                        ->where('reflectivo_cotizacion_id', $reflectivo->id)
                        ->get();
                    
                    \Log::info('📸 Fotos de reflectivo encontradas', [
                        'cantidad' => $fotosReflectivo->count()
                    ]);
                    
                    // Agregar las fotos del reflectivo a la primera prenda
                    if ($fotosReflectivo->count() > 0 && !empty($prendas)) {
                        if (!isset($prendas[0]['fotos'])) {
                            $prendas[0]['fotos'] = [];
                        }
                        
                        foreach ($fotosReflectivo as $foto) {
                            $prendas[0]['fotos'][] = [
                                'url' => '/storage/' . ltrim($foto->ruta_webp ?? $foto->ruta_original, '/'),
                                'ruta_original' => $foto->ruta_original,
                                'ruta_webp' => $foto->ruta_webp,
                                'orden' => $foto->orden ?? 0,
                            ];
                        }
                        
                        \Log::info('✅ Fotos de reflectivo agregadas a prendas[0]', [
                            'total_fotos_prenda_0' => count($prendas[0]['fotos'])
                        ]);
                    }
                }
            }

            // ✅ VERIFICAR SI HAY FOTOS EN EL FORMULARIO
            \Log::info('📸 [DEBUG] Verificando fotos en formulario', [
                'total_prendas' => count($prendas),
            ]);
            
            $hayFotosEnFormulario = false;
            foreach ($prendas as $index => $prenda) {
                \Log::info("📸 [DEBUG] Prenda {$index}", [
                    'tiene_fotos' => !empty($prenda['fotos']),
                    'cantidad_fotos' => count($prenda['fotos'] ?? []),
                    'tiene_telas' => !empty($prenda['telas']),
                    'cantidad_telas' => count($prenda['telas'] ?? []),
                    'tiene_logos' => !empty($prenda['logos']),
                    'cantidad_logos' => count($prenda['logos'] ?? []),
                ]);
                
                if (!empty($prenda['fotos']) || !empty($prenda['telas']) || !empty($prenda['logos'])) {
                    $hayFotosEnFormulario = true;
                }
            }
            
            \Log::info('📸 [DEBUG] Resultado verificación', [
                'hay_fotos_en_formulario' => $hayFotosEnFormulario,
            ]);
            
            if ($hayFotosEnFormulario) {
                // GUARDAR SOLO LAS FOTOS QUE EL USUARIO ENVIÓ (respeta lo que eliminó)
                \Log::info('📸 [PedidosProduccionController] Guardando fotos seleccionadas por el usuario', [
                    'numero_pedido' => $pedido->numero_pedido,
                    'total_prendas' => count($prendas),
                ]);

                try {
                    // Obtener prendas del pedido recién creadas
                    $prendasPedido = PrendaPedido::where('numero_pedido', $pedido->numero_pedido)
                        ->get();

                    // Variable para guardar fotos de logo solo una vez
                    $fotosLogoGuardadas = false;

                    $indexPrenda = 0;
                    foreach ($prendasPedido as $prendaPedido) {
                        if (isset($prendas[$indexPrenda])) {
                            $prendaFormulario = $prendas[$indexPrenda];
                            
                            \Log::info("📸 [DEBUG] Procesando prenda {$indexPrenda}", [
                                'prenda_pedido_id' => $prendaPedido->id,
                                'tiene_fotos' => !empty($prendaFormulario['fotos']),
                                'estructura_fotos' => $prendaFormulario['fotos'] ?? [],
                            ]);

                            // Guardar fotos de prenda
                            if (!empty($prendaFormulario['fotos'])) {
                                foreach ($prendaFormulario['fotos'] as $orden => $foto) {
                                    // La foto puede venir como string (ruta directa) o como objeto
                                    if (is_string($foto)) {
                                        $rutaFoto = $foto;
                                    } else {
                                        $rutaFoto = $foto['ruta_webp'] ?? $foto['ruta_original'] ?? $foto['url'] ?? null;
                                    }
                                    
                                    if ($rutaFoto) {
                                        DB::table('prenda_fotos_pedido')->insert([
                                            'prenda_pedido_id' => $prendaPedido->id,
                                            'ruta_original' => is_array($foto) ? ($foto['ruta_original'] ?? $rutaFoto) : $rutaFoto,
                                            'ruta_webp' => is_array($foto) ? ($foto['ruta_webp'] ?? $rutaFoto) : $rutaFoto,
                                            'ruta_miniatura' => is_array($foto) ? ($foto['ruta_miniatura'] ?? null) : null,
                                            'orden' => $orden + 1,
                                            'ancho' => is_array($foto) ? ($foto['ancho'] ?? null) : null,
                                            'alto' => is_array($foto) ? ($foto['alto'] ?? null) : null,
                                            'tamaño' => is_array($foto) ? ($foto['tamaño'] ?? null) : null,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                    }
                                }
                                \Log::info('✅ Fotos de prenda guardadas', [
                                    'prenda_id' => $prendaPedido->id,
                                    'cantidad_fotos' => count($prendaFormulario['fotos']),
                                ]);
                            }

                            // Guardar fotos de telas
                            if (!empty($prendaFormulario['telas'])) {
                                foreach ($prendaFormulario['telas'] as $tela) {
                                    // Las telas pueden venir como array de fotos directamente
                                    $fotosTela = [];
                                    if (isset($tela['fotos'])) {
                                        $fotosTela = $tela['fotos'];
                                    } elseif (isset($tela['url']) || isset($tela['ruta_webp'])) {
                                        // La tela es una foto directamente
                                        $fotosTela = [$tela];
                                    }
                                    
                                    if (!empty($fotosTela)) {
                                        foreach ($fotosTela as $orden => $foto) {
                                            // La foto puede venir como string o como objeto
                                            if (is_string($foto)) {
                                                $rutaFoto = $foto;
                                            } else {
                                                $rutaFoto = $foto['ruta_webp'] ?? $foto['ruta_original'] ?? $foto['url'] ?? null;
                                            }
                                            
                                            if ($rutaFoto) {
                                                DB::table('prenda_fotos_tela_pedido')->insert([
                                                    'prenda_pedido_id' => $prendaPedido->id,
                                                    'tela_id' => is_array($tela) ? ($tela['tela_id'] ?? null) : null,
                                                    'color_id' => is_array($tela) ? ($tela['color_id'] ?? null) : null,
                                                    'ruta_original' => is_array($foto) ? ($foto['ruta_original'] ?? $rutaFoto) : $rutaFoto,
                                                    'ruta_webp' => is_array($foto) ? ($foto['ruta_webp'] ?? $rutaFoto) : $rutaFoto,
                                                    'ruta_miniatura' => is_array($foto) ? ($foto['ruta_miniatura'] ?? null) : null,
                                                    'orden' => $orden + 1,
                                                    'ancho' => is_array($foto) ? ($foto['ancho'] ?? null) : null,
                                                    'alto' => is_array($foto) ? ($foto['alto'] ?? null) : null,
                                                    'tamaño' => is_array($foto) ? ($foto['tamaño'] ?? null) : null,
                                                    'created_at' => now(),
                                                    'updated_at' => now(),
                                                ]);
                                            }
                                        }
                                        \Log::info('✅ Fotos de tela guardadas', [
                                            'prenda_id' => $prendaPedido->id,
                                            'cantidad_fotos' => count($fotosTela),
                                        ]);
                                    }
                                }
                            }

                            // Guardar fotos de logos/bordados SOLO UNA VEZ (no por cada prenda)
                            if (!empty($prendaFormulario['logos']) && !$fotosLogoGuardadas) {
                                foreach ($prendaFormulario['logos'] as $orden => $logo) {
                                    $rutaLogo = is_string($logo) ? $logo : ($logo['ruta_webp'] ?? $logo['ruta_original'] ?? $logo['url'] ?? null);
                                    
                                    if ($rutaLogo) {
                                        DB::table('prenda_fotos_logo_pedido')->insert([
                                            'prenda_pedido_id' => $prendaPedido->id,
                                            'ruta_original' => is_array($logo) ? ($logo['ruta_original'] ?? $rutaLogo) : $rutaLogo,
                                            'ruta_webp' => is_array($logo) ? ($logo['ruta_webp'] ?? $rutaLogo) : $rutaLogo,
                                            'ruta_miniatura' => is_array($logo) ? ($logo['ruta_miniatura'] ?? null) : null,
                                            'orden' => $orden + 1,
                                            'ubicacion' => is_array($logo) ? ($logo['ubicacion'] ?? null) : null,
                                            'ancho' => is_array($logo) ? ($logo['ancho'] ?? null) : null,
                                            'alto' => is_array($logo) ? ($logo['alto'] ?? null) : null,
                                            'tamaño' => is_array($logo) ? ($logo['tamaño'] ?? null) : null,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                    }
                                }
                                $fotosLogoGuardadas = true; // Marcar como guardadas para no repetir
                                \Log::info('✅ Fotos de logo guardadas (solo una vez)', [
                                    'prenda_id' => $prendaPedido->id,
                                    'cantidad_fotos' => count($prendaFormulario['logos']),
                                ]);
                            }
                        }
                        $indexPrenda++;
                    }

                    \Log::info('✅ [PedidosProduccionController] Todas las fotos del usuario guardadas');
                } catch (\Exception $e) {
                    \Log::error('❌ [PedidosProduccionController] Error al guardar fotos del usuario', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            } else {
                // Si NO hay fotos del formulario, COPIAR de la cotización (fallback)
                \Log::info('🖼️ [PedidosProduccionController] No hay fotos del formulario, copiando de cotización');
                try {
                    $copiarImagenesService = app(\App\Application\Services\CopiarImagenesCotizacionAPedidoService::class);
                    $copiarImagenesService->copiarImagenesCotizacionAPedido($cotizacion->id, $pedido->id);
                    \Log::info('✅ [PedidosProduccionController] Imágenes copiadas exitosamente');
                } catch (\Exception $e) {
                    \Log::error('❌ [PedidosProduccionController] Error al copiar imágenes', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
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
            \Log::info('🔍 [heredarVariantes] Iniciando herencia de variantes', [
                'cotizacion_id' => $cotizacion->id,
                'prenda_pedido_id' => $prendaPedido->id,
                'index' => $index,
            ]);

            // Obtener prendas de cotización desde la tabla correcta
            $prendasCot = \App\Models\PrendaCot::where('cotizacion_id', $cotizacion->id)
                ->orderBy('id')
                ->get();
            
            if (!isset($prendasCot[$index])) {
                \Log::warning('⚠️ No se encontró prenda de cotización en índice', [
                    'index' => $index,
                    'total_prendas_cot' => $prendasCot->count()
                ]);
                return;
            }
            
            $prendaCot = $prendasCot[$index];
            
            \Log::info('🔍 [heredarVariantes] Prenda de cotización encontrada', [
                'prenda_cot_id' => $prendaCot->id,
                'nombre' => $prendaCot->nombre_producto,
            ]);
            
            // Obtener variantes de la tabla prenda_variantes_cot
            $variantes = \DB::table('prenda_variantes_cot')
                ->where('prenda_cot_id', $prendaCot->id)
                ->get();
            
            \Log::info('🔍 [heredarVariantes] Variantes encontradas', [
                'total_variantes' => $variantes->count(),
            ]);
            
            if ($variantes->isEmpty()) {
                \Log::info('ℹ️ Sin variantes en prenda_variantes_cot, intentando con prenda directa');
                
                // Si no hay variantes, usar los datos de la prenda directamente
                $prendaPedido->update([
                    'color_id' => $prendaCot->color_id,
                    'tela_id' => $prendaCot->tela_id,
                    'tipo_manga_id' => $prendaCot->tipo_manga_id,
                    'tipo_broche_id' => $prendaCot->tipo_broche_id,
                    'tiene_bolsillos' => $prendaCot->tiene_bolsillos ?? 0,
                    'tiene_reflectivo' => $prendaCot->tiene_reflectivo ?? 0,
                ]);
                
                \Log::info('✅ Datos heredados desde prenda_cot directamente', [
                    'color_id' => $prendaCot->color_id,
                    'tela_id' => $prendaCot->tela_id,
                    'tipo_manga_id' => $prendaCot->tipo_manga_id,
                    'tipo_broche_id' => $prendaCot->tipo_broche_id,
                ]);
                
                return;
            }
            
            // Copiar la primera variante
            $variante = $variantes->first();
            
            $telaId = null;
            $colorId = null;
            
            // 1. Buscar o crear COLOR usando el campo directo 'color' de la variante
            if (!empty($variante->color)) {
                $color = \DB::table('colores_prenda')
                    ->where('nombre', 'LIKE', '%' . $variante->color . '%')
                    ->first();
                
                if (!$color) {
                    $colorId = \DB::table('colores_prenda')->insertGetId([
                        'nombre' => $variante->color,
                        'activo' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    \Log::info('✅ Color creado', ['nombre' => $variante->color, 'id' => $colorId]);
                } else {
                    $colorId = $color->id;
                }
            }
            
            // 2. Buscar o crear TELA usando telas_multiples JSON
            if (!empty($variante->telas_multiples)) {
                $telasMultiples = json_decode($variante->telas_multiples, true);
                if (is_array($telasMultiples) && !empty($telasMultiples)) {
                    $primeraTela = $telasMultiples[0];
                    
                    if (!empty($primeraTela['tela'])) {
                        $tela = \DB::table('telas_prenda')
                            ->where('nombre', 'LIKE', '%' . $primeraTela['tela'] . '%')
                            ->first();
                        
                        if (!$tela) {
                            $telaId = \DB::table('telas_prenda')->insertGetId([
                                'nombre' => $primeraTela['tela'],
                                'activo' => 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            \Log::info('✅ Tela creada', ['nombre' => $primeraTela['tela'], 'id' => $telaId]);
                        } else {
                            $telaId = $tela->id;
                        }
                    }
                }
            }
            
            \Log::info('🔍 [heredarVariantes] IDs obtenidos/creados', [
                'color_campo_directo' => $variante->color,
                'color_id' => $colorId,
                'tela_desde_json' => isset($telasMultiples) ? ($telasMultiples[0]['tela'] ?? null) : null,
                'tela_id' => $telaId,
            ]);
            
            $prendaPedido->update([
                'color_id' => $colorId,
                'tela_id' => $telaId,
                'tipo_manga_id' => $variante->tipo_manga_id,
                'tipo_broche_id' => $variante->tipo_broche_id,
                'tiene_bolsillos' => $variante->tiene_bolsillos ?? 0,
                'tiene_reflectivo' => $variante->tiene_reflectivo ?? 0,
                'descripcion_variaciones' => $variante->descripcion_adicional ?? null,
            ]);
            
            \Log::info('✅ Variantes heredadas exitosamente desde prenda_variantes_cot', [
                'prenda_pedido_id' => $prendaPedido->id,
                'color_id' => $colorId,
                'tela_id' => $telaId,
                'tipo_manga_id' => $variante->tipo_manga_id,
                'tipo_broche_id' => $variante->tipo_broche_id,
                'telas_multiples' => $variante->telas_multiples,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('❌ Error heredando variantes', [
                'prenda_pedido_id' => $prendaPedido->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Generar número de pedido único usando secuencia centralizada
     * Retorna solo el número entero (sin prefijo PEP-)
     * Usa DB lock para prevenir race conditions
     */
    private function generarNumeroPedido()
    {
        try {
            $secuencia = \DB::table('numero_secuencias')
                ->lockForUpdate()
                ->where('tipo', 'pedidos_produccion_universal')
                ->first();

            if (!$secuencia) {
                \Log::warning('Secuencia pedidos_produccion_universal NO ENCONTRADA. Usando fallback.');
                $ultimoPedido = PedidoProduccion::max('numero_pedido') ?? 0;
                return (int) ($ultimoPedido + 1); // ✅ Retornar solo el número
            }

            $siguiente = $secuencia->siguiente;
            
            // Incrementar la secuencia
            \DB::table('numero_secuencias')
                ->where('tipo', 'pedidos_produccion_universal')
                ->update([
                    'siguiente' => $siguiente + 1,
                    'updated_at' => now(),
                ]);

            // ✅ Retornar solo el número entero (sin prefijo PEP-)
            $numeroPedido = (int) $siguiente;
            
            \Log::info('Número de pedido generado', [
                'numero' => $numeroPedido,
                'tipo' => gettype($numeroPedido),
                'secuencia_anterior' => $siguiente,
                'secuencia_nueva' => $siguiente + 1,
            ]);

            return $numeroPedido;
        } catch (\Exception $e) {
            \Log::error('Error generando número de pedido', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
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
        
        // 1. Descripción del producto (incluye ubicaciones del reflectivo si aplica)
        if (!empty($producto['descripcion'])) {
            $lineas[] = strtoupper($producto['descripcion']);
        }
        
        // 2. Tallas y cantidades
        if (!empty($cantidadesPorTalla) && is_array($cantidadesPorTalla)) {
            $tallasTexto = [];
            foreach ($cantidadesPorTalla as $talla => $cantidad) {
                if ($cantidad > 0) {
                    $tallasTexto[] = "$talla: $cantidad";
                }
            }
            if (!empty($tallasTexto)) {
                $lineas[] = "TALLAS: " . implode(', ', $tallasTexto);
            }
        }
        
        // 3. Cantidad total
        $cantidadTotal = array_sum($cantidadesPorTalla);
        if ($cantidadTotal > 0) {
            $lineas[] = "CANTIDAD TOTAL: $cantidadTotal";
        }
        
        return !empty($lineas) ? implode("\n\n", $lineas) : '-';
    }

    /**
     * FUNCIÓN OBSOLETA - Mantener por compatibilidad pero no se usa
     * La descripción ahora se genera dinámicamente en el frontend
     */
    private function construirDescripcionPrendaCompleta($numeroPrenda, $producto, $cantidadesPorTalla)
    {
        $lineas = [];
        
        // 1. Prenda número y nombre
        $nombrePrenda = strtoupper($producto['nombre_producto'] ?? 'PRENDA');
        $lineas[] = "Prenda $numeroPrenda: $nombrePrenda";
        
        // 2. Descripción
        if (!empty($producto['descripcion'])) {
            $lineas[] = "Descripción: " . strtoupper($producto['descripcion']);
        }
        
        // 3. Telas/Colores múltiples (nuevas del formulario editable)
        if (!empty($producto['telas_multiples']) && is_array($producto['telas_multiples'])) {
            foreach ($producto['telas_multiples'] as $telaMultiple) {
                $telaDescripcion = '';
                
                if (!empty($telaMultiple['tela'])) {
                    $telaDescripcion .= strtoupper($telaMultiple['tela']);
                }
                
                if (!empty($telaMultiple['referencia'])) {
                    $telaDescripcion .= ' REF:' . strtoupper($telaMultiple['referencia']);
                }
                
                if (!empty($telaMultiple['color'])) {
                    $telaDescripcion .= ' - ' . strtoupper($telaMultiple['color']);
                }
                
                if (!empty($telaDescripcion)) {
                    $lineas[] = "Tela/Color: " . $telaDescripcion;
                }
            }
        } else {
            // Fallback a tela individual antigua
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
        }
        
        // 5. Género
        if (!empty($producto['genero'])) {
            if (is_array($producto['genero'])) {
                $genero = implode(', ', array_map('strtoupper', $producto['genero']));
            } else {
                $genero = strtoupper($producto['genero']);
            }
            $lineas[] = "Genero: " . $genero;
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
                'tipoCotizacion',
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
                // Reflectivo con sus fotos
                'reflectivo.fotos',
            ])->findOrFail($cotizacionId);

            // Verificar que pertenezca al asesor actual
            if ($cotizacion->asesor_id !== Auth::id()) {
                return response()->json([
                    'error' => 'No tienes permiso para acceder a esta cotización'
                ], 403);
            }

            // Convertir especificaciones del formato antiguo al nuevo (si es necesario)
            $especificacionesConvertidas = $this->convertirEspecificacionesAlFormatoNuevo(
                $cotizacion->especificaciones ?? []
            );

            // Extraer forma de pago de las especificaciones
            $formaPago = '';
            if (!empty($especificacionesConvertidas['forma_pago']) && is_array($especificacionesConvertidas['forma_pago'])) {
                if (count($especificacionesConvertidas['forma_pago']) > 0) {
                    $formaPago = $especificacionesConvertidas['forma_pago'][0]['valor'] ?? '';
                }
            }

            return response()->json([
                'id' => $cotizacion->id,
                'numero' => $cotizacion->numero_cotizacion,
                'tipo_cotizacion_id' => $cotizacion->tipo_cotizacion_id,
                'tipo_cotizacion_codigo' => $cotizacion->tipoCotizacion ? $cotizacion->tipoCotizacion->codigo : null,
                'cliente' => $cotizacion->cliente ? $cotizacion->cliente->nombre : '',
                'asesora' => $cotizacion->asesor ? $cotizacion->asesor->name : Auth::user()->name,
                'forma_pago' => $formaPago,
                'tipo_venta' => $cotizacion->tipo_venta ?? '',
                'especificaciones' => $especificacionesConvertidas,
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
                    'fotos' => $cotizacion->reflectivo->fotos ? $cotizacion->reflectivo->fotos->map(function($foto) {
                        return [
                            'id' => $foto->id,
                            'url' => '/storage/' . ltrim($foto->ruta_webp ?? $foto->url, '/'),
                            'ruta_original' => '/storage/' . ltrim($foto->ruta_original, '/'),
                            'ruta_webp' => '/storage/' . ltrim($foto->ruta_webp, '/'),
                        ];
                    })->toArray() : [],
                ] : null,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Convierte especificaciones del formato antiguo (tabla_orden[field]) al nuevo (forma_pago, disponibilidad, etc)
     */
    private function convertirEspecificacionesAlFormatoNuevo($especificaciones)
    {
        if (!$especificaciones) {
            return [];
        }

        // Si ya es un array con estructura forma_pago, no convertir
        if (is_array($especificaciones) && isset($especificaciones['forma_pago'])) {
            return $especificaciones;
        }

        // Si es string, parsear
        if (is_string($especificaciones)) {
            $datos = json_decode($especificaciones, true) ?? [];
        } else {
            $datos = $especificaciones;
        }

        // Si ya está en formato nuevo, devolver
        if (isset($datos['forma_pago'])) {
            return $datos;
        }

        // Convertir del formato antiguo tabla_orden[field]
        $convertidas = [
            'forma_pago' => [],
            'disponibilidad' => [],
            'regimen' => [],
            'se_ha_vendido' => [],
            'ultima_venta' => [],
            'flete' => []
        ];

        // Mapeos de nombres para conversión
        $mapeoFormaPago = [
            'tabla_orden[contado]' => 'Contado',
            'tabla_orden[credito]' => 'Crédito',
        ];

        $mapeoDisponibilidad = [
            'tabla_orden[bodega]' => 'Bodega',
            'tabla_orden[cucuta]' => 'Cúcuta',
            'tabla_orden[lafayette]' => 'Lafayette',
            'tabla_orden[fabrica]' => 'Fábrica',
        ];

        $mapeoRegimen = [
            'tabla_orden[comun]' => 'Común',
            'tabla_orden[simplificado]' => 'Simplificado',
        ];

        // Procesar FORMA_PAGO
        foreach ($mapeoFormaPago as $clave => $etiqueta) {
            if (isset($datos[$clave]) && ($datos[$clave] === '1' || $datos[$clave] === true)) {
                $obsKey = str_replace(']', '_obs]', str_replace('[', '[pago_', $clave));
                $convertidas['forma_pago'][] = [
                    'valor' => $etiqueta,
                    'observacion' => $datos[$obsKey] ?? ''
                ];
            }
        }

        // Procesar DISPONIBILIDAD
        foreach ($mapeoDisponibilidad as $clave => $etiqueta) {
            if (isset($datos[$clave]) && ($datos[$clave] === '1' || $datos[$clave] === true)) {
                $obsKey = str_replace(']', '_obs]', $clave);
                $convertidas['disponibilidad'][] = [
                    'valor' => $etiqueta,
                    'observacion' => $datos[$obsKey] ?? ''
                ];
            }
        }

        // Procesar RÉGIMEN
        foreach ($mapeoRegimen as $clave => $etiqueta) {
            if (isset($datos[$clave]) && ($datos[$clave] === '1' || $datos[$clave] === true)) {
                $obsKey = str_replace(']', '_obs]', str_replace('[', '[regimen_', $clave));
                $convertidas['regimen'][] = [
                    'valor' => $etiqueta,
                    'observacion' => $datos[$obsKey] ?? ''
                ];
            }
        }

        // Remover campos vacíos
        foreach ($convertidas as $key => $value) {
            if (empty($value)) {
                unset($convertidas[$key]);
            }
        }

        return $convertidas;

    }

    /**
     * Crear procesos automáticamente para cotizaciones REFLECTIVO
     * 
     * Crea:
     * 1. Proceso "Creación Orden" (Completado)
     * 2. Proceso "Costura" asignado a Ramiro (En Ejecución)
     */
    private function crearProcesosParaReflectivo(PedidoProduccion $pedido, Cotizacion $cotizacion): void
    {
        try {
            // Verificar si es cotización tipo REFLECTIVO
            if (!$cotizacion->tipoCotizacion) {
                \Log::info('⏭️ No hay tipo de cotización asociado');
                return;
            }

            $tipoCotizacion = strtolower(trim($cotizacion->tipoCotizacion->nombre ?? ''));
            
            \Log::info('🔍 Verificando tipo de cotización', [
                'tipo_encontrado' => $tipoCotizacion,
                'es_reflectivo' => ($tipoCotizacion === 'reflectivo' ? 'SI' : 'NO'),
            ]);

            if ($tipoCotizacion !== 'reflectivo') {
                \Log::info('⏭️ Cotización no es de tipo REFLECTIVO', [
                    'tipo_actual' => $tipoCotizacion,
                ]);
                return;
            }

            \Log::info('🎯 CREAR PROCESOS PARA COTIZACIÓN REFLECTIVO', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cotizacion_id' => $cotizacion->id,
            ]);

            // Obtener prendas del pedido
            $prendas = PrendaPedido::where('numero_pedido', $pedido->numero_pedido)->get();

            \Log::info('📋 Prendas encontradas', [
                'numero_pedido' => $pedido->numero_pedido,
                'cantidad' => $prendas->count(),
            ]);

            if ($prendas->isEmpty()) {
                \Log::warn('⚠️ No hay prendas en el pedido', [
                    'numero_pedido' => $pedido->numero_pedido,
                ]);
                return;
            }

            // Obtener nombre de la asesora logueada
            $asesoraLogueada = Auth::user()->name ?? 'Sin Asesora';

            foreach ($prendas as $prenda) {
                \Log::info('🔍 Procesando prenda', [
                    'prenda_pedido_id' => $prenda->id,
                    'nombre_prenda' => $prenda->nombre_prenda,
                ]);

                // Verificar si ya existen procesos para esta prenda
                $procesosExistentes = ProcesoPrenda::where('prenda_pedido_id', $prenda->id)
                    ->pluck('proceso')
                    ->toArray();

                \Log::info('🔍 Procesos existentes para prenda', [
                    'prenda_pedido_id' => $prenda->id,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'procesos' => $procesosExistentes,
                ]);

                // Crear proceso de Creación de Orden asignado a la asesora logueada
                if (!in_array('Creación de Orden', $procesosExistentes)) {
                    $procsCreacion = ProcesoPrenda::create([
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_pedido_id' => $prenda->id,
                        'proceso' => 'Creación de Orden',
                        'encargado' => $asesoraLogueada,
                        'estado_proceso' => 'En Progreso',
                        'fecha_inicio' => now(),
                        'observaciones' => 'Proceso de creación asignado automáticamente a la asesora para cotización reflectivo',
                    ]);

                    \Log::info('✅ Proceso Creación de Orden creado para prenda', [
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_pedido_id' => $prenda->id,
                        'nombre_prenda' => $prenda->nombre_prenda,
                        'encargado' => $asesoraLogueada,
                        'proceso_id' => $procsCreacion->id,
                    ]);
                }

                // NO crear duplicados si ya existe Costura
                if (in_array('Costura', $procesosExistentes)) {
                    \Log::info('✅ Proceso Costura ya existe, omitiendo');
                    continue;
                }

                // Crear proceso Costura con Ramiro
                $procsCostura = ProcesoPrenda::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_pedido_id' => $prenda->id,
                    'proceso' => 'Costura',
                    'encargado' => 'Ramiro',
                    'estado_proceso' => 'En Progreso',
                    'fecha_inicio' => now(),
                    'observaciones' => 'Asignado automáticamente a Ramiro para cotización reflectivo',
                ]);

                \Log::info('✅ Proceso Costura creado para prenda', [
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_pedido_id' => $prenda->id,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'encargado' => 'Ramiro',
                    'proceso_id' => $procsCostura->id,
                ]);
            }

            \Log::info('✅ Procesos de cotización reflectivo completados', [
                'numero_pedido' => $pedido->numero_pedido,
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Error al crear procesos para cotización reflectivo', [
                'error' => $e->getMessage(),
                'numero_pedido' => $pedido->numero_pedido,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

}

