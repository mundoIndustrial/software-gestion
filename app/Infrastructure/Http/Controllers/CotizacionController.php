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
use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
use App\Application\Services\Cotizacion\ActualizarCotizacionService;
use App\Application\Services\Cotizacion\ActualizarImagenesCotizacionService;
use App\Application\Services\Cotizacion\AnularCotizacionService;
use App\Application\Services\Cotizacion\BorrarImagenPrendaService;
use App\Application\Services\Cotizacion\BorrarImagenTelaService;
use App\Application\Services\Cotizacion\EliminarBorradorCotizacionService;
use App\Application\Services\Cotizacion\EliminarFotoInmediatamenteService;
use App\Application\Services\Cotizacion\ObtenerTipoCotizacionIdService;
use App\Application\Services\Cotizacion\ProcesarImagenesCotizacionOrchestratorService;
use App\Infrastructure\Http\Mappers\ActualizarImagenesCotizacionRequestMapper;
use App\Infrastructure\Http\Mappers\ActualizarCotizacionRequestMapper;
use App\Infrastructure\Http\Mappers\StoreCotizacionRequestMapper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCotizacionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


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
        private readonly GenerarNumeroCotizacionService $generarNumeroCotizacionService,
        private readonly ActualizarCotizacionService $actualizarCotizacionService,
        private readonly ActualizarImagenesCotizacionService $actualizarImagenesCotizacionService,
        private readonly ActualizarImagenesCotizacionRequestMapper $actualizarImagenesCotizacionRequestMapper,
        private readonly ActualizarCotizacionRequestMapper $actualizarCotizacionRequestMapper,
        private readonly StoreCotizacionRequestMapper $storeCotizacionRequestMapper,
        private readonly AnularCotizacionService $anularCotizacionService,
        private readonly EliminarBorradorCotizacionService $eliminarBorradorCotizacionService,
        private readonly ProcesarImagenesCotizacionOrchestratorService $procesarImagenesCotizacionOrchestratorService,
        private readonly BorrarImagenPrendaService $borrarImagenPrendaService,
        private readonly BorrarImagenTelaService $borrarImagenTelaService,
        private readonly EliminarFotoInmediatamenteService $eliminarFotoInmediatamenteService,
        private readonly ObtenerTipoCotizacionIdService $obtenerTipoCotizacionIdService,
    ) {
    }

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

    public function show(int $id): JsonResponse
    {
        try {
           
            $cotizacion = \App\Models\Cotizacion::with([
                'prendas.fotos',
                'prendas.telas',
                'prendas.tallas',
                'prendas.variantes.genero',
                'prendas.variantes.manga',
                'prendas.variantes.broche',
                'prendas.logoCotizacionesTecnicas',
                'cliente'
            ])->findOrFail($id);

            if ($cotizacion->asesor_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
            }

            $data = $cotizacion->toArray();

            $data['prendas'] = $cotizacion->prendas
                ->filter(function ($prenda) {
                    return $prenda->logoCotizacionesTecnicas->isEmpty();
                })
                ->values()
                ->map(function ($prenda) {
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

  
    public function getReflectivoForEdit(int $id): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Tipo de cotización no soportado',
        ], 404);
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
                'prendas.logoCotizacionesTecnicas',
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

            $data = $cotizacion->toArray();
            $data['prendas'] = $cotizacion->prendas
                ->filter(function ($prenda) {
                    return $prenda->logoCotizacionesTecnicas->isEmpty();
                })
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => $data,
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
            
            $this->borrarImagenPrendaService->ejecutar((int) $fotoId);
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen borrada exitosamente'
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
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

            $this->borrarImagenTelaService->ejecutar((int) $fotoId);
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen borrada exitosamente'
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
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
    public function store(StoreCotizacionRequest $request): JsonResponse
    {
        try {
            $cotizacionIdExistente = $request->input('cotizacion_id');
            if ($cotizacionIdExistente) {
                Log::info('CotizacionController@store: Detectada actualización de borrador existente', [
                    'cotizacion_id' => $cotizacionIdExistente,
                ]);
                return $this->update($request, (int)$cotizacionIdExistente);
            }

            $usuarioId = Auth::id();
            $dto = $this->storeCotizacionRequestMapper->map($request, (int) $usuarioId);

            if (!$dto->esBorrador) {
                $usuarioIdVO = \App\Domain\Shared\ValueObjects\UserId::crear((int) $usuarioId);
                $numeroCotizacion = $this->generarNumeroCotizacionService->generarProxNumeroCotizacion($usuarioIdVO);

                Log::info('CotizacionController@store: Número de cotización generado con servicio seguro', [
                    'usuario_id' => $usuarioId,
                    'numero' => $numeroCotizacion,
                    'numero_formateado' => $this->generarNumeroCotizacionService->formatearNumero($numeroCotizacion),
                ]);

                $dto = CrearCotizacionDTO::desdeArray(array_merge(
                    $dto->toArray(),
                    ['numero_cotizacion' => $numeroCotizacion]
                ));
            }

            $comando = CrearCotizacionCommand::crear($dto);
            $cotizacionDTO = $this->crearHandler->handle($comando);

            // Obtener el ID de la cotización desde el DTO
            $cotizacionId = $cotizacionDTO->toArray()['id'] ?? null;

            // Procesar imágenes DESPUÉS de crear la cotización (para tener el ID)
            if ($cotizacionId) {
                $this->procesarImagenesCotizacionOrchestratorService->ejecutar($request, $cotizacionId);
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

            $dtoActualizarCotizacion = $this->actualizarCotizacionRequestMapper->map($request);
            $cotizacion = $this->actualizarCotizacionService->ejecutar($cotizacion, $dtoActualizarCotizacion);

            $dtoActualizarImagenes = $this->actualizarImagenesCotizacionRequestMapper->map($request, $dtoActualizarCotizacion->prendasRecibidas);
            $this->actualizarImagenesCotizacionService->ejecutar($cotizacion, $dtoActualizarImagenes);
            
            // Procesar nuevas imágenes DESPUÉS de actualizar logo
            $this->procesarImagenesCotizacionOrchestratorService->ejecutar($request, $id);

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
     * Deshabilitado: el tipo Reflectivo ya no está soportado.
     */
    public function storeReflectivo(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Tipo de cotización no soportado',
        ], 404);
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
            
            if (!$rutaFoto) {
                return response()->json(['success' => false, 'message' => 'Ruta de foto no proporcionada'], 400);
            }
            $fotoId = $request->input('foto_id');

            $fotosEliminadas = $this->eliminarFotoInmediatamenteService->ejecutar((string) $rutaFoto, $fotoId ? (int) $fotoId : null);
            
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
            $this->eliminarBorradorCotizacionService->ejecutar($cotizacion, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Borrador eliminado correctamente'
            ]);
        } catch (\DomainException $e) {
            $status = $e->getMessage() === 'Solo se pueden eliminar borradores' ? 400 : 403;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $status);
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

        try {
            $cotizacion = \App\Models\Cotizacion::findOrFail($id);
            $nombreUsuario = auth()->user()->name;

            $cotizacion = $this->anularCotizacionService->ejecutar(
                $cotizacion,
                Auth::id(),
                (string) $request->novedad,
                (string) $nombreUsuario,
            );
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cotización anulada correctamente',
            'cotizacion' => $cotizacion,
        ]);
    }

    private function obtenerTipoCotizacionId(string $tipo): int
    {
        return $this->obtenerTipoCotizacionIdService->ejecutar($tipo);
    }
}
