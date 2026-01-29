<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LogoCotizacion;
use App\Models\TipoLogoCotizacion;
use App\Models\LogoCotizacionTecnicaPrenda;
use App\Models\LogoCotizacionTecnicaPrendaFoto;
use App\Models\PrendaCot;
use App\Services\TecnicaImagenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * LogoCotizacionTecnicaController
 * 
 * Controlador para gestionar técnicas y prendas en cotizaciones de logo
 * Estructura: LogoCotizacion -> TipoLogoCotizacion -> LogoCotizacionTecnicaPrenda
 */
class LogoCotizacionTecnicaController extends Controller
{
    /**
     * Obtener tipos de técnicas disponibles (para select en UI)
     */
    public function tiposDisponibles()
    {
        try {
            Log::info('🔵 tiposDisponibles() - Iniciando');
            
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
                'message' => 'Error al obtener tipos de técnicas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar una técnica (TipoLogoCotizacion) con prendas a una cotización
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
            Log::info('🔵 agregarTecnica() - Request FormData recibido', [
                'logo_cotizacion_id' => $request->input('logo_cotizacion_id'),
                'tipo_logo_id' => $request->input('tipo_logo_id'),
                'es_combinada' => $request->input('es_combinada'),
                'es_combinada_type' => gettype($request->input('es_combinada')),
                'grupo_combinado' => $request->input('grupo_combinado'),
                'prendas_raw' => substr($request->input('prendas') ?? '', 0, 100),
                'archivos_subidos' => collect($request->files->all())->map(fn($v) => is_array($v) ? count($v) : 1)->sum(),
                'all_inputs_keys' => array_keys($request->all())
            ]);

            // Validar datos básicos
            Log::info('✓ Iniciando validación', [
                'campos_esperados' => ['logo_cotizacion_id', 'tipo_logo_id', 'prendas', 'es_combinada', 'grupo_combinado']
            ]);
            
            $validated = $request->validate([
                'logo_cotizacion_id' => 'required|integer|exists:logo_cotizaciones,id',
                'tipo_logo_id' => 'required|integer|exists:tipo_logo_cotizaciones,id',
                'prendas' => 'required|json',
                'grupo_combinado' => 'nullable|integer',
                'es_combinada' => 'nullable|in:true,false,1,0,null',  // Aceptar string o boolean
            ]);
            
            Log::info(' Validación exitosa', [
                'logo_cotizacion_id' => $validated['logo_cotizacion_id'],
                'tipo_logo_id' => $validated['tipo_logo_id'],
                'es_combinada' => $validated['es_combinada'] ?? 'null'
            ]);

            // Decodificar JSON de prendas
            Log::info('✓ Decodificando JSON de prendas');
            $prendasData = json_decode($validated['prendas'], true);
            if (!is_array($prendasData)) {
                Log::error(' Error decodificando prendas', [
                    'error' => json_last_error_msg(),
                    'raw_prendas' => substr($validated['prendas'], 0, 200)
                ]);
                throw new \Exception('Datos de prendas inválidos: ' . json_last_error_msg());
            }
            
            Log::info(' Prendas decodificadas correctamente', [
                'count' => count($prendasData),
                'prendas' => array_map(fn($p) => $p['nombre_prenda'] ?? 'sin_nombre', $prendasData)
            ]);

            $logoCotizacionId = $validated['logo_cotizacion_id'];
            $tipoLogoId = $validated['tipo_logo_id'];
            $grupoCombinado = $validated['grupo_combinado'] ?? null;
            
            // Convertir es_combinada a boolean
            Log::info('✓ Convirtiendo es_combinada a boolean', ['raw_value' => $request->input('es_combinada'), 'type' => gettype($request->input('es_combinada'))]);
            $esCombinada = filter_var($request->input('es_combinada'), FILTER_VALIDATE_BOOLEAN);
            Log::info('✓ Conversion completada', ['boolean_value' => $esCombinada, 'type' => gettype($esCombinada)]);

            // Obtener datos de la cotización y tipo de logo
            $logoCotizacion = LogoCotizacion::findOrFail($logoCotizacionId);
            $tipoLogo = TipoLogoCotizacion::findOrFail($tipoLogoId);

            // Auto-generar grupo_combinado si es técnica combinada
            if (!$grupoCombinado && $esCombinada === true) {
                $maxGrupo = LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacionId)
                    ->where('grupo_combinado', '!=', null)
                    ->max('grupo_combinado');
                
                $grupoCombinado = ($maxGrupo ? $maxGrupo + 1 : 1);
                
                Log::info('🆔 Grupo combinado auto-generado', [
                    'nuevo_grupo' => $grupoCombinado
                ]);
            }

            // Inicializar servicio de imágenes
            $imagenService = new TecnicaImagenService();

            // Crear prendas con imágenes
            $prendas = [];
            
            foreach ($prendasData as $prendasIndex => $prendaData) {
                Log::info(' Creando prenda de catálogo', [
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
                    // Buscar una prenda con el mismo nombre y grupo_combinado en esta cotización
                    $prendaCot = PrendaCot::whereHas('logoCotizacionesTecnicas', function($query) use ($grupoCombinado) {
                        $query->where('grupo_combinado', $grupoCombinado);
                    })
                    ->where('cotizacion_id', $logoCotizacion->cotizacion_id)
                    ->where('nombre_producto', $prendaData['nombre_prenda'])
                    ->first();

                    if ($prendaCot) {
                        Log::info('✓ Reutilizando PrendaCot existente del grupo_combinado', [
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

                // Procesar imágenes para esta prenda
                // Las imágenes vienen con clave: imagenes_prenda_{prendasIndex}_{imagenIndex}
                foreach ($request->files->all() as $fieldName => $archivo) {
                    if (preg_match("/^imagenes_prenda_{$prendasIndex}_(\d+)$/", $fieldName, $matches)) {
                        $imagenIndex = (int)$matches[1];

                        try {
                            Log::info(' Procesando imagen', [
                                'prenda_id' => $prenda->id,
                                'imagen_index' => $imagenIndex,
                                'fieldName' => $fieldName
                            ]);

                            // Guardar imagen en disco
                            $rutasImagen = $imagenService->guardarImagen(
                                $archivo,
                                $logoCotizacionId,
                                $tipoLogo->nombre,
                                $grupoCombinado
                            );

                            // Guardar metadata en BD
                            $foto = LogoCotizacionTecnicaPrendaFoto::create([
                                'logo_cotizacion_tecnica_prenda_id' => $prenda->id,
                                'ruta_original' => $rutasImagen['ruta_original'],
                                'ruta_webp' => $rutasImagen['ruta_webp'],
                                'ruta_miniatura' => $rutasImagen['ruta_miniatura'],
                                'orden' => $imagenIndex,
                                'ancho' => $rutasImagen['ancho'],
                                'alto' => $rutasImagen['alto'],
                                'tamaño' => $rutasImagen['tamaño'],
                            ]);

                            Log::info(' Imagen guardada en BD', [
                                'foto_id' => $foto->id,
                                'ruta_webp' => $rutasImagen['ruta_webp']
                            ]);

                        } catch (\Exception $e) {
                            Log::error(' Error procesando imagen', [
                                'error' => $e->getMessage(),
                                'fieldName' => $fieldName,
                                'prenda_id' => $prenda->id
                            ]);
                            // Continuar con siguiente imagen
                        }
                    }
                }

                $prendas[] = $prenda;
            }

            Log::info(' Técnica agregada completamente', [
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
                'message' => 'Técnica agregada exitosamente',
                'data' => [
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
            Log::warning(' Errores de validación', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error(' Modelo no encontrado', [
                'error' => $e->getMessage(),
                'model' => class_basename($e->getModel())
            ]);
            return response()->json([
                'success' => false,
                'message' => 'LogoCotizacion o TipoLogoCotizacion no encontrado',
                'error' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            Log::error(' Error al agregar técnica', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar técnica',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener técnicas y prendas de una cotización
     */
    public function obtenerTecnicas($logoCotizacionId)
    {
        try {
            // Verificar que existe la cotización
            LogoCotizacion::findOrFail($logoCotizacionId);

            // Obtener todas las prendas agrupadas por tipo de logo
            $prendas = LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacionId)
                ->with('tipoLogo', 'prendaCot')
                ->get()
                ->groupBy('tipo_logo_id')
                ->map(function($prendasPorTipo) {
                    $tipoLogo = $prendasPorTipo->first()->tipoLogo;
                    
                    return [
                        'tipo_logo' => [
                            'id' => $tipoLogo->id,
                            'nombre' => $tipoLogo->nombre,
                            'codigo' => $tipoLogo->codigo,
                            'color' => $tipoLogo->color,
                        ],
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
                'message' => 'Cotización no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error(' Error al obtener técnicas', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener técnicas'
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

            // Eliminar la prenda técnica de logo
            $prenda->delete();

            // Eliminar también de prendas_cot si existe
            if ($prendaCotId) {
                PrendaCot::destroy($prendaCotId);
                Log::info(' Prenda eliminada de prendas_cot', ['prenda_cot_id' => $prendaCotId]);
            }

            // Si no hay más prendas de este tipo para esta cotización
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
            $validated = $request->validate([
                'descripcion' => 'nullable|string',
                'ubicaciones' => 'nullable|array',
                'talla_cantidad' => 'nullable|array',
                'cantidad_general' => 'nullable|integer|min:1',
            ]);

            $prenda = LogoCotizacionTecnicaPrenda::findOrFail($prendeId);
            $prenda->update($validated);

            // Actualizar también en prendas_cot si existe
            if ($prenda->prenda_cot_id) {
                $prendaCot = PrendaCot::find($prenda->prenda_cot_id);
                if ($prendaCot && $validated['cantidad_general']) {
                    $prendaCot->update(['cantidad' => $validated['cantidad_general']]);
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


