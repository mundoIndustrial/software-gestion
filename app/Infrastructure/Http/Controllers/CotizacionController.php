<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Cotizacion\Commands\AceptarCotizacionCommand;
use App\Application\Cotizacion\Commands\CambiarEstadoCotizacionCommand;
use App\Application\Cotizacion\Commands\CrearCotizacionCommand;
use App\Application\Cotizacion\Commands\EliminarCotizacionCommand;
use App\Application\Cotizacion\Commands\SubirImagenCotizacionCommand;
use App\Application\Cotizacion\DTOs\CrearCotizacionDTO;
use App\Application\Cotizacion\Handlers\Commands\AceptarCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\CambiarEstadoCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\CrearCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\EliminarCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\SubirImagenCotizacionHandler;
use App\Application\Cotizacion\Handlers\Queries\ListarCotizacionesHandler;
use App\Application\Cotizacion\Handlers\Queries\ObtenerCotizacionHandler;
use App\Application\Cotizacion\Queries\ListarCotizacionesQuery;
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

            if ($cotizacion->asesor_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
            }

            $data = $cotizacion->toArray();

            $data['prendas'] = $cotizacion->prendas->map(function ($prenda) {
                $variantes = $prenda->variantes->map(function ($variante) {
                    return [
                        'id' => $variante->id,
                        'color' => $variante->color ?? null,
                        'tela' => $variante->tela ?? null,
                        'referencia' => $variante->referencia ?? null,
                        'manga' => $variante->manga?->nombre ?? null,
                        'broche' => $variante->broche?->nombre ?? null,
                        'tiene_bolsillos' => $variante->tiene_bolsillos ?? false,
                        'telas_multiples' => $variante->telas_multiples ? json_decode($variante->telas_multiples, true) : null,
                    ];
                })->toArray();
                
                return [
                    'id' => $prenda->id,
                    'nombre_producto' => $prenda->nombre_producto,
                    'descripcion' => $prenda->generarDescripcionDetallada(),
                    'tallas' => $prenda->tallas->pluck('talla')->toArray(),
                    'fotos' => $prenda->fotos->pluck('url')->toArray(),
                    'variantes' => $variantes
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
            Log::info(' getReflectivoForEdit: INICIANDO', ['cotizacion_id' => $id, 'usuario_id' => Auth::id()]);

            $cotizacion = \App\Models\Cotizacion::with([
                'cliente',
                'prendas.tallas',           
                'prendas.variantes',       
            ])->findOrFail($id);

            Log::info(' Cotización cargada', ['cotizacion_id' => $cotizacion->id, 'asesor_id' => $cotizacion->asesor_id]);

            if ($cotizacion->asesor_id !== Auth::id()) {
                Log::warning(' Usuario no autorizado', ['cotizacion_asesor' => $cotizacion->asesor_id, 'usuario_actual' => Auth::id()]);
                return response()->json(['success' => false, 'message' => 'No tienes permiso'], 403);
            }

            if ($cotizacion->es_borrador === false) {
                Log::warning(' No es borrador', ['cotizacion_id' => $id, 'es_borrador' => $cotizacion->es_borrador]);
                return response()->json(['success' => false, 'message' => 'Solo se pueden editar borradores'], 403);
            }

            $prendasProcesadas = [];
            if ($cotizacion->prendas) {
                foreach ($cotizacion->prendas as $prenda) {
                    $variante = $prenda->variantes ? $prenda->variantes->first() : null;
                    $tieneReflectivo = $variante ? ($variante->tiene_reflectivo ?? false) : false;
                    
                    $tallas = [];
                    $cantidades = [];
                    if ($prenda->tallas) {
                        foreach ($prenda->tallas as $talla) {
                            $tallas[] = $talla->talla;
                            $cantidades[$talla->talla] = $talla->cantidad;
                        }
                    }

                    $genero = null;
                    $variantes = null;
                    if ($prenda->variantes) {
                        foreach ($prenda->variantes as $variante) {
                            if ($variante->genero_id) {
                                $generoIds = is_string($variante->genero_id) ? json_decode($variante->genero_id, true) : $variante->genero_id;
                                if (is_array($generoIds) && !empty($generoIds)) {
                                    $generoObj = \App\Models\GeneroPrenda::find($generoIds[0]);
                                    $genero = $generoObj ? strtolower($generoObj->nombre) : null;
                                }
                            }
                        }
                        $variantes = $prenda->variantes->toArray();
                    }
                    
                    Log::info(' Prenda con variante', [
                        'prenda_id' => $prenda->id,
                        'prenda_nombre' => $prenda->nombre_producto,
                        'tallas_count' => count($tallas),
                        'genero' => $genero,
                        'tiene_reflectivo' => $tieneReflectivo,
                        'variante_id' => $variante ? $variante->id : null,
                    ]);

                    $prendasProcesadas[] = [
                        'id' => $prenda->id,
                        'tipo' => $prenda->nombre_producto,
                        'descripcion' => $prenda->descripcion ?? '',
                        'tallas' => $tallas,                    
                        'cantidades' => $cantidades,           
                        'genero' => $genero,                   
                        'variantes' => $variantes,             
                        'reflectivo' => $tieneReflectivo ? [
                            'tiene_reflectivo' => true,
                            'observaciones' => $variante ? $variante->obs_reflectivo : null,
                        ] : null,
                    ];
                }
            }

            Log::info(' CotizacionController@getReflectivoForEdit: Cotización RF cargada para editar', [
                'cotizacion_id' => $cotizacion->id,
                'prendas_count' => count($prendasProcesadas),
                'prendas_con_reflectivo' => collect($prendasProcesadas)->filter(fn($p) => $p['reflectivo'] !== null)->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'cotizacion' => $cotizacion->toArray(),
                    'prendas' => $prendasProcesadas,  
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
            $cotizacion = \App\Models\Cotizacion::with([
                'cliente',
                'prendas.fotos',
                'prendas.telaFotos',
                'prendas.tallas',
                'prendas.variantes.manga',
                'prendas.variantes.broche',
                'logoCotizacion.fotos'
            ])->findOrFail($id);

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
        abort(404);
    }

    /**
     * Borrar imagen de prenda específica
     */
    public function borrarImagenPrenda(Request $request, $id)
    {
        try {
            $fotoId = $request->input('foto_id');
            
            Log::info('Borrando imagen de prenda:', ['foto_id' => $fotoId, 'cotizacion_id' => $id]);
            
            $foto = \App\Models\PrendaFotoCot::find($fotoId);
            
            if (!$foto) {
                Log::warning(' Imagen no encontrada:', ['foto_id' => $fotoId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Imagen no encontrada'
                ], 404);
            }
            
            if ($foto->ruta_original && \Storage::disk('public')->exists($foto->ruta_original)) {
                \Storage::disk('public')->delete($foto->ruta_original);
            }
            
            $foto->forceDelete();
            
            Log::info(' Imagen de prenda borrada exitosamente:', ['foto_id' => $fotoId]);
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen borrada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error(' Error al borrar imagen de prenda:', ['error' => $e->getMessage()]);
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
            
            $foto = \App\Models\PrendaTelaFotoCot::find($fotoId);
            
            if (!$foto) {
                Log::warning(' Imagen de tela no encontrada:', ['foto_id' => $fotoId]);
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
            
            Log::info(' Imagen de tela borrada exitosamente:', ['foto_id' => $fotoId]);
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen borrada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error(' Error al borrar imagen de tela:', ['error' => $e->getMessage()]);
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
            $cotizacionIdExistente = $request->input('cotizacion_id');
            if ($cotizacionIdExistente) {
                Log::info('CotizacionController@store: Detectada actualización de borrador existente', [
                    'cotizacion_id' => $cotizacionIdExistente,
                ]);
                return $this->update($request, (int)$cotizacionIdExistente);
            }
            

            // Mapear productos_friendly -> prendas para compatibilidad frontend
            //  OBTENER PRENDAS DESDE FORMDATA (no uses input() para arrays complejos)
            $allData = $request->all();
            Log::info(" CONTROLLER - Todos los datos recibidos", [
                'all_keys' => array_keys($allData),
                'productos_friendly_exists' => isset($allData['productos_friendly']),
                'prendas_exists' => isset($allData['prendas']),
                'productos_friendly_raw' => $allData['productos_friendly'] ?? 'NO ENVIADO'
            ]);
            
            $prendasRecibidas = $allData['prendas'] ?? $allData['productos_friendly'] ?? $request->input('prendas', $request->input('productos_friendly', []));
            $especificacionesRecibidas = $request->input('especificaciones', []);
            
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
            
          

            $clienteId = $request->input('cliente_id');
            $nombreCliente = $request->input('cliente');
            $accion = $request->input('accion');
            $esBorrador = $request->input('es_borrador');

            if ($nombreCliente && !$clienteId) {
                $cliente = $this->obtenerOCrearClienteService->ejecutar($nombreCliente);
                $clienteId = $cliente->id;
                Log::info('Cliente creado/obtenido', ['cliente_id' => $clienteId, 'nombre' => $nombreCliente]);
            }

            // Si es_borrador viene del frontend, usarlo. Si no, usar la lógica de acción
            if ($esBorrador === null) {
                $esBorrador = ($accion === 'guardar');
            } else {
                // IMPORTANTE: (bool)"0" en PHP es true (string no vacío). Parsear correctamente.
                if (is_bool($esBorrador)) {
                    $esBorrador = $esBorrador;
                } elseif (is_numeric($esBorrador)) {
                    $esBorrador = ((int)$esBorrador) === 1;
                } elseif (is_string($esBorrador)) {
                    $esBorradorLower = strtolower(trim($esBorrador));
                    $esBorrador = in_array($esBorradorLower, ['1', 'true', 'yes', 'on'], true);
                } else {
                    $esBorrador = false;
                }
            }
            
            $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR';

            // Generar número de cotización si es envío (no borrador)
            $numeroCotizacion = null;
            if (!$esBorrador) {
                // Usar el servicio de generación segura de números (con database locks)
                $usuarioId = \App\Domain\Shared\ValueObjects\UserId::crear(Auth::id());
                // IMPORTANTE: guardar el consecutivo como INT en el DTO/handler.
                // Si se envía formateado (ej: "COT-00005"), el DTO puede castear mal y
                // el handler termina generando otro consecutivo.
                $numeroCotizacion = $this->generarNumeroCotizacionService->generarProxNumeroCotizacion($usuarioId);
                
                Log::info('CotizacionController@store: Número de cotización generado con servicio seguro', [
                    'usuario_id' => Auth::id(),
                    'numero' => $numeroCotizacion,
                    'numero_formateado' => $this->generarNumeroCotizacionService->formatearNumero($numeroCotizacion),
                ]);
            }

       

            // Tipo de cotización: Logo (L), Combinado (PL), o Reflectivo (RF)
            $tipoCotizacion = $request->input('tipo_cotizacion', 'PL');
            $logoData = $request->input('logo', []);
            
            Log::info('CotizacionController@store: Tipo de cotización', [
                'tipo_cotizacion' => $tipoCotizacion,
                'logo_data' => $logoData,
            ]);

            //  OBTENER PRENDAS DESDE FORMDATA (no uses input() para arrays complejos)
            $allData = $request->all();
            $prendasRecibidas = $allData['prendas'] ?? $request->input('prendas', []);
            
            Log::info('CotizacionController@store: Prendas extraídas', [
                'prendas_count' => is_array($prendasRecibidas) ? count($prendasRecibidas) : 0,
                'prendas_type' => gettype($prendasRecibidas),
                'prendas_keys' => is_array($prendasRecibidas) && !empty($prendasRecibidas) ? array_keys($prendasRecibidas) : [],
            ]);

            $dto = CrearCotizacionDTO::desdeArray([
                'usuario_id' => Auth::id(),
                'tipo' => $tipoCotizacion,
                'cliente_id' => $clienteId,
                'prendas' => $prendasRecibidas,
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

            // Determinar si es borrador o envío
            $tipo = $request->input('tipo'); // 'borrador' o 'enviada'
            $esBorrador = ($tipo === 'borrador' || $request->input('es_borrador') === '1' || $request->input('es_borrador') === true);
            $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR';
            
            // Generar número de cotización si es envío (y no tiene uno)
            $numeroCotizacion = $cotizacion->numero_cotizacion;
            if (!$esBorrador && !$numeroCotizacion) {
                $usuarioId = \App\Domain\Shared\ValueObjects\UserId::crear(Auth::id());
                $numeroCotizacion = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId);
                Log::info('Número de cotización generado en UPDATE', ['numero' => $numeroCotizacion, 'cotizacion_id' => $id]);
            }
            
            Log::info('UPDATE - Estado de cotización', [
                'tipo_recibido' => $tipo,
                'es_borrador_anterior' => $cotizacion->es_borrador,
                'es_borrador_nuevo' => $esBorrador,
                'estado_anterior' => $cotizacion->estado,
                'estado_nuevo' => $estado,
                'numero_cotizacion' => $numeroCotizacion,
            ]);

            //  LÓGICA: Si es COMBINADA (PL) pero NO hay logo, cambiar a PRENDA (P)
            $tipoCotizacionEnviado = $request->input('tipo_cotizacion');
            $logoData = $request->input('logo', []);
            
            // Verificar si hay datos de logo
            $tieneLogoDatos = !empty($logoData) && (
                !empty($logoData['descripcion']) ||
                !empty($logoData['tecnicas']) ||
                !empty($logoData['imagenes']) ||
                !empty($logoData['ubicaciones'])
            );
            
            Log::info('CotizacionController@update: Análisis de Logo', [
                'tipo_cotizacion_enviado' => $tipoCotizacionEnviado,
                'tiene_logo_datos' => $tieneLogoDatos,
                'logo_data' => $logoData,
            ]);
            
            // Determinar el tipo_cotizacion_id correcto
            $tipoCotizacionId = $cotizacion->tipo_cotizacion_id; // Mantener el actual por defecto
            
            // Mapear tipo a tipo_cotizacion_id
            // Tipos disponibles: Prenda (P=3), Logo (L=2), Combinado (PL/PB=1), Reflectivo (RF=4)
            if ($tipoCotizacionEnviado === 'P') {
                $tipoCotizacionId = 3; // Prenda
            } elseif ($tipoCotizacionEnviado === 'PL' || $tipoCotizacionEnviado === 'PB') {
                $tipoCotizacionId = 1; // Combinado
            } elseif ($tipoCotizacionEnviado === 'L') {
                $tipoCotizacionId = 2; // Logo
            } elseif ($tipoCotizacionEnviado === 'RF') {
                $tipoCotizacionId = 4; // Reflectivo
            } else {
                $tipoCotizacionId = 1; // Por defecto Combinado
            }

            // Actualizar datos básicos
            $datosActualizar = [
                'cliente_id' => $clienteId,
                'tipo_venta' => $request->input('tipo_venta'),
                'es_borrador' => $esBorrador,
                'estado' => $estado,
                'numero_cotizacion' => $numeroCotizacion,
                'tipo_cotizacion_id' => $tipoCotizacionId,
                'fecha_envio' => !$esBorrador ? \Carbon\Carbon::now('America/Bogota') : null,
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
            //  OBTENER PRENDAS DESDE FORMDATA (no uses input() para arrays complejos)
            $allData = $request->all();
            $prendasRecibidas = $allData['prendas'] ?? $request->input('prendas', []);
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
            // NOTA: NO actualizamos aquí, lo hacemos en procesarImagenesCotizacion() para evitar conflictos
            $logoCotizacion = $cotizacion->logoCotizacion;
            if ($logoCotizacion) {
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
            //  OBTENER PRENDAS DESDE FORMDATA (no uses input() para arrays complejos)
            $allData = $request->all();
            $prendas = $allData['prendas'] ?? $request->input('prendas', []);
            $allFiles = $request->allFiles();
            
            // DETECTAR si es UPDATE o CREATE
            $cotizacionExistente = \App\Models\Cotizacion::find($cotizacionId);
            $esUpdate = !!$cotizacionExistente;

            Log::info('Procesando imágenes de cotización', [
                'cotizacion_id' => $cotizacionId,
                'es_update' => $esUpdate,
                'prendas_count' => count($prendas),
                'all_files_keys' => array_keys($allFiles),
            ]);
            
            Log::info('DEBUG - prendas input:', [
                'prendas_array' => $prendas,
                'prendas_type' => gettype($prendas),
            ]);
            
            // ======== DEBUG ALLFILES STRUCTURE ========
            Log::info(' ESTRUCTURA COMPLETA DE allFiles:', [
                'all_files_keys' => array_keys($allFiles),
                'tiene_prendas' => isset($allFiles['prendas']),
                'prendas_is_array' => isset($allFiles['prendas']) ? is_array($allFiles['prendas']) : false,
                'prendas_count' => isset($allFiles['prendas']) && is_array($allFiles['prendas']) ? count($allFiles['prendas']) : 0,
            ]);
            
            if (isset($allFiles['prendas']) && is_array($allFiles['prendas'])) {
                foreach ($allFiles['prendas'] as $idx => $prendaFiles) {
                    Log::info("  🔹 allFiles['prendas'][$idx]:", [
                        'keys' => array_keys((array)$prendaFiles),
                        'tiene_telas' => isset($prendaFiles['telas']),
                        'telas_type' => isset($prendaFiles['telas']) ? gettype($prendaFiles['telas']) : 'N/A',
                        'telas_count' => isset($prendaFiles['telas']) && is_array($prendaFiles['telas']) ? count($prendaFiles['telas']) : 0,
                    ]);
                    
                    if (isset($prendaFiles['telas']) && is_array($prendaFiles['telas'])) {
                        foreach ($prendaFiles['telas'] as $telaIdx => $telaData) {
                            Log::info("      allFiles['prendas'][$idx]['telas'][$telaIdx]:", [
                                'keys' => array_keys((array)$telaData),
                                'tiene_fotos' => isset($telaData['fotos']),
                                'fotos_type' => isset($telaData['fotos']) ? gettype($telaData['fotos']) : 'N/A',
                                'fotos_count' => isset($telaData['fotos']) && is_array($telaData['fotos']) ? count($telaData['fotos']) : 0,
                                'fotos_items' => isset($telaData['fotos']) && is_array($telaData['fotos']) ? array_map(fn($f) => is_object($f) ? get_class($f) : gettype($f), $telaData['fotos']) : [],
                            ]);
                        }
                    }
                }
            }

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

                // === FOTOS DE PRENDA EXISTENTES (preservar en edición o copia) ===
                // EN UPDATE: No procesar fotos existentes - ya están en la prenda
                // EN CREATE: Permitir copiar fotos de otras prendas si es necesario
                $ordenFotosPrenda = (($prendaModel->fotos()->max('orden')) ?? 0) + 1;
                $fotosPrendaExistentes = $request->input("prendas.{$index}.fotos_existentes") ?? ($prenda['fotos_existentes'] ?? []);
                if (is_string($fotosPrendaExistentes)) {
                    $fotosPrendaExistentes = json_decode($fotosPrendaExistentes, true) ?? [];
                }
                if (!is_array($fotosPrendaExistentes)) {
                    $fotosPrendaExistentes = [];
                }
                
                //  EN UPDATE: IGNORAR fotos_existentes (ya están guardadas)
                //  EN CREATE: PROCESAR fotos_existentes (para copiar entre cotizaciones)
                if (!empty($fotosPrendaExistentes) && !$esUpdate) {
                    foreach ($fotosPrendaExistentes as $fotoId) {
                        $fotoExistente = \App\Models\PrendaFotoCot::find($fotoId);
                        if ($fotoExistente) {
                            // Si ya pertenece a esta prenda, no duplicar
                            if ($fotoExistente->prenda_cot_id == $prendaModel->id) {
                                Log::info('↔️ Foto de prenda ya pertenece a la prenda, no se duplica', [
                                    'foto_id' => $fotoId,
                                    'prenda_id' => $prendaModel->id,
                                ]);
                                continue;
                            }
                            $prendaModel->fotos()->create([
                                'ruta_original' => $fotoExistente->ruta_original,
                                'ruta_webp' => $fotoExistente->ruta_webp,
                                'ruta_miniatura' => $fotoExistente->ruta_miniatura,
                                'orden' => $ordenFotosPrenda,
                                'ancho' => $fotoExistente->ancho,
                                'alto' => $fotoExistente->alto,
                                'tamaño' => $fotoExistente->tamaño,
                            ]);
                            Log::info(' Foto de prenda existente copiada', [
                                'foto_id' => $fotoId,
                                'prenda_id' => $prendaModel->id,
                                'orden' => $ordenFotosPrenda,
                            ]);
                            $ordenFotosPrenda++;
                        }
                    }
                }

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
                    $orden = $ordenFotosPrenda; // continuar después de las existentes
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
                    $orden = max($ordenFotosPrenda, count($fotosArchivos) + $ordenFotosPrenda); // Continuar con el orden
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
                //  REFRESH: Recargar el modelo para obtener las variantes recién creadas
                $prendaModel->refresh();
                $variante = $prendaModel->variantes()->first();
                $telasMultiples = [];
                if ($variante && $variante->telas_multiples) {
                    $telasMultiples = is_array($variante->telas_multiples) 
                        ? $variante->telas_multiples 
                        : json_decode($variante->telas_multiples, true);
                }
                
                Log::info(' Telas multiples de variante:', [
                    'prenda_id' => $prendaModel->id,
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
                            Log::info(' Color creado', ['color' => $telaInfo['color'], 'id' => $colorId]);
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
                            Log::info(' Tela creada', ['tela' => $telaInfo['tela'], 'id' => $telaId]);
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
                            
                            // Guardar el ID en el mapeo
                            $telaIndex = $telaInfo['indice'] ?? null;
                            if ($telaIndex !== null) {
                                $telaCotIds[$telaIndex] = $prendaTelaCotId;
                            }
                            
                            Log::info(' Registro guardado en prenda_telas_cot (desde telas_multiples)', [
                                'prenda_telas_cot_id' => $prendaTelaCotId,
                                'prenda_id' => $prendaModel->id,
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
                            
                            Log::info(' Registro ya existe en prenda_telas_cot', [
                                'prenda_id' => $prendaModel->id,
                                'prenda_tela_cot_id' => $existente->id,
                                'color' => $telaInfo['color'] ?? '',
                                'tela' => $telaInfo['tela'] ?? '',
                                'indice' => $telaIndex,
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
                            // Reiniciar $ordenFotosTela para cada tela (evita duplicación)
                            unset($ordenFotosTela);
                            
                            // Buscar info de la tela (color/tela/ref) por índice
                            $telaInfo = [];
                            foreach ($telasMultiples as $tm) {
                                if (($tm['indice'] ?? null) === (int)$telaIndex) {
                                    $telaInfo = $tm;
                                    break;
                                }
                            }
                            
                            // === FOTOS DE TELA EXISTENTES (preservar) ===
                            $fotosTelaExistentes = $request->input("prendas.{$index}.telas.{$telaIndex}.fotos_existentes") ?? ($telaData['fotos_existentes'] ?? []);
                            if (is_string($fotosTelaExistentes)) {
                                $fotosTelaExistentes = json_decode($fotosTelaExistentes, true) ?? [];
                            }
                            if (!is_array($fotosTelaExistentes)) {
                                $fotosTelaExistentes = [];
                            }
                            
                            //  EN UPDATE: IGNORAR fotos_existentes (ya están guardadas)
                            //  EN CREATE: PROCESAR fotos_existentes (para copiar entre cotizaciones)
                            if (empty($fotosTelaExistentes) || $esUpdate) {
                                Log::info('UPDATE o fotos vacías - IGNORANDO fotos_existentes de tela para evitar duplicados', [
                                    'prenda_id' => $prendaModel->id,
                                    'tela_index' => $telaIndex,
                                    'es_update' => $esUpdate,
                                    'fotos_existentes_count' => count($fotosTelaExistentes),
                                ]);
                            } else {
                                // Orden inicia después de las fotos que ya tenga esta prenda en ese índice
                                $ordenFotosTela = (DB::table('prenda_tela_fotos_cot')
                                    ->where('prenda_cot_id', $prendaModel->id)
                                    ->where('tela_index', $telaIndex)
                                    ->max('orden') ?? 0) + 1;
                                
                                foreach ($fotosTelaExistentes as $fotoId) {
                                    $fotoExistente = \App\Models\PrendaTelaFotoCot::find($fotoId);
                                    if ($fotoExistente) {
                                        // Crear copia (en CREATE para copiar entre cotizaciones)
                                        $prendaTelaCotId = $telaCotIds[$telaIndex] ?? null;
                                        \DB::table('prenda_tela_fotos_cot')->insert([
                                            'prenda_cot_id' => $prendaModel->id,
                                            'prenda_tela_cot_id' => $prendaTelaCotId,
                                            'tela_index' => $telaIndex,
                                            'ruta_original' => $fotoExistente->ruta_original,
                                            'ruta_webp' => $fotoExistente->ruta_webp,
                                            'ruta_miniatura' => $fotoExistente->ruta_miniatura,
                                            'orden' => $ordenFotosTela,
                                            'ancho' => $fotoExistente->ancho,
                                            'alto' => $fotoExistente->alto,
                                            'tamaño' => $fotoExistente->tamaño,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                        Log::info(' Foto de tela copiada de otra prenda', [
                                            'foto_id' => $fotoId,
                                            'prenda_origen_id' => $fotoExistente->prenda_cot_id,
                                            'prenda_destino_id' => $prendaModel->id,
                                            'tela_index' => $telaIndex,
                                            'orden' => $ordenFotosTela,
                                        ]);
                                        $ordenFotosTela++;
                                    }
                                }
                            }
                            
                            // Nuevas fotos de tela (subidas)
                            if (isset($telaData['fotos']) && is_array($telaData['fotos'])) {
                                Log::info(' Encontrado grupo de fotos de tela', [
                                    'prenda_index' => $index,
                                    'tela_index' => $telaIndex,
                                    'cantidad_archivos' => count($telaData['fotos']),
                                    'telaCotIds_disponibles' => $telaCotIds,
                                ]);
                                
                                // IMPORTANTE: Inicializar $ordenFotosTela si no existe
                                if (!isset($ordenFotosTela)) {
                                    $ordenFotosTela = (DB::table('prenda_tela_fotos_cot')
                                        ->where('prenda_cot_id', $prendaModel->id)
                                        ->where('tela_index', $telaIndex)
                                        ->max('orden') ?? 0) + 1;
                                }
                                
                                // Obtener prenda_tela_cot_id del mapeo
                                $prendaTelaCotId = $telaCotIds[$telaIndex] ?? null;
                                
                                //  VERIFICAR si hay fotos en este índice de tela
                                if (!isset($telaData['fotos']) || empty($telaData['fotos'])) {
                                    Log::info('No hay fotos para esta tela', [
                                        'prenda_id' => $prendaModel->id,
                                        'tela_index' => $telaIndex,
                                        'telaData_keys' => array_keys($telaData),
                                    ]);
                                    continue; // Saltear si no hay fotos
                                }
                                
                                $fotosArray = $telaData['fotos'];
                                Log::info(' DEBUG fotos encontradas', [
                                    'tela_index' => $telaIndex,
                                    'fotos_type' => gettype($fotosArray),
                                    'fotos_count' => is_array($fotosArray) ? count($fotosArray) : 'N/A',
                                    'fotos_keys' => is_array($fotosArray) ? array_keys($fotosArray) : 'N/A',
                                    'fotos_debug' => is_array($fotosArray) ? array_map(fn($f) => is_object($f) ? get_class($f) : gettype($f), $fotosArray) : 'N/A',
                                ]);
                                
                                // Normalizar a array si es un único archivo
                                if ($fotosArray instanceof \Illuminate\Http\UploadedFile) {
                                    $fotosArray = [$fotosArray];
                                }
                                
                                //  Verificar que sea un array
                                if (!is_array($fotosArray)) {
                                    Log::warning(' fotosArray no es array', [
                                        'tela_index' => $telaIndex,
                                        'tipo' => gettype($fotosArray),
                                    ]);
                                    continue;
                                }
                                
                                foreach ($fotosArray as $archivoFoto) {
                                    if ($archivoFoto && $archivoFoto instanceof \Illuminate\Http\UploadedFile && $archivoFoto->isValid()) {
                                        try {
                                            // Usar el servicio para procesar y convertir a WebP
                                            $rutaGuardada = $this->procesarImagenesService->procesarImagenTela(
                                                $archivoFoto,
                                                $cotizacionId,
                                                $prendaModel->id
                                            );
                                            $rutaUrl = Storage::url($rutaGuardada);
                                            
                                            // Guardar en tabla prenda_tela_fotos_cot con prenda_tela_cot_id
                                            DB::table('prenda_tela_fotos_cot')->insert([
                                                'prenda_cot_id' => $prendaModel->id,
                                                'prenda_tela_cot_id' => $prendaTelaCotId,
                                                'tela_index' => $telaIndex,
                                                'ruta_original' => null, // Ya no guardamos original
                                                'ruta_webp' => $rutaGuardada, // Guardar ruta WebP
                                                'ruta_miniatura' => null, // Podría generarse si se necesita
                                                'orden' => $ordenFotosTela,
                                                'ancho' => null,
                                                'alto' => null,
                                                'tamaño' => $archivoFoto->getSize(),
                                                'created_at' => now(),
                                                'updated_at' => now(),
                                            ]);

                                            Log::info(' Foto de tela guardada en prenda_tela_fotos_cot', [
                                                'prenda_id' => $prendaModel->id,
                                                'prenda_tela_cot_id' => $prendaTelaCotId,
                                                'referencia' => $telaInfo['referencia'] ?? '',
                                                'ruta' => $rutaUrl,
                                                'orden' => $ordenFotosTela,
                                            ]);
                                            $ordenFotosTela++;
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
            }

            // ===== PROCESAR FOTOS EXISTENTES DE TELAS (sin archivos nuevos) =====
            // Este bloque detecta cuando se envían telas sin archivos nuevos
            // y copia automáticamente las fotos existentes
            Log::info('🔄 PROCESANDO FOTOS EXISTENTES DE TELAS - Verificando necesidad de copia');
            
            try {
            foreach ($prendas as $index => $prenda) {
                // Obtener la prenda guardada
                $prendaModel = \App\Models\PrendaCot::where('cotizacion_id', $cotizacionId)
                    ->skip($index)
                    ->first();

                if (!$prendaModel) {
                    Log::info('Prenda no encontrada para fallback', ['cotizacion_id' => $cotizacionId, 'index' => $index]);
                    continue;
                }

                // ESTRATEGIA: Si hay telas pero NO hay fotos de esas telas, COPIAR de BD
                // Obtener todas las telas de esta prenda
                $todasLasTelas = DB::table('prenda_telas_cot')
                    ->where('prenda_cot_id', $prendaModel->id)
                    ->orderBy('created_at')
                    ->get();

                if ($todasLasTelas->isEmpty()) {
                    Log::info('Fallback: Sin telas para procesar', ['prenda_id' => $prendaModel->id]);
                    continue;
                }

                // Para cada tela, verificar si necesita copiar fotos
                foreach ($todasLasTelas as $telaIndex => $telaCot) {
                    // Verificar si esta tela ya tiene fotos
                    $yaTieneFotos = DB::table('prenda_tela_fotos_cot')
                        ->where('prenda_tela_cot_id', $telaCot->id)
                        ->exists();

                    if ($yaTieneFotos) {
                        Log::info('Fallback: Tela ya tiene fotos', ['prenda_tela_cot_id' => $telaCot->id]);
                        continue;
                    }

                    // BUSCAR: Fotos existentes de tela del mismo color/tela en OTRA cotización (draft anterior)
                    // Estrategia: Buscar en prenda_tela_fotos_cot por color_id y tela_id
                    // NO incluimos variante_prenda_cot_id porque cada cotización tiene su propia variante
                    $fotosAnteriores = DB::table('prenda_tela_fotos_cot as ptf')
                        ->join('prenda_telas_cot as ptc', 'ptf.prenda_tela_cot_id', '=', 'ptc.id')
                        ->where('ptc.color_id', $telaCot->color_id)
                        ->where('ptc.tela_id', $telaCot->tela_id)
                        ->whereNotNull('ptf.ruta_original')
                        ->select('ptf.*')
                        ->get();

                    if ($fotosAnteriores->isEmpty()) {
                        Log::info('Fallback: No hay fotos anteriores para copiar', ['tela_cot_id' => $telaCot->id]);
                        continue;
                    }

                    Log::info('Fallback: Encontradas fotos anteriores para copiar', [
                        'prenda_tela_cot_id' => $telaCot->id,
                        'fotos_count' => $fotosAnteriores->count()
                    ]);

                    // Copiar cada foto anterior
                    $orden = 1;
                    foreach ($fotosAnteriores as $fotoAnterior) {
                        // Usar ruta_original como fuente (siempre tiene valor)
                        $rutaAUsar = $fotoAnterior->ruta_original ?: $fotoAnterior->ruta_webp;

                        DB::table('prenda_tela_fotos_cot')->insert([
                            'prenda_cot_id' => $prendaModel->id,
                            'prenda_tela_cot_id' => $telaCot->id,
                            'tela_index' => $telaIndex,
                            'ruta_original' => $rutaAUsar,
                            'ruta_webp' => $rutaAUsar,  // Usar la misma para que aparezca
                            'ruta_miniatura' => $fotoAnterior->ruta_miniatura,
                            'orden' => $orden,
                            'ancho' => $fotoAnterior->ancho,
                            'alto' => $fotoAnterior->alto,
                            'tamaño' => $fotoAnterior->tamaño,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        Log::info(' Foto de tela copiada automáticamente', [
                            'prenda_tela_cot_id' => $telaCot->id,
                            'foto_anterior_id' => $fotoAnterior->id,
                            'ruta' => $rutaAUsar,
                            'orden' => $orden,
                        ]);

                        $orden++;
                    }
                }
            }
            } catch (\Exception $e) {
                Log::error(' Error en fallback de fotos existentes de telas', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
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
            $logoDescripcion = trim($request->input('descripcion_logo', '')) ?: null;
            $logoTecnicas = $request->input('tecnicas', []);
            if (is_string($logoTecnicas)) {
                $logoTecnicas = json_decode($logoTecnicas, true) ?? [];
            }
            $logoObservacionesTecnicas = $request->input('observaciones_tecnicas', '');
            $logoSecciones = $request->input('secciones', []);
            if (is_string($logoSecciones)) {
                $logoSecciones = json_decode($logoSecciones, true) ?? [];
            }
            $logoObservacionesGenerales = $request->input('observaciones_generales', []);
            if (is_string($logoObservacionesGenerales)) {
                $logoObservacionesGenerales = json_decode($logoObservacionesGenerales, true) ?? [];
            }
            
            //  VALIDAR si logo (PASO 3) tiene información escrita válida
            $tipoCotizacionRequest = $request->input('tipo_cotizacion');

            // Para incluir logo necesita: técnicas agregadas (en window.tecnicasAgregadasPaso3)
            $logoTecnicasAgregadas = $request->input('logo.tecnicas_agregadas');
            if (is_string($logoTecnicasAgregadas)) {
                $logoTecnicasAgregadas = json_decode($logoTecnicasAgregadas, true) ?? [];
            } else {
                $logoTecnicasAgregadas = (array)$logoTecnicasAgregadas;
            }
            
            // El logo tiene información válida si hay técnicas agregadas con prendas
            $logoTieneInformacionValida = false;
            if (!empty($logoTecnicasAgregadas) && is_array($logoTecnicasAgregadas)) {
                foreach ($logoTecnicasAgregadas as $tecnica) {
                    if (!empty($tecnica['prendas']) && is_array($tecnica['prendas'])) {
                        // VERIFICACIÓN SIMPLIFICADA: Solo requiere ubicaciones Y imágenes
                        // NO requiere tallas ni cantidades en el paso 3
                        foreach ($tecnica['prendas'] as $prenda) {
                            $tieneUbicaciones = !empty($prenda['ubicaciones']);
                            $tieneImagenes = !empty($prenda['imagenes']);
                            
                            // Debug: Log de validación de cada prenda
                            \Log::info('🔍 DEBUG Validación prenda logo (SIN TALLAS)', [
                                'nombre_prenda' => $prenda['nombre_prenda'] ?? 'Sin nombre',
                                'tieneUbicaciones' => $tieneUbicaciones,
                                'tieneImagenes' => $tieneImagenes,
                                'ubicaciones' => $prenda['ubicaciones'] ?? [],
                                'imagenes_count' => isset($prenda['imagenes']) ? count($prenda['imagenes']) : 0,
                                'condicion_final' => $tieneUbicaciones && $tieneImagenes
                            ]);
                            
                            // SIMPLIFICADO: Solo requiere ubicaciones Y imágenes
                            // NO valida tallas ni cantidades
                            if ($tieneUbicaciones && $tieneImagenes) {
                                $logoTieneInformacionValida = true;
                                break 2; // Salir de ambos loops
                            }
                        }
                    }
                }
            }
            
            // VERIFICACIÓN ADICIONAL: También válido si hay archivos de imágenes reales
            if (!$logoTieneInformacionValida) {
                $imagenesP3Files = $request->file('logo.imagenes_paso3');
                if ($imagenesP3Files && !empty($imagenesP3Files)) {
                    \Log::info('🔍 DEBUG - Encontrados archivos reales de imágenes', [
                        'imagenesP3Files_type' => gettype($imagenesP3Files),
                        'imagenesP3Files_count' => is_array($imagenesP3Files) ? count($imagenesP3Files, COUNT_RECURSIVE) : 0
                    ]);
                    $logoTieneInformacionValida = true;
                }
            }
            
            \Log::info('📸 Validación LOGO (PASO 3)', [
                'logoTecnicasAgregadas_count' => count($logoTecnicasAgregadas),
                'logoTieneInformacionValida' => $logoTieneInformacionValida,
            ]);

            // Si la cotización es tipo Prenda (P), NUNCA debe crearse logo_cotizaciones.
            if ($tipoCotizacionRequest === 'P') {
                $logoTieneInformacionValida = false;
            }
            
            // Crear o actualizar logo_cotizaciones SOLO si hay información válida
            $logoCotizacion = null;
            $logoFueCreadoNuevo = false;
            
            if ($logoTieneInformacionValida) {
                // PRIMERO: Verificar si ya existe un LogoCotizacion para esta cotización
                $logoExistente = \App\Models\LogoCotizacion::where('cotizacion_id', $cotizacionId)->first();
                
                if ($logoExistente) {
                    // Si existe, SOLO actualizar (no crear nuevo)
                    // IMPORTANTE: Merge con datos existentes para NO SOBRESCRIBIR si viene vacío
                    $datosActualizar = [
                        'observaciones_generales' => is_array($logoObservacionesGenerales) && !empty($logoObservacionesGenerales)
                            ? json_encode($logoObservacionesGenerales)
                            : $logoExistente->observaciones_generales,
                        'tipo_venta' => $request->input('tipo_venta_paso3') ?? $request->input('tipo_venta') ?? $logoExistente->tipo_venta,
                    ];
                    
                    $logoExistente->update($datosActualizar);
                    $logoCotizacion = $logoExistente;
                    Log::info(' LogoCotizacion ACTUALIZADO (ya existía)', [
                        'cotizacion_id' => $cotizacionId,
                        'logo_id' => $logoCotizacion->id,
                        'descripcion_guardada' => $logoDescripcion,
                        'datos_actualizados' => $datosActualizar,
                    ]);
                } else {
                    // Si NO existe, crear nuevo
                    $logoCotizacion = \App\Models\LogoCotizacion::create([
                        'cotizacion_id' => $cotizacionId,
                        'observaciones_generales' => is_array($logoObservacionesGenerales) ? json_encode($logoObservacionesGenerales) : $logoObservacionesGenerales,
                        'tipo_venta' => $request->input('tipo_venta_paso3') ?? $request->input('tipo_venta') ?? null,
                    ]);
                    $logoFueCreadoNuevo = true;
                    Log::info(' LogoCotizacion CREADO (nuevo)', [
                        'cotizacion_id' => $cotizacionId,
                        'logo_id' => $logoCotizacion->id,
                    ]);
                }
            } else {
                Log::warning(' Logo (PASO 3) sin información válida - No se creará logo_cotizacion', [
                    'cotizacion_id' => $cotizacionId,
                ]);
            }
            
            Log::info('Logo datos guardados', [
                'cotizacion_id' => $cotizacionId,
                'logo_id' => $logoCotizacion->id ?? null,
                'descripcion' => $logoDescripcion,
                'tecnicas' => $logoTecnicas,
                'tecnicas_count' => is_array($logoTecnicas) ? count($logoTecnicas) : 0,
                'ubicaciones' => $logoSecciones,
                'ubicaciones_count' => is_array($logoSecciones) ? count($logoSecciones) : 0,
                'observaciones_generales' => $logoObservacionesGenerales,
                'observaciones_generales_count' => is_array($logoObservacionesGenerales) ? count($logoObservacionesGenerales) : 0,
            ]);
            
            Log::info('DEBUG - Antes de guardar fotos de logo:', [
                'logoCotizacion_existe' => !!$logoCotizacion,
                'logoCotizacion_id' => $logoCotizacion->id ?? null,
                'logoArchivos_count' => count($logoArchivos)
            ]);
            
            if ($logoCotizacion) {
                // Solo guardar fotos si la cotización de logo fue creada
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
                                
                                Log::info(' Logo foto CREADA EN BD', [
                                    'cotizacion_id' => $cotizacionId,
                                    'foto_id' => $fotoCreada->id ?? 'NULL',
                                    'logo_cotizacion_id' => $logoCotizacion->id,
                                    'ruta' => $ruta,
                                    'orden' => $orden
                                ]);
                            } catch (\Exception $e) {
                                Log::error(' ERROR al crear foto de logo', [
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
                
                // Si este request está actualizando un borrador (LogoCotizacion ya existía),
                // NO duplicar registros copiando las mismas fotos otra vez.
                if (!empty($fotoLogosExistentes) && $logoFueCreadoNuevo) {
                    // Deduplicar IDs
                    $fotoLogosExistentes = array_unique($fotoLogosExistentes);
                    $orden = 1;
                    
                    foreach ($fotoLogosExistentes as $fotoIdExistente) {
                        if ($fotoIdExistente && is_string($fotoIdExistente)) {
                            // Buscar la foto existente - usar find() para no lanzar error si no existe
                            $fotoExistente = \App\Models\LogoFotoCot::find($fotoIdExistente);
                            
                            // Si la foto no existe, simplemente continuar (puede haber sido eliminada)
                            if (!$fotoExistente) {
                                Log::warning(' Foto de logo no encontrada, ignorando', ['foto_id' => $fotoIdExistente]);
                                continue;
                            }
                            
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
                                
                                Log::info(' Foto de logo reutilizada (copiada)', [
                                    'nuevo_foto_id' => $fotoCopiadaCreada->id,
                                    'foto_original_id' => $fotoIdExistente,
                                    'logo_cotizacion_id' => $logoCotizacion->id,
                                    'ruta' => $fotoExistente->ruta_webp,
                                    'orden' => $orden
                                ]);
                                
                                $orden++;
                            } catch (\Exception $e) {
                                Log::warning(' Error al reutilizar foto de logo', [
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
            } else {
                Log::info(' Logo_cotizacion no fue creado - No se guardarán fotos de logo', [
                    'cotizacion_id' => $cotizacionId,
                ]);
            }

            //  PROCESAR PASO 3: TÉCNICAS DE LOGO (Para cotizaciones combinadas)
            // Las técnicas vienen en logo[tecnicas_agregadas] como JSON string
            // Solo procesar si logo_cotizacion fue creado (tiene información válida)
            
            if ($logoCotizacion) {
                $tecnicasAgregadasJson = $request->input('logo.tecnicas_agregadas', '[]');
                if (is_string($tecnicasAgregadasJson)) {
                    $tecnicasAgregadas = json_decode($tecnicasAgregadasJson, true) ?? [];
                } else {
                    $tecnicasAgregadas = (array)$tecnicasAgregadasJson;
                }

                // Normalizar estructuras para que el JSON sea estable (y el WHERE por string funcione)
                $normalizarArrayRecursivo = function ($v) use (&$normalizarArrayRecursivo) {
                    if (!is_array($v)) return $v;
                    foreach ($v as $k => $val) {
                        $v[$k] = $normalizarArrayRecursivo($val);
                    }
                    // Si es array asociativo, ordenar por clave; si es indexado, ordenar por valor (si aplica)
                    $keys = array_keys($v);
                    $esIndexado = ($keys === range(0, count($v) - 1));
                    if ($esIndexado) {
                        // Normalizar valores escalares
                        $allScalar = true;
                        foreach ($v as $val) {
                            if (is_array($val) || is_object($val)) { $allScalar = false; break; }
                        }
                        if ($allScalar) {
                            sort($v);
                        }
                    } else {
                        ksort($v);
                    }
                    return $v;
                };
                $jsonEstable = function ($v) use ($normalizarArrayRecursivo) {
                    if (is_string($v)) {
                        $decoded = json_decode($v, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $v = $decoded;
                        }
                    }
                    $v = is_array($v) ? $normalizarArrayRecursivo($v) : $v;
                    return json_encode($v ?? [], JSON_UNESCAPED_UNICODE);
                };
                
                Log::info(' Procesando técnicas agregadas para cotización combinada', [
                    'cotizacion_id' => $cotizacionId,
                    'logo_cotizacion_id' => $logoCotizacion->id ?? null,
                    'tecnicas_count' => count($tecnicasAgregadas),
                    'tecnicas_first' => count($tecnicasAgregadas) > 0 ? array_keys($tecnicasAgregadas[0] ?? []) : []
                ]);
                
                if (!empty($tecnicasAgregadas)) {
                    try {
                        // PASO 3 (cotización combinada): reutilizar la MISMA PrendaCot por nombre
                        // para que todas las técnicas (y sus imágenes) queden asociadas a una sola prenda.
                        // Cache por request/cotización.
                        $prendasCotPorNombre = [];

                        foreach ($tecnicasAgregadas as $tecnicaIndex => $tecnicaData) {
                            Log::info(" Procesando técnica {$tecnicaIndex}", [
                                'tipo_logo_id' => $tecnicaData['tipo_logo']['id'] ?? null,
                                'prendas_count' => count($tecnicaData['prendas'] ?? [])
                            ]);
                            
                            // Obtener tipo_logo_id de los datos
                            $tipoLogoId = $tecnicaData['tipo_logo']['id'] ?? null;
                            
                            if (!$tipoLogoId) {
                                Log::warning(' tipo_logo_id no encontrado en técnica', ['tecnica_index' => $tecnicaIndex]);
                                continue;
                            }
                        
                        // Procesar prendas de esta técnica
                        if (!empty($tecnicaData['prendas']) && is_array($tecnicaData['prendas'])) {
                            // Deduplicar prendas por nombre + ubicaciones + talla_cantidad
                            $prendasProcessadas = [];
                            $prendasKeys = [];
                            
                            foreach ($tecnicaData['prendas'] as $prendaIndex => $prendaData) {
                                //  VALIDAR: nombre_prenda no puede estar vacío
                                $nombrePrendaCompleto = $prendaData['nombre_prenda'] ?? '';
                                if (empty($nombrePrendaCompleto) || trim($nombrePrendaCompleto) === '') {
                                    Log::warning(' Prenda ignorada: nombre_prenda vacío', [
                                        'tecnica_index' => $tecnicaIndex,
                                        'prendaIndex' => $prendaIndex,
                                        'nombre_raw' => $prendaData['nombre_prenda'] ?? 'NULL'
                                    ]);
                                    continue; // Saltar esta prenda
                                }
                                
                                // Crear clave única para detectar duplicados
                                $prendaKey = md5(
                                    json_encode([
                                        'nombre' => $nombrePrendaCompleto,
                                        'ubicaciones' => $prendaData['ubicaciones'] ?? [],
                                        'talla_cantidad' => $prendaData['talla_cantidad'] ?? []
                                    ])
                                );
                                
                                // Si ya procesamos esta prenda exacta, saltar
                                if (in_array($prendaKey, $prendasKeys)) {
                                    Log::warning(' Prenda duplicada detectada y saltada', [
                                        'nombre' => $nombrePrendaCompleto,
                                        'prendaKey' => $prendaKey,
                                        'tecnica_index' => $tecnicaIndex
                                    ]);
                                    continue;
                                }
                                
                                $prendasKeys[] = $prendaKey;
                                
                                Log::info("   Procesando prenda {$prendaIndex} de técnica {$tecnicaIndex}");
                                
                                // PASO 3 (cotización combinada): ya NO depende de PASO 2.
                                // Crear (o reutilizar) una prenda en prendas_cot con el nombre escrito en el modal.
                                // IMPORTANTE: si se seleccionan múltiples técnicas, TODAS apuntan al mismo prenda_cot_id.
                                // Adicional: proteger contra duplicados consultando BD (firstOrCreate).
                                $nombreKey = trim(mb_strtoupper($nombrePrendaCompleto));
                                $nombreProductoNormalizado = $nombreKey;

                                if (!isset($prendasCotPorNombre[$nombreKey])) {
                                    // PASO 3 (combinada): permitir duplicados por nombre vs PASO 2.
                                    // No usar firstOrCreate por nombre, porque puede colisionar con prendas del PASO 2
                                    // (ej: cuando PASO 2 trae prenda_bodega=true).
                                    // Reutilizamos solo si ya existe una prenda previamente referenciada por técnicas de logo
                                    // para esta logo_cotizacion y ese nombre.
                                    $prendaCotExistentePaso3 = \App\Models\LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacion->id)
                                        ->whereHas('prendaCot', function ($q) use ($nombreProductoNormalizado) {
                                            $q->whereRaw('LOWER(nombre_producto) = ?', [strtolower($nombreProductoNormalizado)]);
                                        })
                                        ->orderByDesc('id')
                                        ->first();

                                    if ($prendaCotExistentePaso3 && $prendaCotExistentePaso3->prendaCot) {
                                        $prendasCotPorNombre[$nombreKey] = $prendaCotExistentePaso3->prendaCot;
                                        Log::info(' Prenda reutilizada desde técnicas existentes (PASO 3)', [
                                            'prenda_cot_id' => $prendasCotPorNombre[$nombreKey]->id,
                                            'nombre_producto' => $nombreProductoNormalizado,
                                            'cotizacion_id' => $cotizacionId,
                                        ]);
                                    } else {
                                        $prendasCotPorNombre[$nombreKey] = \App\Models\PrendaCot::create([
                                            'cotizacion_id' => $cotizacionId,
                                            'nombre_producto' => $nombreProductoNormalizado,
                                            'descripcion' => $prendaData['descripcion'] ?? ($prendaData['observaciones'] ?? ''),
                                            'texto_personalizado_tallas' => $prendaData['texto_personalizado_tallas'] ?? null,
                                            'cantidad' => $prendaData['cantidad'] ?? 1,
                                            'prenda_bodega' => true,
                                        ]);

                                        Log::info(' Prenda creada en prendas_cot (desde PASO 3)', [
                                            'prenda_cot_id' => $prendasCotPorNombre[$nombreKey]->id,
                                            'nombre_producto' => $nombreProductoNormalizado,
                                            'cotizacion_id' => $cotizacionId,
                                        ]);
                                    }
                                } else {
                                    Log::info(' Prenda reutilizada en memoria (desde PASO 3)', [
                                        'prenda_cot_id' => $prendasCotPorNombre[$nombreKey]->id,
                                        'nombre_producto' => $nombreProductoNormalizado,
                                        'cotizacion_id' => $cotizacionId
                                    ]);
                                }

                                $prendaCot = $prendasCotPorNombre[$nombreKey];
                                
                                // Verificar si ya existe un registro con la misma técnica, prenda y ubicaciones
                                $ubicacionesJson = $jsonEstable($prendaData['ubicaciones'] ?? []);
                                $tallaCantidadJson = $jsonEstable($prendaData['talla_cantidad'] ?? []);
                                
                                $logoCotizacionTecnicaPrendaExistente = \App\Models\LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacion->id)
                                    ->where('tipo_logo_id', $tipoLogoId)
                                    ->where('prenda_cot_id', $prendaCot->id)
                                    ->where('ubicaciones', $ubicacionesJson)
                                    ->where('talla_cantidad', $tallaCantidadJson)
                                    ->first();
                                
                                if ($logoCotizacionTecnicaPrendaExistente) {
                                    Log::info(' Registro duplicado detectado en logo_cotizacion_tecnica_prendas, no se crea nuevo', [
                                        'tecnica_id' => $logoCotizacionTecnicaPrendaExistente->id,
                                        'prenda_cot_id' => $prendaCot->id
                                    ]);
                                } else {
                                    // Crear técnica de logo con prenda
                                    $logoCotizacionTecnicaPrenda = \App\Models\LogoCotizacionTecnicaPrenda::create([
                                        'logo_cotizacion_id' => $logoCotizacion->id,
                                        'tipo_logo_id' => $tipoLogoId,
                                        'prenda_cot_id' => $prendaCot->id,
                                        'observaciones' => $prendaData['observaciones'] ?? '',
                                        'ubicaciones' => $ubicacionesJson,
                                        'talla_cantidad' => $tallaCantidadJson,
                                        'variaciones_prenda' => json_encode($prendaData['variaciones_prenda'] ?? []),
                                        'grupo_combinado' => $prendaIndex,  // Usar índice como grupo
                                    ]);
                                    
                                    Log::info(' Técnica guardada en logo_cotizacion_tecnica_prendas', [
                                        'tecnica_id' => $logoCotizacionTecnicaPrenda->id,
                                        'prenda_cot_id' => $prendaCot->id,
                                        'ubicaciones' => $prendaData['ubicaciones'] ?? []
                                    ]);
                                    
                                    // 📸 GUARDAR IMÁGENES DE LA TÉCNICA
                                    // Procesar imágenes desde prendaData['imagenes']
                                    if (isset($prendaData['imagenes']) && is_array($prendaData['imagenes']) && !empty($prendaData['imagenes'])) {
                                        $ordenFoto = 1;
                                        $maxImagenes = 5;
                                        
                                        foreach ($prendaData['imagenes'] as $imagen) {
                                            if ($ordenFoto > $maxImagenes) {
                                                break;
                                            }
                                            
                                            $rutaGuardar = null;
                                            
                                            // Si es imagen del PASO 2, usar la ruta directamente
                                            if (isset($imagen['tipo']) && $imagen['tipo'] === 'paso2' && isset($imagen['ruta'])) {
                                                $rutaGuardar = $imagen['ruta'];
                                                \Log::info('📸 Logo: Imagen del PASO 2 detectada', [
                                                    'tecnica' => $nombreTecnica ?? 'N/A',
                                                    'prenda' => $prendaData['nombre_prenda'],
                                                    'ruta' => $rutaGuardar,
                                                ]);
                                            }
                                            // Si es imagen nueva del PASO 3, crear ruta
                                            elseif (isset($imagen['tipo']) && $imagen['tipo'] === 'paso3') {
                                                \Log::info('📸 Logo: Imagen del PASO 3 detectada (será procesada después)', [
                                                    'tecnica' => $nombreTecnica ?? 'N/A',
                                                    'prenda' => $prendaData['nombre_prenda'],
                                                    'tipo' => 'archivo'
                                                ]);
                                            }
                                            
                                            // Guardar ruta en logo_cotizacion_tecnica_prendas_fotos
                                            if ($rutaGuardar) {
                                                try {
                                                    $yaExisteFoto = $logoCotizacionTecnicaPrenda->fotos()
                                                        ->where(function ($q) use ($rutaGuardar) {
                                                            $q->where('ruta_webp', $rutaGuardar)
                                                              ->orWhere('ruta_original', $rutaGuardar)
                                                              ->orWhere('ruta_miniatura', $rutaGuardar);
                                                        })
                                                        ->exists();

                                                    if ($yaExisteFoto) {
                                                        \Log::info(' Logo foto ya existe (no se duplica)', [
                                                            'tecnica_prenda_id' => $logoCotizacionTecnicaPrenda->id,
                                                            'ruta' => $rutaGuardar,
                                                        ]);
                                                        continue;
                                                    }

                                                    $logoCotizacionTecnicaPrenda->fotos()->create([
                                                        'ruta_original' => $rutaGuardar,
                                                        'ruta_webp' => $rutaGuardar,
                                                        'ruta_miniatura' => $rutaGuardar,
                                                        'orden' => $ordenFoto,
                                                    ]);
                                                    
                                                    \Log::info(' Logo foto guardada en BD', [
                                                        'tecnica_prenda_id' => $logoCotizacionTecnicaPrenda->id,
                                                        'ruta' => $rutaGuardar,
                                                        'orden' => $ordenFoto,
                                                    ]);
                                                    
                                                    $ordenFoto++;
                                                } catch (\Exception $e) {
                                                    \Log::error(' Error al guardar foto de técnica', [
                                                        'tecnica_prenda_id' => $logoCotizacionTecnicaPrenda->id,
                                                        'error' => $e->getMessage()
                                                    ]);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    Log::info(' Técnicas procesadas completamente', [
                        'cotizacion_id' => $cotizacionId,
                        'tecnicas_totales' => count($tecnicasAgregadas)
                    ]);
                    
                    //  PROCESAR IMÁGENES DEL PASO 3 - Guardar archivos en disco
                    // Las imágenes vienen en: logo[imagenes_paso3][tecnicaIndex][prendaIndex][imagenIndex]
                    
                    // Procesar FILES enviados por FormData - acceder directamente a logo.imagenes_paso3
                    $imagenesP3Files = $request->file('logo.imagenes_paso3');
                    
                    Log::info('📦 DEBUG: Archivos paso3 recibidos', [
                        'imagenesP3Files' => $imagenesP3Files,
                        'tipo' => gettype($imagenesP3Files),
                        'es_array' => is_array($imagenesP3Files) ? 'SÍ' : 'NO'
                    ]);
                    
                    $imagenesP3Archivos = [];
                    if ($imagenesP3Files && is_array($imagenesP3Files)) {
                        // Flatear recursivamente para obtener todos los archivos
                        $this->flatearArchivos($imagenesP3Files, $imagenesP3Archivos, 'logo[imagenes_paso3]');
                    }
                    
                    Log::info('📸 DEBUG - Archivos paso3 flaetados', [
                        'total' => count($imagenesP3Archivos),
                        'keys' => array_keys($imagenesP3Archivos)
                    ]);
                    
                    if (count($imagenesP3Archivos) > 0) {
                        Log::info('📸 Procesando imágenes del PASO 3', [
                            'cotizacion_id' => $cotizacionId,
                            'total_imagenes' => count($imagenesP3Archivos)
                        ]);

                        // Cache por request: si el mismo archivo se usa en múltiples técnicas (logo compartido),
                        // se procesa/guarda físicamente una sola vez y se reutiliza la misma ruta.
                        $logoPaso3PathCache = [];
                        
                        foreach ($imagenesP3Archivos as $fieldName => $archivo) {
                            // Coincide con patrón: logo[imagenes_paso3][{tecnicaIndex}][{prendaIndex}][{imagenIndex}]
                            if (preg_match('/^logo\[imagenes_paso3\]\[(\d+)\]\[(\d+)\]\[(\d+)\]$/', $fieldName, $matches)) {
                                $tecnicaIndex = (int)$matches[1];
                                $prendaIndex = (int)$matches[2];
                                $imagenIndex = (int)$matches[3];
                                
                                try {
                                    Log::info('📸 Procesando imagen del PASO 3', [
                                        'fieldName' => $fieldName,
                                        'tecnica_index' => $tecnicaIndex,
                                        'prenda_index' => $prendaIndex,
                                        'imagen_index' => $imagenIndex,
                                        'archivo_size' => $archivo->getSize()
                                    ]);
                                    
                                    // Obtener la técnica y prenda del array ya procesado
                                    if (isset($tecnicasAgregadas[$tecnicaIndex]) && isset($tecnicasAgregadas[$tecnicaIndex]['prendas'][$prendaIndex])) {
                                        $prendaData = $tecnicasAgregadas[$tecnicaIndex]['prendas'][$prendaIndex];
                                        
                                        // Buscar el registro de LogoCotizacionTecnicaPrenda correspondiente
                                        $nombrePrendaBase = explode(' - ', $prendaData['nombre_prenda'])[0];
                                        Log::info('🔍 Buscando prenda en BD', [
                                            'nombre_prenda_raw' => $prendaData['nombre_prenda'],
                                            'nombre_prenda_base' => $nombrePrendaBase
                                        ]);
                                        
                                        // IMPORTANTE (combinada): si existe prenda del PASO 2 y del PASO 3 con el mismo nombre,
                                        // las técnicas e imágenes del PASO 3 deben ir a la prenda del PASO 3 (prenda_bodega=true).
                                        // Reutilizar el cache de prendas creado durante el procesamiento de técnicas si está disponible.
                                        $nombreKeyImg = trim(mb_strtoupper($nombrePrendaBase));
                                        $prendaCot = null;

                                        if (isset($prendasCotPorNombre) && is_array($prendasCotPorNombre) && isset($prendasCotPorNombre[$nombreKeyImg])) {
                                            $prendaCot = $prendasCotPorNombre[$nombreKeyImg];
                                        } else {
                                            $prendaCot = \App\Models\PrendaCot::where('cotizacion_id', $cotizacionId)
                                                ->whereRaw('LOWER(nombre_producto) = ?', [strtolower($nombrePrendaBase)])
                                                ->where('prenda_bodega', true)
                                                ->orderByDesc('id')
                                                ->first();
                                        }
                                        
                                        if ($prendaCot) {
                                            $tipoLogoId = $tecnicasAgregadas[$tecnicaIndex]['tipo_logo']['id'];
                                            $ubicacionesJsonImg = $jsonEstable($prendaData['ubicaciones'] ?? []);
                                            $tallaCantidadJsonImg = $jsonEstable($prendaData['talla_cantidad'] ?? []);
                                            $logoCotizacionTecnicaPrenda = \App\Models\LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacion->id)
                                                ->where('tipo_logo_id', $tipoLogoId)
                                                ->where('prenda_cot_id', $prendaCot->id)
                                                ->where('ubicaciones', $ubicacionesJsonImg)
                                                ->where('talla_cantidad', $tallaCantidadJsonImg)
                                                ->first();
                                            
                                            if ($logoCotizacionTecnicaPrenda) {
                                                // Determinar orden de la foto
                                                $ordenFoto = $logoCotizacionTecnicaPrenda->fotos()->count() + 1;
                                                
                                                if ($ordenFoto > 5) {
                                                    Log::warning(' Máximo de imágenes alcanzado (5)', [
                                                        'prenda_id' => $logoCotizacionTecnicaPrenda->id
                                                    ]);
                                                    continue;
                                                }
                                                
                                                // Generar ruta de almacenamiento
                                                $rutaDirectorio = "cotizaciones/{$cotizacionId}/logo";
                                                $nombreArchivo = uniqid('img_paso3_');
                                                $rutaCompleta = $rutaDirectorio . '/' . $nombreArchivo . '.webp';
                                                
                                                // Usar el servicio para procesar y convertir a WebP (logo compartido: deduplicar)
                                                $cacheKey = null;
                                                try {
                                                    $realPath = $archivo->getRealPath();
                                                    if ($realPath && is_string($realPath) && file_exists($realPath)) {
                                                        $cacheKey = hash_file('sha256', $realPath);
                                                    }
                                                } catch (\Throwable $e) {
                                                    $cacheKey = null;
                                                }

                                                if ($cacheKey && isset($logoPaso3PathCache[$cacheKey])) {
                                                    $path = $logoPaso3PathCache[$cacheKey];
                                                    Log::info(' Logo compartido: reutilizando imagen ya procesada', [
                                                        'cacheKey' => $cacheKey,
                                                        'path' => $path,
                                                        'fieldName' => $fieldName,
                                                    ]);
                                                } else {
                                                    $path = $this->procesarImagenesService->procesarImagenLogo($archivo, $cotizacionId);
                                                    if ($cacheKey) {
                                                        $logoPaso3PathCache[$cacheKey] = $path;
                                                    }
                                                }
                                                
                                                Log::info(' Imagen del PASO 3 procesada y guardada como WebP', [
                                                    'path' => $path,
                                                    'ruta_completa' => $rutaCompleta
                                                ]);
                                                
                                                // Registrar en BD
                                                $logoCotizacionTecnicaPrenda->fotos()->create([
                                                    'ruta_original' => $path,
                                                    'ruta_webp' => $path, // Guardar ruta WebP
                                                    'ruta_miniatura' => $path, // Mismo archivo por ahora
                                                    'orden' => $ordenFoto,
                                                ]);
                                                
                                                Log::info(' Imagen del PASO 3 guardada en BD', [
                                                    'tecnica_prenda_id' => $logoCotizacionTecnicaPrenda->id,
                                                    'ruta' => $path,
                                                    'orden' => $ordenFoto
                                                ]);
                                            } else {
                                                Log::warning(' No se encontró registro de técnica para guardar imagen', [
                                                    'logo_cotizacion_id' => $logoCotizacion->id,
                                                    'tipo_logo_id' => $tipoLogoId,
                                                    'prenda_cot_id' => $prendaCot->id
                                                ]);
                                            }
                                        } else {
                                            Log::warning(' No se encontró prenda en BD para guardar imagen', [
                                                'nombre_prenda_base' => $nombrePrendaBase,
                                                'cotizacion_id' => $cotizacionId
                                            ]);
                                        }
                                    } else {
                                        Log::warning(' Índices fuera de rango', [
                                            'tecnica_index' => $tecnicaIndex,
                                            'prenda_index' => $prendaIndex,
                                            'tecnicas_count' => count($tecnicasAgregadas),
                                            'prendas_count' => isset($tecnicasAgregadas[$tecnicaIndex]) ? count($tecnicasAgregadas[$tecnicaIndex]['prendas']) : 0
                                        ]);
                                    }
                                } catch (\Exception $e) {
                                    Log::error(' Error procesando imagen del PASO 3', [
                                        'error' => $e->getMessage(),
                                        'fieldName' => $fieldName,
                                        'file' => $e->getFile(),
                                        'line' => $e->getLine()
                                    ]);
                                }
                            } elseif (preg_match('/^logo\[imagenes_paso3\]\[(\d+)\]$/', $fieldName, $matches)) {
                                // Fallback: array plano logo[imagenes_paso3][]
                                // Debe guardarse relacionado a técnica+prenda (logo_cotizacion_tecnica_prendas_fotos).
                                try {
                                    // Logo compartido: deduplicar el archivo físico
                                    $cacheKey = null;
                                    try {
                                        $realPath = $archivo->getRealPath();
                                        if ($realPath && is_string($realPath) && file_exists($realPath)) {
                                            $cacheKey = hash_file('sha256', $realPath);
                                        }
                                    } catch (\Throwable $e) {
                                        $cacheKey = null;
                                    }

                                    if ($cacheKey && isset($logoPaso3PathCache[$cacheKey])) {
                                        $path = $logoPaso3PathCache[$cacheKey];
                                        Log::info(' Logo compartido (fallback): reutilizando imagen ya procesada', [
                                            'cacheKey' => $cacheKey,
                                            'path' => $path,
                                            'fieldName' => $fieldName,
                                        ]);
                                    } else {
                                        $path = $this->procesarImagenesService->procesarImagenLogo($archivo, $cotizacionId);
                                        if ($cacheKey) {
                                            $logoPaso3PathCache[$cacheKey] = $path;
                                        }
                                    }

                                    $logoCotizacionTecnicaPrendaTarget = null;

                                    // Intentar asociar al único registro técnica/prenda si existe
                                    if ($logoCotizacion) {
                                        $queryTecnicaPrenda = \App\Models\LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacion->id)
                                            ->orderByDesc('id');

                                        $countTecnicaPrenda = (clone $queryTecnicaPrenda)->count();

                                        if ($countTecnicaPrenda === 1) {
                                            $logoCotizacionTecnicaPrendaTarget = $queryTecnicaPrenda->first();
                                        }

                                        // Si hay varias, usar la primera técnica+prenda del JSON (si existe)
                                        if (!$logoCotizacionTecnicaPrendaTarget && isset($tecnicasAgregadas[0]) && isset($tecnicasAgregadas[0]['prendas'][0])) {
                                            $prendaData = $tecnicasAgregadas[0]['prendas'][0];
                                            $tipoLogoId = $tecnicasAgregadas[0]['tipo_logo']['id'] ?? null;

                                            if ($tipoLogoId) {
                                                $nombrePrendaBase = explode(' - ', $prendaData['nombre_prenda'])[0];
                                                $nombreKeyImg = trim(mb_strtoupper($nombrePrendaBase));

                                                $prendaCot = null;
                                                if (isset($prendasCotPorNombre) && is_array($prendasCotPorNombre) && isset($prendasCotPorNombre[$nombreKeyImg])) {
                                                    $prendaCot = $prendasCotPorNombre[$nombreKeyImg];
                                                } else {
                                                    $prendaCot = \App\Models\PrendaCot::where('cotizacion_id', $cotizacionId)
                                                        ->whereRaw('LOWER(nombre_producto) = ?', [strtolower($nombrePrendaBase)])
                                                        ->where('prenda_bodega', true)
                                                        ->orderByDesc('id')
                                                        ->first();
                                                }

                                                if ($prendaCot) {
                                                    $logoCotizacionTecnicaPrendaTarget = \App\Models\LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacion->id)
                                                        ->where('tipo_logo_id', $tipoLogoId)
                                                        ->where('prenda_cot_id', $prendaCot->id)
                                                        ->first();
                                                }
                                            }
                                        }
                                    }

                                    if ($logoCotizacionTecnicaPrendaTarget) {
                                        $ordenFoto = $logoCotizacionTecnicaPrendaTarget->fotos()->count() + 1;

                                        $logoCotizacionTecnicaPrendaTarget->fotos()->create([
                                            'ruta_original' => $path,
                                            'ruta_webp' => $path,
                                            'ruta_miniatura' => $path,
                                            'orden' => $ordenFoto,
                                        ]);

                                        Log::info(' Imagen del PASO 3 guardada en logo_cotizacion_tecnica_prendas_fotos (array plano)', [
                                            'fieldName' => $fieldName,
                                            'cotizacion_id' => $cotizacionId,
                                            'logo_cotizacion_id' => $logoCotizacion->id ?? null,
                                            'logo_cotizacion_tecnica_prenda_id' => $logoCotizacionTecnicaPrendaTarget->id,
                                            'ruta' => $path,
                                            'orden' => $ordenFoto,
                                        ]);
                                    } else {
                                        Log::warning(' No se pudo asociar imagen PASO 3 (array plano) a técnica+prenda; no se guardará', [
                                            'fieldName' => $fieldName,
                                            'cotizacion_id' => $cotizacionId,
                                            'logo_cotizacion_id' => $logoCotizacion->id ?? null,
                                            'ruta' => $path,
                                        ]);
                                    }
                                } catch (\Exception $e) {
                                    Log::error(' Error guardando imagen PASO 3 (array plano) en técnica+prenda', [
                                        'fieldName' => $fieldName,
                                        'error' => $e->getMessage(),
                                    ]);
                                }
                            }
                        }
                        
                        Log::info(' Procesamiento de imágenes PASO 3 completado', [
                            'total_procesadas' => count($imagenesP3Archivos)
                        ]);
                    } else {
                        Log::info(' No hay imágenes del PASO 3 para procesar');
                    }
                    
                } catch (\Exception $e) {
                        Log::error(' Error procesando técnicas', [
                            'cotizacion_id' => $cotizacionId,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } else {
                    Log::info(' No hay técnicas agregadas para procesar');
                }
            } else {
                Log::info(' Logo_cotizacion no existe - No se procesarán técnicas de logo', [
                    'cotizacion_id' => $cotizacionId
                ]);
            }

            // Procesar PASO 4: REFLECTIVO
            \Log::info('🔍 DEBUG PASO 4 - ALL REQUEST INPUTS', [
                'keys' => array_keys($request->all())
            ]);
            \Log::info('🔍 DEBUG PASO 4 - REFLECTIVO REQUEST', [
                'reflectivo_data' => $request->input('reflectivo', [])
            ]);
            
            // Obtener descripción y garantizar que es string (no null)
            $reflectivoDescripcionRaw = $request->input('reflectivo.descripcion');
            $reflectivoDescripcion = (string)($reflectivoDescripcionRaw ?? '');  // Convertir null a string vacía
            
            \Log::info('🔍 DEBUG PASO 4 REFLECTIVO - Inicio', [
                'cotizacion_id' => $cotizacionId,
                'reflectivo_descripcion_raw' => $reflectivoDescripcionRaw,
                'reflectivo_descripcion' => $reflectivoDescripcion,
                'reflectivo_descripcion_empty' => empty($reflectivoDescripcion),
                'reflectivo_descripcion_type' => gettype($reflectivoDescripcion),
                'reflectivo_descripcion_length' => strlen($reflectivoDescripcion ?? ''),
                'reflectivo_input' => $request->input('reflectivo', ''),
                'all_keys' => array_keys($request->all()),
            ]);
            
            // Obtener ubicación desde 'ubicaciones_reflectivo' (array JSON) o 'reflectivo.ubicacion' (string legacy)
            $ubicacionesData = $request->input('ubicaciones_reflectivo', '[]');
            
            \Log::info(' DEBUG storeReflectivo - Datos recibidos:', [
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
            
            \Log::info(' DEBUG storeReflectivo - Ubicaciones después de decode:', [
                'ubicaciones_array' => $ubicacionesArray,
                'ubicaciones_count' => count($ubicacionesArray),
                'array_structure' => json_encode($ubicacionesArray),
            ]);
            
            $reflectivoUbicacion = !empty($ubicacionesArray) ? json_encode($ubicacionesArray) : ($request->input('reflectivo.ubicacion', '') ?? '[]');
            
            \Log::info(' DEBUG storeReflectivo - Ubicación final a guardar:', [
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
            
            // Guardar reflectivo SI Y SOLO SI hay información válida (ubicaciones O descripción+imágenes)
            // El backend intenta guardar reflectivo para CADA prenda del PASO 2
            $prendas = \App\Models\PrendaCot::where('cotizacion_id', $cotizacionId)->get();
            
            // 📦 Obtener datos de prendas del PASO 4 (reflectivo)
            // Estos contienen ubicaciones específicas para cada prenda
            $prendasReflectivoPaso4 = $request->input('prendas_reflectivo_paso4', []);
            if (is_string($prendasReflectivoPaso4)) {
                $prendasReflectivoPaso4 = json_decode($prendasReflectivoPaso4, true) ?? [];
            }
            
            \Log::info('📦 Prendas reflectivo PASO 4 recibidas:', [
                'count' => count($prendasReflectivoPaso4),
                'prendasReflectivoPaso4' => json_encode($prendasReflectivoPaso4),
            ]);
            
            //  VALIDAR si reflectivo tiene información escrita válida
            // Prioridad: Si hay prendas_reflectivo_paso4, usar eso para validar
            $tieneInfoValidaDesdeP4 = !empty($prendasReflectivoPaso4) && count($prendasReflectivoPaso4) > 0;
            
            // Si no hay datos en P4, validar usando los datos unificados (fallback)
            $tieneUbicacionesReflectivo = !empty($reflectivoUbicacion) && $reflectivoUbicacion !== '[]' && $reflectivoUbicacion !== '{}';
            $tieneDescripcionReflectivo = !empty($reflectivoDescripcion);
            $tieneImagenesReflectivo = !empty($reflectivoArchivos) && count($reflectivoArchivos) > 0;
            
            $reflectivoTieneInfoValida = $tieneInfoValidaDesdeP4 || $tieneUbicacionesReflectivo || ($tieneDescripcionReflectivo && $tieneImagenesReflectivo);
            
            \Log::info('📦 Prendas encontradas PARA REFLECTIVO - VALIDACIÓN', [
                'prendas_count' => $prendas->count(),
                'tieneInfoValidaDesdeP4' => $tieneInfoValidaDesdeP4,
                'tieneUbicacionesReflectivo' => $tieneUbicacionesReflectivo,
                'tieneDescripcionReflectivo' => $tieneDescripcionReflectivo,
                'tieneImagenesReflectivo' => $tieneImagenesReflectivo,
                'reflectivoTieneInfoValida' => $reflectivoTieneInfoValida,
            ]);
            
            if ($prendas->count() > 0 && $reflectivoTieneInfoValida) {
                try {
                    foreach ($prendas as $prenda) {
                        \Log::info(' Guardando reflectivo para prenda', [
                            'prenda_id' => $prenda->id,
                            'cotizacion_id' => $cotizacionId,
                            'descripcion' => $reflectivoDescripcion,
                        ]);
                        
                        //  Cargar variaciones de la prenda desde la BD
                        $variacionesPrenda = [];
                        if ($prenda->variantes && $prenda->variantes->count() > 0) {
                            foreach ($prenda->variantes as $variante) {
                                // Decodificar telas_multiples si es string, si no usar directamente
                                $telasMultiples = $variante->telas_multiples;
                                if (is_string($telasMultiples)) {
                                    $telasMultiples = json_decode($telasMultiples, true) ?? [];
                                } elseif (!is_array($telasMultiples)) {
                                    $telasMultiples = [];
                                }
                                
                                $variacionesPrenda[] = [
                                    'id' => $variante->id,
                                    'tipo_manga_id' => $variante->tipo_manga_id,
                                    'tipo_broche_id' => $variante->tipo_broche_id,
                                    'genero_id' => $variante->genero_id,
                                    'color' => $variante->color,
                                    'tiene_bolsillos' => $variante->tiene_bolsillos,
                                    'obs_bolsillos' => $variante->obs_bolsillos,
                                    'obs_broche' => $variante->obs_broche,
                                    'tiene_reflectivo' => $variante->tiene_reflectivo,
                                    'descripcion_adicional' => $variante->descripcion_adicional,
                                    'telas_multiples' => $telasMultiples,
                                ];
                            }
                        }
                        
                        $variacionesJson = !empty($variacionesPrenda) ? json_encode($variacionesPrenda) : null;
                        
                        \Log::info(' Variaciones cargadas para prenda', [
                            'prenda_id' => $prenda->id,
                            'variaciones_count' => count($variacionesPrenda),
                            'variaciones_json' => $variacionesJson,
                        ]);
                        
                        // Crear o actualizar reflectivo_cotizaciones para esta prenda
                        $reflectivoCotizacion = \App\Models\ReflectivoCotizacion::updateOrCreate(
                            [
                                'cotizacion_id' => $cotizacionId,
                                'prenda_cot_id' => $prenda->id,
                            ],
                            [
                                'descripcion' => $reflectivoDescripcion,
                                'observaciones_generales' => is_array($reflectivoObservacionesGenerales) ? json_encode($reflectivoObservacionesGenerales) : $reflectivoObservacionesGenerales,
                            ]
                        );
                        
                        \Log::info(' Reflectivo guardado en reflectivo_cotizacion', [
                            'reflectivo_id' => $reflectivoCotizacion->id,
                            'prenda_cot_id' => $prenda->id,
                            'cotizacion_id' => $cotizacionId,
                        ]);
                        
                        //  BUSCAR UBICACIONES Y DESCRIPCIÓN ESPECÍFICAS DE ESTA PRENDA desde prendas_reflectivo_paso4
                        // Cada prenda puede tener sus propias ubicaciones y descripción
                        $ubicacionesEspecificasPrenda = [];
                        $descripcionEspecificaPrenda = null;
                        
                        foreach ($prendasReflectivoPaso4 as $prendaReflectivo) {
                            // Comparar por nombre de prenda - usar 'tipo_prenda' (clave en el PASO 4)
                            if (isset($prendaReflectivo['tipo_prenda']) && 
                                $prendaReflectivo['tipo_prenda'] === $prenda->nombre_producto) {
                                
                                // Obtener ubicaciones
                                if (!empty($prendaReflectivo['ubicaciones'])) {
                                    $ubicacionesEspecificasPrenda = $prendaReflectivo['ubicaciones'];
                                }
                                
                                // Obtener descripción si existe
                                if (!empty($prendaReflectivo['descripcion'])) {
                                    $descripcionEspecificaPrenda = $prendaReflectivo['descripcion'];
                                }
                                
                                break;
                            }
                        }
                        
                        //  Asegurar que ubicaciones sea JSON válido
                        $ubicacionesFinal = !empty($ubicacionesEspecificasPrenda) 
                            ? json_encode($ubicacionesEspecificasPrenda)
                            : json_encode([]);
                        
                        \Log::info(' Ubicaciones y descripción específicas para prenda:', [
                            'prenda_nombre' => $prenda->nombre_producto,
                            'ubicaciones_encontradas' => $ubicacionesEspecificasPrenda,
                            'descripcion_encontrada' => $descripcionEspecificaPrenda,
                            'ubicaciones_json' => $ubicacionesFinal,
                        ]);
                        
                        // Guardar en prenda_cot_reflectivo con variaciones, ubicaciones y descripción
                        $prendaCotReflectivo = \App\Models\PrendaCotReflectivo::updateOrCreate(
                            [
                                'cotizacion_id' => $cotizacionId,
                                'prenda_cot_id' => $prenda->id,
                            ],
                            [
                                'variaciones' => $variacionesJson,  // Variaciones traídas del PASO 2
                                'ubicaciones' => $ubicacionesFinal,  // Ubicaciones específicas del reflectivo para esta prenda
                                'descripcion' => $descripcionEspecificaPrenda,  // Descripción escrita en el paso 4

                            ]
                        );
                        
                        \Log::info(' Reflectivo guardado en prenda_cot_reflectivo', [
                            'prenda_cot_reflectivo_id' => $prendaCotReflectivo->id,
                            'cotizacion_id' => $cotizacionId,
                            'prenda_cot_id' => $prenda->id,
                            'variaciones_guardadas' => $variacionesJson ? 'SÍ' : 'NO',
                            'ubicaciones' => $ubicacionesFinal,
                        ]);
                    }
                    
                    // Guardar imágenes del reflectivo (máximo 3)
                    // Procesar imágenes desde prendas_reflectivo_paso4
                    $ordenFoto = 1;
                    $maxImagenes = 3;
                    
                    foreach ($prendas as $prenda) {
                        $reflectivoCotizacion = \App\Models\ReflectivoCotizacion::where('cotizacion_id', $cotizacionId)
                            ->where('prenda_cot_id', $prenda->id)
                            ->first();
                        
                        if (!$reflectivoCotizacion) {
                            continue;
                        }
                        
                        // Buscar imágenes específicas de esta prenda desde prendas_reflectivo_paso4
                        foreach ($prendasReflectivoPaso4 as $prendaReflectivo) {
                            if (isset($prendaReflectivo['tipo_prenda']) && 
                                $prendaReflectivo['tipo_prenda'] === $prenda->nombre_producto &&
                                !empty($prendaReflectivo['imagenes'])) {
                                
                                $ordenFoto = 1;
                                
                                foreach ($prendaReflectivo['imagenes'] as $imagen) {
                                    if ($ordenFoto > $maxImagenes) {
                                        break;
                                    }
                                    
                                    $rutaGuardar = null;
                                    
                                    //  IMPORTANTE: Solo guardar imágenes del PASO 2 que tienen ruta guardada en BD
                                    // Las imágenes del PASO 4 (nuevas) se procesan SOLO desde FormData, NO del JSON
                                    // El JSON contiene preview (base64) que NUNCA debe guardarse directamente
                                    
                                    if (isset($imagen['tipo']) && $imagen['tipo'] === 'paso2' && isset($imagen['ruta'])) {
                                        $rutaGuardar = $imagen['ruta'];
                                        \Log::info('📸 Reflectivo: Imagen del PASO 2 detectada', [
                                            'prenda' => $prenda->nombre_producto,
                                            'ruta' => $rutaGuardar,
                                        ]);
                                    } elseif (isset($imagen['tipo']) && $imagen['tipo'] === 'paso4') {
                                        //  Las imágenes del PASO 4 DEBEN procesarse desde FormData (archivos)
                                        // NO desde el JSON que contiene base64
                                        \Log::info('📸 Reflectivo: Imagen del PASO 4 - Será procesada desde FormData', [
                                            'prenda' => $prenda->nombre_producto,
                                            'es_archivo' => isset($imagen['file']) ? 'SÍ' : 'NO (será procesada desde FormData)',
                                        ]);
                                        // NO asignar $rutaGuardar aquí - dejar que se procese desde FormData
                                        continue;
                                    }
                                    
                                    // Guardar la ruta en reflectivo_fotos_cotizacion (solo PASO 2)
                                    if ($rutaGuardar) {
                                        try {
                                            $reflectivoCotizacion->fotos()->create([
                                                'ruta_original' => $rutaGuardar,
                                                'ruta_webp' => $rutaGuardar,
                                                'orden' => $ordenFoto,
                                            ]);
                                            
                                            \Log::info(' Reflectivo foto (PASO 2) guardada en BD', [
                                                'reflectivo_id' => $reflectivoCotizacion->id,
                                                'ruta' => $rutaGuardar,
                                                'orden' => $ordenFoto,
                                            ]);
                                            
                                            $ordenFoto++;
                                        } catch (\Exception $e) {
                                            \Log::error(' Error guardando foto de reflectivo del PASO 2', [
                                                'reflectivo_id' => $reflectivoCotizacion->id,
                                                'error' => $e->getMessage()
                                            ]);
                                        }
                                    }
                                }
                                break;
                            }
                        }
                    }
                    
                    //  PROCESAR IMÁGENES DEL PASO 4 (Reflectivo) - Guardar archivos en disco
                    // Las imágenes vienen en: reflectivo[imagenes_paso4][prendaIndex][imagenIndex]
                    
                    // 🔍 DEBUG: Check all files in request
                    $allFilesInRequest = $request->allFiles();
                    \Log::debug('DEBUG - Todos los archivos en request:', $allFilesInRequest);
                    
                    $imagenesP4ReflectivoFiles = $request->file('reflectivo.imagenes_paso4');
                    \Log::debug('DEBUG - imagenesP4ReflectivoFiles:', [$imagenesP4ReflectivoFiles]);
                    
                    $imagenesP4ReflectivoArchivos = [];
                    if ($imagenesP4ReflectivoFiles && is_array($imagenesP4ReflectivoFiles)) {
                        \Log::debug('DEBUG - Entrando a flatearArchivos con imagenesP4ReflectivoFiles');
                        $this->flatearArchivos($imagenesP4ReflectivoFiles, $imagenesP4ReflectivoArchivos, 'reflectivo[imagenes_paso4]');
                    } else {
                        \Log::debug('DEBUG - imagenesP4ReflectivoFiles es null o no es array', ['imagenesP4ReflectivoFiles' => $imagenesP4ReflectivoFiles]);
                    }
                    
                    if (count($imagenesP4ReflectivoArchivos) > 0) {
                        
                        foreach ($imagenesP4ReflectivoArchivos as $fieldName => $archivo) {
                            // Coincide con patrón: reflectivo[imagenes_paso4][{prendaIndex}][{imagenIndex}]
                            if (preg_match('/^reflectivo\[imagenes_paso4\]\[(\d+)\]\[(\d+)\]$/', $fieldName, $matches)) {
                                $prendaIndex = (int)$matches[1];
                                $imagenIndex = (int)$matches[2];
                                
                                try {
                                    if (isset($prendasReflectivoPaso4[$prendaIndex])) {
                                        $prendaReflectivoData = $prendasReflectivoPaso4[$prendaIndex];
                                        $nombrePrendaBase = $prendaReflectivoData['tipo_prenda'];
                                        
                                        $prendaCot = \App\Models\PrendaCot::where('cotizacion_id', $cotizacionId)
                                            ->whereRaw('LOWER(nombre_producto) = ?', [strtolower($nombrePrendaBase)])
                                            ->first();
                                        
                                        if ($prendaCot) {
                                            $reflectivoCotizacion = \App\Models\ReflectivoCotizacion::where('cotizacion_id', $cotizacionId)
                                                ->where('prenda_cot_id', $prendaCot->id)
                                                ->first();
                                            
                                            if ($reflectivoCotizacion) {
                                                $ordenFoto = $reflectivoCotizacion->fotos()->count() + 1;
                                                
                                                if ($ordenFoto > 3) {
                                                    continue;
                                                }
                                                
                                                $rutaDirectorio = "cotizaciones/{$cotizacionId}/reflectivo";
                                                $nombreArchivo = uniqid('img_reflectivo_') . '.' . $archivo->getClientOriginalExtension();
                                                $rutaCompleta = $rutaDirectorio . '/' . $nombreArchivo;
                                                
                                                // Guardar archivo en disco (public/storage)
                                                $path = $archivo->store($rutaDirectorio, 'public');
                                                
                                                // Registrar en BD (reflectivo_fotos_cotizacion)
                                                $reflectivoCotizacion->fotos()->create([
                                                    'ruta_original' => $path,
                                                    'ruta_webp' => $path,
                                                    'orden' => $ordenFoto,
                                                ]);
                                                
                                                //  LOG DETALLADO - Solo cuando se guarda correctamente
                                                Log::info('
╔════════════════════════════════════════════════════════════════╗
║      IMAGEN REFLECTIVO GUARDADA CORRECTAMENTE - PASO 4       ║
╠════════════════════════════════════════════════════════════════╣
║ Cotización ID: ' . $cotizacionId . '
║ Prenda: ' . $nombrePrendaBase . '
║ Archivo: ' . $archivo->getClientOriginalName() . '
║ Tamaño: ' . round($archivo->getSize() / 1024, 2) . ' KB
║ Tipo: ' . $archivo->getClientMimeType() . '
║ 
║ ALMACENADO EN:
║ Directorio: ' . $rutaDirectorio . '
║ Ruta BD: ' . $path . '
║ Orden: ' . $ordenFoto . '/3
║ 
║ TABLA: reflectivo_fotos_cotizacion
║ ID Reflectivo: ' . $reflectivoCotizacion->id . '
╚════════════════════════════════════════════════════════════════╝
                                                ');
                                            } else {
                                                Log::warning(' No se encontró reflectivo_cotizacion', ['prenda' => $nombrePrendaBase]);
                                            }
                                        } else {
                                            Log::warning(' No se encontró prenda en BD', ['nombre_prenda' => $nombrePrendaBase]);
                                        }
                                    }
                                } catch (\Exception $e) {
                                    Log::error(' Error procesando imagen reflectivo PASO 4', ['error' => $e->getMessage()]);
                                }
                            }
                        }
                    }

                    if (!empty($reflectivoArchivos)) {
                        $ordenFoto = 1;
                        
                        foreach ($reflectivoArchivos as $foto) {
                            if ($ordenFoto > $maxImagenes) {
                                \Log::warning(' Se alcanzó el límite de 3 imágenes para reflectivo', [
                                    'cotizacion_id' => $cotizacionId,
                                ]);
                                break;
                            }
                            
                            if ($foto instanceof \Illuminate\Http\UploadedFile) {
                                // Procesar la imagen usando el servicio existente
                                $rutaWebP = $this->procesarImagenesService->procesarImagenLogo($foto, $cotizacionId);
                                
                                // Guardar en reflectivo_fotos_cotizacion para cada prenda
                                foreach ($prendas as $prenda) {
                                    $reflectivoCotizacion = \App\Models\ReflectivoCotizacion::where('cotizacion_id', $cotizacionId)
                                        ->where('prenda_cot_id', $prenda->id)
                                        ->first();
                                    
                                    if ($reflectivoCotizacion) {
                                        $reflectivoCotizacion->fotos()->create([
                                            'ruta_original' => $rutaWebP,
                                            'ruta_webp' => $rutaWebP,
                                            'orden' => $ordenFoto,
                                        ]);
                                        
                                        \Log::info(' Reflectivo foto (PASO 4) guardada', [
                                            'reflectivo_id' => $reflectivoCotizacion->id,
                                            'ruta' => $rutaWebP,
                                            'orden' => $ordenFoto,
                                        ]);
                                    }
                                }
                                
                                $ordenFoto++;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error(' Error al guardar reflectivo', [
                        'cotizacion_id' => $cotizacionId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            } else {
                \Log::warning(' Reflectivo NO será guardado - Validación falló', [
                    'cotizacion_id' => $cotizacionId,
                    'prendas_count' => $prendas->count(),
                    'reflectivoTieneInfoValida' => $reflectivoTieneInfoValida,
                    'tieneInfoValidaDesdeP4' => $tieneInfoValidaDesdeP4,
                    'prendasReflectivoPaso4' => $prendasReflectivoPaso4,
                    'reflectivoDescripcion' => $reflectivoDescripcion,
                    'reflectivoUbicacion' => $reflectivoUbicacion,
                    'reflectivoArchivos_count' => count($reflectivoArchivos),
                ]);
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

            // DEBUG: Log de todo lo que recibe
            Log::info('🔍 DEBUG - Datos recibidos en storeReflectivo:', [
                'all_inputs' => $request->all(),
                'prendas_input' => $request->input('prendas'),
                'prendas_type' => gettype($request->input('prendas')),
                'prendas_strlen' => strlen($request->input('prendas') ?? ''),
            ]);

            // Validar datos básicos
            $validated = $request->validate([
                'cliente' => 'required|string|max:255',
                'asesora' => 'nullable|string|max:255',
                'fecha' => 'required|date',
                'action' => 'required|in:borrador,enviar',
                'tipo' => 'required|in:RF',
                'tipo_venta_reflectivo' => 'nullable|in:M,D,X',
                'prendas' => 'nullable', // Cambiar de required|string a nullable para debug
                'especificaciones' => 'nullable|string',
                'descripcion_reflectivo' => 'required|string',
                'ubicaciones_reflectivo' => 'nullable',
                'observaciones_generales' => 'nullable',
                'imagenes_reflectivo.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            // Verificar si prendas es string o array (debug)
            $prendasInput = $request->input('prendas');
            
            // Convertir a string si viene como array (bug de FormData multipart)
            if (is_array($prendasInput)) {
                $prendasInput = json_encode($prendasInput);
            }
            
            if (is_null($prendasInput)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El campo prendas es null. Verificar envío de FormData.',
                    'errors' => [
                        'prendas' => ['Campo no recibido o null'],
                        'debug_all_keys' => array_keys($request->all()),
                    ]
                ], 422);
            }

            if (!is_string($prendasInput)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El campo prendas debe ser string JSON. Recibido: ' . gettype($prendasInput),
                    'errors' => [
                        'prendas' => ['Tipo incorrecto: ' . gettype($prendasInput) . ', esperado: string'],
                    ]
                ], 422);
            }

            if (strlen($prendasInput) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'El campo prendas está vacío.',
                    'errors' => [
                        'prendas' => ['String vacío'],
                    ]
                ], 422);
            }
            
            // Usar el string convertido para el resto del método
            $validated['prendas'] = $prendasInput;

            // Decodificar prendas del JSON string
            $prendas = json_decode($validated['prendas'], true);
            
            Log::info(' DEBUG storeReflectivo - Prendas recibidas:', [
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
            
            Log::info(' DEBUG storeReflectivo - Especificaciones recibidas:', [
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
                Log::info(' Cliente obtenido/creado', ['cliente_id' => $cliente->id]);

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

                Log::info(' Cotización RF creada', [
                    'cotizacion_id' => $cotizacion->id,
                    'especificaciones_guardadas' => $cotizacion->especificaciones,
                    'especificaciones_type' => gettype($cotizacion->especificaciones),
                    'especificaciones_count' => is_array($cotizacion->especificaciones) ? count($cotizacion->especificaciones) : 0,
                ]);

                //  PROCESAR PRENDAS Y CREAR UN REFLECTIVO POR CADA PRENDA
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
                            // IMPORTANTE: Para cotizaciones de reflectivo INDIVIDUAL, prenda_bodega siempre es true
                            $prendaCot = \App\Models\PrendaCot::create([
                                'cotizacion_id' => $cotizacion->id,
                                'nombre_producto' => $prenda['tipo'] ?? $prenda['nombre'] ?? 'Prenda',
                                'cantidad' => 1,
                                'descripcion' => $prenda['descripcion'] ?? '',
                                'prenda_bodega' => true,
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
                                Log::info(' Tallas guardadas para prenda', [
                                    'prenda_cot_id' => $prendaCot->id,
                                    'tallas_count' => count($prenda['tallas']),
                                    'tallas' => $prenda['tallas'],
                                    'cantidades' => $cantidades
                                ]);
                            }

                            // 2b.  GUARDAR GÉNERO EN prenda_variantes_cot SI EXISTE
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
                                    Log::info(' Género guardado en prenda_variantes_cot', [
                                        'prenda_cot_id' => $prendaCot->id,
                                        'genero' => $prenda['genero'],
                                        'genero_id' => $generoId
                                    ]);
                                }
                            }

                            // 2c.  GUARDAR VARIACIONES Y UBICACIONES EN prenda_cot_reflectivo
                            $variacionesDePrenda = $prenda['variaciones'] ?? [];
                            if (is_string($variacionesDePrenda)) {
                                $variacionesDePrenda = json_decode($variacionesDePrenda, true) ?? [];
                            }

                            $ubicacionesDePrenda = $prenda['ubicaciones'] ?? [];
                            if (is_string($ubicacionesDePrenda)) {
                                $ubicacionesDePrenda = json_decode($ubicacionesDePrenda, true) ?? [];
                            }

                            // Procesar color, tela y referencia de esta prenda
                            $colorTelaRef = $prenda['color_tela_ref'] ?? [];
                            if (is_string($colorTelaRef)) {
                                $colorTelaRef = json_decode($colorTelaRef, true) ?? [];
                            }

                            // Crear registro en prenda_cot_reflectivo con variaciones, ubicaciones y color_tela_ref
                            \App\Models\PrendaCotReflectivo::create([
                                'cotizacion_id' => $cotizacion->id,
                                'prenda_cot_id' => $prendaCot->id,
                                'variaciones' => !empty($variacionesDePrenda) ? json_encode($variacionesDePrenda) : json_encode([]),
                                'ubicaciones' => !empty($ubicacionesDePrenda) ? json_encode($ubicacionesDePrenda) : json_encode([]),
                                'color_tela_ref' => !empty($colorTelaRef) ? json_encode($colorTelaRef) : json_encode([]),
                            ]);

                            Log::info(' PrendaCotReflectivo creado con variaciones, ubicaciones y color_tela_ref', [
                                'prenda_cot_id' => $prendaCot->id,
                                'variaciones_count' => count($variacionesDePrenda),
                                'ubicaciones_count' => count($ubicacionesDePrenda),
                                'color_tela_ref_count' => count($colorTelaRef),
                            ]);

                            // 3.  CREAR REFLECTIVO ESPECÍFICO PARA ESTA PRENDA
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
                                'prenda_cot_id' => $prendaCot->id,  //  Vinculado a la prenda
                                'descripcion' => $validated['descripcion_reflectivo'],
                                'tipo_venta' => $validated['tipo_venta_reflectivo'] ?? null,
                                'ubicacion' => json_encode($ubicacionesDePrenda),
                                'observaciones_generales' => json_encode($observacionesDePrenda),
                                'imagenes' => json_encode([]),
                            ]);

                            Log::info(' ReflectivoCotizacion creado para prenda', [
                                'reflectivo_id' => $reflectivo->id,
                                'prenda_cot_id' => $prendaCot->id,
                                'ubicaciones_count' => count($ubicacionesDePrenda)
                            ]);

                            // 4.  PROCESAR IMÁGENES DE ESTA PRENDA ESPECÍFICA
                            // Las imágenes vienen con el nombre: imagenes_reflectivo_prenda_{index}[] o imagenes_reflectivo_prenda_{index}
                            $campoImagenes = "imagenes_reflectivo_prenda_{$prendaIndex}";
                            
                            Log::info(' BUSCANDO IMÁGENES', [
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
                                Log::info(' ENCONTRADAS IMÁGENES PARA PRENDA', [
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
                                        // Guardar archivo en webp
                                        $nombreImagen = 'reflectivo_' . time() . '_' . uniqid() . '.webp';
                                        $rutaDirectorio = "cotizaciones/{$cotizacion->id}/reflectivo";
                                        $ruta = Storage::disk('public')->putFileAs(
                                            $rutaDirectorio,
                                            $archivo,
                                            $nombreImagen
                                        );
                                        
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
                                Log::info(' NO HAY IMÁGENES PARA ESTA PRENDA', [
                                    'campo' => $campoImagenes,
                                    'prenda_index' => $prendaIndex,
                                    'todos_los_archivos' => json_encode(array_keys($request->allFiles())),
                                ]);
                            }

                            // 5.  PROCESAR IMÁGENES DE TELA DE ESTA PRENDA ESPECÍFICA
                            // Las imágenes de tela vienen en: prendas[prendaIndex][telas][telaIndex][fotos][]
                            $allFiles = $request->allFiles();
                            if (isset($allFiles['prendas']) && is_array($allFiles['prendas']) && isset($allFiles['prendas'][$prendaIndex])) {
                                $prendaFiles = $allFiles['prendas'][$prendaIndex];
                                
                                if (isset($prendaFiles['telas']) && is_array($prendaFiles['telas'])) {
                                    Log::info('  Procesando telas de prenda', [
                                        'prenda_index' => $prendaIndex,
                                        'telas_count' => count($prendaFiles['telas']),
                                    ]);
                                    
                                    foreach ($prendaFiles['telas'] as $telaIndex => $telaData) {
                                        // Obtener info de la tela desde color_tela_ref
                                        $colorTelaRef = $prenda['color_tela_ref'] ?? [];
                                        $telaInfo = [];
                                        if (!empty($colorTelaRef)) {
                                            foreach ($colorTelaRef as $ctRef) {
                                                if (($ctRef['indice'] ?? null) === (int)$telaIndex) {
                                                    $telaInfo = $ctRef;
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        // Procesar fotos de tela
                                        if (isset($telaData['fotos']) && is_array($telaData['fotos'])) {
                                            Log::info('   Encontradas fotos de tela', [
                                                'prenda_index' => $prendaIndex,
                                                'tela_index' => $telaIndex,
                                                'fotos_count' => count($telaData['fotos']),
                                                'color' => $telaInfo['color'] ?? '',
                                                'tela' => $telaInfo['tela'] ?? '',
                                                'referencia' => $telaInfo['referencia'] ?? '',
                                            ]);
                                            
                                            $ordenFoto = 1;
                                            $numTelaGlobal = $telaIndex + 1; // Enumerar telas desde 1
                                            
                                            foreach ($telaData['fotos'] as $archivoFoto) {
                                                if ($archivoFoto && $archivoFoto instanceof \Illuminate\Http\UploadedFile && $archivoFoto->isValid()) {
                                                    try {
                                                        // Obtener extensión del archivo
                                                        $extension = $archivoFoto->getClientOriginalExtension();
                                                        
                                                        // Crear nombre enumerado: img_tela_1, img_tela_2, etc.
                                                        $nombreArchivo = "img_tela_{$numTelaGlobal}.{$extension}";
                                                        
                                                        // Guardar en storage con nombre enumerado
                                                        $rutaGuardada = $archivoFoto->storeAs(
                                                            "cotizaciones/{$cotizacion->id}/telas",
                                                            $nombreArchivo,
                                                            'public'
                                                        );
                                                        $rutaUrl = \Storage::url($rutaGuardada);
                                                        
                                                        // Guardar en tabla prenda_tela_fotos_cot
                                                        \DB::table('prenda_tela_fotos_cot')->insert([
                                                            'prenda_cot_id' => $prendaCot->id,
                                                            'prenda_tela_cot_id' => null,  // Sin relación específica en reflectivo
                                                            'tela_index' => $telaIndex,
                                                            'ruta_original' => $rutaUrl,
                                                            'ruta_webp' => null,
                                                            'ruta_miniatura' => null,
                                                            'orden' => $ordenFoto,
                                                            'ancho' => null,
                                                            'alto' => null,
                                                            'tamaño' => $archivoFoto->getSize(),
                                                            'created_at' => now(),
                                                            'updated_at' => now(),
                                                        ]);
                                                        
                                                        Log::info('    Foto de tela guardada para reflectivo', [
                                                            'prenda_id' => $prendaCot->id,
                                                            'tela_index' => $telaIndex,
                                                            'nombre_guardado' => $nombreArchivo,
                                                            'ruta' => $rutaUrl,
                                                            'referencia' => $telaInfo['referencia'] ?? '',
                                                            'orden' => $ordenFoto,
                                                        ]);
                                                        
                                                        $ordenFoto++;
                                                        $numTelaGlobal++; // Incrementar para la siguiente foto
                                                    } catch (\Exception $e) {
                                                        Log::error('    Error guardando foto de tela para reflectivo', [
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
                        } else {
                            Log::warning(' PRENDA NO ES ARRAY', [
                                'prenda_index' => $prendaIndex,
                                'prenda_type' => gettype($prenda),
                                'prenda_value' => $prenda,
                            ]);
                        }
                    }
                    $prendasCount = is_array($prendas) ? count($prendas) : 0;
                    Log::info(' LOOP COMPLETADO - Prendas y reflectivos guardados', [
                        'cotizacion_id' => $cotizacion->id,
                        'prendas_count' => $prendasCount,
                        'imagenes_totales_guardadas' => count($imagenesGuardadas),
                    ]);
                } else {
                    Log::warning(' NO HAY PRENDAS PARA PROCESAR');
                }

                DB::commit();

                // Recargar cotización con relaciones (incluyendo fotos)
                $cotizacionCompleta = \App\Models\Cotizacion::with([
                    'cliente',
                    'reflectivoCotizacion.fotos',
                ])->findOrFail($cotizacion->id);

                Log::info(' CotizacionController@storeReflectivo - Exitoso', [
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
            Log::error(' Error de validación', ['errores' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errores' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error(' CotizacionController@storeReflectivo: Error', [
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

            //  Decodificar JSON strings cuando vienen de FormData con _method=PUT
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
                    
                    Log::info(' DEBUG Especificaciones en updateReflectivo', [
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
                        Log::info(' Actualizando especificaciones con nuevos datos');
                    } else {
                        Log::info(' Preservando especificaciones existentes (nuevas están vacías)');
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

                Log::info(' Cotización RF actualizada', ['cotizacion_id' => $cotizacion->id]);

                //  ACTUALIZAR PRENDAS Y SUS REFLECTIVOS (NUEVO SISTEMA)
                if (isset($validated['prendas']) && is_array($validated['prendas'])) {
                    // 1.  PRESERVAR FOTOS EXISTENTES ANTES DE ELIMINAR
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
                            
                            Log::info(' Preservando fotos de prenda', [
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
                            // IMPORTANTE: Para cotizaciones de reflectivo INDIVIDUAL, prenda_bodega siempre es true
                            $prendaCot = \App\Models\PrendaCot::create([
                                'cotizacion_id' => $cotizacion->id,
                                'nombre_producto' => $prenda['tipo'] ?? $prenda['nombre'] ?? 'Prenda',
                                'cantidad' => 1,
                                'descripcion' => $prenda['descripcion'] ?? '',
                                'prenda_bodega' => true,
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

                            //  CREAR REFLECTIVO PARA ESTA PRENDA CON SUS UBICACIONES
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

                            Log::info(' Prenda y reflectivo actualizados', [
                                'prenda_cot_id' => $prendaCot->id,
                                'reflectivo_id' => $reflectivo->id,
                                'ubicaciones_count' => count($ubicacionesDePrenda)
                            ]);

                            //  PROCESAR IMÁGENES DE ESTA PRENDA
                            $campoImagenes = "imagenes_reflectivo_prenda_{$prendaIndex}";
                            $nuevasFotosGuardadas = false;
                            
                            if ($request->hasFile($campoImagenes)) {
                                // Hay nuevas fotos subidas - guardarlas
                                $orden = 1;
                                foreach ($request->file($campoImagenes) as $archivo) {
                                    if ($archivo && $archivo->isValid()) {
                                        // Guardar archivo en webp
                                        $nombreImagen = 'reflectivo_' . time() . '_' . uniqid() . '.webp';
                                        $rutaDirectorio = "cotizaciones/{$id}/reflectivo";
                                        $ruta = Storage::disk('public')->putFileAs(
                                            $rutaDirectorio,
                                            $archivo,
                                            $nombreImagen
                                        );
                                        
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
                    Log::info(' Prendas y reflectivos actualizados', ['cotizacion_id' => $cotizacion->id, 'prendas_count' => count($prendasArray)]);
                }

                // NOTA: Ya no usamos un reflectivo global, cada prenda tiene el suyo

                DB::commit();

                // Recargar cotización con relaciones actualizadas
                $cotizacionCompleta = \App\Models\Cotizacion::with([
                    'cliente',
                    'prendas.reflectivo.fotos'
                ])->findOrFail($cotizacion->id);

                Log::info(' CotizacionController@updateReflectivo - Exitoso', [
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
            Log::error(' Error de validación', ['errores' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errores' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error(' CotizacionController@updateReflectivo: Error', [
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
     * Flatear recursivamente array de archivos para obtener todos los File objects
     */
    private function flatearArchivos(&$archivos, &$resultado, $prefijo = ''): void
    {
        if (!is_array($archivos)) {
            return;
        }

        foreach ($archivos as $key => $valor) {
            $nuevaLlave = $prefijo . '[' . $key . ']';
            
            if ($valor instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                // Es un archivo, agregarlo al resultado
                $resultado[$nuevaLlave] = $valor;
                Log::info(' Archivo encontrado durante flateo', [
                    'key' => $nuevaLlave,
                    'size' => $valor->getSize()
                ]);
            } elseif (is_array($valor)) {
                // Es un array, recursionar
                $this->flatearArchivos($valor, $resultado, $nuevaLlave);
            }
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
                1 => '/asesores/cotizaciones/create?tipo=PB&editar={id}',  // Combinada (Prenda + Logo)
                2 => '/asesores/cotizaciones/bordado/crear?editar={id}',  // Logo only
                3 => '/asesores/cotizaciones/create?tipo=P&editar={id}',   // Prenda only
                4 => null, // Reflectivo se maneja especialmente
            ];

            $tipoCotizacionId = $cotizacion->tipo_cotizacion_id ?? 1;  // Default to Combinada (ID 1)

            if ($tipoCotizacionId === 1) {
                $logoCotizacion = $cotizacion->logoCotizacion()
                    ->withCount(['tecnicasPrendas', 'fotos'])
                    ->first();

                $logoVacio = !$logoCotizacion
                    || (((int)($logoCotizacion->tecnicas_prendas_count ?? 0)) === 0
                        && ((int)($logoCotizacion->fotos_count ?? 0)) === 0
                        && empty($logoCotizacion->observaciones_generales));

                if ($logoVacio) {
                    $tipoCotizacionId = 3;
                }
            }

            // Si es Reflectivo (tipo 4), mostrar la vista
            if ($tipoCotizacionId === 4) {
                //  Cargar datos completos del reflectivo CON TALLAS, FOTOS Y REFLECTIVO POR PRENDA
                $cotizacion->load([
                    'cliente',
                    'prendas',
                    'prendas.tallas',
                    'prendas.fotos',              //  AGREGAR: Cargar fotos de prendas
                    'prendas.variantes',          //  Cargar variantes (para genero_id)
                    'prendas.reflectivo.fotos'    //  Cargar reflectivo de cada prenda
                ]);
                
                // Preparar datos iniciales en formato JSON
                $prendasConTallas = $cotizacion->prendas ? $cotizacion->prendas->map(function($prenda) {
                    $prendasArray = $prenda->toArray();
                    // Forzar inclusión de tallas
                    $prendasArray['tallas'] = $prenda->tallas ? $prenda->tallas->map(function($talla) {
                        return $talla->talla; // Solo el nombre de la talla
                    })->toArray() : [];
                    
                    //  Incluir cantidades por talla
                    $prendasArray['cantidades'] = [];
                    if ($prenda->tallas) {
                        foreach ($prenda->tallas as $talla) {
                            $prendasArray['cantidades'][$talla->talla] = (int)$talla->cantidad;
                        }
                    }
                    
                    //  Incluir género desde prenda_variantes_cot
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
                    
                    //  Forzar inclusión de fotos de la prenda
                    $prendasArray['fotos'] = $prenda->fotos ? $prenda->fotos->toArray() : [];
                    
                    //  Incluir reflectivo específico de esta prenda
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
                    'prendas' => $prendasConTallas,  //  Cada prenda incluye su propio reflectivo
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
            $ruta = $mapeoTipos[$tipoCotizacionId] ?? null;
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
            
            //  Buscar y eliminar de ReflectivoCotizacionFoto (fotos de reflectivo)
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
