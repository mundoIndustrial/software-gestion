<?php

namespace App\Application\Operario\UseCases;

use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Domain\Operario\Repositories\PedidoProduccionOperarioReadRepository;
use App\Domain\Operario\Repositories\ReciboParcialReadRepository;
use App\Models\ReciboPorPartes;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ObtenerDatosRecibosOperarioUseCase
{
    public function __construct(
        private readonly PedidoProduccionOperarioReadRepository $pedidos,
        private readonly ReciboParcialReadRepository $parciales,
        private readonly ObtenerPedidoUseCase $obtenerPedidoUseCase,
    ) {
    }

    /**
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function execute(int $numeroPedido, Request $request): array
    {
        $tipoRecibo = (string) $request->query('tipo_recibo', 'COSTURA');
        $tipoReciboUpper = strtoupper(trim($tipoRecibo));
        $parcialId = $request->query('parcial_id');
        $consecutivoParcial = $request->query('consecutivo_parcial');
        $reciboId = (int) $request->query('recibo_id', 0);

        \Log::info('[ObtenerDatosRecibosOperarioUseCase] INICIO', [
            'numero_pedido' => $numeroPedido,
            'tipo_recibo' => $tipoRecibo,
            'parcial_id' => $parcialId,
            'consecutivo_parcial' => $consecutivoParcial,
            'recibo_id' => $reciboId,
            'query_params_completos' => $request->query(),
            'url_actual' => $request->fullUrl(),
        ]);

        // OJO: este endpoint recibe un NUMERO de pedido, no un ID. Evitar ambiguedad con findByIdOrNumero.
        $pedido = null;
        $reciboBodega = null;

        if ($reciboId > 0) {
            $reciboBodega = \App\Models\ConsecutivoReciboPedido::with(['prendaBodega.fotos'])->find($reciboId);
        }

        // Fallback bodega: si llega parcial_id sin recibo_id, resolver recibo base.
        if (!$reciboBodega && $reciboId <= 0 && $parcialId && in_array($tipoReciboUpper, ['COSTURA-BODEGA', 'CORTE-PARA-BODEGA', 'BODEGA'], true)) {
            $parcialBodega = DB::table('recibo_por_partes')
                ->where('id', (int) $parcialId)
                ->first(['pedido_produccion_id', 'prenda_pedido_id', 'consecutivo_original', 'tipo_recibo']);

            if ($parcialBodega) {
                $tipoParcialUpper = strtoupper(trim((string) ($parcialBodega->tipo_recibo ?? '')));
                if (in_array($tipoParcialUpper, ['COSTURA-BODEGA', 'CORTE-PARA-BODEGA', 'BODEGA'], true)) {
                    $reciboBodega = \App\Models\ConsecutivoReciboPedido::with(['prendaBodega.fotos'])
                        ->whereRaw('UPPER(TRIM(tipo_recibo)) IN (?, ?)', ['COSTURA-BODEGA', 'CORTE-PARA-BODEGA'])
                        ->where('consecutivo_actual', (int) ($parcialBodega->consecutivo_original ?? 0))
                        ->where('prenda_bodega_id', (int) ($parcialBodega->prenda_pedido_id ?? 0))
                        ->where('activo', 1)
                        ->orderByDesc('id')
                        ->first();

                    if ($reciboBodega) {
                        $reciboId = (int) $reciboBodega->id;
                    }
                }
            }
        }

        $isBodegaByTipo = in_array($tipoReciboUpper, ['COSTURA-BODEGA', 'CORTE-PARA-BODEGA', 'BODEGA'], true);
        $isBodegaOnly = (bool) (
            ($reciboBodega && in_array(strtoupper(trim((string) $reciboBodega->tipo_recibo)), ['COSTURA-BODEGA', 'CORTE-PARA-BODEGA'], true))
            || ($numeroPedido === 0 && $reciboId > 0)
            || ($isBodegaByTipo && $reciboId > 0)
        );

        if ($isBodegaByTipo && $reciboId > 0 && !$reciboBodega) {
            \Log::warning('[ObtenerDatosRecibosOperarioUseCase] Recibo bodega no encontrado', [
                'recibo_id' => $reciboId,
                'numero_pedido_url' => $numeroPedido,
                'tipo_recibo' => $tipoRecibo,
            ]);

            return [
                'status' => 404,
                'payload' => [
                    'success' => false,
                    'error' => 'not found',
                    'message' => 'Recibo de bodega no encontrado',
                ],
            ];
        }

        if ($isBodegaOnly && $reciboBodega && in_array(strtoupper(trim((string) $reciboBodega->tipo_recibo)), ['COSTURA-BODEGA', 'CORTE-PARA-BODEGA'], true)) {
            $pedido = (object) [
                'id' => null,
                'numero_pedido' => 'BODEGA',
                'cliente' => 'SERVICIO',
                'asesor_id' => 'SISTEMA',
                'forma_de_pago' => 'N/A',
                'estado' => $reciboBodega->estado ?? 'En Ejecución',
                'created_at' => $reciboBodega->created_at,
                'fecha_estimada' => null,
                'descripcion' => $reciboBodega->prendaBodega?->descripcion ?? 'Recibo de Bodega',
                'total_prendas' => 1,
                'novedades' => $reciboBodega->notas ?? 'Sin novedades',
                'nombre_prenda_bodega' => $reciboBodega->prendaBodega?->nombre ?? 'N/A'
            ];
        } else {
            $pedido = $this->pedidos->findByNumeroWithPrendas((int) $numeroPedido);
        }

        \Log::info('[ObtenerDatosRecibosOperarioUseCase] Búsqueda de pedido', [
            'numero_pedido' => $numeroPedido,
            'is_bodega_only' => $isBodegaOnly,
            'encontrado' => !!$pedido,
            'tipo_recibo' => $tipoRecibo,
            'parcial_id' => $parcialId,
            'recibo_id' => $reciboId,
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
        $pedidoIdParaQuery = $isBodegaOnly
            ? (int) ($reciboBodega->pedido_produccion_id ?? 0)
            : (int) ($pedido->id ?? 0);



        // Si se proporciona recibo_id, filtrar por ese recibo especi­fico
        if ($reciboId > 0) {
            $reciboEspecifico = DB::table('consecutivos_recibos_pedidos')
                ->where('id', $reciboId)
                ->where('pedido_produccion_id', $pedidoIdParaQuery)
                ->first(['prenda_id', 'tipo_recibo']);

            if ($reciboEspecifico) {
                // Actualizar los parámetros para filtrar por este recibo específico
                $tipoRecibo = (string) $reciboEspecifico->tipo_recibo;
                if ($reciboEspecifico->prenda_id) {
                    $request->merge(['prenda_id' => (int) $reciboEspecifico->prenda_id]);
                }

                \Log::info('[ObtenerDatosRecibosOperarioUseCase] Recibo específico encontrado', [
                    'recibo_id' => $reciboId,
                    'prenda_id' => $reciboEspecifico->prenda_id,
                    'tipo_recibo' => $tipoRecibo,
                ]);
            }
        }

        if ($parcialId) {
            $prendaId = $request->query('prenda_id');
            $generoBase = 'CABALLERO';

            \Log::info('[ObtenerDatosRecibosOperarioUseCase] Obteniendo datos del parcial', [
                'parcial_id' => $parcialId,
                'parcial_id_type' => gettype($parcialId),
                'consecutivo_parcial' => $consecutivoParcial,
                'prenda_id' => $prendaId,
                'pedido_id' => $pedidoIdParaQuery,
            ]);

            $parcial = $this->parciales->findByIdWithRelationsAndTallas((int) $parcialId);

            if ($parcial) {
                \Log::debug('[ObtenerDatosRecibosOperarioUseCase] Datos crudos del parcial', [
                    'parcial_id' => $parcial->id,
                    'tallas_json' => json_encode($parcial->tallas),
                    'primer_talla' => $parcial->tallas ? json_encode($parcial->tallas->first()) : 'sin tallas',
                ]);
            }

            if (!$parcial) {
                // Fallback defensivo: algunos cards envían parcial_id no alineado con recibo_por_partes.id.
                // Intentar resolver por contexto (pedido + prenda + consecutivo_parcial).
                if ($consecutivoParcial !== null && $consecutivoParcial !== '' && $prendaId !== null && $prendaId !== '') {
                    $parcial = ReciboPorPartes::query()
                        ->with(['tallas', 'pedido', 'prenda'])
                        ->where('pedido_produccion_id', $pedidoIdParaQuery)
                        ->where('prenda_pedido_id', (int) $prendaId)
                        ->where('consecutivo_parcial', (float) $consecutivoParcial)
                        ->latest('id')
                        ->first();
                }
            }

            if (!$parcial) {
                // Compatibilidad legacy: algunos parciales todaví­a viven en pedidos_parciales.
                $parcialLegacy = DB::table('pedidos_parciales')
                    ->where('id', (int) $parcialId)
                    ->where('pedido_produccion_id', $pedidoIdParaQuery)
                    ->when(
                        $prendaId !== null && $prendaId !== '',
                        fn($query) => $query->where('prenda_pedido_id', (int) $prendaId)
                    )
                    ->when(
                        $consecutivoParcial !== null && $consecutivoParcial !== '',
                        fn($query) => $query->where('consecutivo_actual', (float) $consecutivoParcial)
                    )
                    ->whereNull('deleted_at')
                    ->first();

                if (!$parcialLegacy && $consecutivoParcial !== null && $consecutivoParcial !== '' && $prendaId !== null && $prendaId !== '') {
                    $parcialLegacy = DB::table('pedidos_parciales')
                        ->where('pedido_produccion_id', $pedidoIdParaQuery)
                        ->where('prenda_pedido_id', (int) $prendaId)
                        ->where('consecutivo_actual', (float) $consecutivoParcial)
                        ->whereNull('deleted_at')
                        ->orderByDesc('id')
                        ->first();
                }

                if ($parcialLegacy) {
                    $tallasLegacy = DB::table('pedidos_parciales_tallas')
                        ->where('pedido_parcial_id', (int) $parcialLegacy->id)
                        ->get(['talla', 'cantidad', 'color_nombre']);

                    $parcialLegacyNormalizado = new \stdClass();
                    $parcialLegacyNormalizado->id = (int) $parcialLegacy->id;
                    $parcialLegacyNormalizado->pedido_produccion_id = (int) $parcialLegacy->pedido_produccion_id;
                    $parcialLegacyNormalizado->prenda_pedido_id = (int) $parcialLegacy->prenda_pedido_id;
                    $parcialLegacyNormalizado->tipo_recibo = (string) ($parcialLegacy->tipo_recibo ?? 'COSTURA');
                    $parcialLegacyNormalizado->consecutivo_parcial = (float) ($parcialLegacy->consecutivo_actual ?? 0);
                    $parcialLegacyNormalizado->consecutivo_original = (float) ($parcialLegacy->consecutivo_inicial ?? $parcialLegacy->consecutivo_actual ?? 0);
                    $parcialLegacyNormalizado->area = 'Costura';
                    $parcialLegacyNormalizado->encargado = null;
                    $parcialLegacyNormalizado->tallas = $tallasLegacy;

                    $parcial = $parcialLegacyNormalizado;
                }
            }

            if (!$parcial) {
                \Log::error('[ObtenerDatosRecibosOperarioUseCase] Parcial no encontrado', [
                    'parcial_id' => (int) $parcialId,
                    'consecutivo_parcial' => $consecutivoParcial !== null && $consecutivoParcial !== '' ? (float) $consecutivoParcial : null,
                    'prenda_id' => $prendaId !== null && $prendaId !== '' ? (int) $prendaId : null,
                    'pedido_id' => $pedidoIdParaQuery,
                    'tipo_recibo' => $tipoRecibo,
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

            if ((int) $parcial->id !== (int) $parcialId) {
                \Log::warning('[ObtenerDatosRecibosOperarioUseCase] Parcial resuelto por fallback de contexto', [
                    'parcial_id_recibido' => (int) $parcialId,
                    'parcial_id_resuelto' => (int) $parcial->id,
                    'consecutivo_parcial' => $consecutivoParcial !== null && $consecutivoParcial !== '' ? (float) $consecutivoParcial : null,
                    'prenda_id' => $prendaId !== null && $prendaId !== '' ? (int) $prendaId : null,
                    'pedido_id' => $pedidoIdParaQuery,
                ]);
            }

            \Log::info('[ObtenerDatosRecibosOperarioUseCase] Parcial encontrado', [
                'parcial_id' => $parcial->id,
                'pedido_id' => $parcial->pedido_produccion_id,
                'prenda_id' => $parcial->prenda_pedido_id,
                'tallas_count' => $parcial->tallas?->count() ?? 0,
            ]);
            $pedidoIdParcial = (int) ($parcial->pedido_produccion_id ?? 0);
            if ($pedidoIdParcial <= 0) {
                $pedidoIdParcial = (int) $pedidoIdParaQuery;
            }

            // Fecha real de activación del recibo (tabla consecutivos_recibos_pedidos.created_at)
            // para mostrarla en la vista de detalle del parcial.
            $tipoReciboParcial = strtoupper(trim((string) ($parcial->tipo_recibo ?: 'COSTURA')));
            $consecutivoParcialInt = (int) round((float) ($parcial->consecutivo_parcial ?? 0));
            $consecutivoOriginalInt = (int) round((float) ($parcial->consecutivo_original ?? 0));

            // Prioridad 1: recibo del anexo identificado por parcial_id en notas.
            $baseReciboActivacionQuery = DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedidoIdParcial)
                ->where('prenda_id', (int) $parcial->prenda_pedido_id)
                ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [$tipoReciboParcial]);

            $reciboActivacion = (clone $baseReciboActivacionQuery)
                ->where('notas', 'like', '%parcial_id:' . (int) $parcial->id . '%')
                ->orderByDesc('created_at')
                ->first(['id', 'created_at']);

            // Prioridad 2: consecutivo del parcial (ej: 55 en tu caso).
            if (!$reciboActivacion && $consecutivoParcialInt > 0) {
                $reciboActivacion = (clone $baseReciboActivacionQuery)
                    ->where('consecutivo_actual', $consecutivoParcialInt)
                    ->orderByDesc('created_at')
                    ->first(['id', 'created_at']);
            }

            // Prioridad 3: consecutivo original como último fallback.
            if (!$reciboActivacion && $consecutivoOriginalInt > 0) {
                $reciboActivacion = (clone $baseReciboActivacionQuery)
                    ->where('consecutivo_actual', $consecutivoOriginalInt)
                    ->orderByDesc('created_at')
                    ->first(['id', 'created_at']);
            }

            $fechaActivacionRecibo = $reciboActivacion?->created_at
                ? (string) $reciboActivacion->created_at
                : null;
            if ($fechaActivacionRecibo === null && !empty($parcial->created_at)) {
                $fechaActivacionRecibo = (string) $parcial->created_at;
            }
            $fechaCreacionParcial = !empty($parcial->created_at)
                ? date('Y-m-d', strtotime((string) $parcial->created_at))
                : ($fechaActivacionRecibo ? date('Y-m-d', strtotime((string) $fechaActivacionRecibo)) : now()->format('Y-m-d'));

            $esParcialBodega = $isBodegaOnly
                && $reciboBodega
                && in_array(strtoupper(trim((string) ($reciboBodega->tipo_recibo ?? ''))), ['COSTURA-BODEGA', 'CORTE-PARA-BODEGA'], true);

            if ($esParcialBodega) {
                $prendaBodegaId = 0;
                foreach ([
                    $reciboBodega->prenda_bodega_id ?? null,
                    $reciboBodega->prenda_id ?? null,
                    $reciboBodega->prendaBodega?->id ?? null,
                ] as $candidatePrendaBodegaId) {
                    $candidatePrendaBodegaId = (int) $candidatePrendaBodegaId;
                    if ($candidatePrendaBodegaId > 0) {
                        $prendaBodegaId = $candidatePrendaBodegaId;
                        break;
                    }
                }

                $tallasParcialRows = DB::table('recibos_por_partes_tallas')
                    ->where('recibo_por_partes_id', (int) $parcial->id)
                    ->get(['talla', 'genero', 'color_nombre', 'cantidad']);

                $generoPorTalla = [];
                if ($prendaBodegaId > 0) {
                    $generoPorTalla = DB::table('prenda_tallas_bodega')
                        ->where('prenda_bodega_id', $prendaBodegaId)
                        ->get(['talla', 'genero'])
                        ->mapWithKeys(function ($row) {
                            $talla = strtoupper(trim((string) ($row->talla ?? '')));
                            $genero = strtoupper(trim((string) ($row->genero ?? 'UNISEX')));
                            return $talla !== '' ? [$talla => $genero] : [];
                        })
                        ->all();
                }

                $tallasParcial = collect($tallasParcialRows)->map(function ($row) use ($generoPorTalla) {
                    $talla = strtoupper(trim((string) ($row->talla ?? '')));
                    $generoFila = strtoupper(trim((string) ($row->genero ?? '')));
                    $genero = $generoFila !== '' ? $generoFila : strtoupper(trim((string) ($generoPorTalla[$talla] ?? 'UNISEX')));
                    if (!in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'], true)) {
                        $genero = 'UNISEX';
                    }

                    return [
                        'talla' => $talla,
                        'genero' => $genero,
                        'color_nombre' => trim((string) ($row->color_nombre ?? '')),
                        'cantidad' => (int) ($row->cantidad ?? 0),
                    ];
                })
                ->filter(fn($t) => $t['talla'] !== '' && $t['cantidad'] > 0)
                ->values()
                ->all();

                $dama = [];
                $caballero = [];
                $unisex = [];
                foreach ($tallasParcial as $talla) {
                    if ($talla['genero'] === 'DAMA') {
                        $dama[$talla['talla']] = ($dama[$talla['talla']] ?? 0) + (int) $talla['cantidad'];
                    } elseif ($talla['genero'] === 'CABALLERO') {
                        $caballero[$talla['talla']] = ($caballero[$talla['talla']] ?? 0) + (int) $talla['cantidad'];
                    } else {
                        $unisex[$talla['talla']] = ($unisex[$talla['talla']] ?? 0) + (int) $talla['cantidad'];
                    }
                }

                $tallaColoresParcial = collect($tallasParcial)->map(function (array $t) {
                    return [
                        'genero' => $t['genero'],
                        'talla' => $t['talla'],
                        'color_nombre' => $t['color_nombre'],
                        'cantidad' => $t['cantidad'],
                    ];
                })->values()->all();

                $descripcionBodega = trim((string) ($reciboBodega->prendaBodega?->descripcion ?? ''));
                if ($descripcionBodega === '') {
                    $descripcionBodega = 'Recibo de Bodega';
                }

                $consecutivoParcialValor = (float) ($parcial->consecutivo_parcial ?? 0);
                $consecutivoOriginalValor = (float) ($parcial->consecutivo_original ?? 0);
                $reciboParcialData = [
                    'id' => (int) $parcial->id,
                    'consecutivo_actual' => $consecutivoParcialValor,
                    'consecutivo_parcial' => $consecutivoParcialValor,
                    'consecutivo_original' => $consecutivoOriginalValor,
                    'tipo_recibo' => 'PARCIAL',
                    'area' => (string) ($reciboBodega->area ?? 'Costura'),
                    'encargado' => null,
                    'tallas' => $tallasParcial,
                    'tallas_estructura' => [
                        'DAMA' => $dama,
                        'CABALLERO' => $caballero,
                        'UNISEX' => $unisex,
                    ],
                    'talla_colores' => $tallaColoresParcial,
                    'observaciones' => '',
                    'es_parcial' => true,
                    'pedido_parcial_id' => (int) $parcial->id,
                    'fecha_activacion_recibo' => $fechaActivacionRecibo,
                    'created_at' => $fechaActivacionRecibo,
                ];

                $imagenes = [];
                if ($reciboBodega && $reciboBodega->prendaBodega && $reciboBodega->prendaBodega->fotos) {
                    foreach ($reciboBodega->prendaBodega->fotos as $foto) {
                        $rutaCompleta = $foto->url;
                        if (!str_starts_with($rutaCompleta, 'http') && !str_starts_with($rutaCompleta, '/storage/') && !str_starts_with($rutaCompleta, 'storage/')) {
                            $rutaCompleta = '/storage/' . $rutaCompleta;
                        }
                        $imagenes[] = [
                            'id' => $foto->id,
                            'url' => $rutaCompleta,
                            'ruta_webp' => $rutaCompleta,
                        ];
                    }
                }

                $responseData = [
                    'id' => null,
                    'numero_pedido' => $reciboBodega->consecutivo_actual,
                    'cliente' => 'SERVICIO',
                    'asesor' => 'SISTEMA',
                    'forma_pago' => 'N/A',
                    'estado' => $reciboBodega->estado ?? 'En Ejecución',
                    'fecha_creacion' => $reciboBodega->created_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
                    'descripcion_prendas' => $descripcionBodega,
                    'prendas' => [
                        [
                            'id' => $prendaBodegaId,
                            'prenda_id' => $prendaBodegaId,
                            'prenda_bodega_id' => $prendaBodegaId,
                            'nombre' => $reciboBodega->prendaBodega?->nombre ?? 'N/A',
                            'descripcion' => $descripcionBodega,
                            'cantidad' => (int) ($reciboBodega->cantidad ?? 0),
                            'tallas' => $tallasParcial,
                            'talla_colores' => $tallaColoresParcial,
                            'imagenes' => $imagenes,
                            'procesos' => [
                                [
                                    'proceso' => 'CORTE-PARA-BODEGA',
                                    'tipo_proceso' => 'CORTE-PARA-BODEGA',
                                    'nombre_proceso' => 'CORTE-PARA-BODEGA',
                                    'es_parcial' => true,
                                    'pedido_parcial_id' => (int) $parcial->id,
                                    'created_at' => $fechaActivacionRecibo,
                                    'tallas' => [
                                        'DAMA' => $dama,
                                        'CABALLERO' => $caballero,
                                        'UNISEX' => $unisex,
                                    ],
                                    'talla_colores' => $tallaColoresParcial,
                                ]
                            ],
                            'recibos' => [
                                'COSTURA-BODEGA' => array_merge($reciboParcialData, ['tipo_recibo' => 'COSTURA-BODEGA']),
                                'CORTE-PARA-BODEGA' => array_merge($reciboParcialData, ['tipo_recibo' => 'CORTE-PARA-BODEGA']),
                                'PARCIAL' => $reciboParcialData,
                            ],
                        ]
                    ],
                ];
                $responseData['fecha_activacion_recibo'] = $fechaActivacionRecibo;
                $responseData['fecha_creacion'] = $fechaCreacionParcial;
                $responseData['fecha_creacion'] = $fechaCreacionParcial;
                $responseData['fecha_creacion'] = $fechaCreacionParcial;

                return [
                    'status' => 200,
                    'payload' => [
                        'success' => true,
                        'data' => $responseData,
                    ],
                ];
            }

            $datosPedido = $this->obtenerPedidoUseCase->ejecutar($pedidoIdParcial, false);
            $responseData = $datosPedido->toArray();

            if (isset($responseData['prendas']) && $parcial->prenda_pedido_id) {
                $responseData['prendas'] = collect($responseData['prendas'])
                    ->filter(function ($prenda) use ($parcial) {
                        $id = $prenda['id'] ?? $prenda['prenda_id'] ?? $prenda['prenda_pedido_id'] ?? null;
                        return $id !== null && (int) $id === (int) $parcial->prenda_pedido_id;
                    })
                    ->map(function ($prenda) use ($parcial, $generoBase, $fechaActivacionRecibo, $pedido) {
                        $generoPrenda = strtoupper(trim((string) (
                            $prenda['genero']
                            ?? $prenda['tipo_flujo_tallas']
                            ?? $prenda['genero_principal']
                            ?? 'CABALLERO'
                        )));
                        if (!in_array($generoPrenda, ['DAMA', 'CABALLERO', 'UNISEX'], true)) {
                            $generoPrenda = $generoBase;
                        }

                        // Cargar tallas desde la tabla correcta
                        $isReciboPorPartes = ($parcial instanceof \App\Models\ReciboPorPartes);
                        $tallasTable = $isReciboPorPartes ? 'recibos_por_partes_tallas' : 'pedidos_parciales_tallas';
                        $foreignKey = $isReciboPorPartes ? 'recibo_por_partes_id' : 'pedido_parcial_id';
                        $columns = ['genero', 'talla', 'cantidad', 'color_nombre'];

                        $tallasRaw = DB::table($tallasTable)
                            ->where($foreignKey, (int) $parcial->id)
                            ->get($columns);

                        // Fallback de compatibilidad:
                        // Solo si no se encontraron tallas en la tabla principal, intentar buscar en la tabla legacy
                        // por contexto (no por ID directo si es ReciboPorPartes para evitar colisiones).
                        if ($tallasRaw->isEmpty()) {
                            $tipoReciboParcial = strtoupper(trim((string) ($parcial->tipo_recibo ?? '')));
                            $legacyParcial = DB::table('pedidos_parciales')
                                ->where('pedido_produccion_id', (int) $parcial->pedido_produccion_id)
                                ->where('prenda_pedido_id', (int) $parcial->prenda_pedido_id)
                                ->where('consecutivo_actual', (float) $parcial->consecutivo_parcial)
                                ->when(
                                    $tipoReciboParcial !== '',
                                    fn($q) => $q->whereRaw('UPPER(tipo_recibo) = ?', [$tipoReciboParcial])
                                )
                                ->whereNull('deleted_at')
                                ->orderByDesc('id')
                                ->first(['id']);

                            if ($legacyParcial?->id) {
                                $tallasRaw = DB::table('pedidos_parciales_tallas')
                                    ->where('pedido_parcial_id', (int) $legacyParcial->id)
                                    ->get(['genero', 'talla', 'cantidad', 'color_nombre']);
                            }
                        }

                        \Log::info('[NORMAL DEBUG 1] Tipo tallas raw: ' . gettype($tallasRaw));
                        \Log::info('[NORMAL DEBUG 2] Es Collection: ' . ($tallasRaw instanceof \Illuminate\Support\Collection ? 'si' : 'no'));
                        \Log::info('[NORMAL DEBUG 3] Contenido raw original: ' . json_encode($tallasRaw));

                        // Convertir a array de forma segura (ya es Collection, así­ que solo normalizamos)
                        if ($tallasRaw instanceof \Illuminate\Support\Collection) {
                            \Log::info('[NORMAL DEBUG 4] Entrando rama Collection');
                            $tallasRaw = $tallasRaw->toArray();
                        } elseif (is_object($tallasRaw)) {
                            \Log::info('[NORMAL DEBUG 4] Entrando rama object');
                            $tallasRaw = json_decode(json_encode($tallasRaw), true) ?: [];
                        } elseif (!is_array($tallasRaw)) {
                            \Log::info('[NORMAL DEBUG 4] Entrando rama no-array');
                            $tallasRaw = [];
                        }

                        \Log::info('[NORMAL DEBUG 5] Contenido despues conversion: ' . json_encode($tallasRaw));

                        $tallasParcial = collect($tallasRaw)->map(function ($talla) use ($generoPrenda) {
                            \Log::info('[NORMAL DEBUG 6] Procesando talla: ' . json_encode($talla));

                            // Soportar tanto acceso como objeto como array
                            $genero = is_array($talla) ? ($talla['genero'] ?? null) : ($talla->genero ?? null);
                            $genero = strtoupper(trim((string) ($genero ?: $generoPrenda)));
                            if (!in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'], true)) {
                                $genero = $generoPrenda;
                            }
                            $tallaNombre = is_array($talla) ? ($talla['talla'] ?? null) : ($talla->talla ?? null);
                            $cantidad = is_array($talla) ? ($talla['cantidad'] ?? 0) : ($talla->cantidad ?? 0);
                            $colorNombre = is_array($talla) ? ($talla['color_nombre'] ?? null) : ($talla->color_nombre ?? null);

                            \Log::info('[NORMAL DEBUG 7] Genero extrai­do: ' . $genero);

                            return [
                                'genero' => $genero,
                                'talla' => $tallaNombre,
                                'cantidad' => (int) $cantidad,
                                'color_nombre' => $colorNombre,
                            ];
                        })->values()->toArray();

                        // IMPORTANTE: en recibos parciales el front espera `prenda.tallas` como LISTA
                        // (para no mostrar tallas del recibo original).
                        $prenda['tallas'] = $tallasParcial;

                        // IMPORTANTE: algunas vistas/tarjetas usan `variantes` para renderizar la seccion de tallas.
                        // En parciales se debe filtrar para NO devolver las tallas del recibo original.
                        $cantidadesPorTalla = [];
                        $cantidadesPorTallaYColor = [];
                        $generosPorTalla = []; // Mapear talla -> genero para preservar info
                        foreach ($tallasParcial as $registro) {
                            $t = strtoupper(trim((string) ($registro['talla'] ?? '')));
                            $c = (int) ($registro['cantidad'] ?? 0);
                            $g = strtoupper(trim((string) ($registro['genero'] ?? 'UNISEX')));
                            $color = strtoupper(trim((string) ($registro['color_nombre'] ?? '')));
                            if ($t === '' || $c <= 0) {
                                continue;
                            }
                            $cantidadesPorTalla[$t] = ($cantidadesPorTalla[$t] ?? 0) + $c;
                            $generosPorTalla[$t] = $g; // Guardar genero (ultimo valor, normalmente es consistente)
    
                            if ($color !== '') {
                                if (!isset($cantidadesPorTallaYColor[$t])) {
                                    $cantidadesPorTallaYColor[$t] = [];
                                }
                                $cantidadesPorTallaYColor[$t][$color] = ($cantidadesPorTallaYColor[$t][$color] ?? 0) + $c;
                            }
                        }

                        // IMPORTANTISIMO: el front prioriza `prenda.talla_colores` en parciales.
                        // Si dejamos la talla_colores original, se muestran tallas del recibo original.
                        // Por eso, en parciales se sobreescribe con SOLO las tallas del parcial (con color si aplica).
                        if (!empty($cantidadesPorTallaYColor)) {
                            $telaNombre = $prenda['tela_nombre'] ?? $prenda['tela'] ?? null;
                            $tallaColoresParcial = [];
                            foreach ($cantidadesPorTallaYColor as $talla => $porColor) {
                                $generoParaTalla = $generosPorTalla[$talla] ?? 'UNISEX';
                                foreach ($porColor as $colorNombre => $cantidad) {
                                    $tallaColoresParcial[] = [
                                        'genero' => $generoParaTalla,
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

                            // Si por algun motivo no venia la variante original para una talla del parcial, crear una mi­nima.
                            foreach ($cantidadesPorTalla as $talla => $cantidad) {
                                if (isset($tallasUsadas[$talla])) {
                                    continue;
                                }
                                $generoParaTalla = $generosPorTalla[$talla] ?? 'UNISEX';
                                $nueva = [
                                    'talla' => $talla,
                                    'genero' => $generoParaTalla,
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

                        // Ademas, muchos renderizadores usan `proceso.tallas` (estructura).
                        // Para parciales, sobreescribir `proceso.tallas` para que NO muestre las tallas del recibo original.
                        // Distribuir correctamente por genero (DAMA, CABALLERO, UNISEX)
                        $dama = [];
                        $caballero = [];
                        $unisex = [];
                        foreach ($tallasParcial as $registro) {
                            $tallaNombre = strtoupper(trim((string) ($registro['talla'] ?? '')));
                            $cantidad = (int) ($registro['cantidad'] ?? 0);
                            $genero = strtoupper(trim((string) ($registro['genero'] ?? 'UNISEX')));

                            if ($tallaNombre === '' || $cantidad <= 0) {
                                continue;
                            }

                            if ($genero === 'DAMA') {
                                $dama[$tallaNombre] = ($dama[$tallaNombre] ?? 0) + $cantidad;
                            } elseif ($genero === 'CABALLERO') {
                                $caballero[$tallaNombre] = ($caballero[$tallaNombre] ?? 0) + $cantidad;
                            } else {
                                $unisex[$tallaNombre] = ($unisex[$tallaNombre] ?? 0) + $cantidad;
                            }
                        }

                        $tallasProceso = [
                            'dama' => $dama,
                            'caballero' => $caballero,
                            'unisex' => $unisex,
                        ];

                        $reciboKey = strtoupper(trim((string) ($parcial->tipo_recibo ?: 'COSTURA')));
                        $observacionProceso = (string) (DB::table('observaciones_recibos_procesos')
                            ->where('pedido_produccion_id', (int) $parcial->pedido_produccion_id)
                            ->where('prenda_pedido_id', (int) $parcial->prenda_pedido_id)
                            ->where('tipo_proceso', $reciboKey)
                            ->value('observacion') ?? '');

                        $tallaColoresParcial = [];
                        if (!empty($cantidadesPorTallaYColor)) {
                            $telaNombre = $prenda['tela_nombre'] ?? $prenda['tela'] ?? null;
                            foreach ($cantidadesPorTallaYColor as $talla => $porColor) {
                                $generoParaTalla = $generosPorTalla[$talla] ?? 'UNISEX';
                                foreach ($porColor as $colorNombre => $cantidad) {
                                    $tallaColoresParcial[] = [
                                        'genero' => $generoParaTalla,
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
                        }

                        // IMPORTANTE: Si no hay colores pero Si hay tallas en el parcial,
                        // construir talla_colores desde las tallas del parcial, NO desde el recibo original.
                        if (empty($tallaColoresParcial) && !empty($tallasParcial)) {
                            $telaNombre = $prenda['tela_nombre'] ?? $prenda['tela'] ?? null;
                            foreach ($tallasParcial as $tallaParcial) {
                                $genero = strtoupper(trim((string) ($tallaParcial['genero'] ?? 'UNISEX')));
                                $talla = strtoupper(trim((string) ($tallaParcial['talla'] ?? '')));
                                $cantidad = (int) ($tallaParcial['cantidad'] ?? 0);
                                if ($talla === '' || $cantidad <= 0) {
                                    continue;
                                }
                                $tallaColoresParcial[] = [
                                    'genero' => $genero,
                                    'talla' => $talla,
                                    'tela_nombre' => $telaNombre,
                                    'color_nombre' => null,
                                    'cantidad' => $cantidad,
                                    'referencia' => null,
                                    'observaciones' => null,
                                    'imagen_ruta' => null,
                                ];
                            }
                        } elseif (empty($tallaColoresParcial)) {
                            // Solo si NO hay tallas en el parcial, usar el fallback del recibo original
                            $tallaColoresParcial = $this->construirTallaColoresDesdePrenda(
                                (int) ($parcial->prenda_pedido_id ?? 0),
                                $tallasParcial,
                                $prenda['tela_nombre'] ?? $prenda['tela'] ?? null
                            );
                        }

                        $reciboParcialData = [
                            'id' => $parcial->id,
                            'consecutivo_actual' => (float) $parcial->consecutivo_parcial,
                            'consecutivo_parcial' => (float) $parcial->consecutivo_parcial,
                            'consecutivo_original' => (float) $parcial->consecutivo_original,
                            'tipo_recibo' => 'PARCIAL',
                            'area' => $parcial->area,
                            'encargado' => $this->obtenerEncargadoDelParcial($parcial, $pedido),
                            'tallas' => $tallasParcial,
                            'tallas_estructura' => $tallasProceso,
                            'talla_colores' => $tallaColoresParcial,
                            'observaciones' => $observacionProceso,
                            'es_parcial' => true,
                            'pedido_parcial_id' => (int) $parcial->id,
                            'fecha_activacion_recibo' => $fechaActivacionRecibo,
                            'created_at' => $fechaActivacionRecibo,
                        ];

                        $prenda['procesos'] = [
                            [
                                'proceso' => $reciboKey,
                                'tipo_proceso' => $reciboKey,
                                'nombre_proceso' => $reciboKey,
                                'es_parcial' => true,
                                'pedido_parcial_id' => (int) $parcial->id,
                                'created_at' => $fechaActivacionRecibo,
                                'tallas' => $tallasProceso,
                                'talla_colores' => $tallaColoresParcial,
                                'ubicaciones' => null,
                                'observaciones' => $observacionProceso,
                            ]
                        ];

                        // Mantener `recibos` como objeto (no array) para que el front lo detecte.
                        $prenda['recibos'] = [
                            $reciboKey => array_merge($reciboParcialData, ['tipo_recibo' => $reciboKey]),
                            'PARCIAL' => $reciboParcialData,
                        ];

                        return $prenda;
                    })
                    ->values()
                    ->toArray();
            }

            $responseData['fecha_activacion_recibo'] = $fechaActivacionRecibo;
            $responseData['fecha_creacion'] = $fechaCreacionParcial;

            // Fallback defensivo: si por alguna incompatibilidad del transformador la prenda queda vaci­a,
            // construir una prenda mi­nima para que el recibo parcial siempre renderice descripcion/tallas.
            if (empty($responseData['prendas']) && $parcial->prenda_pedido_id) {
                $prendaEloquent = PrendaPedido::with(['coloresTelas.color', 'coloresTelas.tela', 'coloresTelas.fotos', 'variantes.tipoManga', 'variantes.tipoBroche'])
                    ->find((int) $parcial->prenda_pedido_id);

                $generoPrenda = strtoupper(trim((string) (
                    $prendaEloquent?->genero
                    ?? $prendaEloquent?->tipo_flujo_tallas
                    ?? 'CABALLERO'
                )));
                if (!in_array($generoPrenda, ['DAMA', 'CABALLERO', 'UNISEX'], true)) {
                    $generoPrenda = 'CABALLERO';
                }

                $coloresTelas = [];
                if ($prendaEloquent && $prendaEloquent->coloresTelas) {
                    foreach ($prendaEloquent->coloresTelas as $ct) {
                        $fotosColorTela = [];
                        foreach (($ct->fotos ?? []) as $foto) {
                            $rutaCompleta = (string) ($foto->ruta_webp ?? $foto->ruta_original ?? $foto->url ?? '');
                            if ($rutaCompleta !== '' && !str_starts_with($rutaCompleta, 'http') && !str_starts_with($rutaCompleta, '/storage/') && !str_starts_with($rutaCompleta, 'storage/')) {
                                $rutaCompleta = '/storage/' . ltrim($rutaCompleta, '/');
                            }

                            if ($rutaCompleta === '') {
                                continue;
                            }

                            $fotosColorTela[] = [
                                'id' => $foto->id,
                                'url' => $rutaCompleta,
                                'ruta_webp' => $rutaCompleta,
                                'ruta_original' => $rutaCompleta,
                                'orden' => (int) ($foto->orden ?? 0),
                            ];
                        }

                        $coloresTelas[] = [
                            'id' => $ct->id,
                            'tela_id' => $ct->tela_id,
                            'tela_nombre' => $ct->tela?->nombre,
                            'color_nombre' => $ct->color?->nombre,
                            'referencia' => $ct->referencia ?? $ct->tela?->referencia,
                            'imagenes' => $fotosColorTela,
                        ];
                    }
                }

                $primerColorTela = $coloresTelas[0] ?? null;
                $variante = $prendaEloquent ? $prendaEloquent->variantes->first() : null;

                // Cargar tallas desde la tabla correcta
                $isReciboPorPartes = ($parcial instanceof \App\Models\ReciboPorPartes);
                $tallasTable = $isReciboPorPartes ? 'recibos_por_partes_tallas' : 'pedidos_parciales_tallas';
                $foreignKey = $isReciboPorPartes ? 'recibo_por_partes_id' : 'pedido_parcial_id';
                $columns = $isReciboPorPartes ? ['talla', 'genero', 'cantidad', 'color_nombre'] : ['genero', 'talla', 'cantidad', 'color_nombre'];

                $tallasRaw = DB::table($tallasTable)
                    ->where($foreignKey, (int) $parcial->id)
                    ->get($columns);

                // Fallback de compatibilidad:
                // Solo si no se encontraron tallas en la tabla principal, intentar buscar en la tabla legacy
                // por contexto (no por ID directo si es ReciboPorPartes para evitar colisiones).
                if ($tallasRaw->isEmpty()) {
                    $tipoReciboParcial = strtoupper(trim((string) ($parcial->tipo_recibo ?? '')));
                    $legacyParcial = DB::table('pedidos_parciales')
                        ->where('pedido_produccion_id', (int) $parcial->pedido_produccion_id)
                        ->where('prenda_pedido_id', (int) $parcial->prenda_pedido_id)
                        ->where('consecutivo_actual', (float) $parcial->consecutivo_parcial)
                        ->when(
                            $tipoReciboParcial !== '',
                            fn($q) => $q->whereRaw('UPPER(tipo_recibo) = ?', [$tipoReciboParcial])
                        )
                        ->whereNull('deleted_at')
                        ->orderByDesc('id')
                        ->first(['id']);

                    if ($legacyParcial?->id) {
                        $tallasRaw = DB::table('pedidos_parciales_tallas')
                            ->where('pedido_parcial_id', (int) $legacyParcial->id)
                            ->get(['genero', 'talla', 'cantidad', 'color_nombre']);
                    }
                }

                \Log::info('[FALLBACK DEBUG 1] Tipo tallas raw: ' . gettype($tallasRaw));
                \Log::info('[FALLBACK DEBUG 2] Es Collection: ' . ($tallasRaw instanceof \Illuminate\Support\Collection ? 'si' : 'no'));
                \Log::info('[FALLBACK DEBUG 3] Contenido raw original: ' . json_encode($tallasRaw));

                // Convertir a array de forma segura (ya es Collection, asi­ que solo normalizamos)
                if ($tallasRaw instanceof \Illuminate\Support\Collection) {
                    \Log::info('[FALLBACK DEBUG 4] Entrando rama Collection');
                    $tallasRaw = $tallasRaw->toArray();
                } elseif (is_object($tallasRaw)) {
                    \Log::info('[FALLBACK DEBUG 4] Entrando rama object');
                    $tallasRaw = json_decode(json_encode($tallasRaw), true) ?: [];
                } elseif (!is_array($tallasRaw)) {
                    \Log::info('[FALLBACK DEBUG 4] Entrando rama no-array');
                    $tallasRaw = [];
                }

                \Log::info('[FALLBACK DEBUG 5] Contenido despues conversion: ' . json_encode($tallasRaw));

                $tallasParcial = collect($tallasRaw)->map(function ($talla) use ($generoPrenda) {
                    \Log::info('[FALLBACK DEBUG 6] Procesando talla: ' . json_encode($talla));

                    // Soportar tanto acceso como objeto como array
                    $genero = is_array($talla) ? ($talla['genero'] ?? null) : ($talla->genero ?? null);
                    $genero = strtoupper(trim((string) ($genero ?: $generoPrenda)));
                    if (!in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'], true)) {
                        $genero = $generoPrenda;
                    }
                    $tallaNombre = is_array($talla) ? ($talla['talla'] ?? null) : ($talla->talla ?? null);
                    $cantidad = is_array($talla) ? ($talla['cantidad'] ?? 0) : ($talla->cantidad ?? 0);
                    $colorNombre = is_array($talla) ? ($talla['color_nombre'] ?? null) : ($talla->color_nombre ?? null);

                    \Log::info('[FALLBACK DEBUG 7] Genero extrai­do: ' . $genero);

                    return [
                        'genero' => $genero,
                        'talla' => $tallaNombre,
                        'cantidad' => $cantidad,
                        'color_nombre' => $colorNombre,
                    ];
                })->filter(fn($r) => !empty($r['talla']) && (int) $r['cantidad'] > 0)->values()->toArray();

                // Distribuir tallas por genero (DAMA, CABALLERO, UNISEX)
                $dama = [];
                $caballero = [];
                $unisex = [];
                $cantidadesPorTallaYColor = [];
                $generosPorTalla = [];

                foreach ($tallasParcial as $registro) {
                    $tallaNombre = strtoupper(trim((string) ($registro['talla'] ?? '')));
                    $cantidad = (int) ($registro['cantidad'] ?? 0);
                    $genero = strtoupper(trim((string) ($registro['genero'] ?? 'UNISEX')));
                    $color = strtoupper(trim((string) ($registro['color_nombre'] ?? '')));

                    if ($tallaNombre === '' || $cantidad <= 0) {
                        continue;
                    }

                    $generosPorTalla[$tallaNombre] = $genero;

                    if ($genero === 'DAMA') {
                        $dama[$tallaNombre] = ($dama[$tallaNombre] ?? 0) + $cantidad;
                    } elseif ($genero === 'CABALLERO') {
                        $caballero[$tallaNombre] = ($caballero[$tallaNombre] ?? 0) + $cantidad;
                    } else {
                        $unisex[$tallaNombre] = ($unisex[$tallaNombre] ?? 0) + $cantidad;
                    }

                    if ($color !== '') {
                        if (!isset($cantidadesPorTallaYColor[$tallaNombre])) {
                            $cantidadesPorTallaYColor[$tallaNombre] = [];
                        }
                        $cantidadesPorTallaYColor[$tallaNombre][$color] = ($cantidadesPorTallaYColor[$tallaNombre][$color] ?? 0) + $cantidad;
                    }
                }

                $tallasProceso = [
                    'dama' => $dama,
                    'caballero' => $caballero,
                    'unisex' => $unisex,
                ];

                $reciboKey = strtoupper(trim((string) ($parcial->tipo_recibo ?: 'COSTURA')));
                $observacionProceso = (string) (DB::table('observaciones_recibos_procesos')
                    ->where('pedido_produccion_id', (int) $parcial->pedido_produccion_id)
                    ->where('prenda_pedido_id', (int) $parcial->prenda_pedido_id)
                    ->where('tipo_proceso', $reciboKey)
                    ->value('observacion') ?? '');

                $tallaColoresParcial = [];
                if (!empty($cantidadesPorTallaYColor)) {
                    $telaNombre = $coloresTelas[0]['tela_nombre'] ?? null;
                    foreach ($cantidadesPorTallaYColor as $talla => $porColor) {
                        $generoParaTalla = $generosPorTalla[$talla] ?? 'UNISEX';
                        foreach ($porColor as $colorNombre => $cantidad) {
                            $tallaColoresParcial[] = [
                                'genero' => $generoParaTalla,
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
                }

                if (empty($tallaColoresParcial)) {
                    $tallaColoresParcial = $this->construirTallaColoresDesdePrenda(
                        (int) ($parcial->prenda_pedido_id ?? 0),
                        $tallasParcial,
                        $coloresTelas[0]['tela_nombre'] ?? null
                    );
                }

                $reciboParcialData = [
                    'id' => $parcial->id,
                    'consecutivo_actual' => (float) $parcial->consecutivo_parcial,
                    'consecutivo_parcial' => (float) $parcial->consecutivo_parcial,
                    'consecutivo_original' => (float) $parcial->consecutivo_original,
                    'tipo_recibo' => 'PARCIAL',
                    'area' => $parcial->area ?? 'Costura',
                    'encargado' => $this->obtenerEncargadoDelParcial($parcial, $pedido),
                    'tallas' => $tallasParcial,
                    'tallas_estructura' => $tallasProceso,
                    'talla_colores' => $tallaColoresParcial,
                    'observaciones' => $observacionProceso,
                    'es_parcial' => true,
                    'pedido_parcial_id' => (int) $parcial->id,
                    'fecha_activacion_recibo' => $fechaActivacionRecibo,
                    'created_at' => $fechaActivacionRecibo,
                ];

                $responseData['prendas'] = [
                    [
                        'id' => (int) $parcial->prenda_pedido_id,
                        'prenda_id' => (int) $parcial->prenda_pedido_id,
                        'prenda_pedido_id' => (int) $parcial->prenda_pedido_id,
                        'nombre' => (string) ($prendaEloquent->nombre_prenda ?? 'PRENDA'),
                        'nombre_prenda' => (string) ($prendaEloquent->nombre_prenda ?? 'PRENDA'),
                        'descripcion' => (string) ($prendaEloquent->descripcion ?? ''),
                        'tela' => $primerColorTela['tela_nombre'] ?? null,
                        'color' => $primerColorTela['color_nombre'] ?? null,
                        'ref' => $primerColorTela['referencia'] ?? null,
                        'colores_telas' => $coloresTelas,
                        'telas_array' => $coloresTelas,
                        'manga' => $variante ? ($variante->tipoManga?->nombre ?? $variante->manga) : null,
                        'broche' => $variante ? ($variante->tipoBroche?->nombre ?? $variante->broche) : null,
                        'tallas' => $tallasParcial,
                        'talla_colores' => $tallaColoresParcial,
                        'procesos' => [
                            [
                                'proceso' => $reciboKey,
                                'tipo_proceso' => $reciboKey,
                                'nombre_proceso' => $reciboKey,
                                'es_parcial' => true,
                                'pedido_parcial_id' => (int) $parcial->id,
                                'created_at' => $fechaActivacionRecibo,
                                'tallas' => [
                                    'dama' => $dama,
                                    'caballero' => $caballero,
                                    'unisex' => $unisex,
                                ],
                                'talla_colores' => $tallaColoresParcial,
                            ]
                        ],
                        'recibos' => [
                            $reciboKey => array_merge($reciboParcialData, ['tipo_recibo' => $reciboKey]),
                            'PARCIAL' => $reciboParcialData,
                        ],
                    ]
                ];

                $responseData['fecha_activacion_recibo'] = $fechaActivacionRecibo;

                \Log::warning('[ObtenerDatosRecibosOperarioUseCase] Prendas vacias en parcial, usando fallback mi­nimo', [
                    'parcial_id' => (int) $parcial->id,
                    'prenda_id' => (int) $parcial->prenda_pedido_id,
                    'total_tallas' => count($tallasParcial),
                ]);
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

        if ($isBodegaOnly) {
            $reciboId = (int) $request->query('recibo_id');
            $reciboBodega = \App\Models\ConsecutivoReciboPedido::with(['prendaBodega.fotos'])->find($reciboId);
            $prendaBodegaId = 0;
            foreach ([
                $reciboBodega->prenda_bodega_id ?? null,
                $reciboBodega->prenda_id ?? null,
                $reciboBodega->prendaBodega?->id ?? null,
            ] as $candidatePrendaBodegaId) {
                $candidatePrendaBodegaId = (int) $candidatePrendaBodegaId;
                if ($candidatePrendaBodegaId > 0) {
                    $prendaBodegaId = $candidatePrendaBodegaId;
                    break;
                }
            }

            $tallasBodegaRows = collect();
            if ($prendaBodegaId > 0) {
                $tallasBodegaRows = DB::table('prenda_tallas_bodega')
                    ->where('prenda_bodega_id', $prendaBodegaId)
                    ->select(['talla', 'genero', 'color', 'cantidad'])
                    ->get();
            }

            $tallasBodega = $tallasBodegaRows
                ->map(function ($row) {
                    $genero = strtoupper(trim((string) ($row->genero ?? 'UNISEX')));
                    if (!in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'], true)) {
                        $genero = 'UNISEX';
                    }

                    $talla = strtoupper(trim((string) ($row->talla ?? '')));
                    if ($talla === '') {
                        $talla = 'SIN_TALLA';
                    }

                    return [
                        'talla' => $talla,
                        'genero' => $genero,
                        'color_nombre' => trim((string) ($row->color ?? '')),
                        'cantidad' => (int) ($row->cantidad ?? 0),
                    ];
                })
                ->filter(fn($t) => $t['cantidad'] > 0)
                ->values()
                ->all();

            if (empty($tallasBodega) && (int) ($reciboBodega->cantidad ?? 0) > 0) {
                $tallasBodega = [[
                    'talla' => 'SIN_TALLA',
                    'genero' => 'UNISEX',
                    'color_nombre' => '',
                    'cantidad' => (int) $reciboBodega->cantidad,
                ]];
            }

            $tallaColoresBodega = collect($tallasBodega)
                ->map(function (array $t) {
                    return [
                        'genero' => $t['genero'],
                        'talla' => $t['talla'],
                        'color_nombre' => $t['color_nombre'],
                        'cantidad' => $t['cantidad'],
                    ];
                })
                ->values()
                ->all();

            $dama = [];
            $caballero = [];
            $unisex = [];
            foreach ($tallasBodega as $talla) {
                $tallaKey = $talla['talla'] !== '' ? $talla['talla'] : 'SIN_TALLA';
                if ($talla['genero'] === 'DAMA') {
                    $dama[$tallaKey] = ($dama[$tallaKey] ?? 0) + (int) $talla['cantidad'];
                } elseif ($talla['genero'] === 'CABALLERO') {
                    $caballero[$tallaKey] = ($caballero[$tallaKey] ?? 0) + (int) $talla['cantidad'];
                } else {
                    $unisex[$tallaKey] = ($unisex[$tallaKey] ?? 0) + (int) $talla['cantidad'];
                }
            }

            $descripcionBodega = trim((string) ($reciboBodega->prendaBodega?->descripcion ?? ''));
            if ($descripcionBodega === '') {
                $descripcionBodega = 'Recibo de Bodega';
            }

            $imagenes = [];
            if ($reciboBodega && $reciboBodega->prendaBodega && $reciboBodega->prendaBodega->fotos) {
                foreach ($reciboBodega->prendaBodega->fotos as $foto) {
                    $rutaCompleta = $foto->url;
                    if (!str_starts_with($rutaCompleta, 'http') && !str_starts_with($rutaCompleta, '/storage/') && !str_starts_with($rutaCompleta, 'storage/')) {
                        $rutaCompleta = '/storage/' . $rutaCompleta;
                    }
                    $imagenes[] = [
                        'id' => $foto->id,
                        'url' => $rutaCompleta,
                        'ruta_webp' => $rutaCompleta,
                    ];
                }
            }

            $responseData = [
                'id' => null,
                'numero_pedido' => $reciboBodega->consecutivo_actual,
                'cliente' => 'SERVICIO',
                'asesor' => 'SISTEMA',
                'forma_pago' => 'N/A',
                'estado' => $reciboBodega->estado ?? 'En Ejecución',
                'fecha_creacion' => $reciboBodega->created_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'descripcion_prendas' => $descripcionBodega,
                'prendas' => [
                    [
                        'id' => $prendaBodegaId,
                        'prenda_id' => $prendaBodegaId,
                        'prenda_bodega_id' => $prendaBodegaId,
                        'nombre' => $reciboBodega->prendaBodega?->nombre ?? 'N/A',
                        'descripcion' => $descripcionBodega,
                        'cantidad' => $reciboBodega->cantidad ?? 0,
                        'tallas' => $tallasBodega,
                        'talla_colores' => $tallaColoresBodega,
                        'imagenes' => $imagenes,
                        'recibos' => (function () use ($reciboBodega) {
                            $recibos = [
                                'COSTURA-BODEGA' => [
                                    'id' => $reciboBodega->id,
                                    'consecutivo_actual' => $reciboBodega->consecutivo_actual,
                                    'consecutivo_recibo_id' => $reciboBodega->id,
                                    'tipo_recibo' => 'COSTURA-BODEGA',
                                    'cantidad' => $reciboBodega->cantidad ?? 0,
                                    'estado' => $reciboBodega->estado ?? 'PENDIENTE',
                                ],
                            ];

                            if (strtoupper(trim((string) ($reciboBodega->tipo_recibo ?? ''))) !== 'COSTURA-BODEGA') {
                                $recibos[$reciboBodega->tipo_recibo] = [
                                    'id' => $reciboBodega->id,
                                    'consecutivo_actual' => $reciboBodega->consecutivo_actual,
                                    'consecutivo_recibo_id' => $reciboBodega->id,
                                    'tipo_recibo' => $reciboBodega->tipo_recibo,
                                    'cantidad' => $reciboBodega->cantidad ?? 0,
                                    'estado' => $reciboBodega->estado ?? 'PENDIENTE',
                                ];
                            }

                            return $recibos;
                        })(),
                        'procesos' => [
                            [
                                'proceso' => $reciboBodega->tipo_recibo,
                                'tipo_proceso' => $reciboBodega->tipo_recibo,
                                'nombre_proceso' => $reciboBodega->tipo_recibo,
                                'consecutivo_recibo_id' => $reciboBodega->id,
                                'estado' => $reciboBodega->estado ?? 'PENDIENTE',
                                'cantidad' => $reciboBodega->cantidad ?? 0,
                                'tallas' => [
                                    'dama' => $dama,
                                    'caballero' => $caballero,
                                    'unisex' => $unisex,
                                ],
                                'talla_colores' => $tallaColoresBodega,
                                'recibos' => [
                                    $reciboBodega->tipo_recibo => [
                                        'id' => $reciboBodega->id,
                                        'consecutivo_actual' => $reciboBodega->consecutivo_actual,
                                        'consecutivo_recibo_id' => $reciboBodega->id,
                                        'tipo_recibo' => $reciboBodega->tipo_recibo,
                                        'cantidad' => $reciboBodega->cantidad ?? 0,
                                        'estado' => $reciboBodega->estado ?? 'PENDIENTE'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        } else {
            \Log::info('[ObtenerDatosRecibosOperarioUseCase] Llamando ObtenerPedidoUseCase');
            $datosPedido = $this->obtenerPedidoUseCase->ejecutar($pedidoIdParaQuery, false);
            \Log::info('[ObtenerDatosRecibosOperarioUseCase] Datos obtenidos del UseCase');

            $responseData = $datosPedido->toArray();
        }

        // Si se proporcion recibo_id, filtrar las prendas para mostrar solo la del recibo especi­fico
        $prendaIdParam = $request->query('prenda_id');
        if ($reciboId && $prendaIdParam) {
            $prendaIdParam = (int) $prendaIdParam;

            \Log::info('[ObtenerDatosRecibosOperarioUseCase] Filtrando prendas por recibo_id', [
                'recibo_id' => $reciboId,
                'prenda_id' => $prendaIdParam,
                'prendas_antes' => count($responseData['prendas'] ?? []),
            ]);

            // Filtrar prendas para mostrar solo la del recibo especi­fico
            if (isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                $responseData['prendas'] = array_values(array_filter(
                    $responseData['prendas'],
                    fn($prenda) => (int) ($prenda['id'] ?? $prenda['prenda_id'] ?? 0) === $prendaIdParam
                ));
            }

            \Log::info('[ObtenerDatosRecibosOperarioUseCase] Prendas despues del filtro', [
                'prendas_despues' => count($responseData['prendas'] ?? []),
            ]);
        }

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

    /**
     * Reconstruye talla_colores del parcial usando la distribucion de colores de la prenda base.
     *
     * @param int $prendaPedidoId
     * @param array<int,array{genero:string,talla:string,cantidad:int,color_nombre:mixed}> $tallasParcial
     * @param string|null $telaNombre
     * @return array<int,array<string,mixed>>
     */
    private function construirTallaColoresDesdePrenda(int $prendaPedidoId, array $tallasParcial, ?string $telaNombre = null): array
    {
        if ($prendaPedidoId <= 0 || empty($tallasParcial)) {
            return [];
        }

        $rows = DB::table('prenda_pedido_talla_colores as pptc')
            ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
            ->where('ppt.prenda_pedido_id', $prendaPedidoId)
            ->select(['ppt.genero', 'ppt.talla', 'pptc.color_nombre', 'pptc.cantidad'])
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $fuente = [];
        foreach ($rows as $row) {
            $genero = strtoupper(trim((string) ($row->genero ?? '')));
            $talla = strtoupper(trim((string) ($row->talla ?? '')));
            $color = trim((string) ($row->color_nombre ?? ''));
            $cantidad = (int) ($row->cantidad ?? 0);
            if ($genero === '' || $talla === '' || $color === '' || $cantidad <= 0) {
                continue;
            }
            $key = $genero . '|' . $talla;
            $fuente[$key][] = ['color' => $color, 'cantidad' => $cantidad];
        }

        if (empty($fuente)) {
            return [];
        }

        $resultado = [];
        foreach ($tallasParcial as $tallaParcial) {
            $genero = strtoupper(trim((string) ($tallaParcial['genero'] ?? 'UNISEX')));
            $talla = strtoupper(trim((string) ($tallaParcial['talla'] ?? '')));
            $cantidadObjetivo = (int) ($tallaParcial['cantidad'] ?? 0);
            if ($talla === '' || $cantidadObjetivo <= 0) {
                continue;
            }

            $key = $genero . '|' . $talla;
            $coloresFuente = $fuente[$key] ?? [];
            if (empty($coloresFuente)) {
                continue;
            }

            $sumaFuente = array_sum(array_map(static fn($c) => (int) ($c['cantidad'] ?? 0), $coloresFuente));
            if ($sumaFuente <= 0) {
                continue;
            }

            $distribucion = [];
            $asignado = 0;
            $residuos = [];
            foreach ($coloresFuente as $idx => $cf) {
                $exacto = ($cantidadObjetivo * (int) $cf['cantidad']) / $sumaFuente;
                $base = (int) floor($exacto);
                $distribucion[$idx] = $base;
                $asignado += $base;
                $residuos[$idx] = $exacto - $base;
            }

            $faltante = $cantidadObjetivo - $asignado;
            if ($faltante > 0) {
                arsort($residuos);
                foreach (array_keys($residuos) as $idx) {
                    if ($faltante <= 0) {
                        break;
                    }
                    $distribucion[$idx] = ($distribucion[$idx] ?? 0) + 1;
                    $faltante--;
                }
            }

            foreach ($coloresFuente as $idx => $cf) {
                $cantidadColor = (int) ($distribucion[$idx] ?? 0);
                if ($cantidadColor <= 0) {
                    continue;
                }
                $resultado[] = [
                    'genero' => $genero,
                    'talla' => $talla,
                    'tela_nombre' => $telaNombre,
                    'color_nombre' => (string) $cf['color'],
                    'cantidad' => $cantidadColor,
                    'referencia' => null,
                    'observaciones' => null,
                    'imagen_ruta' => null,
                ];
            }
        }

        return $resultado;
    }

    /**
     * Obtener el encargado del parcial desde procesos_prenda
     * Busca primero en procesos_prenda, luego en el campo encargado del parcial
     */
    private function obtenerEncargadoDelParcial($parcial, $pedido): ?string
    {
        try {
            // Obtener el numero de recibo del parcial
            $numeroRecibo = $parcial->consecutivo_actual ?? $parcial->consecutivo_parcial;

            if (!$numeroRecibo || !$pedido) {
                return $parcial->encargado ?? null;
            }

            // Buscar proceso en procesos_prenda con numero_recibo = consecutivo del parcial
            $proceso = DB::table('procesos_prenda')
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
                ->where('numero_recibo', $numeroRecibo)
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                ->whereNull('numero_recibo_parcial')  // Asegurar que es un proceso de anexo, no de parcial
                ->whereNull('deleted_at')
                ->orderByDesc('fecha_de_asignacion_encargado')
                ->orderByDesc('created_at')
                ->value('encargado');

            // Si encontro encargado en procesos_prenda, devolverlo
            if ($proceso) {
                return $proceso;
            }

            // Fallback: usar el campo encargado del parcial
            return $parcial->encargado ?? null;
        } catch (\Exception $e) {
            \Log::warning('[ObtenerDatosRecibosOperarioUseCase] Error obteniendo encargado del parcial', [
                'parcial_id' => $parcial->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return $parcial->encargado ?? null;
        }
    }
}
