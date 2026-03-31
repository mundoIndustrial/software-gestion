<?php

namespace App\Application\Operario\UseCases;

use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Domain\Operario\Repositories\PedidoProduccionOperarioReadRepository;
use App\Domain\Operario\Repositories\ReciboParcialReadRepository;
use Illuminate\Http\Request;

class ObtenerDatosRecibosOperarioUseCase
{
    public function __construct(
        private readonly PedidoProduccionOperarioReadRepository $pedidos,
        private readonly ReciboParcialReadRepository $parciales,
        private readonly ObtenerPedidoUseCase $obtenerPedidoUseCase,
    ) {}

    /**
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function execute(int $numeroPedido, Request $request): array
    {
        $tipoRecibo = (string) $request->query('tipo_recibo', 'COSTURA');
        $parcialId = $request->query('parcial_id');
        $consecutivoParcial = $request->query('consecutivo_parcial');

        \Log::info('[ObtenerDatosRecibosOperarioUseCase] INICIO', [
            'numero_pedido' => $numeroPedido,
            'tipo_recibo' => $tipoRecibo,
            'parcial_id' => $parcialId,
            'consecutivo_parcial' => $consecutivoParcial,
            'query_params_completos' => $request->query(),
            'url_actual' => $request->fullUrl(),
        ]);

        // OJO: este endpoint recibe un NÚMERO de pedido, no un ID. Evitar ambigüedad con findByIdOrNumero.
        $pedido = $this->pedidos->findByNumeroWithPrendas((int) $numeroPedido);

        \Log::info('[ObtenerDatosRecibosOperarioUseCase] Búsqueda de pedido', [
            'numero_pedido' => $numeroPedido,
            'encontrado' => !!$pedido,
            'pedido_id' => $pedido->id ?? null,
            'tipo_recibo' => $tipoRecibo,
            'parcial_id' => $parcialId,
        ]);

        if (!$pedido) {
            \Log::warning('[ObtenerDatosRecibosOperarioUseCase] Pedido no encontrado', [
                'numero_pedido' => $numeroPedido,
            ]);

            return [
                'status' => 404,
                'payload' => [
                    'success' => false,
                    'error' => 'not found',
                    'message' => 'Pedido no encontrado',
                ],
            ];
        }

        if ($tipoRecibo === 'PARCIAL' && $parcialId) {
            \Log::info('[ObtenerDatosRecibosOperarioUseCase] Obteniendo datos del parcial', [
                'parcial_id' => $parcialId,
                'parcial_id_type' => gettype($parcialId),
                'consecutivo_parcial' => $consecutivoParcial,
            ]);

            $parcial = $this->parciales->findByIdWithRelationsAndTallas((int) $parcialId);

            if (!$parcial) {
                \Log::error('[ObtenerDatosRecibosOperarioUseCase] Parcial no encontrado', [
                    'parcial_id' => (int) $parcialId,
                ]);

                return [
                    'status' => 404,
                    'payload' => [
                        'success' => false,
                        'error' => 'not found',
                        'message' => 'Parcial no encontrado',
                    ],
                ];
            }

            \Log::info('[ObtenerDatosRecibosOperarioUseCase] Parcial encontrado', [
                'parcial_id' => $parcial->id,
                'pedido_id' => $parcial->pedido_produccion_id,
                'prenda_id' => $parcial->prenda_pedido_id,
                'tallas_count' => $parcial->tallas?->count() ?? 0,
            ]);

            $datosPedido = $this->obtenerPedidoUseCase->ejecutar((int) $pedido->id, false);
            $responseData = $datosPedido->toArray();

            if (isset($responseData['prendas']) && $parcial->prenda_pedido_id) {
                $responseData['prendas'] = collect($responseData['prendas'])
                    ->filter(function ($prenda) use ($parcial) {
                        $id = $prenda['id'] ?? $prenda['prenda_id'] ?? $prenda['prenda_pedido_id'] ?? null;
                        return $id !== null && (int) $id === (int) $parcial->prenda_pedido_id;
                    })
                    ->map(function ($prenda) use ($parcial) {
                        $tallasParcial = ($parcial->tallas ?? collect())->map(function ($talla) {
                            return [
                                // La tabla recibos_por_partes_tallas no tiene género: tratar como UNISEX.
                                // El front (order-detail-modal-mobile) sabe transformar esta lista a estructura.
                                'genero' => 'UNISEX',
                                'talla' => $talla->talla,
                                'cantidad' => (int) $talla->cantidad,
                                'color_nombre' => $talla->color_nombre,
                            ];
                        })->values()->toArray();

                        // IMPORTANTE: en recibos parciales el front espera `prenda.tallas` como LISTA
                        // (para no mostrar tallas del recibo original).
                        $prenda['tallas'] = $tallasParcial;

                        // IMPORTANTE: algunas vistas/tarjetas usan `variantes` para renderizar la sección de tallas.
                        // En parciales se debe filtrar para NO devolver las tallas del recibo original.
                        $cantidadesPorTalla = [];
                        $cantidadesPorTallaYColor = [];
                        foreach ($tallasParcial as $registro) {
                            $t = strtoupper(trim((string) ($registro['talla'] ?? '')));
                            $c = (int) ($registro['cantidad'] ?? 0);
                            $color = strtoupper(trim((string) ($registro['color_nombre'] ?? '')));
                            if ($t === '' || $c <= 0) {
                                continue;
                            }
                            $cantidadesPorTalla[$t] = ($cantidadesPorTalla[$t] ?? 0) + $c;

                            if ($color !== '') {
                                if (!isset($cantidadesPorTallaYColor[$t])) {
                                    $cantidadesPorTallaYColor[$t] = [];
                                }
                                $cantidadesPorTallaYColor[$t][$color] = ($cantidadesPorTallaYColor[$t][$color] ?? 0) + $c;
                            }
                        }

                        // IMPORTANTÍSIMO: el front prioriza `prenda.talla_colores` en parciales.
                        // Si dejamos la talla_colores original, se muestran tallas del recibo original.
                        // Por eso, en parciales se sobreescribe con SOLO las tallas del parcial (con color si aplica).
                        if (!empty($cantidadesPorTallaYColor)) {
                            $telaNombre = $prenda['tela_nombre'] ?? $prenda['tela'] ?? null;
                            $tallaColoresParcial = [];
                            foreach ($cantidadesPorTallaYColor as $talla => $porColor) {
                                foreach ($porColor as $colorNombre => $cantidad) {
                                    $tallaColoresParcial[] = [
                                        'genero' => 'UNISEX',
                                        'talla' => $talla,
                                        'tela_nombre' => $telaNombre,
                                        'color_nombre' => $colorNombre,
                                        'cantidad' => (int) $cantidad,
                                        'referencia' => null,
                                        'observaciones' => null,
                                        'imagen_ruta' => null,
                                    ];
                                }
                            }
                            $prenda['talla_colores'] = $tallaColoresParcial;
                        } else {
                            // Si el parcial no tiene colores, evitar que se use talla_colores del original.
                            $prenda['talla_colores'] = [];
                        }

                        if (isset($prenda['variantes']) && is_array($prenda['variantes'])) {
                            $variantesFiltradas = [];
                            $tallasUsadas = [];

                            foreach ($prenda['variantes'] as $variante) {
                                if (!is_array($variante)) {
                                    continue;
                                }
                                $tallaVariante = strtoupper(trim((string) ($variante['talla'] ?? '')));
                                if ($tallaVariante === '' || !array_key_exists($tallaVariante, $cantidadesPorTalla)) {
                                    continue;
                                }
                                $variante['cantidad'] = $cantidadesPorTalla[$tallaVariante];

                                // Recalcular desglose de colores para que coincida con el parcial.
                                if (isset($cantidadesPorTallaYColor[$tallaVariante]) && !empty($cantidadesPorTallaYColor[$tallaVariante])) {
                                    $detalle = [];
                                    $colorInfoParts = [];
                                    foreach ($cantidadesPorTallaYColor[$tallaVariante] as $colorNombre => $cantidadColor) {
                                        $detalle[] = [
                                            'talla_color_id' => null,
                                            'color' => $colorNombre,
                                            'cantidad' => (int) $cantidadColor,
                                        ];
                                        $colorInfoParts[] = ((int) $cantidadColor) . '-' . $colorNombre;
                                    }
                                    $variante['colores_detalle'] = $detalle;
                                    $variante['color_info'] = implode(', ', $colorInfoParts);
                                } else {
                                    $variante['colores_detalle'] = [];
                                    $variante['color_info'] = '';
                                }

                                $variantesFiltradas[] = $variante;
                                $tallasUsadas[$tallaVariante] = true;
                            }

                            // Si por algún motivo no venía la variante original para una talla del parcial, crear una mínima.
                            foreach ($cantidadesPorTalla as $talla => $cantidad) {
                                if (isset($tallasUsadas[$talla])) {
                                    continue;
                                }
                                $nueva = [
                                    'talla' => $talla,
                                    'genero' => 'UNISEX',
                                    'cantidad' => $cantidad,
                                ];
                                if (isset($cantidadesPorTallaYColor[$talla]) && !empty($cantidadesPorTallaYColor[$talla])) {
                                    $detalle = [];
                                    $colorInfoParts = [];
                                    foreach ($cantidadesPorTallaYColor[$talla] as $colorNombre => $cantidadColor) {
                                        $detalle[] = [
                                            'talla_color_id' => null,
                                            'color' => $colorNombre,
                                            'cantidad' => (int) $cantidadColor,
                                        ];
                                        $colorInfoParts[] = ((int) $cantidadColor) . '-' . $colorNombre;
                                    }
                                    $nueva['colores_detalle'] = $detalle;
                                    $nueva['color_info'] = implode(', ', $colorInfoParts);
                                }
                                $variantesFiltradas[] = $nueva;
                            }

                            $prenda['variantes'] = array_values($variantesFiltradas);
                        }

                        // Además, muchos renderizadores usan `proceso.tallas` (estructura).
                        // Para parciales, sobreescribir `proceso.tallas` para que NO muestre las tallas del recibo original.
                        $unisex = [];
                        foreach ($tallasParcial as $registro) {
                            $tallaNombre = strtoupper(trim((string) ($registro['talla'] ?? '')));
                            $cantidad = (int) ($registro['cantidad'] ?? 0);
                            if ($tallaNombre === '' || $cantidad <= 0) {
                                continue;
                            }
                            $unisex[$tallaNombre] = ($unisex[$tallaNombre] ?? 0) + $cantidad;
                        }

                        $tallasProceso = [
                            'dama' => [],
                            'caballero' => [],
                            'unisex' => $unisex,
                        ];

                        if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                            $prenda['procesos'] = array_map(function ($proceso) use ($tallasProceso) {
                                if (is_array($proceso)) {
                                    $proceso['tallas'] = $tallasProceso;
                                }
                                return $proceso;
                            }, $prenda['procesos']);
                        }

                        $reciboKey = strtoupper(trim((string) ($parcial->tipo_recibo ?: 'COSTURA')));

                        // Mantener `recibos` como objeto (no array) para que el front lo detecte.
                        $prenda['recibos'] = [
                            $reciboKey => [
                                'id' => $parcial->id,
                                'consecutivo_actual' => (float) $parcial->consecutivo_parcial,
                                'consecutivo_parcial' => (float) $parcial->consecutivo_parcial,
                                'consecutivo_original' => (float) $parcial->consecutivo_original,
                                'tipo_recibo' => $parcial->tipo_recibo,
                                'area' => $parcial->area,
                                'encargado' => $parcial->encargado,
                                'tallas' => $tallasParcial,
                            ],
                        ];

                        return $prenda;
                    })
                    ->values()
                    ->toArray();
            }

            \Log::info('[ObtenerDatosRecibosOperarioUseCase] Respuesta de parcial enviada', [
                'keys' => array_keys($responseData),
                'tiene_prendas' => isset($responseData['prendas']),
                'total_prendas' => count($responseData['prendas'] ?? []),
            ]);

            return [
                'status' => 200,
                'payload' => [
                    'success' => true,
                    'data' => $responseData,
                ],
            ];
        }

        \Log::info('[ObtenerDatosRecibosOperarioUseCase] Llamando ObtenerPedidoUseCase');
        $datosPedido = $this->obtenerPedidoUseCase->ejecutar((int) $pedido->id, false);
        \Log::info('[ObtenerDatosRecibosOperarioUseCase] Datos obtenidos del UseCase');

        $responseData = $datosPedido->toArray();

        \Log::info('[ObtenerDatosRecibosOperarioUseCase] Respuesta enviada', [
            'keys' => array_keys($responseData),
            'tiene_prendas' => isset($responseData['prendas']),
            'total_prendas' => count($responseData['prendas'] ?? []),
        ]);

        return [
            'status' => 200,
            'payload' => [
                'success' => true,
                'data' => $responseData,
            ],
        ];
    }
}
