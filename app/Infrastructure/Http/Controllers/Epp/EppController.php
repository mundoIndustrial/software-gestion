<?php

namespace App\Infrastructure\Http\Controllers\Epp;

use App\Http\Controllers\Controller;
use App\Domain\Epp\Queries\BuscarEppQuery;
use App\Domain\Epp\Queries\ObtenerEppPorIdQuery;
use App\Domain\Epp\Queries\ObtenerEppPorCategoriaQuery;
use App\Domain\Epp\Queries\ListarEppActivosQuery;
use App\Domain\Epp\Queries\ListarCategoriasEppQuery;
use App\Domain\Epp\Queries\ObtenerEppDelPedidoQuery;
use App\Domain\Epp\Commands\AgregarEppAlPedidoCommand;
use App\Domain\Epp\Commands\EliminarEppDelPedidoCommand;
use App\Application\Commands\CrearEppCommand;
use App\Domain\Shared\CQRS\QueryBus;
use App\Domain\Shared\CQRS\CommandBus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Epp;
use App\Models\EppImagen;
use Illuminate\Support\Facades\Storage;

/**
 * Controller: EppController
 * 
 * Infrastructure Layer - Maneja requests HTTP y dispara CQRS
 * Cumple: Separación de responsabilidades, DDD
 */
class EppController extends Controller
{
    public function __construct(
        private QueryBus $queryBus,
        private CommandBus $commandBus,
    ) {}

    /**
     * GET /api/epp
     * 
     * Buscar EPP o listar todos los activos
     * Query parameters:
     * - q: término de búsqueda (código o nombre)
     * - categoria: filtrar por categoría
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $termino = $request->query('q');
            $categoria = $request->query('categoria');

            if ($termino) {
                $query = new BuscarEppQuery($termino);
            } elseif ($categoria) {
                $query = new ObtenerEppPorCategoriaQuery($categoria);
            } else {
                $query = new ListarEppActivosQuery();
            }

            $epps = $this->queryBus->execute($query);

            return response()->json([
                'success' => true,
                'data' => $epps,
                'total' => count($epps),
            ]);
        } catch (\DomainException $e) {
            \Log::warning('⚠️  [EppController] DomainException:', [
                'message' => $e->getMessage(),
                'termino' => $termino ?? null,
                'categoria' => $categoria ?? null,
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            \Log::error(' [EppController] Error al buscar EPP:', [
                'message' => $e->getMessage(),
                'termino' => $termino ?? null,
                'categoria' => $categoria ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar EPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/epp/{id}
     * 
     * Obtener EPP por ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $query = new ObtenerEppPorIdQuery($id);
            $epp = $this->queryBus->execute($query);

            if (!$epp) {
                return response()->json([
                    'success' => false,
                    'message' => 'EPP no encontrado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $epp,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener EPP',
            ], 500);
        }
    }

    /**
     * POST /api/epp
     * 
     * Crear nuevo EPP
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'categoria' => 'required|string|max:255',
                'codigo' => 'required|string|max:100',
                'descripcion' => 'nullable|string',
            ]);

            $command = new CrearEppCommand(
                nombre: $validated['nombre'],
                categoria: $validated['categoria'],
                codigo: $validated['codigo'],
                descripcion: $validated['descripcion'] ?? null
            );

            $epp = $this->commandBus->execute($command);

            return response()->json([
                'success' => true,
                'message' => 'EPP creado exitosamente',
                'data' => $epp,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Validation error in store', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error creating EPP', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear EPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/epp/categorias/all
     * 
     * Obtener todas las categorías
     */
    public function categorias(): JsonResponse
    {
        try {
            $query = new ListarCategoriasEppQuery();
            $categorias = $this->queryBus->execute($query);

            return response()->json([
                'success' => true,
                'data' => $categorias,
                'total' => count($categorias),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categorías',
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/{pedidoId}/epp
     * 
     * Obtener EPP de un pedido
     */
    public function obtenerDelPedido(int $pedidoId): JsonResponse
    {
        try {
            $query = new ObtenerEppDelPedidoQuery($pedidoId);
            $epps = $this->queryBus->execute($query);

            return response()->json([
                'success' => true,
                'data' => $epps,
                'total' => count($epps),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener EPP del pedido',
            ], 500);
        }
    }

    /**
     * POST /api/pedidos/{pedidoId}/epp/agregar
     * 
     * Agregar EPP a un pedido
     */
    public function agregar(int $pedidoId, Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'epp_id' => 'required|integer|exists:epps,id',
                'talla' => 'required|string|max:20',
                'cantidad' => 'required|integer|min:1',
                'observaciones' => 'nullable|string',
            ]);

            $command = new AgregarEppAlPedidoCommand(
                pedidoId: $pedidoId,
                eppId: $validated['epp_id'],
                talla: $validated['talla'],
                cantidad: $validated['cantidad'],
                observaciones: $validated['observaciones'] ?? null,
            );

            $resultado = $this->commandBus->execute($command);

            return response()->json($resultado, 201);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar EPP',
            ], 500);
        }
    }

    /**
     * DELETE /api/pedidos/{pedidoId}/epp/{eppId}
     * 
     * Eliminar EPP de un pedido
     */
    public function eliminar(int $pedidoId, int $eppId): JsonResponse
    {
        try {
            $command = new EliminarEppDelPedidoCommand($pedidoId, $eppId);
            $resultado = $this->commandBus->execute($command);

            return response()->json($resultado);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar EPP',
            ], 500);
        }
    }

    /**
     * POST /api/epp/{eppId}/imagenes
     * 
     * Subir imagen a un EPP
     */
    public function subirImagen(int $eppId, Request $request): JsonResponse
    {
        try {
            // Validar EPP existe
            $epp = Epp::findOrFail($eppId);

            // Validar archivo
            $validated = $request->validate([
                'imagen' => 'required|image|max:5120',
                'principal' => 'nullable|boolean',
            ]);

            $archivo = $request->file('imagen');
            $principal = $request->boolean('principal', false);

            // Si es principal, desmarcar otras imágenes como principales
            if ($principal) {
                EppImagen::where('epp_id', $eppId)
                    ->where('principal', true)
                    ->update(['principal' => false]);
            }

            // Crear carpeta si no existe
            $carpeta = "epp/{$epp->codigo}";
            if (!Storage::disk('public')->exists($carpeta)) {
                Storage::disk('public')->makeDirectory($carpeta);
            }

            // Guardar archivo
            $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
            $ruta = $archivo->storeAs($carpeta, $nombreArchivo, 'public');

            // Obtener próximo orden
            $proximoOrden = EppImagen::where('epp_id', $eppId)->max('orden') ?? 0;
            $proximoOrden++;

            // Crear registro en BD
            $imagen = EppImagen::create([
                'epp_id' => $eppId,
                'archivo' => $nombreArchivo,
                'principal' => $principal,
                'orden' => $proximoOrden,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Imagen subida correctamente',
                'data' => [
                    'id' => $imagen->id,
                    'archivo' => $imagen->archivo,
                    'principal' => $imagen->principal,
                    'url' => "/storage/{$ruta}",
                ],
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'EPP no encontrado',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir imagen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/epp/imagenes/{imagenId}
     * 
     * Eliminar imagen de un EPP
     */
    public function eliminarImagen(int $imagenId): JsonResponse
    {
        try {
            $imagen = EppImagen::findOrFail($imagenId);
            $epp = $imagen->epp;

            // Eliminar archivo
            $rutaArchivo = "epp/{$epp->codigo}/{$imagen->archivo}";
            if (Storage::disk('public')->exists($rutaArchivo)) {
                Storage::disk('public')->delete($rutaArchivo);
            }

            // Eliminar registro
            $imagen->delete();

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Imagen no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar imagen',
            ], 500);
        }
    }
}
