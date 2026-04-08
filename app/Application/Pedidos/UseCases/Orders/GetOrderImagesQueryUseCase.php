<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Infrastructure\QueryServices\OrderImageQueryService;

class GetOrderImagesQueryUseCase
{
    public function __construct(
        private readonly OrderImageQueryService $orderImageQueryService,
        private readonly GetLogoImagesQueryUseCase $getLogoImagesQueryUseCase,
    ) {}

    /**
     * @return array{status:int,data:array}
     */
    public function execute(string $pedido, ?string $tipo = null): array
    {
        $response = null;

        try {
            \Log::info(' [getOrderImages] Iniciando busqueda de imagees', [
                'pedido' => $pedido,
                'tipo' => $tipo,
            ]);

            $pedidoProduccion = $this->orderImageQueryService->findPedidoProduccionByNumero($pedido);

            \Log::info(' [getOrderImages] Pedido encontrado', [
                'pedido_id' => $pedidoProduccion?->id,
                'cotizacion_id' => $pedidoProduccion?->cotizacion_id,
            ]);

            // Si el tipo es 'logo', devolver solo imagenes de logo
            if ($tipo === 'logo') {
                $response = $this->getLogoImagesQueryUseCase->execute($pedido);
            } else {
                $normalize = $this->normalizer();
                $images = $this->buildCotizacionImages($pedidoProduccion?->cotizacion_id, $normalize);
                $prendasConImagenes = $this->buildPrendasConImagenes($pedido, $normalize);

                \Log::info(' [getOrderImages] Resultado final', [
                    'total_prendas' => count($prendasConImagenes),
                    'total_images_cotizacion' => count($images),
                ]);

                $response = $this->successResponse($pedido, $prendasConImagenes, $images);
            }
        } catch (\Exception $e) {
            \Log::error('Error al obtener imagenes de orden: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'error' => $e->getMessage(),
            ]);

            $response = $this->errorResponse();
        }

        return $response;
    }

    /**
     * @return array<int, array{url:string,type:string}>
     */
    private function buildCotizacionImages(?int $cotizacionId, \Closure $normalize): array
    {
        if (!$cotizacionId) {
            return [];
        }

        $images = [];
        $cotImages = $this->orderImageQueryService->getCotizacionImagenes($cotizacionId);

        foreach ($cotImages as $ci) {
            $raw = $this->extractRawImagePath($ci);
            $url = $normalize($raw);

            if ($url) {
                $images[] = [
                    'url' => $url,
                    'type' => 'cotizacion',
                ];
            }
        }

        return $images;
    }

    private function extractRawImagePath(mixed $imageData): ?string
    {
        $raw = null;

        if (is_string($imageData)) {
            $raw = $imageData;
        } elseif (is_array($imageData) && isset($imageData['url'])) {
            $raw = (string) $imageData['url'];
        } elseif (is_object($imageData) && isset($imageData->url)) {
            $raw = (string) $imageData->url;
        }

        return $raw;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPrendasConImagenes(string $pedido, \Closure $normalize): array
    {
        $prendasConImagenes = [];

        try {
            $prendas = $this->orderImageQueryService->getPrendasByNumeroPedido($pedido);

            \Log::info(' [getOrderImages] Prendas encontradas', [
                'total_prendas' => $prendas->count(),
            ]);

            foreach ($prendas as $index => $prenda) {
                $imagenesPrend = array_merge(
                    $this->collectPrendaFotos((int) $prenda->id, $normalize),
                    $this->collectTelaFotos((int) $prenda->id, $normalize),
                );

                if (!empty($imagenesPrend)) {
                    $prendasConImagenes[] = [
                        'numero' => $index + 1,
                        'nombre' => $prenda->nombre_prenda,
                        'imagenes' => $imagenesPrend,
                    ];
                }
            }

            \Log::info(' [getOrderImages] Prendas con imagenes', [
                'total_prendas_con_imagenes' => count($prendasConImagenes),
            ]);
        } catch (\Exception $inner) {
            \Log::warning('Error al consultar tablas de fotos de prenda: ' . $inner->getMessage(), ['pedido' => $pedido]);
        }

        return $prendasConImagenes;
    }

    /**
     * @return array<int, array{url:string,type:string,orden:mixed}>
     */
    private function collectPrendaFotos(int $prendaId, \Closure $normalize): array
    {
        $fotosPrenda = $this->orderImageQueryService->getFotosPrenda($prendaId);

        \Log::info('[getOrderImages] Fotos de prenda encontradas', [
            'prenda_id' => $prendaId,
            'cantidad' => $fotosPrenda->count(),
        ]);

        return $this->mapFotos($fotosPrenda, 'prenda', '[getOrderImages] Foto de prenda - Datos en BD', $normalize);
    }

    /**
     * @return array<int, array{url:string,type:string,orden:mixed}>
     */
    private function collectTelaFotos(int $prendaId, \Closure $normalize): array
    {
        $fotosTela = $this->orderImageQueryService->getFotosTela($prendaId);

        \Log::info('[getOrderImages] Fotos de tela encontradas', [
            'prenda_id' => $prendaId,
            'cantidad' => $fotosTela->count(),
        ]);

        return $this->mapFotos($fotosTela, 'tela', '[getOrderImages] Foto de tela - Datos en BD', $normalize);
    }

    /**
     * @param \Illuminate\Support\Collection<int, object> $fotos
     * @return array<int, array{url:string,type:string,orden:mixed}>
     */
    private function mapFotos($fotos, string $type, string $logMessage, \Closure $normalize): array
    {
        $imagenes = [];

        foreach ($fotos as $foto) {
            $ruta = $foto->ruta_webp ?? $foto->ruta_original ?? $foto->ruta_miniatura ?? null;

            \Log::info($logMessage, [
                'ruta_webp' => $foto->ruta_webp,
                'ruta_original' => $foto->ruta_original,
                'ruta_miniatura' => $foto->ruta_miniatura,
                'ruta_seleccionada' => $ruta,
            ]);

            $url = $normalize($ruta);
            if (!$url) {
                continue;
            }

            $imagenes[] = [
                'url' => $url,
                'type' => $type,
                'orden' => $foto->orden,
            ];
        }

        return $imagenes;
    }

    /**
     * @param array<int, array<string, mixed>> $prendasConImagenes
     * @param array<int, array{url:string,type:string}> $images
     * @return array{status:int,data:array}
     */
    private function successResponse(string $pedido, array $prendasConImagenes, array $images): array
    {
        return [
            'status' => 200,
            'data' => [
                'success' => true,
                'prendas' => $prendasConImagenes,
                'images_cotizacion' => $images,
                'pedido' => $pedido,
            ],
        ];
    }

    /**
     * @return array{status:int,data:array}
     */
    private function errorResponse(): array
    {
        return [
            'status' => 500,
            'data' => [
                'success' => false,
                'message' => 'Error al obtener imagenes',
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


