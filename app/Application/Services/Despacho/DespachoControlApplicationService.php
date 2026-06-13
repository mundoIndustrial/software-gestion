<?php

namespace App\Application\Services\Despacho;

use App\Application\Pedidos\Despacho\DTOs\ControlEntregasDTO;
use App\Application\Pedidos\Despacho\Services\DespachoEstadoService;
use App\Application\Pedidos\Despacho\UseCases\GuardarDespachoUseCase;
use App\Application\Pedidos\Despacho\UseCases\ObtenerFilasDespachoUseCase;
use App\Infrastructure\Services\Despacho\DespachoRowHashService;
use App\Events\DespachoPedidoActualizado;
use App\Models\BodegaNota;
use App\Models\DespachoComprobante;
use App\Models\DespachoComprobanteFila;
use App\Models\DespachoAjusteDetalle;
use App\Models\DesparChoParcialesModel;
use App\Models\PedidoObservacionesDespacho;
use App\Models\PedidoProduccion;
use App\Models\PrendaEntrega;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class DespachoControlApplicationService
{
    public function __construct(
        private readonly ObtenerFilasDespachoUseCase $obtenerFilas,
        private readonly GuardarDespachoUseCase $guardarDespacho,
        private readonly DespachoEstadoService $despachoEstadoService,
    ) {
    }

    public function obtenerListadoIndex(string $search, ?int $asesorId = null): array
    {
        $states = ['Pendiente', 'En Ejecucion', 'No iniciado', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'DEVUELTO_A_ASESORA', 'pendiente_cartera', 'RECHAZADO_CARTERA'];

        // Obtener lista de asesoras con pedidos en despacho
        $asesores = User::whereHas('pedidosAsesora', function ($q) use ($states) {
            $q->whereIn('estado', $states)
              ->whereNotNull('numero_pedido')
              ->where('numero_pedido', '!=', '');
        })->withCount(['pedidosAsesora' => function ($q) use ($states) {
            $q->whereIn('estado', $states)
              ->whereNotNull('numero_pedido')
              ->where('numero_pedido', '!=', '');
        }])->get(['id', 'name']);

        $query = PedidoProduccion::query()
            ->whereIn('estado', $states)
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->with(['asesora'])
            ->orderByRaw('COALESCE((SELECT MAX(created_at) FROM pedido_anexos_historial WHERE pedido_produccion_id = pedidos_produccion.id), pedidos_produccion.created_at) DESC')
            ->orderByDesc('pedidos_produccion.created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('numero_pedido', 'like', "%{$search}%")
                    ->orWhere('cliente', 'like', "%{$search}%");
            });
        }

        if ($asesorId) {
            $query->where('asesor_id', $asesorId);
        }

        $pedidos = $query->paginate(20)->withQueryString();

        $pedidos->getCollection()->transform(function ($pedido) {
            $pedido->estado_entrega = $this->despachoEstadoService->obtenerEstadoEntrega($pedido->id);

            $fechaEntrega = \App\Models\PrendaPedido::where('pedido_produccion_id', $pedido->id)
                ->whereHas('entrega', function ($q) {
                    $q->where('entregado', true)
                        ->whereNotNull('fecha_entrega');
                })
                ->with(['entrega' => function ($q) {
                    $q->where('entregado', true)
                        ->whereNotNull('fecha_entrega')
                        ->orderBy('fecha_entrega', 'desc');
                }])
                ->first();

            $pedido->fecha_entrega_prendas = $fechaEntrega && $fechaEntrega->entrega
                ? $fechaEntrega->entrega->fecha_entrega->format('d/m/Y h:i A')
                : '-';

            return $pedido;
        });

        return [
            'pedidos' => $pedidos,
            'search' => $search,
            'asesores' => $asesores,
            'asesorId' => $asesorId,
        ];
    }

    public function obtenerDetallePedido(PedidoProduccion $pedido): array
    {
        $pedido->load(['cliente', 'prendas.pedidoProduccion', 'epps.epp']);

        $filas = $this->obtenerFilas->obtenerTodas($pedido->id);

        $prendas = $filas->where('tipo', 'prenda');
        $epps = $filas->where('tipo', 'epp');

        $despachos = DesparChoParcialesModel::where('pedido_id', $pedido->id)
            ->whereNotNull('fecha_entrega')
            ->get()
            ->keyBy(function ($item) {
                return ($item->tipo_item === 'epp' ? 'epp-' : '') . $item->item_id
                    . ($item->talla_id ? '-' . $item->talla_id : '')
                    . ($item->talla_color_id ? '-' . $item->talla_color_id : '');
            });

        Log::info('[DespachoController] Datos de despacho cargados', [
            'pedido_id' => $pedido->id,
            'despachos_count' => $despachos->count(),
            'despachos_data' => $despachos->toArray(),
        ]);

        [$pendientesBodegueroText, $observacionesAsesoraText] = $this->buildTextosPendientesYAsesora($pedido);

        $ajustesActivos = $this->obtenerAjustesActivosPorPedido($pedido->id);
        $ajustesHistorial = $this->obtenerAjustesHistorialPorPedido($pedido->id);

        return compact('pedido', 'prendas', 'epps', 'despachos', 'pendientesBodegueroText', 'observacionesAsesoraText', 'ajustesActivos', 'ajustesHistorial');
    }

    public function obtenerAjustesHistorialPorPedido(int $pedidoId): array
    {
        $ajustes = DespachoAjusteDetalle::query()
            ->where('pedido_produccion_id', $pedidoId)
            ->orderBy('revision')
            ->orderBy('id')
            ->get();

        $map = [];
        foreach ($ajustes as $ajuste) {
            $key = strtolower((string) $ajuste->tipo_item)
                . '|' . ((int) $ajuste->item_id)
                . '|' . ((int) $ajuste->talla_id)
                . '|' . ((int) ($ajuste->talla_color_id ?? 0))
                . '|' . strtoupper((string) ($ajuste->genero ?? ''));

            $map[$key] ??= [];
            $map[$key][] = [
                'id' => (int) $ajuste->id,
                'revision' => (int) $ajuste->revision,
                'cantidad_base' => (int) $ajuste->cantidad_base,
                'cantidad_ajustada' => (int) $ajuste->cantidad_ajustada,
                'diferencia' => (int) $ajuste->diferencia,
                'estado' => (string) $ajuste->estado,
            ];
        }

        return $map;
    }

    public function obtenerAjustesActivosPorPedido(int $pedidoId): array
    {
        $ajustes = DespachoAjusteDetalle::query()
            ->where('pedido_produccion_id', $pedidoId)
            ->where('estado', 'pendiente')
            ->orderByDesc('revision')
            ->get();

        $map = [];
        foreach ($ajustes as $ajuste) {
            $key = strtolower((string) $ajuste->tipo_item)
                . '|' . ((int) $ajuste->item_id)
                . '|' . ((int) $ajuste->talla_id)
                . '|' . ((int) ($ajuste->talla_color_id ?? 0))
                . '|' . strtoupper((string) ($ajuste->genero ?? ''));

            if (!isset($map[$key])) {
                $map[$key] = [
                    'id' => $ajuste->id,
                    'row_hash' => $ajuste->row_hash,
                    'revision' => $ajuste->revision,
                    'cantidad_base' => (int) $ajuste->cantidad_base,
                    'cantidad_ajustada' => (int) $ajuste->cantidad_ajustada,
                    'diferencia' => (int) $ajuste->diferencia,
                ];
            }
        }

        return $map;
    }

    /**
     * @param array{tipo_item: string, item_id: int, talla_id?: int|null, talla_color_id?: int|null, genero?: string|null, cantidad_original: int, cantidad_ajustada: int, motivo?: string|null} $validated
     */
    public function guardarAjusteCantidad(PedidoProduccion $pedido, array $validated): array
    {
        $tipoItem = (string) $validated['tipo_item'];
        $itemId = (int) $validated['item_id'];
        $tallaId = isset($validated['talla_id']) ? (int) $validated['talla_id'] : 0;
        $tallaColorId = isset($validated['talla_color_id']) ? (int) $validated['talla_color_id'] : 0;
        $genero = strtoupper(trim((string) ($validated['genero'] ?? '')));
        $cantidadOriginal = (int) $validated['cantidad_original'];
        $cantidadAjustada = (int) $validated['cantidad_ajustada'];

        if ($cantidadAjustada < 0) {
            throw new \InvalidArgumentException('La cantidad ajustada no puede ser menor a 0.');
        }
        if ($cantidadAjustada > $cantidadOriginal) {
            throw new \InvalidArgumentException('La cantidad ajustada no puede superar la cantidad original.');
        }

        $rowHash = DespachoRowHashService::generar(
            $pedido->id,
            $tipoItem,
            $itemId,
            $tallaId,
            $tallaColorId,
            $genero
        );

        $ultimo = DespachoAjusteDetalle::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->where('row_hash', $rowHash)
            ->orderByDesc('revision')
            ->first();

        $base = $ultimo ? (int) $ultimo->cantidad_ajustada : $cantidadOriginal;

        if ($ultimo && $ultimo->estado === 'pendiente') {
            $ultimo->update(['estado' => 'descartada']);
        }

        $revision = ((int) ($ultimo?->revision ?? 0)) + 1;

        $ajuste = DespachoAjusteDetalle::create([
            'pedido_produccion_id' => $pedido->id,
            'row_hash' => $rowHash,
            'tipo_item' => $tipoItem,
            'item_id' => $itemId,
            'talla_id' => $tallaId,
            'talla_color_id' => $tallaColorId,
            'genero' => $genero !== '' ? $genero : null,
            'revision' => $revision,
            'cantidad_base' => $base,
            'cantidad_ajustada' => $cantidadAjustada,
            'diferencia' => max(0, $base - $cantidadAjustada),
            'estado' => 'pendiente',
            'motivo' => $validated['motivo'] ?? null,
            'creado_por' => auth()->id() ?? 0,
        ]);

        return [
            'success' => true,
            'message' => 'Ajuste guardado correctamente',
            'ajuste' => [
                'id' => $ajuste->id,
                'row_hash' => $ajuste->row_hash,
                'revision' => $ajuste->revision,
                'cantidad_base' => (int) $ajuste->cantidad_base,
                'cantidad_ajustada' => (int) $ajuste->cantidad_ajustada,
                'diferencia' => (int) $ajuste->diferencia,
                'estado' => $ajuste->estado,
            ],
        ];
    }

    public function obtenerDatosPrint(PedidoProduccion $pedido): array
    {
        $pedido->load(['cliente', 'prendas.pedidoProduccion', 'epps.epp']);

        $filas = $this->obtenerFilas->obtenerTodas($pedido->id);
        $prendas = $filas->where('tipo', 'prenda');
        $epps = $filas->where('tipo', 'epp');
        $comprobante = $this->obtenerOCrearComprobante($pedido);

        [$pendientesBodegueroText, $observacionesAsesoraText] = $this->buildTextosPendientesYAsesora($pedido);

        $tableRowsOriginal = $this->agruparFilasParaComprobante($filas);
        $tableRows = $this->obtenerFilasParaComprobante($comprobante, $filas);

        return compact(
            'pedido',
            'filas',
            'prendas',
            'epps',
            'comprobante',
            'pendientesBodegueroText',
            'observacionesAsesoraText',
            'tableRowsOriginal',
            'tableRows'
        );
    }

    private function obtenerOCrearComprobante(PedidoProduccion $pedido): DespachoComprobante
    {
        return DB::transaction(function () use ($pedido) {
            $clienteModel = $pedido->cliente;
            $clienteNombre = is_object($clienteModel)
                ? ($clienteModel->nombre ?? $pedido->getAttribute('cliente'))
                : $pedido->getAttribute('cliente');
            $clienteEmail = is_object($clienteModel)
                ? ($clienteModel->email ?? null)
                : null;
            return DespachoComprobante::firstOrCreate(
                ['pedido_produccion_id' => $pedido->id],
                [
                    'usuario_id' => auth()->id(),
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente_nombre' => $clienteNombre,
                    'cliente_email' => $clienteEmail,
                    'comp_factura_no' => $pedido->orden_compra,
                    'fecha_entrega' => null,
                    'observaciones' => null,
                    'snapshot' => [
                        'pedido_id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'cliente_nombre' => $clienteNombre,
                        'cliente_email' => $clienteEmail,
                        'comp_factura_no' => $pedido->orden_compra,
                        'observaciones' => $pedido->observaciones,
                        'fecha_entrega' => null,
                        'firmas' => [],
                        'generado_en' => now()->toDateTimeString(),
                    ],
                ]
            );
        });
    }

    public function guardarObservacionComprobante(PedidoProduccion $pedido, string $observaciones, ?string $fechaEntrega = null, array $firmas = [], ?array $filas = null): DespachoComprobante
    {
        return DB::transaction(function () use ($pedido, $observaciones, $fechaEntrega, $firmas, $filas) {
            $comprobante = $this->obtenerOCrearComprobante($pedido);
            $snapshot = (array) ($comprobante->snapshot ?? []);
            if (!isset($snapshot['firmas']) || !is_array($snapshot['firmas'])) {
                $snapshot['firmas'] = [];
            }

            $firmasLimpias = array_filter($firmas, function ($firma) {
                return is_array($firma) && (
                    trim((string) ($firma['texto'] ?? '')) !== ''
                    || trim((string) ($firma['imagen'] ?? '')) !== ''
                );
            });

            $comprobante->update([
                'observaciones' => $observaciones,
                'fecha_entrega' => $fechaEntrega,
                'snapshot' => array_merge($snapshot, [
                    'observaciones' => $observaciones,
                    'fecha_entrega' => $fechaEntrega,
                    'firmas' => $firmasLimpias,
                    'filas_personalizadas' => $filas !== null,
                    'actualizado_en' => now()->toDateTimeString(),
                ]),
            ]);

            if ($filas !== null) {
                $this->guardarFilasComprobante($comprobante, $filas);
            }

            return $comprobante->fresh();
        });
    }

    private function obtenerFilasComprobante(DespachoComprobante $comprobante): array
    {
        $filasGuardadas = $comprobante->filas()
            ->orderBy('orden')
            ->get(['orden', 'cantidad', 'articulo']);

        if ($filasGuardadas->isNotEmpty()) {
            return $filasGuardadas
                ->map(function (DespachoComprobanteFila $fila): array {
                    return [
                        'cantidad' => (int) $fila->cantidad,
                        'articulo' => (string) $fila->articulo,
                    ];
                })
                ->values()
                ->all();
        }

        return [];
    }

    private function guardarFilasComprobante(DespachoComprobante $comprobante, array $filas): void
    {
        $comprobante->filas()->delete();

        $filasNormalizadas = collect($filas)
            ->map(function ($fila, int $index): array {
                $cantidad = (int) data_get($fila, 'cantidad', 0);
                $articulo = trim((string) data_get($fila, 'articulo', ''));

                return [
                    'orden' => $index + 1,
                    'cantidad' => $cantidad,
                    'articulo' => $articulo,
                ];
            })
            ->values();

        foreach ($filasNormalizadas as $fila) {
            $comprobante->filas()->create($fila);
        }
    }

    private function obtenerFilasParaComprobante(DespachoComprobante $comprobante, Collection $filas): array
    {
        $snapshot = (array) ($comprobante->snapshot ?? []);
        $usaFilasPersonalizadas = (bool) data_get($snapshot, 'filas_personalizadas', false);

        if ($usaFilasPersonalizadas) {
            return $this->obtenerFilasComprobante($comprobante);
        }

        $filasGuardadas = $this->obtenerFilasComprobante($comprobante);
        return $filasGuardadas !== [] ? $filasGuardadas : $this->agruparFilasParaComprobante($filas);
    }

    /**
     * Agrupa las filas para que el comprobante muestre una sola lÃƒÂ­nea por prenda/EPP.
     *
     * @param Collection<int, \App\Application\Pedidos\Despacho\DTOs\FilaDespachoDTO> $filas
     * @return array<int, array{cantidad:int, articulo:string}>
     */
    private function agruparFilasParaComprobante(Collection $filas): array
    {
        return $filas
            ->groupBy(function ($fila) {
                $tipo = (string) data_get($fila, 'tipo', '');
                $descripcion = trim((string) (
                    data_get($fila, 'objetoPrenda.nombre_prenda')
                    ?: data_get($fila, 'objetoEpp.nombre_completo')
                    ?: data_get($fila, 'objetoPrenda.nombre')
                    ?: data_get($fila, 'descripcion')
                    ?: ''
                ));

                return implode('|', [$tipo, mb_strtolower($descripcion)]);
            })
            ->map(function (Collection $grupo) {
                $primeraFila = $grupo->first();
                $tipo = (string) data_get($primeraFila, 'tipo', '');

                $descripcionBase = $this->construirDescripcionComprobante($primeraFila);

                $cantidadTotal = (int) $grupo->sum(function ($fila) {
                    return (int) data_get($fila, 'cantidadTotal', 0);
                });

                $detalleTallas = $grupo
                    ->groupBy(function ($fila) {
                        return trim((string) data_get($fila, 'talla', ''));
                    })
                    ->map(function (Collection $items, string $talla) {
                        return [
                            'talla' => $talla,
                            'cantidad' => (int) $items->sum(function ($item) {
                                return (int) data_get($item, 'cantidadTotal', 0);
                            }),
                        ];
                    })
                    ->filter(function (array $item) {
                        return $item['talla'] !== '' && $item['talla'] !== 'Ã¢â‚¬â€';
                    })
                    ->values()
                    ->map(function (array $item) {
                        return $item['talla'] . '-' . $item['cantidad'];
                    })
                    ->implode(', ');

                if ($detalleTallas !== '') {
                    $descripcionBase = trim($descripcionBase . ' ' . $detalleTallas);
                }

                return [
                    'cantidad' => $cantidadTotal,
                    'articulo' => $descripcionBase,
                ];
            })
            ->values()
            ->all();
    }

    private function construirDescripcionComprobante(object|array $fila): string
    {
        $tipo = (string) data_get($fila, 'tipo', '');

        if ($tipo !== 'prenda') {
            return trim((string) (
                data_get($fila, 'objetoEpp.nombre_completo')
                ?: data_get($fila, 'objetoEpp.nombre')
                ?: data_get($fila, 'descripcion')
                ?: ''
            ));
        }

        $segmentos = [];

        $nombre = trim((string) (
            data_get($fila, 'objetoPrenda.nombre')
            ?: data_get($fila, 'objetoPrenda.nombre_prenda')
            ?: data_get($fila, 'descripcion')
            ?: ''
        ));
        if ($nombre !== '') {
            $segmentos[] = $nombre;
        }

        $descripcion = trim(strip_tags(html_entity_decode((string) (
            data_get($fila, 'objetoPrenda.descripcion')
            ?: ''
        ), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($descripcion !== '') {
            $segmentos[] = '(' . $descripcion . ')';
        }

        $procesos = data_get($fila, 'objetoPrenda.procesos', []);
        if (is_iterable($procesos)) {
            foreach ($procesos as $proc) {
                $descripcionProceso = trim(strip_tags(html_entity_decode((string) (
                    data_get($proc, 'descripcion')
                    ?: data_get($proc, 'detalle')
                    ?: ''
                ), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

                $nombreProceso = trim((string) (
                    data_get($proc, 'nombre')
                    ?: data_get($proc, 'tipo_proceso')
                    ?: ''
                ));

                if ($descripcionProceso === '' && $nombreProceso === '') {
                    continue;
                }

                if ($descripcionProceso !== '' && $nombreProceso !== '') {
                    $descripcionProceso = preg_replace(
                        '/^' . preg_quote($nombreProceso, '/') . '\s+/iu',
                        '',
                        $descripcionProceso
                    ) ?? $descripcionProceso;
                }

                $textoProceso = trim($descripcionProceso !== '' ? $descripcionProceso : $nombreProceso);

                if ($textoProceso !== '') {
                    $segmentos[] = '(' . $textoProceso . ')';
                }
            }
        }

        if ($segmentos === []) {
            return trim((string) (
                data_get($fila, 'objetoPrenda.nombre_prenda')
                ?: data_get($fila, 'descripcion')
                ?: ''
            ));
        }

        return implode(', ', $segmentos);
    }

    /**
     * @param array{despachos: array<int, mixed>, cliente_empresa?: string|null, fecha_hora?: string|null} $validated
     */
    public function guardarControlEntregas(PedidoProduccion $pedido, array $validated): array
    {
        $control = new ControlEntregasDTO(
            pedidoId: $pedido->id,
            numeroPedido: $pedido->numero_pedido,
            despachos: $validated['despachos'],
            clienteEmpresa: $validated['cliente_empresa'] ?? '',
            fechaHora: $validated['fecha_hora'] ?? now()->toDateTimeString(),
        );

        return $this->guardarDespacho->ejecutar($control);
    }

    public function obtenerDespachos(PedidoProduccion $pedido): array
    {
        $despachos = DesparChoParcialesModel::where('pedido_id', $pedido->id)
            ->get()
            ->map(function ($despacho) {
                return [
                    'id' => $despacho->id,
                    'tipo_item' => $despacho->tipo_item,
                    'item_id' => $despacho->item_id,
                    'talla_id' => $despacho->talla_id,
                    'talla_color_id' => $despacho->talla_color_id,
                    'genero' => $despacho->genero,
                    'observaciones' => $despacho->observaciones,
                    'entregado' => $despacho->entregado,
                    'fecha_entrega' => $despacho->fecha_entrega?->toISOString(),
                ];
            });

        return [
            'success' => true,
            'despachos' => $despachos,
        ];
    }

    public function obtenerFacturaDatos(PedidoProduccion $pedido): array
    {
        $facturaService = new \App\Infrastructure\Services\Pedidos\FacturaPedidoService();
        return $facturaService->obtenerDatosFactura($pedido->id);
    }

    /**
     * @param array{tipo_item: string, item_id: int, talla_id?: int|null, talla_color_id?: int|null} $validated
     */
    public function marcarEntregado(PedidoProduccion $pedido, array $validated): array
    {
        $tallaId = $validated['talla_id'] ?? null;
        $tallaColorId = $validated['talla_color_id'] ?? null;

        $despacho = DesparChoParcialesModel::where('pedido_id', $pedido->id)
            ->where('tipo_item', $validated['tipo_item'])
            ->where('item_id', $validated['item_id'])
            ->where('entregado', true)
            ->when($tallaId, function ($q) use ($tallaId) {
                $q->where('talla_id', $tallaId);
            })
            ->when($tallaColorId, function ($q) use ($tallaColorId) {
                $q->where('talla_color_id', $tallaColorId);
            })
            ->first();

        Log::info('[DespachoController] BÃƒÂºsqueda de despacho', [
            'pedido_id' => $pedido->id,
            'tipo_item' => $validated['tipo_item'],
            'item_id' => $validated['item_id'],
            'talla_id' => $tallaId,
            'despacho_encontrado' => $despacho ? 'SI' : 'NO',
            'despacho_id' => $despacho?->id,
        ]);

        if (!$despacho) {
            Log::info('[DespachoController] Creando registro de despacho automÃƒÂ¡ticamente', [
                'pedido_id' => $pedido->id,
                'tipo_item' => $validated['tipo_item'],
                'item_id' => $validated['item_id'],
                'talla_id' => $tallaId,
            ]);

            $despacho = DesparChoParcialesModel::create([
                'pedido_id' => $pedido->id,
                'tipo_item' => $validated['tipo_item'],
                'item_id' => $validated['item_id'],
                'talla_id' => $validated['talla_id'] ?? null,
                'talla_color_id' => $validated['talla_color_id'] ?? null,
                'entregado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('[DespachoController] Registro de despacho creado', [
                'despacho_id' => $despacho->id,
            ]);
        }

        $despacho->update([
            'entregado' => true,
            'fecha_entrega' => now(),
        ]);

        // Crear novedad al marcar como entregado
        $usuario = auth()->user();
        $userName = $usuario ? $usuario->name : 'Sistema';
        $fecha = now()->format('Y-m-d H:i:s');
        $tipoItem = ucfirst($validated['tipo_item']);
        $novedad = "[Bodega: $userName - $fecha] $tipoItem marcada como entregada";
        
        // Agregar novedad al pedido
        if ($pedido->novedades) {
            $pedido->novedades .= "\n\n" . $novedad;
        } else {
            $pedido->novedades = $novedad;
        }
        $pedido->save();

        Log::info('[DespachoController] Novedad agregada al marcar como entregado', [
            'pedido_id' => $pedido->id,
            'usuario' => $userName,
            'novedad' => $novedad,
        ]);

        $this->verificarYActualizarEstadoPedido($pedido);

        return [
            'success' => true,
            'message' => 'item marcado como entregado',
            'despacho_id' => $despacho->id,
            'fecha_entrega' => $despacho->fresh()->fecha_entrega?->format('Y-m-d'),
        ];
    }

    public function obtenerEstadoEntregas(PedidoProduccion $pedido): array
    {
        $entregas = DesparChoParcialesModel::where('pedido_id', $pedido->id)
            ->where('entregado', true)
            ->whereNotNull('fecha_entrega')
            ->get()
            ->map(function ($entrega) {
                return [
                    'tipo_item' => $entrega->tipo_item,
                    'item_id' => $entrega->item_id,
                    'talla_id' => $entrega->talla_id,
                    'talla_color_id' => $entrega->talla_color_id,
                    'entregado' => true,
                    'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d'),
                ];
            });

        return [
            'success' => true,
            'entregas' => $entregas,
        ];
    }

    /**
     * @param array{tipo_item: string, item_id: int, talla_id?: int|null, talla_color_id?: int|null} $validated
     */
    public function deshacerEntregado(PedidoProduccion $pedido, array $validated): array
    {
        $tallaId = $validated['talla_id'] ?? null;
        $tallaColorId = $validated['talla_color_id'] ?? null;

        $despacho = DesparChoParcialesModel::where('pedido_id', $pedido->id)
            ->where('tipo_item', $validated['tipo_item'])
            ->where('item_id', $validated['item_id'])
            ->where('entregado', true)
            ->when($tallaId, function ($q) use ($tallaId) {
                $q->where('talla_id', $tallaId);
            })
            ->when($tallaColorId, function ($q) use ($tallaColorId) {
                $q->where('talla_color_id', $tallaColorId);
            })
            ->first();

        Log::info('[DespachoController] Busqueda para deshacer', [
            'pedido_id' => $pedido->id,
            'tipo_item' => $validated['tipo_item'],
            'item_id' => $validated['item_id'],
            'talla_id' => $tallaId,
            'talla_color_id' => $tallaColorId,
            'despacho_encontrado' => $despacho ? 'SI' : 'NO',
            'despacho_id' => $despacho?->id,
            'entregado_actual' => $despacho?->entregado,
        ]);

        if (!$despacho) {
            return [
                'success' => false,
                'message' => 'No se encontro registro de entrega para deshacer',
                '_status' => 404,
            ];
        }

        $despacho->update([
            'entregado' => false,
            'fecha_entrega' => null,
            'usuario_id' => auth()->id(),
        ]);

        $estadoAnteriorPedido = $pedido->estado;
        if ($pedido->estado !== 'En Ejecucion') {
            $pedido->update([
                'estado' => 'En Ejecucion',
                'updated_at' => now(),
            ]);
        }

        // Crear novedad al deshacer entrega
        $usuario = auth()->user();
        $userName = $usuario ? $usuario->name : 'Sistema';
        $fecha = now()->format('Y-m-d H:i:s');
        $tipoItem = ucfirst($validated['tipo_item']);
        $novedad = "[Bodega: $userName - $fecha] Entrega de $tipoItem deshecha - VolviÃƒÂ³ a estado Pendiente";
        
        // Agregar novedad al pedido
        if ($pedido->novedades) {
            $pedido->novedades .= "\n\n" . $novedad;
        } else {
            $pedido->novedades = $novedad;
        }
        $pedido->save();

        Log::info('[DespachoController] Novedad agregada al deshacer entregado', [
            'pedido_id' => $pedido->id,
            'usuario' => $userName,
            'novedad' => $novedad,
        ]);

        Log::info('[DespachoController] Estado general del pedido ajustado por deshacer entregado', [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'estado_anterior' => $estadoAnteriorPedido,
            'estado_nuevo' => $pedido->fresh()->estado,
        ]);

        $this->verificarYActualizarEstadoPedido($pedido);

        return [
            'success' => true,
            'message' => 'Marcado como entregado deshecho correctamente',
        ];
    }

    public function entregarTodo(PedidoProduccion $pedido): array
    {
        DB::beginTransaction();

        try {
            $filas = $this->obtenerFilas->obtenerTodas($pedido->id);

            $itemsProcesados = 0;
            $itemsCreados = 0;

            foreach ($filas as $fila) {
                $tipoItem = $fila->tipo;
                $itemId = $fila->id;
                $tallaId = $fila->tallaId;
                $tallaColorId = $fila->talla_color_id ?? null;
                $genero = $fila->genero ?? null;

                if ($tipoItem === 'prenda' && $tallaId) {
                    $despacho = DesparChoParcialesModel::where('pedido_id', $pedido->id)
                        ->where('tipo_item', $tipoItem)
                        ->where('item_id', $itemId)
                        ->where('talla_id', $tallaId)
                        ->first();
                } else {
                    $despacho = DesparChoParcialesModel::where('pedido_id', $pedido->id)
                        ->where('tipo_item', $tipoItem)
                        ->where('item_id', $itemId)
                        ->when($tallaId, function ($q) use ($tallaId) {
                            $q->where('talla_id', $tallaId);
                        })
                        ->when($tallaColorId, function ($q) use ($tallaColorId) {
                            $q->where('talla_color_id', $tallaColorId);
                        })
                        ->first();
                }

                if (!$despacho) {
                    $despacho = DesparChoParcialesModel::create([
                        'pedido_id' => $pedido->id,
                        'tipo_item' => $tipoItem,
                        'item_id' => $itemId,
                        'talla_id' => $tallaId,
                        'talla_color_id' => $tallaColorId,
                        'genero' => $genero,
                        'entregado' => false,
                        'fecha_despacho' => now(),
                        'usuario_id' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $itemsCreados++;
                }

                $despacho->update([
                    'entregado' => true,
                    'fecha_entrega' => now(),
                    'updated_at' => now(),
                ]);

                // Si es prenda, tambiÃƒÂ©n actualizar PrendaEntrega para que se muestre la fecha en la vista
                if ($tipoItem === 'prenda' && $itemId) {
                    PrendaEntrega::updateOrCreate(
                        ['prenda_pedido_id' => $itemId],
                        [
                            'entregado' => true,
                            'fecha_entrega' => now(),
                            'usuario_id' => auth()->id(),
                        ]
                    );
                }

                $itemsProcesados++;
            }

            $estadoAnterior = $pedido->estado;
            $pedido->update([
                'estado' => 'Entregado',
                'updated_at' => now(),
            ]);

            // Crear novedad al entregar todo
            $usuario = auth()->user();
            $userName = $usuario ? $usuario->name : 'Sistema';
            $fecha = now()->format('Y-m-d H:i:s');
            $novedad = "[Bodega: $userName - $fecha] Pedido completamente entregado - $itemsProcesados items procesados";
            
            // Agregar novedad al pedido
            if ($pedido->novedades) {
                $pedido->novedades .= "\n\n" . $novedad;
            } else {
                $pedido->novedades = $novedad;
            }
            $pedido->save();

            Log::info('[DespachoController] Novedad agregada al entregar todo', [
                'pedido_id' => $pedido->id,
                'usuario' => $userName,
                'items_procesados' => $itemsProcesados,
                'novedad' => $novedad,
            ]);

            DB::commit();

            Log::info('[DespachoController] Pedido marcado como entregado completamente', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'estado_anterior' => $estadoAnterior,
                'items_procesados' => $itemsProcesados,
                'items_creados' => $itemsCreados,
            ]);

            event(new DespachoPedidoActualizado($pedido, [
                'action' => 'pedido_entregado_completo',
                'numero_pedido' => $pedido->numero_pedido,
                'nuevo_estado' => 'Entregado',
                'anterior_estado' => $estadoAnterior,
                'items_procesados' => $itemsProcesados,
                'usuario' => auth()->user()->name,
                'timestamp' => now()->toIso8601String(),
            ]));

            return [
                'success' => true,
                'message' => "Pedido #{$pedido->numero_pedido} marcado como entregado completamente ({$itemsProcesados} items procesados)",
                'items_procesados' => $itemsProcesados,
                'items_creados' => $itemsCreados,
                'estado_anterior' => $estadoAnterior,
                'nuevo_estado' => 'Entregado',
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    private function buildTextosPendientesYAsesora(PedidoProduccion $pedido): array
    {
        $rows = PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->orderByDesc('created_at')
            ->get();

        $bodegaRows = BodegaNota::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->orderByDesc('created_at')
            ->get();

        $observacionesAsesora = $rows
            ->filter(function ($row) {
                $rol = strtolower((string) ($row->usuario_rol ?? ''));
                return str_contains($rol, 'asesor');
            })
            ->values();

        $pendientesBodegueroText = $bodegaRows->count() === 0
            ? 'Sin observaciones'
            : $bodegaRows->map(function ($row) {
                $fechaISO = $row->updated_at ?: $row->created_at;
                $fecha = $fechaISO ? \Carbon\Carbon::parse($fechaISO)->format('d/m/Y H:i') : '';
                $contenido = (string) ($row->contenido ?? '');
                return $contenido . ($fecha ? (' - ' . $fecha) : '');
            })->implode("\n");

        $observacionesAsesoraText = $observacionesAsesora->count() === 0
            ? 'Sin observaciones'
            : $observacionesAsesora->map(function ($row) {
                $fechaISO = $row->updated_at ?: $row->created_at;
                $fecha = $fechaISO ? \Carbon\Carbon::parse($fechaISO)->format('d/m/Y H:i') : '';
                $contenido = (string) ($row->contenido ?? '');
                return $contenido . ($fecha ? (' - ' . $fecha) : '');
            })->implode("\n");

        return [$pendientesBodegueroText, $observacionesAsesoraText];
    }

    private function verificarYActualizarEstadoPedido(PedidoProduccion $pedido): void
    {
        try {
            $itemsPendientes = collect();

            $prendas = $pedido->prendas()->with(['tallas'])->get();
            foreach ($prendas as $prenda) {
                foreach ($prenda->tallas as $talla) {
                    $itemsPendientes->push([
                        'tipo' => 'prenda',
                        'item_id' => $talla->id,
                        'talla_id' => $talla->talla_id,
                    ]);
                }
            }

            $epps = $pedido->epps()->get();
            foreach ($epps as $epp) {
                $itemsPendientes->push([
                    'tipo' => 'epp',
                    'item_id' => $epp->id,
                    'talla_id' => null,
                ]);
            }

            $itemsEntregados = DesparChoParcialesModel::where('pedido_id', $pedido->id)
                ->where('entregado', true)
                ->count();

            $totalItems = $itemsPendientes->count();
            $itemsRestantes = $totalItems - $itemsEntregados;

            Log::info('[DespachoController] VerificaciÃƒÂ³n de estado del pedido', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'total_items' => $totalItems,
                'items_entregados' => $itemsEntregados,
                'items_restantes' => $itemsRestantes,
            ]);

            if ($itemsRestantes === 0 && $totalItems > 0) {
                $estadoAnterior = $pedido->estado;

                $pedido->update([
                    'estado' => 'Entregado',
                    'updated_at' => now(),
                ]);

                event(new DespachoPedidoActualizado($pedido, [
                    'accion' => 'estado_cambiado',
                    'nuevo_estado' => 'Entregado',
                    'anterior_estado' => $estadoAnterior,
                    'mensaje' => 'Pedido marcado como entregado',
                ]));

                Log::info('[Despacho] Pedido marcado como Entregado y evento WebSocket despacho disparado', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => 'Entregado',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[DespachoController] Error verificando estado del pedido', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
