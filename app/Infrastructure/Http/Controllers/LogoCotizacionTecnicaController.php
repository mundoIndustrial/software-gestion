<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LogoCotizacion;
use App\Models\TipoLogoCotizacion;
use App\Models\LogoCotizacionTecnicaPrenda;
use App\Models\LogoCotizacionTecnicaPrendaFoto;
use App\Models\PrendaCot;
use App\Application\Services\Cotizacion\ValidarRutaImagenCotizacion;
use App\Services\TecnicaImagenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * LogoCotizacionTecnicaController
 * 
 * Controlador para gestionar tenicas y prendas en cotizaciones de logo
 * Estructura: LogoCotizacion -> TipoLogoCotizacion -> LogoCotizacionTecnicaPrenda
 */
class LogoCotizacionTecnicaController extends Controller
{
    /**
     * Obtener tipos de tenicas disponibles (para select en UI)
     */
    public function tiposDisponibles()
    {
        try {
            Log::info(' tiposDisponibles() - Iniciando');
            
            $tipos = TipoLogoCotizacion::activos()->get();
            
            Log::info(' Tipos obtenidos', ['count' => $tipos->count()]);

            $tiposFormateados = $tipos->map(fn($tipo) => [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'codigo' => $tipo->codigo,
                'color' => $tipo->color,
                'icono' => $tipo->icono ?? null,
            ])->values();

            return response()->json([
                'success' => true,
                'data' => $tiposFormateados
            ]);

        } catch (\Exception $e) {
            Log::error(' Error al obtener tipos', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de tenicas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar una tecnica (TipoLogoCotizacion) con prendas a una cotizacion
     * 
     * Recibe FormData con:
     * - prendas: JSON con datos de prendas (sin File objects)
     * - imagenes_prenda_X_Y: Archivos de imagen para prenda X, imagen Y
     * - grupo_combinado: ID del grupo (si es combinada)
     * - es_combinada: boolean
     */
    public function agregarTecnica(Request $request)
    {
        try {
            $prendasInput = $request->input('prendas');
            $request->merge([
                'logo_cotizacion_id' => $request->input('logo_cotizacion_id', $request->input('logoCotizacionId')),
                'tipo_logo_id' => $request->input('tipo_logo_id', $request->input('tipoTecnicaId')),
                'prendas' => is_array($prendasInput) ? json_encode($prendasInput) : $prendasInput,
            ]);

            $prendasPreview = $request->input('prendas');
            if (is_array($prendasPreview)) {
                $prendasPreview = json_encode($prendasPreview);
            }

            Log::info(' agregarTecnica() - Request FormData recibido', [
                'logo_cotizacion_id' => $request->input('logo_cotizacion_id'),
                'tipo_logo_id' => $request->input('tipo_logo_id'),
                'es_combinada' => $request->input('es_combinada'),
                'es_combinada_type' => gettype($request->input('es_combinada')),
                'grupo_combinado' => $request->input('grupo_combinado'),
                'prendas_raw' => substr((string) ($prendasPreview ?? ''), 0, 100),
                'archivos_subidos' => collect($request->files->all())->map(fn($v) => is_array($v) ? count($v) : 1)->sum(),
                'all_inputs_keys' => array_keys($request->all())
            ]);

            // Validar datos basicos
            Log::info(' Iniciando validacion', [
                'campos_esperados' => ['logo_cotizacion_id', 'tipo_logo_id', 'prendas', 'es_combinada', 'grupo_combinado']
            ]);
            
            $validated = $request->validate([
                'logo_cotizacion_id' => 'required|integer',
                'tipo_logo_id' => 'required|integer|exists:tipo_logo_cotizaciones,id',
                'prendas' => 'required|json',
                'grupo_combinado' => 'nullable|integer',
                'es_combinada' => 'nullable|in:true,false,1,0,null',  // Aceptar string o boolean
            ]);
            
            Log::info(' validacion exitosa', [
                'logo_cotizacion_id' => $validated['logo_cotizacion_id'],
                'tipo_logo_id' => $validated['tipo_logo_id'],
                'es_combinada' => $validated['es_combinada'] ?? 'null'
            ]);

            // Decodificar JSON de prendas
            Log::info(' Decodificando JSON de prendas');
            $prendasData = json_decode($validated['prendas'], true);
            if (!is_array($prendasData)) {
                Log::error(' Error decodificando prendas', [
                    'error' => json_last_error_msg(),
                    'raw_prendas' => substr($validated['prendas'], 0, 200)
                ]);
                throw new \Exception('Datos de prendas invalidados: ' . json_last_error_msg());
            }
            
            if (count($prendasData) === 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'prendas' => ['Debe enviar al menos una prenda.'],
                ]);
            }

            Log::info(' Prendas decodificadas correctamente', [
                'count' => count($prendasData),
                'prendas' => array_map(fn($p) => $p['nombre_prenda'] ?? 'sin_nombre', $prendasData)
            ]);

            $logoCotizacionId = $validated['logo_cotizacion_id'];
            $tipoLogoId = $validated['tipo_logo_id'];
            $grupoCombinado = $validated['grupo_combinado'] ?? null;
            
            // Convertir es_combinada a boolean
            Log::info(' Convirtiendo es_combinada a boolean', ['raw_value' => $request->input('es_combinada'), 'type' => gettype($request->input('es_combinada'))]);
            $esCombinada = filter_var($request->input('es_combinada'), FILTER_VALIDATE_BOOLEAN);
            Log::info(' Conversion completada', ['boolean_value' => $esCombinada, 'type' => gettype($esCombinada)]);

            // Obtener datos de la cotizacion y tipo de logo
            $logoCotizacion = LogoCotizacion::findOrFail($logoCotizacionId);
            $cotizacionId = $logoCotizacion->cotizacion_id;
            $tipoLogo = TipoLogoCotizacion::findOrFail($tipoLogoId);

            // Auto-generar grupo_combinado si es tecnica combinada
            if (!$grupoCombinado && $esCombinada === true) {
                $maxGrupo = LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacionId)
                    ->where('grupo_combinado', '!=', null)
                    ->max('grupo_combinado');
                
                $grupoCombinado = ($maxGrupo ? $maxGrupo + 1 : 1);
                
                Log::info('ðŸ†” Grupo combinado auto-generado', [
                    'nuevo_grupo' => $grupoCombinado
                ]);
            }

            // Inicializar servicio de imagenes
            $imagenService = new TecnicaImagenService();

            // Crear prendas con imagenes
            $prendas = [];
            
            // PASO 1: Obtener rutas de logos compartidos ya guardados
            $logosCompartidosGuardados = [];
            if ($request->has('logos_compartidos_guardados')) {
                $logosCompartidosGuardados = json_decode($request->input('logos_compartidos_guardados', '{}'), true) ?? [];
                Log::info(' Rutas de logos compartidos recibidas:', [
                    'count' => count($logosCompartidosGuardados),
                    'claves' => array_keys($logosCompartidosGuardados)
                ]);
            }
            
            // PASO 1B: Obtener metadatos de logos compartidos para saber que tenicas comparten cada logo
            $imagenesCompartidas = [];
            foreach ($request->all() as $key => $value) {
                // Buscar metadatos en formato: logo_compartido_metadata_0, logo_compartido_metadata_1, etc.
                if (preg_match("/^logo_compartido_metadata_(\d+)$/", $key) && is_string($value)) {
                    $metadatos = json_decode($value, true);
                    if ($metadatos) {
                        $imagenesCompartidas[$metadatos['nombreCompartido']] = $metadatos;
                    }
                }
            }

            Log::info(' Metadatos de imagenes compartidas encontrados:', [
                'count' => count($imagenesCompartidas),
                'claves' => array_keys($imagenesCompartidas)
            ]);

            // PASO 1C: Persistir archivos logo compartido enviados en esta petición
            // (el front envía imagenes_logo_compartido_{N} + logo_compartido_metadata_{N}; antes solo se
            // esperaba logos_compartidos_guardados en JSON y los archivos nunca se guardaban).
            foreach ($request->all() as $metaKey => $metaRaw) {
                if (!preg_match('/^logo_compartido_metadata_(\d+)$/', $metaKey, $m) || !is_string($metaRaw)) {
                    continue;
                }
                $idx = $m[1];
                $metaLogo = json_decode($metaRaw, true);
                if (!$metaLogo || empty($metaLogo['nombreCompartido'])) {
                    continue;
                }
                $clave = $metaLogo['nombreCompartido'];
                $archivoCompartido = $request->file('imagenes_logo_compartido_' . $idx);
                if (!$archivoCompartido || !$archivoCompartido->isValid()) {
                    Log::warning(' Logo compartido: metadata sin archivo válido', [
                        'idx' => $idx,
                        'clave' => $clave,
                    ]);
                    continue;
                }
                try {
                    $rutasComp = $imagenService->guardarImagen(
                        $archivoCompartido,
                        $cotizacionId,
                        $tipoLogo->nombre,
                        $grupoCombinado
                    );
                    if (!empty($rutasComp['ruta_webp'])) {
                        $logosCompartidosGuardados[$clave] = $rutasComp['ruta_webp'];
                        Log::info(' Logo compartido guardado desde upload', [
                            'clave' => $clave,
                            'ruta_webp' => $rutasComp['ruta_webp'],
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error(' Error guardando archivo de logo compartido', [
                        'clave' => $clave,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // PASO 2: Procesar prendas e imagenes no compartidas
            foreach ($prendasData as $prendasIndex => $prendaData) {
                Log::info(' Creando prenda de catalogo', [
                    'nombre_producto' => $prendaData['nombre_prenda'],
                    'ubicaciones' => $prendaData['ubicaciones'] ?? [],
                    'talla_cantidad' => $prendaData['talla_cantidad'] ?? [],
                    'variaciones_prenda' => $prendaData['variaciones_prenda'] ?? 'NULL',
                    'prenda_index' => $prendasIndex,
                    'grupo_combinado' => $grupoCombinado
                ]);

                // CLAVE: Si hay grupo_combinado, buscar si ya existe una PrendaCot con el mismo nombre
                $prendaCot = null;
                
                if ($grupoCombinado) {
                    // Buscar una prenda con el mismo nombre y grupo_combinado en esta cotizacion
                    $prendaCot = PrendaCot::whereHas('logoCotizacionesTecnicas', function($query) use ($grupoCombinado) {
                        $query->where('grupo_combinado', $grupoCombinado);
                    })
                    ->where('cotizacion_id', $logoCotizacion->cotizacion_id)
                    ->where('nombre_producto', $prendaData['nombre_prenda'])
                    ->first();

                    if ($prendaCot) {
                        Log::info(' Reutilizando PrendaCot existente del grupo_combinado', [
                            'prenda_cot_id' => $prendaCot->id,
                            'nombre_prenda' => $prendaData['nombre_prenda'],
                            'grupo_combinado' => $grupoCombinado
                        ]);
                    }
                }
                
                // Si no existe o no es grupo_combinado, crear nuevo registro en prendas_cot
                if (!$prendaCot) {
                    $prendaCot = PrendaCot::create([
                        'cotizacion_id' => $logoCotizacion->cotizacion_id,
                        'nombre_producto' => $prendaData['nombre_prenda'],
                        'descripcion' => $prendaData['observaciones'] ?? '',
                        'cantidad' => $prendaData['cantidad'] ?? 1,
                        'texto_personalizado_tallas' => $prendaData['texto_personalizado_tallas'] ?? null,
                        'prenda_bodega' => true,
                    ]);

                    Log::info(' Prenda guardada en prendas_cot', [
                        'prenda_cot_id' => $prendaCot->id,
                        'cotizacion_id' => $logoCotizacion->cotizacion_id,
                        'es_primera_del_grupo' => $grupoCombinado ? true : false
                    ]);
                }

                // Crear prenda en logo_cotizacion_tecnica_prendas con referencia a prendas_cot
                $prenda = LogoCotizacionTecnicaPrenda::create([
                    'logo_cotizacion_id' => $logoCotizacionId,
                    'tipo_logo_id' => $tipoLogoId,
                    'prenda_cot_id' => $prendaCot->id,
                    'observaciones' => $prendaData['observaciones'] ?? '',
                    'ubicaciones' => $prendaData['ubicaciones'] ?? [],
                    'talla_cantidad' => $prendaData['talla_cantidad'] ?? [],
                    'variaciones_prenda' => $prendaData['variaciones_prenda'] ?? null,
                    'grupo_combinado' => $grupoCombinado,
                ]);

                Log::info(' Prenda creada en logo_cotizacion_tecnica_prendas', [
                    'prenda_id' => $prenda->id,
                    'prenda_cot_id' => $prenda->prenda_cot_id,
                    'variaciones_guardadas' => $prenda->variaciones_prenda ?? 'NULL',
                    'grupo_combinado' => $grupoCombinado
                ]);

                // Procesar imagenes para esta prenda
                // Las imagenes vienen con clave: imagenes_prenda_X_Y (del procesador de formularios)
                foreach ($request->files->all() as $fieldName => $archivo) {
                    // Procesar solo archivos de este indice de prenda
                    if (preg_match("/^imagenes_prenda_(\d+)_(\d+)$/", $fieldName, $matches)) {
                        $currentPrendaIdx = (int)$matches[1];
                        $imagenIndex = (int)$matches[2];
                        
                        // Solo procesar si es de esta prenda
                        if ($currentPrendaIdx !== $prendasIndex) {
                            continue;
                        }

                        try {
                            Log::info(' Procesando imagen de prenda', [
                                'prenda_id' => $prenda->id,
                                'imagen_index' => $imagenIndex,
                                'fieldName' => $fieldName
                            ]);

                            // Guardar imagen normal (no compartida)
                            $rutasImagen = $imagenService->guardarImagen(
                                $archivo,
                                $cotizacionId,
                                $tipoLogo->nombre,
                                $grupoCombinado
                            );

                            // Validar que se guarde correctamente y que tiene las rutas necesarias
                            if (!$rutasImagen || !isset($rutasImagen['ruta_webp'])) {
                                Log::error(' Rutas de imagen invalidas', [
                                    'prenda_id' => $prenda->id,
                                    'rutasImagen' => json_encode($rutasImagen)
                                ]);
                                throw new \Exception('Error al procesar las rutas de la imagen');
                            }

                            // Usar ruta_webp para ambas (ruta_original y ruta_webp)
                            // Esto asegura que al menos ruta_webp siempre tenga valor
                            $rutaFinal = $rutasImagen['ruta_webp'];

                            if (!is_string($rutaFinal) || !ValidarRutaImagenCotizacion::puedePersistirRuta($rutaFinal, 'LogoCotizacionTecnicaController.imagen_prenda_tecnica', [
                                'prenda_tecnica_id' => $prenda->id,
                                'cotizacion_id' => $cotizacionId,
                            ])) {
                                throw new \Exception('Ruta de imagen no válida para persistir');
                            }

                            // Guardar metadata en BD
                            $foto = LogoCotizacionTecnicaPrendaFoto::create([
                                'logo_cotizacion_tecnica_prenda_id' => $prenda->id,
                                'ruta_original' => $rutaFinal,  // Usar la misma ruta
                                'ruta_webp' => $rutaFinal,      // WebP optimizado
                                'ruta_miniatura' => $rutaFinal, // Misma para miniatura
                                'orden' => $imagenIndex,

                            ]);

                            Log::info(' Imagen de prenda guardada en BD', [
                                'foto_id' => $foto->id,
                                'ruta_webp' => $rutasImagen['ruta_webp']
                            ]);

                        } catch (\Exception $e) {
                            Log::error(' Error procesando imagen de prenda', [
                                'error' => $e->getMessage(),
                                'fieldName' => $fieldName,
                                'prenda_id' => $prenda->id
                            ]);
                        }
                    }
                }
                
                // Agregar imagenes compartidas a esta prenda (SOLO si esta tecnica comparte la imagen)
                foreach ($imagenesCompartidas as $clave => $metadatos) {
                    $tecnicasCompartidas = $metadatos['tecnicasCompartidas'] ?? [];
                    
                    // Verificar si esta tecnica actual esta en la lista de tenicas que comparten esta imagen
                    if (!in_array($tipoLogo->nombre, $tecnicasCompartidas)) {
                        continue; // Saltar si esta tecnica no comparte esta imagen
                    }
                    
                    // Obtener la ruta ya guardada
                    $rutaCompartida = $logosCompartidosGuardados[$clave] ?? null;
                    if (!$rutaCompartida) {
                        Log::warning(' Logo compartido no tiene ruta guardada', [
                            'clave' => $clave,
                            'tecnica' => $tipoLogo->nombre
                        ]);
                        continue; // Si no se encontro la ruta, saltar
                    }

                    if (!is_string($rutaCompartida) || !ValidarRutaImagenCotizacion::puedePersistirRuta($rutaCompartida, 'LogoCotizacionTecnicaController.logo_compartido', [
                        'clave' => $clave,
                        'prenda_id' => $prenda->id,
                    ])) {
                        continue;
                    }

                    $rutaNormalizada = $rutaCompartida;
                    if (is_string($rutaNormalizada) && str_starts_with($rutaNormalizada, '/storage/')) {
                        $rutaNormalizada = substr($rutaNormalizada, strlen('/storage/'));
                    }
                    if (is_string($rutaNormalizada)) {
                        $rutaNormalizada = ltrim($rutaNormalizada, '/');
                    }

                    if (!is_string($rutaNormalizada) || !ValidarRutaImagenCotizacion::puedePersistirRuta($rutaNormalizada, 'LogoCotizacionTecnicaController.logo_compartido.ruta_normalizada', [
                        'clave' => $clave,
                        'prenda_id' => $prenda->id,
                    ])) {
                        continue;
                    }
                    
                    try {
                        // Obtener dimensiones de la imagen compartida para mejor metadata
                        $imagenPath = storage_path('app/public/' . $rutaNormalizada);
                        $dimensiones = @getimagesize($imagenPath);
                        $ancho = $dimensiones[0] ?? 0;
                        $alto = $dimensiones[1] ?? 0;
                        $tamano = @filesize($imagenPath) ?? 0;
                        
                        // Validar que rutaCompartida no sea vacia
                        if (empty($rutaCompartida)) {
                            Log::warning(' Ruta compartida vacia, saltando', [
                                'clave' => $clave,
                                'prenda_id' => $prenda->id
                            ]);
                            continue;
                        }

                        $rutasAComparar = array_values(array_unique(array_filter([
                            $rutaNormalizada,
                            is_string($rutaNormalizada) ? ('/storage/' . ltrim($rutaNormalizada, '/')) : null,
                        ], fn($v) => is_string($v) && $v !== '')));

                        $yaExiste = LogoCotizacionTecnicaPrendaFoto::where('logo_cotizacion_tecnica_prenda_id', (int) $prenda->id)
                            ->whereIn('ruta_webp', $rutasAComparar)
                            ->exists();
                        if ($yaExiste) {
                            continue;
                        }

                        // Crear entrada en BD apuntando a la ruta compartida
                        $foto = LogoCotizacionTecnicaPrendaFoto::create([
                            'logo_cotizacion_tecnica_prenda_id' => $prenda->id,
                            'ruta_original' => $rutaNormalizada,
                            'ruta_webp' => $rutaNormalizada,
                            'ruta_miniatura' => $rutaNormalizada,
                            'orden' => 999, // Orden muy alto para que aparezca al final
                        ]);

                        Log::info(' Imagen compartida vinculada a prenda (SOLO REFERENCIA, NO DUPLICADA)', [
                            'foto_id' => $foto->id,
                            'prenda_id' => $prenda->id,
                            'ruta_compartida' => $rutaCompartida,
                            'tecnica_actual' => $tipoLogo->nombre,
                            'tecnicas_que_comparten' => implode(' + ', $tecnicasCompartidas),
                            'dimensiones' => "{$ancho}x{$alto}",
                            'tamano_bytes' => $tamano
                        ]);
                    } catch (\Exception $e) {
                        Log::error(' Error vinculando imagen compartida a prenda', [
                            'error' => $e->getMessage(),
                            'prenda_id' => $prenda->id,
                            'clave' => $clave
                        ]);
                    }
                }

                $prendas[] = $prenda;
            }

            Log::info('tecnica agregada completamente', [
                'logo_cotizacion_id' => $logoCotizacionId,
                'tipo_logo_id' => $tipoLogoId,
                'grupo_combinado' => $grupoCombinado,
                'prendas_count' => count($prendas),
                'ruta_almacenamiento' => $grupoCombinado 
                    ? "cotizaciones/{$logoCotizacionId}/combinada/{$grupoCombinado}/{$tipoLogo->nombre}"
                    : "cotizaciones/{$logoCotizacionId}/simple/{$tipoLogo->nombre}"
            ]);

            return response()->json([
                'success' => true,
                'message' => 'tecnica agregada exitosamente',
                'data' => [
                    'id' => $prendas[0]->id ?? null,
                    'logoCotizacionId' => $logoCotizacionId,
                    'tipoTecnicaId' => $tipoLogoId,
                    'prendas_count' => count($prendas),
                    'grupo_combinado' => $grupoCombinado,
                    'es_simple' => $grupoCombinado === null,
                    'prendas' => array_map(fn($p) => [
                        'id' => $p->id,
                        'nombre_prenda' => $p->prendaCot?->nombre_producto,
                        'fotos_count' => $p->fotos->count(),
                    ], $prendas)
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Errores de validación', ['errors' => $e->errors()]);
            $errors = $e->errors();
            if (isset($errors['logo_cotizacion_id'])) {
                $errors['logoCotizacionId'] = $errors['logo_cotizacion_id'];
            }
            if (isset($errors['tipo_logo_id'])) {
                $errors['tipoTecnicaId'] = $errors['tipo_logo_id'];
            }

            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $errors
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Modelo no encontrado', [
                'error' => $e->getMessage(),
                'model' => class_basename($e->getModel())
            ]);
            return response()->json([
                'success' => false,
                'message' => 'LogoCotizacion o TipoLogoCotizacion no encontrado',
                'error' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            Log::error(' Error al agregar tecnica', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar tecnica',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tenicas y prendas de una cotizacion
     */
    public function obtenerTecnicas($logoCotizacionId)
    {
        try {
            // Verificar que existe la cotizacion
            LogoCotizacion::findOrFail($logoCotizacionId);

            // Obtener todas las prendas agrupadas por tipo de logo
            $prendas = LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacionId)
                ->with('tipoLogo', 'prendaCot')
                ->get()
                ->groupBy('tipo_logo_id')
                ->map(function($prendasPorTipo) use ($logoCotizacionId) {
                    $tipoLogo = $prendasPorTipo->first()->tipoLogo;
                    
                    return [
                        'id' => $prendasPorTipo->first()->id,
                        'logoCotizacionId' => (int) $logoCotizacionId,
                        'tipoTecnicaId' => $tipoLogo->id,
                        'prendas' => $prendasPorTipo->map(fn($prenda) => [
                            'id' => $prenda->id,
                            'nombre_prenda' => $prenda->prendaCot?->nombre_producto,
                            'descripcion' => $prenda->prendaCot?->descripcion,
                            'ubicaciones' => $prenda->ubicaciones,
                            'talla_cantidad' => $prenda->talla_cantidad,
                            'cantidad_general' => $prenda->prendaCot?->cantidad,
                        ])->values()->toArray(),
                    ];
                })->values();

            return response()->json([
                'success' => true,
                'data' => $prendas
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'cotizacion no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error(' Error al obtener tenicas', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tenicas'
            ], 500);
        }
    }

    /**
     * Eliminar una prenda
     */
    public function eliminarTecnica($prendeId)
    {
        try {
            $prenda = LogoCotizacionTecnicaPrenda::findOrFail($prendeId);
            
            $tipoLogoId = $prenda->tipo_logo_id;
            $logoCotizacionId = $prenda->logo_cotizacion_id;
            $prendaCotId = $prenda->prenda_cot_id;

            // Eliminar la prenda tecnica de logo
            $prenda->delete();

            // Eliminar también de prendas_cot si existe
            if ($prendaCotId) {
                PrendaCot::destroy($prendaCotId);
                Log::info(' Prenda eliminada de prendas_cot', ['prenda_cot_id' => $prendaCotId]);
            }

            // Si no hay más prendas de este tipo para esta cotizacion
            $prendasRestantes = LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacionId)
                ->where('tipo_logo_id', $tipoLogoId)
                ->count();

            Log::info(' Prenda eliminada', [
                'prenda_id' => $prendeId,
                'prendas_restantes_tipo' => $prendasRestantes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Prenda eliminada exitosamente',
                'prendas_restantes' => $prendasRestantes
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prenda no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error(' Error al eliminar prenda', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar prenda'
            ], 500);
        }
    }

    /**
     * Actualizar datos de una prenda
     */
    public function actualizarObservaciones(Request $request, $prendeId)
    {
        try {
            $request->merge([
                'descripcion' => $request->input('descripcion', $request->input('observaciones')),
            ]);

            $validated = $request->validate([
                'descripcion' => 'nullable|string',
                'ubicaciones' => 'nullable|array',
                'talla_cantidad' => 'nullable|array',
                'cantidad_general' => 'nullable|integer|min:1',
            ]);

            $prenda = LogoCotizacionTecnicaPrenda::findOrFail($prendeId);
            $prenda->update($validated);

            // Actualizar tambien en prendas_cot si existe
            if ($prenda->prenda_cot_id) {
                $prendaCot = PrendaCot::find($prenda->prenda_cot_id);
                if ($prendaCot && isset($validated['cantidad_general'])) {
                    $prendaCot->update(['cantidad' => $validated['cantidad_general']]);
                }
                if ($prendaCot && array_key_exists('descripcion', $validated)) {
                    $prendaCot->update(['descripcion' => $validated['descripcion']]);
                }
            }

            Log::info(' Prenda actualizada', ['prenda_id' => $prendeId]);

            return response()->json([
                'success' => true,
                'message' => 'Prenda actualizada exitosamente',
                'data' => [
                    'id' => $prenda->id,
                    'nombre_prenda' => $prenda->prendaCot?->nombre_producto,
                    'descripcion' => $prenda->prendaCot?->descripcion,
                    'ubicaciones' => $prenda->ubicaciones,
                    'talla_cantidad' => $prenda->talla_cantidad,
                    'cantidad_general' => $prenda->prendaCot?->cantidad,
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prenda no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error(' Error al actualizar prenda', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar prenda'
            ], 500);
        }
    }

    /**
     * Obtener todas las prendas guardadas (para autocomplete)
     */
    public function obtenerPrendas()
    {
        try {
            $prendas = DB::table('prendas_cotizaciones_tipos')
                ->select('nombre')
                ->distinct()
                ->orderBy('nombre')
                ->get()
                ->pluck('nombre')
                ->values();

            return response()->json([
                'success' => true,
                'data' => $prendas
            ]);
        } catch (\Exception $e) {
            Log::error(' Error al obtener prendas', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener prendas',
                'data' => []
            ], 500);
        }
    }

    /**
     * Guardar una nueva prenda
     */
    public function guardarPrenda(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255'
            ]);

            $nombre = strtoupper($validated['nombre']);

            // Verificar si ya existe
            $existe = DB::table('prendas_cotizaciones_tipos')
                ->where('nombre', $nombre)
                ->exists();

            if (!$existe) {
                DB::table('prendas_cotizaciones_tipos')->insert([
                    'nombre' => $nombre,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info(' Prenda guardada', ['nombre' => $nombre]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Prenda guardada exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error(' Error al guardar prenda', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar prenda'
            ], 500);
        }
    }
}



