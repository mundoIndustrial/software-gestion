<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Cotizacion\Commands\AceptarCotizacionCommand;
use App\Application\Cotizacion\Commands\CambiarEstadoCotizacionCommand;
use App\Application\Cotizacion\Commands\CrearCotizacionCommand;
use App\Application\Cotizacion\Commands\CrearReflectivoCotizacionCommand;
use App\Application\Cotizacion\Commands\EliminarCotizacionCommand;
use App\Application\Cotizacion\Commands\SubirImagenCotizacionCommand;
use App\Application\Cotizacion\DTOs\CrearCotizacionDTO;
use App\Application\Cotizacion\Handlers\Commands\AceptarCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\CambiarEstadoCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\CrearCotizacionHandler;
use App\Application\Cotizacion\Handlers\CrearReflectivoCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\EliminarCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\SubirImagenCotizacionHandler;
use App\Application\Cotizacion\Handlers\Queries\ListarCotizacionesHandler;
use App\Application\Cotizacion\Handlers\Queries\ObtenerCotizacionHandler;
use App\Application\Cotizacion\Queries\ListarCotizacionesQuery;
use App\Application\Cotizacion\Queries\ObtenerCotizacionQuery;
use App\Application\Cotizacion\Services\ObtenerOCrearClienteService;
use App\Application\Services\ProcesarImagenesCotizacionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * CotizacionController - Controller SLIM refactorizado
 *
 * Delegación completa a handlers CQRS
 * Máximo 100 líneas, responsabilidad única: HTTP
 */
final class CotizacionController extends Controller
{
    public function __construct(
        private readonly CrearCotizacionHandler $crearHandler,
        private readonly ObtenerCotizacionHandler $obtenerHandler,
        private readonly ListarCotizacionesHandler $listarHandler,
        private readonly EliminarCotizacionHandler $eliminarHandler,
        private readonly CambiarEstadoCotizacionHandler $cambiarEstadoHandler,
        private readonly AceptarCotizacionHandler $aceptarHandler,
        private readonly SubirImagenCotizacionHandler $subirImagenHandler,
        private readonly ObtenerOCrearClienteService $obtenerOCrearClienteService,
        private readonly ProcesarImagenesCotizacionService $procesarImagenesService,
        private readonly \App\Application\Services\EliminarImagenesCotizacionService $eliminarImagenesService,
    ) {
    }

    /**
     * Listar cotizaciones del usuario
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ListarCotizacionesQuery::crear(
                usuarioId: Auth::id(),
                soloEnviadas: $request->boolean('solo_enviadas'),
                soloBorradores: $request->boolean('solo_borradores'),
                pagina: $request->integer('pagina', 1),
                porPagina: $request->integer('por_pagina', 15),
            );

            $cotizaciones = $this->listarHandler->handle($query);

            return response()->json([
                'success' => true,
                'data' => array_map(fn($c) => $c->toArray(), $cotizaciones),
                'total' => count($cotizaciones),
            ]);
        } catch (\Exception $e) {
            Log::error('CotizacionController@index: Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener cotización específica (JSON API)
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Cargar cotización con prendas desde la BD directamente
            $cotizacion = \App\Models\Cotizacion::with([
                'prendas.fotos',
                'prendas.telas',
                'prendas.tallas',
                'prendas.variantes.genero',
                'prendas.variantes.manga',
                'prendas.variantes.broche',
                'cliente'
            ])->findOrFail($id);

            // Verificar propiedad
            if ($cotizacion->asesor_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
            }

            $data = $cotizacion->toArray();

            // Agregar prendas con sus tallas formateadas
            $data['prendas'] = $cotizacion->prendas->map(function ($prenda) {
                // Obtener la variante (debería haber una por prenda)
                $variante = $prenda->variantes->first();
                
                return [
                    'id' => $prenda->id,
                    'nombre_producto' => $prenda->nombre_producto,
                    'descripcion' => $prenda->generarDescripcionDetallada(),
                    'tallas' => $prenda->tallas->pluck('talla')->toArray(),
                    'fotos' => $prenda->fotos->pluck('url')->toArray(),
                    'variantes' => $variante ? [
                        'color' => $variante->color ?? null,
                        'tela' => $prenda->telas->first()?->nombre_tela ?? null,
                        'manga' => $variante->manga?->nombre ?? null,
                        'broche' => $variante->broche?->nombre ?? null,
                        'tiene_bolsillos' => $variante->tiene_bolsillos ?? false,
                    ] : []
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('CotizacionController@show: Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener cotización para editar (JSON API)
     * Devuelve todos los datos incluyendo imágenes
     */
    /**
     * Obtener cotización reflectivo para editar (borrador)
     */
    public function getReflectivoForEdit(int $id): JsonResponse
    {
        try {
            Log::info('🔍 getReflectivoForEdit: INICIANDO', ['cotizacion_id' => $id, 'usuario_id' => Auth::id()]);

            // Obtener cotización reflectivo con todas sus relaciones
            $cotizacion = \App\Models\Cotizacion::with([
                'cliente',
                'prendas',
                'reflectivoCotizacion.fotos',
            ])->findOrFail($id);

            Log::info('✅ Cotización cargada', ['cotizacion_id' => $cotizacion->id, 'asesor_id' => $cotizacion->asesor_id]);

            // Verificar que el usuario es propietario
            if ($cotizacion->asesor_id !== Auth::id()) {
                Log::warning('❌ Usuario no autorizado', ['cotizacion_asesor' => $cotizacion->asesor_id, 'usuario_actual' => Auth::id()]);
                return response()->json(['success' => false, 'message' => 'No tienes permiso'], 403);
            }

            // Verificar que es reflectivo y borrador
            if ($cotizacion->es_borrador === false) {
                Log::warning('❌ No es borrador', ['cotizacion_id' => $id, 'es_borrador' => $cotizacion->es_borrador]);
                return response()->json(['success' => false, 'message' => 'Solo se pueden editar borradores'], 403);
            }

            // Procesar prendas para devolver con estructura apropiada
            $prendasProcesadas = [];
            if ($cotizacion->prendas) {
                foreach ($cotizacion->prendas as $prenda) {
                    $prendasProcesadas[] = [
                        'id' => $prenda->id,
                        'tipo' => $prenda->nombre_producto,
                        'descripcion' => $prenda->descripcion ?? '',
                    ];
                }
            }

            // DEBUG DETALLADO DE REFLECTIVO
            Log::info('🔍 DEBUG REFLECTIVO', [
                'tiene_reflectivo' => $cotizacion->reflectivoCotizacion ? 'SÍ' : 'NO',
                'reflectivo_id' => $cotizacion->reflectivoCotizacion?->id,
                'reflectivo_cotizacion_id' => $cotizacion->reflectivoCotizacion?->cotizacion_id,
            ]);

            if ($cotizacion->reflectivoCotizacion) {
                $fotos = $cotizacion->reflectivoCotizacion->fotos;
                Log::info('🔍 DEBUG FOTOS', [
                    'fotos_count' => $fotos ? count($fotos) : 0,
                    'fotos_relation_existe' => $fotos ? 'SÍ' : 'NO',
                ]);

                if ($fotos && count($fotos) > 0) {
                    foreach ($fotos as $idx => $foto) {
                        Log::info("🔍 DEBUG FOTO {$idx}", [
                            'foto_id' => $foto->id,
                            'ruta_original' => $foto->ruta_original,
                            'ruta_webp' => $foto->ruta_webp,
                            'url_accessor' => $foto->url,
                            'orden' => $foto->orden,
                        ]);
                    }
                }
            }

            // Preparar fotos para respuesta
            $fotosParaRespuesta = $cotizacion->reflectivoCotizacion?->fotos ? $cotizacion->reflectivoCotizacion->fotos->toArray() : [];
            
            Log::info('📸 FOTOS A DEVOLVER', [
                'count' => count($fotosParaRespuesta),
                'fotos_json' => json_encode($fotosParaRespuesta),
            ]);

            Log::info('CotizacionController@getReflectivoForEdit: Cotización RF cargada para editar', [
                'cotizacion_id' => $cotizacion->id,
                'fotos_count' => $cotizacion->reflectivoCotizacion?->fotos ? $cotizacion->reflectivoCotizacion->fotos->count() : 0,
                'es_borrador' => $cotizacion->es_borrador,
                'prendas_count' => count($prendasProcesadas),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'cotizacion' => $cotizacion->toArray(),
                    'prendas' => $prendasProcesadas,
                    'reflectivo' => $cotizacion->reflectivoCotizacion?->toArray(),
                    'fotos' => $fotosParaRespuesta,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CotizacionController@getReflectivoForEdit: Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cargar cotización para edición (general)
     */
    public function getForEdit(int $id): JsonResponse
    {
        try {
            // Obtener cotización con todas sus relaciones
            $cotizacion = \App\Models\Cotizacion::with([
                'cliente',
                'prendas.fotos',
                'prendas.telaFotos',
                'prendas.tallas',
                'prendas.variantes.manga',
                'prendas.variantes.broche',
                'logoCotizacion.fotos'
            ])->findOrFail($id);

            // Verificar que el usuario es propietario
            if ($cotizacion->asesor_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'No tienes permiso'], 403);
            }

            Log::info('CotizacionController@getForEdit: Cotización cargada para editar', [
                'cotizacion_id' => $cotizacion->id,
                'prendas_count' => $cotizacion->prendas ? count($cotizacion->prendas) : 0,
                'es_borrador' => $cotizacion->es_borrador,
            ]);

            return response()->json([
                'success' => true,
                'data' => $cotizacion->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('CotizacionController@getForEdit: Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mostrar cotización en vista HTML
     */
    public function showView(int $id)
    {
        try {
            // Obtener cotización con todas sus relaciones
            // IMPORTANTE: Cargar variantes sin eager load de genero.* para evitar filtros de NULL
            $cotizacion = \App\Models\Cotizacion::with([
                'cliente',
                'prendas.fotos',
                'prendas.telaFotos',
                'prendas.tallas',
                'prendas.variantes',
                'logoCotizacion.fotos',
                'reflectivoCotizacion.fotos',
                'tipoCotizacion'
            ])->findOrFail($id);

            // Verificar que el usuario es propietario
            if ($cotizacion->asesor_id !== Auth::id()) {
                abort(403, 'No tienes permiso para ver esta cotización');
            }

            // Obtener logo si existe
            $logo = $cotizacion->logoCotizacion;

            Log::info('CotizacionController@showView: Cotización cargada', [
                'cotizacion_id' => $cotizacion->id,
                'prendas_count' => $cotizacion->prendas ? count($cotizacion->prendas) : 0,
                'especificaciones' => $cotizacion->especificaciones,
                'logo' => $logo ? 'Sí' : 'No',
                'logo_tecnicas' => $logo ? $logo->tecnicas : null,
                'logo_ubicaciones' => $logo ? $logo->ubicaciones : null,
                'logo_observaciones_generales' => $logo ? $logo->observaciones_generales : null,
            ]);

            return view('asesores.cotizaciones.show', [
                'cotizacion' => $cotizacion,
                'logo' => $logo,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Cotización no encontrada');
        } catch (\Exception $e) {
            Log::error('CotizacionController@showView: Error', ['error' => $e->getMessage()]);
            abort(500, 'Error al obtener la cotización');
        }
    }

    /**
     * Crear cotización
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // 🔍 Verificar si es una actualización de borrador existente
            $cotizacionIdExistente = $request->input('cotizacion_id');
            if ($cotizacionIdExistente) {
                Log::info('CotizacionController@store: Detectada actualización de borrador existente', [
                    'cotizacion_id' => $cotizacionIdExistente,
                ]);
                return $this->update($request, (int)$cotizacionIdExistente);
            }
            
            // 🔍 LOG DE ZONA HORARIA
            Log::info('🕐 ZONA HORARIA AL GUARDAR COTIZACIÓN', [
                'config_timezone' => config('app.timezone'),
                'php_timezone' => date_default_timezone_get(),
                'ahora_carbon' => \Carbon\Carbon::now()->toDateTimeString(),
                'ahora_utc' => \Carbon\Carbon::now('UTC')->toDateTimeString(),
                'ahora_bogota' => \Carbon\Carbon::now('America/Bogota')->toDateTimeString(),
                'timestamp' => time(),
                'fecha_php' => date('Y-m-d H:i:s'),
            ]);
            
            $prendasRecibidas = $request->input('prendas', []);
            $especificacionesRecibidas = $request->input('especificaciones', []);
            
            // Las especificaciones ya vienen con estructura {valor, observacion} desde el frontend
            // No necesitamos procesar observaciones_check y observaciones_valor
            if (!is_array($especificacionesRecibidas)) {
                $especificacionesRecibidas = [];
            }
            
            Log::info('CotizacionController@store: Datos recibidos', [
                'tipo' => $request->input('tipo'),
                'cliente' => $request->input('cliente'),
                'tipo_venta' => $request->input('tipo_venta'),
                'especificaciones' => $especificacionesRecibidas,
                'prendas_count' => count($prendasRecibidas),
                'prendas_keys' => array_keys($prendasRecibidas),
            ]);
            
            Log::info('📋 ESPECIFICACIONES RECIBIDAS DEL FRONTEND', [
                'especificaciones_raw' => $especificacionesRecibidas,
                'especificaciones_type' => gettype($especificacionesRecibidas),
                'especificaciones_keys' => is_array($especificacionesRecibidas) ? array_keys($especificacionesRecibidas) : 'no es array',
                'especificaciones_json' => json_encode($especificacionesRecibidas),
            ]);

            // Obtener o crear cliente si se proporciona nombre
            $clienteId = $request->input('cliente_id');
            $nombreCliente = $request->input('cliente');
            $accion = $request->input('accion'); // 'guardar' o 'enviar'
            $esBorrador = $request->input('es_borrador'); // Recibir directamente del frontend

            if ($nombreCliente && !$clienteId) {
                $cliente = $this->obtenerOCrearClienteService->ejecutar($nombreCliente);
                $clienteId = $cliente->id;
                Log::info('Cliente creado/obtenido', ['cliente_id' => $clienteId, 'nombre' => $nombreCliente]);
            }

            // Si es_borrador viene del frontend, usarlo. Si no, usar la lógica de acción
            if ($esBorrador === null) {
                $esBorrador = ($accion === 'guardar');
            } else {
                $esBorrador = (bool)$esBorrador; // Convertir a booleano
            }
            
            $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR';

            Log::info('CotizacionController@store: Lógica aplicada', [
                'accion' => $accion,
                'es_borrador_recibido' => $request->input('es_borrador'),
                'es_borrador_final' => $esBorrador,
                'estado' => $estado,
                'cliente_id' => $clienteId,
            ]);

            $dto = CrearCotizacionDTO::desdeArray([
                'usuario_id' => Auth::id(),
                'tipo' => $request->input('tipo_cotizacion', 'P'),
                'cliente_id' => $clienteId,
                'prendas' => $request->input('prendas', []),
                'logo' => $request->input('logo', []),
                'tipo_venta' => $request->input('tipo_venta', 'M'),
                'especificaciones' => $request->input('especificaciones', []),
                'es_borrador' => $esBorrador,
                'estado' => $estado,
            ]);

            $comando = CrearCotizacionCommand::crear($dto);
            $cotizacionDTO = $this->crearHandler->handle($comando);

            // Obtener el ID de la cotización desde el DTO
            $cotizacionId = $cotizacionDTO->toArray()['id'] ?? null;

            // Procesar imágenes DESPUÉS de crear la cotización (para tener el ID)
            if ($cotizacionId) {
                $this->procesarImagenesCotizacion($request, $cotizacionId);
            }

            // Recargar la cotización con todas sus relaciones para la respuesta
            $cotizacionCompleta = \App\Models\Cotizacion::with([
                'cliente',
                'prendas.fotos',
                'prendas.telaFotos',
                'prendas.tallas',
                'prendas.variantes',
                'logoCotizacion.fotos'
            ])->findOrFail($cotizacionId);

            return response()->json([
                'success' => true,
                'message' => 'Cotización creada exitosamente',
                'data' => $cotizacionCompleta->toArray(),
            ], 201);
        } catch (\Exception $e) {
            Log::error('CotizacionController@store: Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar cotización existente (edición)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $cotizacion = \App\Models\Cotizacion::findOrFail($id);

            // Verificar que el usuario es propietario
            if ($cotizacion->asesor_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'No tienes permiso'], 403);
            }

            // Obtener o crear cliente si se proporciona nombre
            $clienteId = $request->input('cliente_id');
            $nombreCliente = $request->input('cliente');

            if ($nombreCliente && !$clienteId) {
                $cliente = $this->obtenerOCrearClienteService->ejecutar($nombreCliente);
                $clienteId = $cliente->id;
                Log::info('Cliente creado/obtenido en update', ['cliente_id' => $clienteId, 'nombre' => $nombreCliente]);
            }

            // Actualizar datos básicos
            $cotizacion->update([
                'cliente_id' => $clienteId,
                'tipo_venta' => $request->input('tipo_venta'),
                'especificaciones' => json_encode($request->input('especificaciones', [])),
            ]);

            // Eliminar fotos específicamente marcadas para eliminar
            $fotosAEliminar = $request->input('fotos_a_eliminar', []);
            if (!empty($fotosAEliminar)) {
                Log::info('Eliminando fotos marcadas', ['fotos_count' => count($fotosAEliminar)]);
                
                foreach ($fotosAEliminar as $rutaFoto) {
                    // Eliminar del almacenamiento
                    $rutaRelativa = str_replace('/storage/', '', $rutaFoto);
                    
                    // Eliminar de storage/app/public
                    if (\Storage::disk('public')->exists($rutaRelativa)) {
                        \Storage::disk('public')->delete($rutaRelativa);
                        Log::info('Archivo eliminado de storage/app/public', ['ruta' => $rutaRelativa]);
                    }
                    
                    // Eliminar de public/storage
                    $rutaPublica = public_path("storage/{$rutaRelativa}");
                    if (file_exists($rutaPublica)) {
                        @unlink($rutaPublica);
                        Log::info('Archivo eliminado de public/storage', ['ruta' => $rutaPublica]);
                    }
                    
                    // Eliminar registro de la base de datos
                    \App\Models\PrendaFotoCot::where('ruta_original', $rutaFoto)
                        ->orWhere('ruta_webp', $rutaFoto)
                        ->delete();
                    
                    \App\Models\PrendaTelaFotoCot::where('ruta_original', $rutaFoto)
                        ->orWhere('ruta_webp', $rutaFoto)
                        ->delete();
                    
                    Log::info('Registro de foto eliminado de la base de datos', ['ruta' => $rutaFoto]);
                }
            }

            // Procesar prendas y eliminar imágenes no incluidas SOLO si se envían nuevas imágenes
            $prendasRecibidas = $request->input('prendas', []);
            $allFiles = $request->allFiles();
            
            foreach ($prendasRecibidas as $index => $prendaData) {
                $prendaModel = \App\Models\PrendaCot::where('cotizacion_id', $id)
                    ->skip($index)
                    ->first();

                if ($prendaModel) {
                    // Verificar si se enviaron nuevas fotos de prenda para esta prenda
                    $fotosArchivos = $request->file("prendas.{$index}.fotos") ?? [];
                    if (empty($fotosArchivos)) {
                        $fotosArchivos = $allFiles["prendas.{$index}.fotos"] ?? [];
                    }
                    
                    // Solo eliminar fotos antiguas si se enviaron nuevas fotos
                    if (!empty($fotosArchivos)) {
                        $fotosActuales = $prendaData['fotos'] ?? [];
                        $this->eliminarImagenesService->eliminarImagenesPrendaNoIncluidas(
                            $prendaModel->id,
                            $fotosActuales
                        );
                    }

                    // Verificar si se enviaron nuevas fotos de tela para esta prenda
                    $telasArchivos = $request->file("prendas.{$index}.telas") ?? [];
                    if (empty($telasArchivos)) {
                        $telasArchivos = $allFiles["prendas.{$index}.telas"] ?? [];
                    }
                    
                    // Solo eliminar fotos de tela antiguas si se enviaron nuevas fotos de tela
                    if (!empty($telasArchivos)) {
                        $telasActuales = $prendaData['telas'] ?? [];
                        $this->eliminarImagenesService->eliminarImagenesTelaNoIncluidas(
                            $prendaModel->id,
                            $telasActuales
                        );
                    }
                }
            }

            // Procesar logo ANTES de procesar nuevas imágenes para que la eliminación funcione correctamente
            $logoCotizacion = $cotizacion->logoCotizacion;
            if ($logoCotizacion) {
                // Actualizar datos del logo
                $logoCotizacion->update([
                    'descripcion' => $request->input('descripcion_logo', ''),
                    'tecnicas' => json_encode($request->input('tecnicas', [])),
                    'observaciones_tecnicas' => $request->input('observaciones_tecnicas', ''),
                    'ubicaciones' => json_encode($request->input('ubicaciones', [])),
                    'observaciones_generales' => json_encode($request->input('observaciones_generales', [])),
                ]);
                
                // Obtener las fotos guardadas que se envían desde el frontend
                // Pueden venir como array: logo_fotos_guardadas[]
                $fotosLogoGuardadas = $request->input('logo_fotos_guardadas', []);
                if (!is_array($fotosLogoGuardadas)) {
                    $fotosLogoGuardadas = $fotosLogoGuardadas ? [$fotosLogoGuardadas] : [];
                }
                
                // Limpiar rutas: remover /storage/ del principio si existe
                $fotosLogoGuardadas = array_map(function($ruta) {
                    // Si empieza con /storage/, dejarlo como está (comparar con ruta_webp/ruta_original en BD)
                    // Si empieza con http, extraer la parte después de /storage/
                    if (strpos($ruta, 'http') === 0) {
                        // Es una URL completa como http://localhost/storage/cotizaciones/1/logo/...
                        if (preg_match('#/storage/(.+)$#', $ruta, $matches)) {
                            return '/storage/' . $matches[1];
                        }
                    }
                    return $ruta;
                }, $fotosLogoGuardadas);
                
                Log::info('DEBUG - Fotos de logo a conservar (procesadas):', [
                    'logo_id' => $logoCotizacion->id,
                    'fotos_guardadas_count' => count($fotosLogoGuardadas),
                    'fotos_guardadas' => $fotosLogoGuardadas,
                    'raw_input' => $request->input('logo_fotos_guardadas', [])
                ]);
                
                // Obtener archivos nuevos para saber si se están enviando archivos
                $archivosNuevos = $request->file('logo.imagenes') ?? [];
                $allFiles = $request->allFiles();
                if (empty($archivosNuevos) && isset($allFiles['logo']['imagenes'])) {
                    $archivosNuevos = $allFiles['logo']['imagenes'];
                }
                if ($archivosNuevos instanceof \Illuminate\Http\UploadedFile) {
                    $archivosNuevos = [$archivosNuevos];
                }
                
                Log::info('DEBUG - Archivos nuevos de logo:', [
                    'logo_id' => $logoCotizacion->id,
                    'archivos_nuevos_count' => count($archivosNuevos)
                ]);
                
                // SIEMPRE ejecutar eliminación, pasando las fotos a conservar
                // El servicio decide cuáles eliminar basándose en la lista de fotos a conservar
                $this->eliminarImagenesService->eliminarImagenesLogoNoIncluidas(
                    $logoCotizacion->id,
                    $fotosLogoGuardadas
                );
            }
            
            // Procesar nuevas imágenes DESPUÉS de actualizar logo
            $this->procesarImagenesCotizacion($request, $id);

            // Recargar la cotización con todas sus relaciones
            $cotizacionCompleta = \App\Models\Cotizacion::with([
                'cliente',
                'prendas.fotos',
                'prendas.telaFotos',
                'prendas.tallas',
                'prendas.variantes',
                'logoCotizacion.fotos'
            ])->findOrFail($id);

            Log::info('CotizacionController@update: Cotización actualizada', [
                'cotizacion_id' => $id,
                'asesor_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cotización actualizada exitosamente',
                'data' => $cotizacionCompleta->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('CotizacionController@update: Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Procesar imágenes de la cotización
     */
    private function procesarImagenesCotizacion(Request $request, int $cotizacionId): void
    {
        try {
            $prendas = $request->input('prendas', []);
            $allFiles = $request->allFiles();

            Log::info('Procesando imágenes de cotización', [
                'cotizacion_id' => $cotizacionId,
                'prendas_count' => count($prendas),
                'all_files_keys' => array_keys($allFiles),
            ]);

            foreach ($prendas as $index => $prenda) {
                // Obtener la prenda guardada
                $prendaModel = \App\Models\PrendaCot::where('cotizacion_id', $cotizacionId)
                    ->skip($index)
                    ->first();

                if (!$prendaModel) {
                    Log::warning('Prenda no encontrada', ['cotizacion_id' => $cotizacionId, 'index' => $index]);
                    continue;
                }

                Log::info('Procesando prenda', ['prenda_id' => $prendaModel->id, 'index' => $index]);

                // Procesar imágenes de prenda
                // FormData envía múltiples archivos con [] al final: prendas[0][fotos][]
                $fotosArchivos = [];
                
                // Obtener archivos con la sintaxis correcta de array
                $fotosArchivos = $request->file("prendas.{$index}.fotos") ?? [];
                
                // Si no encuentra, buscar con [] al final
                if (empty($fotosArchivos)) {
                    $fotosArchivos = $request->file("prendas.{$index}.fotos.0") ?? [];
                }
                
                // Si aún no encuentra, buscar en allFiles
                if (empty($fotosArchivos)) {
                    $allFiles = $request->allFiles();
                    $fotosArchivos = $allFiles["prendas.{$index}.fotos"] ?? [];
                }
                
                // Normalizar a array (puede ser un UploadedFile único o un array)
                if ($fotosArchivos instanceof \Illuminate\Http\UploadedFile) {
                    $fotosArchivos = [$fotosArchivos];
                } elseif (!is_array($fotosArchivos)) {
                    $fotosArchivos = [];
                }
                
                Log::info('Fotos encontradas', [
                    'index' => $index,
                    'count' => count($fotosArchivos),
                    'key' => "prendas.{$index}.fotos",
                    'fotos_debug' => $fotosArchivos ? array_map(fn($f) => $f instanceof \Illuminate\Http\UploadedFile ? $f->getClientOriginalName() : 'no-file', $fotosArchivos) : [],
                ]);

                if (!empty($fotosArchivos)) {
                    $orden = 1;
                    foreach ($fotosArchivos as $foto) {
                        if ($foto instanceof \Illuminate\Http\UploadedFile) {
                            $ruta = $this->procesarImagenesService->procesarImagenPrenda(
                                $foto,
                                $cotizacionId,
                                $prendaModel->id
                            );

                            $prendaModel->fotos()->create([
                                'ruta_original' => $ruta,
                                'ruta_webp' => $ruta,
                                'orden' => $orden,
                            ]);
                            $orden++;

                            Log::info('Foto de prenda guardada', ['prenda_id' => $prendaModel->id, 'ruta' => $ruta, 'orden' => $orden - 1]);
                        }
                    }
                }

                // Procesar imágenes de telas
                // FormData envía múltiples archivos con [] al final: prendas[0][telas][]
                $telasArchivos = $request->file("prendas.{$index}.telas") ?? [];
                
                // Si no encuentra, buscar con [] al final
                if (empty($telasArchivos)) {
                    $telasArchivos = $request->file("prendas.{$index}.telas.0") ?? [];
                }
                
                // Si aún no encuentra, buscar en allFiles
                if (empty($telasArchivos)) {
                    $allFiles = $request->allFiles();
                    $telasArchivos = $allFiles["prendas.{$index}.telas"] ?? [];
                }
                
                // Normalizar a array (puede ser un UploadedFile único o un array)
                if ($telasArchivos instanceof \Illuminate\Http\UploadedFile) {
                    $telasArchivos = [$telasArchivos];
                } elseif (!is_array($telasArchivos)) {
                    $telasArchivos = [];
                }
                
                Log::info('Telas encontradas', ['key' => "productos.{$index}.telas", 'count' => count($telasArchivos)]);

                if (!empty($telasArchivos)) {
                    $orden = 1;
                    foreach ($telasArchivos as $foto) {
                        if ($foto instanceof \Illuminate\Http\UploadedFile) {
                            $ruta = $this->procesarImagenesService->procesarImagenTela(
                                $foto,
                                $cotizacionId,
                                $prendaModel->id
                            );

                            $prendaModel->telaFotos()->create([
                                'ruta_original' => $ruta,
                                'ruta_webp' => $ruta,
                                'orden' => $orden,
                            ]);
                            $orden++;

                            Log::info('Foto de tela guardada', ['prenda_id' => $prendaModel->id, 'ruta' => $ruta, 'orden' => $orden - 1]);
                        }
                    }
                }
            }

            // Procesar imágenes de logo
            // FormData envía múltiples archivos con nombre: logo[imagenes][0], logo[imagenes][1], etc.
            $logoArchivos = [];
            
            // Intentar obtener archivos de logo
            $allFiles = $request->allFiles();
            Log::info('DEBUG - allFiles keys:', ['keys' => array_keys($allFiles)]);
            
            // Buscar logo[imagenes] en allFiles
            if (isset($allFiles['logo']) && is_array($allFiles['logo']) && isset($allFiles['logo']['imagenes'])) {
                $logoArchivos = $allFiles['logo']['imagenes'];
                Log::info('DEBUG - Encontrado logo[imagenes] en allFiles');
            } else {
                // Si no, intentar con $request->file()
                $logoArchivos = $request->file('logo.imagenes') ?? [];
                Log::info('DEBUG - Buscado logo.imagenes con request->file()');
            }
            
            Log::info('DEBUG - Buscando archivos de logo:', [
                'logo_imagenes_count' => is_array($logoArchivos) ? count($logoArchivos) : (($logoArchivos instanceof \Illuminate\Http\UploadedFile) ? 1 : 0),
                'logo_imagenes_type' => gettype($logoArchivos)
            ]);
            
            // Normalizar a array PRIMERO (puede ser un UploadedFile único o un array)
            if ($logoArchivos instanceof \Illuminate\Http\UploadedFile) {
                $logoArchivos = [$logoArchivos];
            } elseif (!is_array($logoArchivos)) {
                $logoArchivos = [];
            }
            
            Log::info('Logo encontrado', ['count' => count($logoArchivos)]);
            
            // Obtener datos del PASO 3 (Logo)
            $logoDescripcion = $request->input('descripcion_logo', '');
            $logoTecnicas = $request->input('tecnicas', []);
            if (is_string($logoTecnicas)) {
                $logoTecnicas = json_decode($logoTecnicas, true) ?? [];
            }
            $logoObservacionesTecnicas = $request->input('observaciones_tecnicas', '');
            $logoUbicaciones = $request->input('ubicaciones', []);
            if (is_string($logoUbicaciones)) {
                $logoUbicaciones = json_decode($logoUbicaciones, true) ?? [];
            }
            $logoObservacionesGenerales = $request->input('observaciones_generales', []);
            if (is_string($logoObservacionesGenerales)) {
                $logoObservacionesGenerales = json_decode($logoObservacionesGenerales, true) ?? [];
            }
            
            // Crear o actualizar logo_cotizaciones con TODOS los datos del PASO 3
            // Siempre crear/actualizar aunque no haya datos, porque podría haber imágenes
            $logoCotizacion = \App\Models\LogoCotizacion::updateOrCreate(
                ['cotizacion_id' => $cotizacionId],
                [
                    'descripcion' => $logoDescripcion ?: null,
                    'tecnicas' => is_array($logoTecnicas) ? json_encode($logoTecnicas) : $logoTecnicas,
                    'observaciones_tecnicas' => $logoObservacionesTecnicas ?: null,
                    'ubicaciones' => is_array($logoUbicaciones) ? json_encode($logoUbicaciones) : $logoUbicaciones,
                    'observaciones_generales' => is_array($logoObservacionesGenerales) ? json_encode($logoObservacionesGenerales) : $logoObservacionesGenerales,
                ]
            );
            
            Log::info('Logo datos guardados', [
                'cotizacion_id' => $cotizacionId,
                'logo_id' => $logoCotizacion->id ?? null,
                'descripcion' => $logoDescripcion,
                'tecnicas' => $logoTecnicas,
                'tecnicas_count' => is_array($logoTecnicas) ? count($logoTecnicas) : 0,
                'ubicaciones' => $logoUbicaciones,
                'ubicaciones_count' => is_array($logoUbicaciones) ? count($logoUbicaciones) : 0,
                'observaciones_generales' => $logoObservacionesGenerales,
                'observaciones_generales_count' => is_array($logoObservacionesGenerales) ? count($logoObservacionesGenerales) : 0,
            ]);
            
            Log::info('DEBUG - Antes de guardar fotos de logo:', [
                'logoCotizacion_existe' => !!$logoCotizacion,
                'logoCotizacion_id' => $logoCotizacion->id ?? null,
                'logoArchivos_count' => count($logoArchivos)
            ]);
            
            if (!empty($logoArchivos)) {
                $orden = 1;
                foreach ($logoArchivos as $foto) {
                    if ($foto instanceof \Illuminate\Http\UploadedFile) {
                        $ruta = $this->procesarImagenesService->procesarImagenLogo($foto, $cotizacionId);
                        
                        Log::info('DEBUG - Guardando foto de logo:', [
                            'logoCotizacion_id' => $logoCotizacion->id,
                            'ruta' => $ruta,
                            'orden' => $orden,
                            'modelo_relacion' => get_class($logoCotizacion->fotos()->getRelated())
                        ]);
                        
                        // Guardar en logo_fotos_cot (múltiples fotos con orden incremental)
                        try {
                            $fotoCreada = $logoCotizacion->fotos()->create([
                                'ruta_original' => $ruta,
                                'ruta_webp' => $ruta,
                                'orden' => $orden,
                            ]);
                            
                            Log::info('✅ Logo foto CREADA EN BD', [
                                'cotizacion_id' => $cotizacionId,
                                'foto_id' => $fotoCreada->id ?? 'NULL',
                                'logo_cotizacion_id' => $logoCotizacion->id,
                                'ruta' => $ruta,
                                'orden' => $orden
                            ]);
                        } catch (\Exception $e) {
                            Log::error('❌ ERROR al crear foto de logo', [
                                'cotizacion_id' => $cotizacionId,
                                'logo_cotizacion_id' => $logoCotizacion->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                        $orden++;
                        
                        Log::info('Logo foto guardada', ['cotizacion_id' => $cotizacionId, 'ruta' => $ruta, 'orden' => $orden - 1]);
                    }
                }
            } else {
                Log::info('DEBUG - No hay archivos de logo para guardar');
            }

            // Procesar PASO 4: REFLECTIVO
            $reflectivoDescripcion = $request->input('reflectivo.descripcion', '');
            
            // Obtener ubicación desde 'ubicaciones_reflectivo' (array JSON) o 'reflectivo.ubicacion' (string legacy)
            $ubicacionesData = $request->input('ubicaciones_reflectivo', '[]');
            
            \Log::info('🔍 DEBUG storeReflectivo - Datos recibidos:', [
                'reflectivo_descripcion' => $reflectivoDescripcion,
                'ubicaciones_data_tipo' => gettype($ubicacionesData),
                'ubicaciones_data_raw' => $ubicacionesData,
                'ubicaciones_data_length' => is_string($ubicacionesData) ? strlen($ubicacionesData) : (is_array($ubicacionesData) ? count($ubicacionesData) : 0),
                'all_request_keys' => array_keys($request->all()),
            ]);
            
            if (is_string($ubicacionesData)) {
                $ubicacionesArray = json_decode($ubicacionesData, true) ?? [];
            } else {
                $ubicacionesArray = (array)$ubicacionesData;
            }
            
            \Log::info('🔍 DEBUG storeReflectivo - Ubicaciones después de decode:', [
                'ubicaciones_array' => $ubicacionesArray,
                'ubicaciones_count' => count($ubicacionesArray),
                'array_structure' => json_encode($ubicacionesArray),
            ]);
            
            $reflectivoUbicacion = !empty($ubicacionesArray) ? json_encode($ubicacionesArray) : ($request->input('reflectivo.ubicacion', '') ?? '[]');
            
            \Log::info('🔍 DEBUG storeReflectivo - Ubicación final a guardar:', [
                'reflectivo_ubicacion' => $reflectivoUbicacion,
                'sera_guardado' => !empty($reflectivoUbicacion),
            ]);
            
            $reflectivoObservacionesGenerales = $request->input('reflectivo.observaciones_generales', []);
            if (is_string($reflectivoObservacionesGenerales)) {
                $reflectivoObservacionesGenerales = json_decode($reflectivoObservacionesGenerales, true) ?? [];
            }
            
            // Procesar imágenes de reflectivo
            $reflectivoArchivos = $request->file('reflectivo.imagenes') ?? [];
            if (empty($reflectivoArchivos)) {
                $reflectivoArchivos = $request->file('reflectivo.imagenes.0') ?? [];
            }
            if (empty($reflectivoArchivos)) {
                $allFiles = $request->allFiles();
                $reflectivoArchivos = $allFiles['reflectivo.imagenes'] ?? [];
            }
            if ($reflectivoArchivos instanceof \Illuminate\Http\UploadedFile) {
                $reflectivoArchivos = [$reflectivoArchivos];
            } elseif (!is_array($reflectivoArchivos)) {
                $reflectivoArchivos = [];
            }
            
            // Guardar reflectivo si tiene descripción
            if (!empty($reflectivoDescripcion)) {
                try {
                    // Crear o actualizar reflectivo_cotizaciones
                    $reflectivoCotizacion = \App\Models\ReflectivoCotizacion::updateOrCreate(
                        ['cotizacion_id' => $cotizacionId],
                        [
                            'descripcion' => $reflectivoDescripcion,
                            'ubicacion' => $reflectivoUbicacion,
                            'observaciones_generales' => is_array($reflectivoObservacionesGenerales) ? json_encode($reflectivoObservacionesGenerales) : $reflectivoObservacionesGenerales,
                        ]
                    );
                    
                    Log::info('✨ Reflectivo guardado correctamente', [
                        'cotizacion_id' => $cotizacionId,
                        'reflectivo_id' => $reflectivoCotizacion->id,
                        'descripcion' => $reflectivoDescripcion,
                        'ubicacion' => $reflectivoUbicacion,
                        'imagenes_count' => count($reflectivoArchivos),
                        'observaciones_count' => count($reflectivoObservacionesGenerales),
                    ]);
                    
                    // Guardar imágenes del reflectivo (máximo 3)
                    if (!empty($reflectivoArchivos)) {
                        $orden = 1;
                        $maxImagenes = 3;
                        
                        foreach ($reflectivoArchivos as $foto) {
                            if ($orden > $maxImagenes) {
                                Log::warning('⚠️ Se alcanzó el límite de 3 imágenes para reflectivo', [
                                    'cotizacion_id' => $cotizacionId,
                                    'reflectivo_id' => $reflectivoCotizacion->id,
                                ]);
                                break;
                            }
                            
                            if ($foto instanceof \Illuminate\Http\UploadedFile) {
                                $ruta = $this->procesarImagenesService->procesarImagenLogo($foto, $cotizacionId);
                                
                                // Guardar en reflectivo_fotos_cotizacion (máximo 3 fotos con orden incremental)
                                $reflectivoCotizacion->fotos()->create([
                                    'ruta_original' => $ruta,
                                    'ruta_webp' => $ruta,
                                    'orden' => $orden,
                                ]);
                                $orden++;
                                
                                Log::info('📸 Reflectivo foto guardada', [
                                    'cotizacion_id' => $cotizacionId,
                                    'reflectivo_id' => $reflectivoCotizacion->id,
                                    'ruta' => $ruta,
                                    'orden' => $orden - 1
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Error al guardar reflectivo', [
                        'cotizacion_id' => $cotizacionId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            Log::info('Imágenes procesadas correctamente', ['cotizacion_id' => $cotizacionId]);
        } catch (\Exception $e) {
            Log::error('Error procesando imágenes', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Eliminar cotización
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $comando = EliminarCotizacionCommand::crear($id, Auth::id());
            $this->eliminarHandler->handle($comando);

            return response()->json([
                'success' => true,
                'message' => 'Cotización eliminada exitosamente',
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('CotizacionController@destroy: Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cambiar estado de cotización
     */
    public function cambiarEstado(int $id, string $estado): JsonResponse
    {
        try {
            $comando = CambiarEstadoCotizacionCommand::crear($id, $estado, Auth::id());
            $cotizacion = $this->cambiarEstadoHandler->handle($comando);

            return response()->json([
                'success' => true,
                'message' => 'Estado cambiado exitosamente',
                'data' => $cotizacion->toArray(),
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('CotizacionController@cambiarEstado: Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Aceptar cotización
     */
    public function aceptar(int $id): JsonResponse
    {
        try {
            $comando = AceptarCotizacionCommand::crear($id, Auth::id());
            $cotizacion = $this->aceptarHandler->handle($comando);

            return response()->json([
                'success' => true,
                'message' => 'Cotización aceptada exitosamente',
                'data' => $cotizacion->toArray(),
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('CotizacionController@aceptar: Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Subir imagen a cotización
     *
     * Usa FormData (no Base64) para mejor rendimiento
     */
    public function subirImagen(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'prenda_id' => 'required|integer',
                'tipo' => 'required|in:prenda,tela,logo,bordado,estampado',
                'archivo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            $comando = SubirImagenCotizacionCommand::crear(
                $id,
                $request->integer('prenda_id'),
                $request->string('tipo'),
                $request->file('archivo'),
                Auth::id()
            );

            $rutaImagen = $this->subirImagenHandler->handle($comando);

            return response()->json([
                'success' => true,
                'message' => 'Imagen subida exitosamente',
                'data' => [
                    'ruta' => $rutaImagen->valor(),
                ],
            ], 201);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error('CotizacionController@subirImagen: Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al subir imagen'], 500);
        }
    }

    /**
     * Guardar cotización tipo RF (Reflectivo)
     * 
     * Endpoint específico para manejar el flujo de reflectivo desde create-reflectivo.blade.php
     */
    public function storeReflectivo(Request $request): JsonResponse
    {
        try {
            Log::info('🔵 CotizacionController@storeReflectivo - Iniciando creación de cotización RF', [
                'cliente' => $request->input('cliente'),
                'tipo' => $request->input('tipo'),
                'action' => $request->input('action'),
            ]);

            // Validar datos básicos
            $validated = $request->validate([
                'cliente' => 'required|string|max:255',
                'asesora' => 'nullable|string|max:255',
                'fecha' => 'required|date',
                'action' => 'required|in:borrador,enviar',
                'tipo' => 'required|in:RF',
                'prendas' => 'required|string', // Ahora acepta string JSON
                'especificaciones' => 'nullable|string',
                'descripcion_reflectivo' => 'required|string',
                'ubicaciones_reflectivo' => 'nullable',
                'observaciones_generales' => 'nullable',
                'imagenes_reflectivo.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            // Decodificar prendas del JSON string
            $prendas = json_decode($validated['prendas'], true);
            if (!is_array($prendas) || count($prendas) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prendas inválidas. Debe ser un array con al menos 1 prenda.',
                    'errores' => ['prendas' => ['Array inválido o vacío']]
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Obtener o crear cliente
                $cliente = $this->obtenerOCrearClienteService->ejecutar($validated['cliente']);
                Log::info('✅ Cliente obtenido/creado', ['cliente_id' => $cliente->id]);

                // Determinar estado
                $esBorrador = ($validated['action'] === 'borrador');
                $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR';

                // Crear cotización base sin prendas (tipo RF = Reflectivo)
                $cotizacion = \App\Models\Cotizacion::create([
                    'asesor_id' => Auth::id(),
                    'cliente_id' => $cliente->id,
                    'numero_cotizacion' => !$esBorrador ? $this->generarNumeroCotizacion() : null,
                    'tipo_cotizacion_id' => $this->obtenerTipoCotizacionId('RF'),
                    'tipo_venta' => 'M', // Default para reflectivo
                    'fecha_inicio' => $validated['fecha'],
                    'especificaciones' => $validated['especificaciones'] ?? '',
                    'es_borrador' => $esBorrador,
                    'estado' => $estado,
                    'fecha_envio' => !$esBorrador ? \Carbon\Carbon::now('America/Bogota') : null,
                ]);

                Log::info('✅ Cotización RF creada', ['cotizacion_id' => $cotizacion->id]);

                // Procesar prendas - ahora vienen como objetos {tipo, descripcion}
                if (!empty($prendas)) {
                    foreach ($prendas as $prenda) {
                        // La prenda ya está decodificada como array
                        if (is_array($prenda)) {
                            // Guardar prenda en prendas_cot
                            \App\Models\PrendaCot::create([
                                'cotizacion_id' => $cotizacion->id,
                                'nombre_producto' => $prenda['tipo'] ?? $prenda['nombre'] ?? 'Prenda',
                                'cantidad' => 1,
                                'descripcion' => $prenda['descripcion'] ?? '',
                            ]);
                            
                            // Guardar reflectivo con tipo_prenda
                            $reflectivo = \App\Models\ReflectivoCotizacion::create([
                                'cotizacion_id' => $cotizacion->id,
                                'tipo_prenda' => $prenda['tipo'] ?? 'Prenda',
                                'descripcion' => $prenda['descripcion'] ?? '',
                                'ubicacion' => json_encode([]),
                                'observaciones_generales' => json_encode([]),
                                'imagenes' => json_encode([]),
                            ]);
                            
                            Log::info('✅ ReflectivoCotizacion creado con tipo_prenda', [
                                'reflectivo_id' => $reflectivo->id,
                                'tipo_prenda' => $prenda['tipo'] ?? 'Prenda'
                            ]);
                        }
                    }
                    $prendasCount = is_array($prendas) ? count($prendas) : 0;
                    Log::info('✅ Prendas guardadas', ['cotizacion_id' => $cotizacion->id, 'prendas_count' => $prendasCount]);
                }


                // Procesar imágenes
                $imagenesGuardadas = [];
                $orden = 1;
                if ($request->hasFile('imagenes_reflectivo')) {
                    foreach ($request->file('imagenes_reflectivo') as $archivo) {
                        if ($archivo && $archivo->isValid()) {
                            // Guardar archivo
                            $ruta = $archivo->store('cotizaciones/reflectivo', 'public');
                            
                            // Guardar en tabla reflectivo_fotos_cotizacion
                            // Asocuar a la primera prenda reflectivo
                            $primeraReflexico = \App\Models\ReflectivoCotizacion::where('cotizacion_id', $cotizacion->id)->first();
                            if ($primeraReflexico) {
                                $foto = \App\Models\ReflectivoCotizacionFoto::create([
                                    'reflectivo_cotizacion_id' => $primeraReflexico->id,
                                    'ruta_original' => $ruta,
                                    'ruta_webp' => $ruta,
                                    'orden' => $orden++,
                                ]);
                                
                                $imagenesGuardadas[] = $foto->id;

                                Log::info('📸 Imagen guardada en reflectivo_fotos_cotizacion', ['ruta' => $ruta]);
                            }
                        }
                    }
                }

                DB::commit();

                // Recargar cotización con relaciones
                $cotizacionCompleta = \App\Models\Cotizacion::with([
                    'cliente',
                    'reflectivoCotizacion',
                ])->findOrFail($cotizacion->id);

                Log::info('✅ CotizacionController@storeReflectivo - Exitoso', [
                    'cotizacion_id' => $cotizacion->id,
                    'estado' => $estado,
                    'imagenes_count' => count($imagenesGuardadas),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Cotización de reflectivo guardada exitosamente',
                    'data' => [
                        'cotizacion' => $cotizacionCompleta->toArray(),
                        'reflectivo' => $reflectivo->toArray(),
                    ],
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Error de validación', ['errores' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errores' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('❌ CotizacionController@storeReflectivo: Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar cotización: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar cotización tipo RF (Reflectivo) - Para editar borradores
     */
    public function updateReflectivo(Request $request, int $id): JsonResponse
    {
        try {
            Log::info('🔵 CotizacionController@updateReflectivo - Iniciando actualización de cotización RF', [
                'cotizacion_id' => $id,
                'action' => $request->input('action'),
            ]);

            $cotizacion = \App\Models\Cotizacion::findOrFail($id);

            // Validar que el usuario es propietario
            if ($cotizacion->asesor_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'No tienes permiso'], 403);
            }

            // Validar datos
            $validated = $request->validate([
                'cliente' => 'required|string|max:255',
                'asesora' => 'nullable|string|max:255',
                'fecha' => 'required|date',
                'action' => 'required|in:borrador,enviar',
                'tipo' => 'required|in:RF',
                'prendas' => 'nullable|array|min:1',
                'especificaciones' => 'nullable|string',
                'descripcion_reflectivo' => 'required|string',
                'ubicaciones_reflectivo' => 'nullable',
                'observaciones_generales' => 'nullable',
                'imagenes_reflectivo.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'imagenes_a_eliminar' => 'nullable|array',
            ]);

            DB::beginTransaction();

            try {
                // Actualizar cliente si es necesario
                $cliente = $this->obtenerOCrearClienteService->ejecutar($validated['cliente']);

                // Determinar estado
                $esBorrador = ($validated['action'] === 'borrador');
                $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR';

                // Actualizar cotización
                $cotizacion->update([
                    'cliente_id' => $cliente->id,
                    'fecha_inicio' => $validated['fecha'],
                    'es_borrador' => $esBorrador,
                    'estado' => $estado,
                    'numero_cotizacion' => !$esBorrador && !$cotizacion->numero_cotizacion ? $this->generarNumeroCotizacion() : $cotizacion->numero_cotizacion,
                    'fecha_envio' => !$esBorrador && !$cotizacion->fecha_envio ? \Carbon\Carbon::now('America/Bogota') : $cotizacion->fecha_envio,
                ]);

                Log::info('✅ Cotización RF actualizada', ['cotizacion_id' => $cotizacion->id]);

                // Actualizar prendas si se proporcionan
                if (isset($validated['prendas']) && is_array($validated['prendas'])) {
                    // Eliminar prendas existentes
                    \App\Models\PrendaCot::where('cotizacion_id', $cotizacion->id)->delete();
                    
                    // Crear nuevas prendas
                    foreach ($validated['prendas'] as $prenda) {
                        // La prenda puede venir como JSON string o array
                        if (is_string($prenda)) {
                            $prenda = json_decode($prenda, true);
                        }
                        
                        if (is_array($prenda)) {
                            \App\Models\PrendaCot::create([
                                'cotizacion_id' => $cotizacion->id,
                                'nombre_producto' => $prenda['tipo'] ?? $prenda['nombre'] ?? 'Prenda',
                                'cantidad' => 1,
                                'descripcion' => $prenda['descripcion'] ?? '',
                            ]);
                        }
                    }
                    Log::info('✅ Prendas actualizadas', ['cotizacion_id' => $cotizacion->id, 'prendas_count' => count($validated['prendas'])]);
                }

                // Obtener o actualizar reflectivo
                $reflectivo = $cotizacion->reflectivoCotizacion ?? new \App\Models\ReflectivoCotizacion();
                
                // Procesar ubicaciones
                $ubicaciones = [];
                if ($request->has('ubicaciones_reflectivo')) {
                    $ubicacionesInput = $request->input('ubicaciones_reflectivo');
                    if (is_string($ubicacionesInput)) {
                        $ubicaciones = json_decode($ubicacionesInput, true) ?? [];
                    } elseif (is_array($ubicacionesInput)) {
                        $ubicaciones = $ubicacionesInput;
                    }
                }

                // Procesar observaciones
                $observaciones = [];
                if ($request->has('observaciones_generales')) {
                    $observacionesInput = $request->input('observaciones_generales');
                    if (is_string($observacionesInput)) {
                        $observaciones = json_decode($observacionesInput, true) ?? [];
                    } elseif (is_array($observacionesInput)) {
                        $observaciones = $observacionesInput;
                    }
                }

                // Actualizar o crear reflectivo
                if ($reflectivo->id) {
                    $reflectivo->update([
                        'descripcion' => $validated['descripcion_reflectivo'],
                        'ubicacion' => json_encode($ubicaciones),
                        'observaciones_generales' => json_encode($observaciones),
                    ]);
                } else {
                    $reflectivo = \App\Models\ReflectivoCotizacion::create([
                        'cotizacion_id' => $cotizacion->id,
                        'descripcion' => $validated['descripcion_reflectivo'],
                        'ubicacion' => json_encode($ubicaciones),
                        'observaciones_generales' => json_encode($observaciones),
                        'imagenes' => json_encode([]),
                    ]);
                }

                // Eliminar imágenes que el usuario marcó
                if ($request->has('imagenes_a_eliminar')) {
                    $imagenesAEliminar = $request->input('imagenes_a_eliminar');
                    if (is_string($imagenesAEliminar)) {
                        $imagenesAEliminar = json_decode($imagenesAEliminar, true) ?? [];
                    }
                    
                    foreach ($imagenesAEliminar as $fotoId) {
                        $foto = \App\Models\ReflectivoCotizacionFoto::findOrFail($fotoId);
                        // Eliminar archivo
                        if ($foto->ruta_original && Storage::disk('public')->exists($foto->ruta_original)) {
                            Storage::disk('public')->delete($foto->ruta_original);
                        }
                        $foto->delete();
                        Log::info('📸 Imagen eliminada', ['foto_id' => $fotoId, 'ruta' => $foto->ruta_original]);
                    }
                }

                // Procesar nuevas imágenes
                $imagenesGuardadas = [];
                $orden = $reflectivo->fotos ? $reflectivo->fotos->count() + 1 : 1;
                
                if ($request->hasFile('imagenes_reflectivo')) {
                    foreach ($request->file('imagenes_reflectivo') as $archivo) {
                        if ($archivo && $archivo->isValid()) {
                            $ruta = $archivo->store('cotizaciones/reflectivo', 'public');
                            
                            $foto = \App\Models\ReflectivoCotizacionFoto::create([
                                'reflectivo_cotizacion_id' => $reflectivo->id,
                                'ruta_original' => $ruta,
                                'ruta_webp' => $ruta,
                                'orden' => $orden++,
                            ]);
                            
                            $imagenesGuardadas[] = $foto->id;
                            Log::info('📸 Imagen guardada en actualización', ['ruta' => $ruta]);
                        }
                    }
                }

                DB::commit();

                // Recargar cotización con relaciones
                $cotizacionCompleta = \App\Models\Cotizacion::with([
                    'cliente',
                    'reflectivoCotizacion.fotos',
                ])->findOrFail($cotizacion->id);

                Log::info('✅ CotizacionController@updateReflectivo - Exitoso', [
                    'cotizacion_id' => $cotizacion->id,
                    'imagenes_count' => count($imagenesGuardadas),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Cotización de reflectivo actualizada exitosamente',
                    'data' => [
                        'cotizacion' => $cotizacionCompleta->toArray(),
                        'reflectivo' => $cotizacionCompleta->reflectivoCotizacion->toArray(),
                    ],
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Error de validación', ['errores' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errores' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('❌ CotizacionController@updateReflectivo: Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar cotización: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar número de cotización único
     */
    private function generarNumeroCotizacion(): string
    {
        $mes = date('m');
        $anio = date('y');
        $contador = \App\Models\Cotizacion::where('numero_cotizacion', 'like', "COT-{$anio}{$mes}-%")
            ->count() + 1;
        
        return sprintf('COT-%s%s-%04d', $anio, $mes, $contador);
    }

    /**
     * Obtener ID de tipo de cotización por código
     */
    /**
     * Mostrar vista de edición de borrador con datos precargados
     */
    public function editBorrador(int $id)
    {
        try {
            // Obtener cotización con todas sus relaciones
            $cotizacion = \App\Models\Cotizacion::with([
                'cliente',
                'prendas.fotos',
                'prendas.telaFotos',
                'prendas.tallas',
                'prendas.variantes.manga',
                'prendas.variantes.broche',
                'logoCotizacion.fotos',
                'tipoCotizacion',
                'reflectivoCotizacion.fotos'
            ])->findOrFail($id);

            // Verificar que el usuario es propietario
            if ($cotizacion->asesor_id !== Auth::id()) {
                abort(403, 'No tienes permiso para editar esta cotización');
            }

            // Verificar que es borrador
            if (!$cotizacion->es_borrador) {
                abort(403, 'Solo se pueden editar borradores');
            }

            // Determinar el tipo de cotización y la vista a mostrar
            // Si existe reflectivo_cotizacion, es tipo RF (reflectivo)
            // Si no, obtener del tipoCotizacion por codigo
            $tieneReflectivo = $cotizacion->reflectivoCotizacion !== null;
            $tipo = $tieneReflectivo ? 'RF' : ($cotizacion->tipoCotizacion->codigo ?? 'P');
            $vista = 'asesores.pedidos.create-friendly';
            
            if ($tipo === 'RF') {
                $vista = 'asesores.pedidos.create-reflectivo';
            }
            
            Log::info('CotizacionController@editBorrador: Abriendo borrador para editar', [
                'cotizacion_id' => $cotizacion->id,
                'tipo' => $tipo,
                'vista' => $vista,
                'es_borrador' => $cotizacion->es_borrador,
            ]);

            // Transformar prendas a productos para que cargarBorrador() funcione correctamente
            $cotizacionArray = $cotizacion->toArray();
            
            // Forzar la inclusión de relaciones en el array
            try {
                // Incluir cliente manualmente
                if ($cotizacion->cliente) {
                    $cotizacionArray['cliente'] = $cotizacion->cliente->toArray();
                }
                
                // Incluir reflectivo manualmente (IMPORTANTE: asegurar que siempre esté)
                if ($cotizacion->reflectivoCotizacion) {
                    $cotizacionArray['reflectivo_cotizacion'] = $cotizacion->reflectivoCotizacion->toArray();
                    // Asegurar que las fotos están incluidas
                    if ($cotizacion->reflectivoCotizacion->relationLoaded('fotos')) {
                        $cotizacionArray['reflectivo_cotizacion']['fotos'] = $cotizacion->reflectivoCotizacion->fotos->toArray();
                    }
                } else {
                    \Log::warning('⚠️ editBorrador - reflectivoCotizacion es NULL');
                    // Intentar cargar por reflectivo_id si existe
                    if (isset($cotizacion->reflectivo_id) && $cotizacion->reflectivo_id) {
                        $reflectivoModel = \App\Models\ReflectivoCotizacion::with('fotos')->find($cotizacion->reflectivo_id);
                        if ($reflectivoModel) {
                            $cotizacionArray['reflectivo_cotizacion'] = $reflectivoModel->toArray();
                            if ($reflectivoModel->fotos) {
                                $cotizacionArray['reflectivo_cotizacion']['fotos'] = $reflectivoModel->fotos->toArray();
                            }
                        }
                    }
                }
                
                // Incluir prendas manualmente con cada una convertida a array
                if ($cotizacion->prendas && count($cotizacion->prendas) > 0) {
                    $cotizacionArray['prendas'] = $cotizacion->prendas->map(function($prenda) {
                        return $prenda->toArray();
                    })->toArray();
                }
            } catch (\Exception $e) {
                \Log::warning('Error intentando cargar relaciones en editBorrador', ['error' => $e->getMessage()]);
            }
            
            // Debug: Verificar que las fotos se están incluyendo
            \Log::info('DEBUG editBorrador - Prendas con fotos:', [
                'prendas_count' => isset($cotizacionArray['prendas']) ? count($cotizacionArray['prendas']) : 0,
                'primera_prenda' => isset($cotizacionArray['prendas'][0]) ? [
                    'id' => $cotizacionArray['prendas'][0]['id'] ?? null,
                    'fotos_count' => isset($cotizacionArray['prendas'][0]['fotos']) ? count($cotizacionArray['prendas'][0]['fotos']) : 0,
                    'tela_fotos_count' => isset($cotizacionArray['prendas'][0]['tela_fotos']) ? count($cotizacionArray['prendas'][0]['tela_fotos']) : 0,
                    'fotos_keys' => isset($cotizacionArray['prendas'][0]['fotos']) ? array_keys($cotizacionArray['prendas'][0]['fotos'][0] ?? []) : []
                ] : null,
                'logo_cotizacion_existe' => isset($cotizacionArray['logo_cotizacion']),
                'logo_fotos_count' => isset($cotizacionArray['logo_cotizacion']['fotos']) ? count($cotizacionArray['logo_cotizacion']['fotos']) : 0,
                'logo_cotizacion_keys' => isset($cotizacionArray['logo_cotizacion']) ? array_keys($cotizacionArray['logo_cotizacion']) : []
            ]);
            
            // Asegurar que especificaciones esté correctamente deserializada
            if (isset($cotizacionArray['especificaciones']) && is_string($cotizacionArray['especificaciones'])) {
                // Primer decode
                $decoded = json_decode($cotizacionArray['especificaciones'], true);
                
                // Si sigue siendo string (doblemente escapado), hacer otro decode
                if (is_string($decoded)) {
                    $decoded = json_decode($decoded, true);
                }
                
                $cotizacionArray['especificaciones'] = $decoded ?? [];
            }
            
            // Asegurar que logo_cotizacion.fotos se devuelve correctamente
            if (isset($cotizacionArray['logo_cotizacion'])) {
                $logoId = $cotizacionArray['logo_cotizacion']['id'] ?? null;
                \Log::info('DEBUG editBorrador - Logo cotización campos:', [
                    'logo_id' => $logoId,
                    'campos_disponibles' => array_keys($cotizacionArray['logo_cotizacion']),
                    'fotos_count' => isset($cotizacionArray['logo_cotizacion']['fotos']) ? count($cotizacionArray['logo_cotizacion']['fotos']) : 0
                ]);
                
                if (empty($cotizacionArray['logo_cotizacion']['fotos']) && $logoId) {
                    $logoCotizacionModel = \App\Models\LogoCotizacion::with('fotos')->find($logoId);
                    if ($logoCotizacionModel && $logoCotizacionModel->fotos) {
                        $cotizacionArray['logo_cotizacion']['fotos'] = $logoCotizacionModel->fotos->toArray();
                        \Log::info('DEBUG - Logo fotos cargadas directamente desde modelo:', [
                            'logo_id' => $logoId,
                            'fotos_count' => count($cotizacionArray['logo_cotizacion']['fotos']),
                        ]);
                    }
                }
                
                // Deserializar técnicas, ubicaciones y observaciones_generales (ya vienen como arrays por los casts)
                // Pero verificar por si vienen como strings
                if (isset($cotizacionArray['logo_cotizacion']['tecnicas']) && is_string($cotizacionArray['logo_cotizacion']['tecnicas'])) {
                    $cotizacionArray['logo_cotizacion']['tecnicas'] = json_decode($cotizacionArray['logo_cotizacion']['tecnicas'], true) ?? [];
                }
                if (isset($cotizacionArray['logo_cotizacion']['ubicaciones']) && is_string($cotizacionArray['logo_cotizacion']['ubicaciones'])) {
                    $cotizacionArray['logo_cotizacion']['ubicaciones'] = json_decode($cotizacionArray['logo_cotizacion']['ubicaciones'], true) ?? [];
                }
                if (isset($cotizacionArray['logo_cotizacion']['observaciones_generales']) && is_string($cotizacionArray['logo_cotizacion']['observaciones_generales'])) {
                    $cotizacionArray['logo_cotizacion']['observaciones_generales'] = json_decode($cotizacionArray['logo_cotizacion']['observaciones_generales'], true) ?? [];
                }
                
                // Asegurar que observaciones_tecnicas existe (es un campo de texto, no JSON)
                if (!isset($cotizacionArray['logo_cotizacion']['observaciones_tecnicas'])) {
                    $cotizacionArray['logo_cotizacion']['observaciones_tecnicas'] = '';
                }
                
                \Log::info('DEBUG editBorrador - Logo después de procesar:', [
                    'tecnicas' => $cotizacionArray['logo_cotizacion']['tecnicas'] ?? null,
                    'ubicaciones' => $cotizacionArray['logo_cotizacion']['ubicaciones'] ?? null,
                    'obs_generales' => $cotizacionArray['logo_cotizacion']['observaciones_generales'] ?? null,
                    'obs_tecnicas' => $cotizacionArray['logo_cotizacion']['observaciones_tecnicas'] ?? null,
                    'fotos_count' => isset($cotizacionArray['logo_cotizacion']['fotos']) ? count($cotizacionArray['logo_cotizacion']['fotos']) : 0
                ]);
            }

            // Procesar reflectivo_cotizacion si existe
            if (isset($cotizacionArray['reflectivo_cotizacion'])) {
                $reflectivoId = $cotizacionArray['reflectivo_cotizacion']['id'] ?? null;
                \Log::info('🔍 DEBUG editBorrador - Reflectivo cargado:', [
                    'reflectivo_id' => $reflectivoId,
                    'campos_disponibles' => array_keys($cotizacionArray['reflectivo_cotizacion']),
                    'fotos_count' => isset($cotizacionArray['reflectivo_cotizacion']['fotos']) ? count($cotizacionArray['reflectivo_cotizacion']['fotos']) : 0
                ]);

                // Si fotos está vacío, intentar cargar desde el modelo
                if (empty($cotizacionArray['reflectivo_cotizacion']['fotos']) && $reflectivoId) {
                    $reflectivoModel = \App\Models\ReflectivoCotizacion::with('fotos')->find($reflectivoId);
                    if ($reflectivoModel && $reflectivoModel->fotos && count($reflectivoModel->fotos) > 0) {
                        $cotizacionArray['reflectivo_cotizacion']['fotos'] = $reflectivoModel->fotos->toArray();
                        \Log::info('✅ DEBUG - Reflectivo fotos cargadas desde modelo:', [
                            'reflectivo_id' => $reflectivoId,
                            'fotos_count' => count($cotizacionArray['reflectivo_cotizacion']['fotos'])
                        ]);
                    } else {
                        \Log::warning('⚠️ DEBUG - No se encontraron fotos en reflectivo', [
                            'reflectivo_id' => $reflectivoId,
                            'modelo_existe' => $reflectivoModel ? 'SÍ' : 'NO',
                            'fotos_existe' => $reflectivoModel && $reflectivoModel->fotos ? 'SÍ' : 'NO'
                        ]);
                    }
                }

                \Log::info('✅ DEBUG editBorrador - Reflectivo después de procesar:', [
                    'reflectivo_id' => $reflectivoId,
                    'fotos_final_count' => isset($cotizacionArray['reflectivo_cotizacion']['fotos']) ? count($cotizacionArray['reflectivo_cotizacion']['fotos']) : 0
                ]);
            }
            
            if (isset($cotizacionArray['prendas'])) {
                // Transformar variantes a formato simple
                foreach ($cotizacionArray['prendas'] as &$prenda) {
                    // Debug: Verificar qué relaciones existen antes de guardar
                    \Log::info('DEBUG - Prenda antes de guardar fotos:', [
                        'prenda_id' => $prenda['id'] ?? null,
                        'keys' => array_keys($prenda),
                        'fotos_existe' => isset($prenda['fotos']),
                        'tela_fotos_existe' => isset($prenda['tela_fotos']),
                        'fotos_count' => isset($prenda['fotos']) ? count($prenda['fotos']) : 0,
                        'tela_fotos_count' => isset($prenda['tela_fotos']) ? count($prenda['tela_fotos']) : 0,
                        'fotos_sample' => isset($prenda['fotos']) && count($prenda['fotos']) > 0 ? $prenda['fotos'][0] : null
                    ]);
                    
                    // Guardar fotos y tela_fotos antes de transformar variantes
                    $fotosGuardadas = $prenda['fotos'] ?? [];
                    $telaFotosGuardadas = $prenda['tela_fotos'] ?? [];
                    
                    // Si fotos o tela_fotos están vacíos, intentar cargar directamente desde la relación
                    if ((empty($fotosGuardadas) || empty($telaFotosGuardadas)) && isset($prenda['id'])) {
                        \Log::info('DEBUG - Intentando cargar fotos desde modelo:', [
                            'prenda_id' => $prenda['id'],
                            'fotos_vacio' => empty($fotosGuardadas),
                            'tela_fotos_vacio' => empty($telaFotosGuardadas)
                        ]);
                        
                        $prendaModel = \App\Models\PrendaCot::with(['fotos', 'telaFotos'])->find($prenda['id']);
                        
                        \Log::info('DEBUG - Modelo cargado:', [
                            'prenda_id' => $prenda['id'],
                            'modelo_existe' => !!$prendaModel,
                            'fotos_relation_existe' => $prendaModel ? !!$prendaModel->fotos : false,
                            'fotos_relation_count' => $prendaModel && $prendaModel->fotos ? count($prendaModel->fotos) : 0,
                            'tela_fotos_relation_existe' => $prendaModel ? !!$prendaModel->telaFotos : false,
                            'tela_fotos_relation_count' => $prendaModel && $prendaModel->telaFotos ? count($prendaModel->telaFotos) : 0
                        ]);
                        
                        if ($prendaModel) {
                            if (empty($fotosGuardadas) && $prendaModel->fotos && count($prendaModel->fotos) > 0) {
                                $fotosGuardadas = $prendaModel->fotos->toArray();
                                \Log::info('DEBUG - Fotos de prenda cargadas directamente desde modelo:', [
                                    'prenda_id' => $prenda['id'],
                                    'fotos_count' => count($fotosGuardadas)
                                ]);
                            }
                            
                            if (empty($telaFotosGuardadas) && $prendaModel->telaFotos && count($prendaModel->telaFotos) > 0) {
                                $telaFotosGuardadas = $prendaModel->telaFotos->toArray();
                                \Log::info('DEBUG - Tela fotos cargadas directamente desde modelo:', [
                                    'prenda_id' => $prenda['id'],
                                    'tela_fotos_count' => count($telaFotosGuardadas)
                                ]);
                            }
                        }
                    }
                    
                    if (isset($prenda['variantes']) && is_array($prenda['variantes'])) {
                        // Si hay variantes, tomar la primera y extraer los campos principales
                        if (count($prenda['variantes']) > 0) {
                            $variante = $prenda['variantes'][0];
                            
                            // Extraer tela y referencia de telas_multiples si existen
                            $tela = '';
                            $referencia = '';
                            if (isset($variante['telas_multiples']) && is_array($variante['telas_multiples']) && count($variante['telas_multiples']) > 0) {
                                $primeraTela = $variante['telas_multiples'][0];
                                $tela = $primeraTela['tela'] ?? '';
                                $referencia = $primeraTela['referencia'] ?? '';
                            }
                            
                            $prenda['variantes'] = [
                                'color' => $variante['color'] ?? '',
                                'tela' => $tela,
                                'referencia' => $referencia,
                                'genero_id' => $variante['genero_id'] ?? null,
                                'tipo_manga_id' => $variante['tipo_manga_id'] ?? null,
                                'tipo_manga' => $variante['tipo_manga'] ?? '',
                                'obs_manga' => $variante['obs_manga'] ?? '',
                                'tiene_bolsillos' => $variante['tiene_bolsillos'] ?? false,
                                'obs_bolsillos' => $variante['obs_bolsillos'] ?? '',
                                'tipo_broche_id' => $variante['tipo_broche_id'] ?? null,
                                'obs_broche' => $variante['obs_broche'] ?? '',
                                'tiene_reflectivo' => $variante['tiene_reflectivo'] ?? false,
                                'obs_reflectivo' => $variante['obs_reflectivo'] ?? ''
                            ];
                        }
                    }
                    
                    // Restaurar fotos y tela_fotos
                    $prenda['fotos'] = $fotosGuardadas;
                    $prenda['tela_fotos'] = $telaFotosGuardadas;
                    
                    // Si tela_fotos está vacío, intentar cargar directamente desde la relación
                    if (empty($telaFotosGuardadas) && isset($prenda['id'])) {
                        $prendaModel = \App\Models\PrendaCot::find($prenda['id']);
                        if ($prendaModel && $prendaModel->telaFotos) {
                            $prenda['tela_fotos'] = $prendaModel->telaFotos->toArray();
                            \Log::info('DEBUG - Tela fotos cargadas directamente desde modelo:', [
                                'prenda_id' => $prenda['id'],
                                'tela_fotos_count' => count($prenda['tela_fotos'])
                            ]);
                        }
                    }
                    
                    // Debug: Verificar que tela_fotos se está devolviendo
                    \Log::info('DEBUG - Prenda después de restaurar fotos:', [
                        'prenda_id' => $prenda['id'] ?? null,
                        'fotos_count' => count($fotosGuardadas),
                        'tela_fotos_count' => count($prenda['tela_fotos'] ?? []),
                        'tela_fotos_sample' => (isset($prenda['tela_fotos']) && count($prenda['tela_fotos']) > 0) ? $prenda['tela_fotos'][0] : null
                    ]);
                }
                // Mantener prendas en el array (no usar productos para evitar confusión)
                // $cotizacionArray['productos'] = $cotizacionArray['prendas'];
            }
            
            // Pasar los datos a la vista de edición
            // Renombrar reflectivo_cotizacion a reflectivo para que el JavaScript funcione correctamente
            if (isset($cotizacionArray['reflectivo_cotizacion'])) {
                $cotizacionArray['reflectivo'] = $cotizacionArray['reflectivo_cotizacion'];
                unset($cotizacionArray['reflectivo_cotizacion']);
            }
            
            $datosJSON = json_encode($cotizacionArray);
            
            // DEBUG: Verificar que reflectivo está en JSON
            $datosDecodificados = json_decode($datosJSON, true);
            $reflectivoEnJSON = isset($datosDecodificados['reflectivo']) ? 'SÍ' : 'NO';
            $fotosEnJSON = isset($datosDecodificados['reflectivo']['fotos']) ? count($datosDecodificados['reflectivo']['fotos']) : 0;
            
            Log::info('CotizacionController@editBorrador: Datos a pasar a vista', [
                'cotizacion_id' => $cotizacion->id,
                'tipo' => $tipo,
                'cliente_existe' => isset($cotizacionArray['cliente']),
                'fecha_inicio_existe' => isset($cotizacionArray['fecha_inicio']),
                'prendas_count' => isset($cotizacionArray['prendas']) ? count($cotizacionArray['prendas']) : 0,
                'reflectivo_cotizacion_en_json' => $reflectivoEnJSON,
                'fotos_count_en_json' => $fotosEnJSON,
                'datosJSON_length' => strlen($datosJSON),
                'datos_sample' => substr($datosJSON, 0, 500)
            ]);
            
            return view($vista, [
                'cotizacionId' => $cotizacion->id,
                'cotizacion' => (object)$cotizacionArray,
                'tipo' => $tipo,
                'esEdicion' => true,
                'datosIniciales' => $datosJSON,
            ]);
        } catch (\Exception $e) {
            Log::error('CotizacionController@editBorrador: Error', ['error' => $e->getMessage()]);
            abort(500, 'Error al cargar el borrador: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar una foto inmediatamente (sin esperar a guardar el borrador)
     */
    public function eliminarFotoInmediatamente(Request $request): JsonResponse
    {
        try {
            $rutaFoto = $request->input('ruta');
            $cotizacionId = $request->input('cotizacion_id');
            
            if (!$rutaFoto) {
                return response()->json(['success' => false, 'message' => 'Ruta de foto no proporcionada'], 400);
            }
            
            // Decodificar URL (convertir %20 a espacios, etc.)
            $rutaFoto = urldecode($rutaFoto);
            
            Log::info('Eliminando foto inmediatamente', ['ruta' => $rutaFoto, 'cotizacion_id' => $cotizacionId]);
            
            // Extraer ruta relativa de diferentes formatos
            $rutaRelativa = $rutaFoto;
            
            // Si es URL completa, extraer la parte de /storage/
            if (strpos($rutaFoto, '/storage/') !== false) {
                $rutaRelativa = substr($rutaFoto, strpos($rutaFoto, '/storage/') + 9);
            } elseif (strpos($rutaFoto, 'storage/') !== false) {
                $rutaRelativa = substr($rutaFoto, strpos($rutaFoto, 'storage/') + 8);
            }
            
            // También crear variaciones de la ruta para buscar en BD
            $rutaConStorage = 'storage/' . $rutaRelativa;
            $rutaConSlash = '/' . $rutaConStorage;
            
            Log::info('Rutas extraídas para búsqueda en BD', [
                'original' => $rutaFoto,
                'relativa' => $rutaRelativa,
                'con_storage' => $rutaConStorage,
                'con_slash' => $rutaConSlash
            ]);
            
            // Eliminar de storage/app/public
            if (\Storage::disk('public')->exists($rutaRelativa)) {
                \Storage::disk('public')->delete($rutaRelativa);
                Log::info('Archivo eliminado de storage/app/public', ['ruta' => $rutaRelativa]);
            }
            
            // Eliminar de public/storage
            $rutaPublica = public_path("storage/{$rutaRelativa}");
            if (file_exists($rutaPublica)) {
                @unlink($rutaPublica);
                Log::info('Archivo eliminado de public/storage', ['ruta' => $rutaPublica]);
            }
            
            // Eliminar registros de la base de datos - buscar por todas las variaciones de ruta
            $fotosEliminadas = 0;
            
            // Crear array de rutas a buscar
            $rutasABuscar = [
                $rutaFoto,           // URL completa original
                $rutaRelativa,       // Ruta relativa sin storage/
                $rutaConStorage,     // storage/cotizaciones/...
                $rutaConSlash,       // /storage/cotizaciones/...
            ];
            
            Log::info('Buscando fotos en BD con rutas', ['rutas' => $rutasABuscar]);
            
            // Buscar y eliminar de PrendaFotoCot
            foreach ($rutasABuscar as $ruta) {
                $fotosEliminadas += \App\Models\PrendaFotoCot::where('ruta_original', $ruta)
                    ->orWhere('ruta_webp', $ruta)
                    ->delete();
            }
            
            // Buscar y eliminar de PrendaTelaFotoCot
            foreach ($rutasABuscar as $ruta) {
                $fotosEliminadas += \App\Models\PrendaTelaFotoCot::where('ruta_original', $ruta)
                    ->orWhere('ruta_webp', $ruta)
                    ->delete();
            }
            
            Log::info('Registros de foto eliminados de la base de datos', [
                'ruta' => $rutaFoto,
                'ruta_relativa' => $rutaRelativa,
                'registros_eliminados' => $fotosEliminadas
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Foto eliminada correctamente',
                'registros_eliminados' => $fotosEliminadas
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar foto inmediatamente', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la foto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un borrador completamente
     */
    public function destroyBorrador(int $id)
    {
        try {
            $cotizacion = \App\Models\Cotizacion::findOrFail($id);
            
            // Verificar que el usuario es propietario
            if ($cotizacion->asesor_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'No tienes permiso'], 403);
            }
            
            // Verificar que es un borrador
            if (!$cotizacion->es_borrador) {
                return response()->json(['success' => false, 'message' => 'Solo se pueden eliminar borradores'], 400);
            }
            
            Log::info('Eliminando borrador', ['cotizacion_id' => $id, 'asesor_id' => Auth::id()]);
            
            // Eliminar prendas asociadas (cascada)
            \App\Models\PrendaCot::where('cotizacion_id', $id)->delete();
            
            // Eliminar logo asociado (cascada)
            \App\Models\LogoCotizacion::where('cotizacion_id', $id)->delete();
            
            // Eliminar la cotización
            $cotizacion->delete();
            
            Log::info('Borrador eliminado correctamente', ['cotizacion_id' => $id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Borrador eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar borrador', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el borrador: ' . $e->getMessage()
            ], 500);
        }
    }

    private function obtenerTipoCotizacionId(string $tipo): int
    {
        // Crear o buscar tipo de cotización
        $tipoCot = \App\Models\TipoCotizacion::firstOrCreate(
            ['codigo' => $tipo],
            ['nombre' => $tipo === 'RF' ? 'Reflectivo' : $tipo]
        );
        
        return $tipoCot->id;
    }
}
