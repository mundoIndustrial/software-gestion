<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Application\Pedidos\UseCases\AgregarPrendaAlPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerPrendasPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarPrendaPedidoUseCase;
use App\Application\Pedidos\UseCases\AgregarPrendaCompletaUseCase;
use App\Application\Pedidos\UseCases\ActualizarPrendaCompletaUseCase;
use App\Application\Pedidos\UseCases\RenderItemCardUseCase;
use App\Application\Pedidos\UseCases\ObtenerProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\EliminarPrendaPedidoUseCase;
use App\Application\Pedidos\UseCases\EliminarImagenPedidoUseCase;
use App\Application\Pedidos\UseCases\EliminarProcesosListaUseCase;
use App\Application\Pedidos\DTOs\AgregarPrendaAlPedidoDTO;
use App\Application\Pedidos\DTOs\ObtenerPrendasPedidoDTO;
use App\Application\Pedidos\DTOs\ActualizarPrendaPedidoDTO;
use App\Application\Pedidos\DTOs\AgregarPrendaCompletaDTO;
use App\Application\Pedidos\DTOs\ActualizarPrendaCompletaDTO;
use App\Application\Pedidos\DTOs\RenderItemCardDTO;
use App\Application\Pedidos\DTOs\ObtenerProduccionPedidoDTO;
use App\Application\Services\Pedidos\ProcesarImagenesPrendaService;
use App\Application\Services\Asesores\ObtenerPedidoDetalleService;
use App\Application\Services\Asesores\PrendaPedidoEdicionAuditoriaService;
use App\Infrastructure\Http\Resources\PrendaPedidoResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * PrendasPedidoController
 *
 * Responsabilidad: Gestionar prendas asociadas a pedidos de produccion.
 * - Agregar, actualizar y eliminar prendas (simple y completa con imagenes)
 * - Renderizar item-card para interfaz dinamica
 * - Obtener datos completos de prenda para edicion
 * - Eliminar imagenes de prenda
 *
 * Patron: CQRS + Dependency Injection
 * SRP: Solo operaciones de prendas e imagenes, sin logica de negocio
 */
class PrendasPedidoController
{
    public function __construct(
        private AgregarPrendaAlPedidoUseCase $agregarPrendaUseCase,
        private ObtenerPrendasPedidoUseCase $obtenerPrendasUseCase,
        private ActualizarPrendaPedidoUseCase $actualizarPrendaUseCase,
        private AgregarPrendaCompletaUseCase $agregarPrendaCompletaUseCase,
        private ActualizarPrendaCompletaUseCase $actualizarPrendaCompletaUseCase,
        private RenderItemCardUseCase $renderItemCardUseCase,
        private ObtenerProduccionPedidoUseCase $obtenerPedidoUseCase,
        private EliminarPrendaPedidoUseCase $eliminarPrendaPedidoUseCase,
        private EliminarImagenPedidoUseCase $eliminarImagenPedidoUseCase,
        private EliminarProcesosListaUseCase $eliminarProcesosListaUseCase,
        private ProcesarImagenesPrendaService $procesarImagenesService,
        private ObtenerPedidoDetalleService $obtenerPedidoDetalleService,
        private PrendaPedidoEdicionAuditoriaService $prendaPedidoEdicionAuditoriaService,
    ) {}

    /**
     * POST /api/pedidos/{id}/prendas
     * Agregar prenda simple a pedido
     */
    public function agregarPrenda(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PrendasPedidoController] POST /api/pedidos/{id}/prendas', ['id' => $id]);

            $validated = $request->validate([
                'nombre_prenda' => 'required|string|max:255',
                'cantidad' => 'required|integer|min:1',
                'tipo' => 'required|string|in:sin_cotizacion,reflectivo',
                'tipo_manga' => 'required|string|max:100',
                'tipo_broche' => 'required|string|max:100',
                'color_id' => 'required|integer|min:1',
                'tela_id' => 'required|integer|min:1',
            ]);

            $dto = AgregarPrendaAlPedidoDTO::fromRequest($id, $validated);
            $pedido = $this->agregarPrendaUseCase->ejecutar($dto);

            Log::info('[PrendasPedidoController] Prenda agregada exitosamente', [
                'pedido_id' => $pedido->id,
            ]);

            // Registrar en historial: prenda nueva agregada a pedido existente
            $this->prendaPedidoEdicionAuditoriaService->registrarPrendaNueva(
                (int)$pedido->id,
                null,
                $validated['nombre_prenda'] ?? 'PRENDA'
            );

            return response()->json($pedido, 201);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[PrendasPedidoController] Validacion de prenda fallida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Validacion de prenda fallida',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error agregando prenda', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error agregando prenda',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/{id}/prendas
     * Obtener todas las prendas de un pedido
     */
    public function obtenerPrendas(int|string $id): JsonResponse
    {
        try {
            Log::info('[PrendasPedidoController] GET /api/pedidos/{id}/prendas', ['id' => $id]);

            $dto = ObtenerPrendasPedidoDTO::fromRoute($id);
            $prendas = $this->obtenerPrendasUseCase->ejecutar($dto);

            Log::info('[PrendasPedidoController] Prendas obtenidas exitosamente', [
                'pedido_id' => $id,
                'total_prendas' => $prendas->count(),
            ]);

            return response()->json($prendas, 200);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error obteniendo prendas', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error obteniendo prendas',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/pedidos/render-item-card
     * Renderizar componente item-card para agregar dinamicamente
     */
    public function renderItemCard(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'item' => 'required|array',
                'index' => 'required|integer|min:0',
            ]);

            $dto = RenderItemCardDTO::fromRequest($validated);
            $html = $this->renderItemCardUseCase->ejecutar($dto);

            return response()->json([
                'success' => true,
                'html' => $html,
            ], 200);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error renderizando item-card', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error renderizando componente',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST (sin ruta registrada actualmente)
     * Actualizar datos basicos de una prenda especifica dentro de un pedido
     */
    public function actualizarPrenda(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'pedidoId' => 'required|numeric',
                'prendasIndex' => 'required|numeric|min:0',
                'nombre' => 'sometimes|nullable|string',
                'descripcion' => 'sometimes|nullable|string',
                'talla_referencia' => 'sometimes|nullable|string',
                'tallas' => 'sometimes|nullable|array',
                'infoTecnica' => 'sometimes|nullable|array',
                'observaciones' => 'sometimes|nullable|string',
            ]);

            $dto = ActualizarPrendaPedidoDTO::fromRequest($validated['pedidoId'], $validated);
            $prenda = $this->actualizarPrendaUseCase->ejecutar($dto);

            Log::info('[PrendasPedidoController] Prenda actualizada exitosamente', [
                'pedido_id' => $validated['pedidoId'],
                'prenda_index' => $validated['prendasIndex'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Prenda actualizada correctamente',
                'prenda' => $prenda,
            ], 200);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[PrendasPedidoController] Validacion de prenda fallida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error actualizando prenda', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar prenda: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /asesores/pedidos/{id}/agregar-prenda
     * Agregar prenda completa (con telas e imagenes) al pedido en edicion
     */
    public function agregarPrendaCompleta(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PrendasPedidoController] POST /asesores/pedidos/{id}/agregar-prenda', ['id' => $id]);

            $validated = $request->validate([
                'nombre_prenda' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'origen' => 'required|string|in:bodega,confeccion',
                'cantidad_talla' => 'nullable|json',
                'asignaciones_colores' => 'nullable|json',
                'procesos' => 'nullable|json',
                'variantes' => 'nullable|json',
                'novedad' => 'required|string|max:500',
                'imagenes' => 'nullable|array',
                'imagenes.*' => 'nullable|image|max:5120',
                'imagenes_existentes' => 'nullable|json',
                'telas' => 'nullable|json',
            ]);

            $imgs               = $this->procesarImagenesService->procesarParaCrear($request, (int)$id, $validated['asignaciones_colores'] ?? null);
            $imagenesGuardadas  = $imgs['imagenes_guardadas'];
            $imagenesExistentes = $imgs['imagenes_existentes'];
            $fotosProcesoNuevo  = $imgs['fotos_proceso_nuevo'];
            $fotosTelaRutas     = $imgs['fotos_tela_rutas'];
            if ($imgs['asignaciones_colores'] !== null) {
                $validated['asignaciones_colores'] = $imgs['asignaciones_colores'];
            }

            $dto = AgregarPrendaCompletaDTO::fromRequest($id, $validated, $imagenesGuardadas, $imagenesExistentes, $fotosProcesoNuevo, $fotosTelaRutas);
            $prenda = $this->agregarPrendaCompletaUseCase->execute($dto);

            Log::info('[PrendasPedidoController] Prenda completa agregada exitosamente', [
                'pedido_id' => $id,
                'prenda_id' => $prenda->id,
            ]);

            // Registrar en historial: prenda nueva agregada a pedido existente
            $this->prendaPedidoEdicionAuditoriaService->registrarPrendaNueva(
                (int)$id,
                $prenda->id,
                $validated['nombre_prenda'] ?? 'PRENDA'
            );

            return response()->json([
                'success' => true,
                'message' => 'Prenda agregada correctamente a la base de datos',
                'prenda' => $prenda->toArray(),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[PrendasPedidoController] Validacion fallida', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validacion fallida',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error agregando prenda completa', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al agregar prenda: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /asesores/pedidos/{id}/actualizar-prenda
     * Actualizar una prenda existente en un pedido (con telas e imagenes)
     */
    public function actualizarPrendaCompleta(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PrendasPedidoController] POST /asesores/pedidos/{id}/actualizar-prenda', ['id' => $id]);

            Log::info('[PrendasPedidoController] Request raw data', [
                'origen' => $request->input('origen'),
                'de_bodega' => $request->input('de_bodega'),
                'all_inputs' => $request->all()
            ]);

            $validated = $request->validate([
                'prenda_id' => 'required|numeric|min:1',
                'nombre_prenda' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'origen' => 'nullable|string|in:bodega,confeccion',
                'de_bodega' => 'nullable|in:0,1',
                'tallas' => 'nullable|json',
                'variantes' => 'nullable|json',
                'colores_telas' => 'nullable|json',
                'fotos_telas' => 'nullable|json',
                'fotosTelas' => 'nullable|json',
                'procesos' => 'nullable|json',
                'fotos_procesos' => 'nullable|json',
                'novedad' => 'required|string|max:500',
                'asignaciones_colores' => 'nullable|json',
                'imagenes' => 'nullable|array',
                'imagenes.*' => 'nullable|image|max:5120',
                'imagenes_existentes' => 'nullable|json',
                'imagenes_a_eliminar' => 'nullable|json',
                'procesos_a_eliminar' => 'nullable|json',
            ]);

            if (!empty($validated['fotosTelas']) && empty($validated['fotos_telas'])) {
                $validated['fotos_telas'] = $validated['fotosTelas'];
            }

            if ($request->has('imagenes_a_eliminar') && is_string($request->input('imagenes_a_eliminar'))) {
                $request->merge(['imagenes_a_eliminar' => json_decode($request->input('imagenes_a_eliminar'), true)]);
            }

            $imgs                    = $this->procesarImagenesService->procesarParaActualizar($request, (int)$id);
            $imagenesGuardadas       = $imgs['imagenes_guardadas'];
            $imagenesExistentes      = $imgs['imagenes_existentes'];
            $imagenesAEliminar       = $imgs['imagenes_a_eliminar'];
            $fotosTelasProcesadas    = $imgs['fotos_telas_procesadas'];
            $fotosProcesoNuevo       = $imgs['fotos_proceso_nuevo'];
            $fotosProcesoTallasNuevo = $imgs['fotos_proceso_tallas_nuevo'];

            $procesosAEliminar = [];
            if ($request->input('procesos_a_eliminar')) {
                $input = $request->input('procesos_a_eliminar');
                if (is_array($input)) {
                    $procesosAEliminar = $input;
                } elseif (is_string($input)) {
                    $procesosAEliminar = json_decode($input, true) ?? [];
                }
                if (!is_array($procesosAEliminar)) {
                    $procesosAEliminar = [];
                }
            }

            if (!empty($procesosAEliminar)) {
                Log::info('[PrendasPedidoController] Eliminando procesos marcados', [
                    'cantidad' => count($procesosAEliminar),
                    'ids'      => $procesosAEliminar,
                ]);
                $this->eliminarProcesosListaUseCase->ejecutar($procesosAEliminar);
            }

            $fotosColorProcesadas = $imgs['fotos_color_procesadas'];

            if ($request->has('asignaciones_colores')) {
                $asignacionesInput = $request->input('asignaciones_colores');
                if (is_string($asignacionesInput)) {
                    $validated['asignaciones_colores'] = json_decode($asignacionesInput, true);
                } else {
                    $validated['asignaciones_colores'] = $asignacionesInput;
                }
                if (is_null($validated['asignaciones_colores'])) {
                    $validated['asignaciones_colores'] = [];
                }
                \Log::info('[PrendasPedidoController] asignaciones_colores extraido del FormData', [
                    'asignaciones_colores' => $validated['asignaciones_colores'],
                    'es_vacio' => empty($validated['asignaciones_colores'])
                ]);
            }

            $dto = ActualizarPrendaCompletaDTO::fromRequest($validated['prenda_id'], $validated, $imagenesGuardadas, $imagenesExistentes, $fotosTelasProcesadas, $fotosProcesoNuevo, $fotosColorProcesadas, $fotosProcesoTallasNuevo);
            $prenda = $this->actualizarPrendaCompletaUseCase->ejecutar($dto);

            Log::info('[PrendasPedidoController] Prenda completa actualizada exitosamente', [
                'pedido_id' => $id,
                'prenda_id' => $prenda->id,
            ]);

            $prenda = $prenda->fresh([
                'fotos',
                'tallas',
                'variantes.tipoManga',
                'variantes.tipoBroche',
                'coloresTelas.color',
                'coloresTelas.tela',
                'coloresTelas.fotos',
                'fotosTelas',
                'procesos.tipoProceso',
                'procesos.imagenes',
                'procesos.tallas',
            ]);

            // Registrar en historial: prenda editada en pedido existente
            $this->prendaPedidoEdicionAuditoriaService->registrarPrendaEditada(
                (int)$id,
                $prenda->id,
                $prenda->nombre_prenda ?? $validated['nombre_prenda'] ?? 'PRENDA',
                'prenda completa'
            );

            return response()->json([
                'success' => true,
                'message' => 'Prenda actualizada correctamente en la base de datos',
                'prenda'  => PrendaPedidoResource::make($prenda)->resolve(),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[PrendasPedidoController] Validacion fallida en actualizacion', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validacion fallida',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error actualizando prenda completa', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar prenda: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /pedidos/{pedidoId}/imagen/{tipo}/{id}
     * Eliminar imagen de prenda, tela o proceso
     */
    public function eliminarImagen(int $pedidoId, string $tipo, int $id): JsonResponse
    {
        try {
            Log::info('[PrendasPedidoController] DELETE imagen', [
                'pedido_id' => $pedidoId,
                'tipo'      => $tipo,
                'id'        => $id,
            ]);

            $tiposValidos = ['prenda', 'tela', 'proceso'];
            if (!in_array($tipo, $tiposValidos, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de imagen no valido',
                ], 400);
            }

            $resultado = $this->eliminarImagenPedidoUseCase->ejecutar($id, $tipo);

            // Registrar en historial: foto de prenda/tela/proceso eliminada en pedido existente
            $this->prendaPedidoEdicionAuditoriaService->registrarPrendaEditada(
                $pedidoId,
                $id,
                strtoupper($tipo) . ' (foto eliminada)'
            );

            return response()->json($resultado);

        } catch (ModelNotFoundException $e) {
            Log::warning('[PrendasPedidoController] Imagen no encontrada', [
                'tipo' => $tipo,
                'id'   => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Imagen no encontrada',
            ], 404);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error eliminando imagen', [
                'tipo'  => $tipo,
                'id'    => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar imagen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /asesores/pedidos/{id}/eliminar-prenda
     * Eliminar una prenda de un pedido y registrar el motivo en novedades
     */
    public function eliminarPrenda(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PrendasPedidoController] POST /asesores/pedidos/{id}/eliminar-prenda', [
                'pedido_id' => $id,
            ]);

            $validated = $request->validate([
                'prenda_id' => 'required|numeric|min:1',
                'motivo' => 'required|string|min:5|max:1000',
            ]);

            $resultado = $this->eliminarPrendaPedidoUseCase->ejecutar(
                (int) $id,
                (int) $validated['prenda_id'],
                $validated['motivo']
            );

            return response()->json($resultado, 200);

        } catch (ModelNotFoundException $e) {
            Log::warning('[PrendasPedidoController] Prenda o pedido no encontrado', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Prenda o pedido no encontrado',
            ], 404);

        } catch (ValidationException $e) {
            Log::warning('[PrendasPedidoController] Validacion fallida al eliminar prenda', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validacion fallida',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error eliminando prenda', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar prenda: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /asesores/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos
     * Obtener datos completos de una prenda con procesos para edicion en modal
     */
    public function obtenerDatosPrendaEdicion(int|string $pedidoId, int|string $prendaId): JsonResponse
    {
        try {
            Log::info(' [PRENDA-DATOS-INICIO] Endpoint llamado', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'timestamp' => now()
            ]);

            Log::info(' [PRENDA-DATOS] Llamando al servicio...');
            $prendaData = $this->obtenerPedidoDetalleService->obtenerPrendaConProcesos((int)$pedidoId, (int)$prendaId);

            Log::info(' [PRENDA-DATOS-RECIBIDOS] Datos obtenidos del servicio', [
                'procesos_count' => count($prendaData['procesos'] ?? []),
                'tallas_dama_count' => count($prendaData['tallas_dama'] ?? []),
                'tallas_caballero_count' => count($prendaData['tallas_caballero'] ?? []),
                'variantes_count' => count($prendaData['variantes'] ?? []),
                'colores_telas_count' => count($prendaData['colores_telas'] ?? []),
                'imagenes_count' => count($prendaData['imagenes'] ?? []),
                'prenda_keys' => array_keys($prendaData)
            ]);

            if (empty($prendaData)) {
                Log::warning(' [PRENDA-DATOS-VACIA] La prenda retorno datos vacios');
            }

            $pedido = $this->obtenerPedidoUseCase->ejecutar(
                ObtenerProduccionPedidoDTO::fromRequest($pedidoId)
            );
            $pedidoData = [];
            if ($pedido) {
                $pedidoData = [
                    'id' => $pedido->id,
                    'numero' => $pedido->numero_pedido,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'cliente_nombre' => $pedido->cliente,
                    'asesor_nombre' => $pedido->asesor?->name ?? 'Sin asesor',
                    'estado' => $pedido->estado,
                    'fecha_creacion' => $pedido->created_at?->format('d/m/Y') ?? '',
                ];
            }

            return response()->json([
                'success' => true,
                'prenda' => $prendaData,
                'pedido' => $pedidoData
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning(' [PRENDA-DATOS] Prenda no encontrada', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Prenda no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error(' [PRENDA-DATOS] Error obteniendo datos de prenda', [
                'error' => $e->getMessage(),
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de prenda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/pedidos-produccion/{pedidoId}/datos-edicion
     * Obtener datos del pedido para edicion general (sin prenda especifica)
     */
    public function obtenerDatosEdicion(int $pedidoId): JsonResponse
    {
        try {
            Log::info('[PrendasPedidoController] GET /pedidos-produccion/{pedidoId}/datos-edicion', [
                'pedido_id' => $pedidoId,
            ]);

            $dto = ObtenerProduccionPedidoDTO::fromRequest($pedidoId);
            $pedido = $this->obtenerPedidoUseCase->ejecutar($dto);

            if (!$pedido) {
                Log::warning('[PrendasPedidoController] Pedido no encontrado para edicion', [
                    'pedido_id' => $pedidoId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado',
                ], 404);
            }

            Log::info('[PrendasPedidoController] Datos de edicion obtenidos', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido['numero_pedido'] ?? null,
                'prendas_count' => count($pedido['prendas'] ?? []),
            ]);

            return response()->json([
                'success' => true,
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido['numero_pedido'] ?? null,
                'cliente' => $pedido['cliente'] ?? null,
                'prendas_count' => count($pedido['prendas'] ?? []),
                'data' => $pedido,
            ], 200);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error obteniendo datos de edicion', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de edicion: ' . $e->getMessage(),
            ], 500);
        }
    }
}

