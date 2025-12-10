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
            $query = ObtenerCotizacionQuery::crear($id, Auth::id());
            $cotizacion = $this->obtenerHandler->handle($query);

            return view('asesores.cotizaciones.show', [
                'cotizacion' => $cotizacion,
            ]);
        } catch (\DomainException $e) {
            abort(403, $e->getMessage());
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
            Log::info('CotizacionController@store: Datos recibidos', [
                'tipo' => $request->input('tipo'),
                'cliente' => $request->input('cliente'),
                'tipo_venta' => $request->input('tipo_venta'),
                'especificaciones' => $request->input('especificaciones'),
                'productos_count' => count($request->input('productos', [])),
            ]);

            // Obtener o crear cliente si se proporciona nombre
            $clienteId = $request->input('cliente_id');
            $nombreCliente = $request->input('cliente');
            $tipoOperacion = $request->input('tipo'); // 'borrador' o 'enviada'

            if ($nombreCliente && !$clienteId) {
                $cliente = $this->obtenerOCrearClienteService->ejecutar($nombreCliente);
                $clienteId = $cliente->id;
                Log::info('Cliente creado/obtenido', ['cliente_id' => $clienteId, 'nombre' => $nombreCliente]);
            }

            // Si es 'enviada', es_borrador = false. Si es 'borrador', es_borrador = true
            $esBorrador = ($tipoOperacion === 'borrador');

            Log::info('CotizacionController@store: Lógica aplicada', [
                'tipo_operacion' => $tipoOperacion,
                'es_borrador' => $esBorrador,
                'cliente_id' => $clienteId,
            ]);

            $dto = CrearCotizacionDTO::desdeArray([
                'usuario_id' => Auth::id(),
                'tipo' => $request->input('tipo_cotizacion', 'P'),
                'cliente_id' => $clienteId,
                'productos' => $request->input('productos', []),
                'logo' => $request->input('logo', []),
                'tipo_venta' => $request->input('tipo_venta', 'M'),
                'especificaciones' => $request->input('especificaciones', []),
                'es_borrador' => $esBorrador,
            ]);

            $comando = CrearCotizacionCommand::crear($dto);
            $cotizacionDTO = $this->crearHandler->handle($comando);

            // Obtener el ID de la cotización desde el DTO
            $cotizacionId = $cotizacionDTO->toArray()['id'] ?? null;

            // Procesar imágenes DESPUÉS de crear la cotización (para tener el ID)
            if (!$esBorrador && $cotizacionId) {
                $this->procesarImagenesCotizacion($request, $cotizacionId);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cotización creada exitosamente',
                'data' => $cotizacionDTO->toArray(),
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
            $productos = $request->input('productos', []);
            $allFiles = $request->allFiles();

            Log::info('Procesando imágenes de cotización', [
                'cotizacion_id' => $cotizacionId,
                'productos_count' => count($productos),
                'all_files_keys' => array_keys($allFiles),
                'all_files_structure' => json_encode($allFiles, JSON_UNESCAPED_SLASHES),
            ]);

            foreach ($productos as $index => $producto) {
                // Obtener la prenda guardada
                $prenda = \App\Models\PrendaCot::where('cotizacion_id', $cotizacionId)
                    ->skip($index)
                    ->first();

                if (!$prenda) {
                    Log::warning('Prenda no encontrada', ['cotizacion_id' => $cotizacionId, 'index' => $index]);
                    continue;
                }

                Log::info('Procesando prenda', ['prenda_id' => $prenda->id, 'index' => $index]);

                // Procesar imágenes de prenda - buscar archivos por patrón
                $fotosArchivos = [];
                $allFiles = $request->allFiles();
                
                // Buscar archivos que coincidan con el patrón fotos_producto_{index}*
                foreach ($allFiles as $key => $file) {
                    if (strpos($key, "fotos_producto_{$index}") === 0) {
                        if (is_array($file)) {
                            $fotosArchivos = array_merge($fotosArchivos, $file);
                        } else {
                            $fotosArchivos[] = $file;
                        }
                    }
                }
                
                Log::info('Fotos encontradas', [
                    'index' => $index,
                    'count' => count($fotosArchivos),
                    'all_files_keys' => array_keys($allFiles),
                ]);

                if (!empty($fotosArchivos)) {
                    foreach ($fotosArchivos as $foto) {
                        if ($foto instanceof \Illuminate\Http\UploadedFile) {
                            $ruta = $this->procesarImagenesService->procesarImagenPrenda(
                                $foto,
                                $cotizacionId,
                                $prenda->id
                            );

                            $prenda->fotos()->create([
                                'ruta_original' => $ruta,
                                'ruta_webp' => $ruta,
                                'tipo' => 'prenda',
                                'orden' => 1,
                            ]);

                            Log::info('Foto de prenda guardada', ['prenda_id' => $prenda->id, 'ruta' => $ruta]);
                        }
                    }
                }

                // Procesar imágenes de telas
                $telasArchivos = $request->file("productos.{$index}.telas") ?? [];
                
                // Normalizar a array (puede ser un UploadedFile único o un array)
                if ($telasArchivos instanceof \Illuminate\Http\UploadedFile) {
                    $telasArchivos = [$telasArchivos];
                } elseif (!is_array($telasArchivos)) {
                    $telasArchivos = [];
                }
                
                Log::info('Telas encontradas', ['key' => "productos.{$index}.telas", 'count' => count($telasArchivos)]);

                if (!empty($telasArchivos)) {
                    foreach ($telasArchivos as $foto) {
                        if ($foto instanceof \Illuminate\Http\UploadedFile) {
                            $ruta = $this->procesarImagenesService->procesarImagenTela(
                                $foto,
                                $cotizacionId,
                                $prenda->id
                            );

                            $prenda->telaFotos()->create([
                                'ruta_original' => $ruta,
                                'ruta_webp' => $ruta,
                                'orden' => 1,
                            ]);

                            Log::info('Foto de tela guardada', ['prenda_id' => $prenda->id, 'ruta' => $ruta]);
                        }
                    }
                }
            }

            // Procesar imágenes de logo
            $logoArchivos = $request->file('logo') ?? [];
            if (!empty($logoArchivos) && is_array($logoArchivos)) {
                foreach ($logoArchivos as $foto) {
                    if ($foto instanceof \Illuminate\Http\UploadedFile) {
                        $ruta = $this->procesarImagenesService->procesarImagenLogo($foto, $cotizacionId);
                        Log::info('Logo procesado', ['cotizacion_id' => $cotizacionId, 'ruta' => $ruta]);
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
