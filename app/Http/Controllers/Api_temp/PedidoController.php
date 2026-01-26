<?php

namespace App\Http\Controllers\Api_temp;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Application\Pedidos\UseCases\ListarPedidosPorClienteUseCase;
use App\Application\Pedidos\UseCases\CancelarPedidoUseCase;
use App\Application\Pedidos\DTOs\CrearPedidoDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Exceptions\PedidoNoEncontrado;
use App\Domain\Pedidos\Exceptions\EstadoPedidoInvalido;

/**
 * PedidoController
 * 
 * Controlador para gestionar pedidos usando DDD (Fase 3)
 * 
 * Endpoints:
 * - POST /api/pedidos → Crear pedido (CrearPedidoUseCase)
 * - PATCH /api/pedidos/{id}/confirmar → Confirmar pedido (ConfirmarPedidoUseCase)
 * - GET /api/pedidos/{id} → Obtener pedido (Lectura directa)
 */
class PedidoController extends Controller
{
    public function __construct(
        private CrearPedidoUseCase $crearPedidoUseCase,
        private ConfirmarPedidoUseCase $confirmarPedidoUseCase,
        private ObtenerPedidoUseCase $obtenerPedidoUseCase,
        private ListarPedidosPorClienteUseCase $listarPedidosPorClienteUseCase,
        private CancelarPedidoUseCase $cancelarPedidoUseCase,
        private PedidoRepository $pedidoRepository
    ) {}

    /**
     * POST /api/pedidos
     * 
     * Crear un nuevo pedido usando DDD
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validar entrada básica
            $request->validate([
                'cliente_id' => 'required|integer',
                'descripcion' => 'required|string|max:1000',
                'observaciones' => 'nullable|string|max:1000',
                'prendas' => 'required|array|min:1',
                'prendas.*.prenda_id' => 'required|integer',
                'prendas.*.descripcion' => 'required|string',
                'prendas.*.cantidad' => 'required|integer|min:1',
                'prendas.*.tallas' => 'required|array',
            ]);

            // Crear DTO desde request
            $dto = CrearPedidoDTO::fromRequest($request->all());

            // Ejecutar Use Case
            $response = $this->crearPedidoUseCase->ejecutar($dto);

            return response()->json([
                'success' => true,
                'message' => $response->mensaje,
                'data' => $response->toArray()
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH /api/pedidos/{id}/confirmar
     * 
     * Confirmar un pedido existente
     */
    public function confirmar(int $id): JsonResponse
    {
        try {
            // Ejecutar Use Case
            $response = $this->confirmarPedidoUseCase->ejecutar($id);

            return response()->json([
                'success' => true,
                'message' => 'Pedido confirmado exitosamente',
                'data' => $response->toArray()
            ], 200);

        } catch (PedidoNoEncontrado $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado',
            ], 404);

        } catch (EstadoPedidoInvalido $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede confirmar el pedido: ' . $e->getMessage(),
            ], 422);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/pedidos/{id}/cancelar
     * 
     * Cancelar un pedido
     */
    public function cancelar(int $id): JsonResponse
    {
        try {
            $response = $this->cancelarPedidoUseCase->ejecutar($id);

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado exitosamente',
                'data' => $response->toArray()
            ], 200);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/{id}
     * 
     * Obtener un pedido (lectura - CQRS read side)
     */
    public function show(int $id): JsonResponse
    {
        try {
            $response = $this->obtenerPedidoUseCase->ejecutar($id);

            return response()->json([
                'success' => true,
                'data' => $response->toArray()
            ], 200);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/cliente/{clienteId}
     * 
     * Listar pedidos de un cliente
     */
    public function listarPorCliente(int $clienteId): JsonResponse
    {
        try {
            $response = $this->listarPedidosPorClienteUseCase->ejecutar($clienteId);

            return response()->json([
                'success' => true,
                'data' => array_map(fn($dto) => $dto->toArray(), $response)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar pedidos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/pedidos/{id}/recibos-datos
     * 
     * Obtener datos completos del pedido (para recibos)
     * Método de compatibilidad con rutas de asesores
     */
    public function obtenerDetalleCompleto(int $id): JsonResponse
    {
        try {
            $response = $this->obtenerPedidoUseCase->ejecutar($id);

            return response()->json([
                'success' => true,
                'data' => $response->toArray()
            ], 200);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/pedidos/{id}/editar-datos
     * 
     * Obtener datos de un pedido para edición
     * Incluye prendas con variantes, telas, colores, procesos e imágenes
     * Usado por el formulario de edición de pedidos
     */
    public function obtenerDatosEdicion(int $id): JsonResponse
    {
        try {
            $pedido = \App\Models\PedidoProduccion::with([
                'prendas.variantes',
                'prendas.coloresTelas',
                'prendas.procesos',
                'prendas.fotos',
                'prendas.telaFotos',
                'asesor:id,name',
                'cliente:id,nombre'
            ])->findOrFail($id);

            // Transformar variantes para incluir nombres de tipos
            if ($pedido->prendas) {
                foreach ($pedido->prendas as $prenda) {
                    if ($prenda->variantes) {
                        foreach ($prenda->variantes as $variante) {
                            // Obtener nombre de manga
                            if ($variante->tipo_manga_id) {
                                try {
                                    $manga = \App\Models\TipoManga::find($variante->tipo_manga_id);
                                    $variante->manga_nombre = $manga ? $manga->nombre : null;
                                } catch (\Exception $e) {
                                    \Log::debug('[PedidoController] Error obtener manga', ['error' => $e->getMessage()]);
                                }
                            }
                            
                            // Obtener nombre de broche
                            if ($variante->tipo_broche_boton_id) {
                                try {
                                    $broche = \App\Models\TipoBrocheBoton::find($variante->tipo_broche_boton_id);
                                    $variante->broche_nombre = $broche ? $broche->nombre : null;
                                } catch (\Exception $e) {
                                    \Log::debug('[PedidoController] Error obtener broche', ['error' => $e->getMessage()]);
                                }
                            }
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $pedido
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('[PedidoController] Pedido no encontrado para edición', ['pedido_id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obtener datos para edición: ' . $e->getMessage(), [
                'pedido_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del pedido',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/api/tipos-broche-boton
     * 
     * Obtener tipos de broche/botón disponibles
     * Array de tipos de broche/botón con su ID
     */
    public function obtenerTiposBrocheBoton(): JsonResponse
    {
        try {
            $tipos = \App\Models\TipoBrocheBoton::where('activo', true)
                ->select('id', 'nombre')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tipos
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obtener tipos broche/botón: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de broche/botón',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/api/tipos-manga
     * 
     * Obtener tipos de manga disponibles
     * Array de tipos de manga con su ID
     */
    public function obtenerTiposManga(): JsonResponse
    {
        try {
            $tipos = \App\Models\TipoManga::where('activo', true)
                ->select('id', 'nombre')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tipos
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obtener tipos manga: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de manga',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /asesores/api/tipos-manga
     * 
     * Crear o obtener un tipo de manga por nombre
     * Si no existe, lo crea automáticamente
     */
    public function crearObtenerTipoManga(Request $request): JsonResponse
    {
        try {
            $nombre = trim($request->input('nombre', ''));
            
            if (empty($nombre)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre del tipo de manga es requerido'
                ], 400);
            }

            // Buscar si ya existe (case-insensitive)
            $tipo = \App\Models\TipoManga::whereRaw('LOWER(nombre) = ?', [strtolower($nombre)])
                ->first();

            // Si no existe, crearlo
            if (!$tipo) {
                $tipo = \App\Models\TipoManga::create([
                    'nombre' => ucfirst(strtolower($nombre)),
                    'activo' => true
                ]);

                \Log::info('[PedidoController] Nuevo tipo de manga creado', [
                    'id' => $tipo->id,
                    'nombre' => $tipo->nombre
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $tipo,
                'mensaje' => $tipo->wasRecentlyCreated ? 'Tipo creado' : 'Tipo existente'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error crear/obtener tipo manga: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear/obtener tipo de manga',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/api/telas
     * 
     * Obtener lista de telas activas
     * Array de { id, nombre, referencia }
     */
    public function obtenerTelas(): JsonResponse
    {
        try {
            $telas = \App\Models\TelaPrenda::where('activo', true)
                ->select('id', 'nombre', 'referencia')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $telas
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obtener telas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener telas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /asesores/api/telas
     * 
     * Crear o obtener una tela por nombre
     * Si no existe, la crea automáticamente
     */
    public function crearObtenerTela(Request $request): JsonResponse
    {
        try {
            $nombre = trim($request->input('nombre', ''));
            $referencia = trim($request->input('referencia', ''));
            
            if (empty($nombre)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre de la tela es requerido'
                ], 400);
            }

            // Buscar si ya existe (case-insensitive)
            $tela = \App\Models\TelaPrenda::whereRaw('LOWER(nombre) = ?', [strtolower($nombre)])
                ->first();

            // Si no existe, crearla
            if (!$tela) {
                $tela = \App\Models\TelaPrenda::create([
                    'nombre' => ucfirst(strtolower($nombre)),
                    'referencia' => $referencia,
                    'activo' => true
                ]);

                \Log::info('[PedidoController] Nueva tela creada', [
                    'id' => $tela->id,
                    'nombre' => $tela->nombre,
                    'referencia' => $tela->referencia
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $tela,
                'mensaje' => $tela->wasRecentlyCreated ? 'Tela creada' : 'Tela existente'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error crear/obtener tela: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear/obtener tela',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/api/colores
     * 
     * Obtener lista de colores activos
     * Array de { id, nombre, codigo }
     */
    public function obtenerColores(): JsonResponse
    {
        try {
            $colores = \App\Models\ColorPrenda::where('activo', true)
                ->select('id', 'nombre', 'codigo')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $colores
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obtener colores: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener colores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /asesores/api/colores
     * 
     * Crear o obtener un color por nombre
     * Si no existe, lo crea automáticamente
     */
    public function crearObtenerColor(Request $request): JsonResponse
    {
        try {
            $nombre = trim($request->input('nombre', ''));
            $codigo = trim($request->input('codigo', ''));
            
            if (empty($nombre)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre del color es requerido'
                ], 400);
            }

            // Buscar si ya existe (case-insensitive)
            $color = \App\Models\ColorPrenda::whereRaw('LOWER(nombre) = ?', [strtolower($nombre)])
                ->first();

            // Si no existe, crearlo
            if (!$color) {
                $color = \App\Models\ColorPrenda::create([
                    'nombre' => ucfirst(strtolower($nombre)),
                    'codigo' => $codigo,
                    'activo' => true
                ]);

                \Log::info('[PedidoController] Nuevo color creado', [
                    'id' => $color->id,
                    'nombre' => $color->nombre,
                    'codigo' => $color->codigo
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $color,
                'mensaje' => $color->wasRecentlyCreated ? 'Color creado' : 'Color existente'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error crear/obtener color: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear/obtener color',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/prendas-pedido/{prendaPedidoId}/fotos
     * 
     * DEPRECADO: Obtener fotos de una prenda del pedido
     * Requiere refactorización a DDD
     */
    public function obtenerFotosPrendaPedido($prendaPedidoId): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Esta funcionalidad está siendo refactorizada a DDD'
        ], 501);
    }

    /**
     * POST /asesores/pedidos/confirm
     * 
     * DEPRECADO: Alias para confirmar pedido
     * Usa: PATCH /api/pedidos/{id}/confirmar
     */
    public function confirm(Request $request): JsonResponse
    {
        $id = $request->input('pedido_id') ?: $request->route('id');
        
        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Se requiere el ID del pedido'
            ], 400);
        }

        return $this->confirmar($id);
    }

    /**
     * POST /asesores/pedidos/{id}/anular
     * 
     * DEPRECADO: Alias para cancelar pedido
     * Usa: DELETE /api/pedidos/{id}/cancelar
     */
    public function anularPedido(Request $request, $id): JsonResponse
    {
        return $this->cancelar($id);
    }
}
