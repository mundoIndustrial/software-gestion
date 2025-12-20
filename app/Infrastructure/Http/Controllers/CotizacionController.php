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
use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
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
        private readonly GenerarNumeroCotizacionService $generarNumeroCotizacionService,
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
     * 
     * Estructura: Múltiples reflectivos (UNO POR PRENDA) + fotos de cada uno
     */
    public function getReflectivoForEdit(int $id): JsonResponse
    {
        try {
            Log::info('🔍 getReflectivoForEdit: INICIANDO', ['cotizacion_id' => $id, 'usuario_id' => Auth::id()]);

            // Obtener cotización reflectivo con TODAS las relaciones de prendas
            $cotizacion = \App\Models\Cotizacion::with([
                'cliente',
                'prendas.tallas',           // ✅ Tallas de cada prenda
                'prendas.variantes',        // ✅ Género y variantes
                'prendas.reflectivo.fotos', // ✅ Reflectivo y fotos
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

            // ✅ PROCESAR PRENDAS CON SUS REFLECTIVOS Y TALLAS
            $prendasProcesadas = [];
            if ($cotizacion->prendas) {
                foreach ($cotizacion->prendas as $prenda) {
                    $reflectivo = $prenda->reflectivo->first();  // ✅ Obtener el primer (único) reflectivo
                    $fotos = $reflectivo?->fotos ?? [];
                    
                    // Procesar tallas con sus cantidades
                    $tallas = [];
                    $cantidades = [];
                    if ($prenda->tallas) {
                        foreach ($prenda->tallas as $talla) {
                            $tallas[] = $talla->talla;
                            $cantidades[$talla->talla] = $talla->cantidad;
                        }
                    }

                    // Obtener género de variantes
                    $genero = null;
                    $variantes = null;
                    if ($prenda->variantes) {
                        foreach ($prenda->variantes as $variante) {
                            if ($variante->genero_id) {
                                $generoObj = \App\Models\GeneroPrenda::find($variante->genero_id);
                                $genero = $generoObj ? strtolower($generoObj->nombre) : null;
                            }
                        }
                        $variantes = $prenda->variantes->toArray();
                    }
                    
                    Log::info('📦 Prenda con reflectivo', [
                        'prenda_id' => $prenda->id,
                        'prenda_nombre' => $prenda->nombre_producto,
                        'tallas_count' => count($tallas),
                        'genero' => $genero,
                        'reflectivo_id' => $reflectivo?->id,
                        'fotos_count' => count($fotos),
                    ]);

                    $prendasProcesadas[] = [
                        'id' => $prenda->id,
                        'tipo' => $prenda->nombre_producto,
                        'descripcion' => $prenda->descripcion ?? '',
                        'tallas' => $tallas,                    // ✅ Array de tallas
                        'cantidades' => $cantidades,           // ✅ Cantidades por talla
                        'genero' => $genero,                   // ✅ Género (dama/caballero)
                        'variantes' => $variantes,             // ✅ Todas las variantes
                        'reflectivo' => $reflectivo ? [
                            'id' => $reflectivo->id,
                            'descripcion' => $reflectivo->descripcion,
                            'tipo_venta' => $reflectivo->tipo_venta,
                            'ubicacion' => $reflectivo->ubicacion,
                            'observaciones_generales' => $reflectivo->observaciones_generales,
                            'fotos' => $fotos->toArray(),  // ✅ Fotos de ESTA prenda
                        ] : null,
                    ];
                }
            }

            Log::info('✅ CotizacionController@getReflectivoForEdit: Cotización RF cargada para editar', [
                'cotizacion_id' => $cotizacion->id,
                'prendas_count' => count($prendasProcesadas),
                'prendas_con_reflectivo' => collect($prendasProcesadas)->filter(fn($p) => $p['reflectivo'] !== null)->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'cotizacion' => $cotizacion->toArray(),
                    'prendas' => $prendasProcesadas,  // ✅ Prendas con tallas, género y reflectivos
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
                'prendas.reflectivo.fotos',  // ✅ Cargar reflectivo de cada prenda con sus fotos
                'logoCotizacion.fotos',
                'reflectivoCotizacion.fotos',  // Mantener para compatibilidad con cotizaciones antiguas
                'tipoCotizacion'
            ])->findOrFail($id);

            // Verificar que el usuario es propietario
            if ($cotizacion->asesor_id !== Auth::id()) {
                abort(403, 'No tienes permiso para ver esta cotización');
            }

            // Obtener logo si existe
            $logo = $cotizacion->logoCotizacion;

            // Debug: Log reflectivo fotos per prenda
            $prendasDebug = [];
            if ($cotizacion->prendas) {
                foreach ($cotizacion->prendas as $prenda) {
                    $reflectivo = $prenda->reflectivo ? $prenda->reflectivo->first() : null;
                    $prendasDebug[] = [
                        'prenda_id' => $prenda->id,
                        'nombre' => $prenda->nombre_producto,
                        'tiene_reflectivo' => $reflectivo ? 'Sí' : 'No',
                        'reflectivo_id' => $reflectivo ? $reflectivo->id : null,
                        'fotos_count' => $reflectivo && $reflectivo->fotos ? $reflectivo->fotos->count() : 0,
                        'fotos_ids' => $reflectivo && $reflectivo->fotos ? $reflectivo->fotos->pluck('id')->toArray() : []
                    ];
                }
            }

            Log::info('CotizacionController@showView: Cotización cargada', [
                'cotizacion_id' => $cotizacion->id,
                'prendas_count' => $cotizacion->prendas ? count($cotizacion->prendas) : 0,
                'prendas_debug' => $prendasDebug,
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
     * Borrar imagen de prenda específica
     */
    public function borrarImagenPrenda(Request $request, $id)
    {
        try {
            $fotoId = $request->input('foto_id');
            
            Log::info('🗑️ Borrando imagen de prenda:', ['foto_id' => $fotoId, 'cotizacion_id' => $id]);
            
            // Buscar y borrar la imagen
            $foto = \App\Models\PrendaFotoCot::find($fotoId);
            
            if (!$foto) {
                Log::warning('⚠️ Imagen no encontrada:', ['foto_id' => $fotoId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Imagen no encontrada'
                ], 404);
            }
            
            // Borrar archivo del storage
            if ($foto->ruta_original && \Storage::disk('public')->exists($foto->ruta_original)) {
                \Storage::disk('public')->delete($foto->ruta_original);
            }
            
            // Borrar la imagen de la BD
            $foto->forceDelete();
            
            Log::info('✅ Imagen de prenda borrada exitosamente:', ['foto_id' => $fotoId]);
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen borrada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error al borrar imagen de prenda:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al borrar imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Borrar imagen de tela específica
     */
    public function borrarImagenTela(Request $request, $id)
    {
        try {
            $fotoId = $request->input('foto_id');
            
            Log::info('🗑️ Borrando imagen de tela:', ['foto_id' => $fotoId, 'cotizacion_id' => $id]);
            
            // Buscar y borrar la imagen
            $foto = \App\Models\PrendaTelaFotoCot::find($fotoId);
            
            if (!$foto) {
                Log::warning('⚠️ Imagen de tela no encontrada:', ['foto_id' => $fotoId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Imagen no encontrada'
                ], 404);
            }
            
            // Borrar archivo del storage
            if ($foto->ruta_original && \Storage::disk('public')->exists($foto->ruta_original)) {
                \Storage::disk('public')->delete($foto->ruta_original);
            }
            
            // Borrar la imagen de la BD
            $foto->forceDelete();
            
            Log::info('✅ Imagen de tela borrada exitosamente:', ['foto_id' => $fotoId]);
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen borrada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error al borrar imagen de tela:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al borrar imagen: ' . $e->getMessage()
            ], 500);
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
            
            // Mapear productos_friendly -> prendas para compatibilidad frontend
            $prendasRecibidas = $request->input('prendas', $request->input('productos_friendly', []));
            $especificacionesRecibidas = $request->input('especificaciones', []);
            
            // Las especificaciones pueden venir como string JSON o array desde el frontend
            if (is_string($especificacionesRecibidas)) {
                $especificacionesRecibidas = json_decode($especificacionesRecibidas, true) ?? [];
            } elseif (!is_array($especificacionesRecibidas)) {
                $especificacionesRecibidas = [];
            }
            
            // Asegurar que todas las categorías existan, incluso si están vacías
            $categoriasRequeridas = ['forma_pago', 'disponibilidad', 'regimen', 'se_ha_vendido', 'ultima_venta', 'flete'];
            foreach ($categoriasRequeridas as $categoria) {
                if (!isset($especificacionesRecibidas[$categoria])) {
                    $especificacionesRecibidas[$categoria] = [];
                }
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

            // Generar número de cotización si es envío (no borrador)
            $numeroCotizacion = null;
            if (!$esBorrador) {
                // Usar el servicio de generación segura de números (con database locks)
                $usuarioId = \App\Domain\Shared\ValueObjects\UserId::crear(Auth::id());
                $numeroCotizacion = $this->generarNumeroCotizacionService->generarProxNumeroCotizacion($usuarioId);
                
                Log::info('CotizacionController@store: Número de cotización generado con servicio seguro', [
                    'usuario_id' => Auth::id(),
                    'numero_secuencial' => $numeroCotizacion,
                ]);
            }

            Log::info('CotizacionController@store: Lógica aplicada', [
                'accion' => $accion,
                'es_borrador_recibido' => $request->input('es_borrador'),
                'es_borrador_final' => $esBorrador,
                'estado' => $estado,
                'numero_cotizacion' => $numeroCotizacion,
                'cliente_id' => $clienteId,
            ]);

            $dto = CrearCotizacionDTO::desdeArray([
                'usuario_id' => Auth::id(),
                'tipo' => $request->input('tipo_cotizacion', 'P'),
                'cliente_id' => $clienteId,
                'prendas' => $request->input('prendas', []),
                'logo' => $request->input('logo', []),
                'tipo_venta' => $request->input('tipo_venta', 'M'),
                'especificaciones' => $especificacionesRecibidas,
                'es_borrador' => $esBorrador,
                'estado' => $estado,
                'numero_cotizacion' => $numeroCotizacion,
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
            $datosActualizar = [
                'cliente_id' => $clienteId,
                'tipo_venta' => $request->input('tipo_venta'),
            ];
            
            // Solo actualizar especificaciones si se envían nuevas, si no mantener las existentes
            $especificacionesNuevas = $request->input('especificaciones', []);
            
            // Decodificar si viene como string JSON
            if (is_string($especificacionesNuevas)) {
                $especificacionesNuevas = json_decode($especificacionesNuevas, true) ?? [];
            }
            
            if (!empty($especificacionesNuevas)) {
                // Asegurar que todas las categorías existan, incluso si están vacías
                $categoriasRequeridas = ['forma_pago', 'disponibilidad', 'regimen', 'se_ha_vendido', 'ultima_venta', 'flete'];
                foreach ($categoriasRequeridas as $categoria) {
                    if (!isset($especificacionesNuevas[$categoria])) {
                        $especificacionesNuevas[$categoria] = [];
                    }
                }
                
                $datosActualizar['especificaciones'] = $especificacionesNuevas;
                Log::info('Actualizando especificaciones', ['count' => count($especificacionesNuevas)]);
            } else {
                Log::info('No se enviaron especificaciones nuevas, manteniendo las existentes');
            }
            
            $cotizacion->update($datosActualizar);

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
                
                // Procesar fotos guardadas (rutas desde el frontend)
                $fotosGuardadas = $request->input("prendas.{$index}.fotos_guardadas") ?? [];
                if (!is_array($fotosGuardadas)) {
                    $fotosGuardadas = [];
                }
                
                if (!empty($fotosGuardadas)) {
                    $orden = count($fotosArchivos) + 1; // Continuar con el orden
                    foreach ($fotosGuardadas as $rutaGuardada) {
                        if ($rutaGuardada && is_string($rutaGuardada)) {
                            // Limpiar ruta: remover /storage/ del principio si existe
                            $rutaLimpia = $rutaGuardada;
                            if (strpos($rutaLimpia, '/storage/') === 0) {
                                $rutaLimpia = substr($rutaLimpia, 9); // Remover "/storage/" (9 caracteres)
                            }
                            
                            $prendaModel->fotos()->create([
                                'ruta_original' => $rutaLimpia,
                                'ruta_webp' => $rutaLimpia,
                                'orden' => $orden,
                            ]);
                            $orden++;
                            
                            Log::info('Foto de prenda guardada (ruta existente)', ['prenda_id' => $prendaModel->id, 'ruta' => $rutaGuardada, 'orden' => $orden - 1]);
                        }
                    }
                }

                // Procesar imágenes de telas - NUEVA LÓGICA
                // Obtener telas_multiples del JSON de variantes para asociar color_id y tela_id
                $variante = $prendaModel->variantes->first();
                $telasMultiples = [];
                if ($variante && $variante->telas_multiples) {
                    $telasMultiples = is_array($variante->telas_multiples) 
                        ? $variante->telas_multiples 
                        : json_decode($variante->telas_multiples, true);
                }
                
                Log::info('🧵 Telas multiples de variante:', [
                    'prenda_id' => $prendaModel->id,
                    'telas_count' => count($telasMultiples),
                    'telas' => $telasMultiples,
                ]);
                
                // PROCESAR TODAS LAS TELAS DE telas_multiples (CON O SIN FOTOS)
                foreach ($telasMultiples as $telaInfo) {
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
                            Log::info('✅ Color creado', ['color' => $telaInfo['color'], 'id' => $colorId]);
                        } else {
                            $colorId = $color->id;
                        }
                    }
                    
                    // Buscar o crear tela
                    $telaId = null;
                    if (!empty($telaInfo['tela'])) {
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
                            Log::info('✅ Tela creada', ['tela' => $telaInfo['tela'], 'id' => $telaId]);
                        } else {
                            $telaId = $tela->id;
                        }
                    }

                    // GUARDAR REGISTRO EN prenda_telas_cot
                    if ($colorId && $telaId && $variante) {
                        // Verificar si ya existe
                        $existente = DB::table('prenda_telas_cot')
                            ->where('prenda_cot_id', $prendaModel->id)
                            ->where('variante_prenda_cot_id', $variante->id)
                            ->where('color_id', $colorId)
                            ->where('tela_id', $telaId)
                            ->first();
                        
                        if (!$existente) {
                            $prendaTelaCotId = DB::table('prenda_telas_cot')->insertGetId([
                                'prenda_cot_id' => $prendaModel->id,
                                'variante_prenda_cot_id' => $variante->id,
                                'color_id' => $colorId,
                                'tela_id' => $telaId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            
                            Log::info('✅ Registro guardado en prenda_telas_cot (desde telas_multiples)', [
                                'prenda_telas_cot_id' => $prendaTelaCotId,
                                'prenda_id' => $prendaModel->id,
                                'variante_id' => $variante->id,
                                'color_id' => $colorId,
                                'tela_id' => $telaId,
                                'color' => $telaInfo['color'] ?? '',
                                'tela' => $telaInfo['tela'] ?? '',
                                'referencia' => $telaInfo['referencia'] ?? '',
                            ]);
                        } else {
                            Log::info('ℹ️ Registro ya existe en prenda_telas_cot', [
                                'prenda_id' => $prendaModel->id,
                                'color' => $telaInfo['color'] ?? '',
                                'tela' => $telaInfo['tela'] ?? '',
                            ]);
                        }
                    }
                }
                
                // Acceder a la estructura anidada: prendas[index][telas][telaIndex][fotos][]
                $allFiles = $request->allFiles();
                if (isset($allFiles['prendas']) && is_array($allFiles['prendas']) && isset($allFiles['prendas'][$index])) {
                    $prendaFiles = $allFiles['prendas'][$index];
                    
                    if (isset($prendaFiles['telas']) && is_array($prendaFiles['telas'])) {
                        foreach ($prendaFiles['telas'] as $telaIndex => $telaData) {
                            if (isset($telaData['fotos']) && is_array($telaData['fotos'])) {
                                Log::info('🖼️ Encontrado grupo de fotos de tela', [
                                    'prenda_index' => $index,
                                    'tela_index' => $telaIndex,
                                    'cantidad_archivos' => count($telaData['fotos']),
                                ]);

                                // Obtener info de tela del JSON telas_multiples (solo para logging)
                                $telaInfo = null;
                                foreach ($telasMultiples as $tm) {
                                    if (isset($tm['indice']) && $tm['indice'] == $telaIndex) {
                                        $telaInfo = $tm;
                                        break;
                                    }
                                }
                                
                                if (!$telaInfo) {
                                    Log::warning('⚠️ No se encontró info de tela en telas_multiples', [
                                        'tela_index' => $telaIndex,
                                    ]);
                                    $telaInfo = []; // Continuar de todas formas
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
                                        Log::info('✅ Color creado', ['color' => $telaInfo['color'], 'id' => $colorId]);
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
                                        Log::info('✅ Tela creada', ['tela' => $telaInfo['tela'], 'id' => $telaId]);
                                    } else {
                                        $telaId = $tela->id;
                                    }
                                }

                                // GUARDAR REGISTRO EN prenda_telas_cot (solo una vez por cada tela)
                                $prendaTelaCotId = null;
                                if ($colorId && $telaId) {
                                    // Verificar si ya existe un registro con los mismos IDs
                                    $existente = DB::table('prenda_telas_cot')
                                        ->where('prenda_cot_id', $prendaModel->id)
                                        ->where('variante_prenda_cot_id', $variante->id)
                                        ->where('color_id', $colorId)
                                        ->where('tela_id', $telaId)
                                        ->first();
                                    
                                    if (!$existente) {
                                        $prendaTelaCotId = DB::table('prenda_telas_cot')->insertGetId([
                                            'prenda_cot_id' => $prendaModel->id,
                                            'variante_prenda_cot_id' => $variante->id,
                                            'color_id' => $colorId,
                                            'tela_id' => $telaId,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                        
                                        Log::info('✅ Registro guardado en prenda_telas_cot', [
                                            'prenda_telas_cot_id' => $prendaTelaCotId,
                                            'prenda_id' => $prendaModel->id,
                                            'variante_id' => $variante->id,
                                            'color_id' => $colorId,
                                            'tela_id' => $telaId,
                                        ]);
                                    }
                                }

                                foreach ($telaData['fotos'] as $fotoIndex => $archivoFoto) {
                                    if ($archivoFoto && $archivoFoto->isValid()) {
                                        try {
                                            $ruta = $this->procesarImagenesService->procesarImagenTela(
                                                $archivoFoto,
                                                $cotizacionId,
                                                $prendaModel->id
                                            );

                                            // Guardar foto de tela (sin color_id, tela_id, referencia porque están en telas_multiples JSON)
                                            \DB::table('prenda_tela_fotos_cot')->insert([
                                                'prenda_cot_id' => $prendaModel->id,
                                                'color_id' => $colorId,
                                                'tela_id' => $telaId,
                                                'referencia' => $telaInfo['referencia'] ?? '',
                                                'ruta_original' => $ruta,
                                                'ruta_webp' => $ruta,
                                                'ruta_miniatura' => null,
                                                'orden' => $fotoIndex + 1,
                                                'ancho' => null,
                                                'alto' => null,
                                                'tamaño' => null,
                                                'created_at' => now(),
                                                'updated_at' => now(),
                                            ]);

                                            Log::info('✅ Foto de tela guardada en BD', [
                                                'prenda_id' => $prendaModel->id,
                                                'tela_index' => $telaIndex,
                                                'color' => $telaInfo['color'] ?? '',
                                                'tela' => $telaInfo['tela'] ?? '',
                                                'referencia' => $telaInfo['referencia'] ?? '',
                                                'ruta' => $ruta,
                                            ]);
                                        } catch (\Exception $e) {
                                            Log::error('❌ Error guardando foto de tela', [
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
                    'tipo_venta' => $request->input('tipo_venta_paso3') ?? $request->input('tipo_venta') ?? null,
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
            
            // Procesar IDs de fotos de logo existentes (logo_fotos_existentes[])
            // Estas son las fotos que ya están en BD y que el usuario quiere conservar/copiar
            $fotoLogosExistentes = $request->input('logo_fotos_existentes', []);
            if (!is_array($fotoLogosExistentes)) {
                $fotoLogosExistentes = [];
            }
            
            if (!empty($fotoLogosExistentes)) {
                // Deduplicar IDs
                $fotoLogosExistentes = array_unique($fotoLogosExistentes);
                $orden = 1;
                
                foreach ($fotoLogosExistentes as $fotoIdExistente) {
                    if ($fotoIdExistente && is_string($fotoIdExistente)) {
                        // Buscar la foto existente
                        $fotoExistente = \App\Models\LogoFotoCot::findOrFail($fotoIdExistente);
                        
                        // Limpiar rutas: remover /storage/ del principio si existe
                        $rutaOriginal = $fotoExistente->ruta_original;
                        if (strpos($rutaOriginal, '/storage/') === 0) {
                            $rutaOriginal = substr($rutaOriginal, 9); // Remover "/storage/" (9 caracteres)
                        }
                        
                        $rutaWebp = $fotoExistente->ruta_webp;
                        if (strpos($rutaWebp, '/storage/') === 0) {
                            $rutaWebp = substr($rutaWebp, 9); // Remover "/storage/" (9 caracteres)
                        }
                        
                        // Crear nuevo registro con la misma ruta en la nueva cotización
                        try {
                            $fotoCopiadaCreada = $logoCotizacion->fotos()->create([
                                'ruta_original' => $rutaOriginal,
                                'ruta_webp' => $rutaWebp,
                                'orden' => $orden,
                            ]);
                            
                            Log::info('✅ Foto de logo reutilizada (copiada)', [
                                'nuevo_foto_id' => $fotoCopiadaCreada->id,
                                'foto_original_id' => $fotoIdExistente,
                                'logo_cotizacion_id' => $logoCotizacion->id,
                                'ruta' => $fotoExistente->ruta_webp,
                                'orden' => $orden
                            ]);
                            
                            $orden++;
                        } catch (\Exception $e) {
                            Log::warning('⚠️ Error al reutilizar foto de logo', [
                                'foto_id' => $fotoIdExistente,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
                
                Log::info('Fotos de logo existentes reutilizadas:', [
                    'count' => count($fotoLogosExistentes),
                    'ids' => $fotoLogosExistentes,
                    'fotos_creadas' => $orden - 1
                ]);
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
                'tipo_venta_reflectivo' => 'nullable|in:M,D,X',
                'prendas' => 'required|string', // Ahora acepta string JSON
                'especificaciones' => 'nullable|string',
                'descripcion_reflectivo' => 'required|string',
                'ubicaciones_reflectivo' => 'nullable',
                'observaciones_generales' => 'nullable',
                'imagenes_reflectivo.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            // Decodificar prendas del JSON string
            $prendas = json_decode($validated['prendas'], true);
            
            Log::info('🔍 DEBUG storeReflectivo - Prendas recibidas:', [
                'prendas_json' => $validated['prendas'],
                'prendas_decoded' => $prendas,
                'prendas_count' => is_array($prendas) ? count($prendas) : 0,
                'first_prenda' => is_array($prendas) && count($prendas) > 0 ? $prendas[0] : null,
            ]);
            
            if (!is_array($prendas) || count($prendas) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prendas inválidas. Debe ser un array con al menos 1 prenda.',
                    'errores' => ['prendas' => ['Array inválido o vacío']]
                ], 422);
            }

            // Decodificar especificaciones del JSON string
            $especificaciones = [];
            if (!empty($validated['especificaciones'])) {
                if (is_string($validated['especificaciones'])) {
                    $especificaciones = json_decode($validated['especificaciones'], true) ?? [];
                } elseif (is_array($validated['especificaciones'])) {
                    $especificaciones = $validated['especificaciones'];
                }
            }
            
            // Asegurar que todas las categorías existan, incluso si están vacías
            $categoriasRequeridas = ['forma_pago', 'disponibilidad', 'regimen', 'se_ha_vendido', 'ultima_venta', 'flete'];
            foreach ($categoriasRequeridas as $categoria) {
                if (!isset($especificaciones[$categoria])) {
                    $especificaciones[$categoria] = [];
                }
            }
            
            Log::info('🔍 DEBUG storeReflectivo - Especificaciones recibidas:', [
                'especificaciones_raw' => $validated['especificaciones'] ?? null,
                'especificaciones_decoded' => $especificaciones,
                'especificaciones_count' => count($especificaciones),
                'especificaciones_keys' => array_keys($especificaciones),
                'forma_pago' => $especificaciones['forma_pago'] ?? 'NO EXISTE',
                'disponibilidad' => $especificaciones['disponibilidad'] ?? 'NO EXISTE',
                'regimen' => $especificaciones['regimen'] ?? 'NO EXISTE',
                'se_ha_vendido' => $especificaciones['se_ha_vendido'] ?? 'NO EXISTE',
                'ultima_venta' => $especificaciones['ultima_venta'] ?? 'NO EXISTE',
                'flete' => $especificaciones['flete'] ?? 'NO EXISTE',
            ]);

            DB::beginTransaction();

            try {
                // Obtener o crear cliente
                $cliente = $this->obtenerOCrearClienteService->ejecutar($validated['cliente']);
                Log::info('✅ Cliente obtenido/creado', ['cliente_id' => $cliente->id]);

                // Determinar estado
                $esBorrador = ($validated['action'] === 'borrador');
                $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR';

                // Generar número de cotización SIEMPRE (para poder identificar el borrador luego)
                $usuarioId = Auth::id();
                $numeroCotizacion = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId);

                // Crear cotización base sin prendas (tipo RF = Reflectivo)
                $cotizacion = \App\Models\Cotizacion::create([
                    'asesor_id' => Auth::id(),
                    'cliente_id' => $cliente->id,
                    'numero_cotizacion' => $numeroCotizacion,
                    'tipo_cotizacion_id' => $this->obtenerTipoCotizacionId('RF'),
                    'tipo_venta' => $validated['tipo_venta_reflectivo'] ?? 'M',
                    'fecha_inicio' => $validated['fecha'],
                    'especificaciones' => $especificaciones,
                    'es_borrador' => $esBorrador,
                    'estado' => $estado,
                    'fecha_envio' => !$esBorrador ? \Carbon\Carbon::now('America/Bogota') : null,
                ]);

                Log::info('✅ Cotización RF creada', [
                    'cotizacion_id' => $cotizacion->id,
                    'especificaciones_guardadas' => $cotizacion->especificaciones,
                    'especificaciones_type' => gettype($cotizacion->especificaciones),
                    'especificaciones_count' => is_array($cotizacion->especificaciones) ? count($cotizacion->especificaciones) : 0,
                ]);

                // ✅ PROCESAR PRENDAS Y CREAR UN REFLECTIVO POR CADA PRENDA
                $imagenesGuardadas = [];
                
                Log::info('🔵 INICIANDO LOOP DE PRENDAS', [
                    'prendas_totales' => count($prendas),
                    'todos_campos_request' => array_keys($request->allFiles()),
                ]);
                
                if (!empty($prendas)) {
                    foreach ($prendas as $prendaIndex => $prenda) {
                        Log::info("🔵 PROCESANDO PRENDA {$prendaIndex}", [
                            'prenda_tipo' => $prenda['tipo'] ?? 'N/A',
                            'prenda_es_array' => is_array($prenda),
                        ]);
                        
                        // La prenda ya está decodificada como array
                        if (is_array($prenda)) {
                            // 1. Guardar prenda en prendas_cot
                            $prendaCot = \App\Models\PrendaCot::create([
                                'cotizacion_id' => $cotizacion->id,
                                'nombre_producto' => $prenda['tipo'] ?? $prenda['nombre'] ?? 'Prenda',
                                'cantidad' => 1,
                                'descripcion' => $prenda['descripcion'] ?? '',
                            ]);

                            // 2. Guardar tallas en prenda_tallas_cot con cantidades
                            if (!empty($prenda['tallas']) && is_array($prenda['tallas'])) {
                                $cantidades = $prenda['cantidades'] ?? [];
                                foreach ($prenda['tallas'] as $talla) {
                                    $cantidad = $cantidades[$talla] ?? 1; // Usar cantidad del array, o 1 por defecto
                                    \App\Models\PrendaTallaCot::create([
                                        'prenda_cot_id' => $prendaCot->id,
                                        'talla' => $talla,
                                        'cantidad' => (int)$cantidad,
                                    ]);
                                }
                                Log::info('✅ Tallas guardadas para prenda', [
                                    'prenda_cot_id' => $prendaCot->id,
                                    'tallas_count' => count($prenda['tallas']),
                                    'tallas' => $prenda['tallas'],
                                    'cantidades' => $cantidades
                                ]);
                            }

                            // 2b. ✅ GUARDAR GÉNERO EN prenda_variantes_cot SI EXISTE
                            if (!empty($prenda['genero'])) {
                                // Mapear valores del frontend a IDs de la tabla generos_prenda
                                $generoId = null;
                                if ($prenda['genero'] === 'dama') {
                                    // Buscar género Dama en generos_prenda
                                    $generoId = \DB::table('generos_prenda')
                                        ->where(\DB::raw('LOWER(nombre)'), 'dama')
                                        ->value('id');
                                } elseif ($prenda['genero'] === 'caballero') {
                                    // Buscar género Caballero en generos_prenda
                                    $generoId = \DB::table('generos_prenda')
                                        ->where(\DB::raw('LOWER(nombre)'), 'caballero')
                                        ->value('id');
                                }
                                
                                if ($generoId) {
                                    // Crear o actualizar variante con el género
                                    \App\Models\PrendaVarianteCot::updateOrCreate(
                                        ['prenda_cot_id' => $prendaCot->id],
                                        ['genero_id' => $generoId]
                                    );
                                    Log::info('✅ Género guardado en prenda_variantes_cot', [
                                        'prenda_cot_id' => $prendaCot->id,
                                        'genero' => $prenda['genero'],
                                        'genero_id' => $generoId
                                    ]);
                                }
                            }

                            // 3. ✅ CREAR REFLECTIVO ESPECÍFICO PARA ESTA PRENDA
                            // Obtener ubicaciones de esta prenda
                            $ubicacionesDePrenda = $prenda['ubicaciones'] ?? [];
                            if (is_string($ubicacionesDePrenda)) {
                                $ubicacionesDePrenda = json_decode($ubicacionesDePrenda, true) ?? [];
                            }

                            // Procesar observaciones (si existen para esta prenda)
                            $observacionesDePrenda = $prenda['observaciones'] ?? [];
                            if (is_string($observacionesDePrenda)) {
                                $observacionesDePrenda = json_decode($observacionesDePrenda, true) ?? [];
                            }

                            // Crear reflectivo vinculado a esta prenda específica
                            $reflectivo = \App\Models\ReflectivoCotizacion::create([
                                'cotizacion_id' => $cotizacion->id,
                                'prenda_cot_id' => $prendaCot->id,  // ✅ Vinculado a la prenda
                                'descripcion' => $validated['descripcion_reflectivo'],
                                'tipo_venta' => $validated['tipo_venta_reflectivo'] ?? null,
                                'ubicacion' => json_encode($ubicacionesDePrenda),
                                'observaciones_generales' => json_encode($observacionesDePrenda),
                                'imagenes' => json_encode([]),
                            ]);

                            Log::info('✅ ReflectivoCotizacion creado para prenda', [
                                'reflectivo_id' => $reflectivo->id,
                                'prenda_cot_id' => $prendaCot->id,
                                'ubicaciones_count' => count($ubicacionesDePrenda)
                            ]);

                            // 4. ✅ PROCESAR IMÁGENES DE ESTA PRENDA ESPECÍFICA
                            // Las imágenes vienen con el nombre: imagenes_reflectivo_prenda_{index}[] o imagenes_reflectivo_prenda_{index}
                            $campoImagenes = "imagenes_reflectivo_prenda_{$prendaIndex}";
                            
                            Log::info('🔍 BUSCANDO IMÁGENES', [
                                'prenda_index' => $prendaIndex,
                                'campo_esperado' => $campoImagenes,
                                'todos_archivos' => array_keys($request->allFiles()),
                                'has_file_sin_brackets' => $request->hasFile($campoImagenes) ? 'SÍ' : 'NO',
                                'has_file_con_brackets' => $request->hasFile($campoImagenes . '[]') ? 'SÍ' : 'NO',
                            ]);
                            
                            // Intentar obtener archivos con o sin []
                            $archivos = $request->file($campoImagenes);
                            if (!$archivos) {
                                $archivos = $request->file($campoImagenes . '[]');
                            }
                            
                            if ($archivos) {
                                Log::info('✅ ENCONTRADAS IMÁGENES PARA PRENDA', [
                                    'prenda_index' => $prendaIndex,
                                    'campo' => $campoImagenes,
                                    'cantidad' => is_array($archivos) ? count($archivos) : 1,
                                ]);
                                
                                // Normalizar a array
                                if (!is_array($archivos)) {
                                    $archivos = [$archivos];
                                }
                                
                                $orden = 1;
                                foreach ($archivos as $archivo) {
                                    if ($archivo && $archivo->isValid()) {
                                        // Guardar archivo
                                        $ruta = $archivo->store('cotizaciones/reflectivo', 'public');
                                        
                                        // Guardar en tabla reflectivo_fotos_cotizacion vinculada a ESTE reflectivo
                                        $foto = \App\Models\ReflectivoCotizacionFoto::create([
                                            'reflectivo_cotizacion_id' => $reflectivo->id,
                                            'ruta_original' => $ruta,
                                            'ruta_webp' => $ruta,
                                            'orden' => $orden++,
                                        ]);
                                        
                                        $imagenesGuardadas[] = $foto->id;

                                        Log::info('📸 Imagen guardada para prenda', [
                                            'ruta' => $ruta,
                                            'prenda_index' => $prendaIndex,
                                            'prenda_cot_id' => $prendaCot->id,
                                            'reflectivo_id' => $reflectivo->id,
                                            'foto_id' => $foto->id,
                                        ]);
                                    }
                                }
                            } else {
                                Log::info('⚠️ NO HAY IMÁGENES PARA ESTA PRENDA', [
                                    'campo' => $campoImagenes,
                                    'prenda_index' => $prendaIndex,
                                    'todos_los_archivos' => json_encode(array_keys($request->allFiles())),
                                ]);
                            }
                        } else {
                            Log::warning('❌ PRENDA NO ES ARRAY', [
                                'prenda_index' => $prendaIndex,
                                'prenda_type' => gettype($prenda),
                                'prenda_value' => $prenda,
                            ]);
                        }
                    }
                    $prendasCount = is_array($prendas) ? count($prendas) : 0;
                    Log::info('✅ LOOP COMPLETADO - Prendas y reflectivos guardados', [
                        'cotizacion_id' => $cotizacion->id,
                        'prendas_count' => $prendasCount,
                        'imagenes_totales_guardadas' => count($imagenesGuardadas),
                    ]);
                } else {
                    Log::warning('⚠️ NO HAY PRENDAS PARA PROCESAR');
                }

                DB::commit();

                // Recargar cotización con relaciones (incluyendo fotos)
                $cotizacionCompleta = \App\Models\Cotizacion::with([
                    'cliente',
                    'reflectivoCotizacion.fotos',
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

            // ✅ Decodificar JSON strings cuando vienen de FormData con _method=PUT
            if ($request->has('prendas') && is_string($request->input('prendas'))) {
                $request->merge(['prendas' => json_decode($request->input('prendas'), true)]);
            }
            if ($request->has('observaciones_generales') && is_string($request->input('observaciones_generales'))) {
                $request->merge(['observaciones_generales' => json_decode($request->input('observaciones_generales'), true)]);
            }
            if ($request->has('imagenes_a_eliminar') && is_string($request->input('imagenes_a_eliminar'))) {
                $request->merge(['imagenes_a_eliminar' => json_decode($request->input('imagenes_a_eliminar'), true)]);
            }

            // Validar datos
            $validated = $request->validate([
                'cliente' => 'required|string|max:255',
                'asesora' => 'nullable|string|max:255',
                'fecha' => 'required|date',
                'action' => 'required|in:borrador,enviar',
                'tipo' => 'required|in:RF',
                'tipo_venta_reflectivo' => 'nullable|in:M,D,X',
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

                // Procesar especificaciones - PRESERVAR LAS EXISTENTES SI NO HAY NUEVAS
                $especificacionesExistentes = $cotizacion->especificaciones 
                    ? (is_string($cotizacion->especificaciones) ? json_decode($cotizacion->especificaciones, true) : $cotizacion->especificaciones)
                    : [];
                
                $especificacionesArray = $especificacionesExistentes;
                
                if ($request->has('especificaciones')) {
                    $especificacionesData = $validated['especificaciones'] ?? '{}';
                    if (is_string($especificacionesData)) {
                        $nuevasEspecificaciones = json_decode($especificacionesData, true) ?? [];
                    } else {
                        $nuevasEspecificaciones = is_array($especificacionesData) ? $especificacionesData : [];
                    }
                    
                    Log::info('🔍 DEBUG Especificaciones en updateReflectivo', [
                        'especificaciones_data_recibida' => $especificacionesData,
                        'nuevas_especificaciones_parseadas' => $nuevasEspecificaciones,
                        'especificaciones_existentes' => $especificacionesExistentes,
                        'es_vacio' => empty($nuevasEspecificaciones),
                    ]);
                    
                    // Solo actualizar si hay especificaciones reales (no solo {} o [])
                    if (!empty($nuevasEspecificaciones) && $nuevasEspecificaciones !== []) {
                        // Asegurar que todas las categorías existan, incluso si están vacías
                        $categoriasRequeridas = ['forma_pago', 'disponibilidad', 'regimen', 'se_ha_vendido', 'ultima_venta', 'flete'];
                        foreach ($categoriasRequeridas as $categoria) {
                            if (!isset($nuevasEspecificaciones[$categoria])) {
                                $nuevasEspecificaciones[$categoria] = [];
                            }
                        }
                        
                        $especificacionesArray = $nuevasEspecificaciones;
                        Log::info('✅ Actualizando especificaciones con nuevos datos');
                    } else {
                        Log::info('ℹ️ Preservando especificaciones existentes (nuevas están vacías)');
                    }
                }

                // Actualizar cotización
                $cotizacion->update([
                    'cliente_id' => $cliente->id,
                    'fecha_inicio' => $validated['fecha'],
                    'es_borrador' => $esBorrador,
                    'estado' => $estado,
                    'numero_cotizacion' => !$esBorrador && !$cotizacion->numero_cotizacion ? $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado(Auth::id()) : $cotizacion->numero_cotizacion,
                    'fecha_envio' => !$esBorrador && !$cotizacion->fecha_envio ? \Carbon\Carbon::now('America/Bogota') : $cotizacion->fecha_envio,
                    'especificaciones' => $especificacionesArray,
                    'tipo_venta' => $validated['tipo_venta_reflectivo'] ?? $cotizacion->tipo_venta ?? 'M',
                ]);

                Log::info('✅ Cotización RF actualizada', ['cotizacion_id' => $cotizacion->id]);

                // ✅ ACTUALIZAR PRENDAS Y SUS REFLECTIVOS (NUEVO SISTEMA)
                if (isset($validated['prendas']) && is_array($validated['prendas'])) {
                    // 1. ✅ PRESERVAR FOTOS EXISTENTES ANTES DE ELIMINAR
                    $fotosExistentesPorPrenda = [];
                    $prendasExistentes = \App\Models\PrendaCot::where('cotizacion_id', $cotizacion->id)
                        ->with('reflectivo.fotos')
                        ->get();
                    
                    foreach ($prendasExistentes as $index => $prendaExistente) {
                        $reflectivoExistente = $prendaExistente->reflectivo ? $prendaExistente->reflectivo->first() : null;
                        if ($reflectivoExistente && $reflectivoExistente->fotos && $reflectivoExistente->fotos->count() > 0) {
                            // Guardar las fotos con su índice de prenda
                            $fotosExistentesPorPrenda[$index] = $reflectivoExistente->fotos->map(function($foto) {
                                return [
                                    'ruta_original' => $foto->ruta_original,
                                    'ruta_webp' => $foto->ruta_webp,
                                    'orden' => $foto->orden,
                                ];
                            })->toArray();
                            
                            Log::info('💾 Preservando fotos de prenda', [
                                'prenda_index' => $index,
                                'fotos_count' => count($fotosExistentesPorPrenda[$index])
                            ]);
                        }
                    }
                    
                    // 2. Eliminar prendas existentes (esto también eliminará reflectivos por CASCADE)
                    \App\Models\PrendaCot::where('cotizacion_id', $cotizacion->id)->delete();
                    
                    // 2. Decodificar prendas si vienen como JSON string
                    $prendasArray = $validated['prendas'];
                    if (is_string($prendasArray)) {
                        $prendasArray = json_decode($prendasArray, true) ?? [];
                    }
                    
                    // 3. Crear nuevas prendas con sus reflectivos
                    foreach ($prendasArray as $prendaIndex => $prenda) {
                        // La prenda puede venir como JSON string o array
                        if (is_string($prenda)) {
                            $prenda = json_decode($prenda, true);
                        }
                        
                        if (is_array($prenda)) {
                            // Crear prenda
                            $prendaCot = \App\Models\PrendaCot::create([
                                'cotizacion_id' => $cotizacion->id,
                                'nombre_producto' => $prenda['tipo'] ?? $prenda['nombre'] ?? 'Prenda',
                                'cantidad' => 1,
                                'descripcion' => $prenda['descripcion'] ?? '',
                            ]);

                            // Guardar tallas si existen con cantidades
                            if (!empty($prenda['tallas']) && is_array($prenda['tallas'])) {
                                $cantidades = $prenda['cantidades'] ?? [];
                                foreach ($prenda['tallas'] as $talla) {
                                    $cantidad = $cantidades[$talla] ?? 1;
                                    \App\Models\PrendaTallaCot::create([
                                        'prenda_cot_id' => $prendaCot->id,
                                        'talla' => $talla,
                                        'cantidad' => (int)$cantidad,
                                    ]);
                                }
                            }

                            // Guardar género si existe en prenda_variantes_cot
                            if (!empty($prenda['genero'])) {
                                $generoId = null;
                                if ($prenda['genero'] === 'dama') {
                                    $generoId = \DB::table('generos_prenda')
                                        ->where(\DB::raw('LOWER(nombre)'), 'dama')
                                        ->value('id');
                                } elseif ($prenda['genero'] === 'caballero') {
                                    $generoId = \DB::table('generos_prenda')
                                        ->where(\DB::raw('LOWER(nombre)'), 'caballero')
                                        ->value('id');
                                }
                                
                                if ($generoId) {
                                    \App\Models\PrendaVarianteCot::updateOrCreate(
                                        ['prenda_cot_id' => $prendaCot->id],
                                        ['genero_id' => $generoId]
                                    );
                                }
                            }

                            // ✅ CREAR REFLECTIVO PARA ESTA PRENDA CON SUS UBICACIONES
                            $ubicacionesDePrenda = $prenda['ubicaciones'] ?? [];
                            if (is_string($ubicacionesDePrenda)) {
                                $ubicacionesDePrenda = json_decode($ubicacionesDePrenda, true) ?? [];
                            }

                            $observacionesDePrenda = $prenda['observaciones'] ?? [];
                            if (is_string($observacionesDePrenda)) {
                                $observacionesDePrenda = json_decode($observacionesDePrenda, true) ?? [];
                            }

                            $reflectivo = \App\Models\ReflectivoCotizacion::create([
                                'cotizacion_id' => $cotizacion->id,
                                'prenda_cot_id' => $prendaCot->id,
                                'descripcion' => $validated['descripcion_reflectivo'],
                                'tipo_venta' => $validated['tipo_venta_reflectivo'] ?? null,
                                'ubicacion' => json_encode($ubicacionesDePrenda),
                                'observaciones_generales' => json_encode($observacionesDePrenda),
                                'imagenes' => json_encode([]),
                            ]);

                            Log::info('✅ Prenda y reflectivo actualizados', [
                                'prenda_cot_id' => $prendaCot->id,
                                'reflectivo_id' => $reflectivo->id,
                                'ubicaciones_count' => count($ubicacionesDePrenda)
                            ]);

                            // ✅ PROCESAR IMÁGENES DE ESTA PRENDA
                            $campoImagenes = "imagenes_reflectivo_prenda_{$prendaIndex}";
                            $nuevasFotosGuardadas = false;
                            
                            if ($request->hasFile($campoImagenes)) {
                                // Hay nuevas fotos subidas - guardarlas
                                $orden = 1;
                                foreach ($request->file($campoImagenes) as $archivo) {
                                    if ($archivo && $archivo->isValid()) {
                                        $ruta = $archivo->store('cotizaciones/reflectivo', 'public');
                                        
                                        \App\Models\ReflectivoCotizacionFoto::create([
                                            'reflectivo_cotizacion_id' => $reflectivo->id,
                                            'ruta_original' => $ruta,
                                            'ruta_webp' => $ruta,
                                            'orden' => $orden++,
                                        ]);

                                        Log::info('📸 Nueva imagen guardada para prenda', [
                                            'prenda_index' => $prendaIndex,
                                            'reflectivo_id' => $reflectivo->id
                                        ]);
                                        $nuevasFotosGuardadas = true;
                                    }
                                }
                            } elseif (isset($fotosExistentesPorPrenda[$prendaIndex])) {
                                // No hay nuevas fotos - restaurar las fotos existentes
                                foreach ($fotosExistentesPorPrenda[$prendaIndex] as $fotoData) {
                                    \App\Models\ReflectivoCotizacionFoto::create([
                                        'reflectivo_cotizacion_id' => $reflectivo->id,
                                        'ruta_original' => $fotoData['ruta_original'],
                                        'ruta_webp' => $fotoData['ruta_webp'],
                                        'orden' => $fotoData['orden'],
                                    ]);
                                }
                                
                                Log::info('♻️ Fotos existentes restauradas para prenda', [
                                    'prenda_index' => $prendaIndex,
                                    'reflectivo_id' => $reflectivo->id,
                                    'fotos_count' => count($fotosExistentesPorPrenda[$prendaIndex])
                                ]);
                            }
                        }
                    }
                    Log::info('✅ Prendas y reflectivos actualizados', ['cotizacion_id' => $cotizacion->id, 'prendas_count' => count($prendasArray)]);
                }

                // NOTA: Ya no usamos un reflectivo global, cada prenda tiene el suyo

                DB::commit();

                // Recargar cotización con relaciones actualizadas
                $cotizacionCompleta = \App\Models\Cotizacion::with([
                    'cliente',
                    'prendas.reflectivo.fotos'
                ])->findOrFail($cotizacion->id);

                Log::info('✅ CotizacionController@updateReflectivo - Exitoso', [
                    'cotizacion_id' => $cotizacion->id,
                    'prendas_count' => $cotizacionCompleta->prendas->count(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Cotización de reflectivo actualizada exitosamente',
                    'data' => [
                        'cotizacion' => $cotizacionCompleta->toArray(),
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
     * Generar número de cotización único usando secuencia universal
     */
    private function generarNumeroCotizacion(): string
    {
        // Usar secuencia universal para TODAS las cotizaciones
        $secuencia = DB::table('numero_secuencias')
            ->where('nombre', 'cotizaciones_universal')
            ->first();
        
        if (!$secuencia) {
            // Fallback si no existe la secuencia
            $mes = date('m');
            $anio = date('y');
            $contador = \App\Models\Cotizacion::where('numero_cotizacion', 'like', "COT-{$anio}{$mes}-%")
                ->count() + 1;
            return sprintf('COT-%s%s-%04d', $anio, $mes, $contador);
        }
        
        // Incrementar el contador
        $nuevoNumero = $secuencia->numero_actual + 1;
        DB::table('numero_secuencias')
            ->where('nombre', 'cotizaciones_universal')
            ->update(['numero_actual' => $nuevoNumero]);
        
        // Generar número con formato
        $mes = date('m');
        $anio = date('y');
        return sprintf('COT-%s%s-%04d', $anio, $mes, $nuevoNumero);
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
            $cotizacion = \App\Models\Cotizacion::findOrFail($id);

            // Verificar que el borrador sea del asesor autenticado
            if ($cotizacion->asesor_id !== auth()->id()) {
                abort(403, 'No tienes permiso para editar este borrador');
            }

            // Verificar que sea un borrador
            if (!$cotizacion->es_borrador) {
                abort(400, 'Esta cotización no es un borrador');
            }

            // Mapeo de tipos a rutas de redirección
            $mapeoTipos = [
                1 => '/asesores/pedidos/create?tipo=PB&editar={id}',
                2 => '/asesores/cotizaciones/bordado/crear?editar={id}',
                3 => '/asesores/cotizaciones/prenda/{id}/editar',
                4 => null, // Reflectivo se maneja especialmente
            ];

            $tipoCotizacionId = $cotizacion->tipo_cotizacion_id ?? 3;

            // Si es Reflectivo (tipo 4), mostrar la vista
            if ($tipoCotizacionId === 4) {
                // ✅ Cargar datos completos del reflectivo CON TALLAS, FOTOS Y REFLECTIVO POR PRENDA
                $cotizacion->load([
                    'cliente',
                    'prendas',
                    'prendas.tallas',
                    'prendas.fotos',              // ✅ AGREGAR: Cargar fotos de prendas
                    'prendas.variantes',          // ✅ Cargar variantes (para genero_id)
                    'prendas.reflectivo.fotos'    // ✅ Cargar reflectivo de cada prenda
                ]);
                
                // Preparar datos iniciales en formato JSON
                $prendasConTallas = $cotizacion->prendas ? $cotizacion->prendas->map(function($prenda) {
                    $prendasArray = $prenda->toArray();
                    // Forzar inclusión de tallas
                    $prendasArray['tallas'] = $prenda->tallas ? $prenda->tallas->map(function($talla) {
                        return $talla->talla; // Solo el nombre de la talla
                    })->toArray() : [];
                    
                    // ✅ Incluir cantidades por talla
                    $prendasArray['cantidades'] = [];
                    if ($prenda->tallas) {
                        foreach ($prenda->tallas as $talla) {
                            $prendasArray['cantidades'][$talla->talla] = (int)$talla->cantidad;
                        }
                    }
                    
                    // ✅ Incluir género desde prenda_variantes_cot
                    $prendasArray['genero'] = null;
                    if ($prenda->variantes && $prenda->variantes->count() > 0) {
                        $variante = $prenda->variantes->first();
                        // Obtener nombre del género por ID desde generos_prenda
                        if ($variante->genero_id) {
                            $generoNombre = \DB::table('generos_prenda')
                                ->where('id', $variante->genero_id)
                                ->value('nombre');
                            
                            if ($generoNombre) {
                                // Convertir a minúsculas para compatibilidad con el select
                                $generonombre = strtolower($generoNombre);
                                $prendasArray['genero'] = $generonombre === 'dama' ? 'dama' : 'caballero';
                            }
                        }
                    }
                    
                    // ✅ Forzar inclusión de fotos de la prenda
                    $prendasArray['fotos'] = $prenda->fotos ? $prenda->fotos->toArray() : [];
                    
                    // ✅ Incluir reflectivo específico de esta prenda
                    if ($prenda->reflectivo && $prenda->reflectivo->count() > 0) {
                        $reflectivoPrenda = $prenda->reflectivo->first();
                        $prendasArray['reflectivo'] = $reflectivoPrenda->toArray();
                        
                        // Decodificar ubicaciones si vienen como string
                        if (isset($prendasArray['reflectivo']['ubicacion']) && is_string($prendasArray['reflectivo']['ubicacion'])) {
                            $prendasArray['reflectivo']['ubicacion'] = json_decode($prendasArray['reflectivo']['ubicacion'], true) ?? [];
                        }
                    }
                    
                    return $prendasArray;
                })->toArray() : [];
                
                // Obtener el primer reflectivo para descripción global (si existe)
                $reflectivoGlobal = null;
                if ($cotizacion->prendas && $cotizacion->prendas->count() > 0) {
                    $primeraPrenda = $cotizacion->prendas->first();
                    if ($primeraPrenda->reflectivo && $primeraPrenda->reflectivo->count() > 0) {
                        $reflectivoGlobal = $primeraPrenda->reflectivo->first();
                    }
                }
                
                $datosIniciales = [
                    'id' => $cotizacion->id,
                    'cliente' => $cotizacion->cliente ? ['id' => $cotizacion->cliente->id, 'nombre' => $cotizacion->cliente->nombre] : null,
                    'fecha_inicio' => $cotizacion->fecha_inicio,
                    'especificaciones' => $cotizacion->especificaciones,
                    'prendas' => $prendasConTallas,  // ✅ Cada prenda incluye su propio reflectivo
                    'reflectivo_cotizacion' => $reflectivoGlobal ? $reflectivoGlobal->toArray() : null,
                    'reflectivo' => $reflectivoGlobal ? $reflectivoGlobal->toArray() : null,
                ];
                
                return view('asesores.pedidos.create-reflectivo', [
                    'cotizacionId' => $cotizacion->id,
                    'datosIniciales' => json_encode($datosIniciales),
                    'esEdicion' => true
                ]);
            }

            // Para otros tipos, obtener la ruta y redirigir
            $ruta = $mapeoTipos[$tipoCotizacionId] ?? $mapeoTipos[3];
            if ($ruta) {
                $ruta = str_replace('{id}', $id, $ruta);
                return redirect($ruta);
            }

            // No debería llegar aquí
            abort(400, 'Tipo de cotización no válido');
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
            
            // ✅ Buscar y eliminar de ReflectivoCotizacionFoto (fotos de reflectivo)
            foreach ($rutasABuscar as $ruta) {
                $fotosEliminadas += \App\Models\ReflectivoCotizacionFoto::where('ruta_original', $ruta)
                    ->orWhere('ruta_webp', $ruta)
                    ->delete();
            }
            
            // También buscar por ID si se proporciona
            $fotoId = $request->input('foto_id');
            if ($fotoId) {
                $fotoEliminada = \App\Models\ReflectivoCotizacionFoto::where('id', $fotoId)->delete();
                if ($fotoEliminada) {
                    $fotosEliminadas += $fotoEliminada;
                    Log::info('Foto de reflectivo eliminada por ID', ['foto_id' => $fotoId]);
                }
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

    /**
     * Anular cotización con novedad
     */
    public function anularCotizacion(Request $request, int $id)
    {
        $request->validate([
            'novedad' => 'required|string|min:10|max:500',
        ], [
            'novedad.required' => 'La novedad es obligatoria',
            'novedad.min' => 'La novedad debe tener al menos 10 caracteres',
            'novedad.max' => 'La novedad no puede exceder 500 caracteres',
        ]);

        $cotizacion = \App\Models\Cotizacion::findOrFail($id);

        // Verificar que la cotización pertenece al asesor autenticado
        if ($cotizacion->asesor_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para anular esta cotización',
            ], 403);
        }

        // Formatear la novedad con nombre y fecha
        $nombreUsuario = auth()->user()->name;
        $fechaHora = now()->format('d-m-Y h:i:s A');
        $nuevaNovedad = "[{$nombreUsuario} - {$fechaHora}] {$request->novedad}";
        
        // Agregar la novedad al campo novedades existente
        $novedadesActuales = $cotizacion->novedades ?? '';
        $novedadesActualizadas = trim($novedadesActuales) !== '' 
            ? $novedadesActuales . "\n" . $nuevaNovedad 
            : $nuevaNovedad;

        // Actualizar estado y novedades
        $cotizacion->update([
            'estado' => 'Anulada',
            'novedades' => $novedadesActualizadas,
        ]);

        // Log de auditoría
        Log::info("Cotización #{$cotizacion->numero_cotizacion} anulada por asesor " . auth()->user()->name, [
            'novedad' => $request->novedad,
            'fecha' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cotización anulada correctamente',
            'cotizacion' => $cotizacion,
        ]);
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
