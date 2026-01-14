<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\Actions\Procesos\CrearProcesoAction;
use App\Domain\Procesos\Repositories\ProcesoPrendaDetalleRepository;
use App\Domain\Procesos\Repositories\ProcesoPrendaImagenRepository;
use App\Domain\Procesos\Repositories\TipoProcesoRepository;
use App\Domain\Procesos\Services\AprobarProcesoPrendaService;
use App\Domain\Procesos\Services\RechazarProcesoPrendaService;
use App\Domain\Procesos\Services\SubirImagenProcesoService;
use App\DTOs\CrearProcesoPrendaDTO;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ProcesosController extends Controller
{
    public function __construct(
        private CrearProcesoAction $crearProcesoAction,
        private AprobarProcesoPrendaService $aprobarService,
        private RechazarProcesoPrendaService $rechazarService,
        private SubirImagenProcesoService $subirImagenService,
        private ProcesoPrendaDetalleRepository $procesoRepository,
        private ProcesoPrendaImagenRepository $imagenRepository,
        private TipoProcesoRepository $tipoProcesoRepository,
    ) {}

    /**
     * Obtener tipos de procesos disponibles
     * GET /api/procesos/tipos
     */
    public function tipos()
    {
        try {
            $tipos = $this->tipoProcesoRepository->obtenerActivos();

            return response()->json([
                'success' => true,
                'data' => array_map(fn($tipo) => $tipo->toArray(), $tipos),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de procesos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener procesos de una prenda
     * GET /api/prendas/{id}/procesos
     */
    public function obtenerPorPrenda($prendaId)
    {
        try {
            $procesos = $this->procesoRepository->obtenerPorPrenda($prendaId);

            return response()->json([
                'success' => true,
                'data' => array_map(fn($p) => $p->toArray(), $procesos),
                'total' => count($procesos),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener procesos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear nuevo proceso
     * POST /api/prendas/{id}/procesos
     */
    public function crear(Request $request, $prendaId)
    {
        try {
            // Validar request
            $validated = $request->validate([
                'tipo_proceso_id' => [
                    'required',
                    'integer',
                    Rule::exists('tipos_procesos', 'id')->where('activo', true),
                ],
                'ubicaciones' => 'required|array|min:1',
                'ubicaciones.*' => 'string|min:1|max:100',
                'observaciones' => 'nullable|string|max:1000',
                'tallas_dama' => 'nullable|array',
                'tallas_dama.*' => 'string',
                'tallas_caballero' => 'nullable|array',
                'tallas_caballero.*' => 'string',
                'imagen' => 'nullable|string', // base64
                'datos_adicionales' => 'nullable|array',
            ]);

            // Crear DTO
            $dto = CrearProcesoPrendaDTO::fromRequest([
                ...$validated,
                'imagen' => $request->input('imagen'),
            ], $prendaId);

            // Ejecutar action
            $proceso = $this->crearProcesoAction->ejecutar($dto);

            return response()->json([
                'success' => true,
                'message' => 'Proceso creado correctamente',
                'data' => $proceso->toArray(),
            ], 201);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear proceso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar proceso
     * PUT /api/procesos/{id}
     */
    public function actualizar(Request $request, $procesoId)
    {
        try {
            $proceso = $this->procesoRepository->obtenerPorId($procesoId);

            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado',
                ], 404);
            }

            if (!$proceso->puedeSerEditado()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este proceso no puede ser editado en estado: ' . $proceso->getEstado(),
                ], 422);
            }

            // Validar request
            $validated = $request->validate([
                'ubicaciones' => 'nullable|array|min:1',
                'ubicaciones.*' => 'string|min:1|max:100',
                'observaciones' => 'nullable|string|max:1000',
                'tallas_dama' => 'nullable|array',
                'tallas_dama.*' => 'string',
                'tallas_caballero' => 'nullable|array',
                'tallas_caballero.*' => 'string',
                'datos_adicionales' => 'nullable|array',
            ]);

            // Actualizar entity
            if (isset($validated['ubicaciones'])) {
                $proceso->setUbicaciones($validated['ubicaciones']);
            }
            if (isset($validated['observaciones'])) {
                $proceso->setObservaciones($validated['observaciones']);
            }
            if (isset($validated['tallas_dama'])) {
                $proceso->setTallasDama($validated['tallas_dama']);
            }
            if (isset($validated['tallas_caballero'])) {
                $proceso->setTallasCalabrero($validated['tallas_caballero']);
            }
            if (isset($validated['datos_adicionales'])) {
                $proceso->setDatosAdicionales($validated['datos_adicionales']);
            }

            // Guardar
            $procesoActualizado = $this->procesoRepository->actualizar($proceso);

            return response()->json([
                'success' => true,
                'message' => 'Proceso actualizado correctamente',
                'data' => $procesoActualizado->toArray(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar proceso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar proceso
     * DELETE /api/procesos/{id}
     */
    public function eliminar($procesoId)
    {
        try {
            $proceso = $this->procesoRepository->obtenerPorId($procesoId);

            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado',
                ], 404);
            }

            if (!$proceso->puedeSerEditado()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar un proceso en estado: ' . $proceso->getEstado(),
                ], 422);
            }

            $this->procesoRepository->eliminar($procesoId);

            return response()->json([
                'success' => true,
                'message' => 'Proceso eliminado correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar proceso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aprobar proceso
     * POST /api/procesos/{id}/aprobar
     */
    public function aprobar($procesoId)
    {
        try {
            $usuarioId = auth()->id();

            if (!$usuarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            $procesoAprobado = $this->aprobarService->ejecutar($procesoId, $usuarioId);

            return response()->json([
                'success' => true,
                'message' => 'Proceso aprobado correctamente',
                'data' => $procesoAprobado->toArray(),
            ]);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar proceso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rechazar proceso
     * POST /api/procesos/{id}/rechazar
     */
    public function rechazar(Request $request, $procesoId)
    {
        try {
            $request->validate([
                'motivo' => 'required|string|min:5|max:500',
            ]);

            $procesoRechazado = $this->rechazarService->ejecutar($procesoId, $request->motivo);

            return response()->json([
                'success' => true,
                'message' => 'Proceso rechazado correctamente',
                'data' => $procesoRechazado->toArray(),
            ]);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar proceso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener imágenes de un proceso
     * GET /api/procesos/{id}/imagenes
     */
    public function obtenerImagenes($procesoId)
    {
        try {
            $proceso = $this->procesoRepository->obtenerPorId($procesoId);
            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado',
                ], 404);
            }

            $imagenes = $this->imagenRepository->obtenerPorProceso($procesoId);

            return response()->json([
                'success' => true,
                'data' => array_map(fn($img) => $img->toArray(), $imagenes),
                'total' => count($imagenes),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener imágenes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Subir imagen a un proceso
     * POST /api/procesos/{id}/imagenes
     */
    public function subirImagen(Request $request, $procesoId)
    {
        try {
            $proceso = $this->procesoRepository->obtenerPorId($procesoId);
            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado',
                ], 404);
            }

            // Validar request
            $validated = $request->validate([
                'imagen' => 'required|image|mimes:jpeg,png,gif,webp|max:5120', // 5MB
                'descripcion' => 'nullable|string|max:255',
                'es_principal' => 'nullable|boolean',
            ]);

            // Procesar archivo
            $archivo = $request->file('imagen');
            $imagenBinaria = file_get_contents($archivo->getRealPath());
            $tipoMime = $archivo->getMimeType();
            $nombreOriginal = $archivo->getClientOriginalName();

            // Generar nombre de archivo
            $nombreArchivo = "proceso-{$procesoId}-" . time() . "." . $archivo->getClientOriginalExtension();

            // Guardar archivo
            $ruta = Storage::disk('public')->put("procesos/{$nombreArchivo}", $imagenBinaria);

            // Calcular hash MD5 para detectar duplicados
            $hashMd5 = md5($imagenBinaria);

            // Obtener dimensiones de imagen
            $imagenInfo = getimagesizefromstring($imagenBinaria);
            $ancho = $imagenInfo[0] ?? 0;
            $alto = $imagenInfo[1] ?? 0;

            // Usar domain service para guardar imagen
            $imagen = $this->subirImagenService->ejecutar(
                procesoPrendaDetalleId: $procesoId,
                rutaArchivo: "procesos/{$nombreArchivo}",
                nombreOriginal: $nombreOriginal,
                tipoMime: $tipoMime,
                tamaño: strlen($imagenBinaria),
                ancho: $ancho,
                alto: $alto,
                hashMd5: $hashMd5,
                descripcion: $validated['descripcion'] ?? null,
                esPrincipal: $validated['es_principal'] ?? false
            );

            return response()->json([
                'success' => true,
                'message' => 'Imagen subida correctamente',
                'data' => $imagen->toArray(),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir imagen',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marcar imagen como principal
     * POST /api/procesos/{id}/imagenes/{imagenId}/principal
     */
    public function marcarComoPrincipal($procesoId, $imagenId)
    {
        try {
            $imagen = $this->imagenRepository->obtenerPorId($imagenId);
            if (!$imagen || $imagen->getProcesoPrendaDetalleId() !== (int)$procesoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Imagen no encontrada',
                ], 404);
            }

            // Marcar otra como principal
            $this->imagenRepository->marcarOtraComoPrincipal($procesoId, $imagenId);

            return response()->json([
                'success' => true,
                'message' => 'Imagen marcada como principal',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar imagen como principal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar imagen de un proceso
     * DELETE /api/procesos/{id}/imagenes/{imagenId}
     */
    public function eliminarImagen($procesoId, $imagenId)
    {
        try {
            $imagen = $this->imagenRepository->obtenerPorId($imagenId);
            if (!$imagen || $imagen->getProcesoPrendaDetalleId() !== (int)$procesoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Imagen no encontrada',
                ], 404);
            }

            // Eliminar archivo físico
            $ruta = $imagen->getRuta();
            if (Storage::disk('public')->exists($ruta)) {
                Storage::disk('public')->delete($ruta);
            }

            // Eliminar registro de base de datos
            $this->imagenRepository->eliminar($imagenId);

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar imagen',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
