<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Application\Pedidos\UseCases\ActualizarVariantePrendaUseCase;
use App\Application\Pedidos\DTOs\ActualizarVariantePrendaDTO;
use App\Domain\Pedidos\Repositories\PrendaRepositoryInterface;
use App\Domain\Pedidos\ValueObjects\CatalogoTallas;
use App\Models\PedidoAnexoHistorial;

/**
 * VariantesPrendaController
 *
 * Responsabilidad: Consultar y actualizar variantes, tallas y colores de prendas.
 * - Obtener catálogo de tallas disponibles
 * - Obtener tallas, variantes y colores/telas de una prenda específica
 * - Actualizar variante de prenda (manga, broche, bolsillos)
 *
 * Patrón: CQRS + Dependency Injection
 * SRP: Solo operaciones de variantes, tallas y colores/telas
 */
class VariantesPrendaController
{
    public function __construct(
        private ActualizarVariantePrendaUseCase $actualizarVariantePrendaUseCase,
        private PrendaRepositoryInterface $prendaRepository,
    ) {}

    /**
     * GET /api/tallas-disponibles
     * Obtener catálogo de tallas disponibles por género.
     * - Sin ?prendaId → retorna catálogo general
     * - Con ?prendaId   → delega a obtenerTallasPrenda (con cantidades)
     * - Con ?genero     → filtra por género
     */
    public function obtenerTallasDisponibles(Request $request): JsonResponse
    {
        try {
            Log::info('[VariantesPrendaController] GET /api/tallas-disponibles', [
                'params' => $request->all()
            ]);

            $genero = $request->query('genero');
            $prendaId = $request->query('prendaId');

            if ($prendaId) {
                return $this->obtenerTallasPrenda((int)$prendaId);
            }

            if ($genero && CatalogoTallas::tieneGenero($genero)) {
                $resultado = [
                    strtoupper($genero) => CatalogoTallas::porGenero($genero)
                ];
            } else {
                $resultado = CatalogoTallas::todos();
            }

            Log::info('[VariantesPrendaController] Tallas retornadas', [
                'count' => count($resultado),
                'generos' => array_keys($resultado)
            ]);

            return response()->json([
                'success' => true,
                'data' => $resultado,
                'mensaje' => 'Catálogo de tallas cargado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('[VariantesPrendaController] Error al obtener tallas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el catálogo de tallas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/prenda-pedido/{prendaId}/tallas
     * Obtener tallas específicas de una prenda (con cantidades).
     * Retorna: { DAMA: { S: 10, M: 15 }, CABALLERO: { 32: 20 } }
     */
    public function obtenerTallasPrenda(int $prendaId): JsonResponse
    {
        try {
            Log::info('[VariantesPrendaController] GET /api/prenda-pedido/{prendaId}/tallas', [
                'prenda_id' => $prendaId
            ]);

            $tallas = collect($this->prendaRepository->obtenerTallasPedido($prendaId));

            if ($tallas->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => ['DAMA' => [], 'CABALLERO' => []],
                    'mensaje' => 'Prenda sin tallas asignadas'
                ], 200);
            }

            $tallasPorGenero = [];
            foreach ($tallas as $talla) {
                $genero = $talla->genero;
                if (!isset($tallasPorGenero[$genero])) {
                    $tallasPorGenero[$genero] = [];
                }
                $tallasPorGenero[$genero][$talla->talla] = (int)$talla->cantidad;
            }

            return response()->json([
                'success' => true,
                'data' => $tallasPorGenero,
                'mensaje' => 'Tallas de prenda cargadas exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('[VariantesPrendaController] Error al obtener tallas de prenda', [
                'error' => $e->getMessage(),
                'prenda_id' => $prendaId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las tallas de la prenda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/prenda-pedido/{prendaId}/variantes
     * Obtener variantes de prenda (manga, broche, bolsillos)
     */
    public function obtenerVariantesPrenda(int $prendaId): JsonResponse
    {
        try {
            Log::info('[VariantesPrendaController] GET /api/prenda-pedido/{prendaId}/variantes', [
                'prenda_id' => $prendaId
            ]);

            $variantes = $this->prendaRepository->obtenerVariantesPedido($prendaId);

            return response()->json([
                'success' => true,
                'data' => $variantes,
                'mensaje' => 'Variantes de prenda cargadas exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('[VariantesPrendaController] Error al obtener variantes de prenda', [
                'error' => $e->getMessage(),
                'prenda_id' => $prendaId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las variantes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/prenda-pedido/{prendaId}/colores-telas
     * Obtener colores y telas seleccionados para una prenda
     */
    public function obtenerColoresTelasPrenda(int $prendaId): JsonResponse
    {
        try {
            Log::info('[VariantesPrendaController] GET /api/prenda-pedido/{prendaId}/colores-telas', [
                'prenda_id' => $prendaId
            ]);

            $coloresTelas = $this->prendaRepository->obtenerColoresTelasPedido($prendaId);

            return response()->json([
                'success' => true,
                'data' => $coloresTelas,
                'mensaje' => 'Colores y telas cargados exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('[VariantesPrendaController] Error al obtener colores y telas', [
                'error' => $e->getMessage(),
                'prenda_id' => $prendaId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los colores y telas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /asesores/pedidos/{pedidoId}/prendas/{prendaId}/variante
     * Actualizar SOLO la variante de una prenda (manga, broche, bolsillos).
     * Realiza MERGE de datos — solo actualiza los campos enviados, preserva el resto.
     */
    public function actualizarVariantePrend(Request $request, int $pedidoId, int $prendaId): JsonResponse
    {
        try {
            Log::info('[VariantesPrendaController] PUT /pedidos/{pedidoId}/prendas/{prendaId}/variante', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'has_body' => $request->getContent() !== '',
            ]);

            $validated = $request->validate([
                'tipo_manga_id' => 'sometimes|nullable|integer|min:1',
                'manga_obs' => 'sometimes|nullable|string|max:500',
                'tipo_broche_boton_id' => 'sometimes|nullable|integer|min:1',
                'broche_boton_obs' => 'sometimes|nullable|string|max:500',
                'tiene_bolsillos' => 'sometimes|nullable|boolean',
                'bolsillos_obs' => 'sometimes|nullable|string|max:500',
            ]);

            $data = array_merge($validated, [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
            ]);
            $dto = ActualizarVariantePrendaDTO::fromRequest($data);

            // Capturar estado actual ANTES del cambio
            $varianteActual = \DB::table('prenda_pedido_variantes')->where('prenda_pedido_id', $prendaId)->first();
            $nombrePrenda   = \DB::table('prenda_pedido')->where('id', $prendaId)->value('nombre_prenda') ?? 'PRENDA';

            $resultado = $this->actualizarVariantePrendaUseCase->ejecutar($dto);

            // Construir diff
            $cambiosDetalle = [];
            $mapaLabels = [
                'tipo_manga_id'        => 'manga',
                'tipo_broche_boton_id' => 'broche',
                'tiene_bolsillos'      => 'bolsillos',
                'manga_obs'            => 'obs manga',
                'broche_boton_obs'     => 'obs broche',
                'bolsillos_obs'        => 'obs bolsillos',
            ];
            foreach ($mapaLabels as $campo => $label) {
                if (array_key_exists($campo, $validated)) {
                    $vAntes   = (string)($varianteActual->$campo ?? '');
                    $vDespues = (string)($validated[$campo] ?? '');
                    if ($vAntes !== $vDespues) {
                        $cambiosDetalle[] = $label . ': "' . $vAntes . '" → "' . $vDespues . '"';
                    }
                }
            }

            PedidoAnexoHistorial::registrarPrendaEditada(
                $pedidoId,
                $prendaId,
                (string)$nombrePrenda,
                'manga/broche/bolsillos',
                $cambiosDetalle ? implode(' | ', $cambiosDetalle) : null
            );

            Log::info('[VariantesPrendaController] Variante actualizada exitosamente', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'variante_id' => $resultado['id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $resultado,
                'message' => 'Variante actualizada correctamente',
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[VariantesPrendaController] Validación HTTP fallida', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación de datos fallida',
                'errors' => $e->errors(),
            ], 422);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[VariantesPrendaController] Validación de negocio fallida', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[VariantesPrendaController] Error actualizando variante de prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar variante: ' . $e->getMessage(),
            ], 500);
        }
    }
}
