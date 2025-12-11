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
use App\Application\Cotizacion\Queries\ObtenerCotizacionQuery;
use App\Application\Cotizacion\Services\ObtenerOCrearClienteService;
use App\Application\Services\ProcesarImagenesCotizacionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
            $query = ObtenerCotizacionQuery::crear($id, Auth::id());
            $cotizacion = $this->obtenerHandler->handle($query);

            return response()->json([
                'success' => true,
                'data' => $cotizacion->toArray(),
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('CotizacionController@show: Error', ['error' => $e->getMessage()]);
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

            if ($nombreCliente && !$clienteId) {
                $cliente = $this->obtenerOCrearClienteService->ejecutar($nombreCliente);
                $clienteId = $cliente->id;
                Log::info('Cliente creado/obtenido', ['cliente_id' => $clienteId, 'nombre' => $nombreCliente]);
            }

            // Si acción es 'guardar', es_borrador = true. Si es 'enviar', es_borrador = false
            $esBorrador = ($accion === 'guardar');
            $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR';

            Log::info('CotizacionController@store: Lógica aplicada', [
                'accion' => $accion,
                'es_borrador' => $esBorrador,
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
            // FormData envía múltiples archivos con [] al final: logo[imagenes][]
            $logoArchivos = $request->file('logo.imagenes') ?? [];
            
            // Si no encuentra, buscar con .0 al final
            if (empty($logoArchivos)) {
                $logoArchivos = $request->file('logo.imagenes.0') ?? [];
            }
            
            // Si aún no encuentra, buscar en allFiles
            if (empty($logoArchivos)) {
                $allFiles = $request->allFiles();
                $logoArchivos = $allFiles['logo.imagenes'] ?? [];
            }
            
            // Normalizar a array (puede ser un UploadedFile único o un array)
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
            $logoCotizacion = \App\Models\LogoCotizacion::updateOrCreate(
                ['cotizacion_id' => $cotizacionId],
                [
                    'descripcion' => $logoDescripcion,
                    'tecnicas' => is_array($logoTecnicas) ? json_encode($logoTecnicas) : $logoTecnicas,
                    'observaciones_tecnicas' => $logoObservacionesTecnicas,
                    'ubicaciones' => is_array($logoUbicaciones) ? json_encode($logoUbicaciones) : $logoUbicaciones,
                    'observaciones_generales' => is_array($logoObservacionesGenerales) ? json_encode($logoObservacionesGenerales) : $logoObservacionesGenerales,
                ]
            );
            
            Log::info('Logo datos guardados', [
                'cotizacion_id' => $cotizacionId,
                'descripcion' => $logoDescripcion,
                'tecnicas' => $logoTecnicas,
                'tecnicas_count' => is_array($logoTecnicas) ? count($logoTecnicas) : 0,
                'ubicaciones' => $logoUbicaciones,
                'ubicaciones_count' => is_array($logoUbicaciones) ? count($logoUbicaciones) : 0,
                'observaciones_generales' => $logoObservacionesGenerales,
                'observaciones_generales_count' => is_array($logoObservacionesGenerales) ? count($logoObservacionesGenerales) : 0,
            ]);
            
            if (!empty($logoArchivos)) {
                $orden = 1;
                foreach ($logoArchivos as $foto) {
                    if ($foto instanceof \Illuminate\Http\UploadedFile) {
                        $ruta = $this->procesarImagenesService->procesarImagenLogo($foto, $cotizacionId);
                        
                        // Guardar en logo_fotos_cot (múltiples fotos con orden incremental)
                        $logoCotizacion->fotos()->create([
                            'ruta_original' => $ruta,
                            'ruta_webp' => $ruta,
                            'orden' => $orden,
                        ]);
                        $orden++;
                        
                        Log::info('Logo foto guardada', ['cotizacion_id' => $cotizacionId, 'ruta' => $ruta, 'orden' => $orden - 1]);
                    }
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
}
