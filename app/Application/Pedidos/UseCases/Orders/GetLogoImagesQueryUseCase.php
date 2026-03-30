<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Infrastructure\QueryServices\OrderImageQueryService;

class GetLogoImagesQueryUseCase
{
    public function __construct(
        private readonly OrderImageQueryService $orderImageQueryService,
    ) {}

    /**
     * @return array{status:int,data:array}
     */
    public function execute(string $pedido): array
    {
        $response = null;

        try {
            \Log::info(' [getLogoImages] Iniciando busqueda de imagenes de logo', [
                'numero_pedido' => $pedido,
            ]);

            $normalize = $this->normalizer();
            $logoPedido = $this->orderImageQueryService->findLogoPedidoRowByPedido($pedido);

            \Log::info(' [getLogoImages] Logo pedido encontrado', [
                'logo_pedido_id' => $logoPedido?->id,
                'pedido_id' => $logoPedido?->pedido_id,
                'numero_pedido' => $logoPedido?->numero_pedido,
            ]);

            $logos = [];

            if ($logoPedido && $logoPedido->pedido_id) {
                $numeroPedidoProduccion = $this->orderImageQueryService->getPedidoNumeroByPedidoProduccionId((int) $logoPedido->pedido_id);

                if ($numeroPedidoProduccion) {
                    $prendas = $this->orderImageQueryService->getPrendasByNumeroPedido((string) $numeroPedidoProduccion);

                    \Log::info(' [getLogoImages] Prendas encontradas', [
                        'total' => $prendas->count(),
                    ]);

                    $logos = $this->buildLogosFromPrendas($prendas, $normalize);
                }
            }

            \Log::info(' [getLogoImages] Resultado final', [
                'total_logos' => count($logos),
                'total_imagenes' => collect($logos)->sum(fn ($l) => count($l['imagenes'] ?? [])),
            ]);

            $response = $this->successResponse($pedido, $logos);
        } catch (\Exception $e) {
            \Log::error(' [getLogoImages] Error al obtener imagenes de logo: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'trace' => $e->getTraceAsString(),
            ]);

            $response = $this->errorResponse($e);
        }

        return $response;
    }

    /**
     * @param \Illuminate\Support\Collection<int, object> $prendas
     * @return array<int, array<string, mixed>>
     */
    private function buildLogosFromPrendas($prendas, \Closure $normalize): array
    {
        $logos = [];

        foreach ($prendas as $prenda) {
            $imagenes = $this->orderImageQueryService->getLogoImagenesByPrenda((int) $prenda->id);
            if ($imagenes->isEmpty()) {
                continue;
            }

            $imagenesFormateadas = $this->formatLogoImages($imagenes, $normalize);
            if (empty($imagenesFormateadas)) {
                continue;
            }

            $logos[] = [
                'id' => $prenda->id,
                'titulo' => $prenda->nombre_prenda,
                'ubicacion' => $imagenes->first()->ubicacion ?? 'General',
                'imagenes' => $imagenesFormateadas,
            ];
        }

        return $logos;
    }

    /**
     * @param \Illuminate\Support\Collection<int, object> $imagenes
     * @return array<int, array<string, mixed>>
     */
    private function formatLogoImages($imagenes, \Closure $normalize): array
    {
        $imagenesFormateadas = [];

        foreach ($imagenes as $img) {
            $ruta = $img->ruta_webp ?? $img->ruta_original;
            $url = $normalize($ruta);
            if (!$url) {
                continue;
            }

            $imagenesFormateadas[] = [
                'url' => $url,
                'nombre' => basename((string) $ruta),
                'orden' => $img->orden,
                'ancho' => $img->ancho,
                'alto' => $img->alto,
            ];
        }

        return $imagenesFormateadas;
    }

    /**
     * @param array<int, array<string, mixed>> $logos
     * @return array{status:int,data:array}
     */
    private function successResponse(string $pedido, array $logos): array
    {
        return [
            'status' => 200,
            'data' => [
                'success' => true,
                'logos' => $logos,
                'pedido' => $pedido,
                'tipo' => 'logo',
            ],
        ];
    }

    /**
     * @return array{status:int,data:array}
     */
    private function errorResponse(\Exception $e): array
    {
        return [
            'status' => 500,
            'data' => [
                'success' => false,
                'message' => 'Error al obtener imagenes de logo',
                'error' => $e->getMessage(),
            ],
        ];
    }

    private function normalizer(): \Closure
    {
        return function ($ruta) {
            $rutaNormalizada = null;

            if (!empty($ruta)) {
                if (str_starts_with($ruta, 'http') || str_starts_with($ruta, '/storage/')) {
                    $rutaNormalizada = $ruta;
                } elseif (str_starts_with($ruta, 'storage/')) {
                    $rutaNormalizada = '/' . $ruta;
                } else {
                    $rutaNormalizada = '/storage/' . ltrim($ruta, '/');
                }
            }

            return $rutaNormalizada;
        };
    }
}


