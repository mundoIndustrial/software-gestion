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
        try {
            \Log::info(' [getLogoImages] Iniciando búsqueda de imágenes de logo', [
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

                    foreach ($prendas as $prenda) {
                        $imagenes = $this->orderImageQueryService->getLogoImagenesByPrenda((int) $prenda->id);

                        if ($imagenes->count() > 0) {
                            $imagenesFormateadas = [];
                            foreach ($imagenes as $img) {
                                // Priorizar ruta_webp, luego ruta_original
                                $ruta = $img->ruta_webp ?? $img->ruta_original;
                                $url = $normalize($ruta);

                                if ($url) {
                                    $imagenesFormateadas[] = [
                                        'url' => $url,
                                        'nombre' => basename($ruta),
                                        'orden' => $img->orden,
                                        'ancho' => $img->ancho,
                                        'alto' => $img->alto,
                                    ];
                                }
                            }

                            if (!empty($imagenesFormateadas)) {
                                $logos[] = [
                                    'id' => $prenda->id,
                                    'titulo' => $prenda->nombre_prenda,
                                    'ubicacion' => $imagenes->first()->ubicacion ?? 'General',
                                    'imagenes' => $imagenesFormateadas,
                                ];
                            }
                        }
                    }
                }
            }

            \Log::info(' [getLogoImages] Resultado final', [
                'total_logos' => count($logos),
                'total_imagenes' => collect($logos)->sum(fn ($l) => count($l['imagenes'] ?? [])),
            ]);

            return [
                'status' => 200,
                'data' => [
                    'success' => true,
                    'logos' => $logos,
                    'pedido' => $pedido,
                    'tipo' => 'logo',
                ],
            ];
        } catch (\Exception $e) {
            \Log::error(' [getLogoImages] Error al obtener imágenes de logo: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 500,
                'data' => [
                    'success' => false,
                    'message' => 'Error al obtener imágenes de logo',
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    private function normalizer(): \Closure
    {
        return function ($ruta) {
            if (empty($ruta)) {
                return null;
            }

            if (str_starts_with($ruta, 'http')) {
                return $ruta;
            }

            if (str_starts_with($ruta, '/storage/')) {
                return $ruta;
            }

            if (str_starts_with($ruta, 'storage/')) {
                return '/' . $ruta;
            }

            return '/storage/' . ltrim($ruta, '/');
        };
    }
}


