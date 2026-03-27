<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\DTOs\ActualizarVariantePrendaDTO;
use App\Application\Pedidos\UseCases\ActualizarVariantePrendaUseCase;
use App\Application\Services\Asesores\VariantesPrendaAuditoriaService;
use App\Domain\Pedidos\Repositories\PrendaRepositoryInterface;
use App\Domain\Pedidos\ValueObjects\CatalogoTallas;
use App\Infrastructure\Http\Requests\Asesores\ActualizarVariantePrendaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * VariantesPrendaController
 *
 * Responsabilidad: Consultar y actualizar variantes, tallas y colores de prendas.
 */
class VariantesPrendaController
{
    public function __construct(
        private readonly ActualizarVariantePrendaUseCase $actualizarVariantePrendaUseCase,
        private readonly PrendaRepositoryInterface $prendaRepository,
        private readonly VariantesPrendaAuditoriaService $variantesPrendaAuditoriaService,
    ) {
    }

    private function json(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    private function success(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    private function failure(string $message, int $status = 500, array $extra = []): JsonResponse
    {
        return $this->json(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra), $status);
    }

    /**
     * GET /api/tallas-disponibles
     */
    public function obtenerTallasDisponibles(Request $request): JsonResponse
    {
        try {
            Log::info('[VariantesPrendaController] GET /api/tallas-disponibles', [
                'params' => $request->all(),
            ]);

            $genero = $request->query('genero');
            $prendaId = $request->query('prendaId');

            if ($prendaId) {
                return $this->obtenerTallasPrenda((int) $prendaId);
            }

            if (is_string($genero) && CatalogoTallas::tieneGenero($genero)) {
                $resultado = [
                    strtoupper($genero) => CatalogoTallas::porGenero($genero),
                ];
            } else {
                $resultado = CatalogoTallas::todos();
            }

            Log::info('[VariantesPrendaController] Tallas retornadas', [
                'count' => count($resultado),
                'generos' => array_keys($resultado),
            ]);

            return $this->success($resultado, 'Catalogo de tallas cargado exitosamente');
        } catch (\Exception $e) {
            Log::error('[VariantesPrendaController] Error al obtener tallas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->failure('Error al obtener el catalogo de tallas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/prenda-pedido/{prendaId}/tallas
     */
    public function obtenerTallasPrenda(int $prendaId): JsonResponse
    {
        try {
            Log::info('[VariantesPrendaController] GET /api/prenda-pedido/{prendaId}/tallas', [
                'prenda_id' => $prendaId,
            ]);

            $tallas = collect($this->prendaRepository->obtenerTallasPedido($prendaId));

            if ($tallas->isEmpty()) {
                return $this->success([
                    'DAMA' => [],
                    'CABALLERO' => [],
                ], 'Prenda sin tallas asignadas');
            }

            $tallasPorGenero = [];
            foreach ($tallas as $talla) {
                $genero = $talla->genero;
                if (!isset($tallasPorGenero[$genero])) {
                    $tallasPorGenero[$genero] = [];
                }
                $tallasPorGenero[$genero][$talla->talla] = (int) $talla->cantidad;
            }

            return $this->success($tallasPorGenero, 'Tallas de prenda cargadas exitosamente');
        } catch (\Exception $e) {
            Log::error('[VariantesPrendaController] Error al obtener tallas de prenda', [
                'error' => $e->getMessage(),
                'prenda_id' => $prendaId,
            ]);

            return $this->failure('Error al obtener las tallas de la prenda: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/prenda-pedido/{prendaId}/variantes
     */
    public function obtenerVariantesPrenda(int $prendaId): JsonResponse
    {
        try {
            Log::info('[VariantesPrendaController] GET /api/prenda-pedido/{prendaId}/variantes', [
                'prenda_id' => $prendaId,
            ]);

            $variantes = $this->prendaRepository->obtenerVariantesPedido($prendaId);

            return $this->success($variantes, 'Variantes de prenda cargadas exitosamente');
        } catch (\Exception $e) {
            Log::error('[VariantesPrendaController] Error al obtener variantes de prenda', [
                'error' => $e->getMessage(),
                'prenda_id' => $prendaId,
            ]);

            return $this->failure('Error al obtener las variantes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/prenda-pedido/{prendaId}/colores-telas
     */
    public function obtenerColoresTelasPrenda(int $prendaId): JsonResponse
    {
        try {
            Log::info('[VariantesPrendaController] GET /api/prenda-pedido/{prendaId}/colores-telas', [
                'prenda_id' => $prendaId,
            ]);

            $coloresTelas = $this->prendaRepository->obtenerColoresTelasPedido($prendaId);

            return $this->success($coloresTelas, 'Colores y telas cargados exitosamente');
        } catch (\Exception $e) {
            Log::error('[VariantesPrendaController] Error al obtener colores y telas', [
                'error' => $e->getMessage(),
                'prenda_id' => $prendaId,
            ]);

            return $this->failure('Error al obtener los colores y telas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /asesores/pedidos/{pedidoId}/prendas/{prendaId}/variante
     * Actualizar SOLO la variante de una prenda (merge de campos).
     */
    public function actualizarVariantePrend(ActualizarVariantePrendaRequest $request, int $pedidoId, int $prendaId): JsonResponse
    {
        try {
            Log::info('[VariantesPrendaController] PUT /pedidos/{pedidoId}/prendas/{prendaId}/variante', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'has_body' => $request->getContent() !== '',
            ]);

            $validated = $request->validated();

            $data = array_merge($validated, [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
            ]);

            $dto = ActualizarVariantePrendaDTO::fromRequest($data);
            $contextoActual = $this->variantesPrendaAuditoriaService->obtenerContextoActual($prendaId);
            $varianteActual = $contextoActual['variante_actual'];
            $nombrePrenda = $contextoActual['nombre_prenda'];

            $resultado = $this->actualizarVariantePrendaUseCase->ejecutar($dto);

            $this->variantesPrendaAuditoriaService->registrarCambios(
                $pedidoId,
                $prendaId,
                (string) $nombrePrenda,
                $validated,
                $varianteActual
            );

            Log::info('[VariantesPrendaController] Variante actualizada exitosamente', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'variante_id' => $resultado['id'] ?? null,
            ]);

            return $this->success($resultado, 'Variante actualizada correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[VariantesPrendaController] Validacion HTTP fallida', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'errors' => $e->errors(),
            ]);

            return $this->failure('Validacion de datos fallida', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\InvalidArgumentException $e) {
            Log::warning('[VariantesPrendaController] Validacion de negocio fallida', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);

            return $this->failure($e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('[VariantesPrendaController] Error actualizando variante de prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->failure('Error al actualizar variante: ' . $e->getMessage(), 500);
        }
    }
}
