<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\NumeroSecuencia;
use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CotizacionPrendaController extends Controller
{
    public function __construct(
        private readonly GenerarNumeroCotizacionService $generarNumeroCotizacionService,
        private readonly \App\Application\Services\CotizacionPrendaService $cotizacionPrendaService
    ) {
    }

    /**
     * Mostrar formulario de crear cotización de prenda
     */
    public function create()
    {
        return view('cotizaciones.prenda.create');
    }

    /**
     * Guardar cotización de prenda
     * SINCRÓNICO: Genera número INMEDIATAMENTE con pessimistic lock
     */
    public function store(Request $request)
    {
        // Usar transacción para garantizar atomicidad
        // Si algo falla, TODO se revierte (ROLLBACK)
        return DB::transaction(function () use ($request) {
            try {
                Log::info('🔵 CotizacionPrendaController@store - Iniciando guardado de cotización de Prenda');

                // Determinar si es borrador o enviada
                $action = $request->input('action') ?? $request->input('accion');
                $esBorrador = $action === 'borrador';
                $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA';

                // Obtener o crear cliente
                $clienteId = $request->input('cliente_id');
                $nombreCliente = $request->input('cliente');

                if ($nombreCliente && !$clienteId) {
                    $cliente = \App\Models\Cliente::firstOrCreate(
                        ['nombre' => $nombreCliente],
                        ['nombre' => $nombreCliente]
                    );
                    $clienteId = $cliente->id;
                }

                // Generar número SINCRONICAMENTE si se envía
                $numeroCotizacion = null;
                if (!$esBorrador) {
                    $usuarioId = Auth::id();
                    $numeroCotizacion = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId);
                    Log::info(' Número generado sincronicamente', [
                        'numero' => $numeroCotizacion
                    ]);
                }

                // Crear cotización CON número generado
                $cotizacion = Cotizacion::create([
                    'asesor_id' => Auth::id(),
                    'cliente_id' => $clienteId,
                    'numero_cotizacion' => $numeroCotizacion,
                    'tipo_cotizacion_id' => 1, // Cotizaci\u00f3n Combinada (Prenda + Logo)
                    'tipo_venta' => $request->input('tipo_venta', 'M'),
                    'es_borrador' => $esBorrador,
                    'estado' => $estado,
                    'productos' => json_encode($request->input('prendas', [])),
                    'tecnicas' => json_encode($request->input('tecnicas', [])),
                    'ubicaciones' => json_encode($request->input('ubicaciones', [])),
                    'observaciones_tecnicas' => $request->input('observaciones_tecnicas', ''),
                    'observaciones_generales' => json_encode($request->input('observaciones_generales', [])),
                    'especificaciones' => json_encode($request->input('especificaciones', [])),
                    'imagenes' => json_encode($request->input('imagenes', [])),
                ]);

                Log::info(' Cotización de Prenda creada', [
                    'cotizacion_id' => $cotizacion->id,
                    'numero_cotizacion' => $numeroCotizacion,
                    'es_borrador' => $esBorrador,
                    'estado' => $estado,
                    'cliente_id' => $clienteId,
                ]);

                // Guardar productos en tablas normalizadas (prendas_cot, variantes, tallas, etc.)
                $productos = $request->input('prendas', []);
                if (!empty($productos)) {
                    $this->cotizacionPrendaService->guardarProductosEnCotizacion($cotizacion, $productos);
                    Log::info(' Productos guardados en tablas normalizadas', [
                        'cotizacion_id' => $cotizacion->id,
                        'productos_count' => count($productos)
                    ]);
                }

                // OPTIMIZACIÓN: Si se envía, aún encolamos el job pero ahora el número YA EXISTE
                // El job puede usarlo directamente sin generar otro
                if (!$esBorrador) {
                    \App\Jobs\ProcesarEnvioCotizacionJob::dispatch(
                        $cotizacion->id,
                        1 // tipo_cotizacion_id para Combinada (Prenda + Logo)
                    )->onQueue('cotizaciones');

                    Log::info(' Job de envío encolado (número ya existe)', [
                        'cotizacion_id' => $cotizacion->id,
                        'numero' => $numeroCotizacion,
                        'queue' => 'cotizaciones'
                    ]);
                }

                // Procesar imágenes si existen
                // Verificar si hay archivos en el request (prendas, telas, logo, etc.)
                $allFiles = $request->allFiles();
                if (!empty($allFiles)) {
                    $this->procesarImagenesCotizacion($request, $cotizacion->id);
                }

                // Recargar la cotización con todas sus relaciones
                $cotizacionCompleta = Cotizacion::with([
                    'cliente',
                    'prendas.fotos',
                    'prendas.telaFotos',
                    'prendas.tallas',
                    'prendas.variantes.manga',
                    'prendas.variantes.broche',
                    'logoCotizacion.fotos'
                ])->findOrFail($cotizacion->id);

                // Determinar redirección según estado
                $redirectUrl = $esBorrador 
                    ? route('asesores.cotizaciones.index') . '?tab=borradores'
                    : route('asesores.cotizaciones.index');

                return response()->json([
                    'success' => true,
                    'message' => $esBorrador ? 'Cotización guardada como borrador' : 'Cotización enviada - Número: ' . $numeroCotizacion,
                    'data' => $cotizacionCompleta->toArray(),
                    'redirect' => $redirectUrl
                ], 201);

            } catch (\Exception $e) {
                Log::error(' Error al guardar cotización de Prenda', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                // La transacción se revierte automáticamente
                // Nada se guarda en la BD
                throw $e;
            }
        }, attempts: 3); // Reintentar hasta 3 veces si hay deadlock
    }

    /**
     * Generar número de cotización sincronicamente con pessimistic lock
     * 
     * Usa lockForUpdate() para prevenir race conditions
     * Formato: COT-20250124-001
     * 
     * @param string $tipo tipo de secuencia (cotizaciones_prenda, cotizaciones_bordado, etc)
     * @return string número generado
     */
    
    /**
     * Listar cotizaciones de prenda
     */
    public function lista()
    {
        return redirect()->route('asesores.cotizaciones.index');
    }

    /**
     * Editar cotización de prenda
     */
    public function edit($id)
    {
        $cotizacion = Cotizacion::with([
            'cliente',
            'prendas.fotos',
            'prendas.telaFotos',
            'prendas.tallas',
            'prendas.variantes.genero',
            'prendas.variantes.manga',
            'prendas.variantes.broche',
            'logoCotizacion.fotos'
        ])->findOrFail($id);
        
        // Verificar que el usuario es propietario
        if ($cotizacion->asesor_id !== Auth::id()) {
            abort(403, 'No tienes permiso para editar esta cotización');
        }
        
        Log::info('CotizacionPrendaController@edit: Cotización cargada para editar', [
            'cotizacion_id' => $cotizacion->id,
            'prendas_count' => $cotizacion->prendas ? count($cotizacion->prendas) : 0,
            'es_borrador' => $cotizacion->es_borrador,
        ]);
        
        return view('cotizaciones.prenda.create', ['cotizacion' => $cotizacion]);
    }

    /**
     * Actualizar cotización de prenda
     */
    public function update(Request $request, $id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            
            $cotizacion->update([
                'productos' => json_encode($request->input('prendas', [])),
                'tecnicas' => json_encode($request->input('tecnicas', [])),
                'ubicaciones' => json_encode($request->input('ubicaciones', [])),
                'observaciones_tecnicas' => $request->input('observaciones_tecnicas', ''),
                'observaciones_generales' => json_encode($request->input('observaciones_generales', [])),
                'especificaciones' => json_encode($request->input('especificaciones', [])),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Cotización actualizada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al actualizar cotización de Prenda', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cotización'
            ], 500);
        }
    }

    /**
     * Enviar cotización de prenda
     */
    public function enviar(Request $request, $id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            $cotizacion->update([
                'es_borrador' => false,
                'estado' => 'ENVIADA'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Cotización enviada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la cotización'
            ], 500);
        }
    }

    /**
     * Eliminar cotización de prenda
     */
    public function destroy($id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            $cotizacion->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Cotización eliminada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cotización'
            ], 500);
        }
    }
    
    /**
     * Procesar imágenes de la cotización
     */
    private function procesarImagenesCotizacion(Request $request, $cotizacionId)
    {
        Log::info(' Iniciando procesamiento de imágenes para cotización', ['cotizacion_id' => $cotizacionId]);
        
        // Obtener cotización
        $cotizacion = Cotizacion::findOrFail($cotizacionId);

        // Debug: Ver todos los archivos recibidos
        $allFiles = $request->allFiles();
        \Log::info('📁 DEBUG allFiles():', [
            'keys' => array_keys($allFiles),
            'count' => count($allFiles),
        ]);

        // Laravel agrupa los archivos bajo 'prendas', necesitamos acceder a la estructura anidada
        $prendasData = $request->file('prendas', []);
        
        \Log::info('📁 Estructura de prendas recibida:', [
            'tiene_prendas' => !empty($prendasData),
            'tipo' => gettype($prendasData),
            'es_array' => is_array($prendasData),
            'count' => is_array($prendasData) ? count($prendasData) : 0,
            'prendasData_keys' => is_array($prendasData) ? array_keys($prendasData) : 'no es array',
        ]);

        if (!is_array($prendasData) || empty($prendasData)) {
            Log::info(' No hay archivos de prendas para procesar');
            
            // Intentar acceder de otra forma
            \Log::info(' Intentando acceder a archivos de otra forma...');
            if (isset($allFiles['prendas'])) {
                \Log::info(' Encontrado en allFiles[prendas]', [
                    'tipo' => gettype($allFiles['prendas']),
                    'es_array' => is_array($allFiles['prendas']),
                ]);
                $prendasData = $allFiles['prendas'];
            } else {
                \Log::info(' No encontrado en allFiles[prendas]');
                return;
            }
        }

        // Procesar cada prenda
        foreach ($prendasData as $prendaIndex => $prendaFiles) {
            \Log::info(' Procesando archivos de prenda', [
                'prenda_index' => $prendaIndex,
                'keys' => is_array($prendaFiles) ? array_keys($prendaFiles) : 'no es array',
            ]);

            // 1. Procesar fotos de PRENDA: prendas[{index}][fotos][]
            if (isset($prendaFiles['fotos']) && is_array($prendaFiles['fotos'])) {
                \Log::info('📸 Encontrado grupo de fotos de PRENDA', [
                    'prenda_index' => $prendaIndex,
                    'cantidad_archivos' => count($prendaFiles['fotos']),
                ]);

                foreach ($prendaFiles['fotos'] as $fotoIndex => $archivoFoto) {
                    if ($archivoFoto && $archivoFoto->isValid()) {
                        try {
                            // Guardar en storage
                            $nombreOriginal = pathinfo($archivoFoto->getClientOriginalName(), PATHINFO_FILENAME);
                            $extension = $archivoFoto->getClientOriginalExtension();
                            $nombreArchivo = $nombreOriginal . '_prenda_' . time() . '_' . substr(md5(uniqid()), 0, 4) . '.' . $extension;
                            $rutaGuardada = $archivoFoto->storeAs('cotizaciones/' . $cotizacionId . '/prendas', $nombreArchivo, 'public');
                            $rutaUrl = '/storage/' . $rutaGuardada;

                            \Log::info(' Foto de prenda guardada en storage', [
                                'prenda_index' => $prendaIndex,
                                'ruta_guardada' => $rutaGuardada,
                                'nombre_archivo' => $archivoFoto->getClientOriginalName(),
                            ]);

                            // Guardar en tabla prenda_fotos_cot
                            $prendas = $cotizacion->prendas;
                            if ($prendas && isset($prendas[$prendaIndex])) {
                                $prenda = $prendas[$prendaIndex];
                                
                                DB::table('prenda_fotos_cot')->insert([
                                    'prenda_cot_id' => $prenda->id,
                                    'ruta_original' => $rutaUrl,
                                    'ruta_webp' => $rutaUrl,
                                    'ruta_miniatura' => null,
                                    'orden' => $fotoIndex + 1,
                                    'ancho' => null,
                                    'alto' => null,
                                    'tamaño' => $archivoFoto->getSize(),
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);

                                \Log::info(' Foto de prenda guardada en BD', [
                                    'prenda_id' => $prenda->id,
                                ]);
                            }
                        } catch (\Exception $e) {
                            \Log::error(' Error guardando foto de prenda', [
                                'error' => $e->getMessage(),
                                'archivo' => $archivoFoto->getClientOriginalName(),
                            ]);
                        }
                    }
                }
            }
            
            // 2. Procesar fotos de TELAS: prendas[{index}][telas][{telaIndex}][fotos][]
            if (isset($prendaFiles['telas']) && is_array($prendaFiles['telas'])) {
                // Obtener la prenda para acceder a sus variantes
                $prendas = $cotizacion->prendas;
                if (!$prendas || !isset($prendas[$prendaIndex])) {
                    \Log::warning(' Prenda no encontrada para procesar telas', ['prenda_index' => $prendaIndex]);
                    continue;
                }
                
                $prenda = $prendas[$prendaIndex];
                
                // Obtener telas_multiples del JSON de variantes
                $variante = $prenda->variantes->first();
                $telasMultiples = [];
                if ($variante && $variante->telas_multiples) {
                    $telasMultiples = is_array($variante->telas_multiples) 
                        ? $variante->telas_multiples 
                        : json_decode($variante->telas_multiples, true);
                }
                
                \Log::info(' Telas multiples de variante:', [
                    'prenda_id' => $prenda->id,
                    'telas_count' => count($telasMultiples),
                    'telas' => $telasMultiples,
                ]);
                
                // Mapeo de indice de tela => prenda_tela_cot_id
                $telaCotIds = [];
                
                // PROCESAR TODAS LAS TELAS DE telas_multiples (CON O SIN FOTOS)
                foreach ($telasMultiples as $telaInfo) {
                    // Buscar o crear color
                    $colorId = null;
                    if (!empty($telaInfo['color'])) {
                        \Log::info(' Buscando color', ['color_nombre' => $telaInfo['color']]);
                        $color = DB::table('colores_prenda')
                            ->where('nombre', $telaInfo['color'])
                            ->first();
                        
                        if (!$color) {
                            $colorId = DB::table('colores_prenda')->insertGetId([
                                'nombre' => $telaInfo['color'],
                                'activo' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            \Log::info(' Color creado', ['color' => $telaInfo['color'], 'id' => $colorId]);
                        } else {
                            $colorId = $color->id;
                            \Log::info(' Color encontrado', ['color' => $telaInfo['color'], 'id' => $colorId]);
                        }
                    } else {
                        \Log::warning(' Color vacío en telaInfo', ['telaInfo' => $telaInfo]);
                    }
                    
                    // Buscar o crear tela
                    $telaId = null;
                    if (!empty($telaInfo['tela'])) {
                        \Log::info(' Buscando tela', ['tela_nombre' => $telaInfo['tela']]);
                        $tela = DB::table('telas_prenda')
                            ->where('nombre', trim($telaInfo['tela']))
                            ->first();
                        
                        if (!$tela) {
                            $telaId = DB::table('telas_prenda')->insertGetId([
                                'nombre' => trim($telaInfo['tela']),
                                'referencia' => $telaInfo['referencia'] ?? null,
                                'activo' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            \Log::info(' Tela creada', ['tela' => $telaInfo['tela'], 'id' => $telaId]);
                        } else {
                            $telaId = $tela->id;
                            \Log::info(' Tela encontrada', ['tela' => $telaInfo['tela'], 'id' => $telaId]);
                        }
                    } else {
                        \Log::warning(' Tela vacía en telaInfo', ['telaInfo' => $telaInfo]);
                    }

                    // GUARDAR REGISTRO EN prenda_telas_cot
                    \Log::info('💾 Intentando guardar en prenda_telas_cot', [
                        'colorId' => $colorId,
                        'telaId' => $telaId,
                        'variante' => $variante ? $variante->id : null,
                        'prenda_id' => $prenda->id,
                        'condicion_cumplida' => ($colorId && $telaId && $variante) ? 'SI' : 'NO',
                    ]);
                    
                    if ($colorId && $telaId && $variante) {
                        // Verificar si ya existe
                        $existente = DB::table('prenda_telas_cot')
                            ->where('prenda_cot_id', $prenda->id)
                            ->where('variante_prenda_cot_id', $variante->id)
                            ->where('color_id', $colorId)
                            ->where('tela_id', $telaId)
                            ->first();
                        
                        if (!$existente) {
                            $prendaTelaCotId = DB::table('prenda_telas_cot')->insertGetId([
                                'prenda_cot_id' => $prenda->id,
                                'variante_prenda_cot_id' => $variante->id,
                                'color_id' => $colorId,
                                'tela_id' => $telaId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            
                            // Guardar el ID en el mapeo
                            $telaIndex = $telaInfo['indice'] ?? null;
                            if ($telaIndex !== null) {
                                $telaCotIds[$telaIndex] = $prendaTelaCotId;
                            }
                            
                            \Log::info(' Registro guardado en prenda_telas_cot (desde telas_multiples)', [
                                'prenda_telas_cot_id' => $prendaTelaCotId,
                                'prenda_id' => $prenda->id,
                                'variante_id' => $variante->id,
                                'color_id' => $colorId,
                                'tela_id' => $telaId,
                                'color' => $telaInfo['color'] ?? '',
                                'tela' => $telaInfo['tela'] ?? '',
                                'referencia' => $telaInfo['referencia'] ?? '',
                                'indice' => $telaIndex,
                            ]);
                        } else {
                            // Guardar el ID existente en el mapeo
                            $telaIndex = $telaInfo['indice'] ?? null;
                            if ($telaIndex !== null) {
                                $telaCotIds[$telaIndex] = $existente->id;
                            }
                            
                            \Log::info(' Registro ya existe en prenda_telas_cot', [
                                'prenda_id' => $prenda->id,
                                'prenda_tela_cot_id' => $existente->id,
                                'color' => $telaInfo['color'] ?? '',
                                'tela' => $telaInfo['tela'] ?? '',
                                'indice' => $telaIndex,
                            ]);
                        }
                    } else {
                        \Log::error(' NO se puede guardar en prenda_telas_cot - falta algún dato', [
                            'colorId' => $colorId,
                            'telaId' => $telaId,
                            'variante_existe' => $variante ? 'SI' : 'NO',
                            'prenda_id' => $prenda->id,
                            'telaInfo' => $telaInfo,
                        ]);
                    }
                }
                
                \Log::info(' Resumen después de procesar telas_multiples', [
                    'prenda_id' => $prenda->id,
                    'telaCotIds_mapeados' => $telaCotIds,
                    'cantidad_mapeos' => count($telaCotIds),
                ]);
                
                foreach ($prendaFiles['telas'] as $telaIndex => $telaData) {
                    if (isset($telaData['fotos']) && is_array($telaData['fotos'])) {
                        \Log::info(' Encontrado grupo de fotos de tela', [
                            'prenda_index' => $prendaIndex,
                            'tela_index' => $telaIndex,
                            'cantidad_archivos' => count($telaData['fotos']),
                            'telaCotIds_disponibles' => $telaCotIds,
                        ]);

                        // Obtener color y tela del JSON telas_multiples
                        $telaInfo = null;
                        foreach ($telasMultiples as $tm) {
                            if (isset($tm['indice']) && $tm['indice'] == $telaIndex) {
                                $telaInfo = $tm;
                                break;
                            }
                        }
                        
                        if (!$telaInfo) {
                            \Log::warning(' No se encontró info de tela en telas_multiples', [
                                'tela_index' => $telaIndex,
                            ]);
                            continue;
                        }
                        
                        // Buscar o crear color
                        $colorId = null;
                        if (!empty($telaInfo['color'])) {
                            $color = DB::table('colores_prenda')
                                ->where('nombre', $telaInfo['color'])
                                ->first();
                            
                            if (!$color) {
                                $colorId = DB::table('colores_prenda')->insertGetId([
                                    'nombre' => $telaInfo['color'],
                                    'activo' => true,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                \Log::info(' Color creado', ['color' => $telaInfo['color'], 'id' => $colorId]);
                            } else {
                                $colorId = $color->id;
                            }
                        }
                        
                        // Buscar o crear tela
                        $telaId = null;
                        if (!empty($telaInfo['tela'])) {
                            $tela = DB::table('telas_prenda')
                                ->where('nombre', $telaInfo['tela'])
                                ->first();
                            
                            if (!$tela) {
                                $telaId = DB::table('telas_prenda')->insertGetId([
                                    'nombre' => $telaInfo['tela'],
                                    'activo' => true,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                \Log::info(' Tela creada', ['tela' => $telaInfo['tela'], 'id' => $telaId]);
                            } else {
                                $telaId = $tela->id;
                            }
                        }

                        // GUARDAR REGISTRO EN prenda_telas_cot
                        $prendaTelaCotId = null;
                        if ($colorId && $telaId) {
                            $prendaTelaCotId = DB::table('prenda_telas_cot')->insertGetId([
                                'prenda_cot_id' => $prenda->id,
                                'variante_prenda_cot_id' => $variante->id,
                                'color_id' => $colorId,
                                'tela_id' => $telaId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            
                            \Log::info(' Registro guardado en prenda_telas_cot', [
                                'prenda_telas_cot_id' => $prendaTelaCotId,
                                'prenda_id' => $prenda->id,
                                'variante_id' => $variante->id,
                                'color_id' => $colorId,
                                'tela_id' => $telaId,
                            ]);
                        }

                        foreach ($telaData['fotos'] as $fotoIndex => $archivoFoto) {
                            if ($archivoFoto && $archivoFoto->isValid()) {
                                try {
                                    // Guardar en storage
                                    $rutaGuardada = $archivoFoto->store("cotizaciones/{$cotizacionId}/telas", 'public');
                                    $rutaUrl = Storage::url($rutaGuardada);

                                    \Log::info(' Foto de tela guardada en storage', [
                                        'prenda_index' => $prendaIndex,
                                        'tela_index' => $telaIndex,
                                        'ruta_guardada' => $rutaGuardada,
                                        'nombre_archivo' => $archivoFoto->getClientOriginalName(),
                                    ]);

                                    // Obtener prenda_tela_cot_id del mapeo
                                    $prendaTelaCotId = $telaCotIds[$telaIndex] ?? null;
                                    
                                    \Log::info('💾 Guardando foto en prenda_tela_fotos_cot', [
                                        'prenda_id' => $prenda->id,
                                        'tela_index' => $telaIndex,
                                        'prenda_tela_cot_id' => $prendaTelaCotId,
                                        'foto_orden' => $fotoIndex + 1,
                                        'ruta' => $rutaUrl,
                                    ]);
                                    
                                    // Guardar en tabla prenda_tela_fotos_cot con prenda_tela_cot_id
                                    DB::table('prenda_tela_fotos_cot')->insert([
                                        'prenda_cot_id' => $prenda->id,
                                        'prenda_tela_cot_id' => $prendaTelaCotId,
                                        'ruta_original' => $rutaUrl,
                                        'ruta_webp' => null,
                                        'ruta_miniatura' => null,
                                        'orden' => $fotoIndex + 1,
                                        'ancho' => null,
                                        'alto' => null,
                                        'tamaño' => $archivoFoto->getSize(),
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);

                                    \Log::info(' Foto de tela guardada en prenda_tela_fotos_cot', [
                                        'prenda_id' => $prenda->id,
                                        'prenda_tela_cot_id' => $prendaTelaCotId,
                                        'color_id' => $colorId ?? 'N/A',
                                        'tela_id' => $telaId ?? 'N/A',
                                        'referencia' => $telaInfo['referencia'] ?? '',
                                        'ruta' => $rutaUrl,
                                        'orden' => $fotoIndex + 1,
                                    ]);
                                } catch (\Exception $e) {
                                    \Log::error(' Error guardando foto de tela', [
                                        'error' => $e->getMessage(),
                                        'archivo' => $archivoFoto->getClientOriginalName(),
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

        Log::info(' Procesamiento de imágenes completado', ['cotizacion_id' => $cotizacionId]);
    }
}
