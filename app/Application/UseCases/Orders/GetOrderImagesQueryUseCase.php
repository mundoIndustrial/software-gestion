<?php

namespace App\Application\UseCases\Orders;

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
        try {
            $images = [];

            \Log::info(' [getOrderImages] Iniciando búsqueda de imágenes', [
                'pedido' => $pedido,
                'tipo' => $tipo,
            ]);

            $pedidoProduccion = $this->orderImageQueryService->findPedidoProduccionByNumero($pedido);

            \Log::info(' [getOrderImages] Pedido encontrado', [
                'pedido_id' => $pedidoProduccion?->id,
                'cotizacion_id' => $pedidoProduccion?->cotizacion_id,
            ]);

            $normalize = $this->normalizer();

            // Si el tipo es 'logo', devolver solo imágenes de logo
            if ($tipo === 'logo') {
                return $this->getLogoImagesQueryUseCase->execute($pedido);
            }

            // 1) Incluir imágenes asociadas a la cotización (si existe)
            if ($pedidoProduccion && $pedidoProduccion->cotizacion_id) {
                $cotImages = $this->orderImageQueryService->getCotizacionImagenes((int) $pedidoProduccion->cotizacion_id);

                foreach ($cotImages as $ci) {
                    // Soportar formatos: string URL ó objeto/array con campo 'url'
                    $raw = null;
                    if (is_string($ci)) {
                        $raw = $ci;
                    } elseif (is_array($ci) && isset($ci['url'])) {
                        $raw = $ci['url'];
                    } elseif (is_object($ci) && isset($ci->url)) {
                        $raw = $ci->url;
                    }

                    $url = $normalize($raw);
                    if ($url) {
                        $images[] = [
                            'url' => $url,
                            'type' => 'cotizacion',
                        ];
                    }
                }
            }

            // 2) Incluir imágenes guardadas por prenda en el pedido (AGRUPADAS POR PRENDA)
            $prendasConImagenes = [];

            try {
                $prendas = $this->orderImageQueryService->getPrendasByNumeroPedido($pedido);

                \Log::info(' [getOrderImages] Prendas encontradas', [
                    'total_prendas' => $prendas->count(),
                ]);

                foreach ($prendas as $index => $prenda) {
                    $imagenesPrend = [];

                    // Fotos de prenda
                    $fotosPrenda = $this->orderImageQueryService->getFotosPrenda((int) $prenda->id);

                    \Log::info('[getOrderImages] Fotos de prenda encontradas', [
                        'prenda_id' => $prenda->id,
                        'cantidad' => $fotosPrenda->count(),
                    ]);

                    foreach ($fotosPrenda as $fp) {
                        $ruta = $fp->ruta_webp ?? $fp->ruta_original ?? $fp->ruta_miniatura ?? null;

                        \Log::info('[getOrderImages] Foto de prenda - Datos en BD', [
                            'ruta_webp' => $fp->ruta_webp,
                            'ruta_original' => $fp->ruta_original,
                            'ruta_miniatura' => $fp->ruta_miniatura,
                            'ruta_seleccionada' => $ruta,
                        ]);

                        $url = $normalize($ruta);
                        if ($url) {
                            $imagenesPrend[] = [
                                'url' => $url,
                                'type' => 'prenda',
                                'orden' => $fp->orden,
                            ];
                        }
                    }

                    // Fotos de tela
                    $fotosTela = $this->orderImageQueryService->getFotosTela((int) $prenda->id);

                    \Log::info('[getOrderImages] Fotos de tela encontradas', [
                        'prenda_id' => $prenda->id,
                        'cantidad' => $fotosTela->count(),
                    ]);

                    foreach ($fotosTela as $ft) {
                        $ruta = $ft->ruta_webp ?? $ft->ruta_original ?? $ft->ruta_miniatura ?? null;

                        \Log::info('[getOrderImages] Foto de tela - Datos en BD', [
                            'ruta_webp' => $ft->ruta_webp,
                            'ruta_original' => $ft->ruta_original,
                            'ruta_miniatura' => $ft->ruta_miniatura,
                            'ruta_seleccionada' => $ruta,
                        ]);

                        $url = $normalize($ruta);
                        if ($url) {
                            $imagenesPrend[] = [
                                'url' => $url,
                                'type' => 'tela',
                                'orden' => $ft->orden,
                            ];
                        }
                    }

                    // Solo agregar prenda si tiene imágenes
                    if (!empty($imagenesPrend)) {
                        $prendasConImagenes[] = [
                            'numero' => $index + 1,
                            'nombre' => $prenda->nombre_prenda,
                            'imagenes' => $imagenesPrend,
                        ];
                    }
                }

                \Log::info(' [getOrderImages] Prendas con imágenes', [
                    'total_prendas_con_imagenes' => count($prendasConImagenes),
                ]);
            } catch (\Exception $inner) {
                \Log::warning('Error al consultar tablas de fotos de prenda: ' . $inner->getMessage(), ['pedido' => $pedido]);
            }

            \Log::info(' [getOrderImages] Resultado final', [
                'total_prendas' => count($prendasConImagenes ?? []),
                'total_images_cotizacion' => count($images),
            ]);

            return [
                'status' => 200,
                'data' => [
                    'success' => true,
                    'prendas' => $prendasConImagenes ?? [],
                    'images_cotizacion' => $images,
                    'pedido' => $pedido,
                ],
            ];
        } catch (\Exception $e) {
            \Log::error('Error al obtener imágenes de orden: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 500,
                'data' => [
                    'success' => false,
                    'message' => 'Error al obtener imágenes',
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

            // Si ya es una URL completa, devolverla tal cual
            if (str_starts_with($ruta, 'http')) {
                return $ruta;
            }

            // Si ya comienza con /storage/, devolverla tal cual (ya está correcta)
            if (str_starts_with($ruta, '/storage/')) {
                return $ruta;
            }

            // Si comienza con storage/ (sin /), agregar / al inicio
            if (str_starts_with($ruta, 'storage/')) {
                return '/' . $ruta;
            }

            // Si es una ruta relativa (ej: pedidos/2695/prendas/...), agregar /storage/
            return '/storage/' . ltrim($ruta, '/');
        };
    }
}

