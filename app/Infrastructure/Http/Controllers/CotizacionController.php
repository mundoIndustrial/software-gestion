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
            $dto = CrearCotizacionDTO::desdeArray([
                'usuario_id' => Auth::id(),
                'tipo' => $request->input('tipo', 'P'),
                'cliente_id' => $request->input('cliente_id'),
                'productos' => $request->input('productos', []),
                'logo' => $request->input('logo', []),
                'tipo_venta' => $request->input('tipo_venta', 'M'),
                'es_borrador' => $request->boolean('es_borrador', true),
            ]);

            $comando = CrearCotizacionCommand::crear($dto);
            $cotizacion = $this->crearHandler->handle($comando);

            return response()->json([
                'success' => true,
                'message' => 'Cotización creada exitosamente',
                'data' => $cotizacion->toArray(),
            ], 201);
        } catch (\Exception $e) {
            Log::error('CotizacionController@store: Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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
