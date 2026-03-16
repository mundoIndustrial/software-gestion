<?php

namespace App\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Application\Pedidos\UseCases\ListarPedidosPorClienteUseCase;
use App\Models\PedidoAnchoGeneral;
use App\Models\PedidoMetrajeColor;

/**
 * PedidoQueryController
 *
 * Lado lectura (CQRS read side) para pedidos.
 * Maneja show, detalle completo para recibos, datos de edición y catálogos de ancho/metraje.
 */
class PedidoQueryController extends Controller
{
    public function __construct(
        private ObtenerPedidoUseCase $obtenerPedidoUseCase,
        private ListarPedidosPorClienteUseCase $listarPedidosPorClienteUseCase,
    ) {}

    /**
     * GET /api/pedidos/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $response = $this->obtenerPedidoUseCase->ejecutar($id);
            $datos = $response->toArray();

            // Enriquecer procesos con tallas_transformadas y observaciones_por_talla
            if (isset($datos['prendas']) && is_array($datos['prendas'])) {
                foreach ($datos['prendas'] as &$prenda) {
                    if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                        foreach ($prenda['procesos'] as &$proceso) {
                            if (isset($proceso['id'])) {
                                if (isset($proceso['ubicaciones']) && is_string($proceso['ubicaciones'])) {
                                    $decodedUb = json_decode($proceso['ubicaciones'], true);
                                    if (is_array($decodedUb)) {
                                        $proceso['ubicaciones_array'] = $decodedUb;
                                    }
                                }

                                $tallas = \DB::table('pedidos_procesos_prenda_tallas')
                                    ->where('proceso_prenda_detalle_id', $proceso['id'])
                                    ->get();

                                \Log::info('[PedidoQueryController-PROCESOS-TALLAS] Tallas obtenidas', [
                                    'proceso_id' => $proceso['id'],
                                    'tallas_count' => $tallas->count(),
                                    'tallas' => $tallas->toArray()
                                ]);

                                $talasTransformadas = [
                                    'dama' => [],
                                    'caballero' => [],
                                    'unisex' => []
                                ];

                                $tallasDetalle = [];

                                foreach ($tallas as $talla) {
                                    $genero = strtolower($talla->genero ?? 'caballero');
                                    if ($genero === 'dama') $genero = 'dama';
                                    elseif ($genero === 'caballero') $genero = 'caballero';
                                    else $genero = 'unisex';

                                    $tallasDetalle[] = [
                                        'genero'         => strtoupper((string) ($talla->genero ?? '')),
                                        'talla'          => $talla->talla,
                                        'cantidad'       => (int) ($talla->cantidad ?? 0),
                                        'es_sobremedida' => (int) ($talla->es_sobremedida ?? 0),
                                    ];

                                    $colores = \DB::table('pedidos_procesos_prenda_talla_colores')
                                        ->where('pedidos_procesos_prenda_talla_id', $talla->id)
                                        ->get();

                                    \Log::info('[PedidoQueryController-TALLAS-COLORES] DEBUG', [
                                        'pedido_id' => $id,
                                        'proceso_id' => $proceso['id'],
                                        'talla_id' => $talla->id,
                                        'talla_valor' => $talla->talla,
                                        'genero' => $genero,
                                        'cantidad_talla' => $talla->cantidad,
                                        'colores_encontrados' => $colores->count(),
                                        'colores_data' => $colores->map(fn($c) => ['color' => $c->color_nombre, 'cant' => $c->cantidad])->toArray()
                                    ]);

                                    if ($colores->count() > 0) {
                                        $talasTransformadas[$genero][$talla->talla] = $colores->map(fn($color) => [
                                            'color' => $color->color_nombre,
                                            'cantidad' => $color->cantidad
                                        ])->toArray();
                                    } else {
                                        $talasTransformadas[$genero][$talla->talla] = $talla->cantidad;
                                    }
                                }

                                $proceso['tallas'] = $talasTransformadas;
                                $proceso['tallas_detalle'] = $tallasDetalle;

                                $modoTallas = $proceso['modo_tallas'] ?? null;
                                if ($modoTallas === 'general') {
                                    $obsPorTalla = [
                                        'dama' => [],
                                        'caballero' => [],
                                        'unisex' => [],
                                    ];

                                    foreach ($tallas as $talla) {
                                        $obs = trim((string)($talla->observaciones ?? ''));
                                        if ($obs === '') {
                                            continue;
                                        }

                                        $genero = strtolower((string)($talla->genero ?? ''));
                                        if ($genero !== 'dama' && $genero !== 'caballero' && $genero !== 'unisex') {
                                            $genero = 'caballero';
                                        }

                                        $tallaKey = $talla->talla !== null ? (string)$talla->talla : 'SOBREMEDIDA';
                                        $obsPorTalla[$genero][$tallaKey] = $obs;
                                    }

                                    $proceso['observaciones_por_talla'] = $obsPorTalla;
                                }
                            }
                        }
                        unset($proceso);
                    }
                }
                unset($prenda);
            }

            if (!isset($datos['fecha_creacion'])) {
                $pedido = \App\Models\PedidoProduccion::find($id);
                if ($pedido) {
                    $fechaCreacion = $pedido->fecha_de_creacion_de_orden ?? $pedido->created_at;
                    $datos['fecha_creacion'] = $fechaCreacion
                        ? (is_string($fechaCreacion) ? $fechaCreacion : $fechaCreacion->format('d/m/Y'))
                        : date('d/m/Y');
                }
            }

            // Cargar tallas con colores y estado de entrega de cada prenda
            if (isset($datos['prendas']) && is_array($datos['prendas'])) {
                foreach ($datos['prendas'] as &$prenda) {
                    if (isset($prenda['id'])) {
                        try {
                            $tallasPorGenero = [
                                'DAMA' => [],
                                'CABALLERO' => [],
                                'UNISEX' => []
                            ];

                            $tallasColores = \DB::table('prenda_pedido_talla_colores as pptc')
                                ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
                                ->where('ppt.prenda_pedido_id', $prenda['id'])
                                ->select(['ppt.genero', 'ppt.talla', 'pptc.color_nombre', 'pptc.cantidad'])
                                ->get();

                            if ($tallasColores->count() > 0) {
                                foreach ($tallasColores as $tallaColor) {
                                    $genero = strtoupper((string)($tallaColor->genero ?? ''));
                                    if (!in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'], true)) {
                                        $genero = 'CABALLERO';
                                    }
                                    $talla = (string)($tallaColor->talla ?? '');
                                    $color = (string)($tallaColor->color_nombre ?? '');
                                    $cantidad = (int)($tallaColor->cantidad ?? 0);

                                    if ($talla === '' || $cantidad <= 0) {
                                        continue;
                                    }

                                    if (!isset($tallasPorGenero[$genero][$talla])) {
                                        $tallasPorGenero[$genero][$talla] = [];
                                    }

                                    $tallasPorGenero[$genero][$talla][] = [
                                        'cantidad' => $cantidad,
                                        'color' => $color !== '' ? $color : null,
                                    ];
                                }

                                $prenda['tallas'] = $tallasPorGenero;
                            }
                        } catch (\Exception $e) {
                            \Log::warning('[PedidoQueryController::show] Error cargando tallas con color para prenda', [
                                'pedido_id' => $id,
                                'prenda_id' => $prenda['id'],
                                'error' => $e->getMessage(),
                            ]);
                        }

                        $entrega = \App\Models\PrendaEntrega::where('prenda_pedido_id', $prenda['id'])->first();
                        $prenda['entrega'] = $entrega ? [
                            'entregado' => $entrega->entregado,
                            'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d H:i:s'),
                            'usuario' => $entrega->usuario?->name,
                        ] : null;

                        // Agregar recibos parciales (ANEXOS) a cada prenda
                        try {
                            $recibosParciales = \DB::table('pedidos_parciales')
                                ->where('pedido_produccion_id', $id)
                                ->where('prenda_pedido_id', $prenda['id'])
                                ->orderBy('tipo_recibo', 'asc')
                                ->orderBy('id', 'asc')
                                ->get();

                            if ($recibosParciales->count() > 0) {
                                $anexosPorTipo = [];
                                $procesosAdicionales = [];

                                foreach ($recibosParciales as $reciboParcial) {
                                    $tipoRecibo = $reciboParcial->tipo_recibo;

                                    if (!isset($anexosPorTipo[$tipoRecibo])) {
                                        $anexosPorTipo[$tipoRecibo] = 0;
                                    }
                                    $anexosPorTipo[$tipoRecibo]++;

                                    $numeroReciboAnexo = $reciboParcial->consecutivo_actual ?? $reciboParcial->numero_recibo ?? null;

                                    $tallas = \DB::table('pedidos_parciales_tallas')
                                        ->where('pedido_parcial_id', $reciboParcial->id)
                                        ->get();

                                    $tallasList = [];
                                    $talasTransformadas = [
                                        'dama' => [],
                                        'caballero' => [],
                                        'unisex' => []
                                    ];

                                    foreach ($tallas as $talla) {
                                        $tallasList[] = [
                                            'talla' => $talla->talla,
                                            'cantidad' => $talla->cantidad,
                                            'genero' => $talla->genero ?? 'General'
                                        ];

                                        $genero = strtolower($talla->genero ?? 'caballero');
                                        if ($genero === 'dama') $genero = 'dama';
                                        elseif ($genero === 'caballero') $genero = 'caballero';
                                        else $genero = 'unisex';

                                        $talasTransformadas[$genero][$talla->talla] = $talla->cantidad;
                                    }

                                    $procesosAdicionales[] = [
                                        'tipo_proceso' => $tipoRecibo,
                                        'nombre_proceso' => $tipoRecibo . ' ANEXO ' . $anexosPorTipo[$tipoRecibo],
                                        'estado' => $reciboParcial->estado ?? 'PENDIENTE',
                                        'numero_recibo' => $numeroReciboAnexo,
                                        'es_parcial' => true,
                                        'numero_anexo' => $anexosPorTipo[$tipoRecibo],
                                        'pedido_parcial_id' => $reciboParcial->id,
                                        'tallas' => $tallasList,
                                        'tallas_transformadas' => $talasTransformadas,
                                        'created_at' => $reciboParcial->created_at,
                                    ];
                                }

                                if (!isset($prenda['procesos'])) {
                                    $prenda['procesos'] = [];
                                }
                                $prenda['procesos'] = array_merge($prenda['procesos'], $procesosAdicionales);
                            }
                        } catch (\Exception $e) {
                            \Log::error('[PedidoQueryController::show] Error cargando recibos parciales', [
                                'prenda_id' => $prenda['id'],
                                'pedido_id' => $id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
                unset($prenda);
            }

            // EPPs transformados con imágenes
            $eppsList = [];
            try {
                $pedido = \App\Models\PedidoProduccion::find($id);
                \Log::info('[PedidoQueryController::show] Buscando EPPs', [
                    'pedido_id' => $id,
                    'tiene_epps' => $pedido && $pedido->epps ? $pedido->epps->count() : 0,
                ]);

                if ($pedido && $pedido->epps) {
                    foreach ($pedido->epps as $pedidoEpp) {
                        $epp = $pedidoEpp->epp;

                        if (!$epp) {
                            \Log::warning('[PedidoQueryController::show] EPP sin relación válida', [
                                'pedido_epp_id' => $pedidoEpp->id,
                            ]);
                            continue;
                        }

                        $imagenes = $this->obtenerImagenesEpp($pedidoEpp->id);

                        $eppsList[] = [
                            'id' => $pedidoEpp->id,
                            'epp_id' => $pedidoEpp->epp_id,
                            'nombre' => $epp->nombre_completo ?? $epp->nombre ?? '',
                            'nombre_completo' => $epp->nombre_completo ?? $epp->nombre ?? '',
                            'cantidad' => $pedidoEpp->cantidad ?? 0,
                            'observaciones' => $pedidoEpp->observaciones ?? '',
                            'imagen' => !empty($imagenes) ? $imagenes[0] : null,
                            'imagenes' => $imagenes,
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::error('[PedidoQueryController::show] Error procesando EPPs', [
                    'pedido_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            \Log::info('[PedidoQueryController::show] EPPs transformados', [
                'pedido_id' => $id,
                'epps_count' => count($eppsList),
                'primer_epp_imagenes' => !empty($eppsList) ? count($eppsList[0]['imagenes']) : 0,
            ]);

            $datos['epps_transformados'] = $eppsList;

            return response()->json([
                'success' => true,
                'data' => $datos
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
     * Datos completos del pedido para recibos.
     * Con filtrado especial para bodeguero (solo COSTURA-BODEGA) e insumos (de_bodega=false).
     */
    public function obtenerDetalleCompleto(int $id, bool $filtrarProcesosPendientes = false): JsonResponse
    {
        try {
            $pedido = \App\Models\PedidoProduccion::find($id);

            if (!$pedido) {
                $pedido = \App\Models\PedidoProduccion::where('numero_pedido', $id)->first();
            }

            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => "Pedido {$id} no encontrado"
                ], 404);
            }

            $esBodyguero = auth()->check() && auth()->user()->hasRole('bodeguero');

            if ($esBodyguero) {
                $estadoPedido = strtolower($pedido->estado ?? '');
                \Log::info('[PedidoQueryController] Estado del pedido para bodeguero', [
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado_raw' => $pedido->estado,
                    'estado_lower' => $estadoPedido,
                    'es_pendiente_cartera' => $estadoPedido === 'pendiente_cartera',
                    'es_rechazado_cartera' => $estadoPedido === 'rechazado_cartera'
                ]);

                if ($estadoPedido === 'pendiente_cartera' || $estadoPedido === 'rechazado_cartera') {
                    \Log::warning('[PedidoQueryController] 🔐 Bodeguero bloqueado - Pedido en estado: ' . $pedido->estado, [
                        'pedido_id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'usuario_id' => auth()->id(),
                        'estado' => $pedido->estado
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'No puedes ver recibos de pedidos en estado ' . $pedido->estado
                    ], 403);
                }
            }

            $response = $this->obtenerPedidoUseCase->ejecutar($pedido->id, $filtrarProcesosPendientes);
            $responseData = $response->toArray();

            // Enriquecer procesos con observaciones_por_talla y ubicaciones_array
            if (isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                foreach ($responseData['prendas'] as &$prendaProc) {
                    if (!isset($prendaProc['procesos']) || !is_array($prendaProc['procesos'])) {
                        continue;
                    }

                    foreach ($prendaProc['procesos'] as &$procesoProc) {
                        if (!isset($procesoProc['id'])) {
                            continue;
                        }

                        if (isset($procesoProc['ubicaciones']) && is_string($procesoProc['ubicaciones'])) {
                            $decodedUb = json_decode($procesoProc['ubicaciones'], true);
                            if (is_array($decodedUb)) {
                                $procesoProc['ubicaciones_array'] = $decodedUb;
                            }
                        }

                        // tallas_detalle con es_sobremedida
                        try {
                            $tallasProceso = \DB::table('pedidos_procesos_prenda_tallas')
                                ->where('proceso_prenda_detalle_id', $procesoProc['id'])
                                ->get(['genero', 'talla', 'cantidad', 'es_sobremedida', 'ubicaciones', 'observaciones']);

                            if ($tallasProceso->count() > 0) {
                                $procesoProc['tallas_detalle'] = $tallasProceso->map(function ($t) {
                                    return [
                                        'genero'         => strtoupper((string) ($t->genero ?? '')),
                                        'talla'          => $t->talla,
                                        'cantidad'       => (int) ($t->cantidad ?? 0),
                                        'es_sobremedida' => (int) ($t->es_sobremedida ?? 0),
                                        'ubicaciones'    => $t->ubicaciones,
                                        'observaciones'  => $t->observaciones,
                                    ];
                                })->toArray();
                            }
                        } catch (\Exception $e) {
                            // silencioso
                        }

                        $modoTallas = $procesoProc['modo_tallas'] ?? null;
                        if ($modoTallas === 'general') {
                            $tallasObs = \DB::table('pedidos_procesos_prenda_tallas')
                                ->where('proceso_prenda_detalle_id', $procesoProc['id'])
                                ->whereNotNull('observaciones')
                                ->where('observaciones', '!=', '')
                                ->get(['genero', 'talla', 'observaciones']);

                            $obsPorTalla = [
                                'dama' => [],
                                'caballero' => [],
                                'unisex' => [],
                            ];

                            foreach ($tallasObs as $row) {
                                $obs = trim((string)($row->observaciones ?? ''));
                                if ($obs === '') {
                                    continue;
                                }

                                $genero = strtolower((string)($row->genero ?? ''));
                                if ($genero !== 'dama' && $genero !== 'caballero' && $genero !== 'unisex') {
                                    $genero = 'caballero';
                                }

                                $tallaKey = $row->talla !== null ? (string)$row->talla : 'SOBREMEDIDA';
                                $obsPorTalla[$genero][$tallaKey] = $obs;
                            }

                            $procesoProc['observaciones_por_talla'] = $obsPorTalla;
                        }
                    }
                    unset($procesoProc);
                }
                unset($prendaProc);
            }

            // FILTRO BODEGUERO: solo procesos 'costura-bodega'
            if ($esBodyguero && isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                \Log::info('[PedidoQueryController] 🔐 FILTRO BODEGUERO: Filtrando procesos - Solo COSTURA-BODEGA', [
                    'pedido_id' => $pedido->id,
                    'usuario_id' => auth()->id(),
                    'total_prendas' => count($responseData['prendas'])
                ]);

                foreach ($responseData['prendas'] as $prendaIdx => $prenda) {
                    if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                        \Log::debug('[PedidoQueryController] Estructura de procesos para prenda', [
                            'prenda_idx' => $prendaIdx,
                            'prenda_nombre' => $prenda['nombre'] ?? 'N/A',
                            'total_procesos' => count($prenda['procesos']),
                            'primer_proceso' => isset($prenda['procesos'][0]) ? $prenda['procesos'][0] : 'vacío',
                            'claves_primer_proceso' => isset($prenda['procesos'][0]) ? array_keys($prenda['procesos'][0]) : []
                        ]);
                    }
                }

                foreach ($responseData['prendas'] as &$prenda) {
                    if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                        $procesosFiltrados = array_filter($prenda['procesos'], function ($proceso) {
                            $tipoProceso = $proceso['tipo_proceso'] ?? $proceso['nombre_proceso'] ?? $proceso['nombre'] ?? $proceso['proceso'] ?? '';
                            $tipoLower = strtolower(trim($tipoProceso));

                            \Log::debug('[PedidoQueryController] Verificando proceso para bodeguero', [
                                'tipo_proceso' => $tipoProceso,
                                'tipo_lower' => $tipoLower,
                                'proceso_keys' => array_keys($proceso),
                                'es_costura_bodega' => $tipoLower === 'costura-bodega' || $tipoLower === 'costurabodega'
                            ]);

                            return $tipoLower === 'costura-bodega' || $tipoLower === 'costurabodega';
                        });

                        $prenda['procesos'] = array_values($procesosFiltrados);

                        \Log::info('[PedidoQueryController] 🔐 Procesos filtrados para bodeguero', [
                            'prenda_id' => $prenda['id'] ?? 'N/A',
                            'procesos_antes' => count($prenda['procesos'] ?? []),
                            'procesos_despues' => count($procesosFiltrados)
                        ]);
                    }
                }
                unset($prenda); // CRITICAL: romper referencia

                $tieneProcesoCosturaBodega = false;
                foreach ($responseData['prendas'] as $prenda) {
                    if (isset($prenda['procesos']) && is_array($prenda['procesos']) && !empty($prenda['procesos'])) {
                        $tieneProcesoCosturaBodega = true;
                        break;
                    }
                }

                if (!$tieneProcesoCosturaBodega) {
                    \Log::warning('[PedidoQueryController] 🔐 Bodeguero intenta ver pedido sin procesos costura-bodega', [
                        'pedido_id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'usuario_id' => auth()->id()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Este pedido no tiene procesos de COSTURA-BODEGA disponibles'
                    ], 403);
                }
            }

            // FILTRO INSUMOS: solo prendas con de_bodega = false
            $esInsumos = auth()->check() && auth()->user()->hasRole('insumos');
            $referer = request()->headers->get('referer', '');
            $vieneDeRegistros = str_contains($referer, '/registros/');
            $vieneDeInsumos = str_contains($referer, '/insumos/materiales');
            $aplicarFiltroInsumos = $esInsumos && !$vieneDeRegistros && !$vieneDeInsumos;

            if ($aplicarFiltroInsumos && isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                \Log::info('[PedidoQueryController] FILTRO INSUMOS: Mostrando solo prendas con de_bodega = false', [
                    'pedido_id' => $pedido->id,
                    'usuario_id' => auth()->id(),
                    'total_prendas_antes' => count($responseData['prendas']),
                    'referer' => $referer,
                    'viene_de_registros' => $vieneDeRegistros,
                    'viene_de_insumos' => $vieneDeInsumos,
                    'aplicar_filtro' => $aplicarFiltroInsumos
                ]);

                $prendasFiltradas = array_filter($responseData['prendas'], function ($prenda) {
                    $deBodega = $prenda['de_bodega'] ?? false;
                    if (is_string($deBodega)) {
                        $deBodega = (bool)intval($deBodega);
                    }
                    return !$deBodega;
                });

                $responseData['prendas'] = array_values($prendasFiltradas);

                \Log::info('[PedidoQueryController] Prendas filtradas para insumos', [
                    'pedido_id' => $pedido->id,
                    'total_prendas_antes' => count($responseData['prendas'] ?? []) + count($prendasFiltradas),
                    'total_prendas_despues' => count($prendasFiltradas),
                    'prendas_filtradas' => array_map(fn($p) => [
                        'nombre' => $p['nombre'] ?? 'N/A',
                        'de_bodega' => $p['de_bodega'] ?? 'N/A'
                    ], $prendasFiltradas)
                ]);

                if (empty($prendasFiltradas)) {
                    \Log::warning('[PedidoQueryController] Insumos intenta ver pedido sin prendas de_bodega=false', [
                        'pedido_id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'usuario_id' => auth()->id()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Este pedido no tiene prendas disponibles para insumos (todas son de bodega)'
                    ], 403);
                }
            }

            // Agregar ancho/metraje y consecutivos a cada prenda
            if (isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                \Log::info('[PedidoQueryController] Agregando ancho/metraje y consecutivos a prendas', [
                    'pedido_id' => $pedido->id,
                    'total_prendas' => count($responseData['prendas'])
                ]);

                foreach ($responseData['prendas'] as $index => &$prenda) {
                    $prendaId = $prenda['id'] ?? $prenda['prenda_pedido_id'] ?? null;

                    \Log::info('[PedidoQueryController] Procesando prenda para datos adicionales', [
                        'index' => $index,
                        'prenda_id' => $prendaId,
                        'prenda_nombre' => $prenda['nombre'] ?? 'N/A'
                    ]);

                    if ($prendaId) {
                        $anchoGeneral = PedidoAnchoGeneral::where('pedido_produccion_id', $pedido->id)
                            ->where('prenda_pedido_id', $prendaId)
                            ->first();

                        $metrajesPorColor = PedidoMetrajeColor::where('pedido_produccion_id', $pedido->id)
                            ->where('prenda_pedido_id', $prendaId)
                            ->get();

                        if ($anchoGeneral || $metrajesPorColor->isNotEmpty()) {
                            $ancho_metraje_data = [
                                'prenda_id' => $prendaId,
                                'ancho' => $anchoGeneral ? $anchoGeneral->ancho : null,
                                'metraje' => $anchoGeneral ? $anchoGeneral->metraje : null,
                                'tipo_modo' => $anchoGeneral ? $anchoGeneral->tipo_modo : null,
                                'contenido_mano' => $anchoGeneral ? $anchoGeneral->contenido_mano : null,
                                'metrajes_por_color' => []
                            ];

                            foreach ($metrajesPorColor as $metraje) {
                                $ancho_metraje_data['metrajes_por_color'][] = [
                                    'color' => $metraje->color,
                                    'metraje' => $metraje->metraje
                                ];
                            }

                            $prenda['ancho_metraje'] = $ancho_metraje_data;

                            \Log::info('[PedidoQueryController] Ancho/Metraje encontrado para prenda', [
                                'pedido_id' => $pedido->id,
                                'prenda_id' => $prendaId,
                                'prenda_nombre' => $prenda['nombre'] ?? 'N/A',
                                'ancho' => $ancho_metraje_data['ancho'],
                                'metrajes_count' => count($ancho_metraje_data['metrajes_por_color'])
                            ]);
                        } else {
                            $prenda['ancho_metraje'] = null;

                            \Log::info('[PedidoQueryController] No hay ancho/metraje para prenda', [
                                'pedido_id' => $pedido->id,
                                'prenda_id' => $prendaId,
                                'prenda_nombre' => $prenda['nombre'] ?? 'N/A'
                            ]);
                        }

                        $consecutivos = $this->obtenerConsecutivosPrenda($pedido->id, $prendaId);
                        $prenda['recibos'] = $consecutivos;
                        $prenda['consecutivos'] = $consecutivos;

                        \Log::info('[PedidoQueryController] Consecutivos agregados a prenda', [
                            'pedido_id' => $pedido->id,
                            'prenda_id' => $prendaId,
                            'consecutivos_es_null' => is_null($consecutivos)
                        ]);
                    } else {
                        $prenda['ancho_metraje'] = null;
                        $prenda['recibos'] = null;
                        \Log::warning('[PedidoQueryController] Prenda sin ID válido', [
                            'index' => $index,
                            'prenda' => $prenda
                        ]);
                    }
                }
                unset($prenda); // CRITICAL: romper referencia
            }

            // Ancho/metraje general por compatibilidad
            $anchoMetrajeGeneral = null;
            try {
                $pedido = \App\Models\PedidoProduccion::find($id);
                if ($pedido) {
                    $anchoMetrajeGeneral = [
                        'ancho' => $pedido->ancho ?? null,
                        'metraje' => $pedido->metraje ?? null,
                        'fecha_actualizacion' => $pedido->updated_at ?? null
                    ];
                }
            } catch (\Exception $e) {
                \Log::debug('[PedidoQueryController] Error obteniendo ancho/metraje general', ['error' => $e->getMessage()]);
            }

            $responseData['ancho_metraje'] = $anchoMetrajeGeneral;

            // Loguear estructura final de tallas
            if (isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                foreach ($responseData['prendas'] as $prendaIndex => $prenda) {
                    if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                        foreach ($prenda['procesos'] as $procIndex => $proceso) {
                            if (isset($proceso['tallas'])) {
                                \Log::info('[PedidoQueryController] ESTRUCTURA FINAL DE TALLAS ANTES DE ENVIAR', [
                                    'prenda_index' => $prendaIndex,
                                    'proceso_index' => $procIndex,
                                    'proceso_id' => $proceso['id'] ?? 'N/A',
                                    'tallas_keys' => array_keys($proceso['tallas']),
                                    'tallas_data' => $proceso['tallas'],
                                    'caballero_data' => $proceso['tallas']['caballero'] ?? 'NO ENCONTRADO',
                                    'caballero_type' => gettype($proceso['tallas']['caballero'] ?? null),
                                    'caballero_is_array' => is_array($proceso['tallas']['caballero'] ?? null)
                                ]);
                            }
                        }
                    }
                }
            }

            // Datos adicionales del pedido
            if ($pedido) {
                if (!isset($responseData['fecha_estimada_de_entrega'])) {
                    $responseData['fecha_estimada_de_entrega'] = $pedido->fecha_estimada_de_entrega;
                }

                if (!isset($responseData['area'])) {
                    $responseData['area'] = $pedido->area;
                }

                if (!isset($responseData['dia_de_entrega'])) {
                    $responseData['dia_de_entrega'] = $pedido->dia_de_entrega;
                }

                \Log::info('[PedidoQueryController] Datos del pedido agregados', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega,
                    'area' => $pedido->area,
                    'dia_de_entrega' => $pedido->dia_de_entrega
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $responseData
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
     */
    public function obtenerDatosEdicion(int $id): JsonResponse
    {
        try {
            $pedido = \App\Models\PedidoProduccion::with([
                'prendas.variantes',
                'prendas.coloresTelas.fotos',
                'prendas.procesos.tipoProceso',
                'prendas.fotos',
                'prendas.telaFotos',
                'epps.epp',
                'asesor:id,name',
                'cliente:id,nombre'
            ])->findOrFail($id);

            // Transformar variantes para incluir nombres de tipos
            if ($pedido->prendas) {
                foreach ($pedido->prendas as $prenda) {
                    if ($prenda->variantes) {
                        foreach ($prenda->variantes as $variante) {
                            if ($variante->tipo_manga_id) {
                                try {
                                    $manga = \App\Models\TipoManga::find($variante->tipo_manga_id);
                                    $variante->manga_nombre = $manga ? $manga->nombre : null;
                                } catch (\Exception $e) {
                                    \Log::debug('[PedidoQueryController] Error obtener manga', ['error' => $e->getMessage()]);
                                }
                            }

                            if ($variante->tipo_broche_boton_id) {
                                try {
                                    $broche = \App\Models\TipoBrocheBoton::find($variante->tipo_broche_boton_id);
                                    $variante->broche_nombre = $broche ? $broche->nombre : null;
                                } catch (\Exception $e) {
                                    \Log::debug('[PedidoQueryController] Error obtener broche', ['error' => $e->getMessage()]);
                                }
                            }
                        }
                    }
                }
            }

            // Cargar talla_colores manualmente para cada prenda
            if ($pedido->prendas) {
                foreach ($pedido->prendas as $prenda) {
                    $tallaColores = \DB::table('prenda_pedido_talla_colores as ptc')
                        ->join('prenda_pedido_tallas as pt', 'ptc.prenda_pedido_talla_id', '=', 'pt.id')
                        ->where('pt.prenda_pedido_id', $prenda->id)
                        ->select([
                            'ptc.id',
                            'ptc.prenda_pedido_talla_id',
                            'pt.genero',
                            'pt.talla',
                            'ptc.tela_id',
                            'ptc.tela_nombre',
                            'ptc.color_id',
                            'ptc.color_nombre',
                            'ptc.cantidad'
                        ])
                        ->get()
                        ->toArray();

                    $prenda->talla_colores = $tallaColores;

                    \Log::info('[PedidoQueryController] talla_colores cargados para prenda ' . $prenda->id, [
                        'cantidad' => count($tallaColores)
                    ]);
                }
            }

            // Transformar EPPs para incluir imágenes
            $eppsList = [];
            if ($pedido->epps) {
                foreach ($pedido->epps as $pedidoEpp) {
                    $epp = $pedidoEpp->epp;

                    if (!$epp) {
                        continue;
                    }

                    $imagenes = $this->obtenerImagenesEpp($pedidoEpp->id);

                    $eppsList[] = [
                        'id' => $pedidoEpp->id,
                        'epp_id' => $pedidoEpp->epp_id,
                        'nombre' => $epp->nombre_completo ?? $epp->nombre ?? '',
                        'nombre_completo' => $epp->nombre_completo ?? $epp->nombre ?? '',
                        'cantidad' => $pedidoEpp->cantidad ?? 0,
                        'observaciones' => $pedidoEpp->observaciones ?? '',
                        'imagen' => !empty($imagenes) ? $imagenes[0] : null,
                        'imagenes' => $imagenes,
                    ];
                }
            }

            $datosRespuesta = $pedido->toArray();
            $datosRespuesta['epps_transformados'] = $eppsList;

            if (!empty($datosRespuesta['prendas'])) {
                foreach ($datosRespuesta['prendas'] as $idx => $prenda) {
                    if (!empty($prenda['procesos'])) {
                        \Log::info('[obtenerDatosEdicion] Prenda ' . $idx . ' tiene procesos:', [
                            'prenda_id' => $prenda['id'],
                            'procesos_count' => count($prenda['procesos']),
                            'primer_proceso_keys' => array_keys($prenda['procesos'][0])
                        ]);

                        if (isset($prenda['procesos'][0]['tipo_proceso'])) {
                            \Log::info('[obtenerDatosEdicion] tipoProceso encontrado:', $prenda['procesos'][0]['tipo_proceso']);
                        } elseif (isset($prenda['procesos'][0]['tipoProceso'])) {
                            \Log::info('[obtenerDatosEdicion] tipoProceso (camelCase) encontrado:', $prenda['procesos'][0]['tipoProceso']);
                        } else {
                            \Log::warning('[obtenerDatosEdicion] NO SE ENCONTRÓ tipoProceso en proceso');
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $datosRespuesta
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('[PedidoQueryController] Pedido no encontrado para edición', ['pedido_id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('[PedidoQueryController] Error obtener datos para edición: ' . $e->getMessage(), [
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
     * GET /pedidos-public/{pedidoId}/ancho-metraje-prenda/{prendaId}
     */
    public function obtenerAnchoMetrajePrendaPublico($pedidoId, $prendaId)
    {
        try {
            $pedido = \App\Models\PedidoProduccion::find($pedidoId);
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            $anchoGeneral = \App\Models\PedidoAnchoGeneral::where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->latest('created_at')
                ->first();

            $metrajesPorColor = \App\Models\PedidoMetrajeColor::where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->latest('created_at')
                ->get();

            $tipoModo = null;
            if ($anchoGeneral && $anchoGeneral->tipo_modo) {
                $tipoModo = $anchoGeneral->tipo_modo;
            } elseif ($metrajesPorColor->isNotEmpty() && $metrajesPorColor->first()->tipo_modo) {
                $tipoModo = $metrajesPorColor->first()->tipo_modo;
            }

            $response = [
                'success' => true,
                'ancho' => $anchoGeneral ? $anchoGeneral->ancho : null,
                'metraje' => $anchoGeneral ? $anchoGeneral->metraje : null,
                'contenido_mano' => $anchoGeneral ? $anchoGeneral->contenido_mano : null,
                'tipo_modo' => $tipoModo,
                'data' => []
            ];

            if ($metrajesPorColor->isNotEmpty()) {
                $response['data'] = $metrajesPorColor->map(fn($item) => [
                    'color' => $item->color,
                    'metraje' => $item->metraje,
                    'tipo_modo' => $item->tipo_modo ?? 'color'
                ])->toArray();
            }

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::error('Error en obtenerAnchoMetrajePrendaPublico:', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ancho y metraje'
            ], 500);
        }
    }

    /**
     * GET /asesores/prendas-pedido/{prendaPedidoId}/fotos
     *
     * @deprecated Pendiente refactorización a DDD
     */
    public function obtenerFotosPrendaPedido($prendaPedidoId): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Esta funcionalidad está siendo refactorizada a DDD'
        ], 501);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Obtiene imágenes de un pedido_epp con rutas normalizadas.
     */
    private function obtenerImagenesEpp(int $pedidoEppId): array
    {
        try {
            $imagenesData = \DB::table('pedido_epp_imagenes')
                ->where('pedido_epp_id', $pedidoEppId)
                ->orderBy('orden', 'asc')
                ->get(['ruta_web', 'ruta_original', 'principal', 'orden']);

            if ($imagenesData->isEmpty()) {
                return [];
            }

            $imagenes = [];
            foreach ($imagenesData as $img) {
                $ruta = $img->ruta_web ?? $img->ruta_original;

                if (empty($ruta)) {
                    continue;
                }

                if (!str_starts_with($ruta, '/storage/')) {
                    $ruta = str_starts_with($ruta, 'storage/') ? '/' . $ruta : '/storage/' . $ruta;
                }

                $imagenes[] = [
                    'ruta_webp' => $ruta,
                    'ruta_original' => $ruta,
                    'ruta_web' => $ruta,
                    'principal' => $img->principal ?? false,
                    'orden' => $img->orden ?? 0,
                ];
            }

            return $imagenes;

        } catch (\Exception $e) {
            \Log::error('[PedidoQueryController] Error obtener imágenes de EPP', [
                'pedido_epp_id' => $pedidoEppId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Obtiene consecutivos de recibos para una prenda específica.
     */
    private function obtenerConsecutivosPrenda(int $pedidoId, int $prendaId): ?array
    {
        try {
            \Log::info('[PedidoQueryController] Buscando consecutivos para prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId
            ]);

            $consecutivos = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedidoId)
                ->where(function ($query) use ($prendaId) {
                    $query->where('prenda_id', $prendaId)
                          ->orWhereNull('prenda_id');
                })
                ->select([
                    'id', 'pedido_produccion_id', 'prenda_id', 'tipo_recibo',
                    'consecutivo_actual', 'consecutivo_inicial', 'activo',
                    'marcar_plooter', 'color_costura', 'estado', 'area',
                    'notas', 'created_at', 'updated_at'
                ])
                ->get();

            $parciales = \Illuminate\Support\Facades\DB::table('pedidos_parciales')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->whereNull('deleted_at')
                ->select([
                    'id', 'pedido_produccion_id',
                    'prenda_pedido_id as prenda_id', 'tipo_recibo',
                    'consecutivo_actual', 'consecutivo_inicial', 'activo',
                    'estado', 'notas', 'created_at', 'updated_at',
                    \Illuminate\Support\Facades\DB::raw("'PARCIAL' as origen")
                ])
                ->get();

            \Log::info('[PedidoQueryController] Parciales encontrados en BD', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'total_parciales' => $parciales->count(),
                'datos_parciales' => $parciales->toArray()
            ]);

            \Log::info('[PedidoQueryController] Consecutivos encontrados en BD', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'total_encontrados' => $consecutivos->count(),
                'datos_crudos' => $consecutivos->toArray()
            ]);

            if ($consecutivos->isEmpty() && $parciales->isEmpty()) {
                \Log::info('[PedidoQueryController] No hay consecutivos ni parciales para prenda', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prendaId
                ]);
                return null;
            }

            $recibos = [
                'COSTURA' => null,
                'ESTAMPADO' => null,
                'BORDADO' => null,
                'DTF' => null,
                'SUBLIMADO' => null,
                'REFLECTIVO' => null,
                'COSTURA-BODEGA' => null
            ];

            // Priorizar registro base (notas sin 'parcial_id:')
            // Si todos son anexos, usar el de menor consecutivo_actual
            $agrupados = [];
            foreach ($consecutivos as $c) {
                $tipo = $c->tipo_recibo;
                if (!isset($agrupados[$tipo])) {
                    $agrupados[$tipo] = [];
                }
                $agrupados[$tipo][] = $c;
            }

            foreach ($agrupados as $tipo => $items) {
                if (!array_key_exists($tipo, $recibos)) {
                    continue;
                }

                $base = collect($items)->first(function ($item) {
                    $notas = (string)($item->notas ?? '');
                    return stripos($notas, 'parcial_id:') === false;
                });

                if ($base) {
                    $recibos[$tipo] = [
                        'consecutivo_actual' => $base->consecutivo_actual,
                        'activo' => $base->activo,
                        'created_at' => $base->created_at,
                        'tipo_recibo' => $base->tipo_recibo,
                        'notas' => $base->notas
                    ];
                    continue;
                }

                $menor = collect($items)
                    ->filter(fn($item) => !empty($item->consecutivo_actual))
                    ->sortBy(fn($item) => (int)$item->consecutivo_actual)
                    ->first();

                if ($menor) {
                    $recibos[$tipo] = [
                        'consecutivo_actual' => $menor->consecutivo_actual,
                        'activo' => $menor->activo,
                        'created_at' => $menor->created_at,
                        'tipo_recibo' => $menor->tipo_recibo,
                        'notas' => $menor->notas
                    ];
                }
            }

            $recibos['parciales'] = $parciales->toArray();

            \Log::info('[PedidoQueryController] Consecutivos estructurados para prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'recibos' => $recibos,
                'total_parciales' => $parciales->count()
            ]);

            return $recibos;

        } catch (\Exception $e) {
            \Log::error('[PedidoQueryController] Error obteniendo consecutivos de prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
